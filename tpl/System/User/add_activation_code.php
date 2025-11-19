<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/add_activation_code')}" frame="true" refresh="true">
		<input type="hidden" name="code_id" value="{pigcms{$code_info['code_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">添加数量：</td>
				<td>
					<input type="text" class="input fl" name="number" value=""  autocomplete="off" validate="required:true,number:true">
				</td>
			</tr>
            <tr>
                <td width="80">绑定商家ID</td>
                <td>
                    <input type="text" class="input fl" name="mer_id" value="">
                </td>
            </tr>
            <tr>
                <td width="80">会员等级</td>
                <td>
                    <select name="level_id" class="valid">
                        <volist name="user_level" id="item">
                            <option value="{pigcms{$item['id']}" <if condition="$code_data['level_id'] eq $item['id']">selected="selected"</if>>{pigcms{$item['lname']}</option>
                        </volist>
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

