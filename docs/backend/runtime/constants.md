# 目录常量与路径

修订日期：2026-10-23。适用于当前框架。

## 本篇目标与调用场景

区分网站根磁盘目录与部署Web前缀，说明常量形成时间和相对路径转换。

## 1. 目录常量与网址常量

| 常量 | 类型及形成时间 | 用法 |
| --- | --- | --- |
| `ROOT` | 根入口确定的磁盘绝对目录，末尾有 `/` | 组合站内文件地址 |
| `LIB`、`APP`、`PLUGIN` | 根入口定义的公共库、应用和插件目录 | 装配受信源码路径 |
| `CONFIG`、`CACHE`、`FILE` | 根入口定义的配置、运行缓存和文件目录 | 读写配置或业务生成数据 |
| `STATICS`、`VENDOR` | 公共静态资源和第三方依赖的磁盘目录 | 从本地登记／加载资源 |
| `ROOT_WEB` | `init()` 计算的部署 Web 前缀，如 `/sub/` | 拼接公开静态资源地址 |
| `PATH` | `setPara()` 确定的当前应用磁盘目录 | 当前应用内部文件选择 |
| `URL_FIX` | 命中域名／入口映射时按条件定义的虚拟前缀 | 影响路由与 URL；不能假定总存在 |

```php
$file = APP.'Hello/template/main.tpl';
$assetURL = ROOT_WEB.'static/images/loading_img.gif';
```

框架页面使用URL生成器；查询连接符依据结果选择，兼容三种路由模式和子目录部署。磁盘地址、静态资源URL和框架页面URL分别生成。

```php
$url = myStep::setURL('Hello/detail');
$url .= (strpos($url, '?') === false ? '?' : '&')
    . http_build_query(['id' => 123], '', '&', PHP_QUERY_RFC3986);
```

不要将服务器的绝对目录写入可发布配置。配置存相对路径，运行时可使用 `myPath::absolute()` 转换；用户提供的相对文件名仍需 `myFile::resolveWithin()` 等进行目录边界检查。单纯在字符串前添加 `ROOT` 不会阻止 `../` 越界。


## 主要方法：参数、返回和失败约定

### myPath::root

功能说明：读取入口定义的项目根目录，独立使用类库时回退到类库的上级目录。

参数说明：无显式参数。

```text
public static function root();
```

**返回**：string 以/结尾的绝对项目根，不取DOCUMENT_ROOT或当前工作目录

### myPath::absolute

功能说明：将相对网站根的设置转换为运行时绝对路径。

```text
public static function absolute($path);
```

| 参数说明 |
| --- |
| string $path 项目根相对路径；兼容历史绝对路径，运行时绝对路径原样规范分隔符 |

**返回**：string 绝对路径，不依赖getcwd；目录尾部/按输入保留

**异常**：InvalidArgumentException 路径不是字符串、含控制字符或 URL/流包装器

### myPath::relative

功能说明：消除./和目录内的..，把项目内绝对路径转换为网站根相对路径。

```text
public static function relative($path);
```

| 参数说明 |
| --- |
| string $path 相对路径或项目内历史绝对路径；根目录可写.或空串 |

**返回**：string 不以/开头的根相对路径；目录尾部/按输入保留

**异常**：InvalidArgumentException 控制字符、协议、绝对路径越界或..越过网站根

### myPath::settings

功能说明：将框架模板、文件会话及本地数据库路径规范为根相对配置。

```text
public static function settings(array $data, $app = 'myStep');
```

| 参数说明 |
| --- |
| array $data 配置数组，其他字段原样保留 |
| string $app 当前应用目录名，默认myStep；仅用于兼容旧template路径 |

**返回**：array 路径规范后的配置；旧template/...转换为app/当前应用/template/...

**异常**：InvalidArgumentException 路径越界或类型无效

## 明确调用示例

```php
$absolute = myPath::absolute('app/Hello/template/');
$relative = myPath::relative($absolute);
assert($relative === 'app/Hello/template/');
```

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
