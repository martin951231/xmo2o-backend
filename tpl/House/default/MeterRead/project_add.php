<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">

		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">项目名称：</label></label>
									<input class="col-sm-2" size="20" name="project_name" id="project_name" type="text" value=""/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">单价：</label></label>
									<input class="col-sm-2" size="20" name="unit_price" id="unit_price" type="text" value=""/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">倍率：</label></label>
									<input class="col-sm-2" size="20" name="rate" id="rate" type="text" value=""/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">公摊：</label></label>
									<input class="col-sm-2" size="20" name="pool" id="pool" type="text" value=""/>
								</div>
							</div>
<!--							<div id="basicinfo" class="tab-pane active">-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="water_price">状态</label></label>-->
<!--									<label><input name="status"  type="radio" value="1"/>&nbsp;&nbsp;正常</label>-->
<!--									&nbsp;&nbsp;&nbsp;-->
<!--									<label><input name="status"  type="radio" value="0"/>&nbsp;&nbsp;禁止</label>-->
<!--								</div>-->
<!--							</div>-->

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
					</form>
				</div>
			</div>
		</div>
	</div>
<script>
function check_submit(){
	if($('#field_name').val()==''){
		alert('名称不能为空！');
		return false;
	}
	}
</script>




<include file="Public:footer"/>