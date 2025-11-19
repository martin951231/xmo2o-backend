<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Appoint/project_store')}" frame="true" refresh="true">

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="100">项目城市：</th>
				<td colspan="2">
					<select name="city_name">

						<volist name="project_list" id="vo">
							<option value="{pigcms{$vo['project_city_name']}">{pigcms{$vo['project_city_name']}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<th width="80">项目名称</th>
				<td><input type="text" class="input fl" name="project_name" id="project_name" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>