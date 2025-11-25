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
        <a href="{pigcms{:U('System/Village/kefu',array('store_id'=>$store_id))}"><li class="layui-this">客服列表</li></a>
        <a href="{pigcms{:U('System/Village/shortPhrases',array('store_id'=>$store_id))}"><li>快捷回复</li></a>
        <a href="{pigcms{:U('System/Village/menu',array('store_id'=>$store_id))}"><li>自助菜单</li></a>
        <a href="{pigcms{:U('System/Village/privilege',array('store_id'=>$store_id))}"><li>权限配置</li></a>
        <a href="{pigcms{:U('System/Village/autoPhrases',array('from'=>'system'))}"><li>自动回复</li></a>
        <a href="{pigcms{:U('System/Village/villageChat',array('from'=>'system'))}"><li>小区客服聊天记录</li></a>
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

<script>
    var village_id = '{pigcms{$village_id|default=0}';
    layui.use('table', function () {
        var table = layui.table;

        table.render({
            elem: '#kefu_list'
            , url: "{pigcms{:U('kefu')}" + "&village_id=" + village_id
            , toolbar: '#toolbar'
            , defaultToolbar: []
            , title: '用户数据表'
            , cols: [[
                {type: 'checkbox', fixed: 'left'}
                , {field: 'id', title: 'ID', width: 80, fixed: 'left', unresize: true, sort: true}
                , {field: 'name', title: '客服名称'}
                , {
                    field: 'bind_uid', title: '绑定用户', templet: function (res) {
                        return '<p><img style="width: 35px;height: 35px" src="' + res.bind_user_avatar + '"/> ' + res.nickname + '</p>'
                    }
                }
                , {field: 'bind_consumer_count', title: '绑定客户数'}
                , {field: 'cur_receive', title: '当前接待数'}
                , {field: 'max_receive', title: '最大接待数'}
                , {
                    field: 'recommended_goods', title: '推荐商品', templet: function (res) {
                        if (res.recommended_goods == 1) {
                            return '<font style="color: red">√</font>'
                        } else {
                            return '<font style="color: green">×</font>'
                        }
                    }
                }
                , {fixed: 'right', title: '操作', toolbar: '#operate_bar', width: 150}
            ]],
            page: {limit: 10, limits: [10, 50], groups: 10}
        });

        //头工具栏事件
        table.on('toolbar(kefu_list)', function (obj) {
            var checkStatus = table.checkStatus(obj.config.id);
            switch (obj.event) {
                case 'addKefu':
                    layer.open({
                        title: '添加客服',
                        type: 2,
                        area: ['60%', '95%'], //宽高
                        content: '{pigcms{:U("addKefu")}',
                        zIndex: layer.zIndex,
                        btn1: function () {
                            //提交数据
                        },
                        success: function (layero, index) {
                        }
                    });
            }
        });

        //监听行工具事件
        table.on('tool(kefu_list)', function (obj) {
            var data = obj.data;
            //console.log(obj)
            if (obj.event === 'del') {
                layer.confirm('确认删除该客服？', {offset: '150px'},function (index) {
                    layui.use('jquery', function () {
                        var $ = layui.$;
                        $.post('{pigcms{:U("System/Village/delKefu")}', {id: data.id}, function (rs) {
                            if (rs.errorCode != 0) {
                                layer.msg(rs.errorMsg, {time: 1500});
                            } else {
                                layer.close(index);
                                table.reload('kefu_list', {limit: 10, limits: [10, 50], groups: 10});
                            }
                        })
                        return;
                    })

                });
            } else if (obj.event === 'edit') {
                var data = obj.data;

                layer.open({
                    title: '编辑客服',
                    type: 2,
                    area: ['60%', '95%'], //宽高
                    content: '{pigcms{:U("editKefu")}' + '&id=' + data.id,
                    zIndex: layer.zIndex,
                    success: function (layero, index) {
                        //初始化数据
                    }
                });
            }
        });
    });
</script>

</body>
</html>