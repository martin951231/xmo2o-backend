<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>确认订单</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name='apple-touch-fullscreen' content='yes'>
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/wap_pay_check.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/weixin_pay.css" rel="stylesheet"/>
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script src="{pigcms{$static_path}layer/layer.m.js"></script>
    <link href="{pigcms{$static_path}css/check.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/way.css" rel="stylesheet"/>
    <style>
        #pay_now[disabled]{
            background:#aaa!important;
        }
    </style>
</head>
<script type="text/javascript">
    var options = {
        "tradeNO":"{pigcms{$tradeNO}" 
    };
    AlipayJSBridge.call('tradePay', options ,function(result){
        if(result.resultCode == '9000'){
            setTimeout("window.location.href = '"+"{pigcms{$redirctUrl}"+"'",200);
        }else{
             history.go(-1);
        }
    });
</script>