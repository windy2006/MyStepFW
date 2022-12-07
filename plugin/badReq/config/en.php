<?PHP
return array(
    'name' => 'Bad URL Setting',
    'list' => [
        'forward' => array(
            'name' => 'Forward URL',
            'describe' => 'Illegal request will be leaded to the URL.',
            'type' => array('text', '_', '100')
        ),
        'bad_chars' => array(
            'name' => 'Bad Chars',
            'describe' => 'Bad Chars in the Query String. Separated by commas',
            'type' => array('text', '', '100')
        ),
        'cookie' => array(
            'name' => 'Cookie Name',
            'describe' => 'The request which doesn\'t allow cookie will be banned. Blank means cancle the function.',
            'type' => array('radio', array('Open'=>'true', 'Close'=>'false'))
        ),
        'post' => array(
            'name' => 'POST Record',
            'describe' => 'Record POST data from bad Requestion',
            'type' => array('radio', array('Open'=>'true', '¹Ø±Õ'=>'Close'))
        ),
    ]
);