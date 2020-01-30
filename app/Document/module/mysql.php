<?php
global $db;
$db->connect(0, 'mystep');
$db->reconnect(1, 'mystep');
$db->changUser('root', 'cfnadb!@#$%', 'mystep');

$the_tbl = 'cms_news_show';

//select
$db->build('cms_news_cat')
    ->field('name')
    ->exists(['cms_website', 'web_id'], ['web_id', 'n=', 1])
    ->where('cat_id', 'n=', 1);
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$db->build('cms_admin_cat')->field($db->getFields('cms_admin_cat'))->where('id', 'n>', '10')->order('id', 1)->limit(5, 6);
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$db->build('cms_news_show')->where(
  array(
    array('news_id', 'n=', '1', 'or'), 
    array(
      array('tag', 'like', '1', 'or'), 
      array('tag', 'like', '2%', 'or'), 
      array('tag', 'like', '%3', 'or'), 
      'and'
    ), 
    array(
      array('tag', 'like', '1', 'or'), 
      array('tag', 'like', '2%', 'or'), 
      array('tag', 'like', '%3', 'or'), 
      'or'
    ), 
    array('add_date', 'd>', array('now()', 'y-1'), 'or'), 
    'and'
  ), 
  'and'
)->order('news_id', 1)->limit(5, 6);
$db->build('cms_news_detail', array(
                              'mode' => 'left', 
                              'field' => 'news_id'
                            ))->field('sub_title, content')->where('page', 'n>=', '1')->order('page');
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$db->build('cms_news_show')->field('*')->where('subject', '<>', 'xxxx')
                                      ->where('news_id', 'n=', '1')
                                      ->where('add_date', 'd>', array('now()', 'y-1'))
                                      ->where('(0)', 'f=', 'isnull(style)')
                                      ->where('tag', 'like', '1')
                                      ->where('news_id', 'nin', '1, 2, a3, 5a, 5')
                                      ->where('image')
                                      ->where('(1=1)')
                                      ->limit(5);
$db->build('cms_news_detail', array(
                              'mode' => 'left', 
                              'field' => 'news_id'
                            ))->field('sub_title, content')->where('page', 'n>=', '1')->order('page');
echo $db->select(1).';<br /><br />';

//insert
$db->build('[reset]');
$db->build('cms_admin_cat')->field(array(
  'id' => 0, 
  'pid' => '0', 
  'name' => 'xxx', 
  'file' => 'xxx', 
  'path' => '', 
  'web_id' => '0', 
  'order' => '0', 
  'comment' => '010101'
));
echo $db->insert(1).';<br /><br />';
echo $db->replace(1).';<br /><br />';

//update
$db->build('[reset]');
$db->build('cms_news_show')->field(array('path'=>''))->where('news_id', 'n>', '10')->order('news_id', 1)->limit(5, 6);
echo $db->update(1).';<br /><br />';
$db->build('cms_news_show')->reset();
$db->build('cms_news_show')->field(array(
                            'views' => 5, 
                            'tag' => 'tag'
                          ))->where('subject', '<>', 'xxxx')
                            ->where('news_id', 'n=', '1')
                            ->where('news_id', 'n=', '2', 'or')
                            ->where('add_date', 'd>', array('now()', 'y-1', ))
                            ->where('(0)', 'f=', 'isnull(style)')
                            ->where('tag', 'like', '1')
                            ->where('news_id', 'nin', '1, 2, a3, 5a, 5')
                            ->where('image');
$db->build('cms_news_detail', array(
                              'mode' => 'left', 
                              'field' => 'news_id', 
                              'field_join' => 't0.news_id'
                            ))->where('page', 'n>=', '1')->order('page');
echo $db->update(1).';<br /><br />';

//delete
$db->build('[reset]');
$db->build('cms_admin_cat')->where('id', 'n>', '38')->order('id', 1)->limit(5);
echo $db->delete(1).';<br /><br />';
$db->build('[reset]');
$db->build('cms_news_show')->where('subject', '<>', 'xxxx')
                            ->where('news_id', 'n=', '1')
                            ->where('add_date', 'd>', array('now()', 'y-1', ))
                            ->where('(0)', 'f=', 'isnull(style)')
                            ->where('tag', 'like', '1')
                            ->where('news_id', 'nin', '1, 2, a3, 5a, 5')
                            ->where('image');
$db->build('cms_news_detail', array(
                              'mode' => 'left', 
                              'field' => 'news_id', 
                              'field_join' => 't0.news_id'
                            ))->where('page', 'n>=', '1')->order('page');
echo $db->delete(1).';<br /><br />';

//echo $db->close();