<include file="Public:header"/>
	<div class="mainbox">
		<form method="post"  id="myform" action="__SELF__" refresh="true" enctype="multipart/form-data" >
			<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<input type="hidden" class="input-text" name="pick_id" value="{pigcms{$pick_address.pick_id}" />
				<tr>
					<th width="100">登录秘钥</th>
					<td>
						{pigcms{$pick_address['pwd']}
					</td>
					<th><button type="submit">更换秘钥</button></th>
				</tr>
			</table>
			<div class="btn hidden">
				<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
				<input type="reset" value="取消" class="button" />
			</div>
		</form>
	</div>
<include file="Public:footer"/>