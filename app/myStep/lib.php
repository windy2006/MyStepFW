<?php
namespace app\myStep;
global $info_app, $no_db, $s, $p, $q, $setPlugin, $path_admin;

if(!is_array($info_app)) $info_app = array();
if(!isset($info_app['path'])) $info_app['path'] = explode('/', trim($p, '/'));
if(!isset($info_app['para'])) parse_str($q, $info_app['para']);
if(!isset($info_app['name'])) $info_app = array_merge($info_app, include(dirname(__FILE__).'/info.php'));

$func = preg_replace('#^/?(\w+).*$#', '\1', $q);
$setPlugin = !in_array($func, ['language', 'captcha']);
$no_db = 'y';

\myStep::setPara();

switch($s->router->mode) {
    case 'rewrite':
        $path_admin = ROOT_WEB.'manager/';
        break;
    case 'path_info':
        $path_admin = ROOT_WEB.'index.php/manager/';
        break;
    default:
        $path_admin = ROOT_WEB.'index.php?/manager/';
        break;
}

function logCheck($show = true) {
    $user = \r::s('ms_user');
    if(empty($user)) {
        if($show) \myStep::getModule('login');
        return false;
    }
    return true;
}

function getData($tbl_name) {
    if(!logCheck(false)) return [];
    global $db, $s;
    if(!$db->check()) $db->connect(0, $s->db->name);
    $db->build($tbl_name);
    $request = file_get_contents('php://input');
    if(!empty($request)) {
        $request = json_decode($request, true);
        $limit = $request['limit'] ?? '';
        if(!empty($limit)) {
            $offset = $request['offset'];
            if(empty($offset)) $offset = 0;
            $db->build($tbl_name)->limit($offset, $limit);
        }
        foreach($request['field'] as $k => $v) {
            if(!empty($v) && $k) {
                $judge = is_numeric($v) ? '=' : 'like';
                $db->build($tbl_name)->where(htmlentities($k), $judge, htmlentities($v));
            }
        }
    }
    $result = array();
    $result['total'] = $db->count();
    $result['rows'] = $db->records();
    return $result;
}

function getError() {
    $err_file = ROOT.'error.log';
    $count = 0;
    if(is_file($err_file)) {
        $err_content= \f::getLocal($err_file);
        $err_lst = preg_split("/\n+[\-]{20,}\n+/",$err_content);
        $count = count($err_lst) - 1;
    }
    return array('count'=>$count);
}

function autoComplete($mode, $keyword) {
    global $s;
    $keyword = \myString::sc($keyword, $s->gen->charset);
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
                if($s->gen->language=='en') {
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