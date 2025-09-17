<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
			</li>
			<li class="active">添加类别</li>
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
									<label class="col-sm-1"><label for="name">类别名称：</label></label>
									<input class="col-sm-2" size="20" name="cate_name" id="cate_name" type="text" value=""/>
								</div>
							</div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">距离设置：</label></label>
                                    工作人员需距离扫码点<input style="width: 100px;" name="distance" type="text" value=""/>
                                    米,才可扫码记录内容(不填默认不限距离)
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="type">负责人任务配置</label></label>
                                    <label class="col-sm-1"><input name="type" class="type" checked="checked"  type="radio" value="1"/>&nbsp;&nbsp;按单个负责人</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label class="col-sm-1"><input name="type" class="type"  type="radio" value="2"/>&nbsp;&nbsp;按所有负责人</label>
                                    &nbsp;&nbsp;<label class="col-sm-1" style="width: 70%"><font color="red">按所有负责人逻辑：类别名称（ 例：小区环境巡检 ）下有设置多个负责人巡检时，只有其中一个负责人巡检过所有的二维码点位，所有负责人都算已完成巡检；<br/>按单个负责人逻辑：类别名称（ 例：小区设备巡检 ）下有设置多个负责人巡检时，如果是其中一个负责人巡检完成，只有当前负责人算完成，其他负责人不算完成巡检。</font></label>
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
	if($('#cate_name').val()==''){
		alert('名称不能为空！');
		return false;
	}
}
</script>




<include file="Public:footer"/>