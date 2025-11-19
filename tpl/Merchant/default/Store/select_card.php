<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta name="format-detection" content="telephone=no"/>
	<meta charset="utf-8">
	<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
	<script src="{pigcms{$static_path}js/iscroll.js"></script>
	<script src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js"></script>
	<script src="{pigcms{$static_public}js/layer/layer.js"></script>
	<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	<script>var ajax_goods_list = "{pigcms{:U('Store/ajax_shop_goods')}", store_id = "{pigcms{:$store_id}", arrival_pay = "{pigcms{:U('arrival_pay')}", changePriceUrl = "{pigcms{:U('shop_change_price')}", ajax_card_url = "{pigcms{:U('Store/ajax_card')}", shop_order_save = "{pigcms{:U('Store/shop_order_save')}", shop_arrival_check = "{pigcms{:U('shop_arrival_check')}",verify_phone_url="{pigcms{:U('balance_verify_phone')}",sms_code_url="{pigcms{:U('sendCode')}";
	
	</script>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]-->
</head>

<body>
	<div class="fix card" style="display:block;">
		<div class="card_top">{pigcms{:L_("会员卡")}</div>
		<div class="card_end clr">
			<div class="inbox_top clr">
				<div class="inbox fl">
					<input type="text" id="card_value" placeholder="{pigcms{:L_("会员卡")}/{pigcms{:L_("手机号")}" />
					<em></em>
				</div>
				<div class="query fr clr">
					<div class="fr query_qr">{pigcms{:L_("确认使用")}</div>
					<div class="fr query_cx">{pigcms{:L_("查询")}</div>
				</div>
			</div>
			<div class="information">
				<h2 class="h2_top"><span>{pigcms{:L_("会员信息")}</span></h2>
				<ul class="clr">
					<li class="clr">
						<span>{pigcms{:L_("会员卡号：")}</span>
						<div class="p95" id="card_number"></div>
					</li>
					<li class="clr">
						<span>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("名：")}</span>
						<div class="p95" id="card_name"></div>
					</li>
					<li class="clr">
						<span>{pigcms{:L_("实体卡号：")}</span>
						<div class="p95" id="card_number2"></div>
					</li>
					<li class="clr">
						<span>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("别：")}</span>
						<div class="p95" id="card_sex"></div>
					</li>
					<li class="clr">
						<span>{pigcms{:L_("手机号码：")}</span>
						<div class="p95" id="card_phone"></div>
					</li>
					<li class="clr">
						<span>折&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("扣：")}</span>
						<div class="p95" id="card_discount_p"></div>
					</li>
					<li class="bot">
						<div class="p15" id="card_score">{pigcms{:L_("可用")}{pigcms{$config['score_name']}：0</div>
					</li>
					<li class="bot">
						<div class="p15">{pigcms{:L_("储值余额：")}<em id="card_money">{pigcms{$config['Currency_symbol']}0</em></div>
					</li>
				</ul>
				<!-- {pigcms{:L_("没有数据时")} -->
				<div class="img" style="display: none;"><img src="{pigcms{$static_path}images/myxg_03.jpg"></div>
				<!-- {pigcms{:L_("没有数据时")} -->
			</div>
		</div>
	</div>
</body>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">

	var domid=art.dialog.data('domid');
	var origin = artDialog.open.origin;
	var name_add = origin.document.getElementById('form-field-tags')
	var card_no = origin.document.getElementById('card_no')
	var card_money = origin.document.getElementById('card_money')
	var user_card = origin.document.getElementById('user_card')
	var card_discount = origin.document.getElementById('card_discount')
	var card_discount_txt = origin.document.getElementById('card_discount_txt')
	var card_id = origin.document.getElementById('card_id')
	var card_phone = origin.document.getElementById('card_phone')
	var vip_discount_div = origin.document.getElementById('vip_discount_div')
	var vip_discount = origin.document.getElementById('vip_discount')
	var type = origin.document.getElementById('type')
	var vip_discount_offstr = origin.document.getElementById('vip_discount_offstr')
	var ajax_card_url = "{pigcms{:U('Store/ajax_card')}";
	//"查询会员卡")}
	var temp_data = null;
	$('.query_cx').click(function(){
		var key = $('#card_value').val();
		if (key.length < 1) return false;
		var index_loading = layer.load(0, {shade: [0.1,'#000000']});
		$.post(ajax_card_url, {'key':key}, function(response){
			console.log(response)
			if (response.err_code) {
				$('.card .information').find('.clr').hide();
				$('.card .information').find('.img').show();
				$(".query_qr").css("background","#ccc");
			} else {
				$('.card .information').find('.clr').show();
				$('.card .information').find('.img').hide();
				$(".query_qr").css("background","#0099dc");
				temp_data = response.data;
				
				$('#card_name').html(temp_data.name);
				$('#card_number').html(temp_data.card_id);
				$('#card_number2').html(temp_data.physical_id);
				$('#card_sex').html(temp_data.sex);
				$('#card_discount_p').html(parseFloat(temp_data.discount) + '折');
				$('#card_phone').html(temp_data.phone);
				$('#card_money').html(temp_data.card_money);
				$('#card_score').html('{pigcms{:L_("可用积分：")}' + temp_data.card_score);
			}
			layer.close(index_loading);
		}, 'json');
	});

	//"使用会员卡")}
	$('.query_qr').click(function(){
		if (temp_data == null) return false;
		$(card_no).html('{pigcms{:L_("会员卡号：")}'+temp_data.card_id);
		$(card_money).html('{pigcms{:L_("当前余额：")}{pigcms{$config['Currency_symbol']}' + temp_data.card_money);
		$('.card_money').html('{pigcms{:L_("当前余额：")}{pigcms{$config['Currency_symbol']}' + temp_data.card_money);
		$(card_discount_txt).html(parseFloat(temp_data.discount));
		$(card_discount).val(parseFloat(temp_data.discount));
		$(card_id).val(temp_data.card_id);
		$(card_phone).val(temp_data.phone);
		$(user_card).show();
		if (temp_data.leveloff) {
			console.log(temp_data.leveloff)
			$(type).val(temp_data.leveloff.type);
			$(vip_discount_offstr).html(temp_data.leveloff.offstr);
			$(vip_discount).val(temp_data.leveloff.vv);
			$(vip_discount_div).show();
		}
		temp_data = null;
		setTimeout("art.dialog.close()", 100 )
	});
	$('.fl em').click(function(){
		$('#card_value').val('');
	})

	
</script>
</html>
