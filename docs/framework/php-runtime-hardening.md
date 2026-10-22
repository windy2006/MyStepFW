# PHP 运行时风险与框架请求加固

更新日期：2026-10-22。适用于 MyStepFW 的 PHP 7.4 兼容代码及 PHP-FPM 部署。

## 1. 风险边界

PHP 于 2026-10-21 公布 CVE-2026-109618（内存不足处理）与 CVE-2026-109619（跨请求遗留状态）。PHP 的请求解析和部分初始化早于网站入口，应用代码无法覆盖这些阶段。因此本文的代码检查只能减少应用阶段的异常输入和资源消耗，不能宣称修复 PHP 内核漏洞。具体影响、补丁及缓解边界以 [PHP 官方公告](https://www.php.net/archive/2026.php#2026-10-21-5)、[OOM 公告](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w)、[遗留状态公告](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp)为准。

受支持分支的修复版本为 8.2.35、8.3.36、8.4.27、8.5.12；仍保留 PHP 7.4 时，应使用包含官方回移补丁的供应商构建或补丁构建，并核实实际 Web/FPM 二进制。仅看到版本号 7.4.33 不能确认是否已打补丁。CLI 和网站使用的二进制及启动配置也可能不同。

## 2. 已加入代码的措施

### 2.1 应用启动前的输入预算

`myStep::init()` 注册类加载器后立即调用 `mySecurity::guardRequest()`；在清理可重建缓存、读取全局应用配置、执行优先脚本、路由及会话启动之前完成。CLI/phpdbg 跳过；PHP 内置 HTTP 服务会检查。类索引的必要加载和重建可能已发生。

检查对象为已解析的 GET、POST、COOKIE、FILES 数组，以及服务器提供的 Content-Length。默认额度：

| 项目 | 默认值 | 计算方式 |
| --- | --- | --- |
| 数组深度 depth | 32 | 每个来源数组从 0 开始；最多允许配置为 64 |
| 节点 nodes | 4096 | 所有来源数组的条目累计，嵌套容器也计入 |
| 字节 bytes | 8 MiB | 所有字符串键和字符串值累计，按字节计算 |
| 声明请求体 body_bytes | 32 MiB | 十进制长度字符串比较，避免转整数溢出 |

POST 的声明体积还受启动配置 `post_max_size` 的较小正值限制。FILES 检查上传元数据；临时上传文件内容不计入上述 8 MiB，但受 PHP 和前置服务器的上传限制。预算共同消耗，不能按每个来源分别重复使用。

不合法的长度返回 HTTP 400，超额度返回 HTTP 413；正文为固定 JSON，带 `Cache-Control: no-store`，不回显请求内容，不启动会话。HEAD 不输出正文。合法请求不改变输入数组，不读取或复制整个 `php://input`，不触发对象回调。

如应用确需更高额度，在受信的入口脚本、调用 `myStep::init()` 之前配置：

```php
// 例如允许较大文本表单；单位为字节，各项必须为正整数。
define('MYSTEP_REQUEST_LIMITS', [
    'depth' => 24,
    'nodes' => 4096,
    'bytes' => 12 * 1024 * 1024,
    'body_bytes' => 32 * 1024 * 1024,
]);
```

只覆盖所需项目即可。禁止从请求参数、Cookie 或外部提交内容构造该常量；未知字段、非法类型和不支持的深度会作为开发配置错误抛出异常。此常量不会修改 php.ini 或 Web 服务器设置。

### 2.2 不能由此检查替代的边界

输入数组已经由 PHP 生成，过早的解析错误或内存不足可能发生在检查之前；JSON 正文尚未解码，不算入字段预算。缺少 Content-Length 时，代码仍检查字段，但无法据此知道原始流长度。multipart 的原始分段、每段头部及空上传控制数量不能从最终数组完整还原。

反向代理、Web 服务器或 WAF 需要在进入 PHP 之前限制解码后的体积、字段、嵌套、multipart 分段及头部数量，并覆盖没有有效 Content-Length 的情况。普通 Nginx 请求体大小设置不等于完成了上述所有检查，压缩请求也需在相应解码层另设边界。

### 2.3 保留服务器内存策略

以下现有调用识别服务器环境变量 `MYSTEP_PRESERVE_MEMORY_LIMIT=1`，启用时不再执行自身的 `ini_set('memory_limit', ...)`：

- 框架 `lib/myStep.class.php` 的 `go()`；
- 独立解包器 `plugin/manager/install.php`；
- 打包演示 `app/Document/module/myPacker.php`。

开关缺省不启用，沿用既有 `gen.memory` 或脚本值。开关本身不改变内存额度，也不会给网站解除限制。仅在实际服务器已制定内存策略时启用；新增应用和插件也应遵循这一约定。若需禁止任何应用覆盖，使用 FPM 的 `php_admin_value` 锁定，而不是单靠约定。

## 3. 需要部署后才生效的启动配置

提供两份示例，文件存在于网站并不表示生效：

- `config/php-hardening.ini.example`：合并到实际加载的 php.ini 或启动时扫描目录；
- `config/php-fpm-hardening.conf.example`：合并到现有站点 pool，保留原 pool 名称、用户、socket 和进程配置，不可当完整配置直接启动。

示例关闭上传进度，限制 POST 为 32 MiB、单文件 24 MiB、输入变量 1000、解析嵌套 16、上传文件 20。解析嵌套和运行时数组深度的计数阶段不同，不能直接视为相同额度。关闭显示错误、保留日志属于信息暴露控制，不是内核漏洞防护。

`session.upload_progress.enabled=0` 必须在请求解析前生效，修改实际启动配置后重启全部 PHP 工作进程；请求内 `ini_set()` 或 FPM `.user.ini` 不足以覆盖此阶段。此措施只阻断已确认的部分路径。

内存应急策略在两份示例中均保持注释：只有已部署请求限制及外部工作进程内存隔离后，才评估管理员锁定 `memory_limit=-1` 并启用框架保留开关。底层内存分配仍可能失败，不能因此省略补丁。外部约束宜只终止受影响的工作进程；不要以 `RLIMIT_AS` 或 `RLIMIT_DATA` 替代。macOS/ServBay 不应直接照搬 Linux cgroup 命令，需使用适合该平台的隔离方案。

FPM 示例另保留注释的 `pm.max_requests=1` 应急选项，需评估进程创建成本和吞吐量，不默认启用，也不是完整修复。

## 4. Web 服务器及业务额度

仓库的 `NginX` 参考配置、Apache `.htaccess`、IIS `web.config` 加入 32 MiB 请求体限制；Nginx 参考配置同时指定请求读取超时并保持 FastCGI 请求缓冲。实际站点应将参考配置合并到当前使用的虚拟主机。Apache 需允许相应目录指令；各平台语法及作用域参见 [Nginx](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size)、[Apache](https://httpd.apache.org/docs/2.4/mod/core.html#limitrequestbody)、[IIS](https://learn.microsoft.com/en-us/iis/configuration/system.webserver/security/requestfiltering/requestlimits/)。

32 MiB 会影响大文件上传、离线升级包和数据导入。需要更大包时应评估处理过程的内存开销，并协调前置限制、PHP 的 `post_max_size`、`upload_max_filesize`、应用预算及业务上传设置；单文件额度需小于 POST 总额度，给表单与 multipart 元数据留空间。下载大小不受请求体预算限制。

应用还应继续使用有预算的图片解码、归档解包和流式文件处理；错误处理器、shutdown 或 try/catch 无法代替 PHP 对内存不足的正确处理。不要通过隐藏警告、关闭错误日志或取消超时来假定问题消失。

## 5. 本次验证与部署核对

代码使用 PHP 7.4 测试。专项 `tests/security/request_limits.php` 覆盖普通输入、累计额度、巨大声明长度、异常类型、循环/深层数组及只读行为。`tests/core/execution_http_fixture.py` 使用临时站点验证 GET/POST/HEAD 拒绝早于业务与会话、JSON 声明体积、正常路由、16 MiB 内存下的大文件流式响应，以及环境开关的实际内存值。没有发送漏洞利用请求、制造 OOM 或诱发工作进程崩溃。测试不代表已验证 PHP 内核漏洞不可利用。

本轮结果：输入预算专项11项、执行流程HTTP 39项、后台HTTP 284项，以及整站打包/安装/模拟升级23组均通过；PHPDoc覆盖检查无错误。PHP启动ini的实际加载值与FPM片段语法检查通过；IIS文件仅完成XML解析，未做真实IIS/Apache集成。Nginx配置检查受当前沙箱的sysctl/日志访问限制，未完成实际语法验证，部署时仍须运行服务器配置检查。

本次只调整框架代码和仓库配置示例，没有修改 ServBay 的启动配置、重启服务或安装 PHP 补丁。上线时需核对：实际 Web/FPM 的补丁来源、启动配置的实际值、全部工作进程重启情况、入口是否确实经过前置请求限制，以及正常上传/导入/升级是否仍在预算内。仓库中的参考文件不能替代这些部署核对。
