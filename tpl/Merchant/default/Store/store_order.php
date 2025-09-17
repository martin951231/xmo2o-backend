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
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.cash_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_('返回首页')}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_('刷新页面')}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('store_order')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("订单号")}</th>
								<th>{pigcms{:L_("用户信息")}</th>
								<th>{pigcms{:L_("订单金额")}</th>
								<th class="hide_col">{pigcms{:L_("优惠金额")}</th>
								<th class="hide_col">{pigcms{:L_("获得")}{pigcms{$config.score_name}数</th>
								<th class="hide_col">{pigcms{:L_("使用")}{pigcms{$config.score_name}数</th>
								<th>{pigcms{:L_("实付金额")}</th>
								<if condition="C('customization.internal_membership_card')">
                                    <th>{pigcms{:L_("一卡通支付金额")}</th>
                                </if>
								<th>{pigcms{:L_("支付时间")}</th>
								<th>{pigcms{:L_("支付类型")}</th>
                                <th>{pigcms{:L_("备注")}</th>
								<th class="hide_col">{pigcms{:L_("状态")}</th>
								<th>{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td><div class="tagDiv">{pigcms{$vo.order_id}</div></td>
									<td>{pigcms{:L_("昵称：")}{pigcms{$vo['nickname']}<br/>{pigcms{:L_("手机：")}{pigcms{$vo['phone']}</td>
									<td>{pigcms{$vo['total_price']|floatval}</td>
									<td class="hide_col">{pigcms{$vo['discount_price']|floatval}</td>
									<td class="hide_col">{pigcms{$vo.score_give|floatval}</td>
									<td class="hide_col">{pigcms{$vo.score_used_count|floatval}</td>
									<td>{pigcms{$vo['pay_money']|floatval}</td>
									<if condition="C('customization.internal_membership_card')">
                                        <td>{pigcms{$vo.internal_card_pay_money|floatval}</td>
                                    </if>
									<td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
									<td>{pigcms{$vo.pay_type_show}</td>
                                    <td>{pigcms{$vo.remark}</td>
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

			$(function(){
				if($(window).width() <= 1024){
					$('.hide_col').remove();
				}
			});
		</script>
	</body>
</html>