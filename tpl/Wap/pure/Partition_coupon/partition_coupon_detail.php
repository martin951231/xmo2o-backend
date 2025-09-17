<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>瓜分券详情</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/indexDetail.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/icon.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
</head>
<style>
    .del_sing{
        position: absolute;
        width: 35px;
        color: #fff;
        float: right;
        font-size: 21px;    
        right: 45%;
        bottom: 90px;
    }
    .weui-dialog__bd {
        padding: 0 1.6em .8em;
        min-height: 40px;
        font-size: 15px;
        line-height: 1.3;
        word-wrap: break-word;
        word-break: break-all;
        color: #999;
    }
    #gride{
        position:fixed;
        z-index:111;
         left:0px;
         top:0px;
         right:0px;
         bottom:0px;
         background: hsla(0,0%,7%,.7);
    }
    #gride>div{
          height: 100%;
        background-size: 100%;
        background: url(static/activity/assets/image/guide1.png) no-repeat;
        width: 100%;
      background-position: top;
    }
    .time_content span{color:#F6B446; }
    .time {  line-height: 18px; color:#F6B446;  text-align: center;  height: 18px;  width: 18px;  display: inline-block;  padding: 2px;  border-radius: 2px;  }
    .avatar>span {  width: 50px;  height: 50px; text-align:center;  line-height: 40px;  display: inline-block;  vertical-align: middle;  border:1px dashed #ddd;  color: #ddd;  border-radius: 50%;  margin: 5px 5px;  position: relative;  }
    .avatar span.leader {  border: none;  }
    .avatar .ava{  width: 100%;  height: 100%;  border-radius: 50%;  overflow: hidden;  }
    .avatar span.leader .tag{  position: absolute;  top: -4px;  left: 50%;  transform: translate(-50%, 0);  color: #f8f8f8;  background: #f23030;  line-height: normal;  font-size: 10px;  padding: 0 5px;  border-radius: 8px;  white-space: nowrap;  }
    .ping_game .one {  line-height: 16px;  font-size: 12px;  color: #fff;  display: inline-block;  background: #000;  width: 15px;  height: 15px;  border-radius: 50%;  text-align: center;  margin: 0 3px 3px 0;  vertical-align: middle;  }
   
    i.dian{width:2px;height:2px;background:#ccc;border-radius:1px;display:inline-block;vertical-align: inherit;margin: 19px 2px;}
    .align_center{ text-align:center}
    .pb20{padding-bottom:20px !important;}
    .btn{
        border-radius: 19px;
        background: #ff4242;
        color: #fff;
        font-size: 14px;
        width: 80%;
        line-height: 39px;
        margin-bottom: 20px;
        margin: auto;
        /* animation: mymove 1s infinite; */
        -webkit-animation: mymove 1s infinite;
        height: 40px;
    }

    @keyframes mymove
    {
        0% {transform:scale(1);}
        50% {transform:scale(1.1);}
        100% {transform:scale(1);}
    }

    @-webkit-keyframes mymove /*Safari and Chrome*/
    {
        0% {transform:scale(1);}
        50% {transform:scale(1.1);}
        100% {transform:scale(1);}
    }

    .btn-1{
        background: #FFB630;
        margin-top: 20px;
       
    }
    .weui-cell__bd{
        text-align: center;
    }


    .top_img{
        background: url(tpl/Wap/pure/static/images/partition_coupon_bg.png) no-repeat;
        background-size: cover;
        width: 100%;
        background-position: top;
        padding-top: 100%;
        -moz-background-size: 100% 100%;
        object-fit: cover;
    }
    .weui-panel{
        margin: 0 18px;
        z-index: 99;
        /*padding: 2px;    */
        border-radius: 6px;
    }
    .weui-rule {
        position: absolute;
        top: 30px; 
        right: 0;    
        text-align: center;
        z-index: 99;
    }
    .weui-rule .weui-rule-text{
        background: #FFD96F;
        width: 80px;
        padding: 5px;
        border-top-left-radius: 30px;
        border-bottom-left-radius: 30px;
    }
    .weui-rule .weui-rule-text>a{
        color: #F32A17;
        font-size: 14px;
    }
    .align_center{
        color: #FF0236;
        padding:0px 20px;
        font-size: 15px;
        margin-top: 10px;
    }
    .weui-scrollMsg {
        position: absolute;
        top: 12px;
        left: 10px;
        z-index: 99;
        max-width: 95%;
    }
    
    .weui-scrollMsg-active{
        animation: myfirst .6s linear;
    }
    
    @keyframes myfirst {
	0% {
		transform: translateX(-100%);
	}

	100% {
		transform: translateX(0);
	}
}
    .weui-scrollMsg .weui-scrollMsg-text>label{
        height: 40px;
    }

    .weui-scrollMsg2 {
        position: absolute;
        top: 75px; 
        left: 0;    
        z-index: 99;
    }
    .weui-scrollMsg2 .weui-scrollMsg-text>label{
        height: 40px;
    }
    .swiper-container {
        left: 0;    
        z-index: 99;
        background: rgba(0,0,0,.5);
        opacity: 0.9;
        border-radius: 30px;
        color: #fff;       
        font-size: 10px;
        padding: 4px 15px;
    }
    .chip-row ul.swiper-wrapper{position:relative;display:block}
    .chip-row ul.swiper-wrapper>li{
        color: #fff;
        opacity: 1;
    }
    .weui-mask__title{
       padding: 25px;   
       margin-top: 40px;
    }
    .weui-mask__hd{  
       padding: 0 25px;    
       font-size: 16px;
    }
    .rules div{margin-bottom: 10px}

    .del_receive{
        position: absolute;
        width: 35px;
        color: #fff;
        float: right;
        font-size: 21px;    
        right:5px;
        top: 5px;
    } 
    .do_receive{    
        border-radius: 25px;
        background: #FFE10D;
        color: #F64E3C;
        font-size: 18px;
        width: 80%;
        line-height: 45px;
        margin: auto;
        height: 45px;
    }
    .del_receive .iconfont{
        color: #fff;
    }
    .weui-media-box_appmsg .weui-media-box__thumb {
        width: 100%;
        max-height: 100%;
        vertical-align: top;
        border-radius: 100%;
    }
</style>
<body ontouchstart="" style="background:#F5F5F5;">
<div class="page none" >
   <div class="weui-tab" id='showIndex'>
        
    </div>
   <div id="message" class=""></div>
</div>
<div id="production"></div>
<div id="fileImg"></div>
<div id='gride'  style='display:none'>
	<div></div>
</div>
<input type="hidden" id="now_id">
<script>var $home=false</script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script type='text/html' id='showDetail'>
    <div  class="content weui-tab__bd" id='is_top' style="height: 100%">
        <div class=" top_img" >
            <div>
                <div class="swiper-slide">
                    <!-- <image   src="static/activity/assets/image/addMobile.png"/> -->
                </div> 
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <div style="margin-top: -60px">
            <div class="weui-panel tjt-cells" id="main_content"> 
                <div class="align_center avatar">
                    <&if( _this.number) {&>

                        <&if(_this.number>3&&_this.now_detail.uesr_num>5){ var num = 3}else{var num=_this.join_list.length} &>
                        <& for(var i=0;i< num;i++){ &>
                            <& var items=_this.join_list[i]&>
                            <span style="">
                                <img class="ava" src="<&=items.avatar?items.avatar:'{pigcms{$static_path}images/avatar.png'&>">
                            </span>
                        <& } &>
                    <& } &>
                    <&if( _this.number&&_this.diff_num) {&>
                        <&if(_this.now_detail.user_num>5){ &>
                            <& for(var i=0;i< 3-num;i++){ &>
                                <span style="color: #eb0231;background: #ffecf0;border-color: #eb0231; font-size: 25px;line-height: 45px">+</span>
                            <& } &>
                            <span style="color: #eb0231;background: #ffecf0;border-color: #eb0231; font-size: 25px;line-height: 35px">...</span>
                            <span style="color: #eb0231;background: #ffecf0;border-color: #eb0231; font-size: 25px;line-height: 45px">+</span>
                        <& }else{&>
                            <& for(var i=0;i< _this.diff_num;i++){ &>
                                <span style="color: #eb0231;background: #ffecf0;border-color: #eb0231; font-size: 25px;line-height: 45px">+</span>
                            <& } &>
                        <& } &>
                    <& } &>
                    <div class="align_center">
                        <&if(_this.has_hadpull){ &>
                        {pigcms{:L_('还差')}<span class="color_red"><&=_this.diff_num &>{pigcms{:L_('人')}</span>{pigcms{:L_('即可瓜分')}<&=_this.now_detail.money&>{pigcms{$config['Currency_txt']}{pigcms{:L_('红包，邀请好友来瓜分')}
                         
                        <& }else if(_this.share_coupon){&> 
                        <&=_this.share_name &>{pigcms{:L_('邀请您一起瓜分')}<&=_this.now_detail.money&>{pigcms{$config['Currency_txt']}{pigcms{:L_('优惠礼包')}
                        <& }else{&>
                            <&if(_this.is_expir!=1){ &>
                            <div style="">
                                {pigcms{:L_('已为您准备好')}<&=_this.now_detail.money&>{pigcms{$config['Currency_txt']}{pigcms{:L_('红包')}<br/>{pigcms{:L_('点击【领取】开始瓜分')}
                            </div>
                            <img src="{pigcms{$static_path}images/money2.png" style="width: 35px; margin-top: 10px">
                            <& } &>
                        <& } &>
                    </div>

                    <&if(_this.remaining_time){&>
                    <div class="align_center time_content" style="padding:10px 0px 20px;">
                        <span data-clock="<&=_this.remaining_time &>" data-type="1" class="clock">
                            <&if(_this.remaining_day) {&>
                                <span class="color_f time joind d day" style=""> <&=_this.remaining_day &></span>
                                <span  class="day">{pigcms{:L_('天')}</span>
                            <& } &> 
                            <&if(_this.remaining_hour) {&>
                            <span class="color_f time joinh h hourse"><&=_this.remaining_hour &></span><span class="dian hourse">:</span>
                            <& } &> 
                            <&if(_this.remaining_min) {&>
                            <span class="color_f time joinm m"><&=_this.remaining_min &></span><span class="dian">:</span>
                            <& } &> 
                            <&if(_this.remaining_sec) {&>
                            <span class="time color_f joins s"><&=_this.remaining_sec &></span>
                            <& } &> 
                            {pigcms{:L_('后结束')}
                        </span>
                    </div>
                    <& } &>  
                     <&if(_this.is_expire==1){ &>
                        <div class="time_content"><span>{pigcms{:L_('来晚啦！活动已结束啦')}</span></div>
                    <& }else if(_this.is_expire==2){ &>
                        <div class="time_content"><span>{pigcms{:L_('抱歉！您没有在规定有效期内完成瓜分券，点击重新发起')}</span></div>
                    <& }else if(_this.toplimit==1){ &>
                        <div class="time_content"><span>{pigcms{:L_('抱歉！您已超过最多参与次数')}</span></div>
                    <& } &>  
                </div>
                <div class="tjt-cells" style="margin-top:0px;    margin-bottom: 30px;">
                    <div class="weui-cell">
                        <div class="weui-cell__bd">
                        <&if(_this.is_expire==1){ &>
                        <& }else if(_this.is_expire==2){ &>
                            <div class="btn add_new">{pigcms{:L_('重新发起')}</div>
                        <& }else if(_this.toplimit==1){ &>
                        <& }else if(_this.has_hadpull){ &>
                            <div class="btn go_share">
                            <img src="{pigcms{$static_path}images/wx_logo.png" style="width: 20px;vertical-align: middle;margin-right: 3px;">{pigcms{:L_('邀请好友一起来瓜分')}</div>
                        <& }else if(_this.share_coupon){&> 
                            <&if(_this.is_complete==0){ &>
                                <div class="btn add_join">{pigcms{:L_('立即瓜分礼包')}</div>
                            <& } &>
                            <div class="btn btn-1 add_new ">{pigcms{:L_('瓜分新礼包')}</div>
                        <& }else{&>
                            <div class="btn add_new">{pigcms{:L_('领取')}</div>
                        <& } &>
                        </div>
                    </div>
                </div>
            </div>

            <div class="weui-rule">
                <div class="weui-rule-text"><a href="javascript:void(0);" class="agree_ment">{pigcms{:L_('活动规则')}</a></div>
                <div class="weui-rule-text" style="margin-top: 10px;width: 40px;right:0;position: absolute;"><a href="javascript:void(0);" class="back_home">{pigcms{:L_('首页')}</a></div>
            </div>

            <&if(_this.coupon_hadpull_list.length>0){&>
            <div  class="weui-scrollMsg" style="display:none">
                <div class="chip-row">
                    <div class="toutiao-slider swiper-container" id="newsSlider">
                        <ul class="swiper-wrapper  weui-scrollMsg-text">  
                            <li class="swiper-slide" style="list-style:none;">
                                <div>
                                    <label></label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <& } &>

            <&if(_this.coupon_hadpull_list>0){ &>
            <div  class="weui-scrollMsg2" style="">
                <div class="chip-row">
                    <div class="toutiao-slider swiper-container" id="newsSlider2">
                        <ul class="swiper-wrapper  weui-scrollMsg-text">  
                            <& for(var i=_this.coupon_hadpull_list.length-1;i>=0;i--){ &>
                            <& var items=_this.coupon_hadpull_list[i]&>
                            <li class="swiper-slide" style="list-style:none;">
                                <div class='' style="margin-left: 5px; margin-right: 15px;padding-top: 2px;"><label><&=items.nickname &>{pigcms{:L_('获得了')}<&=items.money &>{pigcms{$config['Currency_txt']}{pigcms{:L_('优惠礼包')}</label>
                                </div>
                            </li>
                            <& } &>
                        </ul>
                    </div>
                </div>
            </div>
            <& } &>
            <div id="rules" style="display: none;">
                <div>1. {pigcms{:L_('用户邀请好友参与瓜分券，满')}<&=_this.now_detail.user_num&><if condition="$now_detail['type'] == 0">{pigcms{:L_('人即可平均瓜分总金额为')}<else/>{pigcms{:L_('人即可随机瓜分总金额为')}</if><&=_this.now_detail.money&>{pigcms{$config['Currency_txt']}{pigcms{:L_('的优惠大礼包')}；</div>
                <div>2. {pigcms{:L_('每个用户同一时间最多只可瓜分一个优惠大礼包，每人允许瓜分该礼包')}<&if(_this.now_detail.partition_times == '0'){ &>{pigcms{:L_('无限次')}<& }else{&><&=_this.now_detail.partition_times&>{pigcms{:L_('次')}<& } &>{pigcms{:L_('，超出后则不可再次参与瓜分')}；</div>
                <div>3. {pigcms{:L_('每个优惠大礼包发起后在有效期为')}<&=_this.now_detail.timeStr&>{pigcms{:L_('内未集齐好友，则瓜分失败，无法获得礼包奖励')}；</div>
                <div>4. {pigcms{:L_('领取成功的礼包')}<&=_this.now_detail.useTimeStr&>{pigcms{:L_('有效，过期后失效，无法正常使用')}。</div>
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="aggrement">
    <!-- <div class="weui-mask weui-mask--visible" style="z-index:99"></div> -->
    <div class=" weui-mask weui-mask--visible">
        <div class="weui-mask__title" style="z-index:99">
            <strong class="" style="font-weight: normal;font-size: 18px;color: #fff;">{pigcms{:L_('活动规则')}：</strong>
        </div>
        <div class="weui-mask__hd" style="overflow-y: auto;color: #fff">
            <div class="rules">
               <&=_this &>
            </div>
        </div>
        <a class="del_sing closeHides" href="javascript:;"><span class="iconfont icon-close" style="font-size:35px;color: #fff"></span></a>

    </div>
</script>

<script type="text/html" id="partition">
    <div class="weui-mask weui-mask--visible " style="z-index:99"></div>
    <div class="weui-dialog weui-dialog--visible yui-auth-form" style="border-radius: 10px;">
        <div class="weui-dialog__bd" style="padding: 0;margin-bottom: 20px;">
            <a class="del_receive closeHide" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px;color: #999;"></span></a>
            <img src="tpl/Wap/pure/static/images/smile.png" style="margin: auto;margin-top: 30px; margin-bottom: 30px;">
            <div style="font-size: 20px;">{pigcms{:L_('您已获得一个优惠礼包')}</div>
            <div style="font-size: 20px;">{pigcms{:L_('还差')}<&=_this.surplus_num &>{pigcms{:L_('人即可瓜分')}</div>
            <div style="margin-top: 20px;color: #ff4242;margin-bottom: 20px;font-size: 28px;">{pigcms{$config.Currency_symbol}<&=_this.money &></div><!--<span style="font-size: 18px;">元<span>-->
            <div class="btn go_share">
            <img src="{pigcms{$static_path}images/wx_logo.png" style="width: 20px;vertical-align: middle;margin-right: 3px;">{pigcms{:L_('邀请好友一起来瓜分')}</div>
        </div>
    </div>
</script>

<script type="text/html" id="receive">
    <div class="weui-mask weui-mask--visible " style="z-index:99"></div>
    <div class="weui-dialog weui-dialog--visible yui-auth-form" style="background: #FC3E2D;border-radius: 10px;">
        <div class="weui-dialog__bd" style="padding: 0;margin-bottom: 20px;">
            <a class="del_receive closeHides" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px"></span></a>
            <img src="tpl/Wap/pure/static/images/partition_coupon_r.png" style="    width: 100%;">
            <div style="color: #fff;font-size: 20px;">{pigcms{:L_('好友已集齐')}</div>
            <div style="color: #fff;font-size: 20px;">{pigcms{:L_('立即瓜分吧')}</div>
            <div style="margin-top: 10px;color: #F7E71C;margin-bottom: 10px;font-size: 28px;">{pigcms{$config.Currency_symbol}<&=_this &></div>
            <div class="do_receive">{pigcms{:L_('点击领取')}</div>
        </div>
    </div>
</script>

<script type="text/html" id="receive_detail">
    <div class="align_center avatar">
        <div class="align_center">
          <div>{pigcms{:L_('恭喜，已成功获得')}</div>
          <div>{pigcms{$config["Currency_symbol"]}<span style="    font-size: 35px;"><&=_this.money &></span></div>
        </div>
    </div>
    <div class="tjt-cells" style="margin-top:0px;    margin-bottom: 30px;">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <div class="btn go_index"><a>{pigcms{:L_('立即使用')}</a></div>
                <div class="btn btn-1 add_new ">{pigcms{:L_('瓜分新优惠')}</div>
            </div>
        </div>
    </div>
    <div style="text-align: center;font-size: 18px;background: #F5F5F5;padding: 15px 0;">{pigcms{:L_('好友手气')}</div>
    <div class="tjt-cells" style="margin-top:0px;">

        <& for(var i=0;i< _this.hadpull_list.length;i++){ &>
            <& var items=_this.hadpull_list[i]&>
        <div class="weui-media-box weui-media-box_appmsg">   
            <div class="weui-media-box__hd">       
                <img class="weui-media-box__thumb" src="<&=items.avatar?items.avatar:'{pigcms{$static_path}images/avatar.png' &>" alt="">
            </div>   
            <div class="weui-media-box__bd">
                <p class="resume_label">       
                    <label style="    font-size: 15px;"><&=items.nickname &></label>
                </p>
            </div>   
            <div class="" style="color: #FF0236;">
                <div style="color: #FF0236;font-size: 20px;display: inline-block;margin-right: 2px" >{pigcms{$config.Currency_symbol}<&=items.money &></div>
            </div>
        </div>
        <& } &>
    </div>
</script>

<script>
        var is_sign ="{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/common.js?t=1"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script> 
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript">
var share_url = "{pigcms{$config.site_url}{pigcms{:U('partition_coupon_detail', array('id' => $now_detail['pigcms_id'],'type'=>$_GET['type'],'share_uid'=>$user_session['uid']))}";

var wxapp_share_url = "{pigcms{:D('House_village')->home_url}?redirect=webview&webview_url=" + encodeURIComponent(share_url);
var desc="{pigcms{$now_detail['name']}";
var name="【{pigcms{:L_('X1@我】邀您瓜分X2X3优惠大礼包',['X1'=>$_SESSION['user']['nickname'],'X2'=>$now_detail['money'],'X3'=>$config['Currency_txt']])}";
var img_url = "{pigcms{$now_detail['img_url']}";
if (img_url) {
    var share_pic="{pigcms{:file_domain().$now_detail['img_url']}";
}else{
    var share_pic="{pigcms{$static_path}images/partition_coupon_share.png";
}
window.shareData = {
    "moduleName":"Coupon",
    "moduleID":"0",
    "imgUrl": share_pic,
    "sendFriendLink": share_url,
    "tTitle": "【{pigcms{:L_('X1@我】您有一个待领取的优惠大礼包',$_SESSION['user']['nickname'])}",
    "tContent": "{pigcms{:L_('邀您瓜分X1X2优惠大礼包，点击查看',['X1'=>$now_detail['money'],'X2'=>$config['Currency_txt']])}"
};
;
// 弹出领取弹层
function receive_show(id){
    $('#now_id').val(id);
    var tpl = Simplite.getTemplate('receive');
    var money={pigcms{$now_detail['money']|floatval};
    var compiledTpl = Simplite.compile(tpl)(money);
    $('#production').html(compiledTpl);
    $('#production').show()
}
</script>
{pigcms{$shareScript}
<script type="text/javascript">
    /*wap端非微信环境提示到微信端分享*/
    function wap_share_tip(flag){
        if(share_clock==1){
            return false;
        }
        layer.open({
            content:'<p style="text-align:center;">请在微信端分享<p>',
            btn:['我知道了'],
            yes:function(index,dom){
                layer.close(index);
            }
        });
    }
    /* 小程序分享*/
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
                res_url='/pages/index/share?title='+encodeURIComponent("{pigcms{$now_detail['name']}")+'&shareTitle='+encodeURIComponent("{pigcms{$now_detail['name']}")+'&shareImg='+encodeURIComponent(share_pic)+'&sharePath='+encodeURIComponent(wxapp_share_url)
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
    // 点击分享
    $(document).on('click', '.go_share', function(){
        if(is_mini){
            //小程序环境
            wx.miniProgram.navigateTo({
                url:res_url
            })
        }else{
            if(is_weixin){
                //微信端
                $("#gride").show()
            }else if(is_app){
                shareFriend();
            }else{
                wap_share_tip(1);
            }
        }
    });
</script>
 <!-- app分享 -->
<if condition="$is_app_browser">
<script type="text/javascript">
    if("{pigcms{$app_browser_type}" == 'android'){
        window.lifepasslogin.shareLifePass(name,desc,share_pic,share_url);
    } else {
        iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc });
    }
    /*点击分享事件*/
    function shareFriend(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            iosCommon.iosFunction('shareWeChat', {name:'shareWeChat', imgUrl: share_pic, sendFriendLink:share_url, tTitle:name, tContent:desc });
        }else{
            window.lifepasslogin.shareWeChat(name,desc,share_pic,share_url);
        }
    }
</script>
</if>

<script>
    var image_b = [];
    var agreement = '{pigcms{$config.activity_offline_agreement|html_entity_decode=###}';
    var userBuyInfo = '';
    var userBuyAnim = false;
    (function() {
        var timer = function(options) {
            this.options = $.extend({}, timer.defaults, options);
            // this.init();
            this.initChange()
        };
        timer.defaults = {
            time:0,
            setTimer:null,
            initTimer:0,
            onBackTime:null,
            parentClass:'',
            initTime:false,
            max_close_time:0
        };
        timer.prototype.init = function() {
            var self = this;
            if(self.options.time >= 0  ) {
                var days = parseInt(self.options.time / 1000 / 60 / 60 / 24, 10); //计算剩余的天数
                var hours = parseInt(self.options.time / 1000 / 60 / 60 % 24, 10); //计算剩余的小时
                var minutes = parseInt(self.options.time / 1000 / 60 % 60, 10); //计算剩余的分钟
                var seconds = parseInt(self.options.time / 1000 % 60, 10); //计算剩余的秒数
                days = checkTime(days);
                hours = checkTime(hours);
                minutes = checkTime(minutes);
                seconds = checkTime(seconds);
                var t=[days,hours,minutes,seconds]
                if(self.options.parentClass!=''){
                    t={
                        times:t,
                        className:self.options.parentClass
                    }
                }
                self.options.onBackTime && self.options.onBackTime.call(self, t);
            } else {
                clearInterval(self.options.setTimer);
                var t=["00","00","00","00"]
                if(self.options.parentClass!=''){
                    t={
                        times:t,
                        className:self.options.parentClass
                    }
                }
                self.options.onBackTime && self.options.onBackTime.call(self, t);
            }
            function checkTime(i) { //将0-9的数字前面加上0，例1变为01
                if(i < 10) {
                    i = "0" + i;
                }
                return i;
            }
        };
        timer.prototype.initChange=function(){
            var self = this;
            self.options.setTimer = setInterval(function() {
                self.options.initTimer++;
                self.options.time = self.options.time - 1000;
                self.init();
                if(self.options.initTime && self.options.initTimer % 3 ==0 ){
                    clearInterval(self.options.setTimer);
                     $.ajax({
                    url:'/wap.php?c=Mall&a=get_distance_time',
                    type:'post',
                    dataType:'json',
                    data:{"end_time":self.options.max_close_time},
                    async:false,
                    success:function(res){
                    	self.options.time =res*1*1000
                         self.initChange()
                    },
                    error:function(){
                    }
                });
                 }  
            }, 1000);
        }
        APP.timer = function(options) {
            return new timer(options);
        };
    }());

    var View={
        wh:0,
        init:function(){
            this.initDetail();
            this.function();
            this.time();
         },
         setting:{
            share_uid:'',
            openid:'',
            id:'',
            agreecontent:''
         },
        function: function () {
            var _this=this;
            $('body *').click(function(){
                
            });
            $(document).on("click",'.go_index',function(e){
                window.location.href='{pigcms{:U("Home/index")}';
            })
            
            $(document).on("click",'.add_new',function(e){
                var send_data = {
                    id:_this.setting.id,
                    share_uid:_this.setting.share_uid,
                    type:_this.setting.type,
                    partition_type:1,
                }
                e.stopPropagation();e.preventDefault();
                $.ajax({
                    url: "{pigcms{:U('partition_coupon_join')}",
                    dataType : "json",
                    data:send_data,
                    type : "post",
                    success: function (data) {
                        if (data.errorCode) {
                            $.toast(data.errorMsg);
                        } else {
                            if (data.result.complete) {
                                //好友已集齐
                                receive_show(data.result.id)
                            }else{
                                var tpl = Simplite.getTemplate('partition');
                                var compiledTpl = Simplite.compile(tpl)(data.result);
                                $('#main_content').html(compiledTpl);
                            }
                        }
                    },
                    error:function(data){
                        console.log(data)
                    }
                });
            })
            $(document).on("click",'.add_join',function(e){
                var send_data = {
                    id:_this.setting.id,
                    share_uid:_this.setting.share_uid,
                    type:_this.setting.type,
                    partition_type:2,
                }
                $.showLoading('加载中');
                e.stopPropagation();e.preventDefault();
                $.ajax({
                    url: "{pigcms{:U('partition_coupon_join')}",
                    dataType : "json",
                    data:send_data,
                    type : "post",
                    success: function (data) {
                        if (data.errorCode) {
                            $.toast(data.errorMsg);
                        } else {
                            if (data.result.complete) {
                                //好友已集齐
                                receive_show(data.result.id)
                            }else{
                                // $.toast(data.result.msg);
                                var tpl = Simplite.getTemplate('partition');
                                var compiledTpl = Simplite.compile(tpl)(data.result);
                                $('#main_content').html(compiledTpl);

                                setTimeout(function (argument) {
                                    // location.reload()
                                },1000)
                            }
                        }
                        $.hideLoading();
                    },
                    error:function(data){
                        $.hideLoading();
                        console.log(data)
                    }
                });
            })

            // 开始瓜分
            $(document).on("click",'.do_receive',function(e){
                var now_id = $('#now_id').val();
                var send_data = {
                    id:_this.setting.id,
                    type:_this.setting.type,
                    id:now_id
                }
                e.stopPropagation();e.preventDefault();
                $.ajax({
                    url: "{pigcms{:U('partition_coupon_receive')}",
                    dataType : "json",
                    data:send_data,
                    type : "post",
                    success: function (data) {
                        if (data.errorCode) {
                            $.toast(data.errorMsg);
                        } else {
                            $.toast(data.result.msg);
                            setTimeout(function (argument) {
                                var tpl = Simplite.getTemplate('receive_detail');
                                var compiledTpl = Simplite.compile(tpl)(data.result);
                                $('#main_content').html(compiledTpl);
                                hideMobile()
                            },1000)
                        }
                    },
                    error:function(data){
                        console.log(data)
                    }
                });
            })

            $(document).on("click",'.closeHide',function(){
                location.reload()
                // hideMobile(false,true)

            })
            $(document).on("click",'.closeHides',function(){
                hideMobile(false,true);
            })

            var hideMobile=_this.hideMobile=function(type){
                $(".yui-auth-form").addClass("close-animated");
                $(".yui-auth-layer").addClass("close-animated");
                $("#production").html("")
                if(type){window.history.back(-1)}
            }
			
            setTimeout(function (argument) { 
                if($('#newsSlider2').length>0){
                    var newsObj = $('#newsSlider2');
                    var newsSlider = new Swiper('#newsSlider2', {
                        autoplay: typeof newsObj.data('autoplay')!=='undefined' ? newsObj.data('autoplay') : 3000,
                        spped:  typeof newsObj.data('spped')!=='undefined' ? newsObj.data('spped') : 1000,
                        direction:'vertical',
                        onlyExternal:true,
                        loop: true
                    });
                }  
            },1000)
           

            $(document).on("click",'.agree_ment',function(){
                var tpl = Simplite.getTemplate('aggrement');
                var rules=$('#rules').html()
                var compiledTpl = Simplite.compile(tpl)(rules);
                $('#production').html(compiledTpl);
                 $('#production').show()
            })

            $(document).on("click",'.back_home',function(){
                location.replace(location.origin + '/wap.php')
            })

            $("#gride").on("click",function(){
                $(this).hide()
            })

        },
        initDetail:function(){
            var _this=this;
            $.showLoading('加载中');
            var href=window.location.href;
            var ss=href.lastIndexOf('=')+1
            _this.setting.id="{pigcms{$Think.get.id}";
            _this.setting.type="{pigcms{$Think.get.type}";
            _this.setting.share_uid="{pigcms{$Think.get.share_uid}";
            _this.setting.openid="{pigcms{$Think.get.openid}";
            var send_data = {
                'id':_this.setting.id,
                'share_uid':_this.setting.share_uid,
                'openid':_this.setting.openid,
                'type':_this.setting.type
            }
            APP.http("{pigcms{:U('ajax_partition_coupon_detail')}",send_data,function(data){
                if(data.errorMsg!='success'){
                    $.hideLoading();
                    $.alert(data.errorMsg)
                }else{
                    // 分享用户id
                    _this.setting.share_uid=data.result.share_uid;

                    var tpl = Simplite.getTemplate('showDetail');
                    var compiledTpl = Simplite.compile(tpl)(data.result);
                    $("#showIndex").html(compiledTpl);
                    $(".page").fadeIn();
                    $.hideLoading();
                    if (data.result.is_complete) {
                        receive_show(data.result.has_hadpull.id)
                    }
                    if (data.result.coupon_hadpull_list.length > 0) {
        				userBuyInfo = data.result.coupon_hadpull_list[0]
        				$('.weui-scrollMsg').addClass('weui-scrollMsg-active')
        				$('.weui-scrollMsg').fadeIn();
        				$('#newsSlider label').text(userBuyInfo.nickname + "{pigcms{:L_('获得了')}" + userBuyInfo.money + '{pigcms{$config['Currency_txt']}{pigcms{:L_('优惠礼包')}')
        				setTimeout(() => {
        					userBuyAnim = false
        					userBuyInfo = ''
        					$('.weui-scrollMsg').fadeOut();
        					$('.weui-scrollMsg').removeClass('weui-scrollMsg-active')
        					setUserBuy(1, data.result.coupon_hadpull_list);
        				}, 5000);
        			}
                }
                function setUserBuy (num, msg){
            		setTimeout(() => {
            			if (num < msg.length) {
            				userBuyInfo = msg[num]
            				userBuyAnim = true
            	            $('.weui-scrollMsg').addClass('weui-scrollMsg-active')
            	            $('.weui-scrollMsg').fadeIn();
        				    $('#newsSlider label').text(userBuyInfo.nickname + "{pigcms{:L_('获得了')}" + userBuyInfo.money + '{pigcms{$config['Currency_txt']}{pigcms{:L_('优惠礼包')}')
            				setTimeout(() => {
            					userBuyAnim = false
            					userBuyInfo = ''
            					$('.weui-scrollMsg').fadeOut();
            	                $('.weui-scrollMsg').removeClass('weui-scrollMsg-active')
            					num++;
            					setUserBuy(num, msg);
            				}, 5000);
            			} else {
            				userBuyInfo = ''
            				$('.weui-scrollMsg').fadeOut();
            				$('.weui-scrollMsg').removeClass('weui-scrollMsg-active')
            			}
            		}, 2000);
            	}; 
             },function(data){
                $.hideLoading()
                    $.alert({
                         title: '提示',
                         text: '加载失败',
                         onOK: function () {
                                //点击确认
                              }
                      });
             },'post')
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
</body>
</html>