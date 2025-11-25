<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>拼团详情</title>
    
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
    <style>
        body{width: 100%;height:100%;background: #f8f8f8;overflow-x: hidden;color: #333;}

        .goods-box {padding:0.2rem;height: 2rem;background: #fff;}
        .goods-box img{width: 2rem;height: 100%;float: left;}
        .goods-box .desc-box{margin:0 -0.2rem 0 2rem;padding:0 0.2rem;height: 100%;box-sizing: border-box;position: relative;}
        .goods-box .price-box{position: absolute;bottom: 0;left: 0.2rem;}
        .goods-box .price-box .now-price{color: #f26113;}
        .goods-box .price-box .now-price .price{font-size: 0.4rem;}
        .goods-box .price-box .old-price{color: #888;}
        .ellipsis{overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;margin-bottom: 0.2rem;}

        .member-list {background: #fff;display: block;}
        .member-list li{background: #f3f0f4;margin: 0 0.2rem 0.2rem;border-radius: 0.04rem;padding: 0.2rem 0.3rem;}
        .member-list li>div{float: left;}
        .member-list li .num{line-height: 1.1rem;font-weight: bold;font-size: 0.32rem;margin-right: 0.3rem;}
        .member-list li img{width: 1.1rem;height: 1.1rem;border-radius: 50%;display: block;margin-right: 0.3rem;}
        .member-list li .info .name{font-size: 0.28rem;margin-bottom: 0.2rem;}
        .member-list .color{color: #ee0052;}


        .member-box .button-wrapper{position: fixed;bottom: 0;left: 0;right: 0;padding: 0.2rem;background:#fff;text-align: center; }
        .member-box button {background: #40bc06;color: #fff;width: 100%;height: 0.8rem;line-height: 0.8rem;border-radius: 0.08rem;font-size: 0.28rem;}
        .goods-list {margin: 0.2rem 0 1.2rem;}
        .goods-list .item{padding: 0.2rem;background: #fff;height: 1.2rem;position: relative;}
        .goods-list .item.border-b:after{left: 0.2rem;}
        .goods-list .item.border-b:last-child:after{height: 0;}
        .goods-list .item img{width: 1.4rem;height: 100%;margin-right: 0.2rem;float: left;}
        .goods-list .item .name{font-size: 0.28rem;}
        .goods-list .item .price-box{position: absolute;bottom: 0.2rem;left: 1.8rem;color: #e73810;width: calc(100% - 1.8rem);}
        .goods-list .item .price-box text{vertical-align: sub}
        .goods-list .item .price-box .now-price{font-size: 0.4rem;}
        .goods-list .item .price-box .old-price{color: #999;text-decoration: line-through;margin-left: 0.04rem;}
        .goods-list .item .count-box{position: absolute;bottom: 0;right: 0.2rem;color: #333;height: 0.6rem;line-height: 0.6rem;}
        .goods-list .item .count-box i{font-size: 0.56rem;color: #40bc06;}
        .goods-list .item .count-box text{vertical-align: top;font-size: 0.32rem;}

        .qcode{display: none;text-align: center;padding: 0.8rem 0 0.6rem;}
        .qcode img{width: 3.6rem;height: 3.6rem;padding-bottom: 0.2rem;}
        .qcode .num{font-size: 0.4rem;}
        .qcode i{position: absolute;top: 0; right: 0.1rem;font-size: 0.56rem;color: #999;}

    </style>
</head>
<body>
    <div class="page">
        <div class="goods-box">
            <img src="{pigcms{$order.group.share_image}" alt="">
            <div class="desc-box">
                <div class="ellipsis">{pigcms{$now_group.share_desc}</div>
                <div class="price-box">
                    <div class="now-price">{pigcms{$now_group.name}&nbsp;</text></div>
                    <!--div class="old-price">单买价：{pigcms{$config['Currency_symbol']}11.5</div-->
                </div>
            </div>
        </div>

        <!-- 团员：商品列表 -->
        <div class="member-box">
            <ul class="goods-list">
			<volist name="order_list" id="vo">
                <li class="item border-b">
                    <img src="{pigcms{$vo.user.avatar}" alt="">
                    <div>
                        <div class="name">{pigcms{$vo.user.nickname}</div>
                        <div class="price-box">
                            <text>{pigcms{$config['Currency_symbol']}</text>
                            <text class="now-price">{pigcms{$vo.total_money}</text>
                            <!--text class="old-price">{pigcms{$config['Currency_symbol']}50.00</text-->
                            <div class="count-box">
                                <text>{pigcms{$vo.status_txt}</text>
                            </div>
                        </div>

                    </div>
                </li>
			</volist>
                
            </ul>
           
        </div>

        <!-- 验证码弹窗 -->
        <div class="mask"></div>
        <div class="maskContent qcode">
            <img src="{pigcms{:U('orderBarcode',array('code'=>$order['code']))}" alt="">
            <div class="num">{pigcms{$order.code}</div>
            <i id="cha" class="icon iconfont">&#xe679</i>
        </div>


    </div>
    <script>

        $('.confirm-btn').on('click', () => {
            $('.qcode').show()
            $('.mask').show()
        })

        $('#cha, .mask').on('click', () => {
            $('.qcode').hide()
            $('.mask').hide()
        })
    </script>
</body>
</html>