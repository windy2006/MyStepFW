<?PHP
global $mystep;
$mystep->setLanguagePack(dirname(__FILE__).'/language/', $mystep->setting->gen->language);
return array(
    'name' => $mystep->getLanguage('plugin_badReq_info_name'),
    'idx' => basename(realpath(__DIR__)),
    'ver' => '1.0',
    'intro' => $mystep->getLanguage('plugin_badReq_info_intro'),
    'copyright' => $mystep->getLanguage('plugin_badReq_info_copyright'),
    'description' => $mystep->getLanguage('plugin_badReq_info_description'),
    'app' => ['myStep']
);