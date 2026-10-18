# Composer 与 vendor 依赖维护

整理日期：2026-10-18。目标运行环境：PHP 7.4。版本描述以当前仓库 composer.json、composer.lock 和已安装文件为依据；上游安全审计结果只代表原查询日期。

## 组件取舍与版本

| 组件 | 原版本 | 当前版本/处理 | 实际用途 |
| --- | --- | --- | --- |
| erusev/parsedown | 2.0.x-dev，固定32278af开发提交 | 1.8.0稳定版，约束^1.8.0 | 框架操作指南、CMS guide主题Markdown渲染 |
| mrclay/jsmin-php | 2.4.3 | 保留2.4.3，约束^2.4.3 | 框架JS压缩、合并和文档示例；当前仓库锁定版本 |
| phrozenbyte/cssmin | v3.0.2 | 更新v3.0.4，约束^3.0.4 | 框架CSS压缩与合并 |
| meenie/javascript-packer | 1.1，已停止维护 | 从require、lock及自动加载移除 | 旧式JS eval包装；现有JSMin承担普通压缩 |

Packer并非完全没有调用：myMinify::get(true)、myController::JS和文档示例此前会进入该路径。本轮同步调整调用；不能只删composer.json中的依赖。

Parsedown从较高编号的开发分支切换到维护中的稳定分支，Composer会显示“Downgrading”。这是有意移除开发版依赖；并非回到旧开发提交。1.8.0于2026-02-16发布，要求PHP 7.1+，适合当前PHP 7.4环境。

上游依据：[Parsedown发布说明](https://github.com/erusev/parsedown/releases/tag/1.8.0)、[JSMin包版本](https://packagist.org/packages/mrclay/jsmin-php)、[CSSMin包版本](https://packagist.org/packages/phrozenbyte/cssmin)、[Packer停止维护标记](https://packagist.org/packages/meenie/javascript-packer)。

## 调用兼容与缓存

- 两处Markdown调用从 `new Erusev\Parsedown\Parsedown()` / `toHtml()` 迁移到稳定版的 `new \Parsedown()` / `text()`。调用位置为 app/myStep/module/guide.php、app/CMS/template/guide/module/index.php。
- myMinify::get($pack=false)、show($pack=false)保留原参数签名。true/false均使用普通压缩，不再生成eval包装或混淆输出；JavaScript运行语义保持，输出字节可能变化。直接使用第三方JavaScriptPacker类的外部代码需迁移。
- myController::JS及Document压缩示例不再传入true。get(true)仍可调用，含义变为兼容入口，不要求重新安装Packer。
- 缓存指纹只包含当前模式使用的压缩引擎，组件升级或myMinify源码变化会使旧压缩缓存失效。旧pack标记不再影响缓存身份；有源输入时自动重建。源文件缺失时仍按原约定报告错误。
- 没有删除全部缓存目录或计算锁。真实页面在下次访问时按原机制重建JS/CSS缓存。

## Composer配置

composer.json将PHP要求明确为 `^7.4`，与框架已使用的WeakReference/ReflectionReference等功能一致。config.platform.php设为7.4.0，防止在更高版本PHP环境更新时解析出高于目标基线的依赖；实际验证仍使用ServBay PHP 7.4.33。

继续使用stable依赖、dist安装，启用优化自动加载及require排序。项目仓库恢复官方Packagist，新的锁文件不再保留旧镜像地址；不修改机器全局Composer或其他项目设置。

部署使用composer.lock安装，不在生产部署时执行update：

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
composer audit --locked --no-dev
```

更新必须同时提交composer.json、composer.lock及相关调用修改。此项目目前还将vendor纳入版本管理，因此受管理组件及生成的自动加载元数据也需要同步；不要手改vendor源码来代替锁文件更新。

## 验证入口

```sh
php tests/dependencies/composer_regression.php
node tests/dependencies/composer_javascript.js
```

专项覆盖锁文件/实际安装一致、PHP目标、无Packer自动加载、真实两处Markdown调用、普通/兼容JS输出、旧缓存失效、CSS自定义属性和calc，以及12个Node执行前后语义场景。上游audit只表示查询时未返回已知依赖公告，不代替功能验证。

## vendor：核查结论

2026-10-16 核查时，vendor 共 26 个目录：21个存在框架/应用/插件/示例调用，4个未发现现有引用，1个为空目录。vendor/autoload.php是入口；.DS_Store是系统文件，无功能用途。

未发现现有引用的4个目录为jquery.mediaDevice、jquery.parallax、jquery.resizeImg、mdi。用户已明确要求这四个目录全部保留，不属于后续精简范围，不得仅因当前未发现引用而删除；核查覆盖555个源码/模板/脚本等文本文件（含压缩资源），并只读检查mystep/mystep_cms两库的64个文本列，没有找到这些候选库的名称或资源路径。库自身README或组件定义不是业务调用证据；未来动态配置及外部扩展仍可能使用这些工具。

## vendor：各目录用途与调用证据

| 目录 | 使用范围 | 用途 | 主要证据 |
| --- | --- | --- | --- |
| `composer` | 框架基础设施 | Composer自动加载器、已安装版本和平台检查 | `vendor/autoload.php、lib/myStep.class.php` |
| `erusev` | 文档功能 | Parsedown 1.8.0，Markdown转HTML | `app/myStep/module/guide.php、app/CMS/template/guide/module/index.php` |
| `mrclay` | 框架运行 | JSMin，JS压缩 | `lib/myMinify.class.php` |
| `phrozenbyte` | 框架运行 | CssMin，CSS压缩 | `lib/myMinify.class.php` |
| `codemirror` | 后台及示例 | 应用源码、配置及CMS模板编辑器 | `app/myStep/template/function_app.tpl、setting_execute.tpl；app/CMS/template/admin/func_template_input.tpl` |
| `tinymce` | CMS及示例 | 文章/自定义内容富文本编辑 | `app/CMS/template/admin/art_content_input.tpl、art_custom_input.tpl；plugin/sample/template/sample.tpl` |
| `highlight` | 框架及文档 | 错误详情、代码示例、CMS guide主题代码高亮；包含两种高亮引擎和剪贴板组件 | `lib/myException.class.php；app/Document/template/sample.tpl；app/CMS/asset/guide/function.js` |
| `jquery.powerUpload` | 后台及示例 | 应用、插件、文章及模板附件上传 | `app/myStep/template/function_app.tpl、function_plugin.tpl；app/CMS/asset/admin/tinymce_init.js` |
| `jquery.jmpopups` | 更新插件及示例 | 弹窗交互 | `plugin/manager/template/manager.tpl；app/myStep/sample/form.tpl` |
| `jquery.autocomplete` | 框架接口及示例 | 自动补全脚本和国家/省份/地区数据 | `app/myStep/lib.php::autoComplete；app/myStep/sample/form.tpl` |
| `enphp` | 管理插件可选分支 | 更新管理器打包时编码生成的安装入口 | `plugin/manager/class.php::pack 调用 myStep::vendor('enphp')` |
| `Chart` | 示例功能 | 统计图表和示例数据工具 | `app/myStep/sample/chart.tpl` |
| `animate` | 示例功能 | CSS动画展示 | `app/myStep/sample/animate.tpl` |
| `bootstrap-datepicker` | 示例功能 | 日期选择器及中文语言包 | `app/myStep/sample/form.tpl` |
| `bootstrap-table` | 示例功能 | 表格、导出、拖动、筛选等扩展示例 | `app/myStep/sample/table.tpl` |
| `select2` | 示例功能 | 增强下拉框及中文语言包 | `app/myStep/sample/form.tpl` |
| `jquery.date_input` | 示例功能 | 另一种日期输入示例 | `app/myStep/sample/form.tpl` |
| `jquery.range` | 示例功能 | 滑块输入示例 | `app/myStep/sample/form.tpl` |
| `jquery.powerImage` | 示例功能 | 图片延迟加载 | `app/myStep/sample/image_load.tpl` |
| `jquery.showImage` | 示例功能 | 图片浏览展示 | `app/myStep/sample/image.tpl` |
| `masonry.pkgd` | 示例功能 | 瀑布流布局 | `app/myStep/sample/masonry.tpl` |
| `jquery.mediaDevice` | 用户要求保留（当前未发现引用） | 音视频设备及全屏工具 | `已搜索目录名及$.camera/$.audio/$.fullscreen等调用` |
| `jquery.parallax` | 用户要求保留（当前未发现引用） | 视差页面效果 | `已搜索目录名及.parallax调用` |
| `jquery.resizeImg` | 用户要求保留（当前未发现引用） | 前端图片缩放/压缩 | `已搜索目录名、.resizeImg调用和mobileBUGFix依赖` |
| `mdi` | 用户要求保留（当前未发现引用） | Material Design图标字体 | `已搜索目录路径、mdi-*类及materialdesignicons字体名` |
| `bin` | 空目录 | Composer保留的可执行文件目录，目前无文件 | `不属于运行时组件` |

示例入口为后台 `/console/samples/`，由app/myStep/module/samples.php按sample/idx.php列表渲染。仅用于示例的库不能在保留这些示例的情况下直接删除。代码编辑器模式、表格扩展、语言包、主题和字体还存在组件内部动态依赖，不能用“没有逐文件直链”判断可删除。

## vendor：已替换组件是否还有调用

- JavaScriptPacker/meenie/javascript-packer：仓库运行源码、Composer配置、锁文件和生成的自动加载表均无实例化/加载调用；原get(true)/show(true)签名保留，使用JSMin普通压缩。
- Parsedown开发版：两处实际调用均使用稳定版的 `new \Parsedown()` 和 `text()`；运行源码没有旧 `Erusev\Parsedown\Parsedown` / `toHtml()` 调用。
- 旧名称保留在迁移说明、注释及用于验证组件已移除的负向回归断言中，不会加载旧组件。

更新依赖时应同步 manager 的受管理组件路径和摘要，但不能据 2026-10-16 的历史统计推断整份校验基线仍与当前站点一致。

## 版本复核方式

本文不将历史目录数、扫描数或“无公告”视作永久状态。部署按锁文件安装，升级时重新检查 PHP 7.4 平台条件、实际调用、Composer 审计及更新器基线。用户要求保留的四个可选组件继续保留。具体应用的资源适配见 [CMS](../applications/CMS.md) 与 [现有插件](../plugins/existing-plugins.md)。
