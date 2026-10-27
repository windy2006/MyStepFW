# 编辑器初始化与插件按钮

## 运行前提

使用现有sample插件所采用的TinyMCE接口，先加载站点提供的本地TinyMCE编辑器。页面先提供`<textarea id="hello_content" name="content"></textarea>`，框架PHP负责登记并生成插件脚本。

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

## CMS代码段扩展

CMS使用`asset/admin/tinymce_init.js`中的`myStep_cms`专用扩展，沿用既有初始化和按钮登记模式。按钮名称仍为`highlight`，界面显示“代码段”。它不在编辑器中运行高亮引擎，只显示灰底边框，并保存语言信息。光标处于代码段内时可以更改语言或取消代码段；选中文字后可将其转换为代码段。

```javascript
// 已有CMS编辑器实例，可通过命令设置当前代码段；支持撤销。
const editor = tinymce.activeEditor;
editor.execCommand('msCodeBlock', false, 'php');
editor.execCommand('msCodeBlock', false, 'auto'); // 前台自动识别
editor.execCommand('msCodeBlock', false, '');     // 转换为普通正文
```

保存结构如下。代码内容使用文本节点，HTML源码必须转义；空格、制表符、全角空格与换行均保留。`auto`模式不写code的language类；未知语言在前台按普通代码展示。

```html
<pre class="highlight" data-lng="php"><code class="language-php">&lt;?php
    echo "Hello";
</code></pre>
```

设置data-lng选择语言；缺省使用auto。前台加载本地高亮组件后按保存的语言标记展示代码。

```javascript
$.vendor('highlight', {callback: function () {
    highlight(1, 'vs2015');
}});
```

包装器读取data-lng或language类，调用本地highlight.js，并添加一个复制按钮。重复调用不会重复高亮或添加按钮，复制目标仅为code文本。CMS的default和guide文章风格均已接入；自定义风格应自行调用这段初始化。框架的`cache/script/editor_plugin_CMS.js`继续负责其他应用／插件登记的扩展，CMS代码段按钮在`tinymce_init.js`内维护；修改源码后应刷新编辑页资源缓存。

[浏览器前端](../README.md) · [文档总览](../../README.md)

## 代码段操作

在编辑器中选中文字，打开“代码段”菜单并选择语言。语言只作标记，编辑区通过背景、边框和标签区分代码，页面展示时再高亮。

光标位于代码段中时可切换语言；选择“取消代码段”将内容转为普通正文。转换保留缩进、换行和连续空行。提交表单前使用tinymce.triggerSave()将内容同步到textarea。

PHP代码使用pre的highlight类、data-lng="php"及code的language-php类。自动识别使用data-lng="auto"；纯文本使用data-lng="plaintext"及language-plaintext。
