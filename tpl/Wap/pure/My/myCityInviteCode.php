<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>我的商家入驻邀请码</title>
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
</head>
<body>
<div class="my-account" style="background: #FFFFFF;width: 90%;margin-left: 5%;margin-top:1.5rem;padding-top:5.5rem;padding-bottom:5.5rem;color: #ED4E54;line-height:2.5rem;font-size: 2.5rem;text-align: center;" id="code">
    {pigcms{$cityInviteCode}
</div>

<div onclick="copy()" style="background: #ED4E54;width: 90%;margin-left: 5%;margin-top:1.5rem;padding-top:0.6rem;padding-bottom:0.6rem;color: #FFFFFF;font-size: 1.25rem;text-align: center;border-radius: 5px">
    一键复制
</div>
<p style="margin:auto 5%;color: #ED4E54;margin-top:1.5rem">您可将邀请码分享给商家，商家在入驻平台时可以填写此邀请码后跟您绑定推荐关系；绑定关系后商家每收入一笔，您即可获得一定比例的佣金。</p>


<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/common_wap.js"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
{pigcms{$hideSript}

</body>
<script type="text/javascript">
    function copy() {
        var Url2 = document.getElementById("code").innerText;
        copyBtn(Url2);
    }


    function copyBtn(message) {
        var input = document.createElement("input");
        input.value = message;
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, input.value.length), document.execCommand('Copy');
        document.body.removeChild(input);
        my_alert('复制成功',1500)
    }

    function my_alert(text, delay=1500) {
        var informDiv = $('<div style="padding:10px 30px; background:rgba(0,0,0,0.6); color:#fff; font-size:13px; line-height:20px; position:fixed; left:50%; top:0%; z-index:9999; border-radius:10px; opacity:0; -webkit-transform:translateX(-50%); -moz-transform:translateX(-50%); -ms-transform:translateX(-50%); transform:translateX(-50%);">'+text+'</div>');
        informDiv.appendTo($('body')).css({"top": "45%", "opacity": "1"})
        setTimeout(function () {
            informDiv.remove();
        }, delay)
    }
</script>
</html>
