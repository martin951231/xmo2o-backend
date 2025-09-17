<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="keywords" content="{pigcms{$config.seo_keywords}">
<meta name="description" content="{pigcms{$config.seo_description}">
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/index.js"></script>
<script src="{pigcms{$static_path}js/shop_reply.js"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<title>评论订单</title>
</head>
<body class=" hPC" style="padding-bottom: initial; background:#fff;">
<div id="page-loader" style="width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; z-index: 10000; text-align: center; display: none; background-color: rgba(200, 200, 200, 0.2);">
    <div style="margin-top:200px;color: white;background-color: rgba(30,30,30,0.8);padding: 10px;width: 140px;margin-left: auto;margin-right: auto;border-radius: 5px;font-size: 14px;  font-family: &#39;Helvetica&#39;;-webkit-box-shadow: 1px 1px 2px rgba(0,0,0,.4);">正在加载中……</div>
</div>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_5e96991.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_dd39d16.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_3a812b5.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shop_57c5f10.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shopcomment_4bd95ec.css">
<link href="{pigcms{$static_path}css/wap_uploadimg.css" rel="stylesheet"/>
<style type="text/css">
.border_b{position: relative;}
.border_b::after{
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: #ddd;
    transform: scaleY(0.33333);
    -webkit-transform: scaleY(0.33333);
}
.nav{
    padding:12px;
    overflow: auto;
    display:flex;
    align-items:center;
    justify-content: space-between;
}
.nav>p{
    display:flex;
    align-items:center;
}
.nav img{
    display: block;
    object-fit:cover;
    width:60px;
    height:60px;
}
.nav span{
    padding:0 5px 0 10px;
    color: #000;
}
.nav.title span{
    padding:0;
}
a.btn{
    border: 1px solid #e5e5e5;
    color: #fff;
    padding:2px 5px;
    border-radius: 4px;
    display: block;
    float: right;
    white-space: nowrap;
    background: #bbb;
    padding:5px 10px;
    font-weight: bold;
}
a.btn.active{
    background: red;
}


</style>
<body>
    <!-- 是否评价配送员 -->
    <lt name='now_order.is_pick_in_store' value='2'>
        <div class="nav border_b title">
            <p>
                <!-- <img src="{pigcms{$info.image}"/> -->
                <span>配送员点评</span>
            </p>
            <eq name='info.is_goods' value='1'>
                <a class="btn">
                    已评论
                </a>
            <else/>
                <a class="btn active" href="{pigcms{:U('My/shop_feedback',array('order_id'=>$order_id,'order_detail_id'=>0,'is_service'=>1))}">
                    去评论
                </a>
            </eq>
        </div>
    </lt>
    <volist name='list' id='vo'>
        <div class="nav border_b">
            <p>
                <img src="{pigcms{$vo.goods_image}"/>
                <span>{pigcms{$vo.name}</span>
            </p>
            <eq name='vo.is_goods' value='1'>
                <a class="btn">
                    已评论
                </a>
            <else/>
                <a class="btn active" href="{pigcms{:U('My/shop_feedback',array('order_id'=>$order_id,'order_detail_id'=>$vo['id']))}">
                    去评论
                </a>
            </eq>
        </div>
    </volist>
</body>
</html>