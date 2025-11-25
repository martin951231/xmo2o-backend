<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">

					<div class="form-group">
<!--						<label class="col-sm-1">图片预览</label>-->
						<div id="upload_pic_box">
							<volist name="bill['image']" id="pic">
								<li class="upload_pic_li">
									<img src="{pigcms{:file_domain()}/upload/activity/{pigcms{$pic}">
<!--									<input type="hidden" value="{pigcms{$pic}" name="pic[]"><br>-->
<!--									<a onclick="deleteImg('{pigcms{$pic}',this);return false;" href="#">[ 删除 ]</a>-->
								</li>
							</volist>
						</div>
					</div>
				</div>
    </div>

<script type="text/javascript">



</script>
<include file="Publics:footer"/>
