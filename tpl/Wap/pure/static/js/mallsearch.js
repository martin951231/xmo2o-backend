var now_page = 0, hasMorePage = true, isLoading = true, search_type = 0;
var bind_name = 'input';
if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
$(function(){
	var wh=$(window).height();
	$("#pageLoadTip").css("height",wh+'px')
	var store_id = parseInt($('#store_id').val());
	if (store_id > 0) {
		$(".se_input").width($(window).width() - 110);
	} else {
		$(".se_input").width($(window).width() - 140);
	}

	// 图片等比例
	$(".bd_a img").each(function(){
		$(this).height($(this).width()*0.66);
	});
	$(".bd_a a:nth-child(2n)").css("margin-right",0);

	$(".cond span.on").click(function(){
		if($(".cond_list").is(":hidden")){
			$(".cond_list").show();
		}else{
			$(".cond_list").hide();
		}
	});
	$(".searchBox li").click(function(){
		$(".searchBox li").removeClass('active');
		$(this).addClass('active');
		if ($(this).attr('class') == 'sp active') {
			search_type = 0;
		} else {
			search_type = 1;
		}

		if($('.se_input').val().length > 0){
			hasMorePage = true;
			now_page = 0;
			getList(0);
		}
	});
	$(window).scroll(function() {
		if ($(window).scrollTop() > $('.search_list').offset().top) {
			if(isLoading == false && hasMorePage == true && $(document).scrollTop() >= $(document).height() - $(window).height()){
				getList(true);
			}
		}
	});
	$('#search').click(function(){
		hasMorePage = true;
		now_page = 0;
		getList(0);
	});
	var validate=function(res,is_global){
		var result=res.replace(/(^\s+)|(\s+$)/g,"");
		if(is_global.toLowerCase()=="g") {
			if(result==''){
				return false
			}else{
				return true
			}
		}
	}
	$('.se_input').bind(bind_name ,function(event){
		var val=$(this).val();
		if(validate(val,'g')){
			if(event.keyCode == 13){
				hasMorePage = true;
				now_page = 0;
				addHistory( $('.se_input').val());
				getList(0);
			}

		}else{
			$('.bd_a').html('');
			$('#rec_search').show();
			$('#hot_search').show();
			$(".psnone").hide();
			$('.navBox_list').html('');
			if(event.keyCode == 13){
				hasMorePage = true;
				now_page = 0;
				alert('请输入搜索关键词');
				return false;
			}

		}


	})
	if($('.se_input').val().length > 0){
		hasMorePage = true;
		now_page = 0;
		getList(0);
	}
	$('#search').click(function(){
		addHistory( $('.se_input').val());
	});
});

function getList(more) {
	$('.se_input').val($.trim($('.se_input').val()));
	if($('.se_input').val().length == 0){
		alert('请输入搜索关键词');
		return false;
	}
	pageLoadTip(0);
	isLoading = true;
	now_page += 1;
	$.post(ajax_url, {'page':now_page, 'key':$('.se_input').val(), 'store_id':$('#store_id').val(), 'search_type':search_type}, function(result){
		if (search_type == 0) {
			if(result.total > 0){
				$('.psnone').hide();
				hasMorePage = now_page < result.total_page ? true : false;
				laytpl($('#goodsListBoxTpl').html()).render(result, function(html){
					if (more) {
						$('.bd_a').append(html);
					} else {
						$('.bd_a').html(html);
					}
				});
				// 图片等比例
				$(".bd_a img").each(function(){
					$(this).height($(this).width());
				});
				$(".bd_a a:nth-child(2n)").css("margin-right", 0);
				$('.navBox_list').html('');
			} else {
				$('.navBox_list').html('');
				$('.bd_a').html('');
				$('.psnone').show();
			}
		} else {
			if (result.total > 0) {
				$('.psnone').hide();
				hasMorePage = now_page < result.total_page ? true : false;
				laytpl($('#storeListBoxTpl').html()).render(result, function(html){
					if (more) {
						$('.navBox_list').append(html);
					} else {
						$('.navBox_list').html(html);
					}
					$('.bd_a').html('');
				});
			} else {
				$('.bd_a').html('');
				$('.navBox_list').html('');
				$('.psnone').show();
			}
		}
		$('#rec_search').hide();
		$('#hot_search').hide();
		if($(".searchBox").size()>0){
			$(".searchBox").show();
			$("#rec_search").removeClass("mgt_25")
		}
		pageLoadTipHide();
		isLoading = false;
	}, 'json');
}
//添加历史记录
function addHistory(str) {
	if(str=='')return false;
	if(str==' ')return false;
	var content='<div class="keyword hot_word">'+str+'</div>';
	$("#rec_content").append(content)
	var data = new Array();
	var cookie=$.cookie("search_history");
	if(cookie!=null){
		data = JSON.parse(cookie);
	}

	var index=-1;
	if(data){
		index=data.indexOf(str);
	}
	if(index>-1){
		data.splice(index,1);
	}

	//超过删除
	if(data && data.length==8){
		data.splice(0,1);
	}
	data.push(str);
	$.cookie('search_history', JSON.stringify(data), {expires : 365});
}