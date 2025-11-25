<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('user_money_verify_handle')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">审核理由：</td>
				<td>
					<input type="text" name="reason" value=""  class="input fl" >
					<input type="hidden" name="id" value="{pigcms{$_GET['id']}">
				</td>
			</tr>
			
			<tr>
				<td width="80">状态：</td>
				<td>
				
				<select name="status">
				<option value="1">通过</option>
				<option value="2">拒绝</option>
				</select>
				
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
