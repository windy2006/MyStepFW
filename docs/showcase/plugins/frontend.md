# frontend：前端组件版本选择

修订日期：2026-10-23。现有插件介绍专栏。

## 定位和适用场景

按应用设置选择现有本地jQuery、migrate及Bootstrap等组件，使用宿主start阶段调整资源集合。

## 当前目录及主要文件

源码位于`plugin/frontend/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
plugin/frontend/
├── class.php
├── config.php
├── config/
├── files/
├── index.php
├── info.php
├── language/
├── module/
├── route.php
├── template/
```

## 入口与执行过程

index.php登记start钩子；config及files资源供按应用选择；module/frontend.php配对template/frontend.tpl。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 在插件管理中检查安装条件、安装／活动状态，选择所需应用绑定及运行顺序。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 边界与维护

后端插件只负责选择与登记；实际JavaScript API见前端专栏。配置失效的一项不应破坏其他应用有效资源，资源路径须受控。

[框架指南总览](../../README.md)
