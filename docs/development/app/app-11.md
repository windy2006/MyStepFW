# 框架钩子

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 11. 框架钩子

```php
// 普通start钩子：回调接收当前控制器。
$mystep->setFunction('start', function ($controller) {
    $controller->setAddedContent('head', '<meta name="hello" content="enabled">');
});
```

管道型 page 钩子接收 HTML 并返回 HTML，签名与上面的普通钩子不同；参考[插件编写](../plugins/README.md)中的示例。先确认钩子在哪个调用位置执行，再决定业务放在哪里。

钩子适合明确的扩展点，不适合把授权、事务或必须执行的清理隐藏在“可能根本不会触发”的后置环节。检查回调异常，保留框架的错误处理，不吞掉失败冒充成功。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 显式数据管道与返回值

```php
// 自定义纯数据扩展；第三参显式提供初始管道值。
$mystep->setFunction('hello-count', function ($value) { return $value + 1; });
$result = $mystep->run('hello-count', false, 0);
assert($result === 1);
```

省略初始值时普通钩子默认传控制器；显式初始值时按管道传递。page钩子必须返回处理后的HTML，漏return会破坏后续页面；end按反向顺序处理，不能代替当前事务finally。钩子中抛出错误应交给现有异常处理，不吞掉后继续假装成功。

插件order决定初始化先后，初始化时的setFunction调用顺序才决定具体回调序列；这两个顺序不要混淆。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
