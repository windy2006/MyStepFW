# 钩子实例

通过可观察的AB/BA结果说明desc参数，再用空字符串正文验证页面管道不会意外改成控制器对象。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$controller = new myController(); // 仅演示通用钩子，不启动第二个框架请求。
$order = [];
$controller->setFunction('sample', function ($host) use (&$order) { $order[] = 'A'; });
$controller->setFunction('sample', function ($host) use (&$order) { $order[] = 'B'; });
$controller->run('sample');
$forward = implode('', $order);
$order = [];
$controller->run('sample', true);
$backward = implode('', $order);
$controller->setFunction('page', function ($html) { return $html.'A'; });
$controller->setFunction('page', function ($html) { return $html.'B'; });
$page = $controller->run('page', false, '');
echo json_encode(['forward'=>$forward,'backward'=>$backward,'page'=>$page]);
```

## 预期结果

forward=AB，backward=BA，page=AB。省略run第三参数时回调接收控制器；显式第三参数进入正文管道。

## 调用顺序与实际应用

实际应用使用已有$mystep->setFunction而不是再new控制器。本例的new仅隔离演示。page回调返回处理后的HTML；start/end是否运行由真实请求分支决定，文件、API、重定向不应把end当作finally。模板的page_start/page_end是内容插槽，和程序钩子的名字相似但不是同一机制。
