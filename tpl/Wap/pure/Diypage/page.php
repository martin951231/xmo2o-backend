<!DOCTYPE html>
<html class="no-js admin <?php if ($_GET['ps'] <= 320) {
                                echo ' responsive-320';
                            } elseif ($_GET['ps'] >= 540) {
                                echo ' responsive-540';
                            }
                            if ($_GET['ps'] > 540) {
                                echo ' responsive-800';
                            } ?>" lang="zh-CN">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8">
    <title>
        <notempty name='now_page.page_name'>{pigcms{$now_page.page_name}
            <else />{pigcms{$now_store.name}
        </notempty>
    </title>
    <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/base.css" />

    <?php if ($is_mobile) { ?>
        <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/showcase.css?t={pigcms{:time()}" />
    <?php } else { ?>
        <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/showcase_admin.css" />
    <?php } ?>

    <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/swiper.min.css" />
    <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/shop.css" />
    <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/customFieldActivities.css?t=1" />
    <link rel="stylesheet" href="{pigcms{$static_path}diypage/css/info_module.css?t=2" />
    <!-- <link rel="stylesheet" href="./tpl/System/Static/css/custom-ui.css"/> -->
    <style>
        .contact_store {
            display: block;
            padding: 0px 5px 5px 5px;
            border-radius: 4px;
            margin: 10px;
            position: relative;
            background: #fff;
        }

        .contact_store dl dd {
            font-size: 14px;
            color: #333;
            margin-left: 0;
            line-height: 40px;
        }

        .contact_store dl dt {
            font-size: 12px;
            color: #999;
            padding-left: 20px;
            background: url({pigcms{$static_path}diypage/images/dizhi.png) left center no-repeat;
            background-size: 10px auto;
        }

        .contact_icon {
            position: absolute;
            right: 10px;
            top: 15px;
        }

        .contact_icon a {
            width: 36px;
            height: 36px;
            margin-left: 15px;
        }

        .contact_icon a img {
            width: 24px;
        }

        .phone_content {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 1000;
            width: 80%;
            max-height: 50%;
            overflow: auto;
            box-sizing: border-box;
            display: none;
            transform: translate(-50%, -50%);
        }

        .phone_content li {
            color: rgb(81, 195, 50);
        }


        .bg_d {
            background: #ddd !important;
        }

        .br5 {
            border-radius: 5px;
        }

        .pl30 {
            padding-left: 30px !important;
        }

        .pr30 {
            padding-right: 30px !important;
        }

        .pt30 {
            padding-top: 30px !important;
        }

        .pb10 {
            padding-bottom: 10px !important;
        }

        .mb_20 {
            margin-bottom: 20px !important;
        }

        .pl10 {
            padding-left: 18px !important;
        }

        .pt5 {
            padding-top: 5px !important;
        }

        .pb5 {
            padding-bottom: 5px !important;
        }

        .flex {
            display: flex !important;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            align-items: center;
        }

        .bg_f {
            background: #fff !important;
        }

        .mask2 {
            position: fixed;
            top: 0px;
            left: 0px;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2222;
            width: 100%;
            display: none;
        }

        .phone_cancel {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0 10px;
        }

        .color_9 {
            color: #999;
        }

        .font_size28 {
            font-size: 28px !important;
        }

        .cursor {
            cursor: pointer !important;
        }

        .js-product_group_2 .custom-tag-list-title {
            width: 100%;
            height: 26px;
            line-height: 26px;
            font-size: 12px;
            white-space: nowrap;
            word-break: keep-all;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: normal;
            background: #eee;
            padding: 0 0 0 10px;
            margin: 0;
            background: #eee;
        }

        .new_acitivty_box {
            height: auto;
            margin-left: 6px;
            overflow-y: auto;
        }

        .new_acitivty_msg {
            height: auto;
            width: 100%;
            overflow-y: auto;
        }

        .new_acitivty_msg .msg ul {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
            text-align: center;
            float: left;
        }

        .new_acitivty_msg .msg ul img {
            width: 116px;
            height: 100px;
            margin-top: 12px;
        }

        .new_acitivty_msg .clearfix {
            zoom: 1;
        }

        .new_acitivty_msg .msg li {
            width: calc(50% - 10px);
            margin-bottom: 10px;
            margin-right: 10px;
            overflow: hidden;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            display: block;
            float: left;
            border: 1px solid #e3e3e3;
            background-color: #fafafa;
            font-size: 14px;
        }

        .new_acitivty_msg .msg li h3 {
            font-size: 11px;
            color: #000;
            font-weight: normal;
            padding: 5px 5px;
            padding-bottom: 0;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .new_acitivty_msg .msg ul:nth-child(2n-1) {
            clear: both;
        }


        #goods_group3 {
            overflow: hidden;
        }

        #goods_group3 #shopBanner {
            margin-top: 36px;
            background: -webkit-gradient(linear, 0% 0%, 100% 0%, from(#06c1ae), color-stop(0.6, orange), to(#06c1ae));
            position: relative;
            padding-top: 0px;
            overflow: hidden;
        }

        #goods_group3 #shopBanner:after {
            display: block;
            content: "";
            border-top: 2px solid white;
            border-left: 2px solid white;
            width: 8px;
            height: 8px;
            -webkit-transform: rotate(135deg);
            background-color: transparent;
            position: absolute;
            top: 40px;
            right: 18px;
        }

        #goods_group3 #shopBanner .discount {
            line-height: 30px;
            height: 30px;
            color: white;
            font-size: 12px;
            padding-left: 35px;
            padding-right: 12px;
            overflow: hidden;
            position: relative;
            background-color: rgba(0, 0, 0, 0.4);
            word-break: keep-all;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #goods_group3 #shopBanner .discount .noticeBox {
            position: absolute;
            left: 15px;
        }

        #goods_group3 #shopBanner .discount {
            line-height: 30px;
            color: white;
            font-size: 12px;
            word-break: keep-all;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #goods_group3 #shopBanner .discount .notice {
            position: relative;
        }

        #goods_group3 #shopBanner .discount .notice,
        #shopBanner .discount .notice div {
            height: 20px;
            width: 20px;
            padding-left: 15px;
            display: block;
            position: relative;
        }

        #goods_group3 #shopBanner .discount .notice:before {
            content: '';
            height: 2px;
            width: 2px;
            display: block;
            background: white;
            position: absolute;
            top: 13px;
            left: 0px;
        }

        #goods_group3 #shopBanner .discount .notice:after {
            content: '';
            height: 4px;
            width: 0;
            display: block;
            border: 3px transparent solid;
            border-left-width: 0;
            border-right-color: white;
            position: absolute;
            top: 9px;
            left: 2px;
        }

        #goods_group3 #shopBanner .discount .notice div {
            position: absolute;
            top: 0;
            left: 0;
        }

        #goods_group3 #shopBanner .discount .notice div:before {
            content: '';
            height: 12px;
            width: 12px;
            display: block;
            border: 1px solid transparent;
            border-right: 1px solid white;
            border-radius: 20px;
            -webkit-border-radius: 20px;
            -moz-border-radius: 20px;
            position: absolute;
            top: 7px;
            left: -4px;
        }

        #goods_group3 #shopBanner .discount .notice div:after {
            content: '';
            height: 14px;
            width: 14px;
            display: block;
            border: 1px solid transparent;
            border-right: 1px solid white;
            border-radius: 20px;
            -webkit-border-radius: 20px;
            -moz-border-radius: 20px;
            position: absolute;
            top: 6px;
            left: -3px;
        }

        #goods_group3 #shopMenuBar ul {
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            background: #FFF;
            text-align: center;
            border-bottom: 1px solid #edebeb;
            width: 100%;
        }

        #goods_group3 #shopMenuBar li.active {
            color: #06c1ae;
        }

        #goods_group3 #shopMenuBar li {
            -webkit-box-flex: 1;
            -moz-box-flex: 1;
            -ms-flex: 1;
            width: 100%;
            text-align: left;
            text-align: center;
            color: #333;
            position: relative;
            height: 40px;
            line-height: 40px;
        }

        #goods_group3 #shopMenuBar li.active:after {
            content: '';
            position: absolute;
            bottom: 0px;
            width: 100%;
            left: 0;
            height: 2px;
            background: #06c1ae;
        }

        #goods_group3 #shopContentBar {
            position: relative;
        }

        #goods_group3 #shopContentBar>div {
            position: absolute;
            top: 0px;
            left: 0;
            overflow-x: hidden;
        }

        #goods_group3 #shopProductLeftBar2 {
            width: 100px;
            background-color: #fff;
            float: left;
            overflow-y: auto;
        }

        #goods_group3 #shopProductLeftBar2 dl {
            width: 100px;
        }

        #goods_group3 #shopProductLeftBar2 dd.active {
            position: relative;
            background-color: #ededed;
        }

        #goods_group3 #shopProductLeftBar2 dd {
            line-height: 50px;
            overflow: hidden;
            text-align: left;
        }

        #goods_group3 #shopProductLeftBar2 dd.active span {
            color: #06c1ae;
        }

        #goods_group3 #shopProductLeftBar2 dd span {
            padding: 15px 10px;
            line-height: 20px;
            display: block;
            background-color: #ededed;
            border-bottom: 1px solid #e1e0e0;
            font-size: 14px;
        }

        #goods_group3 #shopProductRightBar2 {
            float: left;
            overflow-y: auto;
        }

        #goods_group3 #shopProductRightBar2 dl {
            margin-left: 5px;
            background-color: white;
        }

        #goods_group3 #shopProductRightBar2 dd {
            border-bottom: 1px solid #f1f1f1;
        }

        #goods_group3 #shopProductRightBar2 .cat_name {
            line-height: 20px;
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }

        #goods_group3 #shopProductRightBar2 ul {
            padding-left: 10px;
        }

        #goods_group3 ul {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }

        #goods_group3 #shopProductRightBar2 li {
            position: relative;
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        #goods_group3 #shopProductRightBar2 li .position_img {
            position: absolute;
            width: 60px;
            height: 60px;
            overflow: hidden;
        }

        #goods_group3 #shopProductRightBar2 li .mall img {
            margin-left: 0px;
        }

        #goods_group3 #shopProductRightBar2 li .position_img img {
            height: 100%;
        }

        #goods_group3 #shopProductRightBar2 li .product_text {
            margin-left: 70px;
            font-size: 14px;
            line-height: 1.5;
        }

        #goods_group3 #shopProductRightBar2 li .product_text .title {}

        #goods_group3 #shopProductRightBar2 li .product_text .sale {
            font-size: 11px;
            color: #bbb;
            margin: 2px 0;
        }

        #goods_group3 #shopProductRightBar2 li .product_text .price {
            color: red;
            font-size: 12px;
        }

        #goods_group3 .skill_discount {
            background: url({pigcms{$static_path}shop/images/skill_discount.png) no-repeat left;
            text-align: left;
            padding-left: 20px;
            color: #ff0505;
            font-size: 12px;
            margin-top: -21px;
            margin-bottom: 5px;
            background-size: 14px;
            background-color: white;
            z-index: 111;
            position: relative;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn {
            position: absolute;
            right: 10px;
            line-height: 1.5;
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 2px 5px;
            color: #666;
            z-index: 1;
            top: 46px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.plus,
        #goods_group3 #shopProductRightBar2 li .product_btn.min {
            width: 28px;
            height: 28px;
            border-radius: 100%;
            padding: 0px;
            background: white;
            top: 40px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.plus:before,
        #goods_group3 #shopProductRightBar2 li .product_btn.plus:after {
            content: '';
            height: 2px;
            width: 10px;
            display: block;
            background: #666;
            border-radius: 10px;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            position: absolute;
            top: 12px;
            left: 9px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.plus:after {
            height: 10px;
            width: 2px;
            top: 8px;
            left: 13px;
        }

        #goods_group3 #shopProductRightBar2 li .bgPlusBack {
            position: absolute;
            right: 0;
            bottom: 0px;
            width: 52px;
            height: 42px;
            z-index: 2;
        }

        #goods_group3 #shopProductRightBar2 li .bgMinBack {
            position: absolute;
            right: 52px;
            bottom: 0px;
            width: 52px;
            height: 42px;
            z-index: 2;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.min {
            right: 60px;
        }

        #goods_group3 #shopProductCart {
            position: fixed;
            height: 50px;
            background-color: #393634;
            width: 100%;
            bottom: 0;
            z-index: 126;
            font-size: 14px;
        }

        #goods_group3 #shopProductCart #cartInfo {
            margin-right: 110px;
            height: 50px;
        }

        #goods_group3 #shopProductCart #cartInfo .cart {
            height: 50px;
            float: left;
            margin-left: 10px;
            width: 50px;
            margin-top: -10px;
            background: #302D2C url({pigcms{$static_path}shop/images/cart.png) no-repeat;
            background-size: 20px;
            background-position: center;
            border-radius: 100%;
            position: relative;
        }

        #goods_group3 #shopProductCart #cartNumber {
            position: absolute;
            right: -5px;
            top: -3px;
            background-color: #06c1ae;
            padding: 2px;
            border-radius: 100%;
            font-size: 10px;
            color: white;
            line-height: 16px;
            width: 16px;
            height: 16px;
            text-align: center;
        }

        #goods_group3 #shopProductCart .price {
            height: 50px;
            line-height: 50px;
            color: white;
            margin-left: 10px;
            float: left;
            letter-spacing: 1px;
        }

        #goods_group3 #shopProductCart #emptyCart {
            color: white;
            line-height: 50px;
        }

        #goods_group3 #shopProductCart #emptyCart .cart {
            height: 50px;
            float: left;
            width: 50px;
            background: url({pigcms{$static_path}shop/images/cart.png) no-repeat;
            background-size: 22px;
            background-position: center;
        }

        #goods_group3 #shopProductCart #checkCart {
            position: absolute;
            background: #06c1ae;
            color: white;
            right: 0;
            top: 0;
            height: 50px;
            padding: 0 30px;
            line-height: 50px;
            text-align: center;
        }

        #goods_group3 #shopProductCart #checkCartEmpty {
            position: absolute;
            color: white;
            right: 10px;
            top: 0;
            height: 50px;
            line-height: 50px;
            text-align: center;
        }

        #goods_group3 .pageLoadTipBox {
            width: 120px;
            height: 120px;
            background: rgba(0, 0, 0, 0.59);
            border-radius: 5px;
            box-shadow: 0 0 12px #333;
            -webkit-box-shadow: 0 0 12px #333;
            position: fixed;
            z-index: 9999;
        }

        #goods_group3 .pageLoadTipLoader {
            display: inline-block;
            width: 70px;
            height: 70px;
            position: relative;
            margin: 20px;
        }

        #goods_group3 .pageLoadTipLoader:before {
            content: "";
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            line-height: 100%;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.0);
            border-radius: 100%;
            border: none;
            border-bottom: #06c1ae solid 5px;
            border-top: rgba(6, 193, 174, 0.35) solid 5px;
            border-right: rgba(6, 193, 174, 0.35) solid 5px;
            border-left: rgba(6, 193, 174, 0.35) solid 5px;
            -webkit-animation-name: pageLoader;
            -moz-animation-name: pageLoader;
            -o-animation-name: pageLoader;
            animation-name: pageLoader;
            -webkit-animation-iteration-count: infinite;
            -moz-animation-iteration-count: infinite;
            -o-animation-iteration-count: infinite;
            animation-iteration-count: infinite;
            -webkit-animation-timing-function: linear;
            -moz-animation-timing-function: linear;
            -o-animation-timing-function: linear;
            animation-timing-function: linear;
            -webkit-animation-fill-mode: forwards;
            -o-animation-fill-mode: forwards;
            animation-fill-mode: forwards;
            z-index: 1;
            -webkit-animation-duration: 1s;
            -moz-animation-duration: 1s;
            -o-animation-duration: 1s;
            animation-duration: 1s;
        }

        #goods_group3 .pageLoadTipLoader div {
            position: absolute;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            color: dodgerblue;
            z-index: 2;
            width: 100%;
            height: 100%;
            line-height: 70px;
            font-size: 20%;
            border-radius: 100%;
            margin-left: 5px;
            margin-top: 5px;
            background-repeat: no-repeat;
            background-size: 40px;
            background-position: 50%;
        }

        #goods_group3 .pageLoadTipBg {
            background: #D8D8D8;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: 9998;
            display: none;
        }

        #goods_group3 #shopContentBar>div {
            position: absolute;
            top: 0px;
            left: 0;
            overflow-x: hidden;
        }

        #goods_group3 #shopMerchantBox dl {
            margin-top: 10px;
            background-color: white;
            padding-left: 16px;
            padding-right: 8px;
        }

        #goods_group3 #shopMerchantBox dd.merchant {
            background: url({pigcms{$static_path}shop/images/merchant_home.png) no-repeat;
            background-size: 14px;
            background-position: 6px 14px;
        }

        #goods_group3 #shopMerchantBox dd.phone {
            background: url({pigcms{$static_path}shop/images/merchant_phone.png) no-repeat;
            background-size: 14px;
            background-position: 6px 14px;
        }

        #goods_group3 #shopMerchantBox dd.openTime {
            background: url({pigcms{$static_path}shop/images/merchant_time.png) no-repeat;
            background-size: 14px;
            background-position: 6px 14px;
        }

        #goods_group3 #shopMerchantBox dd.deliveryType {
            background: url({pigcms{$static_path}shop/images/merchant_ride.png) no-repeat;
            background-size: 14px;
            background-position: 6px 14px;
        }

        #goods_group3 #shopMerchantBox dd.merchantNotice {
            background: url({pigcms{$static_path}shop/images/merchant_notice.png) no-repeat;
            background-size: 14px;
            background-position: 6px 14px;
        }

        #goods_group3 #shopMerchantBox dd.merchantVerify {
            background: url({pigcms{$static_path}shop/images/rec.png) no-repeat;
            background-size: 20px;
            background-position: 3px 12px;
        }

        #goods_group3 #shopMerchantBox dd.storelive {
            background: url({pigcms{$static_path}shop/images/video.png) no-repeat;
            background-size: 20px;
            background-position: 3px 12px;
        }

        #goods_group3 #shopMerchantBox dd {
            padding: 12px 8px 12px 30px;
            border-bottom: 1px solid #f1f1f1;
            color: #333;
            position: relative;
        }

        #goods_group3 #shopMerchantBox dd.more:after {
            display: block;
            content: "";
            border-top: 1px solid #BFBFBF;
            border-left: 1px solid #BFBFBF;
            width: 6px;
            height: 6px;
            -webkit-transform: rotate(135deg);
            background-color: transparent;
            position: absolute;
            top: 50%;
            right: 6px;
            margin-top: -3px;
        }

        #goods_group3 #shopMerchantBox dd.address span {
            height: 12px;
            width: 12px;
            border: 1px #b2b2b2 solid;
            border-radius: 100%;
            -webkit-border-radius: 100%;
            -moz-border-radius: 100%;
            position: absolute;
            top: 13px;
            left: 6px;
        }

        #goods_group3 #shopMerchantBox dd.address span:before {
            content: '';
            width: 2px;
            height: 2px;
            background: #b2b2b2;
            border-radius: 100%;
            -webkit-border-radius: 100%;
            -moz-border-radius: 100%;
            position: absolute;
            top: 5px;
            left: 5px;
        }

        #goods_group3 #shopMerchantCouponBox em {
            width: 16px;
            height: 16px;
            line-height: 16px;
            display: inline-block;
            background-color: red;
            position: absolute;
            left: 4px;
            top: 13px;
            border-radius: 2px;
            font-size: 10px;
        }

        #goods_group3 #shopMerchantCouponBox em:before {
            content: '惠';
            font-size: 11px;
            color: white;
            margin-left: 2px;
        }

        #goods_group3 em.merchant_invoice {
            background-color: #7CC6FF !important;
        }

        #goods_group3 em.merchant_invoice:before {
            content: '票' !important;
        }

        #goods_group3 em.merchant_minus {
            background-color: #FF6655 !important;
        }

        #goods_group3 em.system_minus {
            background-color: #5D26EA !important;
        }

        #goods_group3 em.merchant_minus:before,
        em.system_minus:before {
            content: '减' !important;
        }

        #goods_group3 em.merchant_discount {
            background-color: #DD1111 !important;
        }

        #goods_group3 em.merchant_discount:before {
            content: '折' !important;
        }

        #goods_group3 em.merchant_delivery {
            background-color: #6C5CB4 !important;
        }

        #goods_group3 em.merchant_delivery:before {
            content: '送' !important;
        }

        #goods_group3 em.newuser {
            background-color: #FFAA22 !important;
        }

        #goods_group3 em.system_newuser {
            background-color: #0EC0A8 !important;
        }

        #goods_group3 em.newuser:before,
        em.system_newuser:before {
            content: '首' !important;
        }

        #goods_group3 em.isDiscountGoods {
            background-color: #e22424 !important;
        }

        #goods_group3 em.isDiscountGoods:before {
            content: '限' !important;
        }

        #goods_group3 {
            font-size: 14px;
            line-height: 1.5;
        }

        #goods_group3 .photo_s {
            margin-bottom: 12px;
        }

        #goods_group3 .photo {
            padding: 0px 10px;
            background: #fff;
            margin-top: 10px;
        }

        #goods_group3 .photo h2 {
            font-size: 15px;
            font-weight: normal;
            line-height: 40px;
            padding: 0px;
            margin: 0px;
        }

        #goods_group3 .photo ul {
            padding-bottom: 15px;
        }

        #goods_group3 ul {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }

        #goods_group3 .clearfix:after {
            content: '';
            clear: both;
            display: block;
        }

        #goods_group3 .photo li {
            width: 23%;
            margin-right: 2%;
            float: left;
            height: 60px;
            line-height: 60px;
            text-align: center;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.min:before {
            content: '';
            height: 2px;
            width: 10px;
            display: block;
            background: #666;
            border-radius: 10px;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            position: absolute;
            top: 12px;
            left: 9px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.min {
            right: 60px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.plus,
        #shopProductRightBar2 li .product_btn.min {
            width: 28px;
            height: 28px;
            border-radius: 100%;
            padding: 0px;
            background: white;
            top: 40px;
        }

        #goods_group3 #shopProductRightBar2 li .product_btn.number {
            border: none;
            right: 38px;
            font-size: 14px;
            padding: 0;
            width: 24px;
            text-align: center;
            line-height: 30px;
            top: 40px;
        }

        #goods_group3 #shopProductCart .price.active {
            height: 50px;
            line-height: 1.2;
            color: white;
            margin-left: 10px;
            float: left;
            letter-spacing: 1px;
        }

        #goods_group3 #shopProductCart .price.active dl {
            margin-top: 10px;
        }

        .albumContainer {
            position: fixed;
            width: 100%;
            height: 100%;
            left: 0;
            top: 0;
            background: #000;
            z-index: 1000;
            display: none;
            overflow: hidden;
            -webkit-transform-origin: 0px 0px;
            opacity: 1;
            -webkit-transform: scale(1, 1);
        }

        .albumContainer.swiper-container {
            /* margin: 0 auto; */
            margin: 0 0;
            position: relative;
            overflow: hidden;
            -webkit-backface-visibility: hidden;
            -moz-backface-visibility: hidden;
            -ms-backface-visibility: hidden;
            -o-backface-visibility: hidden;
            backface-visibility: hidden;
            z-index: 1;
        }

        .albumContainer.swiper-container,
        .swiper-wrapper,
        .swiper-slide {
            width: 100%;
            height: 100%;
        }

        .albumContainer .swiper-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            z-index: 1;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;
            -webkit-transition-property: -webkit-transform;
            -moz-transition-property: -moz-transform;
            -o-transition-property: -o-transform;
            -ms-transition-property: -ms-transform;
            transition-property: transform;
            -webkit-box-sizing: content-box;
            -moz-box-sizing: content-box;
            box-sizing: content-box;
        }

        .albumContainer .swiper-wrapper {
            padding-top: 50px;
        }

        .albumContainer .swiper-slide {
            float: left;
        }

        .albumContainer .swiper-slide img {
            width: 100%;
            max-height: 85%;
        }

        .albumContainer .swiper-pagination {
            position: absolute;
            z-index: 20;
            left: 0;
            top: 20px;
            text-align: center;
            width: 100%;
            height: 20px;
        }

        .albumContainer .swiper-active-switch {
            background: #06c1ae;
        }

        .albumContainer .swiper-pagination-switch {
            background: #fff;
            opacity: 1;
        }

        .albumContainer .swiper-pagination-switch {
            background: #fff;
            opacity: 1;
        }

        .albumContainer .swiper-close {
            right: 0px;
            top: 0px;
            text-align: right;
            padding-right: 10px;
            line-height: 50px;
            position: absolute;
            z-index: 21;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 20px;
            font-family: "Arial";
        }

        .msg-bg {
            background: rgba(0, 0, 0, .4);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9998;
        }

        .msg-option {
            background: 0;
            bottom: 40px;
        }

        .msg-doc {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 1px;
            background: #fff;
            overflow: hidden;
            z-index: 9999;
        }

        .msg-option div:first-child,
        .msg-option .msg-option-btns:first-child .btn:first-child {
            border-top: 0;
        }

        .msg-option .msg-bd {
            background: #fff;
            border-bottom: 0;
        }

        .msg-bd {
            font-size: 18px;
            padding-left: 12px;
            border-bottom: 1px solid #ddd8ce;
            height: 50px;
            line-height: 50px;
        }

        .msg-option .btn {
            height: 50px;
            line-height: 50px;
            border-top: 1px solid #ccc;
            text-align: center;
        }

        .msg-option .btn {
            display: inline-block;
            width: 100%;
            background: #fff;
            border: 0;
            color: #06c1ae;
        }

        .msg-option .msg-btn-cancel {
            padding: 0;
            margin-top: 2px;
            color: #FF6634 !important;
            height: 50px;
            line-height: 50px;
            font-size: 16px;
        }

        .msg-option .btn {
            height: 50px;
            line-height: 50px;
            border-top: 1px solid #ccc !important;
            text-align: center;
        }

        .msg-option .btn {
            display: inline-block;
            width: 100%;
            background: #fff;
            border: 0;
            color: #06c1ae;
        }

        .msg-option .msg-btn {
            font-size: 16px;
        }


        #goods_group3 #shopProductCartBox {
            width: 100%;
            overflow-y: auto;
        }

        #goods_group3 #shopProductCartBox dt {
            position: relative;
            height: 50px;
            line-height: 50px;
            padding: 0 15px;
            border-left: 5px solid #06c1ae;
        }

        #goods_group3 #shopProductCartBox #shopProductCartDel {
            position: absolute;
            right: 00px;
            top: 0;
            width: 100px;
            text-align: center;
            color: #999;
        }

        #goods_group3 #shopProductCartBox dd {
            padding: 10px 15px;
            border-top: 1px solid #f1f1f1;
        }

        #goods_group3 #shopProductCartBox dd .cartLeft {
            float: left;
            line-height: 40px;
        }

        #goods_group3 #shopProductCartBox dd .cartRight {
            float: right;
            margin-top: 5px;
        }

        #goods_group3 #shopDetailPageNumber .product_btn.plus,
        #goods_group3 #shopDetailPageNumber .product_btn.min,
        #goods_group3 #shopProductCartBox .product_btn.plus,
        #goods_group3 #shopProductCartBox .product_btn.min {
            width: 28px;
            height: 28px;
            border-radius: 100%;
            padding: 0px;
        }

        #goods_group3 #shopDetailPageNumber .product_btn.plus:before,
        #goods_group3 #shopDetailPageNumber .product_btn.plus:after,
        #goods_group3 #shopProductCartBox .product_btn.plus:before,
        #goods_group3 #shopProductCartBox .product_btn.plus:after {
            content: '';
            height: 2px;
            width: 10px;
            display: block;
            background: #666;
            border-radius: 10px;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            position: absolute;
            top: 12px;
            left: 9px;
        }

        #goods_group3 #shopDetailPageNumber .product_btn.number,
        #goods_group3 #shopProductCartBox .product_btn.number {
            border: none;
            font-size: 14px;
            padding: 0;
            width: 24px;
            text-align: center;
            line-height: 26px;
        }

        #goods_group3 #shopDetailPageNumber .product_btn,
        #goods_group3 #shopProductCartBox .product_btn {
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 2px 5px;
            color: #666;
            float: right;
            position: relative;
        }

        #goods_group3 #shopProductCartBox dd .cartRight .price {
            float: right;
            line-height: 30px;
            margin-right: 30px;
            color: red;
        }

        #goods_group3 #shopProductCartBox dd {
            padding: 10px 15px;
            border-top: 1px solid #f1f1f1;
        }

        #goods_group3 #spell_tip {
            text-align: center;
            font-size: 14px;
        }

        #goods_group3 #shopProductCartShade {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, .7);
            z-index: 125;
            display: none;
        }

        #goods_group3 #shopDetailPageNumber .product_btn.min:before,
        #goods_group3 #shopProductCartBox .product_btn.min:before {
            content: '';
            height: 2px;
            width: 10px;
            display: block;
            background: #666;
            border-radius: 10px;
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            position: absolute;
            top: 11px;
            left: 9px;
        }

        #shopDetailPageNumber .product_btn.plus:after,
        #shopProductCartBox .product_btn.plus:after {
            height: 10px;
            width: 2px;
            top: 8px;
            left: 13px;
        }

        .current.get {
            background: #fff
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <?php if (!$is_mobile && $_SESSION['merchant']['mer_id'] == $now_store['mer_id']) { ?>
                <div class="headerbar">
                    <div class="headerbar-wrap clearfix">
                        <div class="headerbar-preview">
                            <span>预览：</span>
                            <ul>
                                <li>
                                    <a href="{pigcms{:U('page',array('page_id'=>$now_page['page_id'],'ps'=>320))}" class="js-no-follow <?php if (empty($_GET['ps']) || $_GET['ps'] == '320') echo ' active'; ?>">iPhone版</a>
                                </li>
                                <li>
                                    <a href="{pigcms{:U('page',array('page_id'=>$now_page['page_id'],'ps'=>540))}" class="js-no-follow <?php if ($_GET['ps'] == '540') echo ' active'; ?>">三星Note3版</a>
                                </li>
                                <!--li>
								   <a href="{pigcms{:U('page',array('page_id'=>$now_page['page_id'],'ps'=>800))}" class="js-no-follow">PC版</a>
								</li-->
                            </ul>
                        </div>
                        <if condition="$_SESSION['merchant']">
                            <div class="headerbar-reedit">
                                <a href="{pigcms{$config.site_url}/merchant.php?c=Diypage&a=create&page_id={pigcms{$now_page.page_id}&store_id={pigcms{$now_page.store_id}" class="js-no-follow">重新编辑</a>
                            </div>
                        </if>
                    </div>
                </div>
            <?php } ?>
            <!-- ▼顶部通栏 -->
            <!-- <div class="js-mp-info share-mp-info">
				<a class="page-mp-info" href="{pigcms{$now_store.url}">
					<img class="mp-image" width="24" height="24" src="<if condition="$now_store['logo']">{pigcms{:replace_file_domain($now_store['logo'])}<else/>{pigcms{:replace_file_domain($now_store['all_pic'][0])}</if>" alt="{pigcms{$now_store.name}"/>
					<i class="mp-nickname">{pigcms{$now_store.name}</i>
				</a>
				<div class="links">
					<a class="mp-homepage" href="{pigcms{$now_store.card_url}">会员中心</a>
				</div>
			</div> -->
            <!-- ▲顶部通栏 -->
        </div>
        <div class="content" <?php if ($now_page['bgcolor']) { ?>style="background-color:<?php echo $now_page['bgcolor']; ?>;" <?php } ?>>
            <div class="content-body">
                <?php foreach ($field_list as $value) {
                    echo $value['html'];
                } ?>
            </div>
            <?php if (!$is_mobile) { ?>
                <div class="content-sidebar">
                    <div class="sidebar-section qrcode-info">
                        <div class="section-detail">
                            <p class="text-center shop-detail"><strong>手机扫码访问</strong></p>
                            <p class="text-center weixin-title">微信“扫一扫”分享到朋友圈</p>
                            <p class="text-center qr-code">
                                <img width="158" height="158" src="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon={pigcms{:urlencode($config['site_url'].'/wap.php?c=Diypage&a=page&page_id='.$now_page['page_id'])}" />
                            </p>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <!-- <div class="js-footer">          
			<div class="footer">
				<div class="copyright">
					<div class="ft-links">
						<a href="{pigcms{$now_store.url}">店铺主页</a>
						<a href="{pigcms{:U('Mall/cart',array('store_id'=>$now_store['store_id']))}">购物车</a>
						<a href="{pigcms{$now_store.card_url}">会员中心</a>
					</div>
				</div>
			</div>
		</div> -->
        <include file="Shop:classic_js_theme" />
    </div>
    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key={pigcms{$config.google_map_ak}"></script>
        <script type="text/javascript">
            var is_google_map = "{pigcms{$config.google_map_ak}"
        </script>
        <else />
        <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1"></script>
    </if>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}" charset="utf-8"></script>
    <script src="{pigcms{$static_path}diypage/js/swiper.min.js"></script>
    <script src="{pigcms{$static_path}diypage/js/waterfall.min.js"></script>
    <script src="{pigcms{$static_path}diypage/js/base.js?t=001"></script>
    <script src="{pigcms{$static_path}js/layer.m.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?220" charset="utf-8"></script>

    <!-- 判断是否在小程序打开 -->
    <if condition="$is_wxapp_browser">
        <script type="text/javascript">

        </script>
    </if>

    <script type="text/javascript">
        var system_own = '{pigcms{$_GET.system_own}';
        if (system_own == '1') {
            // 跳转新版
            window.location.href = '{pigcms{$config.site_url}/packapp/plat/pages/shop_new/shopSpecial/shopSpecialComplex?id={pigcms{$_GET.page_id}';
        }

        var locationClassicHash = 'shop-{pigcms{$_GET.shop_id}';
        var now_store_id = '{pigcms{$_GET.shop_id}'
        var user_long = '0',
            user_lat = '0';
        var user_address = '';
        var ajax_url_root = "{pigcms{$config.site_url}/wap.php?c=Shop&a=";
        var check_cart_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=confirm_order";
        var ajax_map_url = "{pigcms{$config.site_url}/index.php?g=Index&c=Map&a=suggestion&city_id={pigcms{$config.now_city}";
        var get_route_url = "{pigcms{:U('Group/get_route')}";
        var baiduToGcj02Url = "{pigcms{:U('Userlonglat/baiduToGcj02')}";
        var city_id = "{pigcms{$config.now_city}";
        var cat_url = "",
            sort_url = "",
            type_url = "";
        var noAnimate = true;
        var userOpenid = "{pigcms{$_SESSION.openid}";
        var shopShareUrl = "{pigcms{$config.site_url}{pigcms{:U('Shop/index',array('openid'=>$_SESSION['openid'], 'type'=>'share'))}&shop-id=";
        var shopReplyUrl = "{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id=";
        var nowIndex = '{pigcms{$nowIndex}',
            cartid = '{pigcms{$cartid}',
            spellfrm = '{pigcms{$_GET["frm"]}';
        var spell_add_cart = '{pigcms{$config.site_url}/wap.php?c=Shop&a=addNew',
            spell_cart_url = '{pigcms{$config.site_url}/wap.php?c=Shop&a=',
            deliverExtraPrice = 0;
        var ajax_cart_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_cart";
        var ajax_cart_save_url = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_cart_save";
        var ajax_basic_price = "{pigcms{$config.site_url}/wap.php?c=Shop&a=ajax_basic_price";
        var currency_txt = "{pigcms{$config['Currency_txt']}";
        var currency_symbol = "{pigcms{$config['Currency_symbol']}";
        var store_id = "{pigcms{$now_store['store_id']}";
        var is_close = "{pigcms{$is_close}";

        var is_scroller = true;
        var deliverName = "{pigcms{$config['deliver_name']}"; 
        <if condition = "$long_lat" >
            var user_long = "{pigcms{$long_lat.long}",
                user_lat = "{pigcms{$long_lat.lat}"; 
        <else />var user_long = '0',user_lat  = '0';</if>
            var noCart = true;
    </script>
    <script type="text/javascript" src="{pigcms{$static_path}diypage/js/shopGroupBaseNew.js?t={pigcms{$_SERVER.REQUEST_TIME}" charset="utf-8"></script>

    <script id="productCartBoxTpl" type="text/html">
        <dl>
            <dt class="clearfix">购物车<div id="shopProductCartDel">清空</div>
            </dt>
            {{#
            var packCharge = 0;
            for(var i in d){
            packCharge += d[i].productPackCharge*d[i].count;
            }}
                <dd class="clearfix cartDD" data-unit="{{ d[i].unit }}" data-product_id="{{ d[i].productId }}" data-packing_charge="{{ d[i].productPackCharge }}" data-product_price="{{ d[i].productPrice }}" data-product_name="{{ d[i].productName }}" data-stock="{{ d[i].productStock }}" data-max_num="{{ d[i].maxNum }}" data-min_num="{{ d[i].minNum }}" data-o_price="{{ d[i].oldPrice }}" data-is_seckill="{{ d[i].isSeckill }}" data-limit_type="{{ d[i].limit_type }}">
                    <div class="cartLeft {{# if(d[i].productParam.length > 0){ }}hasSpec{{# } }}">
                        <div class="name">{{ d[i].productName }}</div>
                        {{# if(d[i].productParam.length > 0){ }}
                            {{#
                    var tmpParam = [];
                    for(var j in d[i].productParam){
                    if(d[i].productParam[j].type == 'spec'){
                    tmpParam.push(d[i].productParam[j].name);
                    }else{
                    for(var k in d[i].productParam[j].data){
                    tmpParam.push(d[i].productParam[j].data[k].name);
                    }
                    }
                    }
                    var tmpParamStr = tmpParam.join(' ');
                    }}
                                <div class="spec" data-product_id="{{ i }}">{{ tmpParamStr }}</div>
                                {{# } }}
                    </div>
                    <div class="cartRight">
                        <div class="product_btn plus cart"></div>
                        <div class="product_btn number cart productNum-{{ i }}">{{ d[i].count }}</div>
                        <div class="product_btn min cart"></div>
                        <div class="price">{pigcms{$config['Currency_symbol']}{{ d[i].productPrice }}</div>
                    </div>
                </dd>
                {{#
            }
            if(packCharge > 0){
            }}
                    <dd>{{ nowShop.store.pack_alias }}&nbsp;<font color="red">{pigcms{$config['Currency_symbol']}<span id="packChargeCount">{{ packCharge.toFixed(2) }}</span></font>
                    </dd>
                    {{#
            }
            }}
                        <dd id="spell_tip"><a href="javasript:void(0);">商品如需要分开打包，请使用多人点单</a></dd>
        </dl>
    </script>
    <script>
        var firstMenuClick = false;

        var window_width = $(window).width();
        var window_height = $(window).height();


        $('body').width(window_width);
        $('body,.container').css({
            width: window_width,
            'min-height': window_height,
            'overflow-x': 'hidden'
        });




        function getTimeString(stime, ftime, item, index, id) {
            var currentTime = Date.now();
            var endTime = ftime * 1000; //php返回的时间戳是秒为单位
            var startTime = stime * 1000;
            var status;
            var timeString = "",
                remainMS = 0;
            if (startTime > currentTime) {
                remainMS = Math.floor((startTime - currentTime) / 1000) * 1000;
                timeString = '<p class="timer_' + index + '">距开始：' + formatDuring(remainMS) + '</p>';
                status = 1;
            } else if (endTime > currentTime && startTime < currentTime) {
                remainMS = Math.floor((endTime - currentTime) / 1000) * 1000;
                timeString = '<p class="timer_' + index + '">距结束：' + formatDuring(remainMS) + '</p>';
                status = 2;
            } else {
                timeString = '<p class="timer_' + index + '">已结束</p>';
                status = 3;
                var timer_info = 'timer_' + index;
                clearInterval(timer_info);
            }
            $('.al-time-count.timer_info_' + id).html(timeString);
        }

        function checkInterval(stime, ftime, item, index, id) {
            var timer_info = 'timer_' + index;
            timer_info = setInterval(function() {
                getTimeString(stime, ftime, item, index, id);
                if ($('.al-time-count.timer_info_' + id).html() === "已结束") {
                    clearInterval(timer_info)
                }
            }, 1000)
        }

        function formatDuring(mss) {
            var hours = parseInt((mss % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = parseInt((mss % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = (mss % (1000 * 60)) / 1000;
            if (mss <= 24 * 60 * 60 * 1000) {
                return hours + "小时" + minutes + "分钟" + seconds + "秒";
            } else {
                var days = parseInt(mss / (1000 * 60 * 60 * 24));
                return days + "天" + hours + "小时" + minutes + "分钟" + seconds + "秒";
            }
        }


        function showShopContent(nav) {
            if (nav == 'merchant') {
                $('#shopCatBar').hide();
                $('#shopMenuBar').show();
                if ($('#shopMerchantBox').data('isShow') != '1') {
                    $('#shopMerchantBox').data('isShow', '1');
                }
                $('#shopMerchantBox').show();
                pageLoadHides();
            } else if (nav == 'product') {
                $('#shopProductCart').show();
            }
        }

        function close_swiper() {
            $('.albumContainer').remove();
        }

        $(function() {

            if (motify.checkWeixinApp()) {
                $('a').click(function() {
                    var href = $(this).attr('href');
                    if (href.indexOf('/packapp/plat/pages/shop_new/shopDetail/shopDetail') > 0) {
                        wx.miniProgram.navigateTo({
                            url: href.split('/packapp/plat')[1]
                        });
                        return false;
                    }
                    if (href.indexOf('c=My&a=index') > 0) {
                        wx.miniProgram.navigateTo({
                            url: '/pages/nofooter/my_index'
                        });
                        return false;
                    }

                    <?php if ($_GET['page_from'] == 'store_qr_code') { ?>
                        if (href && href.substr(0, 3) != 'tel' && href.substr(0, 1) != '#' && href.substr(0, 10) != 'javascript') {
                            wx.miniProgram.navigateTo({
                                url: '/pages/webview/webview?webview_url=' + encodeURIComponent(href)
                            });
                            return false;
                        }
                    <?php } ?>
                });
            } else {
                $('a').click(function() {
                    <?php if ($_GET['page_from'] == 'store_qr_code') { ?>
                        if ($(this).attr('href').indexOf('/packapp/plat/pages/shop_new/shopDetail/shopDetail') > 0) {
                            location.href = $(this).attr('href') + ($(this).attr('href').indexOf('page_from=store_qr_code') == -1 ? "{pigcms{:($_GET['page_from'] == 'store_qr_code' ? '&page_from=store_qr_code' : '')}" : '');
                            return false;
                        }
                    <?php } ?>
                    if ($(this).attr('href').indexOf('c=My&a=index') > 0) {
                        location.href = '/packapp/plat/pages/nofooter/my_index';
                        return false;
                    }
                });
            }

            $('.photo ul').click(function() {
                var album_more = $(this).data('pics');
                var album_array = album_more.split(',');
                if (motify.checkWeixin()) {
                    wx.previewImage({
                        current: album_array[0],
                        urls: album_array
                    });
                } else {
                    var album_html = '<div class="albumContainer" style="display:block;">';
                    album_html += '<div class="swiper-container">';
                    album_html += '<div class="swiper-wrapper">';
                    $.each(album_array, function(i, item) {
                        album_html += '<div class="swiper-slide">';
                        album_html += '<img src="' + item + '"/>';
                        album_html += '</div>';
                    });
                    album_html += '</div>';
                    album_html += '<div class="swiper-pagination"></div><div class="swiper-close" onclick="close_swiper()">X</div>';
                    album_html += '</div>';
                    album_html += '</div>';
                    $('body').append(album_html);
                    mySwiper_big = $('.albumContainer .swiper-container').swiper({
                        pagination: '.albumContainer .swiper-pagination',
                        loop: true,
                        grabCursor: true,
                        paginationClickable: true
                    });
                }
            });

            /*页面点击事件*/
            $(document).on('click', '.link-url', function(e) {
                if (!$(e.target).hasClass('phone')) {
                    if (typeof(noAnimate) == "undefined") {
                        redirect($(this).data('url'), $(this).data('url-type'), ($(this).attr('target') ? $(this).attr('target') : '_self'));
                        return false;
                    } else {
                        window.location.href = $(this).data('url');
                    }
                }
            });


            /*电话按钮事件*/
            $(document).on('click', '.phone', function(event) {
                if ($(this).attr('data-phone')) {
                    var tmpPhone = $(this).attr('data-phone').split(' ');
                    var bg_height = $('body').height() > $(window).height() ? $('body').height() : $(window).height();
                    var msg_dom = '<div class="msg-bg" style="height:' + bg_height + 'px;"></div>';
                    msg_dom += '<div id="msg" class="msg-doc msg-option">';
                    msg_dom += '<div class="msg-bd">' + ($(this).data('phonetitle') ? $(this).data('phonetitle') : '拨打电话') + '</div>';
                    for (var i in tmpPhone) {
                        msg_dom += '		<div class="msg-option-btns"><a class="btn msg-btn" style="border-top: 1px solid #ccc;" href="tel:' + tmpPhone[i] + '">' + (tmpPhone.length == 1 && $(this).data('phonetip') ? $(this).data('phonetip') : tmpPhone[i]) + '</a></div>';
                    }
                    msg_dom += '		<button class="btn msg-btn-cancel" type="button"  style="border-top: 1px solid #ccc;color: #FF6634;">取消</button>';
                    msg_dom += '</div>';
                    $('body').append(msg_dom);
                }
                event.stopPropagation();
            });
            $(document).on('click', '.msg-btn-cancel', function() {
                $('.msg-doc,.msg-bg').remove();
            });




            $('#shopMenuBar li').click(function() {
                var tmpIndex = $(this).index();
                var tmpNav = $(this).data('nav');
                $(this).addClass('active').siblings().removeClass('active');
                // pageLoadTips({showBg:false});
                $('#shopContentBar').animate({
                    'margin-left': '-' + tmpIndex * window_width + 'px'
                }, function() {
                    showShopContent(tmpNav);
                });
            });


            $('.current').click(function() {
                $('.current').removeClass('get');
                $(this).addClass('get');
            })

            // 计算高度和宽度
            var w = $(window).width();
            console.log('w', w);
            var wh = w - 16;
            var hh = (2 / 3) * wh;

            $('.info_module .al-container.al-display-style-2 .al-main-image-size-style-2.al-main-img').css({
                'weight': wh + 'px',
                'height': wh + 'px'
            });

            if ($(".info_module .swiper-container .swiper-slide").size() > 0) {
                var myswiper = new Swiper('.info_module .swiper-container', {
                    pagination: '.info_module .swiper-container .swiper-pagination',
                    direction: 'horizontal',
                    paginationClickable: true,
                    autoplay: '3000',
                    autoplayDisableOnInteraction: false,
                    loop: true,
                    video: false
                });
            } else {
                $(".info_module .swiper-container").hide().remove()
            }



            $('.al-time-count time-count-down').each(function(index, val) {
                var stime = $(this).data('stime');
                var ftime = $(this).data('ftime');
                var id = $(this).data('id');
                getTimeString(stime, ftime, this, index, id);
                checkInterval(stime, ftime, this, index, id);
            });


            $(".mask2").height($(window).height());
            $(".dianhua").click(function() {
                $(".phone_content").show();
                $(".mask2").show();
            })
            $(".mask2,.phone_cancel").click(function() {
                $('.mask2').hide();
                $(".phone_content").hide();
            });

            if ($('.content_video').size() > 0) {
                $('.content_video').css('width', '100%').css('height', $(window).width() / 16 * 9);
            }

            $(document).on('click', '.phone', function(event) {
                if ($(this).attr('data-phone')) {
                    var tmpPhone = $(this).attr('data-phone').split(' ');
                    var msg_dom = '<div class="msg-bg"></div>';
                    msg_dom += '<div id="msg" class="msg-doc msg-option">';
                    msg_dom += '<div class="msg-bd">' + ($(this).data('phonetitle') ? $(this).data('phonetitle') : '拨打电话') + '</div>';
                    for (var i in tmpPhone) {
                        msg_dom += '<div class="msg-option-btns"><a class="btn msg-btn" href="tel:' + tmpPhone[i] + '">' + (tmpPhone.length == 1 && $(this).data('phonetip') ? $(this).data('phonetip') : tmpPhone[i]) + '</a></div>';
                    }
                    msg_dom += '     <button class="btn msg-btn-cancel" type="button">取消</button>';
                    msg_dom += '</div>';
                    $('body').append(msg_dom);
                }
                event.stopPropagation();
            });

            // 外卖模块点击事件
            $("#shopProductLeftBar2 dd span").click(function() {
                $(this).parents("dd").addClass("active").siblings("dd").removeClass("active");
                $(this).siblings("ul").find("li").removeClass("active").find(".p").hide().find('p').removeClass("active");
                $(this).parents("dd").find('li:first').addClass("active").find('div').show().find('p:first').addClass("active");
                showGoodsByGroupId($(this).data('sort_id'));
            });

        });






        function showGoodsByGroupId(group_id) {
            console.log('group_id', group_id);
            var goods_list = "{pigcms{:U('showGoodsByGroupId')}";
            $.ajax({
                type: "POST",
                url: goods_list,
                data: {
                    "group_id": group_id
                },
                dataType: "json",
                success: function(data) {
                    console.log('data_group_info', data);
                    $('#goods_group3 #shopProductRightBar2 dl').html(data.shop_html);
                },
                error: function(err) {
                    console.log('err', err);
                    // errorCallback&&errorCallback();
                }
            });
        }


        function userCollect(store_id) {
            console.log('store_id', store_id);
            var store_collection = "{pigcms{:U('store_collection')}";
            $.ajax({
                type: "POST",
                url: store_collection,
                data: {
                    "store_id": store_id
                },
                dataType: "json",
                success: function(data) {
                    console.log('data_253', data);
                    if (data.status != 0 && data.msg) {
                        layer.open({
                            content: data.msg,
                            skin: 'msg',
                            time: 2 //2秒后自动关闭
                        });
                        window.location.href = window.location.href;
                    } else if (data.url) {
                        window.location.href = data.url;
                    }

                    // console.log('successCallback', successCallback);
                    // successCallback&&successCallback(data);
                },
                error: function(err) {
                    console.log('err', err);
                    // errorCallback&&errorCallback();
                }
            });
        }
    </script>
    <script id="productTpl" type="text/html">
    {{# for(var i in d){ }}
        <div class="app-fields ui-sortable productRow cat-{{ nowIndex }} link-url link-shop" data-store_id="{{ d[i].id }}" data-url="{pigcms{:U('Shop/index')}#shop-{{ d[i].id }}" {{# if(d[i].is_close){ }}style="opacity:0.6;" {{# } }}>
            <div class="control-group product_dealcard">
                <div class="dealcard-img imgbox">
                    <img src="{{ d[i].image }}" alt="{{ d[i].name }}">
                    {{# if(d[i].is_close){ }}
                        <div class="closeTip">休息中</div>{{# } }}
                </div>
                <div class="dealcard-block-right" style="min-height:60px;">
                    <div class="brand">{{ d[i].name }}<em class="location-right">{{# if(user_long != '0'){ }}{{ d[i].range }}{{# } }}</em></div>
                    <div class="title {{# if(!d[i].delivery){ }}pick{{# } }}">
                        <span class="star">
                            {{#
                                    var tmpScore = parseFloat(d[i].star);
                                    if(tmpScore>0){
                                        for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
                                    }else{
                                }}
                                            <i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
                                            {{#
                                    }
                                }}
                        </span>
                        <span>已售{{ d[i].month_sale_count }}单</span>
                        {{# if(d[i].delivery){ }}
                            <em class="location-right">{{ d[i].delivery_time }} {{ d[i].delivery_time_type }}</em>
                            {{# }else{ }}
                                <em class="location-right">门店自提</em>
                                {{# } }}
                    </div>
                    {{# if(d[i].delivery){ }}
                        <div class="price">
                            <span>起送价 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_price }}</span><span class="delivery">{{# if(d[i].delivery_money==0){ }}免配送费 {{# }else{ }} 配送费 {pigcms{$config['Currency_symbol']}{{ d[i].delivery_money }} {{# } }}</span>
                            {{# if(d[i].delivery_system){ }}
                                <em class="location-right">{{ deliverName }}</em>
                                {{# }else{ }}
                                    <em class="location-right">商家配送</em>
                                    {{# } }}
                        </div>
                        {{# } }}
                            {{# if(d[i].coupon_count > 0){ }}
                                <div class="coupon {{# if(d[i].coupon_count > 2){ }}hasMore{{# } }}">
                                    <ul>
                                        {{# var tmpCouponList = parseCoupon(d[i].coupon_list,'array');  }}
                                            {{# if(tmpCouponList['invoice']){ }}
                                                <li><em class="merchant_invoice"></em>{{ tmpCouponList['invoice'] }}</li>
                                                {{# } }}
                                                    {{# if(tmpCouponList['discount']){ }}
                                                        <li><em class="merchant_discount"></em>{{ tmpCouponList['discount'] }}</li>
                                                        {{# } }}
                                                            {{# if(tmpCouponList['minus']){ }}
                                                                <li><em class="merchant_minus"></em>{{ tmpCouponList['minus'] }}</li>
                                                                {{# } }}
                                                                    {{# if(tmpCouponList['newuser']){ }}
                                                                        <li><em class="newuser"></em>{{ tmpCouponList['newuser'] }}</li>
                                                                        {{# } }}
                                                                            {{# if(tmpCouponList['delivery']){ }}
                                                                                <li><em class="delivery"></em>{{ tmpCouponList['delivery'] }}</li>
                                                                                {{# } }}
                                                                                    {{# if(tmpCouponList['system_minus']){ }}
                                                                                        <li><em class="system_minus"></em>{{ tmpCouponList['system_minus'] }}</li>
                                                                                        {{# } }}
                                                                                            {{# if(tmpCouponList['system_newuser']){ }}
                                                                                                <li><em class="system_newuser"></em>{{ tmpCouponList['system_newuser'] }}</li>
                                                                                                {{# } }}
                                                                                                    {{# if(tmpCouponList['sys_discount']){ }}
                                                                                                        <li><em class="merchant_discount"></em>{{ tmpCouponList['sys_discount'] }}</li>
                                                                                                        {{# } }}
                                                                                                            {{# if(tmpCouponList['mer_discount']){ }}
                                                                                                                <li><em class="merchant_discount"></em>{{ tmpCouponList['mer_discount'] }}</li>
                                                                                                                {{# } }}
                                    </ul>
                                    {{# if(d[i].coupon_count > 2){ }}
                                        <div class="more">{{ d[i].coupon_count }}个活动</div>
                                        {{# } }}
                                </div>
                                {{# } }}
                </div>
            </div>
        </div>
        {{# } }}
    </script>
    <script type="text/javascript">
        var like_page = 0;
        var sids = '';
        var nomore = false;
        var isLoading = false;
        $('.addCategoryBtn span').click(function() {
            if ($(this).hasClass('curr')) {
                return false;
            }
            if (!$(this).data('product')) {
                motify.log('该分类下暂无店铺');
                return false;
            }
            $(this).addClass('curr').siblings().removeClass('curr');
            sids = $(this).data('product');
            like_page = 0;
            nomore = false;
            $("#enddate").hide();
            $("#moress").show();
            $('.productBox').html('');

            nowIndex = $(this).index();
            if ($('.productRow.cat-' + nowIndex).size() > 0) {
                $('.productRow').hide();
                $('.productRow.cat-' + nowIndex).show();
                return false;
            }
            motify.log("加载店铺中...", 0, {
                show: true
            });

            $.post("{pigcms{:U('ajax_get_shop_by_ids')}" + '&page=' + like_page, {
                user_lat: user_lat,
                user_long: user_long,
                ids: $(this).data('product')
            }, function(result) {
                $('.productRow').hide();
                motify.clearLog();
                if (result.status == 1) {
                    laytpl($('#productTpl').html()).render(result.info, function(html) {
                        console.log(html);
                        $('.productBox').append(html);
                        $('.productRow.cat-' + nowIndex + ':last').css('margin-bottom', 0);
                        // if(!motify.checkMobile()){
                        //     $('.productRow').removeClass('link-url');
                        // }
                    });
                }
                if (result.info.length < page_count) {
                    $("#moress").hide();
                    $("#enddate").show();
                    nomore = true;
                }
            });
        });
        $('.addCategoryBtn span:eq(0)').trigger('click');
        $('#containers').css({
            'top': '0px'
        });
        $('#containers,#scroller').css({
            'position': 'static'
        });

        $(window).scroll(function() {
            console.log('1', $(document).scrollTop())
            console.log('2', $(document).height())
            console.log('3', $(window).height())
            if (isLoading == false && nomore == false && $(document).scrollTop() >= $(document).height() - $(window).height() - 150) {
                $("#moress").show();
                $("#enddate").hide();
                getRecommendList();
            }
        });

        var page_count = 10;

        function getRecommendList() {
            isLoading = true;
            like_page = like_page + page_count;
            $.post("{pigcms{:U('ajax_get_shop_by_ids')}" + '&page=' + like_page, {
                user_lat: user_lat,
                user_long: user_long,
                ids: sids
            }, function(result) {
                if (result.status == 1) {
                    laytpl($('#productTpl').html()).render(result.info, function(html) {
                        $('.productBox').append(html);
                        $('.productRow.cat-' + nowIndex + ':last').css('margin-bottom', 0);
                        // if(!motify.checkMobile()){
                        //     $('.productRow').removeClass('link-url');
                        // }
                    });
                    if (result.info.length < page_count) {
                        $("#moress").hide();
                        $("#enddate").show();
                        nomore = true;
                    }
                }
                isLoading = false;
            });
        }
    </script>
    <script type="text/javascript">
        window.shareData = {
            "moduleName": "Mall",
            "moduleID": "0",
            "imgUrl": "{pigcms{$now_store.all_pic.0}",
            "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Diypage/page',array('page_id'=>$now_page['page_id'],'page_from'=>$_GET['page_from']))}",
            "tTitle": "<notempty name='now_page.page_name'>{pigcms{$now_page.page_name}<else/>{pigcms{$now_store.name}</notempty>",
            "tContent": "<notempty name='now_page.page_desc'>{pigcms{$now_page.page_desc}<else/>{pigcms{$now_store.txt_info|str_replace=PHP_EOL,'',###}</notempty>"
        };
    </script>
    {pigcms{$shareScript}
    <php>$showScoreModal = false;</php>
    <include file="merchantScorePlugin" />
</body>

</html>