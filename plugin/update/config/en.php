<?php
return array(
    'name' => 'Plugin Setting',
    'list' => [
        'pack' => array(
            'name' => 'Pack',
            'describe' => 'Allow guests pack the whole site.',
            'type' => array("radio", array("Open"=>"true", "Close"=>"false"))
        ),
        'update' => array(
            'name' => 'Update',
            'describe' => 'Allow other framework use to update frome this site',
            'type' => array("radio", array("Open"=>"true", "Close"=>"false"))
        ),
    ]
);