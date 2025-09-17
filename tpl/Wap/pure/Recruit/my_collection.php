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
    <title>我的收藏</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
	<script>
		var currency_symbol = "{pigcms{$config.Currency_symbol}";
	</script>
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
        .resumeList .weui-media-box:before{ 
            border-top:none
        }
        .resumeList .weui-media-box__info{
           margin-top:0
        }
        .resumeList .weui-media-box{
          padding:10px;
          margin:10px 0;
          
        }
        .dropload-refresh, .dropload-update, .dropload-load, .dropload-noData{
            color: #999;
        }
        
    </style>
</head>
<body>
    <div class="weui-panel weui-panel_access">
        <div class="weui-panel__bd">
        </div>
    </div>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript">
        var ajax_my_collection_url = "{pigcms{:U('ajax_my_collection')}";
        var position_detail_url = "{pigcms{:U('position_detail')}";
        $(function(){
            // 页数
            var page = 0;
            // 每页展示10个
            var count = 10;   
            // dropload
            $('.weui-panel_access').dropload({
                scrollArea : window,
                loadDownFn : function(me){
                    page++;
                    // 拼接HTML
                    var result = '';  
                    $.ajax({
                        type: 'GET',
                        url: ajax_my_collection_url,
                        data:{
                          'page':page,
                          'count':count
                        },
                        dataType: 'json',
                        success: function(data){   
                            var dateArr = data.result.list;
                            if(dateArr.length > 0){ 
                                for(var i=0;i<dateArr.length;i++){ 
                                    result+='<a href="'+position_detail_url+'&position_id='+dateArr[i].position_id+'" class="weui-media-box weui-media-box_appmsg"> ';
                                    result+='   <div class="weui-media-box__bd">';
                                    result+='       <h4 class="weui-media-box__title">'+dateArr[i].position_name+'</h4>';
                                    result+='       <p class="weui-media-box__desc">'+dateArr[i].company_name+'</p>';
                                    result+='       <ul class="weui-media-box__info"> ';
                                    result+='           <li class="weui-media-box__info__meta" style="width:100%">';
                                    result+='               <span class="fl" style="color:#ff552e">'+currency_symbol+dateArr[i].salary+'</span> ';
                                    result+='               <span class="fr">'+dateArr[i].add_time_format+'</span>';
                                    result+='               <div class="clear"></div>';
                                    result+='           </li> ';
                                    result+='       </ul>';
                                    result+='   </div> ';
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