<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>{pigcms{$config.meal_alias_name}-{pigcms{$foodshop['name']}评论列表</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css"/>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
<script type="text/javascript">
	var location_url = "{pigcms{:U('Foodshop/replyList', array('store_id' => $foodshop['store_id']))}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/replylist.js?210" charset="utf-8"></script>
<style>
    .img_box {
        width: 45px;
        height: 45px;
        border-radius: 28px;
        float: left;
        overflow: auto;
        margin: 10px 10px 1px 0;
    }
    .img_box img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }
    .albumContainer {position: fixed;width: 100%;height: 100%;left: 0;top: 0;background: #000;z-index: 1000;display: none;overflow: hidden;-webkit-transform-origin: 0px 0px;opacity: 1;-webkit-transform: scale(1,1);}
    .albumContainer .swiper-close {right: 0px;top: 0px;text-align:right;padding-right: 10px;line-height: 50px;position: absolute;z-index: 21;width: 50px; height: 50px;color: white;font-size: 20px; font-family: "Arial";}
    .albumContainer .swiper-pagination {position: absolute;z-index: 20;left: 0;top: 20px;text-align: center;width: 100%;height: 20px;}
    .albumContainer .swiper-slide img{ width: 100%; max-height: 85% }
    .albumContainer .swiper-wrapper{ padding-top: 50px; }
    .albumContainer .swiper-pagination-switch{ background: #fff; opacity: 1; }
    .albumContainer .swiper-active-switch {background: #06c1ae;}
    .albumContainer .swiper-wrapper{
        position: relative;
        width: 100%;
        height: 100%;
        z-index: 1;
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        -webkit-transition-property: -webkit-transform;
        -moz-transition-property: -moz-transform;
        -o-transition-property: -o-transform;
        -ms-transition-property: -ms-transform;
        transition-property: transform;
        -webkit-box-sizing: content-box;
        -moz-box-sizing: content-box;
        box-sizing: content-box;
    }
    .swiper-container,.swiper-wrapper,.swiper-slide{
        width: 100%;
        height: 100%;
    }
    .swiper-container {
        margin: 0 auto;
        position: relative;
        overflow: hidden;
        -webkit-backface-visibility: hidden;
        -moz-backface-visibility: hidden;
        -ms-backface-visibility: hidden;
        -o-backface-visibility: hidden;
        backface-visibility: hidden;
        z-index: 1;
    }
    .swiper-slide{
        float: left;
    }
    .swiper-pagination {
        position: absolute;
        z-index: 20;
        left: 0px;
        width: 100%;
        text-align: center;
        bottom:4px;
    }
    .swiper-pagination-switch {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 8px;
        background: black;
        margin-right:5px;
        opacity: 0.14;
        cursor: pointer;
    }
    .swiper-active-switch {
        background: #06c1ae;
        opacity: 1;
    }
</style>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
</head>
<body style="background-color: #fff">
	<section class="details_comment">
		<div class="details_comment_n">
			<div class="details_comment_top">
				<div class="atar_Show"><p></p></div>
				<span>{pigcms{$foodshop['score_mean']|floatval}</span>
			</div>
			<div class="details_comment_end">{pigcms{$foodshop['reply_count']}人评价</div>
		</div> 
	</section>
	<div id="container">
		<div id="scroller">
			<div id="pullDown">
				<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
			</div>
			<section class="details_evaluate m43 listBox">
				<ul style="margin-top:0"></ul>
			</section>
			<div id="pullUp">
				<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
			</div>
		</div>
	</div>
</body>
<script id="replyListBoxTpl" type="text/html">
{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
<li>
	<div class="details_evaluate_top clr">
         <span class="img_box">
                <img src="{{ d.list[i].avatar}}">
        </span>
		<div class="evaluate_left">
			<h3>{{ d.list[i].nickname }}</h3>
			<span>{{ d.list[i].add_time }}</span>
		</div>
		<div class="evaluate_right">
			<div class="atar_Show">
				<p tip="{{ d.list[i].score }}"></p>
			</div>
		</div>  
	</div>
	<div class="details_evaluate_end" data-hide="0">{{ d.list[i].comment }}</div>
	{{# if(d.list[i].merchant_reply_content){ }}
		<div style="font-size:12px;background:#F8F8F8;padding:10px;line-height:24px;color:#333333;text-align:justify;"><span style="color:#FF532A;">商家回复：</span><span>{{ d.list[i].merchant_reply_content }}</span></div>
	{{# } }}

	{{# if(d.list[i].pics&&d.list[i].pics.length>0){ }}

		<div class="pics view_album" data-pics="{{# for(var j = 0, len_pic = d.list[i].pics.length; j< len_pic; j++){ }}{{ d.list[i].pics[j].m_image }}{{# if(len_pic > j+1){ }},{{# } }}{{# } }}">
		{{# for(var j = 0, len_pic = d.list[i].pics.length; j< len_pic; j++){ }}
		<span class="pic-container imgbox" style="background:none;"><img style="width:68px;height:68px;" src="{{ d.list[i].pics[j].s_image }}"/></span>&nbsp;
		{{# } }}
		
	{{# } }}
	<a href="javascript:void(0)" class="more"></a>
</li>
{{# } }}
</script>
<script>
    //图片横向滑动width
    var dish_length=$('.dish_content>a').length;
    $('.dish_content').width(dish_length*110);
    function close_swiper(){
        $('.albumContainer').remove();
    }
    //奖票滑动
    var myswiper5 = new Swiper('.swiper-container1', {direction : 'horizontal',  freeMode : true, freeModeMomentumRatio : 0.5, slidesPerView : 'auto'});
    <if condition="count($wap_index_slider) gt 1">
        if($('.swiper-container2').size() > 0){
            var mySwiper2 = $('.swiper-container2').swiper({
                pagination:'.swiper-pagination2',
                loop:true,
                grabCursor: true,
                paginationClickable: true,
                simulateTouch:false
            });
        }
    </if>

    $(".m10_list").each(function(){
        var height = $(this).height();
        if (height > 221) {
            $(this).css({"height":"221px","overflow":"hidden"})
        } else {
            $(this).find(".more").hide();
        }
    });
    $(".m10_list .more").click(function(){
        $(this).hide();
        $(this).parents(".m10_list").css("height","auto");
    });
    $(".m10_list .more i").text(parseFloat($(".m10_list dd").length) - 2);

    // 清除边框
    $(".navBox_list dl").each(function(){
        $(this).find("dd.Menulink").last().css("border-bottom","none");
    });

    $("body").on("click",".pics",function(){
        var album_more = $(this).data('pics');
        if (album_more == '') return false;
        var album_array = album_more.split(',');
        if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
            wx.previewImage({
                current:album_array[0],
                urls:album_array
            });
        }else{
            var album_html = '<div class="albumContainer" style="display:block;">';
            album_html += '<div class="swiper-container">';
            album_html += '<div class="swiper-wrapper">';
            $.each(album_array,function(i,item){
                album_html += '<div class="swiper-slide">';
                album_html += '<img src="'+item+'"/>';
                album_html += '</div>';
            });
            album_html += '</div>';
            album_html += '<div class="swiper-pagination"></div><div class="swiper-close" onclick="close_swiper()">X</div>';
            album_html += '</div>';
            album_html += '</div>';
            $('body').append(album_html);
            mySwiper_big = $('.albumContainer .swiper-container').swiper({
                pagination:'.albumContainer .swiper-pagination',
                loop:true,
                grabCursor: true,
                paginationClickable: true
            });
        }
    });
</script>
</html>