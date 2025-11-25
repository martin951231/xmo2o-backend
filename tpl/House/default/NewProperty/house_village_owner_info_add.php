<include file="NewPublic:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs"  >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('NewProperty/house_village_owner_info',array('iframe'=>true))}">业主资料</a>
			</li>
			<li class="active">添加档案字段</li>
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
							<input type="hidden" value="{pigcms{$_GET['record_cate_id']}" name="record_cate_id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2"><label for="name">字段名称：</label></label>
									<input class="col-sm-3" size="20" name="title" id="title" type="text" value=""/>
									<font color="red">&nbsp;&nbsp;*必填</font>
								</div>
							</div>
<!--							<div id="basicinfo" class="tab-pane active">-->
<!--								<div class="form-group">-->
<!--									<tr>-->
<!--										<label class="col-sm-1"><label for="name">唯一标识：</label></label>-->
<!--										<input type="text" class="col-sm-2" name="key" id="key" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/><font color="red">&nbsp;&nbsp;*必填且唯一</font>-->
<!---->
<!--									</tr>-->
<!--								</div>-->
<!--							</div>-->

							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-2"><label for="name">类型：</label></label>
										<td width="85%">
											<select name="type" id="type">
												<option value="1">文本</option>
												<option value="2">单选</option>
                                                <option value="4">日期</option>  <!--2020/07/29 注释：3有人用了，用到 籍贯上了 -->
											</select>
										</td>

									</tr>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active class_radio">
								<div class="form-group">
									<label class="col-sm-2"><label for="water_price">单选内容:</label></label>
                                    <textarea class="col-sm-3" style="height: 80px;" name="use_field"></textarea>
									<font color="">&nbsp;&nbsp;一行一个,将通过一行一个进行选择</font>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2">业主申请入住时是否展示:</label>

									<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="is_display"><span style="z-index: 1" class="lbl">是</span></label>
									<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="is_display"><span style="z-index: 1" class="lbl">否</span></label>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2">是否必填:</label>

									<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="is_must"><span style="z-index: 1" class="lbl">是</span></label>
									<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="is_must"><span style="z-index: 1" class="lbl">否</span></label>
								</div>
							</div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-2">是否关闭:</label>

                                    <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="1" name="is_close"><span style="z-index: 1" class="lbl">是</span></label>
                                    <label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="is_close" ><span style="z-index: 1" class="lbl">否</span></label>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-2">
                                        <img width="18" style="margin-bottom: 2px;" src="{pigcms{$static_path}images/qyWeixin/qyweixinIcon.png">
                                        是否同步至企业微信:
                                    </label>
                                    <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="1" name="is_syn"><span style="z-index: 1" class="lbl">是</span></label>
                                    <label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="is_syn" ><span style="z-index: 1" class="lbl">否</span></label>
                                </div>
                            </div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-2"><label for="name">排序：</label></label>
										<input type="text" class="col-sm-2" name="sort" id="sort" size="25" placeholder="" validate="maxlength:20,required:true" tips="排序"/>
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
	if($('#field_name').val()==''){
		alert('title！');
		return false;
	}
	if($('#key').val()==''){
		alert('title！');
		return false;
	}
}

$().ready(function(){
   $(".class_radio").hide();
});
$("#type").click(function(){
	console.log($("#type").val())
	if($("#type").val() == 2){
		$(".class_radio").show();
	}else{
		$(".class_radio").hide();
	}
})
</script>




<include file="NewPublic:footer"/>