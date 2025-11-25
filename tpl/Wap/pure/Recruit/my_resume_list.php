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
    <title>个人简历</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
	<script>
	var currency_symbol = "{pigcms{$config.Currency_symbol}";
	</script>
     <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
     <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
     <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script>
    <script src="{pigcms{$static_path}/classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script> 
    <script src="{pigcms{$static_path}recruit/js/zepto.min.js" type="text/javascript" charset="UTF-8"></script>  
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>  
    <div class="share_html">{pigcms{$shareScript}</div> 
    <style>
        .fl{
            float:left
        }
        .fr{
            float:right
        }
        .clear{
            clear:both
        }
        .resumeList .weui-media-box:before{ 
            border-top:none
        }
        .resumeList .weui-media-box__info{
           margin-top:0
        }
        .resumeList .weui-media-box{
          padding:10px;
          margin:10px 0;
          background:#f8f8f8;
          display:block;
        }
        .demos-content-padded{
            padding-bottom:50px;
        }
        .add{   
            margin:10px;
        } 
        .addBox{
            position: fixed;
            background:#fff;
            bottom:0px;
            left:0;
            width:100%; 
            z-index:1;
            height:50px;
            padding-bottom:15px;
        }
        .weui-media-box__info__meta span{
            margin-right:10px;
        }
        .pTil .weui-media-box__title{
            width: 65%;
        }
        .pTil_static{
            color:#CC3300;
            padding-right: 10px;
            font-size: 13px;
        }
        .pTil_static_yes{
            color:green;
            padding-right: 10px;
            font-size: 13px;
        }
        .pTil_static_refresh{
            color:#ff6f4e;
            padding-right: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body> 
    <div class="demos-content-padded">
        <div class="weui-media-box weui-media-box_text resumeList" >  
        </div> 
    </div> 
    <div class="addBox">
       <a class="weui-btn weui-btn_primary add" style='background-color: {pigcms{$config["mobile_head_color"]}'>添加简历</a> 
    </div> 
   
    <script type="text/javascript"> 
    var ajax_my_resume_list_url = "{pigcms{:U('ajax_my_resume_list')}"; 
   $(function(){
    // 页数
    var page = 0;
    // 每页展示10个
    var count = 10;   
    // dropload
        $('.demos-content-padded').dropload({
            scrollArea : window,
            loadDownFn : function(me){
                page++;
                // 拼接HTML
                var result = '';  
                $.ajax({
                    type: 'GET',
                    url: ajax_my_resume_list_url,
                    data:{
                      'page':page,
                      'count':count
                    },
                    dataType: 'json',
                    success: function(data){   
                        var arrLen = data.result.list.length;
                        if(arrLen > 0){
                            for(var i=0;i<arrLen;i++){ 
                                result+=' <a class="weui-media-box weui-media-box_text openUrl newWin"  data-url="{pigcms{:U('resume_detail')}&resume_id='+data.result.list[i].resume_id+'" >';
                                result+='<div class="pTil"><h4 class="weui-media-box__title fl">'+data.result.list[i].resume_name+'</h4>';
                                result+='<span class="fr pTil_static_refresh refresh_resume" data-resume_id="'+data.result.list[i].resume_id+'">刷新</span>';
                                if (data.result.list[i].self_introduction!='' && data.result.list[i].has_work=='1' && data.result.list[i].has_education=='1') {
                                    result+='<span class="fr pTil_static_yes">发布成功</span><div class="clear"></div></div>';
                                }else{
                                    result+='<span class="fr pTil_static">待完善</span><div class="clear"></div></div>';
                                }
                                result+='   <ul class="weui-media-box__info"> '
                                result+='     <li class="weui-media-box__info__meta" style="width:100%">'
                                result+='         <span class="fl" style="margin-right:5px;"> '+data.result.list[i].add_time_format+'</span>'
                                result+='        <span class="fl">浏览 '+data.result.list[i].view_times+'</span>' 
                                result+='        <span class="fr delete" data-resume_id="'+data.result.list[i].resume_id+'">删除</span>' 
                                result+='        <div class="clear"></div>'
                                result+='    </li> '
                                result+=' </ul>'
                                result+=' </a>' 
                            }  
                        }else{ 
                            me.lock(); // 锁定 
                            me.noData(); // 无数据
                        }
                        setTimeout(function(){
                            $('.resumeList').append(result); 
                            me.resetload(); // 每次数据插入，必须重置 
                        })
                        
                    },
                    error: function(xhr, type){
                        alert('Ajax error!');
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
            $(document).on("click",'.add',function(){
                //点击确认
                $.ajax({
                    type : "post",
                    url : "{pigcms{:U('ajax_check_status')}",
                    dataType : "json",
                    data:{},
                    async:false,
                    success : function(data){
                        if(data.errorMsg!='success'){
                            if (data.errorCode=='73000008') {
                                $.alert(data.errorMsg,function(){
                                    window.location.href="{pigcms{:U('Login/index')}";
                                })
                            }else if(data.errorCode=='20120035'){
                                $.alert(data.errorMsg,function(){
                                    window.location.href="{pigcms{:U('My/bind_user')}";
                                })
                            }else{
                                $.alert(data.errorMsg);
                            }
                            return false
                        }else{
                            window.location.href="{pigcms{:U('my_resume_base')}";
                            
                        }
                    },
                    error:function(res){
                        console.log(res)
                    }
                })
            })
            $(document).on("click",'.delete',function(){ 
                var resume_id = $(this).data('resume_id');
                $.confirm({
                    title: '提示',
                    text: '确认删除',
                    onOK: function () {
                        //点击确认
                        $.ajax({
                            type : "post",
                            url : "{pigcms{:U('resume_del')}",
                            dataType : "json",
                            data:{resume_id:resume_id},
                            async:false,
                            success : function(data){
                                if(data.code>0){
                                    $.alert(data.msg);
                                    return false
                                }else{
                                    $.alert(data.msg,function(){
                                        window.location.reload();
                                    })
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
                return false;
            })

            // 刷新简历
            $(document).on("click",'.refresh_resume',function(){
                var recruit_refresh_resume_score = {pigcms{$config['recruit_refresh_resume_score']|floatval};
                if (recruit_refresh_resume_score) {
                    var text = '刷新简历需要消耗'+recruit_refresh_resume_score+'{pigcms{$config[\'score_name\']},您确定要刷新简历吗？';
                }else{
                    var text = '您确定要刷新简历吗？';
                }
                var resume_id = $(this).data('resume_id');
                $.confirm({
                    title: '提示',
                    text: text,
                    onOK: function () {
                        //点击确认
                        $.ajax({
                            type : "post",
                            url : "{pigcms{:U('resume_refresh')}",
                            dataType : "json",
                            data:{resume_id:resume_id},
                            async:false,
                            success : function(data){
                                if(data.error){
                                    if (typeof(data.url)!='undefined'&&data.url!='') {
                                        $.alert(data.msg,function(){
                                            window.location.href="{pigcms{:U('score_package')}";
                                        })
                                    }else{
                                        $.alert(data.msg);
                                    }
                                    return false
                                }else{
                                    $.alert(data.msg);
                                }
                            },
                            error:function(res){
                            }
                        })
                    },
                    onCancel: function () {
                    }
                });
                return false;
            })
        }
    }
    window.View=View;
    View.init()
    </script> 
</body>
</html>