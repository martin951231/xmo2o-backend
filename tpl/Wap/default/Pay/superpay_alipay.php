<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>alipay</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="zh-CN">
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta http-equiv="Cache-Control" content="max-age=1700">
    <meta name="viewport" content="initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="no">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta http-equiv="cleartype" content="on">
    <script src="https://appx/web-view.min.js"></script>
    <!-- 请尽量在 html 头部执行以下脚本 -->
    <script>
      var alipay_order_no = '{pigcms{$alipay_order_no}';
      var order_url = '{pigcms{$order_url}';
      // 判断是否运行在小程序环境里
      my.redirectTo({url: '/pages/foodshop/payPage/payPage?tradeno='+alipay_order_no+'&order_url='+order_url});
    </script>
</head>
<body>

</body>
</html>