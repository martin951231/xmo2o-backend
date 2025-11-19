<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Library/nCoV_2019_config')}">代送基本设置</a>
			</li>
			<li class="active">基本信息设置</li>
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
					<form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Library/nCoV_2019_config')}" enctype="multipart/form-data">
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="ncov_2019_health_visit">允许匿名打卡</label></label>
								<label style="padding-left:0px;padding-right:20px;"><input class="ncov_2019_health_visit" name="ncov_2019_health_visit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_health_visit eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
								<label style="padding-left:0px;"><input class="ncov_2019_health_visit" name="ncov_2019_health_visit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_health_visit eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
								<span class="form_tips">没有入住为业主，是否允许使用健康打卡功能。匿名时房屋、姓名等信息由用户自行填写。</span>
							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="ncov_2019_visit">允许匿名登记</label></label>
								<label style="padding-left:0px;padding-right:20px;"><input class="ncov_2019_visit" name="ncov_2019_visit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_visit eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
								<label style="padding-left:0px;"><input class="ncov_2019_visit" name="ncov_2019_visit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_visit eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
								<span class="form_tips">没有入住为业主，是否允许进行出入登记。匿名时房屋、姓名等信息由用户自行填写。</span>
							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="ncov_2019_out_type">外出小区限制</label></label>


								<select name="ncov_2019_out_type" id="ncov_2019_out_type" style="width: 250px">
									<option value="0" <if condition="$village_info['ncov_2019_out_type'] eq 0">selected="selected"</if> >不限制</option>
									<option value="1" <if condition="$village_info['ncov_2019_out_type'] eq 1">selected="selected"</if> >按每周限制外出次数</option>
									<option value="2" <if condition="$village_info['ncov_2019_out_type'] eq 2">selected="selected"</if> >按每几天限制一次外出</option>
								</select>

								<span class="form_tips"></span>
							</div>

							<div class="form-group" id="ncov_2019_week_group" <if condition="$village_info['ncov_2019_out_type'] neq 1">style="display:none;"</if>>
								<label class="col-sm-1"><label>每周限制次数</label></label>
								<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_week}" type="text" id="ncov_2019_week" name="ncov_2019_week"/>
							</div>

							<div class="form-group" id="ncov_2019_day_group" <if condition="$village_info['ncov_2019_out_type'] neq 2">style="display:none;"</if>>
								<label class="col-sm-1"><label>每几天限制一次外出</label></label>
								<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_day}" type="text" id="ncov_2019_day" name="ncov_2019_day"/>
							</div>

							<div class="form-group" id="ncov_2019_limit_group" <if condition="$village_info['ncov_2019_out_type'] eq 0">style="display:none;"</if>>
								<label class="col-sm-1"><label for="ncov_2019_limit">外出限制类型</label></label>
								<label style="padding-left:0px;padding-right:20px;"><input name="ncov_2019_limit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_limit eq 1">checked</if>/><span class="lbl" style="z-index: 1">每户</span></label>
								<label style="padding-left:0px;"><input name="ncov_2019_limit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_limit eq 0">checked</if>/><span class="lbl" style="z-index: 1">每人</span></label>
								<span class="form_tips">匿名登记的系统不知属实所在的房间，所以每户仅在关闭“允许匿名登记”的情况下生效。</span>
							</div>

							<div class="form-group" id="ncov_2019_time_group" <if condition="$village_info['ncov_2019_out_type'] eq 0">style="display:none;"</if>>
								<label class="col-sm-1"><label>外出时间段限制</label></label>
								<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_time}" type="text" id="ncov_2019_time" name="ncov_2019_time"/>
								<span class="form_tips">例：8-20，早8点整到晚8点整，暂仅支持整点。留空不限制。</span>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<if condition="in_array(3,$house_session['menus'])">
									<button type="submit" class="btn btn-info">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									<else/>
									<button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									</if>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			</div>
		</div>
	</div>
</div>
<script>

	$('#edit_form').submit(function(){

		$('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
		if (lock = 1) {
			return;
		}
		lock = 1;


		$.post("{pigcms{:U('Library/nCoV_2019_config')}",$('#edit_form').serialize(),function(result){
			lock = 0;
			if(result.status == 1){
				window.location.href = "{pigcms{:U('Library/nCoV_2019_config')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});

	//新冠肺炎
	$('#ncov_2019_out_type').change(function(){
		var value = $(this).val();
		if(value == 0){
			$('#ncov_2019_week_group').hide();
			$('#ncov_2019_day_group').hide();
			$('#ncov_2019_limit_group').hide();
			$('#ncov_2019_time_group').hide();
		}else if(value == 1){
			$('#ncov_2019_time_group').show();
			$('#ncov_2019_limit_group').show();
			$('#ncov_2019_week_group').show();
			$('#ncov_2019_day_group').hide();
		}else if(value == 2){
			$('#ncov_2019_week_group').hide();
			$('#ncov_2019_day_group').show();
			$('#ncov_2019_limit_group').show();
			$('#ncov_2019_time_group').show();
		}
	});
</script>

<include file="Public:footer"/>