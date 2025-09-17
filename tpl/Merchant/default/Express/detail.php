<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("订单详情")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th style="width:20%;">{pigcms{:L_("时间")}</th>
            <th>{pigcms{:L_("状态")}</th>
        </tr>
        <volist name="result['Traces']" id="vo">
			<tr>
				<td>{pigcms{$vo['AcceptTime']}</td>
				<td>{pigcms{$vo['AcceptStation']}</td>
			</tr>
		</volist>
    </table>
	<script>
		$('.open_webview').click(function(){
			var url_info =  $(this).attr('href');
			parent.layer.open({
				title:'快递轨迹',
				type:2,
				content:url_info,
				area:['600px','500px'],
				shade: 0.2,
				cancel:function(){

				}
			});
			return false;
		});
	</script>
</body>
</html>