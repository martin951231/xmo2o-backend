<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src=".{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css">
	</head>
	<body>
		<div class="mainBox">
			<div class="rightMain">
				<div class="grid-view">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="form_add" autocomplete="off">
						<input type="hidden" name="order_id" value="{pigcms{$now_order.order_id}"/>
						<div class="form-group">
							<label class="col-sm-2"><label for="table_type">{pigcms{:L_("桌台分类")}</label></label>
							<select id="table_type" name="table_type" <if condition="$config['store_open_table_bind_time'] eq 1">onchange="getNowAppointTime()"</if>>
								<volist name="table_type" id="vo">
									<option value="{pigcms{$vo.id}" <if condition="$vo['id'] eq $now_order['table_type']">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
							</select>
						</div>
						<div class="form-group">
							<label class="col-sm-2"><label for="table_id">{pigcms{:L_("桌台编号")}</label></label>
							<select id="table_id" name="table_id" <if condition="$config['store_open_table_bind_time'] eq 1">onchange="getNowAppointTime()"</if>>
								<volist name="table_list" id="vo">
									<option value="{pigcms{$vo.id}" <if condition="$vo['id'] eq $now_order['table_id']">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
							</select>
						</div>
						<if condition="$config['store_open_table_bind_time'] eq 1">
						<div class="form-group">
							<label class="col-sm-2"><label for="table_id">{pigcms{:L_("预约时间")}</label></label>
							<li id="Choice" style="border-top: none;padding: 6px;display: inline-block;background: #fff;">
								<!-- <span class="fl">{pigcms{:L_("日期")}</span> -->
								<span class="Reser_right">
									<i class="m">{pigcms{$m}</i>月
									<i class="d">{pigcms{$d}</i>日 
									<i class="e">{pigcms{$w}</i> 
									<i class="o">{pigcms{$o}</i>
									<img style="vertical-align: middle;width: 9px;height: 9px;" src="{pigcms{$static_path}images/icons/jiantou.png">
								</span>
								<input type="hidden" name="book_time" id="book_time" value="{pigcms{$book_time}">
							</li>
						</div>
						</if>
						<div class="form-group">
							<label class="col-sm-2"><label for="book_num">{pigcms{:L_("用餐人数")}</label></label>
							<input class="col-sm-2" size="10" name="book_num" id="book_num" type="text" value="{pigcms{$now_order.book_num}"/>
							<span class="form_tips">{pigcms{:L_("不收取客户必点菜费用，可设置为")} 0</span>
						</div>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit" id="submit_btn">
									{pigcms{:L_("保存订单")}
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="time_content" style="position: fixed;top: 0;left: 0;bottom: 0;right: 0;background: rgba(0, 0, 0, .6);display: none;">
			<section class="elastic" style="width: 350px;height: 380px; overflow: hidden;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);">
			<div class="swiper-container swiper-container2 swiper-container-horizontal">
				<div class="swiper-wrapper">
				</div>
				<div class="swiper-button-prev"></div> 
				<div class="swiper-button-next"></div> 
			</div>
			<div class="Switch" style="height: 270px;">
			</div>
			<div class="Resersub" style="width: 350px;position: static;"><input type="submit" value="{pigcms{:L_("确认选择")}" class=""></div>
			</section>
		</div>
	</body>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/storestaffAppoint.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/laytpl.js"></script>
<script>
var  get_appoint_time_url = "{pigcms{:U('get_appoint_time')}";
var  ajax_get_now_appoint_time_url = "{pigcms{:U('ajax_get_now_appoint_time')}";
$(function(){
	$('#total_price').focus();
	$("#table_type").change(function(){
		var loadTip = layer.load(0, {shade: [0.6,'#fff']});
		$.getJSON("{pigcms{:U('get_table_list')}",{table_type:$(this).val()},function(result){
			layer.close(loadTip);
			var tableHtml = '';
			if(result){
				for(var i in result){			
					tableHtml+= '<option value="'+result[i].id+'">'+result[i].name+'</option>';
				}
			}else{
				tableHtml = '<option value="0">{pigcms{:L_("该类型无空闲桌子")}</option>';
			}
			$('#table_id').html(tableHtml);
		});
	});
	$('#book_num').blur(function(){
		var book_num = parseInt($('#book_num').val());
		if(isNaN(book_num)){
			$('#book_num').val('');
		}else{
			$('#book_num').val(book_num);
		}
	});
	
	$('#form_add').submit(function(){
		// if(isNaN(parseInt($('#book_num').val())) || parseInt($('#book_num').val()) <= 0){
		if(isNaN(parseInt($('#book_num').val()))){
			alert('{pigcms{:L_("请输入正确的就餐人数！")}');
			$('#book_num').focus();
			return false;
		}
		if($('#table_id').val() == '0'){
			alert('{pigcms{:L_("请选择就餐桌台")}');
			return false;
		}
		$('#submit_btn').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
		$.post("{pigcms{:U('foodshop_edit_order_save')}",$('#form_add').serialize(),function(result){
			alert(result.info);
			$('#submit_btn').html('{pigcms{:L_("保存订单")}').prop('disabled',false);
		});
		return false;
	});
});
</script>
</html>