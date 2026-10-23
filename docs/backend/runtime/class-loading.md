# 类加载目录、索引与特别指定

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

解释按网站根保存目录、相对搜索目录保存idx映射，以及索引内容与代码执行的区别。

## 类加载设置

```php
return array (
  0 => array (
    'path' => 'lib/',
    'ext' => '.php,.class.php',
    'idx' => array (
      'interface_plugin' => '../plugin/interface_plugin.class.php',
    ),
  ),
  1 => array (
    'path' => 'lib/database/',
    'ext' => '.php,.class.php',
    'idx' => array (),
  ),
);
```

每个目录先按类名和扩展名尝试自动加载；特别指定补充类名与文件名不同的映射。PHP数组文本由`myClassLoader::mapping()`解析为纯数据，不使用eval，不执行变量、函数或include；为兼容此前新版后台，旧JSON对象也可读取，保存时仍为PHP数组。扩展名和目录、索引文件的路径边界由`normalize()`统一校验。第一条核心类目录保留为`lib/`。

旧绝对类目录若仍在当前项目内，启动时自动迁移为相对目录并保留有效自定义条目；损坏或错误核心索引才重建默认规则。插件路由注册及后台保存采用相同根相对规则，历史绝对路由源仍可读。


## 主要方法：参数、返回和失败约定

### myClassLoader::load

功能说明：读取启动类索引，兼容迁移项目内历史绝对目录；损坏索引重建默认规则。

```text
public static function load($file);
```

| 参数说明 |
| --- |
| string $file 受信类配置PHP文件，通常CONFIG.class.php |

**返回**：array 含rules规则列表和reset标志；仅reset为true时需重建相关路由/模板缓存

**异常**：RuntimeException 迁移锁或原子发布失败

### myClassLoader::save

功能说明：校验类加载规则，并在共享旁路锁内比较原版本后原子保存。

```text
public static function save($file, array $rules, $version);
```

| 参数说明 |
| --- |
| string $file 网站根内的受信类配置文件路径 |
| array $rules 目录、扩展名及特别指定规则，保存前按网站根规范化 |
| string $version 浏览时的SHA256内容版本；文件不存在时为missing |

**返回**：bool 保存成功返回true

**异常**：InvalidArgumentException 规则、版本或配置目标无效；RuntimeException 版本冲突（code=409）或配置发布失败

### myClassLoader::mapping

功能说明：解析特别指定文本，只接受PHP数组字面量；兼容旧新版JSON对象。

```text
public static function mapping($text);
```

| 参数说明 |
| --- |
| string $text array('类名'=>'相对文件')、等价[]语法或历史JSON；空串视为空数组 |

**返回**：array 类名到相对文件路径；不执行函数、变量、include或其他PHP代码

**异常**：InvalidArgumentException 语法无效或不是字符串映射

### myClassLoader::normalize

功能说明：统一类目录、扩展名和特别指定路径，持久化时只保留相对路径。

```text
public static function normalize(array $rules, $check = false);
```

| 参数说明 |
| --- |
| array $rules 含path/ext/idx的规则列表，idx为已解析数组 |
| bool $check true时检查目录存在、非符号链接并保留首条核心目录；默认false供启动兼容转换 |

**返回**：array 数字索引规则列表；path相对于ROOT，idx值相对于该path并可使用../访问项目内其他目录

**异常**：InvalidArgumentException 配置类型、扩展名、目录或特别指定越界

## 明确调用示例

```php
$mapping = myClassLoader::mapping("array('interface_plugin' => '../plugin/interface_plugin.class.php')");
assert($mapping['interface_plugin'] === '../plugin/interface_plugin.class.php');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
