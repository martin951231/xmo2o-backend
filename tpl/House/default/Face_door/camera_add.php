<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Face_door/camera_list')}">人脸识别摄像机</a>
			</li>
			<li class="active">添加摄像机设备</li>
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
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('camera_add')}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <div class="form-group 	brand_box">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="brand_box">设备品牌</label></label>
                                    <label>
                                        <select name="device_brand" id="device_brand">
                                            <option value="">请选择设备品牌</option>
                                            <volist name="device_brand" id="vo">
                                                <option value="{pigcms{$vo.key}">{pigcms{$vo['title']}</option>
                                            </volist>
                                        </select>
                                    </label>
                                </div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="camera_sn">设备ID</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="camera_sn" id="camera_sn" type="text"/>
                                    <span class="form_tips">必填，一般是对应设备序列号或者sn码</span>
								</div>
                                <div class="form-group device_code" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_code">设备验证码</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_code" id="device_code" type="text" value=""/>
                                    <span class="form_tips">
                                        必填&nbsp;&nbsp;&nbsp;&nbsp;
                                        <a target="_blank" href="{pigcms{$brandHaikangCodeTip['href']}" style="color:blue;">{pigcms{$brandHaikangCodeTip['title']}</a>
                                    </span>
                                </div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="camera_name">设备名</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="camera_name" id="camera_name" type="text"/>
								</div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">对应位置</label></label>
									<fieldset id="choose_village" single_id="{pigcms{$aDoor['single_id']}" floor_id="{pigcms{$aDoor['floor_id']}"></fieldset>
								</div>
								<div class="form-group is_support_look_box">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="is_support_look">业主查看监控视频</label></label>
									<label><input name="is_support_look" type="radio" value="1"/>&nbsp;&nbsp;支持</label>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<label><input name="is_support_look" type="radio" value="0" checked/>&nbsp;&nbsp;不支持</label>
								</div>
								<div class="form-group is_support_look" style="display: none;">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="look_url">视频直播地址</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="look_url" id="look_url" type="text"/>
								</div>
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="url">坐标：</label></label>-->
<!--									<input class="col-sm-2" size="30" name="location" id="location" type="text"/>-->
<!--                                    <label style=" margin-left:10px"><a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a></label>-->
<!--                                    <label style="margin-left:10px"><span class="red">*&nbsp;&nbsp;坐标可不填写</span></label>-->
<!--								</div>-->
							</div>
						</div>
						<div class="space"></div>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit" <if condition="!in_array(227,$house_session['menus'])">disabled="disabled"</if> >
									<i class="ace-icon fa fa-check bigger-110"></i>
									保存
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<input type="hidden" id="villageLocation" value="{pigcms{$house_session.long},{pigcms{$house_session.lat}"/>
<div id="modal-table" class="modal fade" tabindex="-1">
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
					<input id="map-keyword" type="textbox" style="width:500px;padding:6px 4px;" placeholder="尽量填写城市、区域、街道名"/>
					<input type="submit" value="搜索" style="padding:4px 6px;margin-left:6px;"/>
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
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/service_map.js"></script>
<style>
.form-group>label{font-size:12px;line-height:24px;}
</style>
<script>
	var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
	var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
	var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
	var is_door = 1;
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose.js?t=006"></script>
<script>

    $('#device_brand').change(function(){
        var val = $(this).val();
        if ('brand_haikang'==val) {
            $('.is_support_look_box').hide();
            $('.is_support_look').hide();
            $('.device_code').show();
        } else {
            $('.is_support_look_box').show();
            $('.is_support_look').show();
            $('.device_code').hide();
        }
    });

	$('.is_support_look_box input').click(function() {
		var val = $(this).val();
        console.log('val',val);
		if (1==val) {
			$('.is_support_look').show();
		} else {
			$('.is_support_look').hide();
		}
	});


function check_submit(){
	if($('#camera_sn').val() == ''){
		alert('设备ID不能为空');
		return false;
	}else if($('#camera_name').val() == ''){
		alert('设备名不能为空');
		return false;
	}
	window.location.href = "{pigcms{:U('camera_add')}";
}
</script>
<include file="Public:footer"/>