<include file="Public:header"/>
<form id="myform" method="post" action="" enctype="multipart/form-data">
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="80">名称</th>
			<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="名称"/></td>
		</tr>
		<tr>
			<th width="80">价格</th>
			<td><input type="text" class="input fl" name="price" size="20" validate="maxlength:20,required:true" tips="价格"/></td>
		</tr>
		<tr>
			<th width="80">原价</th>
			<td><input type="text" class="input fl" name="old_price" size="20" validate="maxlength:20,required:true" tips="原价"/></td>
		</tr>
		<tr>
			<th width="80">有效时间</th>
			<td><input type="text" class="input fl" name="active_time" size="20" validate="maxlength:20,required:true" tips="在此期限内有效"/>天</td>
		</tr>
		<tr>
			<th width="80">排名排序</th>
			<td><input type="number" class="input fl" name="ranking_sort" size="20" validate="maxlength:20" tips="数字越大越靠前"/></td>
		</tr>
		<tr>
			<th width="80">排序</th>
			<td><input type="number" class="input fl" name="sort" size="20" validate="maxlength:20" tips="数字越大越靠前"/></td>
		</tr>
		<tr>
			<th width="80">状态</th>
			<td>
				<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked"/></label></span>
				<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0"/></label></span>
			</td>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<include file="Public:footer"/>