<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>预约列表页</title>
<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name='apple-touch-fullscreen' content='yes'>
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="address=no">
<link rel="stylesheet" href="{pigcms{$static_path}css/swiper.min.css" type="text/css">
<link href="{pigcms{$static_path}css/cate_list.css" rel="stylesheet" />
<link href="{pigcms{$static_path}css/base.css" rel="stylesheet" />
<script src="{pigcms{$static_path}js/rem.js"></script>
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script type="text/javascript">
var productUrl="{pigcms{:U(productList)}";
var cate_num={pigcms{$cate_list['cate_num']};
if(cate_num==1){
	var curr_id = "{pigcms{$cate_list[0][cat_id]}";
	location.href=productUrl+"&cat_id="+curr_id;
}
</script>
</head>

<body>
<article>
<section class="categroy">


    <ul class="categroy_list">
    <volist name="cate_list['cate_list']" id="vo">
        <li> <a href="{pigcms{:U('Appoint/productList',array('cat_id'=>$vo['cat_id']))}">
            <div class="categroy_list_img"><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$vo.cat_pic}"></div>
            <p>{pigcms{$vo['cat_name']}</p>
            </a> </li>
        </volist>
    </ul>
    <i class="close"></i>
    </section>
</article>
<script type="text/javascript">
$('.close').click(function(){
	var Url="{pigcms{:U('index')}";
	location.href=Url;
});
</script>
</body>
</html>
