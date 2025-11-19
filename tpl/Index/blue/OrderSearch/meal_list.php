<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<title>{pigcms{$config.meal_alias_name}订单| {pigcms{$config.site_name}</title>
<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
<meta name="description" content="{pigcms{$config.seo_description}" />
<link href="{pigcms{$static_path}css/css.css" type="text/css"  rel="stylesheet" />
<link href="{pigcms{$static_path}css/header.css"  rel="stylesheet"  type="text/css" />
<link href="{pigcms{$static_path}css/meal_order_list.css"  rel="stylesheet"  type="text/css" />
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script src="{pigcms{$static_public}js/jquery.lazyload.js"></script>
	<script type="text/javascript">
	   var  meal_alias_name = "{pigcms{$config.meal_alias_name}";
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
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=group_list&phone={pigcms{$_GET['phone']}" >{pigcms{$config.group_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=meal_list&token={pigcms{$_GET['token']}&phone={pigcms{$_GET['phone']}" class="on">{pigcms{$config.meal_alias_name}订单</a>
							</ul>
						</div>
						<php>if($order_list){</php>
							<volist name="order_list" id="order">
							
							<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" <if condition="$key gt 0 AND !isset($_GET['page'])">style="display:none;"</if>>
							<tr>
								<th width="15%">订单编号</th>
								<td colspan="3" width="85%">{pigcms{$order.real_orderid}</td>
							</tr>
							<if condition="$order.orderid neq 0">
							<tr>
								<th width="15%">订单流水号</th>
								<td colspan="3" width="85%">{pigcms{$order.orderid}</td>
							</tr>
							</if>
							<tr>
								<th width="15%">商家名称</th>
								<td width="35%">{pigcms{$order.merchant_name}</td>
								<th width="15%">店铺名称</th>
								<td width="35%">{pigcms{$order.store_name}</td>
							</tr>
							<tr>
								<th width="15%">桌台名称</th>
								<td width="35%">{pigcms{$order.table_name}</td>
								<th width="15%">订单状态</th>
								<td width="35%">{pigcms{$order.show_status}</td>
							</tr>
					
						
							<tr>
								<th width="15%">下单时间</th>
								<td width="35%">{pigcms{$order.create_time|date='Y-m-d H:i:s',###}</td>
								<if condition="$order['paid']">
									<th width="15%">付款时间</th>
									<td width="35%">{pigcms{$order.pay_time|date='Y-m-d H:i:s',###}</td>
								<else/>
									<th width="15%"></th>
									<td width="35%"></td>
								</if>
							</tr>
							
							<tr>
								<th width="15%">订单信息</th>
								<th width="85%" colspan="3">
									总金额{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|floatval}<br>
									客户姓名：{pigcms{$order['order_detail']['name']}<br>
									客户手机：{pigcms{$order['order_detail']['phone']}<br>
									<if condition="$order['register_phone']">
									客户注册手机：{pigcms{$order['register_phone']}<br>
									</if>
									预订金额：{pigcms{$order['order_detail']['book_price']|floatval} {pigcms{$config['Currency_txt']}<br>
									<if condition="$order['status'] gt 2 AND $order['status'] lt 5">
									订单总价：{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']|floatval} {pigcms{$config['Currency_txt']}<br>
									店铺优惠：{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_reduce']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['balance_reduce'] gt 0">
									平台优惠：{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_reduce']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['score_used_count']">	使用{pigcms{$config.score_name}：{pigcms{$order['order_detail']['score_used_count']} {pigcms{$config.score_name}抵现：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['score_deducte']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['merchant_balance']">
									商家余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['balance_pay']">
									平台余额：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['balance_pay']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['payment_money']">
									在线支付：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['payment_money']|floatval} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['card_id']">
									店铺优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['card_price']} {pigcms{$config['Currency_txt']}<br>
									</if>
									<if condition="$order['order_detail']['coupon_id']">
									平台优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['coupon_price']} {pigcms{$config['Currency_txt']}<br>
									</if>
									实付总价：{pigcms{$config['Currency_symbol']}{pigcms{$order['order_detail']['price']|floatval} {pigcms{$config['Currency_txt']}<br>
									<if condition="$order['order_detail']['book_pay_type']">
										预订支付方式：{pigcms{$order['order_detail']['book_pay_type']}<br>
									</if>
									<if condition="$order['order_detail']['pay_type']">
									订单支付方式：{pigcms{$order['order_detail']['pay_type']}<br>
									</if>
									订单状态：{pigcms{$order['order_detail']['show_status']} <br>
								</th>
							</tr>
							<tr>
								<th width="15%">备注</th>
								<td width="85%" colspan="3">{pigcms{$order['order_detail']['note']|default="无"}</td>
							</tr>
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
