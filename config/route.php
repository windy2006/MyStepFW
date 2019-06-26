<?PHP
$preload_list = array (
  'Document' => '/Users/sunkai/Documents/website/mystep_fw/app/Document/lib.php',
  'cms' => '/Users/sunkai/Documents/website/mystep_fw/app/cms/lib.php',
  'myStep' => '/Users/sunkai/Documents/website/mystep_fw/app/myStep/lib.php',
);

$format_list = array (
  'Document' => 
  array (
    'test' => '([A-Z][a-z]+)+',
  ),
);

$rule_list = array (
  'cms' => 
  array (
    0 => 
    array (
      0 => '/admin_cms/[any]',
      1 => 
      array (
        0 => 'app\\cms\\logCheck',
        1 => 'getModule',
      ),
    ),
  ),
  'myStep' => 
  array (
    0 => 
    array (
      0 => '/api/[any]',
      1 => 'myStep::getApi',
    ),
    1 => 
    array (
      0 => '/module/[any]',
      1 => 'myStep::module',
    ),
    2 => 
    array (
      0 => '/language/[str]/[any]',
      1 => 'myStep::language',
    ),
    3 => 
    array (
      0 => '/setting/[any]',
      1 => 'myStep::setting',
    ),
    4 => 
    array (
      0 => '/captcha/[any]',
      1 => 'myStep::captcha,4,3',
    ),
    5 => 
    array (
      0 => '/manager/[any]',
      1 => 
      array (
        0 => 'app\\myStep\\logCheck',
        1 => 'getModule',
      ),
    ),
    6 => 
    array (
      0 => '/upload',
      1 => 'myStep::upload',
    ),
    7 => 
    array (
      0 => '/download/[any]',
      1 => 'myStep::download',
    ),
    8 => 
    array (
      0 => '/remove_ul/[any]',
      1 => 'myStep::remove_ul',
    ),
  ),
);

$api_list = array (
  'cms' => 
  array (
    'rss' => 'app\\cms\\rss',
    'get' => 'app\\cms\\getData',
  ),
  'myStep' => 
  array (
    'error' => 'app\\myStep\\getError',
    'data' => 'app\\myStep\\getData',
    'autoComplete' => 'app\\myStep\\autoComplete',
  ),
);

