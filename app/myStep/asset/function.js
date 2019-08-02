$.getScript("index.php?setting/myStep", function(){
    $.getScript("index.php?language/myStep/"+setting.language);
    if(typeof setting.debug != 'undefined' && setting.debug == true) {
        window.onerror = reportError;
    }
});