<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/importBatchRemarkSet')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="30%">批次号：</th>
                <td width="70%">{pigcms{$info['batch_no'] ?? ''}</td>
            </tr>
            <tr>
                <th width="30%">备注：</th>
                <td width="70%">
                    <textarea style="width:300px;" maxlength="150" name="remark" rows="10">{pigcms{$info['remark'] ?? ''}</textarea>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
            <input type="hidden" name="id" value="{pigcms{$info['id'] ?? ''}">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
