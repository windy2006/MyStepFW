# 执行顺序

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 7. 执行顺序

把实际加载顺序分开确认：路由汇总读取声明，匹配后选择应用并合并配置，`setPara()` 加载同名核心和 `lib.php`、运行 `preload()`、准备资源及启动流程；默认页面再按自己的入口装配 `global.php`、模块和模板。

显式规则、API、公共资源及默认分支会有差异。不能假设 `global.php` 是所有请求最早执行的文件，也不能依赖 `end` 在所有分支执行。对必需数据准备，选择覆盖相应请求的明确位置，并保持幂等。

出现“访问一个页面要求重新登录”时，应先检查应用上下文、会话及公共端点忽略规则，不通过全局取消登录检查来修补。详细时序以[执行流程](../../backend/runtime/execution.md)为准。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 本环节的文件与完整写法

### 7. 认识请求中可以使用的对象

| 名称 | 用途 |
| --- | --- |
| `$ms_setting` | 已合并的运行配置 |
| `$info_app` | 承接应用、路径段、查询参数及当前 route |
| `$mystep` | 当前控制器，可 render、show、注册标签和钩子 |
| `$tpl_setting` | 当前模板名称、路径、样式和编译目录 |
| `$tpl_cache` | 页面缓存参数；本示例禁用 |
| `$db`、`$cache` | 框架服务对象；存在不代表已自动连接数据库 |
| `PATH`、`ROOT_WEB` | 当前应用磁盘目录、网站 Web 根路径 |

这些变量在应用入口的包含作用域中可用；进入自己定义的函数时，要用 `global` 明确引入，或通过参数传递。带命名空间的代码引用框架类时使用 `\myTemplate`、`\myReq` 等完整类名。

## 普通请求的文件执行顺序

```text
根index.php → myStep::init → myStep::go
→ 全局配置／优先脚本／域名与路由准备
→ 选定Hello并合并其config.php → setPara
→ 加载Hello.class.php并建立当前对象 → 应用lib.php → preload
→ 资源/模板参数 → start：服务、会话、认证、插件与相关钩子（按条件）
→ global.php → index.php → module/index.php
→ 子模板内容 → 主模板main → show → end
```

这是普通未被显式规则接管的页面链。规则处理器、API、文件、跳转、拒绝响应会在自己的分支结束；不能将本图理解为每个请求都读取全部文件。进入应用index.php时服务已准备，模块不要再调用init/start。

`$tpl_sub`由入口按当前模块建立，再由模块assign；`$content`是子模板渲染结果；主模板`<!--main-->`承接它。常见重复输出来自入口自行show后，框架外层又show。

## 用Hello记录实际执行顺序

先按[第一个应用](first-app.md)准备可访问的Hello页面及对应模板。以下代码只加入该测试应用，不修改框架核心。日志使用`CACHE.'hello-flow.log'`；PHP运行账号需要有写权限。

### 在核心中登记生命周期记录

将下面的preload合并到`app/Hello/Hello.class.php`。已有preload时合并方法内容，不重复声明同名方法或类。回调中不输出调试文字。

```php
<?php
class Hello extends myStep
{
    public function preload()
    {
        error_log("preload\n", 3, CACHE.'hello-flow.log');
        $this->setFunction('start', static function ($core) {
            error_log("start\n", 3, CACHE.'hello-flow.log');
        });
        $this->setFunction('page', static function ($html) {
            error_log("page\n", 3, CACHE.'hello-flow.log');
            return $html;
        });
        $this->setFunction('end', static function ($core) {
            error_log("end\n", 3, CACHE.'hello-flow.log');
        });
    }
}
```

`start/end`回调接收控制器；`page`回调接收渲染正文，必须返回要继续输出的正文。preload只登记这些回调，不手动调用run/start/end。

### 在已有页面脚本开头记录

在相应文件的PHP开始标记之后加入一行，不替换文件原有页面逻辑：

```php
// app/Hello/global.php
error_log("global\n", 3, CACHE.'hello-flow.log');
```

```php
// app/Hello/index.php
error_log("index\n", 3, CACHE.'hello-flow.log');
```

```php
// app/Hello/module/index.php
error_log("module\n", 3, CACHE.'hello-flow.log');
```

测试环境中清空该跟踪文件后访问`/Hello/`；确保本次没有页面缓存命中、没有显式规则接管该首页。若原始页面按第一个应用的入口组织，日志依次为：

```text
preload
start
global
index
module
page
end
```

页面显示由入口创建主／子模板、模块赋值、子模板render和外层show共同完成；上述记录不会替应用建立模板或分派模块。

### 对照命名路由与API

按[应用路由](app-06.md)登记独立处理器，在对应函数开头写入同一跟踪文件：

```php
// greet处理器内
error_log("handler\n", 3, CACHE.'hello-flow.log');
```

处理器没有主动包含global/index、没有调用getModule，并按示例show页面后正常返回时：

```text
preload → start → handler → page → end
```

在ping API函数开头记录`api`；函数返回业务数组，由API出口编码输出：

```php
// ping处理器内
error_log("api\n", 3, CACHE.'hello-flow.log');
```

```text
preload → start → api
```

该API分支exit，不经过普通page/end；如果业务代码主动跳转、文件输出、抛出异常或触发其他提前响应，记录以实际出口为准。需要释放资源的处理器自己使用try/finally，不能只登记end。

### 缓存和插件对记录的影响

- `checkCache()`命中页面缓存时，缓存输出点之后的模块查询、模板渲染和page钩子不重跑；该方法调用end结束。
- 插件index在宿主父类start中按条件初始化，可在start钩子之前登记自己的回调。插件order影响初始化顺序，end钩子按登记顺序的逆序执行。
- 业务模块才登记的start钩子不会补跑本次已结束的start阶段。
- 模板编译缓存只复用编译产物，本次渲染及相应page钩子仍执行；它与页面HTML缓存不同。
- 一张页面可能触发多个独立HTTP请求。图片、脚本和AJAX有各自的请求链；不要把同一个日志中多个请求的条目误认为一条链重复执行。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
