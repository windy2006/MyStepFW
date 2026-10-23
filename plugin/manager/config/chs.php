<?PHP
return array(
    'name' => '插件设置',
    'list' => [
        'pack' => array(
            'name' => '开启打包',
            'describe' => '是否允许任意访客打包框架文件',
            'type' => array("radio", array("开启"=>"true", "关闭"=>"false"))
        ),
        'update_url' => array(
            'name' => '更新网址',
            'describe' => '获取框架、应用和插件更新的服务器域名或 HTTP/HTTPS 基址；省略协议使用 HTTPS，不自动降级。HTTP 服务须填写 http://；不填 /api/plugin_manager/，留空仅离线更新。',
            'type' => array('text', '', '200')
        ),
        'update' => array(
            'name' => '开启更新',
            'describe' => '是否用于其他框架用户通过本网站更新',
            'type' => array("radio", array("开启"=>"true", "关闭"=>"false"))
        ),
    ]
);