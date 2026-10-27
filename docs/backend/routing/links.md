# 地址生成

## 本篇目标与调用场景

解释声明、上下文和实际调用之间的关系；请求参数不能选择任意PHP文件或回调。

### 6.1 三种 URL 输入

以下示例仅说明路由形态，`path/to/resource` 不对应任何预设业务模块：

| 模式 | URL 示例 |
| --- | --- |
| `rewrite` | `/path/to/resource?x=1` |
| `path_info` | `/index.php/path/to/resource?x=1` |
| `query_string` | `/index.php?/path/to/resource&x=1` |

`parseQuery()` 在服务变量之间按当前实现提取路径：优先使用 PATH_INFO，再支持 ORIG_PATH_INFO，否则从 REQUEST_URI 中去除入口文件或部署子目录。识别到明确的查询路径时，该路径覆盖前面的路径结果；重定向标记也有独立处理分支。

随后将查询串与路径分离。路径统一以 `/` 开始，纯单词及斜杠构成的路径保留末尾斜杠约定；查询参数按配置分隔符处理后通过 `parse_str()` 解析，同时更新 `$_GET` 和 `$_SERVER['QUERY_STRING']`。

注意：`parse_str()` 具有 PHP 自身的解码、数组参数及重复键语义；框架不会保存一套独立于 PHP 的原始多值参数模型。路径中的百分号编码也不在此阶段统一解码，文件安全检查会自行解码。

| 路由字段 | 含义 |
| --- | --- |
| `route['p']` | 匹配使用的路径 |
| `route['q']` | 分离后的查询串 |
| `route['qstr']` | 路径与查询参数的组合值，路径和参数以 `&` 连接 |
| `query` | 当前参与匹配的路径，不包含查询串 |
| `info['app']` | 初步承接应用标识 |
| `info['path']` | 去除应用标识后的路径段 |
| `info['para']` | 已解析的查询参数 |

`parse()` 仅在首段是合法单词标识且存在对应 `info.php` 时将其识别为应用；否则使用 `router.default_app`，原路径由该默认入口承接。

## 明确调用示例

```php
$url = myStep::setURL('Hello/detail');
$url .= (strpos($url, '?') === false ? '?' : '&').http_build_query(['id'=>7,'keyword'=>'A&B'], '', '&', PHP_QUERY_RFC3986);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
