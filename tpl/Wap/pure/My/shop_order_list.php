<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>{pigcms{:L_("X1订单",$config['shop_alias_name'])}</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <style>
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
	        display: inline-block;
	        width: 24%;
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
		.order-num a{ display:block; float:right; margin-right:.1rem}
		.order-num-foot span:first-child{display:block; float:left; color:#06c1bb; line-height:1rem}
		.order-num-foot span:last-child,.order-pay,.order-cancel{border:1px solid #e5e5e5;color:#666; padding:.2rem; border-radius:4px; display:block; float:right;line-height:.3rem; margin:.1rem .1rem 0 0; }
		.order-num-foot span.order-pay{ color:#06c1bb;padding:.2rem .5rem}
		.pageHide{position:fixed;width:100%;height:100%;z-index:100000;background:#fff}
		
		.headerBox{
			margin-top:0px!important;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			background: white;
			z-index: 999;
		}
		.reply-mask{
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			z-index: 1000;
			background: rgba(0, 0, 0, .6);
			-webkit-box-pack: center;
			-moz-box-pack: center;
			-ms-flex-pack: center;
			-webkit-justify-content: center;
			justify-content: center;
			-webkit-box-align: center;
			-moz-box-align: center;
			-ms-flex-align: center;
			-webkit-align-items: center;
			align-items: center;
		}
		.flex{
			display: -webkit-box;
			display: -moz-box;
			display: -webkit-flex;
			display: -ms-flexbox;
			display: flex;
		}
		
		.reply-mask .mask-content{
			width: 70%;
			padding: 20px;
			background: #fff;
			text-align: center;
			border-radius: 2px;
		}
		
		.reply-mask .img-box .img{
			width: 60px;
			padding: ;
		}
		
		.reply-mask .desc{
			padding: 10px 0;
			word-break: break-all;
			text-align: left;
		}
		
		.reply-mask .btn-box{
			-webkit-box-pack: justify;
			-moz-box-pack: justify;
			-ms-flex-pack: justify;
			-webkit-justify-content:space-between;
			justify-content:space-between;
		}
		
		.reply-mask .btn-box button{
			border: 1px solid #efefef;
			width: 45%;
			line-height: 2.5;
			background: #fff;
			border-radius: 4px;
		}
		
		.reply-mask .btn-box button.right{
			background: rgb(6, 193, 174);
			color: #fff;
		}
		.reply-mask .btn-box button.left:active{
			background: #f2f2f2;
		}
		.reply-mask .btn-box button.right:active{
			opacity: 0.85;
		}
		
	</style>
</head>
<body id="index" >
		<div class="pageHide"></div>
		<div id="tips" class="tips"></div>
		<dl class="list headerBox">
			<dd>
				<div class="tabs">
					<div class="tabs-header">
						<div class="border" style='background:{pigcms{$config["mobile_head_color"]}'></div>
						<ul class="orderindex">
							<li class="active" style='color:{pigcms{$config["mobile_head_color"]}'>
								<a href="javascript:void(0)" tab-id="1" class="react">
									<span>{pigcms{:L_("全部")}</span>
								</a>
							</li>
							<li data-status='-1'>
								<a href="javascript:void(0)" tab-id="2" class="react ">
									<span>{pigcms{:L_("待付款")}</span>
								</a>
							</li>
							<li data-status='1'>
								<a href="javascript:void(0)" tab-id="3" class="react ">
									<span>{pigcms{:L_("未消费")}</span>
								</a>
							</li>
							<li data-status='2'>
								<a href="javascript:void(0)" tab-id="4" class="react " >
									<span>{pigcms{:L_("待评价")}</span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</dd>
		</dl>
		<div class="listBox">
			<dl class="list tabs-content" id="orders">
				<div tab-id="1" class="tab active"></div>
				<div tab-id="2" class="tab"></div>
				<div tab-id="3" class="tab"></div>
				<div tab-id="4" class="tab"></div>
			</dl>
		</div>
		
		<if condition="$order_reply">
		<div class="flex reply-mask">
			<div class="mask-content">
				<div class="img-box">
					<img class="img" src="{pigcms{$static_path}images/reply.png" alt="">
				</div>
				<div class="desc">{pigcms{$order_reply}</div>
				<div class="flex btn-box">
					<button class="left">{pigcms{:L_("取消")}</button>
					<button class="right" style="background:{pigcms{$config['mobile_head_color']}">{pigcms{:L_("去评价")}</button>
				</div>
			</div>
		</div>
		</if>

		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" language="javascript">
			var $shop_top = 0;
			window.onload=function(){
				
				$(document).on('click', '.reply-mask .btn-box .left, .reply-mask', function(){
					$('.reply-mask').hide()
				})
				$(document).on('click', '.mask-content', function(){
					return false
				})
				$(document).on('click', '.reply-mask .btn-box .right', function(){
					location.href = '/wap.php?g=Wap&c=My&a=shop_feedback&order_id={pigcms{$order_reply_id}'
				})
				
				$('.listBox').css('margin-top', $('.headerBox').height() + 10);
				var _this=this;
				var main_color = '{pigcms{$config["mobile_head_color"]}'; 
				var is_index=true;
				$('.pageHide').bind("touchmove",function(e){
					e.preventDefault();
				});
				show();
				if(/(android)/.test(navigator.userAgent.toLowerCase()) && (/(micromessenger)/.test(navigator.userAgent.toLowerCase()))){
					window.onpopstate = function(event) {
						sessionStorage.removeItem('shop_order_list_index');
						sessionStorage.removeItem('shop_order_list_top');
						window.history.back()
					};
					//加入以下俩行代码，才能触发
					window.history.pushState(null, null, '#');
					window.history.forward(1);
					$(window).on("scroll", function(){
						sessionStorage.setItem("shop_order_list_top", $(window).scrollTop());
					})
					var $shop_top = sessionStorage.getItem("shop_order_list_top");
					var $shop_order_list_index = sessionStorage.getItem("shop_order_list_index");
					if(isNaN($shop_top*1)){
						$(".pageHide").hide()
					}else{
						if(isNaN($shop_order_list_index*1)){
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
				}
				function show(){
					 var activePos = $('.tabs-header .active').position();
					 var url = "{pigcms{:U('ajax_shop_order_list')}"
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
						 var tabId = $(this).attr('tab-id');
						 sessionStorage.setItem("shop_order_list_index", parseInt(tabId-1));
						 $('.tabs-header a').stop().parent().removeClass('active');
						 $('.tabs-header ul li').css('color', '');
						 $(this).stop().parent().addClass('active').css('color', main_color);
						 
						var shtml ='<dd><dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">{pigcms{:L_("加载中...")}</dd></dd>';
						$('.tab[tab-id="' + tabId + '"]').html(shtml);
						 
						 changePos();
						 tabCurrentItem = tabItems.filter('.active');
						 $('.tab').stop().fadeOut(300, function () {
							 $(this).removeClass('active');
						 }).hide();

						 $('.tab[tab-id="' + tabId + '"]').stop().fadeIn(300, function () {
							 var status = $('.tabs-header ul li.active').data('status');
							 $.get(url,{'status':status, 'store_id':{pigcms{$_GET.store_id|intval}, 'mer_id':{pigcms{$_GET.mer_id|intval}},function(data){
									 if(data.status){
										 var shtml = '<dd>';
										 var order_list = data['order_list'];
										 for(var i in order_list){
											 shtml += '<dl><dd class="order-num">{pigcms{:L_("订单编号")}：<span>'+order_list[i]["real_orderid"]+'</span>';
											 if((order_list[i]['paid'] == 0)){
												 shtml +='<a href="javascript:void(0)" onclick="del_order('+order_list[i]["order_id"]+')"><img src="{pigcms{$static_path}images/u282.png"></a>';
											 }
											 if((order_list[i]['status']==2) || (order_list[i]['status']==3)){
												 shtml +='<a href="javascript:void(0)" onclick="del_order('+order_list[i]["order_id"]+')"><img src="{pigcms{$static_path}images/u282.png"></a>';
											 }
											 shtml += '</dd>';
											 shtml += '<dd class="dealcard dd-padding goUrl" data-url="'+order_list[i]['order_url']+'">';
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
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("未确认")}</span>';
											 }else if(order_list[i]['status']==1){
												 shtml += '<span>{pigcms{:L_("已确认")}</span>';
											 }else if(order_list[i]['status']==2){
												 var url = "{pigcms{:U('My/shop_feedback')}";
												 url +='&order_id='+order_list[i]['order_id'];
												 shtml +='<span style="color:'+main_color+'" onclick="location.href=\''+url+'\'">{pigcms{:L_("待评价")}</span>';
											 }else if(order_list[i]['status']==3){
												 if(order_list[i]['goods_type_num']>1){
													 var url = "{pigcms{:U('My/shop_feedback')}";
													 url += '&order_id='+order_list[i]['order_id'];
													 shtml +='<span style="color:'+main_color+'" onclick="location.href=\''+url+'\'">{pigcms{:L_("已评价")}</span><span style="display: none;"></span>';
												 }else{
													 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("已评价")}</span><span style="display: none;"></span>';
												 }
											 }else if(order_list[i]['status']==4){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("已退款")}</span>';
											 }else if(order_list[i]['status']==5){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("已取消")}</span>';
											 }else if(order_list[i]['status']==6){
											 }else if(order_list[i]['status']==7){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("分配到自提点")}</span>';
											 }else if(order_list[i]['status']==8){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("发货到自提点")}</span>';
											 }else if(order_list[i]['status']==9){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("自提点接货")}</span>';
											 }else if(order_list[i]['status']==10){
												 shtml += '<span style="color:'+main_color+'">{pigcms{:L_("自提点发货")}</span>';
											 }
											 if(order_list[i]['paid'] == 0 && (order_list[i]['status'] < 2 || order_list[i]['status'] == 7)){
												 shtml +='<span data-order_id="'+order_list[i]['order_id']+'" data-sys_id="'+ order_list[i]['coupon_id'] +'" data-mer_id="'+ order_list[i]['card_id'] +'" class="order-pay" style="color:'+main_color+'">{pigcms{:L_("去支付")}</span>';
											 }else if(order_list[i]['status'] == 2){
												 var url = "{pigcms{:U('My/shop_feedback')}";
												 url += '&order_id='+order_list[i]['order_id'];
												 shtml +='<span onclick="location.href=\''+url+'\'">{pigcms{:L_("评价")}</span>';
											 }else if(order_list[i]['status'] != 3 && order_list[i]['status'] != 1 && order_list[i]['status'] != 0){
												 shtml+='<a></a>';
											 }
											 shtml +='</dd></dl><div style=" height:10px; background:#f0efed"></div>';
										 }
										 shtml += '</dd>';
									 }else{
										 var shtml ='<dd><dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">{pigcms{:L_("暂无订单")}</dd></dd>';
									 }
									 $('.tab[tab-id="' + tabId + '"]').html(shtml);
									  if(!is_index){
										  scroollShow()
									  }
								 },'json')
						 });
					 });
					 
					$('.tabs-header .active a').trigger('click');
				 }

			}
			function del_order(order_id){
				if(!order_id){
					return false;
				}

				layer.open({
					content:'{pigcms{:L_("确认删除？")}',
					btn: ['{pigcms{:L_("确定")}','{pigcms{:L_("取消")}'],
					yes:function(){
						var del_url = "{pigcms{:U('ajax_shop_order_del')}";
						$.get(del_url,{'order_id':order_id},function(data){
							if(data['status']){
								location.reload();
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
		</script>
<!--include file="Public:footer"/-->
{pigcms{$hideScript}
<style>
	.goUrl,.order-pay{
		cursor:pointer;
	}
</style>

<script>
$("body").delegate(".goUrl","click",function(){
	var jpurl = $(this).data('url');
	if(!jpurl){
		return false;
	}
	if(window.__wxjs_environment === 'miniprogram' || (/(micromessenger)/.test(navigator.userAgent.toLowerCase()) && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
		jpurl = jpurl.replace('http://','https://');
		if(jpurl.indexOf('https://') === -1){
			jpurl = 'https://'+location.host + jpurl;
		}

		var webview_title = '{pigcms{:L_("订单详情")}';
		wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(jpurl)+'&webview_title='+encodeURIComponent(webview_title)});
	}else{
		location.href = jpurl;
	}
});
$("body").delegate(".order-pay","click",function(){
	var order_info = $(this).data();
	order_info.use_sys_coupon = order_info.sys_id != 0 ? true : false
	order_info.use_mer_coupon = order_info.mer_id != 0 ? true : false

	if(window.__wxjs_environment === 'miniprogram' || (/(micromessenger)/.test(navigator.userAgent.toLowerCase()) && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
		wx.miniProgram.navigateTo({
			url:'/pages/shop_new/confirmOrder/pay?order_id='+order_info.order_id+'&type=shop&use_sys_coupon='+order_info.use_sys_coupon+'&use_mer_coupon='+ order_info.use_mer_coupon +'&system_coupon_id='+order_info.sys_id+'&merchant_coupon_id='+order_info.mer_id
		});
	}else{
		location.href = "{pigcms{:U('Pay/check',array('type'=>'shop'))}&order_id="+order_info.order_id;
	}
});
</script>
</body>
</html>
