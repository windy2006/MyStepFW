# 命名格式、捕获参数与字面路径

## 本篇目标与调用场景

解释声明、上下文和实际调用之间的关系；请求参数不能选择任意PHP文件或回调。

## 路由 myRouter

### 匹配规则

- 普通规则仍按原来的正则语义注册，但完整匹配边界覆盖整个顶层分支，避免 `/a|/b` 意外匹配 `/a-extra`。
- 编译时选择不与规则冲突的分隔符，规则中的 `#` 等字符不再直接破坏正则。
- 自定义格式预先验证，不接受会破坏捕获参数位置的命名组及反向引用。原来普通内部捕获组继续转为非捕获组，外层只保留一个参数。
- 原始正则规则中的命名捕获只按数字捕获顺序传递一次，不把同一个参数重复传给处理器。
- PCRE 执行失败会抛异常，不再把回溯资源不足当成“没有规则匹配”而继续走应用回退。
- 同一对象多次检查不同 URL 时保留绑定前缀，并同步更新实例和全局应用信息。
- 重解析查询后按 PHP 的 request_order/variables_order 重建 REQUEST，POST/Cookie 的优先级遵循配置，旧 GET 参数不会残留。
- 持久路由只接受可导出的普通数据和命名回调；不再把看起来像 `function(){...}` 的字符串反转义成可执行 PHP。闭包及对象回调仍可在内存注册。

### 字面路径接口

```php
$router->literal('/article/[int].json', 'articleHandler', 'MyApp');
```

仅已定义的命名占位符使用正则，`.` 等字符按字面匹配；未知占位符报错。旧 `rule()` 保持正则语义，已有规则无需全部改写。

路由声明及正则仍是受信开发配置，不是面向不可信用户的正则沙箱。路由优先级保持“直接规则先于绑定前缀规则，同一轮按声明顺序首个匹配”。

## 主要方法：参数、返回和失败约定

### myRouter::format

功能说明：注册或覆盖一个命名占位符格式，每个占位符只占一个参数位置

内部普通捕获组转为非捕获组，整体再包一层捕获；不接受命名捕获及反向引用。
格式在注册时校验，应用作用域格式可由 setRules() 临时加载。

```text
public function format($name, $pattern);
```

| 参数说明 |
| --- |
| string $name 占位符名称，由字母、数字或下划线组成，例如 hex |
| string $pattern 非空 PCRE 片段，不包含两端分隔符，例如 [a-fA-F0-9]+ |

**返回**：$this 当前路由对象，可继续注册

**异常**：InvalidArgumentException 名称、片段、捕获形式或编译结果非法

### myRouter::setFormats

功能说明：批量注册占位符格式，可递归处理多组格式数组

```text
public function setFormats($formats);
```

| 参数说明 |
| --- |
| array $formats 名称到正则片段的映射，或由此类映射组成的嵌套数组 |

**返回**：$this 当前路由对象；空数组不改变格式

**异常**：InvalidArgumentException 参数不是数组或其中格式非法

### myRouter::literal

功能说明：注册字面路径模板，只把已定义的命名占位符展开为正则

例如 /article/[int].json 中的点号按字面匹配；捕获值仍按声明顺序传给处理器。
与 rule() 相比，未知占位符直接报错，不回退为捕获占位符名称。

```text
public function literal($path, $handler, $idx = '');
```

| 参数说明 |
| --- |
| string $path 非空路径模板，例如 /article/[int].json |
| callable\|string\|array $handler 回调、带逗号参数的命名处理器或中间件序列 |
| string $idx 所属应用标识，含义与 rule() 一致（默认：''） |

**返回**：$this 当前路由对象

**异常**：InvalidArgumentException 路径非法、占位符未定义或展开结果无法编译

## 明确调用示例

```php
$router = new myRouter();
$seen = null;
$router->literal('/article/[int].json', function ($id) use (&$seen) { $seen = $id; });
assert($router->check('/article/42.json')); // check会匹配并执行处理器。
assert((int)$seen === 42);
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
