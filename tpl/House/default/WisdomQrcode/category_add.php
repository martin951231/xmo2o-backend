<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<a href="{pigcms{:U('WisdomQrcode/category_index')}">分类管理</a>
			</li>
			<li class="active">添加分类</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
<!--							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">-->
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">分类名称：</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value=""/>
								</div>
							</div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="water_price">状态</label></label>
                                    <label><input name="status"  type="radio" value="1"/>&nbsp;&nbsp;正常</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="status"  type="radio" value="0"/>&nbsp;&nbsp;禁止</label>
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

	<div id="modal-table" class="modal fade map-modal" tabindex="-1" style="display:block;">
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
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    function check_submit(){
        if($('#name').val()==''){
            alert('名称不能为空！');
            return false;
        }
    }
</script>




<include file="Public:footer"/>