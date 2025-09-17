<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_group')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">拼团名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$group.name}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<input type="hidden" name="group_id" value="{pigcms{$_GET['group_id']}">
			<tr>
				<th width="80">拼团描述</th>
				<td><input type="text" class="input fl" name="share_desc" id="share_desc" size="25" value="{pigcms{$group.share_desc}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
		
			<tr>
				<th width="80">商品推广图</th>
				<td><input type="text"  style="width:200px;" name="share_image" id="share_image" class="input input-image" value="" readonly/>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a> </td>
			</tr>
			
			
			<tr>
				<th width="80">成团有效期</th>
				<td><input type="text" class="input fl" name="effective_time" id="effective_time" size="25" value="{pigcms{$group.effective_time}" placeholder="" validate="maxlength:20,required:true" tips="成团有效期,开团后开始计算"/></td>
			</tr>
			
			<tr>
				<th width="80">团长分成</th>
				<td><input type="text" class="input fl" name="header_money" id="header_money" value="{pigcms{$group.header_money}" size="25" placeholder="" validate="maxlength:20,required:true" tips="固定金额，如20"/></td>
			</tr>
			
			<tr>
				<td width="100">团购期限：</td>
				<td colspan="2">
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$group.begin_time|date='Y-m-d',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$group.end_time|date='Y-m-d',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" />
				</td>
			</tr>
			
			<tr>
				<th width="80">拼团状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
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
<include file="Public:footer"/>