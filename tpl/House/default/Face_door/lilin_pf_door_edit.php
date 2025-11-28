<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Face_door/door_list')}">人脸识别门禁列表</a>
			</li>
			<li class="active">修改设备</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
            <style>
                .A185_{
                    display: none;
                }
                .ace-file-input a {display:none;}
                .pass_h{
                    display: block;width: 100%;
                }
                .pass_h label{
                    font-weight:bold !important;
                }
                .pass1_{
                    margin-bottom: 5px;
                }
                .p_t{
                    font-size: 14px;font-weight: bold !important;margin: 5px 10px
                }
                .p_t2{
                    font-size: 14px;margin: 5px 10px;float: left;
                }

                .circular1{
                    width: 50px;
                    height: 30px;
                    border-radius: 16px;
                    background-color: #ccc;
                    transition: .3s;
                    cursor: pointer;
                    margin-left: 8.5%;
                }
                .round-button1{
                    width: 30px;
                    height: 30px;
                    background: #fff;
                    border-radius: 50%;
                    box-shadow: 0 1px 5px rgba(0,0,0,.5);
                    transition: .3s;
                    position: relative;
                    left: 0;
                }
                .round-button1:hover{
                    transform: scale(1.2);
                    box-shadow: 0 1px 8px rgba(0,0,0,.5);
                }
            </style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('door_edit',array('device_id'=>$aDoor['device_id'],'deviceType'=>$device_type))}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                  <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label >设备品牌</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" type="text" value="立林门禁" disabled="ture"/>
                                  </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label >设备厂商</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20"  type="text" value="{pigcms{$aDoor['extra_data']['manufacturer']}" disabled="ture"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label >设备型号</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20"  type="text" value="{pigcms{$aDoor['brand_type']}" disabled="ture"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label >产品名称</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20"  type="text" value="{pigcms{$aDoor['extra_data']['productName']}" disabled="ture"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label >设备号码</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20"  type="text" value="{pigcms{$aDoor['device_no']}" disabled="ture"/>
                                    </div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备ID</label></label>
                                       <input class="col-xs-4 col-sm-4 col-md-2" size="20" id="device_sn" type="text" value="{pigcms{$aDoor.device_sn}" disabled="ture"/>
								</div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_name">设备名</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_name" id="device_name" type="text" value="{pigcms{$aDoor.device_name}" />
								</div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_name">设备内网IP</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" id="device_ip" type="text" value="{pigcms{$aDoor.device_ip}"    disabled="ture" />

                                </div>
                                <div class="form-group third_protocol_hide_16">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="thirdLoginName">设备用户名</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" type="text" value="{pigcms{$aDoor.thirdLoginName}"  disabled="ture" />
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1">
                                        <label class="third_protocol_hide_16">设备密码</label>
                                    </label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" type="text" value="{pigcms{$aDoor.thirdLoginPassword}"  disabled="ture" />
                                </div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">选择位置</label></label>
									<fieldset id="choose_village" style="width: 330px;display: inline-block;vertical-align: middle;" single_id="{pigcms{$aDoor['single_id']}" floor_id="{pigcms{$aDoor['floor_id']}"></fieldset>
                                    <if condition="$aDoor['device_type'] eq 29">
                                        <span class="form_tips dopu">必选</span>
                                    </if>
                 
                                    <div class="multiple_floor_info_box" style="margin-top: 10px;">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>
                                        <if condition="$floor_about_info">
                                            <volist name="floor_about_info" id="item">
                                                <div class="floor_info" style="margin-right: 5px;width:auto; display:inline-block !important; display:inline; ">{pigcms{$item['single_name']}#{pigcms{$item['floor_name']}<span class="del" style="color: red;font-size:18px;cursor:pointer">×</span><input type="hidden" name="floor_arr[]" value="{pigcms{$item['floor_id']}"></div>
                                            </volist>
                                        </if>
                                    </div>
								</div>
 
								<div class="form-group device_type_1 device_type_3 device_type_4 device_type_7 device_show_21 third_protocol_16">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_direction">标识</label></label>
									<label><input name="device_direction" type="radio" value="0" <if condition="$aDoor.device_direction eq 0">checked</if> />&nbsp;&nbsp;进</label>&nbsp;&nbsp;
									&nbsp;&nbsp;&nbsp;
									<label><input name="device_direction" type="radio" value="1" <if condition="$aDoor.device_direction eq 1">checked</if> />&nbsp;&nbsp;出</label>&nbsp;&nbsp;
                                    <label><input name="device_direction" type="radio" value="2" <if condition="$aDoor.device_direction eq 2">checked</if> />&nbsp;&nbsp;进&出</label>
                                    <span class="form_tips">请选择该门禁对应的是进门还是出门</span>
								</div>

                                <div class="form-group device_type_d2">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_port">设备端口</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20"  type="text" value="{pigcms{$aDoor.thirdPort}"  disabled="ture" />
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="remark">备注</label></label>
                                    <textarea class="col-xs-4 col-sm-4 col-md-2" name="remark" id="remark" rows="5">{pigcms{$aDoor.remark}</textarea>
                                </div>

                                <div class="form-group" >
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="door_status">状态</label></label>
                                    <div id="a3_device_status_btn" style="line-height: 33px">
                                        <if condition="$aDoor.device_status eq 1">
                                            <span>正常</span>
                                            <else/>
                                            <span>停止/待机</span>
                                        </if>
                                    </div>
                                </div>
                                <if condition="$synOverView">
                                    <if condition="$synOverView['syn_msg']">
                                        <div class="form-group" >
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="door_status">同步信息</label></label>
                                            <div id="a3_device_status_btn" style="line-height: 33px">
                                                {pigcms{$synOverView['syn_msg']}
                                            </div>
                                        </div>
                                    </if>
                                    <if condition="$synOverView['syn_time_text']">
                                        <div class="form-group" >
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="door_status">同步时间</label></label>
                                            <div id="a3_device_status_btn" style="line-height: 33px">
                                                {pigcms{$synOverView['syn_time_text']}
                                            </div>
                                        </div>
                                    </if>
                                </if>
							</div>
						</div>
						<div class="space"></div>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit" <if condition="!in_array(285,$house_session['menus'])">disabled="disabled"</if> >
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


<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>

<style>
.form-group>label{font-size:12px;line-height:24px;}
</style>
<script>
	var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
	var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
	var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
	var is_door = 1;
    var show_layer_url = "{pigcms{:U('Face_door/device_layer_list')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose_door.js?t={pigcms{:time()}"></script>

<script>
function check_submit(){

    return true;
}

function copyUrlText(text)
{
    console.log('text', text);
    // 创建一个临时的输入框来存储文本
    const input = document.createElement('input');
    input.value = text;
    // 将输入框添加到页面，但不显示
    document.body.appendChild(input);
    input.select(); // 选择输入框的内容
    // 执行复制操作
    const isSuccessful = document.execCommand('copy');
    layer.alert('复制成功了~');
    // 清除临时输入框
    document.body.removeChild(input);
    // 返回操作结果
    return isSuccessful;
}


$('.del').click(function () {
    $(this).closest('.floor_info').remove();
})
window.onload =function() {
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
}
</script>
<include file="Public:footer"/>