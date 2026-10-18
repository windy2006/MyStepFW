<?PHP
echo $GLOBALS['the_order']++.' - app/test/global.php<br />';
//调用lib.php文件中命名空间下的logCheck方法，如果返回false，则提示出错
if(app\test\logCheck()===false) {
    myStep::info('error');
}