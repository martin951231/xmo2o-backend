<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Lang/service_lang_add')}" frame="true" refresh="true">
		<input type="hidden" name="change_location" value="0"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">语言标识符</th>
				<td><input type="text" class="input fl" name="val" size="20" placeholder="非常重要" validate="maxlength:30,required:true"/></td>
			</tr>
			<tr>
				<th width="80">客户端agent</th>
				<td><input type="text" class="input fl" name="as" id="as" size="20" placeholder=""/></td>
			</tr>
			<tr>
				<th width="80">语言包别名</th>
				<td><input type="text" class="input fl" name="alias" size="20" value="" /></td>
			</tr>
			<tr>
				<th width="80">语种</th>
				<td><input type="text" class="input fl" name="display" size="20" value="" /></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>