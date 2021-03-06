/*
MyStep 网站系统数据库结构
14:05 2019-2-1 By Windy2000
*/

# ---------------------------------------------------------------------------------------------------------------

drop DataBase if exists {db_name};
Create DataBase if not exists {db_name} default charset {charset} COLLATE {charset_collate};
use {db_name};

# 网站列表
CREATE TABLE `{pre}website` (
    `web_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '网站编号',
    `name` Char(200) DEFAULT '' NOT NULL COMMENT '网站名称',
    `idx` Char(20) DEFAULT '' NOT NULL COMMENT '字符索引',
    `domain` Char(150) DEFAULT '' NOT NULL COMMENT '网站域名',
    PRIMARY KEY (`web_id`),
    UNIQUE (`idx`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='网站列表';

INSERT INTO `{pre}website` VALUES (1, '{web_name}', 'main', '');
# ---------------------------------------------------------------------------------------------------------------

# 管理目录
CREATE TABLE `{pre}admin_cat` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '目录索引',
    `pid` SMALLINT UNSIGNED DEFAULT 0 COMMENT '父目录索引',
    `name` Char(40) DEFAULT '' NOT NULL COMMENT '目录名称',
    `path` Char(100) DEFAULT '' NOT NULL COMMENT '管理文件路径',
    `public` Tinyint(1) DEFAULT 0 COMMENT '显示于子站',
    `order` TINYINT UNSIGNED DEFAULT 0 COMMENT '显示顺序',
    `comment` Char(255) DEFAULT '' NOT NULL COMMENT '目录说明',
    INDEX `order` (`order`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='管理目录';

INSERT INTO `{pre}admin_cat` VALUES
        (1, 0, '首页', '###', 1, 9, '管理首页'),
        (2, 0, '内容', '###', 0, 8, '内容管理'),
        (3, 0, '功能', '###', 0, 7, '网站功能'),
        (4, 0, '设置', '###', 0, 6, '网站管理'),
        (5, 0, '用户', '###', 0, 5, '用户管理'),

        (0, 1, '流量统计', 'info/count', 0, 0, '简单网站访问统计'),
        (0, 1, '管理日志', 'info/log', 0, 0, '管理日志'),

        (0, 2, '内容分类', 'article/catalog', 1, 0, '文章分类管理'),
        (0, 2, '文章内容', 'article/content', 1, 0, '文章内容管理'),
        (0, 2, '标签管理', 'article/tag', 1, 0, '文章标签管理'),
        (0, 2, '页面信息', 'article/custom', 1, 0, '用户自定义内容'),

        (0, 3, '友情链接', 'function/link', 1, 0, '友情链接管理'),
        (0, 3, '数据维护', 'function/backup', 0, 0, '数据维护'),
        (0, 3, '模板管理', 'function/template', 0, 0, '模板管理'),

        (0, 4, '子站管理', 'setting/subweb', 1, 0, '子站管理'),
        (0, 4, '管理账户', 'setting/sysop', 0, 0, '管理员维护'),
        (0, 4, '管理群组', 'setting/sysgroup', 0, 0, '管理群组维护'),

        (0, 5, '用户管理', 'user/detail', 0, 0, '网站用户维护'),
        (0, 5, '用户群组', 'user/group', 0, 0, '用户群组维护'),
        (0, 5, '用户权限', 'user/power', 0, 0, '用户权限维护'),
        (0, 5, '在线用户', 'user/online', 0, 0, '在线用户');
# ---------------------------------------------------------------------------------------------------------------

# 新闻分类
CREATE TABLE `{pre}news_cat` (
    `cat_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类索引',
    `web_id` TINYINT UNSIGNED DEFAULT 0 COMMENT '所属子站',
    `pid` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主分类索引',
    `name` Char(40) NOT NULL COMMENT '分类名称',
    `keyword` Char(100) NOT NULL COMMENT '分类关键字',
    `comment` Char(255) NOT NULL COMMENT '分类描述',
    `idx` Char(20) DEFAULT '' COMMENT '分类索引（用于目录名等）',
    `image` Char(200) DEFAULT '' COMMENT '分类图示',
    `prefix` Char(240) DEFAULT '' COMMENT '前缀列表（半角逗号间隔）',
    `order` SMALLINT UNSIGNED DEFAULT 1 COMMENT '分类排序',
    `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类显示模式（0 标题列表，1 图片简介，2 图片展示）',
    `link` Char(255) DEFAULT '' COMMENT '分类链接',
    `layer` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类层级',
    `show` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '显示位置（0 不显示，以二进制模式扩充）',
    `view_lvl` Char(10) NOT NULL DEFAULT '0' COMMENT '阅读权限',
    INDEX (`idx`),
    PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='新闻分类';

INSERT INTO `{pre}news_cat` VALUES (0, 1, 0, '新闻资讯', '新闻,体育,娱乐,财经,IT,汽车,房产,女人', '新闻资讯 时事报道 体育新闻 健康生活', 'news', '', '时事,娱乐,体育,健康', 1, 0, '', 1, 255, 0);
# ---------------------------------------------------------------------------------------------------------------

# 新闻描述
CREATE TABLE `{pre}news_show` (
    `news_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `idx` Char(32) NOT NULL COMMENT '标题hash',
    `cat_id` SMALLINT UNSIGNED NOT NULL COMMENT '新闻类型索引',
    `web_id` TINYINT UNSIGNED DEFAULT 0 COMMENT '所属子站',
    `subject` Char(200) NOT NULL COMMENT '新闻标题',
    `style` Char(40) NOT NULL DEFAULT '' COMMENT '标题样式',
    `views` MEDIUMINT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
    `describe` Char(255) DEFAULT '' COMMENT '新闻描述',
    `original` Char(100) NOT NULL DEFAULT '' COMMENT '作者/出处',
    `link` Char(255) DEFAULT '' COMMENT '跳转网址',
    `tag` Char(120) NOT NULL DEFAULT '' COMMENT '文章标签',
    `image` Char(200) NOT NULL DEFAULT '' COMMENT '文章图示',
    `setop` TINYINT UNSIGNED COMMENT '推送模式',
    `order` TINYINT UNSIGNED DEFAULT 0 COMMENT '列表排序',
    `view_lvl` Char(10) NOT NULL DEFAULT '0' COMMENT '阅读权限',
    `pages` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '新闻页数',
    `add_user` Char(20) NOT NULL COMMENT '录入人',
    `add_date` DATETIME DEFAULT '0000-00-00 00:00:00' COMMENT '录入日期',
    `active` DATE COMMENT '激活时间',
    `expire` DATE COMMENT '过期时间',
    INDEX `catalog` (`web_id`, `cat_id`),
    INDEX `order` (`order`, `news_id`),
    PRIMARY KEY (`news_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='新闻描述';

# ---------------------------------------------------------------------------------------------------------------

# 新闻内容
CREATE TABLE `{pre}news_detail` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `news_id` MEDIUMINT UNSIGNED NOT NULL COMMENT '新闻索引',
    `cat_id` SMALLINT UNSIGNED NOT NULL COMMENT '新闻类型索引',
    `page` TINYINT UNSIGNED DEFAULT 1 COMMENT '分页索引',
    `sub_title` Char(200) DEFAULT '' COMMENT '子标题',
    `content` MEDIUMTEXT NOT NULL COMMENT '新闻内容',
    INDEX (`news_id`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='新闻内容';

# ---------------------------------------------------------------------------------------------------------------

# 新闻关键字（用于进行搜索统计排名）
CREATE TABLE `{pre}news_tag` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tag` Char(120) NOT NULL COMMENT '关键字',
    `count` MEDIUMINT UNSIGNED DEFAULT 0 COMMENT '出现次数',
    `click` MEDIUMINT UNSIGNED DEFAULT 0 COMMENT '点击次数',
    `add_date` Char(15) DEFAULT 0 COMMENT '关键字添加日期（unixtimestamp）',
    `update_date` Char(15) DEFAULT 0 COMMENT '关键字更新日期（unixtimestamp）',
    INDEX (`count`),
    INDEX (`click`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='新闻关键字';

# ---------------------------------------------------------------------------------------------------------------

# 内容展示
CREATE TABLE `{pre}info` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `web_id` TINYINT UNSIGNED DEFAULT 0 COMMENT '所属子站',
    `idx` Char(100) NOT NULL COMMENT '索引',
    `content` MEDIUMTEXT NOT NULL COMMENT '内容',
    INDEX (`web_id`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='内容展示';
INSERT INTO `{pre}info` VALUES (0, 1, 'copyright', '<p style="text-align: center;">&copy;2010-2021&nbsp;www.mysteps.cn</p>');
INSERT INTO `{pre}info` VALUES (0, 1, 'contact', '<p>QQ：18509608</p><p>Email：windy_sk@126.com</p>');

# ---------------------------------------------------------------------------------------------------------------

# 友情链接表
CREATE TABLE `{pre}links` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `web_id` TINYINT UNSIGNED DEFAULT 0 COMMENT '所属子站',
    `idx` Char(20) DEFAULT '' NOT NULL COMMENT '分类索引',
    `name` Char(100) NOT NULL COMMENT '链接名称',
    `url` Char(100) NOT NULL COMMENT '链接地址',
    `image` Char(100) COMMENT '链接图形（空视为文字链接）',
    `level` TINYINT UNSIGNED DEFAULT 0 COMMENT '显示级别（0 为不显示）',
    INDEX (`idx`),
    INDEX (`level`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='友情链接表';

# ---------------------------------------------------------------------------------------------------------------

# 网站用户
CREATE TABLE `{pre}sys_op` (
    `id` mediumint(8) unsigned auto_increment,
    `group_id` TINYINT UNSIGNED NOT NULL Default 0 COMMENT '管理组索引',
    `username` varchar(15) UNIQUE COMMENT '用户名',
    `password` varchar(40)  COMMENT '密码',
    `email` varchar(60)  COMMENT '电子邮件',
    INDEX (`username`),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='网站管理员';

# ---------------------------------------------------------------------------------------------------------------

# 管理组权限
CREATE TABLE `{pre}sys_group` (
    `group_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` Char(20) NOT NULL UNIQUE COMMENT '管理组名称',
    `power_func` Char(255) NOT NULL COMMENT '功能权限',
    `power_cat` Char(255) NOT NULL COMMENT '栏目权限',
    `power_web` Char(255) NOT NULL COMMENT '子站权限',
    PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='管理组权限';

INSERT INTO `{pre}sys_group` VALUES (0, '管理员', 'all', 'all', 'all');

# ---------------------------------------------------------------------------------------------------------------

# 维护日志
CREATE TABLE `{pre}sys_log` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user` Char(40) NOT NULL COMMENT '管理员',
    `group` Char(40) NOT NULL COMMENT '管理组',
    `time` Char(15) DEFAULT 0 COMMENT '发生时间（unixtimestamp）',
    `link` Char(255) COMMENT '访问页面',
    `comment` Char(100) DEFAULT '' COMMENT '操作内容',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='网站维护日志';

# ---------------------------------------------------------------------------------------------------------------

# 访客组权限（可通过用户组权限扩展）
CREATE TABLE `{pre}user_group` (
    `group_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` Char(20) NOT NULL UNIQUE COMMENT '访客组名称',
    PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='访客组权限';

INSERT INTO `{pre}user_group` VALUES (1, '一般访客');
INSERT INTO `{pre}user_group` VALUES (2, '普通用户');
INSERT INTO `{pre}user_group` VALUES (3, '高级用户');

# ---------------------------------------------------------------------------------------------------------------

# 用户组权限
CREATE TABLE `{pre}user_power` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `idx` Char(20) NOT NULL UNIQUE COMMENT '权限索引',
    `name` Char(40) NOT NULL UNIQUE COMMENT '权限名称',
    `value` Char(255) NOT NULL COMMENT '权限默认值',
    `format` Char(20) NOT NULL COMMENT '要求格式',
    `comment` Char(255) COMMENT '权限描述',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='会员组权限';

INSERT INTO `{pre}user_power` VALUES (0, 'view_lvl', '阅读权限', '0', 'digital', '用于文章阅读时的权限判断');
alter table `{pre}user_group` add `view_lvl` Char(10) NOT NULL DEFAULT '0';
update `{pre}user_group` set `view_lvl`='1' where `group_id`=2;
update `{pre}user_group` set `view_lvl`='5' where `group_id`=3;

# ---------------------------------------------------------------------------------------------------------------

# 网站用户
CREATE TABLE `{pre}users` (
    `user_id` mediumint(8) unsigned auto_increment,
    `group_id` TINYINT UNSIGNED NOT NULL Default 0 COMMENT '用户组',
    `username` varchar(15) UNIQUE COMMENT '用户名',
    `password` varchar(40)  COMMENT '密码',
    `email` varchar(60)  COMMENT '电子邮件',
    `reg_date` int(10) unsigned Default "0"  COMMENT '注册日期',
    `hash` varchar(32)  COMMENT '哈希码',
    INDEX (`username`),
    PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='网站用户';

# ---------------------------------------------------------------------------------------------------------------

# 网站当前浏览者
CREATE TABLE `{pre}user_online` (
    `sid` char(32) UNIQUE NOT NULL COMMENT 'SessionID',
    `ip` Char(50) NOT NULL UNIQUE COMMENT 'ip地址',
    `refresh` Char(15) DEFAULT 0 COMMENT '最近刷新时间（unixtimestamp）',
    `url` Char(200) COMMENT '当前访问页面',
    `data` Char(255) default 0 COMMENT '用户信息',
    PRIMARY KEY (`sid`)
) ENGINE=HEAP DEFAULT CHARSET={charset} COMMENT='网站当前浏览者';

# ---------------------------------------------------------------------------------------------------------------

# 访问统计
CREATE TABLE `{pre}counter` (
    `date` DATE NOT NULL UNIQUE COMMENT '统计日期',
    `pv` MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL COMMENT '页面访问量',
    `iv` MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL COMMENT 'IP 访问量',
    `online` MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL COMMENT '最大在线人数',
    PRIMARY KEY (`date`)
) ENGINE=MyISAM DEFAULT CHARSET={charset} COMMENT='简单访问统计';

# ---------------------------------------------------------------------------------------------------------------
