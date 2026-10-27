# 查询示例

同一条数据分别通过绑定参数和SQLBuilder查询，让参数值、表名、条件和读取结果之间的关系可直接验证。

## 运行位置与准备

在已经加载框架类库的独立测试脚本中运行；也可放入独立测试应用模块。不要再次调用myStep::init或start。以下代码使用自己的随机临时目录或SQLite内存库，不读取站点数据库或私有配置。代码块包含完整处理与收尾，多个范例分别运行。

## 完整示例

```php
<?php
$connection = new myDb('SQLite', ':memory:');
try {
    $connection->execute('CREATE TABLE articles(id INTEGER PRIMARY KEY,title TEXT,active INTEGER)');
    $connection->execute('INSERT INTO articles(id,title,active) VALUES(?,?,?)', [1,'PHP & 模板',1]);
    $connection->execute('INSERT INTO articles(id,title,active) VALUES(?,?,?)', [2,'缓存说明',0]);

    $first = $connection->fetchOne('SELECT id,title FROM articles WHERE id=?', [1]);
    $empty = $connection->fetchAll('SELECT id FROM articles WHERE id=?', [999]);
    $connection->build('articles')->field('id,title')
        ->where('active', 'n=', 1)->where('title', 'like', 'PHP')
        ->order('id')->limit(0, 10);
    $filtered = $connection->records();

    $connection->execute('UPDATE articles SET active=? WHERE id=?', [1,2]);
    $count = $connection->fetchValue('SELECT COUNT(*) FROM articles WHERE active=?', [1]);
    $connection->execute('DELETE FROM articles WHERE id=?', [2]);
    echo json_encode([
        'first'=>$first, 'empty'=>$empty, 'filtered'=>$filtered,
        'active_after_update'=>(int)$count,
        'remaining'=>(int)$connection->fetchValue('SELECT COUNT(*) FROM articles'),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    $connection->close();
}
```

## 预期结果

first与filtered都包含第1条文章；empty是空数组；更新后启用数为2，删除后剩1条。

## 调用顺序与实际应用

execute负责带参数的写入；fetchOne/fetchAll/fetchValue选择读取形状。SQLBuilder先build指定表，再累积字段和条件，records执行并读取。绑定占位符只放数据值；动态排序字段需要应用白名单，不能写成ORDER BY ?。实际MySQL应用改用框架已有$db和自己的业务表，不照搬本例的建表或清理操作。
