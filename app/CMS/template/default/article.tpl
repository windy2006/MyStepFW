<div class="container-fluid px-4 px-md-5 mt-3 mb-5">
	<div class="row">
		<div class="col-12 col-lg-8 p-0">
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb p-0 d-flex">
					<li class="breadcrumb-item p-2"><a href="<!--url_prefix_app-->"><span class="glyphicon glyphicon-home"></span> 首页</a></li>
					<!--loop:start key="cat_list"-->
					<li class="breadcrumb-item p-2"><a href="<!--cat_list_link-->"><!--cat_list_name--></a></li>
					<!--loop:end-->
					<li class="breadcrumb-item p-2 flex-grow-1">文章内容</li>
				</ol>
			</nav>
			<div class="mb-3">
				<img class="img-fluid img-thumbnail ad" src="http://placehold.jp/630x80.png" alt="" />
			</div>
			<div class="mb-3">
				<div class="text-center">
					<h4><!--record_subject--></h4>
					<h7 class="text-secondary border-top">来源：<!--record_original--> &nbsp; | &nbsp; 时间：<!--record_add_date--> &nbsp; | &nbsp; 浏览：<!--record_views--></h7>
				</div>
				<div class="text-center my-3 <!--multi_page-->">
					<select onchange="location.href=this.value">
						<!--loop:start key="sub_title"-->
						<option value="<!--sub_title_link-->" <!--sub_title_selected-->><!--sub_title_name--></option>
						<!--loop:end-->
					</select>
				</div>
				<div id="content" class="py-3">
					<img src="<!--record_image-->" class="news_img d-none d-md-block" />
					<!--record_content-->
				</div>
			</div>
			<div class="row px-3">
				文章标签：
				<!--loop:start key="tag"-->
				<a class="mr-3" href="<!--tag_link-->"><!--tag_name--></a>
				<!--loop:end-->
			</div>
			<nav class="mb-3 mx-auto <!--multi_page-->">
				<ul class="pagination pagination-sm justify-content-center">
					<!--loop:start key="page"-->
					<li class="page-item <!--page_active-->"><a class="page-link" href="<!--page_link-->"><!--page_no--></a></li>
					<!--loop:end-->
				</ul>
			</nav>
		</div>
		<div class="col-12 col-lg-4 p-0 pl-lg-3">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title m-0">最新更新</h5>
				</div>
				<div class="card-body">
					<!--news limit="10" loop="10" order="news_id desc"-->
				</div>
			</div>
			<div class="mb-3">
				<img class="img-fluid img-thumbnail ad" src="http://placehold.jp/300x80.png" alt="" />
			</div>
		</div>
	</div>
</div>