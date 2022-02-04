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
            <img title="风景 1" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_uIN_N3AApOcJMI5RwAALIxgAOlSwACk6I362.jpg" alt="" />
            <br />
            <img title="风景 2" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_qIWw7HAAYESY7XmCcAALIxQPiaFIABgRh602.jpg" alt="" />
            <br />
            <img title="风景 3" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJ1bKy_qIe0rEABHa6MR1maYAALIxQPobLMAEdsA496.jpg" alt="" />
            <br />
            <img title="风景 4" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g3/M09/0B/0F/Cg-4V1Ru7QOIRV1bAAZWxhKDGCYAARnZwA9PSQABlbe088.jpg" alt="" />
            <br />
            <img title="风景 5" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJlbKy_SIU9ZYAAhhFsfDzXkAALIxQBorLAACGEu653.jpg" alt="" />
            <br />
            <img title="风景 6" data-src="https://desk-fd.zol-img.com.cn/t_s1024x768c5/g5/M00/02/07/ChMkJlbKy_SIHIAsAAYw0WmXJp8AALIxQB2GzYABjDp786.jpg" alt="" />
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