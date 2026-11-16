# MySQL备份（myBackup） <small>v1.0.2</small>

## 功能概述

通过独立MySQL连接导出、导入、优化及修复数据表，支持单表SQL与整库ZIP。

## 安装与设置

配置数据库连接后安装启用，从myBackup/进入。先选择数据库和表，再选择动作。导入会执行备份中的SQL，执行前保留独立备份；整库优化、修复只自动筛选MyISAM表。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-host

**服务器地址**（`host`）

MySQL服务器主机名或IP。连接由插件独立建立，不会修改框架当前业务数据库连接。

示例：

```text
127.0.0.1
```

### setting-user

**数据库用户**（`user`）

用于备份和维护的数据库用户，需要目标库相应权限。仅导出时按需分配读取及查看表结构权限。

示例：

```text
backup_user
```

### setting-password

**数据库密码**（`password`）

对应数据库用户的密码；填写两次且保持一致，留空保留原值。不要把真实密码写入说明文档。

示例：

```text
ExampleOnly_2026
```

### setting-db

**所用数据库**（`db`）

默认选中的数据库名称。后台实际可选范围由该数据库用户的权限决定。

示例：

```text
example_cms
```

### setting-charset

**数据库编码**（`charset`）

连接数据库使用的字符集，页面提供GBK、UTF-8、Latin1选项；按目标库实际编码选择。

示例：

```text
utf-8
```

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-db

**目标数据库**（`db`）

从当前用户可访问的库中选择操作对象。

示例：

```text
example_cms
```

### field-table

**目标数据表**（`table`）

选择单表或all全部表；导入时以备份SQL内容为准。

示例：

```text
cms_article
```

### field-method

**维护动作**（`method`）

export导出、import导入、optimize优化、repair修复。先导出备份再执行会改变数据的动作。

示例：

```text
export
```

### field-the_file

**导入文件**（`the_file`）

导入时选择SQL或ZIP备份，大小受页面显示的PHP上传限制约束。

示例：

```text
example_cms.sql
```

数据库连接参数留空时，管理模块会使用框架当前数据库配置的对应值；需要备份其他数据库时请显式填写独立连接。

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
