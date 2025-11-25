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
					<li class="urlLink" data-url="{pigcms{:U('ticket_order_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink cur" data-url="{pigcms{:U('ticket_verify_list')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("门票验证")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<style>
					.ace-file-input a {display:none;}
				</style>
				<form id="find-form" method="post">
					<select name="find_type" id="find_type"  class="col-xs-1" style="height: 42px;margin-right: 16px;float:left;" >
						<optgroup label="{pigcms{:L_("景区门票")}">
							<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>{pigcms{:L_("消费密码")}</option>
						</optgroup>

						<optgroup label="{pigcms{:L_("通用")}">
							<option value="2" <if condition="$find_type eq 2">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
							<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>{pigcms{:L_("用户")}ID</option>
							<option value="4" <if condition="$find_type eq 4">selected="selected"</if>>{pigcms{:L_("用户昵称")}</option>
							<option value="5" <if condition="$find_type eq 5">selected="selected"</if>>{pigcms{:L_("手机号码")}</option>
						</optgroup>
					</select>
					<input class="col-sm-4" name="find_value" id="find_value" type="text" value="{pigcms{$find_value}" style="margin-right:10px;font-size:18px;height:42px;padding:0;"/>
					<button class="btn btn-success" type="submit" id="find_submit">{pigcms{:L_("查找订单")}</button>
				</form>
				<!-- <p>&nbsp;{pigcms{:L_("此页面只列出已归属到此店铺的订单。若想验证新订单或查找订单，请点击'查找订单'按钮。")}</p> -->
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("门票标题")}</th>
									<th>{pigcms{:L_("门票数量")}</th>
									<th>{pigcms{:L_("门票信息")}</th>
									<th>{pigcms{:L_("订单信息")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th class="button-column" style="width:100px;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td width="50">{pigcms{$vo.order_id}</td>
											<td>{pigcms{$vo.ticket_title}</td>
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
												{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
												{pigcms{:L_("手机号：")}{pigcms{$vo.phone}<br/>
											</td>
											<td>
												{pigcms{:L_("状态：")}<if condition="$vo.paid eq 1"><font color="red">{pigcms{:L_("未支付")}</font><else/><font color="green">{pigcms{:L_("已支付")}</font></if>
												<if condition="$vo.order_status eq 1"><font color="green">{pigcms{:L_("未消费")}</font>
													<elseif condition="$vo.order_status eq 2"/><font color="green">{pigcms{:L_("未评价")}</font>
													<elseif condition="$vo.order_status eq 3"/><font color="green">{pigcms{:L_("已评价")}</font>
													<elseif condition="$vo.order_status eq 4"/><font color="red">{pigcms{:L_("退款中")}</font>
													<elseif condition="$vo.order_status eq 5"/><font color="red">{pigcms{:L_("已退款")}</font>
												</if>
												<br/>{pigcms{:L_("下单时间：")}{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}<br/>
												{pigcms{:L_("支付时间：")}{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<br/>
											</td>
											<td class="button-column" width="40">
												<eq name="vo.state" value='1'>
													<if condition="$vo['is_group'] eq 2 || $vo['ticket_num']==1">
														<a href=""  onclick="verify({pigcms{$vo.order_id})"><i class="ace-icon fa fa-search bigger-130"></i>{pigcms{:L_("验证消费")}</a>
													<else/>
														<a class="green handle_btn" data-title="{pigcms{:L_("消费码详情")}" href="{pigcms{:U('Store/ticket_pass_array',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("查看消费码详情")}</a>
													</if>
												</eq>
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
	<script>
	 	function verify(order_id){
	 		if(!confirm('{pigcms{:L_("确认核销该订单！")}')){
	 			return false;
	 		}
			$.ajax({
				type : "post",
				url : "{pigcms{:U('ajax_verify_ticket')}",
				dataType : "json",
				data:{

					order_id :order_id,
				},
				async:false,
				success : function(result){
					var rideList	=	result;
					if(result.errorCode == 0){
						alert(result.errorMsg);
						window.location.href	=	rideList.result;
					}else{
						alert(result.errorMsg)
					}
				},
				error:function(){
					console.log(123);
					alert('{pigcms{:L_("提交订单失败")}');
				}
			})
		}
	</script>
	<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
</html>