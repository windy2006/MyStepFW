# 框架路径配置规范

整理日期：2026-10-18。目标运行环境：PHP 7.4。下文中的检查及验证记录保留原日期和适用边界。

配置文件保存项目内的相对路径，运行时再由程序转换为绝对路径。`index.php`用自身目录确定`ROOT`，与Web服务网站目录、PHP工作目录或CLI启动目录无关。搬迁项目无需修改服务器磁盘目录。

| 场景 | 保存示例 | 解析基准 |
| --- | --- | --- |
| 类加载所在目录 | `lib/`、`lib/database/` | `ROOT` |
| 类的特别指定 | `../plugin/interface_plugin.class.php` | 当前规则的所在目录 |
| 应用模板目录 | `app/Admin/template` | `ROOT` |
| 文件会话目录 | `cache/session/` | `ROOT` |
| 显式插件路由 | `plugin/manager/route.php` | `ROOT` |
| SQLite文件 | `files/site.sqlite` | `ROOT`，`:memory:`保留内存库语义 |
| 文件缓存、simpleDB目录 | `cache/data/`、`plugin/` | `ROOT` |

`ROOT_WEB`是Web部署前缀（如`/sub/`），`gen.path_admin`和`web.path_admin`是URL路由，`upload.path_mode`是文件根下的日期子目录格式。数据库服务器主机、Cookie路径及外部URL也不是本地文件路径，不与ROOT拼接。

## 调用方式

```php
$relative = 'lib/database/';
$absolute = myPath::absolute($relative); // ROOT . 'lib/database/'
$portable = myPath::relative($absolute); // lib/database/
$configData = myPath::settings($configData, 'Admin');
```

`myPath::relative()`校验配置属于网站内，消除`./`及不越过根的`..`，拒绝协议及根目录外的路径。特别指定允许通过`../`访问类目录之外的项目文件，但不能越过网站根。

已有内部文件API接收计算后的绝对路径仍然有效；不会把系统临时文件、系统字体等运行时路径强制写入配置。`myFile::realPath()`在定义ROOT时以ROOT解析相对路径，不再依据工作目录或最外层调用文件猜测基准。其历史`relative=true`返回带`/`前缀的格式仍兼容；持久化路径使用`myPath::relative()`，不带前导`/`。

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

## 模板与会话

应用配置填写`app/应用名/template`。旧版`template`及`template/...`在当前应用上下文转换为根相对路径；样式style和模板标签规则不变。新建应用应明确声明模板路径，不依赖旧版应用相对路径写法。已有自定义旧应用相对目录若不是template开头，应手工改为`app/应用名/原目录`。

文件会话使用配置中的根相对目录，未配置时使用`cache/session/`。原来框架会覆盖session.path为固定目录，此行为已改为尊重配置；数据库或其他会话后端不把其参数当作文件目录。


验证记录：路径专项9项、后台服务28项、隔离后台HTTP163个场景、前端19项检查通过；含原有回归共44组完成。实际域名HTTP47项检查在补齐Sample根相对模板路径后全部通过；PHPDoc检查54个类库文件、888个方法/函数，问题0项。新旧类编辑器均通过实际HTTP读写往返验证，业务写操作只使用临时站点。
