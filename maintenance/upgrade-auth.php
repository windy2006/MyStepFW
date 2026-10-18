<?php
// CLI-only, idempotent upgrade. Backups must be outside the document root.
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__).'/';
require_once $root.'lib/myAuth.class.php';
$backup = $argv[1] ?? '';
if($backup === '' || !is_dir($backup) || strpos(realpath($backup).'/', realpath($root).'/') === 0) {
    fwrite(STDERR, "Usage: php maintenance/upgrade-auth.php /existing/private/backup-directory\n"); exit(1);
}
chmod($backup, 0700);
$setting = []; include $root.'config/config.php'; $global = $setting;
$setting = []; include $root.'app/CMS/config.php'; $cms = $setting;
$options = array_replace($global['db'], $cms['db']);
$connection = new mysqli($options['host'], $options['user'], $options['password'], $options['name']);
if($connection->connect_errno) throw new RuntimeException('Database connection failed');
$connection->set_charset('utf8mb4');
function authBackup($directory, $name, $data) {
    $path = $directory.'/'.$name;
    $handle = fopen($path, 'x');
    if($handle === false) throw new RuntimeException('Backup already exists; use a new backup directory');
    chmod($path, 0600); fwrite($handle, $data); fclose($handle);
}
$tables = [];
foreach(['sys_op', 'users'] as $suffix) {
    $table = $options['pre'].$suffix;
    if(!preg_match('/^\w+$/D', $table)) throw new RuntimeException('Invalid table name');
    $schema = $connection->query('SHOW CREATE TABLE `'.$table.'`')->fetch_row();
    $rows = $connection->query('SELECT * FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC);
    authBackup($backup, $table.'.json', json_encode(['schema'=>$schema[1], 'rows'=>$rows], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $tables[$table] = $suffix === 'sys_op' ? 'id' : 'user_id';
}
authBackup($backup, 'config.php', file_get_contents($root.'config/config.php'));
foreach($tables as $table => $id) {
    $column = $connection->query("SHOW COLUMNS FROM `".$table."` LIKE 'password'")->fetch_assoc();
    if(!preg_match('/^(?:var)?char\((\d+)\)$/i', $column['Type'], $length) || (int)$length[1] < 255) {
        $nullable = $column['Null'] === 'YES' ? 'NULL DEFAULT NULL' : 'NOT NULL';
        if(!$connection->query('ALTER TABLE `'.$table.'` MODIFY `password` varchar(255) '.$nullable." COMMENT '密码'")) throw new RuntimeException('Password column upgrade failed');
    }
    $rows = $connection->query('SELECT `'.$id.'`, password FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC);
    $update = $connection->prepare('UPDATE `'.$table.'` SET password=? WHERE `'.$id.'`=? AND password=?');
    $count = 0;
    foreach($rows as $row) {
        $old = $row['password']; $new = myAuth::protectLegacyHash($old);
        if($new === $old) continue;
        $key = $row[$id]; $update->bind_param('sss', $new, $key, $old);
        if(!$update->execute()) throw new RuntimeException('Password upgrade failed');
        $count += $update->affected_rows;
    }
    $update->close(); echo $table.': password column ready; protected '.$count." legacy hashes\n";
}
$old = $global['gen']['s_pwd']; $new = myAuth::protectLegacyHash($old);
if($old !== $new) {
    $path = $root.'config/config.php';
    $source = preg_replace_callback('/(\$setting\[\x27gen\x27\]\[\x27s_pwd\x27\]\s*=\s*)[^;]*;/', function($match) use($new) {return $match[1].var_export($new, true).';';}, file_get_contents($path), 1, $count);
    if($count !== 1 || file_put_contents($path, $source, LOCK_EX) === false) throw new RuntimeException('Admin password upgrade failed');
    echo "Framework administrator hash protected; password unchanged\n";
}
$connection->close();
