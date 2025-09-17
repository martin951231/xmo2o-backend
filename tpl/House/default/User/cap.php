<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="stylesheet" href="{pigcms{$static_path}/layer/css/layui.css"  media="all">
    <script src="{pigcms{$static_path}/propery_group/js/jquery-2.1.0.min.js"></script>
    <title>调用移动端摄像头</title>
    <style>
        .camera-btn {
            background: none repeat scroll 0 0 #00B7EE;
            /*border-radius: 12px;*/
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            color: #FFFFFF;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            height: 33px;
            line-height: 33px;
            min-width: 80px;
            /*margin: 0 auto 0px;*/
            overflow: hidden;
            transition: background 0.2s;
            -moz-transition: background 0.2s;
            -webkit-transition: background 0.2s;
            -o-transition: background 0.2s;
            margin-top: 20px;
        }
        .canvas-sty{
            margin-top: -305px;
        }
    </style>
</head>
<body>
<div class="box1" style="text-align: center">
    <div>
        <video id="video" width="450" height="300" autoplay="autoplay" style="border: 1px solid #ccc;margin-top: 10px;"></video>
    </div>
    <div class="canvas-sty" style="display: block">
        <canvas id="canvas" width="450" height="300" style="border: 1px solid #ddd;"></canvas>
    </div>
    <div id="keysR">
<!--        <a class="camera-btn" onclick="getMedias()">开启摄像头</a>-->
        <a class="camera-btn" id="camera" onclick="takePhoto()">拍照</a>
        <a class="camera-btn" id="again" onclick="againTakePhoto()" style="display: none">重新拍</a>
        &nbsp;&nbsp;<a class="camera-btn" onclick="ajasRefer()">确定</a>
    </div>
    <div id="prompt" style="display: none">
    </div>
</div>
<script>
    let base_img = '';
    let pigcms_id = "{pigcms{$pigcms_id}";
    window.onload = getMedias;
    function getMedias() {
        let constraints = {
            video: {
                width: 450,
                height: 300
            },
            audio: true
        };
        //获得video摄像头区域
        let video = document.getElementById("video");
        // 这里介绍新的方法，返回一个 Promise对象
        // 这个Promise对象返回成功后的回调函数带一个 MediaStream 对象作为其参数
        // then()是Promise对象里的方法
        // then()方法是异步执行，当then()前的方法执行完后再执行then()内部的程序
        // 避免数据没有获取到、
        if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia(constraints)){
            $('#keysR').attr('style','display:none');
            $('#prompt').attr('style','display:');
            let htmls = '<p style="font-size: 16px;color: red">摄像头服务异常，请联系客服人员</p>';
            $('#prompt').append(htmls);
        }
        let promise = navigator.mediaDevices.getUserMedia(constraints);

        // 成功调用
        promise.then(function (MediaStream) {
            /* 使用这个MediaStream */
            video.srcObject = MediaStream;
            video.play();
            //console.log(MediaStream); // 对象
        })
        // 失败调用
        promise.catch(function (err) {
            /* 处理error */
            console.log(err); // 拒签
            if( (err.constraintName == null && err.message == null) || err)
            {
                $('#keysR').attr('style','display:none');
                $('#prompt').attr('style','display:');
                let htmls = '<p style="font-size: 16px;color: red">请连接摄像头</p>';
                $('#prompt').append(htmls);
            }
        });
    }
    function takePhoto() {
        $('.canvas-sty').attr('style','display:block');
        $('#camera').attr('style','display:none');
        $('#again').attr('style','display:inline-block;');
        //获得Canvas对象
        let video = document.getElementById("video");
        let canvas = document.getElementById("canvas");
        let ctx = canvas.getContext('2d');
        console.log(video);
        ctx.drawImage(video, 0, 0, 450, 300);
        var strDataURI = canvas.toDataURL('"image/jpeg"');
        console.log(strDataURI);
        base_img = strDataURI;
    }
    function againTakePhoto()
    {
        $('.canvas-sty').attr('style','display:none');
        $('.canvas-sty').attr('style','display:none');
        $('#camera').attr('style','display:inline-block;');
        $('#again').attr('style','display:none');
    }
    function ajasRefer() {
        var formData = new FormData();
        formData.append("base", base_img);
        formData.append("pigcms_id", pigcms_id);
        $.ajax({
            url: "{pigcms{:U('User/add_face_img')}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType:'json',
            success: function (res) {
                console.log(res);
                if(res.error == 2)
                {
                    parent.ChooseAdidValues(base_img,res.data.id); //这是父页面函数
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                }
            }
        });
    }
</script>
</body>
</html>
