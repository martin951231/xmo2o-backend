<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
	<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
	<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
</head>
	<body>
		<div class="mainBox">
			<div class="rightMain">
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th width="50">{pigcms{:L_("订单编号")}</th>
								<th width="80">{pigcms{:L_("客户姓名")}</th>
								<th width="80">{pigcms{:L_("客户电话")}</th>
								<th width="80" class="button-column">{pigcms{:L_("预订金")}</th>
								<th width="80">{pigcms{:L_("预订类型")}</th>
								<th width="80">{pigcms{:L_("预约到店时间")}</th>
								<th width="80">{pigcms{:L_("下单时间")}</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$list">
							<volist name="list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td>{pigcms{$vo.real_orderid}</td>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.phone}</td>
									<td class="button-column">{pigcms{$vo.book_price|floatval}</td>
									<if condition="$vo['order_from'] eq 2">
									<td>{pigcms{:L_("线下预约")}</td>
									<else />
									<td>{pigcms{:L_("线上预约")}</td>
									</if>
									<if condition="$vo['book_time']">
									<td>{pigcms{$vo.book_time|date='Y-m-d H:i:s',###}</td>
									<else />
									<td>--</td>
									</if>
									<td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
								</tr>
							</volist>
							<else />
							<tr>
								<td colspan="12" style="text-align: center;">{pigcms{:L_("该餐台暂无预定")}</th>
							</tr>
							</if>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>

</html>