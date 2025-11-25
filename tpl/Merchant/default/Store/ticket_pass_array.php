<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<title>{pigcms{:L_("小猪")}{pigcms{$config.meal_alias_name}{pigcms{$config.scenic_alias_name}{pigcms{:L_("系统")} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
		<table>
			<tr>
				<th width="15%">{pigcms{:L_("订单编号")}</th>
				<td width="35%">{pigcms{$info.order_imm}</td>
				<th width="15%">{pigcms{$config.scenic_alias_name}{pigcms{:L_("商品")}</th>
				<td width="35%"><a href="{pigcms{$now_order.url}" target="_blank" title="{pigcms{:L_("查看商品详情")}">{pigcms{$info.ticket_title}</a></td>
			</tr>
			<tr>
				<th colspan="3" >{pigcms{:L_("消费码详情")}</th>
				<th colspan="3" >{pigcms{:L_("操作")}&nbsp;
					<if condition="$info['is_verify'] eq 0">
						<a href="javascript:void(0);" onclick="ajax_verify_ticket({pigcms{$info.order_id},'',this);" class="group_verify_btn">{pigcms{:L_("全部验证")}</a>
					<elseif condition="($info['order_status'] eq 2) OR ($now_order['order_status'] eq 3)"/>
						<font color="green">{pigcms{:L_("全部已消费")}</font>
					</if>
				</th>
			</tr>
			<volist name="ticket_list" id="vo">
			<tr>
				<th colspan="3" >{pigcms{$vo.code}</th>
				<td width="35%">
					<if condition="$vo.status eq 1" >
						<font color="red">{pigcms{:L_("未消费")}</font>&nbsp;&nbsp;
						<a href="javascript:void(0);" onclick="ajax_verify_ticket({pigcms{$info.order_id},'{pigcms{$vo.com_id}',this);" title="{pigcms{:L_("查看商品详情")}">{pigcms{:L_("验证消费")}</a>
					<elseif condition="$vo.status eq 2" />
						<font color="green">{pigcms{:L_("已消费")}</font>
					<elseif condition="$vo.status eq 3" />
						<font color="red">{pigcms{:L_("已退款")}</font>
					</if>
				</td>
			</tr>
			</volist>			
		</table>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript">
			var clock=0;
			function ajax_verify_ticket(order_id,com_id,val){
			  	if(clock==1){
			  		return false;
			  	}
			  	clock=1;
				$.post("{pigcms{:U('Store/ajax_verify_ticket')}",{order_id:order_id,com_id:com_id},function(result){
					result=$.parseJSON( result );
					if(result.errorCode==0){
						window.location.href = window.location.href;
					}else{
						clock=0;
					}					
				});
			}
		</script>
	</body>
</html>