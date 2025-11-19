<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .pager li.next>a, .pager li.prev>a{
        padding: 7px 14px 8px;
    }
    .js-confirm-choose {
        text-align: -webkit-center;
    }
    .js-confirm-choose .btn-primary {
        background-color: #428bca!important;
        color: #ffffff;
        padding: 5px 25px !important;
        border-radius: 5px;
    }
    .js-all-choose {
        color: #428bca;
        float: right;
        margin-right: 52px;
        margin-bottom: 3px;
    }
    .btn-primary{
        color: #fff !important;
    }
    .js-all-choose .btn-primary{
        background-color: #428bca !important;
        float: right;
        margin-right: 52px;
        margin-bottom: 3px;
    }
</style>
<div class="page-content" style="background-color: white;">
    <div class="row">
        <div class="col-xs-12">
            <div id="shopList" class="grid-view">
                <button class="js-all-choose">全部选取</button>
                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                    <thead>
                    <tr>

                        <th width="5%">选择房间</th>
                        <th width="5%">排序</th>
                        <!-- <th width="5%">楼层编号</th>-->
                        <th width="8%">物业编号</th>
                        <th width="5%">楼号</th>
                        <th width="5%">单元名称</th>
                        <th width="5%">层号</th>
                        <th width="5%">房间号</th>
                        <th width="5%">房屋面积</th>

                        <th width="10%">操作</th>
                    </tr>
                    </thead>
                    <tbody class="class-checkbox">
                    <if condition="$result['list']">
                        <volist name="result['list']" id="vo">
                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
                                <!-- <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>-->
                                <td><div class="tagDiv">{pigcms{$vo.usernum}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.layer}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.room}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.housesize}</div></td>
                                 <td>
                                    <div class="tagDiv" style="color: #2681f3;cursor:pointer;">
                                        <php>if ($selectedArr && in_array($vo['pigcms_id'],$selectedArr)) {</php>
                                            <button class="js-choose btn-primary" data-id="{pigcms{$vo['pigcms_id']}" data-name="{pigcms{$vo['floor_name']}" data-layer="{pigcms{$vo['floor_layer']}">取消</button>
                                        <php>}else{</php>
                                            <button class="js-choose" data-id="{pigcms{$vo['pigcms_id']}">选取</button>
                                        <php>}</php>
                                    </div>
                                </td>
                             </tr>
                        </volist>
                    <else/>
                        <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                    </if>
                    </tbody>

                </table>
                <if condition="$result['list']">
                    {pigcms{$result.pagebar}
                    <div class="js-confirm-choose">
                        <input type="button" class="btn-primary" value="确定选择">
                    </div>
                </if>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var project_id = "{pigcms{$_GET['project_id']}";
    var cate_id = "{pigcms{$_GET['cate_id']}";


    var choose_arr = [];
    var page = "{pigcms{$_GET['page']}";
    console.log('page', page);
    var is_set = true;
    if (page) {
        selecteds = localStorage.getItem('choose_floor');
        console.log('selecteds1', selecteds);
        is_set = false;
    } else {
        var selecteds = "{pigcms{$selecteds}";
        is_set = true;
    }
    if (selecteds) {
        var selectedsArr = selecteds.split(",");
        if (selectedsArr) {
            for(var i in selectedsArr) {
                if (selectedsArr[i]) {
                    choose_arr[selectedsArr[i]] = selectedsArr[i];
                }
            }
        }
    }

    $.each($('.js-choose'), function(i, item) {
        if (choose_arr[$(item).data('id')]) {
            $(item).addClass('btn-primary').html('{pigcms{:L_("取消")}');
            $('.js-all-choose').addClass('btn-primary').html('{pigcms{:L_("全部取消")}');
        } else {
            $('.js-all-choose').removeClass('btn-primary').html('{pigcms{:L_("全部选择")}');
        }
    });

    $('.js-all-choose').click(function () {
        if($(this).hasClass('btn-primary')){
            // 点击取消全部
            $.each($('.js-choose'), function(i, item) {
                var floor_id = $(item).data('id');
                choose_arr.splice(floor_id, 1);
                $(item).removeClass('btn-primary').html('{pigcms{:L_("选取")}');
                $.post("{pigcms{:U('MeterRead/add_room')}",{room_id:floor_id,project_id:project_id,cate_id:cate_id,type:2},function(res){

                },'json');
            });
            $('.js-all-choose').removeClass('btn-primary').html('{pigcms{:L_("全部选择")}');
        } else {
            // 点击选取全部
            $.each($('.js-choose'), function(i, item) {
                var floor_id = $(item).data('id');
                choose_arr[floor_id] = floor_id;
                $(item).addClass('btn-primary').html('{pigcms{:L_("取消")}');
                $.post("{pigcms{:U('MeterRead/add_room')}",{room_id:floor_id,project_id:project_id,cate_id:cate_id,type:1},function(res){

                },'json');
            });
            $('.js-all-choose').addClass('btn-primary').html('{pigcms{:L_("全部取消")}');
        }
    });

    $('.js-choose').click(function () {
        var floor_id = $(this).data('id');
        if($(this).hasClass('btn-primary')){
            choose_arr.splice(floor_id, 1);
            $(this).removeClass('btn-primary').html('{pigcms{:L_("选取")}');
            console.log("-----")
            $.post("{pigcms{:U('MeterRead/add_room')}",{room_id:floor_id,project_id:project_id,cate_id:cate_id,type:2},function(res){

            },'json');
        }else{
            choose_arr[floor_id] = floor_id;
            $(this).addClass('btn-primary').html('{pigcms{:L_("取消")}');
            console.log("++++++")
            $.post("{pigcms{:U('MeterRead/add_room')}",{room_id:floor_id,project_id:project_id,cate_id:cate_id,type:1},function(res){

            },'json');
        }
        console.log('guojie')
        console.log(choose_arr)
        localStorage.setItem('choose_floor', choose_arr);
    });

    $('.js-confirm-choose').click(function () {



        window.parent.close_all_page(choose_arr);
    });

    if (is_set) {
        setTimeout(function () {
            localStorage.setItem('choose_floor', choose_arr);
        },80);
    }
</script>
<include file="Publics:footer"/>