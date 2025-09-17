<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("优惠数量增加记录")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
		</style>
	</head>
	<body>
		<table>
			<tr>
				<th width="180">{pigcms{:L_("时间")}</th>
				<th>{pigcms{:L_("数量")}</th>
		
			</tr>
			<volist name="list" id="vo">
				<tr>
					<th width="180">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</th>
				
					<th><if condition="$vo['add'] eq 1"><font color="#2bb8aa">+{pigcms{$vo.num}</font><else/><font color="#f76120">-{pigcms{$vo.num}</font></if></th>
				</tr>
			</volist>
			
			
		</table>
		


	</body>
</html>
