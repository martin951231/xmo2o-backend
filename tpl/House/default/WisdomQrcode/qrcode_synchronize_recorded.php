<include file="Public:header" />
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
			</li>
			<li class="active">同步记录内容</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$cate_info['id']}" name="cate_id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">名称：</label></label>
									<input class="col-sm-2" size="20" readonly name="cate_name" id="{pigcms{$cate_info.id}" type="text" value="{pigcms{$cate_info.cate_name}" />
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">

								<div class="form-group">
									<label class="col-sm-1"><label for="name">小区名：</label></label>
									<select name="village_id" id="village_">
										<option value="">请选择小区</option>
										<foreach name="village_list" item="village_list">
											<option value="{pigcms{$village_list.village_id}">{pigcms{$village_list.village_name}</option>
										</foreach>
									</select>
								</div>
							</div>

							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" <if condition="!in_array(205,$house_session['menus'])">disabled="disabled"</if>>
										<i class="ace-icon fa fa-check bigger-110"></i>
										同步
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
		function check_submit() {

			if ($('#village_').val() == '') {
				alert('小区不能为空！');
				return false;
			}
		}
	</script>




	<include file="Public:footer" />