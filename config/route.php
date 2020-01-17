<?PHP
$format_list = array (
  'Sample' => 
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
  'Sample' => 
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
  'plugin_update' => 
  array (
    0 => 
    array (
      0 => '/update/[any]',
      1 => 
      array (
        0 => 'app\\myStep\\logCheck',
        1 => 'plugin_update::update',
      ),
    ),
    1 => 
    array (
      0 => '/pack/[any]',
      1 => 'plugin_update::pack',
    ),
  ),
  'myStep' => 
  array (
    0 => 
    array (
      0 => '/api/[str]/[any]',
      1 => 'myStep::api',
    ),
    1 => 
    array (
      0 => '/module/[str]/[any]',
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
      0 => '/upload',
      1 => 'myStep::upload',
    ),
    6 => 
    array (
      0 => '/download/[any]',
      1 => 'myStep::download',
    ),
    7 => 
    array (
      0 => '/remove_ul/[any]',
      1 => 'myStep::remove_ul',
    ),
    8 => 
    array (
      0 => '/manager/[any]',
      1 => 
      array (
        0 => 'app\\myStep\\logCheck',
        1 => 'myStep::getModule',
      ),
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
  'Sample' => 
  array (
    'sample' => 'app\\sample\\api',
  ),
  'plugin_update' => 
  array (
    'check' => 'plugin_update::remote',
    'update' => 'plugin_update::remote',
    'download' => 'plugin_update::remote',
  ),
);

