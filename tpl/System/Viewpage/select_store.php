<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>选择店铺</title>
<meta http-equiv="MSThemeCompatible" content="Yes" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
<link href="/tpl/Merchant/default/static/css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/cymain.css" />

<script src="/tpl/Merchant/default/static/js/common.js" type="text/javascript"></script>
<script src="/tpl/Merchant/default/static/js/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<style>
body{line-height:180%;}
ul.modules li{padding:4px 10px;margin:4px;background:#efefef;float:left;width:27%;}
ul.modules li div.mleft{float:left;width:40%}
ul.modules li div.mright{float:right;width:55%;text-align:right;}
</style>
</head>
<body style="background:#fff;padding:20px 20px;">

	<form action="#" method="post">
		查询商品：<input type="text" placeholder="请输入店铺名称" value="" class="px" name="search">
		<button class="btnGrayS" style="height: 29px;">搜索</button>
		<div class="tips" id="tips" style="color:red; float: right;"></div>
	</form>
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
	<thead>
		<tr>
			<th>店铺名称</th>
			<th style=" width:80px;">
				操作 <span class="tooltips" ><span>
				<p>点击“选中”即可</p>
				</span></span>
			</th>
		</tr>
	</thead>
	<volist name="list" id="m">
		<tr class="user" data-store_id="{pigcms{$m.store_id}" data-mer_id="{pigcms{$m.mer_id}">
			<td>{pigcms{$m.name}</td>
			<td class="norightborder">
				<a href="###" onclick="returnHomepage('{pigcms{$m.store_id}','{pigcms{$m.mer_id}','{pigcms{$m.name}')">选中</a>
			</td>
		</tr>
	</volist>
</table>
<div class="footactions" style="padding-left:10px">
  <div class="pages">{pigcms{$page}</div>
</div>
<script>
var domid=art.dialog.data('domid');
var origin = artDialog.open.origin;
var name_div = origin.document.getElementById('goods_tags');
// 返回数据到主页面
function returnHomepage(store_id,mer_id,name){
	var dom = origin.document.getElementById(domid);
	setTimeout("art.dialog.close()", 100 )
	var a={};
	a.store_id = store_id;
	a.mer_id = mer_id;
	a.name = name;
	addTag(a)
	
}

function addTag(obj) {
	var tag = obj.name;
	if (tag != '') {
		var i = 0;
		var tag_arr = $(name_div).children(".goods_tags");
		
		$(name_div).find('#store_id').val(obj.store_id);

		$(name_div).find('#mer_id').val(obj.mer_id);

		$(name_div).find('.goods_content').html("<span class='goods_tag'>" + tag + "<a class='store_close' data-store_id='"+obj.store_id+" data-mer_id='"+obj.mer_id+"'>X</a></span>"); //添加标签
	}
}
</script>
</body>
</html>