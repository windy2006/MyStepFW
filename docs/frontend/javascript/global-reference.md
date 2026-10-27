# global.js：函数参数与示例

本页按当前脚本公开声明列出参数、返回和明确调用。先满足本地脚本依赖和DOM条件；省略的业务元素需由页面提供。

## getPath

```text
getPath(lvl=0);
```

获取当前来源及前 N 个目录段。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `[lvl=0]` | number | 保留目录层数，负数按 0；0 仅返回带协议及端口的来源。 |

**返回值**：{string} 来源与目录地址。

### 调用示例

```javascript
console.log(getPath(2));
```

## $id

```text
$id(id);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `id` | string | 元素 ID。 |

**返回值**：{Element|null} 对应元素。

### 调用示例

```javascript
const node = $id('title'); if (node) console.log(node.value);
```

## $name

```text
$name(name, idx);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `name` | string | 元素 name。 |
| `[idx]` | number\|string | 下标、first 或 last；不传则返回完整集合。 |

**返回值**：{NodeList|Element|undefined} 集合或选中元素。

### 调用示例

```javascript
const nodes = $name('topics[]'); console.log(nodes.length);
```

## $tag

```text
$tag(name, theOLE);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `name` | string | 标签名。 |
| `[theOLE=document]` | Document\|Element\|null | 容器。 |

**返回值**：{HTMLCollection} 匹配集合。

### 调用示例

```javascript
console.log($tag('input', document).length);
```

## $class

```text
$class(name, context);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `name` | string | 样式类名。 |
| `[context=document]` | Document\|Element\|null | 容器。 |

**返回值**：{HTMLCollection} 匹配集合。

### 调用示例

```javascript
console.log($class('notice', document).length);
```

## isArray

```text
isArray(para);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `para` | * | 待判断的值。 |

**返回值**：{boolean} 是否为数组（支持跨窗口数组）。

### 调用示例

```javascript
console.assert(isArray([1, 2]));
```

## loadingShow

```text
loadingShow(info, show);
```

显示/隐藏加载遮罩，保存并恢复原页面 overflow；info 以纯文本显示。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `info` | string\|null | 提示文字，空值使用语言包或默认文字。 |
| `[show]` | boolean | 显式指定显示或隐藏；省略时切换显示状态。 |

**返回值**：{boolean} 当前是否显示遮罩。

### 调用示例

```javascript
loadingShow('正在加载', true); loadingShow('', false);
```

## openDialog

```text
openDialog(url, width, height, mode);
```

尝试浏览器对话框，不支持时回退普通窗口。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `url` | string | 目标地址。 |
| `width` | number | 宽度像素。 |
| `height` | number | 高度像素。 |
| `mode` | boolean | true 为模态，false 为非模态。 |

**返回值**：{Window|*|null} 浏览器返回的窗口/对话框结果，拦截弹窗时可为空。

### 调用示例

```javascript
// 在用户点击事件中打开本业务页面。
openDialog('/Hello/help', 640, 480, 0);
```

## openWindow

```text
openWindow(url,width,height);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `url` | string | 地址。 |
| `width` | number | 宽度像素。 |
| `height` | number | 高度像素。 |

**返回值**：{Window|null} 新窗口，拦截时为 null。

### 调用示例

```javascript
openWindow('/Hello/help', 640, 480);
```

## sleep

```text
sleep(ms=0);
```

异步等待，调用方使用 await sleep(ms)。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `ms` | number | 等待毫秒数，负数按 0 处理，最大 2147483647。 |

**返回值**：{Promise<void>} 定时器到期后完成，不阻塞页面。

### 调用示例

```javascript
sleep(20).then(function () { console.log('完成'); });
```

## copyValue

```text
copyValue(obj);
```

将普通字符串或元素（含 jQuery 对象）解析为待复制文字；#id/.class 必须匹配现有元素。

### 调用示例

```javascript
const node = $id('source'); if (node) console.log(copyValue(node));
```

## copy

```text
copy(obj);
```

同步复制文字，保留 boolean 返回值；临时输入框移除后恢复焦点和选区。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `obj` | string\|Element\|jQuery | 普通文字、#id/.class 选择器或元素。 |

**返回值**：{boolean} execCommand 的结果；不可用/拒绝/目标不存在时为 false。

### 调用示例

```javascript
copy('Hello');
```

## copyAsync

```text
copyAsync(obj);
```

优先使用安全上下文的 Clipboard API；被拒绝时回退 copy，结果为 Promise<boolean>。

### 调用示例

```javascript
copyAsync('Hello').then(function (ok) { console.log(ok ? '已复制' : '未能复制'); }).catch(console.error);
```

## rndNum

```text
rndNum(min,max);
```

普通随机整数，闭区间内各整数等概率；不用于安全令牌。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `[min]` | number | 下限；只传一个参数时作为上限，下限为 0。 |
| `[max]` | number | 上限，允许交换上下界，必须存在安全整数区间。 |

**返回值**：{number} 无参数返回 [0,1) 小数；有参数返回区间整数。

@throws {RangeError} 参数非有限数或区间超出安全整数范围。

### 调用示例

```javascript
console.log(rndNum(1, 6));
```

## rndStr

```text
rndStr(len, t_lst='', c_lst=[]);
```

生成普通随机文本，不能作为认证令牌使用。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `len` | number | 选取次数，0—1000000；小数向下取整。 |
| `[t_lst='0110']` | string | 大写/小写/数字/汉字的四位开关，短开关按规则补位。 |
| `[c_lst=[]]` | string\|Array<string> | 额外的字符串单元，可为多字符文本。 |

**返回值**：{string} 没有任何候选字符时返回空串。

### 调用示例

```javascript
console.log(rndStr(12));
```

## watermark

```text
watermark(obj, rate=5, copyright='WaterMark Maker, Coded by Windy2000', char_c='', jam_tag=false);
```

给可信 HTML 的文本片段插入水印，不跨标签拼接干扰 span；不是 HTML 消毒器。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `obj` | string\|Element | 文本或元素；元素会更新 innerHTML。 |
| `[rate=5]` | number | 插入阈值，越大越少；10 不产生随机干扰字。 |
| `[copyright]` | string | 每个换行后的版权说明，以文本转义。 |
| `[char_c='']` | string\|null | 逗号分隔的额外随机字符串单元。 |
| `[jam_tag=false]` | boolean | 是否在文本片段内插入成对 span。 |

**返回值**：{string} 生成的 HTML。

### 调用示例

```javascript
const node = $id('article'); if (node) watermark(node, 5, 'Example');
```

## isMobile

```text
isMobile();
```

**返回值**：{{Android:boolean,BlackBerry:boolean,iOS:boolean,Windows:boolean,any:boolean}} UA 识别结果，包括桌面 UA 的触控 iPad。

### 调用示例

```javascript
console.log(isMobile().any);
```

## base64

```text
base64(code, mode);
```

UTF-8 文本与 Base64 转换；自动判断仅含糊的纯字母文本请明确指定 mode。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `code` | string | 文本或标准 Base64 字符串，支持 Unicode 文本。 |
| `[mode]` | boolean | true 解码、false 编码；省略时尝试解码合法的 UTF-8 Base64，失败则编码。 |

**返回值**：{string} 转换结果；显式解码非法输入时抛出异常。

### 调用示例

```javascript
const value = base64('中文', false); console.log(base64(value, true));
```

## md5

```text
md5(str);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `str` | string | UTF-8 文本，ASCII文本采用相同规则。 |

**返回值**：{string} 32 位小写 MD5，仅用于非认证用途的标识。

### 调用示例

```javascript
console.log(md5('Hello')); // 仅兼容摘要，不用于认证。
```

## debug

```text
debug(para, mode);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `para` | * | 调试值。 |
| `[mode=false]` | boolean | true 追加，否则覆盖。 |

**返回值**：{void} 更新页面调试区。

### 调用示例

```javascript
debug({title:'Hello'}, true);
```

## c

```text
c();
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `values` | ...* | 依次写入控制台的值。 |

**返回值**：{void}

### 调用示例

```javascript
c('Hello');
```

## a

```text
a();
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `values` | ...* | 依次弹出提示的值。 |

**返回值**：{void}

### 调用示例

```javascript
a('Hello');
```

## info

```text
info(msg);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `msg` | string\|null | 可信提示 HTML；空值隐藏。 |

**返回值**：{void} 更新页面提示区。

### 调用示例

```javascript
info('保存完成'); // 仅传受信提示内容。
```

## checkObj

```text
checkObj(obj, func_show);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `obj` | Object | 对象。 |
| `[func_show=alert]` | Function | 每项的输出函数。 |

**返回值**：{void}

### 调用示例

```javascript
checkObj({a:1}, console.log);
```

## reportError

```text
reportError(msg, url, line, column, error);
```

报告错误并保留浏览器控制台默认错误；仅 debug 模式弹出/调用自定义报告器。

### 调用示例

```javascript
reportError('演示错误', location.href, 1, 1, new Error('demo'));
```

## checkNrun

```text
checkNrun(func, params=[], timeout=15000, context=window, interval=100);
```

等待 language/setting 后调用函数，各次注册相互独立，返回取消函数。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `func` | Function\|string | 回调或 window 上的函数名。 |
| `[params=[]]` | Array | 回调参数。 |
| `[timeout=15000]` | number | 最长等待毫秒数；超时写控制台并释放定时器。 |
| `[context=window]` | Object | 回调 this。 |
| `[interval=100]` | number | 轮询间隔，至少 10 毫秒。 |

**返回值**：{Function} 取消当前注册；重复取消无副作用。

### 调用示例

```javascript
checkNrun(function () { console.log(setting.app); }, [], 15000, window, 100);
```

## checkSetting

```text
checkSetting(timer=500, callback=null, params=[]);
```

检查配置是否就绪；需要自动重试时显式传入 callback 和参数，支持严格模式。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `[timer=500]` | number | 重试间隔毫秒数。 |
| `[callback=null]` | Function\|string\|null | 未就绪时安排的回调。 |
| `[params=[]]` | Array | 回调参数。 |

**返回值**：{boolean} 两个配置对象均已定义时为 true；不读取caller/arguments。

### 调用示例

```javascript
if (!checkSetting(100, function () { console.log(setting.app); })) console.log('等待配置');
```

## queryURL

```text
queryURL(url, params={});
```

更新 URL 查询参数，保留查询串路由及锚点，不解码或改写路径。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `url` | string | 原始 URL，可含 index.php?/route/&key=value。 |
| `params` | Object | 参数字典；null 删除，数组生成重复参数。 |

**返回值**：{string} 参数按 URL 标准编码后的 URL。

### 调用示例

```javascript
console.log(queryURL('/index.php?/Hello/list&page=1', {page:2, keyword:'A&B'}));
```

## routeURL

```text
routeURL(url, prefix=global.root_fix);
```

将应用相对链接转换为当前路由模式的地址，查询值和锚点保持原样。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `url` | string | 相对路径或查询串；完整 URL、根路径及锚点直接保留。 |
| `prefix` | string | 当前模块路径，默认 global.root_fix；可为已转换的路由地址。 |

**返回值**：{string} 已转换地址；配置缺失时原样保留url，应在checkNrun回调中转换；可重复转换。

### 调用示例

```javascript
console.log(routeURL('detail?id=7', 'Hello/'));
```

## setURL

```text
setURL(prefix=global.root_fix, context=window.document.body);
```

批量转换容器内的链接、表单和图片；keep-url 标记保留原地址。

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `prefix` | string | 应用或模块路由前缀，默认 global.root_fix。 |
| `context` | Element\|jQuery | 扫描容器，默认页面 body。 |

**返回值**：{boolean|undefined} 已处理返回 true；配置未就绪时等待后重试。

### 调用示例

```javascript
setURL('Hello/', document.getElementById('content') || document.body);
```

## gotoAnchor

```text
gotoAnchor(theAnchor='');
```

定位页面内锚点（空串回顶部），不将锚点插入 CSS 选择器；动画与就绪定时器独立。

### 调用示例

```javascript
gotoAnchor('#details');
```

## setLocation

```text
setLocation(path, name='');
```

更新浏览器地址记录和标题数据，不重复注册无实际行为的 popstate 监听器。

### 调用示例

```javascript
setLocation(location.pathname + '?page=2', '第二页');
```

## ms_func_reg

```text
ms_func_reg(func);
```

| 参数及默认值 | 类型 | 功能说明 |
| --- | --- | --- |
| `func` | Function | 页面初始化回调；非函数忽略。 |

**返回值**：{void} 注册到 global.func。

### 调用示例

```javascript
ms_func_reg(function () { console.log('就绪'); });
```

## ms_func_run

```text
ms_func_run();
```

**返回值**：{void} 配置和语言就绪后执行当前回调快照；未就绪时有限等待，超时不执行回调并停止重试；重复调用不叠加事件监听器。

### 调用示例

```javascript
// 通常由框架在DOM就绪时调用，不要重复触发业务绑定。
ms_func_run();
```

[返回JavaScript入口](frontend.md)
