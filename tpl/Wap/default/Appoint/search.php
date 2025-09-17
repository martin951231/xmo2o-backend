<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>搜索</title>
<meta name="description" content="<?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>搜索">
<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name='apple-touch-fullscreen' content='yes'>
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="address=no">
<link href="{pigcms{$static_path}css/cate_list.css" rel="stylesheet" />
<link href="{pigcms{$static_path}css/base.css" rel="stylesheet" />
<script src="{pigcms{$static_path}js/rem.js"></script>
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
</head>
<body  class="search_content">
<header>
    <div class="categroy_header table">
        <ul class="clearfix">
            <li><a href="{pigcms{:U('Appoint/productList',array('cat_id'=>1))}">项目</a></li>
             <li class="active"><a href="{pigcms{:U('Appoint/workerList',array('cat_id'=>1))}"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?></a></li>
        </ul>
       </div>
</header>
<header class="search_header clearfix"><div class="search"><i></i><input type="text" placeholder="搜索<?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>名称或手机号" id="search_txt"><em></em></div> <span id="search">搜索</span></header>
<script type="text/javascript">
$('#search').click(function(){
	var keyword = $('#search_txt').val();
	
	if(!keyword){
		alert('请输入内容！');
		return false;
	}
	
	var Url="{pigcms{:U('search_result')}";
	location.href=Url+'&keyword='+keyword;
})

$('#search_txt').next('em').click(function(){
	$('#search_txt').val('');
});
</script>
</body>
</html>
