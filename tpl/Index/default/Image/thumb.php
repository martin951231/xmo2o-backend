<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <!-- 移动端三件套 -->
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <meta name="format-detection" content="telephone=no">
    <title>403 - 无权访问</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%}
        body{
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f5f5f5;
            font-family:-apple-system,BlinkMacSystemFont,"Helvetica Neue",Helvetica,"PingFang SC","Hiragino Sans GB","Microsoft YaHei",SimSun,sans-serif;
            color:#fff;
            text-align:center;
            padding:0 20px
        }
        .card{
            width:100%;
            max-width:320px;
            background:rgba(255,255,255,.95);
            border-radius:12px;
            box-shadow:0 8px 24px rgba(0,0,0,.15);
            padding:40px 25px
        }
        .card h1{
            font-size:80px;
            color:#ff4757;
            margin-bottom:10px;
            font-weight:700
        }
        .card .msg{
            font-size:16px;
            color:#666;
            margin-bottom:25px;
            line-height:1.5
        }
        .btn{
            display:inline-block;
            width:100%;
            height:44px;
            line-height:44px;
            background:#667eea;
            color:#fff;
            font-size:15px;
            border-radius:22px;
            text-decoration:none;
            transition:background .3s
        }
        .btn:active{
            background:#764ba2
        }
    </style>
</head>
<body>
<div class="card">
    <h1>403</h1>
    <p class="msg">抱歉，您无权访问</p>
    <!-- 返回上一页 -->
    <a href="javascript:history.back()" class="btn">返回上一页</a>
</div>
</body>
</html>