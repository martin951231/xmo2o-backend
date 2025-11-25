<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
	<title>支付跳转</title>
    <style>
        .goods_name {
            padding: 15px;
            text-align: center;
            font-size: 3rem;
        }
        .pay_img {
            height: 20rem;
            width: auto;
            padding: 5px 30px;
            text-align: center;
            font-size: 16px;
        }
        .pay_money {
            padding: 15px 30px;
            text-align: left;
            font-size: 3rem;
        }
        .pay_money .detail{
            color: #F19257;
            font-size: 3.4rem;
        }

        .plus_class a {
            margin-left: 5%;
            width: 100%;
            height: 40px;
            color: #fff;
            font-size: 3rem;
            line-height: 40px;
            text-align: center;
        }
        .true_btn {
            font-size: 3rem;
            background: #ffa200;
            border-radius: 5px;
            margin: 3rem 5%;
            width: 90%;
            padding: 1.5rem 0;
            color: #fff;
        }
        .pay_msg {
            padding: 10px 30px 0;
            text-align: left;
            font-size: 2.4rem;
        }
        .pay_msg .tip{
            color: red;
        }
    </style>
</head>
<body style="background:#F0F0F0;">

<div class="mui-content">
    <section class="g_details allHtml ma" style="margin-top: 0;background-color: white;">
        <div class="goods_name">{pigcms{$h5_name}</div>
        <div class="pay_img">
            <img src="{pigcms{$static_public}images/pay_ico/weixin@2x.png" height="100%" width="auto">
        </div>
        <div class="pay_msg">
            <span>注意:  </span> <span class="tip">如果当前处于微信，请使用浏览器打开页面</span>
        </div>
        <div class="pay_money">
            <span>微信付款:  ￥</span> <span class="detail">{pigcms{$total_money}</span>
        </div>
    </section>
    </div>
    <div>
        <button type="button" class="true_btn"  data-mweb_url="{pigcms{$mweb_url}"><L_>确认支付</L_></button>
    </div>
</body>
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script>
    $('.true_btn').click(function () {
        if(checkApp){
            if(checkAndroidApp){
                window.pigcmspackapp.openBrowser(mweb_url);
            }else{
                var iosHref = window.btoa(mweb_url);
                iosHref = iosHref.replace('/','&');
                iosFunction('openBrowser/'+iosHref);
            }
        }else{
            location.href=mweb_url;
        }
    });

    function checkApp(){
        if(/(pigcms_pack_app)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    }
    function checkAndroidApp(){
        if(checkApp() && checkAndroid()){
            return true;
        }else{
            return false;
        }
    }
    function checkAndroid(){
        if(/(android)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    }
    function iosFunction(paramStr, paramObj){
        var pack_version = getVersion();
        var str = parseFloat(pack_version) >= 1000;
        if ( str == true) {
            window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage(paramObj);
        } else {
            $('body').append('<iframe id="iosIframe" src="pigcmspackapp://'+paramStr+'"></iframe>');
            setTimeout(function(){
                $('#iosIframe').remove();
            },50);
        }
    }
    function getVersion(){
        var reg = /pack_version=(.*?),/;
        var arr = reg.exec(navigator.userAgent.toLowerCase());
        if(arr == null){
            return '85';
        }else{
            return arr[1];
        }
    }
</script>
</html>