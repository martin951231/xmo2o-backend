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
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css?1">
    <title>简历详情</title> 
    <style> 
        body{
            padding: 0;
            margin: 0;
            background: #EBEBEB
        }
        .center{
            text-align: center;
        }
        .indexDetailsBox{ 
             padding-bottom: 45px;
        }
        .indexDetailsTop{
            background: #fff;
            box-shadow:0 2px 20px 0px #eee;  
            padding:10px 10px;
            border-radius: 5px; 
            position: relative;
        }
        .indexDetailsTopPhoto{
            width: 60px;
            height: 60px;
            position: absolute;
            left: 50%;
            margin-left: -30px;
            top:-23px; 
        }
        .indexDetailsTopPhoto img{
            width: 60px;
            height: 60px;
        }
        .indexDetailsTab {
            background: #fff;
        }
        .weui-flex-content{
            margin: 10px 0 0 0;
            padding: 20px 20px;
        }
        .indexDetailsRecruit .weui-flex__item{
            border-right: 1px solid #eee;
            color: #999;
        }
        .indexDetailsRecruit .weui-flex__item:last-child{
            border-right: none;
        }
        .indexDetailsRecruit .weui-flex__item .p1 label{
            font-weight: bold;
            color: #514E4D;
        }
        .indexDetailsTopInfo p{
            color: #999;
        }
        .indexDetailsTopInfo p label{
            margin-left: 10px;
        }
        .indexDetailsTil{
            padding: 0 20px;
            font-weight: bold;
            margin:10px 0;
        }
        .indexDetailsBlock .p1{
            padding: 10px 0 0 0;
        }
        .indexDetailsBlock .p1 span{
            padding-right: 10px;
        }
        .indexDetailsBlock .p1 label{
            margin-left: 10px;
        }
        .indexDetailsBlock .p2{
            padding: 10px 0;
        }
        .indexDetailsBlock .p2 span{
            border: 1px solid #6382ca;
            color: #6382ca;
            margin-right: 5px;
            padding: 0 5px;
        }
        .indexDetailsBlock .address_lab{
            color: #999;
            position: absolute;
            right: 10px;
            top:20px;
        }
        .indexDetailsBlock .chat_lab{
            position: absolute;
            bottom: 25px;
            right: 20px;
            padding: 2px 10px;
            border: 1px solid #ff552e;
            color: #ff552e;
            border-radius: 5px;
        }
        .indexDetailsBlock{
          border-bottom: 1px solid #f5f5f5; 
        }
        .weui-media-box:before{
            border-top: 1px solid #fff; 
            height: 0!important; 
        }
        .compaybox{
            padding: 10px 0;
        }
        .weui-media-box_appmsg .weui-media-box__hd {
            width: 80px;
            height: 80px;
        }
        .resume_label label{
            font-size: 14px;
            color: #7BA2FD;
            background: #F1F6FF
        }
        .baseMessage .title{
            color: #FF4F00;
            font-size: 13px;
            border-left: 2px solid #FF4F00;
            padding-left: 5px;
        }
        .baseMessage .message_content{
            border-top: 1px solid #ddd;
            margin-top: 10px;
            color: #999;
            font-size: 15px;
            line-height: 40px;
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
        .position_select {
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 999;
            width: 100%;
            height: 100%;
            background-color: #fff;
            /*color: #FFFF01;*/
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
        .rightBtn{
            background:#f5f6f6
        }
        .rightBtn a{
            font-size:12px;
            width:50%;
            text-align:center;
            line-height:14px;
            color:#FF4F00
        }
        .rightBtn a img{
            display:block;
            margin:10px auto 0 auto;
        }
        .photoImg{
            border-radius:100%;
        }
        
    </style>
</head>

<body> 
   <div class="indexDetailsBox">
      <div class="indexDetailsTop">
        <a href="#" class="weui-media-box weui-media-box_appmsg">
            <div class="weui-media-box__hd photoImg" style="background:url('<if condition="$now_resume['avatar']">{pigcms{:file_domain().$now_resume['avatar']}<else/>{pigcms{$static_path}recruit/images/default_avatar.png</if>') center  no-repeat;background-size: cover;"></div>
            <div class="weui-media-box__bd" style="margin-left: 10px">
                <h4 class="weui-media-box__title" style="margin-bottom: 10px">{pigcms{$now_resume['name']}</h4>
                <p class="resume_label">
                    <label>{pigcms{$now_resume['age']}岁</label>
                    <label>{pigcms{$now_resume['qualifications']}</label>
                    <label>{pigcms{$now_resume['work_years']}</label>
                </p>
                <p class="message_last">
                    <!-- <span>在找'+dateArr[i].position_name+'</span> -->
                </p> 
            </div>
        </a>
      </div>

        <div class="indexDetailsTab">
            <div class="weui-flex indexDetailsRecruit center weui-flex-content">
                <div class="weui-flex__item">
                    <p>简历更新时间</p>
                    <p class="p1"><label>{pigcms{$now_resume['update_time_format']}</label></p>
                </div>
                <div class="weui-flex__item">
                    <p>近期投递职位</p>
                    <p class="p1"><label>{pigcms{$now_resume['send_times']}个</label></p>
                </div>
                <div class="weui-flex__item">
                    <p>近期被浏览</p>
                    <p class="p1"><label>{pigcms{$now_resume['view_times']}次</label></p>
                </div> 
              
            </div>
        </div>
        <div class="indexDetailsTab">
            <div class="weui-flex-content baseMessage">
                <p class="title">
                    自我介绍
                </p>
                <div class="message_content">
                    <label>{pigcms{$now_resume['self_introduction']}</label>
                </div>
            </div>
        </div>
        <div class="indexDetailsTab">
            <div class="weui-flex-content baseMessage">
                <p class="title">
                    <span>工作经历</span>
                </p>
                <div class="message_content">
                    <if condition="$now_resume['work_experience']">
                        <volist name="now_resume['work_experience']" id="vo">
                            <div>
                                <p style="    font-size: 12px;">
                                    {pigcms{$vo['name']}
                                    <label>
                                        {pigcms{$vo['start_time']|date='Y-m-d',###}至<if condition="$vo['end_time']">{pigcms{$vo['end_time']|date='Y-m-d',###}<else/>今</if>
                                    </label>
                                </p>
                                <div style="color: #514E4D">
                                    {pigcms{$vo['desc']}
                                </div>
                            </div>
                        </volist>
                    
                    <else/>
                        暂未添加
                    </if>

                </div>
            </div>
        </div>
        <div class="indexDetailsTab">
            <div class="weui-flex-content baseMessage">
                <p class="title">
                    <span>教育经历</span>
                </p>
                <div class="message_content">
                    <if condition="$now_resume['education_experience']">
                        <volist name="now_resume['education_experience']" id="vo">
                            <div>
                                <p style="    font-size: 12px;">
                                    {pigcms{$vo['name']}
                                    <label>
                                        {pigcms{$vo['start_time']|date='Y-m-d',###}至<if condition="$vo['end_time']">{pigcms{$vo['end_time']|date='Y-m-d',###}<else/>今</if>
                                    </label>
                                </p>
                                <div style="color: #514E4D">
                                    {pigcms{$vo['desc']}
                                </div>
                            </div>
                        </volist>
                    
                    <else/>
                        暂未添加
                    </if>
                </div>
            </div>
        </div>
      
        <div class="footer view_bottom weui-flex border_top">
            <if condition="$is_self eq 1">
            <div class="weui-flex__item">
                <a href="{pigcms{:U('my_resume_base',array('resume_id'=>$now_resume['resume_id']))}" class="weui-btn weui-btn-aaa"  style="background-color: #ff4d00;color: #fff;">编辑</a>
            </div>
            <else/>
            <div class="weui-flex__item" style="background:#FF4F00">
                <if condition="$can_view eq 1">
                    <a href="tel:{pigcms{$now_resume['phone']}" class="weui-btn" > 
                        <img src="{pigcms{$static_path}recruit/images/telIcon.png" style="height:20px;">  
                {pigcms{$now_resume['phone']}</a>
                <else/>
                    <a class="weui-btn show_phone" > 
                        <img src="{pigcms{$static_path}recruit/images/telIcon.png" style="height:20px;">
                        联系方式
                    </a>
                </if>
            </div>
            <div class="weui-flex__item rightBtn">
                <a href="javascript:;" style="width:50%; float:left" class="invitation">
                  <img src="{pigcms{$static_path}recruit/images/mian.png" style="height:15px;">
                   面试邀约
                </a>
                <a href="{pigcms{:U('My/go_im',array('hash'=>'group_user'.$now_resume['uid'],'title'=>urlencode($now_resume['nickname'])))}"  style="width:50%;float:left"  id="contactBtn" data-self="0">
                <img src="{pigcms{$static_path}recruit/images/online.png" style="height:15px;">在线交流
               </a>
            </div>
            </if>
        </div>
   </div>

<div class="position_select" style="display: none;background: #EBEBEB;">
    <div class="page__bd" style="background-color: #FF4F00"> 
        <div href="javascript:;" class="icon_back" id="aaa" style="display: inline-block;width: 40px;    height: 40px;color: #FF4F00">返回</div>
        <ul class="top_fixed">
            <li class="topName">
                <span class="nav-head-name">选择职位</span>
            </li> 
        </ul> 
    </div>
    <input class="weui-input" type="hidden" id="position_id">
    <if condition="$position_list">
        <volist name="position_list" id="vo"> 
            <div class="weui-media-box weui-media-box_text" data-position_id="{pigcms{$vo.position_id}" style="background: #fff;margin: 10px 0;">
                <h4 class="weui-media-box__title">{pigcms{$vo.position_name}</h4>
            </div>
        </volist>
    <else/>
        <p style="text-align: center; margin-top: 20px;color: #aaa;">暂无职位</p>
    </if>
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
<div class="mask"></div>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}/recruit/js/recruit.js"></script> 
<script>
var position_id = {pigcms{$_GET['position_id']|intval};
View={
    init:function(){
        this.click()
        this.selectTime()
    },
    click:function(){
        var _this=this
        //选择简历
        $(document).on("click",'.invitation',function(){
            if (position_id>0) {
                $(".view_bottom").css('z-index',0);
                $(".position_select").css('z-index',0);
                $("#invitation_pop").popup();
                $("#position_id").val(position_id);
                $("#invitation_time").val('');
            }else{
                $('.position_select').show();
            }
        })
       
        $(document).on("click",'.page__bd',function(){
            $('.position_select').hide();
        })

        $(document).on("click",'.position_select .weui-media-box_text',function(){
            $(".view_bottom").css('z-index',0);
            $(".position_select").css('z-index',0);
            $("#invitation_pop").popup();
            var position_id1=$(this).data('position_id')
            $("#position_id").val(position_id1);
            $("#invitation_time").val('');
        })

        $('.cancel_invitation').click(function(){
            $(".view_bottom").css('z-index',999);
            $(".position_select").css('z-index',999);
            $("#position_id").val(0);
            $.closePopup()
        })

        //邀约简历
        $(document).on("click",'.do_invitation',function(){
            var resume_id = '{pigcms{$now_resume["resume_id"]}';
            var position_id=$('#position_id').val();
            var need_score = '{pigcms{$config["recruit_invitation_resume_need_score"]}';
            var invitation_times = {pigcms{$invitation_times|intval};
            var invitation_time = $("input[name=invitation_time]").val();
            var remark = $("textarea[name=remark]").val();
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
                                if (data.error=='2') {
                                    $.alert(data.msg,function(){
                                        window.location.href="{pigcms{:U('score_package')}";
                                    })
                                }else{
                                    $.alert(data.msg);
                                }
                                return false
                            }else{
                                $('.position_select').hide();
                                $('.mask').hide();
                                $(".view_bottom").css('z-index',999);
                                $(".position_select").css('z-index',999);
                                $("#position_id").val(0);
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

        // 查看号码
        $(document).on("click",'.show_phone',function(){ 
                var resume_id = {pigcms{$_GET['resume_id']|intval};
                var resume_times = {pigcms{$resume_times|intval};
                var need_score = '{pigcms{$config["recruit_view_resume_need_score"]}';
                var text = '';
                if (need_score) {
                    if (resume_times>0) {
                        text = '您将消耗一次vip机会，还剩余'+resume_times+',确定查看简历吗？';
                    }else{
                        text = '您将消耗'+need_score+'{pigcms{$config["score_name"]},确定查看简历吗？';
                    }
                }else{
                    window.location.reload();
                }
                $.confirm({
                    title: '提示',
                    text: text,
                    onOK: function () {
                        $.ajax({
                            type : "post",
                            url : "{pigcms{:U('do_resume_view')}",
                            dataType : "json",
                            data:{resume_id:resume_id},
                            async:false,
                            success : function(data){
                                if(data.error!='0'){
                                    if (data.error=='2') {
                                        $.alert(data.msg,function(){
                                            window.location.href="{pigcms{:U('Login/index')}";
                                        })
                                    }else if (data.error=='3'){
                                        $.alert(data.msg,function(){
                                            window.location.href="{pigcms{:U('company_apply')}";
                                        })
                                    }else if (data.error=='4'){
                                        $.alert(data.msg,function(){
                                            window.location.href="{pigcms{:U('score_package')}";
                                        })
                                    }else{
                                        $.alert(data.msg);
                                    }
                                    return false
                                }else{
                                    window.location.reload();
                                }
                            },
                            error:function(res){
                                console.log(res)
                            }
                        })
                    },
                    onCancel: function () { 
                    }
                })
            })
    },
    selectTime:function(){
        //选择日期
        // var format=nTime.setDate(nTime.getDate()-1)
        // var after=new Date(format)
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
                    // console.log(values)
                    $("#invitation_time").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4]);
                    $("input[name=invitation_time]").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])

                }
            });
        }
        timers('2030-12-31 23:59',getDate(afterTime),false)
    }
}
window.View=View;
View.init()
</script>
</body>
</html>