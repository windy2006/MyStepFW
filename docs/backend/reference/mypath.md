# myPath：路径规范化

## 职责与调用位置

持久配置用网站根相对路径；运行时才组合为磁盘绝对路径。本类不代替目录访问权限或文件存在检查。

源码：`lib/myPath.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$absolute = myPath::absolute('app/Hello/template/');
$relative = myPath::relative($absolute);
assert($relative === 'app/Hello/template/');
```

## 声明与类职责

### myPath

功能说明：分离项目根相对配置与运行时绝对路径，支持项目内绝对路径。

本类负责路径表达和边界规范化，不检查文件访问权限。
对外文件操作仍须使用 myFile 的路径边界及文件类型检查。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myPath::root` | 读取入口定义的项目根目录，独立使用类库时回退到类库的上级目录。 |
| `myPath::absolute` | 将相对网站根的设置转换为运行时绝对路径。 |
| `myPath::relative` | 消除./和目录内的..，把项目内绝对路径转换为网站根相对路径。 |
| `myPath::settings` | 将框架模板、文件会话及本地数据库路径规范为根相对配置。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myPath::root

功能说明：读取入口定义的项目根目录，独立使用类库时回退到类库的上级目录。

参数说明：无显式参数。

```text
public static function root();
```

显式参数：无。若采用可变位置参数约定，以本方法PHPDoc及初始化说明为准。

**返回值**：string 以/结尾的绝对项目根，不取DOCUMENT_ROOT或当前工作目录

调用形式：

```php
$result = myPath::root();
```

定位：`lib/myPath.class.php:50`。

### myPath::absolute

功能说明：将相对网站根的设置转换为运行时绝对路径。

```text
public static function absolute($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；项目根相对路径；支持绝对路径，运行时绝对路径原样规范分隔符 |

**返回值**：string 绝对路径，不依赖getcwd；目录尾部/按输入保留

**异常与失败**：InvalidArgumentException 路径不是字符串、含控制字符或 URL/流包装器

调用形式：

```php
$result = myPath::absolute($path);
```

定位：`lib/myPath.class.php:60`。

### myPath::relative

功能说明：消除./和目录内的..，把项目内绝对路径转换为网站根相对路径。

```text
public static function relative($path);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$path` | string；相对路径或项目内绝对路径；根目录可写.或空串 |

**返回值**：string 不以/开头的根相对路径；目录尾部/按输入保留

**异常与失败**：InvalidArgumentException 控制字符、协议、绝对路径越界或..越过网站根

调用形式：

```php
$result = myPath::relative($path);
```

定位：`lib/myPath.class.php:74`。

### myPath::settings

功能说明：将框架模板、文件会话及本地数据库路径规范为根相对配置。

```text
public static function settings(array $data, $app = 'myStep');
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$data` | array；配置数组，其他字段原样保留 |
| `$app` | string；当前应用目录名，默认myStep；仅用于支持template路径 |

**返回值**：array 路径规范后的配置；旧template/...转换为app/当前应用/template/...

**异常与失败**：InvalidArgumentException 路径越界或类型无效

调用形式：

```php
$result = myPath::settings($data, $app);
```

定位：`lib/myPath.class.php:110`。
