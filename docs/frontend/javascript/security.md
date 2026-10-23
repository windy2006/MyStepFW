# security.js：CSRF表单与AJAX整合

修订日期：2026-10-23。JavaScript前端脚本；源文件为`static/js/security.js`。

## 加载与变量

完整页面由后端装饰器输出`window.msSecurity`，security.js读取该对象。核心字段包括token、origins、admin、adminContext和root；没有该配置时脚本直接返回，不自行创建登录状态。token属于当前会话，不能硬编码进公开资源或日志。

## 表单和请求行为

1. 为允许来源的POST表单填入`_ms_csrf`；已有字段更新，不重复创建。
2. 为允许目标的非GET/HEAD/OPTIONS请求，通过本地jQuery预过滤器、XHR或fetch包装加入X-CSRF-Token。
3. 对已知旧式状态修改链接转POST，普通链接保持导航；后端仍做方法与权限检查。
4. 退出表单声明目标会话令牌入口时先刷新令牌，再原生提交；刷新失败保持页面并提示，防止旧标签页拿失效令牌退出。

## fetch明确示例

假定页面已登录且包含名为profile-form的表单，form.action由后端生成当前业务POST地址：

```javascript
const form = document.getElementById('profile-form');
if (form) form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!checkForm(form)) return;
    fetch(form.action, {
        method: 'POST', credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
        body: new FormData(form)
    }).then(function (response) {
        if (!response.ok) throw new Error('保存失败：' + response.status);
        return response.json();
    }).then(function (data) { console.log(data); }).catch(console.error);
});
```

本例只声明交互，实际响应结构由业务定义。包装器只为允许目标增加请求头；不能通过它绕过服务器CSRF，不能给任意第三方地址发送本站令牌。登录过期返回HTML时，业务应提示重新登录而不是当JSON成功。

## 与API跨域的关系

跨域许可由具体API的开关和浏览器规则决定；跨域开启不创建身份、Cookie或CSRF令牌。跨域凭据请求需要对应凭据模式并满足Cookie策略。前端不能从另一个应用会话借令牌，服务器仍按当前目标上下文校验。

[返回文档总览](../../README.md)
