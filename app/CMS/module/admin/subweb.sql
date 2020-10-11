/*
MyStep 网站系统数据库结构
14:05 2010-12-23 By Windy2000
*/

# ---------------------------------------------------------------------------------------------------------------

Create DataBase if not exists {db_name};
use {db_name};

# 新闻描述
CREATE TABLE `{pre}news_show` (
    `news_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cat_id` SMALLINT UNSIGNED NOT NULL COMMENT '新闻类型索引',
    `web_id` TINYINT UNSIGNED DEFAULT 0 COMMENT '所属子站',
    `subject` Char(200) NOT NULL COMMENT '新闻标题',
    `style` Char(40) NOT NULL DEFAULT '' COMMENT '标题样式',
    `views` MEDIUMINT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
    `describe` Char(255) DEFAULT '' COMMENT '新闻描述',
    `original` Char(40) NOT NULL DEFAULT '' COMMENT '作者/出处',
    `link` Char(255) DEFAULT '' COMMENT '跳转网址',
    `tag` Char(120) NOT NULL DEFAULT '' COMMENT '相关索引',
    `image` Char(200) NOT NULL DEFAULT '' COMMENT '相关图片',
    `setop` SMALLINT UNSIGNED COMMENT '推送模式',
    `order` TINYINT UNSIGNED COMMENT '列表排序',
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
