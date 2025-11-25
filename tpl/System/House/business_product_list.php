<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>微信公众平台源码,微信机器人源码,微信自动回复源码 PigCms多用户微信营销系统</title>
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
<div style="background:#fefbe4;border:1px solid #f3ecb9;color:#993300;padding:10px;margin-bottom:5px;">使用方法：点击对应内容后面的“选中”即可。
</div>
<h4>{pigcms{$moduleName}&nbsp;列表</h4>
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
<thead>
<tr>
<th>产品名称</th>
<th>产品价格</th>
<th>产品图片</th>
<th style=" width:100px;">操作 <span class="tooltips" ><span>
<p>点击“选中”即可</p>
</span></span></th>
</tr>
</thead>
<volist name="list" id="pd">
<tr>
<td>{pigcms{$pd.name}</td>
<td>{pigcms{$pd.price}</td>
<td><img src="{pigcms{:replace_file_domain($pd['image'])}"  class="view_msg" style="cursor: pointer;padding: 10px; width: 100px;"></td>
<td class="norightborder">
<a href="javascript:;" onclick="returnHomepage({pigcms{$pd.goods_id},'{pigcms{$pd.image}','{pigcms{$pd.name}','{pigcms{$pd.price}','{pigcms{$pd.url}')">选中</a>
</td></tr>
</volist>
</table>
<div class="footactions" style="padding-left:10px">
  <div class="pages">{pigcms{$page}</div>
</div>
<script>
var domid=art.dialog.data('domid');
// 返回数据到主页面
function returnHomepage(product_id,img,name,price,url){
	var origin = artDialog.open.origin;
	var dom = origin.document.getElementById(domid);
    var pHtml='<div class="goods_item"><div class="close_icon">x</div><img style="width:75px" src="'+img+'"><input type="hidden" class="input product_input"  name="product_id" value="'+product_id+'" data-product_id="'+product_id+'" data-pimg="'+img+'" data-pname="'+name+'"  data-price="'+price+'" data-purl="'+url+'" /></div>';
    console.log('domid',domid);
    console.log('dom',dom);
    $(dom).append(pHtml);
    var goods_item_num=$(dom).find('.goods_item').size();
    if(goods_item_num>=3){
        var dom_product_set = origin.document.getElementById('business_upload_add');
        $(dom_product_set).hide();
    }
	setTimeout("art.dialog.close()", 100 )
}
</script>
</body>
</html>