$(function(){
	$('.dropdown-toggle').click(function(){
		if($(this).hasClass('active')){
			close_dropdown();
			return false;
		}
		if(isLoading == true){
			return false;
		}
		close_dropdown();
		// $('.navBox').removeClass('absolute');
		// if(!$('.navBox').hasClass('absolute')) myScroll.scrollTo(0,'-50');
		
		$(".shade").css("height", $(window).height() - 41);
		$('.listBox .shade').removeClass('hide');
		
		$(this).addClass('active');
		var nav = $(this).attr('data-nav');
		$('.dropdown-wrapper').addClass(nav+' active');
		$('.'+nav+'-wrapper').addClass('active');
		if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.5){
			$('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.5);
			// $('#dropdown_scroller,.dropdown-module').height($('#dropdown_scroller div').height());
		}else if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.8){
			$('#dropdown_scroller,.dropdown-module').height($('#dropdown_scroller').height());
		}else{
			$('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.8);
			myScroll3 = new IScroll('#dropdown_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
		}
		if($('.listBox').height() < $('#dropdown_scroller').height()){
			$('.listBox .shade').height($('#scroller').height()+'px');
		}else{
			$('.listBox .shade').removeAttr('style');
		}
		
		if($('.'+nav+'-wrapper').find('.active').attr('data-has-sub')){
		    console.log('nav', nav);
			if (nav == 'category') {
                $('#dropdown_sub_scroller_second').html('<div>'+$('.'+nav+'-wrapper').find('.active').find('.sub_cat').html()+'<div>').css('left','120px');
                $('#dropdown_scroller').width('120px');
                if($('#dropdown_sub_scroller_second>div').height() > $('#dropdown_sub_scroller_second').height()){
                    myScroll1 = new IScroll('#dropdown_sub_scroller_second', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
                }
            } else {
                $('#dropdown_sub_scroller').html('<div>'+$('.'+nav+'-wrapper').find('.active').find('.sub_cat').html()+'<div>').css('left','160px');
                $('#dropdown_scroller').width('160px');
                if($('#dropdown_sub_scroller>div').height() > $('#dropdown_sub_scroller').height()){
                    myScroll2 = new IScroll('#dropdown_sub_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
                }
            }
            //
            // $('#dropdown_sub_scroller_third').html('<div>'+$('.'+nav+'-wrapper').find('.active').find('.sub_cat').html()+'<div>').css('left','120px');
            // $('#dropdown_scroller').width('120px');
            // if($('#dropdown_sub_scroller_third>div').height() > $('#dropdown_sub_scroller_third').height()){
            //     myScroll2 = new IScroll('#dropdown_sub_scroller_third', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
            // }
		}
        second_load();

		myScroll2 = new IScroll('#dropdown_sub_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
	});
	$('.listBox .shade').click(function(){
		close_dropdown();
	});
	
	$('.biz-wrapper ul>li, .category-wrapper ul>li').click(function(){
		var data_dir = $(this).attr('data-dir');
 		console.log('点击', $(this).attr('data-has-sub'));
 		if (data_dir && data_dir > 0) {
            $('#dropdown_sub_scroller').hide();
            $('#dropdown_sub_scroller_third').hide();
            $('#dropdown_sub_scroller_second').css({'overflow':'hide','overflow-y':''});
		} else {
            $('#dropdown_sub_scroller_second').hide();
            $('#dropdown_sub_scroller_third').hide();
            $('#dropdown_sub_scroller').css({'overflow':'hide','overflow-y':''});
		}
		$('.biz-wrapper ul>li, .category-wrapper ul>li').removeClass('active');
		if($(this).attr('data-has-sub')){
			$(this).addClass('active');
            if (data_dir && data_dir > 0) {
                $('#dropdown_sub_scroller_second').show();
                $('#dropdown_sub_scroller_second').html('<div>'+$(this).find('.sub_cat').html()+'<div>').css('left','120px');
                $('#dropdown_scroller').width('120px');
                if($('#dropdown_sub_scroller_second>div').height() > $('#dropdown_sub_scroller_second').height()){
                    myScroll1 = new IScroll('#dropdown_sub_scroller_second', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
                }
            } else {
                console.log('this-----index----------', $(this).find('.sub_cat'));
                $('#dropdown_sub_scroller').html('<div>'+$(this).find('.sub_cat').html()+'<div>').css('left','160px');
                $('#dropdown_scroller').width('160px');
                if($('#dropdown_sub_scroller>div').height() > $('#dropdown_sub_scroller').height()){
                    myScroll2 = new IScroll('#dropdown_sub_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
                }
            }
            second_load();
			// myScroll2.on('scrollStart', function (e) { console.log(e); });
			// myScroll2.on("scroll",function(e){console.log(e);});
		}
	});

	function second_load() {
        console.log('second_load');
        $('.second_sub').click(function(){
            console.log('点击11', $(this).attr('data-has-sub'));
            $('#dropdown_sub_scroller_third').css({'overflow':'hide','overflow-y':''});
            $('.second_sub').removeClass('active');
            if($(this).attr('data-has-sub')){
                $(this).addClass('active');
                $('#dropdown_sub_scroller_third').show();
                $('#dropdown_sub_scroller_third').html('<div>'+$(this).find('.sub_cat').html()+'<div>').css('left','240px');
                if($('#dropdown_sub_scroller_third>div').height() > $('#dropdown_sub_scroller_third').height()){
                    myScroll1 = new IScroll('#dropdown_sub_scroller_third', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
                }
                // myScroll2.on('scrollStart', function (e) { console.log(e); });
                // myScroll2.on("scroll",function(e){console.log(e);});
            }
        });


    }

	// $(document).on('click','.dropdown-list li',function(){
		// if(!$(this).attr('data-has-sub')){
			// alert(222);
			// list_location($(this));
		// }
	// });
});

function close_dropdown(){
    $('#dropdown_scroller,#dropdown_sub_scroller').css('width','');
    $('#dropdown_scroller,#dropdown_sub_scroller_second').css('width','');
    $('#dropdown_scroller,#dropdown_sub_scroller_third').css('width','');
	$('.dropdown-toggle').removeClass('active');
	$('.dropdown-wrapper').prop('class','dropdown-wrapper');
	$('#dropdown_scroller,.dropdown-module').css('height','');
	$('.listBox .shade').addClass('hide');
    $('#dropdown_sub_scroller').css('left','100%');
    $('#dropdown_sub_scroller_second').css('left','100%');
    $('#dropdown_sub_scroller_third').css('left','100%');
	$('#dropdown_scroller>div>ul>li').removeClass('active');
	if(myScroll3){
		myScroll3.destroy();
		myScroll3 = null;
		$('#dropdown_scroller>div').removeAttr('style');
	}
	if(myScroll2){
		myScroll2.destroy();
		myScroll2 = null;
        $('#dropdown_sub_scroller>div').removeAttr('style');
	}

    if(myScroll1){
        myScroll1.destroy();
        myScroll1 = null;
        $('#dropdown_sub_scroller>div').removeAttr('style');
    }
}