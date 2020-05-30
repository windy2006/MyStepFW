<div class="container-fluid px-4 px-md-5 mt-3 mb-5">
    <div class="row mt-3">
        <nav class="nav rounded-pill bg-light border w-100 p-2 mb-3">
            <marquee class="d-inline-block float-left news_marquee" behavior="scroll" scrollamount="5" onmouseover="this.stop()" onmouseout="this.start()">
                <!--news setop="1" limit="5" class="float-left mr-4" template="simple" condition='DATEDIFF(add_date, NOW())<50'-->
            </marquee>
        </nav>
    </div>
    <!--news image="1" setop="1" limit="5" template="carousel" class="mb-3"-->
    <div class="row">
        <div class="col-12 col-lg-8 p-0">
            <div class="row">
                <div class="news_card col-xs-12 col-md-6 mb-3">
                    <ul class="nav nav-tabs nav-hack">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="pill" href="#pills-1">栏目一</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#pills-2">栏目二</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#pills-3">栏目三</a>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0">
                        <div class="tab-pane fade show active" id="pills-1">
                            <!--news catalog="1" limit="5" loop="5" date="Y-m-d"-->
                        </div>
                        <div class="tab-pane fade" id="pills-2">
                            <!--news catalog="2" limit="5" loop="5" date="Y-m-d"-->
                        </div>
                        <div class="tab-pane fade" id="pills-3">
                            <!--news catalog="3" limit="5" loop="5" date="Y-m-d"-->
                        </div>
                    </div>
                </div>
                <div class="news_card col-xs-12 col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header p-0 border-0">
                            <ul class="nav nav-tabs">
                                <li class="nav-item p-0">
                                    <a class="nav-link active" data-toggle="pill" href="#pills-4">栏目四</a>
                                </li>
                                <li class="nav-item p-0">
                                    <a class="nav-link" data-toggle="pill" href="#pills-5">栏目五</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body tab-content border-0">
                            <div class="tab-pane fade show active" id="pills-4">
                                <!--news catalog="4" limit="5" loop="5" date="Y-m-d"-->
                            </div>
                            <div class="tab-pane fade" id="pills-5">
                                <!--news catalog="5" limit="5" loop="5" date="Y-m-d"-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--news image="1" catalog="6" class="row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4" limit="12" template="gallery"-->
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/630x80.png" alt="" />
            </div>
            <!--news image="1" catalog="7" limit="6" template="mixed" pos_img="3"-->
        </div>
        <div class="col-12 col-lg-4 p-0 pl-lg-3">
            <!--news catalog="8" limit="4" loop="4" template="show" pos_img="0"-->
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/300x80.png" alt="" />
            </div>
            <!--news catalog="9" limit="4" loop="4" template="show" pos_img="1"-->
            <div class="mb-3">
                <img class="img-fluid img-thumbnail ad" src="http://placehold.jp/300x80.png" alt="" />
            </div>
            <!--news catalog="8" limit="4" loop="4" template="show" pos_img="2"-->
            <!--tag limit="20" count="2"-->
        </div>
    </div>
    <div class="row">
        <!--link title="图片链接" type="image"-->
    </div>
    <div class="row">
        <!--link title="文字链接" type="all"-->
    </div>
</div>