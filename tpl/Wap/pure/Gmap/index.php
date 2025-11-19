<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="keywords" content="{pigcms{$config.seo_keywords}"/>
	<meta name="description" content="{pigcms{$config.seo_description}"/>
	<title>{pigcms{:L_('选择位置')}</title>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_5e96991.css"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_dd39d16.css"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/address_9d295cd.css"/>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js"></script>
	<script type="text/javascript">
		var is_google_map = "{pigcms{$config.google_map_ak}";
	</script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t=123"></script>
	<style>
		#cmmap{border-bottom:none;}
		.map .dot{
			width:70px;
			height:70px;
			margin-top:-70px;
			margin-left:-35px;
			background: url({pigcms{$static_path}images/gmap/marker_red.png) 0 0 no-repeat;
			background-size:100%;
		}
		.address_btn{
			position: absolute;
			z-index: 9999;
			bottom: 30px;
			width: 80%;
			left: 10%;
			background: red;
			height: 50px;
			line-height: 50px;
			text-align: center;
			font-size: 18px;
			color: white;
			border-radius: 10px;
		}
		.tip_bg1{
			z-index: 9998;
			position:absolute;
			left:0px;
			width:100%;
			top:0px;
			height:160px;
			background-image: linear-gradient(rgba(255,255,255,0.85),rgba(255,255,255,0.85));
		}
		.tip_bg2{
			z-index: 9998;
			position:absolute;
			left:0px;
			width:100%;
			top:160px;
			height:80px;
			background-image: linear-gradient(rgba(255,255,255,0.85),rgba(255,255,255,0.0));
		}
		.tip_box{
			position: absolute;
			left: 10%;
			z-index: 9999;
			top: 50px;
			text-align: center;
			width: 80%;
		}
		.tip_box .title{
			color: red;
			font-size: 24px;
		}
		.tip_box .address{
			margin-top:10px;
			color: #666;
			font-size: 14px;
		}
		.reload_box{
			z-index: 9999;
			bottom: 110px;
			position: absolute;
			right: 10px;
			border: 1px solid #f1f1f1;
			background: white;
			border-radius: 5px;
			padding: 5px;
			display:none;
		}
		.reload_box img{
			width: 28px;
			height: 28px;
			display: block;
		}
		#search_box{
			top:30px;
			left:0px;
			width:100%;
			height: calc(100vh - 30px);
			position:absolute;
			z-index:19997;
			background:white;
			display:none;
		}
		.search_bg1{
			z-index: 9998;
			position:absolute;
			left:0px;
			width:100%;
			top:0px;
			height:30px;
			display:none;
		}
		.search_input_box{
			padding:0 8px;
			position:relative;
			display: -webkit-box;
			display: -webkit-flex;
			display: flex;
			box-sizing: border-box;
			border-bottom: 1px solid #f1f1f1;
		}
		.search_input_div{
			-webkit-box-flex: 1;
			-webkit-flex: auto;
			flex: auto;
			position: relative;
		}
		.search_input{
			border:none;
			outline:0;
			padding:10px 26px;
			width:calc(100% - 52px);
			background:#eeecf2 url({pigcms{$static_path}images/saeh_03.png) no-repeat 8px 12px;
			background-size:12px;
		}
		.search_cancel{
			margin-left: 8px;
			line-height: 36px;
			color: #576b95;
			color: var(--weui-LINK);
			white-space: nowrap;
		}
		.search_clear{
			position: absolute;
			right: 0px;
			width: 40px;
			height: 100%;
			top: 0px;
			display:none;
		}
		.search_clear_icon{
			background:#b9b7bd url({pigcms{$static_path}images/panorama_close.png) no-repeat 0px 0px;
			background-size:100%;
			position: absolute;
			right: 8px;
			width: 20px;
			height: 20px;
			top: 50%;
			margin-top: -10px;
			border-radius:50%;
		}
		
		.search_list, .near_list_box{
			overflow:scroll;
			-webkit-overflow-scrolling:touch;
		}
		.search_list{
			padding: 0px 10px;
			margin-top: 10px;
			display:none;
		}
		.search_item{
			padding: 10px 0;
			border-bottom: 1px solid #f1f1f1;
			position:relative;
		}
		.search_item:last-child{
			border-bottom:none;
		}
		.search_item .name{
			font-size: 14px;
		}
		.search_item .desc{
			font-size: 10px;
			color: #888;
		}
		.search_item .range{
		    position: absolute;
			right: 0;
			top: 50%;
			margin-top: -12px;
			font-size: 10px;
			color: #888;
		}

		
		/*加载层*/
		.motifyShade{
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			bottom:0;
			padding: 0;
			z-index: 998;
			width: 100%;
		}
		.motify {
			display: none;
			position: fixed;
			top: 35%;
			left: 50%;
			width: 260px;
			padding: 0;
			margin: 0 0 0 -130px;
			z-index: 999;
			background: rgba(0, 0, 0, 0.8);
			color: #fff;
			font-size: 14px;
			line-height: 1.5em;
			border-radius: 6px;
			-webkit-box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
			box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
		}
		.motify .motify-inner {
			padding: 10px 10px;
			text-align: center;
			word-wrap: break-word;
		}
		
		.near_list_box{
			padding-top: 20px;
		}
		.now_address{
			padding: 0 10px;
		}
		.near_list_tip{
			margin-top: 10px;
			font-size: 10px;
			text-align: center;
			background: #f7f4f8;
			padding: 5px 0;
			color: #666;			
		}
		.near_list{
			padding:0 10px;
		}
	</style>
</head>
<body>
	<div class="map">
		<div class="MapHolder" id="cmmap"></div>
		<div class="dot" style="display:block;"></div>
	</div>
	<div class="tip_bg1"></div>
	<div class="tip_bg2"></div>
	<div class="tip_box">
		<div class="title">{pigcms{:L_('点击可手动输入')}</div>
		<div class="address" id="address">loading...</div>
	</div>
	
	<div class="search_bg1"></div>
	<div class="search_box" id="search_box">	
		<div class="search_input_box">
			<div class="search_input_div">
				<input type="search" id="search_txt" class="search_input" />
				<div class="search_clear" id="search_clear">
					<div class="search_clear_icon"></div>
				</div>
			</div>
			<a href="javascript:" class="search_cancel" id="searchCancel">{pigcms{:L_('取消')}</a>
		</div>
		<div class="near_list_box" id="near_list_box">
			<div class="now_address" id="now_address"></div>
			<div class="near_list_tip">{pigcms{:L_('附近地点')}</div>
			<div class="near_list" id="near_list"></div>
		</div>
		<div class="search_list" id="search_list"></div>
	</div>
	<div class="reload_box" id="reload_box">
		<img src="{pigcms{$static_path}images/gmap/reload.png"/>
	</div>
	<div class="address_btn" id="address_btn">{pigcms{:L_('设置收货地址')}</div>
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key={pigcms{$config.google_map_ak}&language=en"></script>
    <script type="text/javascript">
		var Gmap = null;
		var locationMark = null;
		var geocoder = new google.maps.Geocoder();
		$(function(){
            $('#cmmap').height($(window).height());
			
			// 谷歌地图API功能
            Gmap = new google.maps.Map(document.getElementById('cmmap'), {
                mapTypeControl:false,
                zoom: 18,
                center: {lng:parseFloat({pigcms{$now_city.area_lng}),lat:parseFloat({pigcms{$now_city.area_lat})},
                // center: {lng:104.9168172,lat:11.5511561},
				disableDefaultUI:true,
				zoomControl:false,
				clickableIcons:false,
				gestureHandling: 'greedy'
            });
			
			google.maps.event.addListener(Gmap, "dragstart", function(event){
				$('.tip_bg1, .tip_bg2, .tip_box').hide();
			});
			
			google.maps.event.addListener(Gmap, "dragend", function(event){
				$('.tip_bg1, .tip_bg2, .tip_box').show();
				$('#address').html('loading...');
				var latLng = Gmap.getCenter();
				geocoderGmap(latLng.lat(), latLng.lng());
			});
			
			getUserLocation({useHistory:false, reportLocation:false, okFunction:'setMarker'});
			
			setInterval(function(){
				getUserLocation({useHistory:false, reportLocation:false, okFunction:'setMarker'});
			}, 5000);
			
			$('#reload_box').click(function(){
				getUserLocation({useHistory:false, reportLocation:false, okFunction:'reloadMap'});
			});
			
			$('.near_list_box, .search_list').height($(window).height() - 30 - $('.search_input_box').height() - 20 - 40)
			
			$('.tip_box').click(function(){
				$('.tip_box').hide();
				$('.search_bg1, .search_box').show();
				$('#search_txt').focus();
				
				$('#near_list_box').show().scrollTop(0);
				
				var latLng = Gmap.getCenter();
				$.post("{pigcms{$config.site_url}/appapi.php?c=Map&a=geocoder_google", {lng:latLng.lng(), lat:latLng.lat()}, function(result){
					console.log('search_result', result);
					var search_html = '';
					for(var i in result.result){
						search_html+= '<div class="search_item" data-place_id="' + result.result[i].place_id + '" data-lng="' + result.result[i].lng + '" data-lat="' + result.result[i].lat + '">';
						search_html+= 	'<div class="name">' + result.result[i].name + '</div>';
						search_html+= 	'<div class="desc">' + result.result[i].address_name + '</div>';
						search_html+= 	'<div class="range">' + result.result[i].range + '米</div>';
						search_html+= '</div>';
					}
					$('#near_list').html(search_html);
				});
			});
			
			var search_timer = null;
			$('#search_txt').bind('input', function(){
				if(search_timer){
					clearTimeout(search_timer);
				}
				$('#near_list_box').hide();
				var search_txt = $.trim($('#search_txt').val());
				if(!search_txt){
					$('#search_list').html('');
					$('#search_clear').hide();
					return ;
				}
				$('#search_clear').show();
				search_timer = setTimeout(function(){
					$.post("{pigcms{$config.site_url}/appapi.php?c=Map&a=suggestion", {query:search_txt}, function(result){
						console.log('search_result', result);
						var search_html = '';
						for(var i in result.result){
							search_html+= '<div class="search_item" data-place_id="' + result.result[i].place_id + '">';
							search_html+= 	'<div class="name">' + result.result[i].name + '</div>';
							search_html+= 	'<div class="desc">' + result.result[i].address_name + '</div>';
							search_html+= '</div>';
						}
						$('#search_list').html(search_html).show();
					});
				},300);
			});
			$('#search_clear').click(function(){
				$('#search_list').html('');
				$('#search_clear').hide();
				$('#search_txt').val('').focus();
			});
			$('#address_btn').click(function(){
				var latLng = Gmap.getCenter();
				choose_address({name:$('#address').text(), lng:latLng.lng(), lat:latLng.lat()});
			});
			$(document).on("click",".search_item",function(){
				var _this = $(this);
				if(_this.data('lng')){
					if({pigcms{:C('config.country_code') == 'au' ? 'false' : 'true'}){
						$('#searchCancel').trigger('click');
						Gmap.setCenter(new google.maps.LatLng(_this.data('lat'), _this.data('lng')));
						$('#address').html(_this.find('.desc').text());
					}else{
						choose_address({name:_this.find('.desc').text(), lng:_this.data('lng'), lat:_this.data('lat')});
					}
				}else{
					$.post("{pigcms{$config.site_url}/appapi.php?c=Map&a=getAddressByPlaceId", {place_id:_this.data('place_id')}, function(result){
						if({pigcms{:C('config.country_code') == 'au' ? 'false' : 'true'}){
							$('#searchCancel').trigger('click');
							Gmap.setCenter(new google.maps.LatLng(result.result.lat, result.result.lng));
							$('#address').html(_this.find('.desc').text());
						}else{
							choose_address({name: result.result.address_name, lng:result.result.lng, lat:result.result.lat});
						}
					});
				}
			}); 
			
			$('#searchCancel').click(function(){
				$('.tip_box').show();
				$('.search_bg1, .search_box').hide();
				
				$('#search_txt').val('');
				$('#search_list').html('');
				$('#search_clear').hide();
			});
		});
		
		function choose_address(addressObj){
			
			let nameObj = addressObj.name.split(', ');
			if(nameObj.length >= 3){
				addressObj.name = nameObj[0];
			}
			
			console.log('choose_address', addressObj);
			
			if(location.href.indexOf('from=uniapp') !== -1){
				addressObj.module = 'locationPicker';
				addressObj.poiname = addressObj.name;
				addressObj.latlng = {
					lat: addressObj.lat,
					lng: addressObj.lng,
				};
				window.parent.postMessage(addressObj, '*');
			}else if(motify.checkApp()){
				addressObj.address_name = addressObj.name;
				addressObj.name = 'locationPicker';
				if(motify.checkIos()){
					window.webkit.messageHandlers.appJSBridgeMethod.postMessage(addressObj);
				}else{
					window.lifepasslogin.locationPicker(JSON.stringify(addressObj));
				}
			}
		}
		
		function reloadMap(userLonglat,userLong,userLat){
			Gmap.setCenter(new google.maps.LatLng(userLat, userLong));
		}
		
		function setMarker(userLonglat,userLong,userLat){
			console.log('userLonglat', userLonglat);
			console.log('userLong', userLong);
			console.log('userLat', userLat);
			
			if(locationMark == null){
				Gmap.setCenter(new google.maps.LatLng(userLat, userLong), 16);
				
				geocoderGmap(userLat, userLong);
				
				var iconImage = {
					url: '{pigcms{$static_path}images/gmap/my_pos.png',
					scaledSize: new google.maps.Size(60, 60),
					anchor: new google.maps.Point(30, 35)
				}
				locationMark = new google.maps.Marker({
					position: new google.maps.LatLng(userLat, userLong),
					icon: iconImage,
					map: Gmap
				});
				locationMark.setAnimation(google.maps.Animation.DROP);
				
				$('#reload_box').show();
			}else{
				locationMark.setPosition(new google.maps.LatLng(userLat, userLong));
			}
		}
		
		function geocoderGmap(lat, lng){
			geocoder.geocode({location:new google.maps.LatLng(lat, lng)},function geoResults(results, status){
				//这里处理结果和上面一模一样
				if (status == google.maps.GeocoderStatus.OK) {
					console.log('results[0]',results[0]);
					var street_number = '';
					var street = '';
					for(var i in results[0].address_components){
						var item = results[0].address_components[i];
						if(item.types[0] == 'street_number'){
							street_number = item.short_name;
						}
						if(item.types[0] == 'route'){
							street = item.short_name;
						}
					}
					if(street){
						$('#address,#now_address').html((street_number ? street_number + ' ' : '') + street);
					}else{
						$('#address,#now_address').html(results[0].formatted_address);
					}
				}else{
					//alert("：error " + status);
				}
			});
		}
	</script>
</body>
</html>