<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{pigcms{$camera_device['camera_name']|default='天眼监控'}</title>
    <style>
        body {
            padding: 0;
        }
        html, body {
            padding: 0;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        #player {
            width: 1000px;                  /*播放容器的宽和高设定*/
            height: auto;
            object-fit: fill;
        }
        .header{
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1004;
            height: 45px;
            line-height: 45px;
            background: #000000;
            color: #ffffff;
            font-size: 20px;
            text-align: center;
            position: relative;
            z-index: 9;
        }
        .header .back {
            background-position: -57px -322px;
            padding-left: 10px;
            text-align: right;
            line-height: 45px;
            display: block;
            color: #fff;
            opacity: 1;
            font-weight: normal;
            text-shadow: none;
        }
        .header .back{
            position: absolute;
            width: 60px;
            height: 45px;
            top: 0;
            left: 0px;
            font-size: 16px;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div id="app">
    <div class="header" <if condition="!$_GET['from'] && $_GET['from']!='user'">style="display:none;"</if>>
    <a href="javascript:void(0);" onclick="return window.history.go(-1);" class="back"><&nbsp;返回</a>
</div>
<video id="player" src="" controls preload autoplay muted></video>
<input id="lookUrl" value="{pigcms{$camera_device['look_url']}" style="display: none;">
</div>


<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}iSecureCenter/hls/hls.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}iSecureCenter/js/flv.min.js"></script>
<script> //播放m3u8视频
    let clientHeightVal1;
    var extType='';

    <if condition="!empty($extType)">
          extType="{pigcms{$extType}";
    </if>

    const fromVal = "{pigcms{$_GET['from']}";
    const clientWidthVal = document.documentElement.clientWidth;
    if (fromVal && fromVal=='user') {
        clientHeightVal1 = document.documentElement.clientHeight - 150;
    } else {
        clientHeightVal1 = document.documentElement.clientHeight - 5;
    }
    $('#player').css({width:clientWidthVal,height:'auto'});
    setTimeout(function(){
        var src = $('#lookUrl').val();
        loadVideo(src);
    },100)
    function loadVideo(vedio_url) {
        var video = document.getElementById('player');
        if (Hls.isSupported() && extType!='flv') {
            var hls = new Hls();
            hls.loadSource(vedio_url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                console.log('play---1');
                video.play();
            });
        }else if(flvjs.isSupported() && extType=='flv'){
            var videoElement = document.getElementById('player')
            var flvPlayer = flvjs.createPlayer({
                type: 'flv',
                isLive: true,
                hasAudio: false,
                url: vedio_url
            });
            if(!flvPlayer) {
                   return false;
             }
            console.log('vedio_url=>', vedio_url)
            flvPlayer.attachMediaElement(videoElement)
            flvPlayer.load()
            flvPlayer.play()
            // 报错重连
            
            flvPlayer.on(flvjs.Events.ERROR, (errType, errDetail) => {
                console.log('errorType:', errType)
                console.log('errorDetail:', errDetail)
                if (flvPlayer) {
                    flvPlayer.pause()
                    flvPlayer.unload()
                    flvPlayer.detachMediaElement()
                    flvPlayer.destroy()
                    flvPlayer = null
                }
            })
            
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = vedio_url;
            video.addEventListener('loadedmetadata', function() {
                console.log('play---2');
                video.play();
            });
        }
    }
</script>
</body>
</html>