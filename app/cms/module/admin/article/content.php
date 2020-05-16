<?PHP
set_time_limit(1200);
ignore_user_abort('on');
global $cat_id, $cat_info;
$cat_id = r::r('cat_id')??'';
if(!empty($cat_id) && ($cat_info = \app\cms\getPara($news_cat_plat, 'cat_id', $cat_id))!==false) {
    $web_id = $cat_info['web_id'];
    if(($web_cur = \app\cms\checkVal($website, 'web_id', $web_id))!==false) {
        $web_cur['setting'] = new myConfig(PATH.'website/config_'.$web_cur['idx'].'.php');
        $web_info = $web_cur;
        unset($web_cur);
    }
}
if(empty($id)) $id = r::r('news_id');
if(!empty($id)) {
    $db->build($web_info['setting']->db->pre.'news_show')
        ->field('web_id')
        ->where('news_id', 'n=', $id);
	$web_id_news = $db->result();
	if($web_id_news===false || !checkPower('web', $web_id_news)) {
	    myStep::info('admin_art_content_nopower');
	}
    if($method=='edit_ok' || $method=='delete') {
        $db->build($web_info['setting']->db->pre.'news_show')
            ->field('cat_id,web_id,add_date')
            ->where('news_id', 'n=', $id);
        list($cat_id, $web_id, $add_date) = array_values($db->record());
        if(!checkPower('web', $web_id)) {
            cms::redirect();
        }
        if(checkPower('func') && ($s->info->time/1000-strtotime($add_date))/(60*60*24)>60) {
	        myStep::info('admin_art_content_locked');
        }
    }
}
switch($method) {
	case 'add':
	case 'edit':
	case 'list':
        $content = build_page($method);
		break;
	case 'delete':
        cms::$log = $mystep->getLanguage('admin_art_content_delete');
        $db->build($web_info['setting']->db->pre.'news_show')
            ->where('news_id', 'n=', $id);
        $db->delete();
        $db->build($web_info['setting']->db->pre.'news_detail')
            ->where('news_id', 'n=', $id);
        $db->delete();
        \app\cms\removeNewsCache($id, $web_info['web_id']);
        cms::redirect();
		break;
	case 'unlock':
		if(checkPower('func')) {
			cms::$log = $mystep->getLanguage('admin_art_content_unlock');
			$db->build($web_info['setting']->db->pre.'news_show')
			    ->field(['add_date'=>'(now())'])
                ->where('news_id', 'n=', $id);
			$db->update();
		}
        cms::redirect();
		break;
	case 'add_ok':
	case 'edit_ok':
        if(!myReq::check('post') || !checkPower('web', r::p('web_id'))) {
            cms::redirect();
		}
        $data = r::p('[ALL]');
        if(empty($data['active'])) unset($data['active']);
        if(empty($data['expire'])) unset($data['expire']);

        $multi_cata = $data['multi_cata'];
        unset($data['multi_cata']);

        $data['style'] = implode(',', $data['style']);

        $content = r::post('content', '!');
        $content = trim($content, chr(13).chr(10));
        $content = preg_replace('/<(\w+)[^>]*><\!\-\- pagebreak \-\-\><\/\\1>[\r\n\s]*/m', '<!-- pagebreak -->', $content);
        unset($data['content']);

        $back_url = htmlspecialchars_decode($data['back_url']);
        $back_url = preg_replace('#web_id=\d+#', 'web_id='.$web_id, $back_url);
        $back_url = preg_replace('#cat_id=\d+#', 'cat_id='.$cat_id, $back_url);
        unset($data['back_url']);

        $get_remote_file = $data['get_remote_file'];
        unset($data['get_remote_file']);
        if($get_remote_file) {
            preg_match_all('/<img.+?src=(.?)(http.+?)\\1.*?>/is', $content, $matches);
            $pic_list = [];
            for($i=0, $m=count($matches[2]); $i<$m; $i++) {
                if(array_search($matches[2][$i], $pic_list)===false) {
                    array_push($pic_list, $matches[2][$i]);
                } else {
                    continue;
                }
                $file_info = pathinfo($matches[2][$i]);
                $file_info['basename'] = preg_replace('/\?.*$/', '', $file_info['basename']);
                $file_info['basename'] = preg_replace('/[;,&\=]/', '', $file_info['basename']);
                $file_info['extension'] = preg_replace('/\?.*$/', '', $file_info['extension']);
                if(empty($file_info['extension'])) $file_info['extension'] = 'jpg';
                if(strlen($file_info['basename'])>120) $old_name = substr($file_info['basename'], -120);
                $the_name = getMicrotime().'.'.$file_info['extension'];
                $the_path = FILE.date($s->upload->path_mode);
                myFile::getRemoteFile($matches[2][$i], $the_path.'/'.$the_name);
                myFile::saveFile(FILE.date($s->upload->path_mode).'/log.txt', $the_name.'::'.$file_info['basename'].'::'.chr(10), 'a');
                $content = str_replace($matches[2][$i], '/api/myStep/download/'.$the_name, $content);
            }
        }

        $content = explode('<!-- pagebreak -->', $content);
        $sub_title = array();
        for($i=0,$m=count($content); $i<$m; $i++) {
            if(preg_match('/<span.+?mceSubtitle.+?>(.+)<\/span>/i', $content[$i], $matches)) {
                $sub_title[$i] = $matches[1];
                $sub_title[$i] = strip_tags($sub_title[$i]);
                $sub_title[$i] = s::substr($sub_title[$i], 0, 98);
                $content[$i] = preg_replace('/[\r\n]*(<(\w+)>)?<span.+?mceSubtitle.+?>.+<\/span>(<\/\\2>)?[\r\n]*/i', '', $content[$i]);
                $sub_title[$i] = str_replace('&nbsp;', ' ', $sub_title[$i]);
                if(strlen(preg_replace('/[\s\r\n\t]/', '', $sub_title[$i]))<4) {
                    $sub_title[$i] = $data['subject'].' - '.($i+1);
                }
            } else {
                $sub_title[$i] = $data['subject'].' - '.($i+1);
            }
        }
        $data['tag'] = str_replace('，', ',', $data['tag']);
        $data['tag'] = str_replace('；', ',', $data['tag']);
        $data['tag'] = str_replace(';', ',', $data['tag']);
        $data['tag'] = str_replace(' ', '_', $data['tag']);
        if(!isset($data['setop_mode']) || $data['setop_mode']==0) {
            $data['setop'] = 0;
        } else {
            $data['setop'] = array_sum($data['setop']);
            if(is_null($data['setop'])) {
                $data['setop'] = 0;
            } else {
                $data['setop'] += ($data['setop_mode'] * 32);
            }
        }
        unset($data['setop_mode']);

        if($method=='add_ok') {
            cms::$log = $mystep->getLanguage('admin_art_content_add');
            $data['add_user'] = r::s('ms_cms_op');
            $data['add_date'] = 'now()';

            $tag = explode(',', $data['tag']);
            for($i=0,$m = count($tag); $i<$m; $i++) {
                $tag[$i] = trim($tag[$i], '_');
                if(strlen(trim($tag[$i]))<2) continue;
                $tag[$i] = s::substr($tag[$i], 0, 15);
                $tag[$i] = $db->safeValue($tag[$i]);
                $db->build($web_info['setting']->db->pre.'news_tag')
                    ->field('id')
                    ->where('tag', '=', $tag[$i]);
                if($db->result()) {
                    $db->build($web_info['setting']->db->pre.'news_tag')
                        ->field(array('count'=>'+1', 'update_date'=>'(UNIX_TIMESTAMP())'))
                        ->where('tag', '=', $tag[$i]);
                    $db->update();
                } else {
                    $db->build($web_info['setting']->db->pre.'news_tag')
                        ->values(0, $tag[$i], 1, 0, 'UNIX_TIMESTAMP()', 'UNIX_TIMESTAMP()');
                    $db->insert();
                }
            }
            unset($data['attach_list']);
            $db->build($web_info['setting']->db->pre.'news_show')->field($data);
            $db->insert();
            $id = $db->getInsertId();
        } else {
            cms::$log = $mystep->getLanguage('admin_art_content_edit');
            unset($data['news_id']);
            $db->build($web_info['setting']->db->pre.'news_detail')
                ->where('news_id', 'n=', $id);
            $db->delete();
            $db->build($web_info['setting']->db->pre.'news_show')
                ->field($data)
                ->where('news_id', 'n=', $id);
            $db->update();
            \app\cms\removeNewsCache($id, $web_info['web_id']);
        }

        $detail = array();
        for($i=0,$m=count($sub_title); $i<$m; $i++) {
            $detail['id'] = 0;
            $detail['cat_id'] = $data['cat_id'];
            $detail['news_id'] = $id;
            $detail['page'] = $i+1;
            $detail['sub_title'] = $sub_title[$i];
            $detail['content'] = $content[$i];
            $db->build($web_info['setting']->db->pre.'news_detail')->field($detail);
            $db->insert();
        }
        unset($content);

        $db->build($web_info['setting']->db->pre.'news_show')
            ->field(['pages'=>count($sub_title)])
            ->where('news_id', 'n=', $id);
        $db->update();

        $cid_list = explode(',', $multi_cata);
        $the_cat = $data['cat_id'];
        $data['setop'] = 0;
        for($i=0,$m=count($cid_list);$i<$m;$i++) {
            if(is_numeric($cid_list[$i]) && $the_cat!=$cid_list[$i]) {
                $data['cat_id'] = $cid_list[$i];
                //$data['link'] = getUrl('read', array($id, $data['cat_id']), 1, $data['web_id']);
                $data['link'] = '###';
                $data['add_user'] = r::s('ms_cms_op');
                $db->build($web_info['setting']->db->pre.'news_show')
                    ->field($data);
                $db->insert();
            }
        }
        myStep::$goto_url = !empty($back_url)?$back_url:preg_replace('#'.preg_quote($method).'$#', '', r::env('REQUEST_URI'));
        break;
	default:
        $content = build_page('list');
}

function build_page($method) {
    global $mystep, $tpl_setting, $s, $db;
    global $news_cat_plat, $id, $cat_id, $web_info, $cat_info, $group_info;

    $tpl_setting['name'] = 'art_content_'.($method=='list'?'list':'input');
    $tpl = new myTemplate($tpl_setting, false);

    $pos = explode(',', $s->content->push_pos);
    for($i=0,$m=count($pos); $i<$m; $i++) {
        $tpl->setLoop('push_pos', ['idx'=>pow(2, $i),'name'=>$pos[$i]]);
    }
    $mode = explode(',', $s->content->push_mode);
    for($i=0,$m=count($mode); $i<$m; $i++) {
        $tpl->setLoop('push_mode', ['idx'=>$i,'name'=>$mode[$i]]);
    }

	//if(empty($group['power_cat'])) $group['power_cat'] = 0;
	if($method == 'list') {
		$page = r::r('page')??1;
		$keyword = r::r('keyword')??'';
		$order = r::r('order')??'news_id';
		$order_type = r::r('order_type');
		if(empty($order_type)) $order_type = 'desc';

		//condition
		$condition = array();
		if(!checkPower('web')) $condition[] = array('web_id', 'nin', $group_info['power_web'], 'and');
		if(!empty($web_info['web_id'])) $condition[] = array('web_id', 'n=', $web_info['web_id'], 'and');
		if(!empty($cat_id)) $condition[] = array('cat_id', 'n=', $cat_id);
		if(!empty($keyword)) $condition[] = array([['subject', 'like', $keyword], ['tag', 'like', $keyword, 'or']], 'and');

		//navigation
        $db->build($web_info['setting']->db->pre.'news_show')
            ->field('count(*)')
            ->where($condition);
		$counter = $db->result();
		list($page_info, $record_start, $page_size) = \app\cms\getPageList($counter, $page, $s->list->txt, 'keyword='.$keyword.'&cat_id='.$id.'&web_id='.$web_info['web_id'].'&order='.$order.'&order_type='.$order_type);
		$tpl->assign($page_info);
		$tpl->assign('record_count', $counter);

		//main list
		$db->build($web_info['setting']->db->pre.'news_show')
            ->field('news_id,cat_id,web_id,subject,add_user,add_date')
            ->where($condition)
            ->order($order, $order_type=='desc')->order('order', $order_type=='desc')
            ->limit($record_start, $page_size);
        $db->build($s->db->pre.'news_cat',[
                'mode' => 'left',
                'field' => 'cat_id'
            ])->field('idx','name as cat_name');
		$db->select();
		while($record = $db->getRS()) {
			s::htmlTrans($record);
			if(empty($record['link'])) {
				$record['link'] = \app\cms\getLink($record);
			}
			$tpl->setLoop('record', $record);
		}
		if(empty($cat_id)) {
            $title = $mystep->getLanguage('admin_art_content_list_all');
        } else {
		    $db->build($s->db->pre.'news_cat')->field('name')->where('cat_id','n=',$cat_id);
            $title = $db->result();
        }
        $tpl->assign('keyword', $keyword);
        $tpl->assign('order', $order);
        $tpl->assign('order_type_org', $order_type);
        $tpl->assign('order_type', $order_type=='asc'?'desc':'asc');
		$tpl->assign('title', $mystep->getLanguage('admin_art_content_list_article').' - '.$s->web->title.' - '.$title);
	} elseif($method == 'edit') {
        $db->build($web_info['setting']->db->pre.'news_show')
            ->where('news_id', 'n=', $id);
        if(!checkPower('web')) {
            $db->build($web_info['setting']->db->pre.'news_show')
                ->where('web_id', 'in', $group_info['power_web'], 'and');
        }
		if(($record = $db->record())===false) {
		    myStep::info('admin_art_content_error');
		}
        s::htmlTrans($record);
		$tpl->assign('record',$record);
		$cat_id = $record['cat_id'];
		$content = array();
        $db->build($web_info['setting']->db->pre.'news_detail')
            ->where('news_id', 'n=', $id)
            ->order('page');
		$db->select();
		while($record = $db->getRS()) {
			$record['content'] = str_replace('&', '&#38;', $record['content']);
            s::htmlTrans($record);
			$record['content'] = '<p><span class="mceSubtitle">'.$record['sub_title'].'</span></p>'.$record['content'];
			$content[] = $record['content'];
		}
		$tpl->assign('record_content', implode(chr(10).'<!-- pagebreak -->'.chr(10), $content));
		$tpl->assign('title', $mystep->getLanguage('admin_art_content_edit'));
	} else {
		$record = array();
		$record['news_id'] = 0;
		$record['cat_id'] = $cat_id;
		$record['web_id'] = $web_info['web_id'];
		$record['subject'] = '';
		$record['style'] = '';
		$record['describe'] = '';
		$record['original'] = '';
		$record['link'] = '';
		$record['tag'] = '';
		$record['image'] = '';
		$record['content'] = '';
		$record['pages'] = 1;
		$record['order'] = 0;
        $record['active'] = '';
        $record['expire'] = '';
        $record['setop'] = '';
		$record['view_lvl'] = $cat_info['view_lvl']??0;
		$tpl->assign('record', $record);
		$tpl->assign('title', $mystep->getLanguage('admin_art_content_add'));
	}

	//catalog select
	if(empty($web_info['web_id'])) $web_info['web_id']=1;
	for($i=0,$m=count($news_cat_plat); $i<$m; $i++) {
	    if(!checkPower('web', $news_cat_plat[$i]['web_id']) || !empty($news_cat_plat[$i]['link'])) continue;
        $news_cat_plat[$i]['name'] = ((isset($news_cat_plat[$i+1]) && $news_cat_plat[$i+1]['layer']==$news_cat_plat[$i]['layer'])?'├ ':'└ ').$news_cat_plat[$i]['name'];
        $news_cat_plat[$i]['name'] = str_repeat('&emsp;&nbsp;', $news_cat_plat[$i]['layer']-1).$news_cat_plat[$i]['name'];
        $news_cat_plat[$i]['name'] = preg_replace('/^├ /', '', preg_replace('/^└ /', '', $news_cat_plat[$i]['name']));
        $tpl->setLoop('catalog', array('cat_id'=>$news_cat_plat[$i]['cat_id'], 'web_id'=>$news_cat_plat[$i]['web_id'], 'name'=>$news_cat_plat[$i]['name'], 'view_lvl'=>$news_cat_plat[$i]['view_lvl'], 'selected'=>($cat_id==$news_cat_plat[$i]['cat_id']?'selected':'')));
		$tpl->setLoop('cat_sub', array('cat_id'=>$news_cat_plat[$i]['cat_id'], 'prefix'=>$news_cat_plat[$i]['prefix']));
	}
	$tpl->assign('get_remote_file', $s->content->get_remote_img?'checked':'');
	$tpl->assign('method', $method);
	$tpl->assign('web_id', $web_info['web_id']);
	$tpl->assign('cat_id', $cat_id);
	$tpl->assign('news_id', $id);
	$tpl->assign('back_url', r::svr('HTTP_REFERER'));
    setWeb($tpl, $web_info['web_id']);
    return $mystep->render($tpl);
}