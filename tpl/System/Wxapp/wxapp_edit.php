<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Wxapp/wxapp_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$detail.pigcms_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">小程序名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$detail.name}" size="20" placeholder="请输入小程序名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">小程序APPID</th>
				<td><input type="text" class="input fl" name="appid" size="20" value="{pigcms{$detail.appid}" placeholder="请输入小程序APPID" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="80">小程序原始ID</th>
				<td><input type="text" class="input fl" name="username" size="20" value="{pigcms{$detail.username}" placeholder="小程序原始ID"/></td>
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