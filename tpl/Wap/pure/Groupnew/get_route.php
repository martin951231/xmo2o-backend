<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>{pigcms{$config.group_index_name|default='同城优选'}</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width, viewport-fit=cover"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
</head>
<body>
    <iframe id="frame_src" style="width:100%;height:100%;border:none;"></iframe>
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script src="{pigcms{$static_path}js/common_wap.js"></script>
    <script>
        $(window).resize(function(){
            window.location.reload();
        });
        $('#frame_src').height($(window).height());
        var url = "{pigcms{$url}";
        console.log('url',url);
        $('#frame_src').attr('src',url);

    </script>
</body>
</html>