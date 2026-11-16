# 搜索引擎监控（se_detect） <small>v2.1.0</small>

## 功能概述

识别搜索引擎及AI访问，记录IP、路径、UA和每日数量；每条规则可单独启停或阻挡。

## 安装与设置

需要MyStepFW 2.6.3、PHP >=8.5.12且<9、mbstring。后台安装、启用并绑定应用，管理地址`se_detect/`。普通GET请求在start钩子识别，跳过框架管理员；阻挡时返回404。

## 内置规则

`catalog.php`维护14条实际UA关键字，包括Google、Bing、百度、搜狗、DuckDuckGo、Apple，以及OpenAI、Claude的训练、搜索和用户触发访问、GoogleOther及OpenAI广告检查。分类为：

- `search`：搜索引擎；
- `ai_train`：AI训练抓取；
- `ai_search`：AI搜索；
- `ai_user`：用户请求触发的访问；
- `other`：其他抓取。

用途和阻挡开关相互独立，新增规则默认允许访问。后台“补充内置规则”只新增缺少的标识，保留现有规则的启停、阻挡及自定义内容；不自动删除已有规则。需要恢复被删除的内置项时可再次使用此按钮。自定义规则不需要在线下载或依赖其他插件。

Google-Extended、Applebot-Extended等robots.txt控制令牌不能作为独立HTTP UA识别，应在robots.txt中配置。UA可伪造，分类是统计用途，不是搜索平台的身份认证；本插件不会把UA匹配等同已核验IP。

## 自定义与匹配

标识为小写字母开头，后续允许数字/下划线，最长40位。UA关键字不区分大小写，最多200字符。按匹配优先级从大到小命中首项，相同值按编号顺序；未填写时按0处理，具体规则可设100等更高值覆盖宽泛规则。无规则命中的bot/spider/crawler/slurp及已知AI用户访问归到other。

```php
$engine = plugin_se_detect::classify('Claude-SearchBot/1.0', PSeDetectSupport::state()['items']);
// $engine['idx'] === 'claude_search'; $engine['category'] === 'ai_search'
```

## 统计与管理

IP仅使用`REMOTE_ADDR`，支持IPv4/IPv6；不直接相信请求传来的转发头。后台可查看规则、IP和每日统计，JSON导入新增规则，导出可归档。保留天数在插件参数设置中调整，范围1～3650天；IP集合最多20000项，并由8MiB总预算清理较旧统计。

私有文件为`config/plugins/se_detect/state.php`；读写校验结构，使用稳定锁及原子替换。卸载保留数据。后台写操作均为POST、管理员授权及CSRF验证，表单使用框架checkForm。

内置名称核对：[OpenAI](https://developers.openai.com/api/docs/bots)、[Claude](https://privacy.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler)、[Google](https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers)、[Apple](https://support.apple.com/en-us/119829)、[DuckDuckGo](https://duckduckgo.com/duckduckgo-help-pages/results/duckduckbot)。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-retention_days

**统计保留天数**（`retention_days`）

抓取统计保留天数，1至3650。清理随记录处理执行；记录数量及总容量还受插件预算约束。

示例：

```text
90
```

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-idx

**规则标识**（`idx`）

小写字母开头，后续小写字母、数字、下划线，最长40位。

示例：

```text
example_bot
```

### field-name

**名称**（`name`）

后台和统计中显示的规则名称。

示例：

```text
示例抓取器
```

### field-agent

**UA关键字**（`agent`）

不区分大小写的User-Agent字面片段，最多200字符，不是正则。

示例：

```text
ExampleBot
```

### field-category

**分类**（`category`）

search搜索、ai_train训练、ai_search搜索、ai_user用户触发、other其他；分类不代表平台身份认证。

示例：

```text
search
```

### field-order

**匹配优先级**（`order`）

范围0至9999，较大数值先匹配，只取第一条命中规则。

示例：

```text
100
```

### field-active

**启用规则**（`active`）

关闭后该规则不参与匹配。

示例：

```text
勾选
```

### field-ban

**阻挡**（`ban`）

命中时返回404；与规则分类独立。

示例：

```text
不勾选
```

### field-json

**导入JSON**（`json`）

粘贴本插件导出的JSON内容，导入会新增记录而不是覆盖；PHP任务和代码片段导入后默认停用，确认内容后再启用。

示例：

```text
{"items": []}
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
