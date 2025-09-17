<include file="Public:header"/>
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
    .btn{
        padding: 2px 6px;
        border-radius: 5px;
        outline: none;
    }
    .grid-view .pager{
        margin: -5px 0 0 0;
        text-align: right;
    }
    table {
        font-size:12px !important;
    }
</style>
<div class="page-content" style="background-color: white;">

    <div class="form-group" >
        <form action="{pigcms{:U('House/choice_village')}" method="get" >
            <input type="hidden" name="c" value="House"/>
            <input type="hidden" name="a" value="choice_village"/>
            <input type="hidden" name="news_id" value="{pigcms{$_GET['news_id']}"/>
            <div id="choose_street" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}" style="display:inline"></div>
            <input type="text" name="keyword" class="input fl" value="{pigcms{$_GET['keyword']}" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称"/>
            &nbsp;&nbsp;
            <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
            <a class="btn btn-success" onclick="location.href='{pigcms{:U('House/choice_village')}&news_id={pigcms{$_GET['news_id']}'">重置</a>
            <div style="margin-top: 10px">
                <span  class="btn btn-success" onclick="chooseFloor()">绑定<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></span>
            </div>
        </form>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div id="shopList" class="grid-view">
                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                    <thead>
                    <tr>
                        <th width="5%"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>ID</th>
                        <th ><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称</th>
                        <th ><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>地址</th>
                        <th width="8%">操作</th>
                    </tr>
                    </thead>
                    <tbody class="class-checkbox">
                    <if condition="$list">
                        <volist name="list" id="vo" key="key">
                    <td><div class="tagDiv">{pigcms{$vo.village_id}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.village_name}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.village_address}</div></td>
                    <td>
                        <div class="tagDiv" style="color: #2681f3;cursor:pointer;">
                            <button class="js-choose" data-id="{pigcms{$vo['village_id']}">移除</button>
                        </div>
                    </td>
                    </tr>
                    </volist>
                    <else/>
                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                    </if>
                    </tbody>

                </table>
                <if condition="$list">
                    <div class="js-confirm-choose" style="margin-bottom: 10px">
                        <input type="button" class="btn-primary" value="关闭">
                    </div>
                    <div style="text-align: right">
                        {pigcms{$pagebar}
                    </div>

                </if>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">

    $("#select_all").click(function () {
        $("input[name='is_check']").each(function () {
            this.checked=!this.checked;
        });
    });
    var news_id = "{pigcms{$_GET['news_id']}";

    function chooseFloor() {
        var url = "{pigcms{:U('House/bind_village')}";
        var href = url + '&news_id='+news_id;
        layer.open({
            type: 2,
            title:'绑定'+"<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>",
            content: href,
            area: ['90%', '95%'],
            maxmin: false
        });
    }
    var choose_arr = [];
    var page = "{pigcms{$_GET['page']}";
    var is_set = true;
    if (page) {
        selecteds = localStorage.getItem('choose_floor');
        is_set = false;
    } else {
        var selecteds = "{pigcms{$selecteds}";
        is_set = true;
    }
    if (selecteds) {
        var selectedsArr = selecteds.split(",");
        if (selectedsArr) {
            for(var i in selectedsArr) {
                if (selectedsArr[i] && selectedsArr[i]!='') {
                    choose_arr[selectedsArr[i]] = selectedsArr[i];
                }
            }
        }
    }

    $('.js-choose').click(function () {
        var id = $(this).data('id');

        $.post("{pigcms{:U('House/remove_bind_village')}",{'notice_id':id},function(result){
            layer.msg(result.info);
            if(result.status == 1){
                setTimeout(function () {window.location.reload() }, 1000);

            }
        },'json');
    });

    $('.js-confirm-choose').click(function () {
        window.parent.close_all_page(choose_arr);


    });

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<include file="Publics:footer"/>