var merimgSlider = new Swiper('#merimg',{
	onSlideChangeEnd: function(swiper){
		$('.merimg-pagination .now').html(swiper.activeIndex+1);
	}
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