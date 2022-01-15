<?PHP
require_once(__DIR__."/class.php");
//语言包
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setLanguagePack(dirname(__FILE__).'/language/', $this->setting->gen->language);
//模块
$this->regModule("test1", __DIR__ . "/module/show1.php");
$this->regModule("test2", __DIR__ . "/module/show2.php");
//模版
$this->regTag('test_tag', function(myTemplate &$tpl, &$att_list = array()) {
    return 'Plugin tag test - '.$att_list['attr'];
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
$this->setFunction('start', function() {
    $times = 30;
    $counter = \myReq::c('counter');
    if(empty($counter)) $counter = 0;
    if($counter>=$times) {
        myStep::info("一分钟内访问不能超过 {$times} 次！");
    } else {
        myReq::setCookie('counter', ++$counter, 60);
    }
});
$this->setFunction('page', 'plugin_sample::setPage');
$this->setFunction('end', function() {
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
                text : "标注选择文字",
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