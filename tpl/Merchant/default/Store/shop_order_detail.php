<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
        <script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
	<form id="myform" method="post" action="{pigcms{:U('Store/shop_edit')}" enctype="multipart/form-data">
		<input type="hidden" name="order_id" value="{pigcms{$order.order_id}"/>
        <input type="hidden" name="now_status" value="{pigcms{$order['status']}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<if condition="!in_array($order['status'], array(2,3,4,5)) AND $order['fetch_number']">
			<tr>
				<th colspan="1">{pigcms{:L_("取单编号")}</th>
				<th colspan="3">{pigcms{$order['fetch_number']}</th>
			</tr>
			</if>
			<tr>
				<th colspan="1">{pigcms{:L_("订单编号")}</th>
				<th colspan="2">{pigcms{$order['real_orderid']}</th>
				<th>
                    <if condition="$order['status'] eq 7 AND $order['paid'] eq 1" >
                        <a data-title="{pigcms{:L_("发货到自提点")}" class="green edit_btn js-send-order js-add-order-{pigcms{$order.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$order.order_id}">{pigcms{:L_("发货到自提点")}</a>
                    </if>
                    <button type="button" id="print">{pigcms{:L_("打印订单")}</button>
                </th>
			</tr>
			<if condition="$order.orderid neq 0">
			<tr>
				<th colspan="1">{pigcms{:L_("订单流水号")}</th>
				<th colspan="3"><if condition="$order['pay_type'] neq 'baidu'">shop_</if>{pigcms{$order['orderid']}</th>
			</tr>
			</if>
		</table>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th><strong>{pigcms{:L_("商品名称")}</strong></th>
				<th><strong>{pigcms{:L_("商品条形码")}</strong></th>
				<th><strong>{pigcms{:L_("原价")}</strong></th>
                <th><strong>{pigcms{:L_("限时价")}</strong></th>
                <if condition="$order['is_weight']"><th><strong>{pigcms{:L_("商品重量")}</strong></th></if>
                <if condition="$order['is_weight']"><th><strong>{pigcms{:L_("实际重量")}</strong></th></if>
                <th><strong>{pigcms{:L_("优惠类型")}</strong></th>
				<th><strong>{pigcms{:L_("优惠率")}</strong></th>
				<th><strong>{pigcms{:L_("优惠价")}</strong></th>
				<th><strong>{pigcms{:L_("数量")}</strong></th>
				<th><strong>{pigcms{:L_("规格属性详情")}</strong></th>

                <th><strong>{pigcms{:L_("附属商品")}</strong></th>
                <th><strong>{pigcms{:L_("价格")}</strong></th>
                <th><strong>{pigcms{:L_("数量")}</strong></th>
                <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>
			</tr>
			<volist name="order['info']" id="rowset">
			<if condition="!empty($rowset['name'])">
			<tr><td colspan="4">{pigcms{$rowset['name']}</td></tr>
			</if>
                <volist name="rowset['list']" id="vo">
                    <tr>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['name']}</th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['number']}</th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['old_price']|floatval}<if condition="$config.open_extra_price eq 1 AND $vo.extra_price gt 0">+{pigcms{$vo.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><?php if($vo['old_price'] > $vo['price']){ ?>{pigcms{$vo['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $vo.extra_price gt 0">+{pigcms{$vo.extra_price}{pigcms{$config.extra_price_alias_name}</if><?php }else{ echo '-'; ?></th><?php } ?>
                        <if condition="$order['is_weight']">
                            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><php>if ($vo['goods_weight'] > 0) {echo $vo['goods_weight'] * $vo['num'];}else{echo '-';}</php></th>
                        </if>
                        <if condition="$order['is_weight']">
                            <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                                <if condition="$vo.sell_type eq 2">
                                <input type="text" name="real_weight" class="real_weight" value="{pigcms{$vo.real_weight}" size="5" data-detail_id="{pigcms{$vo.id}"><button type="button" class="write_weight">{pigcms{:L_("录入重量")}</button>
                                <else />-
                                </if>
                            </th>
                        </if>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><php>if ($vo['discount_type'] == 1 || $vo['discount_type'] == 4) {echo L_("店铺折扣");} elseif ($vo['discount_type'] == 2 || $vo['discount_type'] == 5) {echo L_("分类折扣");} elseif ($vo['discount_type'] == 3) {echo L_("折扣");}if($vo['is_give'] == 1){echo '满赠';}</php></th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong><php>if ($vo['discount_rate']) echo $vo['discount_rate']/10;</php></strong></th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong><php>if ($vo['discount_price']) { echo floatval($vo['discount_price']); } else { echo floatval($vo['price']); }</php></strong></th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>
                        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}" <?php if($vo['info_count'] == 0) echo 'colspan="8"'; ?> >{pigcms{$vo['spec']}</th>
                    </tr>

                        <if condition="$vo.give_goods_name != ''|| $vo.give_coupon_name != ''">
                            <tr>
						<th colspan="12">赠品:<br>
							<if condition="$vo.give_goods_name != ''">
								&nbsp;&nbsp;
								商&nbsp;品:&nbsp;
								{pigcms{$vo.give_goods_name} &nbsp;￥{pigcms{$vo.give_goods_price}&nbsp;X1
							</if>
                            <if condition="$vo.give_coupon_name != ''">
							<br>
							&nbsp;&nbsp;优惠券:&nbsp;{pigcms{$vo.give_coupon_name} &nbsp;X1
							</if>
						</th>
                            </tr>
                        </if>

                    <volist name="vo['info']" id="info">
                        <tr>
                            <th style="color:#9F0050">{pigcms{$info['name']}</th>
                            <th>{pigcms{$info['price']}</th>
                            <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>
                            <th>{pigcms{$info['spec']}</th>
                        </tr>
                    </volist>
                </volist>
			</volist>
			<tr>
				<th colspan="<if condition="$order.is_weight eq 1">18<else/>16</if>">{pigcms{:L_("备注")}:<span style="color:red">{pigcms{$order['desc']|default="无"}</span></th>
			</tr>
            <php>if ($order['platform'] == 0) {</php>
			<tr >
				<th colspan='<if condition="$order.is_weight eq 1">8<else/>6</if>'><strong>{pigcms{:L_("总价")}</strong></th>
				<th>{pigcms{$order['goods_price']|floatval}</th>
				<th>{pigcms{$order['num']}</th>
				<th colspan="8"></th>
			</tr>
            <php>}</php>
		</table>
        <if condition="$finishRefunds">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
            <tr>
                <th colspan="8"><strong>{pigcms{:L_("已退商品")}</strong></th>
            </tr>
            <tr>
                <th><strong>{pigcms{:L_("商品名称")}</strong></th>
                <th><strong>{pigcms{:L_("退款单价")}</strong></th>
                <th><strong>{pigcms{:L_("退款数量")}</strong></th>
                <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>

                <th><strong>附属商品</strong></th>
                <th><strong>价格</strong></th>
                <th><strong>数量</strong></th>
                <th><strong>规格属性详情</strong></th>
            </tr>
            <volist name="finishRefunds" id="finish">
                <tr>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$finish['name']}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$finish['price']|floatval}</th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}"><strong>{pigcms{$finish['num']}</strong><php>if ($finish['unit']) {</php> ({pigcms{$finish['unit']})<php>}</php></th>
                    <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}" <?php if($finish['info_count'] == 0) echo 'colspan="5"'; ?>>{pigcms{$finish['spec']}</th>
                </tr>
                <volist name="finish['info']" id="info">
                    <tr>
                        <th style="color:#9F0050">{pigcms{$info['name']}</th>
                        <th>{pigcms{$info['price']}</th>
                        <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>
                        <th>{pigcms{$info['spec']}</th>
                    </tr>
                </volist>
            </volist>
        </table>
        </if>
        <volist name="refund_list" id="rowset">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
             <input type="hidden" name="refund_id" value="{pigcms{$rowset['id']}">
            <tr>
                <th colspan="4"><strong>{pigcms{:L_("退货信息")}</strong></th>
            </tr>
            <tr>
                <th colspan="4"><strong>{pigcms{:L_("退货商品")}</strong></th>
            </tr>
            <tr>
                <th><strong>{pigcms{:L_("商品名称")}</strong></th>
                <th><strong>{pigcms{:L_("单价")}</strong></th>
                <th><strong>{pigcms{:L_("数量")}</strong></th>
                <th><strong>{pigcms{:L_("规格属性详情")}</strong></th>
            </tr>
            <volist name="rowset['goodsList']" id="vo">
            <tr>
                <th style="color:#9F0050">{pigcms{$vo['name']}</th>
                <th style="color:#9F0050">{pigcms{$vo['price']|floatval}</th>
                <th style="color:#9F0050"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>
                <th style="color:#9F0050">{pigcms{$vo['spec']}</th>
            </tr>
            </volist>
            
            <tr>
                <th colspan="4">{pigcms{:L_("退款备注")}: <strong style="color:red">{pigcms{$rowset['reason']}</strong></th>
            </tr>
            
            <tr>
                <th colspan="4">{pigcms{:L_("退款金额")}: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']} {pigcms{:L_("不含配送费、打包费、优惠金额")}</strong></th>
            </tr>
            <tr>
                <th colspan="4">{pigcms{:L_("退款凭证")}:
                <ul class="imgs" style="display:inline">
                    <php>foreach ($rowset['image'] as $image) {</php>
						<if condition="$image">
							<li style="display:inline">
								<img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
							</li>
						</if>
                    <php>}</php>
                </ul>
                </th>
            </tr>
            
            <tr>
                <th colspan="4">{pigcms{:L_("退款状态")}: 
                    <volist name="rowset['logList']" id="vo">
                        <if condition="$vo['status'] eq 0"><span>{pigcms{:L_("正在审核中")} <php>if (!empty($vo['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$vo['note']}</strong><php>}</php></span>
                        <elseif condition="$vo['status'] eq 1"/><span>{pigcms{:L_("商家审核通过")} <php>if (!empty($vo['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$vo['note']}</strong><php>}</php></span>
                        <elseif condition="$vo['status'] eq 2"/><span>{pigcms{:L_("商家拒绝退货")} <php>if (!empty($vo['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$vo['note']}</strong><php>}</php></span>
                        <elseif condition="$vo['status'] eq 3"/><span>{pigcms{:L_("您重新申请退货")} <php>if (!empty($vo['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$vo['note']}</strong><php>}</php></span>
                        <elseif condition="$vo['status'] eq 4"/><span>{pigcms{:L_("您取消申请退货")} <php>if (!empty($vo['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$vo['note']}</strong><php>}</php></span>
                        </if>
                        {pigcms{$vo.dateline|date="Y-m-d H:i",###}                        
                    </volist>
                </th>
            </tr>
            <tr>
                <th colspan="4">{pigcms{:L_("退款操作")}:
                    <input type="text" name="reply_content" style="height: 24px;width:400px;" placeholder="{pigcms{:L_("请填写原因")}" />
                    <button type="button" class="refund" data-type="agree" data-refund_id="{pigcms{$rowset['id']}">{pigcms{:L_("同意")}</button>
                    <button type="button" class="refund" data-type="disagree" data-refund_id="{pigcms{$rowset['id']}">{pigcms{:L_("不同意")}</button> </th>
            </tr>
        </table>
        </volist>
        
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th><strong>{pigcms{:L_("支付状态")}</strong></th>
				<th style="color: green">{pigcms{$order['pay_status']}</th>
                <if condition="$order['platform'] eq 0">
				<th><strong>{pigcms{:L_("支付方式")}</strong></th>
                <th>{pigcms{$order['pay_type_str']}</th>
                <else />
				<th colspan="2"></th>
                </if>
			</tr>
			<tr>
				<th>{pigcms{:L_("线下需支付")}</th>
				<th style="color: red">{pigcms{$config['Currency_symbol']}{pigcms{$order['offline_price']|floatval}{pigcms{$config['Currency_txt']}</th>
				<th>{pigcms{:L_("发票信息")}</th>
				<th>{pigcms{$order.invoice_head}</th>
			</tr>
			<tr>
				<th><strong>{pigcms{:L_("订单状态")} {pigcms{$current_periodic['text']}</strong></th>
				<if condition="$order['status'] eq 11 && empty($refund_list)">
					<th colspan="3" style="color: red">
                        <!--已跟产品确认，注释掉了-->
					    <!--	用户申请退款-->
                        <if condition="empty($store['miaomaiyun_erp_storeid'])">
                        <button type="button" onclick="agree_cancle_order({pigcms{$order['order_id']});">同意退款</button>
                        </if>
					</th>
                <elseif condition="($order['is_pick_in_store'] eq 3 OR $order['is_pick_in_store'] eq 2) AND $order['order_status'] EQ '0'"/><!--自提和快递配送发货前需增加店【接单】操作-->
                    <th colspan="3">
                        {pigcms{$order['status_str']}
                        <if condition="empty($store['miaomaiyun_erp_storeid'])">
                        <if condition="in_array($order['status'],[0,7])">
                            <button type="button" onclick="take_order({pigcms{$order['order_id']});">接单</button>
                        </if>
                        </if>
                    </th>
                    
				<elseif condition="$order['is_pick_in_store'] eq 3"/>
					<th colspan="3">
						<select name="express_id"><volist name="express_list" id="vo"><option value="{pigcms{$vo.id}" <if condition="$order['express_id'] eq $vo['id']">selected</if>>{pigcms{$vo.name}</option></volist></select>
						<input type="text" name="express_number" value="{pigcms{$order['express_number']}" style=" height: 24px;"/>
                        <input type="hidden" name="periodic_deliver_id" value="{pigcms{$current_periodic['id']}"/>
                        <if condition="empty($store['miaomaiyun_erp_storeid'])">
                        <button type="submit">{pigcms{:L_($botton_name)}</button>
                        </if>
						<if condition="$config['open_staff_power'] && $staff_session['type'] neq 2"><span style="color: red;margin-left: 5px;">{pigcms{:L_("注：您没有权限修改订单状态")}</span></if>

                        <if condition="($order['status'] eq 4) AND ($order['refund_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;退款时间:{pigcms{$order['refund_time']|date="Y-m-d H:i:s",###}</if>
                        <if condition="($order['status'] eq 5) AND ($order['cancel_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;取消时间:{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</if>
					</th>
				<elseif condition="$order['status'] gt 1 AND $order['status'] lt 6" />
					<th colspan="3">
					{pigcms{$order['status_str']}                    
                    <if condition="($order['status'] eq 4) AND ($order['refund_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;退款时间:{pigcms{$order['refund_time']|date="Y-m-d H:i:s",###}</if>
                    <if condition="($order['status'] eq 5) AND ($order['cancel_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;取消时间:{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</if>
					</th>
				<else />
					<th>
						<select name="status" <if condition="$store['miaomaiyun_erp_storeid']">disabled</if>>
						 	<option value="0" <if condition="$order['status'] eq 0">selected</if>>{pigcms{:L_("未确认")}</option>
						 	<option value="1" <if condition="$order['status'] eq 1 OR $order['status'] eq 9">selected</if>>{pigcms{:L_("已确认")}</option>
						 	<option value="2" <if condition="$order['status'] eq 2">selected</if> <if condition="$sure">disabled</if>>{pigcms{:L_("已消费")}</option>
						 	<option value="3" <if condition="$order['status'] eq 3">selected</if> disabled>{pigcms{:L_("已评价")}</option>
						 	<option value="4" <if condition="$order['status'] eq 4 OR $order['status'] eq 5">selected</if>>{pigcms{:L_("已取消")}</option>
						</select>
                        <if condition="($order['status'] eq 4) AND ($order['refund_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;退款时间:{pigcms{$order['refund_time']|date="Y-m-d H:i:s",###}</if>
                        <if condition="($order['status'] eq 5) AND ($order['cancel_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;取消时间:{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</if>
					</th>
					<th style="color: red">{pigcms{:L_("注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，")}<br/>{pigcms{:L_("状态修改后就不能修改了")}</th>
					<input type="hidden" name="now_status" value="{pigcms{$order['status']}"/>
                    
                    <if condition="$order['status'] lt 2 || $order['status']==7" >
					   <th>
                           <if condition="empty($store['miaomaiyun_erp_storeid'])">
                           <button type="submit">{pigcms{:L_("提交")}</button>
                           </if>
                           <if condition="$config['open_staff_power'] && $staff_session['type'] neq 2"><span style="color: red;margin-left: 5px;">{pigcms{:L_("注：您没有权限修改订单状态")}</span></if>
                       </th>
                    </if>
				</if>
			</tr>
		</table>
        <notempty name="periodic_list">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
            <tr>
                <th colspan="5"><strong>{pigcms{:L_("周期购配送详情")}</strong></th>
            </tr>
            <volist name="periodic_list" id="po">
            <tr>    
                <th>{pigcms{$po.info}</th>
                <th>店员发货：{pigcms{$po.accept_time_text} {pigcms{$po.express_info}</th>
                <th>客户收货：{pigcms{$po.arrive_time_text}</th>
                <th>{pigcms{$po.status_text}</th>
                <th>
                    <notempty name="po.delay_class">
                    <button class="{pigcms{$po.delay_class}" type="button" data-id="{pigcms{$po.id}">{pigcms{$po.delay_text}</button>
                    </notempty>
                </th>    
            </tr>
            </volist>
        </table>
        </notempty>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th colspan="2"><strong>{pigcms{:L_("客户信息")}</strong></th>
			</tr>
			<tr>
                <if condition="$order['is_pick_in_store'] != 2">
                    <th>{pigcms{:L_("客户姓名：")}{pigcms{$order['username']}</th>
                </if>
                <if condition="$order['is_pick_in_store'] == 2">
                        <th>{pigcms{:L_("用户自提联系方式：")}
                <else/>
                        <th>{pigcms{:L_("客户手机：")}
                </if>
                <if condition="$order['virtual_phone_arr']"> 
                    {pigcms{$order['userphone']|phone_show}  
                    <?php if($order['virtual_phone_overtime'] >= time()){ ?>
                         / 隐私号码
                        {pigcms{$order['virtual_phone_arr'][0][0]}
                        <?php if($order['virtual_phone_arr'][0][1]){ ?>
                            {pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][0][1]}
                        <?php } ?>   
                    <?php } ?>
                <else/>
                    {pigcms{$order['userphone']|phone_demo_show}                    
                </if>
                </th>
			</tr>
			<if condition="$order['register_phone']">
			<tr>
				<th colspan="2" style="color:red">{pigcms{:L_("客户注册手机：")}
                <if condition="$order['virtual_phone_arr']">
                    {pigcms{$order['register_phone']|phone_show}  
                    <?php if($order['virtual_phone_overtime'] >= time()){ ?>
                         / 隐私号码
                        <if condition="count($order['virtual_phone_arr'])==1">
                        {pigcms{$order['virtual_phone_arr'][0][0]}
                        <?php if($order['virtual_phone_arr'][0][1]){ ?>
                            {pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][0][1]}
                        <?php } ?>  
                        <else/>
                        {pigcms{$order['virtual_phone_arr'][1][0]}
                        <?php if($order['virtual_phone_arr'][1][1]){ ?>
                            {pigcms{:L_('转')}{pigcms{$order['virtual_phone_arr'][1][1]}
                        <?php } ?>  
                        </if>    
                    <?php } ?>
                <else/>
                    {pigcms{$order['register_phone']|phone_demo_show}                    
                </if>
                </th>
			</tr>
			</if>
            <php>if ($order['order_from'] != 6) {</php>
			<if condition="$order['is_pick_in_store'] eq 2">
				<if condition="$order['pick_username']">
					<tr>
						<th colspan="2">{pigcms{:L_("自提人姓名：")}{pigcms{$order['pick_username']}</th>
					</tr>
				</if>
				<tr>
					<th colspan="2">{pigcms{:L_("自提地址：")}{pigcms{$order['address']}</th>
				</tr>
			<else />
				<tr>
					<th colspan="2">{pigcms{:L_("客户地址：")}{pigcms{$order['address']}
          <if condition="$order['change_address_status'] == 1">
            <span>
              (申请修改地址：姓名：{pigcms{$addressChangeRecord['change_user_name']} | 手机号：{pigcms{$addressChangeRecord['change_user_phone']} | 地址：{pigcms{$addressChangeRecord['change_address']} )
            </span>
            <span>
              <button type="button" class="check_change_address" data-status="4">同意</button>
              <button type="button" class="check_change_address" data-status="2">拒绝</button>
            </span>
          </if>
            <if condition="in_array($order['change_address_status'], [4,5])">
            <span style="text-decoration:line-through">
              (原地址：姓名：{pigcms{$addressChangeRecord['user_name']} | 手机号：{pigcms{$addressChangeRecord['user_phone']} | 地址：{pigcms{$addressChangeRecord['address']} )
            </span>
            </if>
          </th>
				</tr>
			</if>
            <php>}</php>
		</table>
        <php>if ($order['order_from'] != 6 && $order['platform'] == 0) {</php>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th colspan="2"><strong>{pigcms{:L_("配送信息")}</strong></th>
			</tr>
			<tr>
				<th>{pigcms{:L_("配送方式：")}{pigcms{$order['deliver_str']}</th>
				<th>{pigcms{:L_("配送状态：")}{pigcms{$order['deliver_status_str']}</th>
			</tr>
            <tr>
                <th>{pigcms{:L_("客户姓名：")}{pigcms{$order['username']}</th>
                <th>{pigcms{:L_("联系方式：")}{pigcms{$order['userphone']}</th>
            </tr>
			<if condition="$order['deliver_user_info']">
				<tr>
					<th>{pigcms{:L_("配送员姓名：")}{pigcms{$order['deliver_user_info']['name']}</th>
					<th>{pigcms{:L_("配送员电话：")}{pigcms{$order['deliver_user_info']['phone']}</th>
				</tr>
			</if>
		</table>
        <php>}</php>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th colspan="2"><strong>{pigcms{:L_("时间信息")}</strong></th>
			</tr>
			<tr>
				<th colspan="4">{pigcms{:L_("下单时间：")}{pigcms{$order['create_time']|date="Y-m-d H:i:s",###} </th>
			</tr>
			<if condition="$order['pay_time']">
				<tr>
					<th colspan="4">{pigcms{:L_("支付时间：")}{pigcms{$order['pay_time']|date="Y-m-d H:i:s",###} </th>
				</tr>
			</if>
            <if condition="$order['cashier_order_time']">
                <tr>
                    <th colspan="4">{pigcms{:L_("店员接单时间：")}{pigcms{$order['cashier_order_time']|date="Y-m-d H:i:s",###} </th>
                </tr>
            </if>
			<if condition="$order['expect_use_time']">
				<tr>
					<th colspan="4">
                        <php>if($order['is_pick_in_store'] == 2){</php>
                            {pigcms{:L_("自提取货时间：")}
                            <if condition="$order['is_right_now']">
                                <span style="color: red;">{pigcms{:L_('立即送达')} </span>{pigcms{$order['expect_use_time']|date="H:i",###}
                            <else/>
                                {pigcms{$order['expect_use_time']|date="Y-m-d H:i",###}
                            </if>
                             - {pigcms{$order['expect_use_time']+1200|date="H:i",###}
                        <php>}else{</php>
			<if condition="$order['is_pick_in_store']!=3">
                            {pigcms{:L_("期望送达时间：")}
                            <if condition="$order['is_right_now']">
                                <span style="color: red;">{pigcms{:L_('立即送达')} </span>{pigcms{$order['expect_use_time']|date="H:i",###}
                            <else/>
                                {pigcms{$order['expect_use_time']|date="Y-m-d H:i",###}
                            </if>
                            - {pigcms{$order['expect_use_time']+900|date="H:i",###}
			    </if>
                        <php>}</php>

                    </th>
				</tr>
			</if>
			<if condition="$order['cancel_time']">
                <tr>
                    <th colspan="4">{pigcms{:L_("取消时间：")}{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###} </th>
                </tr>
            </if>
		</table>
		        <notempty name="is_authentication">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
            <tr>
                <th colspan="2"><strong>{pigcms{:L_("实名认证")}</strong></th>
            </tr>
            <volist name="authentication_field" id="field">
            <tr>
                <if condition="$field['type'] eq 2">
                    <th colspan="4"> {pigcms{$field.title}:<img style="width:400px;" src="{pigcms{:file_domain().$vo['value']}" /></th>
                <else/>
                    <th colspan="4"> {pigcms{$field.title}:{pigcms{$field.value}</th>
                </if>
            </tr>
            </volist>
        </table>
        </notempty>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
			<tr>
				<th colspan="2"><strong>{pigcms{:L_("费用信息")}</strong></th>
			</tr>
            <if condition="$order['goods_price'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("商品总价")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price']|floatval} <if condition="$config.open_extra_price eq 1 AND $order.extra_price gt 0">+{pigcms{$order.extra_price|floatval}{pigcms{$config.extra_price_alias_name}</if></th>
			</tr>
            </if>
			<if condition="$order['packing_charge'] gt 0">
			<tr>
				<th colspan="4">{pigcms{$store['pack_alias']|default=L_("打包费")}{pigcms{$config['Currency_symbol']}{pigcms{$order['packing_charge']|floatval}</th>
			</tr>
			</if>
			<if condition="$order['freight_charge'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("配送费用")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['freight_charge']|floatval} </th>
			</tr>
			</if>
            <if condition="$order['other_money'] gt 0">
            <tr>
                <th colspan="4">{pigcms{:L_("加价送的金额")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['other_money']|floatval}</th>
            </tr>
            </if>
			<if condition="$order['is_pick_in_store'] eq 0 AND $order['no_bill_money'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("支付平台费用")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['no_bill_money']|floatval}</th>
			</tr>
			</if>
            <if condition="$order['qiye_pay'] gt 0">
                <tr>
                    <th colspan="4">
                        {pigcms{:L_("企业预存款抵扣")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['qiye_pay']|floatval}
                    </th>
                </tr>
            </if>
			<tr>
				<th colspan="4">{pigcms{:L_("订单总价")}：{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price'] + $order['packing_charge'] + $order['freight_charge'] + $order['other_money']|floatval} <if condition="$config.open_extra_price eq 1 AND $order.extra_price gt 0">+{pigcms{$order.extra_price|floatval}{pigcms{$config.extra_price_alias_name}</if></th>
			</tr>
    
            <php>if (!empty($order['discount_detail'])) {</php>
            <volist name="order['discount_detail']" id="detail">
            <tr>
				<th colspan="4">{pigcms{:L_("优惠信息")}：
					<php>if($detail['discount_type']==1){</php>{pigcms{:L_("平台首单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '({pigcms{:L_("平台补贴")}:<b style="color: red">' . floatval($detail['plat_money']) . '</b>{pigcms{:L_("商家补贴")}:<b style="color: red">' . floatval($detail['merchant_money']) .'</b>)';}</php>
					<php>}else if($detail['discount_type']==2){</php>{pigcms{:L_("平台满")}<b style="color: red">{pigcms{$config['Currency_symbol']}{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$config['Currency_symbol']}{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
					<php>}else if($detail['discount_type']==3){</php>{pigcms{:L_("商家首单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
					<php>}else if($detail['discount_type']==4){</php>{pigcms{:L_("商家满")}<b style="color: red">{pigcms{$config['Currency_symbol']}{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$config['Currency_symbol']}{pigcms{$detail['minus']|floatval}</b>
                    <php>}else if($detail['discount_type']==30){</php>{pigcms{:L_("平台配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
					<php>}else if($detail['discount_type']==5){</php>{pigcms{:L_("平台配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '('.L_("平台补贴").':<b style="color: red">' . floatval($detail['plat_money']) . '</b>'.L_("商家补贴").':<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
					<php>}else if($detail['discount_type']==6){</php>>{pigcms{:L_("商家配送订单满")}<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
					<php>}</php>
				</th>
            </tr>
            </volist>
            <php>} else {</php>
            <if condition="$order['merchant_reduce'] gt 0">
            <tr>
                <th colspan="4">{pigcms{:L_("店铺优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
            </tr>
            </if>
            <if condition="$order['balance_reduce'] gt 0">
            <tr>
                <th colspan="4">{pigcms{:L_("平台优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
            </tr>
            </if>
            <php>}</php>
			<if condition="$order['card_discount'] neq 0 AND $order['card_discount'] neq 10">
			<tr>
				<th colspan="4">{pigcms{:L_("会员卡：")}{pigcms{$order['card_discount']|floatval} {pigcms{:L_("折优惠(配送费不参加优惠")})</th>
			</tr>
			</if>
			<tr>
				<th colspan="4">{pigcms{:L_("实付金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|floatval}</th>
			</tr>
			<gt name="order.use_ecard_price" value='0'>
				<tr>
					<th colspan="7">
						{pigcms{$config.ecard_alias_name}支付：{pigcms{$config.Currency_symbol}{pigcms{$order.use_ecard_price|floatval}
					</th>
				</tr>
			</gt>
			<if condition="$order['score_used_count'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("使用")}{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
			</tr>
			<tr>
				<th colspan="4">{pigcms{$config.score_name}{pigcms{:L_("抵现：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval}</th>
			</tr>
			</if>
			
			<if condition="$order['card_give_money'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("会员卡余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_give_money']|floatval}</th>
			</tr>
			</if>
			
			<if condition="$order['merchant_balance'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("商家余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|floatval}</th>
			</tr>
			</if>
			<if condition="$order['balance_pay'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("平台余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval}</th>
			</tr>
			</if>
			<if condition="$order['payment_money'] gt 0">
			<tr>
				<th colspan="4">{pigcms{:L_("在线支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval}</th>
			</tr>
			</if>
            <notempty name="order['service_charge']">
            <tr>
                <th colspan="4">在线支付手续费：{pigcms{$config['Currency_symbol']}{pigcms{$order['service_charge']|floatval}</th>
            </tr>
            </notempty>
			<if condition="$order['card_id']">
			<tr>
				<th colspan="4">{pigcms{:L_("店铺优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_price']}</th>
			</tr>
			</if>
			<if condition="$order['coupon_id']">
			<tr>
				<th colspan="4">{pigcms{:L_("平台优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['coupon_price']}</th>
			</tr>
			</if>
		</table>
		<if condition="$order['cue_field']">
			<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
				<tr>
					<th colspan="2"><strong>{pigcms{:L_("分类填写字段")}</strong></th>
				</tr>
				<volist name="order['cue_field']" id="vo">
					<tr>
						<th>{pigcms{$vo.title}</th>
						<th>{pigcms{$vo.txt}</th>
					</tr>
				</volist>
			</table>
		</if>
	</form>
    <script type="text/javascript">
        function take_order(order_id){
            $.post("{pigcms{:U('Store/take_order')}",{id:order_id},function(result){
				result = eval('(' + result + ')');
				alert(result.info);
				location.reload();
			});
        }

		function agree_cancle_order(order_id){
			$.post("{pigcms{:U('Store/agree_cancle_order')}",{id:order_id},function(result){
				result = eval('(' + result + ')');
				alert(result.info);
				location.reload();
			});
		}

    $(function () {
      $('.delay_reply').click(function () {
        $(this).html('{pigcms{:L_("提交中")}...').prop('disabled', true);
        var id = $(this).attr('data-id');
        $.post("{pigcms{:U('Store/periodic_delay')}", {id: id, type: 'reply'}, function (result) {
          result = eval('(' + result + ')');
          alert(result.info);
          location.reload();
        });

      });
      $('.delay_cancel').click(function () {
        $(this).html('{pigcms{:L_("提交中")}...').prop('disabled', true);
        var id = $(this).attr('data-id');
        $.post("{pigcms{:U('Store/periodic_delay')}", {id: id, type: 'cancle'}, function (result) {
          result = eval('(' + result + ')');
          alert(result.info);
          location.reload();
        });

      });
      $('#merchant_remark_btn').click(function () {
        $(this).html('{pigcms{:L_("提交中")}...').prop('disabled', true);
        $.post("{pigcms{:U('Group/group_remark',array('order_id'=>$order['order_id']))}", {merchant_remark: $('#merchant_remark').val()}, function (result) {
          $('#merchant_remark_btn').html('{pigcms{:L_("修改")}').prop('disabled', false);
          alert(result.info);
        });
      });
      $('#store_id_btn').click(function () {
        $(this).html('{pigcms{:L_("提交中")}...').prop('disabled', true);
        $.post("{pigcms{:U('Group/order_store_id',array('order_id'=>$order['order_id']))}", {store_id: $('#order_store_id').val()}, function (result) {
          $('#store_id_btn').html('{pigcms{:L_("修改")}').prop('disabled', false);
          alert(result.info);
        });
      });
      $('#print').click(function () {
        var order_id = $(this).data('order_id'), obj = $(this);
        obj.text('{pigcms{:L_("打印中")}...');
        $.post("{pigcms{:U('Store/shop_order_print', array('order_id' => $order['order_id']))}", function (result) {
          alert(result.info);
          obj.text('{pigcms{:L_("打印订单")}');
        });
      });
      $('.refund').click(function () {
        var refund_id = $(this).data('refund_id'), type = $(this).data('type'),
            order_id = '{pigcms{$order["order_id"]}';
        var reply_content = $(this).parent('th').find('input[name="reply_content"]').val();
        $.post("{pigcms{:U('Store/replyRefund')}", {
          'order_id': order_id,
          'refund_id': refund_id,
          'reply_content': reply_content,
          'type': type
        }, function (response) {
          if (response.errcode == 1) {
            layer.msg(response.msg);
          } else {
            location.reload();
          }
        }, 'json');
      });

      $('.refund1').click(function () {
        var refund_id = $("input[name='refund_id']").val(), type = $(this).data('type'),
            order_id = '{pigcms{$order["order_id"]}';
        var reply_content = "";
        $.post("{pigcms{:U('Store/replyRefund')}", {
          'order_id': order_id,
          'refund_id': refund_id,
          'reply_content': reply_content,
          'type': type
        }, function (response) {
          if (response.errcode == 1) {
            layer.msg(response.msg);
          } else {
            location.reload();
          }
        }, 'json');
      });

      $('.refundImg').click(function () {
        var obj = $(this);
        layer.open({
          type: 1,
          title: false,
          closeBtn: 0,
          skin: 'layui-layer-nobg', //"没有背景色")}
          shadeClose: true,
          content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
        });
      });

      var open_staff_power = {pigcms{$config['open_staff_power'] | intval};
        var staff_type = {pigcms{$staff_session['type'] | intval};
          $('#myform').on('submit', function () {
        // "验证店员是否有权限")}
        if (open_staff_power == 1 && staff_type != 2) {
          layer.msg('{pigcms{:L_("您没有权限修改订单状态！")}');
          return false;
        }

      });
      $('.write_weight').click(function () {
        var detail_id = $(this).parent().find('.real_weight').data('detail_id');
        var real_weight = $(this).parent().find('.real_weight').val();
        if (real_weight <= 0) {
          layer.msg('{pigcms{:L_("请输入正确的商品实际重量！")}');
        }
        $.post("{pigcms{:U('Store/write_weight')}", {
          'detail_id': detail_id,
          'real_weight': real_weight
        }, function (response) {
          layer.msg(response.info);
        }, 'json');
      })

      $('.js-send-order').click(function () {
        var order_id = $(this).attr('js-order');
        $('.js-add-order-' + order_id).html('{pigcms{:L_("处理中")}');
        $.post("{pigcms{:U('Store/deliver_goods')}", {order_id: order_id, status: 1}, function (result) {
          if (result.status == 1) {
            $('.js-add-order-' + order_id).unbind('click');
            $('.js-add-order-' + order_id).html(result.info);
            $('.js-add-order').removeClass('js-add-order');
            top.location.reload()
          } else {
            layer.msg(result.info);
            $('.js-add-order-' + order_id).html('{pigcms{:L_("发货到自提点")}');
          }
        });
      });
      
      //审核地址修改
      $('.check_change_address').click(function () {
        $(this).html('{pigcms{:L_("提交中")}...').prop('disabled', true);
        $(this).siblings().remove()
        var that = $(this)
        var changeStatus = that.data("status")
        $.post("{pigcms{:U('Store/checkChangeAddress',array('order_id' => $order['order_id']))}", {
          "status": changeStatus,
          "type": "shop"
        }, function (result) {
          if (result.status) {
            layer.msg(result.info);
            return
          }
          that.html('{pigcms{:L_("已审核")}');
          layer.msg(result.info);
          location.replace(location.href)
        });
      });
    });
	</script>
	</body>
</html>