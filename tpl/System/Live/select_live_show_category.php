<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>选择直播分类</title>
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
	<input type="text" placeholder="请输入名称搜索词" value="" class="px" name="name">
	<input type="hidden" value="" class="px" name="ids" value="{pigcms{$_GET['ids']}">
	<button class="btnGrayS" style="height: 29px;">搜索</button>
	</form>
</div>
<h4>分类列表</h4>
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
<thead>
<tr>
	<th>名称</th>
	<th style=" width:80px;">
		操作
		<input type="checkbox" id='checkall'/>
	</th>
</tr>
</thead>
<volist name="list" id="vo" key='k'>
	<tr>		
		<td>{pigcms{$vo.name}</td>
		<td class="norightborder">
			<label>
				<input type="checkbox" name="id" value="{pigcms{$vo.id}" data-k="{pigcms{$first+$k}">选中
			</label>			
		</td>
	</tr>
</volist>
</table>
<div class="footactions" style="padding-left:10px">
  	<div class="pages">
  		{pigcms{$page}
  		<button class="btnGrayS" style="float:right" onclick="fun()">确定</button>
  	</div>
</div>

<script>
	var obj = art.dialog.data('dom');
	var dom=obj.parent().find('.tag_box');
	var store_arr = [];
	if(dom.length>0){
		dom.find('.tag').each(function(){
			store_arr.push($(this).data('id')+'');	
		});
	}else{
		dom=$('<p>',{'class':'tag_box'});
		obj.after(dom);
	}
	var check_num=0;
	var count=$('input[name="id"]').length;
	$('input[name="id"]').each(function(index,val){
		if($.inArray($(this).val(), store_arr)>-1){
			$(this).prop('checked',true);
			check_num++;
		}
	});
	/*是否全选中*/
	if(check_num==count){
		$("input#checkall").prop('checked',true);
	}
	$('input[name="id"]').click(function(){
		var id=$(this).val();
		if($(this).is(":checked")){			
			var pobj=$(this).parents('tr');
			var text=pobj.find('td').eq(0).text();
			var k=$(this).data('k');
			dom.append($('<span>',{'class':'tag tag_'+id,'data-id':id,'data-k':k}).append($('<span>',
				{'text':text})).append($('<a>',
				{'class':'del','text':'X'})).append($('<input>',
				{'type':'hidden','value':id,'name':'categorys[]'})));
			store_arr.push($(this).val());
			check_num++;
		}else{
			dom.find('.tag_'+id).remove();
			console.log(dom.find('.tag'+id));
			store_arr.splice($.inArray(id, store_arr), 1);
			check_num--;
		}
		if(check_num<count){
			$("input#checkall").prop('checked',false);
		}else{
			$("input#checkall").prop('checked',true);
		}
		store_list = store_arr.join(',')
		art.dialog.data('store_list',store_list);
	});
	/*批量选择*/
	$(document).on('click','#checkall',function(){
		var flag=this.checked;
		if(flag){
			check_num=count;
		}else{
			check_num=0;
		}
		$('input[name="id"]').each(function(){
			$(this).prop('checked',flag);
			var id=$(this).val();
			var key=$.inArray(id,store_arr);
			if(key>=0){
				if(!flag){
					dom.find('.tag_'+id).remove();
					console.log(dom.find('.tag'+id));
					store_arr.splice(key, 1);
				}
			}else{
				if(flag){
					var pobj=$(this).parents('tr');
					var text=pobj.find('td').eq(0).text();
					var k=$(this).data('k');
					dom.append($('<span>',{'class':'tag tag_'+id,'data-id':id,'data-k':k}).append($('<span>',
				{'text':text})).append($('<a>',
						{'class':'del','text':'X'})).append($('<input>',
				{'type':'hidden','value':id,'name':'categorys[]'})));
					store_arr.push(id);
				}
			}
		});
	});
	
	function fun(){
		setTimeout("art.dialog.close()", 100 );
	}
</script>
</body>
</html>