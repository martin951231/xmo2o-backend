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
    <title>排名购买</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css?222">
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
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
         .swiper-slide {
            text-align: center; 
            background: #fff; 
            /* Center slide text vertically */
            display: -webkit-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            -webkit-justify-content: center;
            justify-content: center;
            -webkit-box-align: center;
            -ms-flex-align: center;
            -webkit-align-items: center;
            align-items: center;
        }

        .demos-content-padded {position: fixed;bottom: 0px;padding: 15px;left: 0px;right: 0px;background: #fff;}
        .weui-btn_primary {background-color: #ff4d00;}
        .weui-btn_primary:not(.weui-btn_disabled):visited {color: #FFFFFF !important;}
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
        
        .ul_pointList{ 
            padding:10px 0
        }
        .ul_pointList li{
            width:33%;
            float:left;
            text-align:center;
            border-right:1px solid #ddd;
        }
        .ul_pointList li .p1{
            color:#999
        }
        .pointBox{
            background:#fff;
            font-size:14px; 
        }
        .point_btBox{ 
        }
        .point_btBox h4{
            padding:0 10px;
        }
        .slideBlock{
            padding:20px 30px;  
            border:1px solid #e3e3e3; 
            position: relative;
            margin-bottom:30px;
        }
        .slideBlock .p1{
            color:#ff6f4e;
            font-size:12px; 
        }
        .slideBlock .p1 label{
            font-size:18px;
        }
        .slideBlock .p2{
            text-decoration: line-through;
            color:#999
        }
        .point_slide{
            border-bottom:1px solid #ddd;
            padding:10px 0;
            margin-bottom:10px;
        }
        .slideBlock .p4{
            font-size:15px; 
            font-weight:bold
        }
        .slideBlock .p3{
            color:#999
        } 
        .slideBlock.cur{
            border:1px solid #f76c4c;
            background:#fff9f7
        }
        .point_slideDIV{
            position: absolute; 
            bottom:23px;
            left:50%;
            background:#fff9f7;
            width:15px;
            height:15px;
            transform:rotate(45deg);  
            border-right:1px solid #f76c4c;
            border-bottom:1px solid #f76c4c;
            margin-left:-8px; 
        }        
        .mask {
            position: fixed;
            top: 0px;
            left: 0px;
            background: rgba(0,0,0,0.7);
            z-index: 888;
            width: 100%;
            height: 100%;
            display: none;
        }
        .cityDiv{
            background: #fff;
            position: fixed;
            width: 100%;
            /*height: 100%;*/
            display: none;
            z-index: 999;
            top: 80px;
            left: 0px;

        }
        .city-content{
            padding: 5px 23px;    
            margin-left: 64px;

        }
        .city-content div{
            color: #999;
            font-size: 16px;

        }
        .select_city,.select_area{
            font-size: 16px;
            /*color: #7BA2FD;
            background: #F1F6FF;*/
            width: 100px;    
            padding: 5px 5px;
        }
        .select_area{
            margin-left: 40px;
        }
        .swiper-slide{
            width: 33%!important;
            margin: 0 10px;
        }
        .layui-m-layercont{
            max-height:360px;
            padding:20px; 
            overflow-y: scroll;
            text-align:left;
            
        }
        .slideBlock .p3{
            color: #ff6f4e;
        }
        .slideBlock .p4{
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
 <div class="pointBox">
 <div class="weui-form-preview__bd">
        <div class="weui-form-preview__item">
            <label class="weui-form-preview__label" style="color:#000; font-size:16px;">选择排名</label>
            <span class="weui-form-preview__value link newWin" data-url="{pigcms{:U('ranking_order_list')}">购买记录</span>
        </div> 
    </div>

      <!-- Swiper -->
    <div class="swiper-container"> 
        <!-- <if condition="$now_ranking"> -->
            <div class="weui-form-preview__item" style="margin-left: 10px;    margin-bottom: 10px;">
                <label class="weui-form-label" style="margin-right:40px;">区域</label>
                <label class="weui-form-label select_city" data-values="{pigcms{$now_company.city}" value="{pigcms{$city}">{pigcms{$city}</label>
                <img src="">
                <label class="weui-form-label select_area" data-values="{pigcms{$now_company.area}" value="{pigcms{$area}">{pigcms{$area}</label>
            </div> 
            <div class="swiper-wrapper ranking">
                 <volist name="now_ranking" id="vo">
                    <div class="swiper-slide change_package">
                        <div class="slideBlock <if condition="$key eq '0'">cur</if>" data-id="{pigcms{$vo.package_id}">
                            <p class="p4">{pigcms{$vo.name}</p>
                            <div class="point_slide">
                                <p class="p1">{pigcms{$config["Currency_symbol"]}<label>{pigcms{$vo.price}</label></p>
                                <p class="p2">{pigcms{$config["Currency_symbol"]}{pigcms{$vo.old_price}</p> 
                            </div> 
                            <p class="p3 orange">有效期{pigcms{$vo.active_time}天</p> 
                            <if condition="$vo['is_buy'] eq 1">
                            <p class="p4">已被别人购买</p> 
                            </if>
                            <if condition="$vo['is_owner'] eq 1">
                            <p class="p4">已购买</p> 
                            </if>
                        </div> 
                        <div class="point_slideDIV" <if condition="$key neq '0'">style="display: none"</if>></div>
                    </div> 
                </volist>
            </div>
            <!-- Add Pagination -->
            <div class="swiper-pagination"></div>

        <!-- </if> -->
    </div>
    <!-- 城市 -->
    <div class="cityDiv" >
        <div class="city-content">
            <volist name="city_arr" id="vo">
                <div data-area_id="{pigcms{$vo.area_name}">{pigcms{$vo.area_name}</div>
            </volist>
        </div>
    </div>
    <div class>
        <label for="weuiAgree" class="weui-agree fl" style="padding-right:0">
            <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox" data-rules='{"required": true}' name="xieyi" data-messages='{"required": "请同意服务协议"}'> 
            <span class="weui-agree__text">
                阅读并同意
            </span>
        </label>  
        <a href="javascript:void(0);" class="fl" id="agree" style="font-size:12px; line-height:32px; color:#ff4d00">《用户协议》</a>
        <div class="clear"></div>
    </div>
   
    <div class="demos-content-padded">
         <a href="javascript:;" class="weui-btn weui-btn_primary submit">确定</a>
    </div>
    <div class="mask"></div>
    <div id="agreeContent" style="display: none;">
        <div class="agreeContent"> 
            <p> {pigcms{:replace_file_domain_content(htmlspecialchars_decode($config["recruit_agreement"],ENT_QUOTES))}</p> 
        </div>
     </div>
 </div> 
</body>
<script>
    var ajax_get_ranking_area_url="{pigcms{:U('ajax_get_ranking_area')}";
    var ranking_go_pay_url="{pigcms{:U('ranking_go_pay')}";
    var city_arr = JSON.parse('{pigcms{$city_arr}');
    var area_arr = JSON.parse('{pigcms{$area_arr}');
    var ranking_list = JSON.parse('{pigcms{$ranking_list}');


    function createHtml(now_area){
        var now_ranking_list = ranking_list[now_area];
        console.log(now_ranking_list)
        console.log(now_area)
        var result = '';
        for(var i=0;i<now_ranking_list.length;i++){
            result+=' <div class="swiper-slide change_package">';
            if(i==0){
                result+='   <div class="slideBlock cur" data-id="{pigcms{$vo.package_id}">';
            }else{
                result+='   <div class="slideBlock" data-id="{pigcms{$vo.package_id}">';
            }
            result+='       <p class="p4">'+now_ranking_list[i].name+'</p>';
            result+='       <div class="point_slide">';
            result+='           <p class="p1">{pigcms{$config["Currency_symbol"]}<label>'+now_ranking_list[i].price+'</label></p>';
            result+='           <p class="p2">{pigcms{$config["Currency_symbol"]}'+now_ranking_list[i].old_price+'</p> ';
            result+='       </div> ';
            result+='       <p class="p3 orange">有效期'+now_ranking_list[i].active_time+'天</p> ';
            if (now_ranking_list[i].is_owner==1) {
                result+='   <p class="p4">已购买</p> ';
            }
            if (now_ranking_list[i].is_buy==1) {
                result+='   <p class="p4">已被别人购买</p> ';
            }
            result+='   </div> ';
            if(i==0){
                result+='   <div class="point_slideDIV"></div>';
            }else{
                result+='   <div class="point_slideDIV" style="display:none;"></div>';
            }
            result+='</div> ';
        }
        $('.ranking').html(result)
    }

    $('.submit').on('click', function(){
        if (!$('#weuiAgree').prop('checked')) {
            layer.open({
                title:['提示：','background-color:#ff4d00;color:#fff;'],
                content:'请同意服务条款',
                btn:['好的'],
                shadeClose:false,
                yes:function(){
                    layer.closeAll();
                    return false;
                }
            });
            return false;
        }
        var package_id =  $('.cur').data('id');
        var area_id = $('.select_area').data('values');
        $.ajax({
            type: 'POST',
            url: ranking_go_pay_url,
            data:{
              'package_id':package_id,
              'area_id':area_id
            },
            dataType: 'json',
            success: function(data){
                if(data.errorCode > 0){ 
                    layer.open({
                        title:['提示：','background-color:#ff4d00;color:#fff;'],
                        content:data.errorMsg,
                        btn:['好的'],
                        shadeClose:false,
                        yes:function(){
                            layer.closeAll();
                            if (data.errorCode=='73000008') {
                                window.location.href="{pigcms{:U('Login/index')}";
                            }
                            return false;
                        }
                    });
                }else{ 
                    if(data.result){
                        window.location.href=data.result;
                    }
                }
            }
        })
    });
    var View={
        init:function(){
              this.initClick();
              this.refresh();
        },
        settings:{
             img:[],
             start_time:null,
             end_time:null,
             city_arr:0,
             city_arr_txt:'',
        }, 
        refresh:function(){
            var self=this;
            $(".select_city").select({
               title: "请选择城市",
               items: city_arr,
               onChange: function(d) {
                    console.log(d)
                    $(".select_city").html(d.titles);
                    // 获取区域
                    var city_id = $('.select_city').data('values');
                    $.ajax({
                        type: 'POST',
                        url: ajax_get_ranking_area_url,
                        data:{
                          'city_id':city_id
                        },
                        dataType: 'json',
                        success: function(data){
                            if(data.result.area_list.length>0){
                                $(".select_area").html(data.result.area_list[0]['title']);
                                $('.select_area').data('values',data.result.area_list[0]['value']);
                                area_arr = data.result.area_list;
                                $(".select_area").select("update", { items: area_arr });
                                var now_area = area_arr[0].value;
                                ranking_list = data.result.ranking_list;
                                createHtml(now_area);
                            }
                        }
                    })

               },
               onClose: function() {
                   $(".mask").hide()
               },
               onOpen: function() {
                   $(".mask").show()
               },
           });

            $(".select_area").select({
               title: "请选择区域",
               items: area_arr,
               onChange: function(d) {
                    console.log(d)
                    $(".select_area").html(d.titles);
                    if (d.values) {
                        createHtml(d.values); 
                    }
               },
               onClose: function() {
                   $(".mask").hide()
               },
               onOpen: function() {
                   $(".mask").show()
               },
           });
        },
        initClick:function(){
            var self=this;
            $('.mask').on('click', function(){
               $(".mask").hide()
               $(".select_area").select("close")
               $(".select_city").select("close")
            });

            $('#agree').on('click', function(){
                layer.open({  
                    title: [
                    '用户协议',
                    'background-color: #ff4d00; color:#fff;'
                    ],  
                    btn: ['知道了']
                    ,yes: function(index){
                        location.reload();
                        layer.close(index);
                    },
                    content: $('.agreeContent').html()
                });
            }); 

            $('.change_package').on('click', function(){
                var couse_times = $(this).data('couse_times');
                var resume_times = $(this).data('resume_times');
                var invitation_times = $(this).data('invitation_times');
                $('.couse_times').html(couse_times+'次');
                $('.resume_times').html(resume_times+'次');
                $('.invitation_times').html(invitation_times+'次');
                $(this).find('.point_slideDIV').show();
                $(this).siblings('div').find('.point_slideDIV').hide();
                $(this).find('.slideBlock').addClass('cur');
                $(this).siblings('div').find('.slideBlock').removeClass('cur');
            });
         }
     }
     window.View=View;
     View.init();
     window.addEventListener("pageshow",function(){
         if(/iPhone|iPod/i.test(navigator.userAgent)) {
             $("input[name=file]").removeAttr("capture")
         } else {

         }
     })
</script>
<script>
    var swiper = new Swiper('.swiper-container', {
      // slidesPerView: 2.5,
      // spaceBetween: 0,
      // init: false,
      // pagination: {
      //   // el: '.swiper-pagination',
      //   // clickable: true,
      // } 
    });
  </script>
</html>