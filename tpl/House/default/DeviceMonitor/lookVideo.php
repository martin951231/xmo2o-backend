<include file="Public:header"/>
<div class="main-content" style="width: 100%;height: 100%;text-align: center;">
    <video id="videoElement" style="width: 60%;height: 100%;" controls="controls"></video>
    <div style="margin:10px;padding:15px;">
        <span>
            <button class="btn btn-success repairVideo" type="button">点击修复监控</button>
        </span>
    </div>
</div>
<script src="{pigcms{$flvMinJs}"></script>
<script>
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
    //修复
    $('.repairVideo').click(function (){
        let camera_id = "{pigcms{$_GET['camera_id']}";
        layer.open({
            content: '确认是否进行监控修复！',
            shadeClose: false,
            closeBtn: 0
            ,btn: ['确定', '取消']
            ,yes:function(index){
                layer.closeAll();
                $.ajax({
                    type : "POST",
                    url : "{pigcms{:U('repairVideo')}",
                    data : {"camera_id":camera_id},
                    dataType:'json',
                    success : function(res) {
                        layer.msg('修复已经执行。如果监控未回复，请自行刷新')
                    }
                });
            }
        });
    })
</script>
<include file="Public:footer"/>