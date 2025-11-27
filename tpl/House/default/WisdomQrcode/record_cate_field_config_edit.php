<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
			</li>

			<li class="active">编辑</li>
	 
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" name="id" value="{pigcms{$cate['acid']}" >
							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">
							<input type="hidden" value="{pigcms{$_GET['record_cate_id']}" name="record_cate_id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">字段名称：</label></label>
									<input class="col-sm-2" size="20" name="title" id="title" type="text" value="{pigcms{$cate.title}"/>
									<font color="red">&nbsp;&nbsp;*必填</font>
								</div>
							</div>
							<!-- <div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-1"><label for="name">唯一标识：</label></label>
										<input type="text" class="col-sm-2" name="key" id="key" size="25" value="{pigcms{$cate.key}" placeholder="" validate="maxlength:20,required:true" tips=""/><font color="red">&nbsp;&nbsp;*必填且唯一</font>

									</tr>
								</div>
							</div> -->

							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-1"><label for="name">类型：</label></label>
										<td width="85%">
											<select name="type" id="type">
												<option <if condition="$cate['type'] eq 1">selected="selected"</if> value="1">文本</option>
												<option <if condition="$cate['type'] eq 2">selected="selected"</if> value="2">图片</option>
												<option <if condition="$cate['type'] eq 3">selected="selected"</if> value="3">单选</option>
											</select>
										</td>

									</tr>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active class_radio">
								<div class="form-group">
									<label class="col-sm-1"><label for="water_price">单选内容:</label></label>
									<textarea class="col-sm-2"  style="height: 80px;padding-left: 1px;" name="use_field">{pigcms{$cate.use_field}</textarea>
									<font color="">&nbsp;&nbsp;一行一个,将通过一行一个进行选择</font>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">是否必填:</label>

									<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$cate.is_must eq 1"> checked="" </if> class="ace" value="1" name="is_must"><span style="z-index: 1" class="lbl">是</span></label>
									<label style="padding-left:0px;"><input type="radio" <if condition="$cate.is_must eq 0"> checked="" </if> class="ace" value="0" name="is_must"><span style="z-index: 1" class="lbl">否</span></label>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<tr>
										<label class="col-sm-1"><label for="name">排序：</label></label>
										<input type="text" class="col-sm-2" name="sort" id="sort" size="25" value="{pigcms{$cate.sort}" placeholder="" validate="maxlength:20,required:true" tips="排序"/>
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
<script type="text/javascript">
	function check_submit(){
		if($('#field_name').val()==''){
			alert('名称不能为空！');
			return false;
		}
		if($('#key').val()==''){
			alert('唯一标识不能为空！');
			return false;
		}
	}

    var type = "{pigcms{$cate.type}"
	$().ready(function(){

		if(type != 3){
			$(".class_radio").hide();
		}
	});


	$("#type").change(function(){
		console.log($("#type").val());
		if($("#type").val() == 3){
			$(".class_radio").show();
		}else{
			$(".class_radio").hide();
		}
	})
</script>




<include file="Public:footer"/>