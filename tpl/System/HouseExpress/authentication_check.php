<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('HouseExpress/authentication_check')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">用户ID：</td>
				<td>
					<label>{pigcms{$authenticationInfo['uid']}</label>
					<input type="hidden" name="authentication_id" value="{pigcms{$authenticationInfo['authentication_id']}">
				</td>
			</tr>

			<volist name="authenticationInfo['authentication_field']" id="vo">
				<if condition="$vo['type'] eq 1">
					<tr>
						<td width="80">{pigcms{$vo.title}：</td>
						<td>
							<label>{pigcms{$vo.value}</label>
						</td>
					</tr>
				<elseif condition="$vo['type'] eq 2"/>
					<tr>
						<td width="80">{pigcms{$vo.title}：</td>
						<td><img style="width:400px;" src="{pigcms{:file_domain().$vo['value']}" /><br/></td>
					</tr>
				</if>
			</volist>
			<if condition="$status neq 1">
				<tr>
					<td width="80">审核备注：</td>
					<td>
						<select name="authentication_status" id="authentication_status">
							<option value="2" selected="selected">审核通过</option>
							<option value="3">审核失败！</option>
						</select>
					</td>
				</tr>

				<tr id="authentication">
					<td width="80">审核原因：</td>
					<td>
						<textarea type="" name="note"></textarea>
					</td>
				</tr>
			</if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="审核" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript">
	$(document).ready(function(){
		$("#authentication").hide()
	});
	$("#authentication_status").click(function(){
		if($("#authentication_status").val() == 3){
           $("#authentication").show()
		}else{
			$("#authentication").hide()
		}

	})
</script>
<include file="Public:footer"/>
