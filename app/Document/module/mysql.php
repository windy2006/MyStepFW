<?PHP
global $db;
$db->connect(0, 'mystep');
$db->reconnect(1, 'mystep_cms');

//select
$db->build('cms_news_cat')
    ->field('name')
    ->exists(['cms_website', 'web_id'], ['web_id', 'n=', 1])
    ->where('cat_id', 'n=', 1);
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$field = $db->getFields('cms_admin_cat');
$db->build('cms_admin_cat')->field($field)->where('id', 'n>', '10')->order('id', 1)->limit(5, 6);
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
                            ))->field('sub_title,content')->where('page', 'n>=', '1')->order('page');
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$db->build('cms_news_show')->field('*')->where('subject', '<>', 'xxxx')
                                      ->where('news_id', 'n=', '1')
                                      ->where('add_date', 'd>', array('now()', 'y-1'))
                                      ->where('subject', 'f=', 'left(subject, 10)')
                                      ->where('tag', 'like', '1')
                                      ->where('news_id', 'nin', '1, 2, a3, 5a, 5')
                                      ->where('image')
                                      ->where('(isnull(style))')
                                      ->limit(5);
$db->build('cms_news_detail', array(
                              'mode' => 'left',
                              'field' => 'news_id'
                            ))->field('sub_title,content')->where('page', 'n>=', '1')->order('page');
echo $db->select(1).';<br /><br />';

$db->build('[reset]');
$db->build('table_name')->field(['field_name', 'count(*) as cnt'])
    ->group('field_name')
    ->group(['field_name', 'cnt>3'])
    ->group('field_name', ['cnt','n>',3])
    ->group(['field'=>'field_name,field2_name', 'having'=>'cnt>3']);
$db->build('tbl2', array(
    'mode' => 'left',
    'field' => 'news_id'
))->field('col1, col2');
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
    ))
    ->values(0,0,'xxx','xxx','','000','000','111222')
    ->values(
        [0,0,'xxx','xxx','','001','001','111222'],
        [0,0,'xxx','xxx','','002','002','111222']
    );
echo $db->insert(1).';<br /><br />';
echo $db->replace(1).';<br /><br />';

//update
$db->build('[reset]');
$db->build('cms_news_show')
    ->field(array('path'=>''))
    ->where('news_id', 'n>', '10')
    ->order('news_id', 1)->limit(5, 6);
echo $db->update(1).';<br /><br />';
$db->build('cms_news_show')->reset();

$db->build('cms_news_show')->field(array(
                            'views' => 5,
                            'tag' => 'tag',
                            'add_date'=>'(now())'
                          ))->where('subject', '<>', 'xxxx')
                            ->where('news_id', 'n=', '1')
                            ->where('news_id', 'n=', '2', 'or')
                            ->where('add_date', 'd>', array('now()', 'y-1', ))
                            ->where('(isnull(style))')
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
                            ->where('(isnull(style))')
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

//create
echo $db->create('my_db', '', 'db', 1).'<br /><br />';
echo $db->create('my_tbl', 'my_col_1, my_col_2', 'idx', 1).'<br /><br />';
echo $db->create('my_tbl', 'tbl2', 'tbl', 1).'<br /><br />';
echo $db->create('my_table', 'my_col char(1000)', 'tbl', 1).'<br /><br />';
echo $db->create('my_table', [
    'col' => [
        'id int identity(1,1)',
        'my_col_1 char(1000)',
        'my_col_2 int',
    ],
    'pri' => 'id',
    'uni' => 'my_col_2',
    'idx' => 'my_col_2',
    'charset' => 'GBK',
    'comment' => 'comments'
], 'tbl', 1);
