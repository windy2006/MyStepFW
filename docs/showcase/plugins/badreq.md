# 请求过滤

## 定位和适用场景

管理请求规则、封禁及日志。priority.php安装后位于config/priority，在应用／会话常规启动之前执行。

## 当前目录及主要文件

源码位于`plugin/badReq/`。以下为当前源码层级，不表示所有文件都会自动执行：

```text
plugin/badReq/
├── class.php
├── config.php
├── config/
├── ignore
├── index.php
├── info.php
├── language/
├── module/
├── priority.php
├── route.php
├── template/
```

## 入口与执行过程

class.php的安装过程登记路由／优先脚本；priority执行早期检查，module/badReq.php与template/badReq.tpl负责已鉴权管理。

## 配置、模板和资源

设置、语言、模板与asset/files等资源保持在本项目内；共用框架服务通过当前应用上下文调用。配置值与后台字段结构分别维护，表单客户端用checkForm，服务端仍校验权限、方法和CSRF。

## 使用步骤

1. 在测试站核对本项目目录、元信息和所需扩展。
2. 在插件管理中检查安装条件、安装／活动状态，选择所需应用绑定及运行顺序。
3. 先执行只读展示，再在独占测试资源中验证保存或管理动作。
4. 检查模板风格、窄屏、资源地址、会话与失败反馈；不在正式数据上试验安装或恢复。

## 边界与维护

启停状态和order控制前置执行，不能在优先脚本假定$this或常规登录上下文。ban_ip.php、data.db及锁属于运行数据。

[框架指南总览](../../README.md)
