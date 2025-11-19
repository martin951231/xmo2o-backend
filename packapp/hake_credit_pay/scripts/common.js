/** laytpl-v1.1 */
;!function(){"use strict";var f,b={open:"{{",close:"}}"},c={exp:function(a){return new RegExp(a,"g")},query:function(a,c,e){var f=["#([\\s\\S])+?","([^{#}])*?"][a||0];return d((c||"")+b.open+f+b.close+(e||""))},escape:function(a){return String(a||"").replace(/&(?!#?[a-zA-Z0-9]+;)/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&#39;").replace(/"/g,"&quot;")},error:function(a,b){var c="Laytpl Error：";return"object"==typeof console&&console.error(c+a+"\n"+(b||"")),c+a}},d=c.exp,e=function(a){this.tpl=a};e.pt=e.prototype,e.pt.parse=function(a,e){var f=this,g=a,h=d("^"+b.open+"#",""),i=d(b.close+"$","");a=a.replace(/[\r\t\n]/g," ").replace(d(b.open+"#"),b.open+"# ").replace(d(b.close+"}"),"} "+b.close).replace(/\\/g,"\\\\").replace(/(?="|')/g,"\\").replace(c.query(),function(a){return a=a.replace(h,"").replace(i,""),'";'+a.replace(/\\/g,"")+'; view+="'}).replace(c.query(1),function(a){var c='"+(';return a.replace(/\s/g,"")===b.open+b.close?"":(a=a.replace(d(b.open+"|"+b.close),""),/^=/.test(a)&&(a=a.replace(/^=/,""),c='"+_escape_('),c+a.replace(/\\/g,"")+')+"')}),a='"use strict";var view = "'+a+'";return view;';try{return f.cache=a=new Function("d, _escape_",a),a(e,c.escape)}catch(j){return delete f.cache,c.error(j,g)}},e.pt.render=function(a,b){var e,d=this;return a?(e=d.cache?d.cache(a,c.escape):d.parse(d.tpl,a),b?(b(e),void 0):e):c.error("no data")},f=function(a){return"string"!=typeof a?c.error("Template not found"):new e(a)},f.config=function(a){a=a||{};for(var c in a)b[c]=a[c]},f.v="1.1","function"==typeof define?define(function(){return f}):"undefined"!=typeof exports?module.exports=f:window.laytpl=f}();



//当前业务名称
var visitWork = 'hake_credit_pay';

var workColor = '#2ecc71';

//当前访问页面名称
var visitPage = location.pathname.split('/').pop().split('.')[0];
if(visitPage == ''){
	visitPage = 'index';
}
console.log(visitPage);
/*
 * 获取参数
 *
 */
function GetRequest(){
	var url = location.search;
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
var urlParam = GetRequest();

var requestUrl = '';

var common = {
	'http':function(url, params, sucFun, errFun, type){
		if (typeof(url) == 'undefined') {
			return false;
		}
		url = requestUrl + url;
		
		if(!params){
			params = {};
		}
		params.ticket = common.getCache('ticket',true);
		params.now_city = common.getCache('now_city');
		params.now_lang = now_lang;
		params['Device-Id'] = common.getDeviceId();
		params['app_type'] = common.checkIos() ? 'ios' : 'android';
		if(urlParam.ios_device){
			params['autologin_ios_device'] = urlParam.ios_device;
		}
		params.app_version = common.getVersion();
		
		if (typeof(type) == 'undefined') {
			type = 'post';
		}
		if(!params.noTip){
			var noTip = false;
			var loadingTip = weui.loading();
		}else{
			var noTip = true;
			var loadingTip = 0;
			delete params.noTip;
		}
		if (type == 'post') {
			$.post(url, params, function(res){
				if(!noTip){
					console.log('关闭请求弹出层');
					loadingTip.hide();
				}
				if(res){
					if (res.errorCode == '0') {
						sucFun(res.result);
					} else if (errFun) {
						errFun(res);
					} else {
						weui.alert(res.errorMsg);
					}
				}
			}, 'json');
		} else if (type == 'get') {
			$.get(url, params, function(res){
				if(!noTip){
					console.log('关闭请求弹出层');
					loadingTip.hide();
				}
				if(res){
					if (res.errorCode == '0') {
						sucFun(res.result);
					} else if (errFun) {
						errFun(res);
					} else {
						weui.alert(res.errorMsg);
					}
				}
			}, 'json');
		}
	},
	checkLogin:function(isReutn){
		if(common.getCache('ticket',true)){
			return true;
		}else{
			//永久登录
			var ticket = common.getCache('ticket');
			if(!ticket){
				if(isReutn){
					return false;
				}else{
					redirect_self('login.html?back='+visitPage);
				}
			}else{
				common.setCache('ticket',ticket,true);
				return true;
			}
			return false;
		}
	},
	getAppDomain:function(){
		var reg = /app_domain=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '';
		}else{
			return arr[1];
		}
	},
	getVersion:function(){
		var reg = /pack_version=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '85';
		}else{
			return arr[1];
		}
	},
	getDeviceId:function(){ 
		var device_id = common.getCache('device_id',true);
		if(device_id){
			console.log('device_id from cache',device_id);
			return device_id;
		}
		if(common.checkApp()){
			var reg = /device_id=(.*?),/;
			var arr = reg.exec(navigator.userAgent.toLowerCase());
			if(arr == null){
				return 'packapp';
			}else{
				return arr[1];
			}
		}else{
			return 'packapp';
		}
	},
	checkApp:function(){
	    if(/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
	        return true;
	    }else{
	        return false;
	    }
	},
	checkIos:function(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase()) && /(iphone)/.test(navigator.platform.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
	checkIosApp:function(){
		if(common.checkApp() && common.checkIos()){
			return true;
		}else{
			return false;
		}
	},
	checkIphoneXApp:function(){
		if(common.checkIosApp() && $(window).height() == '812' && $(window).width() == '375'){
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
	checkAndroidApp:function(){
		if(common.checkApp() && common.checkAndroid()){
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
		if(window.__wxjs_environment === 'miniprogram' || (common.checkWeixin() && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
			return true;
		}else{
			return false;
		}
	},
	getCache:function(key,is_session){
		key = (is_session ? 'session_' : 'local_') + key;
		// key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;


		if(is_session && !common.checkApp()){
			var string = sessionStorage.getItem(key);
		}else{
			var string = localStorage.getItem(key);
		}
		
		if(string){
			if(string.substring(0,5) == 'str||'){
				return string.substring(5);
			}else{
				if(string.substring(5) != 'undefined'){
					return JSON.parse(string.substring(5));
				}
			}
		}
	},
	setCache:function(key,obj,is_session){
		key = (is_session ? 'session_' : 'local_') + key;
		
		var string = '';
		if(typeof(obj) == 'string'){
			string = 'str||'+obj;
		}else{
			string = 'obj||'+JSON.stringify(obj);
		}
		if(is_session && !common.checkApp()){
			return sessionStorage.setItem(key,string);
		}else{
			return localStorage.setItem(key,string);
		}
	}, 
	removeCache:function(key,is_session){
		key = (is_session ? 'session_' : 'local_')  + key;
		
		if(is_session && !common.checkApp()){
			return sessionStorage.removeItem(key);
		}else{
			return localStorage.removeItem(key);
		}
	},
	removeAllCache:function(is_session){
		if(is_session){
			var storage = sessionStorage;
		}else{
			var storage = localStorage;
		}
		storage.clear();
	},
	regWxJs:function(){
		if(common.checkWeixin()){
			$.getScript('https://res.wx.qq.com/open/js/jweixin-1.4.0.js',function(){
				common.http('Config&a=wx_config',{noTip:true,work:visitWork,page:visitPage,location_url:location.href.split('#')[0]}, function(data){
                    common.setCache('shareData',data,true)
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
							if($('#newOrderMp3').size() > 0){
								var myVideo=document.getElementById("newOrderMp3");
								myVideo.load();
							}
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
							if($('#newOrderMp3').size() > 0){
								var myVideo=document.getElementById("newOrderMp3");
								myVideo.load();
							}
						});
					}
				});
			});
		}
	},
	scan:function(okFuncName){
		if(common.checkWeixin()){
			wx.scanQRCode({
				needResult:1,
				scanType:["qrCode","barCode"],
				success:function(res){
					window[okFuncName](res.resultStr);
				}
			});
		}else if(common.checkApp()){
			var index = layer.open({type: 2,shadeClose:false,time:1});
			if(common.checkIos()){
				common.iosFunction('scan////'+okFuncName, { name:'scan', scanType: '', callback: okFuncName});
			}else{
				window.pigcmspackapp.scan('','','',okFuncName);
			}
		}else{
			motify.log(L_('您使用的设备不支持扫码'));
		}
	},
	setData:function(data,pData){
		var tmpVar = '';
		var tmpId = '';
		var exec = '';
		var bindData = '';
		var defaultData = '';
		pData = !pData ? '' : pData + '-';
		for(var i in data){
			tmpVar = typeof(data[i]);
			if(tmpVar == 'array' || tmpVar == 'object'){
				common.setData(data[i],i);
			}else{
				tmpId = $('#'+(pData+i));
				if(tmpId.size() > 0){
					tmpVar = data[i];
					exec = tmpId.data('exec');
					if(exec){
						tmpVar = window[exec](tmpVar);
					}
					bindData = tmpId.data('data');
					if(bindData){
						tmpId.data(bindData,tmpVar);
					}
					bindEmpty = tmpId.data('empty');
					if(tmpVar == '' && tmpVar !== 0){
						defaultData = tmpId.data('default');
						tmpId.html(defaultData ? defaultData : '&nbsp;');
					}else{
						tmpId.html(tmpVar);
					}
				}
			}
		}
	},
	onlyScroll:function(that){
		that.css({'overflow-y':'auto','-webkit-overflow-scrolling':'touch'});
	},
	onScrollArr:{},
	scroll:function(that,callFunc){
		that.css({'overflow-y':'auto','-webkit-overflow-scrolling':'touch'});
		if(common.checkIosApp()){
			that.height(that.height()-20);
		}
		var rndNum = RndNum(8);
		if(!common.onScrollArr[rndNum]){
			common.onScrollArr[rndNum] = {
				onScroll:false,
				dom:that,
				bottom:that.data('bottom') ? parseInt(that.data('bottom')) : 50,
				domHeight:that.height(),
				scrollHeight:that[0].scrollHeight
			}
		}
		that.scroll(function(){
			if(common.onScrollArr[rndNum].onScroll == false){
				if(common.onScrollArr[rndNum].domHeight + that.scrollTop() >= common.onScrollArr[rndNum].scrollHeight - common.onScrollArr[rndNum].bottom){
					console.log('scroll-bottom-callFunc');
					common.onScrollArr[rndNum].onScroll = true;
					callFunc(rndNum);
				}
			}
		});
	},
	scrollEnd:function(index){
		if(!index){
			for(var i in common.onScrollArr){
				common.onScrollArr[i].scrollHeight = common.onScrollArr[i].dom[0].scrollHeight;
				console.log(common.onScrollArr[i].domHeight);
				console.log(common.onScrollArr[i].dom.height());
				if(common.onScrollArr[i].domHeight <= 0){
					common.onScrollArr[i].domHeight = common.onScrollArr[i].dom.height();
				}
			}
			return false;
		}
		
		common.onScrollArr[index].onScroll = false;
		common.onScrollArr[index].scrollHeight = common.onScrollArr[index].dom[0].scrollHeight;
		if(common.onScrollArr[index].domHeight <= 0){
			common.onScrollArr[index].domHeight = common.onScrollArr[index].dom.height();
		}
	},
	floatVal:function(number){	//如果是整数，去除小数点后边没用的0，目前仅提供去除2位
		if(typeof(number) == 'string'){
			number = Number(number);
		}
		var str = number.toFixed(2);
		if(str.indexOf('.') >= 0){
			if(str.substr(-3) == '.00'){
				return number.toFixed(0);
			}else if(str.substr(-1) == '0'){
				return number.toFixed(1);
			}
		}
		return str;
	},
	iosFunction:function(paramStr, paramObj){
		var pack_version = common.getVersion();
		var str = parseFloat(pack_version) >= 1000;
		if ( str == true) {
			window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmspackapp://'+paramStr+'"></iframe>');
			setTimeout(function(){
				$('#iosIframe').remove();
			},50);
		}
		
	},
	formartdate:function(format){	//yyyy-mm-dd hh:mm:ss
		var date = new Date();
		var year = date.getFullYear();
		var month = zeromonth = date.getMonth() + 1;
		var zeromonth = month;
		var strDate = date.getDate();
		var zerostrDate = strDate;
		if (month >= 1 && month <= 9){
			zeromonth = "0" + month;
		}
		if (strDate >= 0 && strDate <= 9) {
			zerostrDate = "0" + strDate;
		}
		var hour = date.getHours();
		var minutes = date.getMinutes();
		var seconds = date.getSeconds();
		format = format.replace('yyyy',year);
		format = format.replace('mm',zeromonth);
		format = format.replace('m',month);
		format = format.replace('dd',zerostrDate);
		format = format.replace('d',strDate);
		format = format.replace('hh',hour);
		format = format.replace('mm',minutes);
		format = format.replace('ss',seconds);
		return format;
	},
	getConfig(param,callBack){
		if(config){
			if(config.config.open_multilingual && config.config.lang_config && config.config.lang_config.now_lang){
				all_lang = config.config.special_lang_list;
				now_lang = config.config.lang_config.now_lang;
				common.setCache('all_lang',all_lang,true);
				common.setCache('now_lang',now_lang,true);
				if(all_lang){
					replace_html_lang();
				}
			}
			callBack();
		}else{
			common.http('Config&a=index',param,function(data){ 
				if(data.config.open_multilingual && data.config.lang_config && data.config.lang_config.now_lang){
					all_lang = data.config.special_lang_list;
					now_lang = data.config.lang_config.now_lang;
					common.setCache('all_lang',all_lang,true);
					common.setCache('now_lang',now_lang,true);
					if(all_lang){
						replace_html_lang();
					}
				}

				config = data;
				common.setCache('config',config,true);
				callBack();
			});
		}
	}, 
	iosKeybordTop(cssName){   
		var height = $(window).height();
		$('body').height(height);
		var heightWithoutKeybord = 0;
		document.body.addEventListener('focusin', () => {
			//软键盘弹出的事件处理
			if(common.checkIos()){
				var active = document.activeElement;
				
				if(heightWithoutKeybord == 0){
					window.scrollTo(0,height);
					
					var cnt = 0;
				  	setInterval( function(){
					    if (cnt < 3) {
					      cnt++;
					    } else {
					      clearInterval(timerId);
					      timerId = null;
					      return;
					    }
						$('body').height(height - $(window).scrollTop());
						window.scrollTo(0, 0);
						heightWithoutKeybord = $('body').height();
					}, 300);
				}else{
					$('body').height(heightWithoutKeybord);
				}
			}
		})
		document.body.addEventListener('focusout', () => {
			//软键盘收起的事件处理
			if(common.checkIos()){
				setTimeout(function (){
					var active = document.activeElement;
					if(!$(active).hasClass(cssName)){
						$('body').height(height);
					}
				}, 300)
			}
		});
	},
	androidKeybordTop(){
		var winHeight = $(window).height();   //获取当前页面高度
		$(window).resize(function(){
		var thisHeight=$(this).height();
			if(winHeight - thisHeight >50){
				//当软键盘弹出，在这里面操作
				$('body').css('height', winHeight + 'px'); 
				$('.bottmButton').css('position','fixed');
			}else{
				//当软键盘收起，在此处操作 
				var wh=window.innerHeight+"px";
				$('body').css('height', wh);   
			}
		});
	}
	 
	
};

/*微信注册JS*/
common.regWxJs();


/*IOS APP顶部增加内容*/
iosFixedTop();
function iosFixedTop(){
	if(common.checkIosApp()){
		if(visitPage == 'login'){
			return false;
		}
		var editClass = [];
		$('body').prepend('<div class="ios_fixed_top"></div>');
		
		if(common.checkIphoneXApp()){
			$('.ios_fixed_top').css('height','44px');
		}
		
		editClass.push({class:'.public',css:'top'});
		if(visitPage == 'index'){
			editClass.push({class:'.group .tit',css:'top'});
			editClass.push({class:'.group .group_list',css:'margin-top'});
		}else if(visitPage == 'group_list'){
			editClass.push({class:'.cable',css:'top'});
			editClass.push({class:'.order .order_list',css:'margin-top'});
		}else if(visitPage == 'group_detail'){
			editClass.push({class:'.g_details',css:'margin-top'});
		}else if(visitPage == 'store'){
			editClass.push({class:'.cable',css:'top'});
			editClass.push({class:'#order_list',css:'margin-top'});
		}else if(visitPage == 'store_detail'){
			editClass.push({class:'.g_details',css:'margin-top'});
		}else if(visitPage == 'cashier'){
			editClass.push({class:'.cable',css:'top'});
			editClass.push({class:'#order_list',css:'margin-top'});
		}else if(visitPage == 'cashier_detail'){
			editClass.push({class:'.g_details',css:'margin-top'});
		}else if(visitPage == 'cashier_set'){
			editClass.push({class:'.establish',css:'margin-top'});
		}else if(visitPage == 'cashier_success'){
			editClass.push({class:'.success',css:'margin-top'});
			editClass.push({class:'.fix_ewm',css:'top'});
		}else if(visitPage == 'foodshop'){
			editClass.push({class:'.meal',css:'margin-top'});
		}else if(visitPage == 'foodshop_list'){
			editClass.push({class:'.menu',css:'margin-top'});
		}else if(visitPage == 'foodshop_eat'){
			editClass.push({class:'.reserve .reserve_tab',css:'top'});
			editClass.push({class:'.stay',css:'margin-top'});
		}else if(visitPage == 'foodshop_table_list'){
			editClass.push({class:'.reserve .reserve_tab',css:'top'});
			editClass.push({class:'.reserve',css:'margin-top'});
		}else if(visitPage == 'mall'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'shop'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'shop_detail'){
			editClass.push({class:'.g_details',css:'margin-top'});
			editClass.push({class:'.defrayal',css:'top'});
		}else if(visitPage == 'shop_order_log'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'statistics'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'retail'){
			editClass.push({class:'.h44',css:'height'});
			editClass.push({class:'.hunt',css:'margin-top'});
		}else if(visitPage == 'guadan'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'commodity'){
			editClass.push({class:'.hunt',css:'margin-top'});
		}else if(visitPage == 'settlement'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'store_qrcode'){
			editClass.push({class:'.g_details',css:'margin-top'});
		}else if(visitPage == 'appoint'){
			editClass.push({class:'.h44',css:'height'});
			editClass.push({class:'.cable',css:'top'});
			editClass.push({class:'.h140 .fix',css:'top'});
		}else if(visitPage == 'appoint_detail'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'setting'){
			editClass.push({class:'.h44',css:'height'});
		}else if(visitPage == 'foodshop_order_list'){
			editClass.push({class:'.stay',css:'margin-top'});
			editClass.push({class:'.reserve .reserve_tab',css:'top'});
			editClass.push({class:'.cable',css:'top'});
		}else if(visitPage == 'foodshop_row_list'){
			editClass.push({class:'.take',css:'margin-top'});
			editClass.push({class:'.cut',css:'top'});
		}
		
		if(editClass.length > 0){
			if(common.checkIphoneXApp()){
				var heightPx = 44;
			}else{
				var heightPx = 20;
			}
			for(var i in editClass){
				if($(editClass[i].class).size() > 0){
					$(editClass[i].class).css(editClass[i].css,parseInt($(editClass[i].class).css(editClass[i].css).replace('px',''))+heightPx+'px');
				}
			}
		}
	}
}

/* 校验HTTPS */
if(common.checkApp()){
	if(window.location.protocol == 'http:'){
		location.href = location.href.replace('http','https');
	}
}

/*修正请求域名*/
var requestDomain = '';
if(common.checkApp()){
	requestDomain = common.getAppDomain();
}else if(urlParam.requestDomain){
	requestDomain = urlParam.requestDomain;
}
if(!requestDomain || requestDomain == ''){
	requestDomain = document.domain;
}
requestUrl = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/appapi.php?c=';

if(urlParam.now_city){
	common.setCache('now_city',urlParam.now_city);
}

 /*
 * 跳转页面
 */
function redirect(url,type){
	if(url == 'close'){
		if(common.checkApp()){
			if(common.checkAndroidApp()){
				window.pigcmspackapp.closewebview(2);
			}else{
				common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
			}
		}else{
			window.history.go(-1);
		}
		return false;
	}
	
	window.location.href = url;
}

function redirect_self(url){
	var stateObj = { foo: url };
	history.replaceState(stateObj, "", url);
	location.reload();
}


/*优化手机中的点击事件*/
if(typeof(FastClick) == 'function'){
	FastClick.attach(document.body);
}

function openWebviewUrl(href){
	if(href == 'back'){
		if(common.checkWeixinApp()){
			wx.miniProgram.navigateBack();
		}else if(common.checkApp()){
			if(common.checkAndroidApp()){
				window.pigcmspackapp.closewebview(2);
			}else{
				common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
			}
		}else{
			window.history.go(-1);
		}
		return false;
	}
	if(common.checkWeixinApp()){
		if(href.indexOf('http://')===-1 && href.indexOf('https://')===-1){
			href = 'https://' + document.domain + href;
		}
		if(href == location.href){
			return false;
		}
		wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(href)+'&webview_title='+encodeURIComponent('打开新页面')});
	}else if(common.checkApp()){
		if(href.substr(0,4) != 'http' && href.substr(0,1) != '/'){
			href = window.location.protocol+'//'+requestDomain+'/packapp/'+visitWork+'/'+href;
		}
		if(common.checkAndroidApp()){
			window.pigcmspackapp.createwebview(href);
		}else{
			var iosHref = window.btoa(href);
			iosHref = iosHref.replace('/','&');
			common.iosFunction('createwebview/'+iosHref, {name:'createwebview', url:iosHref});
		}
	}else{
		window.location.href = href;
	}
}

/*打开地图*/
$(document).on('click','.openMap',function(){
	var that = $(this);
	if(that.data('type') == 'baidu'){
		if(that.data('baidu_lng') && that.data('baidu_lat')){
			if(!common.checkWeixin()){
				openMap({name:that.data('name'),address:that.data('address'),lng:that.data('baidu_lng'),lat:that.data('baidu_lat')});
			}else{
				common.http('Map&a=baiduToGcj02',{baidu_lat:that.data('baidu_lat'),baidu_lng:that.data('baidu_lng')}, function(data){
					openMap({name:that.data('name'),address:that.data('address'),lng:data.lng,lat:data.lat});
				});
			}
		}else{
			//非百度系坐标
			// common.http('Map&a=baiduToGcj02',{baidu_lat:that.data('lat'),baidu_lng:that.data('lng')}, function(data){
				// that.data('baidu_lng',data.lng);
				// that.data('baidu_lat',data.lat);
				// openMap({name:that.data('name'),address:that.data('address'),lng:data.lng,lat:data.lat});
			// });
		}
	}else{
		openMap({name:that.data('name'),address:that.data('address'),lng:that.data('lng'),lat:that.data('lat')});
	}
	return false;
});

function openMap(param){
	// alert(JSON.stringify(param));
	if(common.checkIosApp()){
		common.iosFunction('navigation_show/'+param.lng+'/'+param.lat+'/'+(param.name ? param.name : '')+'/', 
			{name:'navigation_show',lng: param.lng,lat: param.lat,maptype: param.name ? param.name : ''});
	}else if(common.checkAndroidApp()){
		window.pigcmspackapp.navigation_show(parseFloat(param.lng),parseFloat(param.lat),'',param.name ? param.name : '');
	}else if(common.checkWeixin()){
		wx.openLocation({
			latitude: param.lat,
			longitude: param.lng,
			name: param.name,
			address: param.address,
			scale: 14,
			infoUrl: window.location.href
		});
	}else{
		location.href = 'http://api.map.baidu.com/direction?origin=latlng:'+param.lat+','+param.lng+'|name:'+encodeURIComponent(param.name)+'&destination=latlng:'+param.lat+','+param.lng+'|name:'+encodeURIComponent(param.address)+'&mode=driving&region=100010000&src=baidu|jsapi&output=html';
	}
}

function apppush_open(title,msg,js_url,voice_second){
	layer.open({
		title:title
		,content: msg
		,btn: ['查看', '关闭']
		,yes: function(index){
			if(common.checkApp()){
				if(common.checkAndroidApp()){
				    window.pigcmspackapp.voicetip_stop();
					window.pigcmspackapp.createwebview(js_url);
				}else{
					common.iosFunction('voicetip_stop', {name:'voicetip_stop'});
					var iosHref = window.btoa(js_url);
					iosHref = iosHref.replace('/','&');
					common.iosFunction('createwebview/'+iosHref, {name:'createwebview', url:iosHref});
				}
			}else{
				location.href = js_url;
			}
			layer.close(index);
		}
		,no: function(){
			if(common.checkApp()){
				if(common.checkAndroidApp()){
				    window.pigcmspackapp.voicetip_stop();
				}else{
				    common.iosFunction('voicetip_stop', {name: 'voicetip_stop'});
				}
			}
		}
	});
}

/* 简单的消息弹出层 */
var motify = {
	timer:null,
	/*shade 为 object调用 show为true显示 opcity 透明度*/
	/*showTop top位置百分比*/
	log:function(msg,time,shade,showTop){
		if(!showTop){
			showTop = 50;
		}
		$('.motifyShade,.motify').hide();
		if(motify.timer) clearTimeout(motify.timer);
		if($('.motify').size() > 0){
			$('.motify').css('top',showTop+'%').show().find('.motify-inner').html(msg);
		}else{
			$('body').append('<div class="motify" style="display:block;top:'+showTop+'%;"><div class="motify-inner">'+msg+'</div></div>');
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
			}, time);
		}
	},
	clearLog:function(){
		$('.motifyShade,.motify').hide();
	}
};

function RndNum(n){
	var rnd = "";
	for(var i=0;i<n;i++){
		rnd+=Math.floor(Math.random()*10);
	}
	return rnd;
}
function arrUnique(array){
	var res = [];
	var json = {};
	for(var i = 0; i < array.length; i++){
		if(!json[array[i]]){
			res.push(array[i]);
			json[array[i]] = 1;
		}
	}
	return res;
}


function pageShow(){
	typeof pageShowFunc === "function" ? pageShowFunc() : false;
}

function umengPushDeviceToken(device_id,device_token){
	common.http('Storestaff&a=get_device_token', {device_id: device_id, device_token: device_token,noTip:true}, function (data) {
		
	});
}
 

var now_lang = common.getCache('now_lang',true);
if(urlParam.now_lang){
	common.setCache('now_lang',urlParam.now_lang,true);
	now_lang = urlParam.now_lang;
	if(urlParam.now_lang && now_lang && urlParam.now_lang != now_lang){
		common.removeCache('all_lang',true);
		common.removeCache('config',true);
	}
}

var all_lang = common.getCache('all_lang',true);
var config = common.getCache('config',true);

var L_ = function(keywords){  
	if(all_lang && typeof all_lang[keywords] != 'undefined'){ 
		if(all_lang[keywords]){
			 console.log(keywords)
			return all_lang[keywords];
		} 
	} 
	return keywords;
}

var first_replace_lang = true;
function replace_html_lang(){ 
	//L标签，语言标签
	$('L_').each(function(){
		var str = $.trim($(this).html());
		// console.log(L_(str));
		$(this).replaceWith(L_(str));
	});
	//无法用 L标签的
	$('.L_').each(function(){
		var str = $.trim($(this).html());
		// console.log(L_(str));
		$(this).html(L_(str)).removeClass('L_');
	});
	// input placeholder
	$('input,textarea').each(function(){
		var str = $.trim($(this).attr('placeholder'));
		console.log(L_(str));
		if(str != '' && !$(this).hasClass('L_N')){
			$(this).attr('placeholder',L_(str)).addClass('L_N');
		}
	});
	//英文需要的空格，数字和英文之间
	$('EN_').each(function(){
		$(this).replaceWith(now_lang != 'chinese' ? '&nbsp;' : '');
	});
	
	if(first_replace_lang){
		document.title = L_(document.title);
		var $iframe = $('<iframe src="/favicon.ico"></iframe>');
        $iframe.on('load',function() {
            setTimeout(function() {
                $iframe.off('load').remove();
            }, 0);
        }).appendTo($('body'));
	}
	first_replace_lang = false;
}
if(all_lang){
	console.log(all_lang)
	replace_html_lang();
}


