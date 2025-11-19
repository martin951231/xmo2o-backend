<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<title>{pigcms{$config.mall_alias_name}{pigcms{:L_("订单列表")}</title>
	<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
	<link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
	<link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
	<style>
		#pageLoadTip{display:none !important;}
		dl.list dd dl{ padding-left:0;}
		.dealcard-img{ margin-left:.2rem}
		dl.list dd{ border:none;}
		dl.list dd.dealcard {
			overflow: visible;
			-webkit-transition: -webkit-transform .2s;
			position: relative;
			background:rgba(242, 242, 242, 0.86);
		}
		.dealcard.orders-del {
			-webkit-transform: translateX(1.05rem);
		}
		.dealcard-block-right {
			height: 1.5rem;
		}
		.dealcard .dealcard-brand {
			margin-top:.18rem;
			margin-bottom: .18rem;
			heigth:.5rem
		}
		.dealcard small {
			font-size: .24rem;
			color: #9E9E9E;
		}
		.dealcard weak {
			font-size: .24rem;
			color: #999;
			position: absolute;
			bottom: .15rem;
			left: 0;
			display: block;
			width: 100%;
		}
		.dealcard weak b {
			color: #FDB338;
		}
		.dealcard weak a.btn{
			margin: -.15rem 0;
		}
		.dealcard weak b.dark {
			color: #fa7251;
		}
		.hotel-price {
			color: #ff8c00;
			font-size: .24rem;
			display: block;
		}
		.del-btn {
			display: block;
			width: .45rem;
			height: .45rem;
			text-align: center;
			line-height: .45rem;
			position: absolute;
			left: -.85rem;
			top: 50%;
			background-color: #EC5330;
			color: #fff;
			-webkit-transform: translateY(-50%);
			border-radius: 50%;
			font-size: .4rem;
		}
		.no-order {
			color: #D4D4D4;
			text-align: center;
			margin-top: 1rem;
			margin-bottom: 2.5rem;
		}
		.icon-line {
			font-size: 2rem;
			margin-bottom: .2rem;
		}
		.orderindex li {
			float: left;
			width: 20%;
			text-align:center;
			position: relative;
		}

		.orderindex li.active {
			color:#06c1bb
		}

		.orderindex li .react {
			padding: .28rem 0;
		}
		.orderindex .text-icon {
			display: block;
			font-size: .4rem;
			margin-bottom: .18rem;
		}
		.orderindex .amount-icon {
			position: absolute;
			left: 50%;
			top: .16rem;
			color: white;
			background: #EC5330;
			border-radius: 50%;
			padding: .08rem .06rem;
			min-width: .28rem;
			font-size: .24rem;
			margin-left: .1rem;
			display: none;
		}
		.order-icon {
			display: inline-block;
			width: .5rem;
			height: .5rem;
			text-align: center;
			line-height: .5rem;
			border-radius: .06rem;
			color: white;
			margin-right: .25rem;
			margin-top: -.06rem;
			margin-bottom: -.06rem;
			background-color: #F5716E;
			vertical-align: initial;
			font-size: .3rem;
		}
		.order-all {
			background-color: #2bb2a3;
		}
		.order-zuo,.order-jiudian {
			background-color: #F5716E;
		}
		.order-fav {
			background-color: #0092DE;
		}
		.order-card {
			background-color: #EB2C00;
		}
		.order-lottery {
			background-color: #F5B345;
		}
		.color-gray{
			color:gray;
			border-color:gray;
		}
		.color-gray:active{
			background-color:gray;
		}
		.orderindex li .react.hover{
			color:#FF658E;
		}
		.tabs {
			z-index: 15;
			position: relative;
			background: #FFFFFF;
			box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
			box-sizing: border-box;
			overflow: hidden;
		}
		.tabs-header {
			position: relative;
			overflow: hidden;
		}
		.tabs-header .border {
			position: absolute;
			bottom: 0;
			left: 0;
			background: #06c1bb;
			width: auto;
			height: 2px;
			width:25%;
			-webkit-transition: 0.3s ease;
			transition: 0.3s ease;
		}

		.tabs-content .tab {
			display: none;
		}
		.tabs-content .tab.active {
			display: block;
		}

		.order-num{ height:1rem; line-height:1rem; color:#9E9E9E; padding-left:.2rem}
		.order-num p.right_nav{ display:block; float:right; margin-right:.2rem;height: 1rem;line-height: 1rem;}
		.order-num p.right_nav span{color: #06c1bb;margin-right:.2rem;display:inline-block;}
		.order-num p.right_nav a{display: inline-block;float: none;}
		.order-num a.top_nav{color: #9E9E9E;display: inline-block;float: none;}
		.order-num a{ display:block; float:right; margin-right:.1rem}
		.order-num-foot span:first-child{display:block; float:left; color:#06c1bb; line-height:1rem}
		.order-num-foot span:last-child,.order-pay,.order-cancel{border:1px solid #e5e5e5;color:#666; padding:.2rem; border-radius:4px; display:block; float:right;line-height:.3rem; margin:.1rem .1rem 0 0; }
		.order-num-foot span.order-pay{ color:#06c1bb;padding:.2rem .5rem}
		.pageHide{position:fixed;width:100%;height:100%;z-index:100000;background:#fff}
		.explice{
			flex: 1;
			color: #999;
			font-size: 13px;
			line-height: 1.2;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-box-orient: vertical;
			-webkit-line-clamp: 4;
		}
		.swiper-slide{
			width: 1.88rem;
			height: 1.68rem;}
	</style>
</head>
<body id="index">
<div class="pageHide"></div>
<div id="tips" class="tips"></div>
<dl class="list" style="margin-top:0px;">
	<dd>
		<div class="tabs">
			<div class="tabs-header">
				<div class="border"></div>
				<ul class="orderindex">
					<li class="active">
						<a href="javascript:void(0)" tab-id="1" class="react">
							<span>{pigcms{:L_("全部")}</span>
						</a>
					</li>
					<li data-status='-1'>
						<a href="javascript:void(0)" tab-id="2" class="react ">
							<span>{pigcms{:L_("待付款")}</span>
						</a>
					</li>
					<li data-status='3'>
						<a href="javascript:void(0)" tab-id="3" class="react ">
							<span>{pigcms{:L_("待发货")}</span>
						</a>
					</li>
					<li data-status='4'>
						<a href="javascript:void(0)" tab-id="4" class="react ">
							<span>{pigcms{:L_("待收货")}</span>
						</a>
					</li>
					<li data-status='2'>
						<a href="javascript:void(0)" tab-id="5" class="react " >
							<span>{pigcms{:L_("待评价")}</span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</dd>
</dl>
<div style="margin-top:.2rem;">
	<dl class="list tabs-content" id="orders">
		<div tab-id="1" class="tab active">
		</div>
		<div tab-id="2" class="tab"></div>
		<div tab-id="3" class="tab"></div>
		<div tab-id="4" class="tab"></div>
		<div tab-id="5" class="tab"></div>
	</dl>
</div>
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
<script type="text/html" id="list">
	<dd>
		<& for(var i=0;i< _this.length;i++) {&>
		<& var _item=_this[i] &>
		<dl class="dom_<&= _item.order_id &>">
			<dd class="order-num">
				<a href="{pigcms{:U('Mall/store')}&store_id=<&= _item.store_id &>" class='top_nav app_new_link'>
					<img src="{pigcms{$static_path}mall/images/shop.png" style="width: 20px;height: 20px;margin-right: 5px;margin-top: -4px;"><&= _item.name&>&nbsp;&gt;
				</a>
				<span></span>
				<p class='right_nav'>
					<& if(_item.status==0&&_item.order_status==0){ &>
						<span>{pigcms{:L_("未确认")}</span>
					<& }else if(_item.status==0&&_item.order_status==1){&>
						<span>{pigcms{:L_("已接单")}</span>
					<& }else if(_item.status==1){&>
						<span>{pigcms{:L_("已确认")}</span>
					<&}else if(_item.status==2){&>
						<span >{pigcms{:L_("待评价")}</span>
					<&}else if(_item.status==3){&>
						<& if(_item.goods_type_num>1){&>
							<span>{pigcms{:L_("已评价")}</span>
						<&}else{&>
							<span>{pigcms{:L_("已评价")}</span>
						<&}&>
					<&}else if(_item.status==4){&>
						<span>{pigcms{:L_("已退款")}</span>
					<&}else if(_item.status==5){&>
						<span>{pigcms{:L_("已取消")}</span>
					<&}else if(_item.status==6){&>

					<&}else if(_item.status==7){&>
						<span>{pigcms{:L_("分配到自提点")}</span>
					<&}else if(_item.status==8){&>
						<span>{pigcms{:L_("发货到自提点")}</span>
					<&}else if(_item.status==9){&>
						<span>{pigcms{:L_("自提点接货")}</span>
					<&}else if(_item.status==10){&>
						<span>{pigcms{:L_("自提点发货")}</span>
					<&}else if(_item.status==11){&>
						<span>{pigcms{:L_("正在退款中")}</span>
					<&}&>

					<& if(_item.paid==0 || _item.status==2 || _item.status==3){&>
						<a href="javascript:void(0)" onclick="del_order(<&= _item.order_id&>)">
							<img src="{pigcms{$static_path}images/u282.png">
						</a>
					<&}&>
				</p>				
			</dd>
			<& if(_item.goods_list){ &>
				<& if(_item.goods_list.length==1){ &>
					<dd class="dealcard dd-padding"  style="align-items: center;display: flex;">
						<a href="<&= _item.order_url&>" class="app_new_link" style="align-items: center; display: flex;  flex: 1;">
							<div class="dealcard-img imgbox" style="position: relative;margin-right:10px">
								<img src="<&= _item.goods_list['0']['goods_image'] &>" style="width:100%;height:100%;-webkit-transform: none;position: absolute; left: 0; top:0">
							</div>
							<div class="explice" ><&= _item.goods_list['0']['name'] &></div>
						</a>
					</dd>
				<& }else{ &>
					<a href="<&= _item.order_url&>" class="app_new_link">
						<dd class="dealcard dd-padding " >
							<div class="swiper-container <&= window.back(_item.order_id)&>">
								<div class="swiper-wrapper">
									<& for(var j=0;j<_item.goods_list.length;j++){ &>
										<div class="swiper-slide">
											<img src="<&= _item.goods_list[j]['goods_image'] &>" style="width:100%;height:100%;-webkit-transform: none; position: absolute; left: 0; top:0">
										</div>
									<& } &>
								</div>
							</div>
						</dd>
					</a>
				<& } &>
			<& } &>
			<dd class="order-num " style="text-align: right;  padding-right: 10px;color:#000">
				<span>{pigcms{:L_("共")}<&= _item.num&>{pigcms{:L_("件商品")}  &nbsp;&nbsp;</span>
			</dd>
			<div class="timer" style="width: 100%; text-align: right;padding-right: 10px;box-sizing: border-box;margin-bottom: 17px;display: flex;align-items: center;justify-content: flex-end;font-size: inherit;color: #000000;">
				<span>支付时间：202206654666&nbsp;&nbsp;</span>
			</div>
			<& if((_item.paid==0 && _item.pay_type=='' && (_item.status<2 || _item.status==7 )) || _item.status==2){ &>
				<dd class="order-num order-num-foot">
					<& if(_item.paid==0 && (_item.status<2 || _item.status==7 )){&>
						<a href="javascript:;" class="app_new_link">
							<span data-order_id="<&= _item.order_id&>" class="order-pay">{pigcms{:L_("付款")}</span>
						</a>
					<&}&>
					<& if(_item.status==2 ){&>
						<a href="{pigcms{:U('My/mall_feedback')}&order_id=<&= _item.order_id&>" class="app_new_link">
							<span >{pigcms{:L_("去评价")}</span>
						</a>
					<&}&>
					<& if(_item.status!=3 ){&>
						<a></a>
					<&}&>
				</dd>
			<& } &>
		</dl>
		<div class="dom_<&= _item.order_id &>" style="height:10px;background:#f0efed"></div>
		<& }&>
	</dd>
</script>
<script type="text/javascript" language="javascript">
	window.onload=function(){
		var _this=this;
		var is_index=true;
		var type="{pigcms{$_GET.type}";
		var index=0;
		function $click(){
			if(type==1){	//待付款
				$('.tabs-header a').eq(1).click();
			}else if(type==2){
				$('.tabs-header a').eq(2).click();
			}else if(type==3){
				$('.tabs-header a').eq(3).click();
			}else if(type==4){	//待评价
				$('.tabs-header a').eq(4).click();
			}else{
				$('.tabs-header a').eq(0).click();
			}
		}
		$('.pageHide').bind("touchmove",function(e){
			e.preventDefault();
		});
		var list=[];
		window.back=function(id){
			list.push("swiper-container-"+index+"-"+id)
			return "swiper-container-"+index+"-"+id;

		}
		show()
		if(/(android)/.test(navigator.userAgent.toLowerCase()) && (/(micromessenger)/.test(navigator.userAgent.toLowerCase()))){

			window.onpopstate = function(event) {
				sessionStorage.removeItem('mall_order_list_index');
				sessionStorage.removeItem('mall_order_list_top');
				window.history.back()
			};
			//加入以下俩行代码，才能触发
			window.history.pushState(null, null, '#');
			window.history.forward(1);
			$(window).on("scroll", function(){
				sessionStorage.setItem("mall_order_list_top", $(window).scrollTop());
			})
			var $shop_top = sessionStorage.getItem("mall_order_list_top");
			var $shop_order_list_index = sessionStorage.getItem("mall_order_list_index");
			if(!$shop_top || isNaN($shop_top*1)){
				$(".pageHide").hide()
				$click()
			}else{
				if(isNaN($shop_order_list_index*1)){
					$click()
					$("html,body").animate({"scrollTop":$shop_top*1},10,function(){
						$(".pageHide").hide()
					});

				}else{
					is_index=false;
					$('.tabs-header li').eq($shop_order_list_index*1).find("a").trigger('click')
				}

			}

		}else{
			$(".pageHide").hide()
			$click()
		}

		$(document).on('click','.app_new_link',function(e){
			e.preventDefault();
			e.stopPropagation();
			var url = $(this).attr('href');
			if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
				url = window.location.protocol + '//' + document.domain + url;
			}
			if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
				var reg = /versioncode=(\d+),/;
				var arr = reg.exec(navigator.userAgent.toLowerCase());
				if(arr == null){
					
				}else{
					var version = parseInt(arr[1]);
					if(version >= 1400){
						if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
							var iosUrl = window.btoa(url);
							iosUrl = iosUrl.replace('/','&');
							window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'createwebview', url:iosUrl, webUrl: url});
							return false;
						}else{
							window.lifepasslogin.createwebview(url);
							return false;
						}
					}
				}
			}
			location.href = url;
		});
		
		function show(){
			var activePos = $('.tabs-header .active').position();
			var url = "{pigcms{:U('My/ajax_shop_order_list',array('get_goods'=>1))}"
			function changePos() {
				activePos = $('.tabs-header .active').position();
				$('.border').stop().css({
					left: activePos.left,
					width: $('.tabs-header .active').width()
				});

			}
			changePos();
			var tabHeight = $('.tab.active').height();
			function animateTabHeight() {
				tabHeight = $('.tab.active').height();
				$('.tabs-content').stop().css({ height: tabHeight + 'px' });
			}
			var tabItems = $('.tabs-header ul li');
			var tabCurrentItem = tabItems.filter('.active');
			$('.tabs-header a').on('click', function (e) {
				e.preventDefault();
				index++;
				var tabId = $(this).attr('tab-id');
				sessionStorage.setItem("mall_order_list_index", parseInt(tabId-1));
				$('.tabs-header a').stop().parent().removeClass('active');
				$(this).stop().parent().addClass('active');
				changePos();
				tabCurrentItem = tabItems.filter('.active');
				$('.tab').stop().fadeOut(300, function () {
					$(this).removeClass('active');
				}).hide();
				$('.tab[tab-id="' + tabId + '"]').stop().fadeIn(300, function () {
					var status = $('.tabs-header ul li.active').data('status');
					if(list.length>0){
						for( x in list){
							list[x].destroy()
						}
						list=[];
					}
					$.get(url,{'status':status,'store_id':{pigcms{$_GET.store_id|intval},'is_mall':1},function(data){
							if(data.status){
								var tp = Simplite.getTemplate('list');
								var compiledTpl = Simplite.compile(tp)(data.order_list );
								$('.tab[tab-id="' + tabId + '"]').html(compiledTpl);
								for( x in list){
									list[x] = new Swiper('.'+list[x], {
										slidesPerView : "auto",
										spaceBetween : 20,
									});
								}

								var shtml = '<dd>';
								var order_list = data['order_list'];
								for(var i in order_list){
									shtml += '<dl class="dom_'+order_list[i]["order_id"]+'"><dd class="order-num">{pigcms{:L_("订单编号")}：<span>'+order_list[i]["real_orderid"]+'</span>';
									if((order_list[i]['paid'] == 0)){
										shtml +='<a href="javascript:void(0)" onclick="del_order('+order_list[i]["order_id"]+')"><img src="{pigcms{$static_path}images/u282.png"></a>';
									}
									if((order_list[i]['status']==2) || (order_list[i]['status']==3)){
										shtml +='<a href="javascript:void(0)" onclick="del_order('+order_list[i]["order_id"]+')"><img src="{pigcms{$static_path}images/u282.png"></a>';
									}
									shtml += '</dd>';
									shtml += '<dd class="dealcard dd-padding" onclick="window.location.href = \''+order_list[i]['order_url']+"&from=mall_order_list"+'\';">';
									shtml += '<div class="dealcard-img imgbox">';
									shtml += '<img src="'+order_list[i]['image']+'" style="width:100%;height:100%;"/>';
									shtml += '</div>';
									shtml += '<div class="dealcard-block-right">';
									shtml += '<div class="dealcard-brand single-line">'+order_list[i]['name']+'</div>';
									shtml += '<small>{pigcms{:L_("数量")}：'+order_list[i]['num']+'&nbsp;&nbsp;{pigcms{:L_("总价")}：'+order_list[i]['price']+' {pigcms{$config['Currency_txt']}</small>';

									shtml += '</div></dd><dd class="order-num order-num-foot">	';

// 								var url = "{pigcms{:U('My/shop_order_refund')}";
// 								url +='&order_id='+order_list[i]['order_id']+'&mer_id='+order_list[i]['mer_id']+'&store_id='+order_list[i]['store_id'];


									if(order_list[i]['status']==0){
										shtml += '<span>{pigcms{:L_("未确认")}</span>';
									}else if(order_list[i]['status']==1){
										shtml += '<span>{pigcms{:L_("已确认")}</span>';
									}else if(order_list[i]['status']==2){
										var url = "{pigcms{:U('My/mall_feedback')}";
										url +='&order_id='+order_list[i]['order_id'];
										shtml +='<span onclick="location.href=\''+url+'\'">{pigcms{:L_("去评价")}</span>';
									}else if(order_list[i]['status']==3){
										if(order_list[i]['goods_type_num']>1){
											var url = "{pigcms{:U('My/mall_feedback')}";
											url += '&order_id='+order_list[i]['order_id'];
											shtml +='<span onclick="location.href=\''+url+'\'">{pigcms{:L_("已评价")}</span>';
										}else{
											shtml += '<span>{pigcms{:L_("已评价")}</span>';
										}
									}else if(order_list[i]['status']==4){
										shtml += '<span>{pigcms{:L_("已退款")}</span>';
									}else if(order_list[i]['status']==5){
										shtml += '<span>{pigcms{:L_("已取消")}</span>';
									}else if(order_list[i]['status']==6){
									}else if(order_list[i]['status']==7){
										shtml += '<span>{pigcms{:L_("分配到自提点")}</span>';
									}else if(order_list[i]['status']==8){
										shtml += '<span>{pigcms{:L_("发货到自提点")}</span>';
									}else if(order_list[i]['status']==9){
										shtml += '<span>{pigcms{:L_("自提点接货")}</span>';
									}else if(order_list[i]['status']==10){
										shtml += '<span>{pigcms{:L_("自提点发货")}</span>';
									}


									if(order_list[i]['paid'] == 0 && (order_list[i]['status'] < 2 || order_list[i]['status'] == 7)){
										var url = "{pigcms{:U('Pay/check')}";
										url += '&type=mall&order_id='+order_list[i]['order_id'];
										shtml +='<span onclick="location.href=\''+url+'\'" class="order-pay">{pigcms{:L_("付款")}</span>';
									}else if(order_list[i]['status'] == 2){
										var url = "{pigcms{:U('My/mall_feedback')}";
										url += '&order_id='+order_list[i]['order_id'];
										shtml +='<span onclick="location.href=\''+url+'\'">{pigcms{:L_("评价")}</span>';
									}else if(order_list[i]['status'] != 3){
										shtml+='<a></a>';
									}
									shtml +='</dd></dl><div class="dom_'+order_list[i]["order_id"]+'" style="height:10px;background:#f0efed"></div>';
								}
								//$('.tab[tab-id="' + tabId + '"]').html(shtml);
							}else{
								var shtml ='<dd><dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">{pigcms{:L_("暂无订单")}</dd></dd>';
								$('.tab[tab-id="' + tabId + '"]').html(shtml);
							}

							if(!is_index){
								scroollShow()
							}
						},'json')
				});
			});

		}

	}
	function del_order(order_id){
		if(!order_id){
			return false;
		}
		layer.open({
			content:'{pigcms{:L_("确认删除？")}',
			btn: ['{pigcms{:L_("确定")}','{pigcms{:L_("取消")}'],
			yes:function(layer_index){
				var del_url = "{pigcms{:U('My/ajax_shop_order_del')}";
				$.get(del_url,{'order_id':order_id},function(data){
					if(data['status']){
						$('.dom_'+order_id).remove();
						layer.close(layer_index);
						layer.open({
							content:'{pigcms{:L_("删除成功")}',
							btn: ['{pigcms{:L_("好的")}']
						});
					}
				},'json');
			}
		});
	}
	function scroollShow(){
		is_index=true;
		$("html,body").animate({"scrollTop":$shop_top*1},10,function(){
			$(".pageHide").hide()
		});
	}
	
	$("body").delegate(".order-pay","click",function(){
		var order_info = $(this).data();

		if(window.__wxjs_environment === 'miniprogram' || (/(micromessenger)/.test(navigator.userAgent.toLowerCase()) && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
			wx.miniProgram.navigateTo({
				url:'/pages/shop_new/confirmOrder/pay?order_id='+order_info.order_id+'&type=mall'
			});
		}else{
			location.href = "{pigcms{:U('Pay/check',array('type'=>'mall'))}&order_id="+order_info.order_id;
		}
		return false;
	});
</script>
<!--include file="Public:footer"/-->
{pigcms{$hideScript}
</body>
</html>