<div class="card w-100 mb-5 mb-sm-2">
	<div class="card-body text-center">
        <div id="pic_container" style="display:none;">
            <img title="风景 1" src="https://up.enterdesk.com/edpic/9c/14/8c/9c148c3484d58df3a64cc39929e46966.jpg" alt="" />
            <img title="风景 2" src="https://up.enterdesk.com/edpic/bb/12/64/bb1264aae179a8a13d154563ad381d93.jpg" alt="" />
            <img title="风景 3" src="https://up.enterdesk.com/edpic/bf/f2/5c/bff25c52adda065475059b31c2214228.jpg" alt="" />
            <img title="风景 4" src="https://up.enterdesk.com/edpic/9d/1a/cc/9d1acc784d8faf03605ef38a6eaa5850.jpg" alt="" />
            <img title="风景 5" src="https://up.enterdesk.com/edpic/27/99/63/2799637cb551d86b1d40ffd83ef82ddf.jpg" alt="" />
        </div>
        <div id="showImage"></div>
	</div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_1").imageWall({"size_h":1,"size_v":1,"img_width":320,"img_height":240,"speed":1000})</div>
    <div id="logo_container_1">
        <img src="http://cccfna.org.cn/_test/logo/logo_tj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_bj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_sh.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_si.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_kt.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_df.png" />
    </div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_2").imageWall({"size_h":8,"size_v":1,"interval":5})</div>
    <div id="logo_container_2">
        <img src="http://cccfna.org.cn/_test/logo/logo_tj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_bj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_sh.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_si.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_kt.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_df.png" />
    </div>
    <div class="font-weight-bold p-3 mt-5">$("#logo_container_3").imageWall({"size_h":5,"size_v":5,"speed":200})</div>
    <div id="logo_container_3">
        <img src="http://cccfna.org.cn/_test/logo/logo_tj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_bj.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_sh.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_si.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_kt.png" />
        <img src="http://cccfna.org.cn/_test/logo/logo_df.png" />
    </div>
</div>
<script language="JavaScript">
    jQuery.vendor('jquery.showimage', {
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