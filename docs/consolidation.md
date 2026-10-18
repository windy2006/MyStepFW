# 文档合并与迁移对照

整理日期：2026-10-18。处理范围是项目自编开发、维护和站点文档。以正文日期、后续修正及当前源码为依据；不只按文件修改时间判定新旧。

## 内容取舍

- 执行流程采用较新的通用详细流程，补入核心修复和边界；旧站点流程不再并行维护一份通用解释。
- 数据库采用后续可选接口及驱动契约；旧“其他驱动未经本轮调整”不覆盖后续 MSSQL 参数化修复，真实 SQL Server 尚未验证的限制保留。
- 缓存采用 V3 内容帧、lookup／计算锁和当前 clear 约定，移除较早 V2 内容及无锁概述，保留 V2 有效条目兼容读取。
- 模板保留旧括号分支语义，独立 switch:default 才是兜底；CMS XML 入口修复归 CMS。
- 通用工具已覆盖初次清单中后来完成的资源预算、EXIF、指纹、流式归档及可恢复错误回滚；不再把它们列作尚未实现。
- Composer 记录的是仓库锁定版本，不将某次查询得到的“最新”或“无公告”作为永久结论；四个用户要求保留的组件继续保留。
- PHPDoc 索引以本次只读源码分析为准：52文件、56类／接口／trait、860具名方法／函数、1289显式参数；旧50文件与717等全项目汇总不作为现状。
- app／plugin 目录内保留通用规范；具体应用结构、六插件说明和本机域名资料分别集中到 applications／plugins／site。

## 原文档去向

| 原路径 | 合并后入口 | 取舍依据 |
| --- | --- | --- |
| `docs/framework-execution-flow.md` | [docs/framework/execution.md](framework/execution.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/execution-maintenance.md` | [docs/framework/execution.md](framework/execution.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/mechanism-improvements.md` | [docs/framework/routing-config-proxy.md](framework/routing-config-proxy.md) | 保留路由／配置／代理；DB和Cache采用各自后续专项 |
| `docs/path-conventions.md` | [docs/framework/paths.md](framework/paths.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/utility-review.md` | [docs/framework/utilities.md](framework/utilities.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/charset-improvements.md` | [docs/framework/utilities.md](framework/utilities.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/core-improvements.md` | [docs/framework/security-attachments.md](framework/security-attachments.md) | 附件／安全归安全主题，上传／图片／Minify／归档归通用工具 |
| `docs/extension-improvements.md` | [docs/framework/extensions.md](framework/extensions.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/database-improvements.md` | [docs/framework/database.md](framework/database.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/database-interfaces.md` | [docs/framework/database.md](framework/database.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/cache-improvements.md` | [docs/framework/cache.md](framework/cache.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/session-improvements.md` | [docs/framework/session.md](framework/session.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/template-improvements.md` | [docs/framework/templates.md](framework/templates.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/template-compatibility.md` | [docs/framework/templates.md](framework/templates.md) | 后续核查为准：普通 (default) 与显式兜底分开 |
| `docs/javascript-maintenance.md` | [docs/framework/javascript.md](framework/javascript.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/composer-maintenance.md` | [docs/framework/dependencies.md](framework/dependencies.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/vendor-usage-audit.md` | [docs/framework/dependencies.md](framework/dependencies.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/lib-documentation.md` | [docs/framework/phpdoc.md](framework/phpdoc.md) | 按本次源码只读统计更新为52文件／860声明，取代旧50文件索引 |
| `docs/phpdoc-maintenance.md` | [docs/framework/phpdoc.md](framework/phpdoc.md) | 按本次源码只读统计更新为52文件／860声明，取代旧50文件索引 |
| `docs/integration-maintenance.md` | [docs/framework/maintenance.md](framework/maintenance.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/first-application.md` | [docs/guides/first-application.md](guides/first-application.md) | 通用教程移入 guides，示例和代码保留 |
| `docs/first-plugin.md` | [docs/guides/first-plugin.md](guides/first-plugin.md) | 通用教程移入 guides，示例和代码保留 |
| `docs/app-root-layout.md` | [docs/applications/layouts.md](applications/layouts.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/admin-development.md` | [docs/applications/Admin.md](applications/Admin.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/cms-link-maintenance.md` | [docs/applications/CMS.md](applications/CMS.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/app-maintenance.md` | [docs/applications/README.md](applications/README.md) | 按应用拆入 CMS、myStep 和 examples，公共约定回归框架主题 |
| `docs/plugin-maintenance.md` | [docs/plugins/existing-plugins.md](plugins/existing-plugins.md) | 并入主题，保留独特接口与限制；后续专项覆盖旧概述 |
| `docs/request-flow.md` | [docs/site/deployment.md](site/deployment.md) | 通用流程采用较新完整指南，只保留本站部署部分 |
| `docs/app-development-rules.md` | [app/README.md](../app/README.md) | 保留 app 目录内的统一规范入口，移除重复转引文档 |

`app/Admin/README.md` 保留应用内入口，完整说明统一到 applications/Admin.md。图片来源 SOURCES.md 随资产保留；它是来源记录，不是另一份应用指南。根 readme.md 保留项目介绍；后台框架指南改为直接读取本目录、app／plugin规范及static文档，正文不另存副本。测试说明仍在 tests/README.md。

## 验证范围

本次只修改 Markdown 和源码注释中的文档引用。核对本地链接、旧引用、目录和声明索引；执行 PHPDoc CLI 只读检查及注释变更的可执行词元比较。不重新运行数据库写入、安装、升级、卸载或其他业务流程；正文中原测试记录不视作本次结果。全部移出文档的原文备份保存在网站目录之外。
