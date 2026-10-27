# MySQL备份

## 定位和适用场景

通过独立连接备份、导入和维护选定数据库／表，不替换当前宿主$db。临时文件按操作隔离。

## 当前目录及主要文件

源码位于`plugin/myBackup/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
plugin/myBackup/
├── class.php
├── config.php
├── config/
├── index.php
├── info.php
├── language/
├── module/
├── route.php
├── template/
```

## 入口与执行过程

plugin_myBackup::main → showPluginPage → module/myBackup.php与template/myBackup.tpl。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 在插件管理中检查安装条件、安装／活动状态，选择所需应用绑定及运行顺序。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 边界与维护

恢复、删除和表维护会改变数据，先选择正确数据库并备份。备份包含业务数据，应保存在受限目录，不开放匿名下载。

[框架指南总览](../../README.md)
