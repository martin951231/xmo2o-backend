<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="format-detection" content="telephone=no">
    <meta charset="utf-8">
    <title>拼团详情</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet">
    <link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <script>var $home=false</script>
    <style>
        #pageLoadTip{display:none !important;}
        .tjt_border_t:before{  top: -1px !important;  }
        .tjt_border_b:before{  bottom: -1px !important;  }
        .tjt_border_r:before{  right: -1px !important;  }
        .tjt_border_l:before{  left: -1px !important;  }
         .time {  line-height: 18px;  background: #F38144;  text-align: center;  height: 18px;  width: 18px;  display: inline-block;  padding: 2px;  border-radius: 2px;  }
        .avatar>span {  width: 40px;  height: 40px;  line-height: 40px;  display: inline-block;  vertical-align: middle;  border:1px dashed #ddd;  color: #ddd;  border-radius: 50%;  margin: 5px 5px;  position: relative;  }
        .avatar span.leader {  border: none;  }
        .avatar img{  width: 100%;  height: 100%;  border-radius: 50%;  overflow: hidden;  }
        .avatar span.leader .tag{  position: absolute;  top: -4px;  left: 50%;  transform: translate(-50%, 0);  color: #f8f8f8;  background: #f23030;  line-height: normal;  font-size: 10px;  padding: 0 5px;  border-radius: 8px;  white-space: nowrap;  }
        .ping_game .one {  line-height: 16px;  font-size: 12px;  color: #fff;  display: inline-block;  background: #000;  width: 15px;  height: 15px;  border-radius: 50%;  text-align: center;  margin: 0 3px 3px 0;  vertical-align: middle;  }
        #gride {  position: fixed;  z-index: 100;  left: 0px;  top: 0px;  right: 0px;  bottom: 0px;  background: hsla(0,0%,7%,.7);  }
        #gride>div {  height: 100%;  background-size: 100%; background: url({pigcms{$static_path}mall/images/guide1.png) no-repeat;  width: 100%;  background-position: top;  }
        .no_join{background: #ccc;}
        i.dian{width:2px;height:2px;background:#ccc;border-radius:1px;display:inline-block;vertical-align: inherit;margin: 19px 2px;}
    </style>
</head>
<body>
<div class="title_data" data-is_seckill="{pigcms{$now_goods['is_seckill_price']|intval}" data-pack="{pigcms{$now_goods['packing_charge']|floatval}" data-price="{pigcms{$now_goods['price']|floatval}" data-name="{pigcms{$now_goods['name']}" data-stock="{pigcms{$now_goods['stock_num']}" data-goods_id="{pigcms{$now_goods['goods_id']}" data-store_id="{pigcms{$now_goods['store_id']}" data-extra_pay_price="{pigcms{$now_goods['extra_pay_price']}" data-max_num="{pigcms{$now_goods['max_num']}" data-min_num="{pigcms{$now_goods['min_num']}" data-limit_type="{pigcms{$now_goods['limit_type']}" data-unit="{pigcms{$now_goods['unit']}" data-o_price="{pigcms{$now_goods['old_price']|floatval}">
    <div class="slideBox content ">
        <div>
            <div class="closeItem">
                <div class="demo-block  pd10 bg_f">
                    <div class="demo-block ping_tuan mb_0 border_d" style="box-shadow: 0 0 1px 1px #f8f8f8;">
                    <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$now_goods['goods_id'],'group_id'=>$group_info['id']))}" style="">
                        <ul class="ui-list ui-border-tb mb_0">
                            <li>
                                <div class="ui-list-img-square">
                                   <span >
                                        <img src="{pigcms{$now_goods.image}" class="w100 h100" />
                                   </span>
                                </div>
                                <div class="ui-list-info ui-border-t flex_justify" style="padding: 12px 12px 12px 0;">
                                    <h4 class="font_size16 mb_8 ellipsis">
                                        <eq name="group_info.join_type" value='2'><span class='invite_team'>邀新团</span></eq>
                                        {pigcms{$now_goods.name}
                                    </h4>
                                    <div class="demo-block">
                                        <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                                            <li class="after ml_0">
                                                <div class="ui-list-info pd0">
                                                    <div class="ui-nowrap flex border_red mg_r10" style="height:14px;line-height: 14px;border-radius: 2px;">
                                                        <div class="bg_r color_f"><i class="icon iconfont font_size14">&#xe718;</i></div>
                                                        <div  class="color_red font_size10" style="padding: 0px 3px;">{pigcms{$team_info.complete_num}人拼</div>
                                                    </div>
                                                    <h4 class="ui-nowrap money">{pigcms{$config['Currency_symbol']}<span class="font_size24">{pigcms{$group_info.group_price}</span></h4>
                                                </div>
                                            </li>
                                            <div class="font_size12 color_9">单买价<span class="font_size14 ml5 border_center rel">{pigcms{$config['Currency_symbol']}{pigcms{$now_goods.price}</span></div>         
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        </a>
                    </div>
                </div>
                <div class="demo-block demo-block05 ">
                    <div class="bg_f">
                        <h4 class="align_center font_size14" style="padding:10px 0px 20px;">
                        <eq name="team_info.status" value='1'>
                            <span class='font_size18 font_b color_red'>拼团成功！</span>
                        <elseif condition="$team_info['status'] eq 2"/>
                            <span class='font_size18 font_b color_red'>拼团失败</span>
                        <elseif condition="$team_info['status'] eq 0"/>
                            <gt name="team_info.need_num" value='0'>
                                还差<span class="color_red">{pigcms{$team_info.need_num}人</span>拼团成功，距拼团结束还剩
                                <span data-clock="{pigcms{$team_info.time_clock}" data-type='1' class='clock'>
                                    <span class="color_f time joind d day">00</span>
                                    <span style="color:#C74302" class="day">天</span>
                                    <span class="color_f time joinh h hourse">00</span><span class="dian hourse">:</span>
                                    <span class="color_f time joinm m">00</span><span class="dian">:</span>
                                    <span class="time color_f joins s">00</span>
                                </span>                        
                            </gt>
                        </eq>                        
                        </h4>
                        <div class="align_center pb20 avatar">
                            <volist name="team_user_list" id='vo' key='k'>
                                <span class="leader">
                                    <img class="" src="{pigcms{$vo.avatar|default=$static_path.'images/new_my/pic-default.png'}" />
                                    <eq name='k' value='1'><span class="tag">团长</span></eq>
                                </span>
                            </volist>
                            <for start='$k' end="$team_info.complete_num">
                                <span>?</span>
                            </for>
                        </div>
                        <div class="mb_10">
                            <eq name='team_info.status' value='0'>
                                <gt name="team_info.need_num" value='0'>
                                    <if condition="$is_join eq 1">
                                        <button style="width: calc(100% - 20px);" class="ui-btn-lg-nowhole ui-btn-primary share_friend">邀请好友参团</button>
                                    <else/>
                                        <if condition="$group_info['join_type'] eq 2 && $group_info['buy_num'] gt 0">
                                            <button style="width: calc(100% - 20px);"  class="ui-btn-lg-nowhole ui-btn-primary font_size15 no_join">我要参团
                                            </button>
                                        <else/>
                                             <button style="width: calc(100% - 20px);"  class="ui-btn-lg-nowhole ui-btn-primary Choice_n font_size15" data-store_id="{pigcms{$now_goods['store_id']}" data-type="group_join" data-group_id="{pigcms{$group_info.id}" data-team_id="{pigcms{$team_info.id}">我要参团
                                            </button>
                                        </if>
                                    </if>  
                                </gt>
                                <gt name="order_id" value='0'>
                                    <a href="{pigcms{:U('Mall/status',array('order_id'=>$order_id,'callback'=>urlencode(__SELF__)))}"><button style="width: calc(100% - 20px);margin-top:10px;" class="ui-btn-lg-nowhole">订单详情</button></a>
                                </gt>
                            </eq>
                        </div>
                        <div>
                            <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
                                <li class="ui-border-t">
                                    <eq name="group_info.join_type" value='2'>
                                        <div class="ui-list-info cursor standard invite_box">
                                            <span>邀新团</span>
                                            <i class='dian'></i>
                                            <span>所有人均可参团，但仅限店铺新用户参团</span>
                                        </div>
                                    <else/>
                                        <div class="ui-list-info cursor standard">
                                            <h4 class="ui-nowrap font_size14">拼团规则</h4>
                                            <div class="ui-txt-info font_size13 color_9">好友参团<span class="font_b">&nbsp;·&nbsp;</span>人满发货<span class="font_b">&nbsp;·&nbsp;</span>不满退款</div>
                                        </div>
                                    </eq>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="font_size15 pd10 align_center color_3">更多拼购火热进行中</div>

                <ul class="ks-clear bg_f pd10">
                   <!-- <volist name="list" id='vo'>
                        <li class="w47 ui-border-t tjt_border_t left  <if condition="$key%2==0" >clear<else/> ''</if>  " style="<if condition="$key%2==0" >margin: 2% 1% 0% 2%;<else/>     margin: 2% 2% 0% 1%; </if>  >
                          <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$vo['goods_id'],'group_id'=>$vo['group_id']))}" target="_blank">
                                <div class="pt0 pb0  bg_f  ui-border-l tjt_border_l  " >
                                    <div class="ui-border-r tjt_border_r">
                                        <div class="ui-img-vertical" style="padding-top:100%">
                                              <span class="pd0">
                                                <img src="{pigcms{$vo.image}"  style="width:<&= View.setting.pic_w&>px;height:<&= View.setting.pic_w&>px"/>
                                          </span>

                                        </div>
                                        <div class="ui-grid-info  m_0  ui-border-b pd10 "    >
                                            <h4 class="ellipsis font_size13 pl5" style="height: 40px;">{pigcms{$vo.name}</h4>
                                            <div class="font_size12 color_red">
                                                <span class="pr5 font_size11">{pigcms{$vo.complete_num}人拼</span>
                                                <span class="font_b">{pigcms{$config['Currency_symbol']}</span>
                                                <span class="font_size20 font_b">{pigcms{$vo.group_price}</span>
                                                <span class="color_9" style="text-decoration: line-through;">{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}</span>
                                            </div>
                                            <div class="font_size12 color_9">已拼{pigcms{$vo.sales_num}件</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>



                      &lt;!&ndash;  <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$vo['goods_id'],'group_id'=>$vo['group_id']))}">
                            <li class="bg_f left w47" style="<if condition="$key%2==0" >margin: 2% 1% 0% 2%;<else/>     margin: 2% 2% 0% 1%; </if>      ">
                                    <div class="rel" style="width:100%;padding-top:100%;">
                                        <img class="abs w100 h100" style="top: 0;left: 0;" src="{pigcms{$vo.image}" />
                                    </div>
                                    <div class="pd10" style="box-sizing: border-box;">
                                        <h4 class="ellipsis font_size13 mb_10" style="height: 40px;">{pigcms{$vo.name}</h4>
                                        <div class="font_size12 color_red">
                                            <span class="pr5 font_size11">{pigcms{$vo.complete_num}人拼</span>
                                            <span class="font_b">{pigcms{$config['Currency_symbol']}</span>
                                            <span class="font_size20 font_b">{pigcms{$vo.group_price}</span>
                                            <span class="color_9" style="text-decoration: line-through;">{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}</span>
                                        </div>
                                        <div class="font_size12 color_9">已拼{pigcms{$vo.sales_num}件</div>
                                    </div>
                            </li>
                        </a>&ndash;&gt;
                    </volist>-->
                </ul>
            </div>
            <div class="one"></div>
        </div>
    </div>

    <div class="show ui_bottom ping_game" style="display:none" id="ping_game">
        <div class="model hide_modal"></div>
        <div class="demo-block specifications pd10 bg_f " >
            <div class="rel">
                <div class="pt10">
                    <h4 class="align_center" data-i="{pigcms{$i=1}">拼团玩法</h4>
                    <eq name='group_info.join_type' value='2'>
                        <p><span class="one">{pigcms{$i++}</span>什么是邀新团？</p>
                        <span class="color_7 font_size12">即任意用户可开团成为团长，但仅限店铺新用户参团</span><br/><br/>
                    </eq>
                    <p><span class="one">{pigcms{$i++}</span>拼团活动有效期</p>
                    <span class="color_7 font_size12">即团长可发起拼团的时间段，可在拼团详情页查看该团活动倒计时</span><br/><br/>
                    <p><span class="one">{pigcms{$i++}</span>成团有效期</p>
                    <span class="color_7 font_size12">即团长发起拼团后与其他人组团的时间，开团后或参团时可查看具体成团有效期倒计时</span><br/><br/>
                    <p><span class="one">{pigcms{$i++}</span>拼团成功</p>
                    <span class="color_7 font_size12">成团有效期内，邀请参团好友达到成团数量后拼团成功，商家进入备货流程</span><br/><br/>
				    <p><span class="one">{pigcms{$i++}</span>拼团失败</p>
                    <span class="color_7 font_size12">成团有效期内，邀请参团好友未达到成团数量；或超过成团有效期，均为拼团失败，系统将在一定时间内退款</span><br><br/>
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
<div id="gride" class='none'>
    <div></div>
</div>
<!-- 规格选项 import -->
<include file="Speci"/>
<script type="text/html" id="detail">
<volist name="list" id='vo'>
    <li class="w47 ui-border-t tjt_border_t left  <if condition='$key%2==0' >clear<else/> ''</if>  " style="<if condition='$key%2==0' >margin: 2% 1% 0% 2%;<else/>margin: 2% 2% 0% 1%;</if>"  >
        <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$vo['goods_id'],'group_id'=>$vo['group_id']))}"   target="_blank">
            <div class="pt0 pb0  bg_f  ui-border-l tjt_border_l  " >
                <div class="ui-border-r tjt_border_r">
                    <div class="ui-img-vertical" style="padding-top:100%">
                            <span class="pd0">
                                 <img src="{pigcms{$vo.image}"  style="width:<&= View.setting.pic_w&>px;height:<&= View.setting.pic_w&>px"/>
                             </span>
                    </div>
                    <div class="ui-grid-info  m_0  ui-border-b  "    >
                        <h4 class="ellipsis font_size13 pl5" style="height: 40px;">{pigcms{$vo.name}</h4>
                        <div class="font_size12 color_red pl5">
                            <span class="font_b">{pigcms{$config['Currency_symbol']}</span>
                            <span class="font_size20 font_b">{pigcms{$vo.group_price}</span>
                            <span class="color_9" style="text-decoration: line-through;">{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}</span>
                            <span class="font_size12  right " style="padding-top: 9px;color:#777;padding-right:10px">已拼{pigcms{$vo.sales_num}件</span>
                        </div>
                        <div class="font_size12 color_9 pl5 pb10" style="clear: both;overflow: hidden;">
                            <span class="font_size12 color_f bg_r pd1_3 left">{pigcms{$vo.complete_num}人拼团价</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </li>
</volist>
</script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<!-- <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script> -->
<script type="text/javascript" src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/iscroll.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript">var noAnimate = true;</script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/group.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script type="text/javascript">
var goods_list = '{pigcms{$goods_detail}';
var reply_url = "{pigcms{:U('Mall/reply', array('goods_id' => $now_goods['goods_id']))}";
var save_url = "{pigcms{:U('Mall/confirm_order', array('store_id' => $now_goods['store_id']))}";
//获取小程序分享信息地址
var getShareUrl = "{pigcms{$config.site_url}/wap.php?g=Wap&c=Mall&a=share&goods_id={pigcms{$now_goods['goods_id']}&group_id={pigcms{$team_info.group_id}&team_id={pigcms{$team_info.id}";

var shopReplyUrl = "{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$now_goods['store_id']}";
var open_extra_price =Number("{pigcms{$config.open_extra_price}"); 
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
</script>
<script type="text/javascript">
var max_num=Math.floor($('.avatar').width()/($('.avatar span').width()+12));
var num={pigcms{$team_info.complete_num|default=0};
if(max_num<num){
    var k=max_num-1;
    $('.avatar span').eq(k).html('<i class="dian"></i><i class="dian"></i><i class="dian"></i>');
    $('.avatar span:gt('+k+')').hide();
    $('.avatar span:last-child').show();
}
</script>
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "{pigcms{$now_goods['pic_arr'][0]['url']}",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/group_team_detail', array('goods_id' => $now_goods['goods_id'],'group_id'=>$group_info['id'],'team_id'=>$team_info['id']))}",
        "tTitle": "邀您参与{pigcms{$group_info.group_price}{pigcms{$config['Currency_txt']}拼团【{pigcms{$now_goods['name']}】",
        "tContent": "每件仅需{pigcms{$group_info.group_price}{pigcms{$config['Currency_txt']}，{pigcms{$team_info.complete_num}人成团，点击立即参与"
    };
    window.onload = function(){
        $('.ssd-module-wrap').css('width','100%');
    }
</script>
{pigcms{$shareScript}
<!-- 小程序分享 -->
<script type="text/javascript">
    var ua = window.navigator.userAgent.toLowerCase();
    var is_weixin=false;
    var is_mini=false;
    /*是否在app端*/
    var is_app={pigcms{$is_app_browser|default=0};
    var res_url='';
    var  webview=function(){
        wx.miniProgram.getEnv(function(res) {
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
    }else{
        webview();
    }

    $(document).on('click', '.share_friend', function(){
        if(is_mini){
            //小程序环境
            wx.miniProgram.navigateTo({
                url:res_url
            })
        }else{
            if(is_weixin){
                //微信端
                $("#gride").removeClass("none");
            }else if(is_app){
                shareFriend();
            }
        }
    });
    $('#gride').bind("touchmove",function(e){
        e.preventDefault();
    });
</script>
<script>
    var  View={
        init:function(){
            this.showIndex();
            this.time();
            this.click()

        },
        setting:{

        },
        showIndex:function(){
            this.setting.pic_w=$(window).width()*0.94/2;

            var swiper1 = Simplite.getTemplate('detail');
            var swiper1_item = Simplite.compile(swiper1)({});
            $('.ks-clear').html(swiper1_item);
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
        },
        click:function(){
            $(document).on("click",'#gride',function(){
                $("#gride").addClass("none");
            });
            $('.no_join').click(function(){
                layer.open({
                    content:'抱歉，该团为邀新团，仅限店铺新用户参加！',
                    btn: ['我知道了']
                });
            });
            $('.no_join').click();
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
    
    /*点击分享*/
    function shareFriend(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            iosCommon.iosFunction('shareWeChat', {name:'shareWeChat', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }else{
            window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent, window.shareData.imgUrl, window.shareData.sendFriendLink);
        }
    }
</script>
</body>
</html>