<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>申请团长</title>
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
    <style>
        .page { padding: 0.2rem;font-size: 0.28rem;color: #333;}
        .title { font-weight: 600;margin-bottom: 0.2rem;}
        .input-wrapper { height: 1rem;line-height: 1rem;margin-right:-0.2rem;position: relative;}
        .input-wrapper text { width: 2rem;display: inline-block;}
        .input-wrapper input { width: calc(100% - 3rem);color: #333; }
        button {background: #40bc06;color: #fff;margin-top: 2rem;width: 100%;height: 0.8rem;line-height: 0.8rem;border-radius: 0.08rem;}
    </style>
</head>
<body>
    <div class="page">

        <div class="title">申请团长</div>
        <form action="" method="post">
			<div class="input-wrapper border-b" >
                <text>自提地址</text>
                <input type="text" name="address" value="{pigcms{$pick_addr.pick_addr}" placeholder="请填写地址" readOnly>
                <input type="hidden" name="pick_id" value="{pigcms{$_GET['id']}">
            </div>
            <div class="input-wrapper border-b">
                <text>电话</text>
                <input type="text" name="phone" placeholder="请填写电话号码">
            </div>
            <div class="input-wrapper border-b" >
                <text>微信号</text>
                <input type="text" name="wx_number" placeholder="请填写微信号">
            </div>
            
            <button type="submit">提交</button>
        </form>
    </div>
</body>
</html>