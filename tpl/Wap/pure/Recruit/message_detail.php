<!DOCTYPE html>
<html>
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
        <title>消息详情</title>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css">
        <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
        <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
        <script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
        <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
        <style type="text/css">
            body{
                background: #EBEBEB;
            }
            .icon_back:after {
                display: block;
                content: "";
                border-top: 2px solid white;
                border-left: 2px solid white;
                width: 11px;
                height: 11px;
                -webkit-transform: rotate(315deg);
                background-color: transparent;
                position: absolute;
                top: 14px;
                left: 17px;
            }
            .top_fixed .topName span {
                background-size: 20px auto;
                padding-right: 25px;
            }
            .mingpian,.message{
                margin: 10px 10px;
                border-radius: 5%;
            }
            .mingpian .content{
                padding: 10px 10px;
            }
            .mingpian .mp_middle,.mp_footer{
                border-top: solid 1px #eee;
                line-height: 40px;
                font-size: 14px;
            }
            .mingpian .mp_middle  span,.mp_footer span{
                font-size: 14px;
                color: #a2a2a2;
            }
            .mingpian .mp_middle  p,.mp_footer p{
                margin-left: 16px;
            }
            .weui-media-box__thumb {
                height: 100%;
            }
            .message .avatar {
                float: left;
                margin-top: 20px;
            }
            .message .msg {
                margin-top: 20px;
                background-color: #fff;
                margin-left: 55px;
                border-radius: 5%;
                padding: 14px 14px;
            }
            .view_bottom {
                position: fixed;
                left: 0;
                bottom: 0;
                width: 100%;
                background: #fff;
                z-index: 999;
                height: 45px;
            }
            .weui-btn-aaa {
                background-color: #fff;
               color: #000;
            }
            .resume_static span label{
               color: #a2a2a2;
            }
            
            
        </style>
    </head>
    <body> 
        <div class="">
            <div class="page__bd" onclick="window.location.href='{pigcms{:U('message')}'"> 
                <a href="{pigcms{:U('message')}" class="icon_back" style="display: inline-block;"></a>
                <ul class="top_fixed">
                    <li class="topName">
                        <span class="nav-head-name">{pigcms{$resume_send_detail['truename']}</span>
                    </li> 
                </ul> 
            </div>
            <div class="weui-panel weui-panel_access mingpian">
                <div class="weui-panel__bd content">
                    <a href="{pigcms{:U('resume_detail',array('resume_id'=>$resume_send_detail['resume_id']))}" class="weui-media-box weui-media-box_appmsg">
                        <div class="weui-media-box__hd">
                            <img class="weui-media-box__thumb" src="{pigcms{$resume_send_detail['avatar']}" alt="">
                        </div>
                        <div class="weui-media-box__bd">
                            <h4 class="weui-media-box__title">{pigcms{$resume_send_detail['truename']}</h4>
                            <!-- <span class="static_online">当前在线</span> -->
                            <p class="resume_static">
                                <span><img src=""> <label><if condition="$resume_send_detail['sex'] eq 1">男<elseif condition="$resume_send_detail['sex'] eq 2"/>女<else/>未知</if></label></span>
                                <span><img src=""> <label>{pigcms{$resume_send_detail['age']}岁</label></span> 
                                <span><img src=""> <label>{pigcms{$resume_send_detail['user_work_years']}</label></span> 
                            </p> 
                        </div>
                    </a>
                    <div class="mp_middle">
                        <p><span>他在找：</span>{pigcms{$resume_send_detail['position_name']}<span>/期望薪资：</span>{pigcms{$resume_send_detail['salary']}</p>
                    </div>
                   <!--  <div class="mp_footer">
                        <p><span>他在找：</span>安正是否<span>/期望薪资：</span>面议</p>
                    </div> -->
                </div>
            </div>
        </div>

        <div class="">
            <div class="weui-panel weui-panel_access weui-media-box_appmsg message">
                <div class="weui-panel__bd content"  style="background-color: #EBEBEB">
                    <div class="weui-media-box__hd avatar">
                        <img class="weui-media-box__thumb" src="{pigcms{$resume_send_detail['avatar']}" alt="" style="">
                    </div>
                    <div class="msg">
                        <p>您好，我正在找:{pigcms{$resume_send_detail['position_name']},和您的要求符合，已向您投递简历</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer view_bottom weui-flex border_top">

            <div class="weui-flex__item">
                <if condition="$invitation">
                <a href="javascript:;" class="weui-btn weui-btn-aaa" is_invitation="1">已邀约</a>
                <else/>
                <a href="javascript:;" class="weui-btn weui-btn-aaa invitation open-popup" is_invitation="0" data-target="invitation_pop">面试邀约</a>
                </if>
                <input type="hidden" name="invitation_time">
            </div>
            <div class="weui-flex__item">
                <a href="{pigcms{:U('My/go_im',array('hash'=>'group_user'.$resume_send_detail['uid'],'title'=>urlencode($resume_send_detail['nickname'])))}" class="weui-btn weui-btn-aaa" id="contactBtn" data-self="0">聊聊看</a>
            </div>
        </div>
        <div id="invitation_pop" class="weui-popup__container">
            <div class="weui-popup__overlay"></div>
            <div class="weui-popup__modal">
                <div class="weui-cells weui-cells_form">
                    <header class='demos-header'>
                      <h2 class="demos-second-title" style=" text-align: center; margin: 5px;">简历邀约</h2>
                    </header>
                    <div class="weui-cell weui-cell_warns">
                       <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                           <div class="weui-cell__hd">
                               <label for="" class="weui-label">邀约时间</label>
                           </div>
                           <div class="weui-cell__bd">
                               <input class="weui-input" type="text" id="invitation_time" readonly  placeholder="请选择邀约时间" >
                               <input class="weui-input" type="hidden" name="invitation_time">
                           </div>
                       </div>
                   </div>

                    <div class="weui-cell weui-cell_warns">
                       <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                           <div class="weui-cell__hd">
                               <label for="" class="weui-label">邀约备注</label>
                           </div>
                       </div>
                       <div class="weui-cell weui-text">
                           <div class="weui-cell__bd">
                                <textarea class="weui-textarea" placeholder="请输入文本" rows="3" name="remark"></textarea>
                           </div>
                       </div>
                    </div>
                </div>
               <div class="footer view_bottom weui-flex border_top">
                    <div class="weui-flex__item">
                        <a href="javascript:;" class="weui-btn weui-btn-aaa cancel_invitation" style="background: #ccc;;color: #fff;">取消</a>
                    </div>
                    <div class="weui-flex__item">
                        <a href="javascript:;"  class="weui-btn weui-btn-aaa do_invitation" style="background: #ff4d00;;color: #fff;">确定邀约</a>
                    </div>
                </div>
              </div>
                
            </div>
        </div>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script>
        var do_resume_invitation_url = "{pigcms{:U('do_resume_invitation')}";
        var View={
            init:function(){
                this.initClick()
                this.selectTime()
            },
            setting:{
            },
            initClick:function(){
                var self=this;
                $(document).on("click",".do_invitation",function(){
                    var position_id = {pigcms{$resume_send_detail["position_id"]|intval};
                    var resume_id = {pigcms{$resume_send_detail["resume_id"]|intval};
                    var need_score = '{pigcms{$config["recruit_invitation_resume_need_score"]}';
                    var invitation_times = {pigcms{$invitation_times|intval};
                    var is_invitation = $('.invitation').attr('is_invitation');
                    var invitation_time = $("input[name=invitation_time]").val();
                    var remark = $("textarea[name=remark]").val();
                    if (is_invitation=='1') {
                        return false;
                    }
                    var text = '';
                    if (need_score) {
                        if (invitation_times>0) {
                            text = '您将消耗一次vip机会，还剩余'+invitation_times+'次免费邀约次数';
                        }else{
                            text = '您将消耗{pigcms{$config["recruit_invitation_resume_need_score"]}{pigcms{$config["score_name"]}';
                        }
                    }else{
                        text = '确定邀约';
                    }
                   
                    $.confirm({
                        title: '提示',
                        text: text,
                        onOK: function () {
                            //点击确认
                            $.ajax({
                                type : "post",
                                url : '{pigcms{:U("do_resume_invitation")}',
                                dataType : "json",
                                data:{'position_id':position_id,resume_id:resume_id,invitation_time:invitation_time,remark:remark},
                                async:false,
                                success : function(data){
                                    if(data.error){
                                        $.alert(data.msg)
                                        return false
                                    }else{
                                        $('.invitation').html('已邀约');
                                        $('.invitation').attr('is_invitation',1);
                                        $(".view_bottom").css('z-index',999);
                                        $.closePopup()
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
                })
                $('.invitation').click(function(){
                    var is_invitation = $('.invitation').attr('is_invitation');
                    if (is_invitation=='1') {
                        return false;
                    }
                    $(".view_bottom").css('z-index',0);
                    $("#invitation_pop").popup();
                })
                $('.cancel_invitation').click(function(){
                    $(".view_bottom").css('z-index',999);
                    $.closePopup()
                })

                $('.top_fixed .topCategory .nav-head-name').click(function(){
                    $('.fixpostionBox').toggleClass('show');
                    $(this).toggleClass('active')
                })
                $('.fixpostionBox').click(function(){
                    $('.fixpostionBox').removeClass('show');
                    $('.top_fixed .topCategory .nav-head-name').removeClass('show');
                }) 
            },

            selectTime:function(){
                //选择日期
                var _this=this;
                var nTime = new Date();
                var afterTime=new Date(nTime.getTime()+60*60*1000*3)
                var formatNumber =View.formatNumber= function (n) {
                    return n < 10 ? "0" + n : n;
                }
                var getDate=View.getDate=function(t){
                    return t.getFullYear() + "-" +  formatNumber(t.getMonth()+1) + "-" + formatNumber(t.getDate()) + " "+formatNumber(t.getHours()) + ":" +(formatNumber(t.getMinutes()));

                }
                var timers=function(time,star,type){

                    var max='2099-12-31 23:59';
                    if(type){
                        max=getDate(time)
                    }
                    $("#invitation_time").datetimePicker({
                        title: "邀约时间",
                        min: getDate(afterTime),
                        value:star,
                        max:max,
                        onChange: function (picker, values, displayValues) {
                            $("#time-star").html(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4]);
                            $("input[name=invitation_time]").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])

                        }
                    });
                }
                timers('2030-12-31 23:59',getDate(afterTime),false)
            }
        }
        window.View=View;
        View.init();

        function go_invitation(){
            var position_id = {pigcms{$resume_send_detail["position_id"]|intval};
            var resume_id = {pigcms{$resume_send_detail["resume_id"]|intval};
            var need_score = '{pigcms{$config["recruit_invitation_resume_need_score"]}';
            var invitation_times = {pigcms{$invitation_times|intval};
            var is_invitation = $('.invitation').attr('is_invitation');
            var invitation_time = $("input[name=invitation_time]").val();
            if (is_invitation=='1') {
                return false;
            }
            var text = '';
            if (need_score) {
                if (invitation_times>0) {
                    text = '您将消耗一次vip机会，还剩余'+invitation_times+'次免费邀约次数';
                }else{
                    text = '您将消耗{pigcms{$config["recruit_invitation_resume_need_score"]}{pigcms{$config["score_name"]}';
                }
            }else{
                text = '确定邀约';
            }
           
            $.confirm({
                title: '提示',
                text: text,
                onOK: function () {
                    //点击确认
                    $.ajax({
                        type : "post",
                        url : '{pigcms{:U("do_resume_invitation")}',
                        dataType : "json",
                        data:{'position_id':position_id,resume_id:resume_id,invitation_time:invitation_time},
                        async:false,
                        success : function(data){
                            if(data.error){
                                $.alert(data.msg)
                                return false
                            }else{
                                $('.invitation').html('已邀约');
                                $('.invitation').attr('is_invitation',1);
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
    </script>
    </body>
    
</html>
