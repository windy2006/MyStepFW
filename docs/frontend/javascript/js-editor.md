# 前端编辑器

## 运行前提

使用现有sample插件所采用的TinyMCE接口，先加载站点保留的本地编辑器版本。此例不是对所有TinyMCE新版本的通用API声明。页面先提供`<textarea id="hello_content" name="content"></textarea>`，框架PHP负责登记并生成插件脚本。

## asset/editor-extension.js的代码

下列代码在框架生成的插件init(editor,url)内部运行，editor由宿主提供，不是任意全局变量：

```javascript
editor.addCommand('hello_insert', function () {
    editor.insertContent('<p>Hello</p>');
});
editor.addButton('hello_button', {
    text: '插入问候', title: '插入示例段落', cmd: 'hello_insert'
});
```

## 在当前编辑页初始化

```javascript
// 前提：已加载本地jQuery、TinyMCE、global.js和框架生成的编辑器扩展脚本。
ms_func_reg(function () {
    if (!document.getElementById('hello_content')) return;
    tinymce.init({selector: '#hello_content', plugins: 'myStep', toolbar: 'hello_button'});
});
```

`myStep`为现有生成器登记的插件名；hello_button为后端登记的同名按钮。若页面已有完整toolbar配置，应追加按钮而不是覆盖业务工具栏。多个编辑器应按页面作用域初始化，避免重复绑定。

## 表单提交与失败处理

先把编辑器内容同步回textarea，再运行checkForm或构造FormData；原生表单和AJAX的触发次序由实际页面控制。TinyMCE的triggerSave可用于同步，DOM存在和实例加载完成应先检查。服务端保存接口验证权限、CSRF和允许HTML，浏览器预览不能证明HTML安全。

```javascript
const form = document.getElementById('hello-form');
if (form) form.addEventListener('submit', function (event) {
    if (window.tinymce) tinymce.triggerSave();
    if (!checkForm(form)) event.preventDefault();
});
```

## 调试顺序

先确认本地资源请求成功，再检查tinymce与生成器插件名、容器ID、按钮注册、工具栏配置、缓存是否更新。编辑器扩展初始化失败不应继续把空内容作为成功提交。

[浏览器前端](../README.md) · [文档总览](../../README.md)
