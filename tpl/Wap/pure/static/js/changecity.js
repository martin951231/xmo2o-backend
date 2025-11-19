$(function(){
	$('#container,#scroller').css({'position':'static', 'top': '0px'});
	
	var cityKey = [];
	$.each($('.cityKey'),function(i,item){
		cityKey.push($(item).data('city_key'));
	});

	$("#selectCharBox").css({'float':'right',top:'50px',right:'0px',height:($(window).height()-40),width:50,'z-index':9998}).seleChar({
		chars:cityKey,
		callback:function(ret){
			$(window).scrollTop($('#city_'+ret).offset().top + 50);
		}
	});
	
	var windowWidth = $(window).width();
	$('.hotKeyUl li').css({width:(windowWidth*0.312)+'px'});

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
		$('.historyBox .clear').removeClass('none');
		window.addEventListener("pagehide", function(){
			$('#keyword').val('');
		},false);
	});

	$(document).on('click','.city_location,#historyCityList a,.hotKey a',function(){
		if(!$(this).hasClass('city_location_down')){
			var city_id = $(this).data('city_id');
			var city_name = $(this).data('city_name');
			var area_id = $(this).data('area_id');
			if(!area_id){
				area_id = 0;
			}
			var area_name = $(this).data('area_name');
			if(!area_name){
				area_name = '';
			}
			var cityHistory = $.cookie('cityHistory');
			if(cityHistory){
				// console.log(cityHistory);
				cityArr = cityHistory.split('~^%@$$@%^~');
				var newCityArr = [];
				for(var i in cityArr){
					var nowCityArr = cityArr[i].split('~~');
					if(nowCityArr[0] != city_id || nowCityArr[2]!=area_id) newCityArr.push(cityArr[i]);
				}
				newCityArr.unshift(city_id+'~~'+city_name+'~~'+area_id+'~~'+area_name);
				var newCityHistory = newCityArr.join('~^%@$$@%^~');
			}else{
				var newCityHistory = city_id+'~~'+city_name+'~~'+area_id+'~~'+area_name;
			}
			$.cookie('cityHistory',newCityHistory,{expires:730,path:'/'});
			$.cookie('now_city',city_id,{expires:730,path:'/'});
			if(area_id > 0){
				$.cookie('now_area',area_id,{expires:730,path:'/'});
			}else{
				$.cookie('now_area',area_id,{expires:-100,path:'/'});
			}
			
			if(get_wxapp == 1 && motify.checkWeixinApp()){
				var data = new Array();
				if (area_id==0) {
					area_id = city_id;
					area_name = city_name;
				}
				data = {page:'changecity',area_id:area_id,area_name:area_name,top_area_id:city_id,top_area_name:city_name,city_id:city_id,city_name:city_name};
				wx.miniProgram.postMessage({data:data});
				wx.miniProgram.navigateBack();
			}else{
				if(window.location.href.indexOf("Platform")!=-1){
	                window.history.back()
				}else if(window.location.href.indexOf("ActivityOffline")!=-1){
	                redirect('/wap.php?g=Wap&c=ActivityOffline&a=index');
				}else if(window.location.href.indexOf("resume_list")!=-1){
	                redirect('/wap.php?g=Wap&c=Recruit&a=resume_list');
				}else if(window.location.href.indexOf("Recruit")!=-1){
	                redirect('/wap.php?g=Wap&c=Recruit&a=index');
				}else{
	                redirect(indexUrl);
				}
			}
		}else{
			var city_id=$(this).data("city_id");
			var city_name=$(this).data("city_name");
			var that =$(this);
			if (typeof($('.child_'+city_id).html()) == 'undefined') {
				// 获取区县商城
				$.get(ajax_get_area_url,{area_id:city_id},function(data){
					if (data) {
						var childhtml = '<ul class="hotKey child_'+city_id+'" style="display: none;padding-bottom:8px">\
						<li><a href="javascript:void(0)" data-city_name="'+city_name+'" data-city_id="'+city_id+'">全城</a></li>';
						for (var i = 0; i < data.length; i++) {
							if (location_mode == '1'){
								childhtml += '<li><a href="javascript:void(0)" data-area_id="'+data[i].market_id+'" data-area_name="'+data[i].market_name+'" data-city_id="'+city_id+'" data-city_name="'+city_name+'">'+data[i].market_name+'</a></li>';
							}else{
								childhtml += '<li><a href="javascript:void(0)" data-area_id="'+data[i].area_id+'" data-area_name="'+data[i].area_name+'" data-city_id="'+city_id+'" data-city_name="'+city_name+'">'+data[i].area_name+'</a></li>';
							}
						}
						childhtml += '</ul>';
						that.parent('div').append(childhtml);
						
						$(".city_location").removeClass("rt");
						$(".hotKey").hide();
						that.addClass("rt");
						that.siblings('.hotKey').show();
					}
				},'json')
			}else{
				if(!that.hasClass('rt')){
					$(".city_location").removeClass("rt");
					$(".hotKey").hide();
					that.addClass("rt");
					that.siblings('.hotKey').show();
				}else{
					that.removeClass("rt");
					that.siblings('.hotKey').hide();
				}
			}
			
		}

	});

	var cityHistory = $.cookie('cityHistory');
	if(cityHistory){
		cityArr = cityHistory.split('~^%@$$@%^~');
		var html = '';
		for(var i in cityArr){
			var nowCityArr = cityArr[i].split('~~');
			if (nowCityArr[2]>0) {
				html+= '<li><a data-city_id="'+nowCityArr[0]+'" data-city_name="'+nowCityArr[1]+'"data-area_id="'+nowCityArr[2]+'"data-area_name="'+nowCityArr[3]+'">'+nowCityArr[3]+'</a></li>';
			}else{
				html+= '<li><a data-city_id="'+nowCityArr[0]+'" data-city_name="'+nowCityArr[1]+'"data-area_id="'+nowCityArr[2]+'"data-area_name="'+nowCityArr[3]+'">'+nowCityArr[1]+'</a></li>';
			}
		}
		$('#historyCityList').show().find('ul').prepend(html);
	}

	$('.historyBox .clear').click(function(){
		if(!$(this).hasClass('none')){
			$.cookie('searchHistory','',{expires:-1});
			$('.historyBox ul li:not(.clear)').remove();
			$(this).addClass('none').html('暂无记录');
		}
	});
	$("#nowCity .nowCity").click(function(){
		var down=$('#nowCity ul').attr("data-down")
		if(down==1){
			$("#nowCity .giveContent").addClass('giveContent_down')
			$('#nowCity ul').attr('data-down',2).slideUp()
		}else{
			$("#nowCity .giveContent").removeClass('giveContent_down')
			$('#nowCity ul').attr('data-down',1).slideDown()
		}
	})
});