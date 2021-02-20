<style>
    #pic_container img {
        width: 100%;
        height: auto;
        margin-bottom: 10px;
        border-right: 1px gray solid;
        border-radius: 10px;
    }
</style>
<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-body text-center">
        <div id="pic_container">
            <img title="风景 1" data-src="https://up.enterdesk.com/edpic/9c/14/8c/9c148c3484d58df3a64cc39929e46966.jpg" alt="" />
            <br />
            <img title="风景 2" data-src="https://up.enterdesk.com/edpic/bb/12/64/bb1264aae179a8a13d154563ad381d93.jpg" alt="" />
            <br />
            <img title="风景 3" data-src="https://up.enterdesk.com/edpic/bf/f2/5c/bff25c52adda065475059b31c2214228.jpg" alt="" />
            <br />
            <img title="风景 4" data-src="https://up.enterdesk.com/edpic/9d/1a/cc/9d1acc784d8faf03605ef38a6eaa5850.jpg" alt="" />
            <br />
            <img title="风景 5" data-src="https://up.enterdesk.com/edpic/27/99/63/2799637cb551d86b1d40ffd83ef82ddf.jpg" alt="" />
            <br />
        </div>
    </div>
</div>
<script type="application/javascript">
    jQuery.vendor('jquery.powerImage', {
        callback:function(){
            $("#pic_container").powerImage({
                selector: 'data-src',
                width: '100%',
                zoom: true
            });
        }
    });
</script>