<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>微信公众平台源码,微信机器人源码,微信自动回复源码 PigCms多用户微信营销系统</title>
<meta http-equiv="MSThemeCompatible" content="Yes" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
<link href="/tpl/Merchant/default/static/css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/cymain.css" />
<link rel="stylesheet" href="/tpl/Static/css/bootstrap.min.css">
	<!--<link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/3.3.7/css/bootstrap.min.css">-->
<script src="/static/js/jquery-1.9.1.min.js" type="text/javascript"></script>

<script src="/tpl/Static/js/bootstrap.min.js" type="text/javascript"></script>
	<!--<script src="https://cdn.staticfile.org/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>-->
<script src="/tpl/Merchant/default/static/js/common.js" type="text/javascript"></script>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<style>
body{line-height:180%;}
ul.modules li{padding:4px 10px;margin:4px;background:#efefef;float:left;width:32%;}
ul.modules li div.mleft{float:left;}
ul.modules li div.mright{float:right;text-align:right;}
</style>
</head>
<body style="background:#fff;padding:20px 20px;">
<div class="panel-group" id="accordion">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion"
				   href="#collapseOne">
					小区功能库
				</a>
			</h4>
		</div>
		<div id="collapseOne" class="panel-collapse collapse in">
			<div class="panel-body">
				<div style="background:#fefbe4;border:1px solid #f3ecb9;color:#993300;padding:10px;margin-bottom:5px;">使用方法：点击“选中”直接返回对应模块外链代码，或者点击“详细”选择具体的内容外链</div>
				<h4>请选择模块：</h4>
				<ul class="modules">
					<volist name="modules" id="m">
						<?php if (!intval($_GET['iskeyword']) || (intval($_GET['iskeyword'])&&$m['askeyword'])){?>
							<li>
								<div class="mleft">{pigcms{$m.name}</div>
								<div class="mright">
									<if condition="$m['sub']"><a href="<?php if (!$m['linkurl']){?>?g=System&c=Link&a={pigcms{$m.module}&iskeyword=<?php echo intval($_GET['iskeyword']);?><?php }else{echo $m['linkurl'];}?>">详细</a></if>
									<if condition="$m['canselected']"><a href="###" onclick="returnHomepage('<?php if (!intval($_GET['iskeyword'])){?>{pigcms{$m.linkcode}<?php }else{?>{pigcms{$m.keyword}<?php }?>')" style="margin-left:14px;">选中</a></if>
								</div>
								<div style="clear:both"></div>
							</li>
						<?php }?>
					</volist>
					<div style="clear:both"></div>
				</ul>
			</div>
		</div>
	</div>
	<div class="panel panel-success">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion"
				   href="#collapseTwo">
					系统后台功能库
				</a>
			</h4>
		</div>
		<div id="collapseTwo" class="panel-collapse collapse in">
			<div class="panel-body">
				<div style="background:#fefbe4;border:1px solid #f3ecb9;color:#993300;padding:10px;margin-bottom:5px;">使用方法：点击“选中”直接返回对应模块外链代码，或者点击“详细”选择具体的内容外链</div>
				<h4>请选择模块：</h4>
				<ul class="modules">
					<volist name="modules_admin" id="m">
						<?php if (!intval($_GET['iskeyword']) || (intval($_GET['iskeyword'])&&$m['askeyword'])){?>
							<li>
								<div class="mleft">{pigcms{$m.name}</div>
								<div class="mright">
									<if condition="$m['sub']"><a href="<?php if (!$m['linkurl']){?>?g=System&c=Link&a={pigcms{$m.module}&iskeyword=<?php echo intval($_GET['iskeyword']);?><?php }else{echo $m['linkurl'];}?>">详细</a></if>
									<if condition="$m['canselected']"><a href="###" onclick="returnHomepage('<?php if (!intval($_GET['iskeyword'])){?>{pigcms{$m.linkcode}<?php }else{?>{pigcms{$m.keyword}<?php }?>')" style="margin-left:14px;">选中</a></if>
								</div>
								<div style="clear:both"></div>
							</li>
						<?php }?>
					</volist>
					<div style="clear:both"></div>
				</ul>
			</div>
		</div>
	</div>
	<if condition="$modules_merchant">
	<div class="panel panel-info">
		<div class="panel-heading">
			<h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion"
				   href="#collapseThree">
					商家功能库
				</a>
			</h4>
		</div>
		<div id="collapseThree" class="panel-collapse collapse in">
			<div class="panel-body">
				<div class="panel-body">
					<div style="background:#fefbe4;border:1px solid #f3ecb9;color:#993300;padding:10px;margin-bottom:5px;">使用方法：点击“选中”直接返回对应模块外链代码，或者点击“详细”选择具体的内容外链</div>
					<ul class="modules">
						<volist name="modules_merchant" id="m">
							<?php if (!intval($_GET['iskeyword']) || (intval($_GET['iskeyword'])&&$m['askeyword'])){?>
								<li>
									<div class="mleft">{pigcms{$m.name}</div>
									<div class="mright">
										<if condition="$m['sub']"><a href="<?php if (!$m['linkurl']){?>?g=System&c=Link&a={pigcms{$m.module}&iskeyword=<?php echo intval($_GET['iskeyword']);?><?php }else{echo $m['linkurl'];}?>">详细</a></if>
										<if condition="$m['canselected']"><a href="###" onclick="returnHomepage('<?php if (!intval($_GET['iskeyword'])){?>{pigcms{$m.linkcode}<?php }else{?>{pigcms{$m.keyword}<?php }?>')" style="margin-left:14px;">选中</a></if>
									</div>
									<div style="clear:both"></div>
								</li>
							<?php }?>
						</volist>
						<div style="clear:both"></div>
					</ul>
				</div>
			</div>
		</div>
	</div>
	</if>

</div>
<script type="text/javascript">

	$(function () { $('#collapseTwo').collapse('hide')});

	$(function () { $('#collapseOne').collapse('toggle')});
	$(function () { $('#collapseThree').collapse('hide')});
</script>

<script>
var domid=art.dialog.data('domid');
// 返回数据到主页面
function returnHomepage(url){
	var origin = artDialog.open.origin;
	var dom = origin.document.getElementById(domid);
	
	dom.value=url;
	setTimeout("art.dialog.close()", 100 )
}
</script>
</body>
</html>