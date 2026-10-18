# 公共 JavaScript 与表单检查

整理日期：2026-10-18。目标运行环境：PHP 7.4。下文中的检查及验证记录保留原日期和适用边界。

适用文件：`static/js/global.js`、`static/js/checkForm.js`。保持现有 jQuery、框架三种路由模式及 PHP 7.4 环境。保留原作者头信息，补充功能、参数、返回值和兼容性约定。

本篇保留实现维护和兼容性记录；面向开发使用的变量、函数参数、返回值和完整示例统一参见 [static 前端开发文档](../../static/README.md)，need 规则以其中的表单文档为准。

## global.js

| 项目 | 已修复或改进 |
| --- | --- |
| 全局作用域 | MD5 中的 olda/oldb/oldc/oldd 改为局部变量，缺少 root_web 时安全回退 |
| 字符串扩展 | 不覆盖原生 trim；printf 一次解析 %1/%10，替换值中的 `$&`、`%2` 等按原文保留 |
| 原型扩展 | 用不可枚举属性声明，避免污染 for-in 遍历；数组自身 append 不再无限增长，并返回当前数组 |
| 日期/金额 | 季度计算为整数，重复日期标记及毫秒补位正确；金额小数位归一化，非法金额、极大金额和特殊分隔符有明确结果 |
| MD5/Base64 | MD5 使用 UTF-8 字节，与 PHP/标准实现一致；Base64 支持 Unicode、`+`/`/`，不再调用废弃的 escape/unescape |
| 普通随机值 | 整数闭区间等概率，数值字符串不再发生文本拼接；非法范围及时失败；仅汉字/仅额外字符也能生成完整次数 |
| 水印 | 缺省参数不报错，版权文本转义，干扰标签在各文本片段内闭合 |
| 复制 | 普通英文单词按文字复制，避免当作选择器或 HTML；复制后恢复焦点和选区；增加 copyAsync，Clipboard API 失败时回退同步方案 |
| 页面遮罩 | 去掉 cssText 插件依赖，显式显示/隐藏避免动画队列与切换竞态；恢复原始 overflow；提示以纯文本显示 |
| 配置就绪 | 去掉 caller/arguments.callee，严格模式可用；回调参数独立，支持取消及有限超时，不再与锚点动画共用 global.timer |
| 锚点/历史 | 不把 ID 拼进 CSS 选择器，支持特殊字符；动画状态独立；setLocation 不重复注册无功能的监听器 |
| 页面事件 | 修复 beforeunload 的变量暂时性死区及语言包判断；初始化重复运行不叠加监听；被取消的提交保留未保存提示，不移除其他脚本监听器 |
| Ajax | 由 jQuery 自行判断跨域，恢复同源 X-Requested-With；自动凭据只用于当前来源及框架信任来源，显式请求设置仍优先 |
| 路由 | 补齐入口脚本的单段查询路由及原始 index.php 路径归一化；查询 GET 表单尊重已取消事件、submitter 参数、目标窗口及字段特殊名称 |

### 兼容性与使用方式

`String.blen()` 和表单 `len` 保留传统双字节计算：U+0100 以上每个 UTF-16 码元按 2 计，汉字一般为 2，emoji 通常为 4。该结果不是 UTF-8 字节数。

```js
// checkSetting 现在是明确的 boolean 检查；需要重试时显式给出函数和参数。
function initEditor(selector) {
    if (!checkSetting(100, initEditor, [selector])) return;
    // 在这里使用 setting 和 language。
}
const cancel = checkNrun(initEditor, ['textarea'], 15000);
// cancel(); // 页面销毁或不再需要时取消。

// sleep 已改为非阻塞等待；项目活动代码中未发现依赖原同步忙循环的调用。
await sleep(100);

copy('hello');                 // boolean；保留同步接口
const copied = await copyAsync('#source'); // Promise<boolean>，建议在用户点击中调用
loadingShow('正在处理', true);  // 明确显示
loadingShow(null, false);      // 明确隐藏；省略第二参数继续采用旧版切换行为

base64('中文', false);          // 明确编码
base64('5Lit5paH', true);      // 明确解码
```

Base64 的自动模式只为兼容，纯字母字符串无法可靠判定编码方向，请明确传入 mode。MD5 的 ASCII 输出不变，非 ASCII 文本现在生成标准 UTF-8 MD5；仅适用于兼容标识，认证继续使用框架安全机制。`watermark`/`info` 保留处理可信 HTML 的约定，不能作为 HTML 安全过滤器。

现有调用已同步调整：CMS 编辑器上传初始化、框架上传演示、TinyMCE 演示和 setURL 内部的配置就绪检查。演示用的历史压缩输入样本不属于活动公共脚本，不替换为新代码。

## checkForm.js

修复定长校验的数字/字符串比较和 the_length 全局泄漏；校验不再依赖 global.js 的原型方法或已加载的语言包。缺省提示为中文，语言包可覆盖。

```html
<input need="" />                     <!-- 必填 -->
<input need="_" len="100" />           <!-- 可留空的自由文本，非空时仍检查长度 -->
<input need="email_" />                <!-- 允许空值的邮箱 -->
<input len="20" />                     <!-- 最大长度 -->
<input len="4!" />                     <!-- 固定长度 -->
<input len="4-16" />                   <!-- 长度范围 -->
<input need="^\D+$" />               <!-- 保留大小写转义的正则 -->
<input need="/^[A-Z]+$/" />             <!-- 可显式指定正则 flags -->
<input id="password" type="password" />
<input id="password_r" type="password" />
```

保留 email/url/digital/number/alpha/word/name/date/time/tel/fax 规则；alpha 沿用字母、数字、下划线的旧含义。单独的 `need="_"` 兼容 myConfig 的可选自由文本定义，不要求内容包含下划线，适用于 badReq 跳转网址等配置；非空值仍检查 len，原生 required 仍生效。内置规则前/后单个下划线表示该规则允许空值，不修改自定义正则中的 `_`、`\D`、`\S`；需要匹配字面下划线时可显式写 `need="^_$"`。错误的正则或长度配置阻止提交。

### need 格式规则

完整的 need 对照表、参数说明和表单示例统一维护在 [static 前端表单文档](../../static/docs/forms.md#2-need-格式规则)。单独的 `_` 是可留空自由文本；内置规则前后 `_` 是对应格式的可选标记；自定义正则保留其原义。

具体改进：

- 邮箱不限制顶级域名为 2—4 位；网址通过 URL 解析检查协议、端口等，支持 localhost、IPv4、IPv6。
- 日期校验实际日历和可选时间，覆盖闰年、月份天数、时分秒边界；时间与数字规则拒绝越界和非有限值。
- 跳过禁用控件和按钮，支持禁用 fieldset、单选/复选组、多选下拉框和通过 form 属性关联的外部控件；提前建立 ID/组索引。
- 程序调用 checkForm 也会检查原生 required/min/max/pattern 等约束。form 的 novalidate 只关闭原生约束，need/len 仍检查。
- 保留密码的原始空白；编辑时允许保持空密码。确认控件在同一个表单内查找，缺少原控件时返回失败，显示为明文后仍检查。
- 验证成功也不删除确认控件，不破坏重试、Ajax 或 CMS 注册/资料修改的后端校验。
- highlightIt 兼容数字等不可选中文本的输入，并在输入或更改后清除无效标记。

`myChecker` 仍为同步前置回调，参数和 this 都是目标表单；返回 false 或 Promise 会阻止提交，不会在异步验证尚未完成时放行。客户端不替代服务端校验。CMS 管理员/用户管理同步检查已提供的确认值，并在写库前剔除 password_r/password_c；旧页面不发送确认字段时继续兼容，字符串密码 `0` 不再被当作空值。

## 验证方式

```sh
node tests/javascript/javascript_regression.js
node tests/javascript/javascript_regression.js --minified
node tests/applications/cms_link_browser.js
php tests/security/password_confirmation_regression.php
python3 tests/javascript/javascript_browser_server.py
```

最后一条命令启动只监听 127.0.0.1 的隔离测试服务器，输出整合/独立加载两个地址。浏览器打开后自动显示并保存 JSON 结果，结束使用 Ctrl+C。测试页使用真实 jQuery、浏览器表单有效性、事件、焦点、选区及密码显示切换；剪贴板写入调用使用替身，不更改系统剪贴板。

本次实际检查包括：99 项 Node 逻辑检查及压缩后相同 99 项执行对比，真实浏览器整合 54 项与独立 43 项，12 项真实 PHP 后台模块的密码确认场景。PHP 密码工作流只使用临时表，不修改正式账户。CMS 链接专项及现有框架回归另行运行，详见 tests/README.md。
