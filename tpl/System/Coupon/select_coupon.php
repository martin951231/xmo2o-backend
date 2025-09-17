<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>选择平台优惠券</title>
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
	查询：<input type="text" placeholder="请输入优惠券名称" value="" class="px" name="search">
	<button class="btnGrayS" style="height: 29px;">搜索</button>
	<div class="tips" id="tips" style="color:red; float: right;"></div>
	</form>
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
<thead>
<tr>
<th>名称</th>
<th>有效日期</th>
<th>优惠信息</th>
<th>使用平台</th>
<th>使用类别</th>
<th>使用分类</th>
<th>数量</th>
<th style=" width:80px;">操作 <span class="tooltips" ><span>
<p>点击“选中”即可</p>
</span></span></th>
</tr>
</thead>
<volist name="list" id="m">
<tr class="user" data-coupon_id="{pigcms{$m.coupon_id}">
	<td>{pigcms{$m.name}</td>
	<td>{pigcms{$m.start_time|date="Y年m月d日",###}到{pigcms{$m.end_time|date="Y年m月d日",###}</td>
	<eq name='m.is_discount' value='1'>
		<td>满{pigcms{$m.order_money}打{pigcms{$m.discount_value|floatval}折</td>
	<else/>
		<td>满{pigcms{$m.order_money}减{pigcms{$m.discount}{pigcms{$config['Currency_txt']}</td>
	</eq>
	<td>{pigcms{$m.platform}</td>
	<td>{pigcms{$m.cate_name}</td>
	<td>{pigcms{$m.cate_id}</td>
	<td>{pigcms{$m.num}</td>
	<td class="norightborder"><a href="###" onclick="returnHomepage('{pigcms{$m.coupon_id}','{pigcms{$m.name}','{pigcms{$m.show_name}')">选中</a></td></tr>
</volist>
</table>
<div class="footactions" style="padding-left:10px">
  <div class="pages">{pigcms{$page}</div>
</div>
<script>
var domid=art.dialog.data('domid');
var origin = artDialog.open.origin;
var name_add = origin.document.getElementById('form-field-tags')
var name_div = origin.document.getElementById('tags')
var deliver_type = origin.document.getElementById('deliver_type')
// 返回数据到主页面
function returnHomepage(coupon_id,name,show_name){
	var dom = origin.document.getElementById(domid);

	var a={};
	a.coupon_id = coupon_id;
	a.name = name;
	a.show_name = show_name;
	addTag(a);
	setTimeout("art.dialog.close()", 100 )
	
}

function addTag(obj) {
	var tag = obj.name;
	if (tag != '') {
		var i = 0;
		var tag_arr = $(name_div).children(".tag");
		$(name_div).find('#coupon_id').val(obj.coupon_id);
		$(name_div).children('.tag').html( obj.show_name + "&nbsp<button class='close' data-coupon_id='"+obj.coupon_id+"' type='button'>×</button>"); //添加标签
		$(deliver_type).hide();
	}
}

function removeWarning() {
	$('#tips').html('')
}
</script>
</body>
</html>