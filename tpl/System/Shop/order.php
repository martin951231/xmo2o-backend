<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Shop/order',array('from_type'=>$from_type))}" class="on">订单列表</a>
				<a href="{pigcms{:U('Shop/order',array('from_type'=>$from_type))}" style="float:right;" title="点击手动刷新" id="fresh_list_btn"></a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Shop/order')}" method="get">
						<input type="hidden" name="c" value="Shop"/>
						<input type="hidden" name="a" value="order"/>
						<input type="hidden" name="from_type" value="{pigcms{$from_type|default=1}"/>
                        <if condition="$admin_area neq 3">
                        <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                        </if>　　　
						
						搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype" id="searchtype">
							<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>订单编号</option>
							<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>订单流水号</option>
							<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>第三方支付流水号</option>
							<option value="s_name" <if condition="$_GET['searchtype'] eq 's_name'">selected="selected"</if>>店铺名称</option>
							<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
						</select>
						<font color="#000">日期筛选：</font>
						<select name="searchTimeType" id="searchTimeType">
							<option value="create_time" <if condition="$_GET['searchTimeType'] eq 'create_time'">selected="selected"</if>>下单时间</option>
							<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
							<option value="refund_time" <if condition="$_GET['searchTimeType'] eq 'refund_time'">selected="selected"</if>>退款时间</option>
							<option value="expect_use_time" <if condition="$_GET['searchTimeType'] eq 'expect_use_time'">selected="selected"</if>>预计送达时间</option>
                            <option value="use_time" <if condition="$_GET['searchTimeType'] eq 'use_time'">selected="selected"</if>>消费时间</option>
						</select>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						<br/>
						订单状态:
						<select id="status" name="status">
							<volist name="status_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>&nbsp;&nbsp;&nbsp;&nbsp;
                        订单来源:
                        <select id="order_from" name="order_from">
							<option value="0" <if condition="$order_from eq 0">selected="selected"</if>>全部订单</option>
							<option value="1" <if condition="$order_from eq 1">selected="selected"</if>>{pigcms{$config.shop_alias_name}订单</option>
							<option value="6" <if condition="$order_from eq 6">selected="selected"</if>>线下零售订单</option>
                        </select>&nbsp;&nbsp;&nbsp;&nbsp;
						配送方式:
						<select id="is_pick_in_store" name="is_pick_in_store">
							<option value="" <if condition="!isset($_GET['is_pick_in_store']) || $_GET['is_pick_in_store'] === ''">selected="selected"</if>>全部类型</option>
							<option value="0" <if condition="$_GET['is_pick_in_store'] === '0'">selected="selected"</if>>平台配送</option>
							<option value="1" <if condition="$_GET['is_pick_in_store'] === '1'">selected="selected"</if>>商家配送</option>
							<option value="2" <if condition="$_GET['is_pick_in_store'] === '2'">selected="selected"</if>>到店自提</option>
							<option value="3" <if condition="$_GET['is_pick_in_store'] === '3'">selected="selected"</if>>快递配送</option>
							<option value="4" <if condition="$_GET['is_pick_in_store'] == 4">selected="selected"</if>>机器人配送</option>
						</select>&nbsp;&nbsp;&nbsp;&nbsp;
						支付方式: 
						<select id="pay_type" name="pay_type">
							<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>全部支付方式</option>
							<volist name="pay_method" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
							</volist>
								<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>余额支付</option>
						</select>&nbsp;&nbsp;&nbsp;&nbsp;
						<if condition="$config['is_hark_system']">
							支付类型:
							<select id="from_pay_type" name="from_pay_type">
								<option value="" <if condition="!isset($_GET['from_pay_type']) || $_GET['from_pay_type'] === ''">selected="selected"</if>>全部类型</option>
								<option value="0" <if condition="$_GET['from_pay_type'] === '0'">selected="selected"</if>>Pisel支付</option>
								<option value="1" <if condition="$_GET['from_pay_type'] === '1'">selected="selected"</if>>商家自有</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;
						</if>
						<input type="submit" value="查询" class="button"/>　
					</form>
				</td>
				
				<td style="width:100px;">
				<a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
				</td>
			</tr>
			<eq name="from_type" value='2'>
				<tr>
					<td>
	                    <b>总订单金额：{pigcms{$total_price|floatval}</b>
	                    <b>总订单数：{pigcms{$count|floatval}</b>
	                </td>
	            </tr>
			<else/>
				<tr>
					<td>
	                    <b>订单总金额：{pigcms{$total_price|floatval}</b>　
	                    <!-- <b>验证消费总金额：{pigcms{$money|floatval}</b>　 -->
	                    <b>应付总金额：{pigcms{$pay_price|floatval}</b>　
	                    <b>第三方支付平台总额：{pigcms{$payment_money|floatval}</b>　
	                    <b>平台余额支付总额：{pigcms{$balance_pay|floatval}</b>　
						<b>商家会员卡支付总额：{pigcms{$merchant_balance|floatval}</b>　
						<b>商家会员卡（赠送）支付总额：{pigcms{$card_give_money|floatval}</b>　
						<b>平台积分支付总额：{pigcms{$score_deducte|floatval}</b>　
	                </td>
	            </tr>
			</eq>            
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>订单编号</th>
							<th>取单号</th>
							<th>配送方式</th>
							<th>店铺名称</th>
							<th>店铺电话</th>
							<th>下单人</th>
							<th>电话</th>
                            <th>注册姓名</th>
                            <th>注册电话</th>
							<th>总价<i class="menu-icon fa fa-sort"></i></th>
							<th>平台优惠</th>
							<th>商家优惠</th>
							<th>
							<if condition="$type eq 'price'">
								<if condition="$sort eq 'ASC'">
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'price', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">应付总价↓ </a>
								<elseif condition="$sort eq 'DESC'" />
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'price', 'sort' => 'ASC', 'status' => $status)))}" style="color:blue;">应付总价↑</a>
								<else />
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'price', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">应付总价<i class="menu-icon fa fa-sort"></i></a>
								</if>
							<else />
								<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'price', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">应付总价<i class="menu-icon fa fa-sort"></i></a>
							</if>
							</th>
							<th>
							<if condition="$type eq 'pay_time'">
								<if condition="$sort eq 'ASC'">
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">支付时间↓</a>
								<elseif condition="$sort eq 'DESC'" />
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'pay_time', 'sort' => 'ASC', 'status' => $status)))}" style="color:blue;">支付时间↑</a>
								<else />
									<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">支付时间</a>
								</if>
							<else />
								<a href="{pigcms{:U('Shop/order', array_merge($_GET,array('from_type'=>$from_type,'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status)))}" style="color:blue;">支付时间</a>
							</if>
							</th>
							<th>送达时间</th>
                            <th>收货地址</th>
							<th>订单状态</th>
							<if condition="$config['is_hark_system']">
								<th>支付类型</th>
							</if>
							<th>支付情况</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($order_list)">
							<volist name="order_list" id="vo">
								<tr>
									<td>{pigcms{$vo.real_orderid}</td>
									<td><if condition="$vo.fetch_number gt 0">{pigcms{$vo.fetch_number}<else />-</if></td>
									<td>
										<if condition="$vo['is_pick_in_store'] eq 0">
											平台配送
										<elseif condition="$vo['is_pick_in_store'] eq 1" />
											商家配送
										<elseif condition="$vo['is_pick_in_store'] eq 2" />
											到店自提
										<elseif condition="$vo['is_pick_in_store'] eq 3" />
											快递配送
										</if>
									</td>
									<td>{pigcms{$vo.store_name}</td>
									<td>{pigcms{$vo.store_phone|phone_demo_show}</td>
									<td>{pigcms{$vo.username}</td>
									<td>{pigcms{$vo.userphone|phone_demo_show}</td>
                                    <td>{pigcms{$vo.register_name}</td>
                                    <td>{pigcms{$vo.register_phone|phone_demo_show}</td>
									<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo['total_price']|floatval}</td>
                                    <td>{pigcms{$config['Currency_symbol']}<php>echo isset($vo['balance_reduce_total'])?getFormatNumber($vo['balance_reduce_total']):getFormatNumber($vo['balance_reduct']);</php></td>
									<td>{pigcms{$config['Currency_symbol']}<php>echo isset($vo['merchant_reduce_total'])?getFormatNumber($vo['merchant_reduce_total']):getFormatNumber($vo['merchant_reduce']);</php></td>
									<td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.price|floatval}</td>
									<td><if condition="$vo['pay_time']"> {pigcms{$vo['pay_time']|date="Y-m-d H:i:s",###}</if></td>
									<td><if condition="$vo['use_time']">{pigcms{$vo['use_time']|date="Y-m-d H:i:s",###}</if></td>
                                    <td class="status">{pigcms{$vo.address}</td>
									<td class="status">{pigcms{$vo.status_str}</td>
									<if condition="$config['is_hark_system']">
									<td>
										<if condition="$vo['from_pay_type'] eq 0">
											Pisel支付
										<else />
											商家自有
										</if>
									</td>
									</if>
									<td><!-- {pigcms{$vo.pay_status} --><span style="color: green">{pigcms{$vo.pay_type_str}</span></td>
									<td class="textcenter">
										<eq name="from_type" value='2'>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看{pigcms{$config.mall_alias_name}订单详情',900,'90%',true,false,false,false,'detail',true);">查看</a>
										<else/>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看{pigcms{$config.shop_alias_name}订单详情',900,'90%',true,false,false,false,'detail',true);">查看</a>
										</eq>										
									</td>
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


var lefttime = {pigcms{$config['shoporder_auto_fresh_time']} * 60 * 1000;  //距离结束时间的毫秒数
var showtime = function () {
    lefttime = lefttime - 1000;
	console.log('lefttime', lefttime);
	leftd = Math.floor(lefttime/(1000*60*60*24)),  //计算天数
	lefth = Math.floor(lefttime/(1000*60*60)%24),  //计算小时数
	leftm = Math.floor(lefttime/(1000*60)%60),  //计算分钟数
	lefts = Math.floor(lefttime/1000%60);  //计算秒数
    // return leftd + "天" + lefth + ":" + leftm + ":" + lefts;  //返回倒计时的字符串
    return leftm + "分" + lefts + "秒";  //返回倒计时的字符串
}

if(lefttime != 0){
	setInterval(function(){
		var timer = showtime();
		console.log('timer', timer);
		if(timer == '0分0秒'){
			location.href = $('#fresh_list_btn').attr('href');
		}else{
			$('#fresh_list_btn').html(timer + ' 后自动刷新');
		}
	}, 1000);
}


$(function(){
	$('#status').change(function(){
		location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&searchTimeType="+$('#searchTimeType').val()+"&status=" + $(this).val() + "&pay_type=" + $('#pay_type').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $('#order_from').val();
	});
	
	$('#pay_type').change(function(){
		location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&searchTimeType="+$('#searchTimeType').val()+"&pay_type=" + $(this).val() + "&status=" + $('#status').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $('#order_from').val();
	});
    $('#order_from').change(function(){
        location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&searchTimeType="+$('#searchTimeType').val()+"&pay_type=" + $('#pay_type').val() + "&status=" + $('#status').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $(this).val();
    });
    $('.refund').click(function(){
		var get_url = $(this).data('href'), obj = $(this);
		window.top.art.dialog({
			content: '您确定要手动退款吗？只取修改订单状态！',
			lock: true,
			ok: function () {
				this.close();
				$.get(get_url,function(response){
					if (response.status == 1) {
						obj.parents('tr').find('.status').html('<del style="color:gray">已退款</del>');
						obj.remove();
					} else {
						window.top.art.dialog({
							title: response.info
						});
					}
				},'json');
				return false;
			},
			cancelVal: '取消',
			cancel: true
		});
	});
});
var url = "{pigcms{$config.site_url}"
var export_url = "{pigcms{:U('Shop/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>