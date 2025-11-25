<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="format-detection" content="telephone=no">
    <meta charset="utf-8">
    <title>个人中心</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet">
    <link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/mall.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/shop_manger_penci.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <script src="{pigcms{$static_path}mall/js/swiper.min.js" type="text/javascript" ></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t=12955" charset="utf-8"></script>
    	<script>var $home=false</script>
    <style>
        #pageLoadTip{display:none !important;}
    </style>
	<script>
	$(function () { 
	  var isPageHide = false; 
	  window.addEventListener('pageshow', function () { 
		if (isPageHide) { 
		  window.location.reload(); 
		} 
	  }); 
	  window.addEventListener('pagehide', function () { 
		isPageHide = true; 
	  }); 
	}) 
	</script>
    <script charset="utf-8">
    var currency_symbol = "{pigcms{$config.Currency_symbol}";
    var guess_you_like = "{pigcms{$config.site_url}/wap.php?c=Mall&a=guess_you_like";
    var score_name = "{pigcms{$config['score_name']}"; 
    </script>
    <script type="text/javascript" src="{pigcms{$static_path}js/mallbuycart.js?t=1"></script>
    <style>
.plus em, .piton span{
    cursor: pointer;
}

.motify {
    top: 40%;
    left: 50%;
    margin: 0 0 0 -100px;
    
}
.pack_name{
    float:left;
    padding: 10px 0;
}
.endcar dt{
    height:auto;
}
.act_one{
    margin-left:30px;
    padding:3px;
    border:1px solid red;
    border-radius:2px;
}
.act_two{
    margin-left:10px;
}
.love_list{
    padding: 0 5px 100px 5px;
}
.love_listLi{
    float: left;
    margin: 0 1.5%;
    margin-bottom: 20px;
}
.may_til{
    width: 70%;
    color: #FF0000;
    padding: 0 0 20px 0;
    font-size: 15px;
    text-align: center;
    margin: 0 auto;
    display: none;
} 
.love_list .love_listLi .tjt_border_l{
    padding-bottom: 10px!important;
}

.love_list .ui-nowrap-multi .hd{
     overflow: hidden;
     height: 42px;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.noDataCart{
    padding: 40px 0;
    text-align: center;
    line-height: 26px;
}
 
.noDataCart a{ display: inline-block; border: #051b28 1px solid; padding: 3px 20px; font-size: 16px; border-radius: 5px; }
.noDataCart p.p1{ color: #051b28; font-size: 15px; line-height: 30px;}
.noDataCart p.p2{ color: #999999; line-height: 30px; } 
.love_list .ui-img-vertical{
			padding-top:100%
		}

</style>
</head>
<body >
    <div class=" flex flex_justify pt20 pb20 <if condition='!$config["mobile_head_color"]'>bg_yellow</if>" style="<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>">
        <div class="flex w100">
            <div class="pd10" style="width: 50px;height: 50px;"><img class="w100 h100 br" src="<if condition="$now_user['avatar']">{pigcms{$now_user.avatar}<else/>{pigcms{$static_path}images/new_my/pic-default.png</if>" alt="{pigcms{$now_user.nickname}头像"></div>
            <div class="flex_one">
                <div class="font_size20">{pigcms{$now_user.nickname}</div>
                <!-- <div class="font_size12" style="background:rgba(255, 255, 255, 0.3);border-radius: 15px;padding: 3px 10px;"><a href="{pigcms{:U('My/levelUpdate')}">VIP{pigcms{$now_user.level}</a></div> -->
                <div class="font_size14 mg_b5" ><span class="mg_r10">{pigcms{$now_user.phone}</span></div>
                <div class="font_size14 color_red " style="display: none;">暂未绑定手机号<i class="icon iconfont" style="vertical-align: middle;">&#xe616;</i></div>
                <div class="font_size14 flex">
                    <gt name='config.sign_get_score' value='0'>
                        <span class="mg_r20">
                            <eq name='today_sing' value='1'>
                                <a  href="javascript:; " class="border_7 pl10 pr10 br10 color_f">已签到</a>
                            <else/>
                                <a href="{pigcms{:U('My/sign',array('from'=>'mall'))}" target="app_blank" class="border_7 pl10 pr10 br10 color_f">签到</a>
                            </eq>
                        </span>                        
                    </gt>
                    <gt name='now_user.level' value='0'>
                        <span class="bg_0 br10 pd0_5 " style="color:#d7ac79;line-height: 21px;height: 22px;display: inline-block;overflow: hidden;margin-right:20px">
                            <a href="{pigcms{:U('My/levelUpdate')}" style="color:#d7ac79;">
                                <img class="ver_center" src="{pigcms{:file_domain()}{pigcms{$now_user.icon}" style=" border-radius: 50%;width: 20px;  height: 20px" />
                                <span class="font_b">{pigcms{$now_user.lname}</span>
                                <i class="icon iconfont ver_center">&#xe616;</i>
                            </a>
                        </span>
                    </gt>
                </div>
            </div>
        </div>
    </div>

    <div class="mg15 bg_f" style="border-radius: 10px;overflow: hidden;">
        <ul class="ui-list ui-list-single ui-list-link ui-border-b" >
            <li class="ui-border-t ml20 mg_r10">
                <a class="ui-list-info cursor" href="{pigcms{$config.site_url}/packapp/plat/pages/my/my_order" target="app_blank">
                    <h4 class="ui-nowrap font_size15">我的订单<span class="pl10 color_9"></span></h4>
                </a>
            </li>
        </ul>
    </div>

    <div class="mg15 bg_f br10" style="overflow: hidden;">
        <eq name='now_user.level' value='0'>
            <ul class="ui-list ui-list-single ui-list-link pl15 pr15" style="background: #323232;color:#fdd951;box-sizing: border-box;">
                <div class="ui-list-info cursor font_b pd0">
                    <h4 class="ui-nowrap font_size12"><img class="mg_r5" style="vertical-align: text-bottom;" src="/tpl/Wap/pure/static/mall/images/vip.png">{pigcms{$config.vip_card_alias|default='平台会员卡'}<span class="pl10 color_9"></span></h4>
                    <a href="{pigcms{:U('My/levelUpdate')}" target="app_blank">
                        <div class="font_size12 color_9 flex" style="color: #caae73;">开通即享超多优惠<i class="icon iconfont font_size22 ml5" style="font-weight: 400;margin-top: 2px;">&#xe616;</i></div>
                    </a>
                </div>
            </ul>
        </eq>
        <div class="flex flex_justify align_center pt10 pb10 pl30 pr30">  	
			<if condition="$config['open_user_money'] || getFormatNumber($now_user['now_money'])">
				<div>
					<a href="{pigcms{:U('My/my_money')}" target="app_blank">
						<div class="font_size20">{pigcms{$now_user.now_money|floatval}</div>
						<div class="font_size12 color_6">余额</div>
					</a>
				</div>
			</if>
            <div>
                <a href="{pigcms{:U('My/integral')}" target="app_blank">
                    <div class="font_size20">{pigcms{$now_user.score_count|floatval}</div>
                    <div class="font_size12 color_6">{pigcms{$config.score_name}</div>
                </a>
            </div>
            <div>
                <a href="{pigcms{:U('Mall/coupon_list')}" target="app_blank">
                    <div class="font_size20">{pigcms{$coupon_number+$mer_number}</div>
                    <div class="font_size12 color_6">优惠券</div>
                </a>
            </div>
        </div>
    </div>

    <div class="mg15 bg_f ks-clear pd10_0" style="border-radius: 10px;overflow: hidden;margin-bottom: 60px !important;">
	    <!-- <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
                    <div class="pb10"><i class="icon iconfont font_size24 br" style="background:#ebf9f8;color:#fab650;padding: 8px;">&#xe60f;</i></div>
                    <div class="font_size12 color_6">优惠券</div>
                </div> -->
        <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:U('Mall/my_bargain_act')}" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_kanj.png"></div></div>
                <div class="font_size12 color_6">我的砍价</div>
            </a>
        </div>
        <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:U('Mall/my_group_act')}" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_pint.png"></div></div>
                <div class="font_size12 color_6">我的拼团</div>
            </a>
        </div>
        <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:U('My/my_spread')}" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_tuig.png"></div></div>
                <div class="font_size12 color_6">推广返现</div>
            </a>
        </div>
        <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:U('Mall/refund_order_list')}" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_tuik.png"></div></div>
                <div class="font_size12 color_6">退货/售后</div>
            </a>
        </div>
        <div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:D('House_village')->base_url}pages/my/address" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_diz.png"></div></div>
                <div class="font_size12 color_6">收货地址</div>
            </a>
        </div>

        <if condition="$kefu['url'] neq '' || $kefu['phone']" >
        <div class="left align_center pd10_0 phone_btn cursor" style="width: 25%;box-sizing: border-box;">
            <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_kef.png"></div></div>
            <div class="font_size12 color_6 ">客服</div>
        </div>
       </if>

		<div class="left align_center pd10_0" style="width: 25%;box-sizing: border-box;">
            <a href="{pigcms{:U('My/about')}" target="app_blank">
                <div class="pb10"><div class="br pd10_0 mg_auto" style="overflow: hidden;width: 40px;height: 20px;background:#ebf9f8;"><img class="h100" src="{pigcms{$static_path}mall/images/my_guany.png"></div></div>
                <div class="font_size12 color_6 ">关于我们</div>
            </a>
        </div>    
    </div>

        <!--猜你喜欢 -->
    <div class="demo-list"  style="padding-bottom: 120px;">
        <div class="may_til"><span>为你推荐</span></div>
       <div class="ui-panel ui-grid ui-grid-trisect pt0 pb0  mb_0" > 
          <ul  class="clr pb10 love_list">   
            <div class="clear"></div> 
          </ul> 
        </div>
       </div>
    <!--猜你喜欢end --> 
        


    <!-- 客服弹层 -->
    <div class="coupon_mask phone_mask" style="cursor: pointer;"></div>
    <div class="bg_d pt30 pl30 pr30 pb10 br5 phone_content" style="color: #51c332;">
        <ul>
            <volist name="kefu.phone" id="vo">
                <a href="tel:{pigcms{$vo}">
                    <li class="bg_f pt5 pb5 pl10 mb_20 flex br5">                
                        <span><i class="icon iconfont font_size28 mg_r5" >&#xe610;</i></span>
                        <span>{pigcms{$vo}</span>                
                    </li>
                </a>
            </volist>
            <if condition="$kefu['url'] neq ''">
                <a href="{pigcms{$kefu.url}" target="app_blank">
                    <li class="bg_f pt5 pb5 pl10 mb_20 flex br5" style="color: #51c332;">
                        <span><i class="icon iconfont font_size28 mg_r5" >&#xe60e;</i></span>
                        <span>在线客服</span>
                    </li>
                </a>
            <else/> 
                <!-- <li class="bg_f pt5 pb5 pl10 mb_20 flex br5" style="color: #000;">
                    <span><i class="icon iconfont font_size28 mg_r5" >&#xe60e;</i></span>
                    <span>暂未开通在线客服</span>
                </li> -->
            </if>
        </ul>
        <div class="font_size28 color_9 phone_cancel cursor">×</div>
    </div>

    <include file="Mall:footer"/>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
</body>
<script>
    var View={
        init:function(){
            this.initClick();
            this.showStar();
        },
        
        initClick:function(){
            var _self=this;

            $(document).on('click','.comment_header .left',function(){
                $('.comment_header .left').removeClass('color_red')
                
                $(this).addClass('color_red')
                
            })

            $(document).on('click','.phone_mask, .phone_cancel',function(){
                $('.phone_mask, .phone_content').hide()
            })
            $(document).on('click','.phone_btn',function(){
                $('.phone_mask, .phone_content').show()
            })

            $('.commentDetail .comment_item').each(function(){
                $(this).find('ul li').click(function(){
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
                })
            }) 

        },

        showStar: function() {
            $('.commentDetail .comment_item .star .p').width()

            $(".commentDetail").find(".comment_item").each(function(){
                var tip = $(this).find('.star p').attr('tip')
                if (tip) {
                    $(this).find('.star p').width( tip * 16)
                }
            })
        }
    }
    window.View=View;
    View.init()

    // 关闭图片大图浏览
    function close_swiper(){
        $('.albumContainer').remove();
    }
</script>
<!-- 微信分享 -->
<script type="text/javascript">
window.shareData = {
    "moduleName":"Home",
    "moduleID":"0",
    "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
    "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Home/index')}",
    "tTitle": "{pigcms{$config.site_name}",
    "tContent": "{pigcms{$config.seo_description}"
};
</script>
{pigcms{$shareScript}
</html>