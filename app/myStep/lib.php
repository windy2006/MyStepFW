<?PHP
namespace app\myStep;

function logCheck($show = true) {
    $user = \myReq::session('ms_user');
    if(empty($user)) {
        $url = \myReq::server('REQUEST_URI');
        if(\myReq::session('url')=='' && !preg_match('#'.$GLOBALS['ms_setting']->gen->path_admin.'/$#', $url)) \myReq::session('url', $url);
        if($show) \myStep::getModule('login');
        return false;
    }
    return true;
}

function getData($tbl_name) {
    if(!logCheck(false)) return [];
    global $db, $ms_setting;
    if(!$db->check()) $db->connect(0, $ms_setting->db->name);
    $result = array();
    $result['total'] = 0;
    $result['rows'] = [];
    $request = file_get_contents('php://input');
    $db->build($tbl_name);
    $request = json_decode($request, true);
    $limit = $request['limit'] ?? '';
    if(!empty($limit)) {
        $offset = $request['offset'] ?? 0;
        if(empty($offset)) $offset = 0;
        $db->build($tbl_name)->limit($offset, $limit);
    }
    $field = $request['field'] ?? [];
    foreach($field as $k => $v) {
        if(!empty($v) && $k) {
            $judge = is_numeric($v) ? '=' : 'like';
            $db->build($tbl_name)->where(htmlentities($k), $judge, htmlentities($v));
        }
    }
    $sql = $db->select(1);
    $result['total'] = $db->count($sql);
    $result['rows'] = $db->records($sql);
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