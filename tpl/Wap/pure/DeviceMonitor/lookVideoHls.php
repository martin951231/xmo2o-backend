<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta charset="utf-8">
    <title>{pigcms{$monitor['channelName']}</title>
</head>
<link href="{pigcms{$videoMinCss}" rel="stylesheet">
<style>
    .headerBox {
        width: 100%;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1004;
    }
    .headerBox {
        height: 45px;
        line-height: 45px;
        background: #06c1ae;
        color: #ffffff;
        font-size: 20px;
        text-align: center;
    }
    .headerBox .back {
        padding-left: 10px;
        text-align: left;
        line-height: 45px;
        display: block;
        color: #fff;
        opacity: 1;
        font-weight: normal;
        text-shadow: none;
        position: absolute;
        width: 36px;
        height: 45px;
        font-size: 16px;
        overflow: hidden;
        text-decoration: none;
        margin-left: 25px;
    }
    .headerBox #backBtn:after {
        display: block;
        content: "";
        border-top: 2px solid white;
        border-left: 2px solid white;
        width: 12px;
        height: 12px;
        -webkit-transform: rotate(315deg);
        background-color: transparent;
        position: absolute;
        top: 16px;
        left: 16px;
    }
</style>
<body>
<if condition="!$is_weixin_browser && !$is_app_browser">

    <div class="headerBox">
        <div class="title_msg">
            <div id="backBtn"></div>
            <a href="javascript:void(0);" id="return_index" onclick="return window.history.go(-1);" class="back">返回</a>
        </div>
    </div>
</if>
<video id="videoElement" autoplay="autoplay" class="video-js vjs-default-skin vjs-big-play-centered"  controls="controls"  webkit-playsinline='true' x-webkit-airplay="true" x5-video-player-type="h5" x5-video-player-fullscreen="true"  playsinline='true'   preload="auto" data-setup="{}">
    <source id="source" src="{pigcms{$monitor['videoTxt']}" type="application/x-mpegURL" />
</video>
</body>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script src="{pigcms{$videoMinJs}"></script>
<script>
    let bodyHeight = window.screen.height;
    $('body').css('height', bodyHeight-46);
    /**
     * @description hls视频播放
     */
    var myVideo = videojs('videoElement', {
        loop: true,
        controls: true,
        preload: 'auto',
        autoplay: true
    })
</script>
</html>