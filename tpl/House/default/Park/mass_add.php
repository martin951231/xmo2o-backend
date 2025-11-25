<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
<!--			<li>-->
<!--				<i class="ace-icon fa fa-tablet"></i>-->
<!--				<a href="{pigcms{:U('Park/preferential_list')}">缴费优惠列表</a>-->
<!--			</li>-->
<!--			<li class="active">添加缴费优惠</li>-->
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$_GET['park_month_id']}" name="park_month_id">
							<div id="basicinfo" class="tab-pane active">

								<div class="form-group">
									&nbsp;&nbsp;&nbsp;&nbsp;从<label style="">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="start_time" readonly value="{pigcms{$start_time}" />&nbsp;&nbsp;<span class="red"></span></label>
									<select name="start_type">
										<option value='1'>分钟</option>
										<option value='2'>小时</option>
									</select>&nbsp;&nbsp;
									后每<label style="">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="each_time" />&nbsp;&nbsp;<span class="red"></span></label>
									<select name="each_type">
										<option value='1'>分钟</option>
										<option value='2'>小时</option>
									</select>&nbsp;&nbsp;&nbsp;&nbsp;
									收费<label style="">&nbsp;&nbsp;<input type="text" size="6" name="change_money" />&nbsp;&nbsp;<span class="red"></span></label>元
								</div>
								<div class="form-group">
									&nbsp;&nbsp;&nbsp;&nbsp;截止时间<label style="">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="end_time" />&nbsp;&nbsp;<span class="red"></span></label>
									<select name="end_type">
										<option value='1'>分钟</option>
										<option value='2'>小时</option>
									</select>&nbsp;&nbsp;&nbsp;&nbsp;

								</div>
								<div class="form-group">
									&nbsp;&nbsp;&nbsp;&nbsp;注:&nbsp;&nbsp;若已添加收费标准,则默认取最后一条时间开始批量生成,若没有,则从0开始生成!
								</div>
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
<script type="text/javascript">
function check_submit(){

}

//$('select').change(function(){
//	if($(this).val() == 0){
//		$(this).next('label').show();
//	}else{
//		$(this).next('label').hide();
//	}
//});


</script>

<include file="Public:footer"/>