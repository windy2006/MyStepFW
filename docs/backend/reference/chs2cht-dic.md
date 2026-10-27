# chs2cht.dic：PHP简繁字典

## 职责与调用位置

这是PHP后端字符串转换，和浏览器chs2cht.js不是同一入口。转换会受字典与词组匹配限制。

源码：`lib/chs2cht.dic.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
require_once LIB.'chs2cht.dic.php';
$traditional = chs2cht('简体中文', 1);
$simplified = chs2cht($traditional, 0);
// 自定义语言转换使用chg_lng_custom，按下方声明明确目标模式。
```

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `chs2cht` | 简繁转换 |
| `chg_lng_custom` | 语系转换 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### chs2cht

功能说明：简繁转换

```text
function chs2cht($content, $type=1);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；需要转换的文本，自动检测编码后转换并恢复来源编码 |
| `$type` | int；1转繁体，其他值转简体；支持0/2用法（默认：1） |

**返回值**：string 转换后并恢复来源编码的文本

调用形式：

```php
$result = chs2cht($content, $type);
```

定位：`lib/chs2cht.dic.php:17835`。

### chg_lng_custom

功能说明：语系转换

```text
function chg_lng_custom($content, $to);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$content` | string；需要转换的文本，按检测到的来源编码返回 |
| `$to` | string；目标代号：chs/cht/cn/sg/tw/hk |

**返回值**：string 按目标地区替换并恢复来源编码的文本

调用形式：

```php
$result = chg_lng_custom($content, $to);
```

定位：`lib/chs2cht.dic.php:17856`。
