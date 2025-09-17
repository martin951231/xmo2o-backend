<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>展示给商家扫码</title>
		<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
		<meta http-equiv="pragma" content="no-cache"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/reset.css">
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/js/weui/weui.min.css">
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/recharge.css" />
		<script src="{pigcms{$static_path}company_campus/js/jquery-1.8.3.min.js"></script>
		<script src="{pigcms{$static_path}company_campus/js/weui/weui.min.js"></script>
		<script src="{pigcms{$static_path}company_campus/js/recharge.js"></script>
	</head>
    <style type="text/css">
        .weui-media-box__title {
            font-size: 1.2rem;
        }
        .chongzhi{
            font-size: 1.2rem;
            padding:3px 10px;
            border-radius: 5px;
            background: #fff;
            color: #000;
        }
        #pay_money{
            font-size: 2.5rem
        }
    </style>
	<body style="background: {pigcms{:C('config.mobile_head_color')};color: #fff">
        <!-- <header class="header">
           <div class="goleft" onclick="JavaScript:history.back(-1)"></div>
           <span class="com_til">展示给商家扫码</span>
        </header>  -->
        <div class="codeTxt">
            <notempty name="staff"><h4 class="weui-media-box__title">企业预存余额:  <font id="qiye_money">{pigcms{$staff.money|round=###,2}</font></h4></notempty>
            <h4 class="weui-media-box__title" style="margin-top: 5px">平台余额: <font id="now_money">{pigcms{$nowUser.now_money|round=###,2}</font> <a href="/wap.php?g=Wap&c=My&a=recharge" class="cz_btn chongzhi" >充值</a></h4>
        </div>
        <!--支付成功-->
        <div class="code" id="pay_success" style="background: #fff;margin: 0px 10px;border-radius: 10px;display: none">
            <div style="padding: 115px 0 115px 0;color: #000;font-size: 1.2rem">
                <p style="font-size: 1.8rem;color: {pigcms{:C('config.mobile_head_color')}">支付成功</p>
                <p id="pay_money"></p>
            </div>
        </div>

        <!--二维码-->
        <div class="code" id="pay_code" style="background: #fff;margin: 0px 10px;border-radius: 10px;">
            <notempty name="payCode">
               <img src="{pigcms{$payCode.pay_bar_code}" id="pay_bar_code">
               <p class="p1 " id="code" style="color: #000">{pigcms{$payCode.code}</p>
               <p style="padding: 30px 0 10px 0;">
                   <img src="{pigcms{$payCode.pay_qr_code}" id="pay_qr_code" style="width: 120px;">
               </p>
               <p style="color: #000">二维码/条码每1分钟自动更新一次</p>
            <else/>
                <p style="padding: 130px 0 130px 0;color: #000;font-size: 1.2rem">
                    余额不足，请先充值
                </p>
            </notempty>
        </div>

        <notempty name="stores">
        <div class="codeTxt" style="margin-top:20px;">
            <h4 class="weui-media-box__title">企业预存余额可用于以下商家:</h4>
            <volist name="stores" id="m">
            <h4 class="weui-media-box__title">{pigcms{$m.name}</h4>
            </volist>
        </div>
        </notempty>
	</body>
    <script type="text/javascript">
        //获取支付状态
        getPayResult();

        function getPayResult() {
            var auth_code = $('#code').html();
            if (auth_code != undefined) {
                $.get('/wap.php?c=Company_campus&a=getPayResult&auth_code=' + auth_code, function (data) {
                    if (data.errorCode == 0) {
                        $('#pay_money').html(data.result.pay_money + '元');
                        $('#now_money').html(data.result.now_money);
                        $('#qiye_money').html(data.result.qiye_money);
                        $('#pay_code').remove();
                        $('#pay_success').show();
                    }
                }, 'json')
            }
            setTimeout("getPayResult()", 3000);
        }
    </script>
</html>
