<?PHP
return array(
    '0' => array( //sample
        'info' => 'comment of the current update',
        'file' => array(
            'update/update.php',
        ),
        'setting' => array(),
        'code' => 'echo "any php script";',
    ),
    '1.5.3' => array(
        'info' => 'My Step Framework v1.5.3
1.Add function extension method for tinyMCE editor
2.Collect all initial code for tinyMCE in CMS app into a js file
3.Fix a bug in CMS catalog list when it has prefix parameter
4.Fix a bug in document app when call in specified domain
5.Add path information quote in route rule parameter
6.Add http error response code feedback function
7.Some other bug fix, code adjust and function optimize.',
        'file' => array(
            'app/Document/index.php',
            'app/Document/module/myImg.php',
            'app/Sample/route.php',
            'app/myStep/module/function/cache.php',
            'app/myStep/module/function/plugin.php',
            'app/myStep/template/function_cache.tpl',
            'app/myStep/language/chs.php',
            'app/myStep/language/en.php',
            'app/myStep/route.php',
            'config/version.php',
            'plugin/update/class.php',
            'plugin/update/version.php',
            'plugin/sample/index.php',
            'readme.md',
            'static/js/global.js',
            'lib/database/mssql.class.php',
            'lib/cache/memoryCache.class.php',
            'lib/function.php',
            'lib/myStep.class.php',
            'lib/myController.class.php',
            'lib/myRouter.class.php',
            'vendor/jquery.powerupload/jquery.powerupload.js',
        ),
    ),
);