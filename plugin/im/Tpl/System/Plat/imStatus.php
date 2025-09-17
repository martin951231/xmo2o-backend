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

<table class="layui-table">
  <colgroup>
    <col width="150">
    <col width="200">
    <col>
  </colgroup>
  <thead>
    <tr>
      <th>标题</th>
      <th>描述</th>
    </tr> 
  </thead>
  <tbody>
        <tr>
            <td>运行状态</td>
            <td><if condition="$isRunning">运行中<else/><font style="color:red">未运行</font></if></td>
        </tr>
    <if condition="$data">
        <tr>
            <td>启动时间</td>
            <td>{pigcms{$data.beigin_time}</td>
        </tr>
        <tr>
            <td>运行时长</td>
            <td>{pigcms{$data.up_days}</td>
        </tr>
    </if>
    <tr>
        <td>操作</td>
            <td>
                <button onclick="change('start')" class="layui-btn layui-btn-sm <if condition="$isRunning">layui-btn-disabled</if>">启动</button>
                <button onclick="change('stop')" class="layui-btn layui-btn-danger layui-btn-sm  <if condition="!$isRunning">layui-btn-disabled</if>">停止</button>
                <button onclick="change('restart')" class="layui-btn layui-btn-warm layui-btn-sm  <if condition="!$isRunning">layui-btn-disabled</if>">重启</button>
            </td>
        </tr>
  </tbody>
</table>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script type="text/javascript">
    function change(type) {
        layui.use('jquery', function () {
            var $ = layui.$;

            $.post("{pigcms{:U('System/Plat/imStatus')}", {type: type}, function (res) {
            if (res.status == 1) {
                layer.msg(res.info, {icon: 1});
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else {
                layer.msg(res.info, {icon: 2});
            }
            }, 'json');
        })
    }
</script>

</body>
</html>