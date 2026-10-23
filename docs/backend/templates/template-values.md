# 模板变量、HTML与URL转义

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

## 变量和转义

```html
<h1><!--title|html--></h1>
<a href="/item?id=<!--id|url-->"><!--label|html--></a>
<section><!--content--></section>
```

- `<!--name-->` 保留原样输出，兼容现有富文本和 HTML 片段。
- `<!--name|html-->` 对 UTF-8 HTML 文本及已加引号的属性转义，含单双引号和无效字节替换。
- `<!--name|url-->` 编码一个 URL 组件，不是整个链接。
- `assignEscaped('name', $value)` 或 `assignEscaped('prefix', $mapping)` 可以在赋值时转义；不要再对同一值使用 `|html`，否则会二次编码。
- 缺失占位符和不可直接显示的数组/对象输出空串；对象可通过 `assign('user', $object)` 展开一层为 `user_name` 等字段。

HTML 转义不承担 JavaScript、CSS 或链接协议校验。脚本数据应由应用生成合适的 JSON；链接协议由应用校验。


## 主要方法：参数、返回和失败约定

### myTemplate::assign

功能说明：赋值普通变量，数组/对象展开一层为 name_key；旧占位符仍原样输出

```text
public function assign($name, $value = '');
```

| 参数说明 |
| --- |
| string\|array\|object $name 名称或无前缀批量变量 |
| mixed $value 值；非字符串标量保留 myString::fromAny 的历史表示（默认：''） |

**返回**：$this 返回当前实例，支持链式调用。

### myTemplate::assignEscaped

功能说明：将变量按 HTML 文本/引号属性上下文转义后赋值；不要再叠加 |html

```text
public function assignEscaped($name, $value = '');
```

| 参数说明 |
| --- |
| string\|array\|object $name 名称或批量映射 |
| mixed $value 值，批量赋值沿用 assign 的一层展开规则（默认：''） |

**返回**：$this 返回当前实例，支持链式调用。

### myTemplate::loadSet

功能说明：加载可信 PHP 返回数组、INI 或 JSON 配置，支持重复调用

```text
public function loadSet($file, $prefix = '', $mode = false);
```

| 参数说明 |
| --- |
| string $file 本地配置位置；PHP 文件会执行，只能来自可信应用 |
| string $prefix 赋值前缀（默认：''） |
| bool $mode true 用下划线保留嵌套键路径，false 仅保留叶子键（默认：false） |

**返回**：$this 不存在文件沿用旧行为忽略

**异常**：UnexpectedValueException 配置不是数组或深度超过 64

## 明确调用示例

```php
$template = new myTemplate([
    'name'=>'list', 'path'=>$templateDirectory, 'path_compile'=>$compileDirectory,
], false);
$template->assign('title', 'A&B')->setIf('visible', true)
    ->setLoop('items', [['id'=>1,'title'=>'中文']], true);
$html = $template->render('', false);
// list.tpl使用<!--title|html-->和<!--loop:start key="items"-->等标签。
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
