<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Village_group/area_edit')}" frame="true" refresh="true">
		<input type="hidden" name="pigcms_id" value="{pigcms{$area_detail['pigcms_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td>
					{pigcms{$area_detail.area_name}
				</td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="area_sort" size="10" value="0" validate="required:true,number:true,maxlength:6" tips="数值越大，排序越前"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>