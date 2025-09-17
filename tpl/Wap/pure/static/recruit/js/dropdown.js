$(function(){
	$('.dropdown-toggle').click(function(){
		if($(this).hasClass('active')){
			close_dropdown();
			return false;
		}
 
		close_dropdown(); 
		
		$(".shade").css("height", $(window).height() - 41);
		$('.listBox .shade').removeClass('hide');
		
		$(this).addClass('active');
		var nav = $(this).attr('data-nav');
		$('.dropdown-wrapper').addClass(nav+' active');
		$('.'+nav+'-wrapper').addClass('active');
		if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.5){
			$('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.5);
			 
		}else if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.8){
			$('#dropdown_scroller,.dropdown-module').height($('#dropdown_scroller').height());
		}else{
			$('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.8);
 
		}
		if($('.listBox').height() < $('#dropdown_scroller').height()){
			$('.listBox .shade').height($('#scroller').height()+'px');
		}else{
			$('.listBox .shade').removeAttr('style');
		}
		
		if($('.'+nav+'-wrapper').find('.active').attr('data-has-sub')){
			$('#dropdown_sub_scroller').html('<div>'+$('.'+nav+'-wrapper').find('.active').find('.sub_cat').html()+'<div>').css('left','160px');
			$('#dropdown_scroller').width('160px');
			if($('#dropdown_sub_scroller>div').height() > $('#dropdown_sub_scroller').height()){ 
			}
		}
 
	});
	$('.listBox .shade').click(function(){
		close_dropdown();
	}); 
	 
});
function close_dropdown(){
	$('#dropdown_scroller,#dropdown_sub_scroller').css('width','');
	$('.dropdown-toggle').removeClass('active');
	$('.dropdown-wrapper').prop('class','dropdown-wrapper');
	$('#dropdown_scroller,.dropdown-module').css('height','');
	$('.listBox .shade').addClass('hide');
	$('#dropdown_sub_scroller').css('left','100%');
	$('#dropdown_scroller>div>ul>li').removeClass('active');
 
}


function list_locationTop(obj){
	if(obj.attr('data-topCategory-id')){
		obj.addClass('active').siblings('li').removeClass('active');
		$('.top_fixed .topCategory .nav-head-name').html(obj.find('span').data('name'));
		$('.fixpostionBox').removeClass('show');
		$('.top_fixed .topCategory .nav-head-name').removeClass('show'); 
		now_topCategory_id = obj.attr('data-topCategory-id');
	}
}
