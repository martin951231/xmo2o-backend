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
<style type="text/css">
    .layui-form-item {
        margin-bottom: 30px
    }

    .delete_right {
        display: inline-block;
        float: right;
        z-index: 1000;
        width: 14px;
        height: 14px;
        line-height: 14px;
        text-align: center;
        border-radius: 50px;
        border: 1px solid #000;
		cursor:pointer;
		margin: 4px 4px 0 0;
    }

    .pic_div {
        display: inline-block;
        border: 1px solid rgba(215, 215, 215, 1);
        margin: 5px;
        width: 45%
    }
    .notice {
        color: #9f9f9f
    }
    .layui-form-label {
        width: 150px;
    }
    .layui-upload-list {
        margin-left: 70px;
    }
    .user_show {
        width: 80%;
        margin-left: 65px
    }
</style>
<p style="margin-top: 20px;">
</p>

<form class="layui-form" action="">
    <input type="hidden" name="id" id="id" class="layui-input" value="{pigcms{$kefuInfo.id}"/>
    <div class="layui-form-item">
        <label class="layui-form-label">客服名称 <span style="color: red">*</span></label>
        <div class="layui-input-block">
            <input type="text" name="kefu_name" id="kefu_name" lay-verify="title" value="{pigcms{$kefuInfo.name}"
                   class="layui-input" style="width: 50%">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">绑定用户 <span style="color: red">*</span></label>
        <div class="layui-input-block">
            <button type="button" class="layui-btn" onclick="bind_user()">选择粉丝</button>
            <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;绑定用户后，该客服将可通过社区小程序或社区APP来进入移动端客服聊天入口</span>
            <div class="user_show" id="bind-user-show">
                <div class="pic_div"><img style="width: 60px;height: 60px" src="{pigcms{$kefuInfo.bind_user_avatar}"/>
                    {pigcms{$kefuInfo.nickname}
                    <input type="hidden" name="bind_uid" id="bind_uid" class="layui-input"
                           value="{pigcms{$kefuInfo.bind_uid}"/>
                    <div onclick="remove_binduser()" class="delete_right">×</div>
                </div>
            </div>
        </div>

    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">指定客户</label>
        <div class="layui-input-block">
            <button type="button" class="layui-btn" onclick="bind_consumer()">选择客户</button>
            <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;即社区可以给该客服指定专属客户，指定后，下次聊天将优先指定客户给该客服</span>
            <div class="user_show" id="bind-consumer-show">
                <volist name="kefuInfo.bind_consumers" id="c">
                    <div id="consumer_{pigcms{$c.uid}" class="pic_div">
                        <img style="width: 60px;height: 60px" src="{pigcms{$c.user_avatar}"/> {pigcms{$c.nickname}
                        <div onclick="remove_consumer({pigcms{$c.uid},'consumer')" class="delete_right">×</div>
                        <input type="hidden" name="customer_uids[]" class="layui-input" value="{pigcms{$c.uid}"/>
                    </div>
                </volist>
            </div>
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">首句欢迎语</label>
        <div class="layui-input-block">
            <input type="text" name="welcome" lay-verify="welcome" value="{pigcms{$kefuInfo.welcome}"
                   class="layui-input" style="width: 50%">
            <p style="color: #9f9f9f; margin: 10px 53px;">&nbsp;&nbsp;&nbsp;&nbsp;当客户发送第一条消息，系统自动分配到在线客服时，会进行自动回复给客户</p>
        </div>
    </div>
    <div class="item_div">
        <div class="layui-form-item">
            <label class="layui-form-label">客服最大接待数</label>
            <div class="layui-input-block">
                <input type="number" name="max_receive" lay-verify="second" value="{pigcms{$kefuInfo['max_receive']}" class="layui-input"
                       style="display: inline-block;width: 20%"  min="0"/>
                <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;即客服最大接待客户的数量；默认每个客服最大接待数为50个</span>
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block">
            <button class="layui-btn layui-btn-radius" lay-submit="" lay-filter="edit_kefu_submit">立即提交</button>
        </div>
    </div>
</form>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    var village_id = '{pigcms{$village_id|default=0}'

    function remove_consumer(id, action) {
        layui.use('jquery', function () {
            var $ = layui.$;
            $('#' + action + '_' + id).remove();
        })
    }

    function remove_binduser() {
        layui.use('jquery', function () {
            var $ = layui.$;
            $('#bind-user-show').html('');
        })
    }

    function bind_user_show(uid, nickname, avatar) {
        layui.use('jquery', function () {
            var $ = layui.$;
            var _html = '<div class="pic_div"><img style="width: 60px;height: 60px" src="' + avatar + '"/>' + ' ' + nickname +
                '<input type="hidden" name="bind_uid" id="bind_uid" class="layui-input" value="' + uid + '"/>' +
                '<div onclick="remove_binduser()" class="delete_right">×</div>' +
                '</div>';
            $('#bind-user-show').html(_html);
        })
    }

    function bind_consumer_show(uid, nickname, avatar) {
        layui.use('jquery', function () {
            var $ = layui.$;
            if ($('#consumer_'+uid).length >0){
                return false;
            }
            var _html = '<div id="consumer_' + uid + '" class="pic_div">' +
                '<img style="width: 60px;height: 60px" src="' + avatar + '"/> ' + ' ' + nickname +
                '<div onclick="remove_consumer(' + uid + ',\'consumer\')" class="delete_right">×</div>' +
                '<input type="hidden" name="customer_uids[]" class="layui-input" value="' + uid + '"/>' +
                '</div>';
            $('#bind-consumer-show').append(_html);
            return
        })
    }

    function bind_user() {
        layer.open({
            title: '选择粉丝',
            type: 2,
            area: ['80%', '90%'], //宽高
            btn: [],
            content: '{pigcms{:U("System/Customer/villageWorker")}'+'&village_id='+village_id,
            shadeClose: false,
            success: function (layero, index) {
            }
        });
    }

    function bind_consumer() {
        var input=document.getElementById("bind_uid");
        layer.open({
            title: '选择粉丝',
            type: 2,
            area: ['80%', '90%'], //宽高
            btn: [],
            content: '{pigcms{:U("System/Customer/villageUserBind")}'+'&village_id='+village_id+'&bind_uid='+input.value,
            shadeClose: false,
            success: function (layero, index) {
            }
        });
    }

    layui.use(['form', 'upload'], function () {
        var $ = layui.jquery
            , upload = layui.upload
            , form = layui.form;

        form.verify({
            title: function (value) {
                if (value.length <= 0) {
                    return '请输入客服名称';
                }
            }
        });

        //监听提交
        form.on('submit(edit_kefu_submit)', function (data) {
            $.post("{pigcms{:U('System/Village/editKefu')}", data.field, function (rs) {
                if (rs.errorCode != 0) {
                    layer.msg(rs.errorMsg, {
                        time: 2000
                    });
                } else {
                    parent.layer.close(parent.layer.getFrameIndex(window.name));
                    parent.layui.table.reload('kefu_list',{limit: 10, limits: [10, 50], groups: 10});
                }
            })
            return false;
        });

        //普通图片上传
        var uploadInst = upload.render({
            elem: '#kefu_headimage'
            , url: '{pigcms{:U("System/Upload/ajaxKfPic")}'
            , before: function (obj) {
                //预读本地文件示例，不支持ie8
                obj.preview(function (index, file, result) {
                    $('#kefu_headimage_prev').attr('src', result);
                });
            }
            , done: function (res) {
                //如果上传失败
                if (res.code > 0) {
                    return layer.msg('上传失败');
                }
                //上传成功
                $('#avatar').val(res.url);
            }
            , error: function () {
                //演示失败状态，并实现重传
                var demoText = $('#demoText');
                demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs demo-reload">重试</a>');
                demoText.find('.demo-reload').on('click', function () {
                    uploadInst.upload();
                });
            }
        });
    });
</script>
</body>
</html>