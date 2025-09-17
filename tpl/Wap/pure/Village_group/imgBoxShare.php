<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>分享图片</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<style type="text/css">
			
			.content{
				max-height: 100%;
				overflow: hidden;
			}
			.content p{
				line-height: 1.5;
				font-size: 15px;
				color: #333;
				padding: 10px 10px 0;
			}

			#imgBox{
				height: 100%;
			}

			#imgBox .item{
				padding: 10px;
				height: calc(100% - 10px);
				height: -webkit-calc(100% - 10px);
				box-sizing: border-box;
			}

			#imgBox .item img{
				margin: 0 auto;
				cursor: pointer;
				/*box-shadow: 0 0 5px 3px #e5e5e5; */
			}

			#imgBox .item #first{
				width: auto;
				max-width:100%;
				border: 5px solid #fff;
				box-sizing: border-box
			}

			#imgBox .item img.shadow{
				box-shadow: 0 0 5px 3px #e5e5e5;
			}
			
			.sell-out-tip .layui-m-layercont{
				padding: 10px;
				line-height: 1;
			}
			
			.weui-photo-browser-modal .photo-container img{
				height: auto;
			}
			
			.icon-tip{
				width:25px;
				height:25px;
				display:inline-block;
				vertical-align: middle;
			}

			.msak{
				position: absolute;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				background: rgba(255, 255, 255, 0);
			}
			.coupon_mask {
				position: fixed;
				top: 0px;
				left: 0px;
				background: rgba(0,0,0,0.7);
				z-index: 888;
				width: 100%;
				height: 100%;
				display: none;
			}
			.imgModel{
				position: fixed;
				z-index: 5000;
				width: 80%;
				max-width: 300px;
				top: 53%;
				left: 50%;
				-webkit-transform: translate(-50%,-50%);
				transform: translate(-50%,-50%);

				text-align: center;
				border-radius: 3px;
				overflow: hidden;
				display:none;
				height:90%;
			}

			.flex {display: flex !important;display: -webkit-box;display: -moz-box;display: -ms-flexbox;display: -webkit-flex;align-items: center;}
			.flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
			.imgModel .flex {   position: absolute;  bottom: 2%;  left: 10px; right: 10px;   }
			.after:after{content:none !important;}
			.mgr10{    margin-right: 10px;}
			.mgt0{margin-top:0px !important;}
			.showModel{  bottom: 10px; margin: auto; position: fixed;  width: 100%;z-index: 100; }
			.showModel button{ width: 90%; border-radius: 100px;  background-color: #FD4C56;  }

			.animated {-webkit-animation-duration:.3s;animation-duration:.3s;-webkit-animation-fill-mode: both;animation-fill-mode: both}
			@keyframes zoomIn { from {opacity: 0;-webkit-transform: scale3d(.3,.3,.3);transform: scale3d(.3,.3,.3) } 50% {opacity: 1 } }
			.zoomIn {-webkit-animation-name: zoomIn; animation-name: zoomIn  }
			@keyframes zoomOut { from {opacity: 1 }  50% {opacity: 0;-webkit-transform: scale3d(.3,.3,.3);transform: scale3d(.3,.3,.3) } to {opacity: 0 } }
			.zoomOut { -webkit-animation-name: zoomOut; animation-name: zoomOut}
			.imgModel .swiper-button-next{      height: 48px;opacity:0;     	z-index: 1000; top: auto;   position: absolute;   bottom: 2%;   right: 10px;   width: 45%; height: 45px;      }
			#showImg .swiper-slide {
				display: -webkit-box;
				display: -webkit-flex;
				display: flex;
				-webkit-box-pack: center;
				-webkit-justify-content: center;
				justify-content: center;
				-webkit-box-align: center;
				-webkit-align-items: center;
				align-items: center;
				-webkit-flex-shrink: 0;
			}

			.content{
				max-height: 100%;
				overflow: hidden;
			}
			.content p{
				line-height: 1.5;
				font-size: 15px;
				color: #333;
				padding: 10px 10px 0;
				background:#fff;
			} 
			.icon-tip{
				width:25px;
				height:25px;
				display:inline-block;
				vertical-align: middle;
			} 
		  
			.btn_change{
				height:40px;
				width:90%; 
				color:#fff;
				border:none;
				border-radius: 20px;
				margin:0 auto;
				display:block; 
				text-align:center;
				line-height:40px;
				font-size:16px;
				z-index:99999
			}
			:focus{
				outline:0; 
			}

			/*a.btn_change, a.btn_change:hover, a.btn_change:active, a.btn_change:visited, a.btn_change:link, a.btn_change:focus{
				color:#fff;
			}*/
		  body{
				background:#f7f7f7;
			}
		 
			.swiper-container .swiper-slide img.icon-change{
				width:20px;
				height:20px;
				display:inline-block;
				vertical-align: middle;
				margin-right:5px;
				margin-top:-2px;
			}
			.swiper-containerBig{ 
				height:100%;
				margin:15px 30px;
				
			} 
			.swiper-container .swiper-slide{  
			}
			.swiper-container .swiper-slide img.slideImg{
				max-height:100%;
				height:auto;
				width:100%; 
				border-radius:5px;  
				background:#fff;
			}
			.swiperFrist{
				position: relative;
			}
			.swiper-container-son{
				position: absolute;
				left: 0;
				top:0;
				z-index: -1;
				width: 100%;
	      height: 100%; 
			}
			.swiper-container .swiperOut{
				padding-bottom:50px;
			}
			.bottom-btn{
				position: absolute;
    			bottom: -40px;
			    left: 0;
			    right: 0;
			    border: 1px solid #fe4c56;
			}

			.btn_change{
				 display: inline-block;
				 width: 50%;
				 border-radius: 0;
				 color: #000;
			}

			.copy-btn{
				display: inline-block;
				width: 50%;
				background: #fff;
				float: right;
				height: 40px;
				text-align: center;
				line-height: 40px
			}

			#indexShareMask {
				display: none;
				width: 100%;
				height: 100%;
			}

			#indexShareMask .shareContent{
				width: 100%;
				height: 100%;
			}

			#indexShareMask .shareContent .share-wrapper{
				width: 100%;
				height: 100%;
				margin: 0 auto;
				position: relative;
				box-sizing: border-box;
			}
			
			#indexShareMask .shareContent .share-wrapper .txtBox p{
				font-size:12px;
			}

			#indexShareMask .shareContent .desc-box{
				border-radius: 5px;
				height: calc(100% - 60px);
				height: -webkit-calc(100% - 60px);
			}

			#indexShareMask .shareContent .desc-box .title{
				text-align: center;
				height: 50px;
				line-height: 50px;
				font-size: 16px;
			}

			#indexShareMask .shareContent .desc-box .btn{
				height: 50px;
				line-height: 50px;
				text-align: center;
				width: 100%;
				outline: none;
				border: none;
				background: #fe3112;
				color: #fff;
				font-size: 16px;
			}

			#indexShareMask .shareContent .desc-box .btn:active{
				 background: #df2c11;
			}

			#indexShareMask .shareContent .desc-box .txtBox{
				height: calc(100% - 110px);
				height: -webkit-calc(100% - 110px);
				margin: 0 10px 10px 10px;
				overflow-y: auto;
				-webkit-overflow-scrolling:touch;
				border: 1px solid #e5e5e5;
				padding: 10px;
				box-sizing: border-box;
			}

			#indexShareMask .shareContent .desc-box .txtBox textarea{
				padding: 10px;
				border: 1px solid #e5e5e5;
				width: 100%;
				height: 100%;
				box-sizing: border-box;
				line-height: 1;
			}

			#indexShareMask .shareContent .desc-box .cancel{
				position: absolute;
				right: 0;
				top: 0;
				font-size: 30px;
				color: #999;
				padding: 0 5px
			}

			#indexShareMask .shareContent .btn-box{
				margin-top: 20px;
				text-align: center;
			}

			#indexShareMask .shareContent .btn-box .btn{
				outline: none;
				background: #fff;
				color: #df2c11;
				width: 50%;
				border:none;
				height: 40px;
				line-height: 40px;
				border-radius: 20px;
				box-shadow: 0 0 10px 2px #444;
			}

			#indexShareMask .shareContent .btn-box .btn img{
				width: 20px;
				height: auto;
				display: inline-block;
				vertical-align: middle;
				margin-right: 5px;
			}

			.bottom-btn .cur{
                background:#fe4c56;
                color: #fff;
            }

            .change-model{
            	position: fixed;
            	right: 15px;
            	bottom: 100px;
            	background: rgba(0, 0, 0, .7);
            	color: #fff;
            	width: 50px;
            	height: 50px;
            	border-radius: 50%;
            	font-size: 15px;
            	box-sizing: border-box;
            	padding: 6px;
            	text-align: center;
            	line-height: 1.2
            }

		</style>
	</head>
	<body>
		<div class="content">
			<p class="show_tip"><img class='icon-tip' src='{pigcms{$static_path}villagegroup/images/tip.png' />点击图片预览，长按保存图片或发送给好友、群即可。左右滑动还可查看更多商品单图哦！</p>

			<div class="swiper-container swiper-containerBig" id="imgBox">
			    <div class="swiper-wrapper pics">
			      	<div class="swiper-slide " >
				      	<!-- <div class="swiper-container swiper-container-son swiper-no-swiping">
							<div class="swiper-wrapper">
								<div class="swiper-slide"> 
									
								 </div>
							
							</div> 
						</div>  -->
						<!--  -->  
						<div class="item flex flex-align btn-change-content" data-status="-1" style="padding:0"> 
							<!-- <img  id="first" src="./static/images/blank.gif" data-pics="./static/qrcode/wxapp/share_bg10.png" data-index="0"  class="slideImg first first_1">   -->
							<img  id="first" src="./static/images/blank.gif" data-pics="{pigcms{$config['site_url']}/village_group/first_{pigcms{$_GET['leader_id']}?t={pigcms{:time()}" data-index="0"  class="slideImg first first_1">
							<img  id="first" src="./static/images/blank.gif" data-pics="{pigcms{$config['site_url']}/village_group/first_{pigcms{$_GET['leader_id']}_0_2?t={pigcms{:time()}" data-index="0"  class="slideImg first first_2" style="display: none;">
							<img  id="first" src="./static/images/blank.gif" data-pics="{pigcms{$config['site_url']}/village_group/first_{pigcms{$_GET['leader_id']}_0_3?t={pigcms{:time()}" data-index="0"  class="slideImg first first_3" style="display: none;">
							<img  id="first" src="./static/images/blank.gif" data-pics="{pigcms{$config['site_url']}/village_group/first_{pigcms{$_GET['leader_id']}_0_4?t={pigcms{:time()}" data-index="0"  class="slideImg first first_4" style="display: none;">
							<div class="change-model">切换模板</div>
						</div>

						<textarea id="hideShareTxt" style="position: absolute;left: -10000px;opacity: 0;" readonly="">{pigcms{$share_txt}</textarea>
						<div id='indexShareMask'>
							<div class="shareContent flex flex-align">
								<div class="share-wrapper">
									<div class="bg-f rel desc-box">
										<div class="title">一键复制拼团信息</div>
										<div class="txtBox">
											<div id="copy-text">{pigcms{$share_txt_img|html_entity_decode}</div>
										</div>
										<button class="btn copyBtn" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]}; </if>" data-clipboard-action="copy" data-clipboard-target="#hideShareTxt">点击复制文字(分享到微信群、QQ群)</button>
									</div>
								</div>
							</div>
						</div>

						<div class="bottom-btn">
							<a href="javascript:;" class="btn_change cur"><span>图片分享</span></a>
							<div class="copy-btn">一键复制拼团信息</div>
						</div>
					  	
				    </div>
				  	
				    <volist name="list" id="vo">
					   <div class="swiper-slide swiperOut" >
						<div class="item flex flex-align" data-status="-1">
							<img  id="image{pigcms{$i}" class="unchange" style="width: 100%;height: auto;" src="./static/images/blank.gif" data-pics="{pigcms{$config['site_url']}/village_group/goods_{pigcms{$_GET['leader_id']}_{pigcms{$vo.goods_id}?t={pigcms{:time()}" data-index="0" >
							<!-- <div class="msak"></div> -->
						</div>
					  </div>
				    </volist>

			     <!--  <div class="swiper-slide" >
			      	<div class="item flex flex-align" data-status="-1">
			      		<img src="{pigcms{$static_path}villagegroup/images/goods3.jpg" style="height: 100%;width: auto;">
			      	</div>
			      </div> -->
			    </div>
			</div>
		</div>
		<div class='imgModel' id='imgModel'>
			<div class="swiper-container animated"  id='showImg' style='height: 85%;'>
				<div class="swiper-wrapper pics">
					<div class="swiper-slide " ></div>
				</div>
			</div>
			<div class="swiper-button-next swiper-button-next1"></div>
		</div>
		<div class="coupon_mask cursor" style="display: none;"></div>
         <script type="text/javascript">
        var home=false
        </script>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>	
		<!-- weui -->
		
		<script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<!-- 一键复制 -->
		<script src="{pigcms{$static_path}villagegroup/js/clipboard.min.js"></script>
		{pigcms{$hideScript}
		<script type="text/javascript">
			
			$('.swiper-container .swiper-slide').height($(window).height()-120); 
			// var swiperSon = new Swiper('.swiper-container-son',{
			// 	navigation: {
			// 		nextEl: '.btn_change'
			// 	},
			// 	loop:true, 
			// });  
			// var swiper = new Swiper('.swiper-containerBig', {  
			// 	loop:true,
			// });  
			window.onload=function(){
				var page = 1,totalPage=parseInt('{pigcms{$totalPage}'), urlArr = [], last_img = false;
				var imgModels=null;
				var index=1;
				var loading_first = weui.loading('加载中');
				$('body').height($(window).height())
				$('#imgBox').height( $(window).height() - $('.content>p').height());
				//$('#imgBox').height( $(window).height() - $('.content>p').height()-$(".showModel").height()-20);
				$('.first_1').load(function(){
					loading_first.hide()
					$('.first_1').css('box-shadow','0 0 5px 3px #e5e5e5');
					var windowW = $(window).width() - 20
					var firstW = $('.first_1').width()
					console.log('33333',firstW, windowW )
					if (firstW > windowW) {
						$('.first_1').css({
							'width':'100%',
							'height':'auto'
						})
					}
				});

				$(document).on('click', '.copy-btn', function(){
                    $(this).addClass('cur')
                    $('.btn_change').removeClass('cur') 
                    
					$('#indexShareMask').show();
                    $('.show_tip').hide();
					$('.btn-change-content').css("cssText", "display:none !important;");
					$('#imgBox').css('margin-top', '70px');
				})

				var clipboard = new ClipboardJS('.copyBtn');
		        clipboard.on('success', function(e) {
					weui.toast('复制成功', 1500);
		        });

		        clipboard.on('error', function(e) {
		            weui.alert('复制失败，请尝试手动复制！', {
					    title: '提示',
					    buttons: [{
					        label: '好的',
					        type: 'primary',
					        onClick: function(){ console.log('ok') }
					    }]
					});
		        });

				$(document).on('click', '#imgBox .item', function(){
					console.log($(this).find('img').hasClass('first'))
					console.log($('.first_'+index).data('pics'))
					if ($(this).find('img').hasClass('first')) {
					}else{
						var url = $(this).find('img').data('pics')
					}

					if(typeof wx != 'undefined'){
						if ($(this).find('img').hasClass('first')) {
							var url = $('.first_'+index).data('pics')
							urlArr.shift();
							urlArr.unshift(url);
						}else{
							var url = $(this).find('img').data('pics')
						}
						wx.previewImage({
							current:url,
							urls:urlArr
						});
					}

				})
				$(document).on('click', '.change-model', function(){
					var loading_first = weui.loading('加载中');
					index++;
					if(index>4){
						index=1;
					}
					var src = $('.first_'+index).data('pics');
					$('.first_'+index).attr('src',src);
					$('.first_'+index).show();
					$('.first_'+index).siblings('img').hide();
					$('.first_'+index).load(function(){
						loading_first.hide();
					});
					return false
				})
				$(document).on('click', '.btn_change', function(){
					$('.show_tip').show();
					$('#indexShareMask').hide();
					$('.btn-change-content').css("cssText", "");
                     $(this).addClass('cur')
                    $('.copy-btn').removeClass('cur')
                    $('#imgBox').css('margin-top', '');
					
				})

				var image_id  = 1;
				urlArr.push('{pigcms{$config['site_url']}/village_group/first_{pigcms{$_GET['leader_id']}?t={pigcms{:time()}') ;
				var $img='<div class="swiper-slide " >'+
						'<img src="'+urlArr[0]+'" style="height:auto;width:auto;max-height:100%;max-width:100%"/>'+
						'</div>' ;
				$('.unchange').each(function(i,val){
					urlArr.push($(val).data('pics'));
					$img+='<div class="swiper-slide " >'+
							'<img src="'+$(val).data('pics')+'" style="height:auto;width:auto;max-height:100%;max-width:100%"/>'+
							'</div>' ;
				})
				var first_time = setTimeout(function(){
					var src = $('.first_1').data('pics')
					console.log(src)
					$('.first_1').attr('src',src);
					clearTimeout(first_time)
					var mySwiper = new Swiper('#imgBox', {
						preventClicks : true,
						onSlideNextStart:function(swiper, event){
							// 滑动增加一张图片
							if(page<=totalPage){
								if ($('#image'+swiper.activeIndex).hasClass('unchange')) {
									var loading = weui.loading('加载中');
									swiper.lockSwipeToNext();
									list(image_id, loading)
									image_id++;
								}
							}
						},
						onTransitionEnd: function(swipers){
							if(swipers.activeIndex==totalPage && page>totalPage && swipers.swipeDirection=='next'){
								layer.open({
									content: '已滑到最后了!'
									,shade: false
									,className:'sell-out-tip'
									,style: 'background-color:rgba(0, 0, 0, .6);display:inline-block;color:#fff;width:auto;height:auto;padding:10px;'
									,time: 2
								});

							}

						},

						onSlideChangeStart: function(){
							layer.closeAll();
						}
					})

					function list(image_id, loading){

						console.log(image_id)
						$('#image'+image_id).attr('src',$('#image'+image_id).data('pics'))
						$('#image'+image_id).removeClass('unchange')

						// all_page = data.totalPage
						page++;
						$('#image'+image_id).load(function(){
							mySwiper.unlockSwipeToNext()
							mySwiper.update()
							loading.hide()
							$('#image'+image_id).css('box-shadow','0 0 5px 3px #e5e5e5');
						});
					}
				},1000)
			}
 

			var events = navigator.userAgent; 
			if(events.indexOf('Android')>-1 || events.indexOf('Linux')>-1 || events.indexOf('Adr')>-1){
					console.log("安卓手机");
			}else if(events.indexOf('iPhone')>-1){
					//根据尺寸进行判断 苹果的型号 
					if(screen.height == 812 && screen.width == 375){
						$('.swiper-container .swiper-slide').height($(window).height()-120); 
					}else if(screen.height == 736 && screen.width == 414){
						$('.swiper-container .swiper-slide').height($(window).height()-120); 
					}else if(screen.height == 667 && screen.width == 375){
							 
					}else if(screen.height == 568 && screen.width == 320){
						$('.swiper-container .swiper-slide').height($(window).height()-150); 
					}else{
						 
					}
			} 
 
		</script>
	</body>
</html>