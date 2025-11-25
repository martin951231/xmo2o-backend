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
    <title>商学院首页</title> 
      
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?212">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script> 
    <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript">
        var ajax_course_index_list_url = "{pigcms{:U('Recruit/ajax_course_boutique_list')}";
        var ajax_course_free_list = "{pigcms{:U('Recruit/ajax_course_free_list')}";
        var course_detail_url = '{pigcms{:U('Recruit/course_detail')}';
		var currency_symbol = "{pigcms{$config.Currency_symbol}";

    </script>
    <style type="text/css">
        .weui-cell {
            padding: 10px 15px;
            position: relative;
            display: -webkit-box;
            display: -webkit-flex;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            align-items: center;
        }
        .pd-l0 {
            padding-left: 15px;
            font-size: 15px;

        }
        .weui-cell__bd {
            -webkit-box-flex: 1;
            -webkit-flex: 1;
            flex: 1;
        }
        .pd-r10 {
            padding-right: 10px;
        }
        .weui-cell__ft {
            text-align: right;
            /*color: #999999;*/
        }
        .font14 {
            font-size: 14px;
        }

        .linelft{
            position: relative;
            padding-left: 10px;
        }

        .linelft::after{
            content: '';
            width: 2px;
            height:20px;
            position: absolute;
            left: 0;
            top:0;
            background-color: #06c1ae;
        }
         
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

       <section>
           <div class="swiper-container swiper-container1 swiper-container-horizontal" style="height: 140px;">
                <if condition="$wap_index_top_adver">
                <section id="banner_hei" class="banner">
                  <div class="swiper-container swiper-container1">
                    <div class="swiper-wrapper">
                      <volist name="wap_index_top_adver" id="vo">
                        <div class="swiper-slide">
                          <a  class='openUrl newWin' data-url="{pigcms{$vo.url}">
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

          <if condition="$wap_index_slider">
              <section class="slider">
                  <div class="swiper-container swiper-container2" style="height:168px;">
                      <div class="swiper-wrapper">
                          <volist name="wap_index_slider" id="vo">
                              <div class="swiper-slide">
                                  <ul class="icon-list num{pigcms{$wap_slider_number}">
                                      <volist name="vo" id="voo">
                                          <li class="icon">
                                              <a  class='openUrl newWin' data-url="{pigcms{$voo.url}">
                                                  <span class="icon-circle">
                                                      <img src="{pigcms{:thumb_img($voo['pic'],200)}"/>
                                                  </span>
                                                  <span class="icon-desc">{pigcms{$voo.name}</span>
                                              </a>
                                          </li>
                                      </volist>
                                  </ul>
                              </div>
                          </volist>
                      </div>
                      <div class="swiper-pagination swiper-pagination2"></div>
                      <script>
                         var mySwiper2 = new Swiper('.swiper-container2', {
                             loop:true,
                             grabCursor: true,
                             paginationClickable: true,
                             simulateTouch:false
                         })
                     </script>
                  </div>
              </section>
          </if> 
          <!-- 重磅推荐 -->
          <if condition="$course_index_adver[0]">
            <div class="Weight_Recommendation">
                 <div class="weui-cell pd-l0 mg-t10">
                    <div class="weui-cell__bd pd-r10 linelft">
                        <p>重磅推荐</p>
                    </div> 
                </div>
                <div class="Weight_Recommendation_img"> 
                     <a  class='openUrl newWin' data-url="{pigcms{$course_index_adver[0]['url']}"> 
                          <img src="{pigcms{$course_index_adver[0]['pic']}"/> 
                    </a>
                </div>
            </div>
            <style>
                .Weight_Recommendation{  
                }
                .Weight_Recommendation_img img{
                    border-radius: 20px; 
                    padding: 10px 15px;
                    width: 100%;
                    overflow: hidden;
                    box-sizing: border-box;
                }
            </style>
            <script> 
                $('.Weight_Recommendation_img').css('max-height',$(window).width()*0.8); 
            </script>
          </if>
          <!-- 重磅推荐 -->
           <section class="moreGroup">
               <div id="container1">
                   <div class="weui-cell pd-l0 mg-t10">
                       <div class="weui-cell__bd pd-r10 linelft">
                           <p>免费专区</p>
                       </div>

                       <div class="weui-cell__ft border-l rel" style="font-size: 14px;">
                           <span style="border-radius: 2px; padding: 2px 5px; font-size: 12px;"  class='openUrl newWin' data-url="{pigcms{:U('course_free_list')}" >更多></span>
                       </div>


                   </div>
<!--                   <p class="title">免费专区</p>-->

                   <div>
                      <div class="chackGroupList">
                      </div>
                   </div>
               </div>
           </section>
           <section class="moreGroup">
               <div>
                   <div class="weui-cell pd-l0 mg-t10">
                       <div class="weui-cell__bd pd-r10 linelft">
                           <p>热门课程</p>
                       </div>

                       <div class="weui-cell__ft border-l rel" style="font-size: 14px;">
                           <span style="border-radius: 2px; padding: 2px 5px; font-size: 12px;" class='openUrl newWin' data-url="{pigcms{:U('course_boutique_list')}" >更多></span>
                       </div>


                   </div>
<!--                   <p class="title">精品课程</p>-->
                   <div>
                       <div class="chackGroup">
                       </div>
                   </div>
               </div>
           </section>
       </div>
   </div>
<!--    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script>-->
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
<!--    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/course_index.js?444" charset="utf-8"></script>-->
    <script type="text/javascript"> 
        $(function(){
            console.log("========")
            $.ajax({
                type: 'GET',
                url: ajax_course_free_list,
                data:{
                    'page':0,
                    'pageSize':3,keyword:$('#keyword').val()
                },
                dataType: 'json',
                success: function(data){
                    // 拼接HTML
                    var result = '';
                    var arrLen = data.result.list.length;
                    if(arrLen > 0){
                        for(var i=0;i<arrLen;i++){
                            result+=' <a class="openUrl newWin flex border-t" data-url="'+course_detail_url+'&course_id='+data.result.list[i].course_id+'">';
                            result+='	<div class="img_box">';
                            result+='       <img class="" src="{pigcms{:file_domain()}'+data.result.list[i].cover_img +'" alt="">';
                            result+='	</div>';
                            result+='	<div class="flex_one" style="min-width:0">';
                            result+='       <p class="group__title">'+data.result.list[i].title +'</p>';
                            result+='       <p class="group__title1">'+data.result.list[i].content +'</p>'; 
                            result+='       <p class="group__title2">免费观看</p>';  
                            result+='		<div class="flex group_course">';
                            if (!data.result.list[i].fictitious_num) {
                                data.result.list[i].fictitious_num=0;
                            }
                            result+='          <span class="fl">'+(parseFloat(data.result.list[i].view_times)+parseFloat(data.result.list[i].fictitious_num))+'人看过</span>'
                            result+='          <span class="fr">'+data.result.list[i].add_time_format +'</span>'                
                            result+='		</div>';
                            result+='	</div>';
                            result+='</a>';
                        }
                    }
                    $('.chackGroupList').append(result);
                },

            });



            $.ajax({
                type: 'GET',
                url: ajax_course_index_list_url,
                data:{
                    'page':0,
                    'pageSize':3,keyword:$('#keyword').val()
                },
                dataType: 'json',
                success: function(data){
                    // 拼接HTML
                    var result = '';
                    var arrLen = data.result.list.length;
                    if(arrLen > 0){
                        for(var i=0;i<arrLen;i++){
                            result+=' <a class="openUrl newWin flex border-t" data-url="'+course_detail_url+'&course_id='+data.result.list[i].course_id+'" >';
                            result+='	<div class="img_box">';
                            result+='       <img class="" src="{pigcms{:file_domain()}'+data.result.list[i].cover_img +'" alt="">';
                            result+='	</div>';
                            result+='	<div class="flex_one" style="min-width:0">';
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
                            result+='          <span class="fl">'+(parseFloat(data.result.list[i].view_times)+parseFloat(data.result.list[i].fictitious_num))+'人看过</span>'
                            result+='          <span class="fr">'+data.result.list[i].add_time_format +'</span>'                
                            result+='   </div>';
                            result+='	</div>';
                            result+='</a>';
                        }
                    }

                    $('.chackGroup').append(result);  
                },

            });
        });
    </script>  
    <div class="share_html">{pigcms{$shareScript}</div> 
<include file="footer"/>