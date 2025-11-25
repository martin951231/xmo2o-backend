<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta charset="utf-8"/>
	<title>查看二维码</title>
	<style>
		*{margin:0;padding:0;}
		img{
			width:250px;
			height:250px;
		}
		.box{
			padding-top:20px;
			text-align:center;
		}
		div.item{
			width:32%;
			display:inline-block;
			overflow:hidden;
			text-align:center;
		}
	</style>
</head>
<body>
	<div class="box">
        <if condition="$weixin_url">
            <div class="item">
                <img src="{pigcms{$weixin_url}"/>
                <p>微信公众号二维码</p>
            </div>
        </if>
		<if condition="$wxapp_url">
			<div class="item">
				<img src="{pigcms{$wxapp_url}"/>
				<p>小程序二维码</p>
			</div>
		</if>
        <if condition="$app_url">
            <div class="item">
                <img src="{pigcms{$app_url}"/>
                <p>APP下载二维码</p>
            </div>
        </if>
	</div>
</body>
</html>