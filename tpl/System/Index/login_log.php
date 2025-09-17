<include file="Public:header"/>
<div class="mainbox">
	<div class="table-list">

		<div id="nav" class="mainnav_title">
			<p style="font-weight: bold;color: #0a0a0a;border: none;">注意：这套识别程序的数据库是免费IP数据库、IP离线地址库，因此有误差、获取不到一些数据在所难免。仅供参考作用。</p>
		</div>
		<table width="100%" cellspacing="0">
			<colgroup>
				<col/><col/><col/><col/><col/><col/><col/><col/><col/><col/><col/><col width="180" align="center"/>
			</colgroup>
			<thead>
			<tr>
				<th>编号</th>
				<th>操作时间</th>
				<th>操作账号</th>
				<th>账号名称</th>
				<th>登录IP</th>
				<th>登录来源</th>
				<th>登录方式</th>
				<th>操作系统</th>
				<th>操作系统版本</th>
				<th>浏览器</th>
				<th>浏览器版本</th>
				<th>备注</th>
				<th class="textcenter">操作</th>
			</tr>
			</thead>
			<tbody>
			<notempty name="data">
				<volist name="data" id="vo">
					<tr>
						<td>{pigcms{$vo.id}</td>
						<td >{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
						<td>{pigcms{$vo.account|htmlspecialchars}</td>
						<td>{pigcms{$vo.realname|htmlspecialchars}</td>
						<td>{pigcms{$vo.ip}</td>
						<td >{pigcms{$vo.country}</td>
						<td >{pigcms{$vo.login_type}</td>
						<td >{pigcms{$vo.os}</td>
						<td >{pigcms{$vo.os_version}</td>
						<td >{pigcms{$vo.browser_name}</td>
						<td >{pigcms{$vo.browser_version}</td>
						<td >{pigcms{$vo.reson|htmlspecialchars}</td>
						<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Index/login_log_detail',array('log_id'=>$vo['id'],'frame_show'=>true))}','操作详情',900,'90%',true,false,false,false,'detail',true);">详情</a></td>
					</tr>
				</volist>
				<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
				<else />
				<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
			</notempty>
			</tbody>
		</table>
	</div>
</div>

<include file="Public:footer"/>