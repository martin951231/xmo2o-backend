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
			<div class="txt">{pigcms{:L_("店铺余额")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('money_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("店铺余额列表")}</div>
					</li>
					<li class="handle_btn" data-title="{pigcms{:L_("店铺充值")}" data-box_width="800px" data-box_height="300px" class="green handle_btn"  href="{pigcms{:U('Store/recharge')}" data-url="{pigcms{:U('recharge')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("充值")}</div>
					</li>
					
					<li class="urlLink"  data-url="{pigcms{:U('recharge_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("充值列表")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<form action="{pigcms{:U('Stroe/group_list')}" method="get">
					<input type="hidden" name="c" value="Store"/>
					<input type="hidden" name="a" value="money_list"/>
				
				{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
						<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
					</select>
					
					<select name="type">
						<volist name="alias_name" id="vo">
							<option value="{pigcms{$key}" <if condition="$_GET['type'] eq $key">selected=selected</if>>{pigcms{$vo}</option>
						</volist>
					</select>
					<font color="#000">{pigcms{:L_("日期筛选：")}</font>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
				
					　
					<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　
					<a class="btn btn-success" style="padding:2px 14px;"   href="{pigcms{:U('Store/store_money_export',$_GET)}">{pigcms{:L_("导出")}</a>
				
				</form>
				<p>&nbsp;</p>{pigcms{:L_("当前店铺余额")} : {pigcms{$config['Currency_symbol']}{pigcms{$store.money|floatval}
				<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单号")}</th>
									<th>{pigcms{:L_("订单类型")}</th>
									<th>{pigcms{:L_("订单详情")}</th>
									<th>{pigcms{:L_("数量")}</th>
									<th>{pigcms{:L_("总额")}</th>
									<th>{pigcms{:L_("平台佣金")}<font color="red" size="1">({pigcms{:L_("提现代表手续费")})</font></th>
									<th>{pigcms{:L_("当前店铺余额")}</th>
									<th>{pigcms{:L_("对账时间")}</th>
									<th class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td width="100">{pigcms{$vo.id}</td>
										<td width="100">{pigcms{$vo.order_id}</td>
										<td width="100">{pigcms{:msubstr($vo['desc'],0,50,true,'utf-8')}</td>
										<td width="100">{pigcms{$vo.num|floatval}</td>
										<td width="100"><if condition="$vo.income eq 1"><font color="#2bb8aa">+{pigcms{$vo.money|floatval}</font><elseif condition="$vo.income eq 2" /><font color="#f76120">-{pigcms{$vo.money|floatval}</font></if></td>
										<td width="150">{pigcms{$vo.system_take|floatval}<if condition="$vo['system_take'] gt 0" >{pigcms{:L_("（抽成比例")} {pigcms{$vo.percent|floatval} %）</if></td>
									
										<td width="100">{pigcms{$vo.now_store_money|floatval}</td>
										<td width="150">
									
											{pigcms{$vo['use_time']|date='Y-m-d H:i:s',###}
										
										</td>
									
									
										
										<td style="font-size:12px;">
											<if condition="$vo.type eq 'group'">
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-title="{pigcms{:L_("订单详情")}"  style="padding-right:8px;" href="{pigcms{:U('Store/group_edit',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130">{pigcms{:L_("查看详情")}</i>
												</a>
											<elseif condition="$vo.type eq 'meal'" />
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-title="{pigcms{:L_("订单详情")}"  style="padding-right:8px;" href="{pigcms{:U('foodshop_order',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130">{pigcms{:L_("查看详情")}</i>
												</a>
											<elseif condition="$vo.type eq 'appoint'" />
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-title="{pigcms{:L_("订单详情")}"  style="padding-right:8px;" 
												href="{pigcms{:U('appoint_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130">{pigcms{:L_("查看详情")}</i>
												</a>
											
											<elseif condition="$vo.type eq 'shop'" />
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-title="{pigcms{:L_("订单详情")}"   style="padding-right:8px;" href="{pigcms{:U('shop_order_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130">{pigcms{:L_("查看详情")}</i>
												</a>
											<elseif condition="$vo.type eq 'store' OR $vo.type eq 'cash'" />
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-title="{pigcms{:L_("订单详情")}"  style="padding-right:8px;" href="{pigcms{:U('Orderdetail/store_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">
													<i class="ace-icon fa fa-search bigger-130">{pigcms{:L_("查看详情")}</i>
												</a>
					
											
											</if>
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
	
	<script type="text/javascript">

	
	</script>
</html>