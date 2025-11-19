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
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?2221">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css"> 
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>  
     <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
    <script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?2100" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/index.js?13443" charset="utf-8"></script>    
    <script type="text/javascript">
        var ajax_company_list_url = "{pigcms{:U('Recruit/ajax_company_list')}";
        var company_details_url = "{pigcms{:U('Recruit/company_details')}";
        var position_detail_url = "{pigcms{:U('Recruit/position_detail')}";
        <if condition="$long_lat">
            var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";
        <else/>
            var user_long = '0',user_lat  = '0';
        </if>
		var currency_symbol = "{pigcms{$config.Currency_symbol}";

    </script>
    <style type="text/css">
        .hide{
            display: none;
        } 
        .icon-list.num10 .icon { 
            width: 25%; 
          }
        .img_box{
            width:80px;
            height:50px;
        }
        .yewuyuan{
            border:1px solid #ddd;
            padding:0 5px;
            margin:0 5px;
            border-radius: 2px;
        } 
        .chip-row{height:40px; padding-bottom:10px; margin-bottom:10px  }
        .chip,.chip-row{padding-left:12px;padding-right:12px;display:-webkit-box;-webkit-box-align:center;border-bottom:1px solid #eee;}
        .chip-row .toutiao{position:relative;font-weight:700;line-height:40px;padding-right:8px}
        .chip-row .toutiao-slider{color:#666;position:relative;overflow:hidden;-webkit-box-flex:1;height:40px;margin-left:8px;font-size:14px;line-height:16px}
        .chip-row ul.swiper-wrapper{position:relative;display:block}
        .chip-row ul.swiper-wrapper>li{float:left;position:relative;width:100%;height:40px;line-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .group__descHd{
            padding:5px 0; color:#999;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            line-height: 25px;    
            max-height: 25px;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
       } 
       .icon-list .icon > .newWin{
            padding-top: 12px;
            display: block;
       } 
    </style>
</head>
<body>
    <header <if condition="$config['many_city']">class="hasManyCity"</if> style="z-index:111;<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>">
            <if condition="$config['many_city']">
                <div id="cityBtn" class="link-url" data-url="{pigcms{:U('Changecity/index')}&Recruit=1" style='max-width:120px'><if condition="$now_select_area">{pigcms{:msubstr($now_select_area['area_name'],0,5)}<else/>{pigcms{:msubstr($now_select_city['area_name'],0,5)}</if>
                </div>
                <div id="searchBox" data-ff="2" style="<if condition="$config['house_open'] && $config['house_position'] eq 2">margin-left: 0px   <else/> margin-right:45px;margin-left: 0px  </if>">
                    <div class='openUrl newWin' data-url="{pigcms{:U('Recruit/search')}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>
                        <i class="icon-search"></i>
                        <span>请输入您想找的内容</span>
                        <if condition="$is_weixin_browser">  <a href="javascript:;" style="width:20%;float:right;" class="smallVioce"><i class="icon-hua"></i></a></if>
                    </div>
                </div>
           <else />
                <div id="cityBtn" class="link-url" data-name="{pigcms{$config['now_select_city']['area_name']}" data-url="{pigcms{:U('Changecity/index')}&Recruit=1" data-top_domain="{pigcms{$config.many_city_top_domain}" style='max-width:120px'>
                    <if condition="$now_select_area">{pigcms{:msubstr($now_select_area['area_name'],0,5)}<else/>{pigcms{:msubstr($now_select_city['area_name'],0,5)}</if>
                </div>
                <if condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name'] && $config['house_open'] && $config['house_position'] eq 2">
                   <elseif condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name']"/>
                   <div id="locaitonBtn" class="link-url mr_50" data-url="{pigcms{:U('Merchant/around')}"></div>
               </if>

                <div id="searchBox" data-ff="23"  style="<if condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name'] && $config['house_open'] && $config['house_position'] eq 2">margin: 10px 50px 0px 0px;<elseif condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name']"/>margin: 10px 77px 0px 0px;<else/> margin-right:49px;margin-left: 0px</if>">
                    <div class='openUrl newWin' data-url="{pigcms{:U('Recruit/search')}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>

                        <i class="icon-search"></i>
                        <span>请输入您想找的内容</span>
                        <if condition="$is_weixin_browser">
                            <div href="javascript:;" style="width: 20%; float: right;" class="smallVioce"><i class="icon-hua"></i></div>
                        </if>
                    </div>
                </div>
           </if>
    </header> 
 
   <div style="bottom: 50px; top: 50px;">
       <div>
            <section>
                <if condition="$wap_index_top_adver">
                   <div class="swiper-container swiper-container1 swiper-container-horizontal" style="height: 140px;">
                        <section id="banner_hei" class="banner">
                          <div class="swiper-container swiper-container1">
                            <div class="swiper-wrapper">
                              <volist name="wap_index_top_adver" id="vo">
                                <div class="swiper-slide">
                                  <div  class='openUrl newWin' data-url="{pigcms{$vo.url}" >
                                    <img src="{pigcms{:thumb_img($vo['pic'],640,238)}"/>
                                  </div>
                                </div>
                              </volist>
                            </div>
                            <div class="swiper-pagination swiper-pagination1"></div>
                          </div>
                        </section>
                       <div class="swiper-pagination swiper-pagination1"><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span></div>
                   </div>
                </if>
               <script>
                   var banner_height	=	$(window).width()/320;
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

            <if condition="$wap_recruit_index_slider">
                <section class="slider">
                    <div class="swiper-container swiper-container2" style="height:168px;">
                        <div class="swiper-wrapper">
                            <volist name="wap_recruit_index_slider" id="vo">
                                <div class="swiper-slide">
                                    <ul class="icon-list num{pigcms{$wap_slider_number}">
                                        <volist name="vo" id="voo">
                                            <li class="icon">
                                                <div class='openUrl newWin' data-url="{pigcms{$voo.url}">
                                                    <span class="icon-circle">
                                                        <img src="{pigcms{:thumb_img($voo['pic'],200)}"/>
                                                    </span>
                                                    <span class="icon-desc">{pigcms{$voo.name}</span>
                                                </div>
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

            <section  class="weui-cells mt0 after_none" style="height:40px;">
                <div class="chip-row">
                    <div class="toutiao" style='color: {pigcms{$config["mobile_head_color"]}; font-size:16px;'>职位动态</div>
                    <div class="toutiao-slider swiper-container" id="newsSlider">
                        <ul class="swiper-wrapper">
                            <volist name="scrollmsg" id="vo">
                            <li class="swiper-slide"><div class='openUrl newWin' data-url="{pigcms{:U('position_detail',array('position_id'=>$vo['business_id']))}">[{pigcms{$vo["time"]|date="m-d",###}]{pigcms{$vo["text"]}</div></li>
                            </volist>
                        </ul>
                    </div>
                </div>
            </section> 
                
            <section class="moreGroup" style="margin-top:20px">
               <div>
                   <p class="title">推荐企业</p>
                   <div>
                      <div class="flex align_center listGroup" >
                          <div class="flex_one h100  active" data-type="index" >首页推荐</div>
                          <div class="flex_one h100  cursor" data-type="city">{pigcms{:msubstr($now_select_city['area_name'],0,5)}</if>推荐</div>
                          <div class="flex_one h100 cursor" data-type="province">更多地区</div>
                      </div>
                      <div id="container1">
                           <div class="index">
                            </div>
                            <div class="city hide">
                            </div>
                            <div class="province hide">
                            </div>
                      </div>
                       
                   </div>
               </div>
           </section>
       </div>
   </div> 
   <div class="share_html">{pigcms{$shareScript}</div> 
   <script>
    if($('#newsSlider').length>0){
        var newsObj = $('#newsSlider');
        var newsSlider = new Swiper('#newsSlider', {
            autoplay: typeof newsObj.data('autoplay')!=='undefined' ? newsObj.data('autoplay') : 3000,
            spped:  typeof newsObj.data('spped')!=='undefined' ? newsObj.data('spped') : 1000,
            direction:'vertical',
            onlyExternal:true,
            loop: true
        });
    }  
   </script> 
<include file="footer"/>