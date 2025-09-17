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
					<li class="urlLink " data-url="{pigcms{:U('physical_card')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("实体卡管理")}</div>
					</li>
					<li class="urlLink cur" data-url="{pigcms{:U('physical_card_log')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("实体卡管理记录")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>ID</th>
								<th>{pigcms{:L_("实体卡")}ID</th>
								<th>{pigcms{:L_("商家")}</th>
								<th>{pigcms{:L_("店员")}</th>
								<th>{pigcms{:L_("描述")}</th>
								<th>{pigcms{:L_("添加时间")}</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($log_list)">
								<volist name="log_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_id}</td>
										<td>{pigcms{$merchant_session.name}</td>
										<td>{pigcms{$vo.staff_name}</td>
										<td>{pigcms{$vo.des}</td>
										<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
									
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="7">{pigcms{:L_("列表为空！")}</td></tr>
							</if>
						</tbody>
					</table>
					{pigcms{$pagebar}
				</div>
			</div>
		</div>
	</body>
</html>