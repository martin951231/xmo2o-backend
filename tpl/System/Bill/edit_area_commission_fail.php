<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Bill/edit_area_commission_fail')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$id}">
		<input type="hidden" name="status" value="2">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">驳回原因</td>
				<td>
					<textarea class="input" style="width: 160px; height: 80px;" validate="required:true" name="status_txt"></textarea>
				</td>
			</tr>
		
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>