<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>选择社区</title>
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
	查询社区：<input type="text" placeholder="请输入社区名称" value="" class="px" name="search">
	<button class="btnGrayS" style="height: 29px;">搜索</button><span style="color: red;margin-left: 20px;">只显示当前城市下社区</span>
	<div class="tips" id="tips" style="color:red; float: right;"></div>
	</form>
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
<thead>
<tr>
<th>社区名称</th>
<th style=" width:80px;">操作 <span class="tooltips" ><span>
<p>点击“选中”即可</p>
</span></span></th>
</tr>
</thead>
<volist name="list" id="m">
<tr>
	<td>{pigcms{$m.name}|{pigcms{$m.pick_addr}</td>
	<td class="norightborder">
		<label><input type="checkbox" name="village_id" value="{pigcms{$m.id}" <if condition="$m.select eq 1">checked</if>>选中</label>
	</td>
	</tr>

</volist>
</table>
<div class="footactions" style="padding-left:10px">
  <div class="pages">{pigcms{$page}</div>
</div>
<script>
	var village_list = art.dialog.data('village_list')
	var village_arr = [];
	if(village_list=='0'){
		village_list = '';
	}else{
		village_arr = village_list.split(',');
	}
	$('input').each(function(index,val){
		if($.inArray($(this).val(), village_arr)>-1){
			$(this).attr('checked',true);
		}
	})
	$('input').click(function(){		
		if($(this).is(":checked")){
			village_arr.push($(this).val())
		}else{
			village_arr.splice($.inArray($(this).val(), village_arr), 1);
		}
		village_list = village_arr.join(',')
		art.dialog.data('village_list',village_list)
	})
	
	function fun(){
		var topIframe = window.top.getTopIframe()
		topIframe.art.dialog({id : 'part_village'}).close();  
	}
</script>
</body>
</html>