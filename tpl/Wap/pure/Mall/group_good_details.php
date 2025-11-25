<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1"/>
    <meta http-equiv="Cache-Control" content="no-cache"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta charset="utf-8" />
    <title>拼团详情</title>
    <link href="{pigcms{$static_path}mall/css/video-js.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet">
    <link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/shop_manger_penci.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/collage_list_detail.css" rel="stylesheet">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script>
        var goods_list = '{pigcms{$goods_detail}';
        var reply_url = "{pigcms{:U('Mall/reply', array('goods_id' => $now_goods['goods_id']))}";
        var save_url = "{pigcms{:U('Mall/confirm_order', array('store_id' => $store['store_id']))}";
        //获取商品评论信息
        var shopReplyUrl2="{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$store['store_id']}&goods_id={pigcms{$now_goods['goods_id']}";
        var open_extra_price =Number("{pigcms{$config.open_extra_price}"); 
        var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
        var goods_price = "{pigcms{$now_goods['price']}";
        var book_id = "{pigcms{$book_act['id']}";
    </script>
    <script>
        var ajax_cart_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart";
        var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=ajax_cart_save";
        var goods_cart = '{pigcms{$goods_cart}';
        //获取小程序分享信息地址
        var getShareUrl = "{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=share&goods_id={pigcms{$now_goods['goods_id']}&group_id={pigcms{$group_info.id}";
    </script>
    <style type="text/css">
        #pageLoadTip{display:none !important;}
        .detail_header.active{
            height: 45px;
        }
        .swiper-container2.swiper-slide>div {
            width: 100%;
        }
        .enshrine:after{
            position: absolute;
            content: '';
            height: 80%;
            width: 2px;
            border-left: 1px dashed #ddd;
            top: 5px;
            left: 5px;
        }
    </style>
</head>
<body >
<!-- 返回顶部按钮 -->
<div id='toTop' class="cursor"><img src="{pigcms{$static_path}mall/images/go_top.png" class="w100 h100" /></div>
<div class="bg_f detail_header align_center">
    <span class="color_red align_center font_size14 active cursor"><i class="icon iconfont">&#xe633;</i>商品</span>
    <span id='commentDetail' class="align_center font_size14 cursor"><i class="icon iconfont">&#xe633;</i>评价</span>
    <span id='shopDetail' class="align_center font_size14 cursor" ><i class="icon iconfont">&#xe633;</i>详情</span>
</div>
<div class="danmu">
    <div class="swiper-container swiper-container111" >
        <div class="swiper-wrapper">
            
        </div>
    </div>
</div>
<div class="head title_data" data-is_seckill="{pigcms{$now_goods['is_seckill_price']|intval}" data-pack="{pigcms{$now_goods['packing_charge']|floatval}" data-price="{pigcms{$now_goods['price']|floatval}" data-name="{pigcms{$now_goods['name']}" data-stock="{pigcms{$now_goods['stock_num']}" data-goods_id="{pigcms{$now_goods['goods_id']}" data-store_id="{pigcms{$store['store_id']}" data-extra_pay_price="{pigcms{$now_goods['extra_pay_price']}" data-max_num="{pigcms{$now_goods['max_num']}" data-min_num="{pigcms{$now_goods['min_num']}" data-limit_type="{pigcms{$now_goods['limit_type']}" data-unit="{pigcms{$now_goods['unit']}" data-o_price="{pigcms{$now_goods['old_price']|floatval}">
    <div class="homepage closeItem" id="listBanner">
        <div class="swiper-container swiper-container2" id='scroll_video'>
            <div class="swiper-wrapper pics">
                <!-- 商品视频 -->
                <notempty name='now_goods.video_url'>
                    <div class="swiper-slide w100 play_video cursor" >
                        <div class="video_content_modal cursor"></div>
                        <video id="my_video"  class="video-js vjs-default-skin vjs-big-play-centered h100 w100" style="object-fit: fill" webkit-playsinline='true' x-webkit-airplay="true" x5-video-player-type="h5" x5-video-player-fullscreen="true" playsinline='true'  controls   preload="auto" width="100%" height="100%"  poster="{pigcms{$now_goods.pic_arr.0.url}" data-setup="{}">
                            <source src="{pigcms{:C('config.site_url')}/{pigcms{$now_goods.video_url}"  type="video/mp4" />
                        </video>
                    </div>
                </notempty>
                <volist name="now_goods['pic_arr']" id="vo">
                    <div class="swiper-slide " >
                        <img src="{pigcms{$vo['url']}"  data-pics="{pigcms{$vo['url']}" data-index="{pigcms{$key}"  class="h100 w100 cursor" />
                    </div>
                </volist>
            </div>
            <div class="swiper-pagination <notempty name='now_goods.video_url'>  swiper-pagination-video  </notempty>"></div>
        </div>
    </div>
    <div class="flex grad" style="height: 50px;  line-height: 15px;" id="count_down">
       <div class="flex_one flex pl10">
           <h4 class="ui-nowrap  flex border_f " style="height:16px;border-radius:2px;">
               <div style="background: #fff;color: #fd5306;font-size: 20px;"><i class="icon iconfont">&#xe718;</i></div>
               <div class="color_f font_size10" style="padding: 0px 3px;">{pigcms{$group_info.complete_num}人拼</div>
           </h4>
           <div class="flex_one pl10">
               <span class="font_size24 color_f">{pigcms{$config['Currency_symbol']}{pigcms{$group_info.group_price}</span>
               <span class="font_size14 rel" style="text-decoration: line-through;color: rgba(255, 255, 255, 0.7);font-weight: bold;">{pigcms{$config['Currency_symbol']}{pigcms{$now_goods.price}</span>
           </div>

        </div>
        <div class=" align_right pr10">
            <eq name="group_info.act_status" value='1'>
                <p class="font_size12 align_left" style="color:#ad1e00">距活动结束还剩：</p>
                <span class="font_size13 clock" data-clock="{pigcms{$group_info.time_clock}" data-type='1' class='clock'>
                    <span class="color_f time joind d font_b">00</span>
                    <span style="color:#ad1e00">天</span>
                    <span class="color_f time joinh h font_b">00</span><span class="dian font_b">:</span>
                    <span class="color_f time joinm m font_b">00</span><span class="dian font_b">:</span>
                    <span class="time color_f joins s font_b">00</span>
                </span>
            </eq>
        </div>
    </div>
</div>
<div class="content " style="padding-bottom:48px">
    <div class="content1 ">
        <div class="demo-block bg_f pd10 demo-block01 mb_10">
            <div class="flex ">
                <div class="flex_one flex flex_star">
                    <div class="flex_one">
                        <h4 class="ui-nowrap-multi-del">
                            <eq name='now_goods.is_self' value='1'>
                                <i class="self_tag" style='vertical-align: text-bottom;'>自营</i>    
                            </eq>
                            <eq name="group_info.join_type" value='2'><span class='invite_team'>邀新团</span></eq>{pigcms{$now_goods.name}
                        </h4>
                        <p class="ui-nowrap-multi color_9 font_size14">
                            {pigcms{$now_goods.describe}
                        </p>
                    </div>

                    <div class="align_center enshrine cursor rel pl10">
                        <i class="iconfont icon-share_reward" style="font-size:24px"> </i>
                        <p class="color_7 font_size12" style="margin-top:-7px">分享有礼</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="demo-block mb_10">
            <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
                <li >
                    <eq name="group_info.join_type" value='2'>
                        <div class="ui-list-info cursor standard invite_box">
                            <span>邀新团</span>
                            <i class='dian'></i>
                            <span>所有人均可参团，但仅限店铺新用户参团</span>
                        </div>
                    <else/>
                        <div class="ui-list-info cursor standard">
                            <h4 class="ui-nowrap font_size14">拼团玩法</h4>
                            <div class="ui-txt-info font_size14">详情规则</div>
                        </div>
                    </eq>
                </li>
                <div class="ui-border-t font_size14 mg0_10 flex flex_justify ping_game standard cursor" style="padding: 11px 0;">
                    <div><span class="one">1</span>开团/参团</div>
                    <span>···</span>
                    <div><span class="one">2</span>邀请好友</div>
                    <span>···</span>
                    <div><span class="one">3</span>满员发货(不满退款)</div>
                </div>
            </ul>
        </div>

        <div class="demo-item demo-block06 mb_10">
            <!-- <p class="demo-desc color_fb">小伙伴们正在开团，可直接开团</p> -->
            <notempty name="team_data.list">
                <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
                    <div class="ui-list-info cursor ">
                        <h4 class="ui-nowrap font_size14 mg0_10">{pigcms{$team_data.team_count}人在拼团，可直接参与</h4>
                    </div>
                </ul>
            </notempty>
            <div class="demo-block bg_f pr10 pl10">
                <ul class="ui-list ui-list-single ui-list-link   mb_10" >
                    <volist name='team_data.list' id='vo'>
                        <div class="ui-list-info flex_one ui-border-t pd0" >
                            <div class="ui-list-thumb-s" style="border-radius: 50%;overflow: hidden;margin-right: 8px;">
                                <span ><img src="{pigcms{$vo.avatar|default=$static_path.'images/new_my/pic-default.png'}" class="w100 h100"/></span>
                            </div>
                            <h4 class="ui-nowrap font_size14">{pigcms{$vo.nickname|default='***'}</h4>
                            <div class="ui-txt-info flex">
                                <div class="flex flex_col flex_one">
                                    <p class="color_3">还差<span class="color_red">{pigcms{$vo.need_num}人</span>拼成</p>
                                    <span class="color_9 font_size12 clock" data-clock="{pigcms{$vo.time_clock}" data-type='2'>剩余
                                    <b class='h'></b>:
                                    <b class='m'></b>:
                                    <b class='s'></b>
                                    </span>
                                </div>
                                <div class="pl10 " >
                                    <if condition="$group_info['limit_num'] gt 0 && $group_info['group_buy_num'] egt $group_info['limit_num']">
                                        <button class="ui-btn ui-btn-primary" style="padding:0px 5px 0px 5px;background-color:#ccc;">
                                            去参团<i class="icon iconfont" style="vertical-align: middle;font-size: 17px;">&#xe616;</i>
                                        </button>
                                    <else/>
                                        <a href="{pigcms{:U('Mall/group_team_detail',array('group_id'=>$group_id,'team_id'=>$vo['id']))}">
                                            <button class="ui-btn ui-btn-primary" style="padding:0px 5px 0px 5px">
                                                去参团<i class="icon iconfont" style="vertical-align: middle;font-size: 17px;">&#xe616;</i>
                                            </button>
                                        </a>
                                    </if>                                    
                                </div>
                            </div>
                        </div>
                    </volist>
                </ul>
            </div>            
        </div>
    </div>
    
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

    <div class="demo-block shopDetail" >
        <ul class="ui-list ui-list-single ui-list-link  mb_0 mg_t10">
            <li class="after">
                <div class="ui-list-info">
                    <h4 class="ui-nowrap"><span class="rel " style="padding-left: 5px;border-left: 3px solid #f23030;">商品详情</span></h4>
                </div>
            </li>
        </ul>
        <div class="goods_detail ui-border-t pd10_0 content_box">
            {pigcms{$now_goods['des']}
        </div>
    </div>    
</div>

<div class="footerMenu wap footer">
    <if condition="$group_info['limit_num'] gt 0 && $group_info['group_buy_num'] egt $group_info['limit_num']">
        <p class="limit_tips">您购买的商品已达上限啦，去首页拼其他商品吧</p>
    </if>
    <ul>
        <li style="width:15%">
            <a href="{pigcms{:U('Mall/store',array('store_id'=>$store['store_id']))}" class="color_7"><em class="shop"></em><p>进店</p></a>
        </li>
        <li style="width:15%;" class="contactlx">
            <a href="javascript:;" class="color_7"><em class="kf"></em><p>客服</p></a>
        </li>      
        <div class="btn_box flex" style="height: 100%;">
            <eq name="group_info.act_status" value='0'>
                <!-- 活动未开始 -->
                <li style="width:100%" class="bg_c">
                    <a href="javascript:;" class="show_buy"><p class="font_size16" style="line-height:46px;">活动未开始</p></a>
                </li>
            <elseif condition="$group_info['act_status'] eq 2"/>
                <!-- 活动已结束 -->
                <li style="width:100%" class="bg_c">
                    <a href="javascript:;" class="show_buy"><p class="font_size16" style="line-height:46px;">活动已结束</p></a>
                </li>
            <elseif condition="$group_info['has_stock'] eq 0"/>
                <li style="width:100%" class="bg_c">
                    <a href="javascript:;" class="show_buy"><p class="font_size16" style="line-height:46px;">已拼完</p></a>
                </li>
			<elseif condition="$now_goods['stock_num'] eq 0"/>
				<li style="width:100%" class="bg_c">
                    <a href="javascript:;" class="show_buy"><p class="font_size16" style="line-height:46px;">商品已售罄</p></a>
                </li>
            <else/>
                <li style="width:40%" class="bg_pink">
                    <a href="javascript:;" class="show_buy Choice_n" data-store_id="{pigcms{$store['store_id']}" data-type="buy" data-yuan_price='yuan_price'><span class="font_size16">{pigcms{$config['Currency_symbol']}{pigcms{$now_goods.price}</span><p>单独购买</p></a>
                </li>
                <if condition="$group_info['limit_num'] gt 0 && $group_info['group_buy_num'] egt $group_info['limit_num']">
                    <li style="width:60%"  class="bg_c">
                        <a href="javascript:;"  class="show_buy" data-store_id="{pigcms{$store['store_id']}" data-type="group_team" data-group_id="{pigcms{$now_goods.group_id}"><span class="font_size16">{pigcms{$config['Currency_symbol']}{pigcms{$group_info.group_price}</span><p><p>我要开团</p></a>
                    </li>
                <else/>
                    <li style="width:60%"  class="bg_red2">
                        <a href="javascript:;"  class="show_buy Choice_n" data-store_id="{pigcms{$store['store_id']}" data-type="group_team" data-group_id="{pigcms{$now_goods.group_id}"><span class="font_size16">{pigcms{$config['Currency_symbol']}{pigcms{$group_info.group_price}</span><p><p>我要开团</p></a>
                    </li>
                </if>
            </eq>            
        </div>
    </ul>
</div>

<!-- 规格选项 import -->
<include file="Speci"/>

<div class="show ui_bottom ping_game" style="display:none" id="ping_game">
    <div class="model hide_modal"></div>
    <div class="demo-block specifications pd10 bg_f " >
        <div class="rel">
            <div class="pt10">
                <h4 class="align_center" data-i="{pigcms{$i=1}">拼团玩法</h4>
                <eq name='group_info.join_type' value='2'>
                    <p><span class="one">{pigcms{$i++}</span>什么是邀新团？</p>
                    <span class="color_7 font_size12">即任意用户可开团成为团长，但仅限店铺新用户参团</span><br><br>
                </eq>
                <p><span class="one">{pigcms{$i++}</span>拼团活动有效期</p>
                <span class="color_7 font_size12">即团长可发起拼团的时间段，可在拼团详情页查看该团活动倒计时</span><br><br>
                <p><span class="one">{pigcms{$i++}</span>成团有效期</p>
                <span class="color_7 font_size12">即团长发起拼团后与其他人组团的时间，开团后或参团时可查看具体成团有效期倒计时</span><br><br>
                <p><span class="one">{pigcms{$i++}</span>拼团成功</p>
                <span class="color_7 font_size12">成团有效期内，邀请参团好友达到成团数量后拼团成功，商家进入备货流程</span><br><br>
				<p><span class="one">{pigcms{$i++}</span>拼团失败</p>
                <span class="color_7 font_size12">成团有效期内，邀请参团好友未达到成团数量；或超过成团有效期，均为拼团失败，系统将在一定时间内退款</span><br><br>
            </div>
            <div class="btn_confirm">
                <button class="ui-btn-lg ui-btn-primary hide_modal">确定</button>
            </div>
        </div>
    </div>
</div>
</div>
<if condition="$group_info['has_stock'] eq 0">
<div class="stock_tips">
    <div class="tip_box">
        <div class="box">
            <p>本商品库存已拼完</p>
            <p>请查看更多商品</p>
            <a href="{pigcms{:U('Mall/index')}" class="btn">查看更多商品</a>
        </div>
        <span class='close cursor'></span>
    </div>          
</div>
</if>

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
                    2、您的二级推广用户消费后，您将获得{pigcms{$config['Currency_symbol']}&nbsp;<span class='color_red font_size22 font_b'>{pigcms{$spread_rate['second_money']}</span>&nbsp;{pigcms{$config['Currency_txt']}
                </div>
            </if>
            <if condition="$spread_rate['third_money'] gt 0">
                <div class="font_size14 yongjin">
                    3、您的三级推广用户消费后，您将获得{pigcms{$config['Currency_symbol']}&nbsp;<span class='color_red font_size22 font_b'>{pigcms{$spread_rate['third_money']}</span>&nbsp;{pigcms{$config['Currency_txt']}
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
<script>var $home=false</script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js" type="text/javascript" ></script>
<script src="{pigcms{$static_path}mall/js/iscroll.min.js" type="text/javascript" ></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/swiper.js"></script>
<script src="{pigcms{$static_path}mall/js/video.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/mallbuygoods.js?12"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>

{pigcms{$shareScript}
<!-- 微信分享 -->
<script type="text/javascript">
window.shareData = {
    "moduleName":"Mall",
    "moduleID":"0",
    "imgUrl": "{pigcms{$now_goods['pic_arr'][0]['url']}",
    "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/detail', array('goods_id' => $now_goods['goods_id'],'group_id'=>$group_info['id']))}",
    "tTitle": "邀您参与{pigcms{$group_info.group_price}{pigcms{$config['Currency_txt']}拼团【{pigcms{$now_goods['name']}】",
    "tContent": "每件仅需{pigcms{$group_info.group_price}{pigcms{$config['Currency_txt']}，{pigcms{$group_info.complete_num}人成团，点击立即参与"
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
        $(".is_yongjin>div").eq(0).css('padding-top','25%');
        $(".is_yongjin").removeClass('none');
        $(".mask").show();
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
<script type="text/javascript">
var ua = window.navigator.userAgent.toLowerCase();
var is_weixin=false;
var is_mini=false;
var share_clock=1;
/*是否在app端*/
var is_app={pigcms{$is_app_browser|default=0};
var res_url='';
var webview=function(){
    wx.miniProgram.getEnv(function(res) {
        var ss=JSON.stringify(res);
        share_clock=0;
        if (res.miniprogram) {
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
                    res_url= '/pages/index/share?title='+encodeURIComponent(res.title)+'&shareTitle='+encodeURIComponent(res.share_title)+'&shareImg='+encodeURIComponent(res.friend_img)+'&sharePath='+encodeURIComponent(res.wxapp_path)+'&bg='
                            + encodeURIComponent(res.good_image)+'&poster='+encodeURIComponent(res.mall_img);
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
        });
    }else{
        if(is_weixin){
            //微信端
            $(".guide").removeClass('none')
        }else if(is_app){
            shareFriend();
        }else{  //h5分享  
            wap_share_tip(1);
        }
    }
});
$(document).on('click', '.enshrine,.more_buy', function(){
        if(is_weixin){
            //微信端
            if($(".yongjin").size()>0){
                $(".is_yongjin>div").eq(0).css('padding-top','0px');
                $(".is_yongjin").removeClass('none');
            }
            $(".guide").removeClass('none');
            $(".coupon_mask").show();
            $("#enshrine").removeClass('none');
            $(".mask").show();
        }else{
            if(is_mini || is_app){
                if($(".yongjin").size()>0){
                    $(".is_yongjin>div").eq(0).css('padding-top','25%');
                    $(".is_yongjin").removeClass('none');
                    $(".mask").show();
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
            }else{  //h5分享  
                wap_share_tip(0);
            }
        }
});
$('#enshrine').bind("touchmove",function(e){
    e.preventDefault();
});
</script>
<script>
    var nav_click = false
    var View={
        init:function(){
            this.initDetail();
            this.window_scroll();
            this.initClick();
            this.time();
            this.showStar();

        },
        setting:{
            is_chccked:false,
            video:null,
            video_id:false,
            video_end:false,
            video_load:false,
            video_play:false
        },
        initDetail:function(){
            var _self=this;
            var wh=$(window).width();
            $(".swiper-container2 .swiper-slide .h100").css("height",wh+'px')
            $(".swiper-container2").css("height",wh+'px');
            var myswiper = new Swiper('.swiper-container2', {
                pagination: '.swiper-container2 .swiper-pagination',
                paginationClickable :true,
                autoplay :false,
                autoplayDisableOnInteraction : true,
                loop: false,
                video:$("#my_video").length>0?true:false,
                parent:'.swiper-container2',
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
                                var $cideos=document.getElementsByClassName('swiper-pagination-video')[0];
                                $cideos.style.display='none'
                                if($(".swiper-pagination-video").size>0){
                                    $(".swiper-pagination-video").hide()

                                }
                            });
                            //播放结束
                            _self.setting.video.on("ended",function(){
                                _self.setting.video_end=true;
                                _self.setting.video.currentTime(0);
                                _self.setting.video.load();
                                var $cideos=document.getElementsByClassName('swiper-pagination-video')[0];
                                $cideos.style.display='block'
                                if($(".swiper-pagination-video").size>0){
                                    $(".swiper-pagination-video").show()

                                }
                                _self.setting.video_load=false;
                                _self.setting.video_play=true;
                            })
                            //暂停
                            _self.setting.video.on("pause",function(){
                                _self.setting.video_load=true;
                            })

                            //离开全屏时
                            _self.setting.video.on("enterFullScreen",function(){
                                //获取播放进度
                                var whereYouAt = _self.setting.video.currentTime();
                                if(whereYouAt!=0){
                                    _self.setting.video.play();
                                }
                            })
                            //加载时
                            _self.setting.video.on("loadstart",function(){
                            
                            })
                        }
                    }
                },
                onSlideChangeEnd:function(e){
                    console.log(e)
                    var n=$(".swiper-container2 .swiper-pagination span").length;
                    if((e.activeIndex==0) && _self.setting.video_id){
                        if(_self.setting.video!=null){
                            if(_self.setting.video_load && _self.setting.video_play){
                                _self.setting.video.pause();
                                $(".swiper-pagination-video").hide();

                            }else{
                                $(".swiper-pagination-video").show();

                            }

                        }
                        $(".swiper-pagination-video").hide();
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


            // 底部右边按钮的宽度
            var btnWidth = 0
            $('.footerMenu ul>li').each(function(){
                btnWidth = btnWidth + $(this).width()
            })
            $('footerMenu ul .btn_box').css('width', 'calc(100% - '+btnWidth+'px)');


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

                if (nav_click == true) {
                    nav_click = false
                    return false
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

        showStar: function() {
            $('.commentDetail .comment_item .star .p').width()

            $(".commentDetail").find(".comment_item").each(function(){
                var tip = $(this).find('.star p').attr('tip')
                if (tip) {
                    $(this).find('.star p').width( tip * 16)
                }
            })
        },

        initClick:function(){
            var _self=this;
            $(document).on("click",'#bottom .product_name li',function(){
                _self.setting.is_chccked=true;
                if(_self.setting.is_chccked){
                    $("#bottom .btn_confirm button").addClass("ui-btn-primary").removeClass("ui-btn-danger").html('确定')
                }
                console.log($(this).attr("data-name"))
                $("#bottom li").removeClass("active").addClass("color_7");
                $(this).removeClass("color_7").addClass("active");
                $('#bottom .name').html($(this).attr("data-name"))
            })
            $(document).on("click",'#bottom .add',function(){

            })
            $(document).on("click",'#enshrine',function(){
                $(".mask").hide()
                $("#enshrine").addClass('none')
                $(".coupon").removeClass("active")
                $(".coupon_mask").hide();
                $(".guide").addClass('none')
                $(".model_button").addClass('none')
                $(".is_yongjin").addClass('none')
            })

            $(document).on("click",'.standard',function(){
                $("#ping_game").show();
                $("#ping_game>div").addClass("animate_top")
                setTimeout(function(){
                    $("#ping_game>div").removeClass("animate_top")
                },500)
            })
            $(document).on("click",'.ui_bottom .hide_modal',function(){
                $(".ui_bottom>div").addClass("animate_bottom")
                setTimeout(function(){
                    $(".ui_bottom>div").removeClass("animate_bottom");
                    $(".ui_bottom").hide();
                },500)
            })

            $(document).on('click','.detail_header span',function(){
                nav_click = true
                $(".detail_header span").removeClass('color_red')
                $(this).addClass('color_red')
                var id = $(this).attr('id')

                if (id) {
                    $(document).scrollTop($('.' + id).offset().top - 45);
                } else {
                    $(document).scrollTop(0);
                }                
            });
             //关闭弹窗
            $(document).on('click','.stock_tips .close',function(){
                $(this).parents('.stock_tips').remove();
            });
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
            $(document).on("click",'.coupon_mask,.del_enshrine',function(e){
                $(".coupon").removeClass("active")
                $(".coupon_mask").hide();
                $("#enshrine").addClass('none')
                $(".guide").addClass('none')
                $(".is_yongjin").addClass('none')
            });
        },
        time:function(){
            var timer=[];
            $('.clock').each(function(n,v){
                var obj=$(this);
                var clock=obj.data('clock');     
                var type=obj.data('type');
                countFunc(obj,clock,n,type);
                timer[n]=setInterval(function(){
                    clock--;
                    countFunc(obj,clock,n,type);
                },1000);
            });
            function countFunc(obj,time,n,type) {
                if(time>0){
                    if(type==1 && obj.find('.d').length){
                        var d=Math.floor(time/(24*60*60));
                        time=time-d*24*60*60;
                        if(d<=0){
                            obj.find('.d').next().remove();
                            obj.find('.d').remove();
                        }else{
                            d=checkTime(d);
                            obj.find('.d').text(d);    
                        }
                    }
                    var h=Math.floor(time/(60*60));
                    var m=Math.floor(time%(60*60)/60);
                    var s=Math.floor(time%60);
                    h=checkTime(h);
                    m=checkTime(m);
                    s=checkTime(s);
                    obj.find('.h').text(h);
                    obj.find('.m').text(m);
                    obj.find('.s').text(s);
                }else{
                    if(timer[n]){
                        clearInterval(timer[n]);    
                    }
                    obj.find('.d').text('00');
                    obj.find('.h').text('00');
                    obj.find('.m').text('00');
                    obj.find('.s').text('00');
                    
                }
            }
            function checkTime(i) { //将0-9的数字前面加上0，例1变为01
                if(i < 10) {
                    i = "0" + i;
                }
                return i;
            }
        }
    }
    window.View=View;
    View.init();
</script>
<!-- app分享 -->
<if condition="$is_app_browser">
<script type="text/javascript">    
    
    if("{pigcms{$app_browser_type}" == 'android'){
        window.lifepasslogin.shareLifePass(window.shareData.tTitle,window.shareData.tContent, window.shareData.imgUrl, window.shareData.sendFriendLink);
    } else {
        iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
    }
    function shareFriend(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            iosCommon.iosFunction('shareWeChat', {name:'shareWeChat', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }else{
            window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent, window.shareData.imgUrl, window.shareData.sendFriendLink);
        }
    }
</script>
</if>
<script type="text/html" id='buybox'>
    <& for(var i=0;i<_this.length;i++){ &>
    <& var _item=_this[i]; &>
        <div class="swiper-slide flex swiper-no-swiping">
            <img src="<&= _item.avatar &>" />
            <div class="flex_one"><span><&= _item.nickname &>刚刚正在拼团这个商品</span></div>
        </div>
    <& } &>
</script>
<script type="text/javascript">
$(function(){
    /*新增用户参与下单信息*/
    var src="{pigcms{:U('Mall/get_more_buy')}";
    var goods_id={pigcms{$now_goods.goods_id};
    function get_more_buy(){
        $.post(src,{goods_id:goods_id,goods_type:1},function(res){
            if(res=='null' || res.length==0){
                $('.danmu').hide();
		return false;
            }else{
                $('.danmu').show();
                res=$.parseJSON(res);
                var model = Simplite.getTemplate('buybox');
                var html = Simplite.compile(model)(res);
                $('.swiper-container111 .swiper-wrapper').html(html);
                /*新增用户参与下单信息*/
                var mySwiper = new Swiper('.swiper-container111',{
                    direction : 'vertical',
                    autoplay: 3000,
                    loop:true,
                    noSwiping : true,
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