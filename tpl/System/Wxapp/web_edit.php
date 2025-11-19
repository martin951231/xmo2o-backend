<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Wxapp/web_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$detail.pigcms_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" value="{pigcms{$detail.name}" placeholder="请输入打开的页面名称"validate="maxlength:20,required:true" /></td>
			</tr>
			<tr>
				<th width="80">域名</th>
				<td><input type="text" class="input fl" name="domain" size="20" value="{pigcms{$detail.domain}" placeholder="请输入打开的页面域名"validate="required:true" tips="请仅填入完整域名"/></td>
			</tr>
			
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>