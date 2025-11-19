<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">

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
									<label class="col-sm-1"><label for="water_price">记录时间</label></label>
									<label><input name="record_type" <if condition="$qrcode_person.record_type eq 1">checked="checked" </if>  class="record_type"   type="radio" value="1"/>&nbsp;&nbsp;每日</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="record_type" <if condition="$qrcode_person.record_type eq 2">checked="checked" </if> class="record_type"  type="radio" value="2"/>&nbsp;&nbsp;每周</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="record_type" <if condition="$qrcode_person.record_type eq 3">checked="checked" </if> class="record_type"  type="radio" value="3"/>&nbsp;&nbsp;每月</label>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active_zhou">

								<div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">

									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" type="checkbox" name="record_time2[]" value="1" <if condition="in_array(1,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked"</if> id="group_category_1"/>
										<span class="lbl"><label for="group_category_1">周一</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(2,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="2" id="group_category_2"/>
										<span class="lbl"><label for="group_category_2">周二</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(3,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="3" id="group_category_3"/>
										<span class="lbl"><label for="group_category_3">周三</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(4,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="4" id="group_category_4"/>
										<span class="lbl"><label for="group_category_4">周四</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(5,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="5" id="group_category_5"/>
										<span class="lbl"><label for="group_category_5">周五</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(6,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="6" id="group_category_6"/>
										<span class="lbl"><label for="group_category_6">周六</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(7,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 2">checked="checked" </if> type="checkbox" name="record_time2[]" value="7" id="group_category_7"/>
										<span class="lbl"><label for="group_category_7">周日</label></span>
									</label>
								</div>
							</div>

							<div id="basicinfo" class="tab-pane active_yue">

								<div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">

									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(1,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if> type="checkbox" name="record_time3[]" value="1" id="group_category_1"/>
										<span class="lbl"><label for="group_category_1">1号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(2,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="2" id="group_category_2"/>
										<span class="lbl"><label for="group_category_2">2号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(3,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="3" id="group_category_3"/>
										<span class="lbl"><label for="group_category_3">3号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(4,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="4" id="group_category_4"/>
										<span class="lbl"><label for="group_category_4">4号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(5,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="5" id="group_category_5"/>
										<span class="lbl"><label for="group_category_5">5号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(6,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="6" id="group_category_6"/>
										<span class="lbl"><label for="group_category_6">6号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(7,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="7" id="group_category_7"/>
										<span class="lbl"><label for="group_category_7">7号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(8,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="8" id="group_category_8"/>
										<span class="lbl"><label for="group_category_8">8号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(9,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="9" id="group_category_9"/>
										<span class="lbl"><label for="group_category_9">9号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(10,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="10" id="group_category_10"/>
										<span class="lbl"><label for="group_category_10">10号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(11,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="11" id="group_category_11"/>
										<span class="lbl"><label for="group_category_11">11号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(12,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="12" id="group_category_12"/>
										<span class="lbl"><label for="group_category_12">12号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(13,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="13" id="group_category_13"/>
										<span class="lbl"><label for="group_category_13">13号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(14,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="14" id="group_category_14"/>
										<span class="lbl"><label for="group_category_14">14号</label></span>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(15,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="15" id="group_category_15"/>
										<span class="lbl"><label for="group_category_15">15号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(16,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="16" id="group_category_16"/>
										<span class="lbl"><label for="group_category_16">16号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(17,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="17" id="group_category_17"/>
										<span class="lbl"><label for="group_category_17">17号</label></span>
									</label>
									<label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(18,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="18" id="group_category_18"/>
										<span class="lbl"><label for="group_category_18">18号</label></span>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(19,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="19" id="group_category_19"/>
										<span class="lbl"><label for="group_category_19">19号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(20,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="20" id="group_category_20"/>
										<span class="lbl"><label for="group_category_20">20号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(21,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="21" id="group_category_21"/>
										<span class="lbl"><label for="group_category_21">21号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(22,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="22" id="group_category_22"/>
										<span class="lbl"><label for="group_category_22">22号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(23,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="23" id="group_category_23"/>
										<span class="lbl"><label for="group_category_23">23号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(24,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="24" id="group_category_24"/>
										<span class="lbl"><label for="group_category_24">24号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(25,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="25" id="group_category_25"/>
										<span class="lbl"><label for="group_category_25">25号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(26,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="26" id="group_category_26"/>
										<span class="lbl"><label for="group_category_26">26号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(27,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="27" id="group_category_27"/>
										<span class="lbl"><label for="group_category_27">27号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(28,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="28" id="group_category_28"/>
										<span class="lbl"><label for="group_category_28">28号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(29,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="29" id="group_category_29"/>
										<span class="lbl"><label for="group_category_29">29号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(30,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="30" id="group_category_30"/>
										<span class="lbl"><label for="group_category_30">30号</label></span>
									</label>
									</label><label style="margin-right:10px;min-width: 55px;">
										<input class="ace" <if condition="in_array(31,$qrcode_person['record_time']) AND $qrcode_person.record_type eq 3">checked="checked" </if>  type="checkbox" name="record_time3[]" value="31" id="group_category_31"/>
										<span class="lbl"><label for="group_category_31">31号</label></span>
									</label>




								</div>
							</div>
							<div id="basicinfo" class="tab-pane tab-contents active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">起止时间：</label></label>
									<input class="Config_shop_start_time" readonly size="20" name="start_time" id="start_time" type="text" value="{pigcms{$qrcode_person.start_time}"/>&nbsp;&nbsp;至&nbsp;&nbsp;
									<input class="Config_shop_start_time" readonly size="20" name="end_time" id="end_time" type="text" value="{pigcms{$qrcode_person.end_time}"/>
								</div>
							</div>

							<div id="basicinfo" class="tab-pane tab-contents active ">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">提醒时间：</label></label>
									将在
									<input class="Config_shop_start_time" size="20" readonly name="remind_time" id="record_name" type="text" value="{pigcms{$qrcode_person.remind_time}"/>&nbsp;&nbsp;发送模板消息给工作人员
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