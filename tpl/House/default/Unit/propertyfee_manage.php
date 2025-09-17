<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}">物业管理</a>
            </li>
            <li class="active">收费标准列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form method="get" action="{pigcms{:U('Unit/propertyfee_manage')}" id="find-form">
                    <input type="hidden" name="c" value="Unit"/>
                    <input type="hidden" name="a" value="propertyfee_manage"/>
                    <input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
                    房间:
                    <select class=""  name="single_id" id="single" style="height:42px;">
                        <option value="">请选择楼栋</option>
                        <volist name='single_list' id='single_data'>
                            <option <if condition="$_GET['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                        </volist>
                    </select>
                    <select class=""  name="floor_id" id="floor" style="height:42px;">
                        <option <if condition="$_GET['floor_id'] eq '0'">selected="selected"</if> value="0">请选择单元</option>
                    </select>
                    <select class=""  name="layer_id" id="layer" style="height:42px;">
                        <option <if condition="$_GET['layer_id'] eq '0'">selected="selected"</if> value="0">请选择楼层</option>
                    </select>
                    <select class=""  name="vacancy_id" id="vacancy" style="height:42px;">
                        <option <if condition="$_GET['vacancy_id'] eq '0'">selected="selected"</if> value="0">请选择房间</option>
                    </select>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查找业主" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Unit/propertyfee_manage',array(\'id\'=>$_GET[\'id\']))}'">重置</a>
                </form>
            </div>
            <if condition="in_array(29,$house_session['menus'])">
                <button class="btn btn-success" onclick="importAdd()">添加房间</button>
                <else/>
                <button class="btn btn-success disabled" disabled="disabled">添加收费标准</button>
            </if>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="20%">楼号</th>
                                <th width="20%">单元名称</th>
                                <th width="20%">层号</th>
                                <th width="10%">房间号</th>
                                <th width="10%">房屋面积</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list['list']">
                                <volist name="list['list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.single_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.floor_name} </div></td>
                            <td><div class="tagDiv">{pigcms{$vo.layer_name} </div></td>
                            <td><div class="tagDiv">{pigcms{$vo.room} </div></td>
                            <td><div class="tagDiv">{pigcms{$vo.housesize} </div></td>
                            <td class="button-column">
                                <if condition="in_array(31,$house_session['menus'])">
                                    <a style="width: 60px;" class="label label-sm label-info" title="移除" href="javascript:void(0)" onclick="relieve({pigcms{$vo.pigcms_id})">移除</a>
                                </if>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有任何房间。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>

    function relieve(pigcms_id){
        $.post("{pigcms{:U('Unit/propertyfee_relieve')}", {'pigcms_id':pigcms_id}, function(data) {
            data = JSON.parse(data);
            if(data.status == 1){
                alert('解绑成功');
                window.location.reload();
            }
        });
    }
    var cat_id = $('#single').val();
    get_son_category(cat_id);
    var floor_id = "{pigcms{$_GET['floor_id']}";
    console.log("============",floor_id);
    get_new_layer(floor_id);

    var layer_id = "{pigcms{$_GET['layer_id']}";
    console.log("============",layer_id);
    get_vacancy(layer_id)
    $('#single').change(function(){

        var cat_id = $('#single').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
        get_son_category(cat_id);

    });

    function get_son_category(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_floor')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择单元</option>';
            var htmls = '<option value="0">请选择房间</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){

                    if (cat_list[i]['floor_id'] == "{pigcms{$_GET['floor_id']}" ) {
                        console.log(cat_list[i]['floor_id'])
                        console.log("{pigcms{$_GET['floor_id']}")

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
            }
        },'json');
    }


    $('#floor').change(function(){

        var cat_id = $('#floor').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
//		get_vacancy(cat_id);
        get_new_layer(cat_id);

    });

    function get_vacancy(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择房间</option>';
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

        console.log(cat_id)
        if(!cat_id){
            return;
        }
        get_vacancy(cat_id);

    });

    function get_new_layer(cat_id){
        var url = "{pigcms{:U('Service/get_new_layer')}";
        $.post(url,{'cat_id':cat_id},function(result){
            console.log("result")
            console.log(result)
            var shtml = '<option value="0">请选择房间</option>';
            var html = '<option value="0">请选择楼层</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){
                    if (cat_list[i]['id'] == "{pigcms{$_GET['layer_id']}" ) {
                        html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['layer_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
                    }
                }
                $('#layer').html(html);
                $('#vacancy').html(shtml);
            } else {
                $("#layer").html(html);
                $('#vacancy').html(shtml);
            }
        },'json');
    }
    function importAdd(){
        window.location.href = "{pigcms{:U('propertyfee_addroom',array('id'=>$_GET['id']))}";
    }
    function importUserDetail(){
        window.location.href = "{pigcms{:U('User/detail_import')}";
    }
</script>
<include file="Public:footer"/>

