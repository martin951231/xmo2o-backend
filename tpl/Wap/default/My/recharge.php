<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>余额充值</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
	<style>
		.btn{background-color:#44cec1;}
		.recharge_card{    
			color: #06c1ae;
			border: #06c1ae 1px solid;    
			display: inline-block;
		    border-radius: 5px;
		    padding: 1px 3px;
		    font-size: 10px;   
		     margin-left: 25px;
		}
	</style>
</head>
<body id="index">
	<div id="widget_container">
		<if condition="$error">
			<div id="tips" class="tips tips-err" style="display:block;">{pigcms{$error}</div>
		<else/>
			<div id="tips" class="tips"></div>
		</if>
		<form id="form" method="post" action="{pigcms{:U('My/recharge')}">
			<input type="hidden" name="label" value="{pigcms{$_GET.label}"/>
			<input id="money" placeholder="请填写充值金额" class="input-weak" type="hidden" name="money" value="{pigcms{$_GET.money}"/>
			<dl class="list">
				<div style="float:left;line-height:50px;margin-left:10px;">{pigcms{$config['Currency_symbol']}</div>
				<dd class="dd-padding" style="height:40px;padding:4px 12px;padding-left:5px;font-size:16px;line-height:40px;" id="totalNumber">{pigcms{$_GET.money}</dd>
			</dl>
			<p class="btn-wrapper">
				金额最多仅支持两位小数
				<?php if ($config['open_recharge_card']): ?>
					
					<a class="recharge_card" href="{pigcms{:U('recharge_card')}">{pigcms{$config['recharge_card_alias_name']|default='充值卡'}充值</a>
				<?php endif ?>
			</p>
			<volist name="recharge_discount_list" id="vo" key="k">
			<p class="btn-wrapper" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>"><strong>{pigcms{$k}、满{pigcms{$vo.full_money}送{pigcms{$vo.money}</strong></p>
			</volist>
			<div class="btn-wrapper"><button type="submit" class="btn btn-block btn-larger submit" disabled="disabled">充值</button></div>
		</form>
	</div>
	<div class="actual_pay_box" style="position:fixed;left:0;width:100%;text-align:right;height:46px;line-height:46px;background:white;display:none;"><span style="margin-right:10px;">实付金额：{pigcms{$config['Currency_symbol']}<span class="actual_pay_span" style="font-weight:bold;margin-left:2px;font-size:18px;">0</span></span></div>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
	<script src="{pigcms{$static_path}js/common_wap.js"></script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
	<script type="text/javascript" src="{pigcms{$static_public}number/number.js?11" charset="utf-8"></script>
	<if condition="$is_alipayapp_browser">
		<script type="text/javascript" src="https://appx/web-view.min.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			var is_alipayapp_browser = true;
		</script>
	</if>
	<script>
		var default_money = {pigcms{$_GET['money']|default=0};
		function widgetNumberShow(){
			if($('.submit').prop('disabled') == false){
				$('.widget_number_event_btn').css('background','#44cec1');
			}
			$('.submit').hide();
			$('.actual_pay_box').css('bottom',$('.widget_number_input_box').height()).show();	
		}
		function totalNumberFocus(){
			if(default_money > 0){
				$('#totalNumber .widget_number').attr('style','').html(default_money);
				totalNumberClickObj(default_money);
			}else{
				$('#totalNumber').trigger('click');
			}
		}
		function totalNumberClickObj(number){
			$('#tips').empty().hide();
			$('#money').val(number);
			$('.actual_pay_span').html(number);
			if(number > 0){
				$('.submit').prop('disabled',false);
				$('.widget_number_event_btn').css('background','#44cec1');
			}else{
				$('.submit').prop('disabled',true);
				$('.widget_number_event_btn').css('background','#BCBCBC');
			}
			return true;
		}
		function widgetNumberHide(){
			$('.submit').show();
			$('.actual_pay_box').hide();
		}
		function totalNumberErrObj(tipText){
			$('#tips').html(tipText).show();	
			return true;
		}
		function totalNumberBtnObj(number){
			if($('.submit').prop('disabled') == false){
				$('.submit').trigger('click');
			}
		}
		$(function(){
			if(default_money == 0){
				var input1param = {};
				input1param.obj = $('#totalNumber');
				input1param.clickObj = 'totalNumberClickObj';
				input1param.btnObj = 'totalNumberBtnObj';
				input1param.errObj = 'totalNumberErrObj';
				input1param.loadOkFun = 'totalNumberFocus';
				input1param.showFun = 'widgetNumberShow';
				input1param.hideFun = 'widgetNumberHide';
				input1param.maxNum = 200000;
				input1param.decimalLength = 2;
				input1param.btnHtml = '<div style="line-height:26px;margin-top:30px;">确认<br/>支付</div>';
				input1param.btnStyle = 'background:#BCBCBC;color:white;text-align:center;';
				inputNumber(input1param);
			}else{
				$('#totalNumber').click(function(){
					$('#tips').html('订单充值时无法修改金额！').show();
				});
				$('.submit').prop('disabled',false);
			}
			
			$('#form').on('submit', function(e){
				$('#tips').removeClass('tips-err').hide();
				var money = parseFloat($('#money').val());
				if(isNaN(money)){
					$('#tips').html('请输入合法的金额！').addClass('tips-err').show();
					e.preventDefault();
					return false;
				}else if(money > 200000){
					$('#tips').html('单次充值金额最高不能超过20万{pigcms{$config['Currency_txt']}').addClass('tips-err').show();
					e.preventDefault();
					return false;
				}else if(money < 0.01){
					$('#tips').html('单次充值金额最低不能低于 0.01 {pigcms{$config['Currency_txt']}').addClass('tips-err').show();
					e.preventDefault();
					return false;
				}
				$('.submit').html('跳转中...').prop('disabled',true);
				$.post($('#form').attr('action'),$('#form').serialize(),function(result){
					if(result.status == 1){
						if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
							wx.miniProgram.navigateTo({
								url:'/pages/shop_new/confirmOrder/pay?order_id='+result.info.order_id+'&type='+result.info.type,
								success:function(){
//									location.reload();
									$(".submit").html("充值");
								}
							});
						}else{
							$('.submit').html('充值').prop('disabled',false);
							location.href = "{pigcms{:U('Pay/check')}&order_id="+result.info.order_id+'&type='+result.info.type;
						}
					}else if(typeof is_alipayapp_browser != 'undefined'){
						my.navigateTo({
							url:'/pages/shop_new/confirmOrder/pay?order_id='+result.info.order_id+'&type='+result.info.type,
							success:function(){
//								location.reload();
								$(".submit").html("充值");
							}
						});
					}else{
						layer.open({content:result.info,btn: ['确定']});
						$('.submit').html('充值').prop('disabled',false);
					}
				});
				return false;
			});		
			<if condition="$_GET['label'] && $_GET['money']">
				$('#form').trigger('submit');
			</if>
		});
	</script>
	<include file="Public:footer"/>
	{pigcms{$hideScript}
</body>
</html>