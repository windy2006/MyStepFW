# myStep 框架后台与演示维护

整理日期：2026-10-18。目标运行环境：PHP 7.4。myStep 是框架核心的应用承载部分；此处保存具体后台和示例说明。

## 后台接口与模块约定

- 数据浏览接口保留管理员身份检查，校验数据库/表名和实际字段；查询使用明确的 `数据库.表名`，修复已连接时跨数据库表选择不生效的问题。
- JSON 无效或字段类型不符时采用安全默认值；默认每页 20 条，页大小限制为 1–1000，偏移不小于 0。总数仍使用去掉分页后的查询，返回格式保持 `total`、`rows`。
- 查询值不再先做 HTML 转义再交给 SQL 构建器，以免 `&`、引号等内容无法匹配。
- 自动完成的模式名只能由字母、数字、下划线组成，不能拼接目录路径。
- 设置和功能模块的 `checkForm.js` 路径使用 `ROOT_WEB`，兼容子目录；缺省功能模块回到应用管理。

## 后台布局及共享示例

原后台入口由 gen.path_admin 配置；其 module 与 template 保持功能对应。新 Admin 与原后台共用配置、插件注册及示例资源，界面行为分见 [Admin](Admin.md)。图片示例的六张本地照片和来源记录放在 app/myStep/asset/samples；来源文件随资源保留。

## 插件管理流程补强（2026-10-18）

旧后台与Admin现在共用插件公共接口setPluginApps/setPluginOrder。原本局部checkApp已移除，避免重复定义并保持两边强制绑定、连续索引、无变化不写和锁语义一致。卸载钩子失败不会先删除注册，已安装插件不能绕过卸载直接删除目录；非法排序不会清空注册表，启停在一次排他读改写中完成。安装流程仍不是跨文件／数据库事务，失败后的插件副作用由插件实现处理。

专项测试：[旧后台生命周期](../../tests/plugins/plugin_lifecycle.php)；公共契约和调用示例见[插件规范](../../plugin/README.md#15-生命周期一致性与独立-api)。

插件导出同样委托buildPluginPackage，拒绝空标识打包根目录，输出plugin_标识.plugin，并只在临时副本中处理脱敏配置；即使manager没有加载，也不会打包在线config.php和约定的运行数据。


## 框架指南文档中心（2026-10-18）

原`/console/guide`不再依赖旧readme的自定义段落格式；直接按主题阅读docs、app／plugin规范、static和tests中的现有Markdown，与Admin使用相同清单。MyStepGuide辅助类与guide.css放在本应用目录，页面由module/guide.php及guide、guide_group模板装配，保留应用模板配置及风格回退。

使用doc标识选择文档，链接与章节导航继续留在当前原后台，并兼容rewrite、PATH_INFO和query_string。文档缺失或非法标识返回404；源码引用保留为路径提示，正文使用Parsedown安全模式。完整入口说明见[文档总览](../README.md#后台阅读入口)。
