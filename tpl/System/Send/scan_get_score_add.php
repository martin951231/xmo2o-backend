<include file="Public:header"/>
	<style>.frame_form td{vertical-align:middle;}</style>
	<form id="myform" method="post" action="{pigcms{:U('Send/scan_get_score_add')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">标题</td>
				<td>
				<input type="text"  style="width:300px;" class="input fl" name="Full_title" value=""  validate="required:true" autocomplete="off" />
				</td>
			</tr>
			<tr class="plus">
				<td width="40">图文<label></label></td>
				<td>
					<table style="width:100%;border:#d5dfe8 1px solid;padding:2px;">
						<tr>
							<td width="60">标题：</td>
							<td><input type="text" style="width:200px;" class="input" name="title"  value=""  /></td>
							<td width="60">图片：</td>
							<td><input type="text"  style="width:160px;" name="img" class="input input-image" value="" readonly />&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
							<td rowspan="2" class="delete">
								<a href="javascript:void(0)" onclick="del(this)"><img style="width:30px;height:30px;" src="{pigcms{$static_path}images/del.jpg"/></a>
							</td>
						<tr/>
						<tr>
							<td width="60">描述：</td>
							<td><textarea  style="width:200px;height:60px" class="input" name="des" ></textarea></td>
							<td width="60">获得积分：</td>
							<td><input type="text" style="width:200px;" class="input" name="score"  value=""  /></td>
						</tr>
						<tr>
							<td width="60">链接：</td>
							<td><input type="text"  style="width:200px;" class="input" name="url" id="url" value="" /><a href="#modal-table" id="addLink" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
		
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript" src="./static/js/upyun.js"></script>

	<script type="text/javascript">
		KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.J_selectImage').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(site_url+url);
								editor.hideDialog();
							}
						});
					});
				});

			});
	</script>
	<script type="text/javascript">
		$(document).ready(function() {
			if($('.odd').length<=1){
				$('.delete').children('a').hide();
			}
		});
		function addLink(domid,iskeyword){
			art.dialog.data('domid', domid);
			art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
		}
	</script>
<include file="Public:footer"/>

