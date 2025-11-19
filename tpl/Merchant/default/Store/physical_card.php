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
		<script type="text/javascript" src=".{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{:L_("实体卡管理")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('physical_card')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("实体卡管理")}</div>
					</li>
					<li class="urlLink " data-url="{pigcms{:U('physical_card_log')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("实体卡管理记录")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="fixed_header">
					<button class="btn btn-success handle_btn" data-title="{pigcms{:L_("绑定实体卡")}" data-box_width="800px" data-box_height="580px" href="{pigcms{:U('physical_card_add')}">{pigcms{:L_("绑定实体卡")}</button>
				</div>
				<div class="alert alert-block alert-success">
					<p>	{pigcms{:L_("绑定实体卡后，用户的平台余额中会增加相应的实体卡余额数；如果平台在添加实体卡时绑定了商户编号，则店员只能绑定该商家下的实体卡用户；反之，则店员可以绑定平台所有实体卡用户")}
						<br/>
						
					</p>
				</div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("卡号")}</th>
								<th>{pigcms{:L_("用户昵称")}</th>
								<th>{pigcms{:L_("用户电话")}</th>
								<th>{pigcms{:L_("实体卡余额")}</th>
								<th>{pigcms{:L_("绑定时间")}</th>
								<th>{pigcms{:L_("操作员")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="card_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td><div class="tagDiv">{pigcms{$vo.cardid}</div></td>
									<td>{pigcms{$vo['nickname']}</td>
									<td>{pigcms{$vo['phone']}</td>
									<td>{pigcms{$vo['card_money']|floatval}</td>
									<td><if condition="$vo.regtime gt 0 ">{pigcms{$vo.regtime|date="Y-m-d H:i:s",###}</if></td>
									<td>{pigcms{$vo.staff_name}</td>
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