<!doctype html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
	<title>{pigcms{$now_merchant.name}的会员余额</title>
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/style_bai.css"/>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112222" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
</head>
<style>
	.myxq_title{
		display: flex;
		justify-content: space-between;
	}
	#exchangeCard{
		width: 70vw;
		border: 1px solid #d9d9d9;
		padding: 6px 2px;
	}
	.exchange{
		color: #fff;
		background-color: #00b2ff;
		border-radius: 4px;
		width: 40%;
		padding: 6px;
		margin: 0 auto;
		text-align: center;
		margin-top: 20px;
	}
	.layui-layer-btn{
		display: none
	}
</style>
<body>
	<div class="myde">
		<span class="left">我的余额</span>
		<span class="myshu" id="prepay">{pigcms{$card_info['card_money']+$card_info['card_money_give']}{pigcms{$config['Currency_txt']}</span>
	</div>
	<div class="myxq">
		<p class="myxq_title">在线充值 <span style="color:red" onclick="exchangeCard()">兑换卡密</span></p>
		<ul class="price-list">
			<volist name="card_info['recharge_suggest_array']" id="vo">
				<li data-price="{pigcms{$vo}"><a class="price">{pigcms{$vo}{pigcms{$config['Currency_txt']}</a></li>
			</volist>
		</ul>
        <if condition="$custom">
            <div class="jtxx" style="width:94%;padding:0px;border:1px solid #dddddd;height:36px;line-height:36px;">
                <input class="jtxx_r_c xb left" type="number" id="otherPrice" value="" placeholder="输入其他金额" style="    width: 100%;padding-left:8px;height:36px;"/>
            </div>
        </if>
		<div id="rechargeMoneyBox" class="jtxx" style="width:94%;padding:0px;border:none;height:36px;line-height:36px;margin-top:12px;display:none;">
			充值金额：<span id="rechargeMoney"></span> {pigcms{$config['Currency_txt']}
		</div>
		<div id="giveMoneyBox" class="jtxx" style="width:94%;padding:0px;border:none;height:36px;line-height:36px;margin-top:12px;display:none;">
			赠送金额：<span id="giveMoney"></span> {pigcms{$config['Currency_txt']}
		</div>
		<div id="givePointBox" class="jtxx" style="width:94%;padding:0px;border:none;height:36px;line-height:36px;margin-top:12px;display:none;">
			赠送{pigcms{$config['merchant_score_name']|default='积分'}：<span id="givePoint"></span> 分
		</div>
		<button class="btn" id="commitBtn">确定</button>
	</div>
	<div class="myxq" style="margin-top:30px;">
		<p class="myxq_title">预存说明</p>
		<div class="myxq_mx">
		{pigcms{$card_info.recharge_des|nl2br=###}
		</div>
	</div>
	{pigcms{$hideScript}
	<script>
		var nowMoney = 0;
		$(function(){
			$('.price-list li').click(function(){
				$(this).addClass('active').siblings('li').removeClass('active');
				nowMoney = $(this).data('price');
				$('#otherPrice').val('');
				giveCount();
			});
			$('#otherPrice').focus(function(){
				$('.price-list li').removeClass('active');
			}).keyup(function(){
				nowMoney = parseFloat($('#otherPrice').val());
				if(nowMoney > 200000){
					nowMoney = 200000;
					$('#otherPrice').val(nowMoney);
					layer.open({
						title: ['提示'],
						content: '单次充值，最高支持20万{pigcms{$config['Currency_txt']}！请分多次充值',
						time: 2
					});
				}else if(nowMoney.toString().split(".").length > 1 && nowMoney.toString().split(".")[1].length >= 3){
					nowMoney = nowMoney.toFixed(2);
					$('#otherPrice').val(nowMoney);
					layer.open({
						title: ['提示'],
						content: '小数最多精确到分'
					});
					
					return false;
				}
				giveCount();
			});
			$('#commitBtn').click(function(){
				if(nowMoney <= 0){
					layer.open({
						title: [
							'提示',
						],
						content: '充值金额必须大于0',
						time: 2
					});
					return false;
				}
				layer.open({type: 2});
				$.getJSON("{pigcms{:U('merchant_recharge',array('mer_id'=>$now_merchant['mer_id']))}&money="+nowMoney,function(result){
					layer.closeAll();
					if(motify.checkWeixinApp()){
						wx.miniProgram.navigateTo({url:'/pages/shop_new/confirmOrder/pay?order_id='+ result.info.order_id + '&type=' + result.info.type});
					}else if(motify.checkLifeApp() && motify.checkIos() && motify.getLifeAppVersion() >= 1420){
						window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name: 'gopay', orderID: result.info.order_id, type: result.info.type, backNoFresh:'1'});
						$("#submit_order").removeClass('disabled');
					}else if(motify.checkLifeApp() && motify.checkAndroid() && motify.getLifeAppVersion() >= 1000){
						window.lifepasslogin.payCheck(result.info.type, result.info.order_id);
						$("#submit_order").removeClass('disabled');
					}else{
						location.href = "{pigcms{:U('Pay/check')}&order_id=" + result.info.order_id + '&type=' + result.info.type;
					}
				});
				return false;
			});
		});
		// var conditionMoney = {pigcms{$now_merchant_card.recharge_count},giveMoney = {pigcms{$now_merchant_card.recharge_back_money},giveScore = {pigcms{$now_merchant_card.recharge_back_score};
		var recharge_rule = $.parseJSON('{pigcms{$now_merchant_card.recharge_rule}')
		function giveCount(){
			$('#rechargeMoney').html(nowMoney);
			if(isNaN(nowMoney)){
				$('#rechargeMoneyBox').hide();
				$('#giveMoneyBox').hide();
				$('#givePointBox').hide();
				return false;
			}
			$('#rechargeMoneyBox').show();
			var back_money=false;
			
			$.each(recharge_rule,function(index,val){
				if(val.count==nowMoney){
					back_money = true;
					giveUserMoney  = val.back_money;
					giveUserScore  = val.back_score;
					return false
				}
			})
			if(!back_money){
				$('#giveMoneyBox').hide();
				$('#givePointBox').hide();
				return false;
			}
			
			if(giveUserMoney > 0){
				$('#giveMoney').html(giveUserMoney);
				$('#giveMoneyBox').show();
			}else{
				$('#giveMoneyBox').hide();
			}
		
			if(giveUserScore > 0){
				$('#givePoint').html(giveUserScore);
				$('#givePointBox').show();
			}else{
				$('#givePointBox').hide();
				
			}
		}

	let flag = false;
	//兑换卡密
	function exchangeCard(){
		layer.open({
			title:['兑换卡密','background-color:#fff;color:#000;'],
			content: `<input type="text" style="width:70vw" id="exchangeCard" placeholder="请输入卡密"><div class="exchange">兑换</div>`,
		});
		$(".exchange").click(function(){
			if(flag){
				return
			}
			let value = $('#exchangeCard').val();
			if(value){
				flag = true;
				$.post('{pigcms{:U("ajax_exchangeCard")}', {mer_id:'{pigcms{$now_merchant_card.mer_id}',cardmi: value},
					function(data, textStatus, xhr) {
						flag = false;
						let res= JSON.parse(data);
						if(res.error_code == 0){
							layer.open({
									title: [
										'提示',
									],
									content: res.msg,
									time: 2
								});
								setTimeout(() => {
									location.reload(true)
								}, 1000);
						}else{
							layer.open({
								title:['提示'],
								btn: ['确定'],
								content: res.msg,
								yes:()=>{
									exchangeCard();
								}
							});
						}
				});
			}
		})
	}
	</script>
</body>
</html>