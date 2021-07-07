<?PHP
$setting_detail = array();

$setting_detail['web'] = array(
    'name' => '站点设置',
    'list' => [
        'title' => array(
            'name' => '网站名称',
            'describe' => '用于在浏览器上显示网站名称',
            'type' => array('text', 'name', '40')
        ),
        'path_admin' => array(
            'name' => '后台路径',
            'describe' => 'CMS管理后台的路由',
            'type' => array('text', '', '20')
        ),
    ]
);

$setting_detail['db'] = array(
    'name' => '数据库设置',
    'list' => [
        'auto' => array(
            'name' => '自动连接',
            'describe' => '在框架执行时自动建立数据库连接',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'type' => array(
            'name' => '数据库类型',
            'describe' => '选用哪种数据库',
            'type' => array('select', array('MySQL'=>'mysql', 'SQL Server'=>'mssql'))
        ),
        'host' => array(
            'name' => '服务器地址',
            'describe' => '数据库主机的地址',
            'type' => array('text', '', '30')
        ),
        'user' => array(
            'name' => '数据库用户',
            'describe' => '服务器所分配的数据库用户名',
            'type' => array('text', 'alpha', '20')
        ),
        'password' => array(
            'name' => '数据库密码',
            'name2' => '重复密码',
            'describe' => '对应数据库用户的密码',
            'type' => array('password', '', '40')
        ),
        'pconnect' => array(
            'name' => '持久连接',
            'describe' => '数据库存储数据的默认字符集',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'charset' => array(
            'name' => '数据库编码',
            'describe' => '数据库编码集设置',
            'type' => array('select', array('UTF-8'=>'utf-8', 'GBK'=>'gbk', 'Latin1'=>'latin1'))
        ),
        'name' => array(
            'name' => '所用数据库',
            'describe' => '存储网站数据表的数据库名称',
            'type' => array('text', 'alpha', '20')
        ),
        'pre' => array(
            'name' => '数据表前缀',
            'describe' => '用于区分本系统数据表与其他数据表数据表前缀',
            'type' => array('text', 'alpha', '10')
        ),
    ]
);

$setting_detail['content'] = array(
    'name' => '内容设置',
    'list' => [
        'cat_pos' => array(
            'name' => '栏目显示',
            'describe' => '栏目显示位置，以半角逗号间隔',
            'type' => array('text', '', '120')
        ),
        'push_pos' => array(
            'name' => '推送位置',
            'describe' => '将重要文章推送到对应位置，以半角逗号间隔',
            'type' => array('text', '', '120')
        ),
        'push_mode' => array(
            'name' => '推送模式',
            'describe' => '以何种模式进行推送，以半角逗号间隔',
            'type' => array('text', '', '120')
        ),
        'upload' => array(
            'name' => '上传路径',
            'describe' => '上传文件的存放位置',
            'type' => array('text', '', '40')
        ),
        'get_remote_img' => array(
            'name' => '自动下载',
            'describe' => '自动下载文章内容中位于网络位置的文件',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
    ]
);

$setting_detail['template'] = array(
    'name' => '模版设置',
    'list' => [
        'name' => array(
            'name' => '调用模版',
            'describe' => '应用调用的主模版（子模版需要在程序内设置）',
            'type' => array('text', '', '10')
        ),
        'path' => array(
            'name' => '模版路径',
            'describe' => '模版的存放位置（相对于本应用的相对路径）',
            'type' => array('text', '', '30')
        ),
        'style' => array(
            'name' => '模版样式',
            'describe' => '如果程序有多个模版，可通过本选项设置（即为模板路径下的子目录名）',
            'type' => array('text', '_', '20')
        ),
    ]
);

$setting_detail['session'] = array(
    'name' => 'Session 设置',
    'list' => [
        'mode' => array(
            'name' => '处理模式',
            'describe' => '存储Session的模式（非mystep模式会影响到在线统计）',
            'type' => array('select', array('MyStep模式'=>'sess_mystep', '数据库存储'=>'sess_mysql', '文件存储'=>'sess_file'))
        ),
    ]
);

$setting_detail['list'] = array(
    'name' => '列表显示数量设置',
    'list' => [
        'txt' => array(
            'name' => '标题列表',
            'describe' => '文字列表模式下显示新闻的数量',
            'type' => array('text', 'digital', '3')
        ),
        'img' => array(
            'name' => '图片展示',
            'describe' => '图片列表模式下显示新闻的数量',
            'type' => array('text', 'digital', '3')
        ),
        'mix' => array(
            'name' => '图文混合',
            'describe' => '图文混合列表模式下显示新闻的数量',
            'type' => array('text', 'digital', '3')
        ),
    ]
);

$setting_detail['expire'] = array(
    'name' => '静态页面过期时限设置',
    'list' => [
        'default' => array(
            'name' => '默认时限',
            'describe' => '如无特别设置，则应用此时限',
            'type' => array('text', 'digital', '6')
        ),
        'index' => array(
            'name' => '索引页时限',
            'describe' => '索引页过期时间',
            'type' => array('text', 'digital', '6')
        ),
        'catalog' => array(
            'name' => '列表页时限',
            'describe' => '列表页过期时间',
            'type' => array('text', 'digital', '6')
        ),
        'tag' => array(
            'name' => '标签页时限',
            'describe' => '文章标签页过期时间',
            'type' => array('text', 'digital', '6')
        ),
        'article' => array(
            'name' => '内容页时限',
            'describe' => '内容页过期时间',
            'type' => array('text', 'digital', '6')
        ),
    ]
);

$setting_detail['watermark'] = array(
    'name' => '水印设置',
    'list' => [
        'mode' => array(
            'name' => '水印模式',
            'describe' => '是否在文章内容或图片上添加水印',
            'type' => array('checkbox', array('文章水印'=>1, '图片水印'=>2))
        ),
        'txt' => array(
            'name' => '干扰文字',
            'describe' => '用于文章水印的干扰字符串',
            'type' => array('text', 'name', '30')
        ),
        'img' => array(
            'name' => '图片水印',
            'describe' => '用于图片水印的图片或文字',
            'type' => array('text', '', '30')
        ),
        'position' => array(
            'name' => '水印位置',
            'describe' => '用于水印的图片',
            'type' => array('select', array('右下'=>1, '右上'=>2, '左下'=>3, '左上'=>4, '左中'=>5, '右中'=>6, '中上'=>7, '中下'=>8, '正中'=>9))
        ),
        'img_rate' => array(
            'name' => '水印比例',
            'describe' => '用于控制水印图片的大小，数值越大，水印越小',
            'type' => array('text', 'digital', '2')
        ),
        'txt_font' => array(
            'name' => '水印字体',
            'describe' => '水印文字所用到的字体',
            'type' => array('text', '', '40')
        ),
        'txt_fontsize' => array(
            'name' => '字体尺寸',
            'describe' => '水印文字的大小（像素）',
            'type' => array('text', 'digital', '2')
        ),
        'txt_fontcolor' => array(
            'name' => '字体颜色',
            'describe' => '水印文字的颜色，为标准HTML颜色代码（如:#000000）',
            'type' => array('text', '', '7')
        ),
        'txt_bgcolor' => array(
            'name' => '背景颜色',
            'describe' => '文字水印的背景颜色，为标准HTML颜色代码（如:#000000）',
            'type' => array('text', '', '7')
        ),
        'alpha' => array(
            'name' => '水印透明度',
            'describe' => '水印透明度（同时作用于文字水印和图片水印，0-100）',
            'type' => array('text', 'digital', '3')
        ),
        'credit' => array(
            'name' => '版权文字',
            'describe' => '显示在文章内容中的版权文字',
            'type' => array('text', 'name', '30')
        ),
    ]
);

return $setting_detail;