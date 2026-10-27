# checkForm.js：函数参数与示例

本页按当前脚本公开声明列出参数、返回和明确调用。先满足本地脚本依赖和DOM条件；省略的业务元素需由页面提供。

## highlightIt

```text
highlightIt(element);
```

标记并定位无效控件，支持不支持 select 的输入类型和被隐藏的编辑器控件。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `element` | Element\|jQuery\|null | 待定位元素；不存在时不执行。 |

**返回值**：{void}

### 调用示例

```javascript
highlightIt(document.getElementById('email'));
```

## formCheckMessage

```text
formCheckMessage(key, params=[]);
```

读取语言包并替换 %1 等参数；语言包未加载时使用内置中文提示。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `key` | string | 语言条目标识。 |
| `[params=[]]` | Array | 字符串替换参数。 |

**返回值**：{string} 可直接显示的纯文本提示。

### 调用示例

```javascript
console.log(formCheckMessage('checkform_noempty'));
```

## formCheckValue

```text
formCheckValue(value, rule);
```

校验单个已归一化的值，不操作 DOM，不修改原值。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `value` | string | 文字值；密码由调用方保留原始空白。 |
| `rule` | string | 内置规则名、自由文本标记 _，或原样保留的自定义正则。 |

**返回值**：{string|null} 失败时返回语言条目标识，成功返回 null。

### 调用示例

```javascript
console.assert(formCheckValue('7', 'digital') === null);
```

## checkForm

```text
checkForm(form, myChecker);
```

校验表单：自定义前置校验、need/len、原生有效性和密码确认，均通过才允许提交。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `form` | HTMLFormElement\|jQuery | 目标表单，包含通过 form 属性关联的外部控件。 |
| `[myChecker]` | Function | 同步前置校验，接收表单且 this 为表单；返回 false 或 Promise 阻止提交（不自动等待异步校验）。 |

**返回值**：{boolean} 是否通过。禁用控件/非输入按钮不校验；不移除密码确认字段。

### 调用示例

```javascript
const form = document.getElementById('profile-form'); if (form) console.log(checkForm(form));
```

[返回JavaScript入口](frontend.md)
