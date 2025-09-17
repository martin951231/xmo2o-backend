
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>分类订单列表</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
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
	        width: 23.3%;
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
		  z-index: 15px;
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
		.pageHide{position:fixed;width:100%;height:100%;z-index:100000;background:#fff}
		.order-num{ height:1rem; line-height:1rem; color:#9E9E9E; padding-left:.2rem}
		.order-num a{ display:block; float:right; margin-right:.1rem}
		.order-num-foot span:first-child{display:block; float:left; color:#06c1bb; line-height:1rem}
		.order-num-foot span:last-child,.order-pay,.order-cancel{border:1px solid #e5e5e5;color:#666; padding:.2rem; border-radius:4px; display:block; float:right;line-height:.3rem; margin:.1rem .1rem 0 0; }
		.order-num-foot span.order-pay{ color:#06c1bb;padding:.2rem .5rem}
		.dealcard .dealcard-block-right{ margin-left:.5rem} 

		#express_type{height: 26px;width: 40%;outline: none;}
		#express_id{width:calc(100% - 90px);padding: 0 5px;outline: none;}

        .dealcard-brand {
            word-wrap: break-word;
            word-break: break-all;
        }
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
					<li class="active"><a href="javascript:void(0)" tab-id="1" class="react" data-index="1">
						<span>全部</span>
					</a>
					</li><!--li data-status='-1'><a href="javascript:void(0)" tab-id="2" class="react ">
						<span>未付款</span>
					</a>
					</li--><li data-status='1'><a href="javascript:void(0)" tab-id="3" class="react " data-index="2">
						<span>已付款</span>
					</a>
					</li><li data-status='2'><a href="javascript:void(0)" tab-id="4" class="react " data-index="3">
						<span>已收货</span>
					</a>
					</li>
					</li><li data-status='5'><a href="javascript:void(0)" tab-id="2" class="react " data-index="4" >
						<span>待发货</span>
					</a>
					</li>
				</ul>
				</div></div>
			</dd>
		</dl>
	    <div style="margin-top:.2rem;">
		    <dl class="list tabs-content" id="orders">
				<div tab-id="1" class="tab active">
				
				<if condition='$order_list'>
					<volist name="order_list" id="order">
						<dd>
							<dl>
								<dd class="order-num">订单编号：<span>{pigcms{$order.order_id}</span>
								</dd>
								<dd class="dealcard dd-padding" onclick="window.location.href = '{pigcms{:U('Classifynew/order_detail',array('id'=>$order['order_id']))}';">
										<div class="dealcard-block-right">
											<div class="dealcard-brand ">{pigcms{$order.order_name}</div>
											<small>数量：{pigcms{$order.num}&nbsp;&nbsp;总价：{pigcms{$order.total_price} {pigcms{$config['Currency_txt']}</small>
											
										</div>
									</dd>
								<dd class="order-num order-num-foot">		
									<if condition="($order['paid'] == 0)">
										<span>未付款</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 1) && ($order['logistics_status'] == 3)" />
										<span>已收货</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 1) && (!empty($order['express_id']))" />
										<span>已发货</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 3) " />
										<span>已付款</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && ($order['logistics_status'] == 2) && (empty($order['express_id']))" />
										<span>未发货</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 1) && ($order['logistics_status'] == 4) " />
										<span>已自提</span>
									<elseif condition="($order['paid'] == 1) && ($order['status'] == 0) && (empty($order['express_id']) && ($order['is_pick_in_store'] == 1) ) " />
										<span>到店自提</span>		
									</if>
									
									
									<if condition="(($order['paid'] == 1) AND (empty($order['status'])) AND ($order['logistics_status'] == 1) )" >
										<if condition="$order['is_pick_in_store'] eq 0">
											<span class="order-pay"><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order['express_type']}&postid={pigcms{$order['express_id']}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>">查看物流</a></span>
										</if>	
										<if condition="$order['is_pick_in_store'] eq 1">
											<span onclick="chk_order({pigcms{$order['order_id']},'是否确认自提')" class="order-pay">确认自提</span>
										<else />
											<span onclick="chk_order({pigcms{$order['order_id']},'是否确认收货')" class="order-pay">确认收货</span>
										</if>
									<elseif condition="empty($order['paid'])" />
										<span onclick="location.href='{pigcms{:U('Classify/order',array('classify_userinput_id'=>$order['classify_userinput_id']))}'" class="order-pay">付款</span>
									<elseif condition="($order['paid'] == 1) && ($order['logistics_status'] == 2) && ($_SESSION['user']['uid'] ==$order['seller_user_id'])"  />
								 	 	<span onclick="send_order({pigcms{$order['order_id']})" class="order-pay">发货</span>
									<else />
										<a></a>
									</if>
								</dd>
							</dl>
						</dd>
						<div style=" height:10px; background:#f0efed"></div>
					</volist>
				<else />
					<dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">暂无订单</dd>
				</if>
				</div>
			<div tab-id="2" class="tab"></div>
			<div tab-id="3" class="tab"></div>
			<div tab-id="4" class="tab"></div>
		    </dl>
		</div>
    	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script type="text/javascript" language="javascript">	
			function chk_order(order_id,$content){
				if(!order_id){
					return false;
				}
				layer.open({
					content:$content,
					btn: ['确定','取消'],
					yes:function(){
						var chk_url = "{pigcms{:U('Classify/ajax_chk_classify')}";
						$.get(chk_url,{'order_id':order_id},function(data){
							if(data['status']){
								location.reload();
							}else{
								layer.alert(data.msg)
							}
						},'json');
					}
				});
			}				
			function send_order(order_id){
				if(!order_id){
					return false;
				}
				var orderId = order_id;

				var url = "{pigcms{:U('My/ajax_express')}";
				$.post(url,function(data){
					if(data.list != null){//请求快递信息
						//确认发货前 先输入快递单号
						var express_list = data.list;
						var html = '';
						html = '<select id="express_type" name="express_type">';
						for(var i in express_list){
							html +='<option value="'+ express_list[i]['code']+'">'+express_list[i]['name']+'</option>';
						}

						html +='</select>';
						var mask_width = $(window).width() * 0.8;
						layer.open({
							type: 1
							,title:'快递单号'
							,skin:'layui-layer-rim'
							,content: ' <div class="row" style="width:'+ mask_width +'px;max-width:420px;margin:10px; border-radius:5px;">'
									+'<div class="col-sm-12">'
									+'<div class="input-group" style="margin-bottom:10px;">'
									+'<span class="input-group-addon" style="margin-right:5px;"> 快递公司   :</span>'
									+html
									+'</div>'
									+'<div class="input-group">'
									+'<span class="input-group-addon" style="margin-right:5px;"> 快递单号   :</span>'
									+'<input id="express_id" type="text" class="form-control" placeholder="请输入快递单号">'
									+'</div>'
									+'</div>'
									+'</div>'
							,btn:['保存','取消']
							,yes: function (index) {
								var express_type = $('#express_type').val();
								var express_id = $('#express_id').val();
								var btn_url = "{pigcms{:U('Classify/ajax_add_express')}";
								$.post(btn_url,{'order_id':orderId,'express_type':express_type,'express_id':express_id},function(datas){
									var jsobject = JSON.parse(datas);//转化
									if(jsobject.status == 0){
										var chk_url = "{pigcms{:U('Classify/ajax_send_classify')}";
										$.get(chk_url,{'order_id':orderId},function(data){
											if(data['status']){
												layer.open({
													content: jsobject.msg
													,skin: 'msg'
													,time: 2 //2秒后自动关闭
												});

												setTimeout(function(){location.reload();},3000);
											}
										},'json');

									}


								})
								layer.close(index);
							}
							,btn2:function (index) {
								layer.close(index);
							}

						});
					}
					if(data.list == null){
						layer.open({
							content: '快递数据为空'
							,skin: 'msg'
							,time: 2 //2秒后自动关闭
						});
					}
				},'json');
			}
			window.onload=function(){
				var _this=this;
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
				function scroollShow(){
					is_index=true;
					$("html,body").animate({"scrollTop":$shop_top*1},10,function(){
						$(".pageHide").hide()
					});

				}
				function show(){
					var activePos = $('.tabs-header .active').position();
					var url = "{pigcms{:U('ajax_classify_order_list')}"
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
					//animateTabHeight();
					var tabItems = $('.tabs-header ul li');
					var tabCurrentItem = tabItems.filter('.active');
					$('.tabs-header a').on('click', function (e) {
						e.preventDefault();
						var tabId = $(this).attr('tab-id');
						var $index=$(this).attr('data-index');
						sessionStorage.setItem("shop_order_list_index", parseInt($index-1));
						$('.tabs-header a').stop().parent().removeClass('active');
						$(this).stop().parent().addClass('active');
						changePos();
						tabCurrentItem = tabItems.filter('.active');
						$('.tab').stop().fadeOut(300, function () {
							$(this).removeClass('active');
						}).hide();
						$('.tab[tab-id="' + tabId + '"]').stop().fadeIn(300, function () {
							var status = $('.tabs-header ul li.active').data('status');
							$.get(url,{'status':status},function(data){
								if(data.status){
									var shtml = '<dd>';
									var order_list = data['order_list'];
									var order_url = "{pigcms{:U('Classify/ShowDetail')}";
									for(var i in order_list){
										shtml += '<dl><dd class="order-num">订单编号：<span>'+order_list[i]["order_id"]+'</span></dd>';
										order_url += "&vid=" + order_list[i]['classify_userinput_id'];
										shtml += '<dd class="dealcard dd-padding" onclick="window.location.href = \''+ order_url +'\';">';
										shtml += '<div class="dealcard-block-right">';
										shtml += '<div class="dealcard-brand ">'+order_list[i]['order_name']+'</div>';
										shtml += '<small>数量：'+order_list[i]['num']+'&nbsp;&nbsp;总价：'+order_list[i]['total_price']+' {pigcms{$config['Currency_txt']}</small>';

										shtml += '</div></dd><dd class="order-num order-num-foot">	';


										if(order_list[i]['paid']==0){
											shtml += '<span>未付款</span>';
										}else if((order_list[i]['paid'] == 1) && (order_list[i]['status'] == 1) && (order_list[i]['logistics_status'] == 3)){
											shtml += '<span>已收货</span>';
										}else if((order_list[i]['paid'] == 1) && (order_list[i]['status'] == 0) && (order_list[i]['logistics_status'] == 1 && (order_list[i]['express_id'] != ''))){
											shtml += '<span>已发货</span>';
										}else if(order_list[i]['paid'] == 1 && (order_list[i]['status'] == 0) && (order_list[i]['logistics_status'] == 3)){
											shtml += '<span>已付款</span>';
										}else if(order_list[i]['paid'] == 1 && (order_list[i]['status'] == 0) && (order_list[i]['logistics_status'] == 2) && (order_list[i]['express_id'] == '')){
											shtml += '<span>未发货</span>';
										}else if(order_list[i]['paid'] == 1 && (order_list[i]['status'] == 1) && (order_list[i]['logistics_status'] == 4)){
											shtml += '<span>已自提</span>';
										}else if(order_list[i]['paid'] == 1 && (order_list[i]['status'] == 0) && (order_list[i]['express_id'] == '' && (order_list[i]['is_pick_in_store'] == 1))){
											shtml += '<span>到店自提</span>';
										}

										var url = "{pigcms{:U('Classify/order')}";
										if((order_list[i]['paid'] == 1) && (order_list[i]['status'] == 0) && (order_list[i]['logistics_status'] == 1)){
											if(order_list[i]['is_pick_in_store'] == 0){
												shtml +='<span class="order-pay"><a href="http://m.kuaidi100.com/index_all.html?type='+order_list[i]['express_type']+'&postid='+order_list[i]['express_id']+'&callbackurl='+"<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>"+'">查看物流</a></span>';
											}

											if(order_list[i]["is_pick_in_store"] == 1){
												shtml +='<span onclick="chk_order('+order_list[i]["order_id"]+',\'是否确认自提\''+')" class="order-pay">确认自提</span>';
											}else{
												shtml +='<span onclick="chk_order('+order_list[i]["order_id"]+',\'是否确认收货\''+')" class="order-pay">确认收货</span>';
											}
										}else if(order_list[i]['paid'] == 0){
											url += '&classify_userinput_id='+order_list[i]['classify_userinput_id'];
											shtml +='<span onclick="location.href=\''+url+'\'" class="order-pay">付款</span>';
										}else if((order_list[i]['paid'] == 1) && (order_list[i]['logistics_status'] == 2) && (order_list[i]['status'] == 0)  && ({pigcms{:session('user')['uid']} == order_list[i]['seller_user_id'])){
											shtml +='<span onclick="send_order('+order_list[i]["order_id"]+')" class="order-pay">发货</span>';
										}else{
											shtml+='<a></a>';
										}
										shtml +='</dd></dl><div style=" height:10px; background:#f0efed"></div>';
									}
								}else{
									var shtml ='<dd><dd class="dealcard dd-padding" style=" text-align:center; background:#fff; width:100%">暂无订单</dd></dd>';
								}
								$('.tab[tab-id="' + tabId + '"]').html(shtml);
								if(!is_index){
									scroollShow()
								}
						     },'json')
						});
					});

				}

			}






		</script>
    {pigcms{$hideScript}    
</body>
</html>