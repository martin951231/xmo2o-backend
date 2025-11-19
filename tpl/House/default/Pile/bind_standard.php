<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                <th width="5%">编号</th>
                                <th width="20%">设备名称</th>
                                <th width="20%">设备唯一编码</th>
                                <th width="10%">绑定状态</th>
                                <th class="button-column" width="20%" style="text-align: center">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$equipment_list">
                                <volist name="equipment_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td align="center"><input type="checkbox" <if condition="$vo.is_bind neq 1">disabled="true"</if> name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.id}" onclick="return Dcheckbox($(this));"></td>
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_num} </div></td>
                            <if condition="$vo.is_bind eq 1">
                                <td><div class="tagDiv">未绑定</div></td>
                                <td><div class="tagDiv" style="text-align: center"><a style="width: 60px;" class="label label-sm label-info" title="绑定" onclick="bang({pigcms{$vo.id},{pigcms{$vo.attribute_standard_id},{pigcms{$vo.attribute_id})">绑定</a></div></td>
                                <elseif condition="$vo.is_bind eq 2" />
                                <td><div class="tagDiv" style="color: green">已绑定当前</div></td>
                                <td><div class="tagDiv" style="text-align: center"><a style="width: 60px;" class="label label-sm label-info" title="解绑" onclick="relieve({pigcms{$vo.id},{pigcms{$vo.attribute_standard_id},{pigcms{$vo.attribute_id})">解绑</a></div></td>
                                <else/>
                                <td><div class="tagDiv" style="color: red">已绑定其他</div></td>
                                <td><div class="tagDiv" style="text-align: center"><a style="width: 60px;" class="label label-sm label-info" title="解绑" onclick="relieve({pigcms{$vo.id},{pigcms{$vo.attribute_standard_id},{pigcms{$vo.attribute_id})">解绑</a></div></td>
                            </if>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有设备。</td></tr>
                            </if>
                            <tr>
                                <td colspan="14">&nbsp;&nbsp;&nbsp;<button class=" btn btn-success">批量绑定</button></td>
                            </tr>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .modal-content{
        width:400px;
    }
    .modal-body{
        text-align:center;
        padding:20px 15px;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
/*    $(".delete_class").on('click',function(){
        var len = $("input[name='delCheckbox[]']:checked").length;
        if(len<=0){
            $('#myModal_alert').modal({
                keyboard: false,
                backdrop: 'static'
            })
        }else{
            $('#myModal').modal({
                keyboard: false,
                backdrop: 'static'
            })
        }
    });*/
$(".btn-success").on('click',function(){
    var length = $("input[name='delCheckbox[]']").length;
    var value="";
    for(var i=0;i<length;i++){
        if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
    }
    value = value.substring(1);
    if(value){
        var url = "{pigcms{:U('ajax_bang_attribute')}";
        $.post(url,{'equipment_id':value,'attribute_standard_id':{pigcms{$_GET['id']},'attribute_id':{pigcms{$_GET['attribute_id']}},function(data){
                console.log(data.status);
                if(data['status']){
                    location.reload();
                }else{
                    alert('离线设备无法绑定');
                }
            },'json')
    }

});
    function Dcheckbox(e){
        var n=0;
        var len = $("input[name='delCheckbox[]']").length;
        for(var i=0;i<len;i++){
            if($("input[name='delCheckbox[]']")[i].checked) n++;
        }
        if(n==len){
            $(".checkbox_all").prop("checked",true);
        }else{
            $(".checkbox_all").prop("checked",false);
        }

    }

    function relieve(equipment_id,attribute_standard_id,attribute_id){
        if(confirm('确认解绑?')) {
            $.post("{pigcms{:U('ajax_attribute_relieve')}", {'equipment_id': equipment_id,'attribute_standard_id':attribute_standard_id,'attribute_id':attribute_id}, function (data) {
                data = JSON.parse(data);
                if (data.status == 1) {
                    alert('解绑成功');
                    window.location.reload();
                }
            });
        }
    }
    function bang(equipment_id,attribute_standard_id,attribute_id){
        $.post("{pigcms{:U('ajax_bang_attribute')}", {'equipment_id':equipment_id,'attribute_standard_id':attribute_standard_id,'attribute_id':attribute_id}, function(data) {
                data = JSON.parse(data);
                if(data.status == 1){
                    alert('绑定成功');
                    window.location.reload();
                }else{
                    alert('离线设备无法绑定');
                    window.location.reload();
                }
            });
    }

    $(".checkbox_all").on('click',function(){

        if($(this).is(':checked')){
            var len = $("input[name='delCheckbox[]']").length;
            for(var i=0;i<len;i++){
                console.log($("input[name='delCheckbox[]']")[i].disabled)
                if($("input[name='delCheckbox[]']")[i].disabled==false) {
                    $("input[name='delCheckbox[]']").eq(i).prop("checked",true);
                };
            }
        }else{
            $("input[name='delCheckbox[]']").prop("checked",false);
        }

    });

</script>
<include file="Public:footer"/>
