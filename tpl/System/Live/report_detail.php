<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1" width="120">举报编号</th>
		<th colspan="14">{pigcms{$info['id']}</th>
	</tr>
	<tr>
		<th colspan="1">举报人</th>
		<th colspan="14">{pigcms{$info['nickname']}</th>
	</tr>
	<tr>
		<th colspan="1">业务</th>
		<th colspan="14">{pigcms{$info['business_txt']}</th>
	</tr>
	<if condition="!empty($info['report_obj'])">
	<tr>
		<th colspan="1">举报对象</th>
		<th colspan="14">{pigcms{$info['report_obj']}</th>
	</tr>
	</if>
	<tr>
		<th colspan="1">举报类型</th>
		<th colspan="14">{pigcms{$info['category_txt']}</th>
	</tr>
	<tr>
		<th colspan="1">举报内容</th>
		<th colspan="14">{pigcms{$info['content']}</th>
	</tr>
	<tr>
		<th colspan="1">举报时间</th>
		<th colspan="14">{pigcms{$info['create_time']}</th>
	</tr>
	<tr>
		<th colspan="1">举报依据</th>
		<th colspan="14">
			<empty name="info.imgarr">
				无
			<else />
				<volist name="info.imgarr" id="vo">
				<img src="{pigcms{$vo}" style="width: 375px;height: 667px" />
				</volist>
			</empty>
		</th>
	</tr>
</table>

<include file="Public:footer"/>