<include file="Public:header"/>
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">父分类</th>
		<th colspan="3">子分类</th>
	</tr>
	<if condition="$category_list">
		<volist name="category_list" id="vo">
			<tr>
				<th>{pigcms{$vo['cat_name']}</th>
				<th>
					<volist name="vo['son_list']" id="voo">
						{pigcms{$voo.cat_name}&nbsp;&nbsp;&nbsp;&nbsp;
					</volist>
	            </th>
	        </tr>
		</volist>
	<else/>
		<tr>
			<th colspan="2">暂无分类</th>
        </tr>
	</if>
</table>
<include file="Public:footer"/>