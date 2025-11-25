goodList_before();
//下拉刷新效果
var page = 1;
if($('#goodList').find('.goodItem').size() >= 10){
	var miniRefresh = new MiniRefresh({
		container: '#page',
		// isLockX:false,
		down: {
			isLock:true
		},
		up: {
			isAuto: false,
			callback: function(){
				setTimeout(function(){
					
					page++;
					$('#hideLoad').load(loadMoreUrl+'&page='+page+' #goodList',function(res){
						$('#hideLoad').empty();
						$('#goodList').append($(res).find('#goodList').html());
						goodList_before();
						miniRefresh.endUpLoading($(res).find('#goodList').find('.goodItem').size() < 10 ? true : false);
					});
				},600);
			},
		}
	});
}


$('#page').scroll(function(){
	var scrollTop = $(this).scrollTop();
	if(scrollTop >= 200){
		$('#gotopBox').removeClass('hide');
	}else{
		$('#gotopBox').addClass('hide');
	}
});
$('#gotopBox').click(function(){
	$('#page').scrollTop(0);
});