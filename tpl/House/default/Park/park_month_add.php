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

							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2"><label for="name">月租车费用类型名称：</label></label>
									<input class="col-sm-2" size="20" name="month_name" id="month_name" type="text" value=""/>
								</div>
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
					</form>
				</div>
			</div>
		</div>
	</div>
<script>
function check_submit(){
	if($('#month_name').val()==''){
		alert('名称不能为空！');
		return false;
	}
	}
</script>




<include file="Public:footer"/>