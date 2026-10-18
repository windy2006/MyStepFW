<?PHP
namespace app\myStep;

function logCheck($show = true) {
    $user = \myReq::session('ms_user');
    if(empty($user)) {
        if($show) {
            $url = \myReq::server('REQUEST_URI');
            if(\myReq::session('url')=='' && !preg_match('#'.$GLOBALS['ms_setting']->gen->path_admin.'/$#', $url)) \myReq::session('url', $url);
            \myStep::getModule('login');
        }
        return false;
    }
    return true;
}

/**
 * 为后台数据浏览器分页读取数据，先检查框架管理员身份及实际表/字段。
 * @param string $tbl_name 表名或数据库.表名，只接受字母、数字和下划线
 * @return array total 为不含分页的匹配数，rows 为记录列表；未授权/无效表返回空列表
 * POST JSON 的 limit 限制为 1..1000、offset 不小于0，field 仅允许真实字段及标量值。
 */
function getData($tbl_name) {
    if(!logCheck(false)) return [];
    global $db, $ms_setting, $db_name;
    $db_name = $ms_setting->db->name;
    $result = ['total'=>0,'rows'=>[]];
    if(!is_string($tbl_name) || !preg_match('/\A(?:[a-zA-Z0-9_]+\.)?[a-zA-Z0-9_]+\z/D',$tbl_name)) return $result;
    if(strpos($tbl_name,'.')!==false) list($db_name,$tbl_name)=explode('.',$tbl_name,2);
    if(!$db->check()) $db->connect(0,$db_name);
    if(!in_array($tbl_name,$db->getTbls($db_name),true)) return $result;
    $fields=$db->getFields($tbl_name,$db_name);
    $table=$db_name.'.'.$tbl_name;
    $request=json_decode(file_get_contents('php://input'),true);
    if(!is_array($request)) $request=[];
    $limit=is_scalar($request['limit'] ?? null) ? (int)$request['limit'] : 20;
    $offset=is_scalar($request['offset'] ?? null) ? (int)$request['offset'] : 0;
    $db->build('[reset]');
    $db->build($table)->limit(max(0,$offset),max(1,min(1000,$limit)));
    foreach(is_array($request['field'] ?? null) ? $request['field'] : [] as $key=>$value) {
        if(!in_array($key,$fields,true) || !is_scalar($value) || $value==='') continue;
        $db->build($table)->where($key,is_numeric($value) ? '=' : 'like',$value);
    }
    $sql=$db->select(true);
    $result['total']=$db->count($sql);
    $result['rows']=$db->records($sql);
    return $result;
}

function getError() {
    $err_file = ROOT.'error.log';
    $count = 0;
    if(is_file($err_file)) {
        $err_content= \myFile::getLocal($err_file);
        $err_lst = preg_split("/\n+[\-]{20,}\n+/", $err_content);
        $count = count($err_lst) - 1;
    }
    return array('count'=>$count);
}

function autoComplete($mode, $keyword) {
    global $ms_setting;
    $keyword = \myString::setCharset($keyword, $ms_setting->gen->charset);
    $result = array(
        'query' => $keyword,
        'suggestions' => array(),
        'data' => array()
    );
    if(!is_string($mode) || !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/D',$mode)) return $result;
    $dataFile = VENDOR.'jquery.autocomplete/'.$mode.'.php';
    if(is_file($dataFile)) {
        include($dataFile);
        $data = $$mode;
        unset($$mode);
        $keyword = strtolower($keyword);
        for($i=0,$m=count($data);$i<$m;$i++) {
            if(strpos(strtolower(implode('|', $data[$i])), $keyword)!==false) {
                if($ms_setting->gen->language=='en') {
                    $result['suggestions'][] = $data[$i][1];
                    $result['data'][] = $data[$i][1];
                } else {
                    $result['suggestions'][] = $data[$i][0];
                    $result['data'][] = $data[$i][1];
                }
            }
        }
    }
    return $result;
}