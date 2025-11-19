<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>我是vip推广商</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?215"/>
    <link href="{pigcms{$static_path}scenic/css/css_whir.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_public}font-awesome/css/font-awesome.min.css">
    <script src="{pigcms{$static_path}scenic/js/jquery-1.8.3.min.js"></script>
    <style>
        .my-account {
            color: #333;
            position: relative;
            display: block;
            width:100%;
            position: relative;
            height:13rem;
        }
        .account-bg{
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            z-index: -1;
        }
        .my-account>img {
            height: 100%;
            position: absolute;
            right: 0;
            top:0;
            z-index: 0;
        }
        .titleImg{
            width:30px;
            height:25px;
            margin-right:10px;
            margin-top:10px;
            float:left;
        }
        .wh25{
            width:25px;
            height:25px;
        }
        .title{
            padding:0 10px;
            background-color:#fff;
            margin-top:10px;
            margin-bottom:10px;
        }
        .title_img{
            float:right;
            margin-top:-19px;
            width:10px;
        }
        .pb10{
            padding-bottom:10px;
        }
        .reason{
            font-size:16px;
            display: inline-block;
            font: normal normal normal 14px/1 FontAwesome;
            font-size: inherit;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }
        .reason:before{
            content: "\f29c";
        }
    </style>
</head>
<body>
<div class="my-account">
    <div class="account-bg" style="background-color:#ff584c">

    </div>
    <div style="text-align:center;padding-top:40px;color:#fff;">
        <div style="font-size:24px;"><span style="font-size:16px;"></span>{pigcms{$spread.spread_money|getFormatNumber}</div>
        <div style="text-align:center;">我的佣金<span class="frozen_money" style="font-size:12px;margin-left:5px;"></div>
        <div style="text-align:center;">（我是vip推广商）<span class="frozen_money" style="font-size:12px;margin-left:5px;"></div>
    </div>
    <div style="text-align: center;margin-top: 2rem;">
        <span id="invite_spread_update" style="display:inline-block;padding: 3px 0.5rem;width:7rem;color: #ffffff;border: 1px solid #FFFFFF;border-radius: 1px;margin-right: 1rem">邀请推广商升级</span>
        <span id="go_withdraw" style="display:inline-block;padding: 3px 0.5rem;width:7rem;background: #ffffff;border: 1px solid #FFFFFF;border-radius: 1px">去提现</span>
    </div>
</div>



<dl id="my_spread" class="title" >
    <div class="pb10">
        <img class="titleImg" src="{pigcms{$static_path}images/new_my/grade.png" />
        <div style="padding-top:12px;width:80%;">我邀请的推广商
            <div style="position:absolute;padding:0px 8px;right:1.8em;top:228px;border-radius:10px;">共<span style="font-size:17px;">{pigcms{$inviteNum|floatval}</span>个</div>
        </div>
        <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"></img>
    </div>
    <div style="clear:both;"></div>
</dl>

<if condition="C('config.open_city_merchant_spread') eq 1">
<if condition="$spread['is_city_spread'] eq 1">
    <dl id="go_city_spread" class="title">
        <div class="pb10">
            <img class="titleImg" src="{pigcms{$static_path}images/new_my/integral.png" />
            <div style="padding-top:12px;width:95%;">我是城市推广商</div>
            <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"></img>
        </div>
        <div style="clear:both;"></div>
    </dl>
<elseif condition="$spread['is_apply_city_spread'] eq 1"/>
    <dl onclick="my_alert('审核中',1500)" class="title">
        <div class="pb10">
            <img class="titleImg" src="{pigcms{$static_path}images/new_my/integral.png" />
            <div style="padding-top:12px;width:95%;">开通城市推广商等级
                <div style="position:absolute;padding:0px 8px;right:1.8em;top:283px;border-radius:10px;color: #FF0000">审核中</div>
            </div>
        </div>
        <div style="clear:both;"></div>
    </dl>
<else/>
    <dl id="open_city_spread" class="title">
        <div class="pb10">
            <img class="titleImg" src="{pigcms{$static_path}images/new_my/integral.png" />
            <div style="padding-top:12px;width:95%;">开通城市推广商等级</div>
            <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"></img>
        </div>
        <div style="clear:both;"></div>
    </dl>
</if>
</if>




<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/common_wap.js"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<script>
    $('#open_city_spread').on('click',function(){
        location.href =	"{pigcms{:U('My/upgradeMerSpread',array('type'=>2))}";
    });

    $('#go_to_city_spread').on('click',function(){
        location.href =	"{pigcms{:U('My/citySpread')}";
    });

    $('#go_withdraw').on('click',function(){
        location.href =	"{pigcms{:U('Fenrun/withdraw')}";
    });

    $('#invite_spread_update').on('click',function(){
        location.href =	"{pigcms{:U('My/inviteSpreadUpdate')}";
    });

    $('#my_spread').on('click',function(){
        location.href =	"{pigcms{:U('My/spreadLists')}"+'&type=1';
    });

    $('#go_city_spread').on('click',function(){
        location.href =	"{pigcms{:U('My/citySpread')}";
    });

</script>
<script type="text/javascript">
    function my_alert(text, delay=1500) {
        var informDiv = $('<div style="padding:10px 30px; background:rgba(0,0,0,0.6); color:#fff; font-size:13px; line-height:20px; position:fixed; left:50%; top:0%; z-index:9999; border-radius:10px; opacity:0; -webkit-transform:translateX(-50%); -moz-transform:translateX(-50%); -ms-transform:translateX(-50%); transform:translateX(-50%);">'+text+'</div>');
        informDiv.appendTo($('body')).css({"top": "45%", "opacity": "1"})
        setTimeout(function () {
            informDiv.remove();
        }, delay)
    }
</script>
{pigcms{$hideSript}

</body>
</html>
