<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('videoEdit',$_GET)}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <input type="hidden" name="camera_id" value="{pigcms{$_GET['camera_id']}"/>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="channelName">通道名称</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="channelName" id="channelName" type="text" value="{pigcms{$monitor['channelName']}"/>
                                    <span class="form_tips">必填；</span>
								</div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="channelIp">通道ip</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="channelIp" id="channelIp" type="text" value="{pigcms{$monitor['channelIp']}"/>
                                    <span class="form_tips">必填；对应设备的内网ip；注意一旦填写生效后不可变更，请谨慎填写；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="rtspUrl">原始流地址</label></label>
                                    <input size="60"  type="text" value="{pigcms{$monitor['rtspUrl']}" disabled/>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="rtmpUrl">转换后地址</label></label>
                                    <input size="60" type="text" value="{pigcms{$monitor['rtmpUrl']}" disabled/>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="videoTxt">播放路径</label></label>
                                    <input size="60" type="text" value="{pigcms{$monitor['videoTxt']}" disabled/>
                                </div>
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
<script>

    function check_submit(){
        if($('#channelName').val() == ''){
            alert('通道名称不能为空');
            return false;
        }
    }
</script>
<include file="Public:footer"/>