<?PHP
if($GLOBALS['info_app']['app']!='myStep') return;
require_once(__DIR__."/class.php");
$this->setLanguagePack(dirname(__FILE__).'/language/', $this->setting->gen->language);