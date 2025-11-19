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
<form class="layui-form" action="" style="margin: 30px">
    <volist name="ids" id="v">
        <input type="hidden" name="id[]" value="{pigcms{$v}"/>
    </volist>

    <div class="layui-form-item">
        <label class="layui-form-label">分组</label>
        <div class="layui-input-block">
            <select name="group_id" lay-filter="aihao">
                <option value=""></option>
                <volist name="groups" id="g">
                    <option value="{pigcms{$g.id}">{pigcms{$g.name}</option>
                </volist>
            </select>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block" style="text-align: right">
            <button type="button" class="layui-btn layui-btn-normal" lay-submit=""
                    lay-filter="submit">确定
            </button>
            <button type="button" class="layui-btn layui-btn-primary" lay-submit=""
                    lay-filter="cancel">取消
            </button>
        </div>
    </div>
</form>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    var type = '{pigcms{$type}';
    var store_id = '{pigcms{$store_id}';

    layui.use(['form', 'upload'], function () {
        var $ = layui.jquery
            , form = layui.form;

        //监听提交
        form.on('submit(submit)', function (data) {
            $.post("{pigcms{:U('System/Village/selectPhrasesGroup')}", data.field, function (rs) {
                if (rs.errorCode != 0) {
                    layer.msg(rs.errorMsg, {
                        time: 2000
                    });
                } else {
                    parent.reload_table();
                    parent.layer.close(parent.layer.getFrameIndex(window.name));
                }
            })
            return false;
        });

        form.on('submit(cancel)', function (data) {
            parent.layer.close(parent.layer.getFrameIndex(window.name));
            return false;
        });
    });
</script>
</body>
</html>