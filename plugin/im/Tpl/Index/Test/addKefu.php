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
        border: 1px solid #000
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
    <input type="hidden" name="store_id" id="store_id" class="layui-input" value="{pigcms{$store_id}"/>
    <volist name="params" id="p">
        <switch name="p.id">
            <case value="kefu_name">
                <div class="layui-form-item">
                    <label class="layui-form-label">{pigcms{$p.title} <span style="color: red">*</span></label>
                    <div class="layui-input-block">
                        <input type="text" name="kefu_name" id="kefu_name" lay-verify="title" placeholder="请输入客服名称"
                               class="layui-input" style="width: 50%">
                    </div>
                </div>
            </case>
            <case value="bind_user">
                <div class="layui-form-item">
                    <label class="layui-form-label">{pigcms{$p.title} <span style="color: red">*</span></label>
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn" onclick="bind_user()">选择粉丝</button>
                        <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$p.tip}</span>
                        <div class="user_show" id="bind-user-show">
                        </div>
                    </div>
                </div>
            </case>
            <case value="bind_consumer">
                <div class="layui-form-item">
                    <label class="layui-form-label">{pigcms{$p.title}</label>
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn" onclick="bind_consumer()">选择客户</button>
                        <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$p.tip}</span>
                        <div class="user_show" id="bind-consumer-show">
                        </div>
                    </div>
                </div>
            </case>
            <case value="welcome">
                <div class="layui-form-item">
                    <label class="layui-form-label">{pigcms{$p.title}</label>
                    <div class="layui-input-block">
                        <input type="text" name="welcome" lay-verify="welcome" placeholder="请填写首句欢迎语"
                               class="layui-input" style="width: 50%">
                        <p style="color: #9f9f9f; margin: 10px 53px;">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$p.tip}</p>
                    </div>
                </div>
            </case>
            <case value="max_receive">
                <div class="item_div">
                    <div class="layui-form-item">
                        <label class="layui-form-label">{pigcms{$p.title}</label>
                        <div class="layui-input-block">
                            <input type="number" name="max_receive" lay-verify="second" value="{pigcms{$kefuParams['max_receive']|default=50}" class="layui-input"
                                   style="display: inline-block;width: 20%"  min="0"/>
                            <span style="color: #9f9f9f">&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$p.tip}</span>
                        </div>
                    </div>
                </div>
            </case>
            <case value="can_send_coupon">
                <div class="layui-form-item">
                    <label class="layui-form-label">{pigcms{$p.title}</label>
                    <div class="layui-input-block">
                        <input type="checkbox" name="can_send_coupon" lay-skin="switch"
                               lay-filter="switchTest"
                               lay-text="ON|OFF"
                        <if condition="empty($kefuParams) || $kefuParams['recommended_goods'] == 1">checked="" value="1"</if>>
                    </div>
                    <p class="notice">{pigcms{$p.tip}</p>
                </div>
            </case>
            <default />未适配类型，请联系技术人员
        </switch>
    </volist>
    <div class="layui-form-item">
        <div class="layui-input-block">
            <button class="layui-btn layui-btn-radius" lay-submit="" lay-filter="add_kefu_submit">立即提交</button>
        </div>
    </div>
</form>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    resize_window()
    function resize_window() {
        var index = parent.layer.getFrameIndex(window.name);
        parent.layer.iframeAuto(index);
    }

    function remove_consumer(id, action) {
        layui.use('jquery', function () {
            var $ = layui.$;
            $('#' + action + '_' + id).remove();
            resize_window()
        })
    }

    function remove_binduser() {
        layui.use('jquery', function () {
            var $ = layui.$;
            $('#bind-user-show').html('');
            resize_window()
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
            resize_window()
        })
    }

    function bind_consumer_show(uid, nickname, avatar) {
        layui.use('jquery', function () {
            var $ = layui.$;
            if ($('#consumer_' + uid).length > 0) {
                return false;
            }
            var _html = '<div id="consumer_' + uid + '" class="pic_div">' +
                '<img style="width: 60px;height: 60px" src="' + avatar + '"/> ' + ' ' + nickname +
                '<div onclick="remove_consumer(' + uid + ',\'consumer\')" class="delete_right">×</div>' +
                '<input type="hidden" name="customer_uids[]" class="layui-input" value="' + uid + '"/>' +
                '</div>';
            $('#bind-consumer-show').append(_html);
            resize_window()
            return
        })
    }

    function bind_user() {
        layer.open({
            title: '选择粉丝',
            type: 2,
            area: ['80%', '80%'], //宽高
            btn: [],
            content: '{pigcms{:U("System/Customer/selectfans",array("tag"=>"system"))}',
            shadeClose: false,
            success: function (layero, index) {
            }
        });
    }

    function bind_consumer() {
        layer.open({
            title: '选择粉丝',
            type: 2,
            area: ['80%', '80%'], //宽高
            btn: [],
            content: '{pigcms{:U("System/Customer/selectfans",array("from"=>"bind_consumer","tag"=>"system"))}',
            shadeClose: false,
            success: function (layero, index) {
            }
        });
    }

    layui.use(['form'], function () {
        var $ = layui.jquery
            , form = layui.form;

        form.verify({
            title: function (value) {
                if (value.length <= 0) {
                    return '请输入客服名称';
                }
            }
        });

        //监听提交
        form.on('submit(add_kefu_submit)', function (data) {
            $.post("{pigcms{:U('addKefu')}", data.field, function (rs) {
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
    });
</script>
</body>
</html>