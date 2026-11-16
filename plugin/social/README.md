# 第三方登录（social） <small>v1.0.1</small>

## 功能概述

微信（网站扫码／公众号网页）、QQ、Google、GitHub共用框架会员服务，首次必须填写昵称和邮箱并完成本站邮件验证。默认关闭，配置不包含真实凭据。

完整的安装步骤、设置项目、平台回调、邮箱验证、账号绑定和存储说明见[第三方会员登录指南](../../docs/showcase/plugins/social.md)。框架协议和应用适配器见[普通会员服务](../../docs/backend/security/members.md)。

## 安装与设置

1. 在框架后台插件管理中找到“第三方登录”，先阅读设置说明并填写参数。
2. 安装、启用插件，并选择需要使用的应用。
3. 按下方设置项完成配置后保存；各参数的“填写说明”可直接定位到对应章节。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-enabled

**登录开关**（`enabled`）

控制第三方登录功能，插件本身还须安装、启用并绑定会员应用。准备平台回调、本站邮件服务后开启。

示例：

```text
true
```

### setting-app

**会员应用**（`app`）

会员应用核心类的名称，需要实现框架userProvider接口；当前CMS提供适配。

示例：

```text
CMS
```

### setting-endpoint

**规范入口**（`endpoint`）

第三方登录规范入口，必须HTTPS并以/结尾。平台回调使用该入口派生的地址，需与平台控制台完全一致。

示例：

```text
https://cms.example.com/user/social/
```

### setting-site_name

**站点名称**（`site_name`）

本站邮箱验证邮件中显示的站点名称。

示例：

```text
示例网站
```

### setting-email_ttl

**邮件有效期**（`email_ttl`）

邮件验证凭证有效秒数，300至1800，不能超过待完成流程有效期。

示例：

```text
900
```

### setting-pending_ttl

**补充时限**（`pending_ttl`）

首次登录待补资料及邮箱验证流程的有效秒数，600至3600。

示例：

```text
1800
```

### setting-wechat_mode

**微信场景**（`wechat_mode`）

微信登录场景：web为开放平台网站扫码登录；mp为公众号网页授权。填写对应平台应用的ID和密钥。

示例：

```text
web
```

### setting-wechat_enabled

**微信登录**（`wechat_enabled`）

是否开放微信登录。仅在对应平台应用已开通、回调和本站配置完成后启用。

示例：

```text
true
```

### setting-wechat_client_id

**微信应用ID**（`wechat_client_id`）

微信控制台提供的AppID或Client ID，不能填写应用名称。

示例：

```text
example-client-id
```

### setting-wechat_secret

**微信应用密钥**（`wechat_secret`）

微信控制台生成的应用密钥，重复输入须一致，留空保留。密钥仅在服务器使用。

示例：

```text
ExampleSecretOnly
```

### setting-qq_enabled

**QQ登录**（`qq_enabled`）

是否开放QQ登录。仅在对应平台应用已开通、回调和本站配置完成后启用。

示例：

```text
true
```

### setting-qq_client_id

**QQ应用ID**（`qq_client_id`）

QQ控制台提供的AppID或Client ID，不能填写应用名称。

示例：

```text
example-client-id
```

### setting-qq_secret

**QQ应用密钥**（`qq_secret`）

QQ控制台生成的应用密钥，重复输入须一致，留空保留。密钥仅在服务器使用。

示例：

```text
ExampleSecretOnly
```

### setting-google_enabled

**谷歌登录**（`google_enabled`）

是否开放Google登录。仅在对应平台应用已开通、回调和本站配置完成后启用。

示例：

```text
true
```

### setting-google_client_id

**谷歌应用ID**（`google_client_id`）

Google控制台提供的AppID或Client ID，不能填写应用名称。

示例：

```text
example-client-id
```

### setting-google_secret

**谷歌密钥**（`google_secret`）

Google控制台生成的应用密钥，重复输入须一致，留空保留。密钥仅在服务器使用。

示例：

```text
ExampleSecretOnly
```

### setting-github_enabled

**GitHub**（`github_enabled`）

是否开放GitHub登录。仅在对应平台应用已开通、回调和本站配置完成后启用。

示例：

```text
true
```

### setting-github_client_id

**GH应用ID**（`github_client_id`）

GitHub控制台提供的AppID或Client ID，不能填写应用名称。

示例：

```text
example-client-id
```

### setting-github_secret

**GH密钥**（`github_secret`）

GitHub控制台生成的应用密钥，重复输入须一致，留空保留。密钥仅在服务器使用。

示例：

```text
ExampleSecretOnly
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
