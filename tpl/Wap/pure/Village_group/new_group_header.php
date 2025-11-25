<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>认领社区</title>
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
	
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
	
    <style>
        body{width: 100%;height:100%;background: #f8f8f8;overflow-x: hidden;}
       
        .not-leader .village-list .item{background: #fff;color: #333;font-size: 0.28rem;margin-bottom: 0.3rem;padding: 0.2rem;}
        .not-leader .village-list .item .name{background: #f4f4f4;display: inline-block;height: 0.68rem;line-height: 0.68rem;padding: 0 0.28rem;border-radius: 0.34rem;}
        .not-leader .village-list .item .name .icon{display: inline-block;width: 0.36rem;}
        .not-leader .village-list .item .name i{color: #06c1ae;font-size: 0.36rem;}
        .not-leader .village-list .item .content{padding: 0.2rem 0;}
        .not-leader .village-list .item .info .date{color: #999;}
        .not-leader .village-list .item .info .contact{float: right;color: #06c1ae;}


        .leader{padding-bottom: 1.8rem;color: #333;}
        .leader .time-box {background: #fff0d7;color: #ff972a;height: 0.6rem;line-height: 0.6rem;text-align: center;font-size: 0.26rem;}
        .leader .info-wrapper{background: #fff;margin-bottom: 0.3rem;padding: 0.2rem;}
        .leader .info-box{height: 1rem;position: relative;}
        .leader .info-box .avatar{width: 1rem;height: 1rem;border-radius: 50%;float: left;margin-right: 0.2rem;}
        .leader .info-box .name{font-size: 0.32rem;font-weight: 600;}
        .leader .info-box .more-btn{color: #999;margin-top: 0.1rem;}
        .leader .info-box .more-btn i{font-size: 0.24rem;margin-left: 0.04rem;}
        .leader .info-box .instruction {position: absolute;right: -0.2rem;top: 0.2rem;padding: 0.1rem 0.2rem;border-radius: 0.4rem 0 0 0.4rem;box-shadow:0 0 2px 2px #efefef;font-size: 13px;}
        .leader .info-box .instruction i{margin-right: 0.1rem;color: #398cea;font-size: 0.28rem;}
        .leader .info-wrapper .desc{display: none;}
        .leader .info-wrapper .desc div{padding-left: 1.2rem;line-height: 0.44rem;color: #666;}

        .leader .list-box {margin-bottom: 0.3rem;}
        .leader .list-box .item{padding: 0.2rem;background: #fff;height: 1.4rem;position: relative;}
        .leader .list-box .item.border-b:after{left: 0.2rem;}
        .leader .list-box .item.border-b:last-child:after{height: 0;}
        .leader .list-box .item img{width: 1.4rem;height: 100%;margin-right: 0.2rem;float: left;}
        .leader .list-box .item .name{font-size: 0.28rem;}
        .leader .list-box .item .price-box{position: absolute;bottom: 0.2rem;left: 1.8rem;color: #e73810;width: calc(100% - 2rem);}
        .leader .list-box .item .price-box text{vertical-align: sub}
        .leader .list-box .item .price-box .now-price{font-size: 0.4rem;}
        .leader .list-box .item .price-box .old-price{color: #999;text-decoration: line-through;margin-left: 0.04rem;}
        .leader .list-box .item .count-box{position: absolute;bottom: 0;right: 0;color: #333;height: 0.6rem;line-height: 0.6rem;}
        .leader .list-box .item .count-box i{font-size: 0.56rem;color: #40bc06;}
        .leader .list-box .item .count-box text{vertical-align: top;font-size: 0.32rem;}

        .leader .input-wrapper { height: 1rem;line-height: 1rem;background: #fff;padding: 0 0.2rem;font-size: 0.28rem;box-sizing: border-box;}
        .leader .input-wrapper text { width: 1.4rem;display: inline-block;}
        .leader .input-wrapper input { width: calc(100% - 1.6rem);color: #333; }
        .leader .button-wrapper{position: fixed;bottom: 0;left: 0;right: 0;padding: 0.2rem;background:#f8f8f8;text-align: center; }
        .leader .total-wrapper{height: 0.6rem;line-height: 0.6rem;font-size: 0.26rem;}
        .leader .total-wrapper>text:first-child{color: #e73810;}
        .leader .total-wrapper .price{color: #e73810;font-size: 0.4rem; }
        .leader button {background: #40bc06;color: #fff;width: 100%;height: 0.8rem;line-height: 0.8rem;border-radius: 0.08rem;font-size: 0.28rem;}

        .maskContent.explain{transform: translate(0,0);left: 10%;top: 28%;box-sizing: border-box;}
        .explain{padding: 0.24rem;display: none;}
        .explain .title{text-align: center;margin-bottom: 0.32rem;font-size: 0.3rem;}
        .explain p{text-indent:0.48rem;}
        .explain i{position: absolute;top: 0; right: 0.1rem;font-size: 0.56rem;color: #999;}

.swiper-container, .swiper-wrapper, .swiper-slide {
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
.swiper-slide {
    float: left;
}
 .banner img {
    height: 100%;
    width: 100%;
}
.swiper-pagination {
    position: absolute;
    z-index: 20;
    left: 0px;
    width: 100%;
    text-align: center;
    bottom: 4px;
}
.swiper-pagination-switch {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 8px;
    background: black;
    margin-right: 5px;
    opacity: 0.14;
    cursor: pointer;
    /*background: #E2DAD3;*/
}
.swiper-active-switch {
    background: #fff;
    opacity: 1;
}

    </style>
</head>
<body>

    <div class="page">
	
		 
		
		
        <!-- 非团长 -->
        <div class="not-leader">
		
			<if condition="$village_group_index">
			   <section id="listBanner" class="banner" style="height:154px">
                <div class="swiper-container swiper-container1" style="cursor: -webkit-grab;">
                    <div class="swiper-wrapper">

                        <volist name="village_group_index" id="vo">

                            <div class="swiper-slide swiper-slide-duplicate">
                                <a class="link-url" data-url="{pigcms{$vo.url}">
                                    <img src="{pigcms{$vo.pic}" style="width:100%;height: 100%;"/>
                                </a>
                            </div>

                        </volist>

                    </div>
                    <div class="swiper-pagination swiper-pagination1">

                    </div>
                </div>
            </section>
        </if>
            <ul class="village-list">
			<if condition="!empty($pick_address)">
			<volist name="pick_address" id="vo">
                <li class="item">
                    <div class="name">
                        <text class="icon"><i class="icon iconfont">&#xe748</i></text>
                        <text>{pigcms{$vo.name}（待领取）</text>
                    </div>
                    <div class="content">我们需要团长,地址：{pigcms{$vo.pick_addr}</div>
                    <div class="info">
                        <text >Goi</text>
                        <!text class="date">{pigcms{$vo.pick_addr}</text-->
                        <a class="contact" href="{pigcms{:U('apply_new_header',array('id'=>$vo['id']))}">联系我们</a>
                    </div>
                </li>
			</volist>
			
			<else />
				<li class="item">
                    <div class="name">
                        <text class="icon"><i class="icon iconfont">&#xe748</i></text>
                        <text>当前社区团长已满，尽情期待</text>
                    </div>
                   
                </li>
			</if>
			
               
            </ul>

        </div>

        <!-- 活动说明弹窗 -->
        <div class="mask"></div>
        <div class="maskContent explain">
            <div class="title">拼团规则</div>
			{pigcms{$config.village_group_rule|html_entity_decode}
            <i id="cha" class="icon iconfont">&#xe679</i>
        </div>
    </div>
    <script>
        $('.instruction').on('click', () => {
            $('.explain').show()
            $('.mask').show()
        })

        $('#cha, .mask').on('click', () => {
            $('.explain').hide()
            $('.mask').hide()
        })

        $('.info-wrapper .info-box .more-btn').on('click', () => {
            $('.info-wrapper .desc').slideToggle('normal')
            if($('.info-wrapper .info-box .more-btn').html().indexOf('收起团长信息') === 0){
                $('.info-wrapper .info-box .more-btn').html('展开团长信息<i class="icon iconfont">&#xe60a</i>')
            } else {
                $('.info-wrapper .info-box .more-btn').html('收起团长信息<i class="icon iconfont">&#xe609</i>')
            }

        })
		
		var mySwiper = $('.swiper-container1').swiper({
				pagination:'.swiper-pagination1',
				loop:true,
				grabCursor: true,
				paginationClickable: true,
				autoplay:3000,
				autoplayDisableOnInteraction:false,
				simulateTouch:false
			});


    </script>
</body>
</html>