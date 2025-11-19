var myScroll,now_page = 0,hasMorePage = true,isLoading = true;
var isApp = motify.checkApp();
$(function(){
	if(isApp){
		$('header').hide();
	}
	
	$(document).on('click','.link-url-app-main',function(e){
		var jpurl = $(this).data('url');
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
			
		return false;
	});
	
	$('#backBtn').click(function(){
		redirect(backUrl,'openLeftWindow');
	});
	$('#scroller').css({'min-height':($(window).height()-57+1)+'px'});
	// myScroll = new IScroll('#container', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:false,scrollbars:true,shrinkScrollbars: 'scale',resizeScrollbars:false,fadeScrollbars:true});
    myScroll = new IScroll('#container', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
    var upIcon = $("#pullUp"),
		downIcon = $("#pullDown");
    
	myScroll.on("scroll",function(){
		var maxY = this.maxScrollY - this.y;
		if(this.y >= 50){
			if(!downIcon.hasClass("reverse_icon")) downIcon.addClass("reverse_icon").find('.pullDownLabel').html('松开可以刷新');
			return "";
		}else if(this.y < 50 && this.y > 0){
			if(downIcon.hasClass("reverse_icon")) downIcon.removeClass("reverse_icon").find('.pullDownLabel').html('下拉可以刷新');
			return "";
		}
		if(maxY >= 50){
			if(!upIcon.hasClass("reverse_icon")) upIcon.addClass("reverse_icon").find('.pullUpLabel').html('松开加载更多');
			return "";
		}else if(maxY < 50 && maxY >=0){
			if(upIcon.hasClass("reverse_icon")) upIcon.removeClass("reverse_icon").find('.pullUpLabel').html('上拉加载更多');
			return "";
		}
	});
	myScroll.on("slideDown",function(){
		if(this.y > 50){
			$('#bindVillageBox,#villageBox,#bindFamilyBox').hide();
			now_page = 0;
			hasMorePage = true;
			upIcon.removeClass('noMore loading').show();
			pageLoadTip(57);
			getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
		}
	});
	myScroll.on("slideUp",function(){
		if(hasMorePage){
			$('.villageBox dl').append('<dd class="loadMoreList">正在加载</dd>');
			// upIcon.addClass('loading');
			// setTimeout(function(){
				myScroll.refresh();
				myScroll.scrollTo(0,this.maxScrollY);
				getList(true);
			// },200);
		}
		/* if(this.maxScrollY - this.y > 50 && !upIcon.hasClass('noMore')){
			upIcon.addClass('noMore').hide();
		} */
	});
	/* myScroll.on("scrollEnd",function(){
		if(hasMorePage && upIcon.hasClass('noMore') && !upIcon.hasClass('loading')){
			$('.listBox dl').append('<dd class="loadMoreList">正在加载</dd>');
			upIcon.addClass('loading');
			// setTimeout(function(){
				myScroll.refresh();
				myScroll.scrollTo(0,this.maxScrollY);
				getList(true);
			// },200);
		}
	}); */
	/* $(window).resize(function(){
		window.location.reload();
	}); */
	
    if(isApp){
		$('header').hide();
        $('#container').css({'top':'60px'});
    }
    
	$('#search-form').submit(function(){
		var keyword = $.trim($('#keyword').val());
		$('#keyword').val(keyword);
		$('#bindVillageBox,#villageBox,#bindFamilyBox').hide();
		now_page = 0;
		hasMorePage = true;
		upIcon.removeClass('noMore loading').show();
		pageLoadTip(57);
		getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
		return false;
	});
	$(document).on('click','.listBox li.more',function(){
		$(this).hide().siblings('li').show();
		$(this).prev().css({'border-bottom':'none'});
		// setTimeout(function(){
		myScroll.refresh();
		// },200);
	});
	
	// $('.listBox').css('min-height',$(window).height()-95);
	pageLoadTip(57);
	if(user_long == '0'){
		getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
	}else{
		pageGetList();
	}
});
function pageGetList(options,options1,lng,lat){
	if (lng && lat) {
		user_long = lng;
		user_lat = lat;
	}
	getList(false);
}

function getList(more){
	isLoading = true;
	var go_url = location_url;
	now_page += 1;
	go_url += "&page="+now_page;
	$('.noMoreDiv').hide();
	$.post(go_url,{keyword:$('#keyword').val(),account:account,user_long:user_long,user_lat:user_lat},function(result){
		$('.loadMoreList').remove();
		if(now_page == 1 && result.bind_village_list){
			for (var i = 0; i < result.bind_village_list.length; i++) {
				var property_phone_arr = result.bind_village_list[i].property_phone.split(' ');
				result.bind_village_list[i].property_phone = property_phone_arr[0];
			}
			laytpl($('#villageBoxTpl').html()).render(result.bind_village_list, function(html){
				$('#bindVillageBox').show().find('dl').html(html);
			});
		}
		
		if(now_page == 1 && result.bind_village_family_list){
			laytpl($('#village_bind_familyBoxTpl').html()).render(result.bind_village_family_list, function(html){
				$('#bindFamilyBox').show().find('dl').html(html);
			});
		}
		if (result.open_house_logo_show == 1) {
			$('.is_show').show();
		}else{
			$('.is_show').hide();
		}
		
		if(result.login_test && (document.domain.indexOf('.pigcms.com') != -1 || document.domain.indexOf('.group.com') != -1)){
			layer.open({
				content:'为了方便您测试 社区O2O 更多功能，建议您先进行登录。我们然后为您默认绑定一个测试小区。',
				btn: ['去登录','先看看'],
				yes:function(){
                    if(isApp){
                        if(motify.checkAndroid()){
                            window.lifepasslogin.startJsToJavaFunction();
                            function ReturnLastPage(){
                                history.back();
                            }
                        }
                        if(motify.checkIos()){
                        	iosCommon.iosFunction('login', {name:'login'});
                            function refresh(){
								alert(222222);
                                window.location.reload();
                            }
                        }
                    }else{
					    window.location.href = window.location.pathname+'?c=Login&a=index';
                    }
				}
			});
		}
		if(result.first_test && (document.domain.indexOf('.pigcms.com') != -1 || document.domain.indexOf('.group.com') != -1)){
			layer.open({
				content:'为了方便您测试 社区O2O 更多功能，我们为您默认绑定了一个测试小区。建议您进入此小区测试完整的功能。',
				btn: ['好的'],
			});
		}
		if(result.village_list && result.village_list.village_count > 0){
			hasMorePage = now_page < result.village_list.totalPage ? true : false;
			for (var i = 0; i < result.village_list.village_list.length; i++) {
				var property_phone_arr = result.village_list.village_list[i].property_phone.split(' ');
				result.village_list.village_list[i].property_phone = property_phone_arr[0];
			}
			laytpl($('#villageBoxTpl').html()).render(result.village_list.village_list, function(html){
				if(now_page == 1){
					$('#villageBox').show().find('dl').html(html);
				}else{
					$('#villageBox').show().find('dl').append(html);
				}
			});
			if (result.open_house_logo_show == 1) {
				$('.is_show').show();
			}else{
				$('.is_show').hide();
			}
			if(!hasMorePage){
				$("#pullUp").addClass('noMore').hide();
				$('#villageBox dl').append('<dd class="noMore">更多小区正在入驻，敬请期待!</dd>');
			}
		}else{
			$('.noMoreDiv').show();
			$("#pullUp").addClass('noMore').hide();
		}
		pageLoadTipHide();
		setTimeout(function(){
			// console.log(more);
			myScroll.refresh();
			if(!more){
				myScroll.scrollTo(0,0);
			}
		},200);
		isLoading = false;
		
		if(/(main_webview)/.test(navigator.userAgent.toLowerCase())){
			$('.link-url').addClass('link-url-app-main').removeClass('link-url');
		}
		
	});
}
function androidRefresh(ticket,device){
	if(ticket && device){
		$.post("./wap.php?c=Home&a=ajaxLogin", { ticket: ticket, 'Device-Id': device },function(){
			location.reload();
		});
	}
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