<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>{pigcms{$store['name']}</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css"/>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
<script charset="utf-8">var currency_symbol = "{pigcms{$config.Currency_symbol}";</script>
<script type="text/javascript" src="{pigcms{$static_path}js/foodshopmenu.js?t=1" charset="utf-8"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
<script>var store_id = '{pigcms{$store["store_id"]}', all_goods = '{pigcms{$all_goods}', submit_url = '{pigcms{:U("Foodshop/order_detail", array("store_id" => $order["store_id"], "order_id" => $order["order_id"]))}', cookie_index = 'foodshop_cart_{pigcms{$store["store_id"]}_order_{pigcms{$order["order_id"]}';
var  open_extra_price =Number('{pigcms{$config.open_extra_price}');
var saveGoods = '{pigcms{:U("Foodshop/saveGoods", array("orderid" => $order["real_orderid"]))}';
var ajaxSaveGoods = '{pigcms{:U("Foodshop/ajaxSaveGoods", array("orderid" => $order["real_orderid"]))}';
var ajaxGetGoods = '{pigcms{:U("Foodshop/ajaxGetGoods", array("orderid" => $order["real_orderid"]))}';
var searchGoodsUrl = '{pigcms{:U("Foodshop/ajax_search_goods", array("order_id" => $order["real_orderid"],"store_id" => $_GET["store_id"]))}';
</script>
<style>
	/**flex**/
	.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
	.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;}
	.flex_star{align-items: baseline !important;}
	.flex_baseline{align-items: flex-start !important;}
	.flex-end{align-items: flex-end !important;}
	.flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
	.flex_two{  flex:2;  -webkit-box-flex:2;  -webkit-flex:2;  }
	.flex_shree{  flex:3;  -webkit-box-flex: 3;  -webkit-flex: 3;  }
	.flex_col{  flex-direction:column  }
	.mask {
		position: fixed;
		left: 0;
	    top: 0;
	    width: 100%;
	    height: 100%;
	    background-color: rgba(0, 0, 0, .2);
	    z-index: 100;
	}
	.hidden{display: none;}
	.mask div{

		width: 100%;
		height: 250px;
		position: fixed;
		top:50%;
		margin-top: -125px;
		background: #fff;
	}
	.mask img{
		width: 100%;
		height: 250px;
	}
	.rg{float:right;}
	.ft{float:left;}
	.after:after{content:" ";display:block;clear:both;}
	.newAdd p{font-size: 16px;color: #333;}
	.newAdd .user_size{
		width: 97%;
		margin:0 3% 0 0;
		font-size: 12px;
		color: #999;
		padding: 2px 0;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}
	.xuan_food .ft{
		color: #666;
		font-size: 14px;
	}
	.xuan_food .ft span{
		color:#FF6000;
		font-size:16px;
	}
	.xuan_food .rg{
		margin-right: 3%;
		font-size:13px;
		margin-top: 3px;
	}
	.xuan_food .rg span{
		padding: 2px 6px;
		background: #FF6000;
		border-radius:30px;
		color: #fff;

	}
	.Tcancel{ display: none; width: 80%;   background-color: #fff;  position: fixed; top: 0px; left: 0px; height: 350px; margin:10%; border-radius: 5px; top: 50%; margin-top: -175px; z-index: 10001;}
	.Tcancel .Tcancel_top{  font-size: 16px; padding-left: 106px;  padding-top:55px; height: 27px; color: #999999; }
	.TcancelT{ background: #fff ;  }
	.TcancelT_top{ padding: 8px 15px 0 15px; height: 55px;  background: #e7ebee; border-radius: 5px 5px 0 0;  }
	.TcancelT_top .TcancelT_topL{ float: left;  }
	.TcancelT_top a{background: url({pigcms{$static_path}foodshop/img/xx_06.png) right center no-repeat; background-size: 27px; display: block; float: right; width: 27px; height: 27px; margin-top: 10px;}
	.TcancelT_top h2{ font-size: 16px; margin-bottom: 8px; }
	.TcancelT_top span{ color: #ff6000; font-size: 18px; height: 20px; line-height: 20px; }
	.TcancelT_top span i{ font-size: 12px; }
	.TcancelT_zh{ padding: 20px 15px; height: 130px;  overflow-y: auto;  }
	.Tcancel .join{ padding:15px; }
	.Tcancel .join input{ line-height: 40px; background: #2ecc71; color: #ffffff; font-size: 16px; width: 100%; border-radius: 5px; }

	.setmenu{ height: 400px; margin-top: -200px;  }
	.setmenu_list{ padding: 5px; height: 250px; overflow-y: auto; }
	.setmenu_list dd{border: #e6e6e6 1px solid; border-bottom: none;}
	.setmenu_list .condition{  width: 60px; height: 100%; text-align: center; font-size: 14px; color: #ff6000; display: table; }
	.setmenu_list .condition span{ display: table-cell; vertical-align: middle; }
	.setmenu_list .set_list{ overflow: hidden; border-left: #e6e6e6 1px solid;    }
	.setmenu_list .set_list li{ padding: 8px 10px; border-bottom: #e6e6e6 1px solid; padding-right: 25px; line-height: 18px; position: relative; }
	.setmenu_list .set_list li:last-child{ border-bottom: none; }
	.setmenu_list .set_list li:after{ content: ' ';position: absolute; top: 10px; right: 10px; display: block; width: 15px; height: 15px; background: url({pigcms{$static_path}foodshop/img/ad1.png) center no-repeat; background-size: 15px;  }
	.setmenu_list .set_list li.on:after{ background: url({pigcms{$static_path}foodshop/img/ad2.png) center no-repeat; background-size: 15px; }
	.setmenu_list .set_list li h2{ font-size: 12px; color: #666666; }
	.setmenu_list .set_list li p{  color: #ff6000; font-size: 11px; }
	/*.set_list>ul,.condition.fl{
		    border-bottom: #e6e6e6 1px solid;
	}*/
	.mask122{
		width: 100%;
	    background: url(../images/bj_10.png);
	    position: fixed;
	    top: 0px;
	    left: 0px;
	    bottom:0;
	    right: 0;
	    z-index: 10000;
	    display:none;
	    background: rgba(0,0,0,.8);
	}
	.motify{
		z-index: 10002;
	}
    .stock{font-size: 12px !important;color: #999 !important;margin-left: 20px !important;}
    .ui-searchbar-wrap {
	background: #fff;
	display: -webkit-box;
	-webkit-box-pack: center;
	-webkit-box-align: center;
	height: 46px;
	}
	.ui-border-b:before {
	    border-bottom: 1px solid #e9e9e9;
	    content: '';
	    display: block;
	    width: 100%;
	    position: absolute;
	    left: 0;
	    bottom: 0;
	    -webkit-transform-origin: left bottom;
	}
	.tjt-searchbar {
	    border-radius: 300px !important;
	}
	.searcht {
	    background: #f4f4f4;
	    position: relative;
	    top: 0px;
	    left: 0px;
	    width: 100%;
	    padding: 0px;
	    z-index: 99;
	}
	.search:before {
	    border-bottom: 1px solid #e9e9e9;
	    content: '';
	    display: block;
	    width: 100%;
	    position: absolute;
	    left: 0;
	    bottom: 0;
	    -webkit-transform-origin: left bottom;
	    -webkit-transform: scaleY(0.5);
	}
	.flex {
	    display: flex !important;
	    display: -webkit-box;
	    display: -moz-box;
	    display: -ms-flexbox;
	    display: -webkit-flex;
	    align-items: center;
	}
	.searcht_n {
	    padding: 8px 15px;
	    position: relative;
	    background-color: #fff;
   	 	border-bottom: 1px solid #efefef;
	}
	.searcht .se_input {
	    background: #f4f4f4;
	    background-size: 15px;
	    padding-left: 55px;
	    font-size: 14px;
	}
	.se_input {
	    line-height: 1;
	    border: #e6e6e6 1px solid;
	    border-radius: 50px;
	    padding: 8px 0;
	    height: 15px;
	}
	.searcht a {
	    color: #fff;
	    background: #f23030;
	    display: inline-block;
	    font-size: 14px;
	    border-radius: 5px;
	    line-height: 30px;
	    float: right;
	    padding: 0 15px;
	}
	.searcht .se_input {
	    padding-left: 15px !important;
	}
	 .ui-searchbar-wrap {
		    background: #fff;
		    display: -webkit-box;
		    -webkit-box-pack: center;
		    -webkit-box-align: center;
		    height: 46px;
		}
	.ui-border-b:before {
	    border-bottom: 1px solid #e9e9e9;
	    content: '';
	    display: block;
	    width: 100%;
	    position: absolute;
	    left: 0;
	    bottom: 0;
	    -webkit-transform-origin: left bottom;
	}
	.tjt-searchbar {
	    border-radius: 300px !important;
	}
	.ui-searchbar .ui-icon-search {
	    line-height: 30px;
	    margin-right: 4px;
	    display: inline-block;
	    background: url(http://www.group.com/tpl/Wap/pure/static//img/search1.png) no-repeat;
	    width: 14px;
	    height: 14px;
	    margin-top: 8px;
	    background-size: 100%;
	}


	.ui-searchbar {
	    border-radius: 5px;
	    margin: 0 10px;
	    background: #f3f3f3;
	    height: 30px;
	    line-height: 30px;
	    position: relative;
	    padding: 0 12px;
	    -webkit-box-flex: 1;
	    display: -webkit-box;
	    -webkit-box-pack: center;
	    -webkit-box-align: center;
	    color: #bbb;
	    font-size: 14px;
	    width: 100%;
	}
	.ui-searchbar-wrap .ui-searchbar-input, .ui-searchbar-wrap button, .ui-searchbar-wrap .ui-icon-close {
	    display: none;
	}
	.ui-searchbar-input {
	    -webkit-box-flex: 1;
	}
	.ui-searchbar input {
	    -webkit-appearance: none;
	    border: none;
	    background: none;
	    color: #000;
	    width: 100%;
	    padding: 4px 0;
	}
	.ui-searchbar .ui-icon-close {
	    line-height: 30px;
	}
	.ui-icon-close {
	    font-family: "icon-min" !important;
	    font-size: 32px;
	    line-height: 46px;
	    font-style: normal;
	    -webkit-font-smoothing: antialiased;
	    -webkit-text-stroke-width: 0.2px;
	    display: block;
	    color: rgba(0,0,0,0.5);
	    font-size: 12px;
	    color: #777;
	}
	.main {
	   top: 46px;
	}
</style>
</head>
<body>
	<section class="search searcht">
		<div class="searcht_n flex">
			<input type="text" placeholder="请输入商品或店铺名称" class="se_input flex_one" value="" style="width: 235px;">
			<input type="hidden" id="store_id" value="0">
			<a href="javascript:void(0)" id="search" style="margin-left:15px">搜索</a>
		</div>
	</section>
    <div class="main">
		<section class="foodright">
		</section>
    </div>
	
	<div class="Mask"></div>
	<section class="floor clr">
		<div class="trolley"></div>
		<div class="qty">0</div>
		<div class="prix">{pigcms{$config['Currency_symbol']}<i id="total_price">0</i></div>
		<input type="hidden" name="store_id" value="{pigcms{$store['store_id']}" />
		<input type="hidden" name="order_id" value="{pigcms{$order['order_id']}" />
		<input type="button" class="next" value="下一步">
	</section>
	<section class="Cart">
		<div class="Cart_top clr">
			<h2>购物车</h2>
			<span>清空</span>
		</div>
		<div class="Cart_list"><ul></ul></div>
	</section>
	<div class="mask hidden mask_img">
		<div>
			<img src="" alt="" class="mask_img">
		</div>		
	</div>
    <section class="Tcancel setmenu"></section>

    <script id="groupDetailTpl" type="text/html">
    <div class="TcancelT_top clr">
        <div class="TcancelT_topL">
            <h2>{{d.name}}</h2>
            <span class="clr"> <i class="fl">{pigcms{$config['Currency_symbol']}</i> <em class="fl">{{d.price}}</em>
            </span>
        </div>
        <a href="javascript:void(0)" class="gb"></a>
    </div>
    <div class="setmenu_list">
        <dl>
            {{# for (var i in d.goods_detail) { }}
            <dd>
                <div class="condition fl"><span>{{d.goods_detail[i].goods_list.length}}选{{d.goods_detail[i].num}}</span></div>
                <div class="set_list">
                    <ul data-num="{{d.goods_detail[i].num}}">
                        {{# for (var ii in d.goods_detail[i].goods_list) { }}
                        {{# if (d.goods_detail[i].num == 1 && ii == 0) { }}
                        <li class="on" style="cursor: pointer;" data-goods_id="{{d.goods_detail[i].goods_list[ii].goods_id}}" data-unit="{{d.goods_detail[i].goods_list[ii].unit}}" data-price="{{d.goods_detail[i].goods_list[ii].price}}" data-name="{{d.goods_detail[i].goods_list[ii].name}}">
                            <h2>{{d.goods_detail[i].goods_list[ii].name}}</h2>
                        </li>
                        {{# } else { }}
                        <li style="cursor: pointer;" data-goods_id="{{d.goods_detail[i].goods_list[ii].goods_id}}" data-unit="{{d.goods_detail[i].goods_list[ii].unit}}" data-price="{{d.goods_detail[i].goods_list[ii].price}}" data-name="{{d.goods_detail[i].goods_list[ii].name}}">
                            <h2>{{d.goods_detail[i].goods_list[ii].name}}</h2>
                        </li>
                        {{# } }}
                        {{# } }}
                    </ul>
                </div>
            </dd>
            {{# } }}
        </dl>
    </div>
    <div class="join" data-id="{{d.id}}" data-price="{{d.price}}" data-name="{{d.name}}">
        <input type="submit" value="加入菜单" >
    </div>
    </script>
    <script type="text/html" id="menu_list">
       	{{# for (var i in d) { }}
			{{#  var rowset = d[i]; }}
			<dl data-cat_id="{{rowset.sort_id}}" class="foodright-{{rowset.sort_id}}">

            	{{# for (var j in rowset.goods_list) { }}
					{{#  var goods = rowset.goods_list[j] }}

	                {{# if (goods.goods_id == 0) { }}
			            <dd class="goods_ newAdd">
			                <p class="packName">{{goods.name}}</p>
			                <div class="user_size">{{goods.note}}{{goods.note}}</div>
			                <div class="xuan_food after">
			                    <p class="ft"><span>{{rowset.Currency_symbol}}<span class="pacMon">{{goods.price}}</span></span>/份</p>
			                    <p class="rg packageSpeci" data-id="{{goods.id}}" style="cursor: pointer;"><span>选菜品</span></p>
			                </div>
			            </dd>
	                {{# } else { }}
					<dd class="goods_{{goods.goods_id}}">
						<div class="foodr_img">
							<img src="{{goods.pic_arr[0].url.s_image}}">
						</div>
						<div class="food_right">
		                    <h2>{{goods.name}} {{# if (goods.is_table_discount == 1 && rowset.is_open_merchant_foodshop_discount) { }}<span style="float: right;border-radius: 5px;padding: 3px;background: #ff921a;margin-right: 13px;color:white;font-size: 10px;">折</span>{{# } }}</h2>
							<div class="MenuPrice">
								<i>{{rowset.Currency_symbol}}</i>{{goods.price}}
								{{# if (goods.extra_pay_price > 1 && rowset.open_extra_price == 1) { }}
									+<em style="font-size:12px;color:#f03c3c">{{goods.extra_pay_price}}{{rowset.extra_price_alias_name}</em>
								{{# } }}
								<em>/{{goods.unit}}</em>
								{{# if (goods.stock_num < 10 && goods.stock_num != -1) { }}
		                        <em>还剩{{goods.stock_num}}{{goods.unit}}</em>
								{{# } }}
							</div>
							{{# if (goods.spec_list || goods.properties_list) { }}
								<div class="Addsub">
									<span class="Speci">选规格</span>
								</div>
	                		{{# } else { }}
				                <div class="Addsub">
				                    <a href="javascript:void(0)" class="jian" data-stock_num="{{goods.stock_num}}" data-price="{{goods.price}}" data-id="{{goods.goods_id}}" data-index="{{goods.goods_id}}" data-name="{{goods.name}}" data-extra_pay_price="{{goods.extra_pay_price}}" data-extra_price_name = "{{rowset.extra_price_alias_name}}"></a>
									<input type="text" value="0" readOnly="true" class="num">
				                    <a href="javascript:void(0)" class="jia" data-stock_num="{{goods.stock_num}}" data-price="{{goods.price}}" data-id="{{goods.goods_id}}" data-index="{{goods.goods_id}}" data-name="{{goods.name}}" data-extra_pay_price="{{goods.extra_pay_price}}" data-extra_price_name = "{{rowset.extra_price_name}}"></a>
								</div>
							{{# } }}
						</div>
						{{# if (goods.spec_list || goods.properties_list) { }}
							<section class="Tcancel TcancelT">
								<div class="TcancelT_top clr">
									<div class="TcancelT_topL">
										<h2>{{goods.name}}</h2>
										<span class="price"><i>{{rowset.Currency_symbol}}</i>{{goods.price}}</span><span class="stock">
										{{# if (goods.stock_num < 10 && goods.stock_num >= 0) { }}剩余：{{goods.stock_num}}{{# } }}</span>
									</div>
									<a href="javascript:void(0)" class="gb"></a>
								</div>
								<div class="TcancelT_zh">
									<div class="TcancelT_n">
	            						{{# for (var k in goods.spec_list) { }}
										{{# var spec_r = goods.spec_list[k] }}
										<div class="TcancelT_list">
											<h2>{{spec_r.name}}</h2>
											<div class="fications" data-id="{{spec_r.id}}" data-num="1" data-name="{{spec_r.name}}" data-type="spec">
												<ul class="clr" >
	            									{{# for (var l in spec_r.list) { }}
	            									{{# var srow = spec_r.list[l] }}
													<li data-id="{{srow.id}}" data-name="{{srow.name}}" data-type="spec" data-goods_id="{{goods.goods_id}}">{{srow.name}}</li>
													{{# } }}
												</ul>
											</div>
										</div>
										{{# } }}

	            						{{# for (var k in goods.properties_list) { }}
	            						{{# var pro_r = goods.properties_list[k]}}
										<div class="TcancelT_list">
											<h2>{{pro_r.name}}</h2>
											<div class="fications" data-id="{{pro_r.id}}" data-name="{{pro_r.name}}" data-num="{{pro_r.num}}" data-type="properties" id="properties_{{pro_r.id}}">
												<ul class="clr" >
	            									{{# for (var l in pro_r.val) { }}
	            									{{# var val = pro_r.val[l]}}
													<li data-id="{{l}}" data-name="{{val}}" data-type="properties" data-goods_id="{{goods.goods_id}}">{{val}}</li>
													{{# } }}
												</ul>
											</div>
										</div>
										{{# } }}
									</div>
								</div>
								<div class="Selected">
									已选：<span></span>
								</div>
								<div class="join" data-stock_num="{{goods.stock_num}}" data-goods_id="{{goods.goods_id}}" data-name="{{goods.name}}" data-price="{{goods.price}}">
									<input type="button" value="加入菜单">
								</div>
							</section>
						{{# } }}
					</dd>
	                {{# } }}
	       			
                {{# } }}
			</dl>
        {{# } }}
    </script>
</body>
</html>