<?PHP
$setting_detail = array();

$setting_detail['gen'] = array(
    'name' => '基本设置',
    'list' => [
        'language' => array(
            'name' => '网站语言',
            'describe' => '网站显示语种',
            'type' => array('text', 'alpha', '10')
        ),
        'charset' => array(
            'name' => '网站编码',
            'describe' => '网站显示编码集',
            'type' => array('text', 'word', '10')
        ),
        'timezone' => array(
            'name' => '时区矫正',
            'describe' => '设定网站显示时区',
            'type' => array('select', array('GMT-12'=>'Etc/GMT+12', 'GMT-11'=>'Etc/GMT+11', 'GMT-10'=>'Etc/GMT+10', 'GMT-9'=>'Etc/GMT+9', 'GMT-8'=>'Etc/GMT+8', 'GMT-7'=>'Etc/GMT+7', 'GMT-6'=>'Etc/GMT+6', 'GMT-5'=>'Etc/GMT+5', 'GMT-4'=>'Etc/GMT+4', 'GMT-3'=>'Etc/GMT+3', 'GMT-2'=>'Etc/GMT+2', 'GMT-1'=>'Etc/GMT+1', 'GMT'=>'Etc/GMT', 'GMT+1'=>'Etc/GMT-1', 'GMT+2'=>'Etc/GMT-2', 'GMT+3'=>'Etc/GMT-3', 'GMT+4'=>'Etc/GMT-4', 'GMT+5'=>'Etc/GMT-5', 'GMT+6'=>'Etc/GMT-6', 'GMT+7'=>'Etc/GMT-7', 'GMT+8'=>'Etc/GMT-8', 'GMT+9'=>'Etc/GMT-9', 'GMT+10'=>'Etc/GMT-10', 'GMT+11'=>'Etc/GMT-11', 'GMT+12'=>'Etc/GMT-12'))
        ),
        'cache_mode' => array(
            'name' => '数据缓存',
            'describe' => '开启数据缓存，减少数据重复查询，以提高效率',
            'type' => array('select', array('文件缓存'=>'File', '数据库缓存'=>'MySQL'))
        ),
        'cache_page' => array(
            'name' => '页面缓存',
            'describe' => '开启页面缓存，减少固定时间内的查询频率，增强网站效率',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        's_usr' => array(
            'name' => '管 理 员',
            'describe' => '拥有全部权限，且不依赖数据库',
            'type' => array('text', 'alpha', '16')
        ),
        's_pwd' => array(
            'name' => '管理密码',
            'name2' => '重复密码',
            'describe' => '请确认密码的安全可靠',
            'type' => array('password', 'md5', '40')
        ),
        'close' => array(
            'name' => '关闭页面',
            'describe' => '如设置，网站所有页面将均转向到这里',
            'type' => array('text', '_', '80')
        ),
        'static' => array(
            'name' => '静态文件',
            'describe' => '可直接通过url调用的文件类型',
            'type' => array('text', '', '80')
        ),
        'debug' => array(
            'name' => '除错模式',
            'describe' => '显示更多的错误信息',
            'type' => array('radio', array('启用'=>'true', '关闭'=>'false'))
        ),
    ]
);

$setting_detail['web'] = array(
    'name' => '站点设置',
    'list' => [
        'title' => array(
            'name' => '网站名称',
            'describe' => '用于在浏览器上显示网站名称',
            'type' => array('text', 'name', '40')
        ),
        'keyword' => array(
            'name' => '网站关键字',
            'describe' => '用于搜索引擎检索网站，多个关键字请用“, ”间隔',
            'type' => array('text', '', '60')
        ),
        'description' => array(
            'name' => '网站描述',
            'describe' => '用于搜索引擎的网站简介',
            'type' => array('text', '', '100')
        ),
        'gzip_level' => array(
            'name' => '压缩级别',
            'describe' => 'GZIP 压缩页面的级别（0-9），0 为关闭压缩',
            'type' => array('text', 'digital', '1')
        ),
        'minify' => array(
            'name' => '页面压缩',
            'describe' => 'HTML、CSS及JS代码压缩，以减少页面传送时间',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'etag' => array(
            'name' => '过期标识',
            'describe' => '用于 Etag 标识，减少未更改页面的传输',
            'type' => array('text', '', '20')
        ),
        'css' => array(
            'name' => '样式表文件',
            'describe' => '以半角单引号分割，依次加载static目录下的对应文件，所有页面都将自动加载',
            'type' => array('text', '', '150')
        ),
        'js' => array(
            'name' => '脚本文件',
            'describe' => '以半角单引号分割，依次加载static目录下的对应文件，所有页面都将自动加载',
            'type' => array('text', '', '150')
        ),
        'update' => array(
            'name' => '更新网址',
            'describe' => '获取内容系统程序更新的网址',
            'type' => array('text', '_', '200')
        ),
    ]
);

$setting_detail['upload'] = array(
    'name' => '文件上传',
    'list' => [
        'path_mode' => array(
            'name' => '路径模式',
            'describe' => '如何根据时间划分目录，参考data函数的时间格式',
            'type' => array('text', '', '10')
        ),
        'ban_ext' => array(
            'name' => '特殊文件',
            'describe' => '将对相关扩展名的文件做特殊处理（不影响下载）',
            'type' => array('text', '', '50')
        ),
        'free_dl' => array(
            'name' => '自由下载',
            'describe' => '是否限制外网直连下载',
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
        'expire' => array(
            'name' => '过期时间',
            'describe' => '用户保持在线的最长时间',
            'type' => array('text', 'digital', '2')
        ),
        'name' => array(
            'name' => '名称设置',
            'describe' => '程序用于存储Session索引的名称',
            'type' => array('text', 'alpha', '20')
        ),
        'mode' => array(
            'name' => '处理模式',
            'describe' => '存储Session的模式（非mystep模式会影响到在线统计）',
            'type' => array('select', array('MyStep模式'=>'sess_mystep', '数据库存储'=>'sess_mysql', '文件存储'=>'sess_file'))
        ),
        'path' => array(
            'name' => '存储路径',
            'describe' => 'SESSION文件的存放位置',
            'type' => array('text', '', '30')
        ),
        'gc' => array(
            'name' => '定期回收',
            'describe' => '定期删除无用的Session信息',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'trans_sid' => array(
            'name' => '传递 SID',
            'describe' => '通过URL连接传递SID，主要用于用户关闭COOKIE的情况',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
    ]
);

$setting_detail['cookie'] = array(
    'name' => 'Cookie 设置',
    'list' => [
        'prefix' => array(
            'name' => 'Cookie前缀',
            'describe' => '存储Cookie的变量前缀，用于防止用户密码欺骗',
            'type' => array('text', 'alpha', '10')
        ),
        'domain' => array(
            'name' => '作用域名',
            'describe' => 'Cookie仅在该域名下生效',
            'type' => array("text", "_", "20")
        ),
    ]
);

$setting_detail['router'] = array(
    'name' => '路由设置',
    'list' => [
        'mode' => array(
            'name' => '解析模式',
            'describe' => '路径解析模式',
            'type' => array('select', array('QueryString'=>'query_string', 'PathInfo'=>'path_info', 'Rewrite'=>'rewrite'))
        ),
        'default_app' => array(
            'name' => '默认APP',
            'describe' => '在无参数情况下运行的APP',
            'type' => array('text', 'name', '50'),
        ),
        'delimiter_path' => array(
            'name' => '路径分隔符',
            'describe' => '用于分割程序执行路径',
            'type' => array('text', '', '2'),
        ),
        'delimiter_para' => array(
            'name' => '参数分隔符',
            'describe' => '用于分割程序参数',
            'type' => array('text', '', '2'),
        ),
    ]
);

$setting_detail['email'] = array(
    'name' => '电子邮件参数设置',
    'list' => [
        'mode' => array(
            'name' => '发送模式',
            'describe' => '选择发送邮件及服务器认证的方式',
            'type' => array('select', array('SMTP验证'=>'smtp', 'SSL 验证'=>'ssl', 'TLS 验证'=>'tls', 'SSL/TLS 混合验证'=>'ssl/tls'))
        ),
        'host' => array(
            'name' => '服务器地址',
            'describe' => '发送邮件所用SMTP服务器',
            'type' => array('text', false, '30')
        ),
        'port' => array(
            'name' => '服务器端口',
            'describe' => 'SMTP服务器端口',
            'type' => array('text', 'digital_', '5')
        ),
        'user' => array(
            'name' => '服务器账户',
            'describe' => '服务器所分配的邮件账户',
            'type' => array('text', false, '30')
        ),
        'password' => array(
            'name' => '服务器密码',
            'describe' => '对应邮件账户的密码',
            'type' => array('text', false, '40')
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
            'type' => array('password', '_', '40')
        ),
        'pconnect' => array(
            'name' => '持久连接',
            'describe' => '数据库存储数据的默认字符集',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'charset' => array(
            'name' => '数据库编码',
            'describe' => '开启数据库持久连接，减少反复连接数据库造成的资源消耗',
            'type' => array('select', array('GBK'=>'gbk', 'UTF-8'=>'utf-8', 'Latin1'=>'latin1'))
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

return $setting_detail;