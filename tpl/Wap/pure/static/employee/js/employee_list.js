var myScroll, myScroll1,myScroll2=null,myScroll3=null,now_page = 0,hasMorePage = true,isLoading = true,choose_screen={},screen_attrs='',screen_cat_url='',is_ajax=true;
$(function(){
    if($('.swiper-container2').size() > 0){
        var mySwiper2 = $('.swiper-container2').swiper({
            pagination:'.swiper-pagination2',
            loop:true,
            grabCursor: true,
            paginationClickable: true,
            simulateTouch:false
        });
    }
    console.log('slider_num', slider_num);
    if (slider_num > 0) {
        if (slider_num > 4) {
            if($(".slider li").length>0){
                $('#container').css({top:'322px'});
            }else{
                $('#container').css({top:'312px'});
            }


            $('#scroller').css({'min-height':($(window).height()-312+1)+'px'});
        } else {
            if($(".slider li").length>0){
                $('#container').css({top:'242px'});
            }else{
                $('#container').css({top:'232px'});
            }

            $('#scroller').css({'min-height':($(window).height()-232+1)+'px'});
        }
    } else {
        if($(".slider li").length>0){
            $('#container').css({top:'158px'});
        }else{
            $('#container').css({top:'148px'});
        }

        $('#scroller').css({'min-height':($(window).height()-148+1)+'px'});
    }
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


    $('.navi').click(function(){
        var _this = $(this);
        if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
            return false;
        }
        if(typeof wx !=='undefined'){
            var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
            $.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
                layer.close(layerLoadTip);
                if(result['status'] == 1){
                    wx.openLocation({
                        latitude: result['info']['lat'],
                        longitude: result['info']['lng'],
                        name: _this.data('name'), // 位置名
                        address: _this.data('address'), // 地址详情说明
                        scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                        infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                    });
                }else{
                    layer.open({
                        content: result.msg
                        ,btn: '好的'
                    });
                }
            });
        }else{
            var url = "http://api.map.baidu.com/direction?origin=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent(_this.data('address'))+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
            // console.log(url);
            location.href = url;
        }
        return false;
    });

});
function change(index, msg) {
    // $('.title_change ul>li').removeClass('active');
    // $(index).addClass('active');
	console.log('change_url', change_url);
	window.location.href = change_url;
}


function pageGetList(type){
    console.log('pageGetList');
    now_page = 0;
    now_sort_id = 'juli';
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
	console.log('now_sort_id',now_sort_id);
	console.log('go_url',go_url);
	now_page += 1;
	go_url += "&page="+now_page;
	$.post(go_url,function(result){
        console.log('接口返回值------------', result);
        if (result && result['top_category'] && result['top_category']['merchant_cat_url']) {
            var merchant_cat_url = result['top_category']['merchant_cat_url'];
            if (merchant_cat_url && change_url) {
                change_url += '&cat_url='+merchant_cat_url;
            }
        } else {
            change_url = store_url;
        }
        console.log('change_url-------', change_url);

        if (result.now_sort_array) {
        	$('.dropdown-toggle.caret.sort>.nav-head-name').text(result.now_sort_array.sort_value);
            var sort_id = result.now_sort_array.sort_id;
            $('.sort-wrapper>ul li').removeClass('active');
            $('li[data-sort-id='+sort_id+']').addClass('active');
		}

		if(result.count > 0){
			hasMorePage = now_page < result.totalPage ? true : false;
			laytpl($('#ListBoxTpl').html()).render(result, function(html){
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
				$('.listBox dl').append('<dd class="noMore">更多'+employee_alias+'正在入驻，敬请期待!</dd>');
			}
            navi();
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

function navi() {

    $('.navi').click(function(){
        var _this = $(this);
        if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
            return false;
        }
        if(typeof wx !=='undefined'){
            var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
            $.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
                layer.close(layerLoadTip);
                if(result['status'] == 1){
                    wx.openLocation({
                        latitude: result['info']['lat'],
                        longitude: result['info']['lng'],
                        name: _this.data('name'), // 位置名
                        address: _this.data('address'), // 地址详情说明
                        scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                        infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                    });
                }else{
                    layer.open({
                        content: result.msg
                        ,btn: '好的'
                    });
                }
            });
        }else{
            var url = "http://api.map.baidu.com/direction?origin=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent(_this.data('address'))+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
            // console.log(url);
            location.href = url;
        }
        return false;
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
  