var wx;
$(function(){
	
	$(".banner").css('height',Math.ceil($(window).width()/640*238));
	
	$('#container').css({'padding-top':50,'position':'static'});
	$('#container').css({'padding-top':50,'position':'static'});
	$('#scroller').css({'position':'static'});
	
	var mySwiper = $('.swiper-container1').swiper({
		pagination:'.swiper-pagination1',
		loop:true,
		grabCursor: true,
		paginationClickable: true,
		autoplay:3000,
		autoplayDisableOnInteraction:false,
		simulateTouch:false
	});
	var mySwiper2 = $('.swiper-container2').swiper({
		pagination:'.swiper-pagination2',
		loop:true,
		grabCursor: true,
		paginationClickable: true,
		simulateTouch:false
	});
	$('.swiper-container4 .swiper-slide').width($('.swiper-container4 .swiper-slide').width());
	var mySwiper4 = $('.swiper-container4').swiper({
		freeMode:true,
		freeModeFluid:true,
		slidesPerView: 'auto',
		simulateTouch:false/*,
		centeredSlides: true*/
	});
	
	// motify.log('正在加载内容',0,{show:true});
	
	if($('.platformNews').size() > 0){
		var platformNewsIndex = 0;
		var platformNewsSize = $('.platformNews .list li').size();
		setInterval(function(){
			platformNewsIndex += 1;
			if((platformNewsIndex*2)+2>platformNewsSize){
				platformNewsIndex = 0;
			}
			$('.platformNews .list li').hide();
			$('.platformNews .list').find('.num-'+((platformNewsIndex*2)+1)+',.num-'+((platformNewsIndex*2)+2)).show();
		},4000);
	}
	
	$('#qrcodeBtn').click(function(){
		if(motify.checkWeixin()){
			motify.log('正在调用二维码功能');
			wx.scanQRCode({
				desc:'scanQRCode desc',
				needResult:0,
				scanType:["qrCode"],
				success:function (res){
					// alert(res);
				},
				error:function(res){
					motify.log('微信返回错误！请稍后重试。',5);
				},
				fail:function(res){
					motify.log('无法调用二维码功能');
				}
			});
		}else{
			motify.log('您不是微信访问，无法使用二维码功能');
		}
	});
		$('.content_video').css({'width':$(window).width()-20,'height':($(window).width()-20)*9/16});
});