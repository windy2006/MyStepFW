<style>
    #pic_container img {
        width: 100%;
        max-width: 100%;
        height: auto;
        margin-bottom: 10px;
        border-right: 1px gray solid;
        border-radius: 10px;
    }
</style>
<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-body text-center">
        <div id="pic_container">
            <img title="山湖倒影" data-src="<!--sample_assets|html-->lake.jpg" alt="山湖倒影" />
            <br />
            <img title="海岸椰林" data-src="<!--sample_assets|html-->coast.jpg" alt="海岸椰林" />
            <br />
            <img title="林间光影" data-src="<!--sample_assets|html-->forest.jpg" alt="林间光影" />
            <br />
            <img title="山林瀑布" data-src="<!--sample_assets|html-->waterfall.jpg" alt="山林瀑布" />
            <br />
            <img title="沙丘风光" data-src="<!--sample_assets|html-->desert.jpg" alt="沙丘风光" />
            <br />
            <img title="日落山谷" data-src="<!--sample_assets|html-->sunset.jpg" alt="日落山谷" />
            <br />
        </div>
    </div>
</div>
<script type="application/javascript">
    jQuery.vendor('jquery.powerImage', {
        callback:function(){
            $("#pic_container").powerImage({
                selector: 'data-src',
                image: '<!--sample_loading|html-->',
                width: '100%',
                zoom: true
            });
        }
    });
</script>