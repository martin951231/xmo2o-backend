<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalUnit/unitSet')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <td width="30%">归属单位名称：</td>
                <td width="70%">
                    <input type="text" class="input" style="width: 300px;" name="unit_name" value="{pigcms{$info['name'] ?? ''}" maxlength="50">
                </td>
            </tr>
            <if condition="!$info && $admin_area_id eq 0">
                <tr class="open_admin_area">
                    <td width="30%">所在区域</td>
                    <td width="70%" id="choose_cityarea" province_id="0" city_id="0" area_id="0" circle_id="-1"></td>
                </tr>
            </if>
            <tr>
                <td>联系人：</td>
                <td>
                    <input type="text" class="input" style="width: 300px;" name="contact_name" value="{pigcms{$info['contact_name'] ?? ''}" maxlength="30">
                </td>
            </tr>
            <tr>
                <td>联系电话：</td>
                <td>
                    <input type="text" class="input" style="width: 300px;" name="contact_phone" value="{pigcms{$info['contact_phone'] ?? ''}" maxlength="30">
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
