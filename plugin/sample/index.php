<?PHP
//初始化程序一般在页面初始化程序之前执行，用于相关设置
require_once(__DIR__."/class.php");
$this->regModule("test1", __DIR__."/show.php");
$this->regTag('test_tag', function() {return 'Plugin tag test.';});
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setFunction('page', 'plugin_sample::setPage');
$this->editorSetPlugin('
			editor.addCommand( "test_cmd", function() {
				let selected_text = editor.selection.getContent();
				let return_text = "";
				return_text = "<h1>" + selected_text + "</h1>";
				editor.execCommand("mceInsertContent", 0, return_text);
			});
        ');
$this->editorSetPlugin('
			editor.addButton("test_button", {
				text : "按钮",
				title : "插入测试文字",
				cmd: "test_cmd"
			});
        ', 'test_button');
$this->editorSetPlugin('
            editor.addButton( "test_list", {
                text: "下拉选单",
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
                text: "对话框",
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
                            editor.insertContent( "<br /><br />单行文本：" + e.data.textbox_1 + "<br />多行文本：" + e.data.textbox_2 + "<br />下拉选单：" + e.data.listbox);
                        }
                    });
                }
            });
        ', 'test_dialog');