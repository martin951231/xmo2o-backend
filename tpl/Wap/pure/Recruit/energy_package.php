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
    <title>VIP年度会员</title>
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
        .layui-m-layercont{
            max-height:360px;
            padding:20px; 
            overflow-y: scroll;
            text-align:left;
            
        }
    </style>
</head>
<body>
 <div class="pointBox">
 <div class="weui-form-preview__bd">
        <div class="weui-form-preview__item">
            <label class="weui-form-preview__label" style="color:#000; font-size:16px;">选择年度VIP套餐</label>
            <span class="weui-form-preview__value link newWin" data-url="{pigcms{:U('energy_package_order_list')}">购买记录</span>
        </div> 
    </div>

      <!-- Swiper -->
    <div class="swiper-container">
        <div class="swiper-wrapper">
             <volist name="list.list" id="vo">
                <div class="swiper-slide change_package" data-couse_times="{pigcms{$vo.couse_times}" data-resume_times="{pigcms{$vo.resume_times}" data-invitation_times="{pigcms{$vo.invitation_times}">
                    <div class="slideBlock <if condition="$key eq '0'">cur</if>" data-id="{pigcms{$vo.package_id}">
                        <p class="p4">{pigcms{$vo.name}</p>
                        <div class="point_slide">
                            <p class="p1">{pigcms{$config["Currency_symbol"]}<label>{pigcms{$vo.price}</label></p>
                            <p class="p2">{pigcms{$config["Currency_symbol"]}{pigcms{$vo.old_price}</p> 
                        </div> 
                        <p class="p3">有效期{pigcms{$vo.active_time}天</p> 
                    </div> 
                    <div class="point_slideDIV" <if condition="$key neq '0'">style="display: none"</if>></div>
                </div> 
            </volist>
        </div>
        <!-- Add Pagination -->
        <div class="swiper-pagination"></div>
    </div>
    <if condition="$list['list']">
        <div class="point_btBox">
            <h4>获得以下能量</h4> 
            <ul class="ul_pointList">
                <li>
                    <p class="p1">免费查看视频</p>
                    <p class="couse_times">{pigcms{$list['list'][0]['couse_times']}次</p>
                </li>
                <li>
                    <p class="p1">免费查看简历</p>
                    <p class="resume_times">{pigcms{$list['list'][0]['resume_times']}次</p>
                </li>
                <li>
                    <p class="p1">免费邀请简历</p>
                    <p class="invitation_times">{pigcms{$list['list'][0]['invitation_times']}次</p>
                </li>
                <div class="clear"></div>
            </ul>
        </div>
    </if>

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

     <div id="agreeContent" style="display: none;">
        <div class="agreeContent">  
            <p> {pigcms{:replace_file_domain_content(htmlspecialchars_decode($config["recruit_agreement"],ENT_QUOTES))}</p> 
        </div>
     </div>
 </div> 
</body>
<script>
    var energy_go_pay_url="{pigcms{:U('energy_go_pay')}";

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
        $.ajax({
            type: 'POST',
            url: energy_go_pay_url,
            data:{
              'package_id':package_id
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

</script>
<script>
    var swiper = new Swiper('.swiper-container', {
      slidesPerView: 2.5,
      spaceBetween: 0,
      // init: false,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      } 
    });
  </script>
</html>