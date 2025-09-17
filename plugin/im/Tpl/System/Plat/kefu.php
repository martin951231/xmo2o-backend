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
<style type="text/css">
    .layui-table-tool-temp {
        padding-right: 0px;
    }
    .tip{
        background-color: #dff0d8;
        color: #3c763d;
        padding: 20px;
        border: 1px solid #d6e9c6;
        font-size: 1rem;
    }
</style>
<body>

<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        <a href="{pigcms{:U('System/Plat/kefu',array('from'=>'system'))}"><li class="layui-this">客服列表</li></a>
        <a href="{pigcms{:U('System/Plat/shortPhrases',array('from'=>'system'))}"><li>快捷回复</li></a>
        <a href="{pigcms{:U('System/Plat/menu',array('from'=>'system'))}"><li>自助菜单</li></a>
        <a href="{pigcms{:U('System/Plat/privilege',array('from'=>'system'))}"><li>权限配置</li></a>
        <a href="{pigcms{:U('System/Plat/merchantChat',array('from'=>'system'))}"><li>商家客服聊天记录</li></a>
        <a href="{pigcms{:U('System/Plat/systemChat',array('from'=>'system'))}"><li>平台客服聊天记录</li></a>
    </ul>
    <div class="layui-tab-content">
        <if condition="$_SESSION['level'] eq 2">
			<div class="tip">
				<ol>
					<li>客服IM 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=30" target="_blank">【点击查看】</a></li>
				</ol>
			</div>
		</if>
        <div class="layui-tab-item layui-show">
            <table class="layui-hide" id="kefu_list" lay-filter="kefu_list"></table>
        </div>
    </div>
</div>

<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="imStatus">客服运行状态</button>
        <button class="layui-btn layui-btn-sm" style="float: right;" lay-event="addKefu">添加客服</button>
        <button class="layui-btn layui-btn-sm" style="float: right;" lay-event="kefuLogin">登录PC版客服中心</button>
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
    var store_id = {pigcms{$store_id};
    layui.use('table', function () {
        var table = layui.table;

        table.render({
            elem: '#kefu_list'
            , url: "{pigcms{:U('kefu')}" + "&store_id=" + store_id
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
                // , {field: 'bind_consumer_count', title: '绑定客户数'}
                , {field: 'cur_receive', title: '当前接待数'}
                , {field: 'max_receive', title: '最大接待数'}
                , {
                    field: 'can_send_coupon', title: '发送优惠券', templet: function (res) {
                        if (res.can_send_coupon == 1) {
                            return '<font style="color: red">√</font>'
                        } else {
                            return '<font style="color: green">×</font>'
                        }
                    }
                }
                , {
                    field: 'status', title: '状态', templet: function (res) {
                        if (res.status == 1) {
                            return '<font style="color: green">在线</font>'
                        } else {
                            return '<font style="color: red">离线</font>'
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
                        offset: '50px',
                        area: ['80%', '80%'], //宽高
                        content: '{pigcms{:U("addKefu")}' + '&store_id=' + store_id,
                        zIndex: layer.zIndex,
                        btn1: function () {
                            //提交数据
                        },
                        success: function (layero, index) {
                        }
                    });
                    break;
                case 'kefuLogin':
                    window.open("{pigcms{:U('Kefu/login')}");
                    break;
                case 'repairKefu':
                    layer.confirm('操作前请确认极光参数已正确配置，确定一键修复吗，期间请勿重复点击？', {offset: '150px',title:'温馨提示'},function (index) {
                    layui.use('jquery', function () {
                        var $ = layui.$;
                        $.post('{pigcms{:U("repairKefu")}', {}, function (rs) {
                            alert(rs.info);
                            layer.close(index);
                        })
                        return;
                    })

                    });
                    break;
                case 'imStatus':
                    layer.open({
                        title: 'IM运行状态',
                        type: 2,
                        offset: '50px',
                        area: ['50%', '50%'], //宽高
                        content: '{pigcms{:U("imStatus")}' + '&store_id=' + store_id,
                        zIndex: layer.zIndex,
                        btn1: function () {
                            //提交数据
                        },
                        success: function (layero, index) {
                        }
                    });
                    break;
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
                        $.post('{pigcms{:U("Config/delKefu")}', {id: data.id}, function (rs) {
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
                    offset: '50px',
                    area: ['80%', '80%'], //宽高
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