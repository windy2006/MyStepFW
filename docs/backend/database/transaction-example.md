# 事务示例

对应数据库专题中的事务边界。这里特意让返回false和抛异常产生不同结果，避免把“业务返回失败”误认为已自动回滚。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$connection = new myDb('SQLite', ':memory:');
try {
    $connection->execute('CREATE TABLE stock(id INTEGER PRIMARY KEY,qty INTEGER)');
    $connection->execute('INSERT INTO stock VALUES(?,?)', [1,5]);
    $connection->transaction(function ($tx) {
        $tx->execute('UPDATE stock SET qty=qty-1 WHERE id=?', [1]);
        try {
            $tx->transaction(function ($nested) {
                $nested->execute('UPDATE stock SET qty=? WHERE id=?', [0,1]);
                throw new RuntimeException('撤销内层修改');
            });
        } catch (RuntimeException $error) {
            // 内层保存点回滚，外层仍可继续；实际代码应只捕获自己能处理的业务异常。
        }
    });
    $afterNested = (int)$connection->fetchValue('SELECT qty FROM stock WHERE id=?', [1]);
    $returned = $connection->transaction(function ($tx) {
        $tx->execute('UPDATE stock SET qty=? WHERE id=?', [3,1]);
        return false; // 正常返回，提交！
    });
    try {
        $connection->transaction(function ($tx) {
            $tx->execute('UPDATE stock SET qty=? WHERE id=?', [99,1]);
            throw new RuntimeException('需要回滚');
        });
    } catch (RuntimeException $error) {
        // 连接已由受管事务回滚，继续只读核对。
    }
    echo json_encode([
        'after_nested'=>$afterNested, 'returned'=>$returned,
        'after_error'=>(int)$connection->fetchValue('SELECT qty FROM stock WHERE id=?', [1]),
    ]);
} finally {
    $connection->close();
}
```

## 预期结果

after_nested为4、returned为false、after_error为3。最后一次失败没有把库存改成99。

## 调用顺序与实际应用

外层正常提交，内层异常只撤销保存点；整个回调抛异常则回滚对应范围。MySQL需要支持事务的表引擎，MyISAM不具备相同保证。回调内不自行commit/rollback，也不夹带可能隐式提交的DDL；邮件和文件等外部副作用不会随数据库回滚。
