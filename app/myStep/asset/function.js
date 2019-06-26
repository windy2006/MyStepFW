$.getScript("setting/myStep", function(){
    $.getScript("language/myStep/"+setting.language);
    if(typeof setting.debug != 'undefined' && setting.debug == true) {
        window.onerror = reportError;
    }
});