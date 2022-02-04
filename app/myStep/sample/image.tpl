<div class="card w-100 mb-5 mb-sm-2">
    <div class="card-body text-center">
        <div id="pic_container" style="display:none;">
            <img title="风景 1" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_uIN_N3AApOcJMI5RwAALIxgAOlSwACk6I362.jpg" alt="" />
            <img title="风景 2" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_qIWw7HAAYESY7XmCcAALIxQPiaFIABgRh602.jpg" alt="" />
            <img title="风景 3" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_qIe0rEABHa6MR1maYAALIxQPobLMAEdsA496.jpg" alt="" />
            <img title="风景 4" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g3/M09/0B/0F/Cg-4V1Ru7QOIRV1bAAZWxhKDGCYAARnZwA9PSQABlbe088.jpg" alt="" />
            <img title="风景 5" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJlbKy_SIU9ZYAAhhFsfDzXkAALIxQBorLAACGEu653.jpg" alt="" />
            <img title="风景 6" src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJlbKy_SIHIAsAAYw0WmXJp8AALIxQB2GzYABjDp786.jpg" alt="" />
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