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
    <title>简历投递记录</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
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
        .header span{
            line-height: 47px;
            margin-left: 12px;
            padding: 3px 14px;
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
        
    </style>
</head>
<body>
    <div class="weui-panel weui-panel_access header" style="height: 40px;text-align: center;">
        <div class="weui-panel__bd" style="height: 40px;">
            <span class="cur">
                <a href="{pigcms{:U('my_resume_send')}">已投递</a>
            </span>
              
            <span>
              <a href="{pigcms{:U('my_resume_invitation')}">被邀约</a>
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
    <script type="text/javascript">
        var ajax_my_resume_send_url = "{pigcms{:U('ajax_my_resume_send')}";
        var position_detail_url = "{pigcms{:U('position_detail')}";
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
                        url: ajax_my_resume_send_url,
                        data:{
                          'page':page,
                          'count':count
                        },
                        dataType: 'json',
                        success: function(data){   
                            var dateArr = data.result.list;
                            if(dateArr.length > 0){ 
                                for(var i=0;i<dateArr.length;i++){ 
                                    result+=' <a href="'+position_detail_url+'&position_id='+dateArr[i].position_id+'" class="weui-media-box weui-media-box_appmsg sendList"> ';
                                    // result+='    <span class="interested">感兴趣</span>';
                                    result+='    <div class="weui-media-box__bd"> ';
                                    result+='        <h4 class="weui-media-box__title">'+dateArr[i].position_name+'</h4>';
                                    result+='        <p class="weui-media-box__desc">'+dateArr[i].company_name+'</p>';
                                    result+='        <ul class="weui-media-box__info"> ';
                                    result+='            <li class="weui-media-box__info__meta" style="width:100%">';
                                    result+='                <span class="fl"><label class=""><i>&#xe604;</i>'+dateArr[i].work_years+'</label></span> ';
                                    result+='                <span class="fr">'+dateArr[i].add_time_format+'</span>';
                                    result+='                <div class="clear"></div>';
                                    result+='            </li> ';
                                    result+='        </ul>';
                                    result+='    </div> ';
                                    result+='</a> ';
                                 
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
                            alert('Ajax error!');
                            // 即使加载出错，也得重置
                            me.resetload();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>