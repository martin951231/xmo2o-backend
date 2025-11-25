<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta charset="utf-8">
    <title>添加微信</title>
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
</head>

<body style="margin:0px;padding:0px">
	<if condition="$activity['qrcode_style'] and $activity['style_tpl_pic']">
		<div id="box" style="width: 100%;height: 100%;position: absolute;background-image: url('{pigcms{$activity.style_tpl_pic}')">
			<img style="width:100px;left: 20px;bottom:20px;position: fixed" src="{pigcms{$activity.wx_qrcode}"/>
		</div>
	<else/>
		<div id="box" style="width:200px;height:200px;position:fixed;left:50%;top:0;margin-left:-100px;margin-top:30%;">
			<img style="width:100%;" src="{pigcms{$activity.wx_qrcode}"/>
			<p style="text-align:center;">长按识别添加微信领取优惠</p>
		</div>
	</if>
</body>
</html>