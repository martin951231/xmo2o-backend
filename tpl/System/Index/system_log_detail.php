<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">操作编号</th>
		<th colspan="14">{pigcms{$info.id}</th>
	</tr>
	<tr>
		<th colspan="1">操作时间</th>
		<th colspan="14">{pigcms{$info.addtime|date='Y-m-d H:i:s',###}</th>
	</tr>
	<tr>
		<th colspan="1">管理员</th>
		<th colspan="14">{pigcms{$info.realname}</th>
	</tr>
	<notempty name="info.business">
	<tr>
		<th colspan="1">归属业务</th>
		<th colspan="14">{pigcms{$info.business}</th>
	</tr>
	</notempty>
	<tr>
		<th colspan="1">简要内容</th>
		<th colspan="14">{pigcms{$info.content}</th>
	</tr>
	<notempty name="info.post_content">
	<tr>
		<th colspan="1">post数据</th>
		<th colspan="14">
			<code>
				<php>dump($info['post_content'])</php>
			</code>
		</th>
	</tr>
	</notempty>
	<notempty name="info.before_form">
	<tr>
		<th colspan="1">修改之前</th>
		<th colspan="14">
			<code>
				<php>dump($info['before_form'])</php>
			</code>
		</th>
	</tr>
	</notempty>
	<notempty name="info.after_form">
	<tr>
		<th colspan="1">修改之后</th>
		<th colspan="14">
			<code>
				<php>dump($info['after_form'])</php>
			</code>
		</th>
	</tr>
	</notempty>
</table>

<include file="Public:footer"/>