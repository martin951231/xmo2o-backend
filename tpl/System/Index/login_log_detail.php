<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<div>
	<p>注意：这套识别程序的数据库是免费IP数据库、IP离线地址库，因此有误差、获取不到一些数据在所难免。仅供参考作用。</p>
</div>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th >操作编号</th>
		<td >{pigcms{$info.id}</td>
		<th >操作时间</th>
		<td >{pigcms{$info.add_time|date='Y-m-d H:i:s',###}</td>
	</tr>

	<tr>
		<th >操作账号</th>
		<td >{pigcms{$info.account|htmlspecialchars}</td>
		<th >账号名称</th>
		<td >{pigcms{$info.realname|htmlspecialchars}</td>
	</tr>

	<tr>
		<th >登录IP</th>
		<td >{pigcms{$info.ip}</td>
		<th >登录来源</th>
		<td >{pigcms{$info.country}</td>
	</tr>

	<tr>
		<th >登录方式</th>
		<td >{pigcms{$info.login_type}</td>
		<th >country</th>
		<td >{pigcms{$info.country}</td>
	</tr>

	<tr>
		<th >省份</th>
		<td >{pigcms{$info.province}</td>
		<th >城市</th>
		<td >{pigcms{$info.city}</td>
	</tr>


	<tr>
		<th >操作系统</th>
		<td >{pigcms{$info.os}</td>
		<th >操作系统版本</th>
		<td >{pigcms{$info.os_version}</td>
	</tr>

	<tr>
		<th >浏览器</th>
		<td >{pigcms{$info.browser_name}</td>
		<th >浏览器版本</th>
		<td >{pigcms{$info.browser_version}</td>
	</tr>

	<tr>
		<th >ISP</th>
		<td >{pigcms{$info.isp}</td>
		<th >county</th>
		<td >{pigcms{$info.county}</td>
	</tr>
	<tr>
		<th >制造商</th>
		<td >{pigcms{$info.manufacturer}</td>
		<th >model</th>
		<td >{pigcms{$info.model}</td>
	</tr>
	<tr>
		<th >备注</th>
		<td >{pigcms{$info.reson|htmlspecialchars}</td>
		<th > </th>
		<td > </td>
	</tr>



</table>

<include file="Public:footer"/>