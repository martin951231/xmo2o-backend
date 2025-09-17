<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Group/index')}">{pigcms{$config.group_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("新建")}{pigcms{$config.group_alias_name}{pigcms{:L_("套餐")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('Group/mpackageadd')}">
					   <input  name="idx" type="hidden" value="{pigcms{$mpackage['id']}"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="title">{pigcms{:L_("套餐标示名称")}</label></label>
									<input class="col-sm-2" size="80" name="title" id="title" type="text" value="{pigcms{$mpackage['title']}"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">{pigcms{:L_("（必填）")}</span>
								</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="sort">{pigcms{:L_("简短描述")}</label></label>
								<textarea id="description" name="description"  placeholder="{pigcms{:L_("写上一些此套餐的简短描述")}" rows="10" cols="50">{pigcms{$mpackage['description']|htmlspecialchars_decode=ENT_QUOTES}</textarea> 
							</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" id="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
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
.ke-dialog-body .ke-input-text{height: 30px;}
</style>
<script type="text/javascript">
	$('#edit_form').submit(function(){
		if($('#title').val() == ''){
			alert('{pigcms{:L_("请先设置套餐名称")}');
			return false;
		}
		$('#submit').prop('disabled','disabled');
	});
</script>

<include file="Public:footer"/>
