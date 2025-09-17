<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-qrcode"></i>
				<a href="{pigcms{:U('Promote/index')}">{pigcms{:L_("商家推广")}</a>
			</li>
			<li>{pigcms{:L_("推广记录")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name"><span class="required" style="color:red;">*</span>{pigcms{:L_("宣传语")}</label></label>
									<input type="text" class="col-sm-3" id="title" name="title" value="{pigcms{$home_share['title']}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("转向语")}</label></label>
									<input type="text" class="col-sm-3" name="a_name" value="{pigcms{$home_share['a_name']}" />
								</div>
		
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("转向地址")}</label></label>
									<input class="col-sm-3" name="url" id="a_href" type="text" value="{pigcms{$home_share['a_href']}"/>　
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('a_href',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
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
<include file="Public:footer"/>