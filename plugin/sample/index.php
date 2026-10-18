<?PHP

/**
 * 文件：plugin/sample/index.php
 * 功能说明：sample 插件的宿主初始化入口
 *
 * 方法目录：
 *   本文件不直接声明具名方法或函数。
 *
 * 回调目录：
 *   回调 1：编译演示模板标签，读取可选的 attr 属性。
 *   回调 2：在 start 阶段执行固定60秒窗口的 Cookie 限流演示。
 *   回调 3：提供 end 收尾阶段的空演示回调。
 *
 * 使用说明：
 *   1. 登记语言、模块、模板标签、页面钩子和编辑器扩展演示。
 *   2. 由 myController::plugin 在宿主方法作用域包含，此时 $this 为当前控制器。
 *   3. 文件存在不等于已安装或启用；范围由注册状态、应用 plugin.php 和框架分支共同决定。
 *
 * 说明：PHPDoc 类型用于说明，不增加原生类型约束；源码保持 PHP 7.4 兼容。
 *       方法目录覆盖本文件直接声明；闭包参数及捕获变量说明见各回调旁 PHPDoc。
 */

require_once(__DIR__."/class.php");
//语言包
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setLanguagePack(dirname(__FILE__).'/language/', $this->setting->gen->language);
//模块
$this->regModule("test1", __DIR__ . "/module/show1.php");
$this->regModule("test2", __DIR__ . "/module/show2.php");
//模版
$this->regTag('test_tag', 
/**
 * 功能说明：编译演示模板标签，读取可选的 attr 属性。
 *
 * 这是编译阶段的回调，不用于执行数据库操作或写入业务状态。
 *
 * @param myTemplate &$tpl 引用传入的当前模板对象
 * @param array &$att_list 引用输入/输出：标签属性字典，读取 attr，缺省为空串（默认：array()）
 * @return string 供模板编译使用的演示文字
 */
function(myTemplate &$tpl, &$att_list = array()) {
    return 'Plugin tag test - '.($att_list['attr'] ?? '');
});
//内容钩子
$this->setAddedContent('start', '<script>
    //alert("头部内容插入");
</script>');
$this->setAddedContent('end', '<script>
setTimeout(function(){
    //alert("尾部内容插入");
}, 1000);
</script>');
//脚本钩子
$this->setFunction('start', 
/**
 * 功能说明：在 start 阶段执行固定60秒窗口的 Cookie 限流演示。
 *
 * 闭包依赖：当前会话、sample_counter Cookie、HTTP响应及 plugin_sample::rate()。
 *
 * 参数说明：无显式参数。
 *
 * @return void 管理员跳过；普通访问更新 Cookie，超限时发送429提示并结束请求
 */
function() {
    // Both framework and CMS administrators receive this authenticated session flag.
    if(\myReq::session('sysop') === 'y') return;

    $times = 30;
    $state=plugin_sample::rate(myReq::cookie('sample_counter','!'),time(),$times);
    if(!$state['allowed']) {
        http_response_code(429);header('Retry-After: '.$state['retry']);
        myStep::info("一分钟内访问不能超过 {$times} 次！");
    } else {
        myReq::setCookie('sample_counter',json_encode(['start'=>$state['start'],'count'=>$state['count']]),$state['retry']);
    }

});
$this->setFunction('page', 'plugin_sample::setPage');
$this->setFunction('end', 
/**
 * 功能说明：提供 end 收尾阶段的空演示回调。
 *
 * 参数说明：无显式参数。
 *
 * @return void 当前仅保留示例注释，不写入文件
 */
function() {
    //f::s(ROOT.'!!!!!.txt', date('Y-m-d H:i:s'));
});
//编辑器扩展
$this->editorSetPlugin('
            editor.addCommand( "test_cmd", function() {
                let selected_text = editor.selection.getContent();
                let return_text = "";
                return_text = "<span style=\"background-color: #7c3200;color:#fff;\">" + selected_text + "</span>";
                editor.execCommand("mceInsertContent", 0, return_text);
            });
        ');
$this->editorSetPlugin('
            editor.addButton("test_button", {
                text : "自定义功能按钮",
                title : "给所选文字加底色",
                cmd: "test_cmd"
            });
        ', 'test_button');
$this->editorSetPlugin('
            editor.addButton( "test_list", {
                text: "自定义下拉选单",
                icon: false,
                type: "menubutton",
                menu: [
                    {
                        text: "主菜单1",
                        menu: [
                            {
                                text: "子菜单1",
                                onclick: function() {
                                    alert("选中子菜单1");
                                }
                            },
                            {
                                text: "子菜单2",
                                onclick: function() {
                                    alert("选中子菜单2");
                                }
                            }
                        ]
                    },
                    {
                        text: "主菜单2",
                        menu: [
                            {
                                text: "子菜单3",
                                onclick: function() {
                                    alert("选中子菜单3");
                                }
                            },
                            {
                                text: "子菜单4",
                                onclick: function() {
                                    alert("选中子菜单4");
                                }
                            }
                        ]
                    }
                ]
            });
        ', 'test_list');
$this->editorSetPlugin('
            editor.addButton( "test_dialog", {
                text: "自定义对话框",
                icon: false,
                onclick: function() {
                    editor.windowManager.open( {
                        title: "自定义对话框",
                        body: [
                            {
                                type: "textbox",
                                name: "textbox_1",
                                label: "单行文本",
                                value: "30"
                            },
                            {
                                type: "textbox",
                                name: "textbox_2",
                                label: "多行文本",
                                value: "随便写点啥吧\n\n可以换行",
                                multiline: true,
                                minWidth: 300,
                                minHeight: 100
                            },
                            {
                                type: "listbox",
                                name: "listbox",
                                label: "下拉选单",
                                "values": [
                                    {text: "选项 1", value: "1"},
                                    {text: "选项 2", value: "2"},
                                    {text: "选项 3", value: "3"}
                                ]
                            }
                        ],
                        onsubmit: function( e ) {
                            editor.insertContent( "<br /><br />单行文本：" + e.data.textbox_1 + "<br />多行文本：<br />" + e.data.textbox_2.replace(/[\r]?\n/g, "<br />") + "<br />下拉选单：" + e.data.listbox);
                        }
                    });
                }
            });
        ', 'test_dialog');