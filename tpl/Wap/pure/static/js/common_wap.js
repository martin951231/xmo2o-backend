$(function(){
	//$('#container').height($(window).height()-$('header').height()-$('footer').height()-5).css('overflow-y','scroll');
	$('.nav-dropdown-btn').click(function(){
		if($('#nav-dropdown').hasClass('active')){
			$('#nav-dropdown').removeClass('active');
		}else{
			$('#nav-dropdown').addClass('active');
		}
		return false;
	});
	$('body').bind('click',function(e){
		$('#nav-dropdown').removeClass('active');
	});
	
	$(window).scroll(function(){
		if($(window).scrollTop()>150){
			$('.top-btn').show();
		}else{
			$('.top-btn').hide();
		}
	});
	
	$('.top-btn').click(function(){
		$(window).scrollTop(0);
	});

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
});

function is_weixin(){
    var ua = navigator.userAgent.toLowerCase();
    if(is_mobile() && ua.match(/MicroMessenger/i)=="micromessenger") {  
        return true;  
    } else {  
        return false;  
    }  
}
function is_mobile(){
	if ((navigator.userAgent.match(/(iPhone|iPod|Android|ios|iPad)/i))){
		if((navigator.platform.indexOf("Win") == 0) || (navigator.platform.indexOf("Mac") == 0)){
			return false;
		}else{
			return true;
		}
	}else{
		return false;
	}
}