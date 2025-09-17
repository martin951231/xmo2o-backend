<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<div class="table-list">
	<table width="100%" cellspacing="0">
		<thead>
			<tr>
				<th class="textcenter">店员APP账号</th>
				<th class="textcenter">设备类型</th>
				<th class="textcenter">设备号</th>
				<th class="textcenter">APP版本号</th>
				<th class="textcenter">当前是否登录</th>
				<th class="textcenter">当前接收新订单通知</th>
				<th class="textcenter">当前通讯状态</th>
				<th class="textcenter">登录日志</th>
			</tr>
		</thead>
		<tbody>
			<notempty name="staff_list">
				<volist name="staff_list" id="vo">
					<tr>
						<td class="textcenter">{pigcms{$vo.username}</td>
						<td class="textcenter">{pigcms{$vo.phone_brand}</td>
						<td class="textcenter"><if condition="$vo['device_id'] neq 'packapp'">{pigcms{$vo.device_id}<else/>网页版</if></td>
						<td class="textcenter">{pigcms{$vo.app_version_name}</td>
						<td class="textcenter"><if condition="$vo.is_login eq '1'">登录中<else /><font color="red">未登录</font></if></td>
						<td class="textcenter"><if condition="$vo.is_notice eq '1'"><font color="red">不接收</font><else />接单</if></td>
						<td class="textcenter"><if condition="$vo.pingpang eq '1'">正常<else /><font color="red">异常</font></if></td>
						<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Storestate/staff_log',array('staff_id'=>$vo['id'],'account'=>$vo['username']))}','查看{pigcms{$vo.username}登录日志',850,560,true,false,false,'','show',true);">查看登录日志</a></td>
					</tr>
				</volist>
			<else />
				<tr>
					<td colspan="8" class="textcenter red">列表为空！</td>
				</tr>
			</notempty>
		</tbody>
	</table>
</div>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>