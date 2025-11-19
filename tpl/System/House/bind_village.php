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
</style>
<div class="page-content" style="background-color: white;">

    <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
        <form action="{pigcms{:U('House/bind_village')}" method="get" >
            <input type="hidden" name="c" value="House"/>
            <input type="hidden" name="a" value="bind_village"/>
            <input type="hidden" name="news_id" value="{pigcms{$_GET['news_id']}"/>
            <div id="choose_street" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}" style="display:inline"></div>
            <input type="text" name="keyword" class="input fl" value="{pigcms{$_GET['keyword']}" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称"/>
            &nbsp;&nbsp;
            <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
            <a class="btn btn-success" onclick="location.href='{pigcms{:U('House/bind_village')}&news_id={pigcms{$_GET['news_id']}'">重置</a>
        </form>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div id="shopList" class="grid-view">
                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                    <thead>
                    <tr>
                        <th width="8%"><input type="checkbox" id="select_all">全选</th>
                        <th ><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称</th>
                        <th ><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>地址</th>
                    </tr>
                    </thead>
                    <tbody class="class-checkbox">
                    <if condition="$list">
                        <volist name="list" id="vo" key="key">
                    <td>
                        <input class="check_{pigcms{$vo['village_id']}" name="is_check" value="{pigcms{$vo.village_id}" type="checkbox">
                    </td>
                    <td><div class="tagDiv">{pigcms{$vo.village_name}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.village_address}</div></td>
                    </tr>
                    </volist>
                    <else/>
                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                    </if>
                    </tbody>

                </table>
                <if condition="$list">
                    <div class="js-confirm-choose" style="text-align: left;margin-bottom: 10px">
                        <input type="button" class="btn-primary" value="确定绑定">
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

        if($(".check_"+id).is(':checked')){
            $(".check_"+id).prop('checked',false);
            $(this).removeClass('btn-primary').html('{pigcms{:L_("取消")}');
        }else{
            $(".check_"+id).prop('checked',true);
            $(this).addClass('btn-primary').html('{pigcms{:L_("绑定")}');
        }

    });

    $('.js-confirm-choose').click(function () {
        // console.log('js-confirm-choose', choose_arr);
        // window.parent.close_all_page(choose_arr);
        var mark_id = '';
        $("input[name='is_check']").each(function () {
            if($(this).attr("checked")){
                mark_id += $(this).val()+',';
            }
        });
        mark_id=mark_id.substring(0,mark_id.length-1);
        if(!mark_id){
            layer.msg('请选择您要的数据。');
            return false;
        }

        $.post("{pigcms{:U('House/bind_village')}",{'mark_id':mark_id,'news_id':"{pigcms{$_GET['news_id']}"},function(result){
            layer.msg(result.info);
            if(result.status == 1){
                setTimeout(function () {parent.location.reload(); }, 1000);

            }
        },'json');

    });

    if (is_set) {
        setTimeout(function () {
            localStorage.setItem('choose_floor', choose_arr);
        },80);
    }
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<include file="Publics:footer"/>