var order_id = 0;
var isSearch = false;
var hasMore = true;
var nowPage = 1;
$(document).ready(function(){
    async function getIndexData () {
        var indexData = await common.getIndexData();
        indexData && indexData.have_scenic_name && $('.public .content').html(indexData.have_scenic_name + '订单');
    }
	getIndexData()
	
	$('#order_list').css({height:$(window).height()- 104});
	$('#order_list ul').after('<div class="jroll-infinite-tip">正在加载中...</div>');
	common.scroll($('#order_list'),function(scrollIndex){
		showList(scrollIndex);
	});
	
	showList();

	$('#searchForm').submit(function(){
		$('#order_list ul').empty();
		if($('#find_value').val() == ''){
			isSearch = false;
		}else{
			isSearch = true;
		}
		hasMore = true;
		order_id = 0;
		nowPage = 1;
		showList();
		
		return false;
	});
	
	$(document).on('click', 'div.consum', function(){
		var type = $(this).data('type'), order_id = $(this).data('order_id');
		if (type == 1) {
			common.http('Storestaff&a=ticket_pass_array', {'order_id':order_id, 'noTip':true}, function(data){
				laytpl($('#passList').html()).render(data.ticket_list, function(html){
					$('.consum_tc').find('ul').html(html);
					if(data.flag!=1){
						$('.consum_tc').find('#allVerify').hide();
					}else{
						$('.consum_tc').find('#allVerify').show();
					}
					$('#allVerify').data('order_id', order_id);
				});
			});
			$(".consum_tc,.mask").fadeIn();
			myScroll.refresh();
		} else {
		    layer.open({
			    content: '是否验证?'
			    ,btn: ['是', '否']
			    ,yes: function(index){
			      	common.http('Storestaff&a=ajax_verify_ticket', {'order_id':order_id, 'noTip':true}, function(data){
						motify.log('验证消费成功！');
						setTimeout(location.reload(), 5000);
					});
					layer.close(index);
			    }
			    ,no:function(){
			    	motify.log('已取消验证');
			    }
		    });
		}	    
	});
	$(document).on('click', '.consum_tc_n span.a39', function(){
		var com_id = $(this).data('com_id'), obj = $(this), order_id = $(this).data('order_id');
		layer.open({
		    content: '是否验证?'
		    ,btn: ['是', '否']
		    ,yes: function(index){
		      	common.http('Storestaff&a=ajax_verify_ticket', {'order_id':order_id, 'com_id':com_id, 'noTip':true}, function(data){
					motify.log('验证消费成功！');
					obj.removeClass('a39').addClass('ecc').html('已消费').unbind('click');
					obj.parents('.consum_tc_n').find('#allVerify').hide();
				});
				layer.close(index);
		    }
		    ,no:function(){
		    	motify.log('已取消验证');
		    }
		});
	});
	$(document).on('click', '#allVerify', function(){
		var order_id = $(this).data('order_id');
		layer.open({
		    content: '是否验证?'
		    ,btn: ['是', '否']
		    ,yes: function(index){
		      	common.http('Storestaff&a=ajax_verify_ticket', {'order_id':order_id, 'noTip':true}, function(data){
					motify.log('验证消费成功！');
					setTimeout(location.reload(), 5000);
				});
				layer.close(index);
		    }
		    ,no:function(){
		    	motify.log('已取消全部验证');
		    }
		});
	});
	
	$(document).on('click', '.del, .mask', function(){
		$(".consum_tc,.mask").fadeOut();
	});
});

function showList(scrollIndex){
	if(hasMore == false){
		return false;
	}
	if(isSearch == false){
		common.http('Storestaff&a=ticket_order_list',{'order_id':order_id,noTip:true}, function(data){
			if(nowPage == 1){
				$('#order_list').css({height:$(window).height()- 104});
				common.scrollEnd(scrollIndex);
			}
			if(data.order_list.length > 0){
				order_id = data.order_list[data.order_list.length-1].order_id;
			}
			if(nowPage >= data.page){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('#order_list ul').append(html);
				common.scrollEnd(scrollIndex);
			});
			nowPage++;
		});
	}else{
		common.http('Storestaff&a=ticket_order_list',{'order_id':order_id,find_type:$('#find_type').val(),find_value:$('#find_value').val(),noTip:true}, function(data){
			if(data.order_list.length > 0){
				order_id = data.order_list[data.order_list.length-1].order_id;
			}
			if(nowPage >= data.page){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('#order_list ul').append(html);
				common.scrollEnd(scrollIndex);
			});
			nowPage++;
		});
	}
}