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
			<a><cite>店铺列表</cite></a>
		</span>
        <span style="margin-left: 20px"><a href="{pigcms{:U('Kefu/login')}" target="_blank" class="layui-btn layui-btn-xs" lay-event="service">登录PC版客服中心</a></span>
    </div>
    <table class="layui-hide" id="store_list" lay-filter="store_list"></table>
</div>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>

<script type="text/html" id="store_listBar">
    <a class="layui-btn layui-btn-xs" lay-event="service">客服管理</a>
</script>
<script>
    var table = layui.table;

    table.render({
        elem: '#store_list'
        ,url:"{pigcms{:U('store')}"
        ,title: '店铺列表'
        ,cols: [[
            {field:'store_id', title:'店铺编号', width:120, fixed: 'left', unresize: true}
            ,{field:'name', title:'店铺名称', fixed: 'left', unresize: true}
            ,{field:'phone', title:'店铺电话', fixed: 'left', unresize: true}
            ,{fixed: 'right', title:'操作', toolbar: '#store_listBar', width:200}
        ]]
        ,page: {limit:10,limits:[10,50],groups:10}
    });
    //监听行工具事件
    table.on('tool(store_list)', function(obj){
        var data = obj.data;
        console.log(data);
        if(obj.event === 'service'){
            location.href = "{pigcms{:U('kefu')}&store_id=" + data.store_id;
        }
    });
</script>
</body>
</html>