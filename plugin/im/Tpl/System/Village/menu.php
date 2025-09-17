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
    .layui-elem-quote {
        margin: 10px auto;
        padding: 8px;
        line-height: 20px;
        border-left: 5px solid #009688;
        border-radius: 0 2px 2px 0;
        background-color: #fff
    }

    .li_check {
        background: #9f9f9f47;
    }

    .icon_div {
        float: right;
        margin-right: 15px;
        display: none;
        z-index: 998;
    }

    .icon_div > div {
        display: inline-block;
    }

    .layui-icon:hover {
        color: #0a94ff;
        cursor: pointer;
    }

    .left_active_li {
        padding: 15px 0px;
        text-indent: 1em;
        line-height: 1.2em
    }

    .left_new_group {
        cursor: pointer;
        padding: 15px 0px;
        text-indent: 1em;
        line-height: 1.2em;
        color: #0a94ff
    }

    .left_all_group {
        padding: 15px 0px;
        text-indent: 1em;
        line-height: 1.2em;
        font-weight: bold;
    }
</style>
<body>
<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        <a href="{pigcms{:U('System/Village/kefu',array('from'=>'system'))}"><li>客服列表</li></a>
        <a href="{pigcms{:U('System/Village/shortPhrases',array('from'=>'system'))}"><li>快捷回复</li></a>
        <a href="{pigcms{:U('System/Village/menu',array('from'=>'system'))}"><li class="layui-this">自助菜单</li></a>
        <a href="{pigcms{:U('System/Village/privilege',array('from'=>'system'))}"><li>权限配置</li></a>
        <a href="{pigcms{:U('System/Village/autoPhrases',array('from'=>'system'))}"><li>自动回复</li></a>
        <a href="{pigcms{:U('System/Village/villageChat',array('from'=>'system'))}"><li>小区客服聊天记录</li></a>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief" style="display: block">
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <p class="layui-elem-quote">自助菜单列表页面</p>
                    <div class="layui-btn-container" style="margin: 10px">
                        <button class="layui-btn layui-btn-sm" onclick="add_menu()">添加菜单</button>
                    </div>
                    <fieldset class="layui-elem-field layui-field-title"
                              style="margin-top: 20px;padding:10px;border: 1px solid #f2f2f2">
                        <legend>功能说明</legend>
                        用于用户与社区客服聊天界面中的用户看到的自助菜单
                    </fieldset>

                    <table class="layui-hide" id="menu_form" lay-filter="menu_form"></table>

                </div>
            </div>
        </div>


    </div>
</div>

<script type="text/html" id="operate_bar">
    <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" lay-event="edit"><i class="layui-icon"></i>
    </button>
    <button type="button" class="layui-btn layui-btn-sm" lay-event="del"><i class="layui-icon"></i>
    </button>
</script>


<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    var village_id = '{pigcms{$village_id|default=0}';

    function reload_table() {
        layui.table.reload('menu_form');
    }

    function add_menu() {
        layer.open({
            title: '添加菜单',
            type: 2,
            offset: '50px',
            area: ['700px', '450px'], //宽高
            content: '{pigcms{:U("System/Village/addMenu")}',
            success: function (layero, index) {
                layer.iframeAuto(index);
            },
            end: function () {
            }
        });
    }

    function edit_menu(id) {
        layer.open({
            title: '编辑菜单',
            type: 2,
            offset: '50px',
            area: ['700px', '370px'], //宽高
            content: '{pigcms{:U("System/Village/editMenu")}' + '&id=' + id,
            success: function (layero, index) {
                layer.iframeAuto(index);
            },
            end: function () {
            }
        });
    }


    layui.use('table', function () {
        var table = layui.table;
        var $ = layui.jquery;

        table.render({
            elem: '#menu_form'
            , url: "{pigcms{:U('System/Village/menu')}"
            , defaultToolbar: []
            , title: '菜单'
            , cols: [[
                {type: 'checkbox', fixed: 'left'}
                , {field: 'name', title: '菜单名称'}
                , {field: 'type_desc', title: '菜单类型'}
                , {field: 'sort', title: '排序值'}
                , {field: 'create_time', title: '创建时间'}
                , {fixed: 'right', title: '操作', toolbar: '#operate_bar', width: 150}
            ]]
        });

        table.on('tool(menu_form)', function (obj) {
            var data = obj.data;
            if (obj.event == 'edit') {
                edit_menu(data.id);
            } else if (obj.event == 'del') {
                layer.confirm('确认删除该菜单？',{offset: '50px'}, function (index) {
                    layui.use('jquery', function () {
                        var $ = layui.$;
                        $.post('{pigcms{:U("System/Village/delMenu")}', {id: data.id}, function (rs) {
                            if (rs.error_code > 0) {
                                layer.msg(rs.error_msg, {time: 1500});
                            } else {
                                layer.close(index);
                                reload_table();
                            }
                        })
                        return;
                    })

                });
            }
        })
    })
</script>
</body>
</html>