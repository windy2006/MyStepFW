# 模板实例、风格和模块对应

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

## 基本使用

```php
$tpl = new myTemplate([
    'name' => 'main',
    'style' => 'default',
    'path' => __DIR__.'/template',
    'path_compile' => CACHE.'/template/example',
]);
$tpl->assign('title', '欢迎使用框架')
    ->assign('content', '<p>由应用产生的可信 HTML</p>')
    ->setIf('logged_in', true)
    ->setSwitch('kind', 'article')
    ->setLoop('items', [
        ['name'=>'第一条', 'id'=>1],
        ['name'=>'第二条', 'id'=>2],
    ], true);
$html = $tpl->render('', false); // false 只返回，不立即输出
```

`name` 和 `style` 可以是相对的多级子目录，不能含 `..`。`path`、`path_compile`、页面缓存路径由应用配置。找不到首选模板时仍尝试 `path/default/文件名`。后来创建首选文件后，旧默认模板缓存会失效。

## 配套完整示例

阅读[主布局与功能模板](template-example.md)：包含准备步骤、完整代码、预期输出及实际应用中的替换位置。先跑通示例，再查本页逐方法参数。

## 主要方法：参数、返回和失败约定

### myTemplate::init

功能说明：初始化模板、静态页面缓存和标签分隔符

调用示例：
    $tpl = new myTemplate(['path' => $views, 'path_compile' => $compiled, 'name' => 'home']);
    $tpl->assign('title', '示例'); $html = $tpl->render('', false);

```text
public function init($setting, $cache = false, $allow_script = false, $delimiter = null);
```

| 参数说明 |
| --- |
| array $setting name/style/path/path_compile/ext；compile_version 可显式更新扩展编译版本 |
| bool\|array\|null $cache false/null关闭，true或数组启用；选项含name/path/ext/expire/vary，expire单位秒（默认：false） |
| bool $allow_script 仅对可信模板开启，注册标签回调始终属于可信 PHP 扩展（默认：false） |
| array\|null $delimiter 两个非空字符串，默认 HTML 注释分隔符（默认：null） |

**返回**：void 无返回值。

**异常**：InvalidArgumentException 参数或相对模板名称不合法

### myTemplate::setTplPara

功能说明：设置模板位置并建立当前对象的数据空间，同一对象切换模板会清空赋值

设置结构：name默认main，style默认空串，path默认./，path_compile默认./complied（使用拼写），ext默认tpl，compile_version默认空串；路径名必须来自受信配置。

```text
public function setTplPara($setting);
```

| 参数说明 |
| --- |
| array $setting name 默认 main，ext 默认 tpl；path_compile 默认 ./complied |

**返回**：$this 返回当前实例，支持链式调用。

**异常**：InvalidArgumentException 配置非法

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
