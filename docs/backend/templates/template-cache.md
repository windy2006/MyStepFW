# 模板编译、页面缓存和vary

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

myTemplate是PHP后端模板机制，不是JavaScript模板库。模板数据、编译代码和最终HTML分别有自己的作用域和缓存。

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


## 主要方法：参数、返回和失败约定

### myTemplate::setCacheMode

功能说明：启用或关闭页面缓存；登录态/语言等差异必须由调用方通过 vary 或独立路径区分


缓存结构：name默认当前模板名，path默认模板目录/cache/风格，ext默认html，expire默认300秒；expire非正不复用页面，vary用于区分用户/语言等上下文。

```text
public function setCacheMode($cache);
```

| 参数说明 |
| --- |
| bool\|array\|null $cache false/null关闭页面缓存，true使用默认选项；数组可设置name/path/ext/expire/vary |

**返回**：$this 返回当前实例，支持链式调用。

**异常**：InvalidArgumentException 参数或扩展名非法

### myTemplate::removeCache

功能说明：清除当前模板编译版本，可选择同时删除当前页面缓存

```text
public function removeCache($html = false);
```

| 参数说明 |
| --- |
| bool $html true 连同当前 vary 对应的静态页面一起删除，默认保持历史行为（默认：false） |

**返回**：$this 返回当前实例，支持链式调用。

### myTemplate::checkCache

功能说明：检测静态页面是否有效，核对引擎身份、依赖、页面摘要及实际当前时间

参数说明：无显式参数。

```text
public function checkCache();
```

**返回**：bool 空串及字符串 0 也是有效缓存；无元数据的旧页面会重新生成

### myTemplate::getCache

功能说明：读取有效的静态页面

参数说明：无显式参数。

```text
public function getCache();
```

**返回**：string 未命中返回空串；需区分空页面时先调用 checkCache

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
