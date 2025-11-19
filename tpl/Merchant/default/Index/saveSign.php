<include file="Public:header"/>
    <link rel="stylesheet" href="https://www.jq22.com/jquery/bootstrap-4.2.1.css">
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        html,body {
            width: 100%;
            height: 100%;
            text-align: center;
        }
        canvas {
            max-width: 100%;
            border: 2px dotted #ccc;
        }
    </style>
<!--[if lt IE 7]>
<p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
<![endif]-->
<script type="text/javascript" src="{pigcms{$static_path}js/sign_panel.js"></script>

<script>

    function GetRequest() {
        var url = location.search;
        var theRequest = {};
        if (url.indexOf("?") != -1) {
            var str = url.substr(1);
            strs = str.split("&");
            for (var i = 0; i < strs.length; i++) {
                theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
            }
        }
        return theRequest;
    }

    let width = GetRequest().canvasWidth?GetRequest().canvasWidth/2:400;
    let height = GetRequest().canvasHeight?GetRequest().canvasHeight/2:400;
    //初始化
    var sign = new Draw( {
        // canvas:document.getElementById('canvas'),
        lineWidth: 5, // 线条宽度
        width: width, // canvas 宽
        height: height, //canvas 高
        strokeStyle: '#333333' // 线条颜色
    } );

    window.onload = function () {
        // 点击输出图片
        document.querySelector( '.ouput' ).onclick = function () {
            if(sign.state.undopath.length < 1){
                layer.alert("请签名后再提交");
                return false;
            }

            $.post('{pigcms{:U("Index/saveSign")}', {sign_name_base64: sign.ouput()}, function (data) {
                layer.alert(data.info);
                if (data.status == 1) {
                    setTimeout(function () {
                        var index = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(index);
                        window.parent.location.reload();
                    }, 3000)
                }
            })
        }
        // 点击清除
        document.querySelector( '.clear' ).onclick = function () {
            sign.clear();
        }
        // 点击撤销
        /*document.querySelector( '.undo' ).onclick = function () {
            if ( sign.state.undopath.length > 0 ) {
                sign.undo();
            } else {
                console.log( '还没有签名' );
            }
        }*/
    }
</script>
<div class="buttons">
    <button type="button" class="btn btn-primary ouput">完成</button>
    <!--    <button type="button" class="btn btn-light undo">撤销</button>-->
    <button type="button" class="btn btn-light clear">重写</button>
</div>
</body>

</html>