<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Live/setup')}" frame="true" refresh="true">
		<input type="hidden" name="live_id" value="{pigcms{$live.live_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">虚拟观看数</th>
				<td><input type="text" class="input fl" name="live_virtual_views" size="20" placeholder="请输入一个数字" value="{pigcms{$live.live_virtual_views}" validate="maxlength:50,required:true"/></td>
			</tr>
      <tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" size="20" placeholder="请输入一个数字" value="{pigcms{$live.sort}" validate="maxlength:50,required:true"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>