<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/group_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("拼团列表")}</a>
			</li>
			<li>{pigcms{:L_("编辑")}/{pigcms{:L_("新增拼团活动")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<style type="text/css">
	#goods_spec{width:800px;border-color: #ccc;border-style: solid;border-width: 1px 0 0 1px;margin-bottom:10px;}
	#goods_spec tr:first-child{background:#eee;}
	#goods_spec th{height:32px;}
	#goods_spec th,#goods_spec td{border-color: #ccc;border-style: solid;border-width: 0 1px 1px 0;text-align: center;}
	#goods_spec input{text-indent:3px;margin:5px 0;height:26px;}
	select:disabled{background: #F5F5F5;}
	</style>
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<!-- <div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">				
							<li class="active">
								<a href="{pigcms{:U('Mall/sort_edit',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
							</li>
						</ul>
					</div> -->
					<div class="tab-content" style="padding-top:10px;">
						<div class="grid-view">							
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" name="store_id" value="{pigcms{$_GET['store_id']}"/>
								<input type="hidden" name="id" value="{pigcms{$info.id}"/>
								<input type="hidden" name="goods_id" value="{pigcms{$info.goods_id}"/>
								<?php if($_GET['id']){?>
									<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("编辑周期购商品")}</p>
									<?php }else{?>
									<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("新增周期购商品")}</p>
									<?php }?>
								<div class="form-group">
									
									<label class="col-sm-1"><label for="name">{pigcms{:L_("关联商品")}</label></label>
									<input class="col-sm-2" size="20" id="goods_name" name="goods_name"  type="text" value="{pigcms{$info.goods_name}" disabled="true"/>
									<input  id="goods_id" name="goods_id"  type="hidden" value="{pigcms{$info.goods_id}"/>
									<a id='choose_good_btn' class="btn btn-success" style="margin-left: 10px;">{pigcms{:L_("选择商品")}</a>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("活动名称")}</label></label>
									<input class="col-sm-3" size="20" name="name"  type="text" value="{pigcms{$info.name}"/>
									<span class="form_tips">{pigcms{:L_("活动名称(限30个字)")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="des">{pigcms{:L_("活动描述")}</label></label>
									<input class="col-sm-2" size="20" name="des"  type="text" value="{pigcms{$info.des}"/>
									<span class="form_tips">{pigcms{:L_("活动描述（限24个字）")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("配送周期")}</label></label>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="1" name="periodic_type" <if condition="empty($info) || $info['periodic_type'] eq 1">checked="checked"</if> <eq name='info.is_edit' value='0'>disabled="true"</eq>/>{pigcms{:L_("每日")}</label>&nbsp;&nbsp;
									</div>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="2" name="periodic_type" <if condition="$info['periodic_type'] eq 2">checked="checked"</if> <eq name='info.is_edit' value='0'>disabled="true"</eq>/>{pigcms{:L_("每周")}</label>&nbsp;&nbsp;
									</div>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="3" name="periodic_type" <if condition="$info['periodic_type'] eq 3">checked="checked"</if> <eq name='info.is_edit' value='0'>disabled="true"</eq>/>{pigcms{:L_("每月")}</label>&nbsp;&nbsp;
									</div>
								</div>
								<div class="form-group" id="periodic_html">
								</div>
								<div class="form-group" id="periodic_html_tips">		
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pre_delivery_day">{pigcms{:L_("配送提前期")}</label></label>
									<span>买家需提前</span>
									<input style="width: 30px;" size="20" name="pre_delivery_day"  type="text" value="{pigcms{$info.pre_delivery_day}"/>
									<span>天，并在</span>
									<input style="width: 30px;" size="20" name="pre_delivery_time"  type="text" value="{pigcms{$info.pre_delivery_time}"/>
									<span>时下单支付，才能在最近的配送时间送达</span>
									
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="periodic_count">{pigcms{:L_("配送期数")}</label></label>
									<input style="width: 30px;" size="20" name="periodic_count"  type="text" value="{pigcms{$info.periodic_count}"/>
									<span>期</span>	
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="delay_limit">{pigcms{:L_("延期次数限制")}</label></label>
									<span>每个周期购活动，用户每单最多可以延期或取消顺延</span>
									<input style="width: 30px;" size="20" name="delay_limit"  type="text" value="{pigcms{$info.delay_limit}"/>
									<span>次。（0表示不允许顺延，-1表示无限次）</span>
									
								</div>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script>
var old_goods_id="{pigcms{$info.goods_id}";
old_goods_id=parseInt(old_goods_id);
var goods_html_url='{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=mall_good&store_id={pigcms{$_GET.store_id}&type=mall_group';
$(function(){
	/*{pigcms{:L_("调整保存按钮的位置")}*/
	$(".nav-tabs li a").click(function(){
		if($(this).attr("href")=="#imgcontent"){		//"店铺图片")}
			$(".form-submit-btn").css('position','absolute');
			$(".form-submit-btn").css('top','670px');	
		}else{
			$(".form-submit-btn").css('position','static');
		}
	});

	$('form.form-horizontal').submit(function(){
		$(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
	});
	$('#choose_good_btn').click(function(){
		
		parent.layer.open({
			title:false,
			closeBtn: 0,
			type:2,
			content:goods_html_url+'&goods_id='+old_goods_id,
			area:['650px','576px'],
			shade: 0.2,
			cancel:function(){ 
			},
			move:false
		});
	});
});
function login_box_after(a,b,c,d,goods_id,index){
	if(goods_id==old_goods_id){
		layer.close(index);
		return false;
	}

	old_goods_id=goods_id;
	$.post('{pigcms{:U("Mall/get_good_info")}',{goods_id:goods_id},function(res){
		if(res.status==1){
			var data=res.info;
			console.log(res.info);
			
			$('input[name=goods_name]').val(data.name);
			$('input[name=goods_id]').val(goods_id);
			layer.close(index);
		}else{
			layer.msg(res.info);
		}
	}).error(function(){
		alert('{pigcms{:L_("网络出错，请重试")}');
	});	
}
$('input[name=periodic_type]').change(function(){
	update_periodic_date();
});
var now_type_default = parseInt({pigcms{$info.periodic_type});
var now_val_default = '{pigcms{$info.periodic_date}';
function update_periodic_date(now_type=0,now_val=0){
	var periodic_type=$('input[name=periodic_type]:checked').val();
	if(now_val==0){
		now_val = now_val_default;
	}
	html = '';
	tips_html = '';

	if(periodic_type==2){
		var week = ['周日','周一','周二','周三','周四','周五','周六'];
		html = '<label class="col-sm-1"><label for="des">{pigcms{:L_("送达时间")}</label></label>';
		for (var i = 0;i<=week.length - 1;  i++) {
			if(periodic_type==now_type_default&&now_val.indexOf(String(i))>=0){
				html+= '<div style="width:50px;float:left;font-size:16px;"><label><input class="periodic_type_c" type="checkbox" value="'+i+'" name="periodic_date[]" checked="checked"/>'+week[i]+'</label>&nbsp;&nbsp;</div>';
			}else{
				html+= '<div style="width:50px;float:left;font-size:16px;"><label><input class="periodic_type_c" type="checkbox" value="'+i+'" name="periodic_date[]"/>'+week[i]+'</label>&nbsp;&nbsp;</div>';
			}
		}
	}
	if(periodic_type==3){
		html = '<label class="col-sm-1"><label for="des">{pigcms{:L_("送达时间")}</label></label>';
		for (var i = 1; i <= 31; i++) {
			if(periodic_type==now_type_default&&now_val.indexOf(String(i))>=0){
				html+= '<div style="width:50px;float:left;font-size:16px;"><label><input class="periodic_type_c" type="checkbox" value="'+i+'" name="periodic_date[]" checked="checked" />'+i+'号</label>&nbsp;&nbsp;</div>';
			}else{
				html+= '<div style="width:50px;float:left;font-size:16px;"><label><input class="periodic_type_c" type="checkbox" value="'+i+'" name="periodic_date[]" />'+i+'号</label>&nbsp;&nbsp;</div>';
			}
			
		}
		tips_html = '<label class="col-sm-1"></label><span class="form_tips" style="margin-left: 0px;">配送周期为月份的情况下：如果当前月份不足设置的号数，则按当前月份最后一天计算</span>';
	}
	$("#periodic_html").html(html);
	$("#periodic_html_tips").html(tips_html);
}

update_periodic_date(now_type_default,now_val_default);
//"提交表单")}
var clock=0;
$('form').submit(function(){
	var goods_id=$('input[name=goods_id]').val();
	var name=$('input[name=name]').val();
	var des=$('input[name=des]').val();
	var periodic_type=$('input[name=periodic_type]:checked').val();
	var periodic_date=$('input[name=periodic_date]:checked').val();
	var periodic_count=$('input[name=periodic_count]').val();
	var pre_delivery_day=$('input[name=pre_delivery_day]').val();
	var pre_delivery_time=$('input[name=pre_delivery_time]').val();
	var delay_limit=$('input[name=delay_limit]').val();

	if(goods_id==''){
		tip('{pigcms{:L_("请选择周期购商品")}');return false;
	}
	if(name==''){
		tip('{pigcms{:L_("请填写活动名称")}');return false;
	}
	if(des==''){
		tip('{pigcms{:L_("请填写活动描述")}');return false;
	}
	if(periodic_type==''){
		tip('{pigcms{:L_("请选择配送周期")}');return false;
	}
	if(periodic_type==2||periodic_type==3){
		var length = $("input[class=periodic_type_c]:checked").length;
		length = parseInt(length);
		if(length==0){
			tip('{pigcms{:L_("请选择送达时间")}');return false;
		}
	}
	
	if(periodic_count==''){
		tip('{pigcms{:L_("请填写周期期数")}');return false;
	}
	if(pre_delivery_day==''){
		tip('{pigcms{:L_("请填写配送提前期（天数）")}');return false;
	}
	if(pre_delivery_time==''){
		tip('{pigcms{:L_("请填写配送提前期（时间）")}');return false;
	}
	if(delay_limit==''){
		tip('{pigcms{:L_("填写延期次数")}');return false;
	}
	$('select').removeAttr('disabled');
	$('input[type=radio]').removeAttr('disabled');
});
function login_box_close(index){
	layer.close(index);
}
function tip(msg){
	if(!msg){
		return false;
	}
	layer.alert(msg,{icon:2,closeBtn:0},function(index){
		$('.btn.btn-info').text('{pigcms{:L_("保存")}').removeAttr('disabled');
		layer.close(index);
	});
}
</script>

<include file="Public:footer"/>
