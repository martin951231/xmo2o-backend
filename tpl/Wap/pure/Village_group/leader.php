<!-- <?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?> -->
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config['village_group_alias']}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/leader.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css"/>
	</head>
	<body class="leader-page">
		<div style="display:none"><img src="{pigcms{$static_path}villagegroup/images/logo.jpg"></div>
		<div class="address-box">
			<div class="color_9 font_size12">当前地址</div>
			<div class="font_b font_size16" id="local_address">定位中...</div>
			<!-- <div class="near"><a href="{pigcms{:U('leaderList')}"><button class="link" data-url="{pigcms{:U('leaderList')}">附近其他团长</button></a></div> -->
			<div class="near"><button class="link" data-url="{pigcms{:U('leaderList')}">附近其他团长</button></div>
		</div>

		<div class="leader-box mg15">
            <if condition="$header.distance neq ''">
                <div class="distance align_left"><span>距离您{pigcms{$header.distance}</span></div>
            </if>
			<div class="avatar"><img src="{pigcms{$header.avatar}"></div>
			<div class="font_size18">团长：{pigcms{$header.name}</div>
			<div class="color_9 font_size12">社区：{pigcms{$header.pick_address.name}</div>

			<if condition="!$config['village_group_hide_detail']">
				<div class="color_9 font_size12 dis-inblock wx-number" data-clipboard-action="copy" data-clipboard-target="#wx-input" onclick="">微信号：<input id='wx-input' class="color_9 font_size12" style="display: inline-block;min-width: 50px;" type="text" value='{pigcms{$header.wx_number}' readonly="" ></div>
			</if>

			<div class="pd10 pdt15">
				<if condition="!$config['village_group_hide_detail']">
					<a href="tel:{pigcms{$header.phone}"><div class="address"><i class="icon iconfont mgr5" id='icon-phone'>&#xe6dd;</i>电话：<font color="blue">{pigcms{$header.phone}</font></div></a>
				</if>
			<div class="address"><i class="icon iconfont mgr5" id='icon-location'>&#xe60b;</i>地址：{pigcms{$header.pick_address.pick_addr}</div>
			</div>
			
			<div class="btn-box"><a href="{pigcms{:U('index',array('leader_id'=>$header['id']))}"><button style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};    border: 1px solid {pigcms{$config["mobile_head_color"]};</if>">选择此团长</button></div>
		</div>
	</body>
	
	<script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script> 
	<script src="{pigcms{$static_path}villagegroup/js/clipboard.min.js"></script>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>		
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
	<script src="{pigcms{$static_path}villagegroup/js/base.js"></script>	
	{pigcms{$hideScript}
	<script>
		var noAnimate=true;
		var user_long = 0;
		var user_lat = 0;
		$(function(){	
			FastClick.attach(document.body);
			// 强制刷新,针对IOS手机从上一页返回页面不刷新的问题
			forceRefresh()

			var clipboard = new ClipboardJS('.wx-number');
	        clipboard.on('success', function(e) {
				weui.toast('复制微信号成功', 1500);
	        });

	        clipboard.on('error', function(e) {
	            weui.alert('复制失败，请尝试手动复制！', {
				    title: '提示',
				    buttons: [{
				        label: '好的',
				        type: 'primary',
				        onClick: function(){
				          console.log('ok');
				     	}
				    }]
				});
	        });

			// input的宽度自适应
			var currentFont = "12px";
		    var currentText = $('#wx-input').val();
		    var currentWidth = GetCurrentStrWidth(currentText, currentFont);
			$('#wx-input').width(currentWidth)
			
			var now_address = $.cookie('now_address');
			var userLocationLong = $.cookie('userLocationLong');
			var userLocationLat = $.cookie('userLocationLat');
			console.log('555555555588', now_address, userLocationLong, userLocationLat)
			if(now_address && userLocationLong && userLocationLat){
				$('#local_address').html(now_address);
			}else{
				getlocation();
			}
		});

		// 计算文本宽度
		function GetCurrentStrWidth(text, font) {
	        var currentObj = $('<pre>').hide().appendTo(document.body);
	        $(currentObj).html(text).css('font', font);
	        var width = currentObj.width();
	        currentObj.remove();
	        return width;
	    }

		function getlocation(){
			getUserLocation({useHistory:false,okFunction:'getListGeocoderbefore',okFunctionParam:[true],errorFunction:'getListGeocoderError',errorFunctionParam:[false]});
		}
		
		function getListGeocoderError(status,msg){
			layer.open({
				content:msg
				,btn: ['刷新']
				,yes:function(){
					location.href = "{pigcms{:U('selectAddress')}";
				}
			});
		}
		
		function getListGeocoderbefore(type){
			if(type == true){
				if(motify.checkLifeApp()){
					$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');	
				}else{
					$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&coordtype=wgs84ll&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');
				}
			}
		}

		function getListGeocoderBack(obj){
			console.log('5555555555', obj)
			$('.suggest_address').remove();
			$('#local_address').html(obj.result.pois[0].name+'('+obj.result.pois[0].addr+')');
			$('#local_address').data('lat',obj.result.pois[0].point.y);
			$('#local_address').data('lng',obj.result.pois[0].point.x);
			
			$.cookie('userLocationLong',obj.result.pois[0].point.x,{expires:700,path:'/'});
			$.cookie('userLocationLat',obj.result.pois[0].point.y,{expires:700,path:'/'});
			$.cookie('now_address',$('#local_address').html(),{expires:700,path:'/'});
			setTimeout(function(){
				location.reload();
			},200);
		}
	</script>
</html>