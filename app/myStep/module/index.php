<?php
$module = array_pop($info_app['path']);
if(empty($module) || !is_file(PATH.'template/info_'.$module.'.tpl')) $module = 'index';
$tpl_setting['name'] = 'info_'.$module;
$t = new myTemplate($tpl_setting, false, true);
if($module=='phpinfo') {
    phpinfo();
    $content = ob_get_contents();
    ob_clean();
    $content = preg_replace('#^.+?(<table.+</table>).+$#sm', '\1', $content);
    $content = str_replace('<table', '<table class="table"', $content);
    $t->assign('info', '<div id="phpinfo" >'.$content.'</div>');
} elseif($module=='error') {
    $method = r::g('m');
    $err_file = ROOT.'error.log';
    if($method=='clean') {
        f::del($err_file);
        myStep::redirect();
    } elseif($method=='download') {
        $content = f::getLocal($err_file);
        if(!empty($content)) {
            if(ob_get_length()!==false) ob_end_clean();
            $content = preg_replace('/[\r\n]+/', "\r\n", $content);
            header('Content-type: text/plain');
            header('Accept-Ranges: bytes');
            header('Accept-Length: '.strlen($content));
            header('Content-Disposition: attachment; filename='.date('Ymd').'_err.txt');
            echo $content;
            exit;
        }
    }
    if(!is_file($err_file)) {
        $err_msg = $mystep->getLanguage('page_error_msg');
        $err_output = 'disabled';
    } else {
        $err_msg = '';
        $err_output = '';
        $err_content= f::getLocal($err_file);
        if($err_content=='') {
            $err_msg = $mystep->getLanguage('page_error_msg');
        } else {
            $err_lst = preg_split("/\n+[\-]{20,}\n+/", $err_content);
            array_pop($err_lst);
            $err_msg = sprintf($mystep->getLanguage('page_error_info'), count($err_lst));
            for($i=count($err_lst)-1; $i>=0; $i--) {
                $err_lst[$i] = htmlspecialchars($err_lst[$i]);
                $err_lst[$i] = preg_replace("/\n+/", "\n", $err_lst[$i]);
                $err_lst[$i] = str_replace("\n", "\n<br />\n", $err_lst[$i]);
                $err_lst[$i] = str_replace("\t", " &nbsp; &nbsp;", $err_lst[$i]);
                $err_lst[$i] = str_replace("  ", " &nbsp;", $err_lst[$i]);
                $err_lst[$i] = preg_replace("/^([\w \.]+:)/m", '<b>\1</b>', $err_lst[$i]);
                $t->setLoop('err', array('content'=>$err_lst[$i]));
            }
        }
    }
    $t->assign('err_msg', $err_msg);
    $t->assign('err_output', $err_output);
}
$content = $mystep->render($t, 'db, s', false);
