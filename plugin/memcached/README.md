# Memcached缓存（memcached） <small>v1.0.4</small>

## 功能概述

将绑定应用的缓存接入Memcached，并同步数据库查询缓存引用。

## 安装与设置

安装前准备PHP Memcached扩展及服务，填写节点后启用并绑定业务应用。myStep核心后台不在普通插件初始化时替换缓存；应用已明确选择Redis时也不覆盖。Session的Memcached驱动使用框架Session配置，不能仅靠绑定此插件切换会话。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-server

**服务器**（`server`）

JSON数组，每项包含server主机、port端口和weight权重。地址不含协议；多个节点分别添加对象。使用本机或受控网络中的Memcached服务。

示例：

```text
[{"server":"127.0.0.1","port":11211,"weight":5}]
```

### setting-expire

**过期时间**（`expire`）

缓存有效秒数，用于创建缓存实例时的默认配置；具体业务可在写缓存时指定期限。

示例：

```text
86400
```

### setting-timeout

**超时时间**（`timeout`）

连接超时秒数，控制连接不可用节点的等待时间。

示例：

```text
1
```

### setting-retry_interval

**重试频率**（`retry_interval`）

失效节点的重试间隔秒数，避免每次访问都立即重新连接失败节点。

示例：

```text
10
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
