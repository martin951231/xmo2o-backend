<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>选择地址</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<style type="text/css">
			body{
				padding-top: 47px;
			}
			.search-box{
				padding: 8px 15px;
				border-bottom: 1px solid #efefef;
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				white-space: nowrap;
			}
			.search-box .city{
				display: inline-block;
				width: 70px;
				font-size: 12px;
				color: #999;
			}

			.search-box .input-box{
				display: inline-block;
				width: calc(100% - 75px);
				width: -webkit-calc(100% - 75px);
				background-color: #f8f8f8;
				border-radius: 15px;
				height: 30px;
				line-height: 30px;
				padding: 0 15px;
				box-sizing: border-box;
				white-space: nowrap;
			}

			.search-box .input-box .icon{
				display: inline-block;
				width: 15px;
			}

			.search-box .input-box input{
				width: calc(100% - 30px);
				width: -webkit-calc(100% - 30px);
				color: #333;
			}

			.list>div{
				color: #333;
				padding:10px 15px;
				border-bottom: 1px solid #f4f4f4;
			}

			.list>div:last-child{
				border: none;
			}

			.list .fr{
				color: #ff3112;
				font-size: 12px;
			}

			.list .fr .icon{
				vertical-align: middle;
			}
		</style>
	</head>
	<body>
		<div class="search-box">
			<span class="city"><i class="icon iconfont font_size14 mgr5 location_icon">&#xe873;</i><span id="city_name"><if condition="$now_area">{pigcms{$now_area.area_name}<else />定位中</if></span></span>
			<span class="input-box">
				<i class="icon iconfont font_size12 color_9">&#xe61a;</i>
				<input type="text" name="address" id="addressInputText" placeholder="请输入收货地址"/>
			</span>
		</div>

		<div class="list">
			<div class="now_address">
				<span class="local_address" id="local_address" data-lng="" data-lat="">定位中...</span>
				<span class="fr reflesh_location"><i class="icon iconfont ">&#xe643;</i>刷新</span>
			</div>
		</div>
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>		
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
		{pigcms{$hideScript}
		<script type="text/javascript">
			var addressGeocoder = false;
			var layerLoadTip,loadAddressTimer;
			var now_area_id = "{pigcms{$now_area.area_id|default=''}"
			var ajax_map_url = "{pigcms{$config.site_url}/index.php?g=Index&c=Map&a=suggestion&city_id={pigcms{$config.now_city}";
			$(function(){	
				FastClick.attach(document.body);
				getlocation();
				$('.reflesh_location').click(function(){
					layerLoadTip =  layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
					now_area_id='';
					$.cookie('now_city','');
					getlocation();
				})
				
				$("#addressInputText").bind('input', function(e){
					var address = $.trim($(this).val());
					if(address.length > 0){
						clearTimeout(loadAddressTimer);
						loadAddressTimer = setTimeout("searchAddress('"+address+"')", 500);
						
					}
				});
				
				$('body').on('click', '.suggest_address,.local_address', function(){
					$.cookie('now_address',$(this).html(),{expires:1,path:'/'});
					$.cookie('userLocationLong',$(this).data('lng'),{expires:1,path:'/'});
					$.cookie('userLocationLat',$(this).data('lat'),{expires:1,path:'/'});
					window.location.href="{pigcms{:U('leaderList')}"
				});
				
				
				$('#city_name').click(function(){
					window.location.href="{pigcms{:U('Changecity/index')}&village_group=1&only_city=1"
				})
			})	
		
		
			function getlocation(){
				getUserLocation({useHistory:false,okFunction:'getListGeocoderbefore',okFunctionParam:[true],errorFunction:'getListGeocoderError',errorFunctionParam:[false]});
			}
			
			function getListGeocoderError(status,msg){
				layer.open({
					content:msg
					,btn: ['刷新']
					,yes:function(){
						location.reload();
					}
				});
			}
			
			function getListGeocoderbefore(type){
				if(type == true){
					if(motify.checkApp()&& motify.checkWeixinApp()==false){
						
						$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');
						
					}else{
						
						$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&coordtype=wgs84ll&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');
					}
				}
			}
		
			function getListGeocoderBack(obj){
				if(now_area_id){
					$('#city_name').html('{pigcms{$now_area.area_name}');
				}else{
					
					$('#city_name').html(obj.result.addressComponent.city);
				}
				
				$('.suggest_address').remove();
				if(obj.result.pois[0].addr!=''){
					
					$('#local_address').html(obj.result.pois[0].name+'('+obj.result.pois[0].addr+')');
				}else{
					$('#local_address').html(obj.result.pois[0].name);
					
				}
				$('#local_address').data('lat',obj.result.pois[0].point.y);
				$('#local_address').data('lng',obj.result.pois[0].point.x);

				layer.close(layerLoadTip);
			}
			
			
			function searchAddress(address){
				$.get(ajax_map_url, {query:address}, function(data){
					console.log(data)
					if(data.status == 1){
						$('.suggest_address').remove();
						var result = data.result;
						var addressHtml = '';
						for(var i=0;i<result.length;i++){
							$('.list').append('<div class="suggest_address" data-lng="'+result[i]['long']+'" data-lat="'+result[i]['lat']+'" data-name="'+result[i]['name']+'" >'+result[i]['name']+'('+result[i]['address']+')'+"</div>")
						}
					}
				});	
			}
		</script>
	</body>
</html>