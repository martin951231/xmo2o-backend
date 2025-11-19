
var visitWork = "deliver_web"
var langVisitWork = 'deliver'
//当前访问页面名称
var visitPage = location.pathname.split('/').pop().split('.')[0];
if (visitPage == '') {
    visitPage = 'index';
}
var ticket = ''
/*
 * 获取参数
 *
 */
function GetRequest() {
    var url = location.search;
    var theRequest = {};
    if (url.indexOf("?") != -1) {
        var str = url.substr(1);
        strs = str.split("&");
        for (var i = 0; i < strs.length; i++) {
            theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
        }
    }
    return theRequest;
}
var urlParam = GetRequest();

var requestUrl = window.location.protocol + '//' + document.domain + '/v20/public/index.php';

var requestUrlOld = ''


var common = {
    'http': function (url, params, sucFun, errFun, type) {
        if (typeof (url) == 'undefined') {
            return false;
        }
        url = requestUrl + url;
        if (!params) {
            params = {};
        }
        params.v20_ticket = ticket;
        params['Device-Id'] = common.getDeviceId();
        params.app_version = common.getVersion();
        console.log('请求路径------', url)
        console.log('请求参数------', params)
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
                console.log('请求结果------', res)
                if (res.status == '1000') {
                    sucFun(res.data);
                } else if (errFun) {
                    errFun(res);
                } else {
                    // vant.Toast(res.msg)
                    vant.Dialog.alert({
                        title: '提示', 
                        message: res.msg,
                        confirmButtonColor: '#27A3F7',
                        confirmButtonText: '确定',
                    }).then(function () {
                    }).catch(function () {
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
        } else if (type == 'get') {
            $.get(url, params, function (res) {
                if (res.status == '1000') {
                    sucFun(res.data);
                } else if (errFun) {
                    errFun(res);
                } else {
                    vant.Dialog.alert({
                        title:'提示',
                        message: res.msg,
                        confirmButtonColor: '#27A3F7',
                        confirmButtonText:'确定',
                    }).then(function () {
                    }).catch(function () {
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
    },
    'httpOld':function(url, params, sucFun, errFun, type){
		if (typeof(url) == 'undefined') {
			return false;
		}
		url = requestUrlOld + url;
		if(!params){
			params = {};
		}
		params.v20_ticket = ticket || common.getCache('ticket',true);
		params['Device-Id'] = common.getDeviceId();
		params.app_version = common.getVersion();
		params.now_lang = now_lang;
		if (typeof(type) == 'undefined') {
			type = 'post';
		}
		if(!params.noTip){
			var noTip = false;
			vant.Toast.loading({ duration: 0 });
		}else{
			var noTip = true;
			delete params.noTip;
		}
		if (type == 'post') {
			$.post(url, params, function(res){
                console.log('httpOld---请求url--params---res',{url:url,params:params,res:res})
				if (res.errorCode == '0') {
					sucFun(res.result);
				} else if (errFun) {
					errFun(res);
				} else {
                    vant.Dialog.alert({
                        title: L_('提示'), 
                        message: res.errorMsg,
                        confirmButtonColor: '#27A3F7',
                        confirmButtonText: L_('确定'),
                    }).then(function () {
                    }).catch(function () {
                    });
				}
				if(!noTip){
					console.log('关闭请求弹出层');
					vant.Toast.clear();
				}
			}, 'json');
		} else if (type == 'get') {
			$.get(url, params, function(res){
				if (res.errorCode == '0') {
					sucFun(res.result);
				} else if (errFun) {
					errFun(res);
				} else {
					vant.Dialog.alert({
                        title: L_('提示'), 
                        message: res.errorMsg,
                        confirmButtonColor: '#27A3F7',
                        confirmButtonText: L_('确定'),
                    }).then(function () {
                    }).catch(function () {
                    });
				}
				if(!noTip){
					console.log('关闭请求弹出层');
					vant.Toast.clear()
				}
			}, 'json');
		}
	},
    checkLogin: function (isReutn) {
        if (common.getCache('ticket', true)) {
            return true;
        } else {
            //永久登录
            var ticket = common.getCache('ticket');
            if (!ticket) {
                if (isReutn) {
                    return false;
                } else {
                    redirect_self('login.html?back=' + visitPage);
                }
            } else {
                common.setCache('ticket', ticket, true);
                return true;
            }
            return false;
        }
    },
    getAppDomain: function () {
        var reg = /app_domain=(.*?),/;
        var arr = reg.exec(navigator.userAgent.toLowerCase());
        if (arr == null) {
            return '';
        } else {
            return arr[1];
        }
    },
    getAppTicket: function () {
        console.log('userAgent', navigator.userAgent)
        var reg = /ticket=(.*?),/;
        var arr = reg.exec(navigator.userAgent);
        if (arr == null) {
            return '';
        } else {
            return arr[1];
        }
    },
    getVersion: function () {
        var reg = /pack_version=(.*?),/;
        var arr = reg.exec(navigator.userAgent.toLowerCase());
        if (arr == null) {
            return '85';
        } else {
            return arr[1];
        }
    },
    getDeviceId: function () {
        var device_id = common.getCache('device_id', true);
        if (device_id) {
            console.log('device_id from cache', device_id);
            return device_id;
        }
        if (common.checkApp()) {
            var reg = /device-id=([^,]+)/;
            var arr = reg.exec(navigator.userAgent.toLowerCase());
            if (arr == null) {
                return 'packapp';
            } else {
                return arr[1];
            }
        } else {
            return 'packapp';
        }
    },
    checkApp: function () {
        if (/(pigcms_pack_app)/.test(navigator.userAgent.toLowerCase())) {
            return true;
        } else {
            return false;
        }
    },
    checkIos: function () {
        if (/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())) {
            return true;
        } else {
            return false;
        }
    },
    checkIosApp: function () {
        if (common.checkApp() && common.checkIos()) {
            return true;
        } else {
            return false;
        }
    },
    checkIphoneXApp: function () {
        if (common.checkIosApp() && $(window).height() == '812' && $(window).width() == '375') {
            return true;
        } else {
            return false;
        }
    },
    checkAndroid: function () {
        if (/(android)/.test(navigator.userAgent.toLowerCase())) {
            return true;
        } else {
            return false;
        }
    },
    checkAndroidApp: function () {
        if (common.checkApp() && common.checkAndroid()) {
            return true;
        } else {
            return false;
        }
    },
    checkWeixin: function () {
        if (/(micromessenger)/.test(navigator.userAgent.toLowerCase())) {
            return true;
        } else {
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
    getCache: function (key, is_session) {
        key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;

        if (is_session && !common.checkApp()) {
            var string = sessionStorage.getItem(key);
        } else {
            var string = localStorage.getItem(key);
        }

        if (string) {
            if (string.substring(0, 5) == 'str||') {
                return string.substring(5);
            } else {
                if (string.substring(5) != 'undefined') {
                    return JSON.parse(string.substring(5));
                }
            }
        }
    },
    setCache: function (key, obj, is_session) {
        key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;

        var string = '';
        if (typeof (obj) == 'string') {
            string = 'str||' + obj;
        } else {
            string = 'obj||' + JSON.stringify(obj);
        }
        if (is_session && !common.checkApp()) {
            return sessionStorage.setItem(key, string);
        } else {
            return localStorage.setItem(key, string);
        }
    },
    removeCache: function (key, is_session) {
        key = (is_session ? 'session_' : 'local_') + visitWork + '_' + key;

        if (is_session && !common.checkApp()) {
            return sessionStorage.removeItem(key);
        } else {
            return localStorage.removeItem(key);
        }
    },
    removeAllCache: function (is_session) {
        if (is_session) {
            var storage = sessionStorage;
        } else {
            var storage = localStorage;
        }
        storage.clear();
    },
    onlyScroll: function (that) {
        that.css({ 'overflow-y': 'auto', '-webkit-overflow-scrolling': 'touch' });
    },
    onScrollArr: {},
    scroll: function (that, callFunc) {
        that.css({ 'overflow-y': 'auto', '-webkit-overflow-scrolling': 'touch' });
        if (common.checkIosApp()) {
            that.height(that.height() - 20);
        }
        var rndNum = RndNum(8);
        if (!common.onScrollArr[rndNum]) {
            common.onScrollArr[rndNum] = {
                onScroll: false,
                dom: that,
                bottom: that.data('bottom') ? parseInt(that.data('bottom')) : 50,
                domHeight: that.height(),
                scrollHeight: that[0].scrollHeight
            }
        }
        that.scroll(function () {
            if (common.onScrollArr[rndNum].onScroll == false) {
                if (common.onScrollArr[rndNum].domHeight + that.scrollTop() >= common.onScrollArr[rndNum].scrollHeight - common.onScrollArr[rndNum].bottom) {
                    console.log('scroll-bottom-callFunc');
                    common.onScrollArr[rndNum].onScroll = true;
                    callFunc(rndNum);
                }
            }
        });
    },
    scrollEnd: function (index) {
        if (!index) {
            for (var i in common.onScrollArr) {
                common.onScrollArr[i].scrollHeight = common.onScrollArr[i].dom[0].scrollHeight;
                console.log(common.onScrollArr[i].domHeight);
                console.log(common.onScrollArr[i].dom.height());
                if (common.onScrollArr[i].domHeight <= 0) {
                    common.onScrollArr[i].domHeight = common.onScrollArr[i].dom.height();
                }
            }
            return false;
        }

        common.onScrollArr[index].onScroll = false;
        common.onScrollArr[index].scrollHeight = common.onScrollArr[index].dom[0].scrollHeight;
        if (common.onScrollArr[index].domHeight <= 0) {
            common.onScrollArr[index].domHeight = common.onScrollArr[index].dom.height();
        }
    },
    floatVal: function (number) {	//如果是整数，去除小数点后边没用的0，目前仅提供去除2位
        if (typeof (number) == 'string') {
            number = Number(number);
        }
        var str = number.toFixed(2);
        if (str.indexOf('.') >= 0) {
            if (str.substr(-3) == '.00') {
                return number.toFixed(0);
            } else if (str.substr(-1) == '0') {
                return number.toFixed(1);
            }
        }
        return str;
    },
    iosFunction: function (paramStr, paramObj) {
        var pack_version = common.getVersion();
        var str = parseFloat(pack_version) >= 1000
        if (str == true) {
            window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(paramObj);
        } else {
            $('body').append('<iframe id="iosIframe" src="pigcmspackapp://' + paramStr + '"></iframe>');
            setTimeout(function () {
                $('#iosIframe').remove();
            }, 50);
        }

    },
    formartdate: function (format) {	//yyyy-mm-dd hh:mm:ss
        var date = new Date();
        var year = date.getFullYear();
        var month = zeromonth = date.getMonth() + 1;
        var zeromonth = month;
        var strDate = date.getDate();
        var zerostrDate = strDate;
        if (month >= 1 && month <= 9) {
            zeromonth = "0" + month;
        }
        if (strDate >= 0 && strDate <= 9) {
            zerostrDate = "0" + strDate;
        }
        var hour = date.getHours();
        var minutes = date.getMinutes();
        var seconds = date.getSeconds();
        format = format.replace('yyyy', year);
        format = format.replace('mm', zeromonth);
        format = format.replace('m', month);
        format = format.replace('dd', zerostrDate);
        format = format.replace('d', strDate);
        format = format.replace('hh', hour);
        format = format.replace('mm', minutes);
        format = format.replace('ss', seconds);
        return format;
    },
    fillPageBg: function (type, color) {
        //type  1全部 color传字符串，2中间分开，传数组颜色,2个值
        if (type == 1) {
            $('body').prepend('<div class="pageSliderHide" style="z-index:-100;position:fixed;top:0;left:0;width:100%;height:100%;background-color:' + color + ';"></div>');
        } else {
            $('body').prepend('<div class="pageSliderHide" style="z-index:-100;position:fixed;top:0;left:0;width:100%;height:50%;background-color:' + color[0] + ';"></div>');
            $('body').append('<div class="pageSliderHide" style="z-index:-100;position:fixed;bottom:0;left:0;width:100%;height:50%;background-color:' + color[1] + ';"></div>');
        }
    },
    getNowLang: function () {
        var now_lang = common.getCache('now_lang', true);
        if (now_lang) {
            return now_lang;
        }
        if (common.checkApp()) {
            var reg = /now_lang=([^,]+)/;
            var arr = reg.exec(navigator.userAgent.toLowerCase());
            if (arr == null) {
                return 'chinese';
            } else {
                return arr[1];
            }
        } else {
            return 'chinese';
        }
    },
};

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
requestUrlOld = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//'+requestDomain+'/appapi.php?c=';




/*
* 跳转页面
* （默认页面往左滑动，即 openRightWindow）
* （页面往右滑动，即 openLeftWindow）
*/
function redirect(url, type) {
    if (url == 'back') {
        if (common.checkApp() && window.history.length <= 1) {
            if (common.checkAndroidApp()) {
                window.pigcmspackapp.closewebview();
            } else {
                window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage({ name: 'closewebview' });
            }
        } else {
            window.history.go(-1);
        }
        return false;
    }
    var animateCss = {}, animateAfterCss = {};
    if (!type) {
        type = 'openRightWindow';
    }
    switch (type) {
        case 'openRightWindow':
            animateCss = { 'left': '-' + $(window).width() + 'px' };
            animateAfterCss = { 'left': '0px' };
            break;
        case 'openLeftWindow':
            animateCss = { 'left': $(window).width() + 'px' };
            animateAfterCss = { 'left': '0px' };
            break;
    }
    $('body,.pageSliderHide').animate(animateCss, 300, function () {
        pageLoadTip();
        window.addEventListener("pagehide", function () {
            $('body,.pageSliderHide').css(animateAfterCss);
            pageLoadTipHide();
        }, false);
        window.location.href = url;
    });
}
function redirect_self(url) {
    var stateObj = { foo: url };
    history.replaceState(stateObj, "", url);
    location.reload();
}
/*页面加载提示*/
function pageLoadTip(msg) {
    var defaultMsg = '', top = 0;  //'加载中...'
    //如果msg是数字，则是top的值！是字符串就是消息
    if (typeof (msg) == 'number') {
        top = msg;
        msg = defaultMsg;
    } else if (!msg) {
        top = 0;
        msg = defaultMsg;
    }
    $('#pageLoadTip').css({ top: top + 'px', 'display': 'block' }).find('div').css({ 'margin-top': (($(window).height() - 100 - top) / 2) + 'px' }).html(msg);
}
function pageLoadTipHide() {
    $('#pageLoadTip').hide();
}
pageLoadTip();

/*优化手机中的点击事件*/
if (typeof (FastClick) == 'function') {
    FastClick.attach(document.body);
}

function openWebviewUrl(href, type) {
	if (href.substr(0, 4) != 'http' && href.substr(0, 1) != '/') {
		href = window.location.protocol + '//' + document.domain + '/packapp/' + visitWork + '/' + href;
	}
    if (common.checkApp()) {
        var params = { name: 'createwebview', url: href }
        if (type) {
            params.type = type
        }
        if (common.checkAndroidApp()) {
            window.pigcmspackapp.createwebview(JSON.stringify(params));
        } else {
            window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(params);
        }
    } else if(common.checkWeixinApp() && typeof wx != 'undefined') {
		wx.miniProgram.navigateTo({
			url:'/pages/webview/webview?webview_url='+ encodeURIComponent(href)
			,complete:function(){
				$("#submit_order").removeClass('disabled').html('确认订单');
			}
		});
	} else {
        window.location.href = href;
    }
}

/*页面点击事件*/
$(document).on('click', '.link-url', function () {
    if (common.checkApp() && $(this).data('webview')) {
        var href = $(this).data('url');
        console.log('5555566', visitWork)
        if (href.substr(0, 4) != 'http' && href.substr(0, 1) != '/') {
            href = window.location.protocol + '//' + document.domain + '/packapp/' + visitWork + '/' + href;
        }
        if (common.checkAndroidApp()) {
            window.pigcmspackapp.createwebview(href);
        } else {
            var iosHref = window.btoa(href);
            iosHref = iosHref.replace('/', '&');
            common.iosFunction('createwebview/' + iosHref, { name: 'createwebview', url: iosHref });
        }
        return false;
    }

    if (typeof (noAnimate) == "undefined") {
        redirect($(this).data('url'), $(this).data('url-type'));
        return false;
    } else {
        window.location.href = $(this).data('url');
    }
});

/*A标签*/
$(document).on('click', 'a', function (event) {
    if ($(this).data('nobtn')) {
        return false;
    }
    if (common.checkApp() && $(this).data('webview')) {
        var href = $(this).attr('href');
        if (href.substr(0, 4) != 'http' && href.substr(0, 1) != '/') {
            href = window.location.protocol + '//' + document.domain + '/packapp/' + visitWork + '/' + href;
        }
        if (common.checkAndroidApp()) {
            window.pigcmspackapp.createwebview(href);
        } else {
            var iosHref = window.btoa(href);
            iosHref = iosHref.replace('/', '&');
            common.iosFunction('createwebview/' + iosHref, { name: 'createwebview', url: iosHref });
        }
        return false;
    }
    if (typeof (noAnimate) == "undefined") {
        // $('body').append('<div id="pageLoadTip" style="display:none;"><div></div></div>');
        var href = $(this).attr('href');
        if (href && href.substr(0, 3) != 'tel' && href.substr(0, 1) != '#' && href.substr(0, 10) != 'javascript') {
            redirect(href, $(this).data('url-type'));
            return false;
        } else {
            location.href = href;
            event.stopPropagation();
            return false;
        }
    }
});



/* 简单的消息弹出层 */
var motify = {
    timer: null,
    /*shade 为 object调用 show为true显示 opcity 透明度*/
    /*showTop top位置百分比*/
    log: function (msg, time, shade, showTop) {
        if (!showTop) {
            showTop = 50;
        }
        $('.motifyShade,.motify').hide();
        if (motify.timer) clearTimeout(motify.timer);
        if ($('.motify').size() > 0) {
            $('.motify').css('top', showTop + '%').show().find('.motify-inner').html(msg);
        } else {
            $('body').append('<div class="motify" style="display:block;top:' + showTop + '%;"><div class="motify-inner">' + msg + '</div></div>');
        }
        if (shade && shade.show) {
            if ($('.motifyShade').size() > 0) {
                $('.motifyShade').css({ 'background-color': 'rgba(0,0,0,' + (shade.opcity ? shade.opcity : '0.3') + ')' }).show();
            } else {
                $('body').append('<div class="motifyShade" style="display:block;background-color:rgba(0,0,0,' + (shade.opcity ? shade.opcity : '0.3') + ');"></div>');
            }
        }
        if (typeof (time) == 'undefined') {
            time = 3000;
        }
        if (time != 0) {
            motify.timer = setTimeout(function () {
                $('.motify').hide();
            }, time);
        }
    },
    clearLog: function () {
        $('.motifyShade,.motify').hide();
    }
};


//滚动条在Y轴上的滚动距离
function getScrollTop() {
    var scrollTop = 0, bodyScrollTop = 0, documentScrollTop = 0;
    if (document.body) {
        bodyScrollTop = document.body.scrollTop;
    }
    if (document.documentElement) {
        documentScrollTop = document.documentElement.scrollTop;
    }
    scrollTop = (bodyScrollTop - documentScrollTop > 0) ? bodyScrollTop : documentScrollTop;
    return scrollTop;
}
//文档的总高度
function getScrollHeight() {
    var scrollHeight = 0, bodyScrollHeight = 0, documentScrollHeight = 0;
    if (document.body) {
        bodyScrollHeight = document.body.scrollHeight;
    }
    if (document.documentElement) {
        documentScrollHeight = document.documentElement.scrollHeight;
    }
    scrollHeight = (bodyScrollHeight - documentScrollHeight > 0) ? bodyScrollHeight : documentScrollHeight;
    return scrollHeight;
}

//浏览器视口的高度
function getWindowHeight() {
    var windowHeight = 0;
    if (document.compatMode == "CSS1Compat") {
        windowHeight = document.documentElement.clientHeight;
    } else {
        windowHeight = document.body.clientHeight;
    }
    return windowHeight;
}

// 日期转字符串
function dateToString(date) {
    var year = date.getFullYear();
    var month = (date.getMonth() + 1).toString();
    var day = (date.getDate()).toString();
    if (month.length == 1) {
        month = "0" + month;
    }
    if (day.length == 1) {
        day = "0" + day;
    }
    var dateTime = year + "-" + month + "-" + day;
    return dateTime;
}


// 获取标题栏高度
var titleHeight = 0
window['getTitleHeight'] = function (height) {
    console.log('-------------getTitleHeight',height)
    titleHeight = Number(height).toFixed(0)
    var content = document.getElementById("content");
    content.style.paddingTop = titleHeight + "px";
    var filter = document.getElementById("filter");
    console.log('header height', content.style.paddingTop)
    if (filter) {
        filter.style.top = titleHeight + "px";
        if (titleHeight == 44 && common.checkIosApp()) {
            // 刘海屏适配
            console.log('-------------getTitleHeight top',titleHeight + 44)
            filter.style.top = Number(titleHeight) + 44 + "px";
        }
        content.style.paddingTop = (Number(titleHeight) + 50) + "px";
        console.log('filter top', filter.style.top)
    }
}


// 告知app标题栏显示
function setTitleStyle(title, color, background) {
    if (!color) {
        // 默认显示黑色的
        color = '#333333'
    }
    var params = {
        name: 'setTitleStyle',
        title: title,
        color: color,
        background: background,
    }
    if (common.checkApp()) {
        if (common.checkIos()) {
            window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(params);
        } else {
            window.pigcmspackapp.setTitleStyle(JSON.stringify(params));
        }
    } else {
    }
}

// 获取config
var configData = null;
if (common.getCache('config', true)) {
    configData = common.getCache('config', true)
} else {
    getConfig()
}
function getConfig(callback) {
    common.http('/deliver/config/index', { noTip: true }, function (data) {
        if (data) {
            configData = data
            common.setCache('config', data, true)
            // 获取多语言列表
            getLangList()
            typeof (callback) == 'function' && callback(data)
        }
    })
}

// 获取对语言列表
function getLangList() {
    common.httpOld('Deliver&a=lang',{noTip:true}, function(data){
        if(data.lang_config && data.lang_config.now_lang && (data.lang_config.now_lang != 'chinese' || (data.lang_config.lang_list && data.lang_config.lang_list.length > 0))){
            common.setCache('lang_list',data.lang_config.lang_list,true);	
            get_lang_list(data.lang_config.now_lang);
        }
    });
}

// 获取ticket
function getTicket() {
    ticket = common.getCache('ticket', true)
    if (common.checkApp()) {
        ticket = common.getAppTicket()
        // common.setCache('ticket', ticket, true)
    } else {
        // todo h5处理
    }
}
getTicket()

// 多语言
var all_lang = common.getCache('all_lang',true);
var now_lang = common.getNowLang() || common.getCache('now_lang',true);
function get_lang_list(lang,callFunc){
	if(!common.getCache('lang_list',true)){
		return;
	}
	if(all_lang){
		if(window[callFunc]){
			window[callFunc]();
		}
		replace_html_lang();
		
		common.httpOld('Lang&a=get', {'from':langVisitWork,lang:lang}, function(data){
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
		common.httpOld('Lang&a=get', {'from':langVisitWork,lang:lang}, function(data){
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
	keywords = keywords.trim();
	if(keywords != ''){
		common.httpOld('Lang&a=report', {'keywords':keywords,'from':langVisitWork,noTip:true,page:visitPage}, function(data){
			if(data[0]){
				all_lang[data[0].key] = '';
				// all_lang[data[0].key] = 'english';
				common.setCache('all_lang',all_lang,true);
			}
		});
	}
}

var L_ = function(keywords = '',params = null){
	if(common.getCache('lang_list',true)){
        if(all_lang){
            if(typeof all_lang[keywords] != 'undefined'){
                if (all_lang[keywords] && configData && configData.open_multilingual && configData.open_multilingual == 1) {
                    keywords = all_lang[keywords]
                }
            }else{
                report_lang(keywords);
            }
        }
	}
	
    if (keywords && params && Object.keys(params).length) {
        keywords = replaceLang(keywords,params)
    }
	return keywords;
}

// 多语言字符替换 params = {X1:'1',X2:'2',...} 键值不能重复
function replaceLang(keywords = '',params = null) {
    for (let k in params) {
        var reg = `/${k}/g`;
        keywords = keywords.replace(eval(reg), params[k]);
    }
    return keywords
}

var first_replace_lang = true;
function replace_html_lang(){
	if(!common.getCache('lang_list',true)){
		return;
	}
	
	if(first_replace_lang){
		document.title = L_(document.title);
	}
	first_replace_lang = false;
}

$(function(){
	if(!common.getCache('lang_list',true)){
		return;
	}
	if(visitPage != 'register'){
		if(all_lang){
			replace_html_lang();
		}else{
			redirect_self('register.html');
		}
	}
});

