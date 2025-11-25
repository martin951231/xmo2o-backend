<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card/focus')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加幻灯片")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post" action="{pigcms{:U('Card/focusAdd')}">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_info">{pigcms{:L_("幻灯片描述")}</label></label>
									<input type="text" class="col-sm-3" name="info" value="{pigcms{$info.info}" />
									<input type="hidden" name="fid" value="{pigcms{$info.id}" />
									<span class="form_tips">30{pigcms{:L_("个字简短分类描述，可为空")}</span>
								</div>
								
								<div class="form-group" style="margin-bottom:-35px;">
									<label class="col-sm-3"><label for="AutoreplySystem_img">{pigcms{:L_("幻灯片图片")}</label></label>
								</div>
									
								<div class="form-group" style="width:417px;padding-left:140px;">
									<label class="ace-file-input">
										<input class="col-sm-4" id="ace-file-input" size="50" onchange="preview1(this)" name="img" type="file">
										<span class="ace-file-container" data-title="{pigcms{:L_("选择")}">
											<span class="ace-file-name" data-title="{pigcms{:L_("上传图片")}..."><i class=" ace-icon fa fa-upload"></i></span>
										</span>
									</label>
							
									<div><a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('flash_preview')">{pigcms{:L_("选择图片")}</a></div>
								
									<div id="flash_preview"><img style="width:417px;height:200px" id="img" src="{pigcms{$info.img}"></div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("幻灯片链接地址")}  </label></label>
									<input type="text" class="col-sm-3" name="url" value="{pigcms{$info.url}" id="url"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
								</div>
								
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>

<script type="text/javascript">
function preview1(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) { $('#img').attr('src', e.target.result);}
		reader.readAsDataURL(input.files[0]);
	}
}

function viewTpl(){
	var tid = $('#tpid').val();
	chooseTpl(tid,'',2);
}

function viewTpl2(){
	var tid = $('#conttpid').val();
	chooseTpl(tid,'',4);
}
</script>
<include file="Public:footer"/>