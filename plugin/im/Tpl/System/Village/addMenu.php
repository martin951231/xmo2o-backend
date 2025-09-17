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
<style type="text/css">
    #link_div, #coupon_div {
        display: none
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
        margin: 5px 5px 5px 0;
        padding: 10px 5px;
        width: 45%
    }
    .layui-form-select dl {
        max-height: 200px;
    }

</style>
<body>
<form class="layui-form" action="" style="margin: 30px;padding-bottom:80px" id="add_menu_form">
    <input type="hidden" name="store_id" value="{pigcms{$store_id}"/>
    <div class="layui-form-item">
        <label class="layui-form-label">菜单名称</label>
        <div class="layui-input-block">
            <input type="text" name="name" id="name" lay-verify="title" placeholder="" class="layui-input">
        </div>
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">菜单类型</label>
        <div class="layui-input-block" id="select_menu">
            <select name="type" lay-filter="type">
                <option value=""></option>
                <volist name="menuTypes" id="m">
                    <option value="{pigcms{$key}">{pigcms{$m}</option>
                </volist>
            </select>
        </div>
    </div>

    <div class="layui-form-item" id="coupon_div">
        <label class="layui-form-label"></label>
        <div class="layui-input-block">
            <button type="button" class="layui-btn" onclick="select_coupon()"><span
                        style="width:1em;height: 1em;border-radius: 3em;border: 1px solid #fff">＋</span>&nbsp;添加优惠券
            </button>
            <div style="width: 80%;" id="select-coupon-show">
            </div>
        </div>
    </div>

    <div class="layui-form-item" id="link_div">
        <label class="layui-form-label">自定义链接</label>
        <div class="layui-input-block">
            <input type="text" name="link" class="layui-input" style="display: inline-block;" lay-verify="check_href"/>
        </div>
        <!--<span style="float:right;padding: 6px 12px;font-size: 0.8rem;color: #444444;cursor:pointer" onclick="function_lib()">从功能库选择</span>-->
    </div>
    <div class="layui-form-item">
        <label class="layui-form-label">排序值</label>
        <div class="layui-input-block">
            <input type="number" name="sort" class="layui-input" style="display: inline-block;" min="0" value="" placeholder="排序值越大，在用户端越靠前展示"/>
        </div>
    </div>


    <div class="layui-form-item">
        <div class="layui-input-block" style="text-align: left">
            <button type="button" class="layui-btn layui-btn-radius" lay-submit="" lay-filter="submit">确定</button>
        </div>
    </div>
</form>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    var store_id = '{pigcms{$store_id}';
    var selected_coupons_info = new Array();

    function select_coupon_show(coupon_id, name, img) {
        layui.use('jquery', function () {
            var $ = layui.$;
            if ($('#coupon_' + coupon_id).length > 0) {
                return false;
            }
            var _html = '<div id="coupon_' + coupon_id + '" class="pic_div">' + ' ' + name +
                '<div onclick="remove_select_coupon(' + coupon_id + ')" class="delete_right">×</div>' +
                '</div>';
            $('#select-coupon-show').append(_html);
            selected_coupons_info.push({coupon_id: coupon_id, coupon_name: name});
        })
        resize_window();
    }

    function remove_select_coupon(id) {
        layui.use('jquery', function () {
            var $ = layui.$;
            $('#coupon_' + id).remove();
            for (i in selected_coupons_info) {
                if (selected_coupons_info[i].coupon_id == id) {
                    selected_coupons_info.splice(i, 1)
                    break;
                }
            }
        })
        resize_window();
    }

    function select_coupon() {
        layer.open({
            title: '选择优惠券',
            type: 2,
            area: ['80%', '90%'], //宽高
            maxmin: false,
            content: '{pigcms{:U("System/Store/systemCoupon")}',
            shadeClose: false,
            success: function (layero, index) {
            }
        });
    }

    function function_lib() {
        art.dialog.open('./admin.php?g=Admin&c=Link&a=insert&plugin=im&plugin=im&iskeyword=0', {
            lock: true,
            title: '插入链接或关键词',
            width: 800,
            height: 500,
            yesText: '关闭',
            background: '#000',
            opacity: 0.45,
            close: function (url) {
                var checked_url = sessionStorage.getItem("function_url");
                if (checked_url) {
                    $('#link_div').find('input').val(checked_url)
                }
                sessionStorage.removeItem("function_url")
            }
        });
    }

    function resize_window() {
        var index = parent.layer.getFrameIndex(window.name);
        parent.layer.iframeAuto(index);
    }

    layui.use(['form', 'layedit'], function () {
        var $ = layui.jquery
            , layedit = layui.layedit
            , form = layui.form;

        form.verify({
            check_href: function (value) {
                if($('#link_div').is(":visible") && /^(https?|ftp|file)\:\/\/.+$/i.test(value) != true){
                    return '请输入有效的网址';
                }
            }
        });

        form.on('select(type)', function (data) {
            if (data.value == 'link') {
                $('#link_div').show();
            } else {
                $('#link_div').hide();
            }
            if (data.value == 'system_coupon') {
                $('#coupon_div').show();
            } else {
                $('#coupon_div').hide();
            }
            resize_window();
        })
        //监听提交
        form.on('submit(submit)', function (data) {
            var submit_data = data.field;
            submit_data.coupons = selected_coupons_info;

            $.post("{pigcms{:U('System/Village/addMenu')}", submit_data, function (rs) {
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
    });
</script>
</body>
</html>