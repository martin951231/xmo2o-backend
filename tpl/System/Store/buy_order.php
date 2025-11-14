<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('buy_order')}" class="on">{pigcms{$config.cash_alias_name}订单列表</a>
					<a href="{pigcms{:U('Config/index',array('galias'=>'quickpay','header'=>'Store/buy_order_header'))}">{pigcms{$config.cash_alias_name}配置</a>
					<a href="{pigcms{:U('store_ticket')}">票务插件</a>
				</ul>
			</div>



            <table class="search_table" width="100%">
                <tr>
                    <td>
                        <form action="{pigcms{:U('Store/buy_order')}" method="get">
                            <input type="hidden" name="c" value="Store"/>
                            <input type="hidden" name="a" value="buy_order"/>
                            <if condition="$admin_area neq 3">
                                <div id="choose_city_info" street_id="{pigcms{$_GET.street_id}" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" circle_id="{pigcms{$_GET.circle_id}" style="display:inline"></div>
                            </if>　　　

                            搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                            <select name="searchtype" id="searchtype">
                                <option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
                                <option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>店铺名称</option>
                                <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>订单流水号</option>
                            </select>
                            <font color="#000">支付日期筛选：</font>
                            <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            支付方式筛选:
                            <select id="pay_type" name="pay_type">
                                <option value="" <if condition="'' eq $_GET['pay_type']">selected="selected"</if>>全部支付方式</option>
                                <volist name="pay_method" id="vo">
                                    <option value="{pigcms{$key}" <if condition="$key eq $_GET['pay_type']">selected="selected"</if>>{pigcms{$vo.name}</option>
                                </volist>
                                <option value="balance" <if condition="'balance' eq $_GET['pay_type']">selected="selected"</if>>余额支付</option>
                            </select>
                            <input type="submit" value="查询" class="button"/>　
                        </form>
                    </td>

                    <td>
                        <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
                    </td>
                </tr>
            </table>


			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col  align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>订单编号</th>
								<th>商家名称</th>
								<th>店铺名称</th>
								<th>下单人</th>
								<th>电话</th>
								<th>订单金额</th>
								<th>优惠买单店铺优惠金额</th>
<!--								<th>获得{pigcms{$config.score_name}数</th>-->
<!--								<th>使用{pigcms{$config.score_name}数</th>-->
								<th class="hide_col">商家优惠券抵扣</th>
								<th class="hide_col">SYSTEM_STORE_BUY_ORDER_1</th>
								<th class="hide_col">会员等级优惠金额</th>
								<th class="hide_col">商家会员卡折扣金额</th>
								<th class="hide_col">会员卡余额支付</th>
								<th class="hide_col">会员卡赠送余额支付</th>
								<th class="hide_col">SYSTEM_STORE_BUY_ORDER_2</th>
								<th class="hide_col">线下支付</th>
								<th>线上支付金额</th>
								<th>实付金额</th>
								<th>支付时间</th>
								<th>支付类型</th>
                                <th>备注</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($order_list) && $order_list">
								<volist name="order_list" id="vo">
									<tr>
										<td>{pigcms{$vo.order_id}</td>
										<td>{pigcms{$vo.merchant_name}</td>
										<td>{pigcms{$vo.store_name}</td>
										<td>{pigcms{$vo.nickname}</td>
										<td>{pigcms{$vo.phone}</td>
										<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo['total_price']|floatval}</td>
										<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.discount_price|floatval}</td>
<!--										<td>{pigcms{$vo.score_give|floatval}</td>-->
<!--										<td>{pigcms{$vo.score_used_count|floatval}</td>-->
										<td class="hide_col">{pigcms{$vo.card_price|floatval}<if condition="$vo['merchant_coupon']">（{pigcms{$vo.merchant_coupon}）</if></td>
										<td class="hide_col">{pigcms{$vo.coupon_price|floatval}<if condition="$vo['system_coupon']">（{pigcms{$vo.system_coupon}）</if></td>
										<td class="hide_col">{pigcms{$vo.vip_discount_money}</td>
										<td class="hide_col">{pigcms{$vo.card_discount_money|floatval}</td>
										<td class="hide_col">{pigcms{$vo.merchant_balance|floatval}</td>
										<td class="hide_col">{pigcms{$vo.card_give_money|floatval}</td>
										<td class="hide_col">{pigcms{$vo.balance_pay|floatval}</td>
										<td class="hide_col">{pigcms{$vo.offline_money|floatval}</td>
										<td>{pigcms{$vo['payment_money']|floatval}</td>
										<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</td>
										<td>{pigcms{$vo['pay_time']|date="Y-m-d H:i:s",###}</td>
										<td>{pigcms{$vo.pay_type_show}</td>
                                        <td>{pigcms{$vo.remark}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>

var url = "{pigcms{$config.site_url}";
var export_url = "{pigcms{:U('Store/buy_order_export')}";

</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_circle.js?t=0000004"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>