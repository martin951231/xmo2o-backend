<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/edit_activation_code')}" frame="true" refresh="true">
		<input type="hidden" name="code_id" value="{pigcms{$code_info['code_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">激活码：</td>
				<td>
					{pigcms{$code_info['activation_code']}
				</td>
			</tr>
            <tr>
                <td width="80">绑定用户ID</td>
                <td>
                    <input type="text" class="input fl" name="uid" value="{pigcms{$code_info['uid']}">
                </td>
            </tr>
            <tr>
                <td width="80">绑定商家ID</td>
                <td>
                    <input type="text" class="input fl" name="mer_id" value="{pigcms{$code_info['mer_id']}">
                </td>
            </tr>
            <tr>
                <td width="80">会员等级</td>
                <td>
                    <select name="level_id" class="valid">
                        <volist name="user_level" id="item">
                            <option value="{pigcms{$item['id']}" <if condition="$code_info['level_id'] eq $item['id']">selected="selected"</if>>{pigcms{$item['lname']}</option>
                        </volist>
                    </select>
                </td>
            </tr>
            <tr>
                <td width="80">状态</td>
                <td>
                    <select name="status" class="valid">
                        <option value="-1" <if condition="$code_info['status'] eq 0">selected="selected"</if>>未激活</option>
                        <option value="1" <if condition="$code_info['status'] eq 1">selected="selected"</if>>激活</option>
                        <option value="2" <if condition="$code_info['status'] eq 2">selected="selected"</if>>禁用</option>
                        <option value="3" <if condition="$code_info['status'] eq 3">selected="selected"</if>>失效</option>
                    </select>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>

