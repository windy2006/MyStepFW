# 接口设置

## 本篇目标与调用场景

HTTP分发仅调用已登记处理器。接口提供启用、允许方法、跨域、仅限后台四项设置；参数按处理器签名的位置传入。

## 2. 全局参数统一放入框架设置

新后台进入`/Admin/setting`，选择“主框架”，查看“API设置”；原后台进入`/console/setting/`，不选择具体应用。

字段由`config/construction/chs.php`、`en.php`及`default.php`生成，沿用`myConfig`表单和checkForm校验；默认值写在`config/config_default.php`中。保存到`config/config.php`的`api`分组：

```php
$setting['api'] = array(
    'enabled' => true,
    'default_format' => 'json',
    'body_bytes' => 33554432,
    'domain' => '',
);
```

| 字段 | 含义 |
| --- | --- |
| enabled | API总开关；关闭时API返回503，后台设置仍可打开 |
| default_format | 未识别到明确格式时采用json/xml/string/hex/code之一 |
| body_bytes | 请求正文上限，单位字节，范围1至33554432，默认32 MiB；适用于全部内容类型 |
| domain | 专属主机名及可选端口，如api.mystep.com；不含协议、路径或通配符，留空生成普通路径 |

接口统一沿用保留参数模式，JSON正文由业务按需读取并校验；跨域开关属于各接口设置。主配置未设置api分组时使用默认值，参数页显示可保存的正确类型和值，不会为了读取页面直接重写主配置。

## 5. 逐接口功能管理

新后台`/Admin/function/api`、原后台`/console/function/api`共用设置。菜单名称为“API 管理”，新后台位于“插件管理”之后。列表显示所属项目、接口、代码处理器、调用地址、状态及“设置”操作。

点击某条接口的“设置”，直接在该行下方展开表单；通过本地checkForm校验后以AJAX保存，无需刷新页面。成功后更新该接口状态及所有表单的文件版本，保留其他接口未保存的修改；并发冲突返回409，提示刷新，不覆盖其他修改。

| 设置 | 作用 |
| --- | --- |
| 启用接口 enabled | 关闭后当前接口返回404；启用不能重新注册已停用插件的接口 |
| 允许方法 methods | 逗号分隔GET、HEAD、POST、PUT、PATCH、DELETE；留空不增加限制；OPTIONS只处理预检 |
| 允许跨域 cors | 布尔开关，默认关闭；开启后允许任意合法HTTP/HTTPS来源，无需维护域名名单；不放行null、账号密码、路径或控制字符 |
| 仅限后台 admin | 要求框架管理员ms_user与sysop校验同时通过；不等于普通CMS用户登录；仍保留原接口业务权限 |

恢复默认会移除该接口覆盖，恢复启用、方法不额外限制、跨域关闭和不额外要求管理员。未登记的接口只能清理设置，不能在后台新增可执行回调。

`config/api.php`仅保存接口设置：

```php
return array(
    'endpoints' => array(
        'Hello' => array(
            'detail' => array(
                'enabled' => true,
                'methods' => array('GET'),
                'cors' => false,
                'admin' => false,
            ),
        ),
    ),
);
```

策略文件使用版本比较、锁和原子替换；保存不改全局参数。文件属于站点私有数据，不作为系统更新的文件覆盖成员。无需清缓存或重启PHP。

## 主要方法：参数、返回和失败约定

### myApi::httpSettings

功能说明：补齐并验证框架api分组，供运行时及现有参数设置共同使用。

```text
public static function httpSettings(array $data = []);
```

| 参数说明 |
| --- |
| array $data api分组中的四个全局字段（默认：[]） |

**返回**：array 标准全局字段；域名转为小写，正文上限为字节数

**异常**：UnexpectedValueException 未知字段、类型、正文额度或域名无效

### myApi::httpConfiguration

功能说明：合并框架api参数与config/api.php的逐接口策略，支持旧独立参数文件。

```text
public static function httpConfiguration($data = null);
```

| 参数说明 |
| --- |
| array\|null $data 显式完整配置；null读取当前框架及策略文件（默认：null） |

**返回**：array 标准全局参数及已补齐的接口策略

**异常**：UnexpectedValueException 配置、来源或逐接口策略无效

### myApi::httpPolicy

功能说明：执行逐接口额外限制，不替代原业务鉴权和CSRF。

```text
public static function httpPolicy($scope, $name, array $config);
```

| 参数说明 |
| --- |
| string $scope 已登记的应用或插件范围 |
| string $name 已登记接口名称 |
| array $config 已验证配置 |

**返回**：array 完整策略；拒绝时404/405/403结束

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
