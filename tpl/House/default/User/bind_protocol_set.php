<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="x-page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="">
					   <input  name="idd" type="hidden" value="{pigcms{$protocol['id']}"/>
						<div class="tab-content">
							<div id="bind_protocol" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1" style=""><label for="title">协议标题</label></label>
									<input class="col-sm-4" name="ptitle" id="ptitle" type="text" value="{pigcms{$protocol['ptitle']}" autocomplete="off" maxlength="50" required/>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">协议内容</label></label>
									<textarea id="pcontent" name="pcontent"  placeholder="写上一些想要发布的内容" >{pigcms{:replace_file_domain_content($protocol['pcontent'])}</textarea> 
								</div>

								<div class="form-group">
									<label class="col-sm-1">是否启用</label>
									<label><input  value="0" name="status" type="radio" <if condition="$protocol['status'] eq 0"> checked="checked" </if> />&nbsp;&nbsp;否</label>
									&nbsp;&nbsp;&nbsp;
									<label><input  value="1" name="status" type="radio" <if condition="$protocol['status'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
								</div>
								<div class="space"></div>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											保存
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>
			</div>
		</div>
	 </div>
   </div>
</div>
<style type="text/css">
    #bind_protocol .col-sm-1{width:110px;}
    .ke-dialog-body .ke-input-text{height: 30px;}
    .no-skin .ke-dialog{
        left: 30% !important;top:18% !important;
    }
</style>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript">
KindEditor.ready(function(K){
			kind_editor = K.create("#pcontent",{
				width:'85%',
				height:'400px',
				resizeType : 1,
				pasteType : 1,
				allowPreviewEmoticons:false,
				allowImageUpload : true,
				filterMode: true,
				items : [
					'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
					'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
					'insertunorderedlist', '|', 'emoticons', 'image', 'wordpaste'
				],
				emoticonsPath : './static/emoticons/',
				uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"
			});
		});
</script>

<include file="Public:footer"/>
