# 随机值、编码、水印和调试

修订日期：2026-10-23。浏览器脚本用法。

## 本篇目标与调用场景

本篇所有JavaScript示例在浏览器运行。先加载所需本地脚本，不把PHP变量或磁盘路径直接当成浏览器变量。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `root_web/global.root` | 网站Web根前缀，用于公开资源 |
| `setting/language` | 异步装入的公开设置和语言，须等待就绪 |
| `document/window` | 当前浏览器页面上下文，不是PHP的全局变量 |

## 7. 随机数、随机文本和编码

| 函数 | 参数与功能 | 返回值／限制 |
| --- | --- | --- |
| `rndNum(min, max)` | 无参数为 Math.random；一个参数表示 0～该上限；两个参数为闭区间，可交换上下界 | number；整数范围必须有效且在安全整数范围内，否则 RangeError |
| `rndStr(len, t_lst='', c_lst=[])` | len 为选取次数，向下取整，范围 0～1000000；四位开关依次为大写、小写、数字、汉字；默认有效开关 0110；c_lst 为额外字符串或数组 | string；额外单元可含多个字符，所以返回字符串长度不保证等于 len；候选池为空时空串 |
| `isMobile()` | 根据 UA 和桌面 UA 的触控 iPad 特征检测 | `{Android, BlackBerry, iOS, Windows, any}`，各值 boolean |
| `base64(code, mode)` | true 解码，false 编码；省略时尝试按合法 UTF-8 Base64 解码，失败则编码 | string；显式解码非法数据会抛异常，不是加密 |
| `md5(str)` | 对 UTF-8 文本计算兼容 MD5 | 32 位小写十六进制 string |

```js
const dice = rndNum(1, 6);            // 1～6
const label = rndStr(8, '1110');      // 大小写字母和数字
const hexLabel = rndStr(8, '0000', '0123456789abcdef'.split(''));
const encoded = base64('中文', false); // 5Lit5paH
console.log(base64(encoded, true));    // 中文
console.log(md5('abc'));               // 900150983cd24fb0d6963f7d28e17f72
console.log(dice, label, hexLabel, isMobile().any);
```

rndStr 的额外字符串按一个候选单元处理；传 `'ABC'` 相当于一个可整体选中的单元，需要单字符候选时使用 split('')。随机值基于 Math.random，不用于密码、会话或 CSRF 令牌；MD5 不用于管理员认证。


## 8. 水印和可信 HTML 提示

| 函数 | 参数与功能 | 返回值 |
| --- | --- | --- |
| `watermark(obj, rate=5, copyright='WaterMark Maker, Coded by Windy2000', char_c='', jam_tag=false)` | obj 为 HTML 字符串或具有 innerHTML 的元素；rate 越大随机干扰越少，10 不插入随机干扰字；copyright 在换行后作为版权提示转义；char_c 为逗号分隔的额外单元；jam_tag 是否插入成对干扰 span | 生成的 HTML string；obj 是元素时也会改写该元素 |
| `info(msg)` | 创建／复用页面提示区；msg 为可信 HTML，null／undefined 隐藏 | undefined |

```js
const text = $id('trusted-article');
if (text) watermark(text, 8, '本站内容', '', false);
info('<strong>提示：</strong>请检查填写内容。');
// info(null); // 隐藏提示区
```

watermark 的 `.watermark` span 依赖 global.css 隐藏干扰文字；rate=10 仍可能生成换行后的版权片段。watermark 虽删除 script/style 块，也不是通用 HTML 净化器；info 使用 html 写入。两者只接受受信内容，不把用户提交的 HTML 直接传入。


## 9. 调试与错误报告

| 函数 | 功能和参数 | 返回值 |
| --- | --- | --- |
| `debug(para, mode)` | 把值写入页面 #debug 文本区；mode===true 时把新内容放在旧内容前，其他情况覆盖 | undefined |
| `c(...values)` | 逐项 console.log | undefined |
| `a(...values)` | 逐项 alert | undefined |
| `checkObj(obj, func_show=alert)` | 遍历可枚举属性，输出 `键 : 值`；可传自己的输出函数 | undefined |
| `reportError(msg, url, line, column, error)` | 调试模式或 setting 尚未定义时报告；优先 global.err_report_func，否则 alert | false，保留浏览器默认错误处理 |

```js
debug('开始处理');
debug({step: 2}, true);
c('应用状态', {ready: true});
checkObj({page: 1, size: 20}, function (item) { console.log(item); });

global.err_report_func = function (text, verbose, error) {
    console.error(text, error);
};
// a('本地调试提示');
// reportError('示例错误', location.href, 1, 1, new Error('示例'));
```

这些函数输出到浏览器或页面，不自动写入服务器 error.log。调试值不要包含密码、会话令牌或其他不应展示的数据。


## 10. 自动行为与调用边界

- global.js 自己注册 jQuery Ajax 预过滤和离页提示；ms_func_run 注册锚点、表单离页标记等命名空间事件。
- `global.alert_leave = true` 可启用未保存提示；正常提交先清除该脚本的标记，如果提交后来被取消，会恢复原值。
- DOM 就绪时为密码框添加显示／隐藏控件，并标记 data-ms-password；checkForm 据此在显示明文后继续进行密码确认。
- 配置加载失败时，checkNrun 超时停止；业务应允许用户重试，不能把“定时器已结束”当作已成功初始化。
- 普通函数、异步 Promise 和返回取消函数的工具应按各自约定使用，不把 Promise 当作同步布尔值。

## 明确调用示例

```javascript
console.log(rndNum(1, 6));
const encoded = base64('中文', false);
console.log(base64(encoded, true));
// rndNum/rndStr、MD5不是认证令牌或密码散列。
```

## 验证与进一步阅读

在浏览器开发工具查看实际值及网络请求；先验证依赖和就绪状态，再检查返回值或Promise。DOM不存在、格式不合法及网络失败均应有反馈。服务端输入、权限和CSRF另外验证，客户端校验通过不等于业务授权。

[返回文档总览](../../README.md)
