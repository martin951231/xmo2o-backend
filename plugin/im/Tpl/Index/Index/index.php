<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>管理页面</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
		<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css"  media="all"/>
		<link rel="stylesheet" href="{pigcms{$plugin_public}im/css/base.css"  media="all"/>
	</head>
	<body>
		<div class="layui-main">
			<div id="LAY_preview">
				<span class="layui-breadcrumb">
					<a href="{pigcms{:U('index')}">客服IM</a>
					<a><cite>店铺列表</cite></a>
				</span>
			</div>
			<table class="layui-hide" id="store_list"></table>
		</div>
		<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
		<script>
			var table = layui.table;

			table.render({
				elem: '#store_list'
				,url:'/test/table/demo1.json'
				,title: '用户数据表'
				,cols: [[
					{field:'id', title:'店铺编号', width:120, fixed: 'left', unresize: true}
					,{field:'id', title:'店铺名称', fixed: 'left', unresize: true}
					,{field:'id', title:'店铺电话', fixed: 'left', unresize: true}
					,{fixed: 'right', title:'操作', toolbar: '#barDemo', width:200}
				]]
				,page: true
			});
		</script>
	</body>
</html>