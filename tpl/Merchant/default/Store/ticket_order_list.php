<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" href="./tpl/Merchant/default/static/css/font-awesome.min.css">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.scenic_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('ticket_order_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('ticket_verify_list')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("门票验证")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<style>
					.ace-file-input a {display:none;}
				</style>
				<form id="frmselect" method="get" action="" style="margin-bottom:0;">
					<input type="hidden" name="c" value="Store"/>
					<input type="hidden" name="a" value="ticket_order_list"/>
					<select id="ticket_id" name="ticket_id" class="col-xs-1">
						<option value="0" <if condition="$_GET['ticket_id'] eq 0">selected="selected"</if>>{pigcms{:L_("全部订单")}</option>
						<volist name="ticket_list" id="vo">
							<option value="{pigcms{$vo.ticket_id}" <if condition="$_GET['ticket_id'] eq $vo['ticket_id']">selected="selected"</if>>{pigcms{$vo.ticket_title}</option>
						</volist>
					</select>
				</form>
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("门票标题")}</th>
									<th>{pigcms{:L_("游玩时间")}</th>
									<th>{pigcms{:L_("门票数量")}</th>
									<th>{pigcms{:L_("门票信息")}</th>
									<th>{pigcms{:L_("订单信息")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th class="button-column" style="width:100px;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="now_order">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td width="50">{pigcms{$vo.order_id}</td>
											<td>{pigcms{$vo.ticket_title}</td>
											<td>{pigcms{$vo.ticket_time}</td>
											<td>
												{pigcms{:L_("门票数量：")}{pigcms{$vo.ticket_count}张<br/>
												{pigcms{:L_("车位数量：")}<if condition="$vo['park_count'] eq 0">{pigcms{:L_("未预定")}<else/>{pigcms{$vo.park_count}位</if><br/>
												{pigcms{:L_("向导预定：")}<if condition="$vo['guide'] eq 0">{pigcms{:L_("未预定")}<else/><span class="red">{pigcms{:L_("已预定")}</span></if><br/>
											</td>
											<td>
												{pigcms{:L_("总价：")}{pigcms{$vo.order_total}<br/>
												{pigcms{:L_("余额支付：")}{pigcms{$vo.balance_pay}<br/>
												{pigcms{:L_("在线支付：")}{pigcms{$vo.payment_money}<br/>
											</td>
											<td>
												{pigcms{:L_("用户ID：")}{pigcms{$vo.user_id}<br/>
												{pigcms{:L_("用户名：")}{pigcms{$vo.truename}<br/>
												{pigcms{:L_("手机号：")}{pigcms{$vo.phone}<br/>
											</td>
											<td>
												{pigcms{:L_("状态：")}<if condition="$vo.paid eq 1"><font color="red">{pigcms{:L_("未支付")}</font><else/><font color="green">{pigcms{:L_("已支付")}</font></if>
												<if condition="$vo.order_status eq 1"><font color="green">{pigcms{:L_("未消费")}</font>
													<elseif condition="$vo.order_status eq 2"/><font color="green">{pigcms{:L_("未评价")}</font>
													<elseif condition="$vo.order_status eq 3"/><font color="green">{pigcms{:L_("已评价")}</font>
													<elseif condition="$vo.order_status eq 4"/><font color="red">{pigcms{:L_("用户退款")}</font>
													<elseif condition="$vo.order_status eq 5"/><font color="gray">{pigcms{:L_("已过期")}</font>
													<elseif condition="$vo.order_status eq 6"/><font color="gray">{pigcms{:L_("已过期")}</font>
												</if>
												<br/>{pigcms{:L_("下单时间：")}{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}<br/>
												<if condition="$vo['pay_time']">{pigcms{:L_("支付时间：")}{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<br/></if>
											</td>
											<td class="button-column" width="40">
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn2" style="padding-right:8px;" href="{pigcms{:U('ticket_order_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$show}
					</div>
				</div>
			</div>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
	$(function(){
		$('.handle_btn2').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("操作订单")}',
				padding: 0,
				width: 720,
				height: 520,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});

		$('#ticket_id').change(function(){
			$('#frmselect').submit();
		});
		$('#type_id').change(function(){
			$('#frmselect').submit();
		});
	});
	</script>
</html>