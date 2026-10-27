# checkForm整体流程

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 完整框架页面在脚本前预置公开设置和语言；独立页面用checkNrun等待 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 3. len、原生属性与空值

```html
<input name="title" need="" len="80">
<input name="code" need="alpha" len="4!">
<input name="nickname" need="_" len="4-16">
<input name="age" type="number" need="digital" min="0" max="150">
```

第三行允许留空；填写后须满足长度范围。数字规则本身不限定数值大小，需要时配合 min、max 或服务端业务约束。len 的中文计数是框架计数规则，和 HTML maxlength 的计数方式不同。

执行过程为：

1. 如果传了 myChecker，先调用；返回 false 或 Promise 都阻止提交，不等待异步结果。
2. 收集表单关联的 INPUT／SELECT／TEXTAREA，跳过 disabled 控件、disabled fieldset 内控件以及提交／普通按钮。
3. 普通文字去首尾空白用于校验，密码保留原始内容；不会把归一化值写回输入框。
4. 处理可选标记、分组、len 与 need 格式，然后检查原生有效性。
5. 检查同表单内的确认密码；全部通过返回 true。

`novalidate` 只让 checkForm 跳过原生有效性检查，need／len 和密码确认仍执行；浏览器自身也会根据该属性决定是否弹出原生提示。只有 type="email" 而没有 need="email" 的控件，仍可能由其原生类型约束拒绝非法邮箱。

## 4. 一个完整表单

模板中的 profile_url 应由应用 PHP 的框架 URL 工具赋值，指向业务自己的 POST 接口：

```html
<form id="profile-form" method="post" action="<!--profile_url|html-->">
    <label>名称 <input name="name" need="name" len="40" required></label>
    <label>邮箱 <input name="email" need="email_" len="120"></label>
    <label>主页 <input name="homepage" need="url_" len="200"></label>
    <label>备注 <textarea name="remark" need="_" len="500" rows="4"></textarea></label>
    <button type="submit">保存</button>
</form>
```

在应用自己的 asset/function.js 中登记一次提交处理：

```js
ms_func_reg(function () {
    const form = $id('profile-form');
    if (!form || form.dataset.checkBound === '1') return;
    form.dataset.checkBound = '1';
    form.addEventListener('submit', function (event) {
        if (!checkForm(form)) event.preventDefault();
    });
});
```

不需要业务事件逻辑时，也可以使用框架 `onsubmit="return checkForm(this)"`。两种绑定方式选一种，不重复校验。Admin 已为 `.adm-shell form` 统一绑定校验，新后台模板无需再次绑定。

## 5. 单选、复选与多选下拉框

```html
<label><input type="radio" name="visibility" value="public" need="">公开</label>
<label><input type="radio" name="visibility" value="private">私有</label>

<label><input type="checkbox" name="topics[]" value="php" need="">PHP</label>
<label><input type="checkbox" name="topics[]" value="js">JavaScript</label>

<select name="category_ids[]" multiple need="digital">
    <option value="1">分类一</option>
    <option value="2">分类二</option>
</select>
```

同名 radio／checkbox 的 `need=""` 要求组内至少有一项选中。给某个 checkbox 单独加原生 required 时，那一项本身必须选中，与“组内任选”不同。多选 select 的已选值逐个检查；未选择且使用非可选格式规则时不通过。

## 6. 密码与确认密码

```html
<label>新密码
    <input type="password" name="password" id="profile_password" len="40">
</label>
<label>确认密码
    <input type="password" name="password_r" id="profile_password_r" len="40">
</label>
```

确认关系按 id 判断：确认框 id 为原密码框 id + `_r`，且必须属于同一个表单。name 仍由后端接收约定决定，不用 name 判断配对。

编辑密码时不设 need／required，允许两框同时为空；首次设置密码需必填时，在相应框上加 `need="" required`。只要确认框存在，找不到原密码框或两值不同都会失败。密码中的首尾空格保留，显示为明文后仍通过 data-ms-password 标记参与确认检查。成功验证不会删除确认框。

## 7. 自定义同步检查与提示

```js
function validatePublish(form) {
    const start = form.elements.namedItem('start_date');
    const end = form.elements.namedItem('end_date');
    if (start && end && start.value && end.value && start.value > end.value) {
        // 此比较仅适用于已经规范为 YYYY-MM-DD 的日期值。
        alert('结束日期不能早于开始日期。');
        highlightIt(end);
        return false;
    }
    return true;
}

// 在自己的 submit 处理器中调用：checkForm(form, validatePublish)。
console.log(formCheckMessage('checkform_lenth_limit1', ['80']));
```

myChecker 在普通字段格式检查之前执行，自定义逻辑应自行判断可用值。异步唯一性检查需要在业务提交处理器中明确 await，再决定提交，不把 async 函数直接当作同步 myChecker。

## 9. 与安全和服务端校验的关系

checkForm 只负责浏览器交互和基本格式，不验证管理员权限，也不替你保存配置。正常页面的 POST 令牌由[安全适配脚本](../../backend/runtime/variables.md)准备，服务端继续执行权限、CSRF、类型、范围和业务规则检查。

原生 `form.submit()` 会绕过提交事件和 checkForm；需要触发表单正常提交流程时使用提交按钮或 requestSubmit。校验失败时不要禁用保存按钮，成功并真正开始提交后再处理防重复操作。

## 明确调用示例

```javascript
const form = $id('profile-form');
if (form) form.addEventListener('submit', function (event) {
    if (!checkForm(form)) event.preventDefault();
});
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
