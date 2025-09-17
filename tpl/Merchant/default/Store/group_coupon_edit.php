<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<title>{pigcms{:L_("小猪")}{pigcms{$config.meal_alias_name}{pigcms{$config.group_alias_name}{pigcms{:L_("系统")} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
		<table>
			<tr>
				<th width="15%">{pigcms{:L_("券序列码")}</th>
				<td colspan="3" width="85%">{pigcms{$now_order.group_pass}
                    <if condition="$now_order.can_verify eq 0">
                        <div>{pigcms{$now_order.status_msg}</div>
                        <else/>
                        <button onclick="verify({pigcms{$now_order.order_id},'{pigcms{$now_order.group_pass}')">{pigcms{:L_("验证消费")}</button>
                    </if>
                
                </td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("过期时间")}</th>
				<td colspan="3" width="85%">{pigcms{$now_order.deadline|date='Y-m-d H:i:s',###}              
                </td>
			</tr>
            <tr>
                <th width="15%">{pigcms{:L_("数量")}</th>
                <td width="35%">1</td>
            </tr>
			<tr>
				<th width="15%">{pigcms{:L_("订单编号")}</th>
				<td colspan="3" width="85%">{pigcms{$now_order.real_orderid}</td>
			</tr>
			<if condition="$now_order.orderid neq 0">
				<tr>
					<th width="15%">{pigcms{:L_("订单流水号")}</th>
					<td colspan="3" width="85%">{pigcms{$now_order.orderid}</td>
				</tr>
			</if>
			<tr>
				<th width="15%">{pigcms{$config.group_alias_name}{pigcms{:L_("商品")}</th>
				<td colspan="3" width="85%"><a href="{pigcms{$now_order.url}" target="_blank" title="{pigcms{:L_('查看商品详情')}">{pigcms{$now_order.s_name}</a></td>
			</tr>
<!--			<tr>-->
<!--				<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("订单信息")}：</b></td>-->
<!--			</tr>-->
<!--			<tr>-->
<!--				<th width="15%">{pigcms{:L_("订单类型")}</th>-->
<!--				<td width="35%"><if condition="$now_order['is_group_combine'] eq '1'">{pigcms{:L_("优惠组合")}<elseif condition="$now_order['tuan_type'] eq '0'"/>{pigcms{$config.group_alias_name}券<elseif condition="$now_order['tuan_type'] eq '1'"/>{pigcms{:L_("代金券")}<else/>{pigcms{:L_("实物")}</if></td>-->
<!--				<th width="15%">{pigcms{:L_("订单状态")}</th>-->
<!--                <td width="35%">-->
<!--                    <if condition="$now_order['status'] eq 7">-->
<!--                        <font color="red">{pigcms{:L_("退款失败，原因：")}{pigcms{$now_order['refund_detail']}</font>-->
<!--                    <elseif condition="$now_order['status'] eq 6"/>-->
<!--                        <font color="red">{pigcms{:L_("已退款")}</font>-->
<!--                    <elseif condition="$now_order['status'] eq 3"/>-->
<!--                        <font color="red">已退款</font>-->
<!--                        <php>if ($now_order['refund_detail']['refund_time']){</php>-->
<!--                            <font color="red"> 退款时间：{pigcms{$now_order['refund_detail']['refund_time']|date='Y-m-d H:i:s',###}</font>-->
<!--                        <php>}</php>-->
<!--                    <elseif condition="$now_order['status'] eq 9"/>-->
<!--                        <font color="red">{pigcms{:L_("已过期")}</font>-->
<!--                    <elseif condition="$now_order['paid'] eq '1'"/>-->
<!--                        <php>if($now_order['third_id']==0 && $now_order['pay_type']=='offline' && $now_order['status']== 0){</php>-->
<!--                            <font color="red">{pigcms{:L_("线下未付款")}</font>-->
<!--                        <php>} else if($now_order['status']==0){</php>-->
<!--                            <font color="green">{pigcms{:L_("已付款")}</font>-->
<!--                            <php>if($now_order['tuan_type'] != 2){</php>-->
<!--                                <font color="red">{pigcms{:L_("未消费")}</font>-->
<!--                            <php>}else{</php>-->
<!--                                <php>if($now_order['is_pick_in_store']){</php>-->
<!--                                    <font color="red">{pigcms{:L_("未取货")}</font>-->
<!--                                <php>}else{</php>-->
<!--                                    <php>if($now_order['express_id'] != ''){</php>-->
<!--                                        <font color="red">{pigcms{:L_("已发货")}</font>-->
<!--                                    <php>}else{</php>-->
<!--                                        <font color="red">{pigcms{:L_("未发货")}</font>-->
<!--                                    <php>}</php>-->
<!--                                <php>}</php>-->
<!--                            <php>}</php>-->
<!--                        <php>} else if($now_order['status']==1){</php>-->
<!--                            <php>if($now_order['tuan_type'] != 2){</php>-->
<!--                                <font color="green">{pigcms{:L_("已消费")}</font>-->
<!--                            <php>}else{</php>-->
<!--                                <php>if($now_order['is_pick_in_store']){</php>-->
<!--                                    <font color="green">{pigcms{:L_("已取货")}</font>-->
<!--                                <php>}else{</php>-->
<!--                                    <font color="green">{pigcms{:L_("已发货")}</font>-->
<!--                                <php>}</php>-->
<!--                            <php>}</php>-->
<!--                            <font color="red">{pigcms{:L_("待评价")}</font>-->
<!--                        <php>} else {</php>-->
<!--                            <font color="green">{pigcms{:L_("已完成")}</font>-->
<!--                        <php>}</php>-->
<!--                    <else/>-->
<!--                        <font color="red">{pigcms{:L_("未付款")}</font>-->
<!--                    </if>-->
<!--                </td>-->
<!--			</tr>-->
<!--			<tr>-->
<!--				<th width="15%">{pigcms{:L_("数量")}</th>-->
<!--				<td width="35%">{pigcms{$now_order.num}</td>-->
<!--				<th width="15%">{pigcms{:L_("总价")}</th>-->
<!--				<td width="35%">{pigcms{$config['Currency_symbol']} {pigcms{$now_order.total_money}</td>-->
<!--			</tr>-->
			<if condition="$now_order.express_fee gt 0">
			<tr>
				<th width="15%">{pigcms{:L_("运费")}</th>
				<td width="35%">{pigcms{$now_order.express_fee}</td>
				<th width="15%"></th>
				<td width="35%"></td>
			</tr>
			</if>
			<tr>
				<th width="15%">{pigcms{:L_("下单时间")}</th>
				<td width="35%">{pigcms{$now_order.add_time|date='Y-m-d H:i:s',###}</td>
				<if condition="$now_order['paid']">
					<th width="15%">{pigcms{:L_("付款时间")}</th>
					<td width="35%"><if condition="$now_order.pay_type neq 'offline'">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</if></td>
				<else/>
					<th width="15%"></th>
					<td width="35%"></td>
				</if>
				<if condition="$now_order.pay_type eq 'offline' AND empty($now_order['third_id']) AND $now_order.status neq 3">			
					<tr>
						<th width="15%">{pigcms{:L_("线下还需支付")}</th>
						<td width="85%" colspan="3"><font color="red">{pigcms{$config['Currency_symbol']} {pigcms{$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price']} {pigcms{$config['Currency_txt']}</font></td>
					</tr>
				</if>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("买家留言")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.delivery_comment}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("支付方式")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.paytypestr}</td>
			</tr>
			<if condition="!empty($now_order['verify_time'])">		
				<tr>
					<th width="15%">{pigcms{:L_("消费时间")}</th>
					<td width="35%">{pigcms{$now_order.verify_time|date='Y-m-d H:i:s',###}</td>
					<th width="15%">{pigcms{:L_("操作店员：")}</th>
					<td width="35%">{pigcms{$now_order.staff_name}</td>
				</tr>
			</if>
		</table>
		<script type="text/javascript">
				<if condition="$now_order['paid'] eq 1 && $now_order['status'] eq 0">var fahuo=1;<else/>var fahuo=0;</if>
		</script>
		<script type="text/javascript">
            function verify(order_id,group_pass){
                $(this).html('{pigcms{:L_("验证中")}...').prop('disabled',true);
                layer.open({
                    content:'{pigcms{:L_("是否确认验证")}?',
                    btn: ['{pigcms{:L_("是','否")}'],
                    yes:function(layer_index){
                        $.post('{pigcms{:U("group_array_verify")}', {'order_id':order_id,'group_pass':group_pass},function(result){
                            if(result.status == 1){
                                var msg = '{pigcms{:L_("验证成功！")}';
                                layer.msg(msg, {icon: 1});
                                layer.close(layer_index);
                                setTimeout(function () {
                                    window.location.reload();
                                    // location.href="{pigcms{:U('group_coupon')}";
                                }, 200)
                            }else{
                                layer.msg(result.msg, {icon: 2});
                            }
                        });
                        
                        
                        // var del_url = "{pigcms{:U('ajax_shop_order_del')}";
                        // $.get(del_url,{'order_id':order_id},function(data){
                        //     if(data['status']){
                        //         $('.dom_'+order_id).remove();
                        //         layer.close(layer_index);
                        //         layer.open({
                        //             content:'删除成功',
                        //             btn: ['好的']
                        //         });
                        //     }
                        // },'json');
                    }
                });
                
                
                
                // layer.confirm('{pigcms{:L_("是否确认验证")}?', {
                //     btn: ['{pigcms{:L_("是','否")}'] //"按钮")}
                // }, function(index){
                //     $.post('{pigcms{:U("group_array_verify")}', {'order_id':order_id,'group_pass':group_pass},function(result){
                //         if(result.status == 1){
                //             var msg = '{pigcms{:L_("验证成功！")}';
                //             layer.msg(msg, {icon: 1});
                //             setTimeout(function () {
                //                 layer.close(index);
                //                 // layer.closeAll();
                //                 // window.location.reload();
                //                 // $('.layui-layer-close').click();
                //             }, 200)
                //         }else{
                //             layer.msg(result.msg, {icon: 2});
                //         }
                //     });
                // }, function(){
                //     layer.msg('{pigcms{:L_("验证失败")}', {icon: 2});
                // });
            }
		</script>
	</body>
</html>