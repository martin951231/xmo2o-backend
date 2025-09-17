<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Bill/edit_area_commission')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$id}">
		<input type="hidden" name="status" value="1">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">兑换方式</td>
				<td><input type="text" class="input fl" name="paytype" value=""> 我兑现的支付方式，一般用于备注，比如：通过银行卡兑现。
				</td>
			</tr>
		
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>