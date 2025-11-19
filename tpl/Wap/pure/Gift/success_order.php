<!DOCTYPE html>
<html lang="en">
<head>
    <title>{pigcms{$config.gift_alias_name}订单成功</title>
<include file="Public:gift_header" />
    <style>
        .add_more{position:relative}
        .add_more:before {
            transform: rotate(135deg);
            position: absolute;
            width: 8px;
            height: 8px;
            top: 15px;
            right: 10px;
            content: '';
            border: 2px solid #ddd;
            border-right: 2px solid transparent;
            border-bottom: 2px solid transparent;
        }
        .adress {
            position: relative;
            padding-right: 15px;
            padding-left: 10px;
            flex: 1;
            text-align:right
        }


        .adress:after {
            border-left: .04rem solid #999;
            border-bottom: .04rem solid #999;
            position: absolute;
            right: .05rem;
            top: 50%;
            display: block;
            content: '';
            width: .6em;
            height: .6em;

            -webkit-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
            -moz-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
            -ms-transform: translateY(-50%) scaleY(0.7) rotateZ(-135deg);
        }
    </style>
    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&key={pigcms{$config.google_map_ak}"></script>
        <script type="text/javascript">var is_google_map = "{pigcms{$config.google_map_ak}"</script>
        <else />
        <script type="text/javascript" src="{pigcms{$_SERVER.REQUEST_SCHEME}://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    </if>
</head>
<body>
<section class="orderSucced">
    <div class="wrap">
        <span class="dib vm icon"><i class="fa fa-check-circle"></i></span>
        <div class="dib vm desc">
            <h2>恭喜您，兑换成功!</h2>
            <if condition="!$merchant_session">
            <p>您还可以查看<a href="{pigcms{:U('My/integral')}">兑换记录</a><!--a href="##">查看订单详情</a--></p>
            </if>
        </div>
    </div>
</section>
<section class="order">

    <div class="orderRow">
        <ul>

            <if condition="$now_gift_order['pick_address']">
            <li>
                <div class="wrap">
                    <p class="fr">门店自提</p>
                    <span>配送方式</span>
                </div>
            </li>
            <elseif condition="$now_gift_order['is_pick_in_store'] eq 2"/>
            <li>
                <div class="wrap">
                    <p class="fr">平台配送</p>
                    <span>配送方式</span>
                </div>
            </li>
            <else/>
            <li>
                <div class="wrap">
                    <p class="fr">快递（免邮）</p>
                    <span>配送方式</span>
                </div>
            </li>
            </if>
            <li>
                <div class="wrap">
                    <p class="fr">{pigcms{$now_gift_order.order_time|date='Y-m-d H:i:s',###}</p>
                    <span>成交时间</span>
                </div>
            </li>
            <if condition="$now_gift_order['pick_address']">
            <li>
                <div class="wrap" style="display:flex;    align-items: center;">
                    <span>自提地址</span>
                    <p class="fr more adress" data-url="{pigcms{$now_gift_order.order_id}-{pigcms{$now_gift_order.pick_address.long}-{pigcms{$now_gift_order.pick_address.lat}-{pigcms{$now_gift_order['pick_address']['area_info']['province']}{pigcms{$now_gift_order['pick_address']['area_info']['city']}{pigcms{$now_gift_order['pick_address']['area_info']['area']}-{pigcms{$now_gift_order['pick_address']['name']}">{pigcms{$now_gift_order['pick_address']['area_info']['province']}{pigcms{$now_gift_order['pick_address']['area_info']['city']}{pigcms{$now_gift_order['pick_address']['area_info']['area']}{pigcms{$now_gift_order['pick_address']['name']}</p>

                </div>
            </li>
            </if>
        </ul>
    </div>

</section>

<section class="ftHeight"></section>
<footer class="deatilFtBtn">
    <div class="number tc fl" id="share">
        <!--span class="dib tit"><i class="share fa fa-share-square-o"></i>分享赚取更多{pigcms{$config['score_name']}</span-->
    </div>
    <div class="buyNow fl">
        <if condition="$merchant_session">
			<a href="/packapp/merchant/personal_center.html">查看订单</a>
        <else/>
			<a href="{pigcms{:U('Gift/index')}">回到首页</a>
        </if>
    </div>
</footer>
<section class="fullBg"></section>
<!--section class="shareMod JSshareMod">
    <div class="listRect">
        <ul class="clearfix">
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s1.png"></i>
                    <h2>微信</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s2.png"></i>
                    <h2>新浪微博</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s3.png"></i>
                    <h2>腾讯微博</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s4.png"></i>
                    <h2>朋友圈</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s5.png"></i>
                    <h2>QQ好友</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s6.png"></i>
                    <h2>QQ空间</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s7.png"></i>
                    <h2>短信</h2>
                </a>
            </li>
            <li>
                <a href="##">
                    <i><img src="{pigcms{$static_path}gift/images/placeholder/s8.png"></i>
                    <h2>复制链接</h2>
                </a>
            </li>
        </ul>
    </div>
    <div class="btn">
        <a href="javascript:;" class="JSbtn">取消分享</a>
    </div>
</section-->
<script type="text/javascript" language="javascript">
    var currency_symbol = "{pigcms{$config.Currency_symbol}";
</script>
<include file="Public:gift_footer" />
<script>

$(function(){
    $("#share").tap(function(){
       $(".fullBg").show();
        $(".JSshareMod").animate({
          height:'auto'
        }, 500, 'ease');
    });

    $(".JSbtn,.fullBg").tap(function(){
        $(".fullBg").hide();
        $(".JSshareMod").animate({
            height:'0'
        }, 500, 'ease');
    });
});
$('.adress').click(function(){
    var url  = $(this).data('url');
    var base_url  ="{pigcms{:U('adress_map')}";
    base_url = base_url+'&param='+url;
    window.location.href=base_url;
})
</script>
</body>
</html>