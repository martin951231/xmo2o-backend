<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑")}{pigcms{$config.mall_alias_name}{pigcms{:L_("信息")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<!-- <li>
								<a data-toggle="tab" href="#category">{pigcms{:L_("选择分类")}</a>
							</li> -->
							<!--li>
								<a data-toggle="tab" href="#label">{pigcms{:L_("选择标签")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#pay">{pigcms{:L_("支付方式")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#delivertime">{pigcms{:L_("配送时间")}</a>
							</li-->
							<li>
								<a data-toggle="tab" href="#promotion">{pigcms{:L_("店铺折扣")}</a>
							</li>
							<!-- <li>
								<a data-toggle="tab" href="#stock">{pigcms{:L_("库存类型选择")}</a>
							</li> -->
							<if condition="!empty($levelarr)">
								<li>
									<a data-toggle="tab" href="#levelcoupon">{pigcms{:L_("会员优惠")}</a>
								</li>
							</if>
							<!-- <if condition="$config.open_store_live eq 1">
								<li>
									<a data-toggle="tab" href="#store_live">{pigcms{:L_("视频直播")}</a>
								</li>
							</if>
							<li>
								<a data-toggle="tab" href="#offlineShop">{pigcms{:L_("线下零售")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#store_fitment">{pigcms{:L_("页面装修")}</a>
							</li> -->
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">				
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否开启商城")}</label></label>
									<select name="store_theme">
										<option value="0" <if condition="$store_shop['store_theme'] eq 0 ">selected</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$store_shop['store_theme'] eq 1 ">selected</if>>{pigcms{:L_("开启")}</option>
									</select>
									<span class="form_tips"> {pigcms{:L_("开启：")}{pigcms{$config.mall_alias_name}{pigcms{:L_("中的商品显示为侧重图片模板，店铺中的商品设置了商城属性后，商品就会进入商城中；关闭：")}{pigcms{$config.mall_alias_name}{pigcms{:L_("中的商品显示为侧重文字模板，商品在商城中不显示。")}</span>
								</div>
                                <div class="form-group background" <if condition="$store_shop['store_theme'] eq 0">style="display:none"</if>>
                                    <label class="col-sm-1">{pigcms{:L_("商城店铺背景")}</label>
                                    <div style="display:inline-block;" id="J_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red"> {pigcms{:L_("商城店铺背景建议上传尺寸：640*420。")}</span>
                                </div>
								<div class="form-group background" <if condition="$store_shop['store_theme'] eq 0">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<if condition="$now_store['background']">
											<li class="upload_pic_li"><img src="{pigcms{$now_store['background_image']}"/><input type="hidden" name="background" value="{pigcms{$now_store['background']}"/><br/><a href="#" onclick="deleteImage('{pigcms{$now_store['background']}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</if>
										</ul>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="Config_notice">{pigcms{:L_("店铺公告")}</label></label>
									<textarea class="col-sm-3" rows="4" name="mall_notice" id="Config_notice">{pigcms{$store_shop.mall_notice}</textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("配送方式")}</label></label>
									<select name="mall_deliver_type">
									<volist name="deliver_types" id="deliver">
										<option value="{pigcms{$deliver['id']}" <if condition="$store_shop['mall_deliver_type'] eq $deliver['id']">selected</if>>{pigcms{$deliver['name']}</option>
									</volist>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("运费别名")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="freight_alias" type="text" value="{pigcms{$store_shop.freight_alias|default=L_("配送费用")}" />
									<span class="form_tips"> {pigcms{:L_("把商品从商家送到用户手上所产生的运费的费用名称（如：配送费用,运费...）。")}</span>
								</div>
								<div class="form-group">
                                    <label class="col-sm-1" for="Config_send_time"> {pigcms{:L_("出单时长")}</label>
                                    <input class="col-sm-1" size="10" maxlength="10" name="mall_work_time" id="Config_work_time" type="text" value="{pigcms{$store_shop.mall_work_time}"/>　
                                    <select name="mall_send_time_type">
                                    <option value="0" <if condition="$store_shop['mall_send_time_type'] eq 0 ">selected</if>>{pigcms{:L_("分钟")}</option>
                                    <option value="1" <if condition="$store_shop['mall_send_time_type'] eq 1 ">selected</if>>{pigcms{:L_("小时")}</option>
                                    <option value="2" <if condition="$store_shop['mall_send_time_type'] eq 2 ">selected</if>>天</option>
                                    <option value="3" <if condition="$store_shop['mall_send_time_type'] eq 3 ">selected</if>>周</option>
                                    <option value="4" <if condition="$store_shop['mall_send_time_type'] eq 4 ">selected</if>>月</option>
                                    </select>
                                    <span class="form_tips red"> {pigcms{:L_("注：从店员接单起到店员配好订单所有商品所需要的时间。")}</span>
                                </div>
							</div>
							<div id="promotion" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺折扣")}</label>
									<if condition="!empty($_SESSION['system']) AND isset($config['discount_controler']) AND  $config['discount_controler'] eq 0">
										<strong style="color:red">{pigcms{:L_("只能由商家自己设置")}</strong>
										<input class="col-sm-1" size="10" maxlength="10" name="mall_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.mall_discount}" disabled /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />
									    <strong style="color:red">{pigcms{:L_("只能由系统设置")}</strong>
										<input class="col-sm-1" size="10" maxlength="10" name="mall_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.mall_discount}" disabled /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									<else />
									<input class="col-sm-1" size="10" maxlength="10" name="mall_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.mall_discount}" /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("优惠方式")}</label></label>
									<php>if(1==$config['group_store_discount_more']){</php>
									<if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">
										<strong style="color:red">{pigcms{:L_("只能由商家自己设置")}</strong>
									<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />
									    <strong style="color:red">{pigcms{:L_("只能由系统设置")}</strong>
									<else />
									
									</if>
									<php>}</php>
									<span><label><input id='discount_type0' name="mall_discount_type" <if condition="$store_shop['mall_discount_type'] eq 0 ">checked="checked"</if> value="0" type="radio"  <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>>&nbsp;<span>{pigcms{:L_("折上折")}</span>&nbsp;</span></label>
									<span><label><input id='discount_type1' name="mall_discount_type" <if condition="$store_shop['mall_discount_type'] eq 1 ">checked="checked"</if> value="1" type="radio" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>>&nbsp;<span>{pigcms{:L_("折扣最优")}</span></span></label>
									<p style="margin-left:8.4%;">
									<strong style="color:red">({pigcms{:L_("折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；")}<br/>
									{pigcms{:L_("折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。")})
									</strong></p>
								</div>
								<div style="clear:both;"></div>
							</div>
							<if condition="!empty($levelarr)">
								<div id="levelcoupon" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！")}</label>
									</div>
								    <volist name="levelarr" id="vv">
									  <div class="form-group">
									    <input  name="mall_leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
									    <input  name="mall_leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
										<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
										{pigcms{:L_("优惠类型：")}&nbsp;
										<select name="mall_leveloff[{pigcms{$vv['level']}][type]" <php>if(1==$config['group_store_discount_more']){</php><if condition="empty($_SESSION['system']) AND $config['discount_sync'] eq 1">disabled</if> <php>}</php>>
											<option value="0">{pigcms{:L_("无优惠")}</option>
											<option value="1" <if condition="$vv['type'] eq 1">selected="selected"</if>>{pigcms{:L_("百分比（%）")}</option>
											<!--<option value="2">{pigcms{:L_("立减")}</option>-->
										</select>
										<input name="mall_leveloff[{pigcms{$vv['level']}][vv]" type="text" value="{pigcms{$vv['vv']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="empty($_SESSION['system']) AND $config['discount_sync'] eq 1">disabled</if><php>}</php> placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>
									</div>
									</volist>
								</div>
							</if>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
.webuploader-container{
	position:relative;
}
.webuploader-container div{
	width: 78px!important;
    height: 34px!important;
}
input.ke-input-text {
	background-color: #FFFFFF;
	background-color: #FFFFFF!important;
	font-family: "sans serif",tahoma,verdana,helvetica;
	font-size: 12px;
	line-height: 24px;
	height: 24px;
	padding: 2px 4px;
	border-color: #848484 #E0E0E0 #E0E0E0 #848484;
	border-style: solid;
	border-width: 1px;
	display: -moz-inline-stack;
	display: inline-block;
	vertical-align: middle;
	zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.webuploader-element-invisible {
    position: absolute !important;
    clip: rect(1px 1px 1px 1px);
    clip: rect(1px,1px,1px,1px);
}
.webuploader-pick-hover .btn{
	background-color: #629b58!important;
    border-color: #87b87f;
}
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script type="text/javascript">
$(function(){
	var deliver_type={pigcms{$now_store.deliver_type};
	var deliver_name="{pigcms{$config.deliver_name}";
	$('select[name=mall_deliver_type]').change(function(){console.log(tip);
		var val=$(this).val();
		var tip='';
		if(val==5 || val==3){
			if(deliver_type==0 || deliver_type==3){
				tip='{pigcms{:L_("该快递配送模式下可以将订单配送方式转为")}'+deliver_name;
			}else if(deliver_type==1 || deliver_type==4){
				tip='{pigcms{:L_("该快递配送模式下可以将订单配送方式转为商家配送")}';
			}
		}else if(val==2){
			tip='{pigcms{:L_("该自提模式下不可修改订单配送方式")}';
		}
		
		$(this).next().text(tip);
	});
	$('select[name=mall_deliver_type]').change();
});
</script>
<script>
var uploader = WebUploader.create({
	auto: true,
	swf: '{pigcms{$static_public}js/Uploader.swf',
	server: "{pigcms{:U('Mall/ajax_upload_shoppic', array('store_id' => $now_store['store_id']))}",
	pick: '#J_selectImage',
	accept: {
		title: 'Images',
		extensions: 'gif,jpg,jpeg,png',
		mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
	}
});
uploader.on('fileQueued',function(file){
	if($('.upload_pic_li').size() >= 1){
		uploader.cancelFile(file);
		alert('{pigcms{:L_("最多上传一张图片！")}');
		return false;
	}
});
uploader.on('uploadSuccess',function(file,response){
	if(response.error == 0){
		$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+response.url+'"/><input type="hidden" name="background" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
	}else{
		alert(response.info);
	}
});

uploader.on('uploadError', function(file,reason){
	$('.loading'+file.id).remove();
	alert('{pigcms{:L_("上传失败！请重试。")}');
});


function deleteImage(path,obj){
	$.post("{pigcms{:U('Mall/ajax_del_shoppic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}

function check(obj){
	var length = $('.paycheck:checked').length;
	if(length == 0){
		$(obj).attr('checked','checked');
		bootbox.alert('{pigcms{:L_("最少要选择一种支付方式")}');
	}			
}

var fitment_header_bgcolor = "{pigcms{$now_store.shop_fitment_color|default='06C1AE'}";
$(function($){
    
	$('select[name=store_theme]').change(function(){
		if ($(this).val() == 1) {
			$('.background').show();
		} else {
			$('.background').hide();
		}
	});
	$('#delivertime_start').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
	$('#delivertime_stop').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
	$('#delivertime_start2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#delivertime_stop2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
    $('#delivertime_start3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#delivertime_stop3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));

	$('#edit_form').submit(function(){
		$.post("{pigcms{:U('Mall/shop_edit',array('store_id'=>$_GET['store_id']))}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Mall/index')}";
			}else{
				alert(result.info);
			}
		})
		return false;
	});
	
	$('.fitment').click(function(){
		var _this = $(this);
		layer.open({
			title:'{pigcms{:L_("页面装修")} -> ' + _this.data('title'),
			type:2,
			fixed:false,
			content:'{pigcms{$this->config['site_url']}/merchant.php?c=Shop&a='+_this.data('type')+'&store_id={pigcms{$_GET.store_id}',
			area:['600px',($(window).height()-$('#store_fitment').offset().top-100)+'px'],
			offset:[$('#store_fitment').offset().top+'px',$('#store_fitment').offset().left-30+'px'],
			shade: 0.01,
			cancel:function(){
				if(_this.data('type') == 'shop_fitment_color'){
					$('#fitment_preview .fitment_header').css('background-color','#' + fitment_header_bgcolor);
				}
			},
			move:false
			// ,shadeClose: false
		});
	});
	
	$('.fitment_subject img').click(function(){
		$('.fitment.subject').trigger('click');
	});
	$('.fitment_showcase img').click(function(){
		$('.fitment.showcase').trigger('click');
	});
	
	console.log($('.page-content').width());
	$('#fitment_preview').css('left',(500 + ($('.page-content').width()-500 - 350)/2)+'px');
});
</script>
<style type="text/css">
	#store_fitment{
		margin-left:30px;
		position:relative;
	}
	.store_fitment_div{
		width:500px;
	}
	#store_fitment .form-group{
		border-bottom:1px solid #EEE;
		padding:15px 0;
	}
	#store_fitment .form-group.cat-group{
		border-bottom:0;
	}
	#fitment_preview{
		position: absolute;
		left: 650px;
		top: 0;
		width:315px;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
	}
	#fitment_preview img{
		width:100%;
	}
	.fitment_subject img:hover,.fitment_showcase img:hover{
		cursor:pointer;
		border:2px solid blue;
	}
	
	.group-title{
		position:relative;
		font-size:18px;
		color:black;
		margin-bottom:10px;
	}
	.group-content{
		width:320px;
		line-height:1.6;
		font-size:14px;
		color:#999;
	}
	.group-title .btn.fitment{
		background-color: #438eb9!important;
		border-radius: 3px;
		border: none;
		position:absolute;
		right:0px;
		top:0px;
	}
	.group-content.cat{
		width:100%;
	}
	.group-content .cat-row{
		margin-top:20px;
		position:relative;
	}
	.group-content .cat-name{
		font-size:16px;
		color:black;
		float:left;
		width:30%;
	}
	.group-content .cat-desc{
		font-size:14px;
		float:left;
		width:70%;
		border-bottom:1px solid #EEE;
		padding-bottom: 20px;
	}
	.group-content .cat-edit{
		position:absolute;
		right:0px;
		top:0px;
	}
</style>
<include file="Public:footer"/>
