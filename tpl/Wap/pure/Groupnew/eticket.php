<?php if (!defined('PigCms_VERSION')) {
    exit('deny access!');
} ?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8" />
    <title>电子凭证</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name='apple-touch-fullscreen' content='yes' />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="address=no" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/weui.css" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/jquery-weui.min.css" />
    <script type="text/javascript">
        var main_color = "{pigcms{$config.mobile_head_color}"
    </script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/eticket.css?t={pigcms{:time()}" />
</head>

<body>

<scrollView class="page y two_category" id="two_category_page" style='background:{pigcms{$config.mobile_head_color}'>
    <div class="container">

        <div class="eticket-box">
            <div class="desc-box">
                <if condition="$now_group['title_pic']">
                    <div class="store-img"><img src="{pigcms{:thumb_img($now_group['title_pic'],72)}" /></div>
                </if>
                <div class="gray">{pigcms{$now_group.merchant_name}</div>

                <if condition="$now_order['is_group_combine']">
                    <div class="goods-title">{pigcms{$now_order['name']}</div>
                    <else />
                    <div class="goods-title">{pigcms{$now_group['group_name']} | {pigcms{$now_group['s_name']}</div>
                </if>
                <div class="gray">{pigcms{$now_group['intro']}</div>
                <span class="left-icon" style='background:{pigcms{$config.mobile_head_color}'></span>
                <span class="right-icon" style='background:{pigcms{$config.mobile_head_color}'></span>
            </div>
            <div class="qcode-box">
                <!-- Swiper -->
                <div class="swiper-container qcode-swiper">
                    <div class="swiper-wrapper">
                        <volist name="pass_array" id="item">
                            <div class="swiper-slide" data-group_pass="{pigcms{$item['group_pass']}">
                                <div class="qcode-detail ">
                                    <div class="qcode-img">
                                        <if condition="$item['give_status'] eq 0">
                                        <!-- 团购券转赠成功遮罩 -->
                                        <div class="qcode-img-mask">已转赠成功</div>
                                        </if>
                                    </div>
                                    <div class="other-box">
                                        <div class="number">{pigcms{$item['group_pass']}</div>
                                        <!--                                                <div class="desc">过期时间：2019-02-30 14:59</div>-->
                                        <div class="desc">核销状态：
                                            <span style="color:{pigcms{$config.mobile_head_color}">
													<if condition="$item['status'] eq 2">
														已退款
														<elseif condition="$item['status'] eq 1" />
														已核销
														<else />
														未核销
													</if>
												</span>
                                        </div>

                                        <if condition="$now_order['is_group_combine']">
                                            <div class="desc">已核销次数：{pigcms{$item['used_count']}</div>
                                            <div class="desc">未核销次数：{pigcms{$item['no_use_count']}</div>
                                            <else />
                                            <div class="desc">核销时间：<if condition="$item['verify_time']">{pigcms{:date('Y-m-d H:i:s',$item['verify_time'])}
                                                    <else />--
                                                </if>
                                            </div>
                                            <div class="desc">核销人员：<if condition="$item['staff_name']">{pigcms{$item['staff_name']}
                                                    <else />--
                                                </if>
                                            </div>
                                        </if>
                                    </div>
                                    <if condition="$item['status'] eq 3">
                                        <div class="qcode-mask">
                                            <img src="{pigcms{$static_path}groupnew/images/out-time.png" />
                                        </div>
                                        <elseif condition="$item['status'] eq 1" />
                                        <div class="qcode-mask">
                                            <img src="{pigcms{$static_path}groupnew/images/out-use.png" />
                                        </div>
                                    </if>
                                </div>
                            </div>
                        </volist>
                    </div>
                    <if condition="count($pass_array) gt 1">
                        <!-- Add Pagination -->
                        <div class="swiper-pagination"></div>
                        <!-- Add Arrows -->
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </if>
                </div>
                <!--div class="check-btn">自助核销</div-->
            </div>
            <if condition="$now_group && $now_group['cue_arr']">
                <div class="use-desc">
                    <div class="use-title">使用须知</div>
                    <ul>
                        <volist name="now_group['cue_arr']" id="item">
                            <li>
                                <b>{pigcms{$item['key']}：</b>{pigcms{$item['value']}
                            </li>
                        </volist>
                    </ul>
                </div>
            </if>

            <div class="storeList">
                <div class="title">适用店铺</div>
                <if condition="$now_group['store_list']">
                    <volist name="now_group['store_list']" id="vo" key="k">
                        <div class="item <if condition="$k gt 2">hide</if>">
                <div class="storeName"><span class="name">{pigcms{$vo.name}</span><if condition="strip_tags($vo['work_time']) neq '-'"><span class="clock">{pigcms{$vo.work_time}</span></if></div>
                <div class="storeAddress">{pigcms{$vo.adress}</div>
                <div class="phoneNavi">
                    <div class="phone iconfont fl" data-phone="{pigcms{$vo.phone}">&#xe615;</div>
                    <div class="navi iconfont fl" data-lng="{pigcms{$vo.long}" data-lat="{pigcms{$vo.lat}" data-name="{pigcms{$vo.name}" data-address="{pigcms{$vo.adress}">&#xe606;</div>
                </div>
            </div>
            </volist>
            <if condition="COUNT($now_group['store_list']) gt 2">
                <div class="more iconfont down">&#xe608;</div>
                <div class="more iconfont up hide">&#xe607;</div>
            </if>
            </if>
        </div>
    </div>

    </div>
</scrollView>
<div id="hideLoad" class="hide"></div>

<div class="coupon-gift" style='background:{pigcms{$config.mobile_head_color}'>
    <if condition="$now_group['is_give'] eq 1 && $give_submit_show eq 1">
        <button class="btn" style='color:{pigcms{$config.mobile_head_color}'>转赠</button>
    </if>

</div>

<!-- 微信分享提示 -->
<div class="weixin-share-tips-wrap">
    <div class="top">
        <img src="{pigcms{$static_path}groupnew/images/tipsBg.png" alt="" class="img">
        <span class="desc">点击右上角<br/>分享给团购券给您的好友~</span>
    </div>
    <div class="bottom">
    <img src="{pigcms{$static_path}groupnew/images/btnBg.png" alt="" class="img">
        <span class="desc">我知道了</span>
    </div>
</div>

<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script src="{pigcms{$static_public}js/jquery.qrcode.min.js"></script>


<!-- minirefresh -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css" />
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css" />
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

<!-- layer -->
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?t={pigcms{:time()}" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/jquery-weui.min.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
<!-- src="{pigcms{$static_path}groupnew/js/eticket.js?t={pigcms{:time()}" charset="utf-8" -->
<script type="text/javascript" >
$(function () {
    var group_pass = ''
    var sendFriendLinkInit = window.location.protocol + "//" + window.location.hostname + '/packapp/plat/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon'
    var sendFriendLink = sendFriendLinkInit
    window.shareData = {
        "moduleName": "Home",
        "moduleID": "0",
        "imgUrl": "<if condition="
        $config['wechat_share_img']
        ">{pigcms{$now_order.list_pic}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": sendFriendLink,
        "tTitle": "{pigcms{$now_order.user_name}送你一张团购商品券~",
        "tContent": "{pigcms{$now_order.s_name}"
    };
    // 获取参数
    function GetRequest() {
        var url = location.search;
        var theRequest = {};
        if (url.indexOf("?") != -1) {
            var str = url.substr(1);
            strs = str.split("&");
            for (var i = 0; i < strs.length; i++) {
                theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]);
            }
        }
        return theRequest;
    }

    // 设定swiper初始化时的索引 默认0
    var initialSlide = 0
    initialSlide = GetRequest()['key_id']?Number(GetRequest()['key_id']):0

    // 设置分享链接
    function setSendFriendLink() {
        group_pass = $('.swiper-slide-active').data().group_pass
        sendFriendLink = sendFriendLinkInit + '&group_pass=' + group_pass
        if(typeof wx_share_init == 'function'){
            wx_share_init();
        }
    }

    var swiper = new Swiper(".qcode-swiper", {
        pagination: ".qcode-swiper .swiper-pagination",
        paginationClickable: ".qcode-swiper .swiper-pagination",
        nextButton: ".qcode-swiper .swiper-button-next",
        prevButton: ".qcode-swiper .swiper-button-prev",
        initialSlide: initialSlide,
        onInit: function () {
            $(
                ".qcode-swiper .swiper-pagination-bullet-active, .qcode-swiper .swiper-pagination-bullet"
            ).css("background", main_color);
            setSendFriendLink()
        },
        onTransitionStart: function () {
            $(
                ".qcode-swiper .swiper-pagination-bullet-active,.qcode-swiper .swiper-pagination-bullet"
            ).css("background", main_color);
            setSendFriendLink()
        }
    });
});

</script>

<script type="text/javascript">
    setTimeout(function() {
        var qrcode_width = $(window).width() * 0.64 > 200 ? 200 : $(window).width() * 0.64;

        $('.qcode-img').qrcode({
            width: qrcode_width,
            height: qrcode_width,
            text: "{pigcms{$config.site_url}/wap.php?c=Storestaff&a=group_qrcode&order_id={pigcms{$now_order.real_orderid}&id={pigcms{$now_order.group_pass}&is_group_combine={pigcms{$now_order.is_group_combine}&owner={pigcms{$owner}"
        });
    }, 10);

    var get_route = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=Groupnew&a=get_route";
</script>
<if condition="$give_submit_show eq 1">
{pigcms{$shareScript}
</if>

<script type="text/javascript">
    $(".coupon-gift .btn").on("click", function() {
        $.confirm(
            "团购商品一次只可转赠一份可同时转赠给多位好友，但仅第一位领取者能成功领取",
            function() {
                var group_pass = $('.swiper-slide-active').data().group_pass
                //点击确认后的回调函数
                if (motify.checkWeixinApp()) {
                    // 小程序中
                    // 添加分享参数

                    var wxappUrl = "/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon&group_pass=" + group_pass
                    var url = "/pages/index/share?title=" +
                        encodeURIComponent(window.shareData.tTitle) +
                        "&shareTitle=" + encodeURIComponent('{pigcms{$now_order.user_name}送你一张团购商品券~') +
                        "&shareImg=" + encodeURIComponent(window.shareData.imgUrl) +
                        "&sharePath=" + encodeURIComponent(wxappUrl) +
                        "&poster=" + encodeURIComponent(window.shareData.imgUrl) +
                        "&bg=" + encodeURIComponent(window.shareData.imgUrl)

                    console.log('分享参数', wxappUrl, url)

                    wx.miniProgram.navigateTo({
                        url: url
                    })
                } else if (motify.checkApp()) {

                    var wxappUrl = window.location.protocol + '//' + window.location.host + "/packapp/plat/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon&group_pass=" + group_pass
                    let shareTtitle = window.shareData.tTitle
                    let shareImg = window.shareData.imgUrl
                    let shareDesc = window.shareData.tContent
                    if (motify.checkAndroid()) {
                    	window.lifepasslogin.shareWeChat(shareTtitle, shareDesc, shareImg, wxappUrl)
                    } else {
                    	var ios_shareWeChat = {
                    		name: 'shareWeChat',
                    		imgUrl: shareImg,
                    		sendFriendLink: wxappUrl,
                    		tTitle: shareTtitle,
                    		tContent: shareDesc
                    	}
                    	iosCommon.iosFunction('shareWeChat', ios_shareWeChat)
                    }
                } else if (motify.checkWeixin()) {
                    // 微信公众号环境
                    $('.weixin-share-tips-wrap').show();
                }
            },
            function() {
                //点击取消后的回调函数
            }
        );
    });

    $(".weixin-share-tips-wrap").on("click", function() {
        $('.weixin-share-tips-wrap').hide();
    })

    //店铺更多点击
    $(".storeList .more.down").click(function() {
        $(".storeList .item").show();
        // $(this).removeClass("hide");
        $(".storeList .more.down").addClass("hide");
        $(".storeList .more.up").removeClass("hide");
    });
    $(".storeList .more.up").click(function() {
        $(".storeList .item:gt(1)").hide();
        // $(this).removeClass("hide");
        $(".storeList .more.down").removeClass("hide");
        $(".storeList .more.up").addClass("hide");
    });
</script>


</body>

</html>
