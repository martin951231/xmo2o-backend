<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8">
    <title>砍价商品详情</title>
    <link href="{pigcms{$static_path}mall/css/video-js.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/shop_manger_penci.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/frozenui2.css?1"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
    <style>
        .mask {  position: fixed;  top: 0px; left: 0px;  background: rgba(0,0,0,0.7);  z-index: 888;  width: 100%;height:100%;  display: none;  }
        .enshrine:after{  position: absolute;  content: '';  height: 80%;  width: 2px;  border-left: 1px dashed #ddd;  top: 5px;  left: 5px;  }
        .footerMenu ul {  color: #fff;  display: block;  }
        .footerMenu ul li a{color: #fff;}
        .backTime{  bottom:0px;  z-index:100;  left:0px;  right:0px;  height:30px;  line-height:30px;  opacity: 0.8;  }
        .ui-progress {  height: 8px;overflow:visible;border-radius: 4px;overflow: hidden;}
        .ui-progress span.grad {background: -webkit-linear-gradient(left, #fd8945, #ff3440 );background: -o-linear-gradient(right, #fd8945, #ff3440 );background: -moz-linear-gradient(right, #fd8945, #ff3440 );background: linear-gradient(to right, #fd8945, #ff3440 );border-radius: 4px;}
        .now_money{border-radius:30px;padding:5px 9px;top:-10px}
        .content{padding-bottom:50px}
        .please_friend p:first-child{font-size: 14px;  padding-top: 3px;}
        .please_friend p:last-child{font-size: 12px;  margin-top: -3px;}
        .dian{color: #fff;}
        .ping_game .one{line-height: 16px;font-size: 12px;color: #fff;display: inline-block;background: #000;width: 15px;height: 15px;border-radius: 50%;text-align: center;margin:0 3px 3px 0;vertical-align: middle;}

        .count_down .time {line-height: 18px;background: #fa8332;text-align: center;height: 18px;width: 18px;display: inline-block;padding: 2px;border-radius: 2px;}
        .count_down .dian {color: #ad1e00;padding: 0px 2px 0px 6px;}
        .swiper-container2.swiper-slide>div {
            width: 100%;
        }
        #pageLoadTip{display:none !important;}
        .content_box img{width:100%}
        #my-video{width:100%}
        #gride {  position: fixed;     z-index: 20000; left: 0px;  top: 0px;  right: 0px;  bottom: 0px;  background: hsla(0,0%,7%,.7);  }
        #gride>div {  height: 100%;  background-size: 100%; background: url({pigcms{$static_path}mall/images/guide1.png) no-repeat;  width: 100%;  background-position: top;  }
        @media only screen and (device-width: 375px) and (device-height:812px) and (-webkit-device-pixel-ratio:3) {
            .content {
                padding-bottom:84px!important;
            }
        }
    </style>
    <script src="{pigcms{$static_path}mall/js/jquery.min.js" type="text/javascript" ></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script type="text/javascript">
        //获取小程序分享信息地址
        var getShareUrls = "{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=share&goods_id={pigcms{$now_goods['goods_id']}&bar_id={pigcms{$bar_info.id}";
        var getShareUrl = "{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=share&goods_id={pigcms{$now_goods['goods_id']}&bar_id={pigcms{$bar_info.id}&team_id={pigcms{$team_info.id}";
        //获取商品评论信息
        var shopReplyUrl2="{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$store['store_id']}&goods_id={pigcms{$now_goods['goods_id']}";
    </script>
</head>
<body>
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
<div class="head">
    <div class="homepage closeItem rel" id="listBanner">
        <div class="swiper-container swiper-container2" >
            <div class="swiper-wrapper pics">
                <!-- 商品视频 -->
                <notempty name='now_goods.video_url' >
                    <div class="swiper-slide w100 play_video cursor" >
                        <div class="video_content_modal cursor"></div>
                        <video id="my_video"  class="video-js vjs-default-skin vjs-big-play-centered h100 w100" controls  style="object-fit: fill" webkit-playsinline='true' x-webkit-airplay="true" x5-video-player-type="h5" x5-video-player-fullscreen="true" playsinline='true'   preload="auto" width="100%" height="100%"  poster="{pigcms{$now_goods.pic_arr.0.url}" data-setup="{}">
                            <source src="{pigcms{:C('config.site_url')}/{pigcms{$now_goods.video_url}"  type="video/mp4" />
                        </video>
                    </div>
                </notempty>
                <volist name="now_goods['pic_arr']" id="vo">
                    <div class="swiper-slide " >
                        <img src="{pigcms{$vo['url']}" data-index="{pigcms{$key}"  data-pics="{pigcms{$vo['url']}" width="100%" class="h100 w100 cursor" />
                    </div>
                </volist>
            </div>
            <div class="swiper-pagination <notempty name='now_goods.video_url'>  swiper-pagination-video  </notempty>"></div>
        </div>        
    </div>
</div>
<!--发起砍价结束时间-->
<if condition="($stock_num gt 0 or $stock_num==-1) and $team_info['bar_num'] gt 0">
    <div class="flex grad count_down"  id="count_down" style="height: 50px;  line-height: 15px;">
       <div class="flex_one flex pl10">
           <div class="flex_one color_f">
               <span class="font_size12">底价{pigcms{$config['Currency_symbol']}</span><span class="font_size24">{pigcms{$bar_info.floor_price}</span>
               <del style="font-size:15px;color:rgba(255, 255, 255, .7);">{pigcms{$config['Currency_symbol']}{pigcms{$bar_info.spec_price}</del>
           </div>

        </div>
        <div class=" align_right pr10">
            <p class="font_size12 align_left" style="color:#ad1e00">距砍价结束还剩：</p>
            <P data-clock="{pigcms{$team_info.time_clock}" data-type='1' class='clock'>
                <span class="font_size13">
                    <span class="color_f time joind d font_b">00</span>
                    <span style="color:#ad1e00">天</span>
                    <span class="color_f time joinh h font_b">00</span><span class="dian font_b">:</span>
                    <span class="color_f time joinm m font_b">00</span><span class="dian font_b">:</span>
                    <span class="time color_f joins s font_b">00</span>
                </span>
            </P>
        </div>
    </div>  
</if>
<!-- 砍价活动结束时间 -->
<if condition="($stock_num gt 0 or $stock_num==-1) and $team_info['bar_num'] elt 0">
    <div class="flex grad count_down" style="height: 50px;  line-height: 15px;" id="count_down">
       <div class="flex_one flex pl10" >
           <div class="flex_one color_f">
                <span class="font_size12">底价{pigcms{$config['Currency_symbol']}</span><span class="font_size24">{pigcms{$bar_info.floor_price}</span>
                <del style="font-size:15px;color:rgba(255, 255, 255, .7);">{pigcms{$config['Currency_symbol']}{pigcms{$bar_info.spec_price}</del>
           </div>

        </div>
        <div class=" align_right pr10">
            <eq name="bar_info.act_status" value='0'>
                <p class="font_size12 align_left" style="color:#ad1e00">距活动开始还有：</p>                
            <else/>
                <p class="font_size12 align_left" style="color:#ad1e00">距活动结束还剩：</p>
            </eq>
            <P data-clock="{pigcms{$bar_info.time_clock}" data-type='1' class='clock'>
                <span class="font_size13">
                    <span class="color_f time joind d font_b day">00</span>
                    <span style="color:#ad1e00" class="day">天</span>
                    <span class="color_f time joinh h font_b hourse">00</span><span class="dian font_b hourse">:</span>
                    <span class="color_f time joinm m font_b">00</span><span class="dian font_b">:</span>
                    <span class="time color_f joins s font_b">00</span>
                </span>
            </P>
        </div>
    </div>
</if>
<div class="content ">
    <div class="bg_f">
        <div class="pd10 ">
            <div class="flex_one flex flex_star">
                <div class="flex_one">
                    <h4 class="ui-nowrap-multi-del">
                        <eq name='now_goods.is_self' value='1'>
                            <i class="self_tag" style='vertical-align: text-bottom;'>自营</i>    
                        </eq>
                        {pigcms{$now_goods.name}
                        <notempty name="bar_info.spec_str">
                            <span class='font_size13 color_7'>({pigcms{$bar_info.spec_str})</span>
                        </notempty>
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
            <div class="pt20">
                <div class="demo-block">
                    <div class="ui-progress rel">
                        <span class="grad" style="width:{pigcms{$team_info.percent|default=0}%"></span>
                    </div>
                </div>
                <div class="flex pt10 font_size13">
                    <div class="flex_one color_red">已砍{pigcms{$team_info.bar_total_price|default=0}{pigcms{$config['Currency_txt']}</div>
                    <div class="flex_one align_right color_9">还剩<if condition="isset($team_info['bar_discount_price'])">{pigcms{$team_info.bar_discount_price|default=0}<else/>{pigcms{$bar_info['tprice']-$bar_info['floor_price']}</if>{pigcms{$config['Currency_txt']}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="cut">
        <div class="demo-block">
            <ul class="ui-list ui-list-single ui-list-link ui-border-t mg10_0">
                <li class="ui-border-t">
                    <div class="ui-list-info cursor standard">
                        <h4 class="ui-nowrap font_size15">砍价玩法<span class="pl10 color_9"></span></h4>
                    </div>
                </li>
                <div class="ui-border-t font_size14 mg0_10 flex flex_justify ping_game standard cursor" style="padding: 11px 0;">
                    <div><span class="one">1</span>发起砍价</div>
                    <span>···</span>
                    <div><span class="one">2</span>邀请帮砍</div>
                    <span>···</span>
                    <div><span class="one">3</span>砍到低价后下单购买</div>
                </div>
            </ul>
            
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

            <div class="ui-tab mg_t10 shopDetail">
                <ul class="ui-tab-nav ui-tab-nav-bisect-lg ui-border-b">
                    <li class="current"><span class="font_size16">商品详情</span></li>
                    <gt name="team_info.bar_num" value='0'>
                        <li><span class="font_size16">好友助力({pigcms{$team_info.bar_num})</span></li>
                    </gt>
                </ul>
                <ul class="ui-tab-content" style="width:200%;background:#fff">
                    <li class="content_box" style="padding:0 !important;">
                        <div style="box-sizing:border-box;padding: 20px 12px !important;">
                            {pigcms{$now_goods['des']}    
                        </div>                        
                    </li>
                    <li class="ui-tab-content-2">
                        <div class="demo-block">
                            <ul class="ui-list ui-list-single ui-list-link pd5_0">
                                <volist name="team_user_list" id='vo'>
                                <li class="pd5_0 after">
                                    <div class="ui-list-thumb-s ui-border-t">
                                        <span >
                                            <img class="br w100 h100" src="{pigcms{$vo.avatar|default=$static_path.'images/new_my/pic-default.png'}" >
                                        </span>
                                    </div>
                                    <div class="ui-list-info">
                                        <h4 class="ui-nowrap font_size14">{pigcms{$vo.nickname} <gt name="team_id" value='0'>砍价<else/>帮你砍</gt><span class="color_red ml5">{pigcms{$vo.bar_price}{pigcms{$config['Currency_txt']}</span></h4>
                                        <div class="ui-txt-info font_size14">{pigcms{$vo.bar_time|date="Y-m-d",###}</div>
                                    </div>
                                </li>
                                </volist>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="footerMenu wap footer rel">
    <ul>
        <li style="width:20%">
            <a class=" color_7" href="{pigcms{:U('Mall/store',array('store_id'=>$store['store_id']))}">
                <em class="shop"></em>
                <p>进店</p>
            </a>
        </li>

        <!-- 助力砍价 -->
        <gt name="team_id" value='0'>
            <eq name='team_info.is_help' value='1'>
                <li style="width:80%" class="bg_r">
                    <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$now_goods['goods_id'],'bar_id'=>$bar_id))}" class="color_f" style="line-height:46px;">
                        <p>我也要玩</p>
                    </a>
                </li>
            <else/>
                <eq name="team_info.status" value='0'>
                    <li style="width:40%" class=" ui-border-l">
                        <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$now_goods['goods_id'],'bar_id'=>$bar_id))}" class="" style="line-height:46px;">
                            <p style='color:#777;'>我也要玩</p>
                        </a>
                    </li>
                    <li style="width:40%" class="bg_r">
                        <a href="javascript:;" class="help_cut cursor color_f" style="line-height:46px"><p>帮TA砍一刀</p></a>
                    </li>

                <else/>
                    <li style="width:80%" class="bg_fa">
                        <a href="javascript:;" class="bg_c" style="line-height:46px;color:#fff;"><p>砍价失败</p></a>
                    </li>
                </eq>
            </eq>
        <else/>
            <gt name="team_info.bar_num" value='0'>
                <eq name="team_info.status" value='0'>
                    <eq name='team_info.bar_discount_price' value='0'>
                        <li style="width:80%" class="ui-border-l bg_r">
                            <a href="{pigcms{:U('Mall/confirm_order',array('store_id'=>$now_goods['store_id'],'params'=>'onlybuy','bar_id'=>$bar_id,'team_id'=>$team_info['id']))}" class="show_buy color_f" style="line-height:46px"><p class="font_size14">砍价成功！以当前价购买</p></a>
                        </li>
                    <else/>
                        <li style="width:40%;color:#fff;" class="ui-border-l bg_pink">
                            <a href="{pigcms{:U('Mall/confirm_order',array('store_id'=>$now_goods['store_id'],'params'=>'onlybuy','bar_id'=>$bar_id,'team_id'=>$team_info['id']))}" class="show_buy" style="line-height:23px">
                                <p class="font_size16">{pigcms{$config['Currency_symbol']}{pigcms{$team_info.now_price}</p>
                                <p class="">当前价购买</p>
                            </a>
                        </li>
                        <li style="width:40%" class="bg_red2">
                            <a href="javascript:;" class="show_buy color_f" style="line-height:23px;">
                                <div class="please_friend share">
                                    <span class="font_size16">{pigcms{$config['Currency_symbol']}{pigcms{$bar_info.floor_price}</span>
                                    <p class="cursor">找人帮砍</p>
                                </div>
                            </a>
                        </li>
                    </eq>                    
                <else/>
                    <li style="width:80%" class="bg_fa">
                        <a href="javascript:;" class="bg_c " style="line-height:46px;color:#fff;"><p>砍价失败</p></a>
                    </li>
                </eq>
            <else/>
                <eq name="bar_info.act_status" value='0'>
                    <li style="width:80%" class="bg_d">
                        <a href="javascript:;" class="color_9 font_size14 cursor" style="line-height:46px;"><p>活动未开始</p></a>
                    </li>
                <elseif condition="$bar_info['act_status'] eq 2"/>
                    <li style="width:80%" class="bg_d">
                        <a href="javascript:;" class="color_9 font_size14 cursor" style="line-height:46px;"><p>活动已结束</p></a>
                    </li>
                <elseif condition="$stock_num elt 0 and $stock_num!=-1"/>
                    <li style="width:80%" class="bg_fa">
                        <a href="javascript:;" class="bg_c" style="line-height:46px;color:#fff;"><p>已砍完</p></a>
                    </li>
				<elseif condition="$now_goods['stock_num'] eq 0"/>
					<li style="width:80%" class="bg_fa">
                        <a href="javascript:;" class="bg_c" style="line-height:46px;color:#fff;"><p>商品已售罄</p></a>
                    </li>
                <else/>
                    <li class="bg_fa abs" style="width: 100%;top: 0;left: 0;">
                        <a href="javascript:;" class="first_cut color_f font_size14 cursor" style="line-height:46px"><p>砍下第一刀</p></a>
                    </li>
                </eq>
            </gt>
        </gt>
    </ul>
</div>
<div class="ui-dialog ui-dialog-operate ui-dialog-operate-icon" id="dialog6">

</div>

<!-- 玩法介绍 -->
<div class="show ui_bottom ping_game" style="display:none" id="ping_game">
    <div class="model hide_modal"></div>
    <div class="demo-block specifications pd10 bg_f " >
        <div class="rel">
            <div class="pt10">
                <h4 class="align_center">砍价规则</h4><br>
                <p class="color_7 font_size12">1.邀请好友一起砍价，砍到低价即可获得商品</p><br>
                <p class="color_7 font_size12">2.参与砍价的好友越多，越容易砍到底价</p><br>
                <p class="color_7 font_size12">3.对于同一砍价商品，您只能帮好友砍一次</p><br>
                <gt name='config.max_help_bargain_num' value='0'>
                <p class="color_7 font_size12">4.每个用户每日最多可帮助他人砍价{pigcms{$config.max_help_bargain_num}次</p><br><br>
                </gt>
            </div>
            <div class="btn_confirm">
                <button class="ui-btn-lg ui-btn-primary hide_modal">确定</button>
            </div>
            </div>
            <div></div>
        </div>
    </div>
</div>
<!-- 邀请好友弹层 -->
<div id="gride" class='none cursor'>
    <div></div>
</div>
<!-- 成功砍价弹层 -->
<script type="text/html" id="model">
    <div class="ui-dialog-cnt">
        <div class="ui-dialog-hd">
            <div class="ui-img">
                <span ><img src="{pigcms{$static_path}mall/images/my_kanc.png" class="w100 h100" id="cut_title_img"/></span>
            </div>
        </div>
        <div class="ui-dialog-bd">
            <h3>成功砍价<span class="color_red pl5"><&= _this.bar_price &>{pigcms{$config['Currency_txt']}</span></h3>
            <div class="color_9 font_size14">
                <p>分享给好友多砍几刀吧</p>
            </div>
        </div>
        <div class="" style="padding: 0px 30px 16px 30px;">
            <button class="ui-btn-lg ui-btn-primary share_friend cursor" data-type='2' data-team_id="{pigcms{$team_info.id}}">邀请好友</button>
        </div>
        <i class="ui-dialog-close cursor" data-role="button"></i>
    </div>
</script>
<if condition="$stock_num elt 0 and $stock_num!=-1">
<div class="stock_tips">
    <div class="tip_box">
        <div class="box">
            <p>本商品库存已砍完</p>
            <p>请查看更多商品</p>
            <a href="{pigcms{:U('Mall/index')}" class="btn">查看更多商品</a>
        </div>
        <span class='close'></span>
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

<div class="mask cursor" ></div>
<!-- 浮动tabble -->
<include file="floatTabble"/>
<script>var $home=false</script>
<script src="{pigcms{$static_path}mall/js/video.min.js"></script>
<script src="{pigcms{$static_path}mall/js/cate_common.js" type="text/javascript" ></script>
<script src="{pigcms{$static_path}mall/js/simplite.js" type="text/javascript" ></script>
<script src="{pigcms{$static_path}mall/js/iscroll.min.js" type="text/javascript" ></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/swiper.js"></script>
<script src="{pigcms{$static_path}mall/js/video.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script>
    var first_cut_url="{pigcms{:U('Mall/add_bargain_team')}";
    var store_id="{pigcms{$now_goods.store_id}";
    var bar_id="{pigcms{$bar_info.id}";
    var team_id="{pigcms{$team_info.id}";
    var help_cut_url="{pigcms{:U('Mall/help_bargain')}";
	var desc="{pigcms{$page_share_content}";
    var name="{pigcms{$now_goods.name}";
    var price="{pigcms{$now_goods.price}";
    desc=desc || '价值{pigcms{$config['Currency_symbol']}'+price+','+name;
    var share_pic="{pigcms{$now_goods['pic_arr'][0]['url']}";
    //var share_url="{pigcms{$config.site_url}{pigcms{:U('Mall/detail', array('goods_id' => $now_goods['goods_id']))}";
	var share_url="{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=detail&goods_id={pigcms{$now_goods['goods_id']}&bar_id={pigcms{$bar_info.id}&team_id={pigcms{$team_info.id}";
    var app_version = {pigcms{$app_version|intval=###};
	if("{pigcms{$app_browser_type}" == 'android'){
		if(app_version >= 1400){
			var android_shareLifePass = {imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
			android_shareLifePass.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
			if(android_shareLifePass.share_wx == 'wxapp'){
				android_shareLifePass.share_wx_info = {userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(share_url), image:share_pic, title:desc};
			}
			window.lifepasslogin.shareLifePassNew(JSON.stringify(android_shareLifePass));
		}else{
			window.lifepasslogin.shareLifePass(name,desc,share_pic,share_url);
		}
    } else {
		var ios_shareLifePass = {name:'shareLifePass', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
		ios_shareLifePass.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
		if(ios_shareLifePass.share_wx == 'wxapp'){
			ios_shareLifePass.share_wx_info = JSON.stringify({userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(share_url), image:share_pic, title:desc});
        }
		iosCommon.iosFunction('shareLifePass', ios_shareLifePass);
    }
    function shareFriend(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            var ios_shareWeChat = {name:'shareWeChat', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
			ios_shareWeChat.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
			if(ios_shareWeChat.share_wx == 'wxapp'){
				ios_shareWeChat.share_wx_info = JSON.stringify({userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(share_url), image:share_pic, title:desc});
            }
			iosCommon.iosFunction('shareWeChat', ios_shareWeChat);
        }else{
			if(app_version >= 1400){
				var android_shareWeChat = {imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc };
				android_shareWeChat.share_wx = "{pigcms{:(($config['pay_wxapp_important'] && $config['pay_wxapp_username']) ? 'wxapp' : 'h5') }";
				if(android_shareWeChat.share_wx == 'wxapp'){
					android_shareWeChat.share_wx_info = {userName:"{pigcms{$config['pay_wxapp_username']}", path:'/pages/index/index?redirect=webview&webview_url=' + encodeURIComponent(share_url), image:share_pic, title:desc};
					window.lifepasslogin.shareWeChatNew(JSON.stringify(android_shareWeChat));
				}else{
					window.lifepasslogin.shareWeChat(name,desc,share_pic,share_url);
				}	
			}else{
				window.lifepasslogin.shareWeChat(name,desc,share_pic,share_url);
			}
        }
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

{pigcms{$shareScript}
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "{pigcms{$now_goods['pic_arr'][0]['url']}",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/detail', array('goods_id' => $now_goods['goods_id'],'bar_id'=>$bar_id,'team_id'=>$team_info['id']))}",
        "tTitle": "差你一刀了！快来一起砍价【{pigcms{$now_goods['name']}】",
        "tContent": "价值{pigcms{$bar_info.spec_price}{pigcms{$config['Currency_txt']}，砍到{pigcms{$bar_info.floor_price}{pigcms{$config['Currency_txt']}就拿走，点击立即参与"
    };
    window.onload = function(){
        $('.ssd-module-wrap').css('width','100%');
    }
    var res_url='';
    var ua = window.navigator.userAgent.toLowerCase();
    var is_weixin=false;
    var is_mini= /(miniProgram)/.test(navigator.userAgent.toLowerCase()) ? true : false;
    var share_clock=1;
    /*是否在app端*/
    var is_app={pigcms{$is_app_browser|default=0};
    var webview=function(type){
        wx.miniProgram.getEnv(function(res) {
            var ss=JSON.stringify(res);
            share_clock=0;
            if (res.miniprogram) {
                is_mini=true;
                $.ajax({
                    type: "POST",
                    url:getShareUrl,
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
                        var res=msg.info;//wxapp_path
                        res_url= '/pages/index/share?title='+encodeURIComponent(res.title)+'&shareTitle='+encodeURIComponent(res.share_title)+'&shareImg='+encodeURIComponent(res.friend_img)+'&sharePath='+encodeURIComponent(res.wxapp_path)+'&bg='
                                + encodeURIComponent(res.good_image)+'&poster='+encodeURIComponent(res.mall_img);
                         if(type){
                            //小程序环境
                            if($.cookie('is_share')==1){
                                 $.cookie('is_share',0);
                                 wx.miniProgram.navigateTo({
                                     url: res_url
                                 });
                            }
                        }
                    }
                });
            }else{
                //微信端
                is_weixin=true;
                if(type){
                    if($.cookie('is_share')==1){
                        $.cookie('is_share',0);
                        $("#gride").removeClass('none')
                    }
                }
            }
        });
    }
    if(ua.indexOf('micromessenger') == -1){
        //不在微信或者小程序中
        if(is_app){
            if(team_id && team_id!=undefined && team_id!=null && team_id!=''){
                if($.cookie('is_share')==1){
                    $.cookie('is_share',0);
                    shareFriend();
                }
            }
        }
        share_clock=0;
    }else{
        if(team_id && team_id!=undefined && team_id!=null && team_id!=''){
            webview(true)
        }else{
            webview()
        }

    }

    $(document).on('click', '#go_share', function(){
        if(is_mini){
            //小程序环境
            wx.miniProgram.navigateTo({
                url: res_url
            })
        }else{
            if(is_weixin){  //微信端
                $("#guide").removeClass('none')
            }else if(is_app){
                shareFriend();
            }else{  //h5分享  
                wap_share_tip(1);
            }
        }
    });
	$(document).on("click",'.share',function(){
		if(is_weixin){
			$("#gride").removeClass("none");
		}else {
			if(is_mini){
				layer.open({
					type: 2
					,content: '加载中'
				});
				wx.miniProgram.navigateTo({
					url: res_url
					,complete:function(){
						layer.closeAll();
					}
				})
			}else if(is_app){
				shareFriend();
			}else{  //h5分享  
				share_clock=0;
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
                            url: res_url
                        }); 
                    }
                }
            }else{  //h5分享  
                wap_share_tip(0);
            }
        }
    });
    $(document).on('click', '#enshrine', function(){
        $(".mask").hide();
        $("#enshrine").addClass('none');
        $(".coupon").removeClass("active");
        $(".coupon_mask").hide();
        $(".guide").addClass('none');
        $(".model_button").addClass('none');
        $(".is_yongjin").addClass('none');
    });
    $(document).on("click",'.share_friend',function(){
        var type=$(this).data('type');
        var href=window.location.href;
        if(type==1){
            var team_id=$(this).data('team_id');
            getShareUrls+='&team_id='+team_id
            href+='&team_id='+team_id;
        }
        if(is_mini){
            get_share_info(getShareUrls,href);
        }else{
            $.cookie('is_share',1);
            APP.fnUrlReplace(href);
        }
    });
    $('#enshrine').bind("touchmove",function(e){
        e.preventDefault();
    });
</script>
<script type="text/javascript">
/*小程序不刷新跳转分享关注*/
function get_share_info(src,href){
    $.ajax({
        type: "POST",
        url:src,
        beforeSend:function(){
            motify.log('跳转中...','');
        },
        success: function(msg){
            motify.clearLog();
            if(!msg.status){
                layer.open({
                    content:msg.info,
                    btn:['我知道了'],
                    yes:function(index){
                        layer.close(index);
                        APP.fnUrlReplace(href);
                    }
                });
                return false;
            }
            var res=msg.info;//wxapp_path
            res_url= '/pages/index/share?title='+encodeURIComponent(res.title)+'&shareTitle='+encodeURIComponent(res.share_title)+'&shareImg='+encodeURIComponent(res.friend_img)+'&sharePath='+encodeURIComponent(res.wxapp_path)+'&bg='
                    + encodeURIComponent(res.good_image)+'&poster='+encodeURIComponent(res.mall_img);
            wx.miniProgram.navigateTo({
                url: res_url,
                complete:function(){
                    APP.fnUrlReplace(href);
                }
            });
        }
    });
}
</script>
<script>
    var nav_click = false
    var View={
        inits:function(){
            this.initDetail();
            this.initClick();
            this.time();
            this.window_scroll();
        },
        setting:{
            page:1,
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
            $(".head").css("height",wh+'px')
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
                            //离开全屏时
                            _self.setting.video.on("enterFullScreen",function(){
                                //获取播放进度
                                var whereYouAt = _self.setting.video.currentTime();
                                if(whereYouAt!=0){
                                    _self.setting.video.play();
                                }
                            })
                            //暂停
                            _self.setting.video.on("pause",function(){
                                _self.setting.video_load=true;
                            })
                            //加载时
                            _self.setting.video.on("loadstart",function(){

                            })

                        }

                    }
                },
                onSlideChangeEnd:function(e){
                    console.log(e.slides)
                    var n=$(".swiper-container2 .swiper-pagination span").length;
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

        },
        videoLoad:function(){
            $(".swiper-pagination-video").show()
            this.setting.video_load=false;
        },
        initClick:function(){
            var _self=this;
            $(document).on("click",'.ui-dialog-close',function(){
                $('.ui-dialog').removeClass("show");
                window.location.reload();
            })
            $('.ui-tab-nav').eq(0).find('li').on('click',function() {
                $(this).parent().find('li').removeClass('current');
                $(this).addClass('current');
                var index=$(this).index()==0?1:0;

 console.log($('.ui-tab-content-2>.demo-block').height())
                $('.ui-tab-content').eq(0).css({
                    'transform':'translate3d(-'+($(this).index()*$('.ui-tab-content>li').width())+'px,0,0)',
                    'transition':'transform 0.5s linear'
                })
               setTimeout(function(){
                   if(index==0){
                       $(".ui-tab-content-2").css('height', $('.ui-tab-content-2>.demo-block').height()+'px')
                       $(".ui-tab-content").css('height', $('.ui-tab-content-2>.demo-block').height()+'px')
                   }else{
                       $(".ui-tab-content").css('height', 'auto');
                       $(".ui-tab-content-2").css('height', 'auto')
                   }
               },0.5)
            });
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
            });

            $(document).on("click",'#gride',function(){
                $("#gride").addClass("none");
                $.cookie('is_share',0);
            });
            $(document).on("click",'.coupon_mask,.del_enshrine',function(e){
                $(".coupon").removeClass("active")
                $(".coupon_mask").hide();
                $("#enshrine").addClass('none')
                $(".guide").addClass('none')
                $(".is_yongjin").addClass('none')
            });
            //砍下第一刀
            $(document).on("click",'.first_cut',function(){
                $.post(first_cut_url,{store_id:store_id,bar_id:bar_id,goods_id:{pigcms{$now_goods['goods_id']}},function(res){
                    if(res.status==0){
                        motify.log(res.info);
                        if(res.url){
                            setTimeout(function(){
                                window.location.href=res.url;
                            },1000);
                        }
                    }else if(res.status==1){
                        var tp = Simplite.getTemplate('model');
                        var compiledTpl = Simplite.compile(tp)(res);
                        $('#dialog6').html(compiledTpl).addClass('show');
                        var team_id=res.team_id;
                        $('.share_friend').data('type',1).data('team_id',team_id);
                        setTimeout(function(){
                            APP.imgPic({
                                'id':'cut_title_img',
                                'callback':function(dimensions){
                                    var i=dimensions.w/dimensions.h;
                                    if(i>=1){
                                        if(dimensions.w<110){
                                            $(".ui-dialog-operate-icon .ui-img ").css({
                                                'width':dimensions.w+'px',
                                                'height':dimensions.h+'px'
                                            })
                                        }else{
                                            $(".ui-dialog-operate-icon .ui-img ").css({
                                                'width':'110px',
                                                'height':110/i+'px'
                                            })
                                        }
                                    }else{
                                        $(".ui-dialog-operate-icon .ui-img ").css({
                                            'width':dimensions.w+'px'
                                        })

                                    }

                                }
                            })
                        },200)

                    }
                });
            })

            //助力砍价
            $(document).on("click",'.help_cut',function(){
                $.post(help_cut_url,{bar_id:bar_id,team_id:team_id},function(res){
                    if(res.status==0){
                        motify.log(res.info);
                        if(res.url){
                            setTimeout(function(){
                                window.location.href=res.url;
                            },1000);
                        }
                    }else if(res.status==1){
                        var tp = Simplite.getTemplate('model');
                        var compiledTpl = Simplite.compile(tp)(res);
                        $('#dialog6').html(compiledTpl).addClass('show');
                        var team_id=res.team_id;
                        $('.share_friend').data('type',1).data('team_id',team_id);
                        setTimeout(function(){
                            APP.imgPic({
                                'id':'cut_title_img',
                                'callback':function(dimensions){
                                    var i=dimensions.w/dimensions.h;
                                    if(i>=1){
                                        if(dimensions.w<110){
                                            $(".ui-dialog-operate-icon .ui-img ").css({
                                                'width':dimensions.w+'px',
                                                'height':dimensions.h+'px'
                                            })
                                        }else{
                                            $(".ui-dialog-operate-icon .ui-img ").css({
                                                'width':'110px',
                                                'height':110/i+'px'
                                            })
                                        }
                                    }else{
                                        $(".ui-dialog-operate-icon .ui-img ").css({
                                            'width':dimensions.w+'px'
                                        })

                                    }

                                }
                            })
                        },200)
                    }
                });
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
            //关闭弹窗
            $(document).on('click','.stock_tips .close',function(){
                $(this).parents('.stock_tips').remove();
            });

            // 点击顶部导航
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
        },
        time:function(){
            var clock= $('.clock').attr('data-clock')*1;
            if(clock && clock>0){
                APP.timer({
                    time: clock*1000,
                    onBackTime:function(d){
                        if ( d[0]*1 > 0 ) {
                            $(".joind").html(d[0]);
                        } else {
                            $('.day').css('display', 'none')
                        }

                        if( d[1]*1 > 0 ) {
                            $(".joinh").html(d[1]);
                        } else {
                            $('.hourse').css('display', 'none')
                        }

                        $(".joinm").html(d[2]);
                        $(".joins").html(d[3]);
                    }
                })
            }


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
    }
    window.View=View;
    View.inits()
</script>
<!-- app分享 -->
<if condition="$is_app_browser">
    <script type="text/javascript">
        
        /*if("{pigcms{$app_browser_type}" == 'android'){
            window.lifepasslogin.shareLifePass(window.shareData.tTitle,window.shareData.tContent, window.shareData.imgUrl, window.shareData.sendFriendLink);
        } else {
            iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }*/
    </script>
</if>
<script type="text/html" id='buybox'>
    <& for(var i=0;i<_this.length;i++){ &>
    <& var _item=_this[i]; &>
        <div class="swiper-slide flex swiper-no-swiping">
            <img src="<&= _item.avatar &>" />
            <div class="flex_one"><span><&= _item.nickname &>刚刚正在砍价这个商品</span></div>
        </div>
    <& } &>
</script>
<script type="text/javascript">
$(function(){
    /*新增用户参与下单信息*/
    var src="{pigcms{:U('Mall/get_more_buy')}";
    var goods_id={pigcms{$now_goods.goods_id};
    function get_more_buy(){
        $.post(src,{goods_id:goods_id,goods_type:2},function(res){
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
    var is_left=0,is_z_index=1,is_show_friend=true;
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
</body>
</html>