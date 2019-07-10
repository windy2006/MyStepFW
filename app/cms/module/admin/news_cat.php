<?PHP
$method = strtolower(end($info_app['path']));
switch(true) {
    case in_array($method, ['add', 'edit', 'list']):
        $method = 'add';
        break;
    case is_numeric($method):
        $method = 'edit';
        break;
    default:
        $method = 'list';
        break;
}
$cat = \app\cms\getCache('news_cat');

switch($method) {
    case "add":
    case "edit":
    case "list":
        build_page($method);
        break;
    case "delete":
        $log_info = $mystep->getLanguage('admin_art_catalog_delete');
        function multiDelData($catid) {
            global $db, $setting;
            $db->delete($setting['db']['pre']."news_cat", array("cat_id","n=",$catid));
            $db->delete($setting['db']['pre']."news_show", array("cat_id","n=",$catid));
            $db->delete($setting['db']['pre']."news_detail", array("cat_id","n=",$catid));
            $catid_list = array();
            $db->select($setting['db']['pre']."news_cat", "cat_id", array("cat_main","n=",$catid));
            while($record = $db->GetRS()) {$catid_list[] = $record['cat_id'];}
            $db->free();
            $max_count = count($catid_list);
            for($i=0; $i<$max_count; $i++) {
                multiDelData($catid_list[$i]);
            }
            return;
        }
        multiDelData($cat_id);
        deleteCache("news_cat");
        break;
    case "order":
        $log_info = $mystep->getLanguage('admin_art_catalog_change');
        for($i=0,$m=count($_POST['cat_id']);$i<$m;$i++) {
            $db->update($setting['db']['pre']."news_cat", array("cat_order"=>$_POST['cat_order'][$i]), array("cat_id","n=",$_POST['cat_id'][$i]));
        }
        deleteCache("news_cat");
        break;
    case "up":
    case "down":
        $log_info = $mystep->getLanguage('admin_art_catalog_change');
        list($cat_main, $cat_layer, $web_id)=array_values($db->record($setting['db']['pre']."news_cat", "cat_main, cat_layer, web_id", array("cat_id","n=",$cat_id)));
        $db->select($setting['db']['pre']."news_cat", "cat_id, cat_order, web_id",array(array("cat_layer","n=",$cat_layer),array("cat_main","n=",$cat_main,"and"),array("web_id","n=",$web_id,"and")),array("order"=>"cat_order"));
        while($record[] = $db->GetRS()) {}
        $db->Free();
        $max_count = count($record)-1;
        for($i=0; $i<$max_count; $i++) {
            if($record[$i]['cat_id']!=$cat_id) continue;
            if($method=="up") {
                if($i>0) {
                    $db->update($setting['db']['pre']."news_cat", array("cat_order",$record[$i-1]['cat_order']), array("cat_id","n=",$cat_id));
                    $db->update($setting['db']['pre']."news_cat", array("cat_order",$record[$i]['cat_order']), array("cat_id","n=",$record[$i-1]['cat_id']));
                }
            } elseif($method=="down") {
                if($i<count($record)-2) {
                    $db->update($setting['db']['pre']."news_cat", array("cat_order",$record[$i+1]['cat_order']), array("cat_id","n=",$cat_id));
                    $db->update($setting['db']['pre']."news_cat", array("cat_order",$record[$i]['cat_order']), array("cat_id","n=",$record[$i+1]['cat_id']));
                }
            }
            break;
        }
        deleteCache("news_cat");
        break;
    case "add_ok":
    case "edit_ok":
        if(myReq::check('post')) {
            if($_POST['cat_main']==0) {
                $_POST['cat_layer'] = 1;
            } else {
                $_POST['cat_layer'] = 1 + $db->result($setting['db']['pre']."news_cat", "cat_layer", array("cat_id", "n=", $_POST['cat_main']));
            }
            $_POST['cat_show'] = array_sum($_POST['cat_show']);
            if(is_null($_POST['cat_show'])) $_POST['cat_show'] = 0;
            $view_lvl_org = $_POST['view_lvl_org'];
            unset($_POST['view_lvl_org']);
            $notice_org = $_POST['notice_org'];
            unset($_POST['notice_org']);
            $merge = $_POST['merge'];
            unset($_POST['merge']);
            $template = "";
            if($_POST['cat_type']==3) $template = $_POST['template'];
            unset($_POST['template']);
            if($_POST['cat_type']==3 && $template=="") $_POST['cat_type'] = 1;
            if($method=="add_ok") {
                $log_info = $mystep->getLanguage('admin_art_catalog_add');
                $_POST['cat_order'] = 1 + $db->result($setting['db']['pre']."news_cat", "max(cat_order)");
                $db->insert($setting['db']['pre']."news_cat", $_POST, true);
            } else {
                if(!is_null($merge) && $_POST['cat_main']!=0 && $_POST['cat_main']!=$cat_id) {
                    $log_info = $mystep->getLanguage('admin_art_catalog_merge');
                    $db->update($setting['db']['pre']."news_cat",array("cat_id",$_POST['cat_main']),array("cat_main","n=",$cat_id));
                    $db->update($setting['db']['pre']."news_show",array("cat_id",$_POST['cat_main']),array("cat_id","n=",$cat_id));
                    $db->update($setting['db']['pre']."news_detail",array("cat_id",$_POST['cat_main']),array("cat_id","n=",$cat_id));
                    $db->delete($setting['db']['pre']."news_cat", array("cat_id",$cat_id));
                } else {
                    $log_info = $mystep->getLanguage('admin_art_catalog_edit');
                    function multiChange($catid, $layer) {
                        global $db, $setting, $mystep;
                        if($layer>100) showInfo($mystep->getLanguage('admin_art_catalog_error'));
                        $db->update($setting['db']['pre']."news_cat",array("cat_layer"=>$layer),array("cat_id","n=",$catid));
                        $catid_list = array();
                        $db->select($setting['db']['pre']."news_cat", "cat_id", array("cat_main","n=",$catid));
                        while($record = $db->GetRS()) {$catid_list[] = $record['cat_id'];}
                        $db->free();
                        for($i=0,$m=count($catid_list); $i<$m; $i++) {
                            multiChange($catid_list[$i], $layer+1);
                        }
                        return;
                    }
                    multiChange($cat_id, $_POST['cat_layer']);
                    $db->update($setting['db']['pre']."news_cat", $_POST, array("cat_id","n=",$cat_id));
                    $setting_sub = getSubSetting($webInfo['web_id']);
                    if($setting['db']['name']==$setting_sub['db']['name']) {
                        $setting['db']['pre_sub'] = $setting_sub['db']['pre'];
                    } else {
                        $setting['db']['pre_sub'] = $setting_sub['db']['name'].".".$setting_sub['db']['pre'];
                    }
                    if($view_lvl_org!=$_POST['view_lvl'] && is_numeric($_POST['view_lvl'])) {
                        $db->update($setting['db']['pre_sub']."news_show", array("view_lvl", $_POST['view_lvl']), array(array("cat_id","n=",$cat_id), array("view_lvl","n=",$view_lvl_org,"and")));
                    }
                    if($notice_org!=$_POST['notice']) {
                        $db->update($setting['db']['pre_sub']."news_show", array("notice", $_POST['notice']), array(array("cat_id","n=",$cat_id), array("notice","=",$notice_org,"and")));
                    }
                }
            }
            if($method=="add_ok") {
                $cat_id = $db->GetInsertId();
                if($group['power_cat']!="all") {
                    $db->update($setting['db']['pre']."user_group", array("power_cat", "concat(power_cat, ',".$cat_id."')"), array("group_id","n=",$usergroup));
                    deleteCache("user_group");
                }
            }
            deleteCache("news_cat");
            $the_file = ROOT."/".$setting['path']['template']."/default/list_cat_".$cat_id.".tpl";
            if(!empty($template)) {
                WriteFile($the_file, $template, "wb");
            } else {
                @unlink($the_file);
            }
        } else {
            $goto_url = $setting['info']['self'];
        }
        break;
    default:
        $goto_url = $setting['info']['self'];
}