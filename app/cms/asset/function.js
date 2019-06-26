$.getScript("setting/cms", function(){
    $.getScript("language/cms/"+setting.language);
    if(typeof setting.debug != 'undefined' && setting.debug == true) {
        window.onerror = reportError;
    }
});