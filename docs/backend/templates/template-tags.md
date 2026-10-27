# 扩展标签、子模板和依赖

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

## 自定义标签

```php
$tpl->regTag('badge', function (myTemplate &$template, &$attrs) {
    return '<span>'.htmlspecialchars($attrs['text'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</span>';
});
// 模板：<!--badge text="a=b"-->，也支持无属性的 <!--badge-->
```

仍保留 `regTag`、`getTemplate`、`getBlock`、`{myTemplate::参数名}`、`/*split*/` 以及属性全局变量引用约定。标签回调在编译时执行，返回内容可以包含可信 PHP。CMS 现有循环单元/heredoc 代码生成接口继续支持；`getBlock` 返回 `[块原文, 单元或分支映射, 补白或 else]`，if 单元仍按旧约定 `addslashes`。switch 的第二项保留所有普通分支映射（含字符串 default），第三项返回显式 `switch:default` 的正文，未设置时为空串。

已登记标签可带引号属性或无属性调用，未知的带属性标签原样保留。扩展生成内容不自动递归调用其他自定义标签，避免意外无限展开。回调依赖的文件应通过 `getTemplate` 读取，才能自动登记依赖。

属性占位符在 HTML、PHP 引号字符串和 heredoc 中按各自语法编译，不在 heredoc 内插入 PHP 开始标签。需要运行期属性值时使用 heredoc；nowdoc 仅替换编译期的静态属性值。

具名函数和静态方法可以跨对象、跨请求复用编译结果。闭包/对象回调带有运行状态，按模板对象区分，避免相同代码行上的不同闭包复用错误；相同生成结果仍共用内容版本文件。若具名回调读取数据库或外部配置来决定编译结果，应用须更新 `setting.compile_version` 或调用 `removeCache()`。

模板、回调及 PHP 配置文件必须来自可信应用；`allow_script=false` 不构成不可信模板沙箱，自定义标签本来就有执行 PHP 的权限。

## 配套完整示例

阅读[编译期生成代码与运行期数据](tag-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myTemplate::getTemplate

功能说明：获取模板或默认风格模板，并登记编译依赖（含不存在的候选路径）

```text
public function getTemplate($file1, &$file2 = '');
```

| 参数说明 |
| --- |
| string $file1 首选本地文件；只能由可信应用代码构造 |
| string &$file2 引用参数；备用文件，成功后回填实际位置（默认：''） |

**返回**：string 原始模板内容，不转换换行或反斜杠

**异常**：RuntimeException 模板不存在或不能读取

### myTemplate::regTag

功能说明：登记可信的编译期标签回调，扩展结果可包含 PHP

回调签名：function (myTemplate &$tpl, array &$attrs): string；两项按引用传入，允许回调忽略不需要的形参。attrs以解析值为基础并含unit/unit_blank/class，回调可补充占位参数；在编译期执行，返回的PHP仅在渲染时按所处模板分支执行。

调用示例：
    $tpl->regTag('hello', function ($current, &$attrs) {
        return htmlspecialchars((string)($attrs['name'] ?? '访客'), ENT_QUOTES, 'UTF-8');
    });

```text
public function regTag($tag_name, $tag_func = '');
```

| 参数说明 |
| --- |
| string\|array $tag_name 标签名或名称到回调的映射 |
| callable\|string $tag_func 接收当前模板和属性引用，返回模板/PHP 文本；允许无属性标签（默认：''） |

**返回**：$this 返回当前实例，支持链式调用。

**异常**：InvalidArgumentException 标签名或回调非法

### myTemplate::getBlock

功能说明：提取首个完整块，支持可信扩展生成 PHP 时使用的返回格式

```text
public function getBlock($tpl_content, $tag = '', $idx = '');
```

| 参数说明 |
| --- |
| string $tpl_content 子模板正文 |
| string $tag loop/if/switch，支持同类嵌套且名称按字面匹配（默认：''） |
| string $idx loop 记录变量名，替换为旧 heredoc 字段表达式（默认：''） |

**返回**：array [块原文, 单元或分支映射, 补白/else/switch 默认正文]；(default) 仍为普通分支键，未找到时均为空串

**异常**：InvalidArgumentException 非法 loop PHP 变量名；UnexpectedValueException 找到的块未闭合

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
