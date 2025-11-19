<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>智能访客开门</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}library/css/public.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}library/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css?211"/>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/village_my.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
    <style type="text/css">
        p{ font-size:12px;}
        .order_list{border:none !important;}
        .mar-10{margin-bottom:10px;}
        .openWx{background: #06c1ae;
            color: #fff;
            width: 68%;
            border-radius: 1000px;
            display: block;
            text-align: center;
            padding: 10px 15px;
            cursor: pointer;
            margin: auto;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        #openFiend button{    height: 100%;color: #fff; width: 100%;display: block;    background-color: transparent; margin: 0; padding: 0; border: 1px solid transparent;  outline: none;}
        #share_time_type_id{width:100%;}
        .select_time {background:#E5F8F6;margin: 0px 20px;     padding: 8px 10px;  position: relative;  display: -webkit-box;display: -webkit-flex;  display: flex; -webkit-box-align: center; -webkit-align-items: center;align-items: center;}
        .select_time>div label{ display: block;  min-width: 105px; word-wrap: break-word;   word-break: break-all;  }
        .effective_time{          -webkit-box-flex: 1; -webkit-flex: 1; flex: 1;  }
        .time-select{padding-left: 0px;  -webkit-appearance: none;    border: 0;outline: 0;    background-color: transparent;    width: 100%;font-size: inherit;   height: 30px;    line-height: 30px;position: relative;   z-index: 1; padding-right: 30px;}
        .effective_time:after {content: " "; display: inline-block; height: 6px; width: 6px;border-width: 0px 2px 2px 0px;    border-color: #c8c8cd;    border-style: solid;-webkit-transform: matrix(.71,.71,-.71,.71,0,0);    transform: matrix(.71,.71,-.71,.71,0,0);   top: -2px;    position: absolute;    top: 50%;right: 15px;   margin-top: -4px;}
        #shareVillage{top: 0px;bottom: 0px;}
        .select_data{width: 100%;border: 0;outline: 0;-webkit-appearance: none;background-color: transparent;font-size: inherit;color: inherit;height: 1.47058824em;line-height: 1.47058824;}
        .weui-media-box__title {font-size: 16px;}
        .nex_bottom{
            height: 200px;
            position: fixed;
            bottom: 0px;
            right: 0px;
            width: 100%;
            text-align: center;
            z-index: 100;
        }
        .weui-media-box {
            padding: 10px;
        }
    </style>
</head>
<body style='background-color: #fff;'>
<!-- <header class="pageSliderHide" ><div id="backBtn"></div>给访客开门</header> -->
<div style="position:fixed;top:0px;left:0px;bottom:0px;right:0px">
    <image src="{pigcms{$static_path}images/homeBg.png" style="width:100%;height:100%;padding-top:16%;" />
</div>
<div class="village_my " style="position:fixed;top:0px;left:0px;bottom:113px;right:0px;overflow: hidden;">
    <div  >
        <div class="hone_title">
            <div style='text-align:center'>
                <image src="{pigcms{$static_path}images/myHome.png" style="width: 100px;   height: 66px;margin-top: 5%;" />
            </div>
            <div style='   color: #000;padding: 5% 30px 15px 30px;font-size: 14px;    text-align: center;'>
                <span>在这里您可以将您所在{pigcms{$config['house_name']}下单元的开门权限分享给好友，好友即可一键开门，方便你我他!</span>
            </div>
        </div>
        <if condition="$village_floor_list && $village_floor_list['list']">
            <div style="overflow-y: scroll" class="all_home">
                <form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_share_form" style=" padding: 0px 15px;">
                    <input id="form_village_id" name="village_id" value="{pigcms{$_GET['village_id']}" type="hidden">
                    <volist name="village_floor_list" id="vo">
                        <volist name="vo" id="child">
                            <div class="weui-media-box weui-media-box_appmsg ">
                                <div class="weui-media-box__hd" style="width:20px;height:20px">
                                    <image src="{pigcms{$static_path}images/openKey.png" class="weui-media-box__thumb border_radius"  style="width:100%;height:100%" />
                                </div>
                                <div class="weui-media-box__bd w_3 flex">
                                    <div class="weui-media-box__title color_9 font_b font_size15 flex_one">
                                        <div class="flex_one h4"><if condition="$child['floor_id'] eq -1">({pigcms{$child.door_name})&nbsp;</if>{pigcms{$child.floor_layer}&nbsp;{pigcms{$child.floor_name}</div>
                                    </div>
                                </div>
                            </div>
                        </volist>
                    </volist>
                </form>
            </div>
            <else/>
            <div class=" openWx no_share" style="background:#ebebeb;">您入住的{pigcms{$config['house_name']}或单元暂未添加门禁</div>
        </if>
    </div>
</div>
<div class="nex_bottom">
    <if condition="$village_floor_list && $village_floor_list['list']">
        <div class="select_time weui-cell_select weui-cell_select-after" >
            <div class=""><label for="" class="weui-label" style="width:auto">门禁权限有效期</label> </div>
            <div class="effective_time" style="padding-left:15px;    text-align: left;">
                <!--<select class="time-select" name="select2" id='share_time_type_id'>
                </select>-->
                <div class="select_data" id="time-inline" value="" ></div>
            </div>
        </div>
        <if condition="$info.tip eq 0  && $village_floor_list && $village_floor_list['list']">
            <div id="openFiend"   class='openWx'  >分享给访客开门 </div>
            <else/>
            <div class='openWx no_share'  style="background:#dedede">分享给访客开门 </div>
        </if>

    </if>
    <if condition="$info.tip eq 1">
        <span>您当前已欠物业费，无法使用该功能</span>
    </if>
    <if condition="$info.tip eq 2">
        <span>工作人员无开门权限</span>
    </if>
    <if condition="$info.tip eq 3">
        <span class="no_share">工作人员不能分享门禁</span>
    </if>
</div>
<div  id="shareVillage"  style="z-index:200"> <div id="" > </div>  </div>
<div class="select_box select_box1 click_no"></div>
<script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}library/js/dySelect.js?444" charset="utf-8"></script>
<script type="text/html" id="day">
    <option value="<&=_this.time&>" data-type="<&= _this.type=='小时'?'1':'2'&>"  <& if(_this.time==1 && _this.type=='小时'){&> selected<&}&>><&=_this.time&><&=_this.type&></option>
</script>
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Village",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{:U('Home/index')}",
        "tTitle": "访客开门",
        "tContent": "我赠送一个临时开门权限",
        'img': '{pigcms{$static_public}wxapp/visitor_open.png'
    };
</script>
{pigcms{$shareScript}
<script>
    var max_time='{pigcms{$time_limit}';
    var is_app={pigcms{$is_app_browser|default=0};
</script>
<if condition="$is_app_browser">
    <script type="text/javascript">
        window.lifepasslogin.shareLifePass(name,desc,share_pic,share_url);
        /*点击分享事件*/
        function shareFriend(){
            if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
                $('body').append('<iframe src="pigcmso2o:/WeChat" style="display:none;"></iframe>');
            }else{
                window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent,'{pigcms{$visitor_open_img}',window.shareData.sendFriendLink );
            }
        }
    </script>
</if>
<script type="text/javascript">
    View={
        init:function(){
            this.initDeatail();
            this.click()
        },
        setting:{
            times:'',
            share_time_type:0,
            share_time_length:0,
            village_id:$('#form_village_id').val(),
            response:null
        },
        initDeatail:function(){
            var _self=this;
            var my_time=0;
            wx.hideOptionMenu();
            $(".village_my").css("bottom",$(window).height()*0.3+'px');
            $(".nex_bottom").css("height",$(window).height()*0.3+'px');
            var h1=$(".hone_title").height();
            if($(".all_home").size()>0){
                $(".all_home").css("height",$(window).height()*0.7-h1+'px')
            }
            var datas=[
                {
                    time:'1小时',
                    type:'1',
                    value:1
                },
                {
                    time:'2小时',
                    type:'1',
                    value:2
                },
                {
                    time:'3小时',
                    type:'1',
                    value:3
                },
                {
                    time:'5小时',
                    type:'1',
                    value:5
                },
                {
                    time:'8小时',
                    type:'1',
                    value:8
                },
                {
                    time:'10小时',
                    type:'1',
                    value:10
                },
                {
                    time:'12小时',
                    type:'1',
                    value:12
                }
            ]
            if(max_time>12 || max_time=='' || max_time==0){
                var day=parseInt(max_time*1/24);
                for(var i=1;i<=day;i++){
                    datas.push({  time:i+'天', type:'2',value:i})
                }

            }else{
                if(1==max_time){
                    datas=datas.slice(0,1)
                }else if(2==max_time){
                    datas=datas.slice(0,2)
                }else if(3==max_time){
                    datas=datas.slice(0,3)
                }else if(5==max_time){
                    datas=datas.slice(0,4)
                }else if(8==max_time){
                    datas=datas.slice(0,5)
                }else if(10==max_time){
                    datas=datas.slice(0,6)
                }
            }
            var hgS1 = new selectSwiper({
                el: '.select_box1',
                activeIndex:0,
                keyMap:{
                    name:'time',
                    type:'type',
                    value:'value'
                },
                data: datas,
                init: function(index) {
                    $("#time-inline").html(this.data[index].time);
                    _self.setting.share_time_length=this.data[index].value;
                    _self.setting.share_time_type=this.data[index].type;
                    _self.share_add()
                },
                okFunUndefind: function() {

                },
                okFun: function(index) {
                    $("#time-inline").html(this.data[index].time);
                    _self.setting.share_time_length=this.data[index].value;
                    _self.setting.share_time_type=this.data[index].type;
                    _self.share_add()
                },
                closeFun: function() {
                    _self.share_add();
                    console.log('取消');
                },
            });
            $('#time-inline').on('click',
                function() {
                    hgS1.openSelectSwiper();
                });

        },
        shareVillage:function(){
            $("#shareVillage").show();
            $("#container").css("top",'0px');
        },
        click:function(){
            var _self=this;
            $("#shareVillage").on("click",function(){
                $("#container").css("top",'50px');
                $(this).hide()
            });
            $(document).on("click",'#openFiend',function(){
                //分享按钮
                var patrn = /^\d+(\.\d+)?$/;
                var my_time=$('#time-inline').html();
                /*if(_self.setting.share_time_type==2){
                    _self.setting.share_time_length=_self.setting.share_time_length*1*24
                }*/
                if(is_app==1){
                    if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
                        $('body').append('<iframe src="pigcmso2o://shareWeChat" style="display:none;"></iframe>');
                    }else{
                        window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent,window.shareData.img,window.shareData.sendFriendLink );
                    }
                }else{
                    var ua = window.navigator.userAgent.toLowerCase();
                    if(window.__wxjs_environment === 'miniprogram'|| ua.match(/MicroMessenger/i) == 'micromessenger' ){
                        if(window.__wxjs_environment === 'miniprogram'){
                            //小程序
                            var path='/pages/index/index?redirect=share_freiend_opendoor&door_share_id='+_self.setting.response.door_share_id;
                            var iosHref = encodeURIComponent(path);
                            var wxappUrl='/pages/index/share?shareTitle='+window.shareData.tContent+'&shareImg='+ window.shareData.img+'&door_share_id='+_self.setting.response.door_share_id+'&title=智能访客开门&bg='+window.shareData.img+'&sharePath='+iosHref;
                            wx.miniProgram.navigateTo({ url:wxappUrl});
                        }else{
                            _self.shareVillage();
                        }

                    }else{
                        layer.open({content:'请在微信中登录！',btn: ['确定'],end:function(){

                            }
                        });
                    }
                }



            })
        },
        share_add:function(){
            var _self=this;
            if($(".no_share").size()==0){
                var my_time=$('#time-inline').html();
                /*if(_self.setting.share_time_type==2){
                    _self.setting.share_time_length=_self.setting.share_time_length*1*24
                }*/
                $.post('{pigcms{:U("Library/house_village_door_share_add")}', {'village_id':_self.setting.village_id,'share_time_type':_self.setting.share_time_type,  ' share_time_length':_self.setting.share_time_length }, function(response){
                    var url = response['share_url'];
                    _self.setting.response=response;
                    window.shareData.tContent='我赠送一个'+my_time+'的临时开门权限。来自【'+response.share_address+'{pigcms{$config.house_name}】';
                    window.shareData.sendFriendLink=url;
                    window.shareData.tTitle='智能访客开门';
                    if(window.__wxjs_environment === 'miniprogram'){
                    }else{
                        //微信端

                        wx.ready(function () {
                            wx.showOptionMenu();
                            // 2. 分享接口
                            // 2.1 监听“分享给朋友”，按钮点击、自定义分享内容及分享结果接口
                            wx.onMenuShareAppMessage({
                                title: window.shareData.tTitle,
                                desc: window.shareData.tContent,
                                link: window.shareData.sendFriendLink ,
                                imgUrl: '{pigcms{$visitor_open_img}',
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
                                title: window.shareData.tTitle,
                                link: window.shareData.sendFriendLink,
                                imgUrl: '{pigcms{$visitor_open_img}',
                                success: function () {

                                    //alert('分享朋友圈成功');
                                },
                                cancel: function () {
                                    //alert('分享朋友圈失败');
                                }
                            });

                            // 2.4 监听“分享到微博”按钮点击、自定义分享内容及分享结果接口
                            wx.onMenuShareWeibo({
                                title: window.shareData.tTitle,
                                desc: window.shareData.tContent,
                                link: window.shareData.sendFriendLink ,
                                imgUrl:'{pigcms{$visitor_open_img}',
                                success: function () {

                                    //alert('分享微博成功');
                                },
                                cancel: function () {
                                    //alert('分享微博失败');
                                }
                            });

                        });
                    }
                }, 'json')

            }else{
                wx.ready(function () {
                    wx.hideOptionMenu()
                });
            }

        }
    }
    window.View=View;
    View.init()
</script>
</body>
</html>