<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>支付跳转</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
	<link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
</head>
<body>
	<!--header  class="navbar">
        <h1 class="nav-header">支付跳转</h1>
    </header-->
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
	<script>layer.open({type:2,content:'支付跳转中,请稍等',shadeClose:false});</script>
	<if condition="$url">
		<script>
			//避免多次自动跳转
			if(!sessionStorage.getItem('h5_pay_key') || sessionStorage.getItem('h5_pay_key') != "{pigcms{$_POST.order_type}_{pigcms{$_POST.order_id}"){
				sessionStorage.setItem('h5_pay_key', "{pigcms{$_POST.order_type}_{pigcms{$_POST.order_id}");
				var timer = setTimeout(function(){
					
					if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
						//询问框
						layer.open({
							content: '页面正在唤起支付，浏览器若提示是否允许打开APP，请点击允许。'
							,btn: ['支付成功', '重新支付']
							,yes: function(index){
								sessionStorage.removeItem("h5_pay_key");
								layer.open({type:2,content:'支付跳转中,请稍等',shadeClose:false});
								setTimeout(function(){
									location.reload();
								}, 1000);
							}
							,no: function(index){
								sessionStorage.removeItem("h5_pay_key");
								location.reload();
							}
						});
					}
					
					clearTimeout(timer);
					window.location.href = '{pigcms{$url}';
				}, 300);
			}else{
				layer.open({type:2,content:'支付校验中,请稍等',shadeClose:false});
				sessionStorage.removeItem("h5_pay_key");	
				if('{pigcms{$_POST.pay_type}' == 'weixinh5'){
					setTimeout(function(){
						$.post('/appapi.php?c=Pay&a=app_weixin_back', {order_id:'{pigcms{$_POST.order_id}', order_type:'{pigcms{$_POST.order_type}', app_type:'packapp'}, function(res){
							if (res.errorCode == 0) {
								location.href = res.result;
							}else{
								layer.open({
									content: '页面正在唤起支付，浏览器若提示是否允许打开APP，请点击允许。'
									,btn: ['支付成功', '重新支付']
									,yes: function(index){
										sessionStorage.removeItem("h5_pay_key");
										location.reload();
									}
									,no: function(index){
										sessionStorage.removeItem("h5_pay_key");
										location.reload();
									}
								});
							}
						});
					},2000);
				}else{
					layer.open({
						content: '页面正在唤起支付，浏览器若提示是否允许打开APP，请点击允许。'
						,btn: ['支付成功', '重新支付']
						,yes: function(index){
							sessionStorage.removeItem("h5_pay_key");
							location.reload();
						}
						,no: function(index){
							sessionStorage.removeItem("h5_pay_key");
							location.reload();
						}
					});
				}
			}
		</script>
	<elseif condition="$form"/>
		{pigcms{$form}
	</if>
{pigcms{$hideScript}
</body>
</html>