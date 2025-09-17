goodList_before();

var activeItem = $('.categoryBox .item.active');
var activeItemLeft = activeItem.offset().left;
if(activeItemLeft > $(window).width()/2){
	$('.categoryBox').scrollLeft(activeItemLeft - ($(window).width()-activeItem.width())/2);
}

$('.categoryBox .item').click(function(){
	$(this).addClass('active').siblings('.item').removeClass('active');
	var adverimg = $(this).data('adverimg');
	if(adverimg && adverimg != ''){
		$('.adverBox').css('background-image','url('+adverimg+')').removeClass('hide');
	}else{
		$('.adverBox').addClass('hide');
	}
	document.title = $(this).text();
	//模拟更新网址栏，防止用户刷新时，还是原页面
	var stateObj = { foo: $(this).data('url') };
	history.replaceState(stateObj, "", $(this).data('url'));
	
	var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
	$('.contentBox').load($(this).data('url') + ' .contentBox',function(res){
		goodList_before();
		layer.close(layerLoadTip);
	});
});

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