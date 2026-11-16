# 请求过滤（badReq） <small>v1.0.2</small>

## 功能概述

在框架前置阶段检测可疑查询、Cookie支持和封禁IP，记录诊断信息并提供请求日志管理。

## 安装与设置

安装并启用后，框架在常规应用会话和认证前执行已登记的priority脚本。后台日志入口为badReq/，依赖SQLite。停用停止前置脚本；卸载会清除插件日志、封禁列表及相关诊断数据。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-forward

**跳转网址**（`forward`）

可疑请求被识别后跳转的地址；留空使用网站根。使用完整HTTP/HTTPS地址或本站根路径。跳转附加wd和q参数用于定位请求。

示例：

```text
/request-blocked
```

### setting-bad_chars

**可疑字符**（`bad_chars`）

在原始查询字符串中按字面查找的可疑片段，以半角逗号分隔；留空不检查片段。不是正则表达式，避免把业务正常使用的字符列入。

示例：

```text
union%20select,../
```

### setting-cookie

**检测标识**（`cookie`）

Cookie检测标识，最长10个英文字母或下划线；留空关闭。实际Cookie名带BQ_前缀和小时标记。普通浏览器首次访问会等待约1秒刷新；API以及识别到的bot/spider跳过此项。

示例：

```text
check
```

### setting-post

**POST记录**（`post`）

是否记录可疑请求的POST字段。记录经过敏感字段脱敏和长度限制，保存在缓存tmp/post目录；不会保存上传文件正文。

示例：

```text
false
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
