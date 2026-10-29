# 文件上传

以 FormData 发送 POST，每个文件一个请求，默认字段为 userfile。框架后台通常已经加载 Bootstrap；独立页面无 Bootstrap 时仍可关闭上传面板。

```javascript
$.vendor('jquery.powerUpload',{callback:function () {
    $('#upload').powerUpload({
        url:global.root+'api/myStep/upload',mode:'browse',
        max_files:3,max_file_size:10,data:{folder:'documents'},
        uploadFinished:function (i,file,result,elapsed,uploader) {
            if(String(result.error)==='0')console.log(result.new_name);
            else console.log(result.message);
        },
        allDone:function (uploader) { uploader.find('.modal-title b').text('全部处理完成'); }
    });
}});
```

| 选项 | 含义 |
| --- | --- |
| `url`、`param_name` | 目标地址、文件字段名 |
| `mode` | drop、paste、browse；也可传 File/Blob 数组或返回文件列表的函数 |
| `max_files`、`max_file_size` | 批次文件数上限、每文件 MiB 上限；兼容 maxfiles/maxfilesize |
| `data` | 附加字段；字段值为函数时发送前求值 |
| `obj_attr` | 浏览输入框属性，如 `{accept:'image/*'}` |
| `timeout` | 请求超时毫秒，0 不设置超时 |
| `beforeUpload()`、`beforeEach(file)` | 返回 false 分别取消批次／跳过文件 |
| `uploadStarted(i,file,total)` | 单个请求开始 |
| `progressUpdated(i,file,percent)` | 上传进度 |
| `uploadFinished(i,file,result,elapsed,uploader)` | 单文件完成；前四参数保持原约定，elapsed 为毫秒 |
| `allDone(uploader)` | 所有文件成功、失败、跳过或取消后执行一次 |
| `error(message,file,index)` | 文件数／大小／配置等错误反馈 |

回调中的 this 是当前实例选项对象。服务器返回合法 JSON；HTTP 失败、无效 JSON、网络失败、取消和超时均按失败收尾。不能只看进度 100% 判断业务成功；以返回的 error 为准。回调异常通过 `upload:callbackerror(event,error,name)` 报告，保证批次收尾。使用回调提供的 uploader 操作当前面板，避免多个入口共用 #uploader 时操作错误实例。

```javascript
$('#upload').powerUpload('browse');
$('#upload').powerUpload('start',fileInput.files);
$('#upload').powerUpload('cancel');
$('#upload').powerUpload('destroy');
```

取消会中止正在执行的请求。关闭面板只是隐藏，不表示取消。框架的 security.js 对同源写请求统一处理请求令牌；独立页面应按其服务端要求携带令牌，上传组件不代替身份和权限检查。
