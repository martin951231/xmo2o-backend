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

<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        <a href="{pigcms{:U('System/Plat/kefu',array('from'=>'system'))}"><li>客服列表</li></a>
        <a href="{pigcms{:U('System/Plat/shortPhrases',array('from'=>'system'))}"><li>快捷回复</li></a>
        <a href="{pigcms{:U('System/Plat/menu',array('from'=>'system'))}"><li>自助菜单</li></a>
        <a href="{pigcms{:U('System/Plat/privilege',array('from'=>'system'))}"><li>权限配置</li></a>
        <a href="{pigcms{:U('System/Plat/merchantChat',array('from'=>'system'))}"><li>商家客服聊天记录</li></a>
        <a href="{pigcms{:U('System/Plat/systemChat',array('from'=>'system'))}"><li class="layui-this">平台客服聊天记录</li></a>
    </ul>
    <div class="layui-tab-content">
        <div class="search_table" style="margin:30px auto">
            <div class="layui-inline">
                <select class="layui-select" name="type" lay-filter="type" id="search_type">
                    <option value="all">全部</option>
                    <option value="kf_name">客服名称</option>
                    <option value="customer_name">客户名称</option>
                </select>
            </div>
            <div class="layui-inline">
                <input class="layui-input" name="keyword" id="search_keyword" autocomplete="off" placeholder="请输入关键字">
            </div>
            <div class="layui-inline">
                &nbsp;&nbsp;会话状态&nbsp;
                <select class="layui-select" name="status" lay-filter="type" id="status">
                    <option value="-1">全部</option>
                    <option value="1">对话中</option>
                    <option value="0">已断开</option>
                </select>
            </div>
            <button class="layui-btn" data-type="reload" style="margin-left: 30px;">搜索</button>
        </div>
        <div class="layui-tab-item layui-show">
            <table class="layui-hide" id="chat_list" lay-filter="chat_list"></table>
        </div>
    </div>
</div>

<script type="text/html" id="operate_bar">
    <span style="color: blue" lay-event="chat_detail">查看聊天历史</span>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<!-- 注意：如果你直接复制所有代码到本地，上述js路径需要改成你本地的 -->

<script>
    layui.use(['table','jquery'], function () {
        var table = layui.table;
        var $ = layui.$
        table.render({
            elem: '#chat_list'
            , url: "{pigcms{:U('System/Plat/systemChat')}"
            , defaultToolbar: []
            , title: '会话列表'
            , cols: [[
                {field: 'chat_id', title: 'ID', width: 80, fixed: 'left', unresize: true, sort: true}
                //, {field: 'merchant_name', title: '所属商家'}
                //, {field: 'store_name', title: '所属店铺'}
                , {field: 'kf_name', title: '客服名称'}
                , {field: 'customer_name', title: '客户信息',templet:function(res){
                        return '<p><img style="width: 35px;height: 35px" src="' + res.customer_avatar + '"/> ' + res.customer_name + '</p>'
                    }}
                , {field: 'status', title: '会话状态'}
                , {field: 'last_time', title: '最近会话时间'}
                , {fixed: 'right', title: '操作', toolbar: '#operate_bar', width: 150}
            ]],
            id: 'chatListReload'
            ,page: {limit: 10, limits: [10, 50], groups: 10}
        });

        table.on('tool(chat_list)', function (obj) {
            var data = obj.data;
            if (obj.event === 'chat_detail') {
                layer.open({
                    title: '聊天历史',
                    type: 2,
                    area: ['40%', '80%'], //宽高
                    offset: '50px',
                    content: '/v20/public/platform/#/common/platform.kfChatHistory/KFChatHistory?from_user='+ data.from_user +'&to_user='+ data.to_user,
                    zIndex: layer.zIndex,
                    success: function (layero, index) {
                        //初始化数据
                    }
                });
            }
        });

        var $ = layui.$, active = {
            reload: function(){
                var keyword = $('#search_keyword').val();
                var type = $('#search_type').val();
                var status = $('#status').val();
                // if(keyword == ''){
                //     layer.msg('请输入关键词', {
                //         time: 2000
                //     });
                //     return false;
                // }
                //执行重载
                table.reload('chatListReload', {
                    page: {
                        curr: 1 //重新从第 1 页开始
                    }
                    ,where: {
                        keyword: keyword,
                        type:type,
                        status:status
                    }
                }, 'data');
            }
        };

        $('.search_table .layui-btn').on('click', function(){
            var type = $(this).data('type');
            active[type] ? active[type].call(this) : '';
        });
    });
</script>

</body>
</html>