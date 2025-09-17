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
        <a href="{pigcms{:U('System/Village/shortPhrases',array('from'=>'system'))}"><li class="layui-this">快捷回复</li></a>
        <a href="{pigcms{:U('System/Village/menu',array('from'=>'system'))}"><li>自助菜单</li></a>
        <a href="{pigcms{:U('System/Village/privilege',array('from'=>'system'))}"><li>权限配置</li></a>
        <a href="{pigcms{:U('System/Village/autoPhrases',array('from'=>'system'))}"><li>自动回复</li></a>
        <a href="{pigcms{:U('System/Village/villageChat',array('from'=>'system'))}"><li>小区客服聊天记录</li></a>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief" style="display: block">
            <ul class="layui-tab-title">
                <li class="layui-this"><a href="{pigcms{:U('System/Village/shortPhrases',array('from'=>'system','type'=>1))}">团队</a></li>
                <li><a href="{pigcms{:U('System/Village/shortPhrases',array('from'=>'system','type'=>2))}">个人</a></li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <p class="layui-elem-quote">快捷短语分组列表式</p>
                    <div class="layui-btn-container" style="margin: 10px">
                        <button class="layui-btn layui-btn-sm" lay-event="addKefu" onclick="add_phrase()">添加</button>
                    </div>
                    <div class="layui-btn-container" style="font-size: 1em">
                        <div style="width: 250px;margin:10px;float:left;background: #f2f2f2;margin-right: 10px;height: auto"
                             id="left_nav">
                            <ul>
                                <li class="left_new_group" onclick="add_group()">＋新建分组
                                </li>
                                <li class="left_all_group">所有分组
                                </li>
                                <volist name="groups" id="g">
                                    <li onclick="show_phrases({pigcms{$g.id})"
                                        class="left_active_li left_nav_{pigcms{$g.id}">
                                        {pigcms{$g.name}
                                        <div class="layui-btn-group icon_div">
                                            <div onclick="edit_group({pigcms{$g.id})"><i class="layui-icon"></i></div>
                                            <div onclick="delete_group({pigcms{$g.id})"><i class="layui-icon"></i>
                                            </div>
                                        </div>
                                    </li>
                                </volist>
                            </ul>
                        </div>


                        <div style="width: 60%;float:left;height: 200px">
                            <table class="layui-hide" id="phrases_form" lay-filter="phrases_form"></table>
                        </div>
                    </div>
                    <p style="clear: both"></p>
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

<script type="text/html" id="toolbar">
    <div class="layui-btn-container">
        <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" lay-event="modify">修改分组</button>
        <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" lay-event="del">删除</button>
    </div>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    var type = '{pigcms{$type}';
    var village_id = '{pigcms{$village_id|default=0}';
    var checked_group_id = 0;
    var first_group_id = "{pigcms{$groups[0]['id']|intval=###}"

    if(first_group_id > 0){
        show_phrases(first_group_id);
    }else{
        show_phrases(0);
    }


    function reload_page() {
        location.reload();
    }

    function reload_table() {
        layui.table.reload('phrases_form');
    }

    function add_group() {
        layer.open({
            title: '新建分组',
            type: 2,
            offset: '50px',
            area: ['600px', '250px'], //宽高
            content: '{pigcms{:U("System/Village/addPhrasesGroup")}' + '&type=' + type,
            success: function (layero, index) {
            },
            end: function () {
                //location.reload();
            }
        });
    }

    function edit_group(id) {
        window.event ? window.event.cancelBubble = true : e.stopPropagation();
        layer.open({
            title: '编辑分组',
            type: 2,
            offset: '50px',
            area: ['600px', '250px'], //宽高
            content: '{pigcms{:U("System/Village/editPhrasesGroup")}' + '&id=' + id,
            success: function (layero, index) {
            },
            end: function () {
                //location.reload();
            }
        });
    }

    function delete_group(id) {
        window.event ? window.event.cancelBubble = true : e.stopPropagation();
        layer.confirm('确认删除该分组？',{offset: '50px'}, function (index) {
            layui.use('jquery', function () {
                var $ = layui.$;
                $.post('{pigcms{:U("System/Village/delPhrasesGroup")}', {id: id}, function (rs) {
                    if (rs.errorCode != 0) {
                        layer.msg(rs.errorMsg, {
                            time: 2000
                        });
                    } else {
                        reload_page();
                    }
                })
                return;
            })

        });
    }
    function show_phrases(group_id) {
        checked_group_id = group_id;
        layui.use('table', function () {
            var table = layui.table;
            var $ = layui.jquery;
            $('.left_nav_' + group_id).addClass('li_check').siblings().removeClass('li_check');
            $('.icon_div').hide();
            $('.left_nav_' + group_id).children('.icon_div').show();

            table.render({
                elem: '#phrases_form'
                , url: "{pigcms{:U('System/Village/ajaxGetPhrases')}" + "&type=1&group_id=" + group_id
                , toolbar: '#toolbar'
                , defaultToolbar: []
                , title: '快捷短语'
                , cols: [[
                    {type: 'checkbox', fixed: 'left'}
                    , {field: 'id', title: 'ID', width: 80, fixed: 'left', unresize: true, sort: true}
                    , {field: 'context', title: '内容'}
                    , {field: 'sort', title: '排序'}
                    , {fixed: 'right', title: '操作', toolbar: '#operate_bar', width: 150}
                ]]
            });

            table.on('toolbar(phrases_form)', function (obj) {
                var checkStatus = table.checkStatus(obj.config.id);
                var data = checkStatus.data;
                var ev = obj.event;
                if (ev == 'modify') {
                    if (data.length == 0) {
                        layer.msg('请至少选择一条记录', {time: 1000, offset:"50px"});
                    } else {
                        var id_str = '';
                        for (i in data) {
                            if (id_str == '') {
                                id_str = data[i].id
                            } else {
                                id_str = id_str + '-' + data[i].id
                            }
                        }
                        layer.open({
                            title: '修改分组',
                            type: 2,
                            offset: '50px',
                            area: ['700px', '250px'], //宽高
                            content: '{pigcms{:U("System/Village/selectPhrasesGroup")}' + '&id_str=' + id_str + '&type=' + type,
                            success: function (layero, index) {
                            },
                            end: function () {
                            }
                        });
                    }
                } else if (ev == 'del') {
                    if (data.length == 0) {
                        layer.msg('请至少选择一条记录', {time: 1000, offset:"50px"});
                    } else {
                        layer.confirm('确认删除？', {offset: '50px'},function (index) {
                            var ids = new Array();
                            for (i in data) {
                                ids[i] = data[i].id
                            }
                            del_phrase(ids)
                            layer.close(index);
                        });
                    }
                }
            });
            table.on('tool(phrases_form)', function (obj) {
                var data = obj.data;
                if (obj.event === 'del') {
                    layer.confirm('确认删除？', {offset: '50px'},function (index) {
                        del_phrase([data.id]);
                        layer.close(index);
                    });
                } else if (obj.event === 'edit') {
                    var data = obj.data;
                    layer.open({
                        title: '编辑快捷短语',
                        type: 2,
                        offset: '50px',
                        area: ['700px', '270px'], //宽高
                        content: '{pigcms{:U("System/Village/editPhrase")}' + '&id=' + data.id,
                        success: function (layero, index) {
                        },
                        end: function () {
                        }
                    });
                }
            });


        })
    }

    function del_phrase(ids) {
        layui.use('jquery', function () {
            var $ = layui.$;
            $.post("{pigcms{:U('System/Village/delPhrase')}", {'ids': ids}, function (rs) {
                if (rs.errorCode != 0) {
                    layer.msg(rs.errorMsg, {time: 1000});
                } else {
                    layui.table.reload('phrases_form');
                }
            })
        })
    }

    function add_phrase() {
        if (checked_group_id < 1) {
            layer.msg('请先添加分组', {time: 1000});
            return false
        }
        layer.open({
            title: '新增快捷短语',
            type: 2,
            offset: '50px',
            area: ['700px', '270px'], //宽高
            content: '{pigcms{:U("System/Village/addPhrase")}' + '&group_id=' + checked_group_id,
            success: function (layero, index) {
            },
            end: function () {
                //layui.table.reload('phrases_form');
            }
        });
    }


</script>

</body>
</html>