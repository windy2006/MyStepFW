    <div class="card-body mb-3" style="padding-bottom:0px;">
        <div class="card">
            <div class="card-header">查询条件</div>
            <div class="card-body">
                <div class="row form-group">
                    <label class="col-form-label col-sm-1" for="txt_search_name">名称</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" id="txt_search_name">
                    </div>
                    <label class="col-form-label col-sm-1" for="txt_search_comment">备注</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" id="txt_search_comment">
                    </div>
                    <div class="col-sm-4" style="text-align:left;">
                        <button type="button" id="btn_query" class="btn btn-primary">查询</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="toolbar" class="btn-group">
            <button id="btn_add" type="button" class="btn btn-secondary">
                <span class="glyphicon glyphicon-plus"></span>新增
            </button>
            <button id="btn_edit" type="button" class="btn btn-secondary" disabled>
                <span class="glyphicon glyphicon-pencil"></span>修改
            </button>
            <button id="btn_delete" type="button" class="btn btn-secondary" disabled>
                <span class="glyphicon glyphicon-remove"></span>删除
            </button>
            <button id="btn_filter" type="button" class="btn btn-secondary">
                <span class="glyphicon glyphicon-filter"></span>筛选
            </button>
        </div>
        <table id="tb_list" class="table-sm w-100"
               data-sort-class="table-active"
               data-mobile-responsive="true" data-check-on-init="true"
               data-use-row-attr-func="true" data-reorderable-rows="true"
               data-reorderable-columns="true"
               data-resizable="true"
               data-sticky-header="true" data-thead-classes="thead-light" data-sticky-header-offset-y="50"
               data-advanced-search="true" data-id-table="advancedTable"
        >
            <thead class="thead-light">
            <tr>
                <th></th>
                <th data-field="pid" data-sortable="true" data-filter-control="select">父栏目</th>
                <th data-field="name" data-sortable="true" data-filter-control="input">名称</th>
                <th data-field="path" data-sortable="true" data-filter-control="input">路径</th>
                <th data-field="web_id" data-sortable="true" data-filter-control="select">所属网站</th>
                <th data-field="order" data-sortable="true" data-filter-control="select">排序</th>
                <th data-field="comment" data-sortable="true" data-filter-control="input">说明</th>
                <th>操作</th>
            </tr>
            </thead>
        </table>
    </div>
    <div class="progress" style="height:5px;">
        <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
    </div>
    <div id="info" class="text-muted small"></div>

    <form id="modalForm" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="glyphicon glyphicon-hdd"></span> <span class="txt">数据编辑</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label">项目</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" placeholder="值">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary" ><span class="glyphicon glyphicon-floppy-disk"></span> <span class="txt">更新</span></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> 关闭</button>
                </div>
            </div>
        </div>
    </form>


<script type="application/javascript">
    $(function(){
        jQuery.vendor('bootstrap-table', {
            add_css:true,
            name_fix:true,
            callback:function(){
                $.setJS([
                    'vendor/bootstrap-table/locale/bootstrap-table-zh-CN.min.js',

                    'vendor/bootstrap-table/extensions/export/bootstrap-table-export.min.js',
                    'vendor/bootstrap-table/extensions/export/tableExport.js',

                    'vendor/bootstrap-table/extensions/mobile/bootstrap-table-mobile.min.js',

                    'vendor/bootstrap-table/extensions/reorder-rows/jquery.tablednd.js',
                    'vendor/bootstrap-table/extensions/reorder-rows/bootstrap-table-reorder-rows.min.js',

                    //'vendor/bootstrap-table/extensions/reorder-columns/jquery.dragtable.js',
                    //'vendor/bootstrap-table/extensions/reorder-columns/bootstrap-table-reorder-columns.min.js',

                    'vendor/bootstrap-table/extensions/filter-control/bootstrap-table-filter-control.min.js',

                    'vendor/bootstrap-table/extensions/resizable/jquery.resizableColumns.js',
                    'vendor/bootstrap-table/extensions/resizable/bootstrap-table-resizable.min.js',

                    'vendor/bootstrap-table/extensions/sticky-header/bootstrap-table-sticky-header.min.js',

                    'vendor/bootstrap-table/extensions/toolbar/bootstrap-table-toolbar.min.js',
                ], true, function(){
                    $.setCSS([
                        "vendor/bootstrap-table/extensions/reorder-rows/bootstrap-table-reorder-rows.min.css",
                        //"vendor/bootstrap-table/extensions/reorder-columns/dragtable.css",
                        "vendor/bootstrap-table/extensions/resizable/jquery.resizableColumns.css",
                        "vendor/bootstrap-table/extensions/sticky-header/bootstrap-table-sticky-header.css"
                    ]);
                    setTbl('#tb_list');
                }, function(num_done, num_total, script) {
                    let obj = $('#progress_bar');
                    obj.width(obj.parent().width() * Math.ceil(num_done/num_total));
                    $('#info').html('脚本 ' + script + ' 已载入！');
                    if(num_done===num_total) {
                        $('#info').html('表格数据处理中。。。');
                        obj.parent().remove();
                    }
                });
            }
        });
    });
    function setTbl(tbl_idx) {
        $tbl = $(tbl_idx);
        $tbl.bootstrapTable({
            url: '<!--url_prefix-->api/myStep/data/&tbl=cms_admin_cat',         //表格数据
            dataType : "json",                     //从服务端接收数据类型定义。
            contentType : "application/json",   //请求体类型定义。
            method: 'post',                     //请求方式（*）
            toolbar: '#toolbar',                //工具按钮所在容器
            striped: true,                      //是否显示行间隔色（似乎没用？）
            cache: false,                       //是否使用缓存，默认为true，所以一般情况下需要设置一下这个属性（*）
            pagination: true,                   //是否显示分页（*）
            sortable: true,                     //是否启用排序（必需首先定义表头）
            sortOrder: "asc",                   //排序方式
            sidePagination: "client",           //分页方式：client客户端分页，server服务端分页（*）
            pageNumber:1,                       //初始化加载第一页，默认第一页
            pageSize: 10,                       //每页的记录行数（*）
            pageList: [10, 15, 20,25],          //可供选择的每页的行数（*）
            search: true,                       //是否显示表格搜索
            strictSearch: false,                //设置为 true启用 全匹配搜索，否则为模糊搜索
            minimumCountColumns: 2,             //最少允许的列数
            clickToSelect: true,                //是否启用点击选中行
            smartDisplay: true,                    //自动设置页面显示条数
            //height: 500,                      //行高，如果没有设置height属性，表格自动根据记录条数觉得表格高度
            uniqueId: "id",                     //每一行的唯一标识，一般为主键列
            showColumns: true,                  //是否显示所有的列
            showRefresh: true,                  //是否显示刷新按钮
            showToggle:true,                    //是否显示详细视图和列表视图的切换按钮
            cardView: false,                    //是否显示详细视图
            detailView: true,                   //是否当前记录详细内容
            showExport: true,                   //是否显示导出
            showFullscreen: true,                //全屏显示
            showPaginationSwitch: true,            //切换分页和全部
            exportDataType: "all",              //'basic', 'all', 'selected'.
            columns: [{
                checkbox: true
            }, {
                field: 'pid',
                title: '父栏目',
                formatter : function (value, row, index) {
                    return value === 0 ? "顶级" : value;
                }
            }, {
                field: 'name',
                title: '名称'
            }, {
                field: 'path',
                title: '路径'
            }, {
                field: 'web_id',
                title: '所属网站'
            }, {
                field: 'order',
                title: '排序'
            }, {
                field: 'comment',
                title: '说明'
            }, {
                field : 'id',
                title : '操作',
                width : '120px',
                align : 'center',
                sortable: false,
                formatter : function (value, row, index) {
                    return '<button type="del" id="'+row.id+'">删除</button> <button type="edit" id="'+row.id+'">修改</button>';
                },
                events: {
                    'click button[type=edit]': function (e, value, row, index) {
                        showEdit(row);
                    },
                    'click button[type=del]': function (e, value, row, index) {
                        if(confirm('是否确认删除？')) {
                            $tbl.bootstrapTable('remove', {
                                field: 'id',
                                values: [row.id]
                            })
                        }
                    }
                }
            }],
            rowStyle: function(row, index) {
                let classes = ['', 'primary', 'secondary','success','danger','warning','info', 'active'];
                return {
                    'classes' : 'table-'+classes[rndNum(0, classes.length-1)],
                    'css': {
                        'font-weight': index%2?'bold':'normal'
                    }
                };
            },
            queryParams: function (params) {
                //生成服务器交互参数
                return {
                    limit: params.limit,
                    offset: params.offset,
                    order: params.order,
                    ordername: params.sort,
                    keyword: $('.bootstrap-table').find('.search input').val(),
                    field: {
                        name: $("#txt_search_name").val(),
                        comment: $("#txt_search_comment").val()
                    }
                };
            },
            detailFormatter: function(index, row) {
                //格式化详细数据
                let html = []
                $.each(row, function (key, value) {
                    if(isNaN(key)) html.push('<p><b>' + key + ':</b> ' + value + '</p>')
                })
                return html.join('')
            },
            responseHandler: function(res) {
                //预处理载入数据
                $.each(res.rows, function (i, row) {
                    //console.log(row.id);
                });
                return res;
            }
        }).on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function() {
            $('#btn_delete').prop('disabled', !$tbl.bootstrapTable('getSelections').length);
            $('#btn_edit').prop('disabled', $tbl.bootstrapTable('getSelections').length!==1);
        }).getIdSelections = function() {
            return $.map($(this).bootstrapTable('getSelections'), function (row) {
                return row.id
            })
        };

        $tbl.on('all.bs.table', function (e, name, args) {
            console.log(name, args)
        });

        $('#btn_query').click(function() {
            $tbl.bootstrapTable('refresh', {
                url : '<!--url_prefix-->api/myStep/data/?tbl=cms_admin_cat'
            });
        });

        $('#btn_delete').click(function() {
            if(confirm('是否确认删除？')) {
                $tbl.bootstrapTable('remove', {
                    field: 'id',
                    values: $tbl.getIdSelections()
                });
                $('#btn_delete').prop('disabled', true);
                $('#btn_edit').prop('disabled', true);
            }
        });

        $('#btn_edit').click(function() {
            let data = $tbl.bootstrapTable('getSelections')[0];
            showEdit(data);
        });

        $('#btn_add').click(function() {
            showEdit();
        });

        $('#btn_filter').click(function() {
            let flag = $tbl.bootstrapTable('getOptions')['filterControl'];
            $tbl.bootstrapTable('refreshOptions', {
                filterControl: !flag,
                filterShowClear: !flag
            })
        });

        function showEdit(data) {
            let cols = $tbl.bootstrapTable('getOptions').columns[0];
            let uid = $tbl.bootstrapTable('getOptions').uniqueId;
            let tpl = '' +
                '<div class="form-group row">\n' +
                '  <label class="col-sm-4 col-form-label">项目</label>\n' +
                '  <div class="col-sm-8">\n' +
                '    <input type="text" class="form-control" placeholder="值" value="">\n' +
                '  </div>\n' +
                '</div>';
            let obj = null;
            let add_mode = (data==null);
            $('#modalForm').find('.modal-title span.txt').text(add_mode?'添加记录':'修改数据');
            $('#modalForm').find('.modal-footer span.txt').text(add_mode?'添加':'更新');
            $('#modalForm').find('.modal-body').empty();
            for(let i=0,m=cols.length;i<m;i++) {
                if(cols[i].field=='') continue;
                if(cols[i].field==uid) {
                    obj = $('<input type="hidden" name="'+uid+'" value="'+(add_mode ? 0 : data[cols[i].field])+'" />');
                } else {
                    obj = $(tpl);
                    obj.find('.col-form-label').html(cols[i].title);
                    obj.find('.form-control').attr('name', cols[i].field).attr('placeholder',cols[i].title)
                    if(!add_mode) obj.find('.form-control').val(data[cols[i].field]);
                }
                $('#modalForm').find('.modal-body').append(obj);
                obj = null;
            }
            $('#modalForm').unbind().submit(function(){
                let data = $(this).serializeArray();
                let record = {};
                for(let i=0,m=data.length;i<m;i++) {
                    record[data[i].name] = data[i].value;
                }
                if(add_mode) {
                    $tbl.bootstrapTable('insertRow', {
                        index: 0,
                        row: record
                    })
                } else {
                    data = {};
                    data[uid] = record[uid];
                    delete record[uid];
                    data.row = record;
                    $tbl.bootstrapTable('updateByUniqueId', data);
                }
                $('#modalForm').modal('hide');
                //$tbl.bootstrapTable('refresh');
                return false;
            });
            $('#modalForm').modal('show');
        }
        $('#info').remove();
    };
</script>