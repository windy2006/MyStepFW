# 接口实例：注册、原始调用和JSON编码

对应原手册“应用接口”。先离开HTTP调度观察myApi对象，再连接到应用route.php、全局API设置与逐接口策略。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$localApi = new myApi();
$localApi->regMethod('status', function ($id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) throw new InvalidArgumentException('编号必须为正整数');
    return ['id'=>$id,'title'=>'框架说明','enabled'=>true];
});
// 关联键不会自动绑定PHP命名参数，仍按位置传入第一个值。
$raw = $localApi->invoke('status', ['external_key'=>7]);
$json = $localApi->run('status', [7], 'json');
$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$rejected = false;
try {
    $localApi->invoke('not_registered', []);
} catch (BadMethodCallException $error) {
    $rejected = true;
}
$localApi->removeMethod('status');
echo json_encode([
    'raw'=>$raw,'decoded'=>$decoded,'unregistered_rejected'=>$rejected,
    'registered_after_remove'=>$localApi->hasMethod('status'),
], JSON_UNESCAPED_UNICODE);
```

## 预期结果

raw与decoded结构相同，id=7；未知接口被拒绝，移除后hasMethod=false。run返回JSON字符串，调用者才决定是否echo。

## 调用顺序与实际应用

实际HTTP接口在应用route.php以$api登记命名处理器；对象示例不是开放任意回调的HTTP入口。全局设置只有总开关、默认格式、正文上限和专属域名；逐接口有启用、允许方法、跨域和仅限后台。业务处理器仍负责字段、资源权限与实际请求方法，见[HTTP参数约定](api-arguments.md)和[后台设置](api-settings.md)。
