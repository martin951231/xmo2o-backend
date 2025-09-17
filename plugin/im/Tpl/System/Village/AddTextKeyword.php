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
    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
</head>
<body>
<form class="layui-form" method="post" action="" style="margin: 30px" enctype="multipart/form-data">
    <input type="hidden" name="group_id" value="{pigcms{$group_id}"/>

    <div class="layui-form-item">
        <label class="layui-form-label">关键词 <span style="color: red">*</span></label>
        <div class="layui-input-block" style="display: flex">
            <input type="text" name="key" id="key" lay-verify="title" placeholder="关键词"
                   class="layui-input" style="width: 300px;">
            <span style="color: red;margin-top: 10px">相同的关键字使用最后一次添加的</span>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">回复内容</label>
        <div class="layui-input-block flex_box_1">
            <textarea placeholder="请输入" lay-verify="title" rows="12" cols="80" id="content" name="content"></textarea>
        </div>
    </div>


    <div class="layui-form-item">
        <label class="layui-form-label">回复图片</label>
        <div class="layui-input-block">
            <input type="file" name="pic" id="pic" placeholder="未选择任何文件"
                   onchange="fileChange(this);">
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block" style="text-align: right">
            <button type="submit" class="layui-btn layui-btn-normal" lay-submit="" lay-filter="add_phrases_submit">确定
            </button>
            <button type="submit" class="layui-btn layui-btn-primary" lay-submit="" lay-filter="add_phrases_cancel">取消
            </button>
        </div>
    </div>
</form>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
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
    function addLink(domid,iskeyword){
        art.dialog.data('domid', domid);
        art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    }
    layui.use(['form', 'layedit'], function () {
        var $ = layui.jquery
            , layedit = layui.layedit
            , form = layui.form;

        //监听提交
        /*form.on('submit(add_phrases_submit)', function (data) {
            console.log(data)
            $.post("{pigcms{:U('System/Village/AddGraphicKeyword')}", data.field, function (rs) {
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

        form.on('submit(add_phrases_cancel)', function (data) {
            parent.layer.close(parent.layer.getFrameIndex(window.name));
            return false;
        });
    });
</script>
<style>
    .flex_box_1 {
        display: flex;
        flex-direction: row;
    }
    .flex_box {
        margin-left: 20px;
    }
</style>
</body>
</html>