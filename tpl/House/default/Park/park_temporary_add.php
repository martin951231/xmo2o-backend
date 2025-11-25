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
									<label class="col-sm-1"><label for="name">临时车类型名称：</label></label>
									<input class="col-sm-2" size="20" name="month_name" id="month_name" type="text" value=""/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-1"><label for="name">临时车类型：</label></label>
										<td width="85%">
											<select name="type" id="type" style="width: 100px;height: 32px;">

												<option value="A" >A</option>
												<option value="B" >B</option>
												<option value="C" >C</option>
												<option value="D" >D</option>
												<option value="E" >E</option>
												<option value="F" >F</option>
												<option value="G" >G</option>
												<option value="H" >H</option>

											</select>
											&nbsp;&nbsp;<font color="red"></font>
										</td>

									</tr>
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