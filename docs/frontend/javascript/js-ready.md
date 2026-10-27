# 就绪队列

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 3. 配置就绪与初始化

| 函数 | 参数 | 返回值及约定 |
| --- | --- | --- |
| `checkNrun(func, params=[], timeout=15000, context=window, interval=100)` | func 为函数或 window 上的函数名；params 必须为数组；timeout 为最长等待毫秒数；context 为回调 this；轮询间隔至少 10ms | 返回取消函数；配置均就绪时执行一次，超时停止并写 console.warn；已经就绪时可同步执行 |
| `checkSetting(timer=500, callback=null, params=[])` | timer 为重试间隔；仅未就绪且 callback 非 null 时委托 checkNrun | 就绪返回 true；否则 false。就绪时不会替你执行 callback |
| `ms_func_reg(func)` | 登记页面初始化函数；非函数忽略 | undefined |
| `ms_func_run()` | 重新绑定本脚本的命名空间事件并执行 global.func 当前快照 | undefined；重复调用会重新执行业务回调，不是“只执行一次”队列 |

```js
ms_func_reg(function () {
    checkNrun(function (selector) {
        const form = document.querySelector(selector);
        if (form) console.log('已准备：', setting.app);
    }, ['#profile']);
});

function initializeSearch(selector) {
    if (!checkSetting(100, initializeSearch, [selector])) return;
    console.log(selector, language.search || '搜索');
}
initializeSearch('#search');
```

checkNrun 的取消函数可用于组件销毁；不靠 sleep 或固定延时替代配置就绪判断。回调异常由调用上下文处理，没有统一“错误回调”参数。

## 明确调用示例

```javascript
ms_func_reg(function () {
    checkSetting(100, function () { console.log(setting.app); });
});
sleep(10).then(function () { console.log('异步等待完成'); });
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
