<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>企业微信登录</title>
        <style>
            .success-title {
                color: #4b0;
                font-size: 18px;
                line-height: 28px;
            }
        </style>
	</head>
	<body>
        <div style="text-align: center;">
            <h4 class="success-title">跳转登录中...</h4>
        </div>
        <button style="display: none;" onclick="link('{pigcms{$info}')">登录</button>
        <script src="{pigcms{$static_public}js/jquery.min.js"></script>
        <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
        <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                link('{pigcms{$info}');
            });
            function link(randomNumber){
                $.ajax({
                    url: '{pigcms{$config.site_url}/index.php?g=Index&c=Login&a=login_result&randomNumber='+randomNumber,
                    type: 'GET',
                    dataType: 'json',
                    success:function(date){
                        if(date.errcode!=0){
                            $('.validate-info').html('<i class="tip-status tip-status--opinfo"></i>'+date.msg).css('visibility','visible');
                        }else{
                            location.href=date.url;
                        }
                    }
                });
            }
        </script>
	</body>
</html>