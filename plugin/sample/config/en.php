<?PHP
return array(
    'name' => 'Setting Sample',
    'list' => [
        'para_1' => array(
            'name' => 'Text test',
            'describe' => 'Alphabets only, 50 characters as max',
            'type' => array('text', 'name', '50')
        ),
        'para_2' => array(
            'name' => 'Number test',
            'describe' => 'Numbers only, 10 digital as max',
            'type' => array('text', 'number', '10')
        ),
        'para_3' => array(
            'name' => 'Checkbox test',
            'describe' => 'Multi-select',
            'type' => array('checkbox', array('Selection 1'=>1, 'Selection 2'=>2, 'Selection 3'=>3, 'Selection 4'=>4))
        ),
        'para_4' => array(
            'name' => 'Radio test',
            'describe' => 'Check one',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        'para_5' => array(
            'name' => 'Select test',
            'describe' => 'drop down select',
            'type' => array('select', array('Selection 1'=>'select_1', 'Selection 2'=>'select_2', 'Selection 3'=>'select_3', 'Selection 4'=>'select_4'))
        ),
        'para_6' => array(
            'name' => 'Password test',
            'name2' => 'Re-input',
            'describe' => 'Input the same password twice, 15 characters as max',
            'type' => array('password', '', '15')
        ),
        'para_7' => array(
            'name' => 'Switch test',
            'describe' => 'Only one checkbox will be show',
            'type' => array('switch', 'y', 'Open or Close')
        ),
        'para_8' => array(
            'name' => 'Multi-line text',
            'describe' => 'Multi-line text box',
            'type' => array('textarea', '', 5)
        ),
    ]
);