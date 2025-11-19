<php>if($_GET["is_show"]){</php>
	<include file="Public:header"/>
<php>}else{</php>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<title>{pigcms{$config.site_name} - {pigcms{:L_("商家中心")}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace-extra.min.js"></script>


<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>

<!-- page specific plugin scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/bootbox.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.custom.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ui.touch-punch.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.easypiechart.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.sparkline.min.js"></script>

<!-- ace scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/ace-elements.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace.min.js"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/jquery.yiigridview.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-i18n.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-timepicker-addon.min.js"></script>
<style type="text/css">
html{
	background:#fff;
}
.jqstooltip {
	position: absolute;
	left: 0px;
	top: 0px;
	visibility: hidden;
	background: rgb(0, 0, 0) transparent;
	background-color: rgba(0, 0, 0, 0.6);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
	-ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
	color: white;
	font: 10px arial, san serif;
	text-align: left;
	white-space: nowrap;
	padding: 5px;
	border: 1px solid white;
	z-index: 10000;
}

.jqsfield {
	color: white;
	font: 10px arial, san serif;
	text-align: left;
}

.statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
	display: none;
}

#shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
	{
	font-size: 12px;
	color: black;
	display: none;
	width: 100%;
}
</style>
<script type="text/javascript">
	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
</script>

</head>

<body class="no-skin">
<php>}</php>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>

				<a href="{pigcms{:U('Appoint/index')}">{pigcms{:L_("预约管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑预约")}</li>
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
							<li>
								<a data-toggle="tab" href="#txtstore">{pigcms{:L_("选择店铺")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtimage">{pigcms{:L_("介绍图片")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtattr">{pigcms{:L_("规格属性")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtorder">{pigcms{:L_("状态设置")}</a>
							</li>
                            <li>
                                <a data-toggle="tab" href="#txtmarketing">{pigcms{:L_("营销设置")}</a>
                            </li>
							<if condition="$config.open_single_spread eq 1 AND $_GET['system_file'] eq 'admin.php'">
							<li>
								<a data-toggle="tab" href="#distributor_percent">分销佣金比例</a>
							</li>
							</if>
							<if condition="!empty($_SESSION['system'])">
							<li>
								<a data-toggle="tab" href="#score">{pigcms{:L_("积分设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#take_a_percentage">{pigcms{:L_("抽成比例")}</a>
							</li>
							</if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("预约名称：")}</label>
									<input class="col-sm-3" maxlength="30" name="appoint_name" type="text" value="{pigcms{$appoint_list.appoint_name}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。在预约页显示此名称！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("预约简介：")}</label>
									<textarea class="col-sm-3" rows="5" name="appoint_content">{pigcms{$appoint_list.appoint_content}</textarea><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("预约的简短介绍，建议为100字以下。")}</span>
								</div>
								<div class="form-group"></div>

								<div class="form-group" <if condition="$config.appoint_rule neq 1">style="display:none;"</if>>
									<label class="col-sm-1">{pigcms{:L_("收取定金")}:</label>
                    <if condition="$appoint_list['payment_status'] eq 1">
                      <input  type="hidden" value="1" name="payment_status">
                      <span style="z-index: 1" class="lbl">{pigcms{:L_("开启")}</span>
                    </if>
                  <if condition="$appoint_list['payment_status'] eq 0">
                    <span style="z-index: 1" class="lbl">{pigcms{:L_("关闭")}</span>
                  </if>
								</div>
								<div class="form-group" id="payment_money" <if condition="$appoint_list['payment_status'] eq 0 or $config.appoint_rule neq 1"> style="display:none;" <else /> style="display:block;" </if>>
									<label class="col-sm-1">{pigcms{:L_("定金：")}</label>
									<input class="col-sm-1" maxlength="100" name="payment_money" type="text" value="{pigcms{$appoint_list['payment_money']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("最多支持2位小数")}</span>
								</div>
                            <div class="form-group" id="is_refund" <if condition="$appoint_list['payment_status'] eq 0"> style="display:none;" <else /> style="display:block;" </if>>
                                <label class="col-sm-1">{pigcms{:L_("定金退款")}</label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$appoint_list['is_refund'] eq 1">checked="checked"</if> class="ace" value="1" name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("不可退款")}</span></label>
<!--                                <label style="padding-left:0px;"><input type="radio" class="ace" value="1" <if condition="$appoint_list['is_refund'] eq 1">checked="checked"</if> name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("直接退款")}</span></label>-->
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="2" <if condition="$appoint_list['is_refund'] eq 2">checked="checked"</if> name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("需要申请退款")}</span></label>
                            </div>
								<!--div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("全价：")}</label>

									<input class="col-sm-1" maxlength="30" name="appoint_price" type="text" value="{pigcms{$appoint_list['appoint_price']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。最多支持2位小数")}</span>
								</div-->

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("全价：")}</label>
                  <if condition='$appoint_list["is_appoint_price"] eq 0'>
                  <div style="width:60px;padding-left:0px;" class="col-sm-1">
                    {pigcms{:L_("面议")}
                  </div>
                  </if>
                                    <input type="hidden" value="{pigcms{$appoint_list['is_appoint_price']}" name="is_appoint_price">
									<div <if condition='$appoint_list["is_appoint_price"] eq 0'>style="display:none"</if> id="appoint_price"><input class="col-sm-1" maxlength="30" name="appoint_price" type="text" value="{pigcms{$appoint_list['appoint_price']}" />
									<if condition="$config.open_extra_price eq 1 AND $appoint_list.extra_pay_price gt 0">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="{pigcms{$appoint_list['extra_pay_price']}" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips red">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>

									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。最多支持2位小数（超过后，系统自动截取）")}</span></div>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("是否使用二维码核销")}</label>
									<label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="0" name="is_code_verify" <if condition="$appoint_list['is_code_verify'] neq 1">checked="checked"</if>><span style="z-index: 1" class="lbl">{pigcms{:L_("不使用")}</span></label>
									<label style="padding-left:0px;"><input type="radio" class="ace" value="1" name="is_code_verify" <if condition="$appoint_list['is_code_verify'] eq 1">checked="checked"</if> ><span style="z-index: 1" class="lbl">{pigcms{:L_("使用")}</span></label>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("耗时：")}</label>
									<input class="col-sm-1" maxlength="30" name="expend_time" type="text" value="{pigcms{$appoint_list['expend_time']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。单位：分钟")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("排序：")}</label>
									<input class="col-sm-1" maxlength="30" name="sort" type="text" value="{pigcms{$appoint_list['sort']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("值越大，越往前显示。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("虚拟预约量：")}</label>
									<input class="col-sm-1" maxlength="30" name="virtual_sales" type="text" value="{pigcms{$appoint_list['virtual_sales']}" />
								</div>

								<div class="form-group"></div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("开始时间：")}</label>
									<input class="col-sm-2 Wdate" type="text" readonly style="height:30px;" onfocus="WdatePicker({minDate:'{pigcms{:date('Y-m-d',$_SERVER['REQUEST_TIME'])}',isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日',startDate:'{pigcms{$appoint_list["start_time"]}',vel:'start_time'})" value="{pigcms{$appoint_list['start_time']|date='Y年m月d日',###}"/>
									<input name="start_time" id="start_time" type="hidden" value="{pigcms{$appoint_list['start_time']|date='Y-m-d',###}"/>
									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("到了开始时间，会自动显示！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("结束时间：")}</label>
									<input class="col-sm-2 Wdate" type="text" readonly style="height:30px;" onfocus="WdatePicker({minDate:'{pigcms{:date('Y-m-d',$_SERVER['REQUEST_TIME'])}',isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日',startDate:'{pigcms{$appoint_list["end_time"]|date="Y-m-d",###}',vel:'end_time'})" value="{pigcms{$appoint_list['end_time']|date='Y年m月d日',###}"/>
									<input name="end_time" id="end_time" type="hidden" value="{pigcms{$appoint_list['end_time']|date='Y-m-d',###}"/>
									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("超过结束时间，会自动结束！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("预约类别：")}</label>
									<select name="appoint_type" class="col-sm-2">
										<option value="0" <if condition="$appoint_list['appoint_type'] eq 0">selected="selected"</if>>{pigcms{:L_("到店")}</option>
										<option value="1" <if condition="$appoint_list['appoint_type'] eq 1">selected="selected"</if>>{pigcms{:L_("上门")}</option>
									</select>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择分类：")}</label>
									<select id="choose_catfid" name="cat_fid" class="col-sm-1" style="margin-right:10px;">
										<volist name="f_category_list" id="vo">
											<option value="{pigcms{$vo.cat_id}" <if condition="$appoint_list['cat_fid'] eq $vo['cat_id']">selected="selected"</if>>{pigcms{$vo.cat_name}</option>
										</volist>
									</select>
									<select id="choose_catid" name="cat_id" class="col-sm-1" style="margin-right:10px;">
										<volist name="s_category_list" id="vo">
											<option value="{pigcms{$vo.cat_id}" <if condition="$appoint_list['cat_id'] eq $vo['cat_id']">selected="selected"</if> data-is-autotrophic="{pigcms{$vo.is_autotrophic}">{pigcms{$vo.cat_name}</option>
										</volist>
									</select>
									<span class="form_tips red" <if condition='$appoint_list["is_autotrophic"] neq 1'>style="display:none"</if> id="autotrophic_warn">*{pigcms{:L_("此分类为平台自营，须等待平台派单，用户无法直接浏览。到店类别请勿选择自营，无法正常派单。")}</span>
								</div>

								<div class="form-group" >
									<label class="col-sm-1">{pigcms{:L_("预约详情：")}<br/></label>
									<textarea name="appoint_pic_content" id="content" style="width:702px;">{pigcms{:replace_file_domain_content($appoint_list['appoint_pic_content'])}</textarea>
								</div>

								<div class="tabbable">
									<ul class="nav nav-tabs" id="myTab">
										<li class="active">
											<a data-toggle="tab" href="#shop_time_1">
												{pigcms{:L_("营业时间段")}
											</a>
										</li>
									</ul>
									<div class="tab-content" id="office_time">
										<volist name="office_time" id="vo" key="index">
											<div id="shop_time_1" class="tab-pane in active" style="margin-top: 5px;">
												<div>
													<input class="Config_shop_start_time" type="text" value="{pigcms{$vo['open']}" name="office_start_time[]" />&nbsp;&nbsp;至&nbsp;&nbsp;
													<input class="Config_shop_stop_time" type="text" value="{pigcms{$vo['close']}" name="office_stop_time[]" />
													<div class="errorMessage" id="Config_shop_start_time_em_" style="display:none"></div>
													<div class="errorMessage" id="Config_shop_stop_time_em_" style="display:none"></div>
                                                    <if condition="$index egt 2">
                                                        <button type="button" class="reduce_recharge"></button>
                                                    </if>
													<span class="form_tips red <if condition="$index egt 2">add_tips_info</if>">&nbsp;*&nbsp;{pigcms{:L_("如果营业时间段设置为")}00:00-00:00{pigcms{:L_("，则表示24小时营业")}</span>
												</div>
											</div>
										</volist>
									</div>
									<a class="btn btn-success" id="add_shop_time" style="margin-top: 5px">{pigcms{:L_("添加时间段")}</a>
                                    <div class="form-group"></div>
                                    <!--div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("限定人数：")}</label>
											<input class="col-sm-1" maxlength="100" name="appoint_people" type="text" value="{pigcms{$appoint_list['appoint_people']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("限制每个时间点的预约人数，0为不限制")}</span>
										</div-->
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("时间间隔：")}</label>
										<input class="col-sm-1" maxlength="100" name="time_gap" type="text" value="{pigcms{$appoint_list['time_gap']}" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("不填间隔，显示营业时间间隔")}</span>
									</div>

								</div>
							</div>
							<div id="txtstore" class="tab-pane">
                             <div class="form-group">
									<label style="margin-left:20px"><label for="is_store">{pigcms{:L_("是否开启选择店铺：")}</label></label>

										<label style="margin-left:10px"><input type="radio" <if condition='$appoint_list["is_store"] eq 1'>checked="checked"</if> class="ace" value="1" name="is_store"><span style="z-index: 1" class="lbl">{pigcms{:L_("开启")}</span></label>
										<label style="margin-left:10px"><input type="radio" <if condition='$appoint_list["is_store"] eq 0'>checked="checked"</if> class="ace" value="0" name="is_store"><span style="z-index: 1" class="lbl">{pigcms{:L_("关闭")}</span></label>
										<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("关闭后，订单将由商家指定门店及")}<?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("进行服务。")}</span>

								</div>
								<div class="form-group store_list" <if condition='$appoint_list["is_store"] eq 0'>style="display:none"</if>>
									<volist name="store_list" id="vo">
										<div class="radio">
											<label>
												<input class="paycheck ace store-list" type="checkbox" name="store[]" value="{pigcms{$vo.store_id}" id="store_{pigcms{$vo.store_id}" <if condition="in_array($vo['store_id'],$store_arr)">checked="checked"</if>/>
												<span class="lbl"><label for="store_{pigcms{$vo.store_id}">{pigcms{$vo.name} - {pigcms{$vo.area_name}-{pigcms{$vo.adress}</label></span>
												<label><input class="paycheck ace " name="store_sort[]" value="{pigcms{$vo.store_sort}" type="text" style="width:350px" placeholder="{pigcms{:L_("排序值(此排序控制店铺内预约商品的前后顺序。")})" />
											</label>
										</div>

                                        <div style=" margin:5px 0 0 60px;" class="worker-list" data-store-id="{pigcms{$vo.store_id}">
                                        	<volist name="vo['worker_list']" id="val">
                                                <label style="margin-right:10px">
                                                    <input class="paycheck ace" type="checkbox" <if condition="in_array($val['merchant_worker_id'],$merchant_workers_appoint_list)">checked</if> name="worker_memus[]" value="{pigcms{$vo.store_id},{pigcms{$val.merchant_worker_id}" id="worker{pigcms{$val.merchant_worker_id}"/>
                                                    <span class="lbl"><label for="worker{pigcms{$val.merchant_worker_id}">&nbsp;&nbsp;{pigcms{$val.name}</label></span>
                                                </label>
                                            </volist>
                                            </div>

									</volist>
								
								</div>
							</div>
							<div id="txtimage" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("上传图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success input-file-none" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！")}{pigcms{:L_("图片建议为：")}400*400</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="pic_list" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImg('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</volist>
										</ul>
									</div>
								</div>
							</div>


							<div id="txtattr" class="tab-pane">
								<label style="margin-left:20px"><label for="is_store">{pigcms{:L_("规格类型：")}</label></label>
						
<!--								<select name="product_type" style="margin-left:10px">-->
<!--									<option value="0" <if condition="$appoint_list['product_type'] eq 0">selected="selected"</if>>{pigcms{:L_("开启时间段")}</option>-->
<!--									<option value="1" <if condition="$appoint_list['product_type'] eq 1">selected="selected"</if>>{pigcms{:L_("关闭时间段")}</option>-->
<!--								</select>-->
<!--								<span class="form_tips red">{pigcms{:L_("开启，表示预约商品与店员的时间段照常显示在前台，反之，则用户前台隐藏选择时间段功能")}	</span>-->
								<div style="border:1px solid #c5d0dc;/*padding-left:22px;*/margin-bottom:10px;" id="cue_html_tips">
									<table class="table table-striped">
										<tbody>
											<tr>
												<th scope="col">{pigcms{:L_("规格序号")}</th>
												<th scope="col">{pigcms{:L_("规格名称")}</th>
												<th scope="col">{pigcms{:L_("规格定金")}</th>
												<th scope="col">{pigcms{:L_("规格全价")}</th>
												<th scope="col">{pigcms{:L_("规格描述")}</th>
                                                <th scope="col">{pigcms{:L_("平均用时")}</th>
											</tr>
											<?php foreach($product_list as $key => $val): ?>
												<tr class="parent">
													<td><i class="ace-icon fa"></i>{pigcms{:L_("序号")}<?php echo $key+1; ?></td>
													<td><input name="custom_name_s[]" type="text" class="span2 title" value="<?php echo $val['name']; ?>"></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_payment_price_s[]" type="text" class="span2 keyword" value="<?php echo $val['payment_price']; ?>"></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_price_s[]" type="text" class="span2 keyword" value="<?php echo $val['price']; ?>"></td>
													<td><input name="custom_content_s[]" type="text" class="span3 url" value="<?php echo $val['content']; ?>"></td>
                                                    <td><input name="custom_use_time_s[]" type="text" class="span3 url" value="<?php echo $val['use_time']; ?>"></td>
													<input name="custom_id_s[]" type="hidden" value="<?php echo $val['id']; ?>" />
												</tr>
											<?php endforeach; ?>
											<?php for($i=(count($product_list))+1; $i<=40; $i++): ?>
												<tr class="parent">
													<td><i class="ace-icon fa"></i>{pigcms{:L_("序号")}<?php echo $i; ?></td>
													<td><input name="custom_name[]" type="text" class="span2 title" value=""></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_payment_price[]" type="text" class="span2 keyword" value=""></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_price[]" type="text" class="span2 keyword" value=""></td>
													<td><input name="custom_content[]" type="text" class="span3 url" value=""></td>
                                                    <td><input name="custom_use_time[]" type="text" class="span3 url" value=""></td>
												</tr>
											<?php endfor; ?>
										</tbody>
									</table>
								</div>
							</div>


							<div id="txtorder" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("预约状态：")}</label>
									<select name="appoint_status" class="col-sm-1">
										<option value="0" <if condition="$appoint_list['appoint_status'] eq 0">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										<option value="1" <if condition="$appoint_list['appoint_status'] eq 1">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
									</select>
									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("为了方便用户能查找到以前的订单，预约无法删除！")}</span>
								</div>
							</div>

                            <div id="txtmarketing" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("关联预约商品推荐：")}</label>
                                    <label>
                                        <a class="btn btn-success choose_good_btn" onclick="select_goods()">{pigcms{:L_("选择商品")}</a>
                                        <span class="form_tips red">{pigcms{:L_("设置后，该商品详情页【店铺推荐】会展示选中的商品")}</span>
                                        <table style="width:800px;margin-top: 10px;" class="table table-bordered" id='goods_spec'>
                                            <thead>
                                            <tr>
                                                <th>{pigcms{:L_("服务ID")}</th>
                                                <th>{pigcms{:L_("服务名称")}</th>
                                                <th>{pigcms{:L_("价格")}</th>
                                                <th>{pigcms{:L_("预约状态")}</th>
                                                <th>{pigcms{:L_("操作")}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <volist name="recommend_goods" id="goods">
                                                <tr>
                                                    <input type='hidden' name="relation_goods_id[]" value="{pigcms{$goods.appoint_id}"/>
                                                    <td>{pigcms{$goods.appoint_id}</td>
                                                    <td>{pigcms{$goods.appoint_name}</td>
                                                    <td>
                                                        {pigcms{:L_("定金")}：
                                                        <if condition="$goods.payment_status eq 0">--
                                                            <else/>
                                                            {pigcms{$config['Currency_symbol']}{pigcms{$goods.payment_money}
                                                        </if>
                                                        <br/>
                                                        {pigcms{:L_("全价")}：
                                                        <if condition="$goods.is_appoint_price eq 0">面议
                                                            <else/>
                                                            {pigcms{$config['Currency_symbol']}{pigcms{$goods.appoint_price}
                                                        </if>
                                                    </td>
                                                    <td>
                                                        <if condition="$goods['appoint_status'] eq 0"><span style="color:green">{pigcms{:L_("开启")}</span>
                                                            <else/>
                                                            <span style="color:red">{pigcms{:L_("关闭")}</span>
                                                        </if>
                                                    </td>
                                                    <td>
                                                        <a type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</a>
                                                    </td>
                                                </tr>
                                            </volist>
                                    </label>
                                    </tbody>
                                    </table>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("购买赠券：")}</label>
                                    <label>
                                        <a href="javascript:void(0)" class="btn btn-sm btn-success"
                                           onclick="select_mer_coupon()">{pigcms{:L_("选择优惠券")}</a>
                                        <span class="form_tips red">{pigcms{:L_("设置购买该商品可赠送的优惠券")}</span>
                                        <input type='hidden' id="buy_give_coupon_id" name="buy_give_coupon_id"
                                               value="{pigcms{$appoint_list.buy_give_coupon_id}"/>
                                        <table style="width:800px;margin-top: 10px;" class="table table-bordered"
                                               id='coupon_spec'>
                                            <thead>
                                            <tr>
                                                <th>{pigcms{:L_("优惠券ID")}</th>
                                                <th>{pigcms{:L_("名称")}</th>
                                                <th>{pigcms{:L_("状态")}</th>
                                                <th>{pigcms{:L_("操作")}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <volist name="give_coupon" id="coupon">
                                                <tr>
                                                    <td>{pigcms{$coupon.coupon_id}</td>
                                                    <td>{pigcms{$coupon.name}</td>
                                                    <td>
                                                        <if condition="$coupon['status'] eq 1"><span
                                                                    style="color:green">{pigcms{:L_("开启")}</span>
                                                            <else/>
                                                            <span style="color:red">{pigcms{:L_("不可用")}</span>
                                                        </if>
                                                    </td>
                                                    <td>
                                                        <a type="button" onClick="delCouponThis(this)">{pigcms{:L_("移除")}</a>
                                                    </td>
                                                </tr>
                                            </volist>
                                            </tbody>
                                        </table>
                                    </label>
                                </div>
                            </div>

							<if condition="!empty($_SESSION['system'])">
							<div id="score" class="tab-pane">

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("消费1元所获得积分")}</label></label>
									<div>
										<input name="score_percent" type="text" value="{pigcms{$appoint_list.score_percent}" /><b style="color:red">{pigcms{:L_("请填写一个数字")}</b>
									</div>
								</div>
							</div>
							<div id="take_a_percentage" class="tab-pane">

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("抽成比例")}</label></label>
									<div>
										<input name="take_a_percentage" type="text" value="{pigcms{$appoint_list.take_a_percentage}" /><b style="color:red">{pigcms{:L_("请填写一个0-100的数字")}</b>
									</div>
								</div>
							</div>
							</if>

							<if condition="$config.open_single_spread eq 1 AND $_GET['system_file'] eq 'admin.php'">
								<div id="distributor_percent" class="tab-pane">
									<div class="form-group">
										<label  class="col-sm-1">{pigcms{:L_("分销佣金比例设置")}</label>
										<input class="col-sm-1 " maxlength="20" name="distributor_percent" type="num" value="{pigcms{$appoint_list.distributor_percent|floatval}" />
										<span class="form_tips">{pigcms{:L_("分销佣金比例设置，30表示佣金30%，0表示无佣金，不用填%号")}
										</span>
										
									</div>
								</div>
							</if>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9" style="margin-left: 0px;">
									<button  style="float: left;" class="btn btn-info" type="submit" id="save_btn"><i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("保存")}</button>
									<if condition="$is_admin AND ($appoint_list['check_status'] eq 0)">
										<input type="hidden" name="is_admin_operate" value="{pigcms{$is_admin|default=0}">
										<span style="line-height: 40px;margin-left: 2rem;color: red;">{pigcms{:L_("当前预约商品还未审核，点击保存即代表审核通过，请确认信息后操作")}</span>
									</if>
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
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.reduce_recharge {
    background: url("{pigcms{$static_path}css/img/reduce_nc.png") no-repeat;
    width: 32px;
    height: 32px;
    border: none;
    position: absolute;
    margin-left: 5px;
}
.add_tips_info {
    margin-left: 40px;
}
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>


<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js?{pigcms{$config.system_version}"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/video_upload.js?1"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>


<script type="text/javascript">
	var uploaderHas = false;
	var uploaderHas2 = false;
	var swf='{pigcms{$static_public}js/Uploader.swf';
	var server="{pigcms{:U('Appoint/ajax_upload_pic_test', array('store_id' => $now_store['store_id']))}";
	//视频上传的地址
	var _backEndUrl="{pigcms{:U('Mall/vupload')}";
	//删除上传中的缓存视频
	var del_video_cache_url="{pigcms{:U('Mall/del_video_cache')}";
	var diyVideo = "{pigcms{:U('Article/diyVideo')}";
	$('#myTab li a').click(function(){
		if(uploaderHas == false && $(this).attr('href') == '#txtimage'){
			setTimeout(function(){
				var  uploader = WebUploader.create({
					auto: true,
					swf:swf ,
					server: server,
					pick: {
						id:'#J_selectImage',
						multiple:false
					},
					accept: {
						title: 'Images',
						extensions: 'gif,jpg,jpeg,png',
						mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
					}
				});
				uploader.on('fileQueued',function(file){
					if($('.upload_pic_li').size() >= 5){
						uploader.cancelFile(file);
						alert('最多上传5个图片！');
						return false;
					}
				});
				uploader.on('uploadSuccess',function(file,response){
					if(response.error == 0){
						$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+response.url+'"/><input type="hidden" name="pic[]" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ 删除 ]</a></li>');
					}else{
						alert(response.info);
					}
				});

				uploader.on('uploadError', function(file,reason){
					$('.loading'+file.id).remove();
					alert('上传失败！请重试。');
				});
				$('.input-file-none input[type="file"]').css('display','none')
			},20);
			uploaderHas = true;
		}
		if(uploaderHas2 == false && $(this).attr('href') == '#txtattr'){
			add_file_upload();
			uploaderHas2 = true;
		}
	});




	$(function($){
	$('#Config_shop_start_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	$('#Config_shop_stop_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	$('#Config_shop_start_time_2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	$('#Config_shop_stop_time_2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	$('#Config_shop_start_time_3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
	$('#Config_shop_stop_time_3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
});
</script>
<script type="text/javascript">
$(".tab-content").on("focus",".Config_shop_start_time",function(){
　　　$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
});
$(".tab-content").on("focus",".Config_shop_stop_time",function(){
　　　$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
});

// "添加时间段")}
$('#add_shop_time').click(function(){
	var html_div = '';
	var end_time = $("input[name='office_stop_time[]']:last").val();
	var arr = end_time.split(':');
	console.log(arr);

	if (parseInt(arr[0]) >= 23 && parseInt(arr[1]) >= 59 ) {
		alert('{pigcms{:L_("不能添加时间段")}');
		return false;
	}

	html_div = '<div id="shop_time_1" style="margin-top: 5px;" class="tab-pane in active"><div><input class="Config_shop_start_time" type="text" value="'+end_time+'" name="office_start_time[]"/>&nbsp;&nbsp;至	&nbsp;&nbsp;<input class="Config_shop_stop_time" type="text" value="" name="office_stop_time[]" /><button type="button" class="reduce_recharge"></button><span class="form_tips red add_tips_info">&nbsp;*&nbsp;{pigcms{:L_("如果营业时间段设置为")}00:00-00:00{pigcms{:L_("，则表示24小时营业")}</span></div></div>';
	$('#office_time').append(html_div);
    // "删除")}
    $(".reduce_recharge").click(function() {
        if($('#office_time').children().length<=1){
            $(this).val('');
        }else{
            $(this).css('visibility','hidden');
            $(this).parents('#shop_time_1').remove();
        }
    });
});
// "删除")}
$(".reduce_recharge").click(function() {
    if($('#office_time').children().length<=1){
        $(this).val('');
    }else{
        $(this).css('visibility','hidden');
        $(this).parents('#shop_time_1').remove();
    }
});

var diyVideo = "{pigcms{:U('Article/diyVideo')}";
KindEditor.ready(function(K) {
	var content_editor = K.create("#content",{
		width:'702px',
		height:'260px',
		langType : "{pigcms{$config.system_lang_alias}",
		resizeType : 1,
		allowPreviewEmoticons:false,
		allowImageUpload : true,
		filterMode: true,
		autoHeightMode : true,
		afterCreate : function() {
			this.loadPlugin('autoheight');
		},
		items : [
			'fullscreen', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'link', 'table','diyVideo'
		],
		emoticonsPath : './static/emoticons/',
		uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=appoint/content",
		cssPath : "{pigcms{$static_path}css/group_editor.css"
	});

	var editor = K.editor({
		allowFileManager : true
	});
//	K('#J_selectImage').click(function(){
//		if($('.upload_pic_li').size() >= 5){
//			alert('{pigcms{:L_("最多上传5个图片！")}');
//			return false;
//		}
//		editor.uploadJson = "{pigcms{:U('Appoint/ajax_upload_pic')}";
//		editor.loadPlugin('image', function(){
//			editor.plugin.imageDialog({
//				showRemote : false,
//				imageUrl : K('#course_pic').val(),
//				clickFn : function(url, title, width, height, border, align) {
//					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImg(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
//					editor.hideDialog();
//				}
//			});
//		});
//	});

	$('#choose_catfid').change(function(){
		$.getJSON("{pigcms{:U('Appoint/ajax_get_category')}",{cat_fid:$(this).val()},function(result){
			var html = '';
			if(result.error == 0){
				for ( var i=0; i<result.cat_list.length; i++){
                    html += '<option value="'+ result.cat_list[i].cat_id +'" data-is-autotrophic="'+result.cat_list[i].is_autotrophic+'">' + result.cat_list[i].cat_name + '</option>';
                }
                $('#choose_catid').html(html);
            } else {
                $("#choose_catid").html(html);
            }
		});
	});

	$('#choose_catid').change(function(){
		var cat_id = $(this).val();
		var is_autotrophic = $(this).find(':selected').data('is-autotrophic');
		$('#cat_id').attr('value', cat_id);
		if(is_autotrophic==1){
			$('#autotrophic_warn').show();
		}else{
			$('#autotrophic_warn').hide();
		}
	});

	$('#add_form').submit(function(){
        var start_time = $('input[name="start_time"]').val();
        var end_time = $('input[name="end_time"]').val();
        if(start_time == end_time){
            alert('{pigcms{:L_("不能选择相同时间")}');
            return false;
        }
        content_editor.sync();
		$('#save_btn').prop('disabled',true);
		$.post("{pigcms{:U('Appoint/frame_edit', array('appoint_id'=>$appoint_list['appoint_id']))}",$('#add_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
                sessionStorage.setItem("appoint_edit_load", 1);
				window.history.go(-1);
			}else{
				alert(result.info);
			}
			$('#save_btn').prop('disabled',false);
		})
		return false;
	});

	$('#editor_plan_btn').click(function(){
		var dialog = K.dialog({
				width : 200,
				title : '{pigcms{:L_("输入欲插入表格行数")}',
				body : '<div style="margin:10px;"><input id="edit_plan_input" style="width:100%;"/></div>',
				closeBtn : {
						name : '{pigcms{:L_("关闭")}',
						click : function(e) {
							dialog.remove();
						}
				},
				yesBtn : {
						name : '{pigcms{:L_("确定")}',
						click : function(e){
							var value = $('#edit_plan_input').val();
							if(!/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(value)){
								alert('{pigcms{:L_("请输入数字！")}');
								return false;
							}
							value = parseInt(value);
							var html = '<table class="deal-menu">';
							html += '<tr><th class="name" colspan="2">{pigcms{:L_("套餐内容")}</th><th class="price">{pigcms{:L_("单价")}</th><th class="amount">{pigcms{:L_("数量")}/{pigcms{:L_("规格")}</th><th class="subtotal">{pigcms{:L_("小计")}</th></tr>';
							for(var i=0;i<value;i++){
								html += '<tr><td class="name" colspan="2">{pigcms{:L_("内容")}'+(i+1)+'</td><td class="price">¥</td><td class="amount">1份</td><td class="subtotal">¥</td></tr>';
							}
							html += '</table>';
							html += '<p class="deal-menu-summary">{pigcms{:L_("价值")}: <span class="inline-block worth">¥</span>{pigcms{$config.group_alias_name}{pigcms{:L_("价：")} <span class="inline-block worth price">¥</span></p><br/><br/>{pigcms{:L_("介绍")}...';
							content_editor.appendHtml(html);

							dialog.remove();
						}
				},
				noBtn : {
						name : '{pigcms{:L_("取消")}',
						click : function(e) {
							dialog.remove();
						}
				}
		});
	});
});

	$('input[name="is_store"]').each(function(i){
		$(this).click(function(){
			if(i == 0){
				$('.store_list').show();
			}else{
				$('.store_list').hide();
			}
		});
	});
	function deleteImg(path,obj){
		$.post("{pigcms{:U('Appoint/ajax_del_pic')}",{path:path});
		$(obj).closest('.upload_pic_li').remove();
	}

	function paymentHide(){
		$('#payment_money').hide();
		$('#is_refund').hide();
	}
	function paymentShow(){
		$('#payment_money').show();
        $('#is_refund').show();
	}

	$('select[name="appoint_type"]').change(function(){
		var appoint_type=$('select[name="appoint_type"]').val();
		get_worker_list(appoint_type)
	});




$('.store-list').each(function(){
	var flag=$(this).is(':checked');

	if(flag){
			$(this).parents('.radio').next('.worker-list').show();
			flag=false;
		}else{
			var obj=$(this).parents('.radio').next('.worker-list');
			obj.hide();
			obj.children('label').each(function(){
				$(this).children('input').removeAttr('checked');
			});
			flag=true;
		}
	$(this).click(function(){
		if(flag){
			$(this).parents('.radio').next('.worker-list').show();
			flag=false;
		}else{
			var obj=$(this).parents('.radio').next('.worker-list');
			obj.hide();
			obj.children('label').each(function(){
				$(this).children('input').removeAttr('checked');
			});
			flag=true;
		}
	});
});

var appoint_type=$('select[name="appoint_type"]').val();
var appoint_id = {pigcms{$_GET['appoint_id']}+0;
get_worker_list(appoint_type);
function get_worker_list(appoint_type){
		var url="{pigcms{:U('Appoint/ajax_worker_list')}";
		$.post(url,{'appoint_type':appoint_type,'appoint_id':appoint_id},function(data){
			$('.worker-list').each(function(){
				var store_id=$(this).data('store-id');
				var shtml='';
				for(var i in data){
					if(data[i]['merchant_store_id']==store_id){
						shtml+='<label style="margin-right:10px"><input type="checkbox"';
						if(data[i]['selected']){
							shtml+='checked="checked"';
						}
						shtml +='id="worker'+data[i]["merchant_worker_id"]+'" value="'+data[i]["merchant_store_id"]+','+data[i]["merchant_worker_id"]+'" name="worker_memus[]" class="paycheck ace"><span class="lbl"><label for="worker'+data[i]["merchant_worker_id"]+'">&nbsp;&nbsp;'+data[i]["name"]+'</label></span></label>';
					}
				}
				$(this).html('').append(shtml);
			});
		},'json')
}

<if condition="C('butt_open')">
	$('.col-sm-1').width('15%');
</if>

$('select[name="is_appoint_price"]').change(function(){
	if($(this).val()=='1'){
		$('#appoint_price').show();
		$('#appoint_price_pay_type').show();
	}else{
		$('#appoint_price').hide();
        $('#appoint_price_pay_type').hide();
	}
});

var _selected_goods = '{pigcms{$appoint_list["product_recommendation"]}'; //已选择的商品
var _selected_coupon = '{pigcms{$appoint_list["buy_give_coupon_id"]}'; //已选择的优惠券
var offset = parseInt(getScrollTop()) - 150;

//选择商品
function select_goods() {
    var now_id = {pigcms{$appoint_list['appoint_id']};
    layer.open({
        title: '{pigcms{:L_("选择商品")}',
        type: 2,
        area: ['700px', '730px'],
        fixed: false,
        offset: offset + 'px',
        maxmin: true,
        content: '{pigcms{:U("select_goods")}' + '&selected=' + _selected_goods + '&id=' + now_id, 
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            var frameId = $(layero).find("iframe").attr('id');
            var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
            _selected_goods = selected;
            $("#goods_spec tbody").empty();
            $.post("{pigcms{:U('ajaxGetGoods')}", {selected: selected}, function (data) {
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var paymentStatus = data[i].payment_status == 0 ? '--' : '{pigcms{$config["Currency_symbol"]}' + data[i].payment_money;
                        var appointPrice = data[i].is_appoint_price ? '面议' : '{pigcms{$config["Currency_symbol"]}' + data[i].appoint_price;
                        var appointStatus = data[i].appoint_status == 0 ? '<span style="color:green">{pigcms{:L_("开启")}</span>' : '<span style="color:red">{pigcms{:L_("关闭")}</span>';

                        $("#goods_spec tbody").append('<tr>' +
                            '<input type="hidden" name="relation_goods_id[]" value="' + data[i].appoint_id + '" />' +
                            '<td>' + data[i].appoint_id + '</td>' +
                            '<td>' + data[i].appoint_name + '</td>' +
                            '<td>{pigcms{:L_("定金")}：' + paymentStatus + '<br/>{pigcms{:L_("全价")}：' + appointPrice + '</td>' +
                            '<td>' + appointStatus + '</td>' +
                            '<td>' +
                            '<a type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</a>' +
                            '</td>' +
                            '</tr>');
                    }
                }

            }, 'json');
            layer.close(index);
        },
        btn2: function (index) {
            layer.close(index);
        }
    });
}

function delThis(t) {
    layer.open({
        fixed: false,
        offset: offset + 'px',
        title: '{pigcms{:L_("提示")}',
        content: '{pigcms{:L_("你确定要移除此商品吗？")}',
        icon: 3,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            layer.close(index);
            var goods_id = $(t).parents("tr").find('[name="relation_goods_id[]"]').val();
            var _selected_goods_arr = _selected_goods.split(',');
            var _selected_goods_arr_new = [];
            for (var i = 0; i < _selected_goods_arr.length; i++) {
                if (_selected_goods_arr[i] != goods_id) {
                    _selected_goods_arr_new.push(_selected_goods_arr[i]);
                }
            }
            _selected_goods = _selected_goods_arr_new.join(",");
            $(t).parents("tr").remove();
        }
    });
}

//选择优惠券
function select_mer_coupon() {
    var offset = parseInt(getScrollTop()) - 150;
    layer.open({
        title: '{pigcms{:L_("选择优惠券")}',
        type: 2,
        area: ['700px', '730px'],
        fixed: false,
        offset: offset + 'px',
        maxmin: true,
        content: '{pigcms{:U("select_mer_coupon")}' + '&selected=' + _selected_coupon,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            var frameId = $(layero).find("iframe").attr('id');
            var selected = $.trim($(window.frames[frameId].document).find('[name="coupon_id"]').val());
            _selected_coupon = selected;
            $("#buy_give_coupon_id").val(_selected_coupon);
            $("#coupon_spec tbody").empty();
            $.post("{pigcms{:U('ajaxGetCoupon')}", {selected: selected}, function (data) {
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var appointStatus = data[i].status == 1 ? '<span style="color:green">{pigcms{:L_("开启")}</span>' : '<span style="color:red">{pigcms{:L_("不可使用")}</span>';

                        $("#coupon_spec tbody").append('<tr>' +
                            '<td>' + data[i].coupon_id + '</td>' +
                            '<td>' + data[i].name + '</td>' +
                            '<td>' + appointStatus + '</td>' +
                            '<td>' +
                            '<a type="button" onClick="delCouponThis(this)">{pigcms{:L_("移除")}</a>' +
                            '</td>' +
                            '</tr>');
                    }
                }

            }, 'json');
            layer.close(index);
        },
        btn2: function (index) {
            layer.close(index);
        }
    });
}

//移除优惠券
function delCouponThis(t) {
    layer.open({
        fixed: false,
        offset: offset + 'px',
        title: '{pigcms{:L_("提示")}',
        content: '{pigcms{:L_("你确定要移除此优惠券吗？")}',
        icon: 3,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        offset: offset + 'px',
        yes: function (index, layero) {
            layer.close(index);
            $('#buy_give_coupon_id').val('');
            _selected_coupon = '';
            $(t).parents("tr").remove();
        }
    });
}
</script>
<include file="Public:footer"/>