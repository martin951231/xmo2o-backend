<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
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
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
	</head>
	<body style="overflow-y: auto;">
		<div class="mainBox">
			<div class="rightMain">
				<div class="grid-view">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="form_add" autocomplete="off">
						<input type="hidden" name="business_type" value="{pigcms{$_GET.business_type}"/>
						<input type="hidden" name="business_id" value="{pigcms{$_GET.business_id}"/>
						<input type="hidden" name="pay_title" value="{pigcms{$pay_title}"/>
                        <input type="hidden" id="is_use_discount" value="1">
						<div class="form-group">
							<label class="col-sm-2"><label for="total_price">{pigcms{:L_("订单金额")}</label></label>
							<input class="col-sm-4" size="10" name="total_price" id="total_price" type="<if condition="$pigcms_pack_app">number<else/>text</if>" value="{pigcms{$pay_money}"/>
							<input class="col-sm-4" size="10" name="old_price" id="old_price" type="hidden" value="{pigcms{$pay_money}"/>
							<span class="form_tips">{pigcms{$config['Currency_txt']}</span>
							<if condition="$staff_session['is_change'] eq 0 && $_GET['business_id'] && $config['open_staff_power']"><span class="form_tips" style="color: red;">{pigcms{:L_("没有修改价格权限")}</span> </if>
						</div>
						<if condition="$config.open_extra_price eq 1">
							<input type="hidden" name="uid" value="{pigcms{$_GET['uid']}">
							<input type="hidden" name="from_scan" value="{pigcms{$_GET['from_scan']}">
							<input type="hidden" name="payid" value="{pigcms{$_GET['payid']}">				
							<div class="form-group">
								<label class="col-sm-2"><label for="user_phone">{pigcms{:L_("用户手机号")}</label></label>
								<input class="col-sm-4" size="10" name="user_phone" id="user_phone" type="<if condition="$pigcms_pack_app">tel<else/>text</if>" value="{pigcms{$user_phone}"/>
								<span class="form_tips">{pigcms{:L_("该手机号码需要在平台注册,没有则自动注册")}</span>
							</div>
						</if>
						<div class="form-group">
							<label class="col-sm-2">{pigcms{:L_("订单描述")}</label>
							<textarea class="col-sm-4" rows="3" name="txt_info"></textarea>
							<span class="form_tips">{pigcms{:L_("可不填写，仅作用记录值")}</span>
						</div>
						<!-- <div class="form-group" id="form-field-tags">
							<label class="col-sm-2">{pigcms{:L_("会员卡")}</label>
							<button type="button" class="btn btn-info" style="height: 30px;line-height: 6px; "rows="3" id="select_card">{pigcms{:L_("使用会员卡")}</button>
						</div> -->

						<div class="form-group" id="form-field-tags">
							<label class="col-sm-2">{pigcms{:L_("用户")}</label>
                            <if condition="$config['open_user_import_create_user'] eq 1">
								<input type="text" id="phone" name="phone" placeholder="{pigcms{:L_("请输入手机号")}/{pigcms{:L_("实体卡号")}" />
							<else/>
								<input type="text"  id="phone" name="phone" placeholder="{pigcms{:L_("请输入手机号")}" />
							</if>
							<button class="btn btn-info" type="button" id="phone_btn" style=" height: 32px; line-height: 6px; margin-left: 5px;">{pigcms{:L_("扫码添加")}</button>
							<br/>
							<label class="form_tips" id="tags" style=" margin-left: 155px;">
								<span id="nickname"></span>&nbsp;
								<span id="userphone"></span><br/>
								<span id="card_no" style=" margin-left: 155px;"></span>&nbsp;
								<span id="card_money"></span>
							</label>
						</div>




                		<if condition="$tablesDiscount">
                        <div class="form-group">
                            <label class="col-sm-2">{pigcms{:L_("桌台折扣")}</label>
                            <div class="col-sm-4" style="margin-top:5px;padding-left:0px;">
                                    <input type="hidden" value="{pigcms{$tablesDiscount['mer_discount']}" name="mer_table_discount" id="mer_table_discount" disabled="disabled" />
                                    <input type="hidden" value="{pigcms{$tablesDiscount['can_discount_table_money']}" name="can_discount_table_money" id="can_discount_table_money" disabled="disabled" />
                                    <input type="hidden" value="{pigcms{$tablesDiscount['mer_scale']}" name="mer_table_scale" id="mer_table_scale" disabled="disabled" />
                                    <label><input type="checkbox" value="1" name="mer_discountc" id="mer_discountc" disabled="disabled"/>&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
                                    <p><label id="mer_dis_info" style="display: none">{pigcms{:L_("此订单为当日第")}<span style="color:red;">{pigcms{$tablesDiscount['tables']}</span>{pigcms{:L_("桌客人，部分菜品可享受")}<span style="color:red;">{pigcms{$tablesDiscount['mer_discount']}</span>{pigcms{:L_("折优惠")}</label></p>&nbsp;
                                    <span class="form_tips" id="mer_discount_money_tip" style="font-size:20px;color:red;margin-left:0px;display: none">---</span>
                            </div>
                            <span class="form_tips">{pigcms{:L_("“桌位折扣”优惠不可关闭")}</span>
                        </div>
                		</if>
						<if condition="$coupon_discount">
							<div class="form-group discount">
								<label class="col-sm-2"><label for="sort">{pigcms{:L_("优惠信息")}</label></label>
                                <input type="hidden" name="coupon_discount_id" value="{pigcms{$coupon_discount['id']}">
								<span class="form_tips" style="margin-left:0px;font-size:20px;color:red" ><if condition="$coupon_discount.is_discount eq 1">{pigcms{$coupon_discount.discount_value|floatval}折</if></span>{pigcms{:L_("平台折扣券")}/{pigcms{:L_("部分商品不参加折扣")}
							</div>
								<div class="form-group discount">
								<label class="col-sm-2"><label for="sort">{pigcms{:L_("优惠金额")}</label></label>
                                <input type="hidden" id="coupon_discount_money" value="{pigcms{$coupon_discount.discount_money|floatval}">
								<span class="form_tips" style="margin-left:0px;font-size:20px;color:red" ><if condition="$coupon_discount.is_discount eq 1">{pigcms{$config['Currency_symbol']}{pigcms{$coupon_discount.discount_money|floatval}</if></span>
							</div>
						</if>

						<div class="form-group" id="no_discount_div"  style="display:none;">
							<label class="col-sm-2"><label for="sort">{pigcms{:L_("不可优惠金额")}</label></label>
							<input class="col-sm-4" size="20" name="no_discount" id="no_discount" type="<if condition="$pigcms_pack_app">number<else/>text</if>" value=""/>
							<span class="form_tips">{pigcms{:L_("注意：是不给予优惠的部分")}</span>
						</div>
                        <input type="hidden" name="vip_discount_type" id="vip_discount_type" value="{pigcms{$vip_discount_type}" />

							
						<div class="form-group">
							<label class="col-sm-2" for="FoodType_week">{pigcms{:L_("商户优惠")}</label>
							<div class="col-sm-4" style="margin-top:5px;padding-left:0px;">
								<if condition="$has_discount">
									<label><input type="checkbox" value="1" name="buy_discount" id="buy_discount" />&nbsp;&nbsp;{pigcms{:L_("开启")}</label>&nbsp;&nbsp;
								<else/>
									{pigcms{:L_("店铺没有设置优惠")}
								</if>
							</div>
							<span class="form_tips">{pigcms{:L_("若开启，则会使用“优惠买单”的优惠")}</span>
						</div>
						<div id="discount_set" style="display:none;">
							<div class="form-group">
								<label class="col-sm-2"><label for="sort">{pigcms{:L_("优惠信息")}</label></label>
								<span class="form_tips" style="margin-left:0px;"><if condition="$discount_type eq 1">{pigcms{$discount_percent}折<elseif condition="$discount_type eq 2" />{pigcms{:L_("每满")}{pigcms{$condition_price}减{pigcms{$minus_price}{pigcms{$config['Currency_txt']}</if></span>
								<label>{pigcms{:L_("，优惠金额")}</label>
								<span class="form_tips" id="discount_money_tip" style="font-size:20px;color:red;margin-left:0px;">---</span>
								<input class="col-sm-4" size="20" name="discount_money" id="discount_money" type="text" value="" style="display:none;"/>
							</div>
							<div class="form-group">
							</div>
						</div>

						<div class="form-group" id="vip_discount_div" style="display: none;">
							<input class="col-sm-4" size="20" name="vip_discount_money" id="vip_discount_money" type="hidden" value=""/>
                            <label class="col-sm-2">{pigcms{:L_("会员等级优惠")}</label>
                            <div class="col-sm-4" style="margin-top:5px;padding-left:0px;">
                                    <input type="hidden" value="" name="vip_discount" id="vip_discount" />
                                    <input type="hidden" value="" name="type" id="type" />
                                    <label><input type="checkbox" value="1" name="vip_discount_open" id="vip_discount_open" />&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
                                    <!-- <p><label id="vip_discount_offstr" style="display: none"></label></p>&nbsp; -->
                            </div>
                            <span class="form_tips">{pigcms{:L_("若开启，则会使用会员等级优惠设置过的折扣")}</span>
							<br/>
							<label class="form_tips" id="vip_discount_set" style=" margin-left: 155px;display:none;">{pigcms{:L_("优惠金额")}<span id="vip_discount_money_tip" style="font-size:20px;color:red;margin-left:0px;"></span></label>
                        </div>
						<!-- <div id="vip_discount_set" style="display:none;">
							<div class="form-group">
								<label class="col-sm-2">{pigcms{:L_("优惠信息")}</label>
								<span class="form_tips" style="margin-left:0px;" id="vip_discount_offstr"></span>
								<label >{pigcms{:L_("优惠金额")}</label>
								<span class="form_tips" id="vip_discount_money_tip" style="font-size:20px;color:red;margin-left:0px;">---</span>
							</div>
							<div class="form-group">
							</div>
						</div> -->


						<div class="form-group" id="user_card" style="display: none;">
                            <label class="col-sm-2">{pigcms{:L_("使用会员卡折扣")}</label>
                            <div class="col-sm-4" style="margin-top:5px;padding-left:0px;">
                                    <input type="hidden" value="" name="card_discount" id="card_discount" />
                                    <input type="hidden" value="" name="card_id" id="card_id" />
                                    <input type="hidden" value="" name="card_phone" id="card_phone" />
                                    <input type="hidden" value="" name="card_discount_money" id="card_discount_money"/>
                                    <label><input type="checkbox" value="1" name="card_discount_open" id="card_discount_open" />&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
                                    <p><label id="card_discount_set" style="display: none"><span style="color:red;" id="card_discount_txt"></span>折</label></p>&nbsp;
                                    <span class="form_tips" id="card_discount_money_tip" style="font-size:20px;color:red;margin-left:0px;display: none">---</span>
                            </div>
                            <span class="form_tips">{pigcms{:L_("若开启，则会使用会员卡设置过的折扣")}</span>
                        </div>

						<div class="form-group">
							<label class="col-sm-2"><label for="sort">{pigcms{:L_("需付金额")}</label></label>
							<span class="form_tips" id="pay_money_tip" style="font-size:20px;color:red;margin-left:0px;">---</span>
							<input class="col-sm-4" size="20" name="pay_money" id="pay_money" type="text" value="" style="display:none;"/>
							<input class="col-sm-4" size="20" name="extra_price" id="extra_price" type="text" value="" style="display:none;"/>
						</div>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit" id="submit_btn">
									{pigcms{:L_("生成订单")}
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</body>
	<div class="fix chat" style="    margin-left: -349px;    width: 660px;">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("扫码识别用户")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("扫描用户微信码识别用户")}</h2>
				<div class="inputs clr input" style='clear: both;'>
					<input type="text" class="port fl" value="" id="userQrcode">
					<div class="firm fr" data-paymethod="weixin" id="userQrcodeBtn">{pigcms{:L_("确认使用")}</div>
				</div>
				<p style='clear: both;'>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户微信中的码，再写入。如果扫码提示微信错误，可以关闭本页面重新创建订单。")}</p>
			</div>
		</div>
	</div>
	<div class="shadow_two"></div>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
$(function(){
	// "查看商家积分")}
	var platform_score = {pigcms{$platform_score|intval=###};
	var merchant_buy_points_switch = {pigcms{$config['merchant_buy_points_switch']|intval=###};
	if (merchant_buy_points_switch==1) {
		if (platform_score<100&&platform_score>=50) {
			layer.alert("{pigcms{:L_('商家的平台X1已不足100，平台用户支付后可能无法正常获得相应X1，请及时充值！',$config['score_name'])}");
		}else if(platform_score<50&&platform_score>0){

			layer.alert("{pigcms{:L_('商家的平台X1已不足50，平台用户支付后可能无法正常获得相应X1，请及时充值！',$config['score_name'])}");
		}else if(platform_score<=0){
			layer.alert("{pigcms{:L_('商家的平台X1已不足0，平台用户支付后可能无法正常获得相应X1，请及时充值！',$config['score_name'])}");
		}
	}

	$('#total_price').focus();

	$("#buy_discount").change(function(){
		buy_discount_change();
		showPayMoney();
	});

	if ($("#buy_discount") != 'undefined') {
		// "默认选中优惠")}
		$('#buy_discount').prop('checked','checked');
		buy_discount_change();
		showPayMoney();
	}

	$("#vip_discount_open").change(function(){
		vip_discount_open_change();
		showPayMoney();
	});

	$("#card_discount_open").change(function(){
		card_discount_open_change();
		showPayMoney();
	});

    $("#mer_discountc").attr("checked","true");
    $('#mer_dis_info').show();
    $('#mer_table_discount').removeAttr("disabled");
    $('#mer_table_scale').removeAttr("disabled");
    $('#mer_discount_money_tip').show();
    // $("#mer_discountc").change(function(){
    //     if($(this).prop('checked')){
    //         $('#mer_dis_info').show();
    //         $('#mer_table_discount').removeAttr("disabled");
    //         $('#mer_table_scale').removeAttr("disabled");
    //         $('#mer_discount_money_tip').show();
    //     }else{
    //         $('#mer_dis_info').hide();
    //         $('#mer_table_discount').attr("disabled","disabled");
    //         $('#mer_table_scale').attr("disabled","disabled");
    //         $('#mer_discount_money_tip').hide();
    //     }
    //     showPayMoney();
    // });
	if(checkApp()){
		$('#total_price,#no_discount').bind('input',function(e){
			showPayMoney();
		});
	}else{
		$('#total_price,#no_discount').keyup(function(e){
			showPayMoney();
		});
	}
	if($('#total_price').val() != ''){
		showPayMoney();
	}
	$('#form_add').submit(function(){
		var total_price = parseFloat($('#total_price').val());
		if(total_price < 0){
			alert('{pigcms{:L_("请输入正确的订单金额")}');
			$('#total_price').focus();
			return false;
		}
		$('#submit_btn').html('{pigcms{:L_("生成中")}...').prop('disabled',true);
		var card_id = $('#card_id').val()
		$.post("{pigcms{:U('store_arrival_add')}",$('#form_add').serialize(),function(result){
			$('#submit_btn').html('{pigcms{:L_("生成订单")}').prop('disabled',false);
			if(result.status == 1){
				if (result.info === 'SUCCESS') {
					window.top.location.reload();
				} else if(result.info === 'SCAN_PAY_SUCCESS'){
					layer.msg('{pigcms{:L_("订单创建成功，等待用户支付")}', {
					  time: 0 //"不自动关闭")}
					  ,btn: ['{pigcms{:L_("已支付")}']
					  ,yes: function(index){
						layer.close();
						window.top.location.reload();
					  }
					});
				}else {
    				parent.layer.open({
    				  type: 2,
    				  title: '{pigcms{:L_("支付订单")}',
    				  shadeClose: false,
    				  shade: 0.6,
    				  area: ['900px', '650px'],
    				  content: "{pigcms{:U('store_arrival_order')}&order_id="+result.info+'&card_id='+card_id
    				});
				}
			}else{
				alert(result.info);
			}
		});
		return false;
	});
});

function buy_discount_change(){
	if($("#buy_discount").prop('checked')){
		$('#discount_set').show();
		$('#no_discount_div').show();
	}else{
		$('#discount_set').hide();
		if(!$('#vip_discount_open').prop('checked')&&!$('#card_discount_open').prop('checked')){
			$('#no_discount_div').hide();
		}
	}
}
function vip_discount_open_change(){
	if($("#vip_discount_open").prop('checked')){
		$('#vip_discount_set').show();
		$('#no_discount_div').show();
	}else{
		$('#vip_discount_set').hide();
		if(!$('#buy_discount').prop('checked')&&!$('#card_discount_open').prop('checked')){
			$('#no_discount_div').hide();
		}
	}
}
function card_discount_open_change(){
	if($("#card_discount_open").prop('checked')){
		$('#card_discount_set').show();
		$('#card_discount_money_tip').show();
		$('#no_discount_div').show();
	}else{
		$('#card_discount_set').hide();
		$('#card_discount_money_tip').hide();
		if(!$('#buy_discount').prop('checked')&&!$('#vip_discount_open').prop('checked')){
			$('#no_discount_div').hide();
		}
	}
}
//"使用会员卡")}
$('#select_card').click(function(){

	art.dialog.data('titledom', 'titledom');
	art.dialog.data('imgids', 'imgids');
	art.dialog.data('multinews', 'multinews');
	art.dialog.data('singlenews', 'singlenews');
	art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
	art.dialog.data('multione', 'multione');
	art.dialog.open('store.php?g=Merchant&c=Store&a=select_card',{lock:true,title:'{pigcms{:L_("选择会员卡',width:600,height:400,yesText:'关闭")}',background: '#000',opacity: 0.45});

	// $('.card').show()
	// $('.shadow').show()
})

function showPayMoney(){
	var open_extra_price  = Number('{pigcms{$config.open_extra_price|intval=###}');
	var extra_price_name  = '{pigcms{$config.extra_price_alias_name}';
	var extra_percent  = Number('{pigcms{$config.user_score_use_percent}');
	var condition_price = '{pigcms{$condition_price}', minus_money = '{pigcms{$minus_price}', discount_percent = '{pigcms{$discount_percent}', discount_type = '{pigcms{$discount_type}';
	var mer_discout = $('#mer_table_discount').val();
	    mer_discout = mer_discout/10;
    var can_discount_table_money = parseFloat($('#can_discount_table_money').val());
	var old_money = parseFloat($('#old_price').val());
	var total_money = parseFloat($('#total_price').val());
    var coupon_discount_money = parseFloat($('#coupon_discount_money').val());
    var card_discount = parseFloat($('#card_discount').val());
    card_discount = card_discount/10;
    if(isNaN(old_money)){
        old_money = 0;
    }
    if(isNaN(total_money)){
        total_money = 0;
    }
    if(isNaN(coupon_discount_money)){
        coupon_discount_money = 0;
    }

    // "店员 餐饮修改价格权限")}
	var is_change = {pigcms{$staff_session['is_change']|intval};
	var open_staff_power = {pigcms{$config['open_staff_power']|intval};
	var business_id = {pigcms{$_GET['business_id']|intval};
    if (is_change==0&&open_staff_power==1&&business_id>0) {
		total_money = old_money;
		$('#total_price').val(old_money);
    }

    if (old_money != total_money) {
        coupon_discount_money = 0;
        $('#is_use_discount').val(0);
        $('.discount').hide();
    } else {
        $('.discount').show();
        $('#is_use_discount').val(1);
    }


	var no_discount = $('#no_discount').val() != '' ? parseFloat($('#no_discount').val()) : 0;
	var no_discount_money = $("#buy_discount").prop('checked') == true || $("#vip_discount_open").prop('checked') == true   || $("#card_discount_open").prop('checked') == true ? no_discount : total_money;
	// "桌台折扣")}
    if($('#mer_discountc').prop('checked') && mer_discout>0){
        var unmer_price  = parseFloat(total_money).toFixed(2);
        var mer_minus_price = (can_discount_table_money - can_discount_table_money * mer_discout).toFixed(2);
        $('#mer_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + mer_minus_price);
        $('#discount_money').val(parseFloat(mer_minus_price));
        $('#pay_money_tip').html('{pigcms{$config['Currency_symbol']}'+ parseFloat((parseFloat(unmer_price) - mer_minus_price).toFixed(2)));
        $('#pay_money').val((parseFloat(unmer_price) - mer_minus_price).toFixed(2));
        total_money = (parseFloat(unmer_price) - mer_minus_price).toFixed(2);
        if(no_discount_money == unmer_price){
            no_discount_money = total_money;
        }
    }

	var price = total_money;
	if (total_money >= no_discount_money ) {
		var discount_compare = false;
		var discount_total_money = 0;
		var vip_discount = $('#vip_discount').val(),level_discount_type = $('#type').val(),vip_discount_type = $('#vip_discount_type').val();
    	var vip_minus_price=0; // "会员等级优惠金额")}
		// "买单优惠")}
		if ($("#buy_discount").prop('checked') == true) {
			if (discount_type == 1) {
				price = parseFloat(total_money - no_discount_money) * parseFloat(discount_percent) / 10 + no_discount_money;// "买单优惠折扣后应付金额")}
				minus_price = parseFloat(total_money - no_discount_money) * (100 - discount_percent * 10) / 100; // "买单优惠折扣金额")}
			} else if (discount_type == 2) {
				minus_price = Math.floor(parseFloat(total_money - no_discount_money) / parseFloat(condition_price)) * minus_money;
				price = total_money - minus_price;

			} else {
				minus_price = 0;
				price = total_money;
			}
		}else{
			minus_price = 0;
			price = total_money;
		}
		discount_total_money = minus_price;

		// "会员等级优惠")}
	    if($('#vip_discount_open').prop('checked')){
			if(vip_discount_type==1){ // "折扣最优，优惠买和会员等级优惠不同享,选择最优")}
				if(level_discount_type==1){
					vip_minus_price = parseFloat((total_money-no_discount_money)*(1-(vip_discount/100)).toFixed(2)).toFixed(2)
				}else if(level_discount_type==2){
					vip_minus_price = vip_discount;
				}
				discount_compare = true;
				
			}else if(vip_discount_type==2 ){ // "折上折")}
				if(level_discount_type==1){
					vip_minus_price = parseFloat((price-no_discount_money)*(1-(vip_discount/100)).toFixed(2)).toFixed(2)
				}else if(level_discount_type==2){
					vip_minus_price = vip_discount>price ? price : vip_discount;
				}
				price = price-vip_minus_price<0?0:price-vip_minus_price;
				discount_total_money += vip_minus_price;
			}
	    }

		if(discount_compare && (Number(vip_minus_price)>Number(minus_price))){ // "折扣最优，会员等级优惠 较优")}
			discount_total_money = minus_price = vip_minus_price;
			if(total_money-minus_price<0){
				vip_minus_price = minus_price = total_money;
			}
			price = total_money - vip_minus_price;
			$('#vip_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + vip_minus_price);
	        $('#vip_discount_money').val(parseFloat(vip_minus_price));
	        // "使用 折扣最优，会员等级优惠")}
			$('#discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + 0);
			if(typeof mer_minus_price !='undefined' && mer_minus_price>0){minus_price = (parseFloat(minus_price) + parseFloat(mer_minus_price)).toFixed(2)}
			$('#discount_money').val(0);
	       
		}else{
			if(vip_discount_type==2 ){ // "折上折")}
				$('#vip_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + vip_minus_price);
		        $('#vip_discount_money').val(parseFloat(vip_minus_price));
				$('#discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + minus_price);
				if(typeof mer_minus_price !='undefined' && mer_minus_price>0){minus_price = (parseFloat(minus_price) + parseFloat(mer_minus_price)).toFixed(2)}
				$('#discount_money').val(minus_price);
			}else{
	        	// "使用 买单优惠")}
				$('#discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + minus_price);
				if(typeof mer_minus_price !='undefined' && mer_minus_price>0){minus_price = (parseFloat(minus_price) + parseFloat(mer_minus_price)).toFixed(2)}
				$('#discount_money').val(minus_price);
				$('#vip_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + 0);
		        $('#vip_discount_money').val(parseFloat(0));
			}
		}

		$('#pay_money_tip').html(parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2)));
        $('#pay_money').val(parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2)));


		// // "会员卡优惠")}
	    if($('#card_discount_open').prop('checked') && card_discount>0){
	        var unmer_price  = parseFloat(price).toFixed(2);
	        var card_minus_price = ((unmer_price-no_discount_money) - (unmer_price-no_discount_money) * card_discount).toFixed(2);
	        $('#card_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + card_minus_price);
	        $('#card_discount_money').val(parseFloat(card_minus_price));
	        $('#pay_money_tip').html('{pigcms{$config['Currency_symbol']}'+ parseFloat((parseFloat(unmer_price) - card_minus_price).toFixed(2)));
	        $('#pay_money').val((parseFloat(unmer_price) - card_minus_price).toFixed(2));
	        price = (parseFloat(unmer_price) - card_minus_price).toFixed(2);
	    }else{
	    	$('#card_discount_money').val(0);
	    }

		if(open_extra_price==1){							
			$('#extra_price').val((minus_price*extra_percent).toFixed(2)+extra_price_name);
			$('#pay_money_tip').html(parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2))+'+'+(minus_price*extra_percent).toFixed(2)+extra_price_name);
			// $('#discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + minus_price);
			if(typeof mer_minus_price !='undefined' && mer_minus_price>0){minus_price = (parseFloat(minus_price) + parseFloat(mer_minus_price)).toFixed(2)}
			// $('#discount_money').val(minus_price);
            $('#pay_money').val(parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2)));

		}else{
			// price = (parseFloat(price)).toFixed(2);
			// minus_price = minus_price.toFixed(2);
			// $('#discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + minus_price);
            if(typeof mer_minus_price !='undefined' && mer_minus_price>0){minus_price = (parseFloat(minus_price) + parseFloat(mer_minus_price)).toFixed(2)}
			// $('#discount_money').val(minus_price);
			$('#pay_money_tip').html('{pigcms{$config['Currency_symbol']}'+ parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2)));
            $('#pay_money').val(parseFloat((parseFloat(price) - parseFloat(coupon_discount_money)).toFixed(2)));

		}
		
		
		if(parent.customDisplayCan){
			if(price == 0.00 || price == 0){
				window.pigcmspackapp.custom_display_work('','');
			}else{
				var displayCon = ' {pigcms{:L_("订单")}:{pigcms{$config['Currency_symbol']}'+parseFloat(price);
				if(minus_price != 0.00 && minus_price != 0){
					displayCon+= '<br/> {pigcms{:L_("优惠")}:{pigcms{$config['Currency_symbol']}'+parseFloat(minus_price);
				}
                    displayCon+= '<br/> {pigcms{:L_("需付")}:{pigcms{$config['Currency_symbol']}'+parseFloat(price);

				window.pigcmspackapp.custom_display_work(displayCon,'');
			}
		}
	} else {
		$('#no_discount').val(total_money);
		// // "会员卡优惠")}
	    if($('#card_discount_open').prop('checked') && card_discount>0){
	        var unmer_price  = parseFloat(total_money).toFixed(2);
	        var card_minus_price = ((unmer_price-no_discount_money) - (unmer_price-no_discount_money) * card_discount).toFixed(2);
	        $('#card_discount_money_tip').html('-{pigcms{$config['Currency_symbol']}' + card_minus_price);
	        $('#card_discount_money').val(parseFloat(card_minus_price));
	        $('#pay_money_tip').html('{pigcms{$config['Currency_symbol']}'+ parseFloat((parseFloat(unmer_price) - card_minus_price- parseFloat(coupon_discount_money)).toFixed(2)));
	        $('#pay_money').val((parseFloat(unmer_price) - card_minus_price- parseFloat(coupon_discount_money)).toFixed(2));
	        total_money = (parseFloat(unmer_price) - card_minus_price).toFixed(2);
	    }else{
	    	$('#card_discount_money').val(0);
	    }
		// showPayMoney();
	}
}

var temp_data = null;
$('#phone').keyup(function(){
	var phone = $('#phone').val();
	$.post("{pigcms{:U('Store/ajax_user_by_phone')}", {'phone':phone}, function(response){
		user_card(response.data)
		showPayMoney();
	}, 'json');
});

function user_card(data){
	$("#nickname").html('');
	$("#userphone").html('');
	$("#card_no").html('');
	$("#card_money").html('');
	$("#card_discount_txt").html('');
	$("#card_discount").val(0);
	$("#card_id").val(0);
	$("#card_phone").val('');
	$("#user_card").hide();
	$("#type").val('');
	$("#vip_discount_offstr").html('');
	$("#vip_discount").val(0);
	$("#vip_discount_div").hide();
	$("#card_discount_open").prop('checked','');
	$("#vip_discount_open").prop('checked','');
	card_discount_open_change();
	vip_discount_open_change();
	if (data) {
		temp_data = data;
		if (temp_data.uid) {
			$("#card_phone").val(temp_data.phone);
			$("#nickname").html('{pigcms{:L_("会员名：")}'+temp_data.name);
			$("#userphone").html('{pigcms{:L_("手机号：")}'+temp_data.phone);
		
			if (temp_data.card_id) {
				$("#card_no").html('{pigcms{:L_("会员卡号：")}'+temp_data.card_id);
				$("#card_money").html('{pigcms{:L_("当前余额：")}{pigcms{$config['Currency_symbol']}' + temp_data.card_money);
				$("#userphone").html('');
				$("#card_discount_txt").html(parseFloat(temp_data.discount));
				$("#card_discount").val(parseFloat(temp_data.discount));
				$("#card_id").val(temp_data.card_id);
				$("#user_card").show();
				$("#card_discount_open").prop('checked','checked');
				card_discount_open_change();
			}else{
				$("#card_no").html('{pigcms{:L_("会员卡号：暂未领卡")}');
			}
		}else{
			$("#nickname").html('{pigcms{:L_("不是平台会员")}');
		}
		if (temp_data.leveloff) {
			$("#type").val(temp_data.leveloff.type);
			$("#vip_discount_offstr").html(temp_data.leveloff.offstr);
			$("#vip_discount").val(temp_data.leveloff.vv);
			$("#vip_discount_div").show();
			$("#vip_discount_open").prop('checked','checked');
			vip_discount_open_change();
		}
		
	}
}

//"微信支付")}
$("#phone_btn").click(function(){
	$(".chat, .shadow_two").show();
	$('#userQrcode').focus();
	return false;
});

$('#userQrcodeBtn').click(function(){
	postUserQrcode();
});

var postNow = false;
function postUserQrcode(){
$('#userQrcode').val($.trim($('#userQrcode').val()));
	if(postNow == true){
		layer.alert('{pigcms{:L_("正在请求中，请稍等")}');
		return false;
	}
	var auth_code = $('#userQrcode').val();
	var reg = new RegExp("^[0-9]*$");
	if(!reg.test(auth_code)){
		layer.alert('{pigcms{:L_("只允许输入数字")}');
		return false;
	}
	if(auth_code.length < 2){
		$('#userQrcode').focus();
		return false;
	}
	var nowPayMethod = '';			
	var codePrefix =  auth_code.substr(0,2);		

	$('#userQrcodeBtn').html('{pigcms{:L_("请求中")}...');
	$.post("{pigcms{:U('Store/ajax_user_by_phone')}", {'auth_code':auth_code}, function(response){
		$('#userQrcodeBtn').html('{pigcms{:L_("确认使用")}');
		if(response.err_code){
			layer.alert(response.data);
		}else{
			$('#phone').val(response.data.phone)
			user_card(response.data)
			$(".chat, .shadow_two").hide();
		}
		postNow = false;
	},'json');
}
</script>
</html>