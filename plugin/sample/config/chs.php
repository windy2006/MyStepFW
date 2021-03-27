<?PHP
return array(
    'name' => '设置示例',
    'list' => [
        'para_1' => array(
            'name' => '文本测试',
            'describe' => '不可为特殊符号，限长50字',
            'type' => array('text', 'name', '50')
        ),
        'para_2' => array(
            'name' => '数字测试',
            'describe' => '只可为合法实数，限长10位',
            'type' => array('text', 'number', '10')
        ),
        'para_3' => array(
            'name' => '复选测试',
            'describe' => '可多选',
            'type' => array('checkbox', array('选项 1'=>1, '选项 2'=>2, '选项 3'=>3, '选项 4'=>4))
        ),
        'para_4' => array(
            'name' => '单选测试',
            'describe' => '单选',
            'type' => array('radio', array('开启'=>'true', '关闭'=>'false'))
        ),
        'para_5' => array(
            'name' => '选单测试',
            'describe' => '下拉列表',
            'type' => array('select', array('选项 1'=>'select_1', '选项 2'=>'select_2', '选项 3'=>'select_3', '选项 4'=>'select_4'))
        ),
        'para_6' => array(
            'name' => '密码测试',
            'name2' => '重新输入',
            'describe' => '请输入两次密码，限长15位',
            'type' => array('password', 'md5', '6-10')
        ),
        'para_7' => array(
            'name' => '开关测试',
            'describe' => '相当于只有一个项目的复选框',
            'type' => array('switch', 'y', '是否开启')
        ),
        'para_8' => array(
            'name' => '多行文本',
            'describe' => '用于输入多行长文本内容',
            'type' => array('textarea', '', 5)
        ),
    ]
);