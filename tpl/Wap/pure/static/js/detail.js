var myScroll;
// var isApp = motify.checkApp();
var isApp = true;
$(function(){
    //var bignav  = document.getElementById("box_title");
      var scroll=true;
    var scrollList=[],len=$('#box_title .swiper-slide').length,max_w=0,x=0;
       if($('#box_title').length>0){
       	   var wh=$(window).width();
           $('.swiper-container .swiper-slide').width($('.swiper-container .swiper-slide').width());
           $('.swiper-container').swiper({
               freeMode:true,
               freeModeFluid:true,
               slidesPerView: 'auto',
               simulateTouch:false
           });
       	   for(var i=0;i<$('#box_title .swiper-slide').length;i++){
       	   	   var t=$('.group_content'+i).offset().top-20-i*10;
               max_w+=$(".swiper-slide").eq(i).width();
       	   	    if(i==len-1){
       	   	    	t+=$('.group_content'+i).height();
				}
               scrollList.push(t);
		   }
           if(max_w+20<=wh){
               $(".swiper-wrapper").addClass('flex');
           }
            x=$('#box_title').offset().top;
           $("#box_title .swiper-slide").on("click",function(){
               scroll=false
               $("#box_title").addClass('swiper_slide_fixed');
               $(".detail-box .swiper-slide").addClass("actived");
               var index=parseInt($(this).attr('data-index'));
               var x=scrollList[index]-56;
               if(index==len-1){
               	 x-=$('.group_content'+index).height()
			   }
               $(".detail-box .swiper-slide").removeClass("actived");
               $(this).addClass("actived");
               $('html,body').animate({
                   scrollTop: x
               }, 'fast');
           })
	   }
    window.onscroll=function(){
        //滚动的距离,距离顶部的距离
        var topScroll =document.body.scrollTop||document.documentElement.scrollTop;
        //获取到导航栏id
        //当滚动距离大于150px时执行 固定位置 距离
        var clientHeight = document.documentElement.clientHeight;
        var scrollHeight = document.body.scrollHeight;
        if(topScroll + clientHeight == scrollHeight){
            $('.positionDiv').fadeIn('slow');
        }else{
            $('.positionDiv').fadeOut('fast');
        }
        if($('#box_title').length>0){
            if(scroll){
                if(topScroll<x ){
                    $(".detail-box .swiper-slide").removeClass("actived");
                    $(".detail-box .swiper-slide").eq(0).addClass("actived");
                    $("#box_title").removeClass('swiper_slide_fixed');
                    $(".swiper-wrapper").css('border-top', 'none');
                }else if(topScroll>scrollList[len-1]-56){
                    $(".detail-box .swiper-slide").removeClass("actived");
                    $("#box_title").removeClass('swiper_slide_fixed');
                    $(".swiper-wrapper").css('border-top', 'none');
                } else{
                    $("#box_title").addClass('swiper_slide_fixed');
                    $(".swiper-wrapper").css('border-top', '1px solid rgb(225, 225, 225)');
                    if(len==1){

                    }else if(len==2){
                        if(topScroll>=scrollList[0]-56+$('.group_content0').height()){
                            $(".detail-box .swiper-slide").removeClass("actived");
                            $(".detail-box .swiper-slide").eq(1).addClass("actived")
                        }else{
                            $(".detail-box .swiper-slide").removeClass("actived");
                            $(".detail-box .swiper-slide").eq(0).addClass("actived")
                        }
                    }else{
                        if(topScroll<scrollList[1]-56){
                            $(".detail-box .swiper-slide").removeClass("actived");
                            $(".detail-box .swiper-slide").eq(0).addClass("actived")
                        }else if(topScroll>=scrollList[len-2]-56+$('.group_content'+(len-2)).height()){

                            $(".detail-box .swiper-slide").removeClass("actived");
                            $(".detail-box .swiper-slide").eq(len-1).addClass("actived")
                        }else{
                            for(var n=1;n<scrollList.length;n++){
                                if(topScroll>=scrollList[n]-56 && topScroll<scrollList[n+1]-56){
                                    $(".detail-box .swiper-slide").removeClass("actived");
                                    $(".detail-box .swiper-slide").eq(n).addClass("actived")
                                }
                            }
                        }
                    }

                }

            }else{
                setTimeout(function(){
                    scroll=true
                },200)

            }
		}


    }


	if($('.clock').html() != 'undefined'){
		var timeDDom = $('.time_d:eq(0)');
		var timeHDom = $('.time_h:eq(0)');
		var timeMDom = $('.time_m:eq(0)');
		var timeSDom = $('.time_s:eq(0)');
		var timer = setInterval(function(){
			var timeJ = parseInt(timeDDom.html());
			var timeH = parseInt(timeHDom.html());
			var timeM = parseInt(timeMDom.html());
			var timeS = parseInt(timeSDom.html());
			if(timeS == 0){
				if(timeM == 0){
					if(timeH == 0){
						if(timeJ == 0){
							clearInterval(timer);
							window.location.reload();
						}else{
							$('.time_d').html(format_time(timeJ-1));
						}
						$('.time_h').html('23');
					}else{
						$('.time_h').html(format_time(timeH-1));
					}
					$('.time_m').html('59');
				}else{
					$('.time_m').html(format_time(timeM-1));
				}
				$('.time_s').html('59');
			}else{
				$('.time_s').html(format_time(timeS-1));
			}
		},1000);
	}
	$('.content_video').css({'width':$(window).width()-20,'height':($(window).width()-20)*9/16});
    if(isApp){
	   /* $('#scroller').css({'min-height':($(window).height()+1)+'px'});
	    myScroll = new IScroll('#container', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: true,scrollX: false, scrollY:true,click:iScrollClick()});
		//部分安卓机无法长按图片的问题，上面加上 preventDefault:false
	    myScroll.on("scrollEnd",function(){
		    if(this.y < -250){
			    $('.positionDiv').fadeIn('slow');
		    }else{
			    $('.positionDiv').fadeOut('fast');
		    }
	    });*/
    }else{
        $('body').append('<style>::-webkit-scrollbar{width:0px;}</style>');
       // $('#container,#scroller').css({'position':'static'});
       /* $(window).scroll(function(){
            if (($(window).scrollTop()) >= $(window).height()){
                $('.positionDiv').fadeIn('slow');
            }else{
                $('.positionDiv').fadeOut('fast');
            }
        })*/
       $('#pullUp,#pullDown,.back').hide();
       $('.back').hide();
       $('body').css({'padding-bottom':'45px'});
		//$('#scroller').css({'padding-bottom':'20px'});
    }
    $(".btn_a").on("click",function(){
    	location.href=$(this).attr("data-url")
	})
	//判断商品图片加载完成
	var imgNum=$('.detail .content img').length;
	$('.detail .content img').load(function(){
		console.log($(this).attr('src'));
		if(!--imgNum && !isApp){
			//myScroll.refresh();
		}
	});
	if(!isApp){
		window.onload = function(){
			//myScroll.refresh();
		}
	}
	/*myScroll.on("scroll",function(){
		if(this.y >= 0){
			$('.imgBox img').height(200+this.y+'px');
		}else{
			$('.imgBox img').height('200px');
		}

		if(maxY >= 50){
			!upHasClass && upIcon.addClass("reverse_icon");
			return "";
		}else if(maxY < 50 && maxY >=0){
			upHasClass && upIcon.removeClass("reverse_icon");
			return "";
		}
	});*/
	if(user_long == '0'){
		getUserLocation();
	}
	/* $(window).resize(function(){
		window.location.reload();
	}); */

	$('.back').click(function(){
		window.history.go(-1);
	});

	$('.storeProList .more').click(function(){
		$(this).remove();
		$('.storeProList li').show();
        if(!isApp){
		  // myScroll.refresh();
        }
	});
	//评论
	if($('.introList.comment').size() > 0){
		var cOver = false;
		$.each($('.comment .text'),function(i,item){
			if($(item).height() > 63){
				$(item).closest('.textDiv').addClass('overflow');
				cOver = true;
			}
		});
        if(!isApp){
		    cOver && myScroll.refresh();
        }
		$('.comment .textDiv').click(function(){
			$(this).hasClass('overflow') && $(this).removeClass('overflow');
            if(!isApp){
			   // myScroll.refresh();
            }
		});
	}
	if(motify.checkWeixin()){
		$('.imgBox img').click(function(){
			var album_array = $(this).data('pics').split(',');
			wx.previewImage({
				current:album_array[0],
				urls:album_array
			});
		});
		$('.imgList img').click(function(){
			var album_array = $(this).closest('.imgList').data('pics').split(',');
			wx.previewImage({
				current:album_array[0],
				urls:album_array
			});
		});
	}else {
		/*预览图片*/
		$('.imgList').FlyZommImg({
            rollSpeed:200,//切换速度
            miscellaneous:false,//是否显示底部辅助按钮
            closeBtn:false,//是否打开右上角关闭按钮
            hideClass:'hide',//不需要显示预览的 class
            imgQuality:'thumb',//图片质量类型  thumb 缩略图  original 默认原图
            slitherCallback:function (direction,DOM) {//左滑动回调 两个参数 第一个动向 'left,firstClick,close' 第二个 当前操作DOM

            }
        });
	}
});
function format_time(time){
	return time < 10 ? '0'+time : time;
}