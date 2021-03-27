<?PHP
interface interface_db {
    public function connect();
    public function selectDB($the_db);
    public function insert();
    public function select();
    public function update();
    public function delete();
    public function query($sql);
    public function record($sql);
    public function records($sql);
    public function result($sql);
    public function getFields($the_tbl);
    public function close();
}

interface interface_sql {
    public function getRS();
    public function getDBs();
    public function getTbls();
    public function getPri($the_tbl);
    public function getInsertId();
    public function getStat();
    public function getCreateScript($the_tbl);
    public function getDataScript($the_tbl);
    public function getIdxScript($the_tbl);
    public function batchExe($SQLs);
    public function handleSQL($strSQL);
    public function file($file);
    public function check($obj);
    public function setCache($cache, $ttl);
}
