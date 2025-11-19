var myScroll;
var isApp = true;
$(function(){
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
	$('.content_video').css({'width':$(window).width()-60,'height':($(window).width()-60)*9/16});
    if(!isApp){
	    $('#scroller').css({'min-height':($(window).height()+1)+'px'});
	    myScroll = new IScroll('#container', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: true,scrollX: false, scrollY:true,click:iScrollClick()});
	    myScroll.on("scrollEnd",function(){
		    if(this.y < -250){
			    $('.positionDiv').fadeIn('slow');
		    }else{
			    $('.positionDiv').fadeOut('fast');
		    }
	    });
    }else{
        $('body').append('<style>::-webkit-scrollbar{width:0px;}</style>');
        $('#container,#scroller').css({'position':'static'});
        $(window).scroll(function(){
            if (($(window).scrollTop()) >= $(window).height()){
                $('.positionDiv').fadeIn('slow');
            }else{
                $('.positionDiv').fadeOut('fast');
            }
        })
        $('#pullUp,#pullDown,.back').hide();
        $('body').css({'padding-bottom':'45px'});
    }
	//判断商品图片加载完成
	var imgNum=$('.detail .content img').length;
	$('.detail .content img').load(function(){
		console.log($(this).attr('src'));
		if(!--imgNum && !isApp){
			myScroll.refresh();
		}
	});
	if($(".imgBox .swiper-slide").length>0){
		$(".imgBox").css("background","#fff");
		$(".imgBox .swiper-slide").css("text-align","center");
		var max_height=$(".imgBox").height();
		var max_width=$(window).width();
		var max_init=max_width/max_height;
		for(var x=0;x<$(".imgBox .swiper-slide").length;x++){
			(function(x){
				var $img = document.getElementById($(".imgBox .swiper-slide").eq(x).find("img").attr("id"));
				var nImg = new Image();
				nImg.onload = function() {
					var nWidth = nImg.width,
						nHeight = nImg.height,
						nInit=nImg.width/nImg.height;
					console.log(nWidth,nHeight);
					if(nWidth>=nHeight){
						if(nWidth>=max_width){
							if(max_init>=nInit){
								$($img).css("width",(nInit*max_height)+'px')

							}else{

								$($img).css("height",(max_width*nHeight/nWidth)+'px')
							}

						}else{

							if(max_init>=nInit){
								$($img).css("width",(nInit*max_height)+'px')

							}else{

								$($img).css({
									"height":nHeight+"px",
									"width":nWidth+"px"
								})
							}

						}

					}else{
						if(nWidth>=max_width){
							$($img).css("width",(nInit*max_height)+'px')
						}else{
							if(nHeight<=max_height){
								$($img).css({
									"height":nHeight+"px",
									"width":nWidth+"px"
								})
							}else{
								$($img).css("width",(nInit*max_height)+'px')

							}

						}

					}

				}
				nImg.src = $img.src;


			})(x)


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
		    myScroll.refresh();
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
			    myScroll.refresh();
            }
		});
	}
	if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
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
	}
});
function format_time(time){
	return time < 10 ? '0'+time : time;
}