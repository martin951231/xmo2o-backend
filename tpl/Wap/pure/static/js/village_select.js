var myScroll;
var isApp = motify.checkApp();
$(function(){
	if(isApp){
		$('header').hide();
	}
	$('#backBtn').click(function(){
		redirect(backUrl,'openLeftWindow');
	});
	$('#scroller').css({'min-height':($(window).height()-57+1)+'px'});
	// myScroll = new IScroll('#container', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:false,scrollbars:true,shrinkScrollbars: 'scale',resizeScrollbars:false,fadeScrollbars:true});
	if(isApp){
        $('#container').css({'top':'0px'});
    }else{
        myScroll = new IScroll('#container', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
    }
	
	if(/(main_webview)/.test(navigator.userAgent.toLowerCase())){
		$('.link-url').addClass('link-url-app-main').removeClass('link-url');
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
	
});