<!DOCTYPE html>
<html class="" xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>信息发布</title>
		<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}classify/fabuinput.css" media="all" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classify/postui7.css" media="all" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classify/common.css" />
		<script src="{pigcms{:C('JQUERY_FILE')}"></script> 
        <style>
            .fl{
                float: left;
            }
            .fr{
                float: right;
            }
            .clear{
                clear: both;
            }
            .tuijian_label{
                color:#666;
                margin-left: 40px;
                font-size: 14px; 
            }
            .tuijian_label label{
                background: #fd513d;
                color: #fff;
                margin-right: 10px;
                padding: 2px 4px;
                border-radius: 4px;
            }
            .lab_org{
                color:#fd513d
            }
            .center{
                text-align: center;
            }
          
            .wrap_box{
                width: 1000px;
                margin: 50px auto;
            }
            .wrap_box .wrap_til{ 
                font-size: 20px;
                color:#000; 
            }
            .wrap_box .wrap_til1{
                 font-weight: bolder;
            }
            .wrap_box .wrap_til1.weizhi{
                padding-left: 30px;
                background: url(../../../tpl/Static/default/classify/img/weizhi.png)  left center no-repeat;
                background-size: 24px auto;
            }
            .wrap_box .wrap_til1.yinhangka{
                padding-left: 30px;
                background: url(../../../tpl/Static/default/classify/img/yinhangka.png)  left center no-repeat;
                background-size: 30px auto;
            }
            .ul_yinhang{
                padding: 20px 0;
            }
            .ul_yinhang li{
                width: 250px;
                float: left;
                height: 50px;
                line-height: 40px;
                height: 40px;
                margin: 10px auto;
            }
            .ul_yinhang li.yinhang1{
                background: url(../../../tpl/Static/default/classify/img/yinh1.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang2{
                background: url(../../../tpl/Static/default/classify/img/yinh2.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang3{
                background: url(../../../tpl/Static/default/classify/img/yinh3.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang4{
                background: url(../../../tpl/Static/default/classify/img/yinh4.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang5{
                background: url(../../../tpl/Static/default/classify/img/yinh5.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang6{
                background: url(../../../tpl/Static/default/classify/img/yinh6.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang7{
                background: url(../../../tpl/Static/default/classify/img/yinh7.png) left 30px center no-repeat;
                background-size:auto 40px;
            }
            .ul_yinhang li.yinhang8{
                background: url(../../../tpl/Static/default/classify/img/yinh8.png) left 30px center no-repeat;
                background-size:auto 40px;
            } 
            .go_pay{
                background: #999;
                color:#fff;
                padding: 4px 10px;
                border:none;
                font-size: 16px;
                cursor: pointer;
                border-radius: 4px;
            }
            .go_pay:hover{
               background: #555; 
            }
            
            .fabubtn1{
                background: #dd6925;
                padding: 5px 10px;
                border:none;
                color: #fff; 
                font-size: 30px;
                cursor: pointer;
                margin: 30px auto;
                display: block;
                border-radius: 4px;
                margin: 60px auto;
                text-align: center;
                text-decoration: none;
                width: 160px;
            }
            .fabubtn1:hover{ 
               background: #b94a09;
            }
            .notice{
                padding-left: 70px;
                position: relative;
            }
            .notice::before{
               position: absolute;
               left: 0;
               top:20px;
               content: '注意';
               color: red;
               font-size: 30px;
               font-weight: bolder;
            }
            .alap{
                background: rgba(0,0,0,0.6);
                position: fixed;
                left: 0;
                top:0;
                z-index: 999;
                width: 100%;
                height: 100%;
                display: none;
            }
            .alapCon{
                width: 400px;
                position: absolute;
                left: 50%;
                top:50%;
                margin-top: -150px;
                margin-left: -200px;
                z-index: 999;
                background: #fff;
                height: 300px;
                display: none;
                border-radius: 6px;
            }
            .alapCon li{
                width: 50%;
                float: left;
                text-align: center;
            }
            .alapCon li p{
                font-size: 30px;
                color:#dd6925;
                font-weight: bolder;
                padding-top: 80px;
            }
            .alapCon li a{
                padding: 10px 30px;
                background: #dd6925;
                color: #fff;
                border:none; 
                border-radius: 6px;
                cursor: pointer;
                display: block;
                width: 70px;
                margin: 40px auto;
                text-decoration: none;
            }
            .alapCon li a:hover{
                background: #b94a09;
            }
        </style>
 </head>
 <body> 
    <div class="wrap_box">
        <div>
            <div class="wrap_til">
                <span class="fl  wrap_til1 weizhi">微信支付</span>
                <span class="fl tuijian_label"><label>推荐</label>10亿微信用户的首选</span>
                <span class="fr">支付金额<label class="lab_org">300</label>元</span>
                <div class="clear"></div>
            </div>
            <div class="center" style="padding: 100px 0;">
                <div class="fl" style="padding-left: 300px; margin-right: 10px;" > <img src="../../../tpl/Static/default/classify/img/img_fabu2.jpg" style="width: 240px;" />
                <p>打开微信扫一扫付款</p></div> 
                <img src="../../../tpl/Static/default/classify/img/img_fabu1.png" style="width: 240px;" class="fl"  />
                <div class="clear"></div>
            </div>
        </div>
        
        <div>
            <div class="wrap_til">
                <span class="fl wrap_til1 yinhangka">银行卡支付</span> 
                <div class="clear"></div>
            </div>
            <div style="padding: 20px 0;">
               支持存储卡和信用卡，需要开通网银 
               <ul class="ul_yinhang">
                   <li class="yinhang1"><input type="radio" name="yinghang" checked="checked" /></li>
                   <li class="yinhang2"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang3"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang4"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang5"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang6"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang7"><input type="radio" name="yinghang" /></li>
                   <li class="yinhang8"><input type="radio" name="yinghang" /></li>
                   
                   <div class="clear"></div>
               </ul>
               <p class="wrap_til1">银行卡卡号： </p> 
               <div style="padding: 10px 0;"> 
                 <input type="text"  />   价格：300元
               </div>
               <div>
                   <button class="go_pay">去支付</button>
               </div>
               <div class="notice">
                   <p style="padding-top: 20px;">如已勾选付费置顶选项，则需扫描上方二维码或银行卡进行支付</p>
                   <p>如未勾选付费置顶选项，则忽略扫描支付，点击发布已完成</p>
               </div> 
               
               <a href="{pigcms{$config.site_url}" class="fabubtn1">发布已完成</a> 
            </div>
            
        </div>
        
    </div>  
    
    <div class="alap"> 
    </div>
    <div class="alapCon">
        <ul>
            <li>
                <p>尚未登录</p>
                <a href="{pigcms{$config.site_url}">确定</a>
            </li>
            <li>
                <p>请先登录</p>
                <a href="{pigcms{$config.site_url}">取消</a>
            </li>
            <div class="clear"></div>
        </ul>
    </div>
    
    <script>
        $('.go_pay').click(function(){
            $('.alapCon').show();
            $('.alap').show();
        })
    </script>
    
 </body>
</html>