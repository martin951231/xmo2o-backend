<!DOCTYPE html>
<html lang="zh-CN" class="index_page">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
		<meta name="location" content="" /> 
		<meta charset="utf-8" /> 
		<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width" /> 
		<meta name="format-detection" content="telephone=no" /> 
		<meta name="format-detection" content="email=no" /> 
		<meta name="format-detection" content="address=no;" /> 
		<meta name="apple-mobile-web-app-capable" content="yes" /> 
		<meta name="apple-mobile-web-app-status-bar-style" content="default" /> 
		<meta name="keywords" content="" /> 
		<meta name="description" content="" /> 
		<title>{pigcms{$pageTitle}</title> 
		<link rel="apple-touch-icon-precomposed" href="" /> 
		<link rel="apple-touch-startup-image" href="" /> 
		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<style type="text/css">
		body{padding:26px 12px;background:#EBEBEB;margin:0;font-size:14px;line-height:1.5}
		.container{background:white;border-radius:5px;overflow:hidden;}
		.container .imgRow{background-size:100% 100%;position:relative;}
		.container .imgRow .textBg{position: absolute;top: 0;left: 0;width: 100%;height: 60%;background: -webkit-linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.0));}
		.container .imgRow .text{padding-left:15px;padding-top:10px;color:white;font-size:20px;}
		.container .txtRow{padding:0 15px;}
		.container .txtRow .textp{padding:15px 0;float:left;border-bottom:1px solid #F3F3F3;}
		.container .txtRow:last-child .textp{border-bottom:none;}
		.container .txtRow .text{height:50px;display:flex;align-items:center;align-content:center;overflow:hidden;}
		.container .txtRow .img{float:right;margin-top:15px;}
		.container .txtRow .img img{width:50px;height:50px;}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="imgRow" onclick="location.href='{pigcms{$returnArr.0.url}';" style="background-image:url({pigcms{:replace_file_domain($returnArr[0]['pic'])})">
				<div class="textBg">
					<div class="text">{pigcms{$returnArr.0.title}</div>
				</div>
			</div>
			<volist name="returnArr" id="vo" offset="1">
				<div class="txtRow" onclick="location.href='{pigcms{$vo.url}';">
					<div class="textp"><div class="text"><div>{pigcms{$vo.title|msubstr=###,0,30}</div></div></div>
					<div class="img"><img src="{pigcms{:replace_file_domain($vo['pic'])}"/></div>
				</div>
			</volist>
		</div>
		<script>
			$('.imgRow').css('background-size',($(window).width()-24) + 'px ' + ($(window).width()-30)*500/900 + 'px');
			$('.imgRow').height(($(window).width()-24)*246/580);
			
			$('.container .txtRow .text').width($('.container').width() - 30 - 50 - 20);
			$('.container .txtRow .text').each(function(i,item){
				if($(item).height() < 25){
					
				}
			});
		</script>
	</body>
</html>
 