<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>我的订单</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/myOrder.css?t={pigcms{:time()}"/>
		<style type="text/css">

			.list.minirefresh-scroll{
				background-color: #f8f8f8;
			}
			
			.list-item .actionBtn button{
				background:#fe3919;
				color: #fff;
				display: inline-block;
				padding:7px 10px;
				border-radius: 4px;
			}

			.list-item .finish{
				color: #00b200;
			}

			.list-item .unfinish{
				color: #fe3919;
			}

			.list-item .avatar{
				width: 30px;
				height: 30px;
				display: inline;
				margin-right: 10px;
				vertical-align: middle;
				border-radius: 50%;
			}

			.list .item{
				background: #fff;
				margin-top: 10px;
				font-size: 14px;
				color: #999;
				padding: 10px;
			}

			.list .item .name{
				font-size: 16px;
				color: #333;
			}

			.list .item .name .reply-btn{
				float: right;
				color: #fe3112;
				border: 1px solid #fe3112;
				font-size: 12px;
				padding: 3px 8px;
				border-radius: 4px;
			}

			.list .item .goods-list{
				margin-top: 10px;
				line-height: 1.8;
			}

			.list .item .goods-list .goods-item .goods-name{
				display: inline-block;
				width: 50%;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.list .item .goods-list .goods-item .goods-num{
				vertical-align: top;
			}

			.list .item .goods-list .goods-item .status-box .status{
				font-size: 12px;
				color: #fe3112;
				background: #fff1ef;
				border-radius: 2px;
				padding: 0 5px;
				margin-right: 5px;
				display: inline-block;
			}

			.list .item .goods-list .goods-item .status-box .status.green{
				color: #7ab900;
				background: #eef8da;
			}
			.list .item .goods-list .goods-item .status-box .status:last-child{
				margin-right: 0;
			}

			.list .item .order-info>span:first-child{
				line-height: 29px;
			}

			.list .item .order-info .price{
				color: #fe3112;
				font-size: 18px;
				font-weight: bold;
			}
		
			#minirefresh{
				position: fixed;
				bottom: 0;
				top: 40px;
				left: 0;
				right: 0;
				
				z-index: -1;
			}
			.list.minirefresh-scroll{
				background-color: #f8f8f8;
			}
			
			.verify{
				background: #fe1919;
				color: #fff;

				height: 30px;
				display: inline-block;
				border-radius: 4px;
				padding: 4px;
			}
			.get_order{
				border-color: #088ef0 !important;
				background: -webkit-gradient(linear, left top, left bottom, from(#34a5f8), to(#088ef0)) !important;
				background: linear-gradient(#34a5f8, #088ef0) !important;
				color:#fff !important;
			}
			.finish{
				color:#2243e4;
				float:right;
				font-size:14px;
			}
		 
		</style>
	</head>
	<body>
		<div class="swiper-container" id="orderNav">
		    <div class="swiper-wrapper">
		      <div class="swiper-slide" >
		      	<div class="item" data-status="-1"><span class="active">全部</span></div>
		      </div>
		      <div class="swiper-slide" >
		      	<div class="item" data-status="0"><span>待付款</span></div>
		      </div>
		      <div class="swiper-slide" >
		      	<div class="item" data-status="1,5,7"><span>未到货</span></div>
		      </div>
		      <div class="swiper-slide" >
		      	<div class="item" data-status="6,8"><span>待自提</span></div>
		      </div>
		      <div class="swiper-slide" >
		      	<div class="item" data-status="2,3,13"><span>已自提</span></div>
		      </div>
              <div class="swiper-slide" >
              	<div class="item" data-status="4,9"><span>售后</span></div>
              </div> 
		    </div>
		</div>

		
		<div id="minirefresh" class="minirefresh-wrap ">
			<div class="list minirefresh-scroll">
				
			</div>
		</div>
		
		
		<div class="empty-box" style="display: none;"> 
			<div><img src="{pigcms{$static_path}villagegroup/images/null.jpg"></div>
			<div>暂无订单</div>
		</div>

		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>	<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		{pigcms{$hideScript}
		<script type="text/javascript">
		var myScroll,hasMorePage=false,status="{pigcms{$_GET.status}",page=1,now_url="{pigcms{:U('myOrder',$_GET)}";
			$(function () {  
				// 强制刷新,针对IOS手机从上一页返回页面不刷新的问题
				//forceRefresh()
				$('head').append("<style>#orderNav .item span.active::after{ background:{pigcms{$config["mobile_head_color"]} }</style>")
				if(status !== '' && $('#orderNav .item[data-status="'+status+'"]').size() > 0){
					$('#orderNav .item span').removeClass('active');
					$('#orderNav .item[data-status="'+status+'"] span').addClass('active'); 
					$('#orderNav .item[data-status="'+status+'"] span.active:after').css('background','{pigcms{$config["mobile_head_color"]}')
				}else{
					status = -1;
					now_url = now_url+'&status=-1';
				}
				
				var menuBoxSlider = new Swiper('#orderNav',{
					slidesPerView: 'auto',
					resistanceRatio: 0,
			        spaceBetween: 0,
					freeMode: true
				});

				$(document).on('click', '#orderNav .item', function () {
					status = $("#orderNav .item span.active").closest(".item").attr("data-status");
					if (status == $(this).data('status')) {
						return 
					}
					
					now_url = now_url.replace('status='+status,'status='+$(this).data('status'));
					var stateObj = { foo: now_url };
					history.replaceState(stateObj, "", now_url);
					
					page = 1
					$('#orderNav .item span').removeClass('active')
					$(this).find('span').addClass('active')
					status = $(this).data('status');
					$('.list').html('')
					miniRefresh.resetUpLoading();
					miniRefresh.triggerUpLoading();
				})

				$('.list .imgWrapper').each(function () {
					$(this).width($(this).find('img').length * 95)
				})
				
				var miniRefresh = new MiniRefresh({
					container: '#minirefresh',
					down: {
						isAutoResetUpLoading: false,
						isAuto: false,
						callback: function() {
							// 下拉事件
							page =1;
							get_order_list()
						}
					},
					up: {
						isAuto: true,
						isShowUpLoading: false,
						loadFull: {isEnable:false},
						callback: function() {
							// 上拉事件
							get_order_list()
							// 注意，由于默认情况是开启满屏自动加载的，所以请求失败时，请务必endUpLoading(true)，防止无限请求
						}
					}
				});
							
			
			
			function get_order_list(){
				var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
				var html = '';
				$.ajax({
					url: '{pigcms{:U('ajax_list')}',
					type: 'POST',
					data: {
						status:$("#orderNav .item span.active").closest(".item").attr("data-status"),
						page: page
					},
					dataType: 'json',
					success: function (result ) {
						data = result.order_list;
						totalPage = result.totalPage;
						if(data && data.length > 0){
							for(var i=0;i<data.length;i++){
								var order = data[i];
								var num = 0;
								if(order.order_detail.length>1){
									img_html = '<div class="ks-clear imgWrapper">';
									for(var h=0;h<order.order_detail.length;h++){
										img_html += '<img class="fl" src="'+order.order_detail[h].image+'">';
										num+= parseInt(order.order_detail[h].num);
									}
									img_html += '</div>';
								}else if(order.order_detail.length==1){
									img_html = '<div class="ks-clear"><img class="fl" src="'+order.order_detail[0].image+'"></div>';
									num+=parseInt(order.order_detail[0].num);
								}
								
								html+='<div class="item link" data-url="{pigcms{:U('orderDetail')}&order_id='+order.order_id+'" >';
								html+='	<div class="name">团长：'+order.header.name+'<a href="tel:'+order.header.phone+'" style="font-size:12px;">(<font color="blue">'+order.header.phone+'</font>)</a>';
								if(order.status==6 || order.status==5){
									// html+='			<span class="fr actionBtn"><button class="verify" data-order_id="'+order.order_id+'">核销验证</button></span>';
								}else{
									if($("#orderNav .item span.active").closest(".item").attr("data-status") == -1 || order.status!=10 && order.status!=11 && order.status!=12){										
										// html+='				<span class="finish" >'+order.status_txt+'</span>';
									}
                                    if (order.status==0) {
                                        html+='				<span class="finish" style="color: red;">未支付</span>';
                                    } else if (order.status==10) {
                                        html+='				<span class="finish" style="color: red;">超时未支付</span>';
                                    } else if (order.status==11) {
                                        html+='				<span class="finish" style="color: red;">已取消</span>';
                                    }
								}

								if(order.status==2){
									html+='	<span class="reply-btn link newWin" data-url="{pigcms{:U('reply')}&order_id='+order.order_id+'">去评价</span>';
								}else if(order.status==13){
									html+='	<span class="reply-btn">已评价</span>';
								}else if(order.status==12 || order.status==4){
									html+='	<span class="reply-btn">已退款</span>';
								}else if(order.status==2 && order.is_apply_refund == 1){
									html+='	<span class="reply-btn">申请中</span>';
								}
								
								html+='	</div>';								html+='	<div class="goods-list">';
								for(var h=0;h<order.order_detail.length;h++){
									html+='		<div class="goods-item">';
									html+='			<span class="goods-name">'+order.order_detail[h].name+'</span>';
									html+='			<span class="goods-num">x'+order.order_detail[h].num+'</span>';
									html+='			<span class="fr status-box">';
									if (order.order_detail[h].village_group_type==1) {
                                        html+='<span class="status" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};<else/>color:red</if>">周期购</span>';
                                    }
                                    if(order['status']==0 || order['status']==11 || order['status']==10){
                                        html+='';
                                    }else if(order.order_detail[h].deliver_status==0){
										html+='				<span class="status">未配送</span>';
									}else if(order.order_detail[h].deliver_status==1){										
										html+='				<span class="status green" >已配送</span>';
									}else if(order.order_detail[h].deliver_status==2){										
										html+='				<span class="status green">待自提</span>';
									}else if(order.order_detail[h].deliver_status==3 || order.order_detail[h].deliver_status==4){
										html+='				<span class="status green">已取货</span>';
									}else if(order.order_detail[h].deliver_status==5){										
										html+='				<span class="status green">已评价</span>';
									}
									html+='			</span>';
									html+='		</div>';
								}
					
								html+='	</div>';
								html+='	<div class="order-info">';
								html+='		<span>订单号：'+order.order_id+'</span>';
								html+='		<span class="fr price-box"><span>总金额：</span><span class="price" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};<else/>color:red</if>">{pigcms{$config['Currency_symbol']}'+order.total_money+'</span></span>';
								html+='	</div>';
								html+='</div>';
							}
							if(page == 1){
								$('.list').html('')
								miniRefresh.endDownLoading(true)
							}
							page++;
							// console.log(html)
							$('.list').append(html)
							miniRefresh.resetUpLoading();
							$('.list .imgWrapper').each(function () {
								$(this).width($(this).find('img').length * 95)
							})
							$('.list').show();
							$('.empty-box').hide();
							hasMorePage = page>totalPage?false:true;
							
						}else{
							hasMorePage = false;
							if(page == 1) {
								$('.list').hide();
								$('.empty-box').show();	
							}
							

						}
					
						miniRefresh.endUpLoading(!hasMorePage);
						layer.close(layerLoadTip);
					},
					error: function () {
						if (page == -1 || page == 1) {
							$('.list').html('')
							$('.list').hide();
							$('.empty-box').show();
							page == 1 ? miniRefresh.endUpLoading(true) : miniRefresh.endDownLoading(true)
						} else {
							miniRefresh.endUpLoading(true)
						}
					}

				})


			}

			

		})
			
		</script>
	</body>
</html>