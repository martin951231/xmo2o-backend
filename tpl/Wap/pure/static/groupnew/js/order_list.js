goodList_before();

loadMoreUrl = $('.categoryBox .item.active').data('url');

//下拉刷新效果
var page = 1;
var miniRefresh = new MiniRefresh({
	container: '#order_list_page',
	// isLockX:false,
	down: {
		isLock:true
	},
	up: {
		isAuto: false,
		callback: function(){
			setTimeout(function(){
				
				page++;
				$('#hideLoad').load(loadMoreUrl+'&page='+page+' #orderList',function(res){
					$('#hideLoad').empty();
					$('#orderList').append($(res).find('#orderList').html());
					miniRefresh.endUpLoading($(res).find('#orderList').find('.item').size() < 10 ? true : false);
				});
				
			},600);
		},
	}
});

$('.categoryBox .item').click(function(){
	$(this).addClass('active').siblings().removeClass('active');
	loadMoreUrl = $(this).data('url');
	page = 1;
	var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
	
	var stateObj = { foo: loadMoreUrl };
	history.replaceState(stateObj, "", loadMoreUrl);
	
	$('#orderList').load(loadMoreUrl+'&page='+page+' #orderList',function(res,status,xhr){
		var order_size = $(res).find('#orderList').find('.item').size();
		if(order_size >= 10){
			miniRefresh.resetUpLoading();
		}
		console.log(order_size);
		miniRefresh.endUpLoading(order_size < 10 ? true : false);
		layer.close(layerLoadTip);
	});
});


$('#order_list_page').scroll(function(){
	var scrollTop = $(this).scrollTop();
	if(scrollTop >= 200){
		$('#gotopBox').removeClass('hide');
	}else{
		$('#gotopBox').addClass('hide');
	}
});
$('#gotopBox').click(function(){
	$('#order_list_page').scrollTop(0);
});

