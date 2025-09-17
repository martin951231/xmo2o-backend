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

    <div class="form-group" >
        <form action="{pigcms{:U('News/choice_object')}" method="get" >
            <input type="hidden" name="c" value="News"/>
            <input type="hidden" name="a" value="choice_object"/>
            <input type="hidden" name="news_id" value="{pigcms{$_GET['news_id']}"/>
            选择<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>:
            <select class=""  name="single_id" id="single" style="height:42px;">
                <option value="">请选择<?php echo (D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋') ?></option>
                <volist name='single_list' id='single_data'>
                    <option <if condition="$_GET['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                </volist>
            </select>
            <select class=""  name="floor_id" id="floor" style="height:42px;">
                <option <if condition="$_GET['floor_id'] eq '0'">selected="selected"</if> value="0">请选择<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元') ?></option>
            </select>
            <select class=""  name="layer_id" id="layer" style="height:42px;">
                <option <if condition="$_GET['layer_id'] eq '0'">selected="selected"</if> value="0">c</option>
            </select>
            <select class=""  name="vacancy_id" id="vacancy" style="height:42px;">
                <option <if condition="$_GET['vacancy_id'] eq '0'">selected="selected"</if> value="0"><?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?></option>
            </select>
            &nbsp;&nbsp;
            <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
           <div style="margin-top: 10px">
               <span  class="btn btn-success" onclick="chooseFloor()">绑定<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?></span>
           </div>
        </form>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div id="shopList" class="grid-view">
                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                    <thead>
                    <tr>
                        <th width="5%">编号</th>
                        <th width="10%"><?php echo (D('House_village')->getHouseAlias('single_name',$village_id)?:'楼') ?>号</th>
                        <th width="10%"><?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元') ?></th>
                        <th width="10%">楼层</th>
                        <th width="10%"><?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?></th>
                        <th width="5%">操作</th>
                    </tr>
                    </thead>
                    <tbody class="class-checkbox">
                    <if condition="$list">
                        <volist name="list" id="vo" key="key">
                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                    <td><div class="tagDiv">{pigcms{$key}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.layer}</div></td>
                    <td><div class="tagDiv">{pigcms{$vo.room}</div></td>
                    <td>
                        <div class="tagDiv" style="color: #2681f3;cursor:pointer;">
                            <button class="js-choose" data-id="{pigcms{$vo['notice_id']}">移除</button>
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
<script>
    var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
    var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
    var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
</script>
<script>

    var cat_id = $('#single').val();
    get_son_category(cat_id);

    var floor_id = "{pigcms{$_GET['floor_id']}";
    get_new_layer(floor_id);

    var layer_id = "{pigcms{$_GET['layer_id']}";
    // get_vacancy(layer_id)


    $('#single').change(function(){
        var cat_id = $('#single').val();
        if(!cat_id){
            return;
        }
        get_son_category(cat_id);
    });

    function get_son_category(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_floor')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元') ?>"+'</option>';
            var htmls = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>"+'</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){
                    if (cat_list[i]['floor_id'] == "{pigcms{$_GET['floor_id']}" ) {
                        html +='<option value="'+ cat_list[i]['floor_id'] +'" selected=selected>' + cat_list[i]['floor_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
                    }
                }
                $('#floor').html(html);

                $('#vacancy').html(htmls);
            } else {
                $("#floor").html(html);
                $('#vacancy').html(htmls);
                $('#layer').html('<option value="0">请选择楼层</option>');
            }
        },'json');
    }


    $('#floor').change(function(){
        var cat_id = $('#floor').val();
        if(!cat_id){
            return;
        }
//        get_vacancy(cat_id);
        get_new_layer(cat_id);

    });

    function get_vacancy(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>"+'</option>';
            if(result['status'] == 1){
                var cat_lists = result.cat_list;
                for(var j in cat_lists){

                    if (cat_lists[j]['pigcms_id'] == "{pigcms{$_GET['vacancy_id']}") {
                        html +='<option value="'+ cat_lists[j]['pigcms_id'] +'" selected=selected>' + cat_lists[j]['room'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_lists[j]['pigcms_id'] +'">' + cat_lists[j]['room'] + '</option>';
                    }
                }
                $('#vacancy').html(html);
            } else {
                $("#vacancy").html(html);
            }
        },'json');
    }

    $('#layer').change(function(){
        var cat_id = $('#layer').val();
        if(!cat_id){
            return;
        }
        get_vacancy(cat_id);
    });

    function get_new_layer(cat_id){
        var url = "{pigcms{:U('Service/get_new_layer')}";
        $.post(url,{'cat_id':cat_id},function(result){
            var shtml = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>"+'</option>';
            var html = '<option value="0">请选择楼层</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){
                    if (cat_list[i]['id'] == "{pigcms{$_GET['layer_id']}" ) {
                        html +='<option value="'+ cat_list[i]['id'] +'" selected="selected">' + cat_list[i]['layer_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
                    }
                }
                $('#layer').html(html);

                var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
                $.post(url,{'cat_id':"{pigcms{$_GET['layer_id']}",'type':1},function(result){
                    var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>"+'</option>';
                    if(result['status'] == 1){
                        var cat_lists = result.cat_list;
                        for(var j in cat_lists){

                            if (cat_lists[j]['pigcms_id'] == "{pigcms{$_GET['vacancy_id']}") {
                                html +='<option value="'+ cat_lists[j]['pigcms_id'] +'" selected=selected>' + cat_lists[j]['room'] + '</option>';
                            }else{
                                html +='<option value="'+ cat_lists[j]['pigcms_id'] +'">' + cat_lists[j]['room'] + '</option>';
                            }
                        }
                        $('#vacancy').html(html);
                    } else {
                        $("#vacancy").html(html);
                    }
                },'json');

            } else {
                $("#layer").html(html);
                $('#vacancy').html(shtml);
            }
        },'json');
    }
</script>
<script type="text/javascript">

    $("#select_all").click(function () {
        $("input[name='is_check']").each(function () {
            this.checked=!this.checked;
        });
    });
    var news_id = "{pigcms{$_GET['news_id']}";

    function chooseFloor() {
        localStorage.removeItem('choose_floor');
        var url = "{pigcms{:U('News/floor_list')}";
        // var href = url + '&selecteds='+selecteds;
        var href = url + '&news_id='+news_id;
        layer.open({
            type: 2,
            title:'绑定'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间') ?>",
            content: href,
            area: ['72%', '85%'],
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

        $.post("{pigcms{:U('News/choice_remove')}",{'notice_id':id},function(result){
            layer.msg(result.info);
            if(result.status == 1){
                setTimeout(function () {window.location.reload() }, 1000);

            }
        },'json');

        // if($(".check_"+id).is(':checked')){
        //     $(".check_"+id).prop('checked',false);
        //     $(this).removeClass('btn-primary').html('{pigcms{:L_("取消")}');
        // }else{
        //     $(".check_"+id).prop('checked',true);
        //     $(this).addClass('btn-primary').html('{pigcms{:L_("绑定")}');
        // }

    });

    $('.js-confirm-choose').click(function () {
        window.parent.close_all_page(choose_arr);


    });

    // if (is_set) {
    //     setTimeout(function () {
    //         localStorage.setItem('choose_floor', choose_arr);
    //     },80);
    // }
</script>
<include file="Publics:footer"/>