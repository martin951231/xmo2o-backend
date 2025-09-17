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
    $('#container').css({top:'51px'});
    $('#scroller').css({'min-height':($(window).height()-51+1)+'px'});
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


function pageGetList(type){
    console.log('pageGetList');
    now_page = 0;
    now_sort_id = 'juli';
	getList(false);
}
function getList(more){
    if (!is_ajax) return;
    is_ajax = false;
	isLoading = true;
	var go_url = location_url;
	console.log('go_url',go_url);
	now_page += 1;
	go_url += "&page="+now_page;
	$.post(go_url,function(result){

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
				$('.listBox dl').append('<dd class="noMore">更多'+employee_alias+'等待您的收藏!</dd>');
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
  