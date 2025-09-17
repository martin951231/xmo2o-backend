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
    <title>我的职位</title>
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
           padding:10px 0 0 0;
           position: relative;
        }
        .dropload-refresh, .dropload-update, .dropload-load, .dropload-noData{
            color: #999;
        }
        .edit{
            border: 1px solid #ff4d00;
            color: #fff;
            margin-right: 50px;
            float: right;
            background: #ff4d00;
            padding: 3px;
            border-radius: 5px;
            width: 40px;
            text-align: center;
        }
        .fr p{
            text-align: center;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        .recommend {
            border: 1px solid #ff4d00;
            color: #fff;
            margin-right: 20px;
            float: right;
            background: #ff4d00;
            padding: 3px;
            border-radius: 5px;
            width:60px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="weui-panel weui-panel_access content">
        <div class="weui-panel__bd">          
        </div>
    </div>

    <div class="addBox">
       <a class="weui-btn weui-btn_primary add" href="{pigcms{:U('position_edit')}" style='background-color: {pigcms{$config["mobile_head_color"]}'>发布职位</a> 
    </div> 

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript">
        var ajax_my_position_url = "{pigcms{:U('ajax_my_position')}";
        var position_detail_url = "{pigcms{:U('position_detail')}";
        var position_edit_url = "{pigcms{:U('position_edit')}";
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
                        url: ajax_my_position_url,
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
                                    result+='        <p class="weui-media-box__desc">'+dateArr[i].company_name;
                                    if(dateArr[i].status==0){
                                        result+='<span class="fr" style="color:orange; line-height:23px;">待审核</span>';
                                    }else if(dateArr[i].status==1){
                                        result+='<span class="fr" style="color:green; line-height:23px;">审核通过</span>';
                                    }else{
                                        result+='<span class="fr" style="color:red;line-height:23px;">未审核通过</span>';
                                    }
                                    result+='<span style="position: absolute;right: 30px;">'; 
                                    result+='<span class="edit link" data-url="'+position_edit_url+'&position_id='+dateArr[i].position_id+'">编辑</span>'; 
                                    if(dateArr[i].is_recommend==0){
                                    result+='<span class="recommend" data-position_id="'+dateArr[i].position_id+'">置顶</span>'; 
                                    }else{
                                    result+='<span class="recommend" data-position_id="'+dateArr[i].position_id+'">取消置顶</span>'; 
                                    }
                                    result+='</span>'; 
                                    result+=' </p>';
                                    result+='        <ul class="weui-media-box__info"> ';
                                    result+='            <li class="weui-media-box__info__meta" style="width:100%">';
                                    result+='                <span class="fl"><label class="work_years"><img src="{pigcms{$static_path}recruit/images/work_years.png" style="    width: 13px;height: 13px;">'+dateArr[i].work_years+'</label></span> ';
                                    result+='                <span class="fr">'; 
                                    result+=dateArr[i].add_time_format+'</span>';
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
                var _this=this;
                $(document).on("click",'.recommend',function(e){
                    var that = $(this);
                    if(window.event){//IE下阻止默认事件
                        event.returnValue = false;
                    }else{
                        event.preventDefault();
                    }
                    var position_id = $(this).data('position_id');
                    var recommend_html = that.html();
                    console.log(recommend_html)
                    if (recommend_html=='置顶') {
                        var is_recommend = 1;
                    }else{
                        var is_recommend = 0;
                    }
                   
                    $.ajax({
                        type : "post",
                        url : "{pigcms{:U('position_recommend')}",
                        dataType : "json",
                        data:{position_id:position_id,is_recommend:is_recommend},
                        success : function(data){
                            if(data.code>0){
                                if (data.code==2) {
                                    $.alert(data.msg,function(){
                                        window.location.href="{pigcms{:U('Login/index')}";
                                    })
                                }else{
                                    $.alert(data.msg)
                                }
                                return false
                            }else{
                                console.log(that)
                                    that.html('取消置顶');
                                if (recommend_html=='置顶') {
                                    that.html('取消置顶');
                                }else{
                                    that.html('置顶');
                                }
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
        View.init()
    </script>
</body>
</html>