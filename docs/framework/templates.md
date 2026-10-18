# 模板、缓存与标签兼容规范

整理日期：2026-10-18。目标运行环境：PHP 7.4。普通字符串分支 `(default)` 与兜底 `switch:default` 按后续兼容核查结论区分。

## 已确认并修复的问题

| 原有问题 | 调整后的行为 |
| --- | --- |
| 静态变量表只按模板文件命名，不同对象串用参数 | 每个实例有独立空间；克隆、序列化恢复也独立；析构只释放自己的数据 |
| `loadSet` 在方法内声明全局函数，第二次调用可能致命失败 | 使用有深度限制的局部递归闭包；JSON/PHP/INI 返回值必须为数组 |
| 块正文放入 PHP heredoc，美元符、花括号、反斜杠和结束标记可能导致插值、内容改变或语法错误 | 普通文字编译成 `var_export` 字面量输出，不作为 PHP 表达式解释 |
| 多行短输出标签无法正确移除，XML 声明可能受 PHP 短标签配置影响 | PHP 词法器隔离脚本；保留 XML 声明；默认关闭模板脚本 |
| 嵌套循环/条件无法正确配对，局部循环变量相互覆盖 | 按层解析；使用独立循环记录栈；错误闭合明确报错，嵌套上限 64 |
| 循环编译依赖首条记录字段，缺字段或空集合导致错误 | 在渲染时读取每条记录；缺失字段为空；数组、对象记录和可遍历集合均支持 |
| switch 分支被普通变量注释或嵌套标签打断 | 只用当前层 case 标记划分分支；支持显式默认分支 |
| 属性等号被截断、空字符串和反斜杠丢失 | 按引号边界读取属性，保留原始值 |
| 全部空行被压缩，简单 minify 改变 JS/CSS、pre 等内容 | 默认保留原始换行；minify 改为保守去除普通 HTML 标签行首缩进 |
| 输出复用或清空外部缓冲区，异常可能留下半页 | 渲染使用自己的缓冲层，异常清理后继续传播 |
| `cache=true`、空配置数组不能启用缓存 | `true` 和 `[]` 都使用默认页面缓存配置；有效空页和 `0` 可以命中 |
| 编译目录与风格目录缺少分隔符 | 使用明确的路径连接，模板名和风格拒绝上级路径、包装器和绝对路径 |
| 只判断文件修改时间，同秒更新和子模板更新会漏失 | 对主模板、默认风格候选和 `getTemplate` 子模板记录内容摘要；具名标签源码也参与依赖校验 |
| 编译身份遗漏 PHP 开关、分隔符、标签实现和外部版本 | 编译指纹覆盖这些选项；闭包和对象回调按实例区分；外部编译状态可用 `compile_version` 更新 |
| 并发写入 PHP/HTML 文件可能读取到半个文件 | 同目录临时文件原子替换；渲染使用内容摘要命名的独立 PHP 版本 |
| 页面缓存名称清理可能碰撞，无法显式区分语言等差异 | 清理会附加原名摘要；新增 `vary` 作为缓存维度 |



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

## 自定义标签

```php
$tpl->regTag('badge', function (myTemplate &$template, &$attrs) {
    return '<span>'.htmlspecialchars($attrs['text'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</span>';
});
// 模板：<!--badge text="a=b"-->，也支持无属性的 <!--badge-->
```

仍保留原有 `regTag`、`getTemplate`、`getBlock`、`{myTemplate::参数名}`、`/*split*/` 以及属性全局变量引用约定。标签回调在编译时执行，返回内容可以包含可信 PHP。CMS 现有循环单元/heredoc 代码生成接口继续兼容；`getBlock` 返回 `[块原文, 单元或分支映射, 补白或 else]`，if 单元仍按旧约定 `addslashes`。switch 的第二项保留所有普通分支映射（含字符串 default），第三项返回显式 `switch:default` 的正文，未设置时为空串。

已登记标签可带引号属性或无属性调用，未知的带属性标签原样保留。扩展生成内容不自动递归调用其他自定义标签，避免意外无限展开。回调依赖的文件应通过 `getTemplate` 读取，才能自动登记依赖。

属性占位符在 HTML、PHP 引号字符串和 heredoc 中按各自语法编译，不在 heredoc 内插入 PHP 开始标签。需要运行期属性值时使用 heredoc；nowdoc 仅替换编译期的静态属性值。

具名函数和静态方法可以跨对象、跨请求复用编译结果。闭包/对象回调带有运行状态，按模板对象区分，避免相同代码行上的不同闭包复用错误；相同生成结果仍共用内容版本文件。若具名回调读取数据库或外部配置来决定编译结果，应用须更新 `setting.compile_version` 或调用 `removeCache()`。

模板、回调及 PHP 配置文件必须来自可信应用；`allow_script=false` 不构成不可信模板沙箱，自定义标签本来就有执行 PHP 的权限。

## 页面缓存与失效

```php
$tpl = new myTemplate($setting, [
    'path' => CACHE.'/html/example',
    'name' => 'home',
    'expire' => 300,
    'vary' => ['lang'=>$language, 'audience'=>'public'],
]);
if ($tpl->checkCache()) {
    echo $tpl->getCache();
} else {
    // 查询数据、赋值后渲染
    $tpl->render();
}
// 清理编译文件；true 连同当前页面及其元数据一起清理。
$tpl->removeCache(true);
```

缓存维度由应用确定。私人页面应关闭共享页面缓存，或使用足够完整的用户维度。`vary` 不会自动识别登录态、权限、语言或查询参数；它支持可序列化值，不建议把含凭据的完整配置作为维度。

页面到期使用实际 `time()`；`expire<=0` 不读写页面缓存。页面必须有有效元数据，内容/依赖/基础编译身份一致才命中；旧无元数据页面第一次会重新生成。正常赋值不会自动让未到期页面失效，数据库业务变化仍应由应用主动失效或等待 TTL。

编译输出包括旧 `name.php` 最新入口、`name.php.meta.json` 和 `name.php.<SHA256>.php` 内容版本。当前渲染直接使用已验证版本，避免并发切换编译选项影响正在执行的页面。历史内容版本由 `removeCache` 或站点统一缓存清理释放；编译/缓存目录不可允许访客写入，也不应作为公共静态资源目录发布。

## 兼容性变化

1. 同文件新建模板对象不会继承其他对象的变量；需要复用赋值时明确赋值或克隆。
2. `setTplPara` 切换模板会重建当前对象变量空间。
3. 无法找到模板、非法属性和未闭合块明确抛出异常。`setLoop` 不再接受非集合的完整列表或非数组/对象记录。
4. 默认渲染保留空行和反斜杠，页面字节长度可能与旧版本不同；minify 改为保守处理，不再粗暴压缩脚本和样式。
5. `cache=true` / `[]` 会实际启用缓存，旧配置若无意缓存须明确改为 false。
6. 自定义标签代码生成语法仍属于可信 PHP 扩展，不替应用校验任意表达式。

## 验证

```sh
php tests/templates/template_regression.php
```

测试只使用临时目录、合成模板和 CLI 子进程，不修改业务数据。涵盖实例/克隆/序列化、重复配置加载、字面量、缺字段、嵌套结构、脚本开关、转义、文件路径、缓存维度、依赖和内容校验、可信标签接口、异常缓冲恢复、保守压缩及 8 个并发冷编译进程。

## 兼容核查结论

现有括号分支维持原结构与 PHP 7.4 宽松匹配；每个 switch 至多一个显式兜底，重复兜底报错，嵌套块互不影响。getBlock 继续返回块原文、普通分支映射和第三项兜底正文；只接收前两项的旧调用有效。实际示例核对另见 [示例应用](../applications/examples.md)。

执行 `php tests/templates/template_regression.php`；结构检查和编译成功不代替业务模板的查询及权限验证。
