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
    <title>首页</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/shopBase.css?t={pigcms{$_SERVER.REQUEST_TIME}"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css">
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css?1">
<!--    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/index.js?444" charset="utf-8"></script>-->
    <script type="text/javascript">
      var type = "{pigcms{$_GET['type']}";
      var ajax_search__url = '';
        var details_url = "{";
        if (type=='resume') {
            ajax_search__url = "{pigcms{:U('Recruit/ajax_resume_list')}";
            details_url = "{pigcms{:U('Recruit/resume_detail')}";
        }else{
            ajax_search__url = "{pigcms{:U('Recruit/ajax_search_company_list')}";
            details_url = "{pigcms{:U('Recruit/company_details')}";
        }
        <if condition="$long_lat">
            var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";
        <else/>
            var user_long = '0',user_lat  = '0';
        </if>
    </script>

    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>  

    <style type="text/css">
        .hide{
            display: none;
        }

         .weui-panel{
            background:#f3f3f3; 
         }
         
         .weui-panel__bd a{ 
            border-bottom:1px solid #eee;
         }
         .weui-panel__bd .blockData{
            background:#fff;
            margin:10px 10px 0 10px;
         }

         .weui-panel__bd .blockData .message_last{
             padding:10px;
             display:block;
             height:30px;
         }
         .weui-media-box__title .age{
             margin-left:10px;
             padding-left:10px;
             position: relative;
         }
         .weui-media-box__title .age:after{
            position: absolute;
            content:'';
            width:1px;
            height:14px;
            background:#333;
            left:0;
            top:6px;
         }
         .ago_time{
             color:#999;
             position: absolute;
             right:0;
             top:0;
             font-size:12px;
         }
         .online_chat{
            position: absolute;
            right:0;
            top:30px;
            border:1px solid #666;
            padding:0 10px;
            font-size:12px;
         }

         .address{
            background:url('{pigcms{$static_path}recruit/images/adress.png') left center no-repeat;
            background-size:20px auto;
            padding-left:25px;
            color:#999;
            font-size:12px;
         } 
         .compay_block{
            background: #fff;
            margin: 10px;
         }
         .border-t:before{
            border-bottom:1px solid #fff;
         }
        </style>

    </style>
</head>
<body style="background:#f3f3f3">

<div id="pageShopSearch" class="pageDiv" style="display: block;">
    <div id="pageShopSearchHeader" class="searchHeader">
        <div id="pageShopSearchBackBtn" class="searhBackBtn"></div>
        <div id="pageShopSearchBox" class="searchBox">
            <div class="searchIco"></div>
            <input type="text" id="pageShopSearchTxt" class="searchTxt" placeholder="<if condition="$_GET['type'] eq 'resume'">请输入职位名称<else/>请输入企业名称</if>" autocomplete="off"/>
            <div class="delIco" id="pageShopSearchDel"><div></div></div>
        </div>
        <div id="pageShopSearchBtn" class="searchBtn" style='background-color: {pigcms{$config["mobile_head_color"]}'>搜索</div>
    </div>
    <div id="storeList" style="display:none;">
        <dl class="dealcard"></dl>
        <div id="storeListLoadTip">正在加载中...</div>
    </div>
</div>
   <div style="bottom: 50px; top: 50px;"> 
       <section class="moreGroup" style="margin-top:70px;"> 
          <div id="container1">
            <div id="scroller">
                <div class="city weui-panel__bd ">

                </div>
            </div>
          </div> 
       </section> 
   </div>

   <script type="text/javascript">
        $("#pageShopSearchBackBtn").click(function(){
            window.history.go(-1);
        })

        $('.searchTxt').bind('keydown', function (event) {
            var event = window.event || arguments.callee.caller.arguments[0];
            if (event.keyCode == 13){
                $("#pageShopSearchBtn").trigger('click');
            }
        });

       $("#pageShopSearchBtn").click(function(){
           var keyword = $("#pageShopSearchTxt").val();
           var province
           if(province != ''){
               province =  '';
           }else{
               province =  '';
           }
           
           var result = '';
           if(keyword != ''){
            $.ajax({
               type : "post",
               url : ajax_search__url,
               dataType : "json",
               data:{'province':province,'company_name':keyword,'keyword':keyword},
               success : function(data){
                   console.log(data); 
                   $('.city').empty(); 
                    if (type=='resume') {
                        var dateArr = data.result.list;
                        var arrLen = dateArr.length;
                        if(arrLen > 0){
                            for(var i=0;i<arrLen;i++){
                                result+='<div class="blockData">'
                                result+='<a href="'+details_url+'&resume_id='+dateArr[i].resume_id+'" data-resume_id="'+dateArr[i].resume_id+'" data-can_view="'+dateArr[i].can_view+'" class="weui-media-box weui-media-box_appmsg resume_detail">';
                                result+='   <div class="weui-media-box__hd">';
                                if (dateArr[i].avatar) {
                                    result+='       <img class="weui-media-box__thumb" src="{pigcms{:file_domain()}'+dateArr[i].avatar+'" alt="">';
                                }else{
                                    result+='       <img class="weui-media-box__thumb" src="{pigcms{$static_path}recruit/images/default_avatar.png" alt="">';
                                }
                                result+='   </div>';
                                result+='   <div class="weui-media-box__bd">';
                                result+='       <h4 class="weui-media-box__title">'+dateArr[i].name+'<label class="age">'+dateArr[i].age+'岁</label>'+'</h4>';
                                result+='       <span class="ago_time">'+dateArr[i].update_time_format+'</span>';
                                result+='       <span class="online_chat" style="border-color: {pigcms{$config["mobile_head_color"]}; color:{pigcms{$config["mobile_head_color"]}">直聊</span>';
                                result+='       <p class="resume_label">';
                                result+='           <label>'+dateArr[i].work_years+'</label>';
                                result+='           <label>'+dateArr[i].qualifications+'</label>';
                                result+='           <label>'+dateArr[i].salary+'</label>';
                                result+='       </p>'; 
                                result+='   </div>';
                                result+='</a>'; 
                                result+=' <div class="message_last">'; 
                                result+='           <span class="fl"><label style="color:#666">求职意向：</label>'+dateArr[i].position_name+'</span>';
                                result+='           <span class="address fr">'+dateArr[i].area_name+'</span>';
                                result+='           <span class="clear"> </span>';  
                                result+='  </div> ';
                                result+='</div>'
                            }
                        }else{
                           result+='<p style="color:#999; text-align:center; padding:40px 0">暂无数据</p>'
                        }
                    }else{
                        var arrLen = data.result.length;
                        if(arrLen > 0){
                            for(var i=0;i<arrLen;i++){
                                result+=' <a href="'+details_url+'&company_id='+data.result[i].company_id+'" class="compay_block flex border-t">';
                                result+='   <div class="img_box">';
                                result+='       <img class="" src="'+data.result[i].image +'" alt="">';
                                result+='   </div>';
                                result+='   <div class="flex_one" style="min-width:0">';
                                result+='       <p class="group__title">'+data.result[i].company_name +'</p>';
                                result+='       <div class="flex">';
                                result+='           <div>';
                                result+='           <p class="group__desc">'+data.result[i].address +data.result[i].detail +'</p>';
                                result+='           <p>在招职位:'+data.result[i].position_count +'</p>';
                                result+='           </div>';
                                if (data.result[i].range) {
                                   result+='           <p style="max-width: 34%;min-width:25%; text-align:right">'+data.result[i].range +'</p>';
                                }
                                result+='       </div>';
                                result+='   </div>';
                                result+='</a>';
                            }
                        }else{
                           result+='<p style="color:#999; text-align:center; padding:40px 0">暂无数据</p>'
                       }
                   }
                   $('.city').append(result);
               },
               error:function(res){
                   console.log(res)
               }
           });
          }else{   
            $.toast("请输入关键搜索字", "text");
          }
          
       })
</script>