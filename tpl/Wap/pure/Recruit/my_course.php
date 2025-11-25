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
    <title>商学院视频</title>
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
        
        .view_bottom {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background: #fff;
            z-index: 999;
            height: 50px;
        } 
        .weui-btn_primary {
            width: 90%;
        } 

        .img_box {
            margin-right: .8em;
            width: 160px;
            height: 100px;
            line-height: 100px;
            text-align: center;
        }
        .img_box img {
            width: 100%;
            height: 100%;
            vertical-align: top;
            border-radius: 5px;
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

        .group__title1{
              font-size:12px;
              color:#999;
          }
          .group__title2{
              color:red;
              font-size:16px;
              padding:10px 0;
          }
          .group_course{
              color:#999;
          }
          .group_course.flex{
            justify-content:space-between;
            font-size:13px;
          }
          .group_course.flex .fl{ 
              padding-left:25px; 
              background:url('{pigcms{$static_path}recruit/images/gou.png') left center no-repeat;
              background-size:20px auto;
          }
          .group_course.flex .fr{
              
          }
          .flex_one{
            position: relative;
          }
          .flex_one p{
            overflow: hidden;/*超出部分隐藏*/
            white-space: nowrap;/*不换行*/
            text-overflow:ellipsis;/*超出部分文字以...显示*/
          } 
          .weui-panel_access{
              padding:10px;
          }
          .float_btn{
            position:absolute;
            right:10px;
            top:10px; 
          }
          .float_btn img{
            width:20px;
          }
          .videoBlock{
              padding:10px 0;
              border-bottom:1px solid #eee;
          }
          .add{
              margin-left: 5%;;
          }
    </style>
</head>
<body>

    <div class="weui-panel weui-panel_access">
        <div class="weui-panel__bd">
        </div>
    </div>
    <!-- <div class="footer view_bottom weui-flex border_top addBox">
        <a href="{pigcms{:U('release_video')}" class="weui-btn weui-btn_primary add" style='background-color: {pigcms{$config["mobile_head_color"]}'>发布视频</a>
    </div> -->

    <div class="addBox">
       <a class="weui-btn weui-btn_primary add" href="{pigcms{:U('release_video')}" style='background-color: {pigcms{$config["mobile_head_color"]}'>发布视频</a> 
    </div> 

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript">
        var ajax_my_collection_url = "{pigcms{:U('ajax_my_course')}";
        var course_detail_url = "{pigcms{:U('course_detail')}";
        var release_video_url = "{pigcms{:U('release_video')}";
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
                                    result+=' <a  class="flex border-t videoBlock" href="'+course_detail_url+'&course_id='+dateArr[i].course_id +'">';
                                    result+='	<div class="img_box">';
                                    result+='       <img class="" src="{pigcms{:file_domain()}'+dateArr[i].cover_img +'" alt="">';
                                    result+='	</div>';
                                    result+='	<div class="flex_one" style="min-width:0">';
                                    if (dateArr[i].status!=1) {
                                        result+='<div class="float_btn"><img src="{pigcms{$static_path}recruit/images/delete.png" class="del" data-course_id='+dateArr[i].course_id +'"> <img src="{pigcms{$static_path}recruit/images/edit.png" class="edit link" data-url="'+release_video_url+'&course_id='+dateArr[i].course_id +'"> </div>';
                                    }
                                    result+='       <p class="group__title">'+dateArr[i].title +'</p>';
                                    result+='       <p class="group__title1">'+dateArr[i].content +'</p>'; 
                                    if (dateArr[i].status==0) {
                                        result+='       <p class="group__title2">待审核</p>';  
                                    }else if(dateArr[i].status==1){
                                        if(data.result.list[i].score>0){
                                          result+='       <p class="group__title2">所需{pigcms{$config.score_name}：'+data.result.list[i].score +'</p>';  
                                        }else{
                                          result+='       <p class="group__title2">免费观看</p>';  
                                        }
                                    }else{
                                        result+='       <p class="group__title2">审核未通过</p>';  
                                    }
                                    result+='		<div class="flex group_course">';
                                    result+='          <span class="fl">'+dateArr[i].view_times +'人看过</span>'
                                    result+='          <span class="fr">'+dateArr[i].add_time_format +'</span>'                
                                    result+='		</div>';
                                    result+='	</div>';
                                    result+='</a>';  
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
                var _this=this;
                $(document).on("click",'.del',function(){

                    if(window.event){//IE下阻止默认事件
                        event.returnValue = false;
                    }else{
                        event.preventDefault();
                    }
                    var course_id = $(this).data('course_id');
                    $.confirm({
                        title: '提示',
                        text: '确认删除',
                        onOK: function () {
                            //点击确认
                            $.ajax({
                                type : "post",
                                url : "{pigcms{:U('course_del')}",
                                dataType : "json",
                                data:{course_id:course_id},
                                async:false,
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
                })
            }
        }
        window.View=View;
        View.init()
    </script>
</body>
</html>