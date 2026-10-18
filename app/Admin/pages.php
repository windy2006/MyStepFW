<?php
/**
 * 功能说明：后台页面、菜单、视图和动作的统一声明；不在此执行业务代码。
 * 使用说明：路径精确匹配，group控制菜单分组，module指定应用内功能脚本，view和handler指定该脚本的回调。
 * actions只允许POST；fields只提取声明字段；mutex串行化同类多文件操作。
 */
$required = ['type' => 'string', 'required' => true];
$array = ['type' => 'array', 'default' => []];
$action = function ($handler, $fields = []) {
    $mutex = in_array($handler, ['installPlugin', 'savePlugin', 'togglePlugin', 'uninstallPlugin', 'deletePlugin', 'orderPlugins'], true)
        ? 'plugins' : ($handler === 'saveSettings' ? 'settings' : (in_array($handler, ['uploadApp', 'uploadPlugin'], true) ? 'packages' : ''));
    return ['handler' => $handler, 'fields' => $fields, 'mutex' => $mutex];
};
return [
    'manager' => [
        'title' => '框架更新', 'module' => 'manager', 'view' => 'manager',
    ],
    '' => [
        'title' => '基本信息', 'group' => '信息', 'module' => 'info/main', 'view' => 'dashboard',
    ],
    'db' => [
        'title' => '数据库信息', 'group' => '信息', 'module' => 'info/db', 'view' => 'database',
    ],
    'php' => [
        'title' => 'PHP 信息', 'group' => '信息', 'module' => 'info/php', 'view' => 'php',
    ],
    'phpinfo' => [
        'title' => 'phpinfo()', 'group' => '信息', 'module' => 'info/phpinfo', 'view' => 'phpinfo',
    ],
    'error' => [
        'title' => '错误日志', 'group' => '信息', 'module' => 'info/error', 'view' => 'errors',
        'actions' => [
            'clear' => $action('clearErrors'),
            'download' => $action('downloadErrors'),
        ],
    ],
    'setting' => [
        'title' => '参数设置', 'group' => '设置', 'module' => 'setting/main', 'view' => 'settings',
        'actions' => [
            'save' => $action('saveSettings', ['target' => 'string', 'version' => $required, 'setting' => $array]),
        ],
    ],
    'setting/class' => [
        'title' => '类加载设置', 'group' => '设置', 'module' => 'setting/class', 'view' => 'classes',
        'actions' => [
            'save' => $action('saveClasses', ['rows' => $array, 'version' => $required]),
        ],
    ],
    'setting/domain' => [
        'title' => '域名绑定', 'group' => '设置', 'module' => 'setting/domain', 'view' => 'domains',
        'actions' => [
            'save' => $action('saveDomains', ['rows' => $array, 'version' => $required]),
        ],
    ],
    'setting/alias' => [
        'title' => '类别名', 'module' => 'setting/alias', 'view' => 'aliases',
        'actions' => [
            'save' => $action('saveAliases', ['aliases' => $array, 'version' => $required, 'directory' => ['type'=>'int','min'=>0]]),
        ],
    ],
    'setting/detail' => [
        'title' => '方法说明及别名', 'module' => 'setting/detail', 'view' => 'methods',
        'actions' => [
            'save' => $action('saveMethods', ['class' => $required, 'aliases' => $array, 'version' => $required]),
        ],
    ],
    'setting/execute' => [
        'title' => '代码测试', 'group' => '设置', 'module' => 'setting/execute', 'view' => 'execute',
        'actions' => [
            'run' => $action('runCode', ['code' => $required]),
        ],
    ],
    'function/app' => [
        'title' => '应用管理', 'group' => '功能', 'module' => 'function/app', 'view' => 'apps',
        'actions' => [
            'save' => $action('saveApp', ['name' => $required, 'route' => 'string', 'plugins' => $array, 'version' => $required, 'plugin_version' => $required]),
            'mapping' => $action('saveMapping', ['mapping' => $array, 'version' => $required]),
            'upload' => $action('uploadApp'),
        ],
    ],
    'function/cache' => [
        'title' => '缓存管理', 'group' => '功能', 'module' => 'function/cache', 'view' => 'caches',
        'actions' => [
            'clear' => $action('clearCaches', ['areas' => $array]),
        ],
    ],
    'function/plugin' => [
        'title' => '插件管理', 'group' => '功能', 'module' => 'function/plugin', 'view' => 'plugins',
        'actions' => [
            'toggle' => $action('togglePlugin', ['idx' => $required]),
            'uninstall' => $action('uninstallPlugin', ['idx' => $required]),
            'delete' => $action('deletePlugin', ['idx' => $required]),
            'pack' => $action('packPlugin', ['idx' => $required]),
            'order' => $action('orderPlugins', ['order' => $array]),
            'upload' => $action('uploadPlugin'),
        ],
    ],
    'function/plugin/view' => [
        'title' => '插件安装', 'module' => 'function/plugin_setting', 'view' => 'pluginSettings',
        'actions' => [
            'install' => $action('installPlugin', ['idx' => $required, 'setting' => $array, 'apps' => $array, 'version' => $required, 'registry_version' => $required]),
        ],
    ],
    'function/plugin/setting' => [
        'title' => '插件设置', 'module' => 'function/plugin_setting', 'view' => 'pluginSettings',
        'actions' => [
            'save' => $action('savePlugin', ['idx' => $required, 'setting' => $array, 'apps' => $array, 'version' => $required, 'registry_version' => $required]),
        ],
    ],
    'function/attachment' => [
        'title' => '附件管理', 'group' => '功能', 'module' => 'function/attachment', 'view' => 'attachments',
        'actions' => [
            'delete' => $action('deleteAttachment', ['idx' => $required, 'logged' => ['type' => 'int', 'choices' => [0, 1], 'required' => true]]),
        ],
    ],
    'function/language' => [
        'title' => '语言管理', 'group' => '功能', 'module' => 'function/language', 'view' => 'languages',
        'actions' => [
            'save' => $action('saveLanguage', ['app' => $required, 'type' => $required, 'new' => 'string', 'language' => $array, 'version' => $required]),
        ],
    ],
    'guide' => [
        'title' => '框架指南', 'group' => '文档', 'module' => 'guide', 'view' => 'guide',
    ],
    'samples' => [
        'title' => '功能示例', 'group' => '文档', 'module' => 'samples', 'view' => 'samples',
    ],
    'references' => [
        'title' => '应用与类说明', 'group' => '文档', 'module' => 'references', 'view' => 'references',
    ],
];
