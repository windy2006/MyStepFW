# 命名规范

文件、目录和核心类的名称影响框架加载；普通变量及业务方法的名称主要用于保持代码清晰。以下分别说明必须遵守的对应关系和新代码的推荐写法。已有框架接口按原名称调用，覆写父类方法时保持签名一致。

## 应用标识与核心类

以`Hello`为例，四处名称及大小写保持一致：

```text
app/Hello/                    应用目录
app/Hello/info.php            返回数组中的 app 为 Hello
app/Hello/Hello.class.php     同名核心文件
class Hello extends myStep   核心类声明
```

应用标识使用以英文字母开头的字母、数字和下划线组合，避免保留字、空格、连字符及仅改变大小写的重复标识。`name`用于中文显示，`app`用于定位应用，两者分别填写。

核心类采用全局同名类；框架按`PATH.$info_app['app'].'.class.php'`加载它，不自动将核心名改写成命名空间。业务辅助声明可以放在应用自己的`lib.php`，使用`namespace app\Hello;`隔离。其他辅助文件由应用明确加载，不假定框架递归扫描应用目录。

```php
<?php
// app/Hello/lib.php
namespace app\Hello;

function articleTitle($article_id)
{
    return '文章 '.$article_id;
}
```

```php
$title = \app\Hello\articleTitle(12);
```

同名核心、模块和模板的完整组织方式见[应用规范](../../app/README.md)。

## 插件标识与主类

插件建议使用简短的小写英文标识，例如`hellonote`。目录、`info.php`中的`idx`、主类后缀、应用绑定值及辅助函数参数保持一致。

```text
plugin/hellonote/                    插件目录
idx = hellonote                      插件标识
plugin/hellonote/class.php            主类文件
plugin_hellonote                      主类名称
setPriority('hellonote')              使用同一标识
```

标识以字母开头，后续使用字母、数字和下划线；还须符合当前插件注册表`idx`列的长度。中文显示名称不能代替标识。主类实现`interface_plugin`，`check`、`install`和`uninstall`按接口原名声明，见[插件接口](../development/plugins/reference/interface-plugin.md)。

## 变量和数组键

PHP业务变量、参数和数据字段建议使用小写下划线，名称表达用途及单位：

| 用途 | 写法 | 说明 |
| --- | --- | --- |
| 单条记录 | `$article`、`$user_info` | 不用没有业务含义的`$data1`、`$tmp2`长期传递 |
| 多条记录 | `$article_list`、`$user_ids` | 通过复数或`_list`区分单条与集合 |
| 布尔值 | `$is_visible`、`$has_permission` | 表达能用true/false回答的问题 |
| 主键及关联键 | `$article_id`、`$web_id` | 与表单、数据库字段保持对应 |
| 数量和时间 | `$page_size`、`$expire_seconds` | 单位体现在名称或参数说明中 |
| 数组键 | `['article_id'=>12]` | 键的大小写与读取处完全一致 |

`$mystep`、`$ms_setting`、`$info_app`、`$router`、`$tpl_setting`、`$tpl_cache`、`$db`、`$cache`由框架承担既定职责，不用于存放同名业务值。局部函数使用参数传递所需数据；确需访问全局对象时明确声明`global`。

```php
function visibleArticleCount(array $article_list)
{
    $visible_count = 0;
    foreach ($article_list as $article) {
        if (!empty($article['visible'])) {
            $visible_count++;
        }
    }
    return $visible_count;
}
```

## 函数、方法与属性

新业务函数和方法推荐小驼峰，以动词说明操作：`getArticle()`、`saveArticle()`、`removeAttachment()`。判断方法使用`isVisible()`、`hasPermission()`等名称；避免用`check()`同时表示格式校验、登录判定和数据保存。

PHP对象属性可与现有框架保持小写下划线，例如`$page_title`。同一类内使用一致风格。框架已有`setPara()`、`getData_func()`、`remove_ul()`等接口名称，调用时按原声明书写；不要仅为统一风格改动入口名。

```php
class ArticleService
{
    private $page_size = 20;

    public function getPageSize()
    {
        return $this->page_size;
    }

    public function isValidId($article_id)
    {
        return is_int($article_id) && $article_id > 0;
    }
}
```

应用专用类放在本应用目录或已有`lib.php`中，并使用命名空间或明确的应用前缀避免冲突。上述业务类名只是声明示例，不表示框架会自动加载任意辅助类。

## 常量

常量使用大写加下划线；业务常量加应用前缀，例如`HELLO_PAGE_LIMIT`。引用框架的`ROOT`、`APP`、`PATH`、`ROOT_WEB`等常量，不重新定义同名业务常量。

```php
define('HELLO_PAGE_LIMIT', 20);
$module_file = PATH.'module/article.php'; // 当前应用的磁盘路径
$image_url = ROOT_WEB.'static/images/dummy.png'; // 浏览器资源地址
```

名称中含`_WEB`的路径与磁盘目录分开使用。配置中的目录保存网站根相对路径，再由框架结合`ROOT`解析，见[路径常量](../backend/runtime/constants.md)。

## 模块与模板

模块、模板及相关资源围绕同一功能命名；PHP、TPL和JS扩展名分别表明处理代码、展示和浏览器脚本。

```text
module/article/content.php              文章内容功能
template/default/article_content_list.tpl
template/default/article_content_input.tpl
asset/admin/article_content.js
```

推荐使用“分组_对象_用途”，例如`article_content_list.tpl`。具体模板由模块显式选择；模块与模板对应是组织规则，不意味着框架自动替所有应用推导模板名称。既有`art_`、`func_`、`set_`等前缀在所属应用中保持一致。

## 路由、API与模板变量

路由路径、处理器和模板文件名是不同标识，分别登记；路径不直接作为可执行PHP文件名。

```php
// app/Hello/route.php；处理器在本应用lib.php中定义。
$rule = [
    ['/Hello/article/[int]', 'app\\Hello\\showArticle'],
];
$api = [
    'articleStatus' => 'app\\Hello\\articleStatus',
];
```

API键与客户端调用名称完全对应；插件API范围按`plugin_插件标识`生成。模板变量使用清晰的小写下划线，赋值名与标签名保持一致；输出时按上下文转义。

```php
$tpl->assign('article_title', $title);
```

```html
<h1><!--article_title|html--></h1>
```

数据库表及字段推荐小写下划线，例如`hello_article`、`article_id`。表前缀从应用配置取得，SQL关键字与数据库方言的保留字避免用作自定义字段；值通过参数绑定传入。

## JavaScript名称

浏览器变量和函数推荐小驼峰，常量使用大写下划线；应用公开对象采用自己的名称，不覆盖`global`、`setting`、`language`、`root_web`和`msSecurity`。

```javascript
window.HelloPage = window.HelloPage || {};
HelloPage.pageSize = 20;
HelloPage.refreshArticles = function () {
    const articleList = document.getElementById('article-list');
    if (!articleList) return;
    articleList.classList.add('is-ready');
};
ms_func_reg(function () {
    HelloPage.refreshArticles();
});
```

HTML的`id`及CSS类可使用小写连字符，如`article-list`、`is-ready`；业务表单字段可用`article_id`以便与服务器参数对应。DOM标识在页面中保持唯一，公共JS函数按已有名称调用。初始化顺序见[浏览器前端](../frontend/README.md)。

## 注释与接口对应

方法注释写明功能、每个参数的名称和用途、返回值及必要异常。文档参数名应与代码一致；布尔返回需说明true/false分别表示什么。别名、路由字符串、模板标签及JavaScript调用中的名称也需要同步核对。

[开发规范](05-standards.md) · [执行流程](../backend/runtime/execution.md)
