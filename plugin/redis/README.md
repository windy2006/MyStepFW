# Redis存储（redis） <small>v1.0.2</small>

## 功能概述

为框架缓存、Session及业务提供统一Redis连接、命名空间与锁配置。

## 安装与设置

先准备Redis服务及PHP phpredis扩展（至少5.3），填写连接参数并保存检查。框架缓存选择gen.cache_mode=redis；Session选择session.mode=sess_redis。仅安装或绑定插件不会自动切换存储；仍被缓存或Session引用时不能停用或卸载。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-host

**服务器**（`host`）

Redis主机名或IP，不含协议及端口。服务须可由PHP所在机器访问。

示例：

```text
127.0.0.1
```

### setting-port

**端口**（`port`）

服务端口，范围1至65535；TLS使用服务端配置的实际端口。

示例：

```text
6379
```

### setting-database

**逻辑数据库**（`database`）

Redis逻辑库编号，代码接受0至65535，实际可用范围由服务配置决定。缓存和Session还会使用不同用途前缀。

示例：

```text
0
```

### setting-auth

**启用认证**（`auth`）

服务器要求认证时开启；关闭不会清除已保存密码。用户名、密码与服务器ACL保持一致。

示例：

```text
true
```

### setting-username

**ACL用户名**（`username`）

Redis 6及以上ACL用户名；默认用户可留空。仅在开启认证时使用。

示例：

```text
website
```

### setting-password

**访问密码**（`password`）

Redis认证密码，输入两次；留空保留原值。无需认证时关闭认证开关。

示例：

```text
ExampleOnly_2026
```

### setting-prefix

**键前缀**（`prefix`）

键名前缀，1至128个字母、数字、下划线、冒号或短横线；程序继续追加站点与用途标识。

示例：

```text
mystep:
```

### setting-site_id

**站点标识**（`site_id`）

站点隔离标识，留空根据网站根生成。多服务器共享同一项目缓存和Session时填写相同标识；不同站点使用不同值。

示例：

```text
example-main
```

### setting-timeout

**连接超时**（`timeout`）

连接超时秒数，0.01至60，支持小数；不使用无限等待。

示例：

```text
1
```

### setting-read_timeout

**读写超时**（`read_timeout`）

读写超时秒数，0.01至60。长时间阻塞命令应使用独立客户端。

示例：

```text
2
```

### setting-tls

**加密连接**（`tls`）

是否启用TLS并验证服务端证书。必须先在Redis服务端配置TLS，主机名应与证书匹配。

示例：

```text
false
```

### setting-cafile

**CA证书**（`cafile`）

可选PEM CA证书路径，相对于网站根；留空使用系统证书。与TLS开关配合。

示例：

```text
config/certs/redis-ca.pem
```

### setting-lock_wait

**锁等待时间**（`lock_wait`）

获取缓存计算锁或会话锁的最长等待秒数，0至60；0表示只立即尝试一次，失败不继续执行受锁保护操作。

示例：

```text
5
```

### setting-lock_ttl

**锁租约时间**（`lock_ttl`）

锁租约秒数，1至3600，需超过最长请求或计算时间。租约丢失后拒绝会话写入。

示例：

```text
120
```

### setting-max_bytes

**单条容量上限**（`max_bytes`）

单条缓存序列化数据及Session正文的容量上限，1至67108864字节。

示例：

```text
1048576
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
