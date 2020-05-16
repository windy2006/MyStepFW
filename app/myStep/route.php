<?PHP
$rule = array(
    array('/api/[str]/[any]', 'myStep::api'),
    array('/module/[str]/[any]', 'myStep::module'),
    array('/ms_language/[str]/[any]', 'myStep::language'),
    array('/ms_setting/[any]', 'myStep::setting'),
    array('/captcha/[any]', 'myStep::captcha,4,3'),
    array('/manager/[any]', array('app\myStep\logCheck', 'myStep::getModule')),
);
$api = array(
    'error' => 'app\myStep\getError',
    'data' => 'app\myStep\getData',
    'autoComplete' => 'app\myStep\autoComplete',
    'segment' => 'myStep::segment',
    'upload' => 'myStep::upload',
    'download' => 'myStep::download',
    'remove' => 'myStep::remove_ul',
);