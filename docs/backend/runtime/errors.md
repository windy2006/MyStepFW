# 错误处理

## 初始化阶段

统一入口先登记基础错误处理，完整应用start再按实际配置调整。myException负责PHP错误、未捕获异常、关闭阶段错误与日志回调；它不是业务返回结果的通用容器。应用模块不应每次重新init，否则会覆盖既有日志及错误策略。

| 配置项 | 作用 | 约束 |
| --- | --- | --- |
| log_type | error_reporting和框架记录范围 | 位掩码，与PHP级别对应 |
| callback_type | 触发错误展示回调的级别 | 不等于所有记录级别 |
| callback | 受信的错误响应回调 | 不能由请求参数指定任意PHP函数 |
| log_file/log_mode | 用户日志或系统日志 | 路径规范化及可写性按init实现核对 |
| display_details | 是否显示内部细节 | 正式环境关闭；内部路径、SQL及口令不面向用户 |
| exit_on_error | 错误后是否结束 | 调用者不能假定出错后一定继续执行 |

## 独立CLI试验中的初始化示例

只在独立试验脚本中使用，不覆盖正式应用已注册的处理器。先加载框架基础依赖，$directory为该试验专用临时目录：

```php
$_SERVER['DOCUMENT_ROOT'] = $directory;
myException::init([
    'log_file'=>$directory.'/example-error.log',
    'log_mode'=>0, 'log_type'=>E_ALL,
    'display_details'=>false, 'exit_on_error'=>false,
    'callback_type'=>0,
]);
```

init会修改PHP错误级别、display_errors以及全局处理器；这是进程状态变更，不能把该片段用于普通“读日志”。不手工调用shutdownCheck模拟整页退出。

## 业务输入错误与内部故障

可预期输入失败在业务边界转成稳定提示；存储或程序错误交给框架异常处理并记录，不向客户端回显完整异常。不要用catch(Throwable)后返回成功掩盖故障。

```php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    $message = '编号必须为正整数';
    // 页面模板对message进行HTML转义；API使用固定错误响应。
}
```

## API、下载与清理

API错误使用该协议的稳定状态和JSON格式，不能往JSON尾部输出HTML堆栈；下载开始前完成校验，响应头发出后不能再切换普通页面。临时文件、锁及事务放在当前操作的try/finally中释放，不能依赖全局end一定执行。

```php
$stream = fopen($directory.'/scratch.txt', 'w+b');
if ($stream === false) throw new RuntimeException('无法建立临时流');
try {
    if (fwrite($stream, 'example') !== 7) throw new RuntimeException('写入不完整');
} finally {
    fclose($stream);
}
```

## 日志排查

记录时间、级别、文件、行号及调用链；先定位最早的真实失败，后续headers already sent、JSON解析错误可能是连带现象。清空或下载日志必须通过管理员入口；空日志时后台禁用下载／清空。更新后在隔离环境复现并观察新增错误，不把删除日志当作修复。

[框架功能总览](../README.md) · [文档总览](../../README.md)
