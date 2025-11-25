<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Employee/memory_edit_modify')}" frame="true" refresh="true">
        <input type="hidden" name="memory_id" value="{pigcms{$info.memory_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">标签名称</th>
                <td><input type="text" class="input fl" name="memory_name" value="{pigcms{$info.memory_name}" id="memory_name" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
            </tr>
            <tr>
                <th width="80">标签备注</th>
                <td><input type="text" class="input fl" name="memory_remark" value="{pigcms{$info.memory_remark}" id="memory_remark" maxlength="100" size="25" placeholder=""  tips="用于描述标签，阐述标签含义"/></td>
            </tr>
			<tr>
				<th width="80">标签排序</th>
				<td><input type="text" class="input fl" name="sort" value="{pigcms{$info.sort}" size="10" placeholder="标签排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
			</tr>
			<tr>
				<th width="80">标签状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$info['status'] eq 0">selected</if>"><span>启用</span><input type="radio" name="status" value="0" <if condition="$info['status'] eq 0">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$info['status'] eq 1">selected</if>"><span>禁用</span><input type="radio" name="status" value="1" <if condition="$info['status'] eq 1">checked="checked"</if> /></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>