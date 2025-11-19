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
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('videoRecorderAdd')}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <input type="hidden" name="logId" value="{pigcms{$_GET['logId']}"/>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="videoRecorderName">硬盘录像机名称</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="videoRecorderName" id="videoRecorderName" type="text" value="{pigcms{$monitorLog['videoRecorderName']}"/>
                                    <span class="form_tips">必填；</span>
								</div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="videoRecorderIp">内网ip</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="videoRecorderIp" id="videoRecorderIp" type="text" value="{pigcms{$monitorLog['videoRecorderIp']}" <if condition="$monitorLog['rtmpStatus']&&$monitorLog['rtmpStatus']==1">disabled</if> />
                                    <span class="form_tips">必填；对应设备的内网ip；注意一旦填写生效后不可变更，请谨慎填写；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="videoRecorderPort">ip端口</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="videoRecorderPort" id="videoRecorderPort" type="text" value="{pigcms{$monitorLog['videoRecorderPort']}"/>
                                    <span class="form_tips">必填；对应设备的内网ip的端口；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="ipAddress">映射ip</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="ipAddress" id="ipAddress" type="text" value="{pigcms{$monitorLog['ipAddress']}"/>
                                    <span class="form_tips">必填；映射对应设备的外网ip；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="ipPort">映射ip端口</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="ipPort" id="ipPort" type="text" value="{pigcms{$monitorLog['ipPort']}" <if condition="$monitorLog['rtmpStatus']&&$monitorLog['rtmpStatus']==1">disabled</if>/>
                                    <span class="form_tips">必填；映射对应设备的外网ip端口；注意一旦填写生效后不可变更，请谨慎填写；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="userName">账号</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="userName" id="userName" type="text" value="{pigcms{$monitorLog['userName']}" <if condition="$monitorLog['rtmpStatus']&&$monitorLog['rtmpStatus']==1">disabled</if>/>
                                    <span class="form_tips">必填；支持查看设备的账号；注意一旦填写生效后不可变更，请谨慎填写；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="password">密码</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="password" id="password" type="text" value="{pigcms{$monitorLog['password']}" <if condition="$monitorLog['rtmpStatus']&&$monitorLog['rtmpStatus']==1">disabled</if>/>
                                    <span class="form_tips">必填；对应账号的密码;注意一旦填写生效后不可变更，请谨慎填写；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="subType">码流类型</label></label>
                                    <label><input name="subType" type="radio" value="2" <if condition="!$monitorLog['subType']||$monitorLog['subType']==2">checked</if>/>&nbsp;&nbsp;辅码流</label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label><input name="subType" type="radio" value="1" <if condition="$monitorLog['subType']==1">checked</if>/>&nbsp;&nbsp;主码流</label>
                                    <span class="form_tips">必选；注意一旦选择生效后不可变更，请谨慎选择；</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="channelEnd">通道编号数</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="channelEnd" id="channelEnd" type="number" value="{pigcms{$monitorLog['channelEnd']}"/>
                                    <span class="form_tips">必填；填写了通道编号后会对应生成对应通道的摄像机信息，例如填写了10 会生成1-10（包含1和10）的10个通道摄像机；注意一旦填写生效后，只可变更更大的数值；</span>
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
        if($('#videoRecorderName').val() == ''){
            alert('硬盘录像机名称不能为空');
            return false;
        }else if($('#videoRecorderIp').val() == ''){
            alert('内网ip不能为空');
            return false;
        }else if($('#videoRecorderPort').val() == ''){
            alert('ip端口不能为空');
            return false;
        }else if($('#videoRecorderPort').val() == ''){
            alert('ip端口不能为空');
            return false;
        }else if($('#ipAddress').val() == ''){
            alert('映射ip不能为空');
            return false;
        }else if($('#ipPort').val() == ''){
            alert('映射ip端口不能为空');
            return false;
        }else if($('#userName').val() == ''){
            alert('账号不能为空');
            return false;
        }else if($('#password').val() == ''){
            alert('密码不能为空');
            return false;
        }else if($('#channelEnd').val() == ''){
            alert('通道编号数不能为空');
            return false;
        }
    }
</script>
<include file="Public:footer"/>