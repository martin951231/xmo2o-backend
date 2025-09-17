<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Unit/preferential_list')}">缴费优惠列表</a>
			</li>
			<li class="active">编辑缴费优惠</li>
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
							<input type="hidden" value="{pigcms{$park_change['id']}" name="id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="park_month_num">名称:</label></label>
									<input type="text" size="10" name="change_name" value="{pigcms{$park_change['change_name']}" />&nbsp;&nbsp;<span class="red"></span>

								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="park_month_num">收费标准</label></label>
									<label style="">截止:&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="change_time" value="{pigcms{$park_change['change_time']}"/>&nbsp;&nbsp;<span class="red"></span></label>
									<select name="change_type">
										<option <if condition="$park_change['change_type'] eq 1">selected="selected"</if> value='1'>分钟</option>
										<option <if condition="$park_change['change_type'] eq 2">selected="selected"</if> value='2'>小时</option>
									</select>&nbsp;&nbsp;&nbsp;&nbsp;
									收费<label style="">&nbsp;&nbsp;<input type="text" size="6" name="change_money" value="{pigcms{$park_change['change_money']}" />&nbsp;&nbsp;<span class="red"></span></label>元
								</div>
							</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
            						<if condition="in_array(30,$house_session['menus'])">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									<else/>
									<button class="btn btn-info" type="submit" disabled="disabled">
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
<script type="text/javascript">
function check_submit(){

}




</script>

<include file="Public:footer"/>