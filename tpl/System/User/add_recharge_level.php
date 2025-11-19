<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/add_recharge_level')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">充值金额</td>
				<td>
				<input type="text" class="input fl" name="money" value="{pigcms{$recharge_level['money']|floatval}">
				</td>
			</tr>
		
			<tr>
				<td width="80">选择等级</td>
				<td>
					<select name="level">
						<volist name="level" id="vo">
							<option value="{pigcms{$vo.level}" <if condition="$recharge_level['level'] eq $vo['level']">selected</if> >{pigcms{$vo.lname}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<td width="100">状态</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$recharge_level['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$recharge_level['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$recharge_level['status'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="status" value="0" <if condition="$recharge_level['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			
		   
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>


