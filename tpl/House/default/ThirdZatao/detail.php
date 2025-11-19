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
    <style type="text/css">
    .green{ color:green}
    </style>
	<body>
		<table>
			<tr>
				<th width="15%">同步编号</th>
				<td width="85%">{pigcms{$detail.id}</td>
			</tr>
            <tr>
                <th width="15%">同步时间</th>
                <td width="85%">{pigcms{$detail.addtime|date='Y-m-d H:i:s',###}</td>
            </tr>
            <tr>
                <th width="15%">操作人</th>
                <td width="85%">{pigcms{$detail.admin_id}</td>
            </tr>
            <tr>
                <th width="15%">归属业务</th>
                <td width="85%" colspan="3">{pigcms{$detail.business}</td>
            </tr>
            <tr>
                <th width="15%">数据id</th>
                <td width="85%" colspan="3">{pigcms{$detail.data_id}</td>
            </tr>
            <tr>
				<th width="15%">同步状态</th>
				<td width="85%">
                    <if condition='$detail["status"] eq 0'>
                        <span class="red">同步中</span>
                        <elseif condition='$detail["status"] eq 1' />
                        <span class="green">同步成功</span>
                        <elseif condition='$detail["status"] eq 2' />
                        <span class="green">同步失败</span>
                    </if>
                </td>
			</tr>
            <tr>
                <th width="15%">失败原因</th>
                <td width="85%" colspan="3">{pigcms{$detail.fair_reason}</td>
            </tr>
            <tr>
				<th width="15%">同步内容</th>
				<td width="85%" colspan="3">{pigcms{$detail.content}</td>
			</tr>
		</table>
	</body>
</html>