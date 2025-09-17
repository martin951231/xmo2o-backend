var oCal;
var week = ["日","一","二","三","四","五","六"];
var index;
(function (doc, win) {
    index = layer.open({type: 2});
  var docEl = doc.documentElement,
    resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
    recalc = function () {
      var clientWidth = docEl.clientWidth;
      if (!clientWidth) return;
      docEl.style.fontSize = 20 * (clientWidth / 320) + 'px';
    };
	var banner_height	=	$(window).width()/320;
	banner_height	=	 Math.ceil(banner_height*119);
	
	$("#banner_hei").css('height',banner_height);
	
	if($('.swiper-container1').size() > 0){
		var mySwiper = $('.swiper-container1').swiper({
			pagination:'.swiper-pagination1',
			loop:true,
			grabCursor: true,
			paginationClickable: true,
			autoplay:3000,
			autoplayDisableOnInteraction:false,
			simulateTouch:false
		});
	}
	
	if (!doc.addEventListener) return;
	win.addEventListener(resizeEvt, recalc, false);
	doc.addEventListener('DOMContentLoaded', recalc, false);

  
	$('#choose_date').click(function(){
		$('#J_Calendar').show();
	});

	//日历 start

	YUI({
		modules: {
			'price-calendar': {
				fullpath: public_path+'/trip-calendar/price-calendar.js',
				type    : 'js',
				requires: ['price-calendar-css']
			},
			'price-calendar-css': {
				fullpath: public_path+'/trip-calendar/price-calendar.css',
				type    : 'css'
			}
		}
	}).use('price-calendar', function(Y) {
		
		/**
		 * 非弹出式日历实例
		 * 直接将日历插入到页面指定容器内
		 */
		 if(typeof($.cookie('foodChooseDate'))=='string' && $.cookie('foodChooseDate').length>1){
		 	var foodChooseDate = $.cookie('foodChooseDate');
		 	var date = new Date(foodChooseDate);

			$('.nextday').data('next_day',date_format(date.getTime() + 24*60*60*1000));
			$('.prevday').data('prev_day',date_format(date.getTime() - 24*60*60*1000));
             var now = new Date();
             var nowdate = now.getFullYear() + '-' + Appendzero(now.getMonth() + 1) + '-' + Appendzero(now.getDate());
             $.cookie('todayDate',nowdate);
			if(foodChooseDate<=nowdate){
                $.cookie('foodChooseDate',nowdate);
                foodChooseDate = nowdate;
            }
		 }else{
			var now = new Date();
			var foodChooseDate = now.getFullYear() + '-' + Appendzero(now.getMonth() + 1) + '-' + Appendzero(now.getDate());
             $.cookie('todayDate',foodChooseDate);
             $('.nextday').data('next_day',date_format(now.getTime() + 24*60*60*1000));
             $('.pervday').data('prev_day',date_format(now.getTime() - 24*60*60*1000));
		 }
		oCal = new Y.PriceCalendar({
			container   : '#J_Calendar' //非弹出式日历时指定的容器（必选）
			// ,selectedDate: new Date       //指定日历选择的日期
			,count		: 2
			,afterDays	: day
			,depDate	: foodChooseDate
			,endDate	: foodChooseDate
		});
		changeTime();
		
		$('.price-calendar-bounding-box table td').click(function(){
			if($(this).hasClass('disabled')){
				return false;
			}else{
				if(($('.dep-date').size() > 0) || ($('.dep-date').size() == 0 )){
                    $('.dep-date').find('.mark').empty();
                    $('.dep-date').removeClass('dep-date');
                    $('.end-date').find('.mark').empty();
                    $('.end-date').removeClass('end-date');
                    oCal.set('endDate','');

                    $('.selected-range').removeClass('selected-range');

					oCal.set('depDate',$(this).data('date'));
					$(this).addClass('dep-date').find('.mark').html('选择');
                    setTimeout(function(){
                        changeTime()
                    },300);
				}else if(oCal.get('depDate')){
					setTimeout(function(){
						changeTime()
					},300);
				}
			}
		});

        layer.close(index);
	});
	
	//日历结束
	//前一天后一天
	$('.prevday').click(function(){
		var nowdate = $.cookie('todayDate');
		if($('.prevday').data('prev_day')<nowdate){
			motify.log('前一天不可预订了');
			return false;
		}else{
            changeTime($(this).data('prev_day'));
		}
	});
	$('.nextday').click(function(){
		var date = new Date();
        var fdate = date_format(date.getTime() + day*24*60*60*1000);
        if($('.nextday').data('next_day')>=fdate){
            motify.log('后一天不可预订了');
            return false;
		}else{
            changeTime($(this).data('next_day'));
		}
	});

})(document, window);

function changeTime(ctime){
	var dep_time = $('.dep-date').data('date');
	if(typeof(ctime) !="undefined" && ctime.length>1){
		dep_time = ctime;
	}
	if( $('.dep-date').find('.mark').text() == '入住'){
        $('.dep-date').find('.mark').text('选择')
	}
	$('.startweek').html('周'+week[oCal._toDate(dep_time).getDay()]+'预定');
	var aDate_start  = dep_time.split("-")  
	var start_date_md = aDate_start[1]  +  '月'  +  aDate_start[2]+'日';
	var oDate1  =  new  Date(aDate_start[0]  +  '-'  +  aDate_start[1]  +  '-'  +  aDate_start[2])
	var flag = false;
    if($.cookie('foodChooseDate')!=dep_time){
        flag = true;
    }
	$.cookie('foodChooseTime',aDate_start[0]+aDate_start[1]+aDate_start[2])
	$.cookie('foodChooseDate',dep_time)

	$('.startdate').html(start_date_md)
	$('#J_Calendar').hide();
	if(flag==true){
        window.location.reload();
	}

}

//zero
function Appendzero(obj)  
{  
	if(obj<10) return "0" +""+ obj;  
	else return obj;  
}

//时间戳转时间
function date_format(datetime)
{
    var now = new Date(datetime);
    var   year=now.getFullYear();
    var   month=Appendzero(now.getMonth()+1);
    var   date=Appendzero(now.getDate())
    return   year+"-"+month+"-"+date;

}

