<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8" />
    <title>商品详情</title>
    <link href="{pigcms{$static_path}mall/css/video-js.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet" />
    <link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css?2" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/shop_manger_penci.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet" />
    <script src="{pigcms{$static_path}mall/js/jquery.min.js" type="text/javascript" ></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script>
        var goods_list = '{pigcms{$goods_detail}';
		var price_type = '{pigcms{$now_goods.price_type}';

        var reply_url = "{pigcms{:U('Mall/reply', array('goods_id' => $now_goods['goods_id']))}";
        var save_url = "{pigcms{:U('Mall/confirm_order', array('store_id' => $store['store_id'],'share_uid'=>$_GET['share_uid'],'openid'=>$_GET['openid']))}";
        //获取商品评论信息
        var shopReplyUrl2="{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$store['store_id']}&goods_id={pigcms{$now_goods['goods_id']}";
        var open_extra_price =Number("{pigcms{$config.open_extra_price}");
        var extra_price_name ="{pigcms{$config.extra_price_alias_name}";

        // 
        var score_name = "{pigcms{$config['score_name']}";
        var book_id = "{pigcms{$book_act['id']}";

        var is_collect="{pigcms{$iscollect}";
        /*$(".img-collect"+is_collect).show();*/
        $(function () {
            setTimeout(function () {
                $(".img-collect"+is_collect).show();
            },500)
        })
    </script>
    <script>
        var ajax_cart_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart";
        var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart_save";
        var goods_cart = '{pigcms{$goods_cart}';
        //获取小程序分享信息地址
        var getShareUrl = "{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=share&goods_id={pigcms{$now_goods['goods_id']}&is_seckill_price={pigcms{$now_goods['is_seckill_price']}";
        var $home=false;
		var goods_price = {pigcms{$now_goods['price']};
    </script>
    <style>
        #pageLoadTip{display:none !important;}
        .swiper-pagination-video{
            padding-right: 0px !important;
            width: 100% !important;
        }
        .swiper-container110 .swiper-slide>div{width:100%}
        .explices:after{
            transform: rotate(135deg);
            width: 5px;
            height: 5px;
            border: 1px solid #f23030;
            border-right: 2px solid transparent;
            border-bottom: 2px solid transparent;
            content: "";
            display: block;
            position: absolute;
            right: 12px;
            top: 56%;
            margin-top: -7px;
            background: none;
        }
        .dashed:before{
            z-index: 10;
            top: 50%;
            position: absolute;
            content: '';
            height: 81px;
            width: 2px;
            left: -2px;
            border-left: 3px dashed rgba(29, 127, 238, 0.1);
            margin-top: -41px;
            background: #fff;
        }
        .video-js .vjs-tech{
        	height: auto;
        	bottom: 0;
        	margin: auto 0;
        }
        .card_span{
            background: #f23030; border: 1px solid #f23030; padding: 1px 3px; color: #fff; border-radius: 4px;
        }
        .card_span1{
            background: #dedede; border: 1px solid #dedede; padding: 1px 3px; color: #999; border-radius: 4px;
            white-space: nowrap;
        }
       /* #toTop{ background-color: rgba(0,0,0,0.7) !important;}*/
        .media_l{width:65%}
        .media_r{width:35%}
        .chuxiao{
            padding: 1px 3px;
            border-radius: 7px;
            border: 1px solid #f23030;
            color: #f23030;
        }
        .delivery-desc  {
                background: #fff;
        }
        .delivery-desc .detail-box {
            border: 1px solid #efefef;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .delivery-desc .detail-box .line {
            border-bottom: 1px solid #efefef;
        }
       .delivery-desc .detail-box .line>span {
            color: #999;
            font-size: 12px;
            font-weight: normal;
            text-align: center;
            padding: 5px;
            box-sizing: border-box;
        }
        .delivery-desc .detail-box .line>span {
            color: #999;
            font-size: 12px;
            font-weight: normal;
            text-align: center;
            padding: 5px;
            box-sizing: border-box;
        }
        .delivery-desc .detail-box .line .txt {
            width: 65%;
            border-left: 1px solid #efefef;
        }
        .delivery-desc .detail-box .line .tip {
            width: 35%;
        }
        .delivery-desc .detail-box {
            border: 1px solid #efefef;
            margin-top: 10px;
        }

	.specs_info_buy_btn {
            border-radius: 30px !important;
	}
    .flex_news{
        width: 100%;
        box-sizing: border-box;
        padding-right: 15px;
    }
	

		.specs_info_buy_btn {
				border-radius: 30px !important;
		}
		.flex_news{
			width: 100%;
			box-sizing: border-box;
			padding-right: 15px;
		}

        @media only screen and (device-width: 375px) and (device-height:812px) and (-webkit-device-pixel-ratio:3) {
            .content {
                padding-bottom:82px!important;
            }
        }
		
		.shopDetail .content_box table{
			width: 100%;
			margin-top: 0px;
			border: none;
			font-size: 12px;
			color: #222;
		}
		.shopDetail .content_box table td {
			padding: 8px 10px;
			background: #FFF;
			border: 1px solid #E8E8E8;
			border-top: none;
			word-break: break-all;
			word-wrap: break-word;
		}
		.shopDetail .content_box table td {
			color: #666;
			border-bottom-style: dotted;
			font-size: 12px;
		}
		.shopDetail .content_box table.ke-zeroborder td {
			border: 1px dotted #AAA;
		}
		.shopDetail .content_box p {
			margin: 5px 0;
		}
		.time{ 
			background-color: #fa8332;
			padding: 2px;
			color: #fff;
			width: 20px;
			text-align: center;
			margin-left: 0px;
			display: inline-block;
		}
		.topGrad{
			justify-content:space-between;
			padding:10px; 
			color:#ad1e00;
			font-size: 14px;
		}
		 
		.yushou span{
			font-size: 15px;
			color: #000;
			padding-top: 20px;
		}
		.yushou{
			padding: 10px 0;
		}
		.one{
			display: block;
			width: 15px;
			height: 15px;
			line-height: 15px;
			text-align: center;
			margin-right: 10px;
			border-radius:100% ;
			display: inline-block;
			background-color: #000;
			color:#fff;
		}
		.time_ju span{
			margin: 0 4px;
		}
		
		.collect_box{
			position:absolute;
			width:35px;
			height:35px;
			background:rgba(0,0,0,0.4);
			border-radius:50%;
			top:15px;
			right:15px;
			z-index: 10;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.collect_box img{
			width:20px;
			height:20px;
            z-index: 11;
		}
        /* 可得养老金 */
        .pension {
            margin-left: 4px;
            padding: 0 7px 0 18px;
            font-size: 9px;
            line-height: 18px;
            display: inline-block;
            background-color: red;
            background-image:url({pigcms{$static_public}/images/oldAgePensionIcon.png);
            background-size: 12px 12px;
            background-repeat: no-repeat;
            background-position: 4px;
            color: #ffffff;
            border-radius: 20px;
            white-space: pre-wrap;
            max-width: 100%;
            word-break: break-word;
            font-weight: normal;
            box-sizing: border-box;
        }
    </style>
    <link href="{pigcms{$static_path}mall/css/media.css" rel="stylesheet" />
</head>
<body style="overflow-x: hidden;">
<!-- 返回顶部按钮 -->
<div id='toTop' class="cursor"><img src="{pigcms{$static_path}mall/images/go_top.png" class="w100 h100" /></div>
<div class="bg_f detail_header flex">
    <div class="flex_one flex" style="">
        <span class="color_red align_center font_size14 active flex_one cursor"><i class="icon iconfont">&#xe633;</i>商品</span>
        <span id='commentDetail' class="align_center font_size14 flex_one cursor" ><i class="icon iconfont">&#xe633;</i>评价</span>
        <span id='shopDetail' class="align_center font_size14 flex_one cursor" ><i class="icon iconfont">&#xe633;</i>详情</span>
    </div>
</div>
 
<div class="danmu">
    <div class="swiper-container swiper-container111" >
        <div class="swiper-wrapper">

        </div>
    </div>
</div>


<!-- 收藏 -->
<if condition="$isLogin">
<div class="collect_box">
       <img src="{pigcms{$config.site_url}/tpl/Wap/pure/static/images/notcollect.png" alt=""  onclick="imgcollect(1)" class="img-collect0" <if condition="$now_goods['is_collect']">style="display: none"</if>>

        <img src="{pigcms{$config.site_url}/tpl/Wap/pure/static/images/collectyet.png" alt=""  onclick="imgcollect(0)" class="img-collect1" <if condition="!$now_goods['is_collect']">style="display: none"</if>>
	<!-- 收藏之后的图片是collectyet.png -->
</div>
</if>

<input type="file" value="{pigcms{$static_path}mall/video/video.mp4" style="display:none" id="hide_video"/>
<div class="head title_data"  data-is_seckill="{pigcms{$now_goods['is_seckill_price']|intval}" data-pack="{pigcms{$now_goods['packing_charge']|floatval}" data-price="{pigcms{$now_goods['price']|floatval}" data-name="{pigcms{$now_goods['name']}" data-stock="{pigcms{$now_goods['stock_num']}" data-goods_id="{pigcms{$now_goods['goods_id']}" data-store_id="{pigcms{$store['store_id']}" data-extra_pay_price="{pigcms{$now_goods['extra_pay_price']}" data-max_num="{pigcms{$now_goods['max_num']}" data-min_num="{pigcms{$now_goods['min_num']}" data-limit_type="{pigcms{$now_goods['limit_type']}" data-unit="{pigcms{$now_goods['unit']}" data-o_price="{pigcms{$now_goods['old_price']|floatval}" data-account_money="{pigcms{$now_goods['account_money']|floatval}" data-account_score="{pigcms{$now_goods['account_score']|floatval}">
    <div class="homepage closeItem rel" id="listBanner">
        <div class="swiper-container swiper-container110" >
            <div class="swiper-wrapper pics" id='scroll_video'>
                <!-- 商品视频 -->
                <notempty name='now_goods.video_url' >
					<div class="swiper-slide w100 play_video cursor" >
                        <div class="video_content_modal cursor"></div>
                        <video id="my_video" class="video-js vjs-default-skin vjs-big-play-centered"  controls="controls" style="object-fit:fill;"  webkit-playsinline='true' x-webkit-airplay="true" x5-video-player-type="h5" x5-video-player-fullscreen="true"  playsinline='true'   preload="auto" poster="{pigcms{$now_goods.pic_arr.0.url}" data-setup="{}">
                            <source src="{pigcms{:replace_file_domain('/'.ltrim($now_goods['video_url']), '/')}" type="video/mp4" />
                        </video>
                    </div>
                </notempty>
                <volist name="now_goods['pic_arr']" id="vo" >
                    <div class="swiper-slide w100 ">
                        <img src="{pigcms{$vo.url}" data-pics='{pigcms{$vo.url}' alt="" data-index="{pigcms{$key}"  class="h100 w100 cursor"/>
                    </div>

                </volist>

            </div>
            <div class="swiper-pagination   <notempty name='now_goods.video_url'>  swiper-pagination-video  </notempty> "  ></div>
        </div>
    </div>
</div>

<if condition="$now_goods['current_liveshow_id']">
	<style>
		.goLiveIng{
			background:linear-gradient(90deg,rgba(255,74,42,1),rgba(254,65,64,1));
			border-radius:15px;
			color: #fff; 
			width: 60px;
			padding: 3px 2px;
			position: fixed;
			right: 10px;
			font-size: 12px;
			top:60px;
			z-index: 1;
		}
		.goLiveIng .statusIng{
			background: url({pigcms{$static_path}mall/images/statusIng.gif) left center no-repeat;
			background-size: 18px 18px;
			padding-left: 18px;
		}
	</style>
	<div class="goLiveIng">
		<span class="statusIng">直播中</span>
	</div>
	<if condition="$is_wxapp_browser">
		<script>
			$('.goLiveIng').click(function(){
				wx.navigateTo({url: '/pages/live_and_video/live/viewer/main_page?live_id={pigcms{$now_goods.current_liveshow_id}'});
			});
		</script>
	<else/>
		<script>
			$('.goLiveIng').click(function(){
				location.href = '{pigcms{$config.site_url}{pigcms{:D('House_village')->base_url}pages/live_and_video/live/viewer/main_page?live_id={pigcms{$now_goods.current_liveshow_id}';
			});
		</script>
	</if>
</if>

<div class="content " style="padding-bottom:48px">
	<if condition="!empty($book_act)" >
	<div class="flex grad topGrad">
		<div style="color: #fff;"> 
			<div style="font-size: 16px;">定金：<label style=" ">{pigcms{$book_act['bargin_price']}</label></div>
			<div style="font-size: 15px;">预售价： <label style="font-size: 15px;">{pigcms{$config['Currency_symbol']}{pigcms{$book_act['price']}</label></div>
		</div> 
		<div>
			距离结束时间：
			<!-- <div>{pigcms{$book_act['end_timestamp']|floatval}秒 {pigcms{$book_act['end_time']}</div> -->
			<div class="time_ju">
				<span id="d" style="color: #fff;"></span><span>天</span>
				<span id="h" class="time"></span><span>:</span>
				<span id="i" class="time"></span><span>:</span>
				<span id="s" class="time"></span> 
			</div> 
			 
		</div>  
		<script type="text/javascript">
				test();
			    function test(){ 
					var nowTime = new Date().getTime(); 
					// 结束时间
					var endTime = {pigcms{$book_act['end_time']}+'000';
					// 相差的时间	
					var t = endTime - nowTime;
					//var t = {pigcms{$book_act['end_timestamp']|floatval} 
					var d = Math.floor(t/1000/60/60/24);
					
					var h = Math.floor(t/1000/60/60%24);
					var i = Math.floor(t/1000/60%60);
					var s = Math.floor(t/1000%60);
									 
					document.getElementById('d').innerHTML = d;
					document.getElementById('h').innerHTML = h;
					document.getElementById('i').innerHTML = i;
					document.getElementById('s').innerHTML = s;
									 
					setTimeout(test, 1000);
				}
			 		
		</script>
		
	</div>
	</if>
    <!-- 限时优惠 -->
    <if condition="$now_goods['is_seckill_price']">
        <div class=" bg_f" id="count_down" style=" height: 50px;">
            <div class="left color_f grad_red h100 media_l" style="width: 52%">
                <div style="margin-top:-3px" class="pl10"><span>{pigcms{$config['Currency_symbol']}</span><span class="font_size24">{pigcms{$now_goods.price}</span></div>
                <div class="flex pl10" style="margin-top:-4px">
                    <div class="flex border_tran" style="line-height: 12px;border-radius: 7px;overflow: hidden;">
                        <span class="bg_f color_red pl5" style="padding-right: 2px;"><i class="icon iconfont font_size12">&#xe6c2;</i></span>
                        <span class="font_size12 pd0_5">{pigcms{$config.seckill_alias_name|default='限时优惠'}</span>
                    </div>
                    <span class="font_size13 rel ml10" style="text-decoration: line-through;">{pigcms{$config['Currency_symbol']}{pigcms{$now_goods.old_price}</span>
                </div>
            </div>
            <div class=" align_center left h100 media_r" style="background:#ffeae9;width: 48%">
                <div>
                    <if condition="!$now_goods['is_begin_seckill_price']">
                            <p class="font_size12 align_center" style="color:#e32650;margin-top:3px">距结束还剩：</p>
                            <span class="font_size12 clock" >
                                <span class="color_f time joind d">00</span>
                                <span style="color:#e32650">天</span>
                                <span class="color_f time joinh h">00</span><span class="dian font_b">:</span>
                                <span class="color_f time joinm m">00</span><span class="dian font_b">:</span>
                                <span class="time color_f joins s">00</span>
                            </span>
                        <else/>
                           <p class="font_size12 align_center" style="color:#e32650;margin-top:3px">距开始还有：</p>
                            <span class="font_size12 clock" >
                                <span class="color_f time joind d">00</span>
                                <span style="color:#e32650">天</span>
                                <span class="color_f time joinh h">00</span><span class="dian font_b">:</span>
                                <span class="color_f time joinm m">00</span><span class="dian font_b">:</span>
                                <span class="time color_f joins s">00</span>
                            </span>
                    </if>

                </div>

            </div>
        </div>
    </if>
    <div class="demo-block bg_f pd10 mb_10" style="padding-bottom:5px !important">
        <div class="flex">
            <div class="flex_one width0 shoucang rel pr10">
                <h4 class="ui-nowrap-multi-del">
                    <eq name='now_goods.is_self' value='1'>
                        <i class="self_tag" style='vertical-align: text-bottom;'>自营</i>
                        <if condition="!empty($book_act)" >
						<i class="self_tag" style='vertical-align: text-bottom;'>预售</i>
                        </if>
                    </eq>
                    <if condition="$now_goods['vip_show']">
                        <i class="self_tag" style='vertical-align: text-bottom;'>{pigcms{$now_goods.vip_tag}</i>
                    </if>
                    {pigcms{$now_goods['name']}
                </h4>
                <p class="ui-nowrap-multi color_9 font_size14">{pigcms{$now_goods.describe}</p>
            </div>
            <div class="flex enshrine cursor">
                <div class="align_center">
                    <i class="iconfont icon-share_reward" style="font-size:24px"> </i>
                    <p class="color_7 font_size12" style="margin-top:-7px">分享有礼</p>
                </div>

            </div>
        </div>
        <if condition="!$now_goods['is_seckill_price']&&empty($book_act)">
            <h4 class="ui-nowrap color_red" >
				<if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">
					<span style="font-size:22px" class="font_b"><label style="font-size: 12px;">{pigcms{$config['Currency_symbol']}</label> 
						<span class=" "  id="tgoods_price">
                            <if condition="$now_goods['vip_show']">
                                {pigcms{$now_goods.vip_price}
                            <else/>
							{pigcms{$now_goods['price']|floatval}+{pigcms{$now_goods.extra_pay_price}{pigcms{$config.extra_price_alias_name}
                            </if>
						</span>
					</span>
				<elseif condition="$now_goods.price_type eq 3"/>
					<span style="font-size:22px" class="font_b">
						<span class=" "  id="tgoods_price">
                             <if condition="$now_goods['vip_show']">
                                {pigcms{$now_goods.vip_price}
                            <else/>
							    {pigcms{$now_goods['account_score']|floatval}<label style="font-size: 12px;">{pigcms{$config.score_name}</label>
                             </if>
						</span>
					</span>
				<elseif condition="$now_goods.price_type eq 2"/>
					<span style="font-size:22px" class="font_b"><label style="font-size: 12px;">{pigcms{$config['Currency_symbol']}</label>
						<span class=" "  id="tgoods_price">
                             <if condition="$now_goods['vip_show']">
                                {pigcms{$now_goods.vip_price}
                            <else/>
                                 {pigcms{$now_goods['account_money']|floatval} + {pigcms{$now_goods['account_score']|floatval}
							    <label style="font-size: 12px;">{pigcms{$config.score_name}</label>
                             </if>
						</span>
					</span>
				<else/>
					<span style="font-size:22px" class="font_b"><label style="font-size: 12px;">{pigcms{$config['Currency_symbol']}</label>
                        <span class=" "  id="tgoods_price">
                             <if condition="$now_goods['vip_show']">
                                {pigcms{$now_goods.vip_price}
                            <else/>
                                {pigcms{$now_goods['price']|getFormatNumber}
                             </if>
						</span>
					</span>
				</if>  
                <if condition="$config['open_old_age_pension'] == '1'">
                <span class="pension">可得养老金 {pigcms{$config['Currency_symbol']}{pigcms{$now_goods.old_age_pension}</span>
                </if>  
                <!-- <span class="font_size16 color_7 ml10  border_center rel">¥{pigcms{$now_goods['old_price']|floatval}</span> -->
            </h4>
        </if>

		<!-- 原价 -->
		<if condition="$now_goods['price_type'] gt 1 && !$now_goods['is_seckill_price'] && !$now_goods['is_begin_seckill_price']">
		    <h4 class="ui-nowrap yuan_price ui-list-link Choice_n" data-type='buy' data-yuan_price='yuan_price' data-price_type="1">
		        <span style="font-size:15px">原价购买：<span style="color:#333; font-weight: bold;" >{pigcms{$config['Currency_symbol']}
                {pigcms{$now_goods['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">+{pigcms{$now_goods.extra_pay_price}{pigcms{$config.extra_price_alias_name}</if></span></span>
		    </h4>
		</if>
			
        <if condition="!$now_goods['is_seckill_price']">
            <p class="color_9 font_size12 flex" style=''>
            <span class="flex_one">
	            <gt name="now_goods.sell_count" value='0'>
                    已售{pigcms{$now_goods['sell_count']}
                    <else/>
                    新品上线
                </gt>
			</span>
            <if condition="$plat_deliver">
                <span class="flex_one">{pigcms{$config.deliver_name}</span>
                <else/>
                <span class="flex_one">运费:{pigcms{$config['Currency_symbol']}{pigcms{$now_goods['deliver_fee']}</span>
            </if>
            
            <span class="flex_one align_right">{pigcms{$store['now_city_name']}</span>
        </p>

         <else/>

        <p class="color_9 font_size12 flex" style='padding-top:15px '>
            <span class="flex_one">
	            <gt name="now_goods.sell_count" value='0'>
                    已售{pigcms{$now_goods['sell_count']}
                    <else/>
                    新品上线
                </gt>
			</span>
            <span class="flex_one">运费:{pigcms{$config['Currency_symbol']}{pigcms{$now_goods['deliver_fee']}</span>
            <span class="flex_one align_right">{pigcms{$store['now_city_name']}</span>
        </p>
        </if>
        <!-- 商品标签 -->
        <div class="flex" style="flex-direction: row;  flex-wrap: wrap;margin-top:5px;">
            <volist name='now_goods.label_arr' id='lvo'>
                <div class=" color_9 font_size12 flex mg_r10">
                    <img src="{pigcms{$static_path}mall/images/duigou.png" style="width:12px;height:12px;padding-right:3px"/>
                    <span>{pigcms{$lvo}</span>
                </div>
            </volist>
        </div>
        <if condition="!empty($book_act)" > 
			<!--<div class="yushou">
				<span>预售规则</span>
			</div> -->
			
			<div class="ui-list ui-list-single ui-list-link ui-border-t mb_0" style="padding-top: 5px;">
				<div class="title" style="margin-bottom: 5px;font-size:15px; font-weight: 700;">预售规则</div>
				<div class='flex_one'>
					<if condition="$book_act.rest_pay_type eq '1'" >
						<p class='color_9 font_size12'>支付尾款时间：{pigcms{$book_act['rest_start_time']|date='Y-m-d',###}至{pigcms{$book_act['rest_end_time']|date='Y-m-d',###}</p>
					<else/>
						<p class='color_9 font_size12'>支付尾款时间：定金支付后{pigcms{$book_act['rest_day']|floatval}天</p>
					</if>
					
					<if condition="$book_act.send_goods_type eq '1'" >
						<p class='color_9 font_size12'>发货时间：{pigcms{$book_act['send_goods_date']|date='Y-m-d',###}</p>
					<else/>
						<p class='color_9 font_size12'>发货时间：尾款支付后{pigcms{$book_act['rest_day']|floatval}天</p>
					</if>
				</div>
				
				<div class=" font_size14  flex ping_game standard cursor" style="padding: 11px 0;"> 
				<div style="color:#222222; font-size:15px; font-weight: 700; margin-right: 30px;">流程</div>
					<div style="color:#999; font-size: 13px !important;">1.付定金</div>
					<span style="border-bottom:1px solid #F2F2F2;width: 30px;height: 1px;margin: 0 8px;"></span>
					<div style="color:#999; font-size: 13px !important;">2.交尾款</div>
					<span style="border-bottom:1px solid #F2F2F2;width: 30px;height: 1px;margin: 0 8px;"></span>
					<div style="color:#999; font-size: 13px !important;">3.发货</div>
				</div> 
			</div>
			
			
        </if>
    </div>
    <!-- 开通会员 -->
    <if condition="$member_info['max_level_discount'] lt 10">
        <div class="demo-block" >
            <ul class="ui-list ui-list-nospace ui-border-t" style="background-color: #F9F5EF;">
                <li class="ui-border-t ui-arrowlink ml_0 flex explices">
                    <div class="ui-list-info flex_one" >
                        <h4 class="ui-nowrap  color_7 font_size14 flex">
                            <img src="{pigcms{$static_path}mall/images/vip_01.png" style="width: 15px; height: 15px; border-radius: 50%;"/>
                            <span>{pigcms{$config.vip_card_alias|default='平台会员卡'}下单最多可享</span>
                            <span class="font_size20 color_red">&nbsp;{pigcms{$member_info.max_level_discount}折</span></h4>
                    </div>
                    <if condition="$member_info['is_vip'] eq 0">
                        <a href="{pigcms{:U('My/levelUpdate')}">
                            <div class="ui-list-info rel go_member">
                                <h4 class="ui-nowrap  font_size14 color_red">开通会员</h4>
                            </div>
                        </a>
                    <else/>
                        <div class="ui-list-info rel go_member">
                            <h4 class="ui-nowrap  font_size14 color_red Choice_n" data-type='buy'>去使用</h4>
                        </div>
                    </if>
                </li>
            </ul>
        </div>
    </if>
	
	
    <notempty name="pack">
    <div class="demo-block" >
            <ul class="ui-list ui-list-nospace ui-border-t" style="background-color: #F9F5EF;">
                
                <li class="ui-border-t ui-arrowlink ml_0 mt_10 flex explices">
                    <div class="ui-list-info flex_one" >
                        <h4 class="ui-nowrap  color_7 font_size14 flex">
                            <img src="{pigcms{$static_path}mall/images/vip_01.png" style="width: 15px; height: 15px; border-radius: 50%;"/>
                            <span class="font_size20 color_red">{pigcms{$pack.pack_name}</span> 
                    </div>
                    <a href="{pigcms{:U('pack_act',array('id'=>$pack['id']))}">
                        <div class="ui-list-info rel go_member">
                            <h4 class="ui-nowrap font_size14 color_red">去凑单</h4>
                        </div>
                    </a>    
                </li>
               
            </ul>
        </div>
    </notempty>
    <if condition="$merchant_card_spread_info && $merchant_card_spread_info['card_discount']">
        <div class="demo-block ">
           <ul class="ui-list ui-list-nospace ui-border-t mb_0">
                 <li class="ui-border-t ui-arrowlink flex flex_star after">
                    <div class="width_48">
                        <span class="font_size15 color_9">领卡</span>
                    </div>
                   <div class="ui-list-info explain flex_one width0 pl0 pr0">
                        <p class="">
                            <span class="sever rel  mg_r10 "><span class="color_7">领商家会员卡享</span>&nbsp;<span class="font_size20 color_red">{pigcms{$merchant_card_spread_info.card_discount|floatval}折</span>&nbsp;<span class="color_7">下单优惠</span>&nbsp;
                     <span class="card_spans" data-url="<if condition="empty($user_session)">{pigcms{:U
                   ('Login/index')}<elseif condition="!$merchant_card_spread_info['get_card']"/>
                 {pigcms{$merchant_card_spread_info.url}<else/>{pigcms{$merchant_card_spread_info.url}</if>">
                        <if condition="empty($user_session)">
                           <span class="card_span">请先登录</span>
                        <elseif condition="!$merchant_card_spread_info['get_card']"/>
                             <span class="card_span">点击领取</span>
                            <else/>
                             <span class="card_span1">已经领取</span>
                        </if>
                        </span>
                       </span>
                       </p>
                  </div>
                </li>
            </ul>
        </div>
     </if>
    <if condition="$periodic">
        <div class="delivery-desc">
            <div class="font_size14" style="    margin-left: 15px;    padding-top: 12px;">配送说明</div>
            <div class="detail-box">
                <div class="flex flex-align line">
                    <span class="tip">配送频次</span>
                    <span class="txt">
                        <if condition="$periodic['periodic_type'] eq 1">每日一期<elseif condition="$periodic['periodic_type'] eq 2"/>每周一期<else/>每月一期</if>，共{pigcms{$periodic['periodic_count']}期
                    </span>
                </div>
                <div class="flex flex-align line">
                    <span class="tip">配送顺延限制</span>
                    <span class="txt">
                        <if condition="$periodic['delay_limit'] eq '-1'">不限制次数<else/>限制{pigcms{$periodic['delay_limit']}次</if>
                    </span>
                </div>
                <div class="flex flex-align line">
                    <span class="tip">最近配送日期</span>
                    <if condition="$periodic['pre_delivery_day'] gt 0">
                        <span class="txt">需在最近配送日期前{pigcms{$periodic['pre_delivery_day']}天，且{pigcms{$periodic['pre_delivery_time']}点前下单支付</span>
                    <else/>
                        <span class="txt">需在最近配送日期当天，且{pigcms{$periodic['pre_delivery_time']}点前下单支付</span>
                    </if>
                </div>
            </div>
        </div>
    </if>

<if condition="$user_score_use_money gt 0">
<div class="demo-block ">
    <ul class="ui-list ui-list-nospace ui-border-t mb_0">
        <li class="ui-border-t ui-arrowlink flex flex_star after">
            <div class="width_48">
                <span class="font_size15 color_9">{pigcms{$config.score_name}</span>
            </div>
            <div class="ui-list-info  explain flex_one width0 pl0 pr0 <if condition="empty($user_session)">score_login</if> cursor" data-url="{pigcms{:U('Login/index')}">
                <p class="color_7" >
				 <if condition="empty($user_session)">
                    请先登录查看可抵现金额
					<else />
					可抵现{pigcms{$user_score_use_money|floatval}{pigcms{$config['Currency_txt']}
					</if>
                </p>

                <p class="ui-nowrap explain color_7">
					<if condition="empty($user_session)">
                    <span class="sever rel ">请先登录查看获得{pigcms{$config.score_name}</span>
					<else />
					可获得{pigcms{$can_get_score}{pigcms{$config.score_name}
					</if>
                </p>

            </div>
        </li>
    </ul>
</div>
</if>

<if condition="!empty($coupon_list)">
<div class="demo-block">
    <div class="demo-block">
        <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
            <li class="ui-border-t ">
                <div class="ui-list-info coupon_click flex cursor">
                    <div class="flex flex_one">
                        <h4 class=" font_size15 width_48" >
                            <span class="opt2 color_9">领券</span>
                        </h4>
                        <div>
                            <volist name="coupon_list" id="vc" key='k'>
                                <elt name='k' value='3'>
                                    <span class="color_f pd3_5 font_size13 mg_r5" style="background-image: url('tpl/Wap/pure/static/mall/images/coupon1.jpg');background-size: 100% 100%;" ><if condition="$vc.is_discount eq 1">满{pigcms{$vc.order_money|floatval}打{pigcms{$vc.discount_value|floatval}折<else />满{pigcms{$vc.order_money|floatval}减{pigcms{$vc.discount|floatval}</if></span>
                                    <elseif condition="$k eq 4"/>
                                    <span>...</span>
                                </elt>
                            </volist>
                            <!-- <p><span>共{pigcms{:count($coupon_list)}张</span></p> -->
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>
</if>
<!-- 系统、商家优惠 -->
<if condition="$store['coupon_list'] OR $price">
<div class="demo-block activity_chuxiao  cursor">
    <ul class="ui-list ui-list-link ui-list-nospace ui-border-t mb_0">
        <li class=" ui-arrowlink flex flex_star ui-list-single">
            <div class="ui-list-info pl0 w100">
                <div class="width_48">
                    <span class="font_size15 color_9">促销</span>
                </div>
                <div class=" explain flex_one width0 pl0 pr0" >
                    <div class="tjt_two">
                        <notempty name="store.coupon_list.free_postage">
                          <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                                <span class="chuxiao font_size11 mg_r5" >包邮</span>
                                <span class="sever rel  mg_r10  color_7">满
                                {pigcms{$store['coupon_list']['free_postage']}包邮
                            </span>
                          </p>
                        </notempty>
                        <notempty name="store.coupon_list.system_newuser">
                        <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                            <volist name='store.coupon_list.system_newuser' key='k' id='sn'>
                                <span class="chuxiao font_size11 mg_r5" >平台首单</span>
                                <span class="sever rel  mg_r10  color_7">
                                <!--<gt name='k' value='1'>;</gt>-->
                                    <if condition="isset($sn['percentage_status']) and ($sn['percentage_status'] eq 1)">
                                        满{pigcms{$sn['money']}享<php>echo round($sn['minus']*10,2);</php>折
                                    <else/>
                                        满{pigcms{$sn['money']}减{pigcms{$sn['minus']}
                                    </if>
                            </span>
                            </volist>
                        </php>
                        </notempty>
                        <if condition="$store['coupon_list']['system_minus']">
                            <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;" data-ro="55">
                                <volist name="store.coupon_list.system_minus" id='sm' key='k'>
                                    <span class="chuxiao font_size11 mg_r5" >平台满减</span>
                                    <span class="sever rel  mg_r10  color_7">
                                <!--<gt name='k' value='1'>;</gt>-->
                                <if condition="isset($sm['percentage_status']) and ($sm['percentage_status'] eq 1)">
                                    满{pigcms{$sm['money']}享<php>echo round($sm['minus']*10,2);</php>折
                                <else/>
                                    满{pigcms{$sm['money']}减{pigcms{$sm['minus']}
                                </if>
                            </span>
                                </volist>
                            </p>
                        </if>
                        <notempty name="store.coupon_list.discount">
                          <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                                <span class="chuxiao font_size11 mg_r5" >商家折扣</span>
                                <span class="sever rel  mg_r10  color_7">
                                {pigcms{$store['coupon_list']['discount']}折
                            </span>
                          </p>
                        </notempty>
                        <if condition="$store['coupon_list']['newuser']">
                            <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                                <volist name="store.coupon_list.newuser" id='n' key='k'>
                                    <span class="chuxiao font_size11 mg_r5" >商家首单</span>
                                    <span class="sever rel  mg_r10  color_7">
                                <gt name='k' value='1'>;</gt>
                                满{pigcms{$n['money']}减{pigcms{$n['minus']}
                            </span>
                                </volist>
                            </p>
                        </if>
                        <if condition="$store['coupon_list']['minus']">
                            <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                                <volist name="store.coupon_list.minus" id='m' key='k'>
                                    <span class="chuxiao font_size11 mg_r5" >商家优惠</span>
                                    <span class="sever rel  mg_r10  color_7">
                                <gt name='k' value='1'>;</gt>
                                满{pigcms{$m['money']}减{pigcms{$m['minus']}
                            </span>
                                </volist>
                            </p>
                        </if>
                    </div>
                    <if condition="$price">
                    <div class="">
                        <p class="ui-nowrap coupon_nav" style="margin-right:10px;margin-bottom: 5px;">
                            <span class="chuxiao font_size11 mg_r5">赠品</span>
								<volist name="price" id="p">
									满{pigcms{$config.Currency_symbol}{pigcms{$p['goods_price']}赠商品或优惠券&nbsp;
								</volist>
                            </span>
                        </p>
                    </div>
                    </if>

                    <script>
                        var $del_mall=function(){
                            if($(".tjt_two>p").length==2){
                                $(".tjt_two>p").eq(1).html($(".tjt_two>p").eq(1).html()+'...')

                            }
                            if($(".tjt_two>p").length>2){
                                $(".tjt_two>p").eq(2).hide().remove()
                                $del_mall()
                            }

                        }
                        $del_mall()
                    </script>
                </div>
            </div>
        </li>

    </ul>
</div>
</if>
<!-- 无规格商品不显示 -->
<notempty name="goods_detail">
    <div class="demo-block">
        <div class="demo-block">
            <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
                <li class=" Choice">
                    <div class="ui-list-info cursor show_detail">
                        <h4 class="ui-nowrap font_size15 ">
                            <span class="opt">请选择</span>
                            <span class="namic">规格/属性</span>
                        </h4>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</notempty>

    <!-- 多少人购买 -->
    <gt name='buy_data.count' value='0'>
        <div class="demo-block">
            <div class="  bg_f  mg_t10 is_jion_friend">
                <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0 ">
                    <li class="ui-border-t after">
                        <div class="ui-list-info">
                            <h4 class="ui-nowrap font_size14">{pigcms{$buy_data.count}人已购买</h4>
                        </div>
                    </li>
                </ul>
                <div class="flex">
                    <div class="flex_one">
                        <volist name='buy_data.head_list' id='vo'>
                             <img src="{pigcms{:replace_file_domain($vo['avatar'])}" title="{pigcms{$vo.nickname}"/>
                        </volist>
                    </div>
                    <span class="noMore">...</span>

                    <div class="all_friend">
                        <button class="ui-btn ui-btn-primary more_buy" >邀请好友一起抢</button>
                    </div>
                </div>
            </div>
        </div>
    </gt>

    <!-- 商品评论 -->
    <include file="reply_box"/>

    <!-- 商家店铺 -->
    <include file="store_box"/>
    <!-- 店铺推荐 -->
    <include file="Recommend"/>
     <!-- 商品详情 -->
     <div class="demo-block shopDetail" >
        <ul class="ui-list ui-list-single ui-list-link mb_0 mg_t10" style="border-bottom: #edebeb 1px solid;">
            <li class="after">
                <div class="ui-list-info">
                    <h4 class="ui-nowrap"><span class="rel " style=" padding-left: 5px;border-left: 3px solid #f23030;">商品详情</span></h4>
                </div>
            </li>
        </ul>
        <div class="content_box">{pigcms{$now_goods['des']}</div>
    </div>
</div>
<div class="foot">
<div class="footerMenu wap footer flex ui-border-t">
    <div class="flex_two">
        <ul class="flex">
            <li class="flex_one"  <if condition="$kf_url"><else/>style="padding-left:13px" </if>>
                <a class="" href="{pigcms{:U('Mall/store',array('store_id'=>$store['store_id']))}"><em class="shop"></em><p>进店</p></a>
            </li>
            <li class="flex_one contactlx">
                <a href="javascript:;"><em class="kf"></em><p>客服</p></a>
            </li>
            <li class=" cart flex_one"  <if condition="$kf_url"><else/>style="padding-right:13px" </if>>
                 <em class="Number"></em>
                 <a href="{pigcms{:U('Mall/cart')}"><em class="cart"></em><p>购物车</p></a>
            </li>
        </ul>
    </div>
    <div class=" flex right flex_shree" >
        <if condition="!empty($book_act)" >
                <button class="specs_info_buy_btn ui-btn-lg-nowhole ui-btn-primary flex_one buy w_auto cursor bg_r font_size14 Choice_n no_border-radius-l" data-store_id="{pigcms{$store['store_id']}" data-type="buy">
                <p style="font-size:10px; margin-top: -10px;">支付定金</p>

                <if condition="$book_act.send_goods_type eq '1'" >
                    <p style="font-size:10px; margin-top: -20px;">{pigcms{$book_act['send_goods_date']|date='Y-m-d',###}发货</p>
                <else/>
                    <p style="font-size:10px; margin-top: -20px;">付款后{pigcms{$book_act['rest_day']|floatval}天发货</p>
                </if>
                
                </button>
        <else/>
        <if condition="empty($periodic) and !empty($now_goods['stock_num']) and $now_goods['status'] and !$now_goods['is_begin_seckill_price']">
            <button class="ui-btn-lg-nowhole ui-btn-primary flex_one join w_auto cursor font_size14 Choice_n bg_pink no_border-radius-r" data-store_id="{pigcms{$store['store_id']}" data-type="add">加入购物车</button>
        </if>

        <if condition="empty($periodic) and !empty($now_goods['stock_num']) and $now_goods['status'] and $now_goods['is_begin_seckill_price']">
         <button class="ui-btn-lg-nowhole ui-btn-primary flex_one join w_auto cursor font_size14 no_border-radius-r" style="background-color: darkgrey;">{pigcms{$now_goods['seckill_start_time']}开始</button>
        </if>

        <if condition="empty($now_goods['stock_num']) and $now_goods['status']">
            <button class="specs_info_buy_btn ui-btn-lg-nowhole ui-btn-primary flex_one cursor bg_d font_size14 Choice_n no_border-radius-l" data-store_id="{pigcms{$store['store_id']}" data-type="">
            商品已售罄
            </button>
        <elseif condition="$now_goods['status'] && !$now_goods['is_begin_seckill_price']"/>
            <button class="<if condition="$periodic">specs_info_buy_btn</if> ui-btn-lg-nowhole ui-btn-primary flex_one buy w_auto cursor bg_r font_size14 Choice_n no_border-radius-l" data-store_id="{pigcms{$store['store_id']}" data-type="buy">马上购买</button>
        <elseif condition="$now_goods['status'] && $now_goods['is_begin_seckill_price']"/>
            <button class="ui-btn-lg-nowhole ui-btn-primary flex_one buy w_auto cursor font_size14 no_border-radius-l" style="background-color: lightgrey">马上购买</button>
        <else/>
            <button class="specs_info_buy_btn ui-btn-lg-nowhole ui-btn-primary flex_one cursor bg_d font_size14 bg_r no_border-radius-l" data-store_id="{pigcms{$store['store_id']}" data-type="">
            商品已下架
            </button>
        </if>
		</if>
		
    </div> 
	
</div>
</div>
<div style="display:none" class="loop">
<div class="flex ui-border-b "   id="loop">
    <div class="flex_one align_center">
        <span class="rel active">好物</span>
    </div>
    <div class="flex_one align_center">
        <span class="rel ">详情</span>
    </div>
</div>
</div>
        <!-- 规格选项 -->
<include file="Speci"/>

<div class="coupon_mask cursor"></div>

<div class="coupon bg_f w100">
<div class="none">
    <div class="pt15 pb15 align_center font_b rel">优惠券<img src="{pigcms{$static_path}mall/images/icon_del.png" class="abs icon_del cursor"  style="width:30px;height:30px;right:10px"/></div>
    <ul class="ul pl10 pr10">
        <div class="mb_10  font_size16">可领取优惠券</div>
        <volist name="coupon_list" id="vo">
            <li class=" mb_10 " style="width: 100%;">
                <div class="flex" style="    height: 99px">
                    <div class='h100 color_red'>
                        <div class="align_center  h100 coupon_one" style="    border-top-right-radius: 20px; border-bottom-right-radius: 20px;width: 99px;">
                            <if condition="$vo.is_discount eq 1">
                                <div class="font_b " ><span class="font_size20" style="font-size: 40px !important;">{pigcms{$vo.discount_value|floatval}</span>折</div>
                                <else />
                                <div class="font_b pt10" style="font-size: 40px;"><span class="font_size20">{pigcms{$config['Currency_symbol']}</span>{pigcms{$vo.discount|floatval}</div>
                            </if>
                            <div class="font_size14">满{pigcms{$vo.order_money|floatval}可用</div>
                        </div>

                    </div>
                    <div class="flex_one h100 rel dashed" style="margin-left:2px">
                        <div class="h100 pd10 border_gray coupon_one flex" style="    border-top-left-radius: 20px; border-bottom-left-radius: 20px;box-sizing: border-box;">
                           <div class="flex_one">
                               <div>
                                   <span class="font_size12 color_f" style="background:#f23030;padding: 2px 8px;border-radius: 10px;"><if condition="$vo.is_system eq 1">平台券<else />商家券</if></span>
                                   <span class="font_size14">{pigcms{$vo.name}</span>
                               </div>
                               <div class="flex pt10">
                                   <div class="flex_one color_7">
                                       <div class="flex flex_justify pb5">
                                           <span class="font_size12">{pigcms{$vo.start_time|date="Y.m.d",###}-{pigcms{$vo.end_time|date="Y.m.d",###}生效</span>
                                       </div>
                                       <div class="ellipsis font_size12   flex coupon_see cursor" data-class="coupon_explain{pigcms{$i}">
                                           <div class="flex_one ellipsis" style="-webkit-line-clamp: 1;">
                                               {pigcms{$vo.cate_name}
                                           </div>
                                           <div class="choose_juan rel" style="height: 15px; width: 15px;"></div>
                                       </div>
                                   </div>
                               </div>
                           </div>
                            <if condition="$vo['receive_status'] eq 2">
                                <div class="">
                                    <span data-coupon_id='{pigcms{$vo.coupon_id}' class="font_size14 color_f grad_red br20 " style="padding: 4px 10px;background:#ccc;">已领完</span>
                                </div>
                                <elseif condition="$vo['receive_status'] eq 1"/>
                                <div class="">
                                    <span data-coupon_id='{pigcms{$vo.coupon_id}' class="font_size14 color_f grad_red br20 " style="padding: 4px 10px;background:#ccc;">已领取</span>
                                </div>
                                <else/>
                                <div class="receive_card"  data-system="{pigcms{$vo.is_system}" data-coupon_id='{pigcms{$vo.coupon_id}' data-cate_name="{pigcms{$vo.cate_name}"  data-wxcardid="{pigcms{$vo.wx_cardid}" data-tickettime="{pigcms{$vo.wx_ticket_addtime}" data-sign="{pigcms{$vo.cardsign}"  data-status="{pigcms{$vo.status}" data-allow_new="{pigcms{$vo.allow_new}">
                                    <span class="font_size14 color_f grad_red br20 " style="padding: 4px 10px">点击领取</span>
                                </div>
                            </if>


                        </div>
                    </div>

                </div>
                <div class="coupon_explain{pigcms{$i} none">
                    <ul class="ui-list ui-list-nospace  mb_0">
                        <li class=" ui-arrowlink flex flex_star after">
                            <div class="ui-list-info explain flex_one">
                                <p  class="sever rel pl10 mg_r10">
                                    描述：{pigcms{$vo.des}
                                </p>
                                <p  class="sever rel pl10 mg_r10">
                                    使用平台：{pigcms{$vo.platform}

                                </p>
                            </div>
                        </li>

                    </ul>
                </div>
            </li>
        </volist>

    </ul>
</div>
<div class="w100 none">
    <div class="pt15 pb15 align_center font_b rel">促销<img src="./tpl/Wap/pure/static/mall/images/icon_del.png" class="abs coupon_mask_close cursor" style="width:30px;height:30px;right:10px" /></div>

    <!-- 优惠信息 -->
    <notempty name="store['coupon_list'] OR coupon_lists">
        <div class="demo-block" id="coupon_box">
            <ul class="ui-list ui-list-single ui-border-t mb_0" style="overflow-y:scroll;height: 333.5px;width: auto;padding-left: 12px;">
                <!-- 满多少，包邮 -->
                <notempty name="store.coupon_list.free_postage">
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">包邮</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <span>满{pigcms{$store['coupon_list']['free_postage']}{pigcms{$config['Currency_txt']}包邮</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </notempty>
                <volist name="store.coupon_list.system_newuser" id='sn'>
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class=" font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">平台首单</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <if condition="isset($sn['percentage_status']) and ($sn['percentage_status'] eq 1)">
                                        <span>平台优惠满{pigcms{$sn['money']}{pigcms{$config['Currency_txt']}享<php>echo round($sn['minus']*10,2);</php>折</span>
                                    <else/>
                                        <span>平台首单满{pigcms{$sn['money']}{pigcms{$config['Currency_txt']}减{pigcms{$sn['minus']}{pigcms{$config['Currency_txt']}</span>
                                    </if>
                                </div>
                            </div>
                        </div>
                    </li>
                </volist>
                <volist name="store.coupon_list.system_minus" id='sm'>
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">平台满减</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <if condition="isset($sm['percentage_status']) and ($sm['percentage_status'] eq 1)">
                                        <span>平台优惠满{pigcms{$sm['money']}{pigcms{$config['Currency_txt']}享<php>echo round($sm['minus']*10,2);</php>折</span>
                                    <else/>
                                        <span>平台优惠满{pigcms{$sm['money']}{pigcms{$config['Currency_txt']}减{pigcms{$sm['minus']}{pigcms{$config['Currency_txt']}</span>
                                    </if>
                                </div>
                            </div>
                        </div>
                    </li>
                </volist>
                <notempty name="store.coupon_list.discount">
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">商家折扣</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <span>商家{pigcms{$store['coupon_list']['discount']}折优惠</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </notempty>
                <volist name="store.coupon_list.newuser" id='n'>
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">商家首单</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <span>商家首单满{pigcms{$n['money']}{pigcms{$config['Currency_txt']}减{pigcms{$n['minus']}{pigcms{$config['Currency_txt']}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </volist>
                <volist name="store.coupon_list.minus" id='m'>
                    <li class="ui-border-t " style="margin-left:0px;">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">商家优惠</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <span>商家优惠满{pigcms{$m['money']}{pigcms{$config['Currency_txt']}减{pigcms{$m['minus']}{pigcms{$config['Currency_txt']}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </volist>
                <volist name="coupon_lists" id='coupon'>
                <li class="ui-border-t " style="margin-left:0px;">
                    <div class="ui-list-info flex">
                        <div class="flex flex_one">
                            <h4 class="font_size11 mg_r5" style="min-width:60px">
                                <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">满{pigcms{$coupon['goods_price']}送以下优惠券或商品</span>
                            </h4>

                        </div>
                    </div>
                </li>
                    <if condition="$coupon['coupon_id']">
                <li class="" style="width: 100%;margin-left:0px;">
                    <div class="flex flex_news" style="height: 99px">
                        <div class="h100 color_red">
                            <div class="align_center  h100 coupon_one" style="border-top-right-radius: 20px; border-bottom-right-radius: 20px;width: 99px;">
                                <div class="font_b pt10" style="font-size: 40px;"><span class="font_size20">{pigcms{$config["Currency_symbol"]}</span>{pigcms{$coupon['discount']}</div>                            <div class="font_size14">满{pigcms{$coupon['goods_price']}可用</div>
                            </div>
                        </div>
                        <div class="flex_one h100 rel dashed" style="margin-left:2px">
                            <div class="h100 pd10 border_gray coupon_one flex" style="border-top-left-radius: 20px; border-bottom-left-radius: 20px;box-sizing: border-box;">
                                <div class="flex_one">
                                    <div>
                                        <span class="font_size12 color_f" style="background:#f23030;padding: 2px 8px;border-radius: 10px;">商家券</span>
                                        <span class="font_size14">{pigcms{$coupon['name']}</span>
                                    </div>
                                    <div class="flex pt10">
                                        <div class="flex_one color_7">
                                            <div class="flex flex_justify pb5">
                                                <span class="font_size12">{pigcms{$coupon['start_time']}-{pigcms{$coupon['end_time']}生效</span>
                                            </div>
                                            <div class="ellipsis font_size12   flex coupon_see cursor" data-class="coupon_explain1">
                                                <div class="flex_one ellipsis" style="-webkit-line-clamp: 1;">
                                                    商城
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </li>
                    </if>
                    <if condition="$coupon['shop_good_name']">
                    <li style="margin-left:0px;">
                        <div class="flex pt10">
                            <div class="flex_one color_7">
                                <div class="flex flex_justify pb5">
                                    <span class="font_size12" style="color:gray;">赠品:&nbsp;{pigcms{$coupon['shop_good_name']} &nbsp;&nbsp;+ 1个</span>
                                </div>
                            </div>
                        </div>
                    </li>
                   </if>
                </volist>
                <notempty name="now_goods.tickets_bind_goods">
                    <li class="ui-border-t ">
                        <div class="ui-list-info flex">
                            <div class="flex flex_one">
                                <h4 class="font_size11 mg_r5" style="min-width:60px">
                                    <span style=" border: 1px solid #f23030; padding: 1px 3px; color: #f23030; border-radius: 100px;">绑定商品</span>
                                </h4>
                                <div class="font_size14 color_7">
                                    <volist name="now_goods.tickets_bind_goods" id="to">
                                    <span>{pigcms{$to['name']}；</span>
                                    </volist>
                                </div>
                            </div>
                        </div>
                    </li>

                </notempty>
            </ul>
        </div>
    </notempty>
</div>
</div>
<div id="enshrine" class="none align_center cursor" >
    <div class="guide none"><img src='{pigcms{$static_path}mall/images/guide1.png'/></div>
    <div class="is_yongjin none">
    <div style="padding-top:25%">
        <img src='{pigcms{$static_path}mall/images/my_yongj.png' style="width:45px;height:45px"/>
        <p class="font_b font_size20">分享得佣金</p>
    </div>
    <div class="align_left" style="width:85%;margin:auto;padding-top: 10%;">
        邀请好友购买该商品，您将获得佣金，快去分享吧！
        <if condition="$spread_rate['first_rate'] gt 0">
            <div class="font_size14 yongjin">
                1、您的一级推广用户消费后，您将获得&nbsp;<span class='color_red font_size22 font_b'>{pigcms{$spread_rate['first_money']}</span>&nbsp;{pigcms{$config['Currency_txt']}
            </div>

        </if>
        <if condition="$spread_rate['second_money'] gt 0">
            <div class="font_size14 yongjin">
                2、您的二级推广用户消费后，您将获得&nbsp;<span class='color_red font_size22 font_b'>{pigcms{$spread_rate['second_money']}</span>&nbsp;{pigcms{$config['Currency_txt']}
            </div>
        </if>
        <if condition="$spread_rate['third_money'] gt 0">
            <div class="font_size14 yongjin">
                3、您的三级推广用户消费后，您将获得&nbsp;<span class='color_red font_size22 font_b'>{pigcms{$spread_rate['third_money']}</span>&nbsp;{pigcms{$config['Currency_txt']}
            </div>
        </if>
      </div>
    </div>
   <div style="width: 100%; position: fixed; bottom: 5%"  class="model_button none">
    <button class="cursor ui-btn-primary del_enshrine cursor font_size14  inline align_center color_0" style='border-radius:50px; margin-right: 18%; padding: 10px 0;    background: #E3E3E5;width: 35%;'>
        取消
    </button>
    <button id='go_share' class=" ui-btn-primary   font_size14 inline cursor" style=" padding: 10px 0;border-radius: 50px;width: 35%;">
        去挣钱
    </button>
    </div>
</div>


<!-- 浮动tabble -->
<include file="floatTabble"/>

<div class="pageSliderHide"></div>
</body>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js" type="text/javascript" ></script>
<script src="{pigcms{$static_path}mall/js/iscroll.min.js" type="text/javascript" ></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/swiper.js"></script>
<script src="{pigcms{$static_path}mall/js/video.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/mallbuygoods.js?t=6"></script>
 <!-- 微信分享 -->
<script type="text/javascript">
    function imgcollect(action){
        var tip = '';
        if(action == 1){
            //收藏
            $(".img-collect0").hide();
            $(".img-collect1").show();
            motify.log('已收藏',1000);
            tip = '已收藏';
        }else{
            //取消收藏
            $(".img-collect1").hide();
            $(".img-collect0").show();
            motify.log('取消收藏',1000);
            tip = '取消收藏';
        }

        var src="{pigcms{:U('My/do_collect')}";
        var goods_id = parseInt('{pigcms{$now_goods.goods_id}');
        $.post(src,{type:'mallgoods',collect_id:goods_id},function(res){
            if(res.error==0){
                motify.log(tip,1000);
            }else{
                motify.log(res.msg,1000);
            }
        });
    }
window.shareData = {
    "moduleName":"Mall",
    "moduleID":"0",
    "imgUrl": "{pigcms{$now_goods['pic_arr'][0]['url']}",
    "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/detail', array('goods_id' => $now_goods['goods_id']))}",
    "tTitle": "{pigcms{$now_goods['name']}",
    "tContent": "<if condition="!empty($now_goods['describe'])">{pigcms{$page_share_content}<else/>{pigcms{$now_goods['name']}</if>"
};
window.onload = function(){
    $('.ssd-module-wrap').css('width','100%');
}
/*wap端非微信环境提示到微信端分享*/
function wap_share_tip(flag){
    if(share_clock==1){
    	return false;
    }
    flag=flag || 0;
    if(flag==0 && $(".yongjin").size()>0){
        $(".is_yongjin>div").eq(0).css('padding-top','25%')
        $(".is_yongjin").removeClass('none');
        $(".coupon_mask").show();
        $("#enshrine").removeClass('none');
        $(".model_button").removeClass('none');
    }else{
        layer.open({
            content:'<p style="text-align:center;">请在微信端分享<p>',
            btn:['我知道了'],
            yes:function(index,dom){
                layer.close(index);
            }
        });
    }
}
</script>
{pigcms{$shareScript}

 <!-- 小程序分享 -->
<script type="text/javascript">
var ua = window.navigator.userAgent.toLowerCase();
var is_weixin=false;
var is_mini=false;
var share_clock=1;
/*是否在app端*/
var is_app={pigcms{$is_app_browser|default=0};
var res_url='';
var  webview=function(){
    wx.miniProgram.getEnv(function(res) {
    	share_clock=0;
        if (res.miniprogram) {
            //小程序中
            is_mini=true;
            $.ajax({
                type: "POST",
                url: getShareUrl,
                success: function(msg){
                    if(!msg.status){
                        layer.open({
                            content:msg.info,
                            btn:['我知道了'],
                            yes:function(index){
                                layer.close(index);
                            }
                        });
                        return false;
                    }
                    var res=msg.info;
                    res_url='/pages/index/share?title='+encodeURIComponent(res.title)+'&shareTitle='+encodeURIComponent(res.share_title)+'&shareImg='+encodeURIComponent(res.friend_img)+'&sharePath='+encodeURIComponent(res.wxapp_path)+'&bg='
                            + encodeURIComponent(res.good_image)+'&poster='+encodeURIComponent(res.mall_img)

                }
            });
        }else{
            //微信端
            is_weixin=true;
        }
    });
}


if(ua.indexOf('micromessenger') == -1){
    //不在微信或者小程序中
    share_clock=0;
}else{
    webview();
}

$(document).on('click', '#go_share', function(){
    if(is_mini){
        //小程序环境
        wx.miniProgram.navigateTo({
            url:res_url
        })
    }else{
        if(is_weixin){
            //微信端
            $(".guide").removeClass('none')
        }else if(is_app){
            shareFriend();
            //shareWeChat();
        }else{
            wap_share_tip(1);
        }
    }
});
$(document).on('click', '.enshrine,.more_buy', function(){
    if(is_mini || is_app){
        //小程序环境
        if($(".yongjin").size()>0){
            $(".is_yongjin>div").eq(0).css('padding-top','25%')
            $(".is_yongjin").removeClass('none');
            $(".coupon_mask").show();
            $("#enshrine").removeClass('none');
            $(".model_button").removeClass('none');
        }else{
            if(is_app){
                shareFriend();
            }else{
                wx.miniProgram.navigateTo({
                    url:res_url
                });
            }
        }
    }else{
        if(is_weixin){
            //微信端
            if($(".yongjin").size()>0){
                $(".is_yongjin>div").eq(0).css('padding-top','0px');
                $(".is_yongjin").removeClass('none');
            }
            $(".guide").removeClass('none');
            $(".coupon_mask").show();
            $("#enshrine").removeClass('none');
        }else{
            wap_share_tip();
        }
    }
});
$(document).on('click', '#enshrine', function(){
    //微信端
    $(".coupon").removeClass("active");
    $(".coupon_mask").hide();
    $("#enshrine").addClass('none');
    $(".guide").addClass('none');
    $(".model_button").addClass('none');
    $(".is_yongjin").addClass('none');
    $('body').css('overflow','auto');
    $(".mask").hide();
});
$('#enshrine').bind("touchmove",function(e){
    e.preventDefault();
});
</script>
<if condition="$is_app_browser">
<script type="text/javascript">
    var desc="{pigcms{$page_share_content}";
    var name="{pigcms{$now_goods.name}";
    var price="{pigcms{$now_goods.price}";
    desc=desc || '价值{pigcms{$config['Currency_symbol']}'+price+','+name;
    var share_pic="{pigcms{$now_goods['pic_arr'][0]['url']}";
    var share_url="{pigcms{$config.site_url}{pigcms{:U('Mall/detail', array('goods_id' => $now_goods['goods_id']))}";
    var app_version = {pigcms{$app_version|intval=###};
    if("{pigcms{$app_browser_type}" == 'android'){
		if(app_version >= 1400){
			var android_shareLifePass = {imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
			android_shareLifePass.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
			if(android_shareLifePass.share_wx == 'wxapp'){
				android_shareLifePass.share_wx_info = {userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:share_pic, title:desc};
			}
			window.lifepasslogin.shareLifePassNew(JSON.stringify(android_shareLifePass));
		}else{
			window.lifepasslogin.shareLifePass(name,desc,share_pic,share_url);
		}
    } else {
		var ios_shareLifePass = {name:'shareLifePass', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
		ios_shareLifePass.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
		if(ios_shareLifePass.share_wx == 'wxapp'){
			ios_shareLifePass.share_wx_info = JSON.stringify({userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:share_pic, title:desc});
        }
		iosCommon.iosFunction('shareLifePass', ios_shareLifePass);
    }
    /*点击分享事件*/
    function shareFriend(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            var ios_shareWeChat = {name:'shareWeChat', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
			ios_shareWeChat.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
			if(ios_shareWeChat.share_wx == 'wxapp'){
				ios_shareWeChat.share_wx_info = JSON.stringify({userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:share_pic, title:desc});
            }
			iosCommon.iosFunction('shareWeChat', ios_shareWeChat);
        }else{
			if(app_version >= 1400){
				var android_shareWeChat = {imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
				android_shareWeChat.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
				if(android_shareWeChat.share_wx == 'wxapp'){
					android_shareWeChat.share_wx_info = {userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(location.href), image:share_pic, title:desc};
					window.lifepasslogin.shareWeChatNew(JSON.stringify(android_shareWeChat));
				}else{
					window.lifepasslogin.shareWeChat(name,desc,share_pic,share_url);
				}	
			}else{
				window.lifepasslogin.shareWeChat(name,desc,share_pic,share_url);
			}
        }
    }
</script>
</if>
<!-- 展示优惠信息 -->
<script type="text/javascript">
var coupon_num=$('#coupon_box').find('li').length;
if(coupon_num>0){
    var wh=$(window).height();
    $('#coupon_box').find('ul').css({'height':wh*0.5,'overflow-y':'scroll'});
}
</script>
<!-- 优惠券领取start -->
<script>
<if condition="!$is_wexin_browser">
    var is_wexin_browser = false;
<else />
var is_wexin_browser = true;
</if>
</script>
<script type="text/javascript">
var is_new =Number("{pigcms{$isnew}");
</script>
<script src="{pigcms{$static_path}mall/js/receive_card.js?v==1"></script>
        <!-- 优惠券领取end -->
<script>
var first_rate = "{pigcms{$spread_rate['first_rate']}";
var is_begin_seckill_price="{pigcms{$now_goods.is_begin_seckill_price}";
var is_seckill_price="{pigcms{$now_goods.is_seckill_price}";
var View={
    init:function(){
        this.initDetail();
        this.window_scroll();
        this.initClick();
        this.showStar();
    },
    setting:{
        video:null,
        model:0,//初始化
        video_id:false,
        video_end:false,
        video_load:false,
        video_play:false,
        is_frist:false
    },
    initDetail:function(){
        var _self=this;
        var wh=$(window).width();
        var h=$(window).height();
        if($(".content_video").size()!=0){
            $(".content_video").css({
                'width':wh+'px',
                'height':h*0.299+'px'
            })


        }
        $(".swiper-container110").css("height",wh+'px')
        $(".swiper-container110 .swiper-slide .h100").css("height",wh+'px')
        var myswiper = new Swiper('.swiper-container110', {
            pagination: '.swiper-container110 .swiper-pagination',
            paginationClickable :true,
            autoplay :false,
            autoplayDisableOnInteraction : true,
            loop: false,
            video:$("#my_video").length>0?true:false,
            parent:'.swiper-container110',
            paginationType:'fraction',
            onInit:function(){
                //初始化视频
                if($("#my_video").length>0){
                    var w=$(window).width()
                    videojs("my_video").ready(function(){
                        _self.setting.video = this;
                        _self.setting.video.width(w);
                        var $cideos=document.getElementsByClassName('swiper-pagination-video')[0];
                        $cideos.style.display='block'
                        if($(".swiper-pagination-video").size>0){
                            $(".swiper-pagination-video").show()

                        }
                        //_self.setting.video .play();
                        play_ready()
                    });
                    var play_ready=function(){
                        //播放开始
                        _self.setting.video.on("play",function(){
                            _self.setting.video_play=true;
                            _self.setting.video_id=true;
                            _self.setting.video_load=false;
                                $(".swiper-pagination-video").hide()

                        });
                        //播放结束
                        _self.setting.video.on("ended",function(){
                            _self.setting.video_end=true;
                            _self.setting.video.currentTime(0);
                            _self.setting.is_frist=true
                            _self.setting.video.load();
                           $(".swiper-pagination-video").show()
                            _self.setting.video_load=false;
                            _self.setting.video_play=true;
                        })
                        //暂停
                        _self.setting.video.on("pause",function(){


                            _self.setting.video_load=true;
                        })
                        //加载时
                        _self.setting.video.on("loadstart",function(){

                        })



                        //离开全屏时
                        _self.setting.video.on("enterFullScreen",function(){
                            //获取播放进度
                            var whereYouAt = _self.setting.video.currentTime();
                            if(whereYouAt!=0){
                                _self.setting.video.play();
                            }
                        })
                    }

                }
            },
            onSlideChangeEnd:function(e){
                var n=$(".swiper-container110 .swiper-pagination span").length;
                console.log(e.activeIndex,n)

                if(e.activeIndex==0){
                    if(_self.setting.video!=null){
                        if(_self.setting.video_load && _self.setting.video_play){
                            _self.setting.video.pause();
                            $(".swiper-pagination-video").hide();

                        }else{

                            $(".swiper-pagination-video").show();

                        }

                    }

                    $(".paginationVideo").hide();
                    $(".video_content_modal").hide();
                    $(".video_modal").hide();
                    if($(".paginationLength").size()>0 && $("#my_video").length>0){
                        $(".paginationLength").hide()
                    }
                }else{
                    if(_self.setting.video!=null){
                        _self.setting.video.pause();
                    }
                   $(".swiper-pagination-video").show()
                    $(".paginationVideo").show();
                    $(".video_content_modal").show();
                    $(".video_modal").hide();
                }
            }
        });
        APP.timer({
            time: '{pigcms{$now_goods.time_clock|default=0}'*1*1000,
            onBackTime:function(d,h,m,s){
                $(".joind").html(d[0]);
                $(".joinh").html(d[1]);
                $(".joinm").html(d[2]);
                $(".joins").html(d[3]);
                if(d[0]*1==0 && d[1]*1==0 && d[2]*1==0 && d[3]*1==0 && (is_begin_seckill_price || is_seckill_price)){
                    window.location.reload();
                }
            }
        });
    },
    showStar: function() {
        $('.commentDetail .comment_item .star .p').width()

        $(".commentDetail").find(".comment_item").each(function(){
            var tip = $(this).find('.star p').attr('tip')
            if (tip) {
                $(this).find('.star p').width( tip * 16)
            }
        })
    },
    videoLoad:function(){
        $(".swiper-pagination-video").show()
        this.setting.video_load=false;
    },
    window_scroll:function(){
        $(window).on("scroll", function(){

            if ($(window).scrollTop() > 45 ) {
                $(".detail_header").addClass('active')
            } else {
                $(".detail_header").removeClass('active')
            }

            if( $(window).scrollTop() < $(".commentDetail").offset().top - 45){
                $(".detail_header span").removeClass("color_red")
                $(".detail_header span").eq(0).addClass("color_red")
            }

            if( $(window).scrollTop() >= $(".commentDetail").offset().top - 45){
                $(".detail_header span").removeClass("color_red")
                $(".detail_header span").eq(1).addClass("color_red")
            }

            if( $(window).scrollTop() >= $(".shopDetail").offset().top - 45 ){
                $(".detail_header span").removeClass("color_red")
                $(".detail_header span").eq(2).addClass("color_red")
            }
        })
    },
    initClick:function(){
        var wh=$(window).height();
        var _self=this;
        $(".coupon .ul").css({
            'overflow-y':'scroll',
            'max-height':(wh*0.75)+'px'
        })

        $(document).on("click",'.coupon_click',function(){
            $(".coupon>div").addClass("none")
            $(".coupon>div").eq(0).removeClass("none")
            $(".coupon").addClass("active")
            $(".coupon_mask").show()
        });
        $(document).on("click",'.activity_chuxiao',function(){
            $(".coupon>div").addClass("none")
            $(".coupon>div").eq(1).removeClass("none")
            $(".coupon").addClass("active")
            $(".coupon_mask").show()
        });
        $(document).on("click",'.coupon_mask,.del_enshrine',function(e){
            $(".coupon").removeClass("active")
            $(".coupon_mask").hide();
            $("#enshrine").addClass('none')
            $(".guide").addClass('none')
            $(".is_yongjin").addClass('none')
        });
        $(document).on("click",'.coupon_mask_close',function(){
            $(".coupon").removeClass("active")
            $(".coupon_mask").hide()
        });
        $(document).on("click",'.card_spans,.score_login',function(){
            location.href = $(this).data('url');
        });
        $(document).on("click",'.coupon_see',function(){
            var c=$(this).data('class');
            if($(this).find(".choose_juan").hasClass("choose_juan_all")){
                $("."+c).slideUp();
                $(this).find(".choose_juan").removeClass("choose_juan_all")

            }else{
                $("."+c).slideDown();
                $(this).find(".choose_juan").addClass("choose_juan_all")
            }

        });
        $(document).on('click','.detail_header span',function(){
            $(".detail_header span").removeClass('color_red')
            $(this).addClass('color_red')
            var id = $(this).attr('id')

            if (id) {
                $(document).scrollTop($('.' + id).offset().top - 45);
            } else {
                $(document).scrollTop(0);
            }

        })
        $(document).on('click','.icon_del',function(){
            $(".coupon_mask").trigger("click")

        })
        $(document).on('click','.video_play',function(e){
            e.stopPropagation();
            e.preventDefault();
            $(".swiper-pagination-video").hide()
            $(".paginationVideo").hide();
            $(".video_content_modal").hide();
            $(".video_modal").hide();
            if($(".paginationLength").size()>0 && $("#my_video").length>0){
                $(".paginationLength").hide()
            }

            _self.setting.video_id=true;
            _self.setting.video.play();


        });
        $(document).on('click','.video_back',function(e){
            e.stopPropagation();
            e.preventDefault();
            $(".swiper-pagination-video").show()
            $(".paginationVideo").show();
            $(".video_content_modal").show();
            $(".video_modal").hide();
            _self.setting.video_id=false
        });
        $(document).on('click','.video_content_modal',function(){
            if(_self.setting.video_id ||  _self.setting.load){
                _self.setting.video.play();
            }else{
                APP.wifi({
                    callback:function(type){
                        if(type){
                            $(".swiper-pagination-video").hide()
                            $(".paginationVideo").hide();
                            $(".video_content_modal").hide();
                            $(".video_modal").hide();
                            if($(".paginationLength").size()>0 && $("#my_video").length>0){
                                $(".paginationLength").hide()
                            }
                            _self.setting.video.play();
                            _self.setting.video_id=true
                        }else{
                            $(".swiper-pagination-video").hide()
                            $(".paginationVideo").hide();
                            $(".video_content_modal").hide();
                            $(".video_modal").show();
                        }
                    }
                });
            }
        });

    }
}
window.View=View;
View.init();
</script>
<script type="text/html" id='buybox'>
    <& for(var i=0;i<_this.length;i++){ &>
    <& var _item=_this[i]; &>
        <div class="swiper-slide flex swiper-no-swiping">
            <img src="<&= _item.avatar &>" />
            <div class="flex_one"><span><&= _item.nickname &>刚刚购买了这个商品</span></div>
        </div>
    <& } &>
</script>
<script type="text/javascript">
$(function(){
    var src="{pigcms{:U('Mall/get_more_buy')}";
    var goods_id={pigcms{$now_goods.goods_id};
    function get_more_buy(){
        $.post(src,{goods_id:goods_id},function(res){
            if(res=='null' || res.length==0 || $.trim(res)=='[]'){
                $('.danmu').hide();
                return false;
            }else{
                $('.danmu').show();
                if(typeof(res)=='string'){
                    res=$.parseJSON(res);
                }

                var model = Simplite.getTemplate('buybox');
                var html = Simplite.compile(model)(res);
                $('.swiper-container111 .swiper-wrapper').html(html);
                /*新增用户参与下单信息*/
                var mySwiperBuy = new Swiper('.swiper-container111',{
                    direction : 'vertical',
                    autoplay: 3000,
                    loop:true,
                    noSwiping : true,
                    on:{
                        progress: function(progress){
                            console.log(progress);
                        },
                    },
                });
            }
        });
    }
    get_more_buy();
    /*setInterval(function(){
        get_more_buy();
    },10000);*/
    var winndow_h=$(window).width();
    var is_all=$(".all_friend").width();
    var  is_share_other_friend=winndow_h-is_all-24;
    console.log(is_all,is_share_other_friend)
    var is_left=0,is_z_index=10,is_show_friend=true;
    for(var e=0;e<$(".is_jion_friend .flex_one img").length;e++) {
        if(is_show_friend){
            $(".is_jion_friend .flex_one img").eq(e).css({
                'left':is_left+'px',
                'z-index':is_z_index
            });
            is_z_index--;
            is_left+=20;
            console.log(is_share_other_friend,is_left)
            if(is_share_other_friend-40-is_left>50) {

            }else{
                is_show_friend=false;
            }
        }else{
            $(".noMore").css({
                'left':(is_left+5)+'px',
                'z-index':(is_z_index-1)
            });
            $(".is_jion_friend img").eq(e).css({
                'left':'-300px',
                'z-index':-1,
                'width':'0px',
                'height':'0px'
            });
        }
    }
});
</script>
</html>