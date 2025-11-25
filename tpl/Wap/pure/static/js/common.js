/* jQuery cookie 操作*/
jQuery.cookie = function (key, value, options) {
    if (arguments.length > 1 && (value === null || typeof value !== "object")){
        options = jQuery.extend({}, options);
        if (value === null) {
            options.expires = -1;
        }
        if (typeof options.expires === 'number'){
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }
        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? String(value) : encodeURIComponent(String(value)),
            options.expires ? '; expires=' + options.expires.toUTCString() : '',
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};
/** laytpl-v1.1 */

;!function(){"use strict";var f,b={open:"{{",close:"}}"},c={exp:function(a){return new RegExp(a,"g")},query:function(a,c,e){var f=["#([\\s\\S])+?","([^{#}])*?"][a||0];return d((c||"")+b.open+f+b.close+(e||""))},escape:function(a){return String(a||"").replace(/&(?!#?[a-zA-Z0-9]+;)/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&#39;").replace(/"/g,"&quot;")},error:function(a,b){var c="Laytpl Error：";return"object"==typeof console&&console.error(c+a+"\n"+(b||"")),c+a}},d=c.exp,e=function(a){this.tpl=a};e.pt=e.prototype,e.pt.parse=function(a,e){var f=this,g=a,h=d("^"+b.open+"#",""),i=d(b.close+"$","");a=a.replace(/[\r\t\n]/g," ").replace(d(b.open+"#"),b.open+"# ").replace(d(b.close+"}"),"} "+b.close).replace(/\\/g,"\\\\").replace(/(?="|')/g,"\\").replace(c.query(),function(a){return a=a.replace(h,"").replace(i,""),'";'+a.replace(/\\/g,"")+'; view+="'}).replace(c.query(1),function(a){var c='"+(';return a.replace(/\s/g,"")===b.open+b.close?"":(a=a.replace(d(b.open+"|"+b.close),""),/^=/.test(a)&&(a=a.replace(/^=/,""),c='"+_escape_('),c+a.replace(/\\/g,"")+')+"')}),a='"use strict";var view = "'+a+'";return view;';try{return f.cache=a=new Function("d, _escape_",a),a(e,c.escape)}catch(j){return delete f.cache,c.error(j,g)}},e.pt.render=function(a,b){var e,d=this;return a?(e=d.cache?d.cache(a,c.escape):d.parse(d.tpl,a),b?(b(e),void 0):e):c.error("no data")},f=function(a){return"string"!=typeof a?c.error("Template not found"):new e(a)},f.config=function(a){a=a||{};for(var c in a)b[c]=a[c]},f.v="1.1","function"==typeof define?define(function(){return f}):"undefined"!=typeof exports?module.exports=f:window.laytpl=f}();

/* 简单的消息弹出层 */
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
    checkAlipay:function(){
        if(/(alipayclient)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
    checkAlipayApp:function(){
        if(motify.checkAlipay() && (/(miniprogram)/.test(navigator.userAgent.toLowerCase()) || /(alipayide)/.test(navigator.userAgent.toLowerCase()))){
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
/*修复Iscroll高版本安卓无法点击*/
function iScrollClick(){
	/*if (/iPhone|iPad|iPod|Macintosh/i.test(navigator.userAgent)) return false;
	 if (/Chrome/i.test(navigator.userAgent)) return (/Android/i.test(navigator.userAgent));
	 if (/Silk/i.test(navigator.userAgent)) return false;
	 if (/Android/i.test(navigator.userAgent)) {
	 var s=navigator.userAgent.substr(navigator.userAgent.indexOf('Android')+8,3);
	 return parseFloat(s[0]+s[3]) < 44 ? false : true
	 }*/
	if(/webdebugger/i.test(navigator.userAgent)) return false;
	return true;
}

/*判断IOS方法调用*/
var iosCommon = {
	getVersion: function () {
		var reg = /versioncode=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '85';
		}else{
			return arr[1];
		}
	},
	iosFunction:function(paramStr, paramObj){
		var pack_version = iosCommon.getVersion();
		var str = parseFloat(pack_version) >= 1000
		if ( str == true) {
			window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
		}
		
	}
}

var voiceBtmTimer =null,voiceLocalId=0;
$(function(){
	if(typeof(FastClick) == 'function'){
		FastClick.attach(document.body);
	}
	/*如果页面没有底部导航，修改样式*/
	if($('.footerMenu').size() == 0){
		$('body').css({'padding-bottom':'0px'});
		$('#container').css({bottom:'0px'});
	}
	if(motify.getAndroidVersion() < 4 && $.cookie('lowPhoneVersion') == null){
		if(confirm('您使用的手机安卓版是 '+motify.getAndroidVersion()+'，低于安卓4.0版本，我们为您提供了更流畅的版本！请问是否需要切换？下次访问会重新询问。')){
			$.cookie('lowPhoneVersion','1');
			window.location.reload();
		}else{
			$.cookie('lowPhoneVersion','2');
		}
	}
	// alert(navigator.platform);
	// alert(navigator.userAgent);
	/*语音事件 start*/
	$('.voiceBtn,.smallVioce').click(function(e){
		$('body').append('<div class="voiceContainer"><div class="close"></div><div class="title">请大声说出您想要找什么</div><div class="content"><div class="tip">点击按钮开始录音</div><div class="btn"></div></div><div class="searchBtm1"></div><div class="searchBtm2"></div></div>');
		$('.searchBtm1').css({'background-size':$('.searchBtm1').width()+'px '+$('.searchBtm1').height()+'px'});
		$('.searchBtm2').css({'background-size':$('.searchBtm2').width()+'px '+$('.searchBtm2').height()+'px'});

		$('.voiceContainer .close').click(function(){
			if(voiceBtmTimer){
				clearInterval(voiceBtmTimer);
			}
			$('.voiceContainer').remove();
			if($('.voiceContainer .btn').hasClass('start')){
				endVoice();
			}
		});
		$('.voiceContainer .btn').click(function(){
			if($(this).hasClass('start')){
				$('.voiceContainer .tip').html('点击按钮开始录音');
				$(this).removeClass('start');
				clearInterval(voiceBtmTimer);
				endVoice();
			}else{
				beginVoice();
			}
		});
		return false;
	});
	$('.footerMenu li a').click(function(e){
		if($(this).hasClass('active')){
			return false;
			e.stopPropagation();
		}
	});
	/*语音事件 end*/
	
	//请同事别删除，有意义的。删除后微信7.0的 .link-url事件 点击无效
	$('body *').click(function(){
		
	});
	
	/*页面点击事件*/
	$(document).on('click','.link-url',function(e){
		if(!$(e.target).hasClass('phone')){
			if(typeof(noAnimate) == "undefined"){
				redirect($(this).data('url'),$(this).data('url-type'),($(this).attr('target') ? $(this).attr('target') : '_self'));
				return false;
			}else{
				window.location.href = $(this).data('url');
			}
		}
	});
	
	/*A标签*/
	$(document).on('click','a',function(){
		console.log($(this).data('nobtn'))
		if($(this).data('nobtn')){
			return false;
		}
		var href = $(this).attr('href');
		if(!href){
		    return ;
		}
		if(typeof(noAnimate) == "undefined"){
			if(href && href.substr(0,3) != 'tel' && href.substr(0,10) != 'javascript'){
				//app_target_blank 代表兼容过APP的打开新页面
				redirect(href,$(this).data('url-type'),($(this).attr('app_target_blank') ? 'app_blank' : ($(this).attr('target') ? $(this).attr('target') : '_self')));
				return false;
			}
		}else{
			window.location.href = href;
		}
	});
	
	/*电话按钮事件*/
	// if($('.phone').size() > 0){
		$(document).on('click','.phone',function(event){
			if($(this).attr('data-phone')){
				var tmpPhone = $(this).attr('data-phone').split(' ');
				var bg_height = $('body').height()>$(window).height() ? $('body').height() : $(window).height();
				var msg_dom = '<div class="msg-bg" style="height:'+bg_height+'px;"></div>';
				msg_dom+= '<div id="msg" class="msg-doc msg-option">';
				msg_dom+= '<div class="msg-bd">'+($(this).data('phonetitle') ? $(this).data('phonetitle') : '拨打电话')+'</div>';
				for(var i in tmpPhone){
					msg_dom+= '		<div class="msg-option-btns"><a class="btn msg-btn" href="tel:'+tmpPhone[i]+'">'+(tmpPhone.length == 1 && $(this).data('phonetip') ? $(this).data('phonetip') : tmpPhone[i])+'</a></div>';
				}
				msg_dom+= '		<button class="btn msg-btn-cancel" type="button">取消</button>';
				msg_dom+= '</div>';	
				$('body').append(msg_dom);
			}
			event.stopPropagation();
		});
		$(document).on('click','.msg-btn-cancel',function(){
			$('.msg-doc,.msg-bg').remove();
		});
	// }
	
	$('body').append('<div id="pageLoadTip" style="display:none;"><div></div></div>');
});

/*
 * 微信开始录音
 */
function beginVoice(){
	wx.startRecord({
		success: function (res){
			$('.voiceContainer .tip').html('点击按钮结束录音');
			$('.voiceContainer .btn').addClass('start');
			var voiceBtmNumber = 0;
			voiceBtmTimer = setInterval(function(){
				if(voiceBtmNumber%5 < 3){
					$('.searchBtm1').css({left:(parseInt($('.searchBtm1').css('left'))-1)+'px'});
				}else{
					$('.searchBtm2').css({left:(parseInt($('.searchBtm2').css('left'))-1)+'px'});
				}
				voiceBtmNumber += 1;
			},15);
			
		},
		fail: function (res){
			if(res.errMsg == 'startRecord:system permission denied'){
				motify.log('您拒绝了授权录音');
			}else{
				motify.log('未成功调用录音');
			}
		},
		cancel: function(){
			motify.log('您拒绝了授权录音');
		}
	});
}

/*
 * 微信结束录音
 */
function endVoice(){
	if(typeof(voice_type)=='undefined'){
		voice_type = 'group';
	}
	wx.stopRecord({
		success: function (res) {
			voiceLocalId = res.localId;
			// alert(voiceLocalId);
			/* wx.playVoice({
				localId: voiceLocalId // 需要播放的音频的本地ID，由stopRecord接口获得
			}); */
			wx.translateVoice({
				localId: voiceLocalId, // 需要识别的音频的本地Id，由录音相关接口获得
				isShowProgressTips: 1, // 默认为1，显示进度提示
				success: function (res) {
					if(res.translateResult){
						res.translateResult = res.translateResult.replace('。','');
						motify.log('<div style="color:#06c1ae;">'+res.translateResult+'</div>正在为您查询...',0); // 语音识别的结果
						window.location.href = window.location.pathname+'?c=Search&a='+voice_type+'&w='+encodeURIComponent(res.translateResult);
					}else{
						motify.log('没有听出您说了什么，请重试');
					}
				},fail: function (res) {
					motify.log('无法识别该段语音，请重试');
				}
			});
		},fail: function (res) {
			
		}
	});
}
 
//客户www.life.unee.com.cn页面跳转搭桥方法  
function getJsBridge(){
	window._dsf=window._dsf||{};return{call:function(method,args,cb){var ret="";if(typeof args=="function"){cb=args;args={}}if(typeof cb=="function"){window.dscb=window.dscb||0;var cbName="dscb"+window.dscb++;window[cbName]=cb;args["_dscbstub"]=cbName}args=JSON.stringify(args||{});if(window._dswk){ret=prompt(window._dswk+method,args)}else{if(typeof _dsbridge=="function"){ret=_dsbridge(method,args)}else{ret=_dsbridge.call(method,args)}}return ret},register:function(name,fun){if(typeof name=="object"){Object.assign(window._dsf,name)}else{window._dsf[name]=fun}}}
}window.dsBridge=getJsBridge(); 
/*
 * 跳转页面
 * （默认页面往左滑动，即 openRightWindow）
 * （页面往右滑动，即 openLeftWindow）
 *
 *  target _blank 小程序新窗口打开，优先于 type 方法，仅在小程序中生效
 *  target _self  当前页面打开新页面
 *  target self   h5当前页面直接重载至页面，不留浏览器历史记录
 */
function redirect(url,type,target){
	if(url == location.href){
		if(typeof myScroll != 'undefined'){
			myScroll.scrollTo(0,0);
		}else{
			$(window).scrollTop(0);
		}
		return false;
	}
	if(motify.checkLifeApp()){
		if(target == 'app_blank' && motify.getLifeAppVersion() >= 1400){
			if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
				url = 'https://' + document.domain + url;
			}
			if(url == location.href){
				return false;
			}
			if(motify.checkIos()){
				var iosUrl = window.btoa(url);
				iosUrl = iosUrl.replace('/','&');
				iosCommon.iosFunction('createwebview/'+iosUrl, {name:'createwebview', url:iosUrl, webUrl: url});
			}else{
				window.lifepasslogin.createwebview(url);
			}
			return false;
		} else if(target == 'self'){
            var stateObj = { foo: url };
            history.replaceState(stateObj, "", url);
            location.reload();
            return false;
        }
		window.location.href = url;
	}else{
		if(/(UniCustApp)/.test(navigator.userAgent) && location.href.indexOf('c=Mall&a=home') > 0 && (target == '_blank' || target == 'app_blank'|| target == '_self'|| target == 'self')){
			dsBridge.call("goSmallPigDetail", {"url":url});
			return false;
		}
		if((target == '_blank' || target == 'app_blank') && typeof wx !=='undefined' && url.indexOf('javascript')===-1 && window.__wxjs_environment === 'miniprogram'){
			if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
				url = 'https://' + document.domain + url;
			}
			if(url == location.href){
				return false;
			}
			if(url.indexOf('/packapp/') > 0 && url.indexOf('/pages/') > 0){
				var tmpUrl = url.split('/pages/');
				if(tmpUrl.length >= 2){
					wx.miniProgram.navigateTo({url:'/pages/' + tmpUrl[1]});
					return true;
				}
			}
			wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent('打开新页面')});
			return false;
		}
		if(target == 'self'){
			var stateObj = { foo: url };
			history.replaceState(stateObj, "", url);
			location.reload();
			return false;
		}

		window.location.href = url;
	}
}

/*页面加载提示*/
function pageLoadTip(msg){
	var defaultMsg='',top=0;	//'加载中...'
	//如果msg是数字，则是top的值！是字符串就是消息
	if(typeof(msg) == 'number'){
		top = msg;
		msg = defaultMsg;
	}else if(!msg){
		top = 0;
		msg = defaultMsg;
	}

	$('#pageLoadTip').css({top:top+'px','display':'block'}).find('div').css({'margin-top':(($(window).height()-100-top)/2)+'px'}).html(msg);
	// $('body').append('<div id="pageLoadTip" style="top:'+top+'px;"><div style="margin-top:'+(($(window).height()-100-top)/2)+'px;">'+msg+'</div></div>');
}
function pageLoadTipHide(){
	// $('#pageLoadTip').remove();
	$('#pageLoadTip').hide();
}


/*
 *
 *获得用户经纬度方法
 *
 * 无返回值！如果需要返回值，只能使用 okFunction 调用其他方法
 *
*/
var locationOptions = [];
function callbackLocation(lnglatname,lat){
	var arr = lnglatname.split(',');
	
	var userLat = arr[1];
	var userLong = arr[0];
	
	if(motify.checkIos()){
		userLat = lat;
    }
	
	locationOptions['userLocation'] = userLong+','+userLat;
	locationOptions['userLocationLong'] = userLong;
	locationOptions['userLocationLat'] = userLat;
	locationOptions['is_baidu'] = true;
	//通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
	var expire =  new Date();
	expire.setTime(expire.getTime() + locationOptions.historyTime*1000);
	locationOptions.historyTime = expire;
	$.cookie('userLocation',locationOptions['userLocation'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLong',locationOptions['userLocationLong'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLat',locationOptions['userLocationLat'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationHasRecord',0,{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLbsType','baidu',{expires:locationOptions.historyTime,path:'/'});
	if(locationOptions.reportLocation){
		$.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat,lbs_type:'baidu'},function(){
			if(locationOptions.okFunctionReport){
				locationOkFun(locationOptions);
			}
		});
		if(!locationOptions.okFunctionReport){
			locationOkFun(locationOptions);
		}
	}else{
		locationOkFun(locationOptions);
	}
}

//APP移动端定位回调
function callbackLocationGPS(lnglatname,lat){
	var arr = lnglatname.split(',');
	
	var userLat = arr[1];
	var userLong = arr[0];
	
	if(motify.checkIos()){
		userLat = lat;
    }
	
	locationOptions['userLocation'] = userLong+','+userLat;
	locationOptions['userLocationLong'] = userLong;
	locationOptions['userLocationLat'] = userLat;
	locationOptions['is_baidu'] = true;
	//通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
	var expire =  new Date();
	expire.setTime(expire.getTime() + locationOptions.historyTime*1000);
	locationOptions.historyTime = expire;
	$.cookie('userLocation',locationOptions['userLocation'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLong',locationOptions['userLocationLong'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLat',locationOptions['userLocationLat'],{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationHasRecord',0,{expires:locationOptions.historyTime,path:'/'});
	$.cookie('userLocationLbsType','baidu',{expires:locationOptions.historyTime,path:'/'});
	if(locationOptions.reportLocation){
		$.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat,lbs_type:'gps'},function(){
			if(locationOptions.okFunctionReport){
				locationOkFun(locationOptions);
			}
		});
		if(!locationOptions.okFunctionReport){
			locationOkFun(locationOptions);
		}
	}else{
		locationOkFun(locationOptions);
	}
}

function getUserLocation(options){
	this.options = {
		'useHistory':true,				//boolean 是否使用历史数据
		'historyTime':120,				//number  使用历史数据的时效，也是保存数据的有效期
		'okFunction':false,				//string 得到位置之后调用的方法， refresh 代表刷新页面
		'okFunctionParam':[],		//array 调用方法的自定义参数，以数组形式传参！系统参数：
		'errorFunction':false,			//string 没得到位置调用的方法， refresh 代表刷新页面
		'errorFunctionParam':[],		//array 调用方法的自定义参数，以数组形式传参！系统参数：
		'errorAction':1,				//number 1代表给指定“errorTipTime”参数时间内的提示，2代表弹层给出确认按钮的提示后跳转“errorUrl”（需要自行加载layer.js支持），3代表直接跳转到URL，4代表不提示
		'errorUrl':'',					//string errorAction 等于2或3时生效，值 history 代表上一页 值  href 代表刷新当前页面
		'errorTipTime':3,				//number  给有效时间提示的时间 	0代表长时间存在
		'errorTipTitle':'错误提示：',	//string errorAction 等于2生效
		'errorContentSuffix':'',	//string errorAction 等于2生效
		'errorShade':false,				//boolean errorAction 等于1时，是否开启遮罩层
		'reportLocation':true,			//boolean 是否上报用户地理位置，默认开启
		'okFunctionReport':true			//boolean 是否在上报用户地理位置成功后再执行方法，在 reportLocation为true时生效
	}
	for (var i in options){
		this.options[i] = options[i];
	}
	options = this.options;
	if(options.useHistory && $.cookie('userLocationLong') && $.cookie('userLocationLat') && !$.cookie('userLocationName')){
		options['userLocation'] = $.cookie('userLocation');
		options['userLocationLong'] = $.cookie('userLocationLong');
		options['userLocationLat'] = $.cookie('userLocationLat');
		locationOkFun(options);
		return false;
	}
	if(typeof(is_google_map) != "undefined" && is_google_map){
        if(motify.checkLifeApp() && self == top){
            locationOptions = options;
            if(motify.checkAndroid()){
				if(window.lifepasslogin && typeof(window.lifepasslogin.getLocationGPS) == 'function'){
					window.lifepasslogin.getLocationGPS();
				}else{
					window.lifepasslogin.getLocation(true);
				}
            }else{
            	iosCommon.iosFunction('getLocation/true', {name:'getLocation', timely:'1', callback:''});
                setTimeout(function(){
                    $('#iosIframe').remove();
                },50);
            }
        }else if('https:' == document.location.protocol  && navigator.geolocation){
            navigator.geolocation.getCurrentPosition(function(position){
                var userLong = position.coords.longitude.toFixed(6);
                var userLat  = position.coords.latitude.toFixed(6);
                options['userLocation'] = userLong+','+userLat;
                options['userLocationLong'] = userLong;
                options['userLocationLat'] = userLat;
                //通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
                var expire =  new Date();
                expire.setTime(expire.getTime() + options.historyTime*1000);
                options.historyTime = expire;
                console.log('444444444444445',options )
                $.cookie('userLocation',options['userLocation'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationLong',options['userLocationLong'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationLat',options['userLocationLat'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationHasRecord',0,{expires:options.historyTime,path:'/'});

                if(options.reportLocation){
                    $.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat},function(){
                        if(options.okFunctionReport){
                            locationOkFun(options);
                        }
                    });
                    if(!options.okFunctionReport){
                        locationOkFun(options);
                    }
                }else{
                    locationOkFun(options);
                }
            },function(error){
                switch (error.code){
                    case error.PERMISSION_DENIED:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,用户拒绝请求地理定位' : 'Failed to acquire your location data.  GPS access authorisation is declined.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,位置信息不可用' : 'Failed to acquire your location data. Location data is not available.';
                        break;
                    case error.TIMEOUT:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,请求获取用户位置超时' : 'Failed to acquire your location data. Request for location data timeout.';
                        break;
                    case error.UNKNOWN_ERROR:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,定位系统失效' : 'Failed to acquire your location data. GPS is invalid.';
                        break;
                }
                locationErorrTip(options);
            },{enableHighAccuracy:true,timeout:10000});
        }else{
            var loc = null;
            window.addEventListener('message', function(event) {
                // 接收位置信息
                if(event.data && loc == null){
                    loc = event.data;
                    console.log('loca====tion', loc);
                    $('#geoPage').remove();

                    var userLat = loc.lat;
                    var userLong = loc.lng;
                    options['userLocation'] = userLong+','+userLat;
                    options['userLocationLong'] = userLong;
                    options['userLocationLat'] = userLat;
                    //通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
                    var expire =  new Date();
                    expire.setTime(expire.getTime() + options.historyTime*1000);
                    options.historyTime = expire;
                    $.cookie('userLocation',options['userLocation'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationLong',options['userLocationLong'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationLat',options['userLocationLat'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationHasRecord',0,{expires:options.historyTime,path:'/'});

                    if(options.reportLocation){
                        $.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat},function(){
                            if(options.okFunctionReport){
                                locationOkFun(options);
                            }
                        });
                        if(!options.okFunctionReport){
                            locationOkFun(options);
                        }
                    }else{
                        locationOkFun(options);
                    }
                }
            }, false);
            $('body').append('<iframe id="geoPage" width=0 height=0 frameborder=0 style="display:none;" scrolling="no" src="https://apis.map.qq.com/tools/geolocation?key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77&referer=myapp"></iframe>');
        }
	}else{
        if(typeof(wxSdkLoad) != "undefined" && self == top){
			wx.error(function(res){
				if(typeof res == 'object' && res.errMsg){
					motify.log('微信接口报错<br/>' + res.errMsg);
				}
			});
            wx.ready(function () {
                wx.getLocation({
                    type: 'wgs84',
                    success: function (res) {
                        var userLat = res.latitude;
                        var userLong = res.longitude;
                        options['userLocation'] = userLong+','+userLat;
                        options['userLocationLong'] = userLong;
                        options['userLocationLat'] = userLat;
                        //通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
                        var expire =  new Date();
                        expire.setTime(expire.getTime() + options.historyTime*1000);
                        options.historyTime = expire;
                        $.cookie('userLocation',options['userLocation'],{expires:options.historyTime,path:'/'});
                        $.cookie('userLocationLong',options['userLocationLong'],{expires:options.historyTime,path:'/'});
                        $.cookie('userLocationLat',options['userLocationLat'],{expires:options.historyTime,path:'/'});
                        $.cookie('userLocationHasRecord',0,{expires:options.historyTime,path:'/'});

                        if(options.reportLocation){
                            $.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat},function(){
                                if(options.okFunctionReport){
                                    locationOkFun(options);
                                }
                            });
                            if(!options.okFunctionReport){
                                locationOkFun(options);
                            }
                        }else{
                            locationOkFun(options);
                        }
                    },
                    fail: function(res){
                        if(res.errMsg == 'getLocation:cancel' || res.errMsg == 'getLocation:fail'){
                            options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,用户拒绝请求地理定位' : 'Failed to acquire your location data.  GPS access authorisation is declined.';
                        }else{
							options['errorMsg'] = res.errMsg;
						}
                        locationErorrTip(options);
                    },
                    cancel: function(res){
						if(res.errMsg == 'getLocation:cancel' || res.errMsg == 'getLocation:fail'){
                            options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,用户拒绝请求地理定位' : 'Failed to acquire your location data.  GPS access authorisation is declined.';
                        }else{
							options['errorMsg'] = res.errMsg;
						}
                        locationErorrTip(options);
                    }
                });
            });
        }else if(motify.checkLifeApp() && self == top){
            locationOptions = options;
            if(motify.checkAndroid()){
                window.lifepasslogin.getLocation(true);
            }else{
            	iosCommon.iosFunction('getLocation/true', {name:'getLocation', timely:'1', callback:''});
                setTimeout(function(){
                    $('#iosIframe').remove();
                },50);
            }
        }else if('https:' == document.location.protocol  && navigator.geolocation){
            navigator.geolocation.getCurrentPosition(function(position){
                var userLong = position.coords.longitude.toFixed(6);
                var userLat  = position.coords.latitude.toFixed(6);
                options['userLocation'] = userLong+','+userLat;
                options['userLocationLong'] = userLong;
                options['userLocationLat'] = userLat;
                //通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
                var expire =  new Date();
                expire.setTime(expire.getTime() + options.historyTime*1000);
                options.historyTime = expire;
                $.cookie('userLocation',options['userLocation'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationLong',options['userLocationLong'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationLat',options['userLocationLat'],{expires:options.historyTime,path:'/'});
                $.cookie('userLocationHasRecord',0,{expires:options.historyTime,path:'/'});

                if(options.reportLocation){
                    $.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat},function(){
                        if(options.okFunctionReport){
                            locationOkFun(options);
                        }
                    });
                    if(!options.okFunctionReport){
                        locationOkFun(options);
                    }
                }else{
                    locationOkFun(options);
                }
            },function(error){
                switch (error.code){
                    case error.PERMISSION_DENIED:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,用户拒绝请求地理定位' : 'Failed to acquire your location data.  GPS access authorisation is declined.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,位置信息不可用' : 'Failed to acquire your location data. Location data is not available.';
                        break;
                    case error.TIMEOUT:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,请求获取用户位置超时' : 'Failed to acquire your location data. Request for location data timeout.';
                        break;
                    case error.UNKNOWN_ERROR:
                        options['errorMsg'] = (!$.cookie('userLocation') || $.cookie('userLocation') == 'chinese') ? '获取位置信息失败,定位系统失效' : 'Failed to acquire your location data. GPS is invalid.';
                        break;
                }
                locationErorrTip(options);
            },{enableHighAccuracy:true,timeout:2000});
        }else{
            var loc = null;
            window.addEventListener('message', function(event) {

                // 接收位置信息
                if(event.data && loc == null){
                    loc = event.data;
                    console.log('location', loc);
                    $('#geoPage').remove();

                    var userLat = loc.lat;
                    var userLong = loc.lng;
                    options['userLocation'] = userLong+','+userLat;
                    options['userLocationLong'] = userLong;
                    options['userLocationLat'] = userLat;
                    //通过 cookie 记录用户的经纬度，不通过H5的本地存储方便PHP调用
                    var expire =  new Date();
                    expire.setTime(expire.getTime() + options.historyTime*1000);
                    options.historyTime = expire;
                    $.cookie('userLocation',options['userLocation'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationLong',options['userLocationLong'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationLat',options['userLocationLat'],{expires:options.historyTime,path:'/'});
                    $.cookie('userLocationHasRecord',0,{expires:options.historyTime,path:'/'});

                    if(options.reportLocation){
                        $.post(window.location.pathname+'?c=Userlonglat&a=report',{userLong:userLong,userLat:userLat},function(){
                            if(options.okFunctionReport){
                                locationOkFun(options);
                            }
                        });
                        if(!options.okFunctionReport){
                            locationOkFun(options);
                        }
                    }else{
                        locationOkFun(options);
                    }
                }
            }, false);
            $('body').append('<iframe id="geoPage" width=0 height=0 frameborder=0 style="display:none;" scrolling="no" src="https://apis.map.qq.com/tools/geolocation?key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77&referer=myapp"></iframe>');
        }
	}

}

function locationOkFun(options){
	if(options.okFunction){
		if(options.okFunction == 'refresh'){
			window.location.reload();
		}else{
			options.okFunctionParam.push(options.userLocation);
			options.okFunctionParam.push(options.userLocationLong);
			options.okFunctionParam.push(options.userLocationLat);
			if(options.is_baidu){
				options.okFunctionParam.push(options.is_baidu);
			}
			call_user_func(options.okFunction,options.okFunctionParam);
		}
	}
}
function locationErorrTip(options){
	if(options.errorMsg && options.errorContentSuffix){
		options.errorMsg = options.errorMsg + '<br/>' + options.errorContentSuffix;
	}
	if(options.errorFunction){
		if(options.errorFunction == 'refresh'){
			window.location.reload();
		}else{
			options.errorFunctionParam.push(options.errorMsg);
			call_user_func(options.errorFunction,options.errorFunctionParam);
		}
	}else{
		if(options.errorMsg){
			switch(options.errorAction){
				case 1:
					motify.log(options.errorMsg,options.errorTipTime*1000,(options.errorShade ? {show:true} : null));
					break;
				case 2:
					layer.open({
						title:[options.errorTipTitle,'background-color:#FF658E;color:#fff;'],
						content:options.errorMsg,
						btn: ['确定'],
						end:function(){
							if(options.errorUrl != ''){
								if(options.errorUrl == 'history'){
									window.history.go(-1);
								}else if(options.errorUrl == 'href'){
									window.location.reload();
								}else{
									window.location.href=options.errorUrl;
								}
							}
						}
					});
					break;
				case 3:
					if(options.errorUrl != ''){
						if(options.errorUrl == 'history'){
							window.history.go(-1);
						}else if(options.errorUrl == 'href'){
							window.location.reload();
						}else{
							window.location.href=options.errorUrl;
						}
					}else{
						motify.log('调用了“直接跳转URL”，却没有传URL地址',0);
					}
					break;
			}
		}
	}	
}
function geoconv(funName,lng,lat,mapType=''){
	if(mapType == 'tianditu'){
		// 1. WGS-84(天地图) 转 BD-09(百度)
		// const [lng1,lat1] = Coordinate.wgs84ToBd09(lng,lat);
		var result;
        result = {y:parseFloat(lat),x:parseFloat(lng),coordinate_type:'wgs84'};
        funName = eval(funName);
        funName.call(this,result);
	}else if(typeof(is_google_map) != "undefined" && is_google_map){
        var result;
        result = {y:parseFloat(lat),x:parseFloat(lng)};
        funName = eval(funName);
        funName.call(this,result);
    }else{
        baidu_map_ak = baidu_map_ak ? baidu_map_ak : '4c1bb2055e24296bbaef36574877b4e2';
		url = 'https://api.map.baidu.com/geoconv/v1/?coords='+lng+','+lat+'&ak='+baidu_map_ak+'&from=1&to=5&callback='+funName+'&jsoncallback=?';
		$.get('/index.php?c=Map&a=invokeBaiduLocationForJs&url=' + encodeURIComponent(url), function (data) {
			//$.getJSON(eval(data));
		})
    }
}
function geocoder(funName,lng,lat,callback,mapType=''){
	if(mapType == 'tianditu'){
		$.post(`/v20/public/index.php/common/api.BaiduMap/reverseGeocoding`,{lng,lat,coordinate_type:'wgs84'},function(res){
			if(res.status == 1000){
				callback(res.data)
			}
		})
	}else if(funName){
		baidu_map_ak = baidu_map_ak ? baidu_map_ak : '4c1bb2055e24296bbaef36574877b4e2';
		url = 'https://api.map.baidu.com/reverse_geocoding/v3/?location='+lat+','+lng+'&ak='+baidu_map_ak+'&output=json&pois=1&callback='+funName+'&jsoncallback=?';
		$.get('/index.php?c=Map&a=invokeBaiduLocationForJs&url=' + encodeURIComponent(url), function (data) {
			//$.getJSON(eval(data));
		})
	}else if(callback){
		$.post(`/v20/public/index.php/common/api.BaiduMap/reverseGeocoding`,{lng,lat},function(res){
			if(res.status == 1000){
				callback(res.data)
			}
		})
	}
}
/* 得到经纬度之间的距离 */
function Rad(d){
   return d * Math.PI / 180.0;//经纬度转换成三角函数中度分表形式。
}
//输出为米
function GetDistance(lng1,lat1,lng2,lat2){
	var radLat1 = Rad(lat1);
	var radLat2 = Rad(lat2);
	var a = radLat1 - radLat2;
	var  b = Rad(lng1) - Rad(lng2);
	var s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a/2),2) +
	Math.cos(radLat1)*Math.cos(radLat2)*Math.pow(Math.sin(b/2),2)));
	s = s *6378.137 ;// EARTH_RADIUS;
	s = Math.round(s * 10000) / 10; //输出为公里
	return s;
}
	
/*调用JS 自定义方法并带参数*/
function call_user_func(cb, options){
	func = window[cb];
	func.apply(cb,options);
}
/* function alerts(params,params2){
	console.log(arguments);
}
call_user_func('alerts',['1111','22222']); */

function parseCoupon(obj,type){
	
	var returnObj = {};
	for(var i in obj){
		if(typeof(obj[i]) == 'object'){
			returnObj[i] = [];
			for(var j in obj[i]){
				if(obj[i][j].money > 0){
					returnObj[i].push('满'+currency_symbol+obj[i][j].money+'减'+currency_symbol+obj[i][j].minus);
				}else{
					returnObj[i].push('减'+currency_symbol+obj[i][j].minus);
				}
			}
		}else if(i=='invoice'){
			returnObj[i] = '满'+currency_symbol+obj[i]+'支持开发票，请在下单时填写发票抬头';
		}else if(i=='discount'){
			returnObj[i] = '店内全场'+obj[i]+'折';
		}else if(i=='isDiscountGoods'){
            returnObj[i] = '店内有部分商品限时优惠';
        }else if(i=='isdiscountsort'){
            returnObj[i] = '部分商品分类参与折扣优惠';
        }
	}
	
	var textObj = [];
	for(var i in returnObj){
		if(typeof(returnObj[i]) == 'object'){
			switch(i){
				case 'system_newuser':
					textObj[i] = '平台首单'+returnObj[i].join(',');
					break;
				case 'system_minus':
					textObj[i] = '平台优惠'+returnObj[i].join(',');
					break;
				case 'newuser':
					textObj[i] = '店铺首单'+returnObj[i].join(',');
					break;
				case 'minus':
					textObj[i] = '店铺优惠'+returnObj[i].join(',');
					break;
				case 'system_minus':
					textObj[i] = '平台优惠'+returnObj[i].join(',');
					break;
				case 'delivery':
					textObj[i] = '配送费'+returnObj[i].join(',');
					break;
			}
		}else if(i=='invoice' || i=='discount' || i=='isDiscountGoods' || i=='isdiscountsort'){
			textObj[i] = returnObj[i];
		}
	}
	if(type == 'text'){
		var tmpObj = [];
		for(var i in textObj){
			tmpObj.push(textObj[i]);
		}
		return tmpObj.join(';');
	}else{
		return textObj;
	}
}

// 处理弹出显示问题
$(function(){
	if (motify.checkWeixin() &&  !motify.checkWeixinApp()) {
		$.post(window.location.pathname+'?c=Login&a=common_recommend_tip',{},function(data){
			console.log('data',data);
			if (data && data.pay_wxapp_important == '1') {
				if (!data.share_txt) {
					data.share_txt = '进入平台小程序享受更多优惠';
				}
				console.log('share_txt------------------------',data.share_txt);
				// wxapp_recommend_tip(data.share_txt)
				wxapp_recommend_tip_new(data)
			}
		}, 'json');
	}
});

// 处理弹出显示
function wxapp_recommend_tip_new(data) {
	var wxapp_page = 'pages/plat_menu/index.html?redirect=webview&webview_url=' + encodeURIComponent(location.href) + '&webview_title=' + encodeURIComponent('');
	var tip_html = '<div style="position:fixed;bottom:140px;left:40px;z-index:998;"><wx-open-launch-weapp id="launch-btn" username="' + data.pay_wxapp_username + '" path="' + wxapp_page + '" >';
	tip_html+= '<template>';
	tip_html+= '<style>.wxapp_tip_new{background:' + data.mobile_head_color + ';color:white;border-radius:20px;height:40px;line-height:40px;padding:0px 20px;width:' + ($(window).width() - 120) + 'px;}</style>';
	tip_html+= '<div class="wxapp_tip_new"><span class="close"></span>' + data.share_txt + '</div>';
	tip_html+= '</template>';
	tip_html+= '</wx-open-launch-weapp></div>';
	$('body').append(tip_html);
	
	var btn = document.getElementById('launch-btn');
	btn.addEventListener('ready', function (e) {
		console.log('ready success');
	});
	btn.addEventListener('launch', function (e) {
		console.log('success');
	});
	btn.addEventListener('error', function (e) {
		console.log('fail', e.detail);
	});
}

// 处理弹出显示
function wxapp_recommend_tip(share_txt) {
    console.log('wxapp__recommend_tip',1);

    console.log('location',window.location);
    var url ='./index.php?c=Recognition_wxapp&a=create_url_qrcode';
    console.log('url',url);

	var location_href = location.href.replace(window.location.protocol + "//" + window.location.host, '');
    var img = url + '&webview_url='+encodeURIComponent(location_href);

	if(typeof layer != 'object'){
		var script = document.createElement("script");
		script.type="text/javascript";
		script.src="./static/layer/mobile/layer.js?t=00000000001";
		document.getElementsByTagName('head')[0].appendChild(script);
		script.onload=function(){
			var styleLink = document.createElement("link");
			styleLink.type="text/css";
			styleLink.rel="styleSheet";
			styleLink.id="layermcss";
			styleLink.href="./static/layer/mobile/need/layer.css?2.0";
			document.getElementsByTagName('head')[0].appendChild(styleLink);
			
			wxappLayerLoaded(url,share_txt,img);
		}
	}else{
		wxappLayerLoaded(url,share_txt,img);
	}
}

function wxappLayerLoaded(url,share_txt,img){
	setTimeout(function(){
		console.log('url1',url);
		layer.open({
			content:'<div style="text-align: center;width: 100%;"><div style="font-size: 16px; margin-bottom: 20px;">'+share_txt+'</div><img src="'+img+'" style="width:104px;height:104px;margin-bottom:5px;background:url(/static/images/loading.gif) center center no-repeat;"/><br/><div style="color: #B7B7B7;display: inline;text-align: center;background-color: #F8F8F8;padding: 8px 8px;border-radius: 5px;font-size: 12px;">长按图片识别小程序码</div></div>',
			btn: ['关闭'],
			success:function(){
				console.log('确定end');
				layer.close();
			},
			shadeClose:false
		});
		$('.layermbtn').css('color', '#DC3B3C');
		$('.layermbox0 .layermchild').css('width', '260px');
	},300);
}
 
function onPageFinished(){

}

window.onerror = function(errorMessage, scriptURI, lineNumber,columnNumber,errorObj) {
	$.post('/appapi.php?c=Config&a=log_report',{file:'h5_js_error',errorMessage:errorMessage,scriptURI:scriptURI,lineNumber:lineNumber,columnNumber:columnNumber,errorObj:errorObj,href:location.href});
}


var requestDomain = location.protocol + '//' + location.host + '/';
// v20请求地址
const requestUrlV20 = requestDomain + 'v20/public/index.php';

function v20Axios(url, params, sucFun, errFun, type) {
	if (typeof (url) == 'undefined') {
		return false;
	}
	url = requestUrlV20 + url;
	if (!params) {
		params = {};
	}
	// 用户v20_ticket
	params.v20_ticket = getTicket().v20_ticket;
	params.ticket = getTicket().ticket;
	params['Device-Id'] = getTicket().device;
	params.app_version = getVersion();
	if (typeof (type) == 'undefined') {
		type = 'post';
	}
	if (!params.noTip) {
		var noTip = false;
		// 在文档内需先引入vant js
		vant.Toast.loading({ duration: 0 });
	} else {
		var noTip = true;
		delete params.noTip;
	}
	if (type == 'post') {
		$.post(url, params, function (res) {
			if (res.status == '1000') {
				sucFun(res.data);
			} else if (res.status == '1002') {
				//需要去登录
				var url = requestDomain + 'packapp/my/login.html?back=' + encodeURIComponent(location.href) + '&loginFail=' + encodeURIComponent(location.href)
				location.replace(url)
			}else if (errFun) {
				errFun(res);
			} else {
				// vant.Toast(res.msg)
				vant.Dialog.alert({
					title: '提示', 
					message: res.msg,
					confirmButtonColor: '#27A3F7',
					confirmButtonText: '确定',
				}).then(function () {
					errFun && errFun(res);
				}).catch(function () {
					errFun && errFun(res);
				});
			}
			if (!noTip) {
				vant.Toast.clear();
			}
		}, 'json').error(function() { 
			if (!noTip) {
				vant.Toast.clear();
			}
		 });
	}
}
function getVersion(){
    var reg = /versioncode=(.*?),/;
    var arr = reg.exec(navigator.userAgent.toLowerCase());
    if(arr == null){
      return '85';
    }else{
      return arr[1];
    }
}
function getTicket(){
	let login_status = $.cookie('login_status')
	var ticket = ''
	var device = ''
	let v20_ticket = ''
	// cookie中缓存的，一般用于 PHP页面跳转
	if (login_status && login_status == 'login') {
		ticket = $.cookie('user_ticket')
		device = $.cookie('user_device_id')
		v20_ticket = decodeURIComponent($.cookie('v20_ticket')) 
	}

	// userAgent中的，一般用于APP的页面
	// 如果 v20_ticket 有值，则不管APP逻辑，避免APP ticket过期导致无限循环
	if(motify.checkLifeApp() && !v20_ticket){
		var tmp_app_device = '';
		var reg = /device-id=(.*?),/;
		var arr = reg.exec(navigator.userAgent);
		if(arr !== null){
			tmp_app_device = arr[1];
		}
		console.log('tmp_app_device', tmp_app_device);
		
		var tmp_app_ticket = '';
		var reg = /ticket=(.*?),/;
		var arr = reg.exec(navigator.userAgent);
		if(arr !== null){
			tmp_app_ticket = arr[1];
		}
		console.log('tmp_app_ticket', tmp_app_ticket);
		
		// v20_ticket获取
		var tmp_app_v20_ticket = '';
		var v20_Reg = /v20_ticket=(.*?),/;
		var v20_Arr = v20_Reg.exec(navigator.userAgent);
		if(v20_Arr !== null){
			tmp_app_v20_ticket = v20_Arr[1] || '';
		}
		
		var tmp_app_now_lang = '';
		var reg = /now_lang=(.*?),/;
		var arr = reg.exec(navigator.userAgent);
		if(arr !== null){
			tmp_app_now_lang = arr[1];
		}
		
		if(tmp_app_device && tmp_app_ticket && tmp_app_now_lang){
			device = tmp_app_device;
			ticket = tmp_app_ticket;
			now_lang = tmp_app_now_lang;
		}
		if(tmp_app_v20_ticket){
			v20_ticket = tmp_app_v20_ticket;
		}
	}

	// 获取浏览器v20_ticket
	let v20_ticket_storage = localStorage.getItem('v20_ticket');
	if(v20_ticket_storage && !v20_ticket){
		v20_ticket = v20_ticket_storage;
	}

	return {ticket,device,v20_ticket}
}