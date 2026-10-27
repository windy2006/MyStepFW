# mySessionHandler：PHP原生Session桥接器

适用于PHP8.5.12及以上8.x。源码：`lib/session/mySessionHandler.class.php`。

## 功能与使用

由myReq自动使用；直接调用静态处理器的应用无需改写。桥接器只转换调用协议，不选择后端、不连接Redis、不授予认证权限。

```php
myReq::sessionStart('sess_file');
// 或在没有活动Session及输出之前：
myReq::sessionStart(new SessionHandler());
```

## 方法目录

| 方法 | 功能 |
| --- | --- |
| `mySessionHandler::__construct` | 验证六项基础或九项完整会话回调。 |
| `mySessionHandler::open` | 打开实际存储。 |
| `mySessionHandler::close` | 关闭实际存储并释放锁。 |
| `mySessionHandler::read` | 保留原始会话字节和读取失败状态。 |
| `mySessionHandler::write` | 将会话正文交给实际存储。 |
| `mySessionHandler::destroy` | 删除会话，不将失败转换成成功。 |
| `mySessionHandler::gc` | 将回调协议的true回收结果转换为原生协议的零条计数。 |
| `mySessionHandler::create_sid` | 调用自定义ID生成器或生成随机ID。 |
| `mySessionHandler::validateId` | 调用可选的严格ID检查；六回调模式沿用接受规则。 |
| `mySessionHandler::updateTimestamp` | 调用可选续期回调，否则完整写入。 |

## 方法说明

### mySessionHandler::__construct

```php
public mySessionHandler::__construct(array $callbacks)
```

功能说明：验证并保存六个基础回调或九个完整回调，不打开存储。

@param array $callbacks 依次为open/close/read/write/destroy/gc及可选create_sid/validateId/updateTimestamp

@return void 初始化完成

@throws InvalidArgumentException 数量错误或任一条目不可调用

### mySessionHandler::open

```php
public mySessionHandler::open(string $path, string $name): bool
```

功能说明：打开底层存储，传递PHP提供的路径和Cookie名称。

@param string $path 原生会话保存路径

@param string $name 原生会话Cookie名称

@return bool 实际处理器的打开结果

### mySessionHandler::close

```php
public mySessionHandler::close(): bool
```

功能说明：关闭底层连接，释放会话互斥资源。

参数说明：无显式参数。

@return bool 实际关闭结果

### mySessionHandler::read

```php
public mySessionHandler::read(string $id): string|false
```

功能说明：读取原始Session正文，空字符串保持为有效的空会话。

@param string $id 当前会话ID

@return string|false 原始字节或失败；不把false变成空字符串

@throws UnexpectedValueException 处理器返回非字符串且非false

### mySessionHandler::write

```php
public mySessionHandler::write(string $id, string $data): bool
```

功能说明：写入原始Session正文，不更改序列化器。

@param string $id 当前会话ID

@param string $data PHP序列化后的会话字节

@return bool 底层写入结果

### mySessionHandler::destroy

```php
public mySessionHandler::destroy(string $id): bool
```

功能说明：通过底层处理器删除指定会话。

@param string $id 待删除的会话ID

@return bool 底层删除结果

### mySessionHandler::gc

```php
public mySessionHandler::gc(int $max_lifetime): int|false
```

功能说明：支持旧静态协议的布尔回收结果及原生删除条数。

@param int $max_lifetime 原生会话有效期秒数

@return int|false 非负删除条数、旧true转换为0、失败保留false

@throws UnexpectedValueException 返回类型或计数不合法

### mySessionHandler::create_sid

```php
public mySessionHandler::create_sid(): string
```

功能说明：使用可选生成器，未指定时创建48位十六进制随机ID。

参数说明：无显式参数。

@return string 非空且符合PHP会话ID字符规则的字符串

@throws UnexpectedValueException 自定义生成器返回无效ID

### mySessionHandler::validateId

```php
public mySessionHandler::validateId(string $id): bool
```

功能说明：执行可选严格ID存在性检查，六回调模式保留支持语义。

@param string $id 客户端提供的会话ID

@return bool 已声明检查器时返回其结果，否则接受格式合法的ID

### mySessionHandler::updateTimestamp

```php
public mySessionHandler::updateTimestamp(string $id, string $data): bool
```

功能说明：调用可选惰性续期入口；缺少入口时按原协议完整写入。

@param string $id 当前会话ID

@param string $data PHP提供的现有会话正文

@return bool 续期或回退写入是否成功

## 完整回调示例

```php
$callbacks = [
    'sess_file::open', 'sess_file::close', 'sess_file::read',
    'sess_file::write', 'sess_file::destroy', 'sess_file::gc',
    static fn() => bin2hex(random_bytes(24)),
    'sess_file::validateId', 'sess_file::updateTimestamp',
];
myReq::sessionStart($callbacks);
```

未知ID检查、锁和TTL由底层驱动负责。外部创建的自定义回调必须满足对应返回规则，不将null、数组等结果误当成Session正文。
