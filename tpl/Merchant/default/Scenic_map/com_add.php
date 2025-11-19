<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('index')}">景区管理</a>
			</li>
			<li>
				<a href="{pigcms{:U('index')}">景区地图分类</a>
			</li>
			<li>
				<a href="{pigcms{:U('one_index',array('map_id'=>$_GET['map_fid']))}">地图二级分类</a>
			</li>
			<li>
				<a href="{pigcms{:U('com_list',array('map_id'=>$_GET['map_id'],'map_fid'=>$_GET['map_fid']))}">地图列表</a>
			</li>
			<li class="active">新增地图</li>
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
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">新增地图</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<input type="hidden" name="map_id" value="{pigcms{$_GET['map_id']}"/>
						<input type="hidden" name="map_fid" value="{pigcms{$_GET['map_fid']}"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label>地标名</label></label>
									<input type="text" name="com_name" value="" />
									<span class="form_tips">必填。</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">店铺经纬度</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" <if condition="$now_merchant['long'] neq 0">value="{pigcms{$now_merchant.long},{pigcms{$now_merchant.lat}"</if> type="text" readonly="readonly"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1">状态</label>
									<select name="status">
										<option value="1" selected="selected">开启</option>
										<option value="0">关闭</option>
									</select>
								</div>
							</div>
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
</div>
<div id="modal-table" class="modal fade" tabindex="-1" style="display:block;">
	<div class="modal-dialog" style="width:80%;">
		<div class="modal-content" style="width:100%;">
			<div class="modal-header no-padding" style="width:100%;">
				<div class="table-header">
					<button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						<span class="white">&times;</span>
					</button>
					(用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="尽量填写城市、区域、街道名"/>
					<input type="submit" value="搜索"/>
				</form>
				<div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
			</div>

			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					关闭
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- PAGE CONTENT ENDS -->
<script type="text/javascript" src="https://www.0855mlh.com/tpl/Merchant/default/static/js/scenic_map.js?12223"></script> 
<script type="text/javascript">
$('#edit_form').submit(function(){
	$.post("{pigcms{:U('com_add')}",$('#edit_form').serialize(),function(result){
		if(result.status == 1){
			alert(result.info);
			//window.location.href = "{pigcms{:U('com_list',array('map_id'=>$_GET['map_id'],'map_fid'=>$_GET['map_fid']))}";
		}else{
			alert(result.info);
		}
	})
	return false;
});
</script>
<include file="Public:footer"/>