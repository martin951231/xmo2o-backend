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
<input type="hidden" name="come_from" id="come_from" value="{pigcms{$from}"/>
<div class="demoTable" style="margin-top: 15px;margin-left: 15px;">
    <div class="layui-inline">
        <input class="layui-input" name="keyword" id="demoReload" autocomplete="off" placeholder="请输入关键字">
    </div>
    <button class="layui-btn" data-type="reload">搜索</button>
</div>

<table class="layui-hide" id="LAY_table_user" lay-filter="user"></table>

<script type="text/html" id="user_listBar">
    <a class="layui-btn layui-btn-xs" lay-event="check-user">选中</a>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
<script>
    var village_id = '{pigcms{$village_id|default=0}';
    var bind_uid = '{pigcms{$bind_uid|default=0}';
    layui.use(['table','jquery'], function(){
        var table = layui.table;
        var $ = layui.$
        //方法级渲染
        table.render({
            elem: '#LAY_table_user'
            ,url: '{pigcms{:U("System/Customer/villageUserBind")}'+'&village_id='+village_id+'&bind_uid='+bind_uid
            ,cols: [[
                {field:'name', title: '名称'}
                ,{field:'phone', title: '电话'}
                ,{field:'avatar', title: '头像', templet: function(res){
                        return '<img style="width: 35px;height: 35px" src="'+ res.avatar +'"/>'
                    }}
                ,{fixed: 'right', title:'操作', toolbar: '#user_listBar'}
            ]]
            ,id: 'testReload'
            ,page: {limit:10,limits:[10,50],groups:10}
        });

        table.on('tool(user)', function(obj){
            var data = obj.data;
            var $ = layui.$;
            if(obj.event === 'check-user'){
                parent.bind_consumer_show(data.pigcms_id,data.name,data.avatar);
                var index = parent.layer.getFrameIndex(window.name);
                parent.layer.close(index);
            }
        });

        var $ = layui.$, active = {
            reload: function(){
                var demoReload = $('#demoReload');
                var come_from = $('#come_from').val();

                //执行重载
                table.reload('testReload', {
                    page: {
                        curr: 1 //重新从第 1 页开始
                    }
                    ,where: {
                        keyword: demoReload.val(),
                        from:come_from
                    }
                }, 'data');
            }
        };

        $('.demoTable .layui-btn').on('click', function(){
            var type = $(this).data('type');
            active[type] ? active[type].call(this) : '';
        });
    });
</script>

</body>
</html>