<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Appoint/project_city_store')}" frame="true" refresh="true">

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">城市名称</th>
				<td><input type="text" class="input fl" name="project_city_name" id="project_city_name" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>