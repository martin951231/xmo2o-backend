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
<form class="layui-form" action="{pigcms{:U('System/Village/editAutoPhrase')}" method="post" style="margin: 30px" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$phrase.id}"/>

    <div class="layui-form-item">
        <label class="layui-form-label">关键词 <span style="color: red">*</span></label>
        <div class="layui-input-block">
            <input type="text" name="key" id="key" lay-verify="title" placeholder="关键词"
                   class="layui-input" value="{pigcms{$phrase.key}">
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">回复内容</label>
        <div class="layui-input-block" >
            <textarea placeholder="请输入" lay-verify="title" rows="12" cols="80" id="content" name="content">{pigcms{$phrase.content}</textarea>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">回复图片</label>
        <div class="layui-input-block">
            <input type="file" name="pic" id="pic" placeholder="未选择任何文件"
                    onchange="fileChange(this);">
        </div>
        <if condition="$phrase.pic != ''">
            <img src="{pigcms{$phrase.pic}" style="width: 80px;height: 102px" />
        </if>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block" style="text-align: right">
            <button type="submit" class="layui-btn layui-btn-normal" lay-submit="" lay-filter="edit_phrases_submit">确定
            </button>
            <button type="submit" class="layui-btn layui-btn-primary" lay-submit="" lay-filter="edit_phrases_cancel">
                取消
            </button>
        </div>
    </div>
</form>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    function fileChange(target) {
        //可以上传的文件类型
        var filetypes =[".jpg",".png"];
        //获取上传文件的本地路径
        var filepath = target.value;

        //上传文件的大小限制
        var filemaxsize = 1024*2;//2M 
        if(filepath){
            var isnext = false;

            //获取上传文件的扩展名
            var fileend = filepath.substring(filepath.indexOf("."));
            if(filetypes && filetypes.length>0){
                for(var i =0; i<filetypes.length;i++){
                    if(filetypes[i]==fileend){
                        isnext = true;
                        break;
                    }
                }
            }
            if(!isnext){
                alert("不接受此文件类型！");
                target.value ="";
                return false;
            }
        }else{
            return false;
        }
    }
    layui.use(['form', 'layedit'], function () {
        var $ = layui.jquery
            , layedit = layui.layedit
            , form = layui.form;

        //监听提交
        /*form.on('submit(edit_phrases_submit)', function (data) {
            console.log(data)
            $.post("{pigcms{:U('System/Village/editAutoPhrase')}", data.field, function (rs) {
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
        });*/

        form.on('submit(edit_phrases_cancel)', function (data) {
            parent.layer.close(parent.layer.getFrameIndex(window.name));
            return false;
        });
    });
</script>
</body>
</html>