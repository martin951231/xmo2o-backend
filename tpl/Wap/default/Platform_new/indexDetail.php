<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动详情</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ($static_public); ?>jqueryweui/swiper.css"/> 
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/indexDetail.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/icon.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    

</head>
<style>
   .weui-masks{
   	    position: fixed;
    z-index: 20;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    opacity: 1;
    visibility: visible;
        -webkit-transition-duration: .3s;
    transition-duration: .3s;
        background: rgba(0,0,0,.6);
   }
    .no_border-radius{
        border-radius:0px;
        margin-right: unset;
        width:55% !important;
    }
    .no_border-radius:after{
        border-radius:0px
    }
    .del_sing{
        position: absolute;
        width: 20px;
        color: #bbb;
        float: right;
        font-size: 21px;
        right: 12px;
        top: 0px;
    }
     .pin-bg {
        width: 97px;
        height: 16px;
        background: url(static/activity/assets/image/starIconBg.png) no-repeat;
        background-size: 100%;
        display: inline-block;
         margin-left: 12px;
    }
     .pin-bg1 {
         height: 20px;
         display: inline-block;
        background: url(static/activity/assets/image/starIcon.png) no-repeat;
    }
    .no_is_owner:after {
        content: " ";
        position: absolute;
        bottom: 0;
        right: 0;
        height: 1px;
        border-top: 1px solid #e5e5e5;
        color: #e5e5e5;
        -webkit-transform-origin: 0 0;
        transform-origin: 0 0;
        -webkit-transform: scaleY(.5);
        transform: scaleY(.5);
        left: 15px;
        z-index: 2;
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
.weui_check_label {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}
.weui_cell {
    padding: 10px 15px;
    position: relative;
    display: -webkit-box;
    display: -webkit-flex;
    display: flex;
    -webkit-box-align: center;
    -webkit-align-items: center;

    align-items: flex-start;
}
.weui_cells_checkbox .weui_cell_hd {
    padding-right: .35em;
}
.weui_check {
    position: absolute;
    left: -9999em;
}
[class*=" weui_icon_"]:before, [class^=weui_icon_]:before {
    font-family: weui;
    font-style: normal;
    font-weight: 400;
    speak: none;
    display: inline-block;
    vertical-align: middle;
    text-decoration: inherit;
    width: 1em;
    margin-right: .2em;
    text-align: center;
    font-variant: normal;
    text-transform: none;
    line-height: 1em;
    margin-left: .2em;
}
[class*=" weui_icon_"]:before, [class^=weui_icon_]:before {
    margin: 0;
}
.weui_cells_checkbox .weui_icon_checked:before {
    content: '\EA01';
    color: #c9c9c9;
    font-size: 23px;
    display: block;
}
.weui_cells_checkbox .weui_icon_checked:before {
    font-size: 18px !important;
}
.weui_cells_checkbox .weui_check:checked+.weui_icon_checked:before {
    content: '\EA06';
    color: #09bb07;
}
.weui-prompt-inputs {
       outline: 0;
    -webkit-appearance: none;
    background-color: transparent;
    font-size: inherit;
    color: inherit;
padding: 4px 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
height: 85px;
    width: 80%;
    margin-top: 10px;
    line-height: 1.41176471;
}
.back_modal{
	    top: 15px;
    float: right;
    height: 25px;
    position: absolute;
    width: 25px;
    width: 25px;
    left: 7px;
}
.back_modal:after {
        content: " ";
    display: inline-block;
    height: 10px;
    width: 10px;
    border-width: 0px 0px 2px 2px;
    border-color: #c8c8cd;
    border-style: solid;
    -webkit-transform: matrix(.71,.71,-.71,.71,0,0);
    transform: matrix(.71,.71,-.71,.71,0,0);
    position: relative;
    top: -2px;
    position: absolute;
    top: 50%;
    margin-top: -4px;
    right: 2px;
}
.weui-dialogs{
	    z-index: 30;
    background-color: #fff;
    width: 300px;
    min-height: 150px;
    border-radius: 3px;
    top: 20%;
    left: 50%;
    margin-left: -150px;
    text-align: center;
    position: fixed;
}
#gride{
	position:fixed;
	z-index:100;
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
 
#pageLoadTip{display:none !important;}
.tjt_border_t:before{  top: -1px !important;  }
.tjt_border_b:before{  bottom: -1px !important;  }
.tjt_border_r:before{  right: -1px !important;  }
.tjt_border_l:before{  left: -1px !important;  }
    .time {  line-height: 18px;  background: #F38144; color:#fff;  text-align: center;  height: 18px;  width: 18px;  display: inline-block;  padding: 2px;  border-radius: 2px;  }
.avatar>span {  width: 40px;  height: 40px; text-align:center;  line-height: 40px;  display: inline-block;  vertical-align: middle;  border:1px dashed #ddd;  color: #ddd;  border-radius: 50%;  margin: 5px 5px;  position: relative;  }
.avatar span.leader {  border: none;  }
.avatar img{  width: 100%;  height: 100%;  border-radius: 50%;  overflow: hidden;  }
.avatar span.leader .tag{  position: absolute;  top: -4px;  left: 50%;  transform: translate(-50%, 0);  color: #f8f8f8;  background: #f23030;  line-height: normal;  font-size: 10px;  padding: 0 5px;  border-radius: 8px;  white-space: nowrap;  }
.ping_game .one {  line-height: 16px;  font-size: 12px;  color: #fff;  display: inline-block;  background: #000;  width: 15px;  height: 15px;  border-radius: 50%;  text-align: center;  margin: 0 3px 3px 0;  vertical-align: middle;  }
#gride {  position: fixed;  z-index: 100;  left: 0px;  top: 0px;  right: 0px;  bottom: 0px;  background: hsla(0,0%,7%,.7);  }
#gride>div {  height: 100%;  background-size: 100%; background: url(https://hf.pigcms.com/tpl/Wap/pure/static/mall/images/guide1.png) no-repeat;  width: 100%;  background-position: top;  }
.no_join{background: #ccc;}
i.dian{width:2px;height:2px;background:#ccc;border-radius:1px;display:inline-block;vertical-align: inherit;margin: 19px 2px;}
.align_center{ text-align:center}
.pb20{padding-bottom:20px !important;}
.weui-photo-browser-modal{
    z-index:999
}

</style>
<body ontouchstart="">
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
<script type='text/html' id='showDetail'>
     <div  class="content weui-tab__bd" id='is_top'>
            <div class="weui-panel tjt-cells">  
                <div class="activity_image1 swiper-container" >
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <image   src="<&= _this.image&& _this.image!=''?_this.image:'static/activity/assets/image/addMobile.png' &>" />
                        </div>
                        <div class="swiper-slide">
                            <image   src="<&= _this.image&& _this.image!=''?_this.image:'static/activity/assets/image/addMobile.png' &>" />
                        </div> 
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
              
                <div class="weui-cell" style='padding-bottom:0px'>
                        <div class="weui-cell__bd weui-cell__ft" style='font-size:16px'>
                        <& if(_this.examine_status==2) {&>
                        <span class="title_right share" style='background:transparent;cursor: pointer;'><img src='static/activity/assets/image/share.png' style='width:20px;height:20px'/></span>
                        <& }&>
                            <& if(_this.examine_status==2) {&>
                                <& if(!_this.is_sign_end &&  !_this.is_end && _this.status==1) {&>
                                <span class="title_right">进行中</span>
                                <&} else if(_this.status==2) {&>
                                <span class="title_right" style='background:#ddd'>已取消</span>
                                <& }&>
                                <& if(_this.status!=2 && _this.is_end) {&>
                                <span class="title_right" style='background:#ddd'>已结束</span>
                                <&} else if(_this.status!=2 &&  _this.is_sign_end && !_this.is_end) {&>
                                <span class="title_right" style='background:#ddd'>报名已结束</span>
                                <& }&>
                            <&}else if(_this.examine_status==1){&>
                                <span class="title_right">审核中</span>
                            <&}else{&>
                                <span class="title_right">审核未通过</span>
                            <&}&>

                        </div>
                 </div>
                 <div class="weui-panel__bd">
                    <div class="weui-media-box weui-media-box_text title_wrapper" style="padding-bottom:0px;    padding-top: 0px;">
                        <div class="weui-media-box__title weui-media-box__title_in-text title" style="white-space:normal;"><&=_this.title &></div>
                    </div>
                </div>

                 
                <div class="align_center pb20 avatar">
                        <h4 class="align_center font_size14" style="padding:10px 0px 20px;">
                            还差<span class="color_red">9人</span>拼团成功，距拼团结束还剩
                                <span data-clock="84785" data-type="1" class="clock">
                                    <span class="color_f time joind d day" style="display: none;">00</span>
                                    <span style="color: rgb(199, 67, 2); display: none;" class="day">天</span>
                                    <span class="color_f time joinh h hourse">22</span><span class="dian hourse">:</span>
                                    <span class="color_f time joinm m">01</span><span class="dian">:</span>
                                    <span class="time color_f joins s">34</span>
                                </span>                        
                        </h4>

                        <span class="leader">
                                <img class="" src="https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJQFmibnu7A2icwMtUV99jgkWibEHaa2COib369GRvwMtiap5ZAUwWHbdWRQBka4I9MqAjX20rGkM4ricwA/132">
                                <span class="tag">团长</span>                                </span>                            <span>?</span><span>?</span><span>?</span><span>?</span><span>?</span><span>?</span><span>?</span><span>?</span><span>?</span>                      
                         </div>
                </div>  


               
                <div class="weui-cells tjt-cells" style="margin-top:0px">
                    <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-time'></span>
                        </div>
                        <div class="weui-cell__bd">
                            <p><&=_this.start_time &> 至 <&=_this.end_time &></p>
                        </div>
                    </div>
                   <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-dizhi'></span>
                        </div>
                        <div class="weui-cell__bd">
                            <p><&=_this.address &></p>
                        </div>
                    </div>
                    <&if(_this.address_name !='') {&>
                    <div class="weui-cell navi" data-lng="<&=_this.longitude &>" data-lat="<&=_this.latitude &>" data-name="<&=_this.title &>" data-address="<&=_this.address_name &>">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-quyu'></span>
                        </div>
                        <div class="weui-cell__bd">
                            <p><&=_this.address_name &></p>
                        </div>
                    </div>
                    <&}&>
                     <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-fenlei'></span>
                        </div>
                        <div class="weui-cell__bd">
                            <p><&=_this.first_cate_name &>  <&=_this.second_cate_name &></p>
                        </div>
                    </div>
                    <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-people'></span>
                        </div>
                        <div class="weui-cell__bd">
                            <p>已报名<&= (_this.number&& _this.number!=''?_this.number:'0') &>人 / <&= (_this.limit&& _this.limit!='0'?'限'+_this.limit+'人':'不限制名额') &></p>
                        </div>
                    </div>
                    <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='iconfont icon-price'></span>
                        </div>
                        <div class="weui-cell__bd " data-fees='' >
                           <& if(_this.fees && _this.fees!='' && (_this.fees*1)!=0) {&>
                                 <p>{pigcms{$config['Currency_symbol']}<&=_this.fees &></p>
                             <& }else{&>
                             <p>免费</p>
                           <& } &>
                        </div>
                    </div>
                    <& if(_this.deadline && _this.deadline!='' ) {&>
                       <div class="weui-cell">
                          <div class="weui-cell__hd">
                            <span class='iconfont icon-naozhong1'></span>
                          </div>
                           <div class="weui-cell__bd">
                            <p>报名截止时间：<&=_this.deadline &></p>
                           </div>
                       </div>      
                    <& } &>

                    <& if(_this.activity_nickname) {&>
                    <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='icon iconfont'>&#xe61b;</span>
                        </div>
                        <div class="weui-cell__bd">
                            <a href="tel:<&=_this.phone &>"style="color: #333;">联系人：<&=_this.activity_nickname &></a>
                        </div>
                    </div>
                    <& } &>

                    <& if(_this.phone > 0) {&>
                    <div class="weui-cell">
                        <div class="weui-cell__hd">
                            <span class='icon iconfont'>&#xe60e;</span>
                        </div>
                        <div class="weui-cell__bd">
                            <a href="tel:<&=_this.phone &>"style="color: #333;">联系电话：<&=_this.phone &></a>
                        </div>
                    </div>
                    <& } &>
                    
                    <!-- <div class="weui-cell">
                        <div class="weui-cell__hd" style="position: relative;margin-right: 10px;">
                            <img src="static/activity/assets/image/addMobile.png" style="width: 50px;display: block">
                        </div>
                        <div class="weui-cell__bd">
                            <p>发布者</p>
                            <p style="font-size: 13px;color: #888888;">今天</p>
                        </div>
                    </div> -->
                    <div style="height:15px;background:#f8f8f8"></div>
                    <div class="weui-cell tjt-cell">
                        <div class="weui-cell__bd" style="border-left:3px solid #16B990;padding-left:5px">活动详情</div>
                    </div>
                    <div class="weui-cell huo_detail" style='word-break:break-all;'>
                        <&=_this.desc &>
                    </div>
                    <div class="weui-cell tjt-cell weui-cell_access  <& if(View.is_owner(_this)) {&>  no_is_owner <&}&>">
                         <a style="padding:0px;width:100%" class="weui-cell weui-cell_access" href="{pigcms{:U('Platform/activityCommentList')}&activity_id=<&=_this.activity_id&>">
                             <div class="weui-cell__hd" style="border-left:3px solid #16B990;padding-left:5px">
                                 活动评论
                             </div>
                             <div class="weui-cell__hd pin-bg" >
                                 <div class="pin-bg1" style="width:<&=(_this.reply_star*20+1)&>%;background-size: 97px 16px"></div>
                             </div>
                             <div class="weui-cell__hd" style="padding-left:10px;    color: #ff9c00; font-weight: bold;">
                                 <&=_this.reply_star&>
                             </div>
                             <div class="weui-cell__bd" style="text-align:right">查看评论</div>
                             <div class="weui-cell__ft"></div>
                         </a>
                    </div>

                    <& if(_this.pay_status == 1 && _this.fees !=0 && View.is_owner(_this)) {&>
                         <button style="width:90%" class="weui-btn weui-btn_primary go_pay" type="button" data-uid="<&= _this.uid&>" data-id="<&= _this.activity_id&>"  style="background-color:#16B990;line-height:2">去付款</button>
                    <&}else if(_this.pay_status == 1 && _this.fees !=0){&>
                         <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>待付款</button>
                    <&}&>
                    <& if(_this.examine_status && _this.examine_status!='') {&>
                        <& if(_this.examine_status==3) {&>
                            <div class="weui-cell tjt-cell">
                                <div class="weui-cell__bd" style="border-left:3px solid #16B990;padding-left:5px">审核状态</div>
                            </div>
                            <div class="weui-cell" style='word-break:break-all;'>
                                未通过：<&=_this.reject_message &>
                            </div>
                        <&}&>
                    <&}&>
                    <div class="weui-cell tjtAgree" style=" padding: 5px 0px;;display:none">
                    <label for="weuiAgree" class="weui-agree" style="display:inline;padding-right:0px">
                        <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox" >
                        <span class="weui-agree__text">阅读并同意</span>
                    </label>
                    <span class="weui-agree" style="display:inline;padding:0px">
                         <span class="weui-agree__text"><a href="javascript:void(0);" class="agree_ment">《报名活动协议》</a></span>
                     </span>
                </div>
                </div>
            </div>
        </div>
       <div <& if(!View.is_owner(_this) || (_this.is_manager == 1)) {&> >
        <div style="position:fixed;z-index:50; <& if((View.is_owner(_this,true) && View.examine_status()==1)||(View.is_owner(_this,true) && View.examine_status()==2 && _this.is_top && _this.is_top!=1)) {&>  padding:0px  <& }&> " class="weui-tabbar" id='is_bottom'>
            <& if(View.is_owner(_this,true) && View.examine_status()==1 && !(_this.pay_status == 1 && _this.fees >0)) {&>
                <button class="weui-btn weui-btn_primary shenhe no_border-radius set_top" >去审核</button>
            <&}else if(View.is_owner(_this,true) && View.examine_status()==2 && _this.is_top && _this.is_top!=1 ){&>
               <button class="weui-btn weui-btn_primary zhiding set_top no_border-radius">去置顶</button>
            <&}else{&>
                <& if(_this.is_end) {&>
                    <&if(_this.status !=2 && _this.is_end) {&>
                        <& if(View.is_owner(_this)) {&>
                          <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动已结束</button>
                        <&}else if(!_this.is_already_reply){&>
                           <button class="weui-btn weui-btn_primary comment " style="width:90%" data-id="{pigcms{:U('Platform/activityComment')}&activity_id=<&=_this.activity_id&>">发布评论</button>
                        <&}&>

                    <&}else if(_this.status==2 ){&>
                        <& if(View.is_owner(_this)) {&>
                            <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动已结束</button>
                        <&}else if(!_this.is_already_reply){&>
                            <button class="weui-btn weui-btn_primary comment " style="width:90%" data-id="{pigcms{:U('Platform/activityComment')}&activity_id=<&=_this.activity_id&>">发布评论</button>
                        <&}&>

                    <&}else{&>
                       <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动报名已结束</button>
                    <&}&>
                <&}else{&>
                   <& if(_this.status==1) {&>
                       <& if(_this.is_owner==0 && !(_this.pay_status == 1 && _this.fees >0)) {&>
                    <!--不是自己创建-->
                          <& if(_this.is_join==1) {&>
                             <& if(_this.join_status==1) {&>
                                <button class="weui-btn mini-btn  bg-ddd" style="width:90%;margin-top: 0px;" disabled>已报名</button>
<!--                                <& if(_this.can_signup) {&>-->
<!--                                    <& if((_this.status=='1' && _this.join_status==1) || _this.order.status==1){&>-->
<!--                                        <button class="weui-btn mini-btn finishOne" type="button" data-join="<&= _this.join_id &>" data-activity="<&= _this.activity_id &>" style="background-color:#1AAD19;width:90%;margin-top: 0px;margin-left: 10px;">签到</button>-->
<!--                                    <&}&>-->
<!--                                <&}&>-->
                             <&} else if(_this.join_status==2) {&>
                                 <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动已完成</button>
                             <&} else if(_this.join_status==3) {&>
                                    <& if(_this.order.status == 2 && _this.fees !=0) {&>
                                        <button style="width:90%" class="weui-btn weui-btn_primary go_pay_join" type="button" data-uid="<&= _this.uid&>" data-id="<&= _this.activity_id&>"  style="background-color:#16B990;line-height:2">去付款</button>
                                    <&} else {&>
                                        <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>待付款</button>
                                     <& } &>
                            <&} else if(_this.join_status==4) {&>
                                <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>已退款</button>
                            <&} else if(_this.join_status==5) {&>
                                <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>已参加</button>
                            <&} else if(_this.join_status==6) {&>
                                <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>已取消</button>
                            <&} else {&>
                                <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>报名失败</button>
                             <& } &>
                          <&}else{&>
                             <& if(_this.number && _this.number!='') {&>
                                <& if(_this.limit && _this.limit*1<=_this.number*1 && _this.limit*1 != 0) {&>
                                    <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>报名已满</button>
                                <&}else{&>
                                    <& if(_this.enroll_status==1 ) {&>
                                       <button class="weui-btn weui-btn_primary signUp " style="width:90%">我要报名</button>
                                    <&}else{&>
                                       <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>报名已结束</button>
                                    <& } &>
                                <& } &>
                             <&}else{&>
                                <& if(_this.enroll_status==1) {&>
                                   <button class="weui-btn weui-btn_primary signUp " style="width:90%">我要报名</button>
                                <&}else{&>
                                   <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>报名已结束</button>
                                <& } &>
                             <& } &>
                          <& } &>
                        <& } &>
                   <&} else if(_this.status==3) {&>
                        <& if(View.is_owner(_this)) {&>
                          <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动已结束</button>
                        <&}else{&>
                            <button class="weui-btn weui-btn_primary comment " style="width:90%" data-id="{pigcms{:U('Platform/activityComment')}&activity_id=<&=_this.activity_id&>">发布评论</button>
                        <&}&>

                   <&}else{&>
                      <button class="weui-btn weui-btn_primary  bg-ddd" style="width:90%" disabled>活动已取消</button>
                   <& } &>
                <&}&>
            <& }&>
        </div>
     </div>
     <&}&>
</script>
<script type="text/html" id="sing">
    <div class="weui-masks " style="z-index:20"></div>
        <& if(View.examine_status()==1) {&>
                <div class="weui-dialogs weui-dialog--visible yui-auth-form  auth-animated">
		            <div class="weui-dialog__hd" style="position:relative">
		                <strong class="weui-dialog__title" style="    font-weight: normal;font-size: 16px;">活动审核</strong>
		                <a class="del_sing closeHide" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px"></span></a>
		            </div>
		            <div class="weui-dialog__bd weui_cells_checkbox">
		                <label class="weui_cell weui_check_label">
		                    <div class="weui_cell_hd">
		                        <input type="radio" class="weui_check" name="status"  value="2" >
		                        <i class="weui_icon_checked"></i>
		                    </div>
		                    <div class="weui_cell_bd weui_cell_primary" style="text-align:left">
		                        <p>通过</p>
		                    </div>
		                </label>
		                <label class="weui_cell weui_check_label">
		                    <div class="weui_cell_hd">
		                        <input type="radio" class="weui_check" name="status"  value="3" >
		                        <i class="weui_icon_checked"></i>
		                    </div>
		                    <div class="weui_cell_bd weui_cell_primary" style="text-align:left">
		                        <p>不通过</p>
		                    </div>
		                </label>
                    </div>
		            <!-- <div class="weui-dialog__bd" style="padding:0px">
		                <div class="weui-cell weui-cell_switch">
		                    <div class="weui-cell__bd" style="text-align:left">是否通过</div>
		                    <div class="weui-cell__ft">
		                        <label for="switchSing" class="weui-switch-cp">
		                            <input id="switchSing" class="weui-switch-cp__input" type="checkbox" >
		                            <div class="weui-switch-cp__box"></div>
		                        </label>
		                    </div>
		                </div>
		                <div class="weui-cell" id="switchNo" >
		                    <div class="weui-cell__bd">
		                        <textarea class="weui-textarea" placeholder="请输入不通过的原因" rows="2"></textarea>
		                    </div>
		                </div>
		            </div> -->
		            <div class="weui-dialog__ft" style="    padding: 0px 10px 10px 10px;line-height:40px">
		                <a href="javascript:;" data-type="2" class="weui-dialog__btn primary submit_shenhe" style="background:#1aad19;color:#fff">确定</a>
		            </div>
		       </div>
		       <div class=" weui-dialogs weui-dialog--visible yui-auth-layer " style="z-index:30;display:none" id="showTwo">
		            <div class="weui-dialog__hd" style="position:relative">
		                <strong class="weui-dialog__title">活动审核</strong>
		                <div class="backRight back_modal" data-r="1" style="cursor: pointer;float:right;background:url(../../../static/assets/images/weChatApp/auth-goback1.png);height: 20px; position: absolute; top: 21px; width: 20px;left: 16px;"></div>
		            </div>
		            <div class="weui-dialog__bd weui_cells_checkbox">
		                <div class="weui_cell_bd weui_cell_primary">
		                    <textarea  class="weui-input weui-prompt-inputs textareas" id="weui-prompt-textarea" rows="5"  placeholder="请输入审核不通过原因" ></textarea>
		                </div>
		            </div>
                  <div class="weui-dialog__ft" style='padding: 0px 10px 10px 10px;line-height:40px'>
                     <a href="javascript:;" data-type="3" id="" class="weui-dialog__btn primary submit_shenhe" style="background:#1aad19;color:#fff">确定</a>
               </div>
        </div>
        <&}else if(View.examine_status()==2){&>
        <div class="weui-dialog weui-dialog--visible yui-auth-form  auth-animated">
            <div class="weui-dialog__hd" style="position:relative">
                <strong class="weui-dialog__title" style="    font-weight: normal;font-size: 16px;">活动置顶</strong>
                <a class="del_sing closeHide" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px"></span></a>
            </div>
            <div class="weui-dialog__bd" style="padding:0px">
                <div class="weui-cell weui-cell_switch">
                    <div class="weui-cell__bd" style="text-align:left">置顶活动</div>
                    <div class="weui-cell__ft">
                        <label for="switchCP" class="weui-switch-cp">
                            <input id="switchCP" class="weui-switch-cp__input" type="checkbox" checked="checked">
                            <div class="weui-switch-cp__box"></div>
                        </label>
                    </div>
                </div>
                <div class="weui-cells__title" style="text-align: left"><span class="iconfont icon-gantan"></span>设置为置顶后，则该活动会在所属城市、所属分类下置顶显示</div>
            </div>
            <div class="weui-dialog__ft" style="    padding: 0px 10px 10px 10px;line-height:40px">
                <a href="javascript:;" class="weui-dialog__btn primary submit_zhiding" style="background:#1aad19;color:#fff">确定</a>
            </div>
            </div>
        <&}else{&>
        <div class="weui-dialog weui-dialog--visible yui-auth-form  auth-animated">
            <div class="weui-dialog__hd">
                <strong class="weui-dialog__title" style="    font-weight: normal;font-size: 16px;">请输入姓名和手机号</strong>
            </div>
            <div class="weui-dialog__bd">
                <input type="text" class="weui-input weui-prompt-input" id="weui-prompt-username" value="" placeholder="输入姓名">
                <input type="phone" maxlength="11" max='11' class="weui-input weui-prompt-input" id="weui-prompt-phone" value="" placeholder="输入手机号码">
            </div>
            <div class="weui-dialog__ft">
                <a href="javascript:;" class="weui-dialog__btn default closeHide">取消</a>
                <a href="javascript:;" class="weui-dialog__btn primary submit">确定</a>
            </div>
            </div>
        <&}&>

  
</script>
<script type="text/html" id="showImg">
    <div class="weui-gallery" style="display: block">
        <span class="weui-gallery__img" style="background-image: url(<&= _this.img&>);"></span>
        <div class="weui-gallery__opr">
            <a href="javascript:" class="weui-gallery__del">
                <i class="weui-icon-delete weui-icon_gallery-delete none"></i>
            </a>
        </div>
    </div>
</script>
<script type="text/html" id="payMessage">
    <div style="z-index:10;top:0px;bottom:66px;height:auto;max-height:<&= _this.h &>" class="weui-popup__modal">
        <div class="weui-cells__title">姓名</div>
        <div class="weui-cells">
            <div class="weui-cell">
                <div class="weui-cell__bd">
                    <input class="weui-input" name="name" type="text" placeholder="请输入姓名">
                </div>
            </div>
        </div>
        <div class="weui-cells__title">号码</div>
        <div class="weui-cells">
            <div class="weui-cell">
                <div class="weui-cell__bd">
                    <input class="weui-input" maxlength="11" max='11' name="phone" type="phone" placeholder="请输入手机号码">
                </div>
            </div>
        </div>
        <& for(var i=0;i< _this.item.length;i++){ &>
          <& var items=_this.item[i]&>
          <& var itemsIndex=i&>
         <& if(items.type==1) {&>
          <div class='type1' data-index='<&= itemsIndex&>'>
               <div class="weui-cells__title"><&= items.title&></div>
            <div class="weui-cells">
              <div class="weui-cell">
                <div class="weui-cell__bd">
                    <input class="weui-input input"  name="name" type="text" placeholder="请输入<&= items.title&>">
                </div>
               </div>
            </div>
          </div>
            
         <& }else if(items.type==3) {&>
         <div class='type3' data-index='<&= itemsIndex&>'>
              <div class="weui-cells__title"><&= items.title&></div>
            <div class="weui-cells weui-cells_radio">
                 <& for(var n=0;n< items.content.length;n++){ &>
                       <& var contents=items.content[n]&>
                       <& var indexs=n&>
                      <label class="weui-cell weui-check__label" for="x1<&= itemsIndex&><&= indexs&>">
                    <div class="weui-cell__bd">
                        <p><&= contents&></p>
                    </div>
                    <div class="weui-cell__ft">
                        <input type="radio" class="weui-check" value='<&= contents&>' name="x1<&= itemsIndex&>" id="x1<&= itemsIndex&><&= indexs&>">
                        <span class="weui-icon-checked tjt-checkDadio"></span>
                    </div>
                   </label>
                 <& } &>
            </div>
         </div>
           
         <& }else if(items.type==4) {&>
           <div class='type4' data-index='<&= itemsIndex&>'>
                  <div class="weui-cells__title"><&= items.title&></div>
            <div class="weui-cells weui-cells_checkbox">
               <& for(var x=0;x< items.content.length;x++){ &>
                   <& var contents=items.content[x]&>
                   <& var index=x&>
                    <label class="weui-cell weui-check__label" for="s1<&= itemsIndex&><&= index&>">
                    <div class="weui-cell__bd">
                        <p><&= contents&></p>
                    </div>
                    <div class="weui-cell__hd">
                        <input type="checkbox" data-title='<&= contents&>' class="weui-check" name="checkbox1" id="s1<&= itemsIndex&><&= index&>" >
                        <i class="weui-icon-checked"></i>
                    </div>

                </label>
               <& } &>
                
            </div>
           </div>
           
          <& }else if(items.type==2) {&>
          <div class='type1' data-index='<&= itemsIndex&>'>
              <div class="weui-cells__title"><&= items.title&></div>
            <div class="weui-cells weui-cells_form">
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <textarea class="weui-textarea input" data-index='<&= itemsIndex&>' placeholder="请输入<&= items.title&>" rows="3"></textarea>
                        <!--<div class="weui-textarea-counter"><span>0</span>/200</div>-->
                    </div>
                </div>
            </div>
          </div>
            
        
         <& } &>
      <& } &>
        
       
       


    </div>
    <div style="position:fixed;    z-index: 50" class="weui-tabbar">
        <div style="margin:auto;width:90%">
            <button class="weui-btn weui-btn_primary personMessage" style="width:48%;display:inline-block">我要报名</button>
            <button class="weui-btn weui-btn_primary back" style="width:48%;margin-top:0px;display:inline-block">返回</button>
        </div>

    </div>
</script>
<script type="text/html" id="aggrement">
    <div class="weui-mask weui-mask--visible " style="z-index:99"></div>
    <div class="weui-dialog weui-dialog--visible yui-auth-form  auth-animated">
        <div class="weui-dialog__hd">
            <strong class="weui-dialog__title" style="    font-weight: normal;font-size: 16px;">报名活动协议</strong>
            <a class="del_sing closeHides" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px"></span></a>
        </div>
        <div class="weui-dialog__bd" style="overflow-y: auto; max-height:<&=_this.hh&>px;">
            <div ><&=_this.content&></div>
        </div>

    </div>
</script>
<script>
        var is_sign ="{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/common.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script> 
<script type="text/javascript" src="static/activity/assets/js/components/swiper/swiper.js"></script>
{pigcms{$shareScript}
<script>
     var View={
         wh:0,
         init:function(){
         	 this.agreement();
             this.initDetail();
             this.submit();
             this.showImg();
             this.back();
             this.go_pay_info();
         },
         setting:{
             examine_status:'',
             activity_id:'',
             fees:'',
               agreement:false,
                 agreecontent:''
         },
          agreement:function(){
            var _this=this
            $.ajax({
                type : "post",
                url : "{pigcms{:U('Platform/agreement')}",
                dataType : "json",
                data:{},
                async:false,
                success : function(data){
                    if(data.result && data.result!=null && data.result.length>0){
                        for(var i=0;i<data.result.length;i++){
                            if(data.result[i].id==2){
                             
                                _this.setting.agreecontent=data.result[i].content
                            }
                        }
                    }else{
                     
                    }

                },
                error:function(res){

                }
            })
        },
         go_pay_info: function () {
             $(document).on("click",'.go_pay',function(e){
                 var id=$(this).attr('data-id');
                 var  uid=$(this).attr("data-uid")
                 e.stopPropagation();e.preventDefault()
                 $.ajax({
                     url: "{pigcms{:U('Platform/go_pay_activity')}",
                     dataType : "json",
                     data:{activity_id:id,uid:uid},
                     type : "post",
                     success: function (data) {
                         console.log(data);
                         if (data.errorCode) {
                             $.toast(data.errorMsg, "cancel");
                         } else if(data.result && data.result.url) {
                             location.href=data.result.url
                         }
                     },
                     error:function(data){
                         console.log(data)
                     }
                 });
             });

             $(document).on("click",'.go_pay_join',function(e){
                 var id=$(this).attr('data-id');
                 var  uid=$(this).attr("data-uid");
                 e.stopPropagation();e.preventDefault();
                 $.ajax({
                     url: "{pigcms{:U('Platform/go_pay_join')}",
                     dataType : "json",
                     data:{activity_id:id,uid:uid},
                     type : "post",
                     success: function (data) {
                         if (data.errorCode) {
                             $.toast(data.errorMsg, "cancel");
                         } else if(data.result && data.result.url) {
                             location.href=data.result.url
                         }
                     },
                     error:function(data){
                         console.log(data)
                     }
                 });
             })
         },
         examine_status:function(){
            console.log(1)
             return this.setting.examine_status
         },
         is_owner:function(res,type){
             var _this=this;
             //详情
             if(_this.setting.examine_status==''){
                 if(type){
                     return false
                 }else{

                     if(res.is_owner==1){
                         return true
                     }else{
                         return false
                     }
                 }

             }else{
                 //审核
                     if(_this.setting.examine_status!=1 && _this.setting.examine_status!=2){
                         return false
                     }else{
                         return true
                     }

             }
         },
         initDetail:function(){
            var _this=this;

             $.showLoading('加载中');
             var href=window.location.href;
              var ss=href.lastIndexOf('=')+1
             _this.setting.activity_id="{pigcms{$Think.get.activity_id}";
             _this.setting.examine_status="{pigcms{$Think.get.examine_status}";
             console.log(33)
             APP.http("{pigcms{:U('Platform/activity_detail')}",{'activity_id':_this.setting.activity_id},function(data){
                 if(data.errorMsg!='success'){
                    $.hideLoading();
                    $.alert(data.errorMsg)
                }else{
                    var tpl = Simplite.getTemplate('showDetail');
                    var compiledTpl = Simplite.compile(tpl)(data.result);
                    _this.setting.fees=data.result.fees
                    $("#showIndex").html(compiledTpl);
                    h=$(window).width()*0.44;
                    View.wh=$(window).height()-66;
                    $(".activity_image1").css("height",h+'px');
                    $(".page").fadeIn();
                    console.log(data.result)
                    $.hideLoading();
                    View.custom_list=data.result.custom_list;
                    View.activity_id=_this.setting.activity_id
                    _this.sing(data.result.custom_list);
                    if($(".signUp").length>0){
                         $(".tjtAgree").show();
                         _this.setting.agreement=true
                    }
                    if($("#is_bottom").size()>0){
                         $("#is_top").css('padding-bottom','66px')
                    }else{
                        $("#is_top").css('padding-bottom','0px')
                    }
                    setTimeout(function(){
                     _this.share()
                    },1000)
                }
                _this.navi();
             },function(data){
                 $.hideLoading()
                        $.alert({
                                 title: '提示',
                                 text: '加载失败',
                                 onOK: function () {
                                        //点击确认
                                      }
                              });
                       console.log(res)
             },'post')
               /*$.ajax({
                    type : "post",
                    url : "{pigcms{:U('Platform/activity_detail')}",
                    dataType : "json",
                    data:{'activity_id':_this.setting.activity_id},
                    async:false,
                    success : function(data){
                        if(data.errorMsg!='success'){
                            $.hideLoading();
                            $.alert(data.errorMsg)
                        }else{
                            var tpl = Simplite.getTemplate('showDetail');
                            var compiledTpl = Simplite.compile(tpl)(data.result);
                            _this.setting.fees=data.result.fees
                            $("#showIndex").html(compiledTpl);
                            h=$(window).width()*0.44;
                            View.wh=$(window).height()-66;
                            $(".activity_image").css("height",h+'px');
                            $(".page").fadeIn();
                            console.log(data.result)
                            $.hideLoading();
                            View.custom_list=data.result.custom_list;
                            View.activity_id=_this.setting.activity_id
                            _this.sing(data.result.custom_list);
                        }

                    },
                    error:function(res){
                        $.hideLoading()
                        $.alert({
                                 title: '提示',
                                 text: '加载失败',
                                 onOK: function () {
                                        //点击确认
                                      }
                              });
                       console.log(res)
                    }
                })*/
            

         },
         sing:function(custom_list){
         	var  _this=this;
             $(document).on("click",'.signUp',function(){
             	 if(_this.setting.agreement=true){
                    if(!$("#weuiAgree").prop("checked")){
                        $.toptip("请同意协议", 'error')
                        return false
                    }
                }
                if(custom_list.length>2){
                    var tps = Simplite.getTemplate('payMessage');
                    var datas={
                        h:View.wh,
                        item:custom_list
                    }
                    var compiledTps = Simplite.compile(tps)(datas);
                    $("#message").html(compiledTps)
                    $("#message").popup();
                    $(".weui-tab").fadeOut()
                }else{
                     var tpl = Simplite.getTemplate('sing');
                     var compiledTpl = Simplite.compile(tpl)([]);
                     $('#production').html(compiledTpl);
                     setTimeout(function () {
                        $(" #production .yui-auth-form").removeClass("auth-animated")

                     },1000);
                }
             })
             $(document).on('click','.set_top',function(){
                 var tpl = Simplite.getTemplate('sing');
                 var compiledTpl = Simplite.compile(tpl)([]);
                 $('#production').html(compiledTpl);
                 setTimeout(function () {
                     $(" #production .yui-auth-form").removeClass("auth-animated")

                 },1000);
             })
         },
         navi: function () {
             $('.navi').click(function(){
                 var _this = $(this);
                 if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
                     return false;
                 }

                 var ua = window.navigator.userAgent.toLowerCase();
                 if(window.__wxjs_environment === 'miniprogram'|| ua.match(/MicroMessenger/i) == 'micromessenger' ){
                     $.showLoading('跳转中');
                     $.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
                         $.hideLoading();
                         if(result['status'] == 1){
                             wx.openLocation({
                                 latitude: result['info']['lat'],
                                 longitude: result['info']['lng'],
                                 name: _this.data('name'), // 位置名
                                 address: _this.data('address'), // 地址详情说明
                                 scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                                 infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                             });
                         }else{
                             $.alert({
                                 title: '提示',
                                 text: result.msg,
                                 onOK: function () {
                                     //点击确认
                                 }
                             });
                         }
                     });
                 } else {
                     var url = "http://api.map.baidu.com/direction?origin=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent(_this.data('address'))+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
                     // console.log(url);
                     location.href = url;
                 }
                 return false;
             });
         },
         submit:function(){
             //报名
             var _this=this;

             $(document).on("click",'.closeHide',function(){
                 hideMobile()

             })
             $(document).on("click",'.closeHides',function(){
                hideMobile(false,true)

            })
             $(document).on("click",'.submit',function(){
               
                 var name=$("#weui-prompt-username").val();
                 var phone=$("#weui-prompt-phone").val();
                 var customs={
                    name:name,
                    phone:phone,
                     activity_id:View.activity_id,
                };
                 var myreg = /^[1][3,4,5,7,8][0-9]{9}$/;
                if(!_this.validate(name,'g')){
                    $.toptip("请输入姓名", 'error')
                    return false
                }
                 if(!_this.validate(phone,'g')){
                     $.toptip("请手机号码", 'error')
                     return false
                 }
                 if (!myreg.test(phone.trim())) {
                     $.toptip("手机号码格式不正确", 'error')
                     return false
                 }
                  _this.tijiao(customs,true)

             })
             $(document).on("click",'.personMessage',function(){
                 var name= $("#message input[name=name]").val();
                 var phone= $("#message input[name=phone]").val();
                 var customs={
                    name:name,
                    phone:phone,
                    activity_id:View.activity_id,
                     custom:{}
                };
                 var myreg = /^[1][3,4,5,7,8][0-9]{9}$/;
                 if(!_this.validate(name,'g')){
                     $.toptip("请输入姓名", 'error')
                     return false
                 }
                 if(!_this.validate(phone,'g')){
                     $.toptip("请手机号码", 'error')
                     return false
                 }
                 if (!myreg.test(phone.trim())) {
                     $.toptip("手机号码格式不正确", 'error')
                     return false
                 }
                 if($(".type1").length>0){
                    for(var i=0;i<$(".type1").length;i++){
                         var index=parseInt($(".type1").eq(i).attr("data-index"));
                           var html=$(".type1").eq(i).find('.input').val()
                          if(View.custom_list[index].is_required==1){
                              if(!_this.validate(html,'g')){
                                    $.toptip("请输入"+View.custom_list[index].title, 'error')
                                    return false
                                 }else{
                                      customs.custom[View.custom_list[index].title]=html
                                 }
                          }else{
                              if(_this.validate(html,'g')){
                                    customs.custom[View.custom_list[index].title]=html
                                 }   
                          }
                    }
                 }
                  if($(".type3").length>0){
                    for(var i=0;i<$(".type3").length;i++){
                         var index=parseInt($(".type3").eq(i).attr("data-index"));
                           var html=$(".type3").eq(i).find('input:checked').val()
                           if(View.custom_list[index].is_required==1){
                              if(html==undefined || html==null){
                                    $.toptip("请选择"+View.custom_list[index].title, 'error')
                                    return false
                                 }else{
                                       customs.custom[View.custom_list[index].title]=html
                                 }
                          }else{
                              if(html!=undefined || html!=null){
                                     customs.custom[View.custom_list[index].title]=html
                                 } 
                          }
                          
                    }
                 }
                 if($(".type4").length>0){
                    for(var i=0;i<$(".type4").length;i++){
                         var index=parseInt($(".type4").eq(i).attr("data-index"));
                         var err=[];
                          for(var n=0;n<$(".type4").eq(i).find('input').length;n++) {
                               if($(".type4").eq(i).find('input').eq(n).prop('checked')){
                                var con=$(".type4").eq(i).find('input').eq(n).attr("data-title")
                                   err.push(con)
                               }
                          }  
                         if(View.custom_list[index].is_required==1){
                              if(err.length==0){
                                 $.toptip("请选择"+View.custom_list[index].title, 'error')
                                    return false
                                }else{
                                    customs.custom[View.custom_list[index].title]=err
                                }
                          }else{
                               if(err.length>0){
                                 customs.custom[View.custom_list[index].title]=err
                               }
                          }
                       

                       
                          
                    }
                 }
                 _this.tijiao(customs,false)
             })
           $(document).on("change",'.weui_check',function(){
           	     if($(this).val()==3){
           	     	$(".yui-auth-form").addClass("change-animated");
                   setTimeout(function(){
                     $('.yui-auth-layer').show();
                     $(".yui-auth-form").hide();
                     $(".yui-auth-form").removeClass("change-animated");
                   },600)
           	     }
               
           })
            $(document).on("click",'.backRight',function(){
               $(".yui-auth-layer ").addClass("change-animated");
               $("input[type='radio']").attr("checked",false)
               setTimeout(function(){
                     $('.yui-auth-layer').hide();
                     $(".yui-auth-form").show();
                     $(".yui-auth-layer").removeClass("change-animated");
                   },600)
           })
             $(document).on("click",'.submit_shenhe',function(){
                  var type=$(this).attr("data-type");
                  var datas={
                     examine_status:2,
                     activity_id:_this.setting.activity_id
                 }
                  if(type==2){
                      if($("input[type='radio']:checked").val()==undefined){
                   	   $.toptip("请选择审核是否通过", 'error')
                   	 return false
                   }
                  }else{
                   datas.examine_status=3;
                    var text=$("#weui-prompt-textarea").val()
                     if(!_this.validate(text,'g')){
                     $.toptip("请输入不通过理由", 'error')
                        return false
                     }
                     datas.reject_message=text
                  }
                 $.ajax({
                     url: "{pigcms{:U('Platform/activity_examine')}",
                     async: false,//这一步是非常重要的，作用是设置为同步执行
                     dataType : "json",
                     data:datas,
                     type : "post",
                     success: function (data) {
                         if(data.errorMsg!='success'){
                             $.hideLoading();
                             $(".yui-auth-form").addClass("close-animated");
                             $(".yui-auth-layer").addClass("close-animated");
                             setTimeout(function () {
                                 $("#production").html("");
                             },20);
                             $.alert(data.errorMsg)
                         }else{
                             $.toptip("审核成功",1500, 'success',function(){
                                 $(".yui-auth-form").addClass("close-animated");
                                 $(".yui-auth-layer").addClass("close-animated");
                                 setTimeout(function () {
                                     $("#production").html("");
                                 },20);
                                 window.location.href = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=Platform&a=activityInspect";
                             })


                         }

                     },
                     error:function(data){

                     }
                 });
             })
             $(document).on("click",'.submit_zhiding',function(){
                 var datas={
                     is_top:1,
                     activity_id:_this.setting.activity_id
                 }
                 if(!$("#switchCP").prop('checked')){
                     datas.is_top=0;
                 }
                 $.ajax({
                     url: "{pigcms{:U('Platform/set_top')}",
                     async: false,//这一步是非常重要的，作用是设置为同步执行
                     dataType : "json",
                     data:datas,
                     type : "post",
                     success: function (data) {
                         if(data.errorMsg!='success'){
                             $.hideLoading();
                             $.alert(data.errorMsg)
                         }else{
                             $.toptip("设置成功",1500, 'success',function(){
                                 _this.hideMobile(true)
                             })

                         }

                     },
                     error:function(data){

                     }
                 });
             })
             var hideMobile=_this.hideMobile=function(type){
                 $(".yui-auth-form").addClass("close-animated");
                   $(".yui-auth-layer").addClass("close-animated");
                 setTimeout(function () {
                     $("#production").html("")
                     if(type){window.history.back(-1)}
                 },1000);
             }
         },
         tijiao:function(customs,type){
             $.showLoading('保存中...')
              $.ajax({
                    type : "post",
                    url : "{pigcms{:U('Platform/join_activity')}",
                    dataType : "json",
                    data:customs,
                    async:false,
                    success : function(data){
                        console.log(data);
                        if(data.errorMsg!='success'){
                            $.hideLoading();
                            $(".yui-auth-form").addClass("close-animated");
                            $(".yui-auth-layer").addClass("close-animated");
                            setTimeout(function () {
                                $("#production").html("")
                            },200);
                            $.alert(data.errorMsg);
                        }else{
                            $.hideLoading()
                            console.log(data)
                            $(".signUp").attr('disabled','disabled');
                            $(".signUp").addClass("bg-ddd");
                            if(!type){
                                $(".weui-tab").fadeIn()
                                $.closePopup();
                                window.location.href=data.result.url;

                            }else{
                                $.toast("报名成功",function(){
                                    $(".yui-auth-form").addClass("close-animated");
                                    setTimeout(function () {
                                        $("#production").html("")
                                    },1000);
                                    window.location.href=data.result.url
                                })

                            }
                        }

                        
                   
                    },
                    error:function(res){
                       $.hideLoading()
                       $.toptip("报名失败", 'error')
                         return false
                    }
                })
         },
         validate:function(res,is_global){
             var result=res.replace(/(^\s+)|(\s+$)/g,"");
             if(is_global.toLowerCase()=="g") {
                 result = result.replace(/\s/g,"");
                 if(result==''){
                     return false
                 }else{
                     return true
                 }
             }
         },
         showImg:function(){
             $("#ImgPic").on("click",function(){
                 var tpl = Simplite.getTemplate('showImg');
                 var img=$(this).attr("src")
                 var data={
                     img:img
                 }
                 var compiledTpl = Simplite.compile(tpl)(data);
                 $("#fileImg").html(compiledTpl)
             })
             $(document).on("click",'#fileImg .weui-gallery',function(){
                 var _this=$(this);
                 $(this).fadeOut()
                 setTimeout(function(){
                     _this.remove()
                 },500)
             })
         },
         back:function(){
         	var  _this=this;
             $(document).on("click",'.back',function(){
                 $(".weui-tab").fadeIn()
                 $.closePopup();
             })
            $(document).on('click','.comment',function(){
                 location.href=$(this).attr('data-id')
             })
              $(document).on("click",'.agree_ment',function(){
                var tpl = Simplite.getTemplate('aggrement');
                var datas={
                    hh:$(window).height()*0.6,
                    content:_this.setting.agreecontent
                }
                var compiledTpl = Simplite.compile(tpl)(datas);
                $('#production').html(compiledTpl);
                setTimeout(function () {
                    $(" #production .yui-auth-form").removeClass("auth-animated")
                    _this.setting.top=$(window).scrollTop()
                    $(".page").addClass("fixed").css('top',_this.setting.top*(-1))

                },1000);
            })
            $("#gride").on("click",function(){
              	 $(this).hide()
              })
              $(document).on("click",'.share',function(){
              	    var ua = window.navigator.userAgent.toLowerCase();
              	    if(window.__wxjs_environment === 'miniprogram'|| ua.match(/MicroMessenger/i) == 'micromessenger' ){
                        $("#gride").show()
              	    }
              })
         },
         share:function(){
         	  console.log($(".huo_detail").html(),$("#ImgPic").attr("src"))
         	  var con=$(".huo_detail").html();
              var title=$(".title").html();
         	  var img=$("#ImgPic").attr("src")
                        wx.ready(function () {
                                // 2. 分享接口
                                // 2.1 监听“分享给朋友”，按钮点击、自定义分享内容及分享结果接口
                                wx.onMenuShareAppMessage({
                                    title: title,
                                    desc: con,
                                    link: window.location.href,
                                    imgUrl: img,
                                    type: '', // 分享类型,music、video或link，不填默认为link
                                    dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                                    success: function () {

                                    },
                                    cancel: function () {
                                        //alert('分享朋友失败');
                                    }
                                });


                                // 2.2 监听“分享到朋友圈”按钮点击、自定义分享内容及分享结果接口
                                wx.onMenuShareTimeline({
                                    title: title,
                                    desc: con,
                                    link: window.location.href,
                                    imgUrl: img,
                                    success: function () {

                                        //alert('分享朋友圈成功');
                                    },
                                    cancel: function () {
                                        //alert('分享朋友圈失败');
                                    }
                                });

                                // 2.4 监听“分享到微博”按钮点击、自定义分享内容及分享结果接口
                                wx.onMenuShareWeibo({
                                     title: title,
                                    desc: con,
                                    link: window.location.href,
                                    imgUrl: img,
                                    success: function () {

                                        //alert('分享微博成功');
                                    },
                                    cancel: function () {
                                        //alert('分享微博失败');
                                    }
                                });

                            });

         }

     }
     window.View=View;
     View.init();
</script>

<script>
    var swiper = new Swiper('.swiper-container', {
      pagination: {
        el: '.swiper-pagination',
      } 
    });

    var pb1 = $.photoBrowser({
        items: [
          "http://o2o-static.pigcms.com/upload/activity/001/358/683/20181130154410_5c00ea4a8f60d.jpeg",
          "http://o2o-static.pigcms.com/upload/activity/001/358/683/20181130154410_5c00ea4a8f60d.jpeg" 
        ],

        onSlideChange: function(index) {
          console.log(this, index);
        },

        onOpen: function() {
          console.log("onOpen", this);
        },
        onClose: function() {
          console.log("onClose", this);
        }
      });
      $(".activity_image1").click(function() {
        pb1.open(2);
      });
 </script>


</body>
</html>