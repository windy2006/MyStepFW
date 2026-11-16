# 广告展示（ad_show） <small>v3.0.0</small>

## 功能概述

用于文字、图片、HTML5页面、视频、原生图文及第三方脚本广告，支持广告位、日期、优先级、展示/点击统计。使用框架模板，不引入外部前端库。

## 安装与设置

环境：MyStepFW 2.6.3、PHP >=8.5.12且<9、mbstring；图片上传需要fileinfo及对应图片识别支持。通过后台安装、启用并绑定宿主应用。管理逻辑地址`ad_show/`，新旧后台共用module与template。

## 素材类型

|编号|类型|填写方式|
|---|---|---|
|0|文字|填写标题，可选点击目标|
|1|响应式图片|图片URL，可选移动端图片、点击目标、图片说明|
|3|HTML5页面|受信HTML页面的URL，以iframe展示，支持全屏及画中画授权|
|4|第三方脚本|支持异步加载的受信JavaScript资源URL|
|5|HTML5视频|MP4/WebM等浏览器可播放资源URL，可选封面、循环、静音自动播放及独立按钮|
|6|原生图文|图片、标题、说明、可选按钮，形成可适配页面的图文卡片|

图片支持JPEG、PNG、GIF、WebP、AVIF。上传上限5MiB，文件放在私有目录，再通过`ad_asset/令牌`输出正确MIME。视频和HTML5页面填写已部署资源URL，不通过图片上传入口上传。HTML5页面的相对依赖资源应与页面一起部署。第三方SDK需要完整HTML初始化片段时，可部署受信HTML5页面并采用类型3。

移动端图片在视口不超过767px时使用；图片使用picture、延迟加载及异步解码。视频保留原生播放控件和行内播放，默认不自动播放、不预加载；开启自动播放时同时静音，实际能否自动播放由浏览器策略决定。视频按钮单独显示，不遮挡播放控件。

## 广告位及有效期

`idx`为1～40位字母、数字、下划线或连字符。同一广告位允许多条素材，优先级从大到小，相同时编号从小到大。开始/结束日期为`YYYY-MM-DD`，结束日包含当天，留空不限制。

标题用于文字、图文及图片替代说明。广告标识默认“广告”；图文说明按纯文本转义。没有点击目标的图片不会生成失效链接。广告链接使用`sponsored nofollow noopener`标记。

## 模板调用

```html
<!--ad_show idx="banner" limit="2" width_ad="460" height_ad="120"-->
<!--ad idx="recommend" limit="3" class="recommend-ads"-->
```

`idx`留空展示所有广告位；`limit`为1～100；`width/height`设容器尺寸，`width_ad/height_ad`为素材尺寸；`class`设容器类名，`css/css_ad`为容器/单条样式。实际外观可修改template中的对应`ad_编号.tpl`、`unit.tpl`及`asset/display.css`。

```php
echo plugin_ad_show::display(['idx' => 'banner', 'limit' => 2]);
```

绑定应用时插件自动登记本地展示CSS。自行加载声明而未初始化插件时，需要先通过`$mystep->addCSS(PLUGIN.'ad_show/asset/display.css')`登记样式。

## 统计与存储

自动展示及`ad_link/编号`跳转分别累计展示和点击；`count($id, 'view')`、`count($id, 'click')`供自行展示的调用方使用，不与自动计数重复调用。同页统计合并写入，爬虫不计数；`ip_view/ip_click`实际为每日会话去重累计，不是独立IP数。页面缓存命中不产生新的动态展示统计。

私有数据在`config/plugins/ad_show/state.php`，稳定锁和原子文件替换保护读改写。管理页提供近期明细、JSON导入/导出；导入新增广告，不覆盖现有数据，不导入图片二进制。卸载保留数据。管理写操作为POST并经过框架管理员、CSRF和服务端字段验证；编辑表单复用checkForm。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

本插件没有全局参数，具体内容在插件管理入口维护。

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-idx

**广告位标识**（`idx`）

1至40位字母、数字、下划线或短横线；模板使用此标识选择素材。

示例：

```text
banner
```

### field-ad_client

**客户**（`ad_client`）

管理备注用的客户名称。

示例：

```text
示例客户
```

### field-ad_mode

**广告类型**（`ad_mode`）

0文字、1图片、3HTML5页面、4第三方脚本、5视频、6原生图文。按所选类型填写资源。

示例：

```text
1
```

### field-ad_url

**点击目标URL**（`ad_url`）

可选，填写HTTP/HTTPS或本站根路径；留空表示不生成点击跳转。

示例：

```text
https://example.com/product
```

### field-ad_file

**资源URL**（`ad_file`）

图片、HTML5页面、脚本或视频地址；类型3和4仅使用受信资源。图片也可先通过图片上传获取资源地址。

示例：

```text
/static/images/banner.webp
```

### field-ad_mobile

**移动端图片**（`ad_mobile`）

可选，屏幕宽度不超过767px时使用。

示例：

```text
/static/images/banner-mobile.webp
```

### field-ad_poster

**视频封面**（`ad_poster`）

视频加载前显示的封面图片URL。

示例：

```text
/static/images/video-poster.webp
```

### field-ad_text

**标题和图片说明**（`ad_text`）

文字或图文标题，也是图片的替代说明。

示例：

```text
查看新品
```

### field-ad_desc

**图文说明**（`ad_desc`）

原生图文卡片的纯文本说明，按文本转义。

示例：

```text
了解本季新品。
```

### field-ad_cta

**按钮文字**（`ad_cta`）

图文或视频独立按钮的显示文本。

示例：

```text
了解详情
```

### field-ad_label

**广告标识**（`ad_label`）

标明推广内容的文字，默认广告。

示例：

```text
广告
```

### field-video_autoplay

**静音自动播放**（`video_autoplay`）

勾选后以静音方式请求自动播放；能否播放由浏览器策略决定。

示例：

```text
勾选
```

### field-video_loop

**循环播放**（`video_loop`）

勾选后视频播放结束自动循环。

示例：

```text
不勾选
```

### field-ad_level

**优先级**（`ad_level`）

范围0至255，较大数值先展示，相同优先级按编号排序。

示例：

```text
10
```

### field-add_date

**开始日期**（`add_date`）

YYYY-MM-DD，留空不限制开始时间。

示例：

```text
2026-12-01
```

### field-exp_date

**结束日期**（`exp_date`）

YYYY-MM-DD，包含当天，留空不限制。

示例：

```text
2026-12-31
```

### field-active

**启用**（`active`）

控制单条广告是否参与前台展示。

示例：

```text
勾选
```

### field-comment

**备注**（`comment`）

仅用于后台维护的说明。

示例：

```text
首页推广位
```

### field-image

**上传图片**（`image`）

选择JPEG、PNG、GIF、WebP或AVIF图片，最多5 MiB；视频、脚本及HTML页面通过资源URL配置。

示例：

```text
banner.webp
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
