<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理页面</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
    <link rel="stylesheet" href="{pigcms{$plugin_public}im/css/base.css" media="all"/>
</head>
<body>

<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        <a href="{pigcms{:U('System/Plat/kefu',array('from'=>'system'))}">
            <li class="layui-this">客服列表</li>
        </a>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab-item layui-show">
            <table class="layui-hide" id="kefu_list" lay-filter="kefu_list"></table>
        </div>
    </div>
</div>

<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-sm" lay-event="addKefu">添加客服</button>
    </div>
</script>

<script type="text/html" id="operate_bar">
    <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" lay-event="edit"><i class="layui-icon"></i>
    </button>
    <button type="button" class="layui-btn layui-btn-sm" lay-event="del"><i class="layui-icon"></i>
    </button>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->
<script type="text/html" id="show_bind_user">
    <p><img style="width: 35px;height: 35px" src="{{d.bind_user_avatar}}"/> {{d.nickname}} </p>
</script>
<script type="text/html" id="send_coupon">
    {{#  if(d.can_send_coupon == 1){ }}
    <font style="color: red">√</font>
    {{#  } else { }}
    <font style="color: green">×</font>
    {{#  } }}
</script>
<script>
    var store_id = 0;
    var columns = '{pigcms{$columns}'

    layui.use(['table', 'jquery'], function () {
        var table = layui.table;
        var $ = layui.$;
        columns = $.parseJSON(columns)
        console.log(typeof columns)

        table.render({
            elem: '#kefu_list'
            , url: "{pigcms{:U('kefu')}" + "&store_id=" + store_id
            , toolbar: '#toolbar'
            , defaultToolbar: []
            , title: '用户数据表'
            , cols: columns
            , page: {limit: 10, limits: [10, 50], groups: 10}
        });

        //头工具栏事件
        table.on('toolbar(kefu_list)', function (obj) {
            if (obj.event == 'addKefu') {
                layer.open({
                    title: '添加客服',
                    type: 2,
                    area: ['60%', '95%'], //宽高
                    content: '{pigcms{:U("addKefu")}' + '&store_id=' + store_id,
                    zIndex: layer.zIndex,
                    btn1: function () {
                        //提交数据
                    },
                    success: function (layero, index) {
                    }
                });
            }
        });
    });
</script>

</body>
</html>