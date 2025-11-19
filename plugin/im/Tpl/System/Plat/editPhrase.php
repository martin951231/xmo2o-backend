<!DOCTYPE html>
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
    <input type="hidden" name="id" value="{pigcms{$phrase.id}"/>
    <input type="hidden" name="type" value="{pigcms{$type}"/>
    <div class="layui-form-item">
        <label class="layui-form-label">排序值&nbsp;</label>
        <div class="layui-input-block">
            <input type="number" name="sort" class="layui-input" style="display: inline-block;" min="0"
                   value="{pigcms{$phrase.sort|default=0}"/>
        </div>
    </div>

    <if condition="$type eq 1">
    <div class="layui-form-item">
        <label class="layui-form-label">标题 &nbsp;</label>
        <div class="layui-input-block">
            <input type="text" name="title" id="title" lay-verify="title" placeholder=""
                   class="layui-input" value="{pigcms{$phrase.title|default=''}">
        </div>
    </div>
    </if>

    <div class="layui-form-item">
        <label class="layui-form-label">内容 <span style="color: red">*</span></label>
        <div class="layui-input-block">
            <input type="text" name="context" id="context" lay-verify="title" placeholder=""
                   class="layui-input" value="{pigcms{$phrase.context|default=''}">
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block" style="text-align: right">
            <button type="button" class="layui-btn layui-btn-normal" lay-submit="" lay-filter="edit_phrases_submit">确定
            </button>
            <button type="button" class="layui-btn layui-btn-primary" lay-submit="" lay-filter="edit_phrases_cancel">
                取消
            </button>
        </div>
    </div>
</form>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    var type = '{pigcms{$type}';
    var store_id = '{pigcms{$store_id}';


    layui.use(['form', 'layedit'], function () {
        var $ = layui.jquery
            , layedit = layui.layedit
            , form = layui.form;

        //监听提交
        form.on('submit(edit_phrases_submit)', function (data) {
            console.log(data)
            $.post("{pigcms{:U('System/Plat/editPhrase')}", data.field, function (rs) {
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

        form.on('submit(edit_phrases_cancel)', function (data) {
            parent.layer.close(parent.layer.getFrameIndex(window.name));
            return false;
        });
    });
</script>
</body>
</html>