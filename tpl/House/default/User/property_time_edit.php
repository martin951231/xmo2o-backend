<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 小区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
		<form id="myform" action="{pigcms{:U('property_time_edit')}" method='post'>
			<input type="hidden" name="idList" value="{pigcms{$_GET['idList']}" >
			<table>
				<tr>
					<th width="25%">物业开始时间</th>
					<td width="75%">
						<input type="text" name="property_starttime" class="input-text" value=""  style="height:30px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（开始）"/>
					</td>
				</tr>
				<tr>
					<th width="25%">物业结束时间</th>
					<td width="75%">
						<input type="text" name="property_endtime" class="input-text" value=""  style="height:30px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（结束）"/>
					</td>
				</tr>
				<tr class="btn hidden" style="display: none;">
					<td colspan="4"><button class="chk_express" id="dosubmit">保存</button></td>
				</tr>
			</table>
		</form>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
</html>