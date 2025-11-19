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
		<title>{pigcms{:L_("免单套餐")} - {pigcms{:L_("店员管理中心")}</title>
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
			<div class="txt">{pigcms{:L_("免单订单列表")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_('返回首页')}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_('刷新页面')}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('sub_card')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('sub_card_find')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("查找订单")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="form-group clearfix">
					<form action="{pigcms{:U('Stroe/sub_card')}" method="get">
						<input type="hidden" name="c" value="Store"/>
						<input type="hidden" name="a" value="sub_card"/>
						
						
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<select name="searchtype">
								<option value="pass" <if condition="$_GET['searchtype'] eq 'pass'">selected="selected"</if>>{pigcms{:L_("消费码")}</option>
		
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
							</select>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" style="width:200px;"/>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;-&nbsp;			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						</div>
					
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<input type="submit" value="{pigcms{:L_('查询')}" class="btn btn-success" style="padding:2px 14px;"/>　
							<!--a href="{pigcms{:U('Store/group_export',$_GET)}" class="down_excel" style="float:right;padding:8px 14px;border:1px solid #629b58;color:#629b58;">{pigcms{:L_("导出订单")}</a-->
						</div>
					</form>
				</div>
				<p>&nbsp;{pigcms{:L_("此页面只列出已归属到此店铺的订单。若想验证新订单或查找订单，请点击‘查找订单’按钮。")}</p>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("订单号")}</th>
								<th>{pigcms{:L_("订单名称")}</th>
								<th>{pigcms{:L_("用户信息")}</th>
								<th>{pigcms{:L_("订单金额")}</th>
						
								<th>{pigcms{:L_("消费码")}</th>
								<th>{pigcms{:L_("支付时间")}</th>
								<th>{pigcms{:L_("操作店员")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td><div class="tagDiv">{pigcms{$vo.id}</div></td>
									<td><div class="tagDiv">{pigcms{$vo.name}</div></td>
									<td>{pigcms{:L_("昵称：")}{pigcms{$vo['nickname']}<br/>{pigcms{:L_("手机：")}{pigcms{$vo['phone']}</td>
									<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo['price']/$vo['free_total_num']|round=###,2}</td>
								
							
									<td>{pigcms{$vo.pass}</td>
									<td>{pigcms{$vo.use_time|date="Y-m-d H:i:s",###}</td>
									<td>{pigcms{$vo.last_staff}</td>
								</tr>
							</volist>
						</tbody>
					</table>
					{pigcms{$pagebar}
				</div>
			</div>
		</div>
		<script>
			$(function(){
				if($(window).width() <= 1024){
					$('.hide_col').remove();
				}
			});
		</script>
	</body>
</html>