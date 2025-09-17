var myScroll = null;
var isSearch = false;
var hasMore = true;
var nowPage = 1, status = urlParam.status, is_order = 0;
var contents = ['全部订单', '预订中', '就餐中', '已买单', '待确认菜品', '已取消'];
var table_order_id = 0;
// var commonScrollIndex = 0;
$(document).ready(function(){
	$('select[name=order_status]').val('88');
	//直接到达此页面
	if(common.checkWeixin() && urlParam.from && urlParam.from == 'message'){
		$('.link-url.return').click(function(){
			redirect_self('index.html');
			return false;
		});
	}
	
	$(".mask").height($(window).height());
	$('.public .content').html(contents[urlParam.status]);
	$('.entry ul').empty();
	table_order_id = urlParam.order_id;

	var currYear = new Date().getFullYear();
	var opt = {
	    'dateYMD': {
	        preset: 'date',
	        dateFormat: 'yy-mm-dd',
	        theme: 'android-ics light', //皮肤样式
	        display: 'bottom',           //显示方式
	        mode: 'scroller',           //日期选择模式
	        showNow: true,
	        nowText: L_("今天"),
	        onSelect: function (valueText, inst) {
	        	$('.entry ul').empty();
	            if($('#find_value').val() == ''){
	                isSearch = false;
	            } else {
	                isSearch = true;
	            }
	            hasMore = true;
	            nowPage = 1;
				$('.jroll-infinite-tip').removeClass('hideText');
				for (var i in common.onScrollArr) {
					common.onScrollArr[i].onScroll = false
				}
	            showList();
	        }
	    },'select': {
	        preset: 'select'
	    }
	} 
	$('#stime').scroller($.extend(opt['dateYMD'],opt['default']));
	$('#etime').scroller($.extend(opt['dateYMD'],opt['default']));

    $(document).on('change', 'select[name=st], select[name=order_status]', function(e){
        $('.entry ul').empty();
        if($('#find_value').val() == ''){
            isSearch = false;
        } else {
            isSearch = true;
        }
        hasMore = true;
        nowPage = 1;
		$('.jroll-infinite-tip').removeClass('hideText');
		for (var i in common.onScrollArr) {
			common.onScrollArr[i].onScroll = false
		}
        showList();
        return false;
    });

	showList();
	$('body,html').animate({scrollTop : 0}, 300);
	$('.entry').css({'overflow-y':'auto','-webkit-overflow-scrolling':'touch',height:$(window).height() - 172});
	$('.entry ul').after('<div class="jroll-infinite-tip">正在加载中...</div>');
	common.scroll($('.entry'),function(scrollIndex){
		console.log('scrollIndex', scrollIndex)
		showList(scrollIndex);
	});
	$('#searchForm').submit(function(){
		$('.entry ul').empty();
		if($('#find_value').val() == ''){
			isSearch = false;
		}else{
			isSearch = true;
		}
		hasMore = true;
		nowPage = 1;
		showList();
		return false;
	});

	$(document).on('click', '.entry .con_top', function() {
		var order_id = $(this).data('order_id');
		common.http('Storestaff&a=foodshop_detail_new',{'order_id':order_id, noTip:true}, function(data){
			if (data.temp_list == null) {
				$('.tol_price').html('<div class="fl">共' + data.total_num + '份</div><div class="fr price">总计：<span>￥' + data.total_price + '</span></div>');
			} else {
				$('.tol_price').html('<div class="fl">共' + data.total_num + '份</div><div class="fl price">总计：<span>￥' + data.total_price + '</span></div><a class="fr go" href="foodshop_menu.html?order_id=' + order_id + '&isShow=1">去确认</a>');
			}
			
			laytpl($('#orderDetailTpl').html()).render(data, function(html){
				$('.cease .surface ul').html(html);
			});
			 new JRoll(".surface");
		});
		$(".cease,.mask").fadeIn();
		$(".surface").height($(".cease").height() - 125);
	});
	
	
	// 接单、拒单完成订单
	$(document).on('click',".operate",function() {
		var order_id = $(this).data('order_id');
		$('.change_status .button .ensure').data('order_id', $(this).data('order_id'));
		$('.change_status .button .ensure').data('status',  $(this).data('status'));
		if ($(this).data('status')==1) {
			$('.change_status .message').html('<L_>是否确认接单？</L_>');
		}else if ($(this).data('status')==2) {
			$('.change_status .message').html('<L_>是否确认完成订单？</L_>');
		}else {
			$('.change_status .message').html('<L_>拒单后，用户支付金额将原路退回，无法恢复，是否确认拒单？</L_>');
		}
		$(".change_status,.mask").fadeIn();
	});

	//确认  接单、拒单完成订单
	$(document).on('click', '.change_status .button .ensure', function() {
		var order_id = $('.change_status .button .ensure').data('order_id');
		var status = $('.change_status .button .ensure').data('status');
		common.http('Storestaff&a=change_foodshop_status', {'order_id':order_id,'status':status}, function(data){
			$(".change_status,.mask").fadeOut();
        	$('.entry ul').empty();
            hasMore = true;
            nowPage = 1;
			$('.jroll-infinite-tip').removeClass('hideText');
			for (var i in common.onScrollArr) {
				common.onScrollArr[i].onScroll = false
			}
			showList();
		});
	});

	// 修改桌台
	$(document).on('click',".change_table",function() {
		var order_id = $(this).data('order_id');
		$('.change_table_div .button .ensure').data('order_id', $(this).data('order_id'));
		common.http('Storestaff&a=get_table_list', {'order_id':order_id,'noTip':true}, function(data){
			var html = '';
			if (data.length>0) {
				for (var i = 0; i < data.length; i++) {
					html += '<option value="'+data[i].id+'">'+data[i].name+'</option>';
				}
			}
			$('select[name=table_id]').html(html);
		});
		$(".change_table_div,.mask").fadeIn();
	});

	//确认  修改桌台
	$(document).on('click', '.change_table_div .button .ensure', function() {
		var order_id = $('.change_table_div .button .ensure').data('order_id');
		var table_id = $('select[name=table_id]').val();
		common.http('Storestaff&a=change_table', {'order_id':order_id,'table_id':table_id, 'noTip':true}, function(data){
			var table_text = data.table_type_name +' '+data.table_name +' （'+data.min_max +'）';
			$('#li_'+order_id).find('.table_txt').html(table_text);
			$(".change_table_div,.mask").fadeOut();
		});
	});

	//取消订单  
	$(document).on('click', '.pitch .cancel', function() {
		$('#order_id').val($(this).data('order_id'))
		$(".book,.mask").fadeIn();
	});
	$(".book .ensure").click(function() {
		var order_id = $('#order_id').val();
		common.http('Storestaff&a=cancel_book', {'order_id':order_id, 'noTip':true}, function(data){
			$('#li_' + order_id).fadeOut();
				motify.log(data);
		});
		$(".book,.mask").fadeOut();
	});
	
	$(".seek .del,.seek .close,.mask").click(function() {
		$(".book,.prints,.mask,.change_status,.change_table_div").fadeOut();
	});
	$(".cease .del,.mask").click(function() {
	    $(".cease,.mask").fadeOut();
	});

	//打印菜单
	$(document).on('click', '.click .print', function() {
		var order_id = $(this).data('order_id');
		$('.prints .button .ensure').data('order_id', $(this).data('order_id'));
		$('.prints .button .ensure').data('is_print_menu', 0);
		$(".prints,.mask").fadeIn();
	});
	
	$('.prints .button .ensure').click(function() {
		common.http('Storestaff&a=foodshop_print_order',{'order_id':$(this).data('order_id'),'is_foodshop_new':1, noTip:true}, function(data){
			motify.log(data);
			$(".prints,.mask").fadeOut();
		});
	}); 
    
    // 取消订单
    $(document).on('click', '.quxiao_btn', function() {  
        var order_id =$(this).data('order_id'); 
        common.http('Storestaff&a=cancel_foodshop_order',{order_id:order_id}, function(data){ 
        });
    })
    
});



function showList(scrollIndex){
	if(hasMore == false){
		return false;
	}
	var order_status = $('select[name=order_status]').val();
	var stime = $('#stime').val(), etime = $('#etime').val();
	if(isSearch == false){
		common.http('Storestaff&a=foodshop_order',{'page':nowPage, 'status':urlParam.status, noTip:true,order_id:table_order_id,order_status:order_status,'stime':stime, 'etime':etime,open_meal_electronic_menu:open_meal_electronic_menu}, function(data){
			if(nowPage >= data.totalPage){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
			if($('.mask').height() == 0 || $('.entry').height() <= 0){
				$(".mask").height($(window).height());
				$('.entry').css({"height":$(window).height()-172});
			}
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('.entry ul').append(html);
				$(".remark").each(function() {
					var h = $(this).find(".p30").height();
					if (h > 36) {
						$(this).find(".p30").addClass("has_on");
					}
				});
				common.scrollEnd(scrollIndex);
				if (data.open_foodshop_print_type=='1') {
					$('.print_menu').show();
				}
			});
			nowPage++;
		});
	}else{
		common.http('Storestaff&a=foodshop_order',{'page':nowPage, keyword:$('#find_value').val(), searchType:$('#searchType').val(),'status':urlParam.status,noTip:true,order_id:table_order_id,order_status:order_status,'stime':stime, 'etime':etime,open_meal_electronic_menu:open_meal_electronic_menu}, function(data){
			if(nowPage >= data.totalPage){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('.entry ul').append(html);
				$(".remark").each(function() {
					var h = $(this).find(".p30").height();
					if (h > 36) {
						$(this).find(".p30").addClass("has_on");
					}
				});
				common.scrollEnd(scrollIndex);
				if (data.open_foodshop_print_type) {
					$('.print_menu').show();
				}
			});
			nowPage++;
		});
	}
} 

 