<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
</head>
<body>
    <p class="show-text" style="margin-top:20px;margin-bottom:20px;text-align:center;">{pigcms{:L_("请使用微信扫描二维码")}</p>
    <div style="text-align:center;">
        <img class="scan_code" src="{pigcms{$ticket}" style="width:300px;height:300px; <if condition="$sucess eq 0">display: none"</if> />
        <img class="success" src="/tpl/Merchant/default/static/images/success.png" style="width:100px;height:100px;margin-top: 22%;display: none"/>

        <if condition="$sucess eq 0">
            <!--   失败         -->
            <div class="error">{pigcms{$msg}</div>
            <div class="error"><p style="color: #ff0000">请关闭此窗口再打开重试。</p></div>
        </if>
    </div>
    <p id="login_status" style="margin-top:20px;display:none;text-align:center;"></p>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script>
        loop_time = setInterval("is_bind()", 1500);
        function is_bind()
        {
            $.post("{pigcms{:U('Config/has_bind')}", function(response){
                if (response.error_code == false) {
                    $('.show-text').text('{pigcms{:L_("绑定成功")}')
                    $('.success').show();
                    $('.scan_code').hide();
                    $('.error').hide();
                    clearInterval(loop_time);
                }
            }, 'json');
        }

    </script>
</body>
</html>