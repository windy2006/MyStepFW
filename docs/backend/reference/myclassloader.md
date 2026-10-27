# 加载声明

接口说明：`myClassLoader`。

## 职责与调用位置

类目录相对网站根，idx映射相对该搜索目录；解析的是受信映射数据，不执行用户提交PHP。

源码：`lib/myClassLoader.class.php`。本页列出该文件直接声明的全部类、接口、trait和具名函数；继承及trait导入方法按原声明页查询，不重复伪装成另一次声明。

## 运行前提与示例变量

本页示例放在框架已初始化的应用模块中使用；`$mystep`、`$db`、`$ms_setting`和`$tpl_setting`来自当前应用，不另起一套框架。文件类示例的`$directory`应为测试进程独占临时目录；图片、包或模板输入必须先准备真实文件。调用示例中的业务表、用户编号及目标路径为显式前提，不能直接替换成未校验请求参数。

## 主要用法示例

```php
$mapping = myClassLoader::mapping("array('interface_plugin' => '../plugin/interface_plugin.class.php')");
assert($mapping['interface_plugin'] === '../plugin/interface_plugin.class.php');
```

## 声明与类职责

### myClassLoader

功能说明：统一框架启动与配置编辑使用的类加载规则语义。

第一条规则必须指向核心 lib 目录，特别指定映射相对于所属规则目录。
解析配置文本不执行用户提供的代码；load() 仍会包含受信的配置 PHP 文件。
规则发布使用旁路锁和原子替换，save() 同时检查内容版本，避免覆盖并发编辑。

## 方法目录

| 方法／函数 | 可见性及用途 |
| --- | --- |
| `myClassLoader::load` | 读取启动类索引，兼容迁移项目内历史绝对目录；损坏索引重建默认规则。 |
| `myClassLoader::save` | 校验类加载规则，并在共享旁路锁内比较原版本后原子保存。 |
| `myClassLoader::mapping` | 解析特别指定文本，只接受PHP数组字面量；兼容旧新版JSON对象。 |
| `myClassLoader::normalize` | 统一类目录、扩展名和特别指定路径，持久化时只保留相对路径。 |

## 逐方法参数、返回与示例

下面的“调用形式”说明位置参数及所属对象；变量须按参数表准备。它与上面的完整用法配合阅读，不把尚未准备的文件、连接或请求当作现成环境。内部／魔术方法说明引擎或扩展触发点，不建议直接调用。

### myClassLoader::load

功能说明：读取启动类索引，兼容迁移项目内历史绝对目录；损坏索引重建默认规则。

```text
public static function load($file);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；受信类配置PHP文件，通常CONFIG.class.php |

**返回值**：array 含rules规则列表和reset标志；仅reset为true时需重建相关路由/模板缓存

**异常与失败**：RuntimeException 迁移锁或原子发布失败

调用形式：

```php
$result = myClassLoader::load($file);
```

定位：`lib/myClassLoader.class.php:51`。

### myClassLoader::save

功能说明：校验类加载规则，并在共享旁路锁内比较原版本后原子保存。

```text
public static function save($file, array $rules, $version);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$file` | string；网站根内的受信类配置文件路径 |
| `$rules` | array；目录、扩展名及特别指定规则，保存前按网站根规范化 |
| `$version` | string；浏览时的SHA256内容版本；文件不存在时为missing |

**返回值**：bool 保存成功返回true

**异常与失败**：InvalidArgumentException 规则、版本或配置目标无效；RuntimeException 版本冲突（code=409）或配置发布失败

调用形式：

```php
$result = myClassLoader::save($file, $rules, $version);
```

定位：`lib/myClassLoader.class.php:101`。

### myClassLoader::mapping

功能说明：解析特别指定文本，只接受PHP数组字面量；兼容旧新版JSON对象。

```text
public static function mapping($text);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$text` | string；array('类名'=>'相对文件')、等价[]语法或历史JSON；空串视为空数组 |

**返回值**：array 类名到相对文件路径；不执行函数、变量、include或其他PHP代码

**异常与失败**：InvalidArgumentException 语法无效或不是字符串映射

调用形式：

```php
$result = myClassLoader::mapping($text);
```

定位：`lib/myClassLoader.class.php:135`。

### myClassLoader::normalize

功能说明：统一类目录、扩展名和特别指定路径，持久化时只保留相对路径。

```text
public static function normalize(array $rules, $check = false);
```

| 参数 | 类型、默认值与含义 |
| --- | --- |
| `$rules` | array；含path/ext/idx的规则列表，idx为已解析数组 |
| `$check` | bool；true时检查目录存在、非符号链接并保留首条核心目录；默认false供启动兼容转换 |

**返回值**：array 数字索引规则列表；path相对于ROOT，idx值相对于该path并可使用../访问项目内其他目录

**异常与失败**：InvalidArgumentException 配置类型、扩展名、目录或特别指定越界

调用形式：

```php
$result = myClassLoader::normalize($rules, $check);
```

定位：`lib/myClassLoader.class.php:187`。
