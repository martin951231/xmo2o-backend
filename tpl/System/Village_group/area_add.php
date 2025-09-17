<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('area_add')}" frame="true" refresh="true">
		<input type="hidden" name="area_type" value="{pigcms{$_GET['type']}"/>
		<input type="hidden" name="area_pid" value="{pigcms{$_GET['pid']}"/>
		<input type="hidden" name="change_location" value="0"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td>
					<select name="area_id">
						<volist name="area_list" id="vo">
							<option value="{pigcms{$vo.area_id}">{pigcms{$vo.area_name}</option>
						</volist>
					</select>
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