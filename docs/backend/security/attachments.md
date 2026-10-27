# 附件管理

## 本篇目标与调用场景

会话与凭据是框架辅助能力，业务仍独立校验身份、数据归属及操作权限。代码预算不替代PHP解析前的补丁与服务器限制。

## 附件定位、日志与管理入口

上传文件名使用 `时间戳.随机值.扩展名`：文件名前两段组成附件ID，随机部分来自 `random_bytes()`。

使用 `myAttachment` 统一访问附件日志。下载、删除和 CMS 图片接口均使用它：

- 支持完整文件名及不含最后扩展名的完整 ID，不再按前缀匹配，拒绝歧义。
- 优先按时间戳定位目录；支持早先生成的纯随机文件名，回查不跟随符号链接，并设置扫描上限。
- 日志追加/读取/删除加锁，显示名去除换行和日志分隔符；上传成功但记录失败时删除刚上传的文件。
- 批量接收的每个成功附件都会记入日志，API 继续返回第一项以保留原接口结构。
- 删除最后一个记录时保留目录和其他文件，不再递归清空目录。

附件后台页面不再在浏览时删除“没有日志”的目录或改写日志。年份、月份及删除目标均有路径校验；删除未记录文件仅允许 POST。显示名按 HTML 上下文转义。

下载的 Referer 检查改为精确来源比较，拒绝类似 `mystep.com.evil.test`。Referer 仍仅作为防盗链策略，不能作为保密附件的用户授权机制；私有资源应由应用按用户身份及记录归属授权。

## 主要方法：参数、返回和失败约定

### myAttachment::find

功能说明：按日期目录或有限递归扫描附件索引，仅返回唯一匹配

```text
public static function find($root, $id, $mode = 'Ym/');
```

| 参数说明 |
| --- |
| string $root 操作限制在其中的受信根目录 |
| string $id 操作对象的标识 |
| string $mode 由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'） |

**返回**：array|false 唯一附件记录；未找到、歧义或路径不合法为false

### myAttachment::append

功能说明：清理显示文件名并加锁追加一条附件索引

```text
public static function append($directory, $stored, $display);
```

| 参数说明 |
| --- |
| string $directory 存放附件文件及log.txt的目录 |
| string $stored 实际落盘的附件文件名 |
| string $display 用于展示和下载的原始文件名，会移除日志分隔符/换行 |

**返回**：bool 索引记录是否完整追加

### myAttachment::remove

功能说明：加锁复核索引中的唯一匹配，删除附件并重写剩余索引

```text
public static function remove($root, $id, $mode = 'Ym/');
```

| 参数说明 |
| --- |
| string $root 操作限制在其中的受信根目录 |
| string $id 操作对象的标识 |
| string $mode 由文件名时间戳推导子目录的date格式，默认Ym/（默认：'Ym/'） |

**返回**：bool 附件删除及索引改写是否成功

## 明确调用示例

```php
// $directory为当前分区目录，stored.bin已由上传流程写入。
$ok = myAttachment::append($directory, 'stored.bin', '用户原始名称.bin');
// 从HTTP参数读取id后先校验业务授权，再在指定root内find/remove。
$entry = myAttachment::find($uploadRoot, $id, 'Ym/');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
