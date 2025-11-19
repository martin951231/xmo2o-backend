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
    <title>{pigcms{$cat_name}</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <!-- <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script> -->
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript">
        var ajax_course_index_list_url = "{pigcms{:U('Recruit/ajax_course_index_list')}";
        var course_detail_url = '{pigcms{:U('Recruit/course_detail')}'
    </script>
    <style type="text/css">
        .flex{
            align-items:flex-start
        }

        .icon-list.num10 .icon { 
            width: 25%; 
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
        .flex_one p{
        overflow: hidden;/*超出部分隐藏*/
        white-space: nowrap;/*不换行*/
        text-overflow:ellipsis;/*超出部分文字以...显示*/
        }
    </style>
</head>
<body>

   <div id="container1" style="">
       <div id="scroller" >
           <section>
               <div class="swiper-container swiper-container1 swiper-container-horizontal" style="height: 140px;">
                    <if condition="$wap_index_top_adver">
                    <section id="banner_hei" class="banner">
                      <div class="swiper-container swiper-container1">
                        <div class="swiper-wrapper">
                          <volist name="wap_index_top_adver" id="vo">
                            <div class="swiper-slide">
                              <a href="{pigcms{$vo.url}">
                                <img src="{pigcms{:thumb_img($vo['pic'],640,238)}"/>
                              </a>
                            </div>
                          </volist>
                        </div>
                        <div class="swiper-pagination swiper-pagination1"></div>
                      </div>
                    </section>
                    </if>
                   <div class="swiper-pagination swiper-pagination1"><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span></div>
               </div>
               <script>
                   var banner_height    =   $(window).width()/320;
                   $(".swiper-container1").css('height',Math.ceil(banner_height*119)+'px');
                   var mySwiper1 = new Swiper('.swiper-container1', {
                       autoplay: 3000,
                       pagination : '.swiper-pagination1',
                       loop:true,
                       grabCursor: true,
                       paginationClickable: true,
                       autoplayDisableOnInteraction:false,
                       simulateTouch:false
                   })
               </script>
           </section>

        
           <section class="moreGroup">
               <div>
                   <!-- <p class="title">分类：{pigcms{$cat_name}</p> -->
                   <div>
                      <div class="chackGroup">
                      </div>
                   </div>
               </div>
           </section>
       </div>
   </div>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script>
<!--    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>-->
<!--    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/course_index.js?444" charset="utf-8"></script>-->
<script>


    var myScroll,now_page = 0,hasMorePage = true,isLoading = true;
    var isApp = motify.checkApp();
    var now_area = 0;

    $(function(){
        if(isApp){
            $('header').hide();
        }
        $('#backBtn').click(function(){
            redirect(backUrl,'openLeftWindow');
        });


        $('#search-form').submit(function(){
            var keyword = $.trim($('#keyword').val());
            $('#keyword').val(keyword);
            $('#bindVillageBox,#villageBox,#bindFamilyBox').hide();
            now_page = 0;
            hasMorePage = true;
            upIcon.removeClass('noMore loading').show();
            getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
            return false;
        });


    });


    // 页数
    var page = 0;
    // 每页展示10个
    var pageSize = 10;
    var dropLoad = $('#container1').dropload({
        scrollArea : window,
        loadDownFn : function(me){
            page++;
            // 拼接HTML
            var result = '';
            $.ajax({
                type: 'GET',
                url: ajax_course_index_list_url,
                data:{
                    'cat_id':{pigcms{$_GET['cat_id']|intval},
                    'page':page,
                    'pageSize':pageSize,keyword:$('#keyword').val()
                },
                dataType: 'json',
                success: function(data){
                    var arrLen = data.result.list.length;
                    if(arrLen > 0){
                        for(var i=0;i<arrLen;i++){
                            result+=' <a href="'+course_detail_url+'&course_id='+data.result.list[i].course_id+'" class="flex border-t">';
                            result+='  <div class="img_box">';
                            result+='       <img class="" src="{pigcms{:file_domain()}'+data.result.list[i].cover_img +'" alt="">';
                            result+=' </div>';
                            result+=' <div class="flex_one" style="min-width:0">';
                            result+='       <p class="group__title">'+data.result.list[i].title +'</p>';
                            result+='       <p class="group__title1">'+data.result.list[i].content +'</p>'; 
                            if(data.result.list[i].score>0){
                              result+='       <p class="group__title2">所需{pigcms{$config.score_name}：'+data.result.list[i].score +'</p>';  
                            }else{
                              result+='       <p class="group__title2">免费观看</p>';  
                            }
                            result+='   <div class="flex group_course">';
                            if (!data.result.list[i].fictitious_num) {
                                data.result.list[i].fictitious_num=0;
                            }
                            result+='          <span class="fl">'+(parseInt(data.result.list[i].view_times)+parseInt(data.result.list[i].fictitious_num))+'人看过</span>'
                            result+='          <span class="fr">'+data.result.list[i].add_time_format +'</span>'                
                            result+='   </div>';
                            result+=' </div>';
                            result+='</a>';
                        }
                    }else{
                        me.lock(); // 锁定
                        me.noData(); // 无数据
                    }
                    setTimeout(function(){
                        $('.chackGroup').append(result);
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

    function androidRefresh(ticket,device){
        if(ticket && device){
            $.post("./wap.php?c=Home&a=ajaxLogin", { ticket: ticket, 'Device-Id': device },function(){
                location.reload();
            });
        }
    }

    /*判断IOS方法调用*/
    var iosCommon = {
        getVersion: function () {
            var reg = /versioncode=(.*?),/;
            var arr = reg.exec(navigator.userAgent.toLowerCase());
            if(arr == null){
                return '85';
            }else{
                return arr[1];
            }
        },
        iosFunction:function(paramStr, paramObj){
            var pack_version = iosCommon.getVersion();
            var str = parseFloat(pack_version) >= 1000
            if ( str == true) {
                window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
            } else {
                $('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
            }

        }
    }
</script>
