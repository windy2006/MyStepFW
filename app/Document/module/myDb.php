<?PHP
/**
 * 文件：app/Document/module/myDb.php
 * 功能说明：演示数据库代理、能力检查、参数绑定、SQL构建及事务回滚。
 * 方法目录：事务回调两个（参数及返回值见各回调PHPDoc）。
 * 使用说明：使用独立SQLite内存库，不替换全局$db，不读写网站业务数据；缺少SQLite3时给出提示。
 */
if(!class_exists('SQLite3', false)) {
    echo '<p>运行本示例需要启用 PHP SQLite3 扩展；下方源代码仍可阅读。</p>';
    return;
}
$demoDb = new myDb('SQLite', ':memory:');
try {
    $demoDb->execute('CREATE TABLE demo_items(id INTEGER PRIMARY KEY, name TEXT)');
    $demoId = $demoDb->transaction(
        /**
         * 功能说明：插入一条绑定参数记录，正常返回后提交。
         * @param myDb $tx 当前数据库代理
         * @return int 新增记录ID
         */
        function($tx) {
            $tx->execute('INSERT INTO demo_items(name) VALUES(?)', ['MyStep 示例']);
            return $tx->getInsertId();
        }
    );
    $demoDb->build('demo_items')->field('id,name')->where('id', 'n>=', 1)->order('id')->limit(0, 5);
    $demoSql = $demoDb->select(true); // true只生成SQL；数据值也可用fetchAll的参数数组绑定。
    $demoRolledBack = false;
    try {
        $demoDb->transaction(
            /**
             * 功能说明：插入后主动抛异常，演示事务回滚。
             * @param myDb $tx 当前数据库代理
             * @return void 本回调始终抛出异常
             * @throws RuntimeException 用于演示回滚的固定异常
             */
            function($tx) {
                $tx->execute('INSERT INTO demo_items(name) VALUES(?)', ['应当回滚']);
                throw new RuntimeException('示例回滚');
            }
        );
    } catch(RuntimeException $demoError) {
        $demoRolledBack = true;
    }
    echo '<pre>'.htmlspecialchars(print_r([
        '支持参数化'=> $demoDb->supports(interface_db_prepared::class),
        '支持受管事务'=>$demoDb->supports(interface_db_transaction::class),
        '新增ID'=>$demoId,
        '构建的SQL'=>$demoSql,
        '读取记录'=>$demoDb->fetchAll($demoSql),
        '按参数读取单值'=>$demoDb->fetchValue('SELECT name FROM demo_items WHERE id=?', [$demoId]),
        '异常已回滚'=>$demoRolledBack,
        '回滚后记录数'=>$demoDb->fetchValue('SELECT count(*) FROM demo_items'),
    ], true), ENT_QUOTES, 'UTF-8').'</pre>';
} finally {
    $demoDb->close();
}
// MySQL等驱动使用相应连接配置；先用supports判断能力，不能仅按同名方法是否存在推断支持。
