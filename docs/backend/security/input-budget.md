# 输入预算

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

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

## 主要方法：参数、返回和失败约定

### mySecurity::inspectRequest

功能说明：有预算地检查已解析输入和声明体积，返回拒绝原因。

仅检查Content-Length及PHP已经生成的输入数组，不读取php://input、临时上传文件或
反序列化内容。缺少声明长度时仍检查输入结构，但无法据此限制原始或解码请求体。
PHP解析请求可能早于此检查；本方法不修复PHP内核漏洞，也不能计算原始multipart分段数。

```text
public static function inspectRequest(array $server, array $input, array $limits = []);
```

| 参数说明 |
| --- |
| array $server 请求服务器变量，只读取CONTENT_LENGTH |
| array $input GET、POST、COOKIE、FILES等来源数组组成的集合，不修改原数组 |
| array $limits depth/nodes/bytes/body_bytes的正整数覆盖值（默认：[]） |

**返回**：array 无问题为[]；拒绝时返回status及固定error文字，不回显输入

**异常**：InvalidArgumentException 受信预算配置无效；深度不得超过64

### mySecurity::guardRequest

功能说明：在框架应用和会话启动前拒绝超预算请求，不把它当作PHP解析器补丁。

预算可由入口受信常量MYSTEP_REQUEST_LIMITS覆盖；POST还受实际post_max_size的
较小正值限制。拒绝时直接输出不缓存的固定JSON，不创建会话、路由或应用对象。
请求体解析可能已在本方法之前完成；无Content-Length的请求仍需Web服务器限制。

参数说明：无显式参数。

```text
public static function guardRequest();
```

**返回**：void 合法请求返回；400/413响应后结束请求，HEAD不输出正文

**异常**：InvalidArgumentException 入口预算常量不是数组或预算配置无效

## 明确调用示例

```php
$problem = mySecurity::inspectRequest(
    ['REQUEST_METHOD'=>'POST', 'CONTENT_LENGTH'=>'5'],
    ['get'=>[], 'post'=>['title'=>'Hi'], 'cookie'=>[], 'files'=>[]]
);
assert($problem === []);
// 在框架已启动会话的页面处理器中：
$token = mySecurity::token();
assert(mySecurity::valid($token));
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
