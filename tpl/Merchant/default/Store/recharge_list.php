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
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{:L_("充值记录")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink " data-url="{pigcms{:U('money_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("店铺余额列表")}</div>
					</li>
					<li class="handle_btn" data-title="{pigcms{:L_("店铺充值")}" data-box_width="800px" data-box_height="300px" class="green handle_btn"  href="{pigcms{:U('Store/recharge')}" data-url="{pigcms{:U('recharge')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("充值")}</div>
					</li>
					
					<li class="urlLink cur"  data-url="{pigcms{:U('recharge_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("充值列表")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<form action="{pigcms{:U('Stroe/recharge_list')}" method="get">
					<input type="hidden" name="c" value="Store"/>
					<input type="hidden" name="a" value="recharge_list"/>
				
					
					{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
						<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
					</select>
					<font color="#000">{pigcms{:L_("日期筛选：")}</font>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
					{pigcms{:L_("订单状态筛选")}: 
					<select id="status" name="status" >
						
						<option value="1" <if condition="$_GET['paid'] eq '1'">selected="selected"</if>>{pigcms{:L_("已支付")}</option>
						<option value="0" <if condition="$_GET['paid'] eq '0'">selected="selected"</if>>{pigcms{:L_("未支付")}</option>
					</select>
					　
			
					{pigcms{:L_("支付方式筛选")}: 
					<select id="pay_type" name="pay_type">
						<option value="weixin" <if condition="$_GET['pay_type'] eq 'weixin'">selected="selected"</if>>{pigcms{:L_("微信支付")}</option>
						<option value="alipay" <if condition="$_GET['pay_type'] eq 'alipay'">selected="selected"</if>>{pigcms{:L_("支付宝支付")}</option>
						<option value="allinpay" <if condition="$_GET['pay_type'] eq 'allinpay'">selected="selected"</if>>{pigcms{:L_("通联支付")}</option>
					</select>
					</select>
					<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　
					
				</form>
				<p>&nbsp;</p>{pigcms{:L_("此页面只列出已归属到此店铺的订单。若想验证新订单或查找订单，请点击"查找订单"按钮。")}
				<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单编号")}</th>
									<th>{pigcms{:L_("流水号")}</th>
									<th>{pigcms{:L_("金额")}</th>
									<th>{pigcms{:L_("支付时间")}</th>
									<th>{pigcms{:L_("支付方式")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
								
								</tr>
							</thead>
							<tbody>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td width="100">{pigcms{$vo.order_id}</td>
										<td width="100">{pigcms{$vo.orderid}</td>
										<td width="100">{pigcms{$vo.money|floatval}</td>
									
										<td width="150">
									
											<if condition="$vo.pay_time gt 0">{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}</if>
										
										</td>
										<td width="150">
									
											{pigcms{$pay_type[$vo['pay_type']]}
										
										</td>
										
										<td width="150">
									
											<if condition="$vo.paid eq 1"><font color="green">{pigcms{:L_("已支付")}</font>
											<elseif condition="$vo.paid eq 0"/><font color="red">{pigcms{:L_("未支付")}</font></if>
										
										</td>
									</tr>
								</volist>
							</tbody>
						</table>
					{pigcms{$pagebar}
					</div>
				</div>
			</div>
	</body>
</html>