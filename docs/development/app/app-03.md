# 应用设置补充

## 本篇目标与调用场景

以Hello为教学名称，说明本环节的文件位置、调用前提和与其他模块的对应关系。

## 主要变量

| 变量 | 用途与约束 |
| --- | --- |
| `$mystep` | 当前应用核心实例 |
| `$info_app` | 当前入口与路径上下文 |
| `$tpl_setting` | 当前风格和模板根设置 |
| `$tpl_sub` | 入口先建立，再由对应模块赋值 |

## 3. 应用设置补充：字段、语言与关联保存

有后台可编辑设置时，配套提供本应用 `config/` 下的字段结构、默认及 chs／en 等说明。配置值、表单结构、配置说明语言、界面语言分别管理，不能只改一个中文标签而漏掉对应值和英文说明。

`config_default.php` 是安装逻辑按需加载的初始数据，不是任意应用运行时自动合并的默认文件。`config_入口名.php` 用于当前入口的覆盖；`setting_expand.php` 只在对应保存流程显式调用。是否存在文件，不代表每次请求都会执行。

当一个设置变化需要同步菜单、入口配置或路由时，将关联逻辑放在明确的保存流程中，校验全部输入和版本，处理失败与回滚。只读配置页面不应顺便重建业务资料或安装插件。

## 操作步骤与注意事项

本功能源码全部放在自己的app目录。同名核心继承myStep，module与template按功能对应。JS代码另按JavaScript前端专栏编写；应用入口不再次启动框架或Session。

## 实际值、结构、语言的完整对应

| 文件 | 内容 | 谁读取 |
| --- | --- | --- |
| `app/Hello/config.php` | 返回本应用实际值，覆盖全局默认 | 应用选定后框架合并 |
| `app/Hello/config/chs.php` | 字段标签、帮助和type结构 | 设置管理显式读取，不是配置值 |
| `app/Hello/config/default.php` | 配置表单语言回退，不是实际值初始文件 | myConfig结构加载 |
| `app/Hello/config/construction.php` | 字段类型与密码变换元数据 | makeConstruction及set |
| `app/Hello/language/` | 页面与脚本语言 | 按应用语言读取流程 |
| `app/Hello/setting_expand.php` | 可选设置扩展 | 以设置管理的显式调用为准，不是每个请求的global |

下面是结构语言文件的最小内容，不要存进实际配置文件：

```php
return ['web'=>['name'=>'Hello网站','list'=>[
    'title'=>['name'=>'标题','describe'=>'显示在页面标题中','type'=>['text','', '1-80']],
]]];
```

```php
// 后台已鉴权的设置展示分支；只渲染当前应用的配置。
$config = new myConfig(APP.'Hello/config.php');
$rows = $config->build(APP.'Hello/config/chs.php');
// $rows标题行有idx，控件行有html，交给对应设置模板。
```

字段保存在`setting[web][title]`而非独立的title。保存时先服务端验证并限制可修改字段，再set和save；多语言文件须保持同一字段路径。详细字段类型与生成HTML见[配置表单](../../backend/config/config-forms.md)。

## 验证与进一步阅读

示例中的文件、数据库和变量按本篇前提准备；涉及写入时使用独占临时目录或隔离库，按明确返回值和异常验证。源码中的public/protected/private、引用参数和可选接口是实际契约，不能只按方法名猜测返回或能力。

[返回文档总览](../../README.md)
