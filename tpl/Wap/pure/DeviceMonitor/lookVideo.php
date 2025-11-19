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
<div class="main-content" style="width: 100%;height: 100%;text-align: center;">
    <video id="videoElement"  webkit-playsinline="webkit-playsinline" 
        playsinline="playsinline" style="width: 100%;height: 100%;" controls="controls"></video>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script src="{pigcms{$flvMinJs}"></script>
<script>
    let bodyHeight = window.screen.height;
    $('body').css('height', bodyHeight-46);
    /**
     * @description flv视频播放
     */
    if (flvjs.isSupported()) {
        var videoElement = document.getElementById('videoElement');
        var flvPlayer = flvjs.createPlayer({
            type: 'flv',
            url:"{pigcms{$monitor['videoTxt']}"
        });
        flvPlayer.attachMediaElement(videoElement);
        flvPlayer.load();
        flvPlayer.play();
    }

 
    function isIOS() {
        return /iPad|iPhone/.test(navigator.userAgent) && !window.MSStream;
    }

    function initializeVideoPlayer() {
        const videoElement = document.getElementById('videoElement');
        if (isIOS()) {
            // iOS设备使用HLS格式
            const hlsUrl = "{pigcms{$monitor['videoTxt']}}"; // 替换为HLS视频流URL
            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(hlsUrl);
                hls.attachMedia(videoElement);
                hls.on(Hls.Events.MANIFEST_PARSED, function () {
                    videoElement.play();
                });
            } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
                videoElement.src = hlsUrl;
                videoElement.addEventListener('loadedmetadata', function () {
                    videoElement.play();
                });
            }
        } else {
            // 其他设备使用FLV格式
            if (flvjs.isSupported()) {
                const flvPlayer = flvjs.createPlayer({
                    type: 'flv',
                    url: "{pigcms{$monitor['videoTxt']}}"
                });
                flvPlayer.attachMediaElement(videoElement);
                flvPlayer.load();
                flvPlayer.play();
            }
        }
    }

    // initializeVideoPlayer();
</script>
</html>