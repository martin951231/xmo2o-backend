<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<title>{pigcms{$config.group_alias_name}订单| {pigcms{$config.site_name}</title>
<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
<meta name="description" content="{pigcms{$config.seo_description}" />
<link href="{pigcms{$static_path}css/css.css" type="text/css"  rel="stylesheet" />
<link href="{pigcms{$static_path}css/header.css"  rel="stylesheet"  type="text/css" />
<link href="{pigcms{$static_path}css/meal_order_list.css"  rel="stylesheet"  type="text/css" />
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script src="{pigcms{$static_public}js/jquery.lazyload.js"></script>
	<script type="text/javascript">
	   var  group_alias_name = "{pigcms{$config.group_alias_name}";
	</script>
<script src="{pigcms{$static_path}js/common.js"></script>
<script src="{pigcms{$static_path}js/category.js"></script>
<!--[if IE 6]>
<script  src="{pigcms{$static_path}js/DD_belatedPNG_0.0.8a.js" mce_src="{pigcms{$static_path}js/DD_belatedPNG_0.0.8a.js"></script>
<script type="text/javascript">
   DD_belatedPNG.fix('.enter,.enter a,.enter a:hover');
</script>
<script type="text/javascript">DD_belatedPNG.fix('*');</script>
<style type="text/css"> 
body{behavior:url("{pigcms{$static_path}css/csshover.htc");}
.category_list li:hover .bmbox {filter:alpha(opacity=50);}
.gd_box{display: none;}
</style>
<![endif]-->
<style>
	.body{
		width:100%;
	}
	#bd{
		width:100%;
	}
	.has-order-nav #content {
		width: 100%;
	}
	table{margin-bottom:10px;width:100%;}
	table tr,table th{background: url(img/hr.png) 0 bottom repeat-x;}
	table td{padding:0px 10px 0px 15px;color:#666;vertical-align:top;line-height:24px;    background-color: #f8f8f8;}
	table th{padding:0px 10px 0px;background-color:#f8f8f8;border-right:1px solid #e5e3e3;font-weight:100;line-height:24px;vertical-align:top;text-align:left;}
	table tr:hover th{background-color:#eef3f6;}
	
	@charset "UTF-8";.title-block {    height: 26px;    text-align: center;}.block {    display: -webkit-box;    display: -ms-flexbox;    display: flex;    -webkit-box-orient: vertical;    -webkit-box-direction: reverse;    -ms-flex-flow: column-reverse nowrap;    flex-flow: column-reverse nowrap;    width: 360px;    margin: 0 auto;    font-size: 14px;    color: #4C4C4C;    line-height: 28px;    /*border: 1px solid #999;*/    padding: 10px;}.block > .detail {    max-height: 163px;    margin-bottom: 10px;    overflow: hidden;}.block > .case-block {    display: -webkit-box;    display: -ms-flexbox;    display: flex;    -webkit-box-pack: center;    -ms-flex-pack: center;    justify-content: center;    -webkit-box-align: center;    -ms-flex-align: center;    align-items: center;    width: 120px;    height: 24px;    margin: 0 auto 0;    color: #0e0e0e;    background: #fff;    border: 1px solid #0e0e0e;    border-radius: 5px;}.block > .case-block div:nth-of-type(1) {    display: block;}.block > .case-block div:nth-of-type(2) {    display: none;}.block > [type="checkbox"] {    position: relative;    display: block;    width: 120px;    height: 24px;    margin: -24px auto 0;    z-index: 1000;    opacity: 0;}.block > [type="checkbox"]:hover + .case-block {    background-color: #f5f5f5;}.block > [type="checkbox"]:checked + .case-block div:nth-of-type(1) {    display: none;}.block > [type="checkbox"]:checked + .case-block div:nth-of-type(2) {    display: block;}.block > [type="checkbox"]:checked + .case-block + .detail {    max-height: inherit;}
	.detail th{
		background-color:#ccc;
	}
	.has-order-nav #content .mainbox {
		min-height: 100%;
	}
	#nav.mainnav_title {    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 10px;color:white;}
#nav.mainnav_title a {    padding: 0;
    height: 48px;
    padding: 0 20px;
    min-width: 0;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #666666;
	line-height: 46px;
	    display: inline-block;}
#nav.mainnav_title a.on{border-bottom: 2px solid #0099FC;text-decoration: none;    color: #3283FA;}
#nav.mainnav_title a:hover{color:#3283FA;text-decoration: none;}

</style>
<script src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
</head>
<body id="orders" class="has-order-nav" style="position:static;">

 <div class="body pg-buy-process"> 
	<div id="doc" class="bg-for-new-index">
		<div id="bdw" class="bdw">
			<div id="bd" class="cf">
				<div id="content" class="coupons-box">
					<div class="mainbox mine">
					
						<div class="orders-wrapper" id="order-list">
						<div id="nav" class="mainnav_title">
							<ul>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=shop_list&phone={pigcms{$_GET['phone']}" >{pigcms{$config.shop_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=service_list&phone={pigcms{$_GET['phone']}" >帮我买/帮我送</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=store_list&phone={pigcms{$_GET['phone']}" >店内消费</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=group_list&phone={pigcms{$_GET['phone']}" class="on">{pigcms{$config.group_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=meal_list&token={pigcms{$_GET['token']}&phone={pigcms{$_GET['phone']}">{pigcms{$config.meal_alias_name}订单</a>
							</ul>
						</div>
						<php>if($order_list){</php>
							<volist name="order_list" id="now_order">
							
							<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" <if condition="$key gt 0 AND !isset($_GET['page'])">style="display:none;"</if>>
							<tr>
								<th width="15%">订单编号</th>
								<td colspan="3" width="85%">{pigcms{$now_order.real_orderid}</td>
							</tr>
							<if condition="$now_order.orderid neq 0">
							<tr>
								<th width="15%">订单流水号</th>
								<td colspan="3" width="85%">{pigcms{$now_order.orderid}</td>
							</tr>
							</if>
							<tr>
								<th width="15%">{pigcms{$config.group_alias_name}商品</th>
								<td colspan="3" width="85%"><a href="{pigcms{$now_order.url}" target="_blank" title="查看商品详情">{pigcms{$now_order.s_name}</a></td>
							</tr>
							
							
							<tr>
								<td colspan="4" style="padding-left:5px;color:black;"><b>订单信息</b></td>
							</tr>
							<tr>
								<th width="15%">订单类型</th>
								<td width="35%"><if condition="$now_order['tuan_type'] eq '0'">{pigcms{$config.group_alias_name}券<elseif condition="$now_order['tuan_type'] eq '1'"/>代金券<else/>实物</if></td>
								<th width="15%">订单状态</th>
								<td width="35%">
									<if condition="$now_order['paid']">
										<if condition="$now_order['pay_type'] eq 'offline' AND empty($now_order['third_id'])" >
											<font color="red">线下支付&nbsp;未付款</font>
										<elseif condition="$now_order['status'] eq 0" />
											<font color="green">已付款</font>&nbsp;
											<php>if($now_order['tuan_type'] != 2){</php>
												<font color="red">未消费</font>
											<php>}else{</php>
												<php>if($now_order['is_pick_in_store']){</php>
														<font color="red">未取货</font>
													<php>}else{</php>
														<php>if($now_order['express_id'] != ''){</php>
															<font color="red">已发货</font>
														<php>}else{</php>
															<font color="red">未发货</font>
														<php>}</php>
													<php>}</php>
											<php>}</php>

										<elseif condition="$now_order['status'] eq 7"/>
										  <font color="red">退款失败，原因：{pigcms{$now_order['refund_detail']}</font>
										<elseif condition="$now_order['status'] eq 1"/>
										
											<php>if($now_order['tuan_type'] != 2){</php>
												<font color="green">已消费</font>
											<php>}else{</php>
												<php>if($now_order['is_pick_in_store']){</php>
														<font color="green">已取货</font>
													<php>}else{</php>
														<font color="green">已发货</font>
													<php>}</php>
											<php>}</php>&nbsp;
											<font color="red">待评价</font>
										<elseif condition="$now_order['status'] eq 2"/>
											<font color="green">已完成</font>
										<elseif condition="$now_order['status'] eq 3"/>
											<font color="red">已退款</font>
										<elseif condition="$now_order['status'] eq 4"/>
											<font color="red">已取消</font>
										</if>
									<else/>
										<if condition="$now_order['status'] eq 4">
											<font color="red">已取消</font>
										<else />
											<font color="red">未付款</font>
										</if>
									</if>
									<if condition="$now_order['status'] eq 0 AND $now_order['paid'] eq 1"><a href="javascript:void(0)" onclick="refund_confirm();"><font color="blue">更改状态为已退款</font></a></if>
									
									<if condition=" $now_order['status'] eq 0 AND $now_order['paid'] eq 0"><a href="javascript:void(0)" onclick="change_status();"><font color="blue">更改状态为已取消</font></a></if>
								</td>
							</tr>
							<tr>
								<th width="15%">数量</th>
								<td width="35%">{pigcms{$now_order.num}</td>
								<th width="15%">总价</th>
								<td width="35%">{pigcms{$config['Currency_symbol']} {pigcms{$now_order.total_money}<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if></td>
							</tr>
							<if condition="$now_order.express_fee gt 0">
							<tr>
								<th width="15%">运费</th>
								<td width="35%">{pigcms{$now_order.express_fee}</td>
								<th width="15%"></th>
								<td width="35%"></td>
							</tr>
							</if>
							<tr>
								<th width="15%">下单时间</th>
								<td width="35%">{pigcms{$now_order.add_time|date='Y-m-d H:i:s',###}</td>
								<if condition="$now_order['paid']">
									<th width="15%">付款时间</th>
									<td width="35%">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</td>
								<else/>
									<th width="15%"></th>
									<td width="35%"></td>
								</if>
							</tr>
							<tr>
								<th width="15%">消费密码</th>
								<td width="35%"><if condition="$now_order['group_pass']">{pigcms{$now_order.group_pass_txt}</if></td>
								<th width="15%">验证店员</th>
								<td width="35%">
									<if condition="$now_order['store_id']">
										<if condition="$now_order['store_name']">{pigcms{$now_order.store_name}<else/>店铺不存在</if>
										 (<if condition="$now_order['last_staff']">{pigcms{$now_order.last_staff}<else/>尚未验证</if>)
									<else/>
										尚未验证
									</if>
								</td>
							</tr>
							<tr>
								<th width="15%">买家留言</th>
								<td width="85%" colspan="3">{pigcms{$now_order.delivery_comment}</td>
							</tr>
							<if condition="$now_order['status'] eq 1">		
								<tr>
									<th width="15%"><if condition="$now_order['tuan_type'] neq 2">消费<else/>发货</if>时间</th>
									<td width="85%" colspan="3">{pigcms{$now_order.use_time|date='Y-m-d H:i:s',###}</td>
								</tr>
							</if>
							
							<tr>
								<td colspan="4" style="padding-left:5px;color:black;"><b>用户信息：</b></td>
							</tr>
							<tr>
								<th width="15%">用户ID</th>
								<td width="35%">{pigcms{$now_order.uid}</td>
								<th width="15%">用户昵称</th>
								<td width="35%">{pigcms{$now_order.nickname}</td>
							</tr>
							<tr>
								<th width="15%">订单手机号</th>
								<td width="35%">{pigcms{$now_order.phone}</td>
								<th width="15%">用户手机号</th>
								<td width="35%">{pigcms{$now_order.user_phone}</td>
							</tr>
							<if condition="$now_order['tuan_type'] eq 2">
								<tr>
									<td colspan="4" style="padding-left:5px;color:black;"><b>配送信息：</b></td>
								</tr>
								<tr>
									<th width="15%">收货人</th>
									<td width="35%">{pigcms{$now_order.contact_name}</td>
									<th width="15%">联系电话</th>
									<td width="35%">{pigcms{$now_order.phone}</td>
								</tr>
								<tr>
									<th width="15%">配送要求</th>
									<td width="35%">
										<switch name="now_order['delivery_type']">
											<case value="1">工作日、双休日与假日均可送货</case>
											<case value="2">只工作日送货</case>
											<case value="3">只双休日、假日送货</case>
											<case value="4">白天没人，其它时间送货</case>
										</switch>
									</td>
									<th width="15%">邮编</th>
									<td width="35%">{pigcms{$now_order.zipcode}</td>
								</tr>
								<tr>
									<th width="15%">收货地址</th>
									<td width="85%" colspan="3">{pigcms{$now_order.adress}</td>
								</tr>
							</if>
							<if condition="$now_order['third_id'] eq '0' AND $now_order['pay_type'] eq 'offline'">
								<tr>
									<th width="15%">线下支付</th>
									<th width="85%" colspan="3">总金额{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_money']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;平台余额支付{pigcms{$now_order.balance_pay}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;使用商家会员卡余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['merchant_balance']} &nbsp;&nbsp;使用商家会员卡赠送余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['card_give_money']}<br>
									<if condition="$now_order['wx_cheap'] neq '0.00'">微信优惠 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['wx_cheap']}<br></if>
									<br>{pigcms{$config.score_name}抵扣金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.score_deducte}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$config.score_name}使用数量 {pigcms{$now_order.score_used_count}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										<br>
										平台优惠券抵扣金额:{pigcms{$config['Currency_symbol']} {pigcms{$now_order.coupon_price}
									
										商家优惠券抵扣金额:{pigcms{$config['Currency_symbol']} {pigcms{$now_order.card_price}
										
										微信优惠 ：{pigcms{$config['Currency_symbol']}{pigcms{$now_order.wx_cheap}
										
										<br>线下需向商家付金额：<font color="red">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price']}{pigcms{$config['Currency_txt']}</font>
									</th>
								</tr>
							<else />
								<tr>
									<th width="15%">支付方式</th>
									<th width="85%" colspan="3">
									使用商家会员卡折扣 {pigcms{$now_order.card_discount} 折<br>
									余额支付金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.balance_pay}<br>
									在线支付金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.payment_money}<br>
									使用商家会员卡余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.merchant_balance}<br>
									使用商家会员卡赠送余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['card_give_money']}<br>
									{pigcms{$config.score_name}抵扣金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.score_deducte}<br>
									{pigcms{$config.score_name}使用数量 {pigcms{$now_order.score_used_count}<br>
								
										平台优惠券抵扣金额:{pigcms{$config['Currency_symbol']} {pigcms{$now_order.coupon_price}		<br>
									
										商家优惠券抵扣金额:{pigcms{$config['Currency_symbol']} {pigcms{$now_order.card_price}		<br>
										
										微信优惠 ：{pigcms{$config['Currency_symbol']}{pigcms{$now_order.wx_cheap}		<br>
										
									</th>
								</tr>	
							</if>
							<if condition="$pass_array">
								<tr>
									<th width="15%">消费密码</th>
									<th width="85%" colspan="3">
										<volist name="pass_array" id="vo">
										{pigcms{$i}. {pigcms{$vo.group_pass}<br>
										</volist>
									</th>
								</tr>	
							<elseif condition="$now_order['group_pass']"  />
								<tr>
									<th width="15%">消费密码</th>
									<th width="85%" colspan="3">
									{pigcms{$now_order.group_pass}
									</th>
								</tr>	
							</if>
							<if condition="$trade_hotel_info">
								<tr>
									<td colspan="4" style="padding-left:5px;color:black;"><b>酒店订单详情：</b></td>
								</tr>
								<tr>
									<th width="15%">房间类型</th>
									<td width="85%" colspan="3">{pigcms{$trade_hotel_info.cat_pname} ({pigcms{$trade_hotel_info.cat_name})</td>
								</tr>
								<tr>
									<th width="15%">入住时间</th>
									<td width="35%">{pigcms{$trade_hotel_info.dep_time_txt}</td>
									<th width="15%">离店时间</th>
									<td width="35%">{pigcms{$trade_hotel_info.end_time_txt}</td>
								</tr>
								<tr>
									<th width="15%">房间数</th>
									<td width="35%">{pigcms{$trade_hotel_info.num}</td>
									<th width="15%">入住天数</th>
									<td width="35%">{pigcms{$trade_hotel_info['end_time']-$trade_hotel_info['dep_time']}天</td>
								</tr>
								<tr>
									<th width="15%">价格清单</th>
									<th width="65%" colspan="2">
										<volist name="trade_hotel_info['price_list_txt']" id="vo">
											{pigcms{$vo.day}：{pigcms{$vo.price} {pigcms{$config['Currency_txt']} * {pigcms{$trade_hotel_info.num}<br/>
										</volist>
									</th>
									<th width="20%">
									
									<if condition="$now_order['refund_detail']['refund_time']">
										已退款积分：{pigcms{$now_order['refund_detail']['score']|floatval} 个<br>
										已退款商家余额：{pigcms{$now_order['refund_detail']['card_give_money']+$now_order['refund_detail']['detail']['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}<br>
										
										已退款平台余额余额：{pigcms{$now_order['refund_detail']['balance_pay']|floatval} {pigcms{$config['Currency_txt']}<br>
										已退款在线支付：{pigcms{$now_order['refund_detail']['payment_money']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									</th>
											
								</tr>
							</if>
							<if condition="$now_order['refund_detail']['err_msg'] neq ''">
								<th width="15%">退款信息</th>
								<td width="85%" colspan="3">{pigcms{$now_order.refund_detail.err_msg}</td>
							</if>
						</table>
						</volist>
							<php>}else{</php>
												没有数据
											<php>}</php>
                        </div>
						{pigcms{$pagebar}
					</div>
					<if condition="count($order_list) gt 1 AND !isset($_GET['page'])">
						<section class="block">    
							<input type="checkbox" class="explode">    
							<div class="case-block">      
								<div >展开</div>      
								<div >收起</div>   
							</div>    
						</section>
					</if>
				</div>
			</div> <!-- bd end -->
		</div>
	</div>	
</div>

<script type="text/javascript">
	<if condition="!isset($_GET['page'])">
	var flag = false;
	</if>
	$(document).ready(function(){
		$("#select_status").change(function(data){
			location.href='{pigcms{:U("User/Index/shop_list")}&status=' + $(this).val();
		});
		
		if(!flag){
			$('.paginator-wrapper').hide();
		}
		
		$('.explode').click(function(){
			if(flag==false){
				$('.paginator-wrapper').show();
				$('#order-list table').show();
				flag = true;
			}else{
				$('#order-list table').hide();
				$('.paginator-wrapper').hide();
				$('#order-list table:first').show();
				flag = false;
			}
		})
	});
	
	
	function shop_order_cancel(order_id){
			if(confirm('确认取消该订单？')){
				var cancelUrl = "{pigcms{:U('shop_order_check_refund')}";
				cancelUrl += "&order_id="+order_id;
				location.href = cancelUrl;
			}
			
		}
    
    function shop_order_finish(order_id){
        if(confirm('您确定已经收到货物了？')){
            var cancelUrl = "{pigcms{:U('finishOrder')}";
            cancelUrl += "&order_id="+order_id;
            location.href = cancelUrl;
        }
    }
</script>
</body>
</html>
