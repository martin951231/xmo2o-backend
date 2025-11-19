<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/audit_unbind')}">申请解绑</a>
            </li>
            <li class="active">申请解绑列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                    <form method="get" id="find-form">
                        <input type="hidden" name="c" value="User"/>
                        <input type="hidden" name="a" value="audit_unbind"/>
                        <select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">
                            <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>手机号</option>
                            <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>姓名</option>
                        </select>
                        <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" placeholder="" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>

                        <select name="status" id="status" class="col-sm-1" style="margin-right:10px;height:42px;">
                            <option value="" selected="selected">请选择</option>
                            <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>审核中</option>
                            <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>拒绝解绑</option>
                            <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已解绑</option>
                        </select>
                        &nbsp;&nbsp;
                        申请日期：
                        <input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>-
                        <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                        
                        <input class="btn btn-success" type="submit" id="find_submit" value="查找业主" />&nbsp;
                        <a class="btn btn-success" onclick="location.href='{pigcms{:U('User/audit_unbind')}'">重置</a>

                        <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(437,$house_session['menus'])">
                            <a class="btn btn-success my_bind">批量通过</a>&nbsp;
                            <else/>
                            <a class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">批量通过</a>&nbsp;
                        </if>
                        <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(438,$house_session['menus'])">
                            <a class="btn btn-success" onclick="un_bind()">批量拒绝通过</a>
                            <else/>
                            <a class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">批量拒绝通过</a>
                        </if>
                    </form>
                </div>
                
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <if condition="!$base_third_config && !$base_third_config['third_id']">
                                        <th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                    </if>
                                    <th width="10%">申请编号</th> 
                                    <th width="10%">姓名</th>
                                    <th width="10%">手机号码</th>
									<th width="20%"><?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>/<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?></th>
                                    <th width="10%">所属角色</th>
                                    <th width="10%">状态</th>
                                    <th width="15%">申请时间</th>
                                    <th width="15%">操作时间</th>
                                    <th class="button-column" >操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$lists['list']">
                                    <volist name="lists['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                <td>
                                                    <if condition="$vo['status'] eq 1">
                                                    <input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.itemid}" onclick="return Dcheckbox($(this));">
                                                    </if>
                                                </td>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.itemid}</div></td>
                                            <td>{pigcms{$vo.name}</td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
											<td>{pigcms{$vo.address}</td>
                                            <td>
												<div class="tagDiv">
													<if condition='$vo["type"] eq 0'>
                                                        <if condition="$config['open_house_user_name']">房主&社长<else/>房主</if>
													<elseif condition='$vo["type"] eq 1' />
                                                        <if condition="$config['open_house_user_name']">亲友<else/>家属</if>
													<elseif condition='$vo["type"] eq 2' />
                                                        <if condition="$config['open_house_user_name']">租客&社员<else/>租客</if>
                                                    <elseif condition='$vo["type"] eq 3' />
                                                        <if condition="$config['open_house_user_name']">替换房主&社长<else/>替换房主</if>
													</if>
												</div>
											</td>
                                            <td>
												<div class="tagDiv">
													<if condition='$vo["status"] eq 1'>
														<span class="red">审核中</span>
													<elseif condition='$vo["status"] eq 2' />
														<span class="red">拒绝解绑</span>
													<elseif condition='$vo["status"] eq 3' />
														<span class="green">已解绑</span>
													</if>
												</div>
											</td>
                                            <td>{pigcms{$vo.addtime|date='Y-m-d H:i:s',###}</td>
                                            <td>{pigcms{$vo.edittime|date='Y-m-d H:i:s',###}</td>
                                            
                                            <td class="button-column">
                                                <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                    <if condition='$vo["status"] neq 3'>
                                                    <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('User/audit_unbind_edit',array('itemid'=>$vo['itemid']))}">编辑</a>&nbsp;&nbsp;
                                                    </if>

                                                    <if condition="in_array(109,$house_session['menus'])">
                                                    <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条信息？')){location.href=\'{pigcms{:U('audit_unbind_del',array('itemid'=>$vo['itemid']))}\'}">删除</a>
                                                    </if>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何申请信息</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$lists.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="myModal_alert" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">提示信息</h4>
            </div>
            <div class="modal-body">
                请先选择要执行的信息！
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary class-tip">确定</button>
            </div>
        </div>
    </div>
</div>
<div id="unbind" style="display: none;">
    <div >
        <label class="col-sm-5"><label for="reason">拒绝原因：</label></label>
        <textarea class="col-sm-7" size="20" style="height: 50px" name="reason" id="reason" /></textarea>
    </div>
</div>
<div class="modal fade" id="myBindModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    警告提示
                </h4>
            </div>
            <div class="modal-body">
                确定批量通过吗？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary class-adopt-bind">
                    确定
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal -->
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" language="javascript">



function send_property_one(pigcms_id , usernum){
	if(confirm('确认发送微信消息？')){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url,{'pigcms_id':pigcms_id,'usernum':usernum},function(data){
			if(data['status']){
				alert(data['msg']);
			}
		},'json')
	}
}

$(".class-tip").on('click',function(){
    $('#myModal_alert').modal('hide');
});

$(".checkbox_all").on('click',function(){

    if($(this).is(':checked')){
        $("input[name='delCheckbox[]']").prop("checked",true);
    }else{
        $("input[name='delCheckbox[]']").prop("checked",false);
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

//批量通过
$(".my_bind").on('click',function(){
    var len = $("input[name='delCheckbox[]']:checked").length;
    if(len<=0){
        $('#myModal_alert').modal({
            keyboard: false,
            backdrop: 'static'
        })
    }else{
        $('#myBindModal').modal({
            keyboard: false,
            backdrop: 'static'
        })
    }
});
//确定通过
$(".class-adopt-bind").on('click',function(){
    $('#myBindModal').modal('hide');
    var length = $("input[name='delCheckbox[]']").length;
    var value="";
    for(var i=0;i<length;i++){
        if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
    }
    value = value.substring(1);
    console.log(value)
    if(value){

        var url = "{pigcms{:U('Unit/ajax_audit_unbind_edit')}";
        $.post(url,{'status':3,'arr_itemid':value},function(data){
            console.log("guojie")
            console.log(data)
            if(data.status == 1){
                alert('操作成功');
                location.reload();
            }else{
                alert(data['msg']);
            }
        },'json')
    }
});



function un_bind(){
    var len = $("input[name='delCheckbox[]']:checked").length;
    if(len<=0){
        $('#myModal_alert').modal({
            keyboard: false,
            backdrop: 'static'
        })
    }else{
        var length = $("input[name='delCheckbox[]']").length;
        var value="";
        for(var i=0;i<length;i++){
            if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
        }
        value = value.substring(1);
        console.log(value)
        art.dialog({
            content: document.getElementById('unbind'),
            id: 'handle',
            title:'解除绑定',
            padding: 0,
            width: 350,
            height: 150,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            left: '50%',
            top: '50px',
            opacity:'0.4',
            ok:function(){
                var reason = $('#reason').val();
                $.get("{pigcms{:U('Unit/ajax_audit_unbind')}",{arr_itemid:value,reason:reason,status:2},function(result){
                    if (result.status>0) {
                        alert(result.msg);return false;
                    } else if (result.status==0){
                        alert(result.msg);
                        window.location.reload();
                    }
                },'json');
            }
        });
        return false;


    }
}
</script>
<include file="Public:footer"/>
