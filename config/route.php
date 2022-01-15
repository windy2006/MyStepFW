<?PHP
$format_list = array (
  'Sample' => 
  array (
    'camel' => '[a-z]+([A-Z][a-z]+)+',
  ),
  'test' => 
  array (
    'Camel' => '([A-Z][a-z]+)+',
  ),
);

$rule_list = array (
  'CMS' => 
  array (
    0 => 
    array (
      0 => '/admin_cms/[any]',
      1 => 
      array (
        0 => 'app\\CMS\\installCheck,index',
        1 => 'app\\CMS\\logCheck',
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
      0 => '/mySample3/[camel]',
      1 => 
      array (
        0 => 'app\\sample\\preCheck,3',
        1 => 'app\\sample\\routeTest',
      ),
    ),
  ),
  'test' => 
  array (
    0 => 
    array (
      0 => '/t1/[any]',
      1 => 'app\\test\\route',
    ),
    1 => 
    array (
      0 => '/t2/[any]',
      1 => 'mystep::getModule',
    ),
    2 => 
    array (
      0 => '/t3/[Camel]/[any]',
      1 => 
      array (
        0 => 'app\\test\\f1,$1',
        1 => 'app\\test\\f2,$2',
        2 => 'app\\test\\f3',
      ),
    ),
  ),
  'plugin_manager' => 
  array (
    0 => 
    array (
      0 => '/manager/[any]',
      1 => 
      array (
        0 => 'app\\myStep\\logCheck',
        1 => 'plugin_manager::main',
      ),
    ),
    1 => 
    array (
      0 => '/pack/[any]',
      1 => 'plugin_manager::pack',
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
      0 => '/error/[int]',
      1 => 'myStep::header,$1',
    ),
    6 => 
    array (
      0 => '/console/[any]',
      1 => 
      array (
        0 => 'app\\myStep\\logCheck',
        1 => 'myStep::getModule',
      ),
    ),
  ),
);

$api_list = array (
  'CMS' => 
  array (
    'user' => 'app\\CMS\\getUserInfo',
    'get' => 'app\\CMS\\getData',
    'attachment' => 'app\\CMS\\getAttachment',
  ),
  'Sample' => 
  array (
    'setting' => 'app\\sample\\api',
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
  'test' => 
  array (
    'api' => 'app\\test\\api',
  ),
  'plugin_manager' => 
  array (
    'check' => 'plugin_manager::remote',
    'update' => 'plugin_manager::remote',
    'download' => 'plugin_manager::remote',
  ),
);

