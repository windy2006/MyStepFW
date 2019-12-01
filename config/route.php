<?PHP
$format_list = array (
  'sample' => 
  array (
    'camel' => '[a-z]+([A-Z][a-z]+)+',
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
        0 => 'app\\cms\\installCheck,index',
        1 => 'app\\cms\\logCheck',
        2 => 'myStep::getModule',
      ),
    ),
  ),
  'sample' => 
  array (
    0 => 
    array (
      0 => '/mySample/[any]',
      1 => 'app\\sample\\route',
    ),
    1 => 
    array (
      0 => '/mySample2/[any]',
      1 => 'mystep::getModule',
    ),
    2 => 
    array (
      0 => '/sample/[camel]',
      1 => 
      array (
        0 => 'app\\sample\\preCheck,3',
        1 => 'app\\sample\\routeTest',
      ),
    ),
  ),
  'myStep' => 
  array (
    0 => 
    array (
      0 => '/[str]/api/[any]',
      1 => 'myStep::api',
    ),
    1 => 
    array (
      0 => '/[str]/module/[any]',
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
        1 => 'myStep::getModule',
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
  'sample' => 
  array (
    'sample' => 'app\\sample\\api',
  ),
);

