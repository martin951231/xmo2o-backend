<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<div class="table-list">
	<table width="100%" cellspacing="0">
		<thead>
			<tr>
				<th class="textcenter">店员账号</th>
				<th class="textcenter">操作类型</th>
				<th class="textcenter">设备号</th>
				<th class="textcenter">版本名称</th>
				<th class="textcenter">登录ip</th>
				<th class="textcenter">操作时间</th>
			</tr>
		</thead>
		<tbody>
			<notempty name="log_list">
			<volist name="log_list" id="vo">
			<tr>
				<td class="textcenter">{pigcms{$staff_account}</td>
				<td class="textcenter"><if condition="$vo.type eq '1'"><font color="red">登出</font><else />登入</if></td>
				<td class="textcenter"><if condition="$vo['device_id'] neq 'packapp'">{pigcms{$vo.device_id}<else/>网页版</if></td>
				<td class="textcenter">{pigcms{$vo.app_version_name}</td>
				<td class="textcenter">{pigcms{$vo.add_ip}</td>
				<td class="textcenter">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
			</tr>
			</volist>
			<tr>
				<td colspan="5" class="textcenter pagebar">{pigcms{$pagebar}</td>
			</tr>
			<else />
			<tr>
				<td colspan="5" class="textcenter red">列表为空！</td>
			</tr>
		</notempty>
		</tbody>
	</table>
</div>
<include file="Public:footer"/>