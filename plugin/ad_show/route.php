<?php
$rule=[['/ad_show/[any]',['app\\myStep\\logCheck','plugin_ad_show::main']]];
$rule[]=['/ad_link/[int]','plugin_ad_show::link'];
$rule[]=['/ad_asset/([a-f0-9]{40})','plugin_ad_show::asset'];
