<html style="font-size: 25px;"><head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{pigcms{$goods['goods_name']}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/community_base.css?v=02">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/goodsDetail.css?v=02">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/swiper.min.css?v=02">
    <script src="{pigcms{$static_path}integral_mall/js/rem.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}integral_mall/js/swiper.min.js"></script>
    <style>
        .swiper-pagination-bullet {
            width: 0.4rem;
            height: 0.4rem;
            display: inline-block;
            border-radius: 0.26rem;
            background: #ffe9db;
        }
        .swiper-pagination-bullet-active {
            opacity: 1;
            background: #fd8056;
        }
        .swiper-pagination{text-align:right}

        .goods_content{
            padding: 10px;
            margin-bottom: 10px;
            background: white;
        }
        .goods_content img {
            max-width: 100%;
            _width: 100%;
            display: inline-block;
            vertical-align: middle;
        }
        .Speci {
            background: #fff;
             position: fixed;
            left: 0px;
            z-index: 900;
            width: 100%;
        }
        .mb_0 {
            margin-bottom: 0px !important;
        }
        .ui-list {
            width: 100%;
        }
        .ui-list>li {
            position: relative;
            display: -webkit-box;
        }
        .ui-list-link>li:after {
            content: "";
            display: block;
            position: absolute;
            right: 12px;
            top: 50%;
            margin-top: -7px;
            width: 8px;
            height: 14px;
            background: url(http://i.gtimg.cn/vipstyle/qui/2.0.0/img/icon_arrowlink.png) no-repeat;
            -webkit-background-size: 100% auto;
            background-size: 100% auto;
        }
        .ks-clear:after, .clear:after {
            content: '\20';
            display: block;
            height: 0;
            clear: both;
        }
        .after:after {
            content: none !important;
        }
        .rel {
            position: relative;
        }
        .abs {
            position: absolute;
        }
        .cursor {
            cursor: pointer !important;
        }
        .h100 {
            height: 100%;
        }
        .w100 {
            width: 100%;
        }
        .coupon_mask {
            position: fixed;
            top: 0px;
            left: 0px;
            background: rgba(0,0,0,0.7);
            z-index: 888;
            width: 100%;
            height: 100%;
            display: none;
        }
        .ui-list>li:first-child .ui-border-t {
            background-image: none;
            border-top-color: #f2f2f2;
        }
        .ui-border-t:before {
            border-top: 1px solid #e9e9e9;
            content: '';
            display: block;
            width: 100%;
            position: absolute;
            left: 0;
            top: 0;
            -webkit-transform-origin: left top;
        }
        .Speci .title {
            position: relative;
        }
        .flex_baseline {
            align-items: flex-start !important;
        }
        .flex {
            display: flex !important;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            align-items: center;
        }
        .Speci .title h2 {
            color: #343434;
            font-size: 16px;
            line-height: 1.5;
        }
        .flex_one {
            flex: 1;
            -webkit-box-flex: 1;
            -webkit-flex: 1;
        }
        .ui-nowrap-multi {
            display: -webkit-box;
            overflow: hidden;
            text-overflow: ellipsis;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .Speci .del {
            background-size: 22px;
        }
        .Speci .title_Price {
            font-size: 12px;
            position: relative;
            line-height: 18px;
            margin-top: 12px;
            overflow: auto;
        }
        .clr {
            zoom: 1;
        }
        .Speci .title_Price .tuftof {
            color: #ff4d45;
            float: left;
        }
        .Speci .title_Price .tuftof i {
            font-size: 16px;
            font-weight: 500;
        }
        .goods_price i.unit {
            color: #999999;
            font-size: 12px !important;
            float: right;
            line-height: 20px;
        }
        .ml10 {
            margin-left: 10px !important;
        }
        .ml2 {
            margin-left: 2px !important;
        }
        .color_f {
            color: #fff !important;
        }
        .left {
            float: left;
        }
        .bg_fa {
            background: #FA9732 !important;
        }
        .inline {
            display: inline-block !important;
        }
        .mg1_3 {
            margin: 1px 3px;
        }
        .pd1_3 {
            padding: 1px 3px;
        }
        .fl {
            float: left;
            display: inline;
        }
        .Speci .title_Price .Surplus {
            color: #999999;
            margin-left: 2px;
            line-height: 18px;
        }
        .clear {
            clear: both;
        }
        .cations {
            padding: 10px 0px 10px 0;
            margin: 0 10px;
            border-bottom: #efefef 1px solid;
            line-height: 24px;
            overflow: auto;
        }
        .cations span.cas_left {
            text-align: center;
            color: #666666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .cursor {
            cursor: pointer !important;
        }
        .pl0 {
            padding-left: 0px !important;
        }
        .cations .p35 li {
            float: left;
            font-size: 14px;
            color: #000;
            line-height: 22px;
            margin-bottom: 10px;
            background: #F1F1F1;
            padding: 2px 15px;
            border-radius: 5px;
            margin-right: 10px;
        }
        .right {
            float: right;
        }
        .cations .wrap .plus a {
            width: 30px;
            float: left;
            height: 28px;
            border: #e5e5e5 1px solid;
            text-align: center;
            color: #333333;
            line-height: 28px;
            font-size: 20px;
            font-family: "Arial";
        }
        .cations .wrap .plus input {
            width: 45px;
            text-align: center;
            float: left;
            border: #e5e5e5 1px solid;
            border-left: none;
            border-right: none;
            height: 30px;
            font-size: 14px;
            border-radius: 0px;
        }
        .Speci .spec-btn {
            position: absolute;
            left: 0;
            bottom: 0;
        }
        .purchase_right100 {
            width: 100%;
        }
        .purchase_right {
            line-height: 50px;
        }
        .purchase_right a {
            color: #fff;
            font-size: 14px;
            text-align: center;
            display: block;
            width: 100%;
        }
        .purchase_right a.shops {
            background: #f23030;
        }
        .cations .p35 li.on {
            color: #fff;
            background: #f23030;
            border: none;
        }
    </style>

</head>
<body class="mui-ios mui-ios-11 mui-ios-11-0 index_msg">
<!-- begin:商品详细信息 -->

<!-- begin:轮播  -->
<if condition="!empty($goods['wap_pic_list'])">
    <div class="change_banner" style="height:13.76rem;">
        <div class="swiper-container swiper-container-horizontal">
            <div class="swiper-wrapper" style="transform: translate3d(-1620px, 0px, 0px); transition-duration: 0ms;">
                <volist name="goods['wap_pic_list']" id="item">
                    <div class="swiper-slide" data-swiper-slide-index="{pigcms{$key}" style="width: 375px; margin-right: 30px;">
                        <img src="{pigcms{$item['url']}" style="height:13.76rem;">
                    </div>
                </volist>
            </div>

            <div class="swiper-pagination swiper-pagination-clickable swiper-pagination-bullets">
                <span class="swiper-pagination-bullet"></span>
                <span class="swiper-pagination-bullet"></span>
                <span class="swiper-pagination-bullet"></span>
                <span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span>
                <span class="swiper-pagination-bullet"></span>
            </div>
        </div>
    </div>
</if>

<!-- end:轮播  -->
<div class="change_wrap">
    <!-- begin:商品信息 -->
    <div class="change_good_details">
        <dl>
            <dt>{pigcms{$goods['goods_name']}</dt>
            <dd>
                <strong class="fl" id="price">积分：{pigcms{$goods['payment_pure_integral']}</strong>
                <!--<i>市场价:3680.00</i>-->
                <if condition="$goods['exchanged_num'] gt 0 || $goods['sale_count'] gt 0">
                    <em class="fr">已兑换{pigcms{$goods['exchanged_num'] + $goods['sale_count']}件</em>
                </if>
            </dd>
            <div id="goods_spec">

            </div>

        </dl>
    </div>
    <!-- end:商品信息 -->

</div>
<!-- end:商品详细信息 -->
<div class="address_ps">
    <a href="javascript:void(0);" class="dh_buttom dhcp_bg redeem_now">立即兑换</a>
</div>

<!-- begin:商品详情页 -->

<div class="dh_goods_details">
		<span class="title_good">
			产品介绍
			<i></i>
		</span>
    <div class="picture_good">
        <script>
            $(function(){
                $('div.picture_good').find('img').each(function(){
                    var src = $(this).attr('src');
                    if(src.indexOf('http://') == -1 && src.indexOf('https://') == -1){
                        $(this).attr('src', 'http://statics.76sd.com/'+src);
                    }
                })
            })
        </script>
        <div class="goods_content">{pigcms{$goods.goods_content}</div>
    </div>
</div>
<!-- end:商品详情页 -->


<!-- begin：规格-->
<include file="speci"/>
<div class="coupon_mask cursor"></div>
<!-- end：规格-->




<!-- begin：回到顶部-->
<a href="javascript:;" id="qdd_backup" style="display: none;"></a>
<!-- end：回到顶部-->

<script>
    // 轮播
    var swiper = new Swiper('.swiper-container', {
        pagination: '.swiper-pagination',
        nextButton: '.swiper-button-next',
        prevButton: '.swiper-button-prev',
        paginationClickable: true,
        spaceBetween: 30,
        centeredSlides: true,
        autoplay: 2500,
        autoplayDisableOnInteraction: false,
        loop:true
    });
    var payment_pure_integral = 0;
    var sku = "{pigcms{$goods['sku']}";
    var is_spec = "{pigcms{$goods['is_spec']}";
    var exchange_limit_num = "{pigcms{$goods['exchange_limit_num']}";
    var spec_ids = '';
    var is_choose = false;
    var buy_num = "{pigcms{$buy_num}";

    $(function(){

        // 回到顶部
        $(window).scroll(function(){
            var $scrollTop = document.documentElement.scrollTop || window.pageYOffset || document.body.scrollTop;
            if($scrollTop >500){
                $("#qdd_backup").show();
            }else{
                $("#qdd_backup").hide();
            }
        })
        $("#qdd_backup").click(function(){
            $('body,html').animate({scrollTop:0},200);
            return false;
        });

        $(".redeem_now").click(function () {
            $(".coupon_mask").show();
            $(".Speci").animate({bottom:"0px"});
            $(".index_msg").css('overflow', 'hidden');
        });

        $(document).on("click",'.coupon_mask_close',function(){
            var b=$(".Speci").attr("data-bottom");
            $(".Speci").animate({bottom:b},'100','linear',function(){
                $(".index_msg").css('overflow', 'auto');
                $(".coupon_mask").hide();
            });
        });

        $(document).on("click",'.coupon_mask',function(e){
            var b=$(".Speci").attr("data-bottom");
            $(".Speci").animate({bottom:b},'100','linear',function(){
                $(".index_msg").css('overflow', 'auto');
                $(".coupon_mask").hide();
            });
        });

        $(document).on('click', '.spec li', function(){
            $(this).addClass("on").siblings().removeClass("on");
            //获取当前的操作
            show_select_txt();
        });
        var b=$(".Speci").attr("data-bottom");
        $(".Speci").animate({bottom:b});

        // 立即购买
        $(document).on('click', '.integral_mall_buy', function() {
            console.log('integral_mall_buy', spec_ids);
            if (is_spec && (!is_choose || !spec_ids)) {
                alert('请先选择对应规格属性');
                return false;
            }
            var param = '';
            console.log('is_spec', is_spec);
            if (is_spec) {
                param += '&spec_index='+spec_ids;
            }
            var num = $('.plus input').val();
            if (num<=0) {
                alert('请选择数量');
                return false;
            } else {
                param += '&num='+num;
            }
            var group_now = "{pigcms{:U('Integral_mall/confirm_order',array('goods_id'=>$goods['goods_id']))}" + param;
            console.log('group_now', group_now);
            window.location.href = group_now;
        });

        //加减号的操作
        $(document).on('click', '.plus a', function() {
            if (is_spec && (!is_choose || !spec_ids)) {
                alert('请先选择对应规格属性');
                return false;
            }
            var num = parseInt($('.plus input').val());
            if ($(this).attr('class') == 'jia') {
                num ++;
                //限购校验
                var maxNum = exchange_limit_num;
                if(typeof maxNum != 'undefined' && !isNaN(maxNum) && maxNum>0){
                    maxNum = parseInt(maxNum);
                    if(num > maxNum){
                        if (sku == 0) {
                            alert('库存不足');
                        } else {
                            alert('每个用户限兑' + maxNum + '份');
                        }
                        return false;
                    }
                }
                if (buy_num>0) {
                    var max_no_buy = exchange_limit_num - buy_num;
                    if(typeof max_no_buy != 'undefined' && !isNaN(max_no_buy) && max_no_buy>0){
                        max_no_buy = parseInt(max_no_buy);
                        if(num > max_no_buy){
                            if (sku == 0) {
                                alert('库存不足');
                            } else if (exchange_limit_num>0) {
                                alert('您已经兑换' + buy_num + '份,还能兑换' + max_no_buy + '份');
                            }
                            return false;
                        }
                    } else if (typeof max_no_buy != 'undefined' && !isNaN(max_no_buy) && max_no_buy<0 && exchange_limit_num>0){
                        alert('您已经兑换' + buy_num + '份,无法再兑换');
                    }
                }
                if (sku>0 && sku<num) {
                    alert('库存不足' + sku + '份');
                    return false;
                }
                if (sku==0) {
                    alert('库存不足' + sku + '份');
                    return false;
                }
            } else {
                num --;
            }
            if (num < 1) {
                num = 1;
            }
            var min_num=1;
            if (num >min_num) {
                $(".plus a.jian").css("color","#333333");
            }else if(num==min_num){
                $(".plus a.jian").css("color","#bfbfbf");
            }else{
                $(".plus a.jian").css("color","#bfbfbf");
                return false;
            }
            /*记录不同类型活动当前商品数量*/
            $('.plus input').val(num);
        });
    });

    function show_select_txt()
    {
        var choose = false;
        spec_ids = '';
        $('.cations ul').find('li.on').each(function(){
            if (!spec_ids) {
                spec_ids = $(this).data('id');
            } else {
                spec_ids += '_' + $(this).data('id');
            }
        });
        console.log('spec_ids', spec_ids);
        var index_msg = $('.payment_pure_integral_'+spec_ids).val();
        var payment_pure_integral_old = $('.payment_pure_integral_'+spec_ids).val();
        var sku_old = $('.sku_'+spec_ids).val();
        var sell_num_old = $('.sell_num_'+spec_ids).val();
        var exchange_limit_num_old = $('.exchange_limit_num_'+spec_ids).val();
        var buy_num_old = $('.buy_num_'+spec_ids).val();

        if (spec_ids) {
            payment_pure_integral = payment_pure_integral_old;
            if (!payment_pure_integral) {
                payment_pure_integral = 0;
            }
            $('#show_format_price').html(payment_pure_integral);
            choose = true;
        }
        if (sku_old) {
            sku = sku_old;
            if (sell_num_old > 0) {
                sku = sku - sell_num_old;
            }
            if (sku>0) {
                $('#show_stock_num').html('剩余' + sku + '份')
            } else if (sku==0) {
                $('#show_stock_num').html('库存不足')
            }else if (sku==-1) {
                $('#show_stock_num').html('库存充足')
            }
            choose = true;
        }
        exchange_limit_num = exchange_limit_num_old;
        buy_num = buy_num_old;
        if (choose && index_msg) {
            is_choose = true;
        } else {
            is_choose = false;
        }
        console.log('is_choose', is_choose);
    }

</script>
</body>
</html>