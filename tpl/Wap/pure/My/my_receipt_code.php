<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>二维码收款</title>
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
			width:30px;
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
            background-color: #E0B741;
        }
        .title_icon {
            width:25px;
            height:25px;
            margin-right:10px;
            margin-top:10px;
            float:left;
        }
        .receipt_content {
            background-color: white;padding: 10% 0;text-align: center
        }
        .title-transaction {
            border-top: 1px solid #f1f1f1;
        }
        .title_tip {
            background-color: #FBFBFB
        }
        .title-card-code {
            background-color: #E0CF56;
            color: #FFFFFF;
            margin-top: 15px;
        }
	</style>
</head>
<body>
    <div style="margin: 10px;">
        <dl id="integral" class="title title_tip">
            <div class="pb10">
                <img class="title_icon" src="{pigcms{$static_path}images/new_my/get_balance_money_title.png" />

                <div style="padding-top:12px;width:95%;color: #E0B741;">二维码收款</div>
            </div>
            <div style="clear:both;"></div>
        </dl>
        <div>
            <div class="receipt_content">
                <div>仅限于双方的平台余额互赠互转</div>
                <img src="{pigcms{$code_url}">
            </div>
            <dl id="transaction" class="title title-transaction">
                <div class="pb10">
                    <img class="titleImg" src="{pigcms{$static_path}images/new_my/transaction.png" />
                    <div style="padding-top:12px;width:95%;">转赠记录</div>
                    <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"/>
                </div>
                <div style="clear:both;"></div>
            </dl>
        </div>
        <dl id="cardCode" class="title title-card-code">
            <div class="pb10">
                <img class="titleImg" src="{pigcms{$static_path}images/new_my/card_code_give.png" />
                <div style="padding-top:12px;width:95%;">二维码付款</div>
                <img class="title_img" src="{pigcms{$static_path}images/new_my/tubiao2_11.png"/>
            </div>
            <div style="clear:both;"></div>
        </dl>
        </div>
		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script src="{pigcms{$static_path}layer/layer.m.js"></script>
		<script>

            $('#transaction').on('click',function(){
                location.href =	"{pigcms{:U('My/transaction')}";
            });

            $('#cardCode').on('click',function(){
                location.href =	"{pigcms{:U('My/my_store_card_code')}";
            });
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