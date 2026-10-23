<?PHP
return array(
    'name' => 'Plugin Setting',
    'list' => [
        'pack' => array(
            'name' => 'Pack',
            'describe' => 'Allow guests pack the whole site.',
            'type' => array("radio", array("Open"=>"true", "Close"=>"false"))
        ),
        'update_url' => array(
            'name' => 'Update URL',
            'describe' => 'Server domain or HTTP/HTTPS base URL. Without a scheme, HTTPS is used with no automatic fallback; HTTP requires http://. Omit /api/plugin_manager/. Leave empty for offline updates only.',
            'type' => array('text', '', '200')
        ),
        'update' => array(
            'name' => 'Update',
            'describe' => 'Allow other framework use to update frome this site',
            'type' => array("radio", array("Open"=>"true", "Close"=>"false"))
        ),
    ]
);