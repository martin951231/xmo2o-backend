// JavaScript Document

$(function(){
	var height=$(window).height();
	var s_height=$(".swiper_content").height();
    s_height=(height-s_height)/2;
    $(".fakeloader").css("max-height",height);
	$(".swiper_content").css("top",s_height);
    $(".banner").css({"height":height,});
	
	$(".swiper_content .section_img img").load(function(){
		var s_height=$(".swiper_content").height();
		console.log(s_height);
		s_height=(height-s_height)/2;
		$(".fakeloader").css("max-height",height);
		$(".swiper_content").css("top",s_height);
	});
	
	$("footer button,.jiandou,.download_content ul li:nth-child(4),.layer").click(function(){
		var agent = navigator.userAgent.toLowerCase();
        if((/(micromessenger)/.test(agent))){
            $(".layer").fadeToggle(300);
            $(".download_content").fadeToggle(500);
        }else{
			if($(this).hasClass('ios_down') && agent.indexOf("like mac os x") > 0){
				var version = parseFloat((agent.match(/os [\d._]*/gi)+"").replace(/[^0-9|_.]/ig,"").replace(/_/ig,"."));
				if(version >= 9){
                    var url =   '/topic/app_wap_explain.html?'+$(this).attr('url');
                    location.href=url;
                    return false;
				}
			}
            var url = $(this).attr('url');
            location.href=url;
        }
	})
})