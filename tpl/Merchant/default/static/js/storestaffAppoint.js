$(function(){
	//时间切换
	$(document).on('click', ".swiper-wrapper .swiper-slide", function(event){
		event.stopPropagation();
		$(this).addClass("on").siblings().removeClass("on")
		var index = $(this).index();
		$(".Switch ul").eq(index).show(200).siblings().hide(200);
	});  
 
	//选择时间点
	$(document).on('click', ".Switch ul li", function(event){
		event.stopPropagation();
		var End = $(this).hasClass("End")
		if (!End) {
			$(this).addClass("on").siblings().removeClass("on").parents("ul").siblings().find("li").removeClass("on");
		}
	});

	//关闭时间弹层
	$(document).on('click', ".time_content", function(){
		$('.time_content').hide()
	});

	//打开时间选择
	$("#Choice").click(function(){
		var height = $(document).height();
		getAppointTime();
	});

	// 确认选中时间
	$(".elastic .Resersub").click(function(){
		$('.time_content').hide()
		var m = $(".swiper-wrapper").find(".on .m").text();
		var d = $(".swiper-wrapper").find(".on .d").text();
		var e = $(".swiper-wrapper").find(".on em").text(), _date = $(".swiper-wrapper").find(".on input").val();
		var t = $(".Switch").find(".on").text();
		$("#Choice .Reser_right").find(".m").text(m);
		$("#Choice .Reser_right").find(".d").text(d);
		$("#Choice .Reser_right").find(".e").text(e);
		$("#Choice .Reser_right").find(".o").text(t);
		$('#book_time').val(_date + ' ' + t);
	});

	var tableAppointTpl = '	{{# for(var i in d.day_list){  }}\
								<div class="swiper-slide">\
								<em>{{ d.day_list[i].title}}</em>\
								<span>{{ d.day_list[i].day}}</span>\
								<input type="hidden" name="date" value="{{ d.day_list[i].date}}">\
								</div>\
							{{# } }}';

	var tableAppointTimeTpl = '	{{# for(var i in d.time_list){ }}\
								<ul class="clr" style="display: none;">\
								{{# for(var j in d.time_list[i].time_list){ }}\
									<li class="{{ d.time_list[i].time_list[j].class}}" style="cursor: pointer;">{{ d.time_list[i].time_list[j].time}}</li>\
								{{# } }}\
								</ul>\
							{{# } }}';
							
	function getAppointTime(){
		var table_type = $('select[name=table_type]').val();
		var table_id = $('select[name=table_id]').val();
		// 获取可预约时间段
		var book_time = $('input[name=book_time]').val();
		$.post(get_appoint_time_url,{book_time:book_time,table_type:table_type,table_id:table_id},function(data){
			if(!data.err_code){
				if (data.day_list.length) {
					var index = 0;
					var e = $("#Choice .Reser_right").find(".e").text();
					var m = $("#Choice .Reser_right").find(".m").text();
					var d = $("#Choice .Reser_right").find(".d").text();
					var o = $("#Choice .Reser_right").find(".o").text();

					laytpl(tableAppointTpl).render(data, function(html){
						$('.swiper-wrapper').html(html);
						$('.time_content').show()

						$('.swiper-wrapper .swiper-slide').each(function(i){
							var t_e = $(this).find('em').text();
							var t_m = $(this).find('.m').text();
							var t_d = $(this).find('.d').text();
							if (t_e == e && t_m == m && t_d == d ) {
								index = i;
								$(this).addClass('on');
							}
						})

						//滑动天数
						var swiper = new Swiper('.swiper-container', {
							pagination: '.swiper-pagination',
							nextButton: '.swiper-button-next',
							prevButton: '.swiper-button-prev',
							slidesPerView: 'auto'
						});
					});

					// 时间
					laytpl(tableAppointTimeTpl).render(data, function(html){
						$('.Switch').html(html);
						$('.Switch').find('ul').eq(index).show();
						$('.Switch').find('ul').eq(index).find('li').each(function(i){
							var t_o = $(this).text();
							if (t_o == o) {
								$(this).addClass('on');
							}
						})
					});

				}
			}else{
				if (data.err_code==2) {
					getNowAppointTime();
					getAppointTime();
				}else{
					layer.alert(data.msg);
				}
			}
		},'json')
	}
});


// 根据桌台获取当前桌台最近可预约时间
function getNowAppointTime(){
	var table_type = $('select[name=table_type]').val();
	var table_id = $('select[name=table_id]').val();
	$.post(ajax_get_now_appoint_time_url,{table_type:table_type,table_id:table_id},function(data){
		if(!data.err_code){
			$("#Choice .Reser_right").find(".m").text(data.m);
			$("#Choice .Reser_right").find(".d").text(data.d);
			$("#Choice .Reser_right").find(".e").text(data.w);
			$("#Choice .Reser_right").find(".o").text(data.o);
			$('#book_time').val(data.book_time);
		}else{
			layer.alert(data.msg);
			return false;
		}
	},'json')
}