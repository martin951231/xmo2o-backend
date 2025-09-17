var navListHasSub = true;
var menuList = [];
$(function(){
	
	//复制导航
	$('#sidebar .nav-list .arrow.fa-angle-down').remove();
	$('#sidebar .nav-list .menu-icon.fa-caret-right').remove();
	$.each($('#sidebar .nav-list .submenu-box a'),function(i,item){
		$(item).html('<span class="menu-text">'+$.trim($(item).text())+'</span>');
	});
	$.each($('#sidebar .nav-list>.hsub'),function(i,item){
		if(!$(item).hasClass('open')){
			$(item).addClass('not-open');
		}
	});
	$('#sidebar .nav-list>li.active').addClass('open');
	$('#sidebar .nav-list li.open .submenu .active').addClass('show open');
	$('#new-sidebar .new-nav-list').html($('#sidebar .nav-list').html());
	$('#new-sidebar .new-nav-list a[href="#"]').attr('href','javascript:;');
	$('#sidebar .nav-list .submenu-box a')
	
	$.each($('#new-sidebar a'),function(i,item){
		if($(item).attr('href') != 'javascript:;'){
			var temp = {
				name:$(item).find('span.menu-text').text(),
				url:$(item).attr('href')
			};
			menuList.push(temp);
		}
	});
	console.log('menuList',menuList);
	
	$('.link-url').click(function(){
		var href = $(this).data('url');
		if(href){
			location.href = href;
		}
	});
	$('#new_navbar .right').hover(function(){
		$('#new_navbar .right .nav-sub').show();
	},function(){
		$('#new_navbar .right .nav-sub').hide();
	});
	
	if($('.new-nav-list>.open .submenu-box').size() == 0){
		navListHasSub = false;
		changeMainMarginLeft();
	}
	/*$('.new-nav-list>.not-open').hover(function(){
		if($(this).find('.submenu').size() > 0){
			$(this).addClass('open');
			$('.new-nav-list>.active').addClass('open-hide');
		}
	},function(){
		$(this).removeClass('open');
		$('.new-nav-list>.active').removeClass('open-hide');
	});*/
	
	$('.new-nav-list>.not-open>.dropdown-toggle').click(function(){
		var a = $(this).siblings('.submenu-box').find('.submenu li a').eq(0);
		if(a.hasClass('dropdown-toggle')){
			a = a.siblings('.submenu').find('li a').eq(0);
		}
		var href = a.attr('href');
		console.log('href',href);
		if(href){
			location.href = href;
		}
		return false;
	});
	
	$('.new-nav-list .submenu-box .dropdown-toggle').click(function(){
		var a = $(this).siblings('.submenu').find('li a').eq(0);
		var href = a.attr('href');
		console.log('href',href);
		if(href){
			location.href = href;
		}
		return false;
	});
	
	var third_nav = $('.new-nav-list>.open .show.open .nav-show.open');
	if(third_nav.size() > 0 && third_nav.find('li').size() > 0){
		$('#new-third-sidebar .ui-nav').html(third_nav.html());
		$('#new-third-sidebar').show();
		$('#new-third-sidebar+.main-content').css('margin-top','50px');
		swiperScrollX()
		
	}
	
	$('.new_navbar .left .search-box').click(function(){
		$('.search-layer-wrap').addClass('show');
		$('.search-layer-wrap .search-block .search-input input').focus();
	});
	$('.search-layer-wrap .search-layer').click(function(){
		$('.search-layer-wrap').removeClass('show');
		$('.search-layer-wrap .search-block .search-input input').val('');
		$('.search-layer-wrap .search-result-block').removeClass('show-result-list');
	});
	$('.search-layer-wrap .search-block .search-input input').keyup(function(e){
		var text = $.trim($(this).val());
		if(e.keyCode == 13 && text != ''){
			search_help(text);
		}
	});
	$('.search-layer-wrap .search-block .search-input .search-btn').click(function(){
		var text = $.trim($('.search-layer-wrap .search-block .search-input input').val());
		if(text != ''){
			search_help(text);
		}
	});
	
	$('#logout-btn').click(function(){
		var href = $(this).data('url');
		layer.open({
			title:'确认提醒：'
			,content:'您确定要退出账号吗？'
			,btn: ['确定','取消']
			,yes: function(index){
				window.location.href = href;
			}
		});
	});
	
	if($('#min_window').size() > 0){
		$('#min_window').click(function(){
			min_window();
		});
		$('#close_window').click(function(){
			layer.open({
				title:'确认提醒：'
				,content:'您确定要关闭软件吗？'
				,btn: ['确定','取消']
				,yes: function(index){
					close_window();
				}
			});
		});
	}
	
	$('.layer-iframe').click(function(){
		var iframe_width = $(this).data('iframe_width') ? $(this).data('iframe_width') + 'px' : '80%';
		var iframe_height = $(this).data('iframe_height') ? $(this).data('iframe_height') + 'px' : '80%';
		var iframe_title = $(this).data('title') ? $(this).data('title') : $.trim($(this).html());
		layer.open({
			title:iframe_title,
			area:[iframe_width,iframe_height],
			type: 2,
			shade: 0.4,
			content:$(this).attr('href')
		});
		return false;
	});
	
	$('#showMerchantAppPop').hover(function(){
		$('#downMerchantAppPop').css({position:'fixed',top:$(this).offset().top,left:$(this).offset().left-160}).removeClass('hide');
	},function(){
		$('#downMerchantAppPop').addClass('hide');
	});
	
	$('a.item-delete').click(function(){
		var href = $(this).attr('href');
		layer.open({
			title:'确认提醒：'
			,content:'确定要删除这条数据吗？不可恢复。'
			,btn: ['确定','取消']
			,icon: 3
			,yes: function(index){
				location.href = href;
			}
		});
		return false;
	});
});

function search_help(text){
	var searchMenuList = [];
	for(var i in menuList){
		if(menuList[i].name.indexOf(text) >= 0){
			var temp = menuList[i];
			temp.name = temp.name.replace(text,'<em>'+text+'</em>');
			searchMenuList.push(temp);
		}
	}
	$('.search-layer-wrap .search-result-block').addClass('show-result-list');
	console.log(text);
	console.log(searchMenuList);
	if(searchMenuList.length > 0){
		var menuHtml = '';
		for(var i in searchMenuList){
			menuHtml+= '<a href="'+searchMenuList[i].url+'"><span>'+searchMenuList[i].name+'</span></a>';
		}
		$('.search-layer-wrap .search-shortcut').html(menuHtml);
	}else{
		$('.search-layer-wrap .search-shortcut').html('<div class="empty">没有匹配到功能</div>');
	}
}

function changeMainMarginLeft(maxLeft){
	if(navListHasSub || maxLeft){
		$('#new-third-sidebar+.main-content').css('margin-left','281px');
	}else{
		$('#new-third-sidebar+.main-content').css('margin-left','160px');
	}
}

// 三级菜单超过一行点击可左右滑动
function swiperScrollX () {
	// 如果宽度超过一行
	var swiperWidth = $('#new-third-sidebar .ui-nav').width()
	var total_width = 0
	$('#new-third-sidebar .ui-nav>li').each(function(){
		$(this).addClass('swiper-slide')
		total_width += $(this).width()
	})
	if(total_width <= swiperWidth) {
		return
	}

	var third_sidebar = new Swiper('#nav-scroll',{
		freeMode: true,
		freeModeMomentumRatio: 0.5,
		slidesPerView: 'auto',
	});
	var translateX = $.cookie('translateX')
	console.log('22222', translateX)
	if (translateX) {
		$('#new-third-sidebar .ui-nav').attr('style','transition-duration: 300ms;transform:translateX('+translateX+'px)')
		$.cookie('translateX', null)
	}

	var maxTranslate = third_sidebar.maxTranslate();
	var maxWidth = -maxTranslate + swiperWidth / 2

	$(document).on('click', '#new-third-sidebar .swiper-slide', function(e){
		slideLeft = $(this).position().left;
		slideWidth = $(this).width()
		// 被点击slide的中心点
		slideCenter = slideLeft + slideWidth / 2
		// 偏移位置
		var translateX = 0
		if (slideCenter < swiperWidth / 2) {
			translateX = 0
		} else if (slideCenter > maxWidth) {
			translateX = maxTranslate
		} else {
			nowTlanslate = slideCenter - swiperWidth / 2
			translateX = -nowTlanslate
		}
		// 缓存滚动位置
		$.cookie('translateX', translateX)
	})
}

jQuery.cookie = function (key, value, options) {
    if (arguments.length > 1 && (value === null || typeof value !== "object")){
        options = jQuery.extend({}, options);
        if (value === null) {
            options.expires = -1;
        }
        if (typeof options.expires === 'number'){
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }
        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? String(value) : encodeURIComponent(String(value)),
            options.expires ? '; expires=' + options.expires.toUTCString() : '',
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};