# 条件、循环、switch和随机块

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

## 标准块

```html
<!--if:start key="logged_in"-->
    <p>已登录</p>
    <!--loop:start key="items" time="3"-->
        <li data-id="<!--items_id|html-->"><!--items_name|html--></li>
    <!--loop:end-->
<!--else-->
    <p>请登录</p>
<!--if:end-->

<!--switch:start key="kind"-->
<!--(article)--><article><!--content--></article>
<!--switch:default--><p>其他类型</p>
<!--switch:end-->

<!--random:start--><p>A</p><!--(s)--><p>B</p><!--random:end-->
```

`time` 沿用历史含义：补足最少行数，不限制数据条数；最多允许补至 100000 行。需要分页或限制数量时由应用查询完成。补白仍使用空单元规则：清除占位标签，把 `td/li/p/dd/dt` 的内容换成 `&nbsp;`；复杂布局建议由应用提供明确空态。

循环可嵌套，内层可以读取外层记录；相同 key 的内层循环优先。块名称、属性名按大小写不敏感匹配，变量名保持大小写。条件缺省为 false，switch 值缺省为 null，沿用 PHP 的宽松 case 比较；`<!--(值)-->` 保持原分支结构，`<!--(default)-->` 也是匹配字符串 default 的普通分支；新增兜底分支使用 `<!--switch:default-->`，避免占用旧分支值。随机块每次实际渲染时选择一个分支，开启页面缓存后会缓存本次结果。

未配对、交叉闭合或超过 64 层的标准块会抛出 `UnexpectedValueException`，不再静默生成损坏的 PHP。


## 默认分支与兼容语法

现有括号分支维持原结构与 PHP 7.4 宽松匹配；每个 switch 至多一个显式兜底，重复兜底报错，嵌套块互不影响。getBlock 继续返回块原文、普通分支映射和第三项兜底正文；只接收前两项的旧调用有效。实际示例核对另见 [示例应用](../../showcase/applications/sample.md)。

执行 `php tests/templates/template_regression.php`；结构检查和编译成功不代替业务模板的查询及权限验证。

## 主要方法：参数、返回和失败约定

### myTemplate::setLoop

功能说明：设置循环记录，支持数组或可遍历对象

```text
public function setLoop($key, $value, $fullset = false);
```

| 参数说明 |
| --- |
| string $key 循环名称，对应 key_field 占位符 |
| array\|object $value 单条记录；fullset=true 时为完整记录集合 |
| bool $fullset true 替换集合，false 追加记录；time 仍只作最少行数补齐（默认：false） |

**返回**：$this 返回当前实例，支持链式调用。

**异常**：InvalidArgumentException 集合或记录类型非法

### myTemplate::setIf

功能说明：设置条件值，缺省条件为 false

```text
public function setIf($key, $value = true);
```

| 参数说明 |
| --- |
| string $key 条件名称 |
| mixed $value 按 PHP 布尔规则判断（默认：true） |

**返回**：$this 返回当前实例，支持链式调用。

### myTemplate::setSwitch

功能说明：设置分支值，未赋值时按 null 处理

```text
public function setSwitch($key, $value);
```

| 参数说明 |
| --- |
| string $key 分支名称 |
| mixed $value 保留旧 switch 的宽松比较规则 |

**返回**：$this 返回当前实例，支持链式调用。

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
