# API专属域名与地址生成

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。接口提供启用、允许方法、跨域、仅限后台四项设置；参数按处理器签名的位置传入。

## 3. 专属域名与地址显示

接口列表以纯文本显示不带格式尾段的地址，不生成无法完整携带业务参数的调用链接。`httpURL()`的第三个参数传空字符串可生成这种基础地址；省略或传null仍附加默认格式：

```php
$url = myApi::httpURL('myStep', 'csrf', '');
// domain为空：/api/myStep/csrf（示例为根目录rewrite模式）
// domain为api.mystep.com：https://api.mystep.com/myStep/csrf
```

协议沿用当前请求，子目录和三种路由模式沿用框架URL规则。需要携带参数时使用第四个参数数组：

```php
$url = myApi::httpURL('myStep', 'csrf', 'json', ['core' => 'CMS']);
```

设置专属域名后，框架在该主机请求中识别API虚拟前缀，不要求另外写入`config/domain.php`；普通`/api/...`路径继续可用，不强制跨域重定向。已有同名域名若绑定其他应用或路由，保存前会拒绝冲突。清空专属域名后列表恢复普通API路径；原先显式写入域名绑定的规则不会被擅自删除。

DNS、ServBay虚拟主机和证书仍需部署环境提供。`httpURL()`用于生成对外接口地址，不自动改写已有手工地址或必须保持当前会话的内部请求。专属域名本身不保证不同主机共享登录，仍需正确配置Cookie及目标应用会话。

## 主要方法：参数、返回和失败约定

### myApi::httpTarget

功能说明：从框架API捕获路径定位已登记接口，支持插件前缀和框架简写。

```text
public static function httpTarget($path, $apis = null);
```

| 参数说明 |
| --- |
| string $path API前缀之后的路径 |
| array\|null $apis 当前API注册表，null使用全局路由（默认：null） |

**返回**：array [scope,name]；不存在返回空数组，不加载或调用目标

### myApi::httpURL

功能说明：按专属域名或普通路径生成接口地址，沿用三种路由模式和子目录。

```text
public static function httpURL($scope, $name, $format = null, array $query = []);
```

| 参数说明 |
| --- |
| string $scope 应用或plugin_插件标识 |
| string $name 接口名称 |
| string\|null $format 返回格式，null使用当前默认值，空字符串不附加格式尾段（默认：null） |
| array $query 查询参数（默认：[]） |

**返回**：string 专属域名非空时返回绝对URL，否则返回普通框架API路径

**异常**：InvalidArgumentException 接口标识或格式无效

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
