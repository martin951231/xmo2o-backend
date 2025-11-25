<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>订单管理</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/myOrder.css?t={pigcms{:time()}"/>
		<style type="text/css">
			body{
				background: #f8f8f8;
				padding-top: 82px;
			}

			input, input:active, button, button:active{
				outline: none;
				border: none;
				background:transparent;
				border-radius: none;
			}

			.header{
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				background: #fff;
			}

			.header #orderNav{
				position: static;
			}

			.search-box{
				height: 30px;
				line-height: 30px;
				padding: 10px 10px 0;
				background: #fff;
			}

			.search-box .fl{
				border: 1px solid #efefef;
				width: calc(100% - 60px);
				width: -webkit-calc(100% - 60px);
				box-sizing: border-box;
				padding-left: 5px;
				white-space: nowrap;
			}

			.search-box .fl input{
				height: 100%;
				width: calc(100% - 68px);
				width: -webkit-calc(100% - 68px);
				margin-left: 5px;
			}

			.search-box .fl .scan-icon{
				padding:0 10px;
				height: 30px;
				line-height: 30px;
				display: inline-block;
			}

			.search-box .fr{
				width: 50px;
				margin-left: 10px;
				text-align: center;
			}

			.search-box .fr button{
				background:#fe3919;
				color: #fff;
				width: 100%;
				height: 30px;
				display: inline-block;
				border-radius: 4px;
			}
			.search-box .fr button:active, .list-item .actionBtn button:active{
				background:#df2304; 
			}

			.empty-box{
				top: 82px;
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
				top: 82px;
				left: 0;
				right: 0;
				height: calc(100% - 82px);
				height: -webkit-calc(100% - 82px);
				z-index: -1;
			}
			.list.minirefresh-scroll{
				background-color: #f8f8f8;
			}
			
			.verify,.get_all{
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
			}
            .self-pick-person {
                font-size: 12px;
                line-height: 16px;
                margin-top:2px;
                color: #333;
                white-space: pre-wrap;
                word-break: break-all;
                word-wrap: break-word;
            }
		</style>
	</head>
	<body>
		<div class="header">
			<div class="search-box">
				<div class="fl">
					<i class="icon iconfont">&#xe61a;</i>
					<input type="text" name="search" id="keyword" placeholder="请输入核销码进行搜索">
					<i class="icon iconfont scan-icon" id="scancode">&#xe627;</i>
				</div>
				<div class="fr"><button id="search" style='<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]}; </if>'>搜索</button></div>
			</div>
			<div class="swiper-container" id="orderNav">
			    <div class="swiper-wrapper">
			      <div class="swiper-slide" >
			      	<div class="item" data-status="-1"><span class="active">全部</span></div>
			      </div>
			       <div class="swiper-slide" >
			      	<div class="item" data-status="1,5,6,7,8"><span>未消费</span></div>
			      </div>
			      <div class="swiper-slide" >
			      	<div class="item" data-status="3"><span>部分消费</span></div>
			      </div>
			      <div class="swiper-slide" >
			      	<div class="item" data-status="2,13"><span>已消费</span></div>
			      </div>
			
			    </div>
			</div>
		</div>
		

		<div id="minirefresh" class="minirefresh-wrap ">
			<div class="list minirefresh-scroll">
				
			</div>
		</div>

		<div class="empty-box hidden"> 
			<div><img src="{pigcms{$static_path}villagegroup/images/null.jpg"></div>
			<div>暂无订单</div>
		</div>

		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>	
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112333" charset="utf-8"></script>
		<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>
		{pigcms{$hideScript}
		<script type="text/javascript">

		var myScroll,hasMorePage=false,status=-1,page=1,is_load=false, from='';
			$(function () {
				FastClick.attach(document.body);

				var url = window.location.href;
				if (url.indexOf('code') != -1) {
                    is_load = true;
					var paramArr = url.split('=');
					var code = paramArr.pop()
					page=1;
					$('#keyword').val(code)
					get_order_list();
					from = 'my';
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
					$('#orderNav .item span').removeClass('active')
					$(this).find('span').addClass('active')
					status = $(this).data('status');
					$('.list').html('')
					page=1;
					miniRefresh.triggerUpLoading();
				})
				
				$(document).on('click', '#search', function () {
					page=1;
					get_order_list();
				})
				
				$(document).on('click', '.verify', function () {


					order_id= $(this).data('order_id')
					layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'确认全部自提吗？确认后订单将完成验证消费',btn:['好的','取消'],shadeClose:false,yes:function(){
						$.post('{pigcms{:U('ajax_verify')}',{order_id:order_id,confirm:1},function(data){

							layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info, btn:['好的'],shadeClose:false,yes:function(index){ 
									if( motify.checkWeixin() ) {
										layer.close(index);
										page=1;
										get_order_list();
									} else {
                                        window.location.href = "{pigcms{:U('leaderOrder')}&time="+((new Date()).getTime());
									}	
								}
						});
							
						},'json');
					},cancel:function(){
						layer.closeAll();
					}});

					return false
				})
				
				$(document).on('click', '.get_all', function () {
					order_id= $(this).data('order_id')
					layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'确认全部收货吗？',btn:['好的','取消'],shadeClose:false,yes:function(){
						$.post('{pigcms{:U('ajax_get_all')}',{order_id:order_id,confirm:1},function(data){
							layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info, btn:['好的'],shadeClose:false,yes:function(index){ 
									if( motify.checkWeixin() ) {
										layer.close(index);
										page=1;
										get_order_list();
									} else {
                                        window.location.href = "{pigcms{:U('leaderOrder')}&time="+((new Date()).getTime());
									}	
								}
						});
							
						},'json');
					},cancel:function(){
						layer.closeAll();
					}});

					return false
				})
				
				
				
				$('.list .imgWrapper').each(function () {
					$(this).width($(this).find('img').length * 95)
				})

				$(document).on('click', '.link', function () {
					console.log(111)
					window.location.href=$(this).data('url')
				})
				
				$(document).on('click', '.get_order', function () {
					event.stopPropagation();
					order_id= $(this).data('order_id')
					goods_id= $(this).data('goods_id')
					status= $(this).data('status')
					name= $(this).data('name')
					if(status==2){
						status_txt = '到货';
					}else if(status==3){
						status_txt = '取货';
					}
					layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'确认订单号：'+order_id+'【'+name+'】'+status_txt+'？',btn:['确认','取消'],shadeClose:false,yes:function(){
						$.post('{pigcms{:U('ajax_get_order')}',{order_id:order_id,goods_id:goods_id,status:status},function(data){
							layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info, btn:['好的'],shadeClose:false,
								yes:function(index){ 
									if( motify.checkWeixin() ) {
										layer.close(index);
										page=1;
										get_order_list();
									} else {
                                        window.location.href = "{pigcms{:U('leaderOrder')}&time="+((new Date()).getTime());
									}	
								}});
								
						},'json');
					},cancel:function(){
						layer.closeAll();
					}});
					return false
				})
				
				$(document).on('click','#scancode',function(){
					if(motify.checkWeixin()){
						// motify.log('正在调用二维码功能');
						wx.scanQRCode({
							desc:'scanQRCode desc',
							needResult:1,
							scanType:["qrCode","barCode"],
							success:function (res){
								page=1;
								$('#keyword').val(res.resultStr)
								get_order_list();
								// window.location.href='{pigcms{:U('leaderList')}&code='+res.resultStr;
							},
							error:function(res){
								// motify.log('微信返回错误！请稍后重试。',5);
							},
							fail:function(res){
								// motify.log('无法调用二维码功能');
							}
						});
					}else{
						// motify.log('您不是微信访问，无法使用二维码功能');
					}
				});
				
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
                            if (!is_load) {
                            	// 上拉事件
                                console.log('上拉加载');
								if (from != 'my') {
									get_order_list()
								}
                                // 注意，由于默认情况是开启满屏自动加载的，所以请求失败时，请务必endUpLoading(true)，防止无限请求

                            }
						}
					}
				});
							

			function get_order_list(){
				console.log("郭先森");
			    console.log('{pigcms{$_GET['leader_id']}');
				// return trye;

				var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
				var html = '';
				$.ajax({
					url: '{pigcms{:U('ajax_list')}',
					type: 'POST',
					data: {
						status:$("#orderNav .item span.active").closest(".item").attr("data-status"),
						page: page,
						'verify':1,
						'code':$('#keyword').val(),
					},
					dataType: 'json',
					success: function (result) {
						if (from == 'my') {
							from = '';
						}
						data = result.order_list;
						totalPage = result.totalPage;
						if(data.length>0){
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
								
								
//								html+='<div class="item link" data-url="{pigcms{:U('leaderOrderDetail')}&order_id='+order.order_id+'" >';
								html+='<div class="item link" data-url="{pigcms{:U('leaderOrderDetail')}&order_id='+order.order_id+'&leader_id={pigcms{$_GET['leader_id']}" >';
								if (order.join_user) {
                                    html+='	<div class="name">'+order.join_user.nickname+'<a href="tel:'+order.join_user.phone+'" style="font-size:12px;">(<font color="blue">'+order.join_user.phone+'</font>)</a>';
                                } else {
                                    html+='	<div class="name">用户已注销';
                                }
								if(order.status==6 ){
                                    if (order['village_group_type']!=2) {
                                        html += '			<span class="fr actionBtn"><button class="verify" data-order_id="' + order.order_id + '">全部自提</button></span>';
                                    } else if(order['village_group_type']==2 && order['order_status']==4) {
                                        html += '			<span class="fr actionBtn"><button class="verify" data-order_id="' + order.order_id + '">全部核销</button></span>';
                                    }
								}else if(order.status==5){
									
									html+='			<span class="fr actionBtn"><button class="get_all" data-order_id="'+order.order_id+'">全部到货</button></span>';
									
								}else if(order.status==4 || order.status==12){
									html+='			<span class="finish">已退款</span>';
								}else{
									html+='				<span class="finish" >'+order.status_txt+'</span>';
								}
								
								html+='	</div>';

                                // 提货人
                                if(order.pick_name && order.pick_name) {
                                    let pickUpUserHtml = `<div class="self-pick-person" >提货人：${order.pick_name} <span style="margin-left: 6px;">${order.pick_phone}</span></div>`
                                    html+=pickUpUserHtml
                                }

								html+='	<div class="goods-list">';
								for(var h=0;h<order.order_detail.length;h++){
									html+='		<div class="goods-item">';
									html+='			<span class="goods-name">'+order.order_detail[h].name+'</span>';
									html+='			<span class="goods-num">x'+order.order_detail[h].num+'</span>';
									html+='			<span class="fr status-box">';
									
									if(order.order_detail[h].deliver_status==0){										
										html+='				<span class="status">未配送</span>';
									}else if(order.order_detail[h].deliver_status==1){										
										html+='				<span class="status green">已配送</span>';
										if (order['village_group_type']!=2) {
                                            html+='				<span class="status get_order" data-status="2" data-name="'+order.order_detail[h].name+'"  data-order_id="'+order.order_id+'" data-goods_id="'+order.order_detail[h].goods_id+'">确认到货</span>';
                                        }
									}else if(order.order_detail[h].deliver_status==2){
                                        if (order['village_group_type']!=2) {
                                            html+='				<span class="status green">待自提</span>';
                                            html+='				<span class="status get_order" data-status="3" data-name="'+order.order_detail[h].name+'" data-order_id="'+order.order_id+'" data-goods_id="'+order.order_detail[h].goods_id+'">确认自提</span>';
                                        } else if (order.order_status==0){
                                            html+='				<span class="status green">待配送</span>';
                                        } else if (order.order_status==1){
                                            html+='				<span class="status green">已接单</span>';
                                        } else if (order.order_status==2){
                                            html+='				<span class="status green">取货中</span>';
                                        } else if (order.order_status==3){
                                            html+='				<span class="status green">配送中</span>';
                                        }else if (order.order_status==4){
                                            html+='				<span class="status green">已送达</span>';
                                        }
									}else if(order.order_detail[h].deliver_status==3 || order.order_detail[h].deliver_status== 4 ){
										html+='				<span class="status green">已取货</span>';
									}
									html+='			</span>';
									html+='		</div>';
								}
					
								html+='	</div>';
								html+='	<div class="order-info">';
								html+='		<span>订单号：'+order.order_id+'</span>';
								html+='		<span class="fr price-box"><span>总金额：</span><span class="price" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]}; </if>">{pigcms{$config['Currency_symbol']}'+order.total_money+'</span></span>';
								html+='	</div>';
								html+='</div>';
								
								/* html+='<div class="list-item">';
								html+='	<div class="link" data-url="{pigcms{:U('leaderOrderDetail')}&order_id='+order.order_id+'">';
								html+='		<div class="line title">';
								if (order.join_user && order.join_user.avatar) {
                                    html+='			<span><img class="avatar" src="'+order.join_user.avatar+'">'+order.join_user.nickname+'</span>';
                                } else {
								    // 默认图
                                    html+='			<span><img class="avatar" src="{pigcms{$static_path}images/new_my/pic-default.png">'+order.join_user.nickname+'</span>';
                                }
								if(order.status==6 || order.status==5){
									
									html+='			<span class="fr actionBtn"><button class="verify" data-order_id="'+order.order_id+'">核销验证</button></span>';
								}
								html+='		</div>';
								html+='		<div class="goods-box" >';
								html+='			<div class="img-box fl">';
								html+= img_html;
								html+='			</div>';
								if(order.order_detail.length==1){
									html+='			<div class="goods-info fr" >';
									html+='				<div class="fl info"> ';
									html+='					<div class="ellipsis ">'+order.order_detail[0].name+'</div>';
									// html+='					<div class="font_size12 color_9 mgt10">数量：1&nbsp;&nbsp;规格：袋</div>';
									html+='				</div>';
									html+='				<div class="price fr ">{pigcms{$config['Currency_symbol']}'+order.order_detail[0].price+'</div>';
									html+='			</div>';
								}
								html+='		</div>';
								html+='		<div class="line price-box">';
								html+='			<span>共:&nbsp;<strong>'+num+'</strong>&nbsp;件商品</span>&nbsp;';
								html+='			<span>小计:&nbsp;<strong>{pigcms{$config['Currency_symbol']}'+order.total_money+'</strong></span>';
								html+='			<div class="fr">';
								if(order.status>0 && order.status<10){									
									html+='				<span class="finish mgr5">已付款</span>';
								}
								if(order.status==1){									
									html+='				<span class="unfinish mgr5">未发货</span>';
								}
								if(order.status==5){									
									html+='				<span class="unfinish mgr5">已发货</span>';
								}
								if(order.status==6){									
									html+='				<span class="unfinish mgr5">未提货</span>';
								}
								if(order.status==2){									
									html+='				<span class="unfinish mgr5">已提货</span>';
								}
							
								html+='			</div>';
								html+='		</div>';
								html+='	</div>';
								html+='</div>'; */
							}
							if(page == 1){
								$('.list').html('')
								miniRefresh.endDownLoading(true)
							}
							page++;
						
							$('.list').append(html)
							miniRefresh.resetUpLoading();
							$('.list .imgWrapper').each(function () {
								$(this).width($(this).find('img').length * 95)
							})
							$('.list').show();
							$('.empty-box').hide();
					
							hasMorePage =  page>totalPage?false:true;
							
						}else{
							hasMorePage = false;
							if(page == 1) {
								$('.list').hide();
								$('.empty-box').show();
							}
							
						}

						console.log('3333333333333333333',hasMorePage)
						miniRefresh.endUpLoading(!hasMorePage);
						layer.close(layerLoadTip);
                        is_load = false;
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
                        is_load = false;
					}

				})


			}

		})
			
		</script>
	</body>
	
</html>