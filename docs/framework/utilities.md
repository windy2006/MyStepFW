# 通用工具、字符集与资源处理

整理日期：2026-10-18。目标运行环境：PHP 7.4。合并功能类、上传／图像／归档／压缩以及字符集说明；后续实现覆盖初次记录中的旧限制。

## 工具职责与已修正行为

| 类或文件 | 已确认问题 | 本轮处理 |
| --- | --- | --- |
| `myFile` | `removeBom()` 用错误的字符集合 ltrim，损坏正常前缀；gzip 检测不正确；文件 size 总为 0；字符串化回调不可靠；save 无法明确保存空串；递归删除/搜索跟随目录链接 | 精确识别 UTF-8 BOM；正确识别 gzip；修复大小及字符串化、空值保存；删除链接本身，递归不进入链接目录 |
| `myFile` | 容量不支持小数或纯字节；copy 可递归复制到自己的子目录且忽略失败；追加写返回已关闭资源并设为 0777；缺失目录 find 返回 false 使调用方 foreach 警告 | 支持 1.5M/100 等格式；拒绝自复制及目录后代目标；传播复制结果；写入返回字节数或 false，停止无条件放宽权限；缺失搜索目录返回空数组 |
| `myFile` | 下载内容为字符串 `0` 时不保存，文件名可能混入 URL 查询串；judgeChild 提前返回泄漏目录句柄 | 严格判断内容并从 URL path 取文件名；try/finally 关闭句柄；增加归档目标路径检查工具 |
| `myReq` | env 分支读取了全局变量；server 把 `0` 当缺失；HTTP_HOST 丢失端口、破坏 IPv6；参数名 `0` 被当作获取全部参数 | 正确读取环境变量及保留零值；保留原 Host/端口；使用明确空串判断 |
| `myReq` | 任意客户端可伪造转发 IP，IPv6 被正则破坏；ping 没有明确连接超时且 HTTPS 默认端口错误 | 转发头只接受显式可信代理，IP 使用标准验证；ping 使用有超时的 TCP 流连接并识别 HTTPS 443 |
| `myReq` | Session 显示解码器对截断输入越界，反序列化可实例化任意类；无前缀 Cookie 操作异常时不恢复前缀 | 增加长度、嵌套和引用检查，默认禁止构造类对象；前缀用 finally 恢复并返回结果；支持明确读取原始参数数组 |
| `myUploader` | 构造时无上传数据会跳过设置；路径尾斜杠不一致；只能处理有限数组形态；依赖浏览器临时路径；危险扩展名黑名单不足 | 始终保存选项、规范化路径；递归处理上传字段；验证真正 HTTP 上传；规范文件名、扩充脚本扩展限制，随机重命名使用安全随机数 |
| `myUploader` | 同名检查存在并发覆盖窗口，HTML 结果表直接输出文件名与类型；错误项缺 new_name 时报警 | 以独占方式预留目标，失败清理自身预留文件；展示字段统一 HTML 转义，兼容缺失字段；不删除调用方伪造的临时路径 |
| `myString` | 十六进制缺少零填充；JSON 解码替换合法转义引号；编码转换把布尔、数字、null 转成字符串；JSON 标量未编码 | 使用 bin2hex/hex2bin 并拒绝非法输入；标准 JSON 解码；转换时保持非字符串类型，JSON 统一 UTF-8，正确编码标量 |
| `myString` | PHP 配置导出丢失 `0012`、`true` 字符串及 null 类型；UTF-8 拆分不支持 emoji/换行；字节截取忽略 start；对象字符串化不可靠 | 用 var_export 保留类型、校验变量名；完整 UTF-8 拆分；按字节范围截取完整字符；修复字符串对象及其编码状态 |
| `myString` | 文件/字符串 INI 解析模式不同；XML 的 CDATA 终止符与非法标签键生成无效 XML；普通对象转字符串可能致命错误 | INI 均按 RAW 模式；XML 使用字符转义并校验标签；无 __toString 的对象走序列化分支 |
| `myException` | Throwable 被转成新异常导致位置丢失，ReflectionException 被静默忽略；用源码是否有 `@` 判断抑制；反复注册 shutdown；请求字段可注入错误页 HTML | 保留 Throwable 消息与位置；用 error_reporting 判断；shutdown 仅注册一次且只收集致命错误；转义错误详情，非调试模式不向客户端展示源码和请求详情 |
| `myPacker` | 解包可 `../` 越界写入，元信息长度不校验；先覆盖原包再解压，失败会破坏输入；输出句柄未关闭 | 全包预检、路径及链接检查、长度及条目限制；失败保留输入；临时流解析、临时文件替换，关闭资源 |
| `myPacker` | `addFile()` 用数组并集丢掉后续文件；打包去 BOM，解包猜测末尾 dir/fil；计数不复位；强制 sleep；可能递归链接和把输出包打进自身 | 文件列表合并；按声明长度保留完整二进制；复位计数，去掉 sleep；跳过链接及输出文件；成功打包后替换输出包 |
| `myMinify` | 过期比较方向反，删除新缓存却保留旧缓存；空缓存路径被当目录；父目录缺失时写失败；错误使用 Accept-Length 响应头 | 修正过期判断、保留空路径、通过文件写入方法建父目录，改用 Content-Length |
| `myImg` | crop 自身与外部图像的返回分支反；thumb/watermark 把句柄转给当前对象后又销毁；resize 丢透明通道；90 度旋转判断恒真 | 修正分支、资源所有权、尺寸同步、透明通道和角度判断 |
| `myImg` | 创建画布重复泄漏资源、随机比较器不满足排序约定；GIF 调色板从 13 字节头部越界读取；零值饼图除零，零/负刻度可能死循环 | 重建前释放画布并清空颜色，改为 shuffle；读取完整全局调色板并检查文件；拒绝不推进或过密刻度，处理空图表；修复 JPEG MIME 和文字包围框 |
| `myEmail` | 无显示名发件人丢掉域名，地址校验拒绝 plus 和长顶级域；附件参数顺序错；备用正文声明 base64 却未编码 | 保存完整发件与信封地址，标准邮箱校验；纠正附件调用和备用正文编码 |
| `myEmail` | 缺少 MIME 结束边界，多次 build 修改原正文；Bcc 出现在邮件头，single 并非逐封投递；连接失败与异常时泄漏连接 | 重建完整 MIME 层级，重复 build 结果一致；Bcc 仅作信封收件人，single 逐封；完成 DATA 后才标记成功，finally 关闭连接 |
| `myEmail` | 附件文件名可插入 MIME 头；SMTP 欢迎码未验证；读取不处理超时；ssl/tls 模式可能重复 TLS 升级 | 拒绝头注入；检查欢迎码、读取失败及超时并限制回复长度；隐式 TLS 不再二次 STARTTLS；显式配置证书及主机名验证 |
| `myEncrypt` | 旧算法随机数弱且重置全局 PRNG；文件错误密钥会 die 并错误删除路径；先删原文件再 rename 有损坏风险 | 保留旧格式但改用安全随机源；错误密钥返回 false；写临时文件后替换原文件并清理句柄；新增长度及奇数字节检查 |
| `myEncrypt` | 旧 XOR 格式没有完整性验证，不能保证抗篡改 | 新增 seal/open 认证加密接口；旧接口仅兼容已有格式，不把改换随机数视为算法升级 |
| `myReflection` | 扩展当作类调用不存在方法；不能识别仅有接口/trait 的名字；abstract 类型有尾空格；接口被标记 abstract；export 用法不可靠 | 根据反射对象检查能力，支持扩展/接口/trait；修正类型、名称、导出与命名空间检查，保留函数调用参数 |
| `myZip` | open 错误整数被当成成功；忽略 extractTo 的结果；无成员路径/链接/展开量预检；glob 漏隐藏文件 | 严格比较 true；预检全部成员、拒绝越界和符号链接；逐成员流式写入并核对长度；创建失败保留原输出包；包括隐藏文件，拒绝递归源链接 |
| `pinyin.php` | 字符 0 被 empty 丢弃；不合法 GBK 数值可能错误映射到末尾拼音；末尾高字节越界；逐值递减查找低效；局部 include 后表不在 global | 保留 0、检查范围与尾字节、二分查找；明确注册全局拼音表 |
| `chs2cht.dic.php` | 在函数/模块局部作用域 include 时，函数读取不到全局转换表 | 只增加表的 global 绑定；没有重新生成或批量改动词条；检查所有表的 UTF-8 完整性及基本转换 |
| `function.php` | myEval 清空调用方缓冲、异常后遗留可执行临时文件；shortUrl 对无路径 URL 警告；checkPara 对非字符串调用 strpos | 独立缓冲和 finally 清理，保留调用方输出；补齐可选路径和类型判断 |
| `function.php` | recursionFunction 只接受函数名；debug_check 未定义状态及错误传参；短链接请求明文 HTTP；isHttps 无条件信任代理头 | 支持 callable；修正调试参数；短链接改为验证 HTTPS 的客户端；代理协议头与 myReq 共用信任边界 |

myProxy 的当前初始化、虚拟属性和引用约定统一见 [对象代理](routing-config-proxy.md)。

## 请求、归档、加密和异常调用

### 可信代理

直接连接默认使用 `REMOTE_ADDR` 和真实 HTTPS 服务变量。部署在反向代理后面时，在受控的请求前置脚本中声明代理的**直接连接 IP**：

```php
<?php
myReq::setTrustedProxies(['127.0.0.1', '::1']);
```

这只是示例，不能未经核对就信任全部回环连接或公网地址。当前接口使用精确 IP 列表，不支持 CIDR。可以把调用放在框架会实际加载的优先级脚本中，例如 `config/priority/app_myStep.php`；该命名对应框架内置的 `app/myStep/info.php`。服务端必须覆盖而不是透传不可信的转发头。不要从请求参数生成这个列表。

`myReq::server('HTTP_HOST')` 现在保留端口，IPv6 如 `[::1]:8443` 也完整保留。需要纯主机名的调用方应自行按 Host/URL 规则解析。

### 归档限制和输入包保留

默认上限为 10,000 个成员、展开内容 256 MiB。`myPacker` 使用分块流式处理；大小、条目和显式单文件字符集转换仍受预算约束。

```php
<?php
$zip = new myZip($archive);
$zip->setLimits(64 * 1024 * 1024, 2000);
$ok = $zip->unzip($destination);

$packer = new myPacker($destination, $package);
$packer->setLimits(64 * 1024 * 1024, 2000);
$ok = $packer->unpack(false); // 成功后也保留输入包
```

`unpack()` 无参数时保留原有的成功后删除输入包行为；失败不删除包。所有不可信归档先预检成员路径；写入仍限于调用方明确选择的目标目录，不能用未授权请求参数决定部署目录。

### 新数据加密

```php
<?php
// 密钥存放在站点公开目录之外；这里只展示生成方式。
$key = random_bytes(32);
$sealed = myEncrypt::seal('需要保护的数据', $key);
$plaintext = myEncrypt::open($sealed, $key);
if ($plaintext === false) {
    // 密钥错误、格式不支持、数据损坏或篡改。
}
```

新格式为带版本前缀的 AES-256-GCM 信封，使用随机盐和 nonce，通过 HKDF 派生子密钥，验证认证标签。传入高熵密钥；HKDF 不是面向弱口令的慢速密码派生方案。密钥丢失不能恢复数据，旧 encStr/encFile 数据也不会自动变为新格式。

### Session 与异常详情

`myReq::sessionDecode($raw)` 默认禁止构造类对象，供会话列表等读取场景使用。只有确认来源可信且确实需要恢复对象时，才传第二参数允许类名数组；不要对用户提交的序列化数据开放任意类。

`myException::init()` 未显式指定 `display_details` 时，使用当前框架 debug 配置；缺少配置时默认不展示详情。独立开发工具需要详情，可显式设置 `display_details => true`。生产日志仍保留详细错误，需保护日志访问。

## 上传策略与图片资源预算

`myUploader::setLimits($bytes, $allowedExtensions)` 可设置实际文件大小上限与扩展白名单。类默认上限 32 MiB；普通类实例的扩展白名单默认为空，安装包等受信管理流程可按需求配置。

框架公开上传处理器默认使用常见图片、文档、音视频及压缩包扩展白名单。可选配置：

- `upload.max_size`：例如 `32M`，同时受 PHP 的 `upload_max_filesize`、`post_max_size` 限制。
- `upload.allowed_ext`：数组或逗号分隔字符串；建议按实际业务收窄。

这些可选字段无需修改现有配置即可使用默认值。图片扩展需与真实图片头一致，并通过资源预算检查。文件名危险扩展拦截和禁止上传目录执行脚本的部署规则继续生效。

`myImg::setLimits()` 默认限制：文件 32 MiB、单边 16384、总像素 24000000，同时估算当前 PHP 剩余内存。加载前检查图片头，创建、缩放、裁剪、旋转及缩略图分配前检查目标尺寸；旋转保持外部图像所有权并清理临时资源。JPEG 有 EXIF 扩展时自动按 Orientation 校正方向。

估算内存不是操作系统级资源隔离。动画、多帧、ICC 色彩管理及所有 GD 编码组合仍需专项支持。

## 不可信远程图片

新增 `myFile::getRemotePublic()`：

1. 只接受无用户名密码的 HTTP/HTTPS URL，端口限制为 80/443。
2. 校验全部 DNS A/AAAA 结果，拒绝回环、内网、保留、共享地址及 IPv4 映射/转换等非公开范围。
3. 域名连接使用 `CURLOPT_RESOLVE` 固定到已经校验的 IP，保留原 Host/TLS 主机名校验；不使用环境代理。
4. 不跟随重定向；验证 TLS 证书；限制连接/总超时及响应体，默认最多 10 MiB。
5. 缺少 cURL 或没有可验证解析结果时返回 false。

具体采集和重编码流程由应用负责，参见 [CMS 图片导入](../applications/CMS.md)。

通用 `getRemote()`、`getRemoteFile()` 保留访问受信内部服务的能力；不应把它们直接暴露给不可信 URL 输入。公开地址限制也不等同于域名业务白名单或应用授权。

## Minify 内容指纹与并发

缓存元数据包含源文件 SHA-256、合并顺序/内容、模式、打包选项、压缩实现依赖及输出摘要。即使修改时间、长度未变，内容变化也能触发重建；损坏缓存和缺失源文件会被识别。

生成时使用文件锁、同目录临时文件和原子替换；并发调用返回各自输入对应的完整结果。多个 JS 片段使用换行和分号分隔；修正 `addCSS(..., true)` 错写 JS 列表。页面资源地址携带输出摘要版本参数，浏览器能获取新资源。

元数据使用 `.meta` 后缀，当前部署规则和框架文件出口均拒绝直接访问；锁为 `.lock`。旧缓存没有摘要时会重建一次。迁移其他服务器时应同步私有缓存后缀的访问限制。当前压缩依赖和旧 pack 参数语义以 [依赖说明](dependencies.md) 为准；现代 JS/CSS 支持仍取决于实际压缩器。

## 归档流式处理与失败回滚

Packer 写入使用流式 zlib 压缩，读取使用分块 inflate 和临时流，保留原来 gzcompress 的包格式。一般文件不再整包/整文件常驻内存；显式字符集转换仍需对单个文件进行转换，受大小及内存预算限制。归档字节和条目上限继续生效；截断、尾随垃圾、校验失败及重复/冲突路径会拒绝。

Zip 和 Packer 共用 `myArchive`：先把全部内容写入私有暂存目录，再发布到目标。Zip 额外校验每个成员的实际长度和 CRC。发布前备份将覆盖的文件，普通中途失败时恢复已覆盖文件、移除本次新建文件与目录；原包只在完整成功后按调用选项删除。

目标目录保留 `.mystep-extract.lock` 以同步合作写入者；打包目录时跳过内部 `.mystep-*` 文件。目标存在符号链接、保留成员名或路径冲突时拒绝。恢复失败时保留 `.mystep-backup-*` 并记错误日志供人工恢复。

这是单进程可恢复错误下的回滚机制，不提供断电/进程强杀恢复，也不隔离其他程序同时修改目标目录。安装前仍需校验来源、授权与代码可信度；归档检查不能使任意插件代码变得可信。

## 字符集、JSON 与文件转换

更新：2026-10-16。运行环境：ServBay PHP 7.4.33。本轮修改 `lib/myString.class.php`，并同步调整 GBK 拼音字表的调用策略；保留其他类的既有修改，不提交 SVN。

### 原有缺陷与实际复现

1. `charset()` 使用非严格 `mb_detect_encoding()`，且候选混入 ISO-8859-1 等几乎什么字节都能解释的单字节编码。其结果只是猜测，GBK/Big5/西文之间本就可能歧义；没有来源参数时无法保证正确。原版把 UTF-32LE BOM 样本识别为 ISO-8859-1。
2. `setCharset()` 只有目标编码，没有明确来源入口。相同的 `C4 A2` 字节既可为 UTF-8 的 Ģ，也可为 Latin-1 的 Ä¢。原版即使额外传入第三个来源参数也会忽略，按 UTF-8 保持 Ģ。
3. mbstring 的默认替换可能吞掉信息，iconv 回退还隐含 `TRANSLIT/IGNORE`。原版 `A😀B` 转 GBK 后是 `A?B`；原始 `FF` 字节可被猜成西文并变成 UTF-8 的 ÿ，而不是报告来源不明。
4. 对象递归转换原地修改公开属性，没有循环或深度边界；数组键没有转换。`toJson()` 因此可能改动输入对象，遇到非 UTF-8 键仍编码失败；私有数据的 JsonSerializable 没有统一转码处理。
5. BOM、UTF-16/32 字节序、GB18030、编码别名和缺少 mbstring 的回退不完整。实例 get() 转码后再次猜来源，存在继续误判的机会。
6. `fileCharset()` 全量读入后直接覆盖目标；原版在目标编码非法时，已有目标内容 `keep` 被写成空字符串。没有完整编码后再发布、并发内容检查及字节/内存预算。
7. 随机汉字生成器知道源字节是 GBK，却仍调用自动检测；也没有过滤未定义或目标编码无法表示的候选。

以上样本使用临时文件及合成字节验证，不使用或修改业务数据。

### 新接口与常用调用

```php
// 明确来源与目标。推荐业务调用使用这一形式。
$utf8 = myString::convertCharset($gbkBytes, 'GBK', 'UTF-8');
$utf8 = myString::convertCharset($big5Bytes, 'BIG5', 'UTF-8');

// 旧批量入口仍可使用；增加可选来源及策略。
$copy = myString::setCharset($data, 'UTF-8', 'GBK');

// 若业务明确允许损失，必须显式选择。
$text = myString::convertCharset('A😀B', 'UTF-8', 'GBK', ['on_error'=>'replace']); // A?B
$text = myString::convertCharset('A😀B', 'UTF-8', 'GBK', ['on_error'=>'ignore']);  // AB

// GB18030 可表示样本中的补充平面汉字及 Emoji；GBK 不可。
$bytes = myString::convertCharset('𠮷😀', 'UTF-8', 'GB18030');

// 规范别名，不接受 Base64/HTML 实体等“非字符集”或 //IGNORE 后缀。
$name = myString::normalizeCharset('CP936'); // GBK

// 调整推测候选，但来源已知时仍应直接指定。
$name = myString::charset($bytes, ['BIG5','GBK','UTF-8']);
```

#### 严格与容错策略

默认 `on_error=throw`：先验证源字节，再检查转换后是否可按原编码逐字节往返。非法输入、无法表示的字符以及会改变源字节的映射都明确报错，不静默接受替代字符。

这是一项保守检查：某些有多重映射或会规范化字节的编码，即使显示相近也可能被拒绝。它保证“给定来源下不接受可发现的字节损失”，不能证明自动推测的来源就是正确来源。GB18030 的具体映射沿用当前 PHP 后端，不声称实现了所有新版本国家标准。

`replace` 明确允许替换，要求 mbstring；`ignore` 明确允许丢弃，两个后端均可选择。转换临时修改的 `mb_substitute_character()` 在正常返回和异常时恢复，不改变其他调用的全局替换策略。容错时仍建议明确来源，避免把坏 UTF-8 先猜成另一套合法字节。

`backend` 可指定 auto/mbstring/iconv。默认优先 mbstring，不可用时选择 iconv。iconv 不再隐含 TRANSLIT/IGNORE；mbstring 专属别名在必要时映射到 iconv 名称，例如 SJIS-WIN → CP932。后端不存在时明确报错，不能假装成功。不同平台的扩展与字符映射仍可能不同。

#### 编码推测与 BOM

- 按显式候选顺序逐个严格验证，不依赖新版 mb_detect_encoding 的评分变化。
- 默认候选为 UTF-8、GBK、Big5、GB18030；纯 ASCII 及空文本沿用 UTF-8 兼容结果。
- 不默认用 Latin-1/Windows 单字节编码“兜底”；这些来源需要显式指定或放进候选。
- 优先识别 UTF-8、UTF-16LE/BE、UTF-32LE/BE BOM，先检查 UTF-32，避免将共享前缀误认成 UTF-16。
- BOM 与显式源编码冲突会报错；截断或非法的 BOM Unicode 内容不会回退成其他编码。
- 默认 `bom=remove`；`preserve` 将已有 BOM 保留为目标 Unicode BOM；`add` 强制添加。非 Unicode 目标不接受添加 BOM。
- UTF-16/UTF-32 泛称选择明确 BE 字节序并输出 BOM；不需要 BOM 时使用带字节序的名称。

无 BOM 的 UTF-16、任意二进制、混合编码文本不能靠该推测可靠识别。API 处理文本，不是图片、压缩包或任意字节的自动修复器。

### 数组、对象、实例与 JSON

`setCharset()` 返回新结果；嵌套数组保留键及非字符串类型，普通对象复制公开状态，ArrayObject 转换存储内容，共享对象在结果中仍共享同一副本。拒绝循环引用及超过默认 64 层的结构，避免无限递归；深度可用 max_depth 调整至 1～512。

- 数组键默认保持原样；显式 `convert_keys=true` 才转换，转换后发生键冲突会报错。
- 普通类不会自动改名声明属性，不转换其私有业务状态；内部对象的特殊资源/状态不承诺通用复制。不可克隆对象明确报错，自定义 `__clone()` 仍可能执行业务代码。
- 实例构造及 set() 可传明确来源，get() 成功后记住目标编码，失败时保留旧字节和来源。

```php
$value = new myString($bytes, 'ISO-8859-1');
$utf8 = $value->get('UTF-8');

$json = myString::toJson($data);                  // 自动转 UTF-8，不改写输入对象
$json = myString::toJson($data, 'UTF-8', 'GBK');  // 第三参数明确字符串值来源
$json = myString::toJson($data, '');              // 完全跳过预处理，使用原生 JSON 行为
```

JSON 字符串值保留原有前导 U+FEFF（可在 JSON 字符串中合法存在），不把它与 JSON 文档头混淆；需去 BOM 时先显式转换。JSON 恒为 UTF-8，第二个 charset 参数保留原兼容用途，不能据此生成 GBK JSON。新第三参数指定字符串值来源；非 UTF-8 键单独推测，已经有效的 UTF-8 键保持原样。需要对歧义键应用明确来源时，先 setCharset(..., ['convert_keys'=>true])，再用空第二参数 JSON 编码。

JSON 模式尊重 JsonSerializable，并按对象数据形状处理 ArrayObject、DateTime 等；不因处理器的作用域意外暴露 myString 的受保护字段。业务 jsonSerialize() 自身的副作用仍由业务负责，同一共享对象的序列化结果只取得一次。递归/深度及原生不支持类型保留 false 与 json_last_error()；明确来源错误或转码损失会抛异常，业务序列化异常继续传递。

### 文件转换

```php
// 默认覆盖源文件；先成功转换，才原子替换。
$bytes = myString::fileCharset($source, '', 'UTF-8', 'GBK');

// 导出另一个文件，必要时显式调整读取预算。
$bytes = myString::fileCharset($source, $target, 'UTF-8', 'GBK', ['max_bytes'=>16*1024*1024]);
```

只接受本地文本路径，拒绝直接符号链接和流包装器；父目录必须存在。先检查源大小、读取稳定性及 PHP 剩余内存，再完整转换，使用同目录临时文件与旁路锁进行原子发布。保留既有文件权限，新文件权限为 0600；目标内容在读取后发生变化时拒绝覆盖。转换或保存失败时保留旧目标，空文件成功返回 0，源不存在返回 false。

默认输入预算 32 MiB，另有保守内存估算及输出预算。文件仍全量读取，不宣称实现了任意编码的大文件流式转码。超大文件需要分块维护字符边界和状态机的独立方案。父目录及其他文件写入者仍应受信，并遵循锁约定。

### 相关调用与兼容变化

- 拼音字表只覆盖 GBK，已显式选择 ignore 跳过 Emoji 等无法表示的字符，避免严格策略破坏正常汉字/ASCII 拼音输出。
- 随机汉字的来源固定 GBK，不再猜测；候选须是真正汉字且可由目标编码表示，最后整体转换，避免 UTF-16/32 每个字符重复添加 BOM。
- 旧两参数 setCharset() 仍可使用，但默认严格策略与原版自动替换/丢弃不同；原地改写对象的旧副作用也不再保留。调用方应接收返回对象。
- 西文/日文/繁体等已知来源应明确传入，不能依赖默认候选替业务决定。
- 没有迁移或批量改写现有文件、数据库及存量乱码；已经丢失的字符不能靠此修复恢复。

### 回归与参考

```sh
php tests/utilities/charset_regression.php
```

专项包括明确来源歧义、常见别名、GB18030、Unicode BOM/字节序、无效字节、不可表示字符、可选容错、全局替换策略恢复、对象共享与循环、JSON 数据形状、临时文件失败保护及权限，以及禁用 mbstring 函数后真正执行 iconv 回退的独立子进程。

参考：[PHP 编码检测的局限](https://www.php.net/manual/en/function.mb-detect-encoding.php)、[mbstring 替代字符策略](https://www.php.net/manual/en/function.mb-substitute-character.php)、[iconv 的忽略/转写行为](https://www.php.net/manual/en/function.iconv.php)。实现及所有验证以本机 PHP 7.4 为准。

## 兼容规则与剩余边界

- 请求默认 str 模式保留历史文本处理，新入口显式取得原始值并按类型和输出上下文处理；它不代替 SQL 或权限校验。
- `myFile::realPath` 的相对路径以 ROOT 为基准；持久化路径使用 myPath，公开文件边界使用 resolveWithin。文本 getLocal 保留去 BOM 约定，二进制出口使用原始读取。
- 通用 getRemote/getRemoteFile 保留受信内部服务访问能力；不可信远程 URL 使用 getRemotePublic，并由应用补充业务授权。
- 公开上传入口已有扩展白名单、图片内容及资源预算检查；普通 myUploader 实例仍需按场景设置策略。EXIF 方向已支持，动画、多帧及 ICC 不宣称完整支持。
- Zip/Packer 已采用暂存发布及可恢复错误回滚；不提供断电或强杀恢复，不会让任意代码包变得可信。
- Minify 已包含内容指纹、依赖版本和并发发布；旧 get(true)/show(true) 使用普通压缩，已移除 eval 包装依赖。
- 旧 XOR 格式只用于兼容；新敏感数据使用 seal/open，存量密文不自动迁移。SMTP 未据本机测试承诺所有服务商、SMTPUTF8 或国际化邮箱均兼容。
- 拼音及简繁字典保留旧词表边界，不保证多音字、地区用语和任意文本严格可逆；反射与 myEval 仅供可信代码和管理环境使用。

验证入口：`php tests/utilities/utility_regression.php`、`python3 tests/utilities/utility_io_fixture.py`、`php tests/core/core_regression.php`、`php tests/utilities/charset_regression.php`；具体隔离条件见 [测试说明](../../tests/README.md)。
