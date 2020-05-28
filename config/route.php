<?PHP
$format_list = array (
  'Sample' => 
  array (
    'camel' => '[a-z]+([A-Z][a-z]+)+',
  ),
);

$rule_list = array (
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
      0 => '/ms_language/[str]/[any]',
      1 => 'myStep::language',
    ),
    3 => 
    array (
      0 => '/ms_setting/[any]',
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
      0 => '/error/[int]',
      1 => 'myStep::header,$1',
    ),
  ),
);

$api_list = array (
  'Sample' => 
  array (
    'sample' => 'app\\sample\\api',
  ),
  'cms' => 
  array (
    'user' => 'app\\cms\\getUserInfo',
    'get' => 'app\\cms\\getData',
  ),
  'myStep' => 
  array (
    'error' => 'app\\myStep\\getError',
    'data' => 'app\\myStep\\getData',
    'autoComplete' => 'app\\myStep\\autoComplete',
    'segment' => 'myStep::segment',
    'upload' => 'myStep::upload',
    'download' => 'myStep::download',
    'remove' => 'myStep::remove_ul',
  ),
  'plugin_update' => 
  array (
    'check' => 'plugin_update::remote',
    'update' => 'plugin_update::remote',
    'download' => 'plugin_update::remote',
  ),
);

