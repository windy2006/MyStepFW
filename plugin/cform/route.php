<?php
/** 声明源；运行类由框架插件加载或已安装priority脚本加载。 */
$rule = [
    ['/cform/manage/[any]', ['app\\myStep\\logCheck','plugin_cform::main']],
    ['/cform/submit/[int]', 'plugin_cform::submitPage'],
    ['/cform/list/[int]', 'plugin_cform::listPage'],
    ['/cform/print/[int]', 'plugin_cform::printPage'],
    ['/cform/file/[int]', 'plugin_cform::filePage'],
];
