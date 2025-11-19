<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		fieldset:disabled select{
			background: #ddd;
		}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑店铺")}</li>
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
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
								<input type="hidden" name="area_id" value="{pigcms{$_GET.area_id}"/>
								<input type="hidden" name="floor_id" value="{pigcms{$now_floor.floor_id}"/>
								
							
								<div class="form-group">
									<label class="col-sm-1"><label for="address">{pigcms{:L_("楼号名称")}</label></label>
									<input class="col-sm-2" size="20" name="floor_name" id="floor_name" value="{pigcms{$now_floor.floor_name}" type="text"/>
								</div>							
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
