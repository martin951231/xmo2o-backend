var myScroll,myScroll2=null,myScroll3=null,now_page = 0,hasMorePage = true,isLoading = true,choose_screen={},screen_attrs='',screen_cat_url='',is_ajax=true;
$(function(){
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
			now_page = 0;
			hasMorePage = true;
			upIcon.removeClass('noMore loading').show();
			pageLoadTip(0);
			pageGetList(false);
		}
	});
	myScroll.on("slideUp",function(){
		if(hasMorePage){
			$('.listBox dl').append('<dd class="loadMoreList">正在加载</dd>');
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

	
	$(document).on('click','.listBox li.more',function(){
		$(this).hide().siblings('li').show();
		$(this).prev().css({'border-bottom':'none'});
		// setTimeout(function(){
			myScroll.refresh();
		// },200);
	});

	// $('.listBox').css('min-height',$(window).height()-95);
	pageLoadTip(92);
	if(user_long == '0'){
		getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
	}else{
		pageGetList(user_long,user_lat);
	}
});
function pageGetList(type){
	if(type == true){
		//now_sort_id = 'juli';
		//$('.dropdown-toggle.sort span').html('离我最近');
		//$('.sort-wrapper>ul li:first').data('sort-id','juli').find('span').html('离我最近');
	}
    now_page = 0;
	getList(false);
}
function list_location(obj){
	close_dropdown();
	now_page = 0;
	if(obj.attr('data-category-id')){
		obj.addClass('red');
		$('.dropdown-toggle.category .nav-head-name').html(obj.find('span').data('name'));
		now_cat_url = obj.attr('data-category-id');
	}else if(obj.attr('data-area-id')){
		$('.dropdown-toggle.biz .nav-head-name').html(obj.find('span').data('name'));
		now_area_url = obj.attr('data-area-id');
	}else if(obj.attr('data-sort-id')){
		obj.addClass('active').siblings('li').removeClass('active');
		$('.dropdown-toggle.sort .nav-head-name').html(obj.find('span').data('name'));
		now_sort_id = obj.attr('data-sort-id');
	}
	$('.listBox dl').empty().hide();
	$('.listBox .no-deals').addClass('hide');

	$("#pullUp").removeClass('noMore loading').show();
	$('.listBox dl .noMore').remove();
	pageLoadTip(92);
	getList(false);
}

/*重置*/
function reset() {
    choose_screen = {};
    screen_attrs = '';
	$('.btn-tip').removeClass('active');
}
/*转换*/
function change_screen() {
    screen_attrs = '';
    var change = {};
	if (choose_screen) {
		for (var i in choose_screen) {
          if (choose_screen[i]) {
          	if (screen_attrs) {
                screen_attrs += ';' + i + ':';
			} else {
                screen_attrs += i + ':';
			}
			change[i] = false;
			for (var j in choose_screen[i]) {
          		if (choose_screen[i][j] || choose_screen[i][j] === 0) {
          			if (change[i]) {
                        screen_attrs += ',' + choose_screen[i][j];
					} else {
                        change[i] = true;
                        screen_attrs += choose_screen[i][j];
					}
				}
			}
		  }
		}
	}
	console.log('screen_attrs-----------', screen_attrs)
}


function getList(more){
    if (!is_ajax) return;
    is_ajax = false;
	isLoading = true;
	var go_url = location_url;

	console.log(go_url);
	now_page += 1;
	go_url += "&page="+now_page;
	$.post(go_url,function(result){
		result=$.parseJSON(result);
        console.log('接口返回值------------',typeof result);		
		if(result.count > 0){
			hasMorePage = now_page < result.totalPage ? true : false;
			$('.listBox').removeClass('storeListBox');
			laytpl($('#groupListBoxTpl').html()).render(result, function(html){
				if(more){
					if(hasMorePage){
						$("#pullUp").removeClass('noMore loading').show();
					}
					$('.loadMoreList').remove();
					$('.listBox dl').append(html);
				}else{
					$('.listBox dl').html(html).addClass('dealcard').show();
				}
			});
			// console.log(hasMorePage);
			if(!hasMorePage){
				$("#pullUp").addClass('noMore').hide();
				$('.listBox dl').append('<dd class="noMore">暂无更多商圈!</dd>');
			}
		}else{
			$("#pullUp").addClass('noMore').hide();
			$('.listBox dl').hide();
			$('.listBox .no-deals').removeClass('hide');
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
        is_ajax = true;
	});
}

var obj2String = function(_obj) {
    var t = typeof (_obj);
    if (t != 'object' || _obj === null) {
      // simple data type
      if (t == 'string') {
        _obj = '"' + _obj + '"';
      }
      return String(_obj);
    } else {
      if ( _obj instanceof Date) {
        return _obj.toLocaleString();
      }
      // recurse array or object
      var n, v, json = [], arr = (_obj && _obj.constructor == Array);
      for (n in _obj) {
        v = _obj[n];
        t = typeof (v);
        if (t == 'string') {
          v = '"' + v + '"';
        } else if (t == "object" && v !== null) {
          v = this.obj2String(v);
        }
        json.push(( arr ? '' : '"' + n + '":') + String(v));
      }
      return ( arr ? '[' : '{') + String(json) + ( arr ? ']' : '}');
    }
  };
  