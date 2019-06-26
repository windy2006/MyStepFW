<div class="card w-100 mb-5 mb-sm-2">
	<div id="grid" class="card-body">
        <div class="grid-item">1.Normal</div>
        <div class="grid-item grid-item--width2 grid-item--height2">2.w2,h2</div>
        <div class="grid-item grid-item--height3">3.h3</div>
        <div class="grid-item grid-item--height2">4.h2</div>
        <div class="grid-item grid-item--width3">5.w3</div>
        <div class="grid-item">6.Normal</div>
        <div class="grid-item">7.Normal</div>
        <div class="grid-item grid-item--height2">8.h2</div>
        <div class="grid-item grid-item--width2 grid-item--height3">9.w2,h3</div>
        <div class="grid-item">10.Normal</div>
        <div class="grid-item grid-item--height2">11.h2</div>
        <div class="grid-item">12,Normal</div>
        <div class="grid-item grid-item--width2 grid-item--height2">13.w2,h2</div>
        <div class="grid-item grid-item--width2">14.w2</div>
        <div class="grid-item">15.Normal</div>
        <div class="grid-item grid-item--height2">16.h2</div>
        <div class="grid-item">17.Normal</div>
        <div class="grid-item">18.Normal</div>
        <div class="grid-item grid-item--height3">19.h3</div>
        <div class="grid-item grid-item--height2">20.h2</div>
        <div class="grid-item">21.Normal</div>
        <div class="grid-item">22.Normal</div>
        <div class="grid-item grid-item--height2">23.h2</div>
	</div>
</div>
<script language="JavaScript">
    jQuery.vendor('masonry.pkgd', {
        add_css:true,
        callback:function(){
            $('#grid').masonry({
                itemSelector: '.grid-item',
                columnWidth: 160,
                percentPosition: true,
                gutter:2,
                transitionDuration: '0.4s',
                stagger: '0.02s',
                horizontalOrder: false,
                fitWidth: false,
                originLeft: true,
                originTop: true,
                resize: true,
                initLayout: true
            });
            setTimeout(function(){$('#grid').masonry('layout');},100);
            window.resizeTo('100%','100%');
        }
    });
</script>