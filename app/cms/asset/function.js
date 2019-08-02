$.getScript("index.php?setting/cms", function(){
    $.getScript("index.php?language/cms/"+setting.language);
    if(typeof setting.debug != 'undefined' && setting.debug == true) {
        window.onerror = reportError;
    }
});