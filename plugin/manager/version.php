<?PHP
return array(
    '2.0' => array( //sample
        'info' => 'It\'s just a sample of update function.',
        'file' => array(
            '/app/allow',
            '/plugin/allow',
            '/readme.md',
            '/LICENSE.txt',
            '/config/version.php'
        ),
        'setting' => array(
            'web' => ['etag'=>'etag_'.date('Ymd')],
        ),
        'code' => 'myFile::saveFile(ROOT."UpToDate.txt", "2.0");',
    ),
);