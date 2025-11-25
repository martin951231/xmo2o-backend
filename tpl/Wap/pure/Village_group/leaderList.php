<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>附近其他团长</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<style type="text/css">
			body{
				padding-top: 90px;
				background-color: #fe663f;
			}
			.current{
				color: #fff;
				padding: 0 15px;
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
			}

			.current>div:first-child{
				height: 30px;
				line-height: 30px;
				padding-top: 10px; 
			}

			.current>div:last-child{
				height: 50px;
				line-height: 50px;
			}

			.current .btn{
				float: right;
				font-size: 12px;
				border: 1px solid #fff;
				line-height: 26px;
				margin-top: 10px;
				padding: 0 12px;
				border-radius: 14px;
			}

			.leader-list{
				padding:15px 10px;
				box-sizing: border-box;
				border-radius: 15px 15px 0 0;
				background-color: #fff;
			}

			.leader-list .item{
				padding: 20px 0;
				border-bottom: 1px solid #efefef;
			}

			.leader-list .item:last-child{
				border: none;
			}

			.leader-list .item .avatar{
				width: 40px;
				height: 40px;
				border-radius: 50%;
				overflow: hidden;
			}

			.leader-list .item .right{
				width: calc(100% - 40px);
			}

			.leader-list .item .icon{
				width: 40px;
				text-align: right;
				color: #999;
				height: 100%;
			}

			.leader-list .item .info{
				width: calc(100% - 40px);
				padding-left: 10px;
				box-sizing: border-box;
			}

			.color_red{
				color: #eb391f;
				font-weight: 600;
			}
			.address-box{
				width: calc(100% - 100px);
				width: -webkit-calc(100% - 100px);
				line-height: 1.5;
			}

			.phone-box{
				display: inline-block;
			}

		</style>
	</head>
	<body style="background-color:{pigcms{$config["mobile_head_color"]}">
	<!-- style="background-image:url({pigcms{$static_path}villagegroup/images/bg.jpg); " -->
		<div class="bg-cover current"  style="background-color:{pigcms{$config["mobile_head_color"]}">
			<div class="font_size12">您当前所在的位置</div>
			<div>
				<div class="fl address-box"><span class="font_size15 ellipsis now_address"></span></div>
				<span class="btn"><a href="{pigcms{:U('selectAddress')}" >修改地址</a></span>
			</div>
		</div>
		<div class="leader-list"> 

		</div>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script src="{pigcms{$static_path}villagegroup/js/base.js"></script>	
		{pigcms{$hideScript}
		<script type="text/javascript">
			var village_group_hide_detail = {pigcms{$village_group_hide_detail|intval};
			var noAnimate=true;
			$(function(){
				// 强制刷新,针对IOS手机从上一页返回页面不刷新的问题
				if(motify.checkIos()){
					forceRefresh()
				}

				var wh = $(window).height()
				$('.leader-list').css('min-height',(wh - 90)+'px');

				var now_address = $.cookie('now_address');
				var userLocationLong = $.cookie('userLocationLong');
				var userLocationLat = $.cookie('userLocationLat');
				if(now_address && userLocationLong && userLocationLat){
					$('.now_address').html(now_address);
					get_leader_list()
				}else{
					getlocation();
				}
				
				

			})
			
			function onPageFinished(){
				
			}
			
			function get_leader_list(){
				$.post('{pigcms{:U('get_leader_list')}',{lat:$.cookie('userLocationLat'),lng:$.cookie('userLocationLong')},function(data){
					var html = '';
					if(data){
						
						for(var i=0 ;i<data.length;i++){
							 
							html+='<div class="ks-clear item link" data-url="{pigcms{:U('index')}&leader_id='+data[i].id+'">';
							html+='	<div class="fl avatar"><img src="'+data[i]['avatar']+'"></div>';
							html+='	<div class="fl flex flex_justify right">';
							html+='		<div class="info">';
							html+='			<div class="mgb5">';
							html+='				<span class="font_size15">团长：'+data[i]['name']+'</span>';
							html+='				<span class="fr font_size13 color_red">距离您'+data[i]['juli_txt']+'</span>';
							html+='			</div>';
							html+='			<div class="mgb5 font_size15">{pigcms{$config['house_name']}：'+data[i]['village_name']+'</div>';
							if(village_group_hide_detail){
								html+='			<a href="tel:'+data[i]['phone']+'"><div class="mgb5 font_size15 phone-box">电话：<font color="blue">'+data[i]['phone']+'</font></div></a>';
							}
							html+='			<div class="font_size13 color_9">取货地址：'+data[i]['pick_addr']+'</div>';
							html+='		</div>';
							html+='		<div class="icon"><i class="icon iconfont font_size14">&#xe603;</i></div>';
							html+='	</div>';
							html+='</div>';
						}
					}
					if(html){
						$('.leader-list').html(html)
					}else{
					    var $html="<div style='padding-top: 40%;text-align:center'><img src='{pigcms{$static_path}village_group/images/no_tuanzhang.png' style='width:50%;margin:auto'><p style='color:#ddd;padding-top:10px'>附近暂无团长</p></div>"
						$('.leader-list').html($html)
					}
				},'json');
			}
			
			function getlocation(){
				getUserLocation({useHistory:false,okFunction:'getListGeocoderbefore',okFunctionParam:[true],errorFunction:'getListGeocoderError',errorFunctionParam:[false]});
			}
			
			function getListGeocoderError(status,msg){
				// layer.open({
				// 	content:msg
				// 	,btn: ['刷新']
				// 	,yes:function(){
				// 		location.href = "{pigcms{:U('selectAddress')}";
				// 	}
				// });
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
				$('.now_address').html(obj.result.pois[0].name+'('+obj.result.pois[0].addr+')');
				$('.now_address').data('lat',obj.result.pois[0].point.y);
				$('.now_address').data('lng',obj.result.pois[0].point.x);

				$.cookie('userLocationLong',obj.result.pois[0].point.x,{expires:700,path:'/'});
				$.cookie('userLocationLat',obj.result.pois[0].point.y,{expires:700,path:'/'});
				$.cookie('now_address',$('.now_address').html(),{expires:700,path:'/'});
				setTimeout(function(){
					location.reload();
				},200);
			}
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
	</body>
</html>