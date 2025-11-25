$(function(){
	$('.breadcrumb__item').hover(function(){
		if($(this).find('.breadcrumb_item__option').html()){
			$(this).addClass('dropdown--open');
		}
	},function(){
		$(this).removeClass('dropdown--open');
	});
	
	$('.category_list_img').mouseover(function(){
		$(this).find('.bmbox').css('display', 'block');
	}).mouseout(function(){
		$(this).find('.bmbox').css('display', 'none');
	});

	$(".menu_right .menu_right_top li.menu_right_hover").css({"background-color":"rgba(0, 0, 0, .1)"});
	$(".menu_right .menu_right_top li.ctur").hover(function(){
		$(this).css({"background-color":"rgba(0, 0, 0, .1)"});
	}, function(){
		$(this).css({"background-color":""});
	})
	
	$(".paginator-wrapper .paginator .current a, .paginator-wrapper .paginator .current span").css({
	    "border-color":main_color,
	    "background-color":main_color
	})
	$(".filter-section-wrapper .filter-sect-list .current a").css({"background-color":main_color})
	$('.filter-section-wrapper .filter-sect-list .item a').hover(function(){
		$(this).css({'background-color':main_color, 'color':'#fff'})
	}, function(){
		var str = $(this).parents('li.item').attr('class');
		if (str.indexOf('current') == -1) {
			$(this).css({'background-color':'', 'color':''})
		}
	})
	$(".filter-breadcrumb .breadcrumb__item .filter-tag, .filter-breadcrumb .breadcrumb__item .breadcrumb-item--delete").css({"border-color":main_color})
	
	$(".filter-breadcrumb .breadcrumb__item .breadcrumb-item--delete").hover(function(){
		$(this).css({"background-color":main_color})
		$(this).siblings('.filter-tag').css({"color":main_color})
		$(this).siblings('.filter-tag').find('.tri').css({"border-bottom":"3px solid "+main_color})
	}, function(){
		$(this).css({"background-color":""})
		$(this).siblings('.filter-tag').css({"color":""})
		$(this).siblings('.filter-tag').find('.tri').css({"border-bottom":""})
	})

	$(".filter-breadcrumb .breadcrumb__item .breadcrumb_item__option .current").css({"color":main_color})
	$('.filter-breadcrumb .breadcrumb__item .breadcrumb_item__option').css({"border-color":main_color})
	$(".filter-breadcrumb .breadcrumb__item .filter-tag, .filter-breadcrumb .breadcrumb__item .breadcrumb_item__option a").hover(function(){
		$(this).css({"color":main_color})
		$(this).find('.tri').css({"border-bottom":"3px solid "+main_color})
	},function(){

		$(this).css({"color":""})
		$(this).find('.tri').css({"border-bottom":""})
		$(".filter-breadcrumb .breadcrumb__item .breadcrumb_item__option .current").css({"color":main_color})
	})

	$('.banner, .nav .btnclick, .cheap, .menu_right_top, .menu_left').css({"background-color":main_color})
	$('.menu_left .menu_left_top').css({"background-color":"rgba(0, 0, 0, .1)"})
	$('.nav form').css({"border":"2px solid "+main_color})

	$('.nav .search_txt a').hover(function(){
		$(this).find('span').css('color', main_color)
	}, function(){
		$(this).find('span').css('color', '')
	})

	$('.side-nav__list .current a').css('background', main_color);
	$('.side-nav__list a').hover(function(){
		if (!$(this).parents('li').attr('class')) {
			$(this).css('color', main_color)
		}
	}, function(){
		$(this).css('color', '')
	})
});