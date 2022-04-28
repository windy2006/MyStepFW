<?PHP
set_time_limit(0);
ini_set('memory_limit', '512M');
//require_once(LIB.'chs2cht.dic.php');

//pack
$mypacker = new myPacker('./', PATH.'data/unpack/test.pkg');
$mypacker->addIgnore(basename(__DIR__).'/', '.svn/', '.log/', '.idea/', 'cache/', 'web.config', 'aspnet_client/', 'Thumbs.db', '_bak/');
$mypacker->pack();
echo '<b>Pack Files:</b><br />'.chr(10);
echo $mypacker->result();

echo chr(10).chr(10).'<br />--------------------------------<br />'.chr(10).chr(10);

//unpack
$mypacker = new myPacker(PATH.'data/unpack/'.time().'/', PATH.'data/unpack/test.pkg');
$mypacker->unpack();
echo '<b>Unpack Files:</b><br />'.chr(10);
echo $mypacker->result();
