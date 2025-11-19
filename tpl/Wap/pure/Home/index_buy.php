<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>超值抢购</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
        <style>
            body{
                background: #F4F4F4;
                padding-top:50px;
            }
            *{margin: 0;padding: 0;}
            .hidden{display: none !important;}

            .rel{position: relative;}
			.flex{display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box;display:flex;}
			.flex-between{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;box-pack: center;justify-content: space-between;}
			.flex-justify-start{-webkit-box-pack: start;-webkit-justify-content: flex-start;-ms-flex-pack: start;box-pack: start;justify-content: flex-start;}
			.flex-center{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; box-align: center;align-items: center;}
			.flex_one{-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;}
			.flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}
			.flex-grow{-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex-grow: 1;-ms-flex: 1;flex-grow: 1;}

			.border-b::after{content: '';position: absolute;bottom: 0;left: 0;height: 1px;right: 0;background: #e5e5e5;transform: scaleY(0.5);}
			.mb5{margin-bottom: 5px;}
			.mt5{margin-top: 5px;}
			.mr5{margin-right: 5px;}
			.line-through{text-decoration: line-through;}

			.title{align-items: center;background: #fff;height: 40px;font-size: 14px;margin-bottom: 10px;
			box-shadow: 0 -1px 1px 1px #bbb;position: fixed;top: 0;left: 0;right: 0;z-index: 10;}
			.title .line{height: 1px;background:#ddd;margin: 0 10px;}
			.title .time{font-size: 12px;width: 18px;height: 18px;background: #333;color: #fff;display: inline-block;
			text-align: center;line-height: 18px;margin: 2px 4px 0;border-radius: 2px;}

			.shop-item{padding: 10px;width: 100%;box-sizing: border-box;background: #fff;
			margin-bottom: 10px;cursor: pointer;}
			.shop-item .shop-img{width: 85px;height: 85px;border-radius: 5px;margin-right: 10px;overflow: hidden;position: relative;}
			.shop-item .shop-img img{    width: 100%;height: 100%;border-radius: 2px;display: block;object-fit: cover;}
			.shop-item .info-box{position: relative;width: calc(100% - 112px);min-height: 80px;}
			.shop-item .small{color: #333;font-size: 12px;}
			.shop-item .small.distance{margin-top: 2px;}
			.shop-item .small.bg{background: #f4f4f4;margin-right: 5px;margin-bottom: 5px;padding: 0 10px;}
			.shop-item .name-box{position: relative;}
			.shop-item .name{font-size: 16px;font-weight: 500;display: -webkit-box;overflow: hidden;text-overflow: ellipsis;word-wrap: break-word;white-space: normal !important;-webkit-line-clamp: 2;-webkit-box-orient: vertical;margin:0 5px 4px 0;}
			.shop-item .gray{color:#bbb;}
			.shop-item .red{color:#f6503e;} 
			.shop-item .orange{color: #f17019;font-weight: bold;}
			.shop-item .bold{font-weight: bold;}
			.shop-item .right{position: absolute;right: 0;}
			.shop-item .bottom{position: absolute;bottom: 0;}
			.shop-item .desc{margin-bottom:4px;width: 100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
			.shop-item .price-box{line-height: 18px;margin-bottom: 5px;}
			.shop-item .now-price{font-size: 18px;font-weight: 500;}
			.shop-item .discount-icon .icon{margin-right:5px;margin-bottom: 5px;font-size:11px;padding:0 3px;border-radius:2px;color:#ce847f;border:1px solid #f4cfcc;background:#fff4f2; }
			.shop-item .img-tip{position: absolute;left: 0;bottom: 0;color: #fff;padding: 0 5px;font-size: 12px;
			background: linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));border-radius: 0 10px 0 5px;}
			.shop-item .sale-tip{background: linear-gradient(to right, rgb(247, 105, 57) , rgb(255, 60, 0));color: #fff;font-size: 11px;margin-right: 5px;padding: 1px 5px;border-radius: 2px;}
			.shop-item .discount-num{background: #f9e8ec;padding: 3px 5px 3px 8px;position: relative;margin-left: 12px;z-index: 3;border-radius: 2px;}
			.shop-item .discount-num::after{content: '';width: 12px;height: 12px;background: #f9e8ec;position: absolute;left: -5px;top: 3px;
			transform: rotate(45deg);border-radius: 2px;}
			.shop-item .progress{display: inline-block;width: 50%;height: 12px;border-radius: 6px;background: #fdbeb7;vertical-align: top;position: relative;overflow: hidden;}
			.shop-item .progress .line{position: absolute;top:0;left:0;width: 20%;height:100%;background:#e6170f;}
			.shop-item .progress .num{font-size: 11px;color: #fff;position: absolute;line-height: 12px;left: 10px;}
			.shop-item .buy-btn{position: absolute;right: 0;top: -14px;color: #fff;font-size: 12px;padding:5px 12px;border-radius: 20px;
			background: linear-gradient(to right, #f77750 , #fb3567);}

        
		</style>
	</head>
	<body>
		<div class='flex title'>
		  <span class='flex-grow line'></span>本场还剩
		  <span class="time-box">
		  	<!-- <span><span class='time'>2</span>天</span>
			  <span class='time'>12</span>:
			  <span class='time'>45</span>:
			  <span class='time'>56</span> -->
			  
		  </span>
		  <span class='flex-grow line'></span>
		</div>

		<div class="goods-box">
			 <!--  <div class='flex rel shop-item'>
			    <div class='shop-img'>
			      <img src='https://hf.pigcms.com/static/wxapp/demo/buy-1.png'>
			      <span class='img-tip'>品牌甄选</span>
			    </div>
			    <div class='info-box'>
			      <div class='flex flex-between name-box'>
			        <span class='flex-grow name'><span class='sale-tip'>超值</span>周黑鸭</span>
			        <span class='small distance'>8.6km</span>
			      </div>
			      <div class='small desc'>卤鸭脖（大盒）</div>
			      <div class='rel flex flex-center price-box'>
			        <span class='small red bold'>{pigcms{$config['Currency_symbol']}</span>
			        <span class='red mr5 now-price'>5</span>
			        <span class='small gray old-price'>门市价:{pigcms{$config['Currency_symbol']}10</span>
			      </div>
			      <div class='flex flex-wrap discount-icon'>
			        <span class='icon'>哈哈哈哈</span>
			      </div>
			    </div>
			  </div> -->

		</div>


		<script type="text/javascript">

			$(document).on('click', '.link', function(){
				if ($(this).data('goodsid')) {
    					buyDiscountReport($(this).data('goodsid'));
    				}
				if ($(this).data('url') != "") {
					location.href = $(this).data('url');
				}
			})

			var param = GetRequest(location.search)
			console.log('888888', param)
			var url = location.protocol+'//'+document.domain+'/appapi.php?c=Home&a=plat_buy_active'
			var postData = {}
			if (param.lng) {
				postData = {
					lng: param.lng,
					lat: param.lat
				}
			}
			
			$.post(url, postData, function(data){
				if (data.result.have) {
		    		document.title = data.result.name;
					
					window.shareData.tTitle = data.result.name + " - {pigcms{$config.site_name}";
					if(typeof wx_share_init == 'function'){
						wx_share_init();
					}
					
			    	countDown(data.result.end_time);
			    	var activity_html = '';
			    	for (var i=0; i<data.result.active_list.length; i++) {
			    		var item = data.result.active_list[i];
			    		activity_html += '<div class="flex rel shop-item link" data-url="'+ item.active_link +'" data-goodsid="'+ item.id +'"><div class="shop-img"><img src="'+ item.active_img +'"></div><div class="info-box"><div class="flex flex-between name-box"><span class="flex-grow name">'

			  			if (item.active_label != "") {
			  				activity_html += '<span class="sale-tip">'+ item.active_label +'</span>';
			  			}
			  			activity_html += item.active_name+'</span><span class="small distance">'+ (item.range ? item.range : '') +'</span></div><div class="small desc">'+ item.active_desc +'</div><div class="rel flex flex-center price-box"><span class="small red bold">{pigcms{$config['Currency_symbol']}</span><span class="red mr5 now-price">'+ item.active_price +'</span>';
			  			if (item.old_price*1 != item.active_price*1) {
			  				activity_html += '<span class="small gray old-price">门市价:{pigcms{$config["Currency_symbol"]}'+ item.old_price +'</span>';
			  			}
			  			activity_html += '</div>';
			  			if (item.active_info != '') {
			  				activity_html += '<div class="flex flex-wrap discount-icon"><span class="icon">'+ item.active_info +'</span></div>';
			  			}
			  			activity_html += '</div></div>';

			    	}
			    	$('.goods-box').html(activity_html);

			    } else {
			    	$('.goods-box').addClass('hidden');
			    }
			})
			
			function GetRequest(url){
				var theRequest = {}; 
				if(url.indexOf("?") != -1){
					var str = url.substr(1); 
					strs = str.split("&"); 
					for(var i = 0; i < strs.length; i ++) {
						console.log(strs[i].split("=")[1]);
						theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]); 
					}
				}
				return theRequest; 
			}


			// 超值抢购倒计时
			function countDown (s) {
			  var activity_time = timestampToTime(s);
			  var time_html = ''
			  if (activity_time.day > 0) {
			  	time_html += '<span class="time">'+ activity_time.day +'</span>天';
			  }
			  time_html += '<span class="time">'+ activity_time.hours +'</span>:<span class="time">'+ activity_time.min +'</span>:<span class="time">'+ activity_time.sec +'';
			  $('.time-box').html(time_html);
			  if (s == 0) {
			    return
			  }
			  setTimeout(() => {
			    s--
			    countDown(s)
			  }, 1000)
			}

			// 时间转换
			function timestampToTime (s) {
			  var day = Math.floor(s / 3600 / 24);
			  var hours = Math.floor(s / 3600 % 24);
			  var min = Math.floor(s / 60) % 60;
			  var sec = s % 60;
			  hours = add(hours);
			  min = add(min);
			  sec = add(sec);
			  return { day, hours, min, sec }
			}

			// 添 0
			function add(m) {
			  return m < 10 ? '0' + m : m
			}

			function buyDiscountReport (id) {
				$.post(location.protocol+'//'+document.domain+'/appapi.php?c=Home&a=plat_buy_active_up', {id:id})
			}
		</script>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Home/index_buy')}",
				"tTitle": "超值抢购 - {pigcms{$config.site_name}",
				"tContent": "{pigcms{$config.seo_description}"
			};
		</script>
		{pigcms{$shareScript}
	</body>
</html>