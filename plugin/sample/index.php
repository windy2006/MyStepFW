<?PHP
//初始化程序一般在页面初始化程序之前执行，用于相关设置
require_once(__DIR__."/class.php");
$this->regModule("test1", __DIR__."/show.php");
$this->regTag('test_tag', function() {return 'Plugin tag test.';});
$this->setLanguage(['test_lng'=>'模版语言测试']);
$this->setFunction('page', 'plugin_sample::setPage');
$this->editorSetPlugin('
			ed.addButton("button_1", {
				text : "按钮1",
				title : "插入测试文字",
				onclick : function() {
					ed.selection.setContent("[测试文字]");
				}
			});
        ', 'button_1');
$this->editorSetPlugin('
			ed.addCommand( "button_cmd", function() {
				var selected_text = ed.selection.getContent();
				var return_text = "";
				return_text = "<h1>" + selected_text + "</h1>";
				ed.execCommand("mceInsertContent", 0, return_text);
			});
        ');
$this->editorSetPlugin('
			ed.addButton("button_2", {
				text : "按钮2",
				title : "变为大字",
				cmd: "button_cmd"
			});
        ', 'button_2');