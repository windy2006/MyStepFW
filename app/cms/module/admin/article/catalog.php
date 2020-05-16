<?PHP
if(empty($id)) $id = r::r('cat_id');
if(!empty($id)) {
    if($cat_info = \app\cms\getPara($news_cat_plat, "cat_id", $id)) {
        if(!checkPower('web', $cat_info['web_id'])) {
            myStep::info('admin_art_catalog_error');
        }
    } else {
        myStep::info('admin_art_catalog_error');
    }
}
switch($method) {
    case 'add':
    case 'edit':
    case 'list':
        $content = build_page($method);
        break;
    case 'delete':
        cms::$log = $mystep->getLanguage('admin_art_catalog_delete');
        function multiDelData($cat_id) {
            global $db, $s;
            $db->build($s->db->pre.'news_cat')
                ->where('cat_id', 'n=', $cat_id);
            $db->delete();
            $db->build($s->db->pre.'news_show')
                ->where('cat_id', 'n=', $cat_id);
            $db->delete();
            $db->build($web_info['setting']->db->pre.'news_show')
                ->where('cat_id', 'n=', $cat_id);
            $db->delete();
            $db->build($web_info['setting']->db->pre.'news_detail')
                ->where('cat_id', 'n=', $cat_id);
            $db->delete();
            $cat_id_list = array();
            $db->build($s->db->pre.'news_cat')
                ->field('cat_id')
                ->where('pid', 'n=', $cat_id);
            $db->select();
            while($record = $db->getRS()){$cat_id_list[] = $record['cat_id'];}
            $db->free();
            for($i=0,$m=count($cat_id_list); $i<$m; $i++) {
                multiDelData($cat_id_list[$i]);
            }
            return;
        }
        multiDelData($id);
        \app\cms\deleteCache('news_cat');
        $cache->func('\app\cms\setCatList', [$news_cat], null);
        cms::redirect();
        break;
    case 'order':
        cms::$log = $mystep->getLanguage('admin_art_catalog_change');
        $cat_id_list = r::p('cat_id');
        $cat_order_list = r::p('cat_order');
        $cat_layer_list = r::p('cat_layer');
        for($i=0,$m=count($cat_id_list);$i<$m;$i++) {
            $db->build($s->db->pre.'news_cat')
               ->field([
                   'order'=>$cat_order_list[$i],
                   'layer'=>$cat_layer_list[$i]
               ])
               ->where('cat_id', 'n=', $cat_id_list[$i]);
            $db->update();
        }
        \app\cms\deleteCache('news_cat');
        $cache->func('\app\cms\setCatList', [$news_cat], null);
        cms::redirect();
        break;
    case 'add_ok':
    case 'edit_ok':
        if(myReq::check('post')) {
            $data = r::p('[ALL]');
            if($data['pid']==0) {
                $data['layer'] = 1;
            } else {
                $db->build($s->db->pre.'news_cat')
                   ->where('cat_id', 'n=', $data['pid'])
                   ->field('layer');
                $data['layer'] = 1 + $db->result();
            }
            $db->build($s->db->pre.'news_cat')->field('idx')->where('idx', '=', $data['idx'])->where('web_id', 'n=', $data['web_id']);
            if($method=='edit_ok') {
                $db->build($s->db->pre.'news_cat')->where('cat_id', 'n!=', $data['cat_id'], 'and');
            }
            if($db->result()) {
                myStep::info('admin_art_catalog_error_idx');
            }
            $data['show'] = array_sum($data['show']);
            if(is_null($data['show'])) $data['show'] = 0;
            $view_lvl_org = $data['view_lvl_org'];
            unset($data['view_lvl_org']);
            $merge = $data['merge']??0;
            unset($data['merge']);
            $template = '';
            if($data['type']==3) $template = htmlspecialchars_decode($data['template']);
            unset($data['template']);
            if($data['type']==3 && $template=='') $data['type'] = 1;
            if($method=='add_ok') {
                cms::$log = $mystep->getLanguage('admin_art_catalog_add');
                $db->build($s->db->pre.'news_cat')->field('max(`order`)');
                $data['order'] = 1 + $db->result();
                $db->build($s->db->pre.'news_cat')
                   ->field($data);
                $db->insert();
            } else {
                if($merge==1 && $data['pid']!=0 && $data['pid']!=$data['cat_id']) {
                    cms::$log = $mystep->getLanguage('admin_art_catalog_merge');
                    $db->build($s->db->pre.'news_cat')
                       ->field(['pid'=>$data['pid']])
                       ->where('pid', 'n=', $data['cat_id']);
                    $db->update();
                    $db->build($s->db->pre.'news_show')
                       ->field(['cat_id'=>$data['pid']])
                       ->where('cat_id', 'n=', $data['cat_id']);
                    $db->update();
                    $db->build($s->db->pre.'news_detail')
                       ->field(['cat_id'=>$data['pid']])
                       ->where('cat_id', 'n=', $data['cat_id']);
                    $db->update();
                    $db->build($s->db->pre.'news_cat')
                        ->where('cat_id', 'n=', $data['cat_id']);
                    $db->delete();
                } else {
                    cms::$log = $mystep->getLanguage('admin_art_catalog_edit');
                    function multiChange($cat_id, $layer) {
                        global $db, $s;
                        if($layer>100) myStep::info('admin_art_catalog_error');
                        $db->build($s->db->pre.'news_cat')
                           ->field(['layer'=>$layer])
                           ->where('cat_id', 'n=', $cat_id);
                        $db->update();
                        $id_list = array();
                        $db->build($s->db->pre.'news_cat')
                           ->field('cat_id')
                           ->where('pid', 'n=', $cat_id);
                        $db->select();
                        while($record = $db->getRS()){$id_list[] = $record['cat_id'];}
                        $db->free();
                        for($i=0,$m=count($id_list); $i<$m; $i++) {
                            multiChange($id_list[$i], $layer+1);
                        }
                        return;
                    }
                    multiChange($data['cat_id'], $data['layer']);
                    $db->build($s->db->pre.'news_cat')
                       ->field($data)
                       ->where('cat_id', 'n=', $data['cat_id']);
                    $db->update();
                    if($view_lvl_org!=$data['view_lvl']) {
                        $db->build($web_info['setting']->db->pre.'news_show')
                           ->field(['view_lvl'=>$data['view_lvl']])
                           ->where([
                                   array('cat_id', 'n=', $data['cat_id']),
                                   array('view_lvl', 'n=', $view_lvl_org, 'and')
                               ]);
                        $db->update();
                    }
                }
            }
            if($method=='add_ok') {
                $id = $db->getInsertId();
            }
            $the_file = PATH.'/template/custom/cat_'.$id.'.tpl';
            if(!empty($template)) {
                f::s($the_file, $template);
            } else {
                f::del($the_file);
            }
            \app\cms\deleteCache('news_cat');
            $cache->func('\app\cms\setCatList', [$news_cat], null);
        }
        myStep::$goto_url = preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
        break;
    default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db, $cache;
    global $news_cat_plat, $id, $website, $web_info;

    $tpl_setting['name'] = 'art_catalog_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    if($method == 'list') {
        $tpl->assign('news_cat', myString::toJson($news_cat_plat, $s->gen->charset));
        for($i=0,$m=count($news_cat_plat); $i<$m; $i++) {
            if(!checkPower('web', $news_cat_plat[$i]['web_id'])) continue;
            $news_cat_plat[$i]['name'] = ((isset($news_cat_plat[$i+1]) && $news_cat_plat[$i+1]['layer']==$news_cat_plat[$i]['layer'])?'├ ':'└ ').$news_cat_plat[$i]['name'];
            $news_cat_plat[$i]['name'] = str_repeat('&emsp;&nbsp;', $news_cat_plat[$i]['layer']-1).$news_cat_plat[$i]['name'];
            $news_cat_plat[$i]['name'] = preg_replace('/^├ /', '', preg_replace('/^└ /', '', $news_cat_plat[$i]['name']));
            $web = $cache->func('\app\cms\getPara', [$website, 'web_id', $news_cat_plat[$i]['web_id']]);
            $news_cat_plat[$i]['web_name'] = $web ? $web['name'] : $mystep->getLanguage('admin_art_catalog_public');
            $news_cat_plat[$i]['order'] = $i+1;
            $tpl->setLoop('record', $news_cat_plat[$i]);
        }
        $tpl->assign('title', $mystep->getLanguage('admin_art_catalog_catalog'));
    } else {
        if($method == 'edit') {
            $show_merge = 'inline';
            $db->build($s->db->pre.'news_cat')
               ->where('cat_id','n=',$id);
            if(($record = $db->record())===false) {
                myStep::info('admin_art_catalog_error');
            }
            myString::htmlTrans($record);
            $record['template'] = '';
            $the_file = PATH.'/template/custom/cat_'.$id.'.tpl';
            if(file_exists($the_file)) $record['template'] = f::g($the_file);
        } else {
            $show_merge = 'none';
            $record = array();
            $record['cat_id'] = 0;
            $record['web_id'] = 0;
            $record['pid'] = 0;
            $record['name'] = '';
            $record['idx'] = '';
            $record['prefix'] = '';
            $record['keyword'] = '';
            $record['comment'] = '';
            $record['image'] = '';
            $record['link'] = '';
            $record['view_lvl'] = 0;
            $record['view_lvl_org'] = 0;
            $record['type'] = 0;
            $record['show'] = 255;
            $record['template'] = '';
        }
        for($i=0,$m=count($website); $i<$m; $i++) {
            if(!checkPower('web', $website[$i]['web_id'])) continue;
            if($method == 'edit' && $website[$i]['web_id']!=$record['web_id']) continue;
            $website[$i]['selected'] = $website[$i]['web_id']==$record['web_id']?'selected':'';
            $tpl->setLoop('website', $website[$i]);
        }
        $positions = explode(',', $s->content->cat_pos);
        for($i=0,$m=count($positions); $i<$m; $i++) {
            $tpl->setLoop('positions', ['idx'=>pow(2, $i),'name'=>$positions[$i]]);
        }
        $tpl->assign('cat', $record);

        $cur_layer = 99;
        for($i=0,$m=count($news_cat_plat); $i<$m; $i++) {
            if(!checkPower('web', $news_cat_plat[$i]['web_id'])) continue;
            if($method == 'edit' && $news_cat_plat[$i]['web_id']!=$record['web_id']) continue;
            if($news_cat_plat[$i]['cat_id']==$record['cat_id']) {
                $cur_layer = $news_cat_plat[$i]['layer'];
                continue;
            }
            if(!empty($news_cat_plat[$i]['link'])) continue;
            if($news_cat_plat[$i]['layer'] > $cur_layer) {
                continue;
            } else {
                $cur_layer = 99;
            }
            $news_cat_plat[$i]['name'] = ((isset($news_cat_plat[$i+1]) && $news_cat_plat[$i+1]['layer']==$news_cat_plat[$i]['layer'])?'├ ':'└ ').$news_cat_plat[$i]['name'];
            $news_cat_plat[$i]['name'] = str_repeat('&emsp;&nbsp;', $news_cat_plat[$i]['layer']-1).$news_cat_plat[$i]['name'];
            $news_cat_plat[$i]['name'] = preg_replace('/^├ /', '', preg_replace('/^└ /', '', $news_cat_plat[$i]['name']));
            $tpl->setLoop('catalog', array('cat_id'=>$news_cat_plat[$i]['cat_id'], 'name'=>$news_cat_plat[$i]['name'], 'web_id'=>$news_cat_plat[$i]['web_id']));
        }

        $tpl->assign('title', $mystep->getLanguage($method=='add'?'admin_art_catalog_add':'admin_art_catalog_edit'));
        $tpl->assign('method', $method);
        $tpl->assign('show_merge', $show_merge);
        $tpl->assign('back_url', r::svr('HTTP_REFERER'));
    }
    $tpl->assign('web_id', $web_info['web_id']);
    return $mystep->render($tpl);
}