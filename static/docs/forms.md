# 表单校验与配置控件

返回[前端文档入口](../README.md)。源码：[checkForm.js](../js/checkForm.js)、[myConfig::build](../../lib/myConfig.class.php)。

## 1. 加载和公共入口

checkForm.js 使用已有本地 jQuery，但不依赖 global.js 的原型扩展或 language 已加载。需要时通过框架登记，不引入其他表单校验库：

```php
$mystep->addJS(STATICS.'js/checkForm.js');
```

| 函数 | 参数 | 返回值与用途 |
| --- | --- | --- |
| `checkForm(form, myChecker)` | 原生表单或 jQuery 表单对象；myChecker 为可选同步前置函数，this 和第一个参数均为表单 | boolean；整体检查入口，推荐业务直接调用 |
| `highlightIt(element)` | 原生元素或 jQuery 对象 | undefined；添加 is-invalid，定位无效控件，输入／更改时清除标记 |
| `formCheckMessage(key, params=[])` | 语言条目名称和占位符参数数组 | string；language 中有该条目时使用语言包，否则使用内置中文提示 |
| `formCheckValue(value, rule)` | 已归一化字符串和规则 | 成功 null，失败为提示条目名；低层单值工具，不负责完整表单检查 |

formCheckValue 不处理 email_ 等可选标记归一化、len、原生约束、单选／复选分组和密码确认。需要验证页面表单时不要直接用它代替 checkForm。

## 2. need 格式规则

以下以 `checkForm(form)` 的完整校验流程为准。内置规则名不区分大小写；普通字段校验前去除首尾空白，密码保留原值。

| need 值 | 格式要求 | 示例 |
| --- | --- | --- |
| 不设置该属性 | 不做 need 格式检查；len、密码确认和原生约束仍参与 | `<input len="100">` |
| `""`，或只写 `need` | 必填自由文本，不限制内容格式；单选／复选按同名组检查至少一项选中 | `need=""` |
| `_` | 可留空的自由文本；不是“内容必须带下划线”，也不专门检查网址格式 | `https://www.baidu.com/s`、`/local/path`、空值 |
| `email` | 邮箱语法，检查本地部分及多级域名；支持加号、长顶级域名与国际化域名，不检查收件箱是否存在 | `user+tag@example.technology` |
| `url` | 完整 http／https／ftp 网址，须有主机；拒绝空白、反斜杠和无效端口；支持 localhost、IPv4、IPv6，不接受相对地址 | `https://example.com/a?x=1` |
| `digital` | 一个或多个 ASCII 数字 0～9；允许 0、前导零，不允许正负号或小数点 | `0`、`0012` |
| `number` | 有限实数，允许正负号、小数和科学计数；拒绝 Infinity、NaN 及溢出结果 | `-1.5`、`.5`、`1e3` |
| `alpha` | ASCII 字母、数字、下划线；不是“只能字母” | `item_1` |
| `word` | ASCII 字母、数字、下划线、空白，以及 `-`、`(`、`)`、`.`、`,`；不含中文 | `Hello world-1.0` |
| `name` | ASCII 字母、数字、下划线，中文汉字范围 U+3400～U+9FFF、U+F900～U+FAFF，以及半角空格、`-`、`(`、`)` | `名称A-1` |
| `date` | 四位年份，年月日用一致的 `-` 或 `/` 分隔；可用空格或 T 接时分及可选秒；校验真实日历与时间范围 | `2024-02-29`、`2024/2/29 12:30:00` |
| `time` | 24 小时时间，时分必填、秒可选；每段一至两位数字，小时 0～23，分秒 0～59 | `9:05`、`23:59:59` |
| `tel`、`fax` | 相同的通用号码规则：可在开头写 +，其余允许数字、括号、空格、点、短横线；数字总数 6～20，左右括号数量一致；不等同特定地区手机号的精确规则 | `+86 138-0013-8000` |
| 内置名之前／之后加 `_` | 该内置规则允许空值，非空值仍严格校验；两端各加一个也可 | `email_`、`_url`、`_digital_` |
| 其他字符串 | 原样作为不区分大小写的 JavaScript 正则；不会自动加首尾锚点，不会自动允许空值 | `^[A-Z_]+$` |
| `/表达式/flags` | 显式正则及 flags；不额外补 i，需要时自行指定 | `/^[A-Z]+$/` 只接受大写字母 |

注意：

- 纯内置格式规则均不接受空值；`email_` 等可选形式及 `_` 才允许留空，原生 required 仍优先要求填写。
- 自定义正则是否接受空值由表达式本身决定，例如 `^.*$` 可以接受空值，`^.+$` 不接受。自定义正则中的下划线保留字面含义。
- 不存在 `need="true"`、`need="false"` 的特殊开关，它们会进入自定义正则分支。myConfig 的 PHP 定义 `['text', false, '100']` 才会省略 need 属性；字符串 `'_'`、空串 `''` 和布尔 false 的用途不同。
- `len="100"` 为最大长度，`len="4!"` 为固定长度，`len="4-16"` 为范围；按历史 JavaScript UTF-16 规则计数，每个码元大于 U+00FF 时加计 1，因此一般汉字计 2、常见 emoji 计 4，不是 UTF-8 字节数。允许留空的规则在空值且没有 required 时跳过长度检查。
- 推荐显式调用 `checkForm(form)`；`formCheckValue(value, rule)` 是单值内部函数，不负责可选标记归一化、len、原生约束、密码确认和单选／复选组。

badReq 的“跳转网址”目前使用 `need="_" len="100"`：允许完整网址、站内相对路径或留空。若某字段只接受完整网址，使用 `need="url"`；若还允许留空，使用 `need="url_"`。

## 3. len、原生属性与空值

```html
<input name="title" need="" len="80">
<input name="code" need="alpha" len="4!">
<input name="nickname" need="_" len="4-16">
<input name="age" type="number" need="digital" min="0" max="150">
```

第三行允许留空；填写后须满足长度范围。数字规则本身不限定数值大小，需要时配合 min、max 或服务端业务约束。len 的中文计数是框架历史规则，和 HTML maxlength 的计数方式不同。

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

不需要业务事件逻辑时，也可以使用框架原有的 `onsubmit="return checkForm(this)"`。两种绑定方式选一种，不重复校验。Admin 已为 `.adm-shell form` 统一绑定校验，新后台模板无需再次绑定。

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

## 8. myConfig 如何生成规则

配置结构示例：

```php
return [
    'name' => '页面设置',
    'list' => [
        'title' => [
            'name' => '标题',
            'describe' => '必填，最多 80 个传统长度单位',
            'type' => ['text', '', '80'],
        ],
        'homepage' => [
            'name' => '完整主页网址',
            'describe' => '可留空；填写时使用完整 HTTP、HTTPS 或 FTP 网址',
            'type' => ['text', 'url_', '200'],
        ],
        'forward' => [
            'name' => '跳转地址',
            'describe' => '允许完整网址、站内相对路径或留空',
            'type' => ['text', '_', '100'],
        ],
        'remark' => [
            'name' => '自由文本',
            'describe' => '不生成 need 属性，仍保留长度检查',
            'type' => ['text', false, '100'],
        ],
    ],
];
```

text 类型的第二项原样变为 need；布尔 false 表示不生成该属性；第三项变为 len。textarea 的第三项是 rows，不会自动成为 len，需要按控件实际定义理解。配置字段名生成 `setting[...]` 结构，前端校验不会改变后端的数组层级。

```php
$config = new myConfig(PATH.'config.php');
$fields = $config->build(PATH.'config/chs.php');
// 各项 html 为控件，带 idx 的项为分组标题；交给自己的模板布局输出。
```

配置值由 myConfig 转义后放入控件。结构文件的名称、说明及规则来自受信代码，不把用户输入当作控件结构文件执行。

## 9. 与安全和服务端校验的关系

checkForm 只负责浏览器交互和基本格式，不验证管理员权限，也不替你保存配置。正常页面的 POST 令牌由[安全适配脚本](runtime.md#7-mssecurity会话安全适配)准备，服务端继续执行权限、CSRF、类型、范围和业务规则检查。

原生 `form.submit()` 会绕过提交事件和 checkForm；需要触发表单正常提交流程时使用提交按钮或 requestSubmit。校验失败时不要禁用保存按钮，成功并真正开始提交后再处理防重复操作。
