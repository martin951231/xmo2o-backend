<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>签到</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/sign/iconfont/iconfont.css"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/sign/signIn.css?t=1"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}swiper/swiper.min.css"/>
	<style>
		.swiper-slide img{
			width: 100%;
		}
		.swiper-container-horizontal>.swiper-pagination {
			bottom: 5px!important;
		}
		.swiper-pagination-bullet {
			width: 5px;
			height: 5px;
			margin: 0 3px!important;
			background: #fff;
		}
	</style>
</head>
<body>
<div>
	<if condition='!empty($gift_adver_list)'>
		<section class="scroll" id="banner_hei" >
			<div class="swiper-container swiper-container-banner" style="height:100%;">
				<div class="swiper-wrapper">
					<volist name='gift_adver_list' id='slider'>
						<div class="swiper-slide" onclick="window.open('{pigcms{$slider.url}')">
							<img style="height:100%" src="{pigcms{$slider.pic}" >
						</div>
					</volist>
				</div>
				<div class="swiper-pagination swiper-pagination-banner"></div>
			</div>	
		</section>
	</if>
    <div class="header">
        <!-- <p>-连续签到{pigcms{$max_reward_info.day}天-</p> -->
<!--        <p>-->
<!--            <eq name="max_reward_info.type" value='1'>-->
<!--                领<span class="num">{pigcms{$max_reward_info.jifen}</span>{pigcms{$config.score_name}-->
<!--            <elseif condition="$max_reward_info['type'] eq 2"/>-->
<!--                领<span class="num">{pigcms{$max_reward_info.price}</span>{pigcms{$config['Currency_txt']}优惠券-->
<!--            <elseif condition="$max_reward_info['type'] eq 3"/>-->
<!--                领<span class="num">{pigcms{$max_reward_info.discount}</span>折优惠券        -->
<!--            </eq>-->
<!--        </p>-->
        <p style="font-size: 18px;padding: 0 5px">今日签到得
            <span class="num">{pigcms{$today.value}</span>
            {pigcms{$today.score_name}，连续签到{pigcms{$max_reward_info.day}天,
            <span class="num">{pigcms{$jifen_all}</span>
            {pigcms{$config.score_name}轻松拿！
        </p>
    </div>

    <div class="sign-box">
        <div class="day-list ks-clear">
            <volist name="list" id='vo' key='k'>
                <elt name='k' value='$sign_day'>
                    <div class="item active">
                        <div>{pigcms{$k}天</div>
                        <div>
                            <i class="icon iconfont hidden">&#xe60c;</i>
                            <i class="icon iconfont ">&#xe609;</i>
                        </div>
                    </div>
                <elseif condition="$vo['type'] eq 1"/>
                    <div class="item">
                        <div>{pigcms{$k}天</div>
                        <div><i class="icon iconfont">&#xe60c;</i></div>
                    </div>   
                <elseif condition="$vo['type'] eq 2"/>
                    <div class="item reward">
                        <div class="arc"></div>
                        <div class="num">{pigcms{$config['Currency_symbol']}<span>{pigcms{$vo.price|floatval}</span></div>
                    </div>
                <elseif condition="$vo['type'] eq 3"/>
                    <div class="item reward">
                        <div class="arc"></div>
                        <div class="num"><span>{pigcms{$vo.discount|floatval}折</span></div>
                    </div>
                </elt>
            </volist>
        </div>
        <eq name='is_sign' value='1'>
            <div class="btn-box active">
                <div><button>今日已签到</button></div>
            </div>
        <else/>
            <div class="btn-box">
                <div><button>签到</button></div>
            </div>
        </eq>
    </div>

    <!-- <div class="rule-box">
        <p class="title">规则说明</p>
        <p>这里是规则说明这里是规则说明......这里是规则说明这里是规则说明......这里是规则说明......这里是规则说明......这里是规则说明......这里是规则说明......</p>
    </div> -->

    <!--弹窗-->
    <div class="mask-wrapper hidden">
        <div class="mask"></div>
        <div class="mask-content">
            <div class="title">签到成功</div>

            <!--积分-->
            <div class="score hidden">恭喜您获得<span>8</span>个平台{pigcms{$config.score_name}</div>

            <!--优惠券-->
            <div class="coupon hidden">
                <p>恭喜您获得一张平台优惠券！</p>
                <div class="item" style="height: 70px;">
                    <div class="left">
                        <div class="content flex flex_justify">
                            <div class="discount">
                                <div><span class='discount_tag'></span></div>
                                <div class="price">满<span class='min_price_tag'></span>可用</div>
                            </div>
                            <div class="desc-box">
                                <div class="text"></div>
                                <div class="affetct"></div>
                            </div>
                        </div>
                    </div>
                    <div class="right" data-coupon_id="98"><span class="content disabled">已领取</span></div>
                </div>
            </div>

            <!--关闭图标-->
            <i class="icon iconfont"  style="cursor :pointer;">&#xe6b0;</i>
        </div>
    </div>
	
	<div style="text-align:center;margin:20px 0 10px;font-size:18px;">- 积分兑换 -</div>
	<iframe src="{pigcms{:U('Gift/index', ['from_sign' => '1'])}" name="gift_iframe" id="gift_iframe" style="width:100%;border:0;opacity:0;"></iframe>
</div>

<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}swiper/swiper.min.js"></script>
<script>
    var swiper = new Swiper('.swiper-container-banner', {
        loop:true,
        autoplay: 5000,//可选选项，自动滑动
        // 如果需要分页器
        pagination: '.swiper-pagination-banner'
    });
</script>
<script>
    $(function () {
        // 点击签到
        var clock=0;
        var sign_url="/wap.php?g=Wap&c=My&a=custom_sign_api";
        $(document).on('click', '.sign-box button', function () {
            if(clock==1){
                return false;
            }
            clock=1;
            if($(this).parents('.btn-box').hasClass('active')){
                layer.open({
                    content:'今日已签到',
                    btn:['我知道了'],
                    yes:function(index){
                        layer.close(index);
                    }
                });
                return false;
            }
            $.post(sign_url,function(res){
                var data=res.info;
                if(data.error_code==0){
                    $(this).parents('.btn-box').addClass('active');
                    $(this).html('今日已签到');                    
                    var sign_info=data.sign_info;
                    if(sign_info['type']==1){                        
                        $('.mask-wrapper').find('.score span').text(sign_info.reward_arr.jifen);
                        $('.mask-wrapper').find('.score').removeClass('hidden');
                    }else{
                        var pobj=$('.mask-wrapper').find('.coupon');
                        var coupon_info=sign_info.coupon_info;
                        if(coupon_info.is_discount==1){
                            pobj.find('.discount_tag').text(parseFloat(coupon_info.discount_value));
                            pobj.find('.discount_tag').after('<span>折</span>');
                        }else{
                            pobj.find('.discount_tag').text(parseFloat(coupon_info.discount));
                            pobj.find('.discount_tag').before('<b>{pigcms{$config['Currency_symbol']}</b>');
                        }
                        pobj.find('.min_price_tag').text(parseFloat(coupon_info.order_money));
                        pobj.find('.text').text(coupon_info.name);
                        pobj.find('.affetct').text('有效期'+coupon_info.day+'天');
                        pobj.removeClass('hidden');           
                    }
                    $('.mask-wrapper').show();
                }else{
                    var msg=data.msg;
                    layer.open({
                        content:msg,
                        btn:['我知道了'],
                        yes:function(index){
                            layer.close(index);
                            if(data.url){
                                window.location.replace(data.url);
                            }
                        }
                    });
                    clock=0;
                    return false;
                }
            });            
        });
        //关闭弹窗
        $('.mask-wrapper').on('click', '.icon', function () {
            $('.mask-wrapper').hide();
            window.location.reload();
        });

    })
	
	function setGiftIframeHeight(height){
		$('#gift_iframe').height(height + 30).css('opacity', '1');
	}
</script>
{pigcms{$hideScript}
</body>
</html>