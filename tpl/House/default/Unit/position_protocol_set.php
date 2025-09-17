<include file="Public:header"/>
<div class="main-content" style="background-color: white;min-height: 700px;">
	<!-- 内容头部 -->
	<div class="x-page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="">
					   <input  name="idd" type="hidden" value="{pigcms{$protocol['id']}"/>
						<div class="tab-content" style="border:none;">
							<div id="bind_protocol" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label>租/抢车位协议展示：</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_protocol_open" value="1" type="radio" class="ace" <if condition="$protocol['is_protocol_open'] eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="is_protocol_open" value="0" type="radio" class="ace" <if condition="empty($protocol) || $protocol['is_protocol_open'] eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips"></span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1" style=""><label for="title">协议名称</label></label>
									<input class="col-sm-4" name="robbuy_protocol_title" id="robbuy_protocol_title" type="text" value="{pigcms{$protocol['robbuy_protocol_title']}" autocomplete="off" maxlength="50" required/>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">协议内容</label></label>
									<textarea id="robbuy_protocol" name="robbuy_protocol"  placeholder="写上一些想要发布的内容" >{pigcms{:replace_file_domain_content($protocol['robbuy_protocol'])}</textarea> 
								</div>

                              	<div class="space"></div>
								<div class="clearfix">
									<div class="col-md-9" style="margin-top: 50px;text-align: center;">
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
			kind_editor = K.create("#robbuy_protocol",{
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
