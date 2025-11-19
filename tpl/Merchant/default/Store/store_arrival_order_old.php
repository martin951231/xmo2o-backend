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
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	</head>
	<body>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th>{pigcms{:L_("订单编号")}</th>
			<th colspan="2">{pigcms{$now_order['orderid']}</th>
		</tr>
		<tr>
			<th>{pigcms{:L_("订单总价")}</th>
			<th>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_price']|floatval}</th>	
			<th rowspan="4" width="50%">
				<div style="text-align:center;margin-top:30px;">
					<button style="background:#4f99c6!important;text-shadow:none;color:white;padding:10px 13px;" id="balance_pay">{pigcms{:L_("余额支付")}</button>
					<button style="background:#4f99c6!important;text-shadow:none;color:white;padding:10px 13px;" id="merchantEntityCard">{pigcms{:L_("商家实体卡支付")}</button>
					<if condition="C('customization.internal_membership_card') eq 1">
						<button style="background:#4f99c6!important;text-shadow:none;color:white;padding:10px 13px;" id="internalCard">{pigcms{:L_("一卡通支付")}</button>
					</if>
				</div>
				<if condition="$offline_pay_list">
					<div>
						<volist name="offline_pay_list" id="vo">
							<button class="orderofflinepay" data-id="{pigcms{$vo.id}" style="margin-top:20px;">{pigcms{$vo.name}</button>
						</volist>
						<br/>
					</div>
				</if>
				<if condition="$orderprinter">
					<div style="text-align:center;margin-top:30px;">
						<button style="background:#4f99c6!important;text-shadow:none;color:white;padding:10px 13px;" id="orderprinter">{pigcms{:L_("打印订单")}</button>
					</div>
				</if>
				
			</th>
		</tr>
		<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">
		<tr>
			<th>{pigcms{$config['Currency_txt']}{pigcms{:L_("宝价格（只支持线上支付）")}</th>
			<th>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_price']-$now_order['discount_price']|floatval}+{pigcms{$now_order.extra_price|floatval}{pigcms{$config.extra_price_alias_name}</th>
		</tr>
		</if>
		<tr>
			<th>{pigcms{:L_("优惠价格")}</th>
			<th>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['discount_price']|floatval}</th>
		</tr>
		<tr>
			<th>{pigcms{:L_("实付金额")}</th>
			<th>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['price']|floatval}</th>
		</tr>
		<tr>
			<th width="15%">{pigcms{:L_("订单备注")}</th>
			<th width="35%">{pigcms{$now_order['desc']}</th>
		</tr>
		<tr>
			<th colspan="2" style="text-align:center;">
				<div id="qrcodeUserWeixinCodeBox">
					<h3 style="margin:20px 0;">{pigcms{:L_("扫用户付款码支付")}</h3>
					<div style="margin-top:60px;" id="qrcodeBox">
						<input type="text" style="height:30px;line-height:30px;padding-left:5px;" id="userQrcode"/>
						<button style="background:#4f99c6!important;text-shadow:none;color:white;" id="userQrcodeBtn">{pigcms{:L_("确认支付")}</button>
					</div>
					<div style="margin-top:60px;text-align:left;margin-left:30px;">
						<font style="color:red;">{pigcms{:L_("支持扫用户微信")}<if condition="$config['arrival_alipay_open'] OR $is_sub_alipay">{pigcms{:L_("、支付宝")}</if><if condition="$config['pay_yzfpay_open'] eq 1">{pigcms{:L_("、翼支付")}</if>{pigcms{:L_("付款码。")}</font></if><br/>
						{pigcms{:L_("建议使用扫码枪直接扫描得到值。")}<br/>{pigcms{:L_("如果扫码提示返回错误，可以关闭本页面重新创建订单。")}
					</div>
				</div>
			</th>
			<th style="text-align:center;">
				<h3 class="wxcode" style="margin:20px 0;">{pigcms{:L_("用户")}<if condition="$config['cash_pay_qrcode']">{pigcms{:L_("微信")}</if>{pigcms{:L_("扫码支付")}
				<if condition="$config.pay_weixinapp_key neq '' AND $config['open_score_fenrun'] eq 1 ">
				<span class="wxcode" id="changeToAppScan"style="margin-left:20px;font-size:12px;color:blue;cursor:pointer;font-weight:normal; ">APP{pigcms{:L_("扫码支付")}</span></h3> 
				
				</if>
				<h3 class="appcode" style="margin:20px 0;">APP{pigcms{:L_("扫码支付")}
				<span  class="appcode"  id="changeToWxScan" style="margin-left:20px;font-size:12px;color:blue;cursor:pointer;font-weight:normal;">{pigcms{:L_("用户")}<if condition="$config['cash_pay_qrcode']">{pigcms{:L_("微信")}</if>{pigcms{:L_("扫码支付")}</span></h3>
				<div>
					<if condition="$config['cash_pay_qrcode']">
						<img class="wxcode userCode" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id={pigcms{$now_order['order_id']+3600000000}" style="width:250px;height:250px;"/>
					<else/>
						<img class="wxcode userCode" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{:urlencode($config['site_url'].'/wap.php?c=My&a=store_order_before&order_id='.$now_order['order_id'])}" style="width:250px;height:250px;"/>
					</if>
					<img class="appcode" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon=store_{pigcms{$now_order.order_id}" style="width:250px;height:250px;display:none"/>
				</div>
			</th>
		</tr>
	</table>
	<script type="text/javascript">
		var canUseAlipay = <if condition="$config['arrival_alipay_open'] OR $is_sub_alipay">true<else/>false</if>;
	</script>
	<script type="text/javascript">
		var checkPayStatus = null;
		var userQrcodeFocus;
		var internal_card_info = ''
		var internalCardNumber = '';
		$(function(){
			if(checkApp()){
				$('#userQrcode').remove();
				$('#qrcodeBox').prepend('<input type="tel" style="height:30px;line-height:30px;padding-left:5px;" id="userQrcode"/>');
				if(parent.customDisplayCan){
					window.pigcmspackapp.custom_display_work('',$('.userCode').attr('src'));
				}

				//商米Ti客显
		  		window.pigcmspackapp.custom_display_work("￥{pigcms{$now_order['price']|floatval=###}","")// content：客显文字。imgUrl：客显图片地址：（图片大小为：124*40）；客显文字 和 图片 每次只能显示一个。
			}
			$('.appcode').hide();
			
			$('#changeToAppScan').click(function(){
				$('.appcode').show();
				$('.wxcode').hide();
			});
			
			$('#changeToWxScan').click(function(){
				$('.wxcode').show();
				$('.appcode').hide();
			});
			
			$('#userQrcode').focus();
			userQrcodeFocus = setInterval(function(){
				if(document.activeElement.id != 'userQrcode'){
					$('#userQrcode').focus();
				}
			},1000);
			var postPrintNow = false; 
			$('#orderprinter').click(function(){
				if(postPrintNow == true){
					parent.layer.alert('{pigcms{:L_("正在请求中，请稍等")}');
					return false;
				}
				postPrintNow = true;
				$.post("{pigcms{:U('store_arrival_print')}",{order_id:{pigcms{$now_order['order_id']}},function(){
					parent.layer.alert('{pigcms{:L_("已发送打印")}');
					postPrintNow = false;
				});
			});
			$('.orderofflinepay').click(function(){
				var tip_index = parent.layer.load(0, {shade: [0.5,'#fff']});
				$.post("{pigcms{:U('store_arrival_pay')}",{order_id:{pigcms{$now_order.order_id},'offline_pay':$(this).data('id')},function(result){
					parent.layer.close(tip_index);
					if(result.status == 1){
						clearInterval(checkPayStatus);
						parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
							window.top.location.reload();
						});
					}else{
						parent.layer.alert(result.info);
					}
				});
			});
			
			$('#userQrcodeBtn').click(function(){
				postUserQrcode();
			});
			
			$('#balance_pay').click(function(){
				clearInterval(userQrcodeFocus)
				parent.layer.open({
				  type: 2,
				  title: '{pigcms{:L_("平台余额支付")}',
				  shadeClose: false,
				  shade: 0.6,
				  area: ['520px', '310px'],
				  content: "{pigcms{:U('arrival_balance_pay_before')}&order_id={pigcms{$now_order['order_id']}",
				  cancel:function(){
					  $('#userQrcode').focus();
						userQrcodeFocus = setInterval(function(){
							if(document.activeElement.id != 'userQrcode'){
								$('#userQrcode').focus();
							}
						},1000);
				  }
				});
			});

				//商家实体卡支付
				$('#merchantEntityCard').click(function(){
				clearInterval(userQrcodeFocus)
				parent.layer.open({
				  title: '{pigcms{:L_("商家实体卡支付")}',
				  shadeClose: false,
				  shade: 0.6,
				  area: ['520px', '200px'],
				  content: `
				  	<div class="merchantEntityCardNumber" style="display: flex;justify-content: center;padding-top: 30px;">
					  <input style="width: 70%" type="text" placeholder="请输入商家实体卡号" id="merchantEntityCardNumber">
					</div>
				  `,
				  btn: ['确定支付'],
				  yes:function(tip_index, layero){
					 let merchantEntityCardNumber  = parent.$('#merchantEntityCardNumber').val()
					 if(merchantEntityCardNumber){
						$.post("{pigcms{:U('store_arrival_pay')}",{order_id:{pigcms{$now_order.order_id},'mer_physical_card_no':merchantEntityCardNumber},function(result){
							parent.layer.close(tip_index);
							if(result.status == 1){
								clearInterval(checkPayStatus);
								parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
									window.top.location.reload();
								});
							}else{
								parent.layer.alert(result.info);
							}
						});
					 }
				  }
				});
			});

			$('#userQrcode').keyup(function(e){
				if(e.keyCode == 13){
					postUserQrcode();
				}
			});
			checkPayStatus = setInterval(function(){
				$.post("{pigcms{:U('store_arrival_check')}",{order_id:{pigcms{$now_order['order_id']}},function(result){
					if(result.status == 1){
						clearInterval(checkPayStatus);
						parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
							top.layer.load(0,{shade:0.3});
							top.location.reload();
						});
					}
				});
			},3000);
		});
		var postNow = false;
		function postUserQrcode(){
			$('#userQrcode').val($.trim($('#userQrcode').val()));
			if(postNow == true){
				parent.layer.alert('{pigcms{:L_("正在请求中，请稍等")}');
				return false;
			}
			var auth_code = $('#userQrcode').val();
			var reg = new RegExp("^[0-9]*$");
			if(!reg.test(auth_code)){
				parent.layer.alert('{pigcms{:L_("付款码仅允许输入数字")}');
				return false;
			}
			if(auth_code.length < 2){
				$('#userQrcode').focus();
				return false;
			}
			var nowPayMethod = '';			
			var codePrefix =  auth_code.substr(0,2);		
			if(codePrefix == '10' || codePrefix == '11' || codePrefix == '12' || codePrefix == '13' || codePrefix == '14' || codePrefix == '15'){
				nowPayMethod = 'weixin';
				if(auth_code.length != 18){
					parent.layer.alert('{pigcms{:L_("微信付款码（以10/11/12/13/14/15为前缀的18位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else if(canUseAlipay == true && (codePrefix == '25' || codePrefix == '26' || codePrefix == '27' || codePrefix == '28' || codePrefix == '29' || codePrefix == '30')){
				nowPayMethod = 'alipay';
				if(auth_code.length < 16 || auth_code.length > 24){
					parent.layer.alert('{pigcms{:L_("支付宝付款码（以25/26/27/28/29/30为前缀的16-24位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else if(codePrefix == '51'){
				nowPayMethod = 'yzfpay';
				if(auth_code.length != 18){
					parent.layer.alert('{pigcms{:L_("翼支付款码（以51为前缀的18位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else if(codePrefix == '90'){
                nowPayMethod = 'qiye';
                if(auth_code.length != 18){
                    parent.layer.alert('{pigcms{:L_("支付失败，用户付款码为:以90为前缀的18位数字")}', function(index){
                        $('#userQrcode').focus();
                        parent.layer.close(index);
                    });
                    return false;
                }
            }else{
				parent.layer.alert('{pigcms{:L_("请扫描微信")}'+(canUseAlipay ? '{pigcms{:L_("或支付宝")}' : '')+'{pigcms{:L_("的付款码")}');
				return false;
			}
			$('#userQrcodeBtn').html('{pigcms{:L_("请求中")}...');
			postNow = true;
			$.post("{pigcms{:U('store_arrival_pay')}",{order_id:{pigcms{$now_order.order_id},'auth_code':auth_code,'auth_type':nowPayMethod},function(result){
				if(result.status == 1){
					clearInterval(checkPayStatus);
					parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
						top.layer.load(0,{shade:0.3});
						top.location.reload();
					});
				}else{
					parent.layer.alert(result.info);
				}
				$('#userQrcodeBtn,#userAlipayQrcodeBtn').html('{pigcms{:L_("确认支付")}');
				postNow = false;
			});
		}

		//一卡通支付逻辑--start
		$('#internalCard').click(function(){
			clearInterval(userQrcodeFocus)
			parent.layer.open({
				title: '{pigcms{:L_("一卡通支付")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '250px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<input style="width: 70%;display: block" type="text" placeholder="请输入一卡通号或者手机号" id="internalCardNumber">
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['查询'],
				yes:function(tip_index, layero){
				internalCardNumber  = parent.$('#internalCardNumber').val()
				ajax_card_new(tip_index,internalCardNumber)
				},
				success: function(layero, tip_index) {
					parent.$('#internalCardNumber').focus();
				}
			});
		});

		//一卡通查询逻辑
		function ajax_card_new(tip_index,key,password=''){
			if (key.length < 1) return false;
			let params = {
				order_id:{pigcms{$now_order.order_id},
				key,
				type:'ecard',
				password,
				v20_ticket: getCookieValue('storestaff_access_token'),
			}
			$.post(`/appapi.php?c=Storestaff&a=ajax_card_new`, params, function(res){
				let data = res.result
				if (res.errorCode == '0') {
					// 第一次输入是否需要密码
					if(data.need_password == true){
						internalCardPassword()
						return
					}

					//不需要密码或者输入密码操作
					if(data.internal_card_ids && data.internal_card_ids.length>0){
						internal_card_info = data
						internalCardPay()
					}else if(data.internal_card_ids){
						parent.$('.internalCardTip').html('未搜索到信息');
					}
				} else {
					parent.$('.internalCardTip').html(res.errorMsg)
					return
				}
			}, 'json');
		}

		//一卡通输入密码
		function internalCardPassword(){
			parent.layer.open({
				title: '{pigcms{:L_("一卡通密码")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '250px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<input style="width: 70%;display: block" type="password" placeholder="请输入一卡通密码" id="internalCardNumber">
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['确定'],
				yes:function(tip_index, layero){
				let password  = parent.$('#internalCardNumber').val()
				if(!password)return
				ajax_card_new(tip_index,internalCardNumber,password)
				},
				success: function(layero, tip_index) {
					parent.$('#internalCardNumber').focus();
				}
			});
		}
		
		//一卡通支付
		function internalCardPay(){
			parent.layer.open({
				title: '{pigcms{:L_("一卡通支付")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '200px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<p id="internalCardTitle" style="font-size: 16px;color: #f88718;">
						当前一卡通余额：￥<span id="ecard_total_money">${internal_card_info.ecard_total_money || 0}</span>
						，待支付 ￥<span class="total_price" style="color: red;">{pigcms{$now_order['price']|floatval}</span>
					</p>
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['支付'],
				yes:function(tip_index, layero){
				let params = {
					order_id: "{pigcms{$now_order['order_id']}",
					auth_type: 'ecard',
					price: "{pigcms{$now_order['price']|floatval}",
					offline_pay: -1,
					phone: internalCardNumber,
					v20_ticket: getCookieValue('storestaff_access_token'),
				}
				$.post(`/appapi.php?c=Storestaff&a=store_arrival_pay`, params, function(res){
					let data = res.result
					if (res.errorCode == '0') {
						parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
							window.top.location.reload();
						});
					} else {
						parent.$('.internalCardTip').html(res.errorMsg)
						return
					}

				}, 'json');
				}
			});
		}

		// 获取指定名称的 cookie 值
		function getCookieValue(cookieName) {
			var cookies = document.cookie;
			if (cookies) {
				var cookieArr = cookies.split(';');
				for (var i = 0; i < cookieArr.length; i++) {
				var cookie = cookieArr[i].trim();
				if (cookie.indexOf(cookieName + '=') === 0) {
					return decodeURIComponent(cookie.substring(cookieName.length + 1));
				}
				}
			}
			return null;
		}
		//一卡通支付逻辑--end
	</script>
	</body>
</html>