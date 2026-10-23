# need规则、len与字段示例

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

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


## 明确调用示例

```javascript
console.assert(formCheckValue('0012', 'digital') === null);
console.assert(formCheckValue('-1.5', 'number') === null);
console.assert(formCheckValue('2024-02-29', 'date') === null);
console.assert(formCheckValue('2023-02-29', 'date') !== null);
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
