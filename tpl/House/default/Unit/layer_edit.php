<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Unit/single_list')}">楼栋管理</a>
			</li>
			<li class="active">编辑楼层</li>
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
						<input type="hidden" value="{pigcms{$single['id']}" name="id">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_name">楼层名称</label></label>
									<input class="col-sm-2" size="20" name="layer_name" id="layer_name" type="text" value="{pigcms{$single.layer_name}"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="layer_number">楼层编号</label></label>
                                    <input class="col-sm-2" size="20" name="layer_number" id="layer_number" type="number" value="{pigcms{$single.layer_number}"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项（仅限1-99不重复的数字）</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">排序</label></label>
									<input class="col-sm-2" size="20" name="sort" id="sort" type="text" value="{pigcms{$single.sort}"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="">数字越大越靠前</span>
								</div>
								<div class="form-group">
										<label class="col-sm-1">状态</label>
										<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$single["status"] eq 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
										<label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$single["status"] eq 0'>checked="checked"</if> name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
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
			</div>
		</div>
	</div>
	<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	KindEditor.ready(function(K) {
		var editor = K.editor({
			allowFileManager : true
		});
		K('#image3').click(function() {
			editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
			editor.loadPlugin('image', function() {
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#url3').val(),
					clickFn : function(url, title, width, height, border, align) {
						var img = K('#single_keeper_head_view');
						img.attr("src",url);
						img.show();
						K("#single_keeper_head").val(url);
						editor.hideDialog();
					}
				});
			});
		});

	});
function check_submit(){
	if($('#layer_name').val() == ''){
		alert('楼层名称不能为空！');
		return false;
	}


}


</script>

<include file="Public:footer"/>