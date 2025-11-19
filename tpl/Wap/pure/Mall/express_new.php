<!DOCTYPE html>
 <html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui">
	<meta name="format-detection" content="telephone=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<title>快递单轨迹</title>
	<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
	<meta name="description" content="{pigcms{$config.seo_description}" />
    <link href="{pigcms{$static_path}shop/css/order_detail.css" rel="stylesheet"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
</head>

<body>
<section class="g_details">
    <div class="orders_list">
        <ul>
            <volist name="result['Traces']" id="vo">
            <li>
                <div class="time">{pigcms{$vo.AcceptTime}</div>
                <div class="p18">
                    <div class="con">
						<h2>{pigcms{$vo.AcceptStation}</h2> 
						<p>{pigcms{$vo.Location}</p>
                    </div>
                </div>
            </li>
            </volist>
        </ul>
    </div>
</section>
</body>
</html>