<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs" style="display:block!important;">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-shopping-cart"></i>
				<a href="{pigcms{:U('nCoV_2019_white')}">白名单人员列表</a>
			</li>
			<li class="active">添加白名单人员</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#time_error{ height:27px; line-height:27px; color:red}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">手机号</label></label>
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text" value=""/>
								</div>
								<div class="form-group">
									<label class="col-sm-1">是否放行</label>
									<label style="padding-left:0px;padding-right:20px;">
										<input type="radio" class="ace man_status" checked="checked" value="1" name="status"/>
										<span style="z-index: 1" class="lbl">允许外出</span>
									</label>
									<label style="padding-left:0px;">
										<input type="radio" class="ace man_status" value="2" name="status"/>
										<span style="z-index: 1" class="lbl">禁止外出</span>
									</label>
									<label style="margin-left:20px;"><span class="red">*&nbsp;&nbsp;若业主在隔离期间可设置禁止外出，并备注好隔离时间。等隔离期满后物业量体温正常后删除此人员限制。</span></label>
								</div>
								<div class="form-group out_type_group">
									<label class="col-sm-1"><label for="type">外出目的</label></label>
									<select name="type" id="type">
										<option value="外出上班">外出上班</option>
										<option value="物业人员">物业人员</option>
										<option value="其他">其他（须备注）</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="remark">备注</label></label>
									<textarea id="remark" name="remark" style="width:281px;height:100px"></textarea>
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
			if($("#phone").val()==''){
				alert('手机号不能为空!');
				return false;		
			}
		}
		$('.man_status').click(function(){
			
			if($(this).val() == 2){
				$('.out_type_group').hide();
			}else{
				$('.out_type_group').show();
			}
		});
	</script>

<include file="Public:footer"/>