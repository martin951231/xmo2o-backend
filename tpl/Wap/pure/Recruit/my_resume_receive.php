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
    <title>求职者管理</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <style>
        .w100{width:100%}
        .h100{height:100%}
        .footerMenu ul li a em.add {
            background-image: url(add.png);
            margin-top: -12px;
            height: 37px;
            width: 37px;
        }
        .footerMenu ul li {
            width: 20%;
        }
        .fl{
            float:left
        }
        .fr{
            float:right
        }
        .clear{
            clear:both
        }
        .sendList{
            position: relative;
        }
        .sendList .interested{
            position: absolute;
            right:10px;
            top:20px;
            color:#ff552e;
            border:1px solid #ff552e;
            font-size:10px;
            padding:2px; 
            border-radius: 4px;
        }
        .sendList .weui-media-box__desc{
           padding:10px 0 0 0
        }
        .dropload-refresh, .dropload-update, .dropload-load, .dropload-noData{
            color: #999;
        }
        .header a{
            font-size: 15px;
        }
        .header .cur a{
            color: #06c1ae;
            font-size: 15px;
        }
        .header .cur{
            border-bottom: 2px solid #06c1ae;
            color: #06c1ae;
        }
        .header span{
            line-height: 47px;
            margin-left: 12px;
            padding: 3px 14px;
        }
        body{
            background: #f4f4f4;
        }

        .message_last{font-size: 14px;}
        .weui-media-box_appmsg .weui-media-box__hd.phone{margin-top: -25px;margin-left: 10px;margin-right: 10px;}
        .phone img{width: 40px;height: 40px;}
        .time{font-size: 14px;color: #999;white-space: nowrap;line-height: 2;margin-left: -60px;}
        
    </style>
</head>
<body>
    <div class="weui-panel weui-panel_access header" style="height: 40px;text-align: center;">
        <div class="weui-panel__bd" style="height: 40px;">
            <span class="cur">
                <a href="{pigcms{:U('my_resume_receive')}">投递</a>
            </span>
              
            <span>
              <a href="{pigcms{:U('company_invitation')}">邀约</a>
            </span>
        </div>
    </div>
    <div class="weui-panel weui-panel_access content">
        <div class="weui-panel__bd">          
        </div>
    </div>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
    <script type="text/javascript">
        var ajax_my_resume_receiv_url = "{pigcms{:U('ajax_my_resume_receive')}";
        var resume_detail_url = "{pigcms{:U('resume_detail')}";
        $(function(){
            // 页数
            var page = 0;
            // 每页展示10个
            var count = 10;   
            // dropload
            $('.content').dropload({
                scrollArea : window,
                loadDownFn : function(me){
                    page++;
                    // 拼接HTML
                    var result = '';  
                    $.ajax({
                        type: 'GET',
                        url: ajax_my_resume_receiv_url,
                        data:{
                          'page':page,
                          'count':count
                        },
                        dataType: 'json',
                        success: function(data){   
                            var dateArr = data.result.list;
                            if(dateArr.length > 0){ 
                                for(var i=0;i<dateArr.length;i++){
                                    result+='<div class="weui-media-box weui-media-box_appmsg">';
                                    result+='   <div class="weui-media-box__hd">';
                                    result+='       <img class="weui-media-box__thumb" src="'+dateArr[i].avatar+'" alt="">';
                                    result+='   </div>';
                                    result+='   <div data-url="'+resume_detail_url+'&resume_id='+dateArr[i].resume_id+'&position_id='+dateArr[i].position_id+'"class="weui-media-box__bd resume_detail link" data-can_view="'+dateArr[i].can_view+'" >';
                                    result+='       <h4 class="weui-media-box__title">'+dateArr[i].truename+'</h4>';
                                    // result+='        <span class="static_online">2</span>';
                                    result+='       <p class="resume_label">';
                                    if (dateArr[i].age) {
                                    result+='           <label>'+dateArr[i].age+'岁</label>';
                                    }
                                    if (dateArr[i].work_years&&dateArr[i].work_years!=null) {
                                    result+='           <label>'+dateArr[i].work_years+'</label>';
                                    }
                                    if (dateArr[i].qualifications&&dateArr[i].qualifications!=null) {
                                    result+='           <label>'+dateArr[i].qualifications+'</label>';
                                    }
                                    result+='       </p>';
                                    result+='       <p class="message_last">';
                                    result+='           <span>投递职位：'+dateArr[i].position_name+'</span>';
                                    result+='       </p> ';
                                    
                                    result+='   </div>';
                                    result+='   <div class="weui-media-box__hd phone"><div class="time">'+dateArr[i].add_time_format+'</div>';
                                    if (dateArr[i].can_view==1) {
                                        result+='<a href="tel:'+dateArr[i].phone+'"><img  src="{pigcms{$static_path}recruit/images/phone.png" /></a>';
                                    }else{
                                        result+='<a class="show_phone phone_'+dateArr[i].resume_id+'" data-resume_id="'+dateArr[i].resume_id+'"><img  src="{pigcms{$static_path}recruit/images/phone.png" /></a>';
                                    }
                                    result+='   </div>';
                                    result+='</div>';
                                    
                                 
                                }  
                            }else{ 
                                me.lock(); // 锁定 
                                me.noData(); // 无数据
                            }
                            setTimeout(function(){
                                $('.weui-panel__bd').append(result); 
                                me.resetload(); // 每次数据插入，必须重置 
                            })
                            
                        },
                        error: function(xhr, type){
                            // alert('Ajax error!');
                            // 即使加载出错，也得重置
                            me.resetload();
                        }
                    });
                }
            });
        });

        View={
        init:function(){
            this.click()
        },
        click:function(){
            var _this=this
            $(document).on("click",'.show_phone',function(){
                var that = $(this)
                var resume_id = $(this).data('resume_id');
                var can_view = $(this).data('can_view');
                var location_url = $(this).data('url');
                var need_score = '{pigcms{$config["recruit_view_resume_need_score"]}';
                if (can_view==1) {
                     window.location.href = that.attr('href');
                     return false;
                }
                var text = '';
                if (need_score) {
                        text = '您将消耗'+need_score+'{pigcms{$config["score_name"]},确定打电话吗？';
                }else{
                    text = '确定打电话吗？';
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
                                    $('.phone_'+resume_id).attr('href','tel:'+ data.phone);
                                    $('.phone_'+resume_id).attr('data-can_view',1);
                                    window.location.href = 'tel://' + data.phone
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
        }
    }
    window.View=View;
    View.init()
    </script>
</body>
</html>