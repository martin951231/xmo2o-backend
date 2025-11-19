<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/edit_card')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <td width="80">会员卡名称：</td>
                <td>
                    <label>{pigcms{$internal_card['card_name']}</label>
                    <input type="hidden" name="card_name" value="{pigcms{$internal_card['card_name']}">
                </td>
            </tr>
            <tr>
                <td width="80">会员卡ID：</td>
                <td>
                    <label>{pigcms{$internal_card['card_id']}</label>
                    <input type="hidden" name="card_id" value="{pigcms{$internal_card['card_id']}">
                </td>
            </tr>
<!--			<tr>-->
<!--				<td width="80">绑定用户ID：</td>-->
<!--				<td>-->
<!--				<if condition="$internal_card['uid'] gt 0"> {pigcms{$internal_card['uid']}<else /> <input type="text" class="input fl" name="uid" value="{pigcms{$internal_card['uid']}" tips="不修改则不填写" autocomplete="off" validate="digits:true" ></if>-->
<!--				</td>-->
<!--			</tr>-->
            <tr>
                <td width="80">余额：</td>
                <td>
                    {pigcms{$internal_card['balance_money']}
                </td>
            </tr>
            <tr>
                <td width="80">手机号：</td>
                <td>
                    <if condition="$internal_card['uid'] != null">请在用户信息处修改<else /><input type="text" class="input fl" name="phone" value="" tips="不修改则不填写" autocomplete="off" validate="digits:true" ></if>
                </td>
            </tr>
            <tr>
                <td width="80">用户姓名：</td>
                <td>
                    <input type="text" class="input fl" name="user_name" value="{pigcms{$internal_card.user_name}">
                </td>
            </tr>
            <tr>
                <td width="80">一卡通密码：</td>
                <td>
                    <input type="text" class="input fl" name="password" value="" tips="不修改则不填写" autocomplete="off" maxlength="6" >请输入6位数字密码</if>
                </td>
            </tr>
			<tr>
				<td width="80">状态：</td>
				<td>
				
				<select name="status">
				<option value="0" <if condition="$internal_card['status'] eq 0">selected="selected" </if>>禁止</option>
				<option value="1" <if condition="$internal_card['status'] eq 1">selected="selected" </if>>正常</option>
				<option value="2" <if condition="$internal_card['status'] eq 2">selected="selected" </if>>未审核</option>
				</select>
				
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
