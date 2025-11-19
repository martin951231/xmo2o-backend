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
    <title>积分购买</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
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
         .memberbox{
             background:#f3f3f3;
             margin:10px;
         }
         .memberList{
             background:#fff;
             padding:10px;
             position: relative;
             height:50px;
             margin-top:15px;
         }
         .memberList span{
             display:block;
             float:left;
             width:40%;
             text-align:center;
         }
         .memberList span.memberList_3{
             width:20%
         }
         .memberListLine{
             width:1%;
             border-left:1px dashed #f3f3f3;
             position:absolute;
             right:21%;
             height:100%;
         }
         .memberList span.memberListCricle{
            position: absolute;
            width:20px;
            height:20px; 
            background:#f3f3f3; 
            border-radius: 100%;
            right:20%;
         }
         .memberList .memberListCricle.memberListTopCricle{
             top:-10px; 
         }
         .memberList .memberListCricle.memberListBottomCricle{
             bottom:-10px; 
         }
         .memberList .memberList_3{
             line-height:50px;
             color:#ff7b5d; 
         }
         .memberList .memberList_2 .lab1{
            font-size:16px;
            color:#333
         }
         .memberList .memberList_2  p{
             font-size:12px;
             color:#999; 
         }
         .memberList .memberList_2  p .lab2{
            text-decoration: line-through;
         }
    </style>
</head>
<body>
    <div class="memberbox">
       <div class="content">
           
       </div>


    </div>
    <script type="text/javascript">
        var url = "{pigcms{:U('ajax_get_score_package')}"
    </script>

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript">
        var url = "{pigcms{:U('ajax_get_score_package')}";
        var score_go_pay_url = "{pigcms{:U('score_go_pay')}";
        $(function(){
            // 页数
            var page = 0;
            // 每页展示10个
            var count = 10;   
            // dropload
            $('.memberbox').dropload({
                scrollArea : window,
                loadDownFn : function(me){
                    page++;
                    // 拼接HTML
                    var result = '';  
                    $.ajax({
                        type: 'GET',
                        url: url,
                        data:{
                          'page':page,
                          'count':count
                        },
                        dataType: 'json',
                        success: function(data){   
                            var dateArr = data.result.list;
                            if(dateArr.length > 0){ 
                                for(var i=0;i<dateArr.length;i++){ 
                                    result+='<div class="memberList link" data-url="'+score_go_pay_url+'&package_id='+dateArr[i].package_id+'">';
                                    result+='   <span class="memberListCricle memberListTopCricle"></span>';
                                    result+='   <span class="memberListCricle memberListBottomCricle"></span>';
                                    result+='   <div class="memberListLine"></div>';
                                    result+='   <span class="memberList_1">'+dateArr[i].score+'个{pigcms{$config.score_name}</span> ';
                                    result+='   <span class="memberList_2">';
                                    result+='       <label class="lab1"> {pigcms{$config["Currency_symbol"]}'+dateArr[i].price+'</label>';
                                    result+='       <p> 原价 <label class="lab2"> {pigcms{$config["Currency_symbol"]}'+dateArr[i].old_price+'</label></p>  ';
                                    result+='   </span>';
                                    result+='   <span class="fr memberList_3">充值</span> ';
                                    result+='   <div class="clear"></div>';
                                    result+='</div>';
                                 
                                }  
                            }else{ 
                                me.lock(); // 锁定 
                                me.noData(); // 无数据
                            }
                            setTimeout(function(){
                                $('.content').append(result); 
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
    </script>
</body>
</html>