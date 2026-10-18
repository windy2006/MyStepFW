# 公共函数与使用示例

返回[前端文档入口](../README.md)。以下接口定义在 [global.js](../js/global.js)，需要先加载本地 jQuery。函数名和参数顺序按当前源码；除特别说明外，没有业务权限或服务端写入功能。

## 1. DOM 与类型判断

| 函数 | 参数及功能 | 返回值 |
| --- | --- | --- |
| `getPath(lvl=0)` | 取得当前协议、主机、端口和前 lvl 层目录；lvl 向下取整，负数按 0 | string；0 为来源地址，不含查询串 |
| `$id(id)` | 按元素 id 查找 | Element 或 null |
| `$name(name, idx)` | 按 name 查找；idx 可省略、传数字／数字字符串、first、last | 不传 idx 返回 NodeList；传 idx 返回元素或 undefined |
| `$tag(name, theOLE=document)` | 在指定 Document／Element 内按标签名查找 | HTMLCollection |
| `$class(name, context=document)` | 在指定 Document／Element 内按 CSS 类名查找 | HTMLCollection |
| `isArray(para)` | Array.isArray 的兼容入口，支持跨窗口数组 | boolean |

```js
const panel = $id('profile');
if (panel) {
    const firstInput = $tag('input', panel)[0];
    const hints = $class('field-help', panel);
    console.log(firstInput, hints.length);
}
const selected = $name('category', 'first');
console.log(selected, isArray([1, 2]));
console.log(getPath(0)); // 例如 https://example.test:8443
```

$id 返回原生元素；需要 jQuery 方法时再用 `$(element)` 包装。$name 不会自动把带方括号的 PHP 字段名解析成对象。

## 2. 字符串、日期、数组和金额扩展

这些属性在原型上以不可枚举方式定义，调用于值／实例，不是 `String.blen()` 等静态函数。

| 方法 | 功能及参数 | 返回值／边界 |
| --- | --- | --- |
| `text.blen()` | 传统双字节长度：U+0100 以上每个 UTF-16 码元加计 1 | number；汉字一般 2，常见 emoji 4，不是 UTF-8 字节数 |
| `text.printf(...values)` | 替换 `%1`、`%2` 等占位符；缺少参数的占位符保留 | string；单次替换，不把参数里的 `$&`、`%2` 再解释 |
| `text.trim()` | 仅在缺少原生 trim 时补充，去除首尾空白 | string；已有原生实现不覆盖 |
| `date.format(format='yyyy-MM-dd hh:mm:ss')` | 按本地时间格式化；支持 y、M、d、h、m、s、q、S 的重复标记 | string；无效日期返回空串；q 为季度，S 为毫秒 |
| `array.append(items)` | items 是数组则追加其内容，否则追加单值 | 返回原数组并修改它；允许把自身追加一次 |
| `number.formatMoney(places=2, symbol='$', thousand=',', decimal='.')` | 金额、小数位、货币符号、千分符、小数分隔符 | string；小数位取绝对值后向下取整，限制 0～20；非有限金额按 0 |

```js
console.log('中文A'.blen());                         // 5
console.log('你好，%1！订单 %2'.printf('张三', 12));  // 你好，张三！订单 12
console.log(new Date(2026, 9, 18, 9, 5, 6).format()); // 2026-10-18 09:05:06
console.log(new Date(2026, 9, 18).format('yyyy年M月d日 q季度'));
const items = [1];
items.append([2, 3]).append(4);
console.log(items);                                // [1, 2, 3, 4]
console.log((1234.5).formatMoney(2, '¥'));           // ¥1,234.50
```

日期没有引号转义格式片段的机制；自定义格式中的 y、M 等同样会被当作标记。不要把 formatMoney 的显示结果当作金融计算精度保证。

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

## 4. 路由与查询参数

| 函数 | 功能及参数 | 返回值 |
| --- | --- | --- |
| `routeURL(url, prefix=global.root_fix)` | 把应用／模块相对路径与 prefix 合成当前路由地址；结合 setting.url_prefix、url_fix 等归一化 | string；依赖 setting 已就绪。完整协议地址、以 `/` 开头的地址和 `#` 锚点原样保留 |
| `queryURL(url, params={})` | 只合并查询参数并保留锚点、index.php 的首段路由；null／undefined 删除参数；数组生成同名重复参数 | string；使用 URLSearchParams 编码，空格通常为 + |
| `setURL(prefix=global.root_fix, context=document.body)` | 批量调整容器内 a.href、form.action、img.src；带 keep-url 的元素跳过；为查询串路由 GET 表单登记适配 | 已完成返回 true；配置未就绪时安排重试并返回 undefined |

示例假设当前应用为 Hello，未绑定固定应用域名前缀，网站安装在 `/sub/`：

```js
checkNrun(function () {
    const target = routeURL('article/list/', 'Hello/');
    const url = queryURL(target, {cat_id: 3, keyword: 'A+B 中文', page: 1});
    const link = $id('article-list');
    if (link) link.href = url;
});
```

同一个逻辑路由在三种模式下的形态：

| 模式 | routeURL 的路径部分 | 增加参数后的示意 |
| --- | --- | --- |
| rewrite | `/sub/Hello/article/list/` | `/sub/Hello/article/list/?cat_id=3` |
| path_info | `/sub/index.php/Hello/article/list/` | `/sub/index.php/Hello/article/list/?cat_id=3` |
| query_string | `/sub/index.php?Hello/article/list/` | `/sub/index.php?Hello/article/list/&cat_id=3` |

```js
queryURL('/sub/index.php?Hello/article/list/&page=1#list', {
    page: 2, tags: ['php', 'js'], obsolete: null
});
// /sub/index.php?Hello/article/list/&page=2&tags=php&tags=js#list
```

不要手工写 `article/add&cat_id=3`，因为 rewrite／path_info 需要 `?`，query_string 的首段路由之后才用 `&`。重复数组参数在 PHP 后端是否应使用 `tags[]`，取决于接收接口的字段约定；queryURL 不自动增加方括号。

```html
<div id="article-tools">
    <a href="article/add/">添加文章</a>
    <a href="https://example.test/help" keep-url>独立帮助地址</a>
</div>
```

```js
checkNrun(function () {
    setURL('Hello/', $id('article-tools'));
});
```

prefix 应包含需要的尾部分隔符，函数按 prefix + url 组合，不会替任意业务前缀补斜杠。显式 `''` 表示不给 url 添加模块路径；使用时需确认当前路由含义。根路径 `/article/` 会原样保留，不会自动补应用。

setURL 会修改整个指定容器的链接、表单和图片；动态插入的内容需在插入后再次调用，建议限定容器。GET 表单适配尊重取消事件和提交按钮的 formmethod／formaction／formtarget，非查询串路由不额外接管导航。

## 5. 加载提示、窗口与锚点

| 函数 | 参数与功能 | 返回值／说明 |
| --- | --- | --- |
| `loadingShow(info, show)` | info 为纯文本提示，null 用语言包或默认值；show 为 true 显示、false 隐藏，省略则切换 | boolean 当前显示状态；管理遮罩和 body overflow，依赖现有基础样式及本地 loading.gif |
| `openWindow(url, width, height)` | 打开名为 new_window 的浏览器窗口，宽高单位像素 | Window 或 null；受浏览器弹窗策略约束 |
| `openDialog(url, width, height, mode)` | 尝试旧版对话框；mode true 为模态、false 为非模态，不支持时回退 openWindow | 浏览器的窗口／旧对话框结果或 null |
| `gotoAnchor(theAnchor='')` | 按 id 或旧式 a.name 滚动；允许带 #，空串回顶部 | 始终 false，方便链接处理；没有目标或已有动画时不重复启动 |
| `setLocation(path, name='')` | 调用 history.pushState，记录 title／url 状态 | undefined；不导航、不刷新页面，也不更新 document.title |
| `sleep(ms=0)` | 非阻塞等待，毫秒；负数按 0；非有限值或超过 2147483647 被拒绝 | Promise<void>，调用方需 await／then |

```js
async function updatePreview() {
    loadingShow('正在更新预览……', true);
    try {
        await sleep(100);
        // 在此执行自己的异步预览工作。
    } finally {
        loadingShow(null, false);
    }
}

const previewButton = $id('preview-button');
if (previewButton) previewButton.addEventListener('click', function () {
    openWindow('/preview/', 900, 640); // 示例根路径，实际业务地址由框架生成。
});
// gotoAnchor('chapter-2');
// setLocation(queryURL(location.href, {tab: 'preview'}), '预览');
```

openDialog 是兼容入口，不提供现代页面模态组件的行为保证。setLocation 仅改变历史状态，不会执行服务端路由；需要新的页面内容时仍应进行导航或主动加载。

## 6. 复制文本

| 函数 | 参数与功能 | 返回值 |
| --- | --- | --- |
| `copyValue(obj)` | 辅助解析待复制内容：普通文本直接返回；#id／.class／元素／jQuery 对象按 data-copy、非空 val、text 顺序取值 | string 或 null；通常由 copy／copyAsync 内部调用 |
| `copy(obj)` | 同步复制，使用 execCommand，恢复原焦点和选区 | boolean，权限不足或目标不存在时 false |
| `copyAsync(obj)` | 优先 Clipboard API，被拒绝或不可用时回退 copy | Promise<boolean> |

```html
<span id="share-address" data-copy="https://example.test/">复制地址</span>
<button type="button" id="copy-address">复制</button>
```

```js
const copyButton = $id('copy-address');
if (copyButton) copyButton.addEventListener('click', async function () {
    const ok = await copyAsync('#share-address');
    if (!ok) alert('未能复制，请手动选择文字。');
});
// copy('普通文字');
```

以 # 或 . 开头的字符串会被解释为选择器；要复制这种字面文本，可放在元素的 data-copy 中，再传元素。浏览器权限、HTTPS 和用户手势都会影响结果，应检查返回值。

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
