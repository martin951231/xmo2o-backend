<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
            <li>
                <a href="{pigcms{:U('WisdomQrcode/perpon_index',array('cate_id'=>$_GET['cate_id']))}">负责人管理</a>
            </li>
            <li class="active">编辑负责人</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$qrcode_person['id']}" name="id">
							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-1"><label for="name">工作人员：</label></label>
										<td width="85%">
											<select name="uid" id="uid">
												<volist name="house_worker" id="vo">
												    <option <if condition="$qrcode_person['uid'] eq $vo['wid']">selected="selected"</if> value="{pigcms{$vo.wid}">{pigcms{$vo.name}</option>
												</volist>
											</select>
											&nbsp;&nbsp;<font color="red">如需接收模板消息,该工作人员需绑定微信(需到工作人员列表中绑定微信)</font>
										</td>

									</tr>
								</div>
							</div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">负责人手机号码：</label></label>
                                    <input class="col-sm-2" size="20" name="user_phone" id="user_phone" type="text" value="{pigcms{$qrcode_person.user_phone}"/>
                                </div>
                            </div>

							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="water_price">状态</label></label>
									<label><input name="status" <if condition="$qrcode_person.status eq 1">checked="checked" </if> type="radio" value="1"/>&nbsp;&nbsp;正常</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="status" <if condition="$qrcode_person.status eq 0">checked="checked" </if> type="radio" value="0"/>&nbsp;&nbsp;禁止</label>
								</div>
							</div>

							<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<script>
	$("#uid").click(function(){
		console.log($('#uid').val());
		var uid = $('#uid').val();
		if(uid){
			$.post(
				"{pigcms{:U('ajax_user_info')}",
				{uid:uid},
				function(result){
					console.log(result);
					console.log(result.msg);
					if(result.code == 1){
						$("#user_phone").attr("value",'');//填充内容
					}else{
						$("#user_phone").attr("value",result.msg);//填充内容
					}

				},'json');
		}else{
			$("#user_phone").attr("value",'');//填充内容
//			alert("此工作人员不是平台用户!")
			console.log('此工作人员不是平台用户')
		}

	});

	$(".tab-contents").on("focus","#start_time",function(){
		$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	});
	$(".tab-contents").on("focus","#end_time",function(){
		$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	});
	$(".tab-contents").on("focus","#record_name",function(){
		$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	});
function check_submit(){
	if($('#uid').val()==''){
		alert('名称不能为空！');
		return false;
	}

	if($('#user_phone').val()==''){
		alert('联系方式不能为空！');
		return false;
	}

	if($('#record_name').val()==''){
		alert('提醒不能为空！');
		return false;
	}
}

//$().ready(function(){
//	$(".active_yue").hide();
//	$(".active_zhou").hide();
//});
	var type = "{pigcms{$qrcode_person.record_type}";
	console.log(type)
	$().ready(function(){
		console.log(type)
		if(type == 1){
			$(".active_yue").hide();
	        $(".active_zhou").hide();
		}else if(type == 2){
			$(".active_yue").hide();
			$(".active_zhou").show();
		}else if(type == 3){
			$(".active_yue").show();
			$(".active_zhou").hide();
		}
	});

$(".record_type").click(function(){
	if($("input[name='record_type']:checked").val() == 1){
		$(".active_yue").hide();
		$(".active_zhou").hide();
	}else if($("input[name='record_type']:checked").val() == 2){
		$(".active_zhou").show();
		$(".active_yue").hide();

	}else if($("input[name='record_type']:checked").val() == 3){
		$(".active_yue").show();
		$(".active_zhou").hide();
	}
})

</script>




<include file="Public:footer"/>