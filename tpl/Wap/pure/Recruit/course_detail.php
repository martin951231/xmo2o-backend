<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <title>课程详情</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mui.min.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css?a=1123688"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
    <link rel="stylesheet" href="{pigcms{$static_path}css/bbs.css" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/workIcon.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/video-js.min.css">
    
    <script src="{pigcms{$static_path}js/mui.min.js"></script>
    <script src="{pigcms{$static_path}js/mui.zoom.js"></script>
    <script src="{pigcms{$static_path}js/mui.previewimage.js"></script> 
    
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_path}mall/js/video.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <style>
        body{font-size:14px;background-color: #f8f8f8;}
        .font12{font-size:12px}
        .font16{font-size:16px}
        .font14{font-size:14px}
        .w100{width:100%}
        .h100{height:100%}
        .pd15{padding:15px}
        .after:after{content:none !important;}
        .weui-btn_primary {
            background-color: #E8C05E;
        }
        .weui-btn_primary:not(.weui-btn_disabled):active {color: rgba(255, 255, 255, 0.6);background-color: #E8C05E;
        }
        .rel{position:relative}
        .mg-t0{margin-top:0px}
        .mg-t5{margin-top:5px}
        .mg-t10{margin-top:10px}
        .pd-l0{padding-left:0px}
        .pd-b0{padding-bottom:0px}
        .pd-t0{padding-top:0px}
        .pd-t15{padding-top:15px}
        .look{    color: #E8C05E;border: 1px solid #E8C05E;padding: 3px 5px;}
        .group{border: 1px solid #ff4d00;color: #fff;margin-left:10px;background:#ff4d00}
        .border-l:after {
            content: " ";
            position: absolute;
            left: -10px;
            top: 0;
            width: 1px;
            bottom: 0;
            border-left: 1px solid #d5d5d6;
            color: #d5d5d6;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);
        }
        .pd-r10{padding-right:10px}
        .color_9{color:#999}
        .color_f{color:#fff}
        .bg-f{background:#fff}
        .weui-tabbar__icon {
            width: 20px;
            height: 20px;
        }
        i.weui-tabbar__icon, .weui-tabbar__icon > i {
            font-size: 20px;
        }
        .weui-tabbar__item.weui-bar__item--on .weui-tabbar__label {
            color: #999;
        }
        .video_content_modal {

        }
        .video_content_modal .video_model{
            position: absolute;
            z-index: 1000;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width:100%;
            height:100%;
        }
        .video_content_modal p{
            position: absolute;
            z-index: 5000;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;

        }
        .cursor{cursor:pointer}
        .noVip{    background: #000;
            margin-right: 10PX;
            color: #fff;
            padding: 5px 10px;
            border-radius: 100px;}
        .vip{
            background: red;
            margin-left: 10PX;
            color: #fff;
            padding: 5px 10px;
            border-radius: 100px;
        }
        .vjs_back{display:none !important;}
        .view{background: #000;
            margin-right: 10PX;
            color: #fff;
            padding: 5px 10px;
            border-radius: 100px;
        }
        .dish_content{
            width:100%;
        }
        .dish_content>a{
            width:33.3%;
            float:left;
            margin:0; 
        }
        .dish_content>a dt{
            width:100%;
            box-sizing: border-box; 
            padding:0 5px;
            height:60px;
            margin:0 auto;
        }
        .dish_content>a dd{
           height:30px;
           line-height:30px;
        }
        .jifen_til{
            padding:5px 0;
        }
        .timeshow{
            color:#999;
            font-size:12px;
        }
        .mui-preview-image.mui-fullscreen {
            position: fixed;
            z-index: 999;
            background-color: #000;
        }
        .mui-preview-header,
        .mui-preview-footer {
            position: absolute;
            width: 100%;
            left: 0;
            z-index: 10;
        }
        .mui-preview-header {
            height: 44px;
            top: 0;
        }
        .mui-preview-footer {
            height: 50px;
            bottom: 0px;
        }
        .mui-preview-header .mui-preview-indicator {
            display: block;
            line-height: 25px;
            color: #fff;
            text-align: center;
            margin: 15px auto 4;
            width: 70px;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 12px;
            font-size: 16px;
        }
        .mui-preview-image {
            display: none;
            -webkit-animation-duration: 0.5s;
            animation-duration: 0.5s;
            -webkit-animation-fill-mode: both;
            animation-fill-mode: both;
        }
        .mui-preview-image.mui-preview-in {
            -webkit-animation-name: fadeIn;
            animation-name: fadeIn;
        }
        .mui-preview-image.mui-preview-out {
            background: none;
            -webkit-animation-name: fadeOut;
            animation-name: fadeOut;
        }
        .mui-preview-image.mui-preview-out .mui-preview-header,
        .mui-preview-image.mui-preview-out .mui-preview-footer {
            display: none;
        }
        .mui-zoom-scroller {
            position: absolute;
            display: -webkit-box;
            display: -webkit-flex;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            align-items: center;
            -webkit-box-pack: center;
            -webkit-justify-content: center;
            justify-content: center;
            left: 0;
            right: 0;
            bottom: 0;
            top: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            -webkit-backface-visibility: hidden;
        }
        .mui-zoom {
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
        }
        .mui-slider .mui-slider-group .mui-slider-item img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }
        .mui-android-4-1 .mui-slider .mui-slider-group .mui-slider-item img {
            width: 100%;
        }
        .mui-android-4-1 .mui-slider.mui-preview-image .mui-slider-group .mui-slider-item {
            display: inline-table;
        }
        .mui-android-4-1 .mui-slider.mui-preview-image .mui-zoom-scroller img {
            display: table-cell;
            vertical-align: middle;
        }
        .mui-preview-loading {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            display: none;
        }
        .mui-preview-loading.mui-active {
            display: block;
        }
        .mui-preview-loading .mui-spinner-white {
            position: absolute;
            top: 50%;
            left: 50%;
            margin-left: -25px;
            margin-top: -25px;
            height: 50px;
            width: 50px;
        }
        .mui-preview-image img.mui-transitioning {
            -webkit-transition: -webkit-transform 0.5s ease, opacity 0.5s ease;
            transition: transform 0.5s ease, opacity 0.5s ease;
        }
        @-webkit-keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        @-webkit-keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
        .footerMenu #content{
            top:0.8rem;
            bottom:0;
        }
        .dish_content>div{
            width: 33.3%;
            float: left;
            margin: 0;
        }
    </style>
</head>
<body>
<div style="padding-bottom:48px">
    <if condition="$now_course['type'] eq 0">
        <div style="width:100%;max-height:250px;height: 250px;" class="rel" >
            <video id="my-video" class="video-js vjs-default-skin w100 h100" controls playsinline="true" poster="{pigcms{:file_domain().$now_course['cover_img']}"
                   data-setup="{}" playsinline=true>
                <source src="{pigcms{:file_domain().$now_course['videoUrl']}" type='video/mp4' />
                <source src="{pigcms{$static_path}recruit/images/11.webm" type='video/webm' />
                <source src="{pigcms{$static_path}recruit/images/11.ogv" type='video/ogg' />
                
                <!-- <track kind="captions" src="demo.captions.vtt" srclang="en" label="English"></track>
                 <track kind="subtitles" src="demo.captions.vtt" srclang="en" label="English"></track>-->
            </video>
            <div class="video_content_modal cursor">
                <div class="video_model"></div>
                <p><a class="view">查看视频</a></p>
            </div>
        </div>
    <else/>
        <div style="width:100%; ">
            <img  class=" w100 h100" src="{pigcms{:file_domain().$now_course['cover_img']}" data-preview-src="" data-preview-group="2">
        </div>
    </if>
    <div class="demos-content-padded ">
        <div class="weui-cells pd15 mg-t0  font16 after " >
            <?php if ($now_company&&!$can_view): ?>
                <a href="{pigcms{:U('energy_package')}" id="show-toast " class="weui-btn weui-btn_primary">开通VIP会员，商城课程免费看</a>
            <?php endif ?>
            <!-- <p class="font12 pd-t15"> <span class="look">试看</span><span class="look group">商业合作</span></p> -->
            <div class="weui-cell pd-l0 mg-t10">

                <div class="weui-cell__bd pd-r10">
                    <p>{pigcms{$now_course.title}</p>
                    <if condition="$now_course['type'] eq 0">
                        <p class="jifen_til" style="color: red;"><if condition="$now_course['score'] gt 0">所需{pigcms{$config.score_name}：{pigcms{$now_course.score}<else/>免费观看</if></p>
                    </if>
                    <p class="timeshow">{pigcms{$now_course.add_time|date='Y-m-d',###}</p>
                </div>
                <if condition="$now_course['cat_name'] neq ''">
                 <div class="weui-cell__ft border-l rel" style="font-size: 14px;">
                    <span style="background-color: {pigcms{$config["mobile_head_color"]}; border: solid 1px #D2691E;border-radius: 2px; padding: 4px 8px; color:#fff;font-size: 12px;">{pigcms{$now_course.cat_name}</span>
                </div>
                </if>
                <!-- 收藏-->
                <!-- <div class="weui-cell__ft border-l rel">
                     <if condition="!$collect_info">
                        <i class="weui-icon-warns iconfont collect" data-is_cancel="0">&#xe610;</i>
                    <else/>
                        <i class="weui-icon-warns iconfont collect" data-is_cancel="1" style="color: red;">&#xe610;</i>
                    </if>
                </div> -->
            </div>
            <span class="color_9 font14" >{pigcms{$now_course.nickname}</span>
        </div>
        <div class="weui-cells pd15 pd-t0 mg-t5  font16 after mg-t5 " >
            <div class="weui-cell pd-l0 ">
                <div class="weui-cell__bd pd-r10">
                    <p>课程简介</p>
                </div>
                <div class="weui-cell__ft font14">浏览量:{pigcms{$now_course.view_times}</div>
            </div>
            <span class="color_9 font14">
                <p>{pigcms{$now_course.content}</p>
                <volist name="now_course['imgUrl']" id="vo">
                    <img src="{pigcms{:file_domain().$vo}" style="max-width:100%" data-preview-src="" data-preview-group="1">
                </volist>
            </span>
                
        </div>

        <section class="groom_dishes">

            <a class="dish_header clr"><b class="ft" style="font-size: 16px;">课程推荐</b><p class="fr"><span style="color: #0a0a0a;" onclick="location.href='{pigcms{:U("course_recommend_list")}'">更多推荐</span> <i></i></p></a>
            <div class="photo_sroll">
                <div class="dish_content clr">
                    <volist name="commendList" id="commend">
                    <a  class="openUrl newWin fl" data-url="{pigcms{:U('course_detail',array('course_id'=>$commend['course_id']))}"   >
                        <dl>
                            <dt><img src="{pigcms{:file_domain().$commend['cover_img']}" alt=""></dt>
                            <dd>{pigcms{$commend.title}</dd>
                        </dl>
                    </a>
                    </volist>
                </div>
            </div>

        </section>
<div class="row bbs-my-top">
        <div class="col-100 detail-user-comment">
            <div class="bbs-foot bbs-detail-pl">
                <p>评论&nbsp;(共{pigcms{$commListCount}条评论)
                </p>
            </div>
            <div class="bbs-list-pl">
                <ul>
                    <volist name="commList" id="list">
                        <li style="border-top: 1px solid #e5e5e5;">
                            <div class="bbs-detail-tx">
                                <img src="{pigcms{$list['avatar']}">
                                <div class="bbs-detail-pl-l">
                                    <p class="nickname top_comment">{pigcms{$list['nickname']}</p>
                                    <p class="bbs-detail-date">{pigcms{$list['cl_CommentTime']|date="Y-m-d H:i",###}</p>
                                </div>
                            </div>
                            <p class="bbs-detail-pl-desc">{pigcms{$list['cl_CommentContent']}</p>
                        </li>
                    </volist>
                </ul>
            </div>
        </div>
</div>
    </div>
</div>
<footer class="footerMenu" style="background-color: #E0E0E0;height: 58px;">
    <input style="padding-left: 10px;border-radius: 20px;" type="text" id="content" placeholder="发表评论:">
    <input type="hidden" id="videoId" name="videoId" value="{pigcms{$_GET['course_id']}"/>
    <div class="fasong">发布</div>
</footer>
<div class="weui-tabbar bg-f" style="    position: fixed;">
  <!--   <a href="#tab3" class="weui-tabbar__item">
        <div class="weui-tabbar__icon">
            <i class="weui-icon-warns iconfont ">&#xe835;</i>
        </div>
        <p class="weui-tabbar__label">发表评论</p>
    </a> -->
    <?php if ($now_company&&!$can_view): ?>
        <a href="#tab4" class="weui-tabbar__item weui-btn_primary">
            <div class="weui-tabbar__icon">
                <i class="weui-icon-warns iconfont color_f" >&#xe711;</i>
            </div>
            <p class="weui-tabbar__label color_f" data-url="{pigcms{:U('energy_package')}" onclick="location.href='{pigcms{:U('energy_package')}'">开通VIP</p>
        </a>
    <?php endif ?>
</div> 
<script  type="text/javascript"> 
    $(".fasong").click(function(){
        var videoId = $("#videoId").val();
        var content = $("#content").val();

        $.ajax({
            type : "post",
            url : '{pigcms{:U("ajax_add_commend")}',
            dataType : "json",
            data:{'cl_VideoId':videoId,'cl_CommentContent':content},
                async:false,
                success : function(data){
                   console.log(data);
                    if(data.error == 0){
                       $.alert(data.msg,function(){
                        window.location.reload();
                       });

                       return false
                   }else{
                       $.alert(data.msg)
                       return false
                   }

                },
                error:function(res){
                    console.log(res)
                }
        });
        console.log($("#content").val());

        $("#content").val("");//清空
    });



    var View={
        init:function(){
            this.video();
            this.initClick()
        },
        setting:{
            myPlayer:null,
            can_view : '{pigcms{$can_view|intval}'
        },
        video:function(){
            var self=this;
            videojs("my-video").ready(function(){
                self.setting.myPlayer = this;
                play_ready()
            });
            var play_ready=function(){
                //播放开始
                self.setting.myPlayer.on("play",function(){
                    $(".video_content_modal").hide();

                });
                //播放结束
                self.setting.myPlayer.on("ended",function(){

                })
                //暂停
                self.setting.myPlayer.on("pause",function(){

                })
                //加载时
                self.setting.myPlayer.on("loadstart",function(){

                })
                //离开全屏时
                self.setting.myPlayer.on("enterFullScreen",function(){

                })
            }
        },
        initClick:function(){
            var self=this;
            $(document).on("click",".view",function(){
                if (self.setting.can_view=='1') {
                    self.setting.myPlayer.play();
                }else{
                    var couse_times = {pigcms{$now_course["couse_times"]|intval};
                    if (couse_times>0) {
                        var text = '查看该视频，您将消耗一次vip机会，还剩余'+couse_times+'次免费查看次数';
                    }else{

                        var text = '查看该视频，您将消耗{pigcms{$now_course["score"]}{pigcms{$config["score_name"]}';
                    }

                    $.confirm({
                        title: '提示',
                        text: text,
                        onOK: function () {
                            //点击确认
                            $.ajax({
                                type : "post",
                                url : '{pigcms{:U("ajax_pay_course_view")}',
                                dataType : "json",
                                data:{'course_id':{pigcms{$now_course["course_id"]|intval},couse_times:couse_times},
                                async:false,
                                success : function(data){
                                    if(data.error){
                                        if (data.error==2) {
                                            $.alert(data.msg,function(){
                                                window.location.href="{pigcms{:U('Login/index')}";
                                            })
                                        }else if (data.error==3) {
                                            $.alert(data.msg,function(){
                                                window.location.href="{pigcms{:U('score_package')}";
                                            })
                                        }else{
                                            $.alert(data.msg)
                                        }
                                        return false
                                    }else{
                                        self.setting.can_view = '1';
                                        self.setting.myPlayer.play();
                                    }
                                },
                                error:function(res){
                                    console.log(res)
                                }
                            })
                        },
                        onCancel: function () {
                        }
                    });
                }
            })

            //收藏
            $(document).on("click",'.collect',function(){
                var cid = '{pigcms{$now_course["course_id"]}';
                var is_cancel=$(this).attr('data-is_cancel');
                var html = '&#xe610;';
                var _tmp = '1';

                if (is_cancel=='1') {
                    html = '&#xe610;';
                    _tmp = '0';
                }
                //点击确认
                $.ajax({
                    type : "post",
                    url : '{pigcms{:U('do_collect')}',
                    dataType : "json",
                    data:{'cid':cid,is_cancel:is_cancel,type:'course'},
                    async:false,
                    success : function(data){
                        if(data.errorMsg!='success'){
                            $.alert(data.errorMsg)
                            return false
                        }
                        $.alert(data.result)
                        $('.collect').html(html);
                        $('.collect').attr('data-is_cancel',_tmp);
                        if (is_cancel=='1') {
                            $('.collect').css('color','#999999');
                        }else{
                            $('.collect').css('color','red');
                        }
                    },
                    error:function(res){
                        console.log(res)
                    }
                })
            })
        }
    }
    window.View=View;
    View.init();
</script>

<script> 
    mui.previewImage();
</script>  
 <div class="share_html">{pigcms{$shareScript}</div> 
</body>
</html>