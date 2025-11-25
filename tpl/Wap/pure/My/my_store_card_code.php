<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>收付款</title>
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
	    .my-account>img {
	        height: 100%;
	        position: absolute;
	        right: 0;
	        top:0;
	        z-index: 0;
	    }
	    .titleImg{
			width:25px;
			height:25px;
			margin-right:10px;
			margin-top:10px;
			float:left;
	    }
	    .title{
			padding:0 10px;
			background-color:#fff;
	    }
	    .title_img{
			float:right;
			margin-top:-19px;
			width:10px;
	    }
	    .pb10{
			padding-bottom:10px;
	    }
        body {
            background-color: #429056;
        }
        .title_icon {
            width:25px;
            height:25px;
            margin-right:10px;
            margin-top:10px;
            float:left;
        }
        .receipt_content {
            background-color: #FFFFFF;padding: 10% 0;text-align: center
        }
        .title-transaction {
            background-color: #5B9A6B;
            color: #FFFFFF;
            margin-top: 15px;
        }
        .title_tip {
            /*background-color: #999*/
        }
        .balance_tip {
            background-color: #FFFFFF;text-align: left;
        }
        .balance_tip_img {
            width:25px;
            height:25px;
            margin-right:5px;
            margin-left:10px;
            margin-top:10px;
            float:left;
        }
        .tip_info {
            color: #868686;
        }
        .refresh_pay_qrcode_img {
            width: 25px;
            height: 25px;
            position: relative;
            top: 6px;
        }
	</style>
</head>
<body>
    <div style="margin: 10px;">
        <dl id="integral" class="title title_tip">
            <div class="pb10">
                <img class="title_icon" src="{pigcms{$static_path}images/new_my/give_money_title.png" />

                <div style="padding-top:12px;width:95%;color: #429056;">向商家付款</div>
            </div>
            <div style="clear:both;"></div>
        </dl>
        <div>
            <div class="receipt_content">
                <img  id="my_store_card_barcode_img" src="{pigcms{$my_store_card_barcode}">
                <img  id="code_url_img" src="{pigcms{$code_url}">
                <!-- <div class="tip_info">
                    <img class="refresh_pay_qrcode_img" src="{pigcms{$static_path}images/new_my/refresh_pay_qrcode.png" />
                    会员码每隔30秒刷新
                </div> -->
            </div>
            <div class="balance_tip">
                <div class="pb10">
                    <img class="balance_tip_img" src="{pigcms{$static_path}images/new_my/balance.png" />
                    <div style="padding-top:12px;width:95%;font-weight: bold;">平台余额</div>
                </div>
                <div class="tip_info" style="padding:0 10px 10px;">
                    可用于支付线下场景，如到店付、线下零售、线下餐饮收银台
                </div>

            </div>
            <dl id="receiptCode" class="title title-transaction">
                <div class="pb10">
                    <img class="titleImg" src="{pigcms{$static_path}images/new_my/get_balance_money.png" />
                    <div style="padding-top:12px;width:95%;">二维码收款</div>
                    <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"/>
                </div>
                <div style="clear:both;"></div>
            </dl>
        </div>
        </div>
		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script src="{pigcms{$static_path}layer/layer.m.js"></script>
		<script>

            $('#receiptCode').on('click',function(){
                location.href =	"{pigcms{:U('My/my_receipt_code')}";
            });
            // setInterval(function(){
                // $.get("{pigcms{:U('My/refresh_pay_qrcode')}", {}, function(data){
                //     console.log('data',data);
                //     var url = data.url;
                //     var card_barcode = data.card_barcode;
                //     if (url) {
                //         $('#code_url_img').attr('src',url);
                //     }
                //     if (card_barcode) {
                //         $('#my_store_card_barcode_img').attr('src',card_barcode);
                //     }
                // }, 'json')
            // },30000);
		</script>
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Home/index')}",
				"tTitle": "{pigcms{$config.site_name}",
				"tContent": "{pigcms{$config.seo_description}"
			};
		</script>
		{pigcms{$shareScript}
<if condition="$_SESSION['source'] eq 1">
    <include file="Public/scenic_footer"/>
<else/>
</body>
</html>
</if>