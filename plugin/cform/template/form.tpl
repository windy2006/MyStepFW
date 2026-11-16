<section>
    <h1><!--title|html--></h1>

    <form
        onsubmit="return typeof checkForm!=='function' || checkForm(this)"
        method="post"
        enctype="multipart/form-data"
        action="<!--action|html-->"
    >
        <!--hidden-->
        <!--fields-->

        <button type="submit" class="btn btn-primary">
            <!--submit|html-->
        </button>
        <a href="<!--back_url|html-->"><!--back_label|html--></a>
    </form>
</section>
