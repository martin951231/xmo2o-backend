$(window).scroll(function() {
	var scrTop = $(window).scrollTop();
	var n_t = 120;
	// var top = $('#zhinengwulian').offset().top - 55
	console.log('scrTop', scrTop)
	if (scrTop > n_t) {
		$('#headbox').animate({
			backgroundColor: '#2681f3'
		}, 10)
	} else {
		$('#headbox').animate({
			backgroundColor: 'rgba(55, 110, 242,0)'
		}, 10)
	}
});
$(window).scrollTop($(window).scrollTop() - 1);

$("#index_1").click(function() {
	console.log("dasdsadsad")
	$(this).find(".a_style").addClass('banner_dongxiao_6')
	$(this).siblings().find(".a_style").removeClass('banner_dongxiao_6')
	$('.position_active_1').hide()
	$('.position_active_2').hide()
	$('.position_active_3').hide()

})
$('.position_active_1').hide()
$('.position_active_2').hide()
$('.position_active_3').hide()
let toggle = true
$("#functions").hover(function() {
	console.log("dasdsadsad")
	$('.position_active_1').toggle()
	$('.position_active_2').hide()
	$('.position_active_3').hide()
	$(this).find("img").attr("src", "/template/shequhome/images/top_icon.png");
	if (toggle) {
		$(this).children("img").attr("src", "/template/shequhome/images/top_icon.png");
		toggle = false;
	} else {
		$(this).children("img").attr("src", "/template/shequhome/images/bottom_icon.png");
		toggle = true;
	}
})
$('.position_active_1').hover(function(){
	$('.position_active_1').show()
},function(){
	$('.position_active_1').hide()
})
$('.position_active_2').hover(function(){
	$('.position_active_2').show()
},function(){
	$('.position_active_2').hide()
})
let toggle_2 = true
$("#solution").hover(function() {
	$('.position_active_1').hide()
	$('.position_active_2').hide()
	$('.position_active_3').toggle()
	if (toggle_2) {
		$(this).children("img").attr("src", "/template/shequhome/images/top_icon.png");
		toggle_2 = false;
	} else {
		$(this).children("img").attr("src", "/template/shequhome/images/bottom_icon.png");
		toggle_2 = true;
	}
})
let toggle_3 = true
$("#hardware").hover(function() {
	$('.position_active_1').hide()
	$('.position_active_2').toggle()
	$('.position_active_3').hide()
	if (toggle_3) {
		$(this).children("img").attr("src", "/template/shequhome/images/top_icon.png");
		toggle_3 = false;
	} else {
		$(this).children("img").attr("src", "/template/shequhome/images/bottom_icon.png");
		toggle_3 = true;
	}
})
$("#dongtai").click(function() {
	$(this).find(".a_style").addClass('banner_dongxiao_6')
	$(this).siblings().find(".a_style").removeClass('banner_dongxiao_6')
	$('.position_active_1').hide()
	$('.position_active_2').hide()
	$('.position_active_3').hide()
})
$("#lianxi").click(function() {
	$(this).find(".a_style").addClass('banner_dongxiao_6')
	$(this).siblings().find(".a_style").removeClass('banner_dongxiao_6')
	$('.position_active_1').hide()
	$('.position_active_2').hide()
	$('.position_active_3').hide()
})


$('.top_mini_box .wuyefuwu').hover(
	function() {
		$(this).find("img").attr("src", $(this).find("img").data('hover'));
	},
	function() {
		$(this).find("img").attr("src", $(this).find("img").data('default'));
	}
)

$('.right_box_1').hide()
$('.right_box_2').hide()
$('.right_box_3').hide()
$(".tab_1").click(function() {
	console.log("jhjhjjjjjjjjjjjjj")
	$(this).addClass("border_right_bg")
	$(this).find(".text_1").addClass("text_color")
	$(this).find(".yuandian").addClass('yuandian_1')
	$(this).siblings().removeClass("border_right_bg")
	$(this).siblings().find(".text_1").removeClass("text_color")
	$(this).siblings().find(".yuandian").removeClass('yuandian_1')
	$('.position_right_box').show()
	$('.right_box_1').hide()
	$('.right_box_2').hide()
	$('.right_box_3').hide()
})
$(".tab_2").click(function() {
	console.log("jhjhjjjjjjjjjjjjj")
	$(this).addClass("border_right_bg")
	$(this).find(".text_1").addClass("text_color")
	$(this).find(".yuandian").addClass('yuandian_1')
	$(this).siblings().removeClass("border_right_bg")
	$(this).siblings().find(".text_1").removeClass("text_color")
	$(this).siblings().find(".yuandian").removeClass('yuandian_1')
	$('.position_right_box').hide()
	$('.right_box_1').show()
	$('.right_box_2').hide()
	$('.right_box_3').hide()
})
$(".tab_3").click(function() {
	console.log("jhjhjjjjjjjjjjjjj")
	$(this).addClass("border_right_bg")
	$(this).find(".text_1").addClass("text_color")
	$(this).find(".yuandian").addClass('yuandian_1')
	$(this).siblings().removeClass("border_right_bg")
	$(this).siblings().find(".text_1").removeClass("text_color")
	$(this).siblings().find(".yuandian").removeClass('yuandian_1')
	$('.right_box_1').hide()
	$('.position_right_box').hide()
	$('.right_box_2').show()
	$('.right_box_3').hide()
})
$(".tab_4").click(function() {
	console.log("jhjhjjjjjjjjjjjjj")
	$(this).addClass("border_right_bg")
	$(this).find(".text_1").addClass("text_color")
	$(this).find(".yuandian").addClass('yuandian_1')
	$(this).siblings().removeClass("border_right_bg")
	$(this).siblings().find(".text_1").removeClass("text_color")
	$(this).siblings().find(".yuandian").removeClass('yuandian_1')
	$('.right_box_1').hide()
	$('.position_right_box').hide()
	$('.right_box_2').hide()
	$('.right_box_3').show()
})
