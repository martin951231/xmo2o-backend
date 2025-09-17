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
		<title>{pigcms{:L_("平台余额支付")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<script type="text/javascript" src=".{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
		<style>
			.btn{
				margin-left: 15px;
				padding: 6px 8px;
				cursor: pointer;
				display: inline-block;
				text-align: center;
				line-height: 1;
				letter-spacing: 2px;
				font-family: Tahoma, Arial/9!important;
				width: auto;
				overflow: visible;
				color: #333;
				border: solid 1px #999;
				-moz-border-radius: 5px;
				-webkit-border-radius: 5px;
				border-radius: 5px;
				background: #DDD;
				filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#DDDDDD');
				background: linear-gradient(top, #FFF, #DDD);
				background: -moz-linear-gradient(top, #FFF, #DDD);
				background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#FFF), to(#DDD));
				text-shadow: 0px 1px 1px rgba(255, 255, 255, 1);
				box-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0 -1px 0 rgba(0, 0, 0, .09);
				-moz-transition: -moz-box-shadow linear .2s;
				-webkit-transition: -webkit-box-shadow linear .2s;
				transition: box-shadow linear .2s;
				outline: 0;
			}
		</style>
	</head>
	<body>
		<div class="mainBox">
			<div class="rightMain">
				<div class="grid-view">
					
						<div class="form-group">
							<label class="col-sm-2"></label>
							<p id="title">{pigcms{:L_("只有在平台账户绑定手机号的用户才能使用平台余额支付")}</p>
						</div>
						
						
							<div class="form-group check_phone">
								<label class="col-sm-2"><label for="user_phone"></label></label>
								<input class="col-sm-4" size="10" id="balance_phone" type="tel" placeholder="{pigcms{:L_("请输入手机号")}" />
								<button class="btn" id="verify_phone" >{pigcms{:L_("下一步")}</button>
							</div>
							
							<div class="form-group confirm_pay" style="display:none;">
								<label class="col-sm-2"><label for="user_phone"></label></label>
								<input class="col-sm-4" size="10" placeholder="{pigcms{:L_("请输入验证码")}" id="smscode" type="tel"/>
								<button class="btn sms"  >{pigcms{:L_("发送验证码")}</button>
							</div>
					
						<div class="clearfix form-actions confirm_pay" style="display:none;">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info firm" type="submit">
									{pigcms{:L_("确认支付")}
								</button>
							</div>
						</div>
				
				</div>
			</div>
		</div>
	</body>
<script>
var smsTimer;
var verify_phone_url="{pigcms{:U('balance_verify_phone')}",sms_code_url="{pigcms{:U('sendCode')}";
var price=parseFloat("{pigcms{$now_order.price}");
$(function(){
	var user_now_money = 0;
	$("#verify_phone").click(function(){
		
		$.post(verify_phone_url, {phone:$('#balance_phone').val()}, function(result){
				if(result.status != 1){
					layer.msg(result.info);
				}else{
					$('.check_phone').hide();
					$('.confirm_pay').show();
					user_now_money=parseFloat(result.info.money);
					if(user_now_money<price){
						$('#title').html('{pigcms{:L_("当前平台余额：")}{pigcms{$config['Currency_symbol']}'+parseFloat(result.info.money)+',{pigcms{:L_("余额不足,待支付：")}<font color="red">{pigcms{$config['Currency_symbol']}'+price+'</font>')
					
					
						$('.confirm_pay .sms').css('background','#8b9092')
						$('.confirm_pay .firm').hide()
						$('.confirm_pay .sms,.confirm_pay .firm').css('color','#fff')
					}else{
						$('#title').html('{pigcms{:L_("当前平台余额：")}{pigcms{$config['Currency_symbol']}'+parseFloat(result.info.money)+',{pigcms{:L_("待支付：")}{pigcms{$config['Currency_symbol']}'+price)	
						$('.confirm_pay .firm').html('{pigcms{:L_("确认支付")} ({pigcms{$config['Currency_symbol']}'+price.toFixed(2)+')');
						$('.confirm_pay .firm').show()
						smscode();
					}
					$('.return').hide();
					$('.back_check').show();
				}
			});
	})


	$("#balance_phone").keyup(function(){
		$.post(verify_phone_url, {phone:$('#balance_phone').val()}, function(result){
			if(result.status != 1){
				$('.btn, .btn-default, .btn-default:focus, .btn:focus').attr('style','background-color:#999!important')
			}else{
				$('.btn, .btn-default, .btn-default:focus, .btn:focus').attr('style','background-color:#4f99c6!important')
			}
		});
	})
	
	$('.sms').click(function(){
		if(user_now_money>=price){
			smscode();
		}
	})
	
	$('.firm').click(function(){
		var tip_index = parent.layer.load(0, {shade: [0.5,'#fff']});
		
		if(price<user_now_money){
			$.post("{pigcms{:U('store_arrival_pay')}",{order_id:{pigcms{$now_order.order_id},'balance_pay':1,'phone':$('#balance_phone').val(),'smscode':$('#smscode').val()},function(result){
				parent.layer.close(tip_index);
				if(result.status == 1){
					// clearInterval(checkPayStatus);
					parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
						window.top.location.reload();
					});
				}else{
					parent.layer.alert(result.info);
				}
			});
		}
		
	})
	
	
	$('.back_check').click(function(){
		clearInterval(smsTimer);
		$('.sms').html('{pigcms{:L_("发送验证码")}')
		$('#title').html('{pigcms{:L_("只有在平台账户绑定手机号的用户才能使用平台余额支付")}')	
		$('.confirm_pay .money_lease').addClass('firm')
		$('.confirm_pay .firm').removeClass('money_lease')
		$('.confirm_pay .sms').css('background','#fff')
		$('.confirm_pay .sms').css('color','#000')
		$('.check_phone').show();
		$('.confirm_pay').hide();
		$('.back_check').hide();
		$('#balance_phone').val('');

		$('.return').show();
	})
});


function smscode(val){
	$.post(sms_code_url, {phone:$('#balance_phone').val()}, function(result){
		layer.msg('{pigcms{:L_("短信发送成功")}');
		$('.sms').data('second',59).html('59 秒').prop('disabled',true);
		smsTimer = setInterval(function(){
			var second = $('.sms').data('second');
			if(second == 1){
				$('.sms').html('{pigcms{:L_("发送验证码")}').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sms').data('second',second).html(second + ' 秒');
			}
		},1000);
	})
}

</script>
</html>