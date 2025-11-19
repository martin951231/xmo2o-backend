function goodList_before(){
	if($('.goodListBox.row .goodList').size() > 0){
		$('.goodListBox.row .goodList').each(function(i,item){
			var itemWidth = ($(item).width() - 15)/2.3;
			$(item).find('.goodItem').width(itemWidth);
			$(item).find('.goodImage img').height(itemWidth*500/900);
		});
	}
	if($('.goodListBox.list .goodList').size() > 0){
		$('.goodListBox.list .goodList .goodItem').css('width','');
		$('.goodListBox.list .goodList .goodItem').css('margin-bottom','');
		$('.goodListBox.list .goodList .goodItem').css('padding-bottom','');
		$('.goodListBox.list .goodList').each(function(i,item){
			console.log($(item).find('.goodItem').size());
			if($(item).find('.goodItem').size() % 2 == 1){
				$(item).find('.goodItem').eq(-2).css({'margin-bottom':'15px','padding-bottom':'15px'})
			}
			var itemWidth = ($(item).width() - 15)/2;
			$(item).find('.goodItem').width(itemWidth);
			$(item).find('.goodImage img').height(itemWidth*500/900);
		});
	}
}

var motify = {
	timer:null,
	/*shade 为 object调用 show为true显示 opcity 透明度*/
	log:function(msg,time,shade){
		$('.motifyShade,.motify').hide();
		if(motify.timer) clearTimeout(motify.timer);
		if($('.motify').size() > 0){
			$('.motify').show().find('.motify-inner').html(msg);
		}else{
			$('body').append('<div class="motify" style="display:block;z-index:9999999999999;"><div class="motify-inner">'+msg+'</div></div>');
		}
		if(shade && shade.show){
			if($('.motifyShade').size() > 0){
				$('.motifyShade').css({'background-color':'rgba(0,0,0,'+(shade.opcity ? shade.opcity : '0.3')+')'}).show();
			}else{
				$('body').append('<div class="motifyShade" style="display:block;background-color:rgba(0,0,0,'+(shade.opcity ? shade.opcity : '0.3')+');"></div>');
			}
		}
		if(typeof(time) == 'undefined'){
			time = 3000;
		}
		if(time != 0){
			motify.timer = setTimeout(function(){
				$('.motify').hide();
			},time);
		}
	},
	clearLog:function(){
		$('.motifyShade,.motify').hide();
	},
	checkMobile:function(){
		if(/(iphone|ipad|ipod|android|windows phone)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
    checkIos:function(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
    checkAndroid:function(){
        if(/(android)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
	checkWeixin:function(){
		if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
	checkWeixinApp:function(){
		if(window.__wxjs_environment === 'miniprogram' || (motify.checkWeixin() && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
			return true;
		}else{
			return false;
		}
	},
    checkApp:function(){
        if(/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || motify.checkWeixinApp()){
            return true;
        }else{
            return false;
        }
    },
	checkLifeApp:function(){
        if((/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase())) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
    },
	getLifeAppVersion:function(){
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return 0;
		}else{
			return parseInt(arr[1]);
		}
	},
	getAndroidVersion:function(){
		var index = navigator.userAgent.indexOf("Android");
		if(index >= 0){
			var androidVersion = parseFloat(navigator.userAgent.slice(index+8));
			if(androidVersion > 1){
				return androidVersion;
			}else{
				return 100;
			}
		}else{
			return 100;
		}
	}
};


var store_lat=0,store_lng=0,store_address='';
function nav_info(userLonglat,userLong,userLat){
	if(typeof(is_google_map) != "undefined" && is_google_map){
        url = get_route + '&origin='+ userLat + ',' +userLong +'&destination=' + store_lat + ',' + store_lng;
		//url = 'https://www.google.com/maps/embed/v1/directions?key=' + is_google_map + '&origin='+ userLat + ',' + userLong +'&destination=' + store_lat + ',' + store_lng;
	}else{
        url = get_route + '&origin='+ (userLong * 100000) + ',' + (userLat * 100000) +'&destination=' + (store_lng * 100000) + ',' + (store_lat * 100000) +'&adress='+encodeURIComponent(store_address);
        //url = "https://api.map.baidu.com/direction?origin=latlng:" + userLat + "," + userLong + "|name:" + encodeURIComponent('当前位置') + "&destination=latlng:"+store_lat+","+store_lng+"|name:"+encodeURIComponent(store_address)+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
	}
	location.href = url;
}

$(function(){
	FastClick.attach(document.body);
	
	//判断打开URL
	$('body').delegate('.link','click',function(){
		var jpurl = $(this).data('url');
		if(!jpurl){
			return false;
		}
		if($(this).hasClass('newWin') && typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
			jpurl = jpurl.replace('http://','https://');
			if(jpurl.indexOf('https://') === -1){
				jpurl = 'https://'+location.host + jpurl;
			}
			if(jpurl.indexOf('c=My&a=adress') > 0){
				wx.miniProgram.navigateTo({url:'/pages/my/address'});
			}else{
				var webview_title = $(this).data('title');
				if(!webview_title){
					if($(this).text() == $(this).html()){
						webview_title = $(this).html();
					}else if(jpurl.indexOf('c=Groupnew&a=detail') > 0){
						webview_title = '商品详情';
					}else if(jpurl.indexOf('c=My&a=concact_kefu') > 0){
						webview_title = '联系客服';
					}else{
						webview_title = '打开页面';
					}
				}
				wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(jpurl)+'&webview_title='+encodeURIComponent(webview_title)});
			}
		}else if($(this).hasClass('newWin') && motify.checkLifeApp() && motify.getLifeAppVersion() >= 1300){
			jpurl = jpurl.replace('http://','https://');
			if(jpurl.indexOf('https://') === -1){
				jpurl = 'https://'+location.host + jpurl;
			}
			
			if(motify.checkIos()){
				var iosHref = window.btoa(jpurl);
				iosHref = iosHref.replace('/','&');  
				iosCommonNew.iosFunction('createwebview/' + iosHref, {name:'createwebview', url: iosHref}); 
			}else{
				window.lifepasslogin.createwebview(jpurl);
			}
		}else{
			location.href = jpurl;
		}
	});
	$('.navi').click(function(){
		var _this = $(this);
		if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
			return false;
		}
		if(typeof wx !=='undefined'){	
			var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
			$.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
				layer.close(layerLoadTip);
				if(result['status'] == 1){
					wx.openLocation({
						latitude: result['info']['lat'],
						longitude: result['info']['lng'],
						name: _this.data('name'), // 位置名
						address: _this.data('address'), // 地址详情说明
						scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
						infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
					});
				}else{
					layer.open({
						content: result.msg
						,btn: '好的'
					});
				}
			});
		}else if(motify.checkApp() && motify.checkAndroid()){
			window.lifepasslogin.startToNavigation(_this.data('lng'),_this.data('lat'),_this.data('address'));
		}else if(motify.checkApp() && motify.checkIos()){
			iosCommonNew.iosFunction('map_navigation', {name: 'map_navigation', lat: _this.data('lat'), lng: _this.data('lng'), address: _this.data('address')});
		}else{
            var url = '';
            var user_address = $.cookie('user_address');
            if(user_address){
                user_address = $.parseJSON(user_address);
            }
            store_lat=_this.data('lat'),store_lng=_this.data('lng'),store_address=_this.data('address');
            if(user_address && user_address.longitude && user_address.latitude){
				if(typeof(is_google_map) != "undefined" && is_google_map){
					url = get_route + '&origin='+ user_address.latitude + ',' + user_address.longitude +'&destination=' + store_lat + ',' + store_lng;
					// url = 'https://www.google.com/maps/embed/v1/directions?key=' + is_google_map + '&origin='+ user_address.latitude + ',' + user_address.longitude +'&destination=' + store_lat + ',' + store_lng;
				}else{
                    url = get_route + '&origin='+ user_address.latitude + ',' + user_address.longitude +'&destination=' + store_lat + ',' + store_lng +'&adress='+encodeURIComponent(store_address);
					// url = "https://api.map.baidu.com/direction?origin=latlng:"+user_address.latitude+","+user_address.longitude+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+store_lat+","+store_lng+"|name:"+encodeURIComponent(store_address)+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
				}
				location.href = url;
            }else{
                getUserLocation({useHistory:false,okFunction:'nav_info'});
            }
		}
		return false;
	});
	$('.phone').click(function(){
		var phone = $(this).data('phone');
		if(!phone){
			return false;
		}
		if(typeof phone != 'string'){
			phone = $.trim(phone.toString());
		}
		if(phone == ''){
			return false;
		}
		var phoneArr = phone.split(' ');
		if(phoneArr.length == 1){
			location.href = 'tel:'+phoneArr[0];
		}else{
			actionsheet({
				title:'拨打电话',
				itemList:phoneArr,
				success:function(res){
					location.href = 'tel:'+res.text;
					console.log(res);
					return true;
				}
			});
		}
		return false;
	});
});

function actionsheet(arr){
	if(typeof arr != 'object' || typeof arr.itemList != 'object' || arr.itemList.length < 1){
		return false;
	}
	if(!arr.itemColor || arr.itemColor == ''){
		arr.itemColor = '#000000';
	}
	if(!arr.title || arr.title == ''){
		arr.title = '选择操作';
	}
	var html = '<div id="actionsheet" class="layui-m-layer layui-m-layer0">';
		html+= '	<div class="layui-m-layershade"></div>';
		html+= '		<div class="layui-m-layermain">';
		html+= '			<div class="layui-m-layersection">';
		html+= '				<div class="layui-m-layerchild layui-m-layer-footer layui-m-anim-up">';
		html+= '					<div class="layui-m-layercont">'+arr.title+'</div>';
		html+= '						<div class="layui-m-layerbtn">';
		for(var i in arr.itemList){
			html+= '						<span class="item" data-index="' + i + '" style="color:'+arr.itemColor+';">' + arr.itemList[i] +'</span>';
		}
		html+= '							<span class="cancel">取消</span>';
		html+= '						</div>';
		html+= '					</div>';
		html+= '			</div>';
		html+= '	</div>';
		html+= '</div>';
	$('body').append(html);
	$('#actionsheet .layui-m-layershade,#actionsheet .cancel').click(function(){
		$('#actionsheet').remove();
	});
	$('#actionsheet .layui-m-layerbtn .item').click(function(){
		if(typeof arr.success == 'function'){
			var status = arr.success({index:$(this).data('index'),text:$.trim($(this).text())});
			if(status !== false){
				$('#actionsheet').remove();
			}
		}else{
			$('#actionsheet').remove();
		}
	});
}

var common = {
    getAppDomain:function(){
		var reg = /app_domain=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '';
		}else{
			return arr[1];
		}
	},
	checkWeixin:function(){
		if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
	regWxJs:function(){
        if(common.checkWeixin()){ 
        	$.getScript('https://res.wx.qq.com/open/js/jweixin-1.4.0.js',function(){
                var url = requestUrl + 'Config&a=wx_config';
                var params = {work:'',page:'',location_url:location.href.split('#')[0]}
                $.post(url, params, function(res){
                    if(res){
                        if (res.errorCode == '0') {
                            var data = res.result
                            if(data.appId == undefined || data.appId == ''){
                                return false;
                            }
                            wxSdkLoad = true
                            wx.config({
                                debug:false,
                                appId:data.appId,
                                timestamp:data.timestamp,
                                nonceStr:data.nonceStr,
                                signature:data.signature,
                                jsApiList:[
                                    'checkJsApi',
                                    'onMenuShareTimeline',
                                    'onMenuShareAppMessage',
                                    'onMenuShareQQ',
                                    'onMenuShareWeibo',
                                    'scanQRCode',
                                    'previewImage',
                                    'openLocation',
                                    'getLocation',
                                    'getNetworkType'
                                ]
                            }); 
                              
                    
                            if(data.share){
                                wx.ready(function(){
                                    wx.showOptionMenu();
                                    wx.onMenuShareAppMessage({
                                        title: data.share.title,
                                        desc: data.share.content,
                                        link: data.share.url ? data.share.url : location.href,
                                        imgUrl: data.share.image,
                                        type: '',
                                        dataUrl: '',
                                        success: function(){ 
                                            motify.log('分享成功');
                                        },
                                        cancel: function(){
                                            // motify.log('取消了分享');
                                        }
                                    });
                                    wx.onMenuShareTimeline({
                                        title: data.share.title,
                                        link: data.share.url ? data.share.url : location.href,
                                        imgUrl: data.share.image,
                                        success: function(){ 
                                            motify.log('分享成功');
                                        },
                                        cancel: function(){ 
                                            // motify.log('取消了分享');
                                        }
                                    });
                                    wx.onMenuShareWeibo({
                                        title: data.share.title,
                                        desc: data.share.content,
                                        link: data.share.url ? data.share.url : location.href,
                                        imgUrl: data.share.image,
                                        success: function(){ 
                                            motify.log('分享成功');
                                        },
                                        cancel: function(){ 
                                            // motify.log('取消了分享');
                                        }
                                    });
                                });
                            }else{
                                wx.ready(function(){
                                    wx.hideOptionMenu();
                                });
                            }
                        } 
                    }
                }, 'json');
        }); 
        }
	},
}

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
var urlParam = GetRequest(location.search);

/*修正请求域名*/
var requestDomain = '';
if(motify.checkApp()){
	requestDomain = common.getAppDomain();
}else if(urlParam.requestDomain){
	requestDomain = urlParam.requestDomain;
}
if(!requestDomain || requestDomain == ''){
	requestDomain = document.domain;
}
requestUrl = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/appapi.php?c=';

/*微信注册JS*/
common.regWxJs();