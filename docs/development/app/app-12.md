# 链接设定

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 12. 链接设定

```php
$url = myStep::setURL('Hello/detail');
$query = http_build_query(['id' => 7, 'keyword' => 'A&B'], '', '&', PHP_QUERY_RFC3986);
$url .= (strpos($url, '?') === false ? '?' : '&').$query;
$tpl_sub->assign('detail_url', $url);
```

```html
<a href="<!--detail_url|html-->">查看详情</a>
```

路径先通过框架生成，查询串再用正确连接符追加，模板属性最终 HTML 转义。不能写成 `detail/add&id=7`，也不能为了添加参数在路径后任意补 `/&`。不要先把整段地址 URL 编码，再交给链接生成器。

验证 rewrite、path_info、query_string 三种模式和子目录部署。具体业务模块可能还有动作路径，按该模块受控声明生成，不根据 URL 中出现 `add` 就假定存在同名 PHP 文件。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## URL在PHP、HTML和查询参数之间的完整示例

```php
$url = myStep::setURL('Hello/detail');
$url .= (strpos($url, '?') === false ? '?' : '&')
    .http_build_query(['id'=>7,'keyword'=>'A&B'], '', '&', PHP_QUERY_RFC3986);
$tpl_sub->assign('detail_url', $url);
```

```html
<a href="<!--detail_url|html-->">详情</a>
```

| 模式／部署 | 预期地址示意 |
| --- | --- |
| rewrite，站点根 | `/Hello/detail?id=7&keyword=A%26B` |
| path_info，/sub/ | `/sub/index.php/Hello/detail?id=7&keyword=A%26B` |
| query_string，/sub/ | `/sub/index.php?/Hello/detail&id=7&keyword=A%26B` |

先URL编码参数，输出到HTML属性时再HTML转义；两步不能互换，也不要预先把&amp;传回setURL。不要在路径尾直接拼`&web_id=1`。调用接口的地址使用myApi::httpURL，前端动态地址另见[路由地址、参数和批量链接](../../frontend/javascript/js-urls.md)。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
