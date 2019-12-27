<?php
return array(
    '0' => array( //sample
        'info' => 'comment of the current update',
        'file' => array(
            'update/update.php',
        ),
        'setting' => array(),
        'code' => 'echo "any php script";',
    ),
    '1.0.10' => array(
        'info' => '
            none',
        'setting' => array(
            'gen' => ['test'=>'test', 'test1'=>'test1']
        ),
        'file' => array(
            '/config/version.php',
        ),
        'code' => 'echo "any php script";',
    ),
    '1.0.11' => array(
        'info' => '
            1.Update plugin added
            2.Debug mode enhanced
            3.Any other adjust',
        'setting' => array(
            'gen' => ['test'=>'t','test2'=>'test2']
        ),
        'file' => array(
            '/aaa.txt',
        ),
        'code' => 'file_put_contents(ROOT."test.txt",rand());',
    ),
);