<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>{pigcms{:L_("成功提示")}</title>
		<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name='apple-touch-fullscreen' content='yes'>
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
		<meta name="format-detection" content="address=no">
		<link href="{pigcms{$static_path}layer/need/layer.css" rel="stylesheet"/>
		<script src="{pigcms{$static_path}layer/layer.m.js"></script>
	</head>
	<body>
        <script>
			var location_url = '{pigcms{$url}';
            layer.open({
                title: ['{pigcms{:L_("成功提示")}', 'background-color:{pigcms{$config["mobile_head_color"]};color:#fff;'],
                content: '{pigcms{$msg}',
                btn: ['{pigcms{:L_("确定")}'],
                end: function () {
                    var lastPageNeedReload = '{pigcms{$lastPageNeedReload}';
                    if (lastPageNeedReload) {
                        sessionStorage.setItem('lastPageNeedReload', lastPageNeedReload);
                    }
                    <php>if(ACTION_NAME == 'village_group_order_check_refund'){</php>
						sessionStorage.setItem('lastPageNeedReload','village_group_order_check_refund');
						history.go(-2);
                    <php>}else if(ACTION_NAME == 'group_order_check_refund'){</php>
                        sessionStorage.setItem('lastPageNeedReload','group_order_check_refund');
                        history.go(-1);
					<php>}else{</php>
						if(location_url.indexOf('javascript') == -1){
							var stateObj = { foo: 'success' };
							history.replaceState(null, null, location_url);
							location.reload();
						}else{
							//location.href = location_url;
                            location.replace(location_url);
						}
					<php>}</php>
                }
            });
		</script>
    </body>
</html>