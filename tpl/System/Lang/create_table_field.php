<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Lang/create_table_field')}" frame="true" refresh="true">
		<input type="hidden" name="change_location" value="0"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th>选择一张表</th>
				<td><select name="table">
					<volist name="tables" id="vo">
					<option value="{pigcms{$vo}">pigcms_{pigcms{$vo}</option>
					</volist>
				</select></td>
			</tr>
			<tr>
				<th>增加的字段</th>
				<td><input type="text" name="add_fields"></td>
			</tr>
			<tr>
				<th>删除的字段</th>
				<td><input type="text" name="del_fields"></td>
			</tr>
			<tr>
				<td colspan="2" style="color:red;">适用于多语言表增加（或减少）了字段的场景，支持多个字段，多个字段用英文逗号隔开。</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>