<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>店铺优惠券</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css"  media="all"/>
    <link rel="stylesheet" href="{pigcms{$plugin_public}im/css/base.css"  media="all"/>
</head>
<body>

<table class="layui-hide" id="store_coupons" lay-filter="store_coupons"></table>

<script type="text/html" id="user_listBar">
    <a class="layui-btn layui-btn-xs" lay-event="select_coupon">选中</a>
</script>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script>
    var store_id = {pigcms{$storeId}

    layui.use(['table','jquery'], function(){
        var table = layui.table;
        var $ = layui.$
        //方法级渲染
        table.render({
            elem: '#store_coupons'
            ,url: '{pigcms{:U("System/Store/coupons")}'+'&store_id='+store_id
            ,cols: [[
                {field:'name', title: '优惠券名称'}
                ,{field:'img', title: '图片', templet: function(res){
                        return '<img style="height: 35px" src="'+ res.img +'"/>'
                    }}
                ,{field:'cate_name', title: '分类'}
                ,{field:'left_num', title: '剩余数量'}
                ,{fixed: 'right', title:'操作', toolbar: '#user_listBar'}
            ]]
            ,id: 'testReload'
            ,page: {limit:5,limits:[5,10,50],groups:10}
        });

        table.on('tool(store_coupons)', function(obj){
            var data = obj.data;
            var $ = layui.$;
            if(obj.event === 'select_coupon'){
                parent.select_coupon_show(data.coupon_id, data.name, data.img);
                var index = parent.layer.getFrameIndex(window.name);
                parent.layer.close(index);
            }
        });
    });
</script>

</body>
</html>