<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>我的客服列表</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
    <link rel="stylesheet" href="{pigcms{$plugin_public}im/css/base.css" media="all"/>
</head>
<body>

<div class="layui-tab layui-tab-card">
    <div class="layui-tab-content">
        <fieldset style="border: 1px solid #ddd">
        <legend>功能说明</legend>
        使用pc端客服时，可以在所属的店铺下，点击【打开会话】按钮进入指定店铺客服聊天框
        </fieldset>
        <div class="layui-tab-item layui-show">
            <table class="layui-hide" id="kefu_list" lay-filter="kefu_list"></table>
        </div>
    </div>
</div>

<script type="text/html" id="operate_bar">
    <button type="button" class="layui-btn layui-btn-sm layui-btn-danger layui-btn-radius" lay-event="open_conversation">打开会话</button>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>


<script>
    var bind_uid = {pigcms{$bindUid};
    layui.use('table', function () {
        var table = layui.table;
        var $ = layui.jquery;

        table.render({
            elem: '#kefu_list'
            , url: "{pigcms{:U('Config/getByUid')}" + "&bind_uid=" + bind_uid
            , defaultToolbar: []
            , title: '客服列表'
            , cols: [[
                {type: 'checkbox', fixed: 'left'}
                , {field: 'id', title: 'ID', width: 80, fixed: 'left', unresize: true, sort: true}
                , {field: 'name', title: '客服名称'}
                , {field: 'store_name', title: '客服类型'}
                , {field: 'cur_receive',width:120, title: '当前接待数'}
                , {field: 'max_receive', width:120,title: '最大接待数'}
                , {
                    field: 'recommended_goods', width:120,title: '推荐商品', templet: function (res) {
                        if (res.recommended_goods == 1) {
                            return '<font style="color: red">√</font>'
                        } else {
                            return '<font style="color: green">×</font>'
                        }
                    }
                }
                , {
                    field: 'can_send_coupon', width:120,title: '发送优惠券', templet: function (res) {
                        if (res.can_send_coupon == 1) {
                            return '<font style="color: red">√</font>'
                        } else {
                            return '<font style="color: green">×</font>'
                        }
                    }
                }
                , {fixed: 'right', width:150, title: '操作', toolbar: '#operate_bar'}
            ]],
            page: {limit: 10, limits: [10, 50], groups: 10}
        });

        //监听操作
        table.on('tool(kefu_list)', function (obj) {
            if (obj.event === 'open_conversation') {
                var data = obj.data;
                $.post("{pigcms{:U('Config/getKefuLink')}",{username:data.username,belong:data.belong},function(rs){
                    if(rs.status == 1){
                        window.open(rs.url);
                    }else{
                        layer.msg(rs.info);
                    }
                })
                /*layer.open({
                    type: 2 //此处以iframe举例
                    ,title: '智能客服系统-' + data.name
                    ,area: ['400px', '750px']
                    ,shade: 0
                    ,offset: [
                        Math.random()*($(window).height()-800)
                        ,Math.random()*($(window).width()-390)
                    ]
                    ,content: data.kefu_url

                    ,zIndex: layer.zIndex
                    ,success: function(layero){
                        layer.setTop(layero);
                    }
                });*/
            }
        });

    });
</script>

</body>
</html>