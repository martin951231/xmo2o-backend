<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Park/preferential_list')}">缴费优惠列表</a>
			</li>
			<li class="active">添加缴费优惠</li>
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
									<label class="col-sm-1"><label for="park_month_num">停车缴费周期</label></label>
									<select name="park_month_num">
										<option value='0'>请选择</option>
										<option value='1'>1个月</option>
										<option value='3'>3个月</option>
										<option value='6'>6个月</option>
										<option value='12'>12个月</option>
										<option value='24'>24个月</option>
										<option value='0'>自定义</option>
									</select>
									
									<label style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="diy_park_month_num" />&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">单位：月</span></label>
								</div>
								<div class="form-group" style="">
									<label class="col-sm-1"><label>停车缴费费用</label></label>
									<label style=""><input type="text" size="6" name="park_month_price" placeholder="0.00"/></label>
								</div>
								
								<div class="form-group diy_park_type">
									<label class="col-sm-1"><label for="presented_park_month_num">赠送服务类型</label></label>
									<label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="0" name="diy_type" checked="checked"><span style="z-index: 1" class="lbl">月份</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="1" name="diy_type"><span style="z-index: 1" class="lbl">文本</span></label>
								</div>
								
								
								<div class="form-group diy_park_content">
									<label class="col-sm-1"><label for="presented_park_month_num">赠送停车时间</label></label>
									<select name="presented_park_month_num">
										<option value='0'>请选择</option>
										<option value='1'>1个月</option>
										<option value='3'>3个月</option>
										<option value='6'>6个月</option>
										<option value='0'>自定义</option>
									</select>
									
									<label style="display:none">&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="diy_presented_park_month_num" />&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">单位：月</span></label>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*可不填写</span>
								</div>
								
								<div class="form-group diy_park_content" style="display:none">
									<label class="col-sm-1"><label>自定义内容</label></label>
									<label><textarea name="diy_content"></textarea></label>
								</div>
							</div>

                            <div class="form-group">
									<label class="col-sm-1">状态</label>
										<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
										<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
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
	if(!$('select[name="park_month_num"]').val()){
		if(!$('input[name="diy_park_month_num"]').val()){
			alert('停车缴费周期不能为空！');
			return false;
		}
	}
}

$('select').change(function(){
	if($(this).val() == 0){
		$(this).next('label').show();
	}else{
		$(this).next('label').hide();
	}
});

$('input[name="diy_type"]').each(function(i){
	$(this).click(function(){
		$('.diy_park_content').each(function(){
			$(this).hide();
		});
		$('.diy_park_content').eq(i).show();
	});
	
});
</script>

<include file="Public:footer"/>