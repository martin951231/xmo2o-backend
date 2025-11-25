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
		<script>
			var scan_pay_url = "{pigcms{:U('scan_payid_check')}"
		</script>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{:L_("店内收银")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('store_arrival')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("店内收银")}</div>
					</li>
					<if condition="$config['is_cashier']">
						<li class="urlLink" data-url="{pigcms{:U('cashier')}" style="display:none">
							<div class="icon cashier"></div>
							<div class="text">{pigcms{:L_("收银台（独立）")}</div>
						</li>
					</if>
				</ul>
			</div>
			<div class="rightMain">
				<div class="fixed_header">
					<button class="btn btn-success arrival_add" data-title="{pigcms{:L_("创建订单")}" data-box_width="800px" data-box_height="800px" href="{pigcms{:U('store_arrival_add')}">{pigcms{:L_("创建订单")}</button>
					<if condition="$config['open_score_fenrun'] eq 1"><button class="btn btn-success handle_btn scan_pay" data-title="{pigcms{:L_("扫码支付")}" data-box_width="800px" data-box_height="580px" href="{pigcms{:U('store_scan_payid')}">{pigcms{:L_("扫码支付")}</button></if>
				</div>
				<div class="alert alert-block alert-success">
					<p>
						{pigcms{:L_("店内收银功能分为两种：")}
						<br/>
						<br/>
						{pigcms{:L_("第1种：店员输入用户微信付款码付款，理解为扫用户码（支持微信")}<if condition="$config['arrival_alipay_open'] OR $is_sub_alipay">{pigcms{:L_("、支付宝")}</if><if condition="$config['pay_yzfpay_open'] eq 1">{pigcms{:L_("、翼支付")}</if>{pigcms{:L_("付款码。")}</font></if>{pigcms{:L_("）。")}
						<br/>
						{pigcms{:L_("第2种：店员输入好价钱后，让用户扫码付款，支持平台全部在线支付渠道。")}
					</p>
				</div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("订单号")}</th>
								<th>{pigcms{:L_("用户信息")}</th>
								<th class="hide_col" width="10%">{pigcms{:L_("订单描述")}</th>
								<th>{pigcms{:L_("订单金额")}</th>
								<th class="hide_col">{pigcms{:L_("优惠买单店铺优惠金额")}</th>
								<if condition="open_extra_price eq 1"><th class="hide_col">{pigcms{:L_("获得")}{pigcms{$config.score_name}数</th></if>
								<th class="hide_col">{pigcms{:L_("使用X1数",$config['score_name'])}</th>
								<th class="hide_col">{pigcms{:L_("X1抵扣",$config['score_name'])}</th>
								<th class="hide_col">{pigcms{:L_("商家优惠券抵扣")}</th>
								<th class="hide_col">{pigcms{:L_("平台优惠券抵扣")}</th>
								<if condition="$config['is_open_pay_bind_user']">
								<th class="hide_col">{pigcms{:L_("会员等级优惠金额")}</th>
								<th class="hide_col">{pigcms{:L_("商家会员卡折扣金额")}</th>
								<th class="hide_col">{pigcms{:L_("商家会员卡余额支付")}</th>
								<th class="hide_col">{pigcms{:L_("商家会员卡赠送余额支付")}</th>
								<th class="hide_col">{pigcms{:L_("平台余额支付")}</th>
								<th class="hide_col">{pigcms{:L_("线下支付")}</th>
								</if>
								<if condition="C('customization.internal_membership_card')">
									<th>{pigcms{:L_("一卡通支付金额")}</th>
								</if>
								<th>{pigcms{:L_("线上支付金额")}</th>
                                <th>{pigcms{:L_("企业预付款金额")}</th>
								<th>{pigcms{:L_("实收金额")}</th>
								<th>{pigcms{:L_("支付时间")}</th>
								<th>{pigcms{:L_("支付类型")}</th>
								<th class="hide_col">{pigcms{:L_("状态")}</th>
								<th>{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
								
									<td><div class="tagDiv">{pigcms{$vo.order_id}</div></td>
									<td>
										<if condition="$vo['uid']">
											{pigcms{:L_("昵称：")}{pigcms{$vo['nickname']}<br/>{pigcms{:L_("手机：")}{pigcms{$vo['phone']}
										<else/>
											&nbsp;<br/>&nbsp;
										</if>
									</td>
									<td class="hide_col">{pigcms{$vo['desc']}<if condition="$vo.payid neq '' "><if condition="$vo.desc neq ''">,</if>{pigcms{:L_("支付码(平台)：")}{pigcms{$vo.payid}</if></td>
									<td>{pigcms{$vo['total_price']|floatval}</td>
									<td class="hide_col">{pigcms{$vo['discount_price']|floatval}</td>
									<if condition="open_extra_price eq 1"><td class="hide_col">{pigcms{$vo.score_give|floatval}</td></if>
									<td class="hide_col">{pigcms{$vo.score_used_count}</td>
									<td class="hide_col">{pigcms{$vo.score_deducte|floatval}</td>
									<td class="hide_col">{pigcms{$vo.card_price|floatval}</td>
									<td class="hide_col">{pigcms{$vo.coupon_price|floatval}</td>
									<if condition="$config['is_open_pay_bind_user']">
										<td class="hide_col">{pigcms{$vo.vip_discount_money}</td>
										<td class="hide_col">{pigcms{$vo.card_discount_money|floatval}</td>
										<td class="hide_col">{pigcms{$vo.merchant_balance|floatval}</td>
										<td class="hide_col">{pigcms{$vo.card_give_money|floatval}</td>
										<td class="hide_col">{pigcms{$vo.balance_pay|floatval}</td>
										<td class="hide_col">{pigcms{$vo.offline_money|floatval}</td>
									</if>
									<if condition="C('customization.internal_membership_card')">
										<td>{pigcms{$vo.internal_card_pay_money|floatval}</td>
									</if>
									<td>{pigcms{$vo['payment_money']|floatval}</td>
                                    <td>{pigcms{$vo['qiye_pay']|floatval}</td>
									<td>{pigcms{$vo['pay_money']|floatval}</td>
									<td>{pigcms{$vo.pay_time|date="Y/m/d H:i:s",###}</td>
									<td>{pigcms{$vo.pay_type_show}</td>
									<if condition="$vo['refund'] eq 0">
										<td></td>
										<td onclick="refund('{pigcms{$vo.orderid}')">退款</td>
									<else/>
										<td class="hide_col">
											<if condition="$vo['refund_staff_name']">
												{pigcms{$vo.refund_time|date="Y/m/d H:i:s",###}退款<br/>{pigcms{$vo.refund_staff_name}
											<else/>
												{pigcms{$vo.refund_time|date="Y/m/d H:i:s",###}退款
											</if>
											
										</td>
										<td></td>
									</if>
								</tr>
							</volist>
						</tbody>
					</table>
					{pigcms{$pagebar}
				</div>
			</div>
			
			<div class="fix chat" >
				<div class="chat_top clr">
					<div class="fl wx_chat">{pigcms{:L_("扫码支付")}</div>
					<div class="fr return">{pigcms{:L_("返回")} Esc</div>
				</div>
				<div class="chat_end" style="    background: gainsboro;">
					<div class="chat_n">
						<h2>{pigcms{:L_("扫描用户平台付款码支付")}</h2>
						<div class="input clr">
							<input type="text" class="port fl" id ="scan_payid" value="" id="weixin_txt">
							<div class="firm fr" id ="scan_pay" data-title="{pigcms{:L_("创建订单")}" data-box_width="800px" data-box_height="580px" data-href="{pigcms{$config.site_url}{pigcms{:U('store_arrival_add')}">{pigcms{:L_("确认支付")}</div>
						</div>
						<p>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户的付款码，再写入。如果扫码提示错误，可以关闭本页面重新创建订单。")}</p>
					</div>
				</div>
			</div>
			<div class="shadow_two"></div>
		</div>
		<script>
			function refund(orderid){
			    if(confirm("取消订单后，用户支付金额将原路返回，并且不能恢复，确定取消吗？")){
					$.post("{pigcms{:U('refund_store_order')}",{orderid:orderid},function(data){
						if(data.status == 1){
							location.reload();
						}else{
							alert(data.info);
						}
					})
				}
			}

			$('.arrival_add').click(function(){
				$.post("{pigcms{:U('check_merchant')}",{type:'store'},function(data){
					if (data.error==1) {
						layer.alert(data.msg);
					}else{						
						layer.open({
							type: 2,
							title: '{pigcms{:L_("创建订单")}',
							shadeClose: true,
							shade: 0.6,
							area: ['800px','500px'],
							content: "{pigcms{:U('store_arrival_add')}"
						});						
					}
				},'json')
			});

			$(function(){
				if($(window).width() <= 1024){
					$('.hide_col').remove();
				}
			});
			
			if(checkAndroidApp()){
				window.pigcmspackapp.get_custom_display('get_custom_display');
			}
			var customDisplayCan = false;
			var customDisplayType = '';
			var customCanImage = '';
			function get_custom_display(displayType,canImage){
				if(displayType){
					customDisplayCan = true;
					customDisplayType = displayType;
					customCanImage = canImage;
					window.pigcmspackapp.custom_display_work('','');
				}
			}
		</script>
	</body>
</html>