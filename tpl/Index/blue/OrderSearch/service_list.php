<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<title>帮我买帮我送订单| {pigcms{$config.site_name}</title>
<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
<meta name="description" content="{pigcms{$config.seo_description}" />
<link href="{pigcms{$static_path}css/css.css" type="text/css"  rel="stylesheet" />
<link href="{pigcms{$static_path}css/header.css"  rel="stylesheet"  type="text/css" />
<link href="{pigcms{$static_path}css/meal_order_list.css"  rel="stylesheet"  type="text/css" />
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script src="{pigcms{$static_public}js/jquery.lazyload.js"></script>
	
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
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=service_list&phone={pigcms{$_GET['phone']}" class="on">帮我买/帮我送</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=store_list&phone={pigcms{$_GET['phone']}" >店内消费</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=group_list&phone={pigcms{$_GET['phone']}" >{pigcms{$config.group_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=meal_list&token={pigcms{$_GET['token']}&phone={pigcms{$_GET['phone']}">{pigcms{$config.meal_alias_name}订单</a>
							</ul>
						</div>
						<php>if($list){</php>
							<volist name="list" id="order">
						
							<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" <if condition="$key gt 0 AND !isset($_GET['page'])">style="display:none;"</if>>
								<tr>
									<td width="160">订单编号：</td>
									<td>
										<label>{pigcms{$order.order_sn}</label>
									</td>
									<td width="160">服务商：</td>
									<td>
										<label>{pigcms{$order.provider_name}</label>
									</td>
								</tr>
								<tr>
									<td width="160">分类：</td>
									<td>
										<label>{pigcms{$order.cat_name}</label>
									</td>
									<td width="160">价格：</td>
									<td>
										<label>{pigcms{$order.price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">下单时间：</td>
									<td>
										<label>{pigcms{$order.add_time|date="Y-m-d H:i:s",###}</label>
									</td>
									<td width="160">订单状态：</td>
									<td>
										<label>  <if condition="$order.offer_status eq 5">用户申请退款
                                                <elseif condition="$order.status eq 2"/>已支付待服务
                                                <elseif condition="$order.status eq 3"/>已服务待确认
                                                <elseif condition="$order.status eq 4"/>订单完成
                                                <elseif condition="$order.status eq 5"/>用户申请退款
                                                <elseif condition="$order.status eq 6"/>退款成功
                                                <elseif condition="$order.status eq 7"/>订单完成
                                                <elseif condition="$order.status eq 8"/>等待取货
                                                <elseif condition="$order.status eq 9"/>配送中
                                                <elseif condition="$order.status eq 10"/>用户已取消
                                                <elseif condition="$order.status eq 11"/>服务过期
                                                <elseif condition="$order.status eq 12"/>过期退款
                                                <elseif condition="$order.status eq 13"/>退款失败
                                            </if></label>
									</td>
								</tr>
								
								<if condition="$order.catgory_type eq 1">
									<volist name="order['publishInfo']['cat_field']" id="vo">
										<if condition="$vo['type'] eq 6">
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<label>{pigcms{$vo.value.address}</label>
												</td>
											</tr>
										<elseif condition="$vo['type'] eq 3"/>
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<label><volist name="vo.value" id="vvo">
														<span style="display:inline; padding-right: 15px;" class="">{pigcms{$vvo}</span>
													</volist></label>
												</td>
											</tr>
										<elseif condition="$vo['type'] eq 2"/>
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<if condition="$vo['value'] eq 'inputdesc'">
														<label>{pigcms{$vo.desc}</label>
													<elseif condition="$vo['value'] eq 'time'"/>
														<label>{pigcms{$vo.date}{pigcms{$vo.minute}</label>
													<else/>
														<label>{pigcms{$vo.value}</label>
													</if>
												</td>
											</tr>
											
										<elseif condition="$vo['type'] eq 4"/>
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<label>{pigcms{$vo.value.time_start} {pigcms{$vo.value.time_end}</label>
												</td>
											</tr>
										<elseif condition="$vo['type'] eq 7"/>
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<label>起点：<span class="orange js_need_address address_start">{pigcms{$vo['value']['address_start']}</span>
													&nbsp;&nbsp;&nbsp;终点：<span class="orange js_need_address address_end">{pigcms{$vo['value']['address_end']}</span></label>
												</td>
											</tr>
										<else/>
											<tr>
												<td width="160">{pigcms{$vo.alias_name}：</td>
												<td colspan="3">
													<label>{pigcms{$vo.value}</label>
												</td>
											</tr>
										</if>
									</volist>


							<elseif condition="$order.catgory_type eq 2"/>
	

								<tr>
									<td width="160">商品要求：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.goods_remarks}</label>
									</td>
								</tr>
								<tr>
									<td width="160">购买类型：</td>
									<td colspan="3">
										<label><if condition="$cat_field_info.buy_type eq 1">就近购买<else/>指定地址</if></label>
									</td>
								</tr>

								
									<tr>
										<td width="160">指定地址：</td>
										<td colspan="3">
											<label>{pigcms{$order.cat_field_info.address}</label>
										</td>
									</tr>
								

								
								<tr>
									<td width="160">送达地址：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.end_adress_name}</label>
									</td>
								</tr>
								<if condition="$order['publishInfo']['register_phone']">
								<tr>
									<td width="160" >客户注册手机：</td>
									<td colspan="3">
										<label >{pigcms{$order.publishInfo.register_phone}</label>
									</td>
									</tr>
								</if>
								<if condition="$order['cat_field_info']['end_adress_phone']">
								<tr>
									<td width="160" >收货人手机：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.end_adress_phone}</label>
									</td>
									</tr>
								</if>
								<tr>
									<td width="160">送达时间：</td>
									<td colspan="3">
										<label>预计在 {pigcms{$order.cat_field_info.arrival_time}（分钟） 内送达</label>
									</td>
								</tr>
								<tr>
									<td width="160">预估商品费用：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.estimate_goods_price}</label>
									</td>
								</tr>

								<tr>
									<td width="160">基础配送费：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.basic_distance_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">超出基础配送距离费用：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.distance_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">小费：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.tip_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">总价：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.total_price}</label>
									</td>
								</tr>

								<if condition="$order.cat_field_info.img">
									<tr>
										<td width="160">商品图片：</td>
										<td colspan="3">
											<label><volist name="order.cat_field_info['img']" id="vo">
												<!-- <a href="javascript:void(0);" onclick='showImg("{pigcms{$vo}")'><img src="{pigcms{$vo}" style="float:left; padding-left: 2px; width: 70px; height: 70px;" alt=""></a> -->
												<img src="{pigcms{$vo}" style="width:70px; height: 70px;" class="view_msg"/>&nbsp;&nbsp;
											</volist></label>
										</td>
									</tr>

								</if>

							<elseif condition="$order.catgory_type eq 3"/>
								<tr>
									<td width="160">商品分类：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.goods_catgory}</label>
									</td>
								</tr>
								<tr>
									<td width="160">物品重量：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.weight}（KG）</label>
									</td>
								</tr>
								<tr>
									<td width="160">物品价值：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">取件地址：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.start_adress_name}</label>
									</td>
								</tr>
								<tr>
									<td width="160">收货地址：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.end_adress_name}</label>
									</td>
								</tr>
								<if condition="$order.publishInfo.register_phone">
									<tr>
									<td width="160" >客户注册手机：</td>
									<td colspan="3">
										<label>{pigcms{$order.publishInfo.register_phone}</label>
									</td>
									</tr>
								</if>
									<if condition="$order['cat_field_info']['start_adress_phone']">
								<tr>
									<td width="160" >发货人手机：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.start_adress_phone}</label>
									</td>
									</tr>
								</if>
								
								<if condition="$order['cat_field_info']['end_adress_phone']">
								<tr>
									<td width="160" >收货人手机：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.end_adress_phone}</label>
									</td>
									</tr>
								</if>
								<tr>
									<td width="160">取件时间：</td>
									<td colspan="3">
										<label><if condition="$order.cat_field_info.fetch_time">{pigcms{$order.cat_field_info.fetch_time}<else/>立即取件</if></label>
									</td>
								</tr>
								<tr>
									<td width="160">备注：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.remarks}</label>
									</td>
								</tr>
								<tr>
									<td width="160">重量费用：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.weight_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">基础配送费：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.basic_distance_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">超出基础配送费：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.distance_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">小费：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.tip_price}</label>
									</td>
								</tr>
								<tr>
									<td width="160">总价：</td>
									<td colspan="3">
										<label>{pigcms{$order.cat_field_info.total_price}</label>
									</td>
								</tr>
								<if condition="$order.cat_field_info.img">
									<tr>
										<td width="160">商品图片：</td>
										<td colspan="3">
											<label>
												<volist name="order.cat_field_info['img']" id="vo">
													<img src="{pigcms{$vo}" style="width:70px; height: 70px;" class="view_msg"/>&nbsp;&nbsp;
												</volist>
											</label>
										</td>
									</tr>
								</if>
							</if>

							</table>
						</volist>
							<php>}else{</php>
												没有数据
											<php>}</php>
                        </div>
						{pigcms{$pagebar}
					</div>
					<if condition="count($list) gt 1 AND !isset($_GET['page'])">
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
