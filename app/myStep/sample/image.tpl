<style>
.sample-image-wall { overflow-x: auto; max-width: 100%; }
.sample-image-wall img { object-fit: cover; }
</style>
<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-body text-center">
        <div id="pic_container" style="display:none;">
            <img title="山湖倒影" src="<!--sample_assets|html-->lake.jpg" alt="山湖倒影" />
            <img title="海岸椰林" src="<!--sample_assets|html-->coast.jpg" alt="海岸椰林" />
            <img title="林间光影" src="<!--sample_assets|html-->forest.jpg" alt="林间光影" />
            <img title="山林瀑布" src="<!--sample_assets|html-->waterfall.jpg" alt="山林瀑布" />
            <img title="沙丘风光" src="<!--sample_assets|html-->desert.jpg" alt="沙丘风光" />
            <img title="日落山谷" src="<!--sample_assets|html-->sunset.jpg" alt="日落山谷" />
        </div>
        <div id="showImage"></div>
    </div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_1").imageWall({"size_h":1,"size_v":1,"img_width":320,"img_height":240,"speed":1000})</div>
    <div class="sample-image-wall"><div id="logo_container_1">
        <img title="山湖倒影" src="<!--sample_assets|html-->lake.jpg" alt="山湖倒影" />
        <img title="海岸椰林" src="<!--sample_assets|html-->coast.jpg" alt="海岸椰林" />
        <img title="林间光影" src="<!--sample_assets|html-->forest.jpg" alt="林间光影" />
        <img title="山林瀑布" src="<!--sample_assets|html-->waterfall.jpg" alt="山林瀑布" />
        <img title="沙丘风光" src="<!--sample_assets|html-->desert.jpg" alt="沙丘风光" />
        <img title="日落山谷" src="<!--sample_assets|html-->sunset.jpg" alt="日落山谷" />
    </div></div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_2").imageWall({"size_h":8,"size_v":1,"interval":5})</div>
    <div class="sample-image-wall"><div id="logo_container_2">
        <img title="山湖倒影" src="<!--sample_assets|html-->lake.jpg" alt="山湖倒影" />
        <img title="海岸椰林" src="<!--sample_assets|html-->coast.jpg" alt="海岸椰林" />
        <img title="林间光影" src="<!--sample_assets|html-->forest.jpg" alt="林间光影" />
        <img title="山林瀑布" src="<!--sample_assets|html-->waterfall.jpg" alt="山林瀑布" />
        <img title="沙丘风光" src="<!--sample_assets|html-->desert.jpg" alt="沙丘风光" />
        <img title="日落山谷" src="<!--sample_assets|html-->sunset.jpg" alt="日落山谷" />
    </div></div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_3").imageWall({"size_h":5,"size_v":5,"speed":200})</div>
    <div class="sample-image-wall"><div id="logo_container_3">
        <img title="山湖倒影" src="<!--sample_assets|html-->lake.jpg" alt="山湖倒影" />
        <img title="海岸椰林" src="<!--sample_assets|html-->coast.jpg" alt="海岸椰林" />
        <img title="林间光影" src="<!--sample_assets|html-->forest.jpg" alt="林间光影" />
        <img title="山林瀑布" src="<!--sample_assets|html-->waterfall.jpg" alt="山林瀑布" />
        <img title="沙丘风光" src="<!--sample_assets|html-->desert.jpg" alt="沙丘风光" />
        <img title="日落山谷" src="<!--sample_assets|html-->sunset.jpg" alt="日落山谷" />
    </div></div>
</div>
<script type="application/javascript">
    jQuery.vendor('jquery.showImage', {
        add_css:true,
        callback:function(){
            $("#pic_container").showImage({
                "container_id":"showImage",
                "container_width":'100%',
                "thumb_width":120,
                "thumb_height":80,
                "interval":3
            });
            $("#logo_container_1").imageWall({"size_h":1,"size_v":1,"img_width":320,"img_height":240,"speed":1000});
            $("#logo_container_2").imageWall({"size_h":8,"size_v":1,"interval":5});
            $("#logo_container_3").imageWall({"size_h":5,"size_v":5,"speed":300});
        }
    });
</script>