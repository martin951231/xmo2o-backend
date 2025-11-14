/** laytpl-v1.1 */
;!function(){"use strict";var f,b={open:"{{",close:"}}"},c={exp:function(a){return new RegExp(a,"g")},query:function(a,c,e){var f=["#([\\s\\S])+?","([^{#}])*?"][a||0];return d((c||"")+b.open+f+b.close+(e||""))},escape:function(a){return String(a||"").replace(/&(?!#?[a-zA-Z0-9]+;)/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&#39;").replace(/"/g,"&quot;")},error:function(a,b){var c="Laytpl Error：";return"object"==typeof console&&console.error(c+a+"\n"+(b||"")),c+a}},d=c.exp,e=function(a){this.tpl=a};e.pt=e.prototype,e.pt.parse=function(a,e){var f=this,g=a,h=d("^"+b.open+"#",""),i=d(b.close+"$","");a=a.replace(/[\r\t\n]/g," ").replace(d(b.open+"#"),b.open+"# ").replace(d(b.close+"}"),"} "+b.close).replace(/\\/g,"\\\\").replace(/(?="|')/g,"\\").replace(c.query(),function(a){return a=a.replace(h,"").replace(i,""),'";'+a.replace(/\\/g,"")+'; view+="'}).replace(c.query(1),function(a){var c='"+(';return a.replace(/\s/g,"")===b.open+b.close?"":(a=a.replace(d(b.open+"|"+b.close),""),/^=/.test(a)&&(a=a.replace(/^=/,""),c='"+_escape_('),c+a.replace(/\\/g,"")+')+"')}),a='"use strict";var view = "'+a+'";return view;';try{return f.cache=a=new Function("d, _escape_",a),a(e,c.escape)}catch(j){return delete f.cache,c.error(j,g)}},e.pt.render=function(a,b){var e,d=this;return a?(e=d.cache?d.cache(a,c.escape):d.parse(d.tpl,a),b?(b(e),void 0):e):c.error("no data")},f=function(a){return"string"!=typeof a?c.error("Template not found"):new e(a)},f.config=function(a){a=a||{};for(var c in a)b[c]=a[c]},f.v="1.1","function"==typeof define?define(function(){return f}):"undefined"!=typeof exports?module.exports=f:window.laytpl=f}();

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
			theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]); 
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
		params['Device-Id'] = common.getDeviceId();
		params.app_version = common.getVersion();
		params.now_lang = now_lang;
		if (typeof(type) == 'undefined') {
			type = 'post';
		}
		if(!params.noTip){
			var noTip = false;
			var index = layer.open({type:2,shade:false,shadeClose:false});
		}else{
			var noTip = true;
			var index = 0;
			delete params.noTip;
		}
		if (type == 'post') {
			$.post(url, params, function(res){
				if (res.errorCode == '0') {
					sucFun(res.result);
				} else if (errFun) {
					errFun(res);
				} else {
					motify.log(res.errorMsg);
				}
				if(!noTip){
					console.log('关闭请求弹出层');
					layer.close(index);
				}
			}, 'json');
		} else if (type == 'get') {
			$.get(url, params, function(res){
				if (res.errorCode == '0') {
					sucFun(res.result);
				} else if (errFun) {
					errFun(res);
				} else {
					motify.log(res.errorMsg);
				}
				if(!noTip){
					console.log('关闭请求弹出层');
					layer.close(index);
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
     getVersionName:function(){ 
        var reg = /version_name=(.*?),/;
        var arr = reg.exec(navigator.userAgent.toLowerCase());
        if(arr == null){
        	return '';
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
			var arr = reg.exec(navigator.userAgent);
			if(arr == null){
				return 'packapp';
			}else{
				common.setCache('device_id',arr[1],true);
				return arr[1];
			}
		}else{
			return 'packapp';
		}
	},
	checkApp:function(){
        if(/(pigcms_pack_app)/.test(navigator.userAgent.toLowerCase())){
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
	getCache:function(key,is_session){ 
		key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;

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
		key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;
		
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
		key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;
		
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
			$.getScript('https://res.wx.qq.com/open/libs/jweixin/1.1.0/jweixin.js',function(){
				common.http('Config&a=wx_config',{noTip:true,work:visitWork,page:visitPage,location_url:location.href.split('#')[0]}, function(data){
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
	scan:function(okFuncName,param=''){
		if(common.checkWeixin()){
			wx.scanQRCode({
				needResult:1,
				scanType:["qrCode","barCode"],
				success:function(res){
					if (param != '') {
						window[okFuncName](res.resultStr, param);
					} else {
						window[okFuncName](res.resultStr);
					}
				}
			});
		}else if(common.checkApp()){
			var index = layer.open({type: 2,shadeClose:false,time:1});
			if(common.checkIos()){
				common.iosFunction('scan////'+okFuncName, {name: 'scan', scanType: '', callback:okFuncName });
			}else{
				window.pigcmspackapp.scan('','','',okFuncName);
			}
		}else{
			motify.log('您使用的设备不支持扫码');
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
	actionsheet:function(optionArr){
		//itemArr 可以传递文本型数组，也可以传递对象型数组，
		//		  当传递对象型时、对象中必须有text字段代表展示内容，选择后返回值是该对象
		//		  当传递文本型时、将展示将内容，选择后返回值是该文本在数组中的下标值。
		var options = {
			textColor:workColor,
			cancelTextColor:'#FF6634',
			title:L_('请选择'),
			showCancel:true,
			success:function(str){
				motify.log(str);
			},
			fail:function(str){
				motify.log(str);
			}
		}
		for (var i in optionArr){
			options[i] = optionArr[i];
		}
		if(typeof(options.itemArr) != 'object'){
			options.fail('按钮参数请传递数组,键是 itemArr ');
		}
		
		var bg_height = $('body').height() > $(window).height() ? $('body').height() : $(window).height();
		var msg_dom = '<div class="msg-bg" style="height:' + bg_height + 'px;"></div>';
		msg_dom+= '<div id="msg" class="msg-doc msg-option">';
		msg_dom+= '<div class="msg-bd">' + options.title + '</div>';
		for(var i in optionArr.itemArr){
			msg_dom+= '<div class="msg-option-btns btn" data-index="'+i+'" style="color:'+options.textColor+';">'+(typeof(optionArr.itemArr[i]) == 'string' ? optionArr.itemArr[i] : optionArr.itemArr[i].text)+'</div>';
		}
		if(options.showCancel){
			msg_dom+= '<button class="btn msg-btn-cancel" type="button" style="color:'+options.cancelTextColor+';">' + L_('取消') + '</button>';
		}
		msg_dom+= '</div>';
		$('body').append(msg_dom);
		
		$('.msg-btn-cancel,.msg-bg').click(function(){
			$('.msg-doc,.msg-bg').remove();
		});

		$('.msg-option-btns').click(function(){
			$('.msg-doc,.msg-bg').remove();
			var checkedItem = optionArr.itemArr[$(this).data('index')];
			var returnParam = typeof(checkedItem) == 'string' ? $(this).data('index') : checkedItem;
			options.success(returnParam);
		});	
	},
	iosFunction:function(paramStr, paramObj){
 
		var pack_version = common.getVersion();
		var str = parseFloat(pack_version) >= 1000;
		if ( str == true) {
			if(typeof window.webkit == 'object' || typeof window.webkit == 'function'){
				window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(paramObj);
			}else{
				setTimeout(function(){
					common.iosFunction(paramStr, paramObj);
				},200);
			}
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmspackapp://'+paramStr+'"></iframe>');
			setTimeout(function(){
				$('#iosIframe').remove();
			},50);
		}
		
	},
	appFunction:function(obj){
		// obj.name 调用app方法名
		if(!obj || !obj.name){
			console.log('');
			return false;
		}
		if (common.checkAndroid()){
			if((typeof window.pigcmspackapp == 'object' || typeof window.pigcmspackapp == 'function') && (typeof window.pigcmspackapp[obj.name] == 'object' || typeof window.pigcmspackapp[obj.name] == 'function')){
				window.pigcmspackapp[obj.name](JSON.stringify(obj));
			}else{
				setTimeout(function(){
					common.appFunction(obj);
				},200);
			}
		} else{
			if(typeof window.webkit == 'object' || typeof window.webkit == 'function'){
				window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(obj);
			}else{
				setTimeout(function(){
					common.appFunction(obj);
				},200);
			}
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
	fillPageBg:function(type,color){
		//type  1全部 color传字符串，2中间分开，传数组颜色,2个值
		if(type == 1){
			$('body').prepend('<div class="pageSliderHide" style="z-index:-100;position:fixed;top:0;left:0;width:100%;height:100%;background-color:'+color+';"></div>');
		}else{
			$('body').prepend('<div class="pageSliderHide" style="z-index:-100;position:fixed;top:0;left:0;width:100%;height:50%;background-color:'+color[0]+';"></div>');
			$('body').append('<div class="pageSliderHide" style="z-index:-100;position:fixed;bottom:0;left:0;width:100%;height:50%;background-color:'+color[1]+';"></div>');
		}
	}
};

/*全局检测登录态*/
if(visitPage != 'login' && visitPage != 'index' && visitPage != 'webapp_loading' && visitPage != 'agreement' && visitPage != 'userAgreement' && visitPage != 'reg'){ 
	common.checkLogin();
}

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
			editClass.push({class:'.clerk_top',css:'padding-top'});
		}else if(visitPage == 'tongji'){
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'info'){
			editClass.push({class:'.MyEx',css:'margin-top'});
		}else if(visitPage == 'setting'){
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'grab'){
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'pick'){
			$('.Navigation').css('top','64px');
			if(common.checkIphoneXApp()){
				$('.Navigation').css('top','88px');
			}
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'finish'){
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'detail'){
			editClass.push({class:'#fixed_top .h44',css:'height'});
		}else if(visitPage == 'reply'){
			editClass.push({class:'.header',css:'margin-top'});
		}else if(visitPage == 'agreement'){ 
			editClass.push({class:'.clerk_top',css:'margin-top'});
		}else if(visitPage == 'userAgreement'){ 
			editClass.push({class:'.clerk_top',css:'margin-top'});
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
requestUrl = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//'+requestDomain+'/appapi.php?c=';



 /*
 * 跳转页面
 * （默认页面往左滑动，即 openRightWindow）
 * （页面往右滑动，即 openLeftWindow）
 */
function redirect(url,type){
	if(url == 'back'){
		if(common.checkApp() && window.history.length <= 1){
			if(common.checkAndroidApp()){
				window.pigcmspackapp.closewebview(2);
			}else{
				common.iosFunction('closewebview/2', {name: 'closewebview', type: '2'});
			}
		}else{
			window.history.go(-1);
		}
		return false;
	}
	var animateCss = {},animateAfterCss = {};
	if(!type){
		type = 'openRightWindow';
	}
	switch(type){
		case 'openRightWindow':
			animateCss = {'left':'-'+$(window).width()+'px'};
			animateAfterCss = {'left':'0px'};
			break;
		case 'openLeftWindow':
			animateCss = {'left':$(window).width()+'px'};
			animateAfterCss = {'left':'0px'};
		break;
	}
	$('body,.pageSliderHide').animate(animateCss,300,function(){
		pageLoadTip();
		window.addEventListener("pagehide",function(){
			$('body,.pageSliderHide').css(animateAfterCss);
			pageLoadTipHide();
		},false);
		window.location.href = url;
	});
}
function redirect_self(url){
	var stateObj = { foo: url };
	history.replaceState(stateObj, "", url);
	location.reload();
}
/*页面加载提示*/
function pageLoadTip(msg){
	var defaultMsg = '', top = 0;  //'加载中...'
	//如果msg是数字，则是top的值！是字符串就是消息
	if (typeof(msg) == 'number') {
		top = msg;
		msg = defaultMsg;
	} else if(!msg) {
		top = 0;
		msg = defaultMsg;
	}
	$('#pageLoadTip').css({top:top+'px','display':'block'}).find('div').css({'margin-top':(($(window).height()-100-top)/2)+'px'}).html(msg);
}
function pageLoadTipHide(){
	$('#pageLoadTip').hide();
}
pageLoadTip();

/*优化手机中的点击事件*/
if(typeof(FastClick) == 'function'){
	FastClick.attach(document.body);
}

function openWebviewUrl(href){
	if(common.checkApp()){
		if(href.substr(0,4) != 'http' && href.substr(0,4) != 'file' && href.substr(0,1) != '/'){
			//href = window.location.protocol+'//'+requestDomain+'/packapp/'+visitWork+'/'+href;
			href = location.href.split('?')[0].replace(visitPage+'.html',href);
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

/*页面点击事件*/
$(document).on('click','.link-url',function(){
	if(common.checkApp() && $(this).data('webview')){
		var href = $(this).data('url');
		openWebviewUrl(href);
		return false;
	}
	
	if(typeof(noAnimate) == "undefined"){
		redirect($(this).data('url'),$(this).data('url-type'));
		return false;
	}else{
		window.location.href = $(this).data('url');
	}
});

/*A标签*/
$(document).on('click','a',function(event){
	if($(this).data('nobtn') || !$(this).attr('href')){
		return false;
	}
	if(common.checkApp() && $(this).data('webview')){
		var href = $(this).attr('href');
		openWebviewUrl(href);
		return false;
	}
	if(typeof(noAnimate) == "undefined"){
		// $('body').append('<div id="pageLoadTip" style="display:none;"><div></div></div>');
		var href = $(this).attr('href');
		if(href && href.substr(0,3) != 'tel' && href.substr(0,1) != '#' && href.substr(0,10) != 'javascript'){
			redirect(href,$(this).data('url-type'));
			return false;
		}else{
			location.href = href;
			event.stopPropagation();
			return false;
		}
	}
});

/*拨打电话事件*/
$(document).on('click','.callPhone',function(){
	var phone = $(this).data('phone')+'';
	if(!phone || phone == ''){
		return false;
	}

	var phoneTmp = phone.split(' ');
    var phoneArr = [];
    for(var i in phoneTmp){
		if(phoneTmp[i].length > 4){
			phoneArr.push(phoneTmp[i]);
		}
    }
	phoneArr = arrUnique(phoneArr);
	if(phoneArr.length == 0){
		return false;
	}else if(phoneArr.length == 1){
		location.href = 'tel:'+phoneArr[0];
	}else{
		common.actionsheet({
			itemArr:phoneArr,
			success:function(index){
				location.href = 'tel:'+phoneArr[index];
			}
		});
	}
	return false;
});

var is_google_map = common.getCache('is_google_map',true);
if(!is_google_map){
	$.ajax({
		url:requestUrl+'Deliver&a=config',
		data:{},
		async:false,
		success:function(data){
            common.setCache('is_google_map',data.result.google_map_ak,true);
            if(data.result.google_map_ak != '-1'){
                is_google_map  = data.result.google_map_ak;
            }
		}
	});
}else{
    if(is_google_map == '-1'){
        is_google_map  = '';
    }
}


var baidu_map_key = common.getCache('baidu_map_key',true);
if(!baidu_map_key){
	$.ajax({
		url:requestUrl+'Deliver&a=config',
		data:{},
		async:false,
		success:function(data){
            common.setCache('baidu_map_key',data.result.baidu_map_key,true);
            if(data.result.baidu_map_key != '-1'){
                baidu_map_key  = data.result.baidu_map_key;
            }
		}
	});
}else{
    if(baidu_map_key == '-1'){
        baidu_map_key  = '';
    }
}


/*拨打电话事件*/
$(document).on('click','.openMap',function() {
    var that = $(this);
	if(is_google_map !=""){
        openMap({name:that.data('name'),address:that.data('address'),lng:that.data('baidu_lng'),lat:that.data('baidu_lat')});
	}else if(that.data('type') == 'baidu'){
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
		if(is_google_map && common.getVersion() >= 1300){
			common.iosFunction('navigation_google_show/'+param.lng+'/'+param.lat+'/'+(param.name ? param.name : '')+'/'+(param.address ? param.address : '')+'/', 
			{ name: 'navigation_google_show', lng: param.lng, lat: param.lat, maptype: param.name ? param.name : '', address: param.address ? param.address : ''});
		}else{
			common.iosFunction('navigation_show/'+param.lng+'/'+param.lat+'/'+(param.name ? param.name : '')+'/'+(param.address ? param.address : '')+'/', 
			{ name: 'navigation_show', lng: param.lng, lat: param.lat, maptype: param.name ? param.name : '', address: param.address ? param.address : ''});
		}
	}else if(common.checkAndroidApp()){
		if(is_google_map && common.getVersion() >= 1300){
			window.pigcmspackapp.navigation_google_show(parseFloat(param.lng),parseFloat(param.lat),'',param.name ? param.name : '',param.address ? param.address : '');
		}else{
			window.pigcmspackapp.navigation_show(parseFloat(param.lng),parseFloat(param.lat),'',param.name ? param.name : '');
		}
	}else if(common.checkWeixin()){
	    if(is_google_map !=""){
            location.href = 'https://www.google.com/maps/dir/?api=1&destination='+param.lat+','+param.lng +'&travelmode=driving';
        }else{
    		wx.openLocation({
    			latitude: param.lat,
    			longitude: param.lng,
    			name: param.name,
    			address: param.address,
    			scale: 14,
    			infoUrl: window.location.href
    		});
        }
	}else{
		if(is_google_map !=""){
            location.href = 'https://www.google.com/maps/dir/?api=1&destination='+param.lat+','+param.lng +'&travelmode=driving';
        }else{
            location.href = 'http://api.map.baidu.com/direction?origin=latlng:'+param.lat+','+param.lng+'|name:'+encodeURIComponent(param.name)+'&destination=latlng:'+param.lat+','+param.lng+'|name:'+encodeURIComponent(param.address)+'&mode=driving&region=100010000&src=baidu|jsapi&output=html';
        }
	}
}

function apppush_open(title,msg,js_url,voice_second){
	layer.open({
		title:title
		,content: msg
		,btn: ['查看', '关闭']
		,yes: function(index){
			if(common.checkApp()){
				var split_str = '/'+ visitWork +'/';
				js_url = location.href.split(split_str)[0] + split_str + js_url.split(split_str)[1];
				if(common.checkAndroidApp()){
					window.pigcmspackapp.voicetip_stop();
					window.pigcmspackapp.createwebview(js_url);
				}else{
					common.iosFunction('voicetip_stop', {name: 'voicetip_stop'});
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
	common.http('Deliver&a=get_device_token', {device_id: device_id, device_token: device_token,noTip:true}, function (data) {
    
	});
}

var all_lang = common.getCache('all_lang',true);
var now_lang = common.getCache('now_lang',true);
function get_lang_list(lang,callFunc){
	if(!common.getCache('lang_list',true)){
		return;
	}
	if(all_lang){
		if(window[callFunc]){
			window[callFunc]();
		}
		replace_html_lang();
		
		common.http('Lang&a=get', {'from':visitWork,lang:lang}, function(data){
			common.setCache('now_lang',lang,true);
			common.setCache('all_lang',data,true);
			all_lang = data;
			now_lang = lang;
			if(window[callFunc]){
				window[callFunc]();
			}
			replace_html_lang();
		});
	}else{
		common.http('Lang&a=get', {'from':visitWork,lang:lang}, function(data){
			common.setCache('now_lang',lang,true);
			common.setCache('all_lang',data,true);
			all_lang = data;
			now_lang = lang;
			if(window[callFunc]){
				window[callFunc]();
			}
			replace_html_lang();
		});
	}
}

function report_lang(keywords){
	keywords = $.trim(keywords);
	if(keywords != ''){
		common.http('Lang&a=report', {'keywords':keywords,'from':visitWork,noTip:true,page:visitPage}, function(data){
			console.log(data);
			if(data[0]){
				all_lang[data[0].key] = '';
				// all_lang[data[0].key] = 'english';
				common.setCache('all_lang',all_lang,true);
			}
		});
	}
}

var L_ = function(keywords){
	if(!common.getCache('lang_list',true)){
		return keywords;
	}
	if(all_lang){
		if(typeof all_lang[keywords] != 'undefined'){
			if(all_lang[keywords]){
				return all_lang[keywords];
			}
		}else{
			report_lang(keywords);
			// return 'english';
		}
	}
	return keywords;
}

var first_replace_lang = true;
function replace_html_lang(){
	if(!common.getCache('lang_list',true)){
		return;
	}
	console.log(all_lang);
	//L标签，语言标签
	$('L_').each(function(){
		var str = $.trim($(this).html());
		console.log(str);
		console.log(L_(str));
		$(this).replaceWith(L_(str));
	});
	//无法用 L标签的
	$('.L_').each(function(){
		var str = $.trim($(this).html());
		console.log(L_(str));
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
		$('body').append("<style>.mui-switch:before{content:'"+L_('关闭')+"';}.mui-switch.mui-active:before{content:'"+L_('开启')+"';}</style>")
	}
	first_replace_lang = false;
}

$(function(){
	if(!common.getCache('lang_list',true)){
		return;
	}
	if(visitPage != 'index' && visitPage != 'login' && visitPage != 'webapp_loading'){
		if(all_lang){
			replace_html_lang();
		}else{
			redirect_self('index.html');
		}
	}
});

window.onerror = function(message, url, line, column, error) {
	console.log('log---onerror::::',message, url, line, column, error);
	common.http('Config&a=log_report',{'file':'js_error', 'message':message, 'url':url, 'location_href':location.href, 'line':line, 'column':column, 'error':error, 'userAgent':navigator.userAgent.toLowerCase() ,noTip:true}, function(data){
		
	});
}

//获取货币符号
var currency_symbol = '¥';//默认人民币
var is_change = 0;
if (common.getCache('Currency_symbol',true)) {
    currency_symbol = common.getCache('Currency_symbol',true);//取缓存值
    console.log('=========is_change1======', currency_symbol);
    is_change = 1;
}
common.http('Config&a=index',{}, function(data){
	console.log('=========66======');
	console.log(data);
	if (data.config.Currency_symbol) {
        common.setCache('Currency_symbol',data.config.Currency_symbol,true);
        console.log('=========setCache======', data.config.Currency_symbol);
        if (!is_change) {
            currency_symbol = data.config.Currency_symbol;
            console.log('=========is_change0======', currency_symbol);
        }
    }
	$('.currency_symbol').html(currency_symbol);
});

