<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Live/edit_livevideo_category')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">话题名称</th>
				<td><input type="text" class="input fl" name="name" size="75" placeholder="话题名称" validate="maxlength:50,required:true" value="{pigcms{$info.name}"/></td>
			</tr>
			<tr>
				<th width="80">话题描述</th>
				<td><input type="text" class="input fl" name="desc" size="75" placeholder="一段话描述" validate="maxlength:50" value="{pigcms{$info.desc}" /></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="orderby" value="{pigcms{$info.orderby}" size="10" placeholder="排序" validate="maxlength:20,required:true,digits:true" /></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="hidden" name="id" value="{pigcms{$info.id}" />
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
<include file="Public:footer"/>