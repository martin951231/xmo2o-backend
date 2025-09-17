<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/bind_audit_list')}">审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if></a>
            </li>
            <li class="active">审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" id="find-form">
					<input type="hidden" name="c" value="User"/>
					<input type="hidden" name="a" value="bind_audit_list"/>
					<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">
						<option value="1" <if condition="$find_type eq 1">selected="selected"</if>><if condition="$config['open_house_user_name']">亲友<else/>家属</if>姓名</option>
                        <option value="2" <if condition="$find_type eq 2">selected="selected"</if>><if condition="$config['open_house_user_name']">亲友<else/>家属</if>手机号</option>
						<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>业主姓名</option>
                        <option value="4" <if condition="$find_type eq 4">selected="selected"</if>>业主手机号</option>
						<if condition="$now_house['write_iccard']">
							<option value="6" <if condition="$find_type eq 6">selected="selected"</if>>IC卡号</option>
						</if>
					</select>
					<input value="{pigcms{$find_value}" class="col-sm-2" placeholder="" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
					&nbsp;&nbsp;
					审核状态：
					<select name="status" id="status" class="col-sm-1" style="margin-right:10px;height:42px;float:none;">
						<option value="" selected="selected">请选择</option>
						<option value="1" <if condition="$find_status eq 1">selected="selected"</if>>审核通过</option>
                        <option value="2" <if condition="$find_status eq 2">selected="selected"</if>>审核中</option>
					</select>
					&nbsp;&nbsp;
					人脸上传状态：
					<select name="face_status" id="face_status" style="margin-right:10px;height:42px;float:none;">
						<option value="0" <if condition="$_GET['face_status'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['face_status'] eq 1">selected="selected"</if>>同步成功</option>
                        <option value="-1" <if condition="$_GET['face_status'] eq -1">selected="selected"</if>>未上传和同步中</option>
						<option value="2" <if condition="$_GET['face_status'] eq 2">selected="selected"</if>>同步失败</option>
					</select>
                    &nbsp;&nbsp;
                    <span style="margin-left:20px;">是否绑定微信：</span>
					<select name="is_bind_weixin" id="is_bind_weixin"  style="height:42px;margin:10px;width: 100px;">
						<option value="0" <if condition="$_GET['is_bind_weixin'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['is_bind_weixin'] eq 1">selected="selected"</if>>是</option>
                        <option value="2" <if condition="$_GET['is_bind_weixin'] eq 2">selected="selected"</if>>否</option>
					</select>
                    &nbsp;&nbsp;
					<span style="margin-left:20px;">注册来源：</span>
					<select name="source_comefrom" id="source_comefrom"  style="height:42px;margin:10px;width: 150px;">
						<option value="0" <if condition="$_GET['source_comefrom'] eq 0">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['source_comefrom'] eq 1">selected="selected"</if>>公众号</option>
                        <option value="2" <if condition="$_GET['source_comefrom'] eq 2">selected="selected"</if>>小程序</option>
                        <option value="3" <if condition="$_GET['source_comefrom'] eq 3">selected="selected"</if>>小区自动注册</option>
                        <option value="5" <if condition="$_GET['source_comefrom'] eq 5">selected="selected"</if>>H5</option>
                        <option value="4" <if condition="$_GET['source_comefrom'] eq 4">selected="selected"</if>>其他</option>
					</select>
					
					<div style="clear:both;height:10px;"></div>
					<span style="margin-left:0px;">申请日期：</span>
					<input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>-
					<input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                    <if condition="$chinapopinOpen">
                        <span style="margin-left:10px;">数据来源：</span>
                        <select name="third_type" id="third_type"  style="height:42px;">
                            <option value="all" <if condition="$_GET['third_type'] eq 'all'">selected="selected"</if>>全部</option>
                            <option value="house" <if condition="$_GET['third_type'] eq 'house'">selected="selected"</if>>社区注册</option>
                            <option value="ezc_chinapopin" <if condition="$_GET['third_type'] eq 'ezc_chinapopin'">selected="selected"</if>>平安小区接口</option>
                            <option value="dopu_cloudintercom" <if condition="$_GET['third_type'] eq 'dopu_cloudintercom'">selected="selected"</if>>朵普接口</option>
                        </select>&nbsp;&nbsp;
                    </if>
					
					<input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('User/bind_audit_list')}'">重置</a>&nbsp;
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
					<button class="btn btn-success jsExport" style="margin-left:10px;">EXCEL导出</button>

                    <button class="exportUserJsDataForLouYu btn btn-success">导出楼宇小区家属格式数据</button>

                    <a class="btn btn-success" href="javascript:;" onclick="importUserRelatives()" style="margin-left: 20px;">导入亲属</a>&nbsp;
				</form>
			</div>
			
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <if condition="!$base_third_config && !$base_third_config['third_id']">
                                	    <th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                    </if>
                                    <if condition="$config['householdIdShow'] eq 1">
                                        <th>ID</th>
                                    </if>
                                    <th>业主姓名</th>
                                    <th>业主手机号</th>
                                    <th>业主地址</th>
                                    <th><if condition="$config['open_house_user_name']">亲友<else/>家属</if>姓名</th>
                                    <th><if condition="$config['open_house_user_name']">亲友<else/>家属</if>手机号</th>
                                    <if condition="$chinapopinOpen">
                                        <th width="6%">数据来源</th>
                                    </if>
                                    <th>申请日期</th>
                                    <th>绑定关系</th>
                                    <th>属性</th>
                                    <th>备注</th>
                                    <th width="8%">人脸识别</th>
                                    <if condition="$haveXiHanFaceDoor">
                                        <th width="5%" title="仅支持西涵门禁">IC卡管理</th>
                                    </if>
                                    <if condition="$fingerprintJudgeConfig">
                                        <th width="5%">指纹</th>
                                    </if>
                                    <th>状态</th>
                                    <th class="text-middle">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$user_list['list']">
                                    <volist name="user_list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                <td> <input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}" onclick="return Dcheckbox($(this));"></td>
                                            </if>
                                            <if condition="$config['householdIdShow'] eq 1">
                                                <td width="5%">{pigcms{$vo['pigcms_id']}</td>
                                            </if>
                                            <td>{pigcms{$vo.parent_name}</td>
                                            <td><div class="tagDiv">{pigcms{$vo.parent_phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.address}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <if condition="$chinapopinOpen">
                                                <th><div style="background-color: {pigcms{$vo.third_type_color};padding: 10px;border-radius: 10px;text-align: center;">{pigcms{$vo.third_type}</div></th>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                            <td>
												<div class="tagDiv">
													<if condition='$vo["type"] eq 0'>
														房主
													<elseif condition='$vo["type"] eq 1' />
														<if condition="$config['open_house_user_name']">亲友<else/>家属</if>
													<elseif condition='$vo["type"] eq 2' />
														<if condition="$config['open_house_user_name']">社员<else/>租客</if>
													<elseif condition='$vo["type"] eq 3' />
														更新房主
													</if>
												</div>
											</td>
                                            <td><if condition="$vo['attribute_name']">{pigcms{$vo.attribute_name}<else/>--</if></td>
                                            <td>{pigcms{$vo.memo}</td>
                                            <td>
                                                <if condition="$vo['faceTxt']">
                                                    <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">{pigcms{$vo['faceTxt']}</a>
                                                <elseif condition="$vo['is_bind_face']" />
                                                    <if condition="$vo['face_img_status'] eq 2">
														<a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">同步错误</a>
													<else/>
														<a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">已上传</a>
													</if>
												<else/>
                                                    <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">未上传</a>
                                                </if>
                                            </td>
                                           <if condition="$haveXiHanFaceDoor">
                                                <td>
                                                <a style="width: 60px;border-radius: 5px" class="label label-sm label-info" title="点击查看" onclick="userBindIcCard('{pigcms{$vo['pigcms_id']}','{pigcms{$vo.name}','{pigcms{$vo.phone}')">点击查看</a>
                                                </td>
                                            </if>
                                            <if condition="$fingerprintJudgeConfig">
                                                <td>
                                                    <a style="color: #3a87ad!important;" href="javascript:void(0);" onclick="lookFingerprint({pigcms{$vo['pigcms_id']})">查看</a>
                                                </td>
                                            </if>
                                            <td>
												<if condition='$vo["status"] eq 0'>
													<div class="shopNameDiv red">审核失败</div>
												<elseif condition='$vo["status"] eq 1' />
													<div class="shopNameDiv green">审核通过</div>
												<elseif condition='$vo["status"] eq 2' />
													<div class="shopNameDiv red">审核中</div>
												</if>
											</td>
                                            <td class="text-middle">
												<if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(105,$house_session['menus'])">
	                                        		<if condition='$vo["status"] eq 1'>
														<if condition='$vo["type"] eq 3'>
															<span class="green">绑定成功<span>
														<else />
															<!-- <a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id'],'no_bind'=>1))}'}">解除绑定</a> -->
															<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="unbind('{pigcms{$vo.pigcms_id}')">解除绑定</a>
														</if>
													<elseif condition='$vo["status"] eq 2' />
														<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id']))}'}">确认绑定</a>
														&nbsp;<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="unbind('{pigcms{$vo.pigcms_id}')">拒绝绑定</a>
													<else/>
														<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id']))}'}">确认绑定</a>
													</if>													
												</if>
												&nbsp;
                                                <if condition="!$base_third_config && !$base_third_config['third_id']">
												<a style="width: 60px;" class="label label-sm label-info bind_info" title="编辑" href="{pigcms{:U('bind_other',array('pigcms_id'=>$vo['pigcms_id'],'edit'=>1))}">编辑</a>
                                                </if>
												<if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(106,$house_session['menus']) && $vo['status'] neq 2">
												&nbsp;
												<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行删除,请谨慎操作？')){location.href='{pigcms{:U('bind_delete',array('pigcms_id'=>$vo['pigcms_id']))}'}">删除</a>
												</if>											
                                           </td>
                                        </tr>
                                    </volist> 
                                    <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(106,$house_session['menus'])">
                                    <tr>
                                    	<td colspan="14">&nbsp;&nbsp;&nbsp;<button class=" btn delete_class">删除选中</button></td>
                                    </tr>
                                	</if>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有<if condition="$config['open_house_user_name']">亲友<else/>家属</if>审核信息。</td></tr>
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
				确定删除<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if>信息？
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

    loadExport = false;
    $('.exportUserJsDataForLouYu').click(function () {
        if (loadExport) {
            layer.msg('数据导出中，请耐心等待~!',{icon: 0,time:0,shade: [0.5]});
            return false
        }
        var _this = $(this);
        _this.html('导出中...');
        _this.removeClass('btn-success');

        layer.msg('正在为您导出数据，如果数据多时间会较长，请耐心等待!',{icon: 0,time:0,shade: [0.5]});
        // 下载使用的方法
        $.get("{pigcms{:U('exportUserJsDataForLouYu')}",{'type':1},function(data){
            console.log('..............',data)
            layer.closeAll();
            loadExport = false;
            _this.html('导出楼宇小区家属格式数据');
            _this.addClass('btn-success');
            let downloadPath = data.url
            var downloadLink = document.createElement('a')
            downloadLink.style.display = 'none' // 使其隐藏
            downloadLink.href = downloadPath
            downloadLink.download = ''
            downloadLink.click()
            downloadLink.remove()
        },'json')
        loadExport = true;

    });
       function userBindIcCard(pigcms_id,name='',phone=''){
        var url = '{pigcms{:U('user_bind_iccard')}&bind_pigcms_id='+pigcms_id;
        var xtitle='IC卡管理（姓名：'+name+'，手机号：'+phone+'）';
        var index = layer.open({
            type: 2,
            area: ['60%', '620px'],
            fixed: true, //不固定
            // maxmin: true,
            title: xtitle,
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
   }
    function bounced(id) {
        var index = layer.open({
            type: 2,
            area: ['750px', '550px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '人脸上传照片',
            content: "/shequ.php?g=House&c=User&a=faces&id="+id
        });
        layer.style(index, {
            top: '20px'
        });
    }
$('.bind_info').click(function(){
	var open_house_user_name = {pigcms{$config['open_house_user_name']|intval};
	if (open_house_user_name) {
		var tipTitle = '编辑亲友/租客&社员';
	}else{

		var tipTitle = '编辑家属/租客';
	}
	art.dialog.open($(this).attr('href'),{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle_',iframe);
		},
		id: 'handle_',
		title:tipTitle,
		padding: 0,
		width: 800,
		height: 603,
		lock: true,
		resize: false,
		background:'black',
		button: null,
		fixed: false,
		close: null,
		left: '50%',
        top: '50px',
		opacity:'0.4'
	});
	return false;
});

// 拒绝绑定
function unbind(pigcms_id){
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
        	$.get("{pigcms{:U('bind_edit')}",{pigcms_id:pigcms_id,no_bind:1,reason:reason,data_type:'json'},function(result){
		       if (result.status>0) {
		            alert(result.msg);return false;
		       } else if (result.status==0){
			       	alert(result.msg);
		            window.location.reload();
		       }
		    },'json');
        },
    });
	return false;
};

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
		var url = "{pigcms{:U('User/ajax_del_bind')}";
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
		var url = "{pigcms{:U('Unit/ajax_batch_bind')}";
		$.post(url,{'village_id':village_id,'arr_pigcms_id':value},function(data){
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

function importUserRelatives(){
	window.location.href = "{pigcms{:U('User/user_import_relatives')}";
}

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
				$.get("{pigcms{:U('Unit/un_bind_edit')}",{pigcms_id:value,no_bind:1,reason:reason,data_type:'json'},function(result){
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

    var loadJsExport = false;
    $('.jsExport').click(function () {
        if (loadJsExport) {
            layer.msg('当前导出中，请稍后再试!',{icon: 2});
            return false
        }
        var _this = $(this);
        _this.html('导出中...');
        _this.removeClass('btn-success');
        setTimeout(function(){
            _this.html('EXCEL导出');
            loadJsExport = false;
            _this.addClass('btn-success');
        },2500);
        loadJsExport = true;
        window.location.href='{pigcms{:U('bind_audit_export',$_GET)}';
        return false;
    })

    function lookFingerprint(pigcms_id) {
        var url = parent.location.href.split("#")[0]+'#/village/village.device/userFingerprintInfo?pigcms_id='+pigcms_id
        var index = layer.open({
            type: 2,
            area: ['58%', '500px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '查看指纹信息',
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
    }
</script>
<include file="Public:footer"/>
