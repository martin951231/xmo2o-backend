<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta name="format-detection" content="telephone=no"/>
<meta charset="utf-8">
<title>购物车</title>
 
<link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet" />
<link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" /> 
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mall.css"/>
<!-- <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script> -->
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script type="text/javascript">
var save_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=confirm_order";
var open_extra_price =Number("{pigcms{$config.open_extra_price}");
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
var ajax_cart_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart";
var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart_save";

var $home=false;
var guess_you_like = "{pigcms{$config.site_url}/wap.php?c=Mall&a=guess_you_like";
var score_name = "{pigcms{$config['score_name']}";
</script>
<script src="{pigcms{$static_path}js/fastclick.js"></script>
<script src="{pigcms{$static_path}js/common.js"></script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script charset="utf-8">var currency_symbol = "{pigcms{$config.Currency_symbol}";</script>
<script type="text/javascript" src="{pigcms{$static_path}js/mallbuycart.js?t=714"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
<style>
.plus em, .piton span{
	cursor: pointer;
}

/*即将限时优惠标签*/
.seckill_tag {
    background: #ff510c;
    color: #fff;
    font-size: 12px;
    padding: 4px 5px;
    border-radius: 2px;
}

.motify {
    top: 40%;
    left: 50%;
    margin: 0 0 0 -100px;
    
}
.pack_name{
	float:left;
    padding: 10px 0;
}
.endcar dt{
	height:auto;
}
.act_one{
	margin-left:30px;
	padding:3px;
	border:1px solid red;
	border-radius:2px;
}
.act_two{
	margin-left:10px;
}
.love_list{
	padding: 0 5px 100px 5px;
}
.love_listLi{
	float: left;
	margin: 0 1.5%;
	margin-bottom: 20px;
}
.may_til{
	width: 70%;
	color: #FF0000;
	padding: 0 0 20px 0;
	font-size: 15px;
	text-align: center;
	margin: 0 auto;
	display: none;
} 
.love_list .love_listLi .tjt_border_l{
	padding-bottom: 10px!important;
}

.love_list .ui-nowrap-multi .hd{
	 overflow: hidden;
	 height: 42px;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
}
.noDataCart{
	padding: 40px 0;
	text-align: center;
	line-height: 26px;
}
 
.noDataCart a{ display: inline-block; border: #051b28 1px solid; padding: 3px 20px; font-size: 16px; border-radius: 5px; }
.noDataCart p.p1{ color: #051b28; font-size: 15px; line-height: 30px;}
.noDataCart p.p2{ color: #999999; line-height: 30px; } 
</style>
</head>
<body style="background: #eee; ">
	<if condition="$product_list">
	<section class="endcar">
		<ul> 
			<volist name="product_list" id="sto" key="index">
			<php>if ($index == 1) {$store_id = $sto['store_id'];}</php>
			<li>
				<dl>
					<dt>
						<div class="fl piton">
							<span data-store_id="{pigcms{$sto['store_id']}" <if condition="$index eq 1">class="on"</if>></span>
						</div>
						<div class="p30">
							<a href="{pigcms{:U('Mall/store', array('store_id' => $sto['store_id']))}">{pigcms{$sto['name']}</a>
						</div>
						<volist name="sto['pack_act_list']" id="av">
						<div clas="pack_name" style="padding: 5px 0!important;">
							<a href="/wap.php?g=Wap&c=Mall&a=pack_act&id={pigcms{$av['id']}">
								<span class="act_one">{pigcms{$av['pack_name']}</span>
								<span class="act_two">{pigcms{$av['full_text']}</span>
							</a>
						</div>
						</volist>
						
					</dt>
					
					<volist name="sto['goods_list']" id="goods">
					<dd id="{pigcms{$goods['index_key']}" class="goods">
						<div class="fl piton">
							<span data-index_key="{pigcms{$goods['index_key']}" data-store_id="{pigcms{$sto['store_id']}" data-mall_cart_id="{pigcms{$goods['mall_cart_id']}" <if condition="$index eq 1">class="on"</if>></span>
						</div>
						<div class="p30 clr">
							<a href="{pigcms{:U('Mall/detail', array('goods_id' => $goods['goods_id']))}">
								<div class="img fl">
									<img src="{pigcms{$goods['image']}" width="92" height="92">
								</div>
								<div class="p102">
									<div class="conceal">{pigcms{$goods['name']}</div>

                                    <if condition="$goods['is_begin_seckill_price']">
                                        <div class="conceal">
                                            <span class='seckill_tag'>即将限时优惠</span>
                                        </div>
                                    </if>

									<if condition="$goods['status'] eq 0">
									<div class="conceal" style="color: #ff4d45">[已下架]</div>
									</if>
									<div class="cost clr">
										<if condition="$goods.price_type eq 3">
											<span class="fl pricet">{pigcms{$goods['account_score']|floatval}{pigcms{$config.score_name}</span>
										<elseif condition="$goods.price_type eq 2"/>
											<span class="fl pricet">{pigcms{$config['Currency_symbol']}{pigcms{$goods['account_money']|floatval}+{pigcms{$goods['account_score']|floatval}{pigcms{$config.score_name}</span>
										<elseif condition="$goods.price_type eq 1"/>
											<span class="fl pricet">{pigcms{$config['Currency_symbol']}{pigcms{$goods['price']|floatval}</span>
										<else/>
											<span class="fl pricet">{pigcms{$config['Currency_symbol']}{pigcms{$goods['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $goods.extra_pay_price gt 0">+{pigcms{$goods.extra_pay_price}{pigcms{$config.extra_price_alias_name}</if></span>
										</if> 
									</div>
								</div>
							</a>
							<div class="plus clr fr">
								<em href="javascript:void(0)" class="jian" data-goods_id="{pigcms{$goods['goods_id']}" data-price="{pigcms{$goods['price']}" data-stock="{pigcms{$goods['stock_num']}" data-index_key="{pigcms{$goods['index_key']}" data-store_id="{pigcms{$sto['store_id']}" data-extra_pay_price="{pigcms{$goods['extra_pay_price']}">-</em>
								<input type="text" value="{pigcms{$goods['num']}" readonly="readonly">
								<em href="javascript:void(0)" class="jia" data-goods_id="{pigcms{$goods['goods_id']}" data-price="{pigcms{$goods['price']}" data-stock="{pigcms{$goods['stock_num']}" data-index_key="{pigcms{$goods['index_key']}" data-store_id="{pigcms{$sto['store_id']}" data-extra_pay_price="{pigcms{$goods['extra_pay_price']}">+</em>
							</div>
						</div>
					</dd>
					</volist>
					<dd>
						<div class="total clr">
							<div class="fr total_top">小计：<span id="this_total_{pigcms{$sto['store_id']}"></span></div>
							<!--div class="fr total_end">价格明细</div-->
						</div> 
					</dd>
				</dl>
			</li>
			</volist>
		</ul>
	</section> 
	<section class="carbot clr" <if condition="$store_id && 0">style="bottom:0px"</if>>
		<div class="carbot_n clr">
			<div class="fl carbot_left clr">
				<!--div class="fl whole"><span></span>全选</div-->
				<div class="fl close">合计:{pigcms{$config['Currency_symbol']}0</div>
			</div>
			<div class="fr carbot_right clr">
				<div class="fr close" data-store_id="{pigcms{$store_id}">去结算</div>
				<div class="fr whole"></div> 
			</div>
		</div>
	</section>
	
	<!-- 购物单 -->
	<section class="carxq">
		<h2>优惠详情</h2>
		<div class="carxq_ul">
			<ul>
				<li class="clr">
					<span class="fl">商品总价</span>
					<span class="fr red">{pigcms{$config['Currency_symbol']}576</span>
				</li>
				<li class="clr">
					<span class="fl">店铺折扣</span>
					<span class="fr">9折</span>
				</li>
				<li class="clr">
					<span class="fl">折扣后商品总价</span>
					<span class="fr red">{pigcms{$config['Currency_symbol']}576</span>
				</li>
				<li class="clr">
					<span class="fl">包装经费</span>
					<span class="fr red">{pigcms{$config['Currency_symbol']}576</span>
				</li>
				<li class="clr">
					<span class="fl">平台满减优惠</span>
					<span class="fr red">满{pigcms{$config['Currency_symbol']}10,减{pigcms{$config['Currency_symbol']}1{pigcms{$config['Currency_txt']}</span>
				</li>
			</ul>
		</div>
		<div class="meter">
			小计：<span>{pigcms{$config['Currency_symbol']}570</span>
		</div>
		<a href="javascript:void(0)" class="shut">关闭</a>
	</section>
	<div class="mask"></div>
	</if>
	<div class="noDataCart" <if condition="$product_list">style="display:none"</if>>
		<div class=" "  >
			<img src="{pigcms{$static_path}images/car.png" width="100" height="100">
			<p class="p1">购物车快饿瘪了！</p>
			<p class="p2">主人给我挑点东西吃吧！</p>
			<if condition="$store_id">
				<a href="{pigcms{:U('Mall/store',array('store_id'=>$store_id))}">逛一逛</a>
			<else/>
				<a href="{pigcms{:U('Mall/index')}" style="border-color:{pigcms{$config['mobile_head_color']};color:{pigcms{$config['mobile_head_color']}">逛一逛</a>
			</if>
		</div>
	</div> 
	
	<!--猜你喜欢 -->
	<div class="demo-list"  style="padding-bottom: 120px;">
		<div class="may_til"><span>你可能喜欢</span></div>
	   <div class="ui-panel ui-grid ui-grid-trisect pt0 pb0  mb_0" > 
		  <ul  class="clr pb10 love_list">   
			<div class="clear"></div> 
		  </ul> 
		</div>
	   </div>
	<!--猜你喜欢end --> 
	<!-- 底部 -->
	<if condition="empty($store_id)"></if>
	<include file="footer"/>
	
</body>
</html>