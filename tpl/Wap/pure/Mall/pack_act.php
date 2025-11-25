<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8" />
    <title>{pigcms{$info.pack_name}</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
	<script src="{pigcms{$static_path}js/jquery.cookie.js"></script>
	<script>
		var goods_cart = '{pigcms{$goods_cart}';
		var pack_total = '{pigcms{$info.pack_num}';//活动件数
		var have_num   = {pigcms{$have_num};//购买的件数
		var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_pack_cart_save";
	</script>
    <style>
	.top_div{
		width:100%;
		background-color:white;
		margin-bottom:10px;
	}
	#refreshs{
		position: absolute;
		width: 100%;
		overflow: hidden;
		right: 0px;
	}
	.a{
		padding-top:20px;
		line-height:40px;
		text-align:center;
	}
	#selected{
		width:100%;
		text-align:center;
		line-height:30px;
		padding-bottom:10px;
		color:red;
	}
	.pin_num{border: solid 1px #666;
    padding: 4px;}
    .buy{
    	background: #fff;
    	width: 50px;
    	padding: 5px;
    	border-radius: 30px;
    	box-shadow: 3px 2px 5px #888888;
    }
	</style>
</head>
<body>
<!-- 返回顶部按钮 -->
<div id='toTop' class="cursor"><img src="{pigcms{$static_path}mall/images/go_top.png" class="w100 h100" /></div>
<div>
   
    <div class="slideBox content " id="slideBox">
        <div class="top_div">
		<p class="a">以下商品{pigcms{$info.pack_price}元任选{pigcms{$info.pack_num}件</p>
		<p id="selected" style="width:100%;">已选0</p>
		</div>
        <div id='refreshs' class="">
            <div id="ui_list">
                <div >
                    <div class="closeItem  list_all mb_10" id="group_data" >
                        <div class="demo-block">
                            
                            <div class="group">
								
								<div class="demo-block ping_tuan mb_0">
									<ul class="ui-list  mb_0">
										
										<volist name="info.goods_list" id="vo">
										<li class="block  ui-border-t">
											<a href="" class='jump_box' target="_blank"></a>
											<div class="flex flex_baseline" data-url="{pigcms{:U('Mall/detail',array('goods_id'=>$vo['goods_id']))}">
												<div class="ui-list-img-square">
														<span >
														  <img src="{pigcms{$vo.image}" class="w100 h100"/>
													   	</span>
												</div>
												<div class="ui-list-info  flex_one">
													<h4 class="font_size14 mb_8 ellipsis" style="line-height: 18px;">{pigcms{$vo.name}</h4>
													<div class="color_9" style="margin-top:22px">
														剩余： 
														<eq name="vo.stock_num" value="-1">
															<span class=" rel">充足</span>
														<else/>
															<span class=" rel">{pigcms{$vo.stock_num}</span>件
														</eq>
													</div>
													<div class="demo-block" >
														<ul class="ui-list ui-list-single ui-list-link mb_0 ">
															<li class="after ml_0">
																<div class="ui-list-info pd0">
																	<h4 class="ui-nowrap money">{pigcms{$config["Currency_symbol"]}<span class="font_size20">{pigcms{$vo.price}</span></h4>
																	<div class="ui-txt-info font_size14 color_7 ">
																		<empty name="vo.is_added">
																		<a href="javascript:;" data-id="{pigcms{$vo.goods_id}" class="ui-nowrap pin_num inline color_red pack_add_cart">
																			+加入购物车
																		</a>
																		<else/>
																		<a href="javascript:;" data-id="{pigcms{$vo.goods_id}" class="ui-nowrap pin_num inline">
																			已选购
																		</a>
																		</empty>
																	</div>
																</div>
															</li>
															
														</ul>
													</div>
												</div>
											</div>
										</li> 
										</volist>
										<!-- <a class="buy_cart_a" shape="circle" href="{pigcms{:U('mall/cart')}"> -->
										</a>
									</ul>
								</div>
                            </div>
                        </div>
                    </div>            
                </div>
                <div class="buy_car flex_one">
	                <em class="Number">{pigcms{$have_num}</em>
	                <div class="buy">
	                	<a href="{pigcms{:U('Mall/cart')}">
	                		<em class="cart"></em><p>购物车</p>
	                	</a>
	                </div>
	            </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/mallbuygoods.js?12"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/mallpackbuygoods.js?12"></script>
<script>
    $('.flex_baseline').click(function(){
    	window.location.href=$(this).data('url')
    })
</script>
<style>
	.pack_add_cart{
		border:1px solid red;
		padding:2px;
		border-radius:2px;
	}
	.buy_cart_a{
		position: absolute;
		left: 330px;
		top: 320px;
		width:80px;
		height:80px;
		border-radius:40px;
		border:1px solid #E0E0E0;
		background:url("./tpl/Static/default/images/buy_cart.png") no-repeat 10px;6px;;
		
	}
	.buy_car{
		position: fixed;
	    right: 20px;
	    bottom: 150px;
	}
	@media screen and (min-width:320px){
		.buy_car{
			bottom:40px
		}
	}
	@media screen and (min-width:375px){
		.buy_car{
			bottom:120px
		}
	}
	@media screen and (min-width:414px){
		.buy_car{
			bottom:80px
		}
	}
	.buy a{
		color: #777;
	    line-height: 20px;
	    text-align: center;
	    display: block;
	    text-decoration: none;
	    padding-top: 2px;
	    font-size: 12px;
	    position: relative;
	    height: 46px;
	}
	.buy a em.cart {
    	background-image: url(./tpl/Wap/pure/static/mall/images/cart.png);
	}
	.buy a em {
	    margin: 0 auto;
	    display: block;
	    height: 20px;
	    width: 22px;
	    margin-top: 3px;
	    margin-bottom: 2px;
	    background-repeat: no-repeat;
	    background-size: 100%;
	    border-bottom: 0px;
	}
	.buy_car .Number {
	    position: absolute;
	    background: #f23030;
	    font-size: 12px;
	    color: #fff;
	    border-radius: 50px;
	    top: 2px;
	    left: 50%;
	    padding: 0 5px;
	    line-height: 15px;
	    z-index: 1;
	}
<style>
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "http://o2o-static.pigcms.com/upload/config/000/000/001/55efc319ca426.png",
        "sendFriendLink": "http://www.group.com/wap.php?g=Wap&c=Mall&a=home",
        "tTitle": "商城",
        "tContent": "小猪cms生活通o2o系统"
    };
</script>
<!-- 跳转加载的效果 -->
</body>
</html>