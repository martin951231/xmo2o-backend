<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>{pigcms{$title} | 物业管家</title>
        <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name='apple-touch-fullscreen' content='yes'>
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="format-detection" content="telephone=no">
        <meta name="format-detection" content="address=no">
        <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$config['site_url']}/tpl/Wap/pure/static/js/layer.m.js" charset="utf-8"></script>
    </head>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .contact-way-invitation-box {
            height: 100%;
            overflow: hidden;
        }
        .content-box {
            margin: 15px;
            border-top: 1px solid #ccc;
        }
        .item-box {
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
        .title-box {
            padding-top: 15px;
            text-align: center;
            font-weight: bolder;
            font-size: 18px;
        }
        .div-img-box {
            width: 150px;
            height: 150px;
            position: absolute;
            left: 50%;
            -webkit-transform: translateX(-50%);
            transform: translateX(-50%);
            top: -75px;
        }
        .title_img {
            width: 100%;
            height: 100%;
        }
    </style>
    <body>
    <div id="container">
        <div class="contact-way-invitation-box">
            <img src="{pigcms{$housekeeper_info['housekeeper_url']}" width="100%">
        </div>
        <div class="choose-user-bind-box" style="display: none;">
            <div>
                <div class="div-img-box" style="width: 150px;height: 150px;">
                    <div class="title_img" style="background-image: url('{pigcms{$config.site_url}/packapp/plat/static/img/bg1.323da84d.png');background-position: 0% 0%;background-size: 100% 100%;background-repeat: no-repeat;"></div>

                </div>
                <div class="title-box">请选择您的房屋</div>
                <div class="content-box">
                    <volist name="user_bind" id="item">
                        <div class="item-box" data-url="{pigcms{$item.url}">{pigcms{$item.address}</div>
                    </volist>
                </div>
            </div>
        </div>
    </div>
<!--    {pigcms{$hideScript}-->
    <script type="text/javascript">
        var is_choose = "{pigcms{$is_choose}";
        var redirect_url = "{pigcms{$redirect_url}";
        if (2==is_choose) {
            layer_choose();
        }
        if (redirect_url) {
            layer.open({
                type: 2,
                shadeClose:false,
                content: '您不属于当前房间!正在前往绑定当前房间...'
            });
            setTimeout(function () {
                window.location.href = redirect_url;
            },1900)
        }
        function layer_choose() {
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                shadeClose: true,
                skin: 'yourclass',
                content: $('.choose-user-bind-box').html()
            });
        }
        $('.item-box').click(function () {
            var url = $(this).data('url');
            if (url) {
                window.location.href = url;
            }
        })
    </script>
    </body>
</html>