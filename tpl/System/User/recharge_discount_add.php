<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/recharge_discount_add')}" refresh="true" frame="true">
		<input type="hidden" name="discount_id" value="{pigcms{$recharge_discount.discount_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">满足金额</th>
				<td><input type="text" value="{pigcms{$recharge_discount.full_money}" class="input fl" name="full_money" size="20" maxlength="15" placeholder="请输入满足金额" validate="maxlength:20,required:true"/></td>
			</tr>
            <tr>
                <th width="80">赠送金额</th>
                <td><input type="text" value="{pigcms{$recharge_discount.money}" class="input fl" name="money" size="20" maxlength="15" placeholder="请输入赠送金额" validate="maxlength:20,required:true"/></td>
            </tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$recharge_discount['status'] eq 1 OR !$recharge_discount">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$recharge_discount['status'] eq 1  OR !$recharge_discount">checked="checked" </if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$recharge_discount['status'] eq 2">selected</if>"><span>关闭</span><input type="radio" name="status" value="2" <if condition="$recharge_discount['status'] eq 2">checked="checked" </if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>