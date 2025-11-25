<include file="Public:header"/>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th width="100">设备类别</th>
		<th>设备标识</th>
		<th>登录时间</th>
		<th>登录IP</th>
	</tr>
	<volist name="log_list" id="vo">
		<tr>
			<td width="100">
				<if condition="$vo['client'] eq 1">IOS
				<elseif condition="$vo['client'] eq 2"/>安卓
				<elseif condition="$vo['client'] eq 0"/>网页
				</if>
			</td>
			<td><if condition="$vo['device_id'] eq 'packapp'">网页<else/>{pigcms{$vo['device_id']}</if></td>
			<td>{pigcms{:date('Y-m-d H:i:s',$vo['add_time'])}</td>
			<td>{pigcms{$vo['add_ip']}</td>
		</tr>
	</volist>
	<tr>
		<td class="textcenter pagebar" colspan="4" style="border-bottom:1px solid #ccc;">{pigcms{$pagebar}</td>
	</tr>
</table>
<include file="Public:footer"/>