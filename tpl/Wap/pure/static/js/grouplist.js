var myScroll,myScroll2=null,myScroll3=null,now_page = 0,hasMorePage = true,isLoading = true,choose_screen={},screen_attrs='',screen_cat_url='',is_ajax=true;
$(function(){
	console.log(1)
	if($("#top_height").size() > 0){
		var top_height = $("#top_height").height();
	}else{
		var top_height = 98;
	}
	var top_str = top_height+'px';
	$('#container').css({top:top_str});
	
	$('#scroller').css({'min-height':($(window).height()-98+1)+'px'});
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
			pageLoadTip(92);
			if(now_sort_id == 'juli'){
				getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
			}else{
				pageGetList(false);
			}
		}
	});
	myScroll.on("slideUp",function(){
		console.log('sasdad');
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

	$('#search-form').submit(function(){
		var keyword = $.trim($('#keyword').val());
		$('#keyword').val(keyword);
		if(keyword.length == 0){
			layer.open({title:['错误提示：','background-color:#FF658E;color:#fff;'],content:'请输入搜索词！',btn: ['确定']});
			return false;
		}
		var searchHistory = $.cookie('searchHistory');
		if(searchHistory){
			searchArr = searchHistory.split('~^%@$$@%^~');
			var newSearchArr = [];
			for(var i in searchArr){
				if(searchArr[i] != keyword) newSearchArr.push(searchArr[i]);
			}
			newSearchArr.unshift(keyword);
			newSearchHistory = newSearchArr.join('~^%@$$@%^~');
		}else{
			newSearchHistory = keyword;
		}
		$.cookie('searchHistory',newSearchHistory,{expires:730,path:'/'});
		window.addEventListener("pagehide", function(){
			$('#keyword').val('');
		},false);
	});
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
function pageGetList(type,options,lng,lat){
	if (lng && lat) {
		user_long = lng;
		user_lat = lat;
	}
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
/*团购筛选*/
function list_screen(obj) {
    if(obj.attr('data-url-id') && obj.attr('data-url-id') == 'all'){
    	console.log('选择全部');
        close_dropdown();
        $('.listBox dl').empty().hide();
        $('.listBox .no-deals').addClass('hide');

        $("#pullUp").removeClass('noMore loading').show();
        $('.listBox dl .noMore').remove();
        pageLoadTip(92);
        screen_attrs = '';
        choose_screen = {};
        getList(false);
	} else if (obj.attr('data-url-id')) {
        var name = obj.attr('data-name');
        var url = obj.attr('data-url-id');
        var cat_key = obj.attr('data-cat-id') - 1;
        var cat = obj.attr('data-cat-id');
        var class_info = obj.attr('class');
        if (class_info == 'btn-tip') {
        	// 选中
            obj.addClass('active');
            if (!choose_screen[url]) {
                choose_screen[url] = {};
			}
            choose_screen[url][cat_key] = cat;
		} else {
        	// 取消
            obj.removeClass('active');
            if (choose_screen[url]) {
                delete choose_screen[url][cat_key];
			}
		}
        console.log('choose_screen----', choose_screen);
        change_screen();
	}
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

/*确认*/
function true_submit() {
    close_dropdown();
    $('.listBox dl').empty().hide();
    $('.listBox .no-deals').addClass('hide');

    $("#pullUp").removeClass('noMore loading').show();
    $('.listBox dl .noMore').remove();
    pageLoadTip(92);
    getList(false);
}

function getList(more){
    if (!is_ajax) return;
    is_ajax = false;
	isLoading = true;
	var go_url = location_url;
	//alert(go_url.store_list[0].store_name);
	if(now_cat_url != '-1'){
		go_url += "&cat_url="+now_cat_url;
	}
	if(now_area_url != '-1'){
		go_url += "&area_url="+now_area_url;
	}
	if(now_sort_id != 'defaults'){
		go_url += "&sort_id="+now_sort_id;
	}
	if(screen_attrs) {
        go_url += "&attrs="+screen_attrs;
	}
	if(typeof default_circle_id!='undefined' && default_circle_id>0){
		go_url+='&default_circle_id='+default_circle_id;
	}
	if(user_long) {
        go_url += "&user_long="+user_long;
	}
	if(user_lat) {
        go_url += "&user_lat="+user_lat;
	}
	console.log(now_sort_id)
	console.log(go_url)
	now_page += 1;
	go_url += "&page="+now_page;
	$.post(go_url,function(result){
        console.log('接口返回值------------', result);
        console.log('screen_cat_url------------', screen_cat_url);
        console.log('now_cat_url------------', now_cat_url);
		if (result.cat_field && screen_cat_url != now_cat_url) {
            screen_cat_url = now_cat_url;
			var cat_field = result.cat_field;
			var cat_field_val = false;
            console.log('返回值------', cat_field);
			var shtml = '<li class="clear-both-float cat-li" data-url-id="all" onclick="list_screen($(this));return false;">';
            shtml += '<div class="all-tip">全部</div>';
            shtml += '</li>';
            for (var i in cat_field) {
            	if (cat_field[i] && cat_field[i]['name']) {
                    cat_field_val = true;
                    var name = cat_field[i]['name'];
                    var url = cat_field[i]['url'];
                    shtml += '<li class="clear-both-float cat-li"><span>'+name+'</span></li>';
                    shtml += '<div class="tip-content">';
                    for (var j in cat_field[i]['value']) {
                    	if (cat_field[i]['value'][j]) {
                            shtml += '<div class="btn-tip"  data-name="'+name+'" data-url-id="'+url+'" data-cat-id="'+j+'" onclick="list_screen($(this));return false;">'+cat_field[i]['value'][j]+'</div>';
						}
					}
                    shtml += '</div>';
				}
			}
			if (!cat_field_val) {
                shtml += '<li class="no-data" style="display: none;">noData</li>';
			}
            shtml += '<li class="clear-both-float cat-li" style="position: absolute;bottom: 0;width: 100%;background-color: white;">';
            shtml += '<div class="btn-chongzhi" onclick="reset();return false;">重置</div>';
            shtml += '<div class="btn-finish" onclick="true_submit();return false;">完成</div>';
            shtml += '</li>';
			$('.dropdown-list.cat-ul').html(shtml);
		} else {
			if (now_cat_url == -1) {
                shtml = '<li class="no-data" style="display: none;">noData</li>';
                shtml += '<li style="text-align: center;padding-top: 25%;"><span>暂无筛选项，请选择分类</span></li>';
                $('.dropdown-list.cat-ul').html(shtml);
			}
		}
		console.log(shtml);
		if(result.store_count > 0 || result.group_count > 0){
			hasMorePage = now_page < result.totalPage ? true : false;
			if(result.style == 'store'){
				$('.listBox').addClass('storeListBox');
				laytpl($('#storeListBoxTpl').html()).render(result, function(html){
					if(more){
						if(hasMorePage){
							$("#pullUp").removeClass('noMore loading').show();
						}
						$('.loadMoreList').remove();
						$('.listBox dl').append(html);
					}else{
						$('.listBox dl').html(html).removeClass('dealcard').show();
					}
				});
			}else{
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
			}
			// console.log(hasMorePage);
			if(!hasMorePage){
				$("#pullUp").addClass('noMore').hide();
				$('.listBox dl').append('<dd class="noMore">更多商户正在入驻，敬请期待!</dd>');
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
  