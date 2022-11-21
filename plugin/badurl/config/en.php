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
    ]
);