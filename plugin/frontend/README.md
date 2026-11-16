# 前端资源（frontend） <small>v1.0.1</small>

## 功能概述

按应用选择本地jQuery及Bootstrap资源，集中维护前端依赖的版本组合。

## 安装与设置

安装、启用并绑定目标应用后，在frontend/选择应用、jQuery文件及Bootstrap版本，点击添加后保存。资源文件在plugin/frontend/files/；使用列表中的现有版本，保存后程序清理脚本缓存。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

### setting-detail

**设置信息**（`detail`）

按应用标识保存jQuery和Bootstrap本地资源选择的JSON对象。推荐在插件的资源选择页操作；jq为files/jquery中的文件名，bs为files/bootstrap中已有版本目录。删除某应用配置即可取消替换。

示例：

```text
{"CMS":{"jq":"jquery-3.6.1.js","bs":"4.6.2"}}
```

## 管理设置

以下项目在插件自己的管理或编辑页面中维护；隐藏的操作凭证与修订字段由程序生成。

### field-app

**应用**（`app`）

选择已存在的目标应用；每个应用保存一套资源组合。

示例：

```text
CMS
```

### field-jquery

**jQuery版本**（`jquery`）

选择files/jquery中实际存在的jquery-x.y.z.js文件；按组件主版本加载本地migrate脚本。

示例：

```text
jquery-3.6.1.js
```

### field-bootstrap

**Bootstrap版本**（`bootstrap`）

选择files/bootstrap下同时存在CSS和JS的版本。模板的Bootstrap类名需要与选择的版本配合。

示例：

```text
4.6.2
```

### field-detail

**配置JSON**（`detail`）

使用选择器添加或移除应用，保存后序列化为全局detail参数。

示例：

```text
{"CMS":{"jq":"jquery-3.6.1.js","bs":"4.6.2"}}
```

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
