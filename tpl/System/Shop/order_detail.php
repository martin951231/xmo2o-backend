<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">订单编号</th>
		<th colspan="14">{pigcms{$order['real_orderid']}</th>
	</tr>
	<if condition="$order['tw_order_no']">
	<tr>
		<th colspan="1">{pigcms{:L_("图维订单编号")}</th>
		<th colspan="13">{pigcms{$order['tw_order_no']}</th>
	</tr>
	</if>
	
	<if condition="$order.orderid neq 0">
	<tr>
		<th colspan="1">流水号</th>
		<th colspan="14"><if condition="$order['pay_type'] neq 'baidu'">shop_</if>{pigcms{$order['orderid']}</th>
	</tr>
	</if>
	<if condition="$order['shop_pass']">
	<tr>
		<th colspan="1">消费码</th>
		<th colspan="14">{pigcms{$order['shop_pass']}</th>
	</tr>
	</if>
	<tr>
		<th width="90"><strong>商品名称</strong></th>
		<th><strong>商品条形码</strong></th>
		<th><strong>单价</strong></th>
        <th><strong>优惠类型</strong></th>
        <th><strong>优惠率</strong></th>
        <th><strong>优惠价</strong></th>
        <th><strong>数量</strong></th>
        <th><strong>实际重量</strong></th>
		<th><strong>总价</strong></th>
		<th><strong>规格属性详情</strong></th>

		<th><strong>附属商品</strong></th>
		<th><strong>价格</strong></th>
		<!--<th><strong>数量</strong></th>-->
		<th><strong>规格属性详情</strong></th>
	</tr>
	<volist name="order['info']" id="rowset">
		<if condition="!empty($rowset['name'])">
			<tr><td colspan="15">{pigcms{$rowset['name']}</td></tr>
		</if>
		<volist name="rowset['list']" id="vo">
			<tr>
				<th width="90" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['name']}</th>
				<th width="90">{pigcms{$vo['number']}</th>
				<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    {pigcms{$vo['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $vo.extra_price gt 0">+{pigcms{$vo.extra_price}{pigcms{$config.extra_price_alias_name}</if>
                </th>
				<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    <php>if ($vo['discount_type'] == 1 || $vo['discount_type'] == 4) {echo '{pigcms{:L_("店铺折扣")}';} elseif ($vo['discount_type'] == 2 || $vo['discount_type'] == 5) {echo '{pigcms{:L_("分类折扣")}';} elseif ($vo['discount_type'] == 3) {echo 'VIP{pigcms{:L_("折扣")}';} if($vo['is_give'] == 1){echo '满赠';}</php>
                </th>
                <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    <strong><php>if ($vo['discount_rate']) echo $vo['discount_rate']/10;</php></strong>
                </th>
                <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    <strong><php>if ($vo['discount_price']) { echo floatval($vo['discount_price']); } else { echo floatval($vo['price']); }</php></strong>
                </th>
                <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    <strong>{pigcms{$vo['num']}</strong> ({pigcms{$vo['unit']})
                </th>
        <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
          <php>if ($vo['real_weight']) { echo floatval($vo['real_weight']).'g'; }</php>
        </th>
				<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">
                    <strong><php>if ($vo['discount_price']) { echo floatval($vo['discount_price']*$vo['num']); } else { echo floatval($vo['price']*$vo['num']); }</php></strong>
                </th>
                <th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}" <?php if($vo['info_count'] == 0) echo 'colspan="8"'; ?> >
                    {pigcms{$vo['spec']}
                </th>
            </tr>
			<if condition="$vo.give_goods_name != '' || $vo.give_coupon_name != ''">
			<tr>
				<th colspan="12">赠品:<br> &nbsp;&nbsp;商&nbsp;品:&nbsp;{pigcms{$vo.give_goods_name} &nbsp;{pigcms{$config.Currency_symbol}{pigcms{$vo.give_goods_price}&nbsp;X1 <br>&nbsp;&nbsp;优惠券:&nbsp;{pigcms{$vo.give_coupon_name} &nbsp;X1</th>

			</tr>
			</if>
            <volist name="vo['info']" id="info">
                <tr>
                    <th style="color:#9F0050">{pigcms{$info['name']}×{pigcms{$info['num']}</th>
                    <th>{pigcms{$info['price']}</th>
                    <!--<th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>-->
                    <th>{pigcms{$info['spec']}</th>
                </tr>

            </volist>
		</volist>
	</volist>
	<if condition="($order.status eq 0 OR $order.status eq 5) AND $order.paid eq 1">
		<tr>
			<th colspan="15"><a href="javascript:void(0)" onclick="refund_confirm();"><font color="blue">手动退款</font></a></th>
		</tr>
	</if>

    <if condition="$order['circle_info']">
        <tr>
            <th colspan="15">店铺所处的区域商圈信息：{pigcms{$order['circle_info']}</th>
        </tr>
    </if>
    <if condition="$order['uid']">
        <tr>
            <th colspan="15">用户ID：{pigcms{$order['uid']}</th>
        </tr>
    </if>
    <if condition="$order['is_pick_in_store'] != 2">
        <if condition="$order['username']">
            <tr>
                <th colspan="15">客户姓名：{pigcms{$order['username']}</th>
            </tr>
        </if>
    </if>
    <if condition="$order['is_pick_in_store'] == 2">
		<tr>
            <th colspan="15">自提客户：{pigcms{$order['pick_username']}</th>
        </tr>
            <if condition="$order['userphone']">
                <tr>
                    <th colspan="15">用户自提联系方式：{pigcms{$order['userphone']|phone_demo_show}</th>
                </tr>
            </if>
        <else/>
            <if condition="$order['userphone']">
                <tr>
                    <th colspan="15">客户手机：{pigcms{$order['userphone']|phone_demo_show}</th>
                </tr>
            </if>
    </if>
    <if condition="$order['is_open_check'] eq 1">
        <tr>
            <th colspan="15">真实姓名：{pigcms{$check_id_card_info['user_truename']}</th>
        </tr>
        <tr>
            <th colspan="15">身份证编号：{pigcms{$check_id_card_info['user_id_number']}</th>
        </tr>
        <if condition="$order['img_check'] eq 1">
            <tr>
                <th colspan="15">身份证正面：<img src="{pigcms{$check_id_card_info['authentication_img']}"></th>
            </tr>
            <tr>
                <th colspan="15">身份证正面：<img src="{pigcms{$check_id_card_info['authentication_img']}"></th>
            </tr>
        </if>
    </if>
    <tr>
		<th colspan="15" style="color:red">下单用户ID：{pigcms{$order['uid']}</th>
	</tr>
	<if condition="$order['register_phone']">
	<tr>
		<th colspan="15" style="color:red">客户注册手机：{pigcms{$order['register_phone']|phone_demo_show}</th>
	</tr>
	</if>
    <php>if ($order['order_from'] != 6) {</php>
	<if condition="$order['is_pick_in_store'] eq 2">
	<tr>
		<th colspan="15">自提地址：{pigcms{$order['address']}</th>
	</tr>
	<else />
	<tr>
		<th colspan="15">客户地址：{pigcms{$order['address']}</th>
	</tr>
	</if>
	<tr>
		<th colspan="15">配送方式：{pigcms{$order['deliver_str']}</th>
	</tr>
	<tr>
		<th colspan="15">配送状态：<if condition="$order['order_status'] eq 2"><if condition="$supplys['is_fetch_order']">配送员已接单<else/>等待配送员接单</if><else/>{pigcms{$order['deliver_status_str']}</if></th>
	</tr>
	<if condition="$order['is_pick_in_store'] eq 3 AND $order['express_id']">
	<tr>
		<th colspan="15">快递公司：{pigcms{$order['express_name']}</th>
	</tr>
	<tr>
		<th colspan="15">快递单号：{pigcms{$order['express_number']}</th>
	</tr>
	</if>
	<if condition="$order['deliver_user_info']">
	<tr>
		<th colspan="15">配送员姓名：{pigcms{$order['deliver_user_info']['name']}</th>
	</tr>
	<tr>
		<th colspan="15">配送员电话：{pigcms{$order['deliver_user_info']['phone']|phone_demo_show}</th>
	</tr>
	</if>
    <php>} else {</php>
    <tr>
        <th colspan="15">订单来源：线下零售 </th>
    </tr>
    <php>}</php>
	<tr>
		<th colspan="15">下单时间：{pigcms{$order['create_time']|date="Y-m-d H:i:s",###} </th>
	</tr>
	<if condition="$order['pay_time']">
	<tr>
		<th colspan="15">支付时间：{pigcms{$order['pay_time']|date="Y-m-d H:i:s",###} </th>
	</tr>
	</if>
	<if condition="$order['expect_use_time']">
	<tr>
		<th colspan="15">
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
	<!-- 商城订单 {pigcms{$order.order_from}-->
	<eq name="order.order_from" value='1'>
		<notempty name="order.post_time">
			<tr>
				<th colspan="15">
					发货时间：{pigcms{$order.post_time|date='Y-m-d H:i:s',###}
				</th>
			</tr>
		</notempty>
		<if condition="$order['use_time'] && $order['status'] gt 1">
			<tr>
				<th colspan="15">确认收货时间：{pigcms{$order['use_time']|date="Y-m-d H:i:s",###}</th>
			</tr>
		</if>
	<else/>
		<if condition="$order['use_time']">
			<tr>
				<th colspan="15">送达时间：{pigcms{$order['use_time']|date="Y-m-d H:i:s",###}</th>
			</tr>
		</if>
	</eq>
    <if condition="$supplys['confirm_time']">
        <tr>
            <th colspan="15">店员接单时长：{pigcms{$supplys['confirm_time']}</th>
        </tr>
    </if>
    <if condition="$supplys['order_time']">
        <tr>
            <th colspan="15">店员接单时间：{pigcms{$supplys['create_time']|date="Y-m-d H:i:s",###}</th>
        </tr>
    </if>
    <if condition="$supplys['grab_time']">
        <tr>
            <th colspan="15">配送员接单时长：{pigcms{$supplys['grab_time']}</th>
        </tr>
    </if>
    <if condition="$supplys['start_time']">
        <tr>
            <th colspan="15">配送员接单时间：{pigcms{$supplys['start_time']|date="Y-m-d H:i:s",###}</th>
        </tr>
    </if>
    <if condition="$supplys['deliver_use_time']">
        <tr>
            <th colspan="15">配送时长：{pigcms{$supplys['deliver_use_time']}</th>
        </tr>
    </if>
<!--    <if condition="$supplys['end_time']">-->
<!--        <tr>-->
<!--            <th colspan="15">配送结束时间：{pigcms{$supplys['end_time']|date="Y-m-d H:i:s",###}</th>-->
<!--        </tr>-->
<!--    </if>-->
    <!-- deliver_time_info -->
    <if condition="$delivery_times">
        <volist name="delivery_times" id = "time">
            <tr>
                <th colspan="15">
                    <if condition="$time['status'] eq 3">配送员接单时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
                        <elseif condition="$time['status'] eq 4" />配送员取货时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
                        <elseif condition="$time['status'] eq 5" />配送员开始配送时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
                        <elseif condition="$time['status'] eq 6" />配送员送达时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
                    </if>
                </th>
            </tr>
        </volist>
    </if>
    <!-- end -->
	<tr>
		<th colspan="15">商品总价：{pigcms{$config['Currency_symbol']}{pigcms{$order['goods_price']|floatval}<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<if condition="$order['goods_price'] neq $order['discount_price']">折扣后：{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price']|floatval}</if></th>
	</tr>
	<if condition="$order['packing_charge'] gt 0">
	<tr>
		<th colspan="15">{pigcms{$store['pack_alias']|default='打包费'}：{pigcms{$config['Currency_symbol']}{pigcms{$order['packing_charge']|floatval}</th>
	</tr>
	</if>
	<if condition="$order['freight_charge'] gt 0">
	<tr>
		<th colspan="15">{pigcms{$store['freight_alias']|default='配送费用'}：{pigcms{$config['Currency_symbol']}{pigcms{$order['freight_charge']|floatval}<if condition="$order['freight_charge'] lt $order['freight_charge_original']">&nbsp;（优惠前：{pigcms{$config['Currency_symbol']}{pigcms{$order.freight_charge_original}）</if>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<if condition="$order['distance'] gt 0">（配送距离：<font color="red">{pigcms{$order['distance']} 米</font>）【若设置为骑行距离、则是由地图接口提供，会根据当时路况等变化】</if></th>
	</tr>
	<tr>
		<th colspan="15">配送员佣金：{pigcms{$config['Currency_symbol']}{pigcms{$supplys['deliver_user_fee']|floatval}【根据配送员平台抽成比例计算得出】</th>
	</tr>
	</if>
	<if condition="$order['other_money'] gt 0">
	<tr>
		<th colspan="15">加价送的金额：{pigcms{$config['Currency_symbol']}{pigcms{$order['other_money']|floatval}</th>
	</tr>
	</if>
	<tr>
		<th colspan="15">订单总价：{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']|floatval}<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
	</tr>

    <php>if (!empty($order['discount_detail'])) {</php>
    <volist name="order['discount_detail']" id="detail" key="k">
    <tr>
        <th colspan="15">优惠信息：
        <if condition="$detail['discount_type'] eq 1">平台首单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) .'</b>)';}</php>
        <elseif condition="$detail['discount_type'] eq 2" />平台满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <elseif condition="$detail['discount_type'] eq 3" />商家首单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
        <elseif condition="$detail['discount_type'] eq 4" />商家满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
        <elseif condition="$detail['discount_type'] eq 5 && $now['order_from']!=1" />平台配送订单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <elseif condition="$detail['discount_type'] eq 30" />平台配送订单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
        <elseif condition="$detail['discount_type'] eq 6 && $now['order_from']!=1" />
			<if condition="$key eq 'plat_discount'">
			平台优惠满
			<else/>
			商家配送订单满
			</if>
			<b style="color: red">{pigcms{$detail['money']|floatval}</b>
			<if condition="$detail['percentage_status'] eq 1">
			享<b style="color: red">{pigcms{$detail['minus']*10}</b>折
			<else/>
			减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
			</if>
        </if>
        </th>
    </tr>
    </volist>
    <php>} else {</php>
	<if condition="$order['merchant_reduce'] gt 0">
	<tr>
		<th colspan="15">店铺优惠：{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_reduce']|getFormatNumber}</th>
	</tr>
	</if>
	<if condition="$order['balance_reduce'] gt 0">
	<tr>
		<th colspan="15">平台优惠：{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_reduce']|getFormatNumber}</th>
	</tr>
	</if>
    <php>}</php>
	<if condition="$order['card_discount'] neq 0 AND $order['card_discount'] neq 10">
	<tr>
		<th colspan="15">会员卡：{pigcms{$order['card_discount']|getFormatNumber} 折优惠</th>
	</tr>
	</if>
	<tr>
		<th colspan="15">应付总价：{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|getFormatNumber}</th>
	</tr>
	<if condition="$order['score_used_count']">
	<tr>
		<th colspan="15">使用{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
	</tr>
	<tr>
		<th colspan="15">{pigcms{$config.score_name}抵现：{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|getFormatNumber}</th>
	</tr>
	</if>
	<gt name="order.use_ecard_price" value='0'>
		<tr>
			<th colspan="15">{pigcms{$config.ecard_alias_name}支付：{pigcms{$config.Currency_symbol}{pigcms{$order.use_ecard_price|getFormatNumber}</th>
		</tr>
	</gt>

	<if condition="$order['card_give_money'] gt 0">
	<tr>
		<th colspan="15">会员卡余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['card_give_money']|getFormatNumber}</th>
	</tr>
	</if>

	<if condition="$order['merchant_balance'] gt 0">
	<tr>
		<th colspan="15">商家余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|getFormatNumber}</th>
	</tr>
	</if>
	<if condition="$order['balance_pay'] gt 0">
	<tr>
		<th colspan="15">平台余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|getFormatNumber}</th>
	</tr>
	</if>
    <if condition="$order['qiye_pay'] gt 0">
        <tr>
            <th colspan="15">企业预存款余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['qiye_pay']|getFormatNumber}</th>
        </tr>
    </if>
	<if condition="$order['is_open_membership_card'] gt 0">
		<tr>
            <th colspan="15">开通{pigcms{$order['membership_card_name']}：{pigcms{$config['Currency_symbol']}{pigcms{$order['membership_card_price']|getFormatNumber}</th>
        </tr>
	</if>
	<if condition="$order['payment_money'] gt 0">
	<tr>
		<th colspan="15">在线支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|getFormatNumber}</th>
	</tr>
	</if>
	<notempty name="order['service_charge']">
	<tr>
		<th colspan="15">在线支付手续费：{pigcms{$config['Currency_symbol']}{pigcms{$order['service_charge']|getFormatNumber}</th>
	</tr>
	</notempty>
	<if condition="$order['card_id']">
	<tr>
		<th colspan="15">店铺优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$order['card_price']}</th>
	</tr>
	</if>
	<if condition="$order['coupon_id']">
	<tr>
		<th colspan="15">平台优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$order['coupon_price']} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;【优惠券ID：{pigcms{$order.system_coupon_info.coupon_id}；优惠券名称：{pigcms{$order.system_coupon_info.name}】</th>
	</tr>
	</if>
	<if condition="$order['pay_type'] eq 'offline' AND empty($order['third_id'])">
	<tr>
		<th colspan="15">线下需支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-$order['coupon_price']|getFormatNumber}</th>
	</tr>
	</if>
	<tr>
		<th colspan="15">支付状态：{pigcms{$order['pay_status']}</th>
	</tr>
	<tr>
		<th colspan="15">支付方式：{pigcms{$order['pay_type_str']}</th>
	</tr>
    <tr>
        <th colspan="15">订单状态：{pigcms{$order['status_str']}
		<if condition="($order['status'] eq 4) AND ($order['refund_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;退款时间:{pigcms{$order['refund_time']|date="Y-m-d H:i:s",###}</if>
		<if condition="($order['status'] eq 5) AND ($order['cancel_time'] gt 0)">&nbsp;&nbsp;&nbsp;&nbsp;取消时间:{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</if></th>
    </tr>
	
	<if condition="in_array($order['status'],[4,5]) and $order['refund_detail']">
		<th colspan="15">退款响应日志：
			<php>
				echo $order['refund_detail']['msg']??'成功';
			</php>
		</th>
	</if>


        <volist name="authentication_field" id="field">
    <tr>    
        <if condition="$field['type'] eq 2">
            <th colspan="15"> 【实名认证】 {pigcms{$field.title}:<img style="width:400px;" src="{pigcms{:file_domain().$vo['value']}" /></th>
        <else/>
            <th colspan="15">【实名认证】 {pigcms{$field.title}：{pigcms{$field.value}</th>
        </if>
    </tr>
    </volist>
	<tr>
		<th colspan="1">备注:</th>
		<th colspan="13"><input type="text" class="input fl" name="note" value="{pigcms{$order.note}" size="60" placeholder="" validate="">
			&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_note();"><font color="blue">提交</font></a></th>
	</tr>

    <?php if(!empty($refund_list)){ ?>
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
            <tr>
                <th colspan="8"><strong>退货信息</strong></th>
            </tr>
            <volist name="refund_list" id="rowset">
				<tr>
					<th colspan="8"><strong>退货商品</strong></th>
				</tr>
				<tr>
					<th><strong>商品名称</strong></th>
					<th><strong>退款单价</strong></th>
					<th><strong>数量</strong></th>
					<th><strong>规格属性详情</strong></th>

                    <th><strong>附属商品</strong></th>
                    <th><strong>价格</strong></th>
                    <th><strong>数量</strong></th>
                    <th><strong>规格属性详情</strong></th>
				</tr>
				<volist name="rowset['goodsList']" id="vo">
					<tr>
						<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['name']}</th>
						<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}">{pigcms{$vo['price']|getFormatNumber}</th>
						<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}"><strong>{pigcms{$vo['num']}</strong><php>if ($vo['unit']) {</php> ({pigcms{$vo['unit']})<php>}</php></th>
						<th style="color:#9F0050" rowspan="{pigcms{$vo['info_count']+1}" <?php if($vo['info_count'] == 0) echo 'colspan="5"'; ?> >{pigcms{$vo['spec']}</th>
					</tr>
                    <volist name="vo['info']" id="info">
                        <tr>
                            <th style="color:#9F0050">{pigcms{$info['name']}</th>
                            <th>{pigcms{$info['price']}</th>
                            <th><strong>{pigcms{$info['num']}</strong> ({pigcms{$info['unit']})</th>
                            <th>{pigcms{$info['spec']}</th>
                        </tr>
                    </volist>
				</volist>

				<tr>
					<th colspan="8">退款备注: <strong style="color:red">{pigcms{$rowset['reason']}</strong></th>
				</tr>

				<tr>
					<th colspan="8">退款金额: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']} 不含配送费、打包费、优惠金额</strong></th>
				</tr>
				<tr>
					<th colspan="8">退款凭证:
						<ul class="imgs" style="display:inline">
							<php>foreach ($rowset['image'] as $image) {</php>
							<li style="display:inline">
								<img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
							</li>
							<php>}</php>
						</ul>
					</th>
				</tr>

				<tr>
					<th colspan="8">退款状态:
						{pigcms{$rowset.showStatus}
						<if condition="$rowset.reply_content neq ''">
							&nbsp;&nbsp;&nbsp;&nbsp;理由：<span style="color: red;font-weight: bold;">{pigcms{$rowset.reply_content}</span>
						</if>
						<if condition="in_array($rowset['status'],array(0,3,4))">
						&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$rowset.applytime|date="Y-m-d H:i",###}
						<elseif condition="in_array($rowset['status'],array(1,2))"/>
						&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$rowset.reply_time|date="Y-m-d H:i",###}
						</if>
					</th>
				</tr>
				<?php if($rowset['status']==0){ ?>
					<tr>
						<th colspan="8">退款操作: <input type="text" name="reply_content" style="height: 24px;width:400px;" placeholder="请填写原因" />&nbsp;<button type="button" class="refund" data-type="agree" data-refund_id="{pigcms{$rowset['id']}">同意</button>&nbsp;&nbsp;<button type="button" class="refund" data-type="disagree" data-refund_id="{pigcms{$rowset['id']}">不同意</button> </th>
					</tr>
				<?php } ?>
            </volist>
        </table>
    <?php } ?>
	<tr>
		<th colspan="12">订单备注:{pigcms{$order['desc']|default="无"}</th>
	</tr>

	<if condition="$order['invoice_head']">
		<tr>
			<th colspan="12">发票抬头:{pigcms{$order['invoice_head']}</th>
		</tr>
	</if>
	<if condition="$order['cue_field']">
		<tr>
			<th colspan="12">&nbsp;</th>
		</tr>
		<tr>
			<th colspan="12"><strong>分类填写字段</strong></th>
		</tr>
		<volist name="order['cue_field']" id="vo">
			<tr>
				<th colspan="1">{pigcms{$vo.title}</th>
				<th colspan="11">{pigcms{$vo.txt}</th>
			</tr>
		</volist>
	</if>

	<tr>
		<th colspan="12">
            <if condition="$notShow eq 1">
            <else />
			<input type="button" value="登录商家后台" class="button" onclick="go_merchant({pigcms{$order.mer_id})"/>
            &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="button" value="登录店员后台" class="button" onclick="go_staff({pigcms{$order.store_id})"/>
<!--			<eq name='order.order_from' value='1'>-->
<!--				<input type="button" value="登录店员后台" class="button" onclick="window.open('/admin.php?c=Merchant&a=storestaff_login&store_id={pigcms{$order.store_id}&shop_type=2')"/>-->
<!--			<else/>-->
<!--				<input type="button" value="登录店员后台" class="button" onclick="window.open('/admin.php?c=Merchant&a=storestaff_login&store_id={pigcms{$order.store_id}')"/>-->
<!--			</eq>-->
			</if>
            <a href="javascript:void(0);" style="margin-left: 25px;" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/order_detail_log',array('order_id'=>$order['order_id'],'frame_show'=>true))}','查看订单日志',720,520,true,false,false,false,'order_detail_log',true);">查看订单日志</a>

            <if condition="$config['open_system_refund'] eq 1 AND (in_array('374', explode(',',$system_session['menus'])) OR $system_session['account'] == 'admin')">
            	<a href="javascript:void(0);" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/system_refund',array('order_id' => $order['order_id']))}','手动退款',680,450,true,false,false,editbtn,'edit',true);">手动退款</a>
            	<a href="javascript:void(0);" style="margin-left: 25px;" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/system_refund_list',array('order_id'=>$order['order_id'],'frame_show'=>true))}','手动退款记录','50%','70%',true,false,false,false,'system_refund_list',true);">手动退款记录</a>
        	</if>
		</th>
	</tr>
</table>

<script>
	function refund_confirm(){
		layer.confirm('确认后订单状态改为已退款，金额请通过其他渠道手动退款给客户！', {
			btn: ['确定','取消'] //按钮
		}, function(){
			window.location.href='{pigcms{:U('Shop/refund_update',array('order_id'=>$order['order_id']))}';
		});
		//
	}
    function go_merchant(mer_id){
        $.post("{pigcms{:U('Merchant/merchant_login_v20')}&mer_id="+mer_id,function(result){
            setTimeout(function(){
	            window.open(result.data.url);
	        },600)
        },'json');
    }
    function go_staff(store_id){
        $.post("{pigcms{:U('Merchant/storestaff_login_v20')}&store_id="+store_id,function(result){
			if(result.data == undefined){
				layer.msg(result.msg);
            	return false;
			}

            setTimeout(function(){
	            window.open(result.data.url);
	        },600)
        },'json');
    }
	function change_note()
	{
		var order_id = '{pigcms{$order["order_id"]}';
		var note = $('input[name="note"]').val();
		console.log(order_id);
		$.post('{pigcms{:U("change_shop_order_note")}',{order_id:order_id,note:note},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}

    $('.refundImg').click(function(){
        var obj = $(this);
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
			area: ['500px'],
            content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
        });
    });

	var can_click = true;
    $('.refund').click(function(){
        if (!can_click) {
            layer.msg('频繁操作，请稍后再试');
            return false;
        }
        can_click = false;
        var refund_id = $(this).data('refund_id'), type = $(this).data('type'), order_id = '{pigcms{$order["order_id"]}';
        var reply_content = $(this).parent('th').find('input[name="reply_content"]').val();

        $.post("{pigcms{:U('Shop/replyRefund')}", {'order_id':order_id, 'refund_id':refund_id, 'reply_content':reply_content, 'type':type}, function(response){
            can_click = true;
            if (response.errcode == 1) {
                layer.msg(response.msg);
            } else {
                location.reload();
            }
        }, 'json');
    });
</script>
<include file="Public:footer"/>