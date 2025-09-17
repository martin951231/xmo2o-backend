<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/activation_mutil')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">激活码编码集：</td>
				<td>
					<textarea name="codeids" form="myform" autocomplete="off"  style="height:180px;width:300px" validate="required:true"></textarea>
					<span>&nbsp;&nbsp;一个激活码一行</span>
				</td>
			</tr>
			<tr>
				<td width="80">绑定商户ID：</td>
				<td>
				    <input type="text" class="input fl" name="mer_id" value="">&nbsp;
                    <select name="is_mer_id_set" class="valid">
                        <option value="0">不修改</option>
                        <option value="1">修改</option>
                    </select>
				</td>
			</tr>
            <tr>
                <td width="80">会员等级</td>
                <td>
                    <select name="level_id" class="valid">
                        <volist name="user_level" id="item">
                            <option value="{pigcms{$item['id']}" <if condition="$code_info['level_id'] eq $item['id']">selected="selected"</if>>{pigcms{$item['lname']}</option>
                        </volist>
                    </select>&nbsp;
                    <select name="is_level_id_set" class="valid">
                        <option value="0">不修改</option>
                        <option value="1">修改</option>
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
                    </select>&nbsp;
                    <select name="is_status_set" class="valid">
                        <option value="0">不修改</option>
                        <option value="1">修改</option>
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
