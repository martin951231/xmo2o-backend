<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/audit_index')}">审核业主</a>
            </li>
            <li class="active">业主审核列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" id="find-form">
					<input type="hidden" name="c" value="User"/>
					<input type="hidden" name="a" value="audit_index"/>
					<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">
						<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>物业编号</option>
						<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>手机号</option>
                        <option value="2" <if condition="$find_type eq 2">selected="selected"</if>>姓名</option>
					</select>
					<input value="{pigcms{$find_value}" class="col-sm-2" placeholder="" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>

					<select name="status" id="status" class="col-sm-1" style="margin-right:10px;height:42px;">
						<option value="" selected="selected">请选择</option>
						<option value="1" <if condition="$find_status eq 1">selected="selected"</if>>正常</option>
                        <option value="2" <if condition="$find_status eq 2">selected="selected"</if>>审核中</option>
<!--                        <option value="3" <if condition="$find_status eq 3">selected="selected"</if>>审核通过</option>-->
						<option value="3" <if condition="$find_status eq 3">selected="selected"</if>>未通过</option>
                        <option value="4" <if condition="$find_status eq 4">selected="selected"</if>>替换业主</option>
					</select>
					&nbsp;&nbsp;
					申请日期：
					<input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>-
					<input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>
                    <if condition="$chinapopinOpen">
                        <span style="margin-left:10px;">数据来源：</span>
                        <select name="third_type" id="third_type"  style="height:42px;">
                            <option value="all" <if condition="$_GET['third_type'] eq 'all'">selected="selected"</if>>全部</option>
                            <option value="house" <if condition="$_GET['third_type'] eq 'house'">selected="selected"</if>>社区注册</option>
                            <option value="ezc_chinapopin" <if condition="$_GET['third_type'] eq 'ezc_chinapopin'">selected="selected"</if>>平安小区接口</option>
                            <option value="dopu_cloudintercom" <if condition="$_GET['third_type'] eq 'dopu_cloudintercom'">selected="selected"</if>>朵普接口</option>
                        </select>
                    </if>&nbsp;&nbsp;
					<input class="btn btn-success" type="submit" id="find_submit" value="查找业主" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('User/audit_index')}'">重置</a>
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
			
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                    <th width="10%">物业编号</th>
                                    <th width="8%">姓名</th>
                                    <th width="10%">手机号</th>
                                    <if condition="$chinapopinOpen">
                                        <th width="6%">数据来源</th>
                                    </if>
									<th width="8%">房屋类型</th>
                                    <th width="15%">住址</th>
                                    <th width="8%">绑定关系</th>
                                    <th width="8%">属性</th>
                                    <th width="15%">申请时间</th>
                                    <th width="8%">状态</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$user_list['list']">
                                    <volist name="user_list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td align="center">
<!--                                        	  	<if condition="$vo['status'] eq 0">-->
<!--                                        	  		<input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}" onclick="return Dcheckbox($(this));">-->
<!--                                                <else/>-->
<!--                                                	<input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" onclick="return Dcheckbox($(this));"  disabled="disabled">-->
<!--                                                </if>-->
												<input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}#{pigcms{$vo.phone}#{pigcms{$vo.usernum}#{pigcms{$vo.floor_id}" onclick="return Dcheckbox($(this));">
                                            </td>
                                            <td>
                                                <if condition="$vo['bind_number']">
                                                    <div class="tagDiv">{pigcms{$vo['bind_number']}</div>
                                                    <else/>
                                                    <div class="tagDiv">{pigcms{$vo.usernum}</div>
                                                </if>
                                            </td>
                                            <td>{pigcms{$vo.name}</td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <if condition="$chinapopinOpen">
                                                <th><div style="background-color: {pigcms{$vo.third_type_color};padding: 10px;border-radius: 10px;text-align: center;">{pigcms{$vo.third_type}</div></th>
                                            </if>
											<td>
                                                <if condition='$vo["house_type"] eq 1'>
                                                    <div class="shopNameDiv">住宅</div>
                                                    <elseif condition='$vo["house_type"] eq 2' />
                                                    <div class="shopNameDiv">商铺</div>
                                                    <elseif condition='$vo["house_type"] eq 3' />
                                                    <div class="shopNameDiv">办公</div>
                                                    <else/>
                                                    <div class="shopNameDiv">无</div>
                                                </if>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.address}</div></td>
                                            <td>
												<div class="tagDiv">
													<if condition='$vo["type"] eq 0'>
														<if condition="$config['open_house_user_name']">房主&社长<else/>房主</if>
													<elseif condition='$vo["type"] eq 1' />
													<if condition="$config['open_house_user_name']">亲友<else/>家属</if>
													<elseif condition='$vo["type"] eq 2' />
														<if condition="$config['open_house_user_name']">租客&社员<else/>租客</if>
                                                        <elseif condition='$vo["type"] eq 3' />
                                                       更新房主
													</if>
												</div>
											</td>
                                            <td><if condition="$vo['attribute_name']">{pigcms{$vo.attribute_name}<else/>--</if></td>
                                            <td><if condition="$vo['add_time']">{pigcms{$vo.add_time}<else/>--</if></td>

                                            <td>
												<if condition='$vo["status"] eq 0'>
													<div class="shopNameDiv red" style="cursor: pointer;" title="{pigcms{$vo.ownerText}">
                                                        <if condition='$vo["refuse_time"]'>未通过<else/>替换业主</if>（原因：<if condition='$vo["reason"]'>{pigcms{$vo.reason}<else/>无</if>）
                                                    </div>
												<elseif condition='$vo["status"] eq 1' />
													<div class="shopNameDiv green">正常</div><if condition='$vo["reason"]'>（原因：{pigcms{$vo.reason}）</if></div>
												<elseif condition='$vo["status"] eq 2' />
													<div class="shopNameDiv red" style="cursor: pointer;" title="{pigcms{$vo.ownerText}">
                                                        审核中
                                                    </div>
												<elseif condition='$vo["status"] eq 3' />
													<div class="shopNameDiv green">审核通过</div>
												<else />
													<div class="shopNameDiv red">已解绑</div>
												</if>
											</td>
                                            <td class="button-column">
												<div class="tpl-table-black-operation">

                                                    <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                        <if condition='$vo["status"] neq 1'>
                                                            <a title="编辑" href="{pigcms{:U('User/audit_edit',array('pigcms_id'=>$vo['pigcms_id'],'floor_type_name'=>$vo['floor_type_name'],'usernum'=>$vo['usernum'], 'referer'=> urlencode($_SERVER['REQUEST_URI'])))}">编辑</a>
                                                        </if>
                                                        <if condition="in_array(268,$house_session['menus']) && $vo['status'] eq 0">
                                                            &nbsp;&nbsp;<a title="删除" class="item-delete tpl-table-black-operation-del"  onclick="delItemData('{pigcms{$vo.name}','{pigcms{$vo.pigcms_id}')" href="javascript:;">删除</a>
                                                        </if>
                                                    </if>
												</div>
                                           </td>
                                        </tr>
                                    </volist>
<!--                                    <if condition="in_array(268,$house_session['menus'])">-->
<!--										<tr>-->
<!--											<td colspan="14">&nbsp;&nbsp;&nbsp;<button class=" btn delete_class">删除选中</button></td>-->
<!--										</tr>-->
<!--                                	</if>-->
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何业主。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$user_list.pagebar}
                    </div>
                </div>
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
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
				删除业主后，业主下的<if condition="$config['open_house_user_name']">亲友<else/>家属</if>/租客会被一起删除，绑定的房间会被释放，确定删除？
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary class-delete">
					确定
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<div class="modal fade" id="myModal_alert" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="myModalLabel">提示信息</h4>
			</div>
			<div class="modal-body">
				请先选择要执行的业主！
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary class-tip">确定</button>
			</div>
		</div>
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
function importUser(){
	window.location.href = "{pigcms{:U('User/user_import')}";
}
function importUserDetail(){
	window.location.href = "{pigcms{:U('User/detail_import')}";
}

function send_property(){
	var property_warn_day = "{pigcms{$village_info['property_warn_day']}";
	if(parseInt(property_warn_day) > 0){
		var confirm_txt = "确认群发微信消息（物业费到期提前" + property_warn_day + "天提醒）";
	}else{
		var confirm_txt = "确认群发微信消息（物业费到期提醒）";
	}
	
	if(confirm(confirm_txt)){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url , {'is_collective':1},function(data){
			if(data['status']){
				alert(data['msg']);
			}
		},'json')
	}
}

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

$(".delete_class").on('click',function(){
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
});	
$(".class-delete").on('click',function(){
	$('#myModal').modal('hide');
	var length = $("input[name='delCheckbox[]']").length;
	var value="";
	for(var i=0;i<length;i++){
		if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;	
	}
	value = value.substring(1);
	if(value){
		var village_id = "{pigcms{$village_info.village_id}";
		var url = "{pigcms{:U('User/ajax_del_audit')}";
		$.post(url,{'village_id':village_id,'arr_pigcms_id':value},function(data){
			if(data['status']){
				location.reload();
			}else{
				alert(data['msg']);
			}
		},'json')
	}	
		
});
$(".class-tip").on('click',function(){
	$('#myModal_alert').modal('hide');	
});

var audit_delete_url= "{pigcms{:U('User/audit_delete')}";
function delItemData(xname,idd){
    layer.confirm('确认删除业主名是【'+xname+'】这条数据？<br/>一旦删除无法恢复，请谨慎操作。', {
        btn: ['确定','取消'] //按钮
    }, function(){
        window.location.href=audit_delete_url+'&pigcms_id='+idd;
    });
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
	if(value){
		var village_id = "{pigcms{$village_info.village_id}";
		var url = "{pigcms{:U('Unit/ajax_bind_audit')}";
		$.post(url,{'village_id':village_id,'arr_pigcms_id':value,'status':1},function(data){
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
			title:'拒绝通过原因',
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
				$.post("{pigcms{:U('Unit/ajax_unbind_audit')}",{pigcms_id:value,no_bind:1,status:0,reason:reason,data_type:'json'},function(result){
					if (result.status>0) {
						alert(result.info);return false;
					} else if (result.status==0){
						alert(result.info);
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
