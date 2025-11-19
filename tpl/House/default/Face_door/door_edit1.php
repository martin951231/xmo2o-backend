<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('door_edit')}">人脸识别门禁设置</a>
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
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('door_edit',array('device_id'=>$aDoor['device_id']))}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备ID</label></label>
                                    <if condition="$aDoor['login_time'] gt 0 || $aDoor['a2_face_door_doorkeeperId'] gt 0">
                                        {pigcms{$aDoor.device_sn}
                                        <else/>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_sn" id="device_sn" type="text" value="{pigcms{$aDoor.device_sn}"/>
                                    </if>
                                    <span class="form_tips">必填。设备ID必须与对应的人脸识别门禁设备保持一致，该设备ID一旦与人脸识别设备同步后将无法修改。</span>
								</div>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_name">设备名</label></label>
									<input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_name" id="device_name" type="text" value="{pigcms{$aDoor.device_name}"/>
                                    <span class="form_tips">必填</span>
								</div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_name">设备内网IP</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_ip" id="device_ip" type="text" value=""/>
                                    <span class="form_tips">非必填</span>
                                </div>
                                <if condition="$device_type_info">
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_type">设备类型</label></label>
                                        <select class="col-xs-4 col-sm-4 col-md-2" name="device_type" id="device_type">
                                            <volist name="device_type_info" id="vo">
                                                <option value="{pigcms{$vo.device_type}" <if condition="$aDoor['device_type'] eq $vo['device_type']">selected="selected"</if>>{pigcms{$vo['name']}</option>
                                            </volist>
                                        </select>
                                        <span class="form_tips">必选</span>
                                        <span class="form_tips device_type_syn" style="color: #1686ef;">选择当前类型设备添加，设备会进行后台同步相关数据，一旦同步成功，在列表中将会看到该设备的 最新同步时间</span>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">选择位置</label></label>
									<fieldset id="choose_village" single_id="{pigcms{$aDoor['single_id']}" floor_id="{pigcms{$aDoor['floor_id']}"></fieldset>
                                    <div class="multiple_floor_info_tip" style="color: #1686ef;margin-top: 5px;<if condition="$aDoor['device_type']">display: none;</if>">当前设备支持关联多单元，选择单元后下方会对应显示，如果所选位置为公共区域会清空下面数据，公共区域只能选择一个。</div>
                                    <div class="multiple_floor_info_box" style="margin-top: 10px;">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>
                                        <if condition="$floor_about_info">
                                            <volist name="floor_about_info" id="item">
                                                <div class="floor_info" style="margin-right: 5px;width:auto; display:inline-block !important; display:inline; ">{pigcms{$item['single_name']}#{pigcms{$item['floor_name']}<span class="del" style="color: red;font-size:18px;cursor:pointer">×</span><input type="hidden" name="floor_arr[]" value="{pigcms{$item['floor_id']}"></div>
                                            </volist>
                                        </if>
                                    </div>
								</div>
                                <div class="form-group device_show_7 device_hide_21" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_direction">门禁类型</label></label>
                                    <label><input name="device_model" type="radio" value="0" <if condition="$aDoor.device_model eq 0">checked</if> />&nbsp;&nbsp;门禁</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="device_model" type="radio" value="1" <if condition="$aDoor.device_model eq 1">checked</if> />&nbsp;&nbsp;电梯控制</label>
                                    <span class="form_tips">电梯控制必须是单元门，必须对应选择电梯安装层</span>
                                </div>
                                <div class="form-group choose_layer_box device_hide_21" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">电梯安装层</label></label>
                                    <fieldset id="choose_layer_field" layer_id="{pigcms{$aDoor['layer_id']}"></fieldset>
                                    <span class="form_tips" style="color: #1686ef;">请选择对应电梯控制安装的楼层(只显示2位的数字层)</span>
                                    <input name="device_layer" id="choose_device_layer" type="hidden" value=""/>
                                </div>
                                <div class="form-group" >
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="door_status">状态</label></label>
                                    <div id="a3_device_status_btn">
                                        <if condition="$aDoor.device_status eq 1">
                                            <span>正常</span>
                                            <else/>
                                            <span>停止/待机</span>
                                        </if>
                                    </div>
                                </div>
                                <div class="form-group device_type_1 device_type_7 device_hide_21 device_hide_212">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_alive">活体检测</label></label>
                                    <label><input name="device_alive" type="radio" value="1" <if condition="$aDoor.device_alive eq 1">checked</if> />&nbsp;&nbsp;开启</label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label><input name="device_alive" type="radio" value="0" <if condition="$aDoor.device_alive eq 0">checked</if> />&nbsp;&nbsp;关闭</label>
                                    <span class="form_tips">开启活体检测，可以增强验证用户是否为真实活体本人操作，有效规避照片、换脸、面具、遮挡以及屏幕翻拍等常见风险。</span>
                                </div>
                                <div class="form-group device_type_1 device_type_a185 device_type_3 device_hide_21">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_score">人脸识别阈值</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_score" id="device_score" type="number" max="100" min="0" value="{pigcms{$aDoor.device_score}"/>
                                    <span class="form_tips"> 0到100，越高越准确，也越可能出错。建议80左右。</span>
                                    <span class="form_tips"></span>
                                </div>
								<div class="form-group device_type_1 device_type_3 device_type_4 device_type_7 device_show_21">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_direction">标识</label></label>
									<label><input name="device_direction" type="radio" value="0" <if condition="$aDoor.device_direction eq 0">checked</if> />&nbsp;&nbsp;进</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="device_direction" type="radio" value="1" <if condition="$aDoor.device_direction eq 1">checked</if> />&nbsp;&nbsp;出</label>
                                    <span class="form_tips">请选择该门禁对应的是进门还是出门</span>
								</div>
                                <div class="form-group device_type_5 device_hide_21">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备验证码</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="cloud_code" id="cloud_code" type="text" value="{pigcms{$aDoor.cloud_code} "/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <!--A5设备 start-->
                                <div class="form-group device_type_5">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">是否支持可视对讲</label></label>
                                    <label><input name="is_visual" type="radio" value="0" <if condition="$aDoor.is_visual eq 0"> checked </if> />&nbsp;&nbsp;是</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="is_visual" type="radio" value="1" <if condition="$aDoor.is_visual eq 1"> checked </if> />&nbsp;&nbsp;否</label>
                                </div>
                                <!--A5设备 end-->
                                <div class="form-group device_show_21 other_hide" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="network_method">设备联网方式</label></label>
                                    <select class="col-xs-4 col-sm-4 col-md-2" name="network_method" id="network_method">
                                        <option value="4G" <if condition="$aDoor['network_method'] eq '4G'">selected="selected"</if>>4G</option>
                                        <option value="WIFI" <if condition="$aDoor['network_method'] eq 'WIFI'">selected="selected"</if>>WIFI</option>
                                        <option value="FIBER" <if condition="$aDoor['network_method'] eq 'FIBER'">selected="selected"</if>>FIBER</option>
                                        <option value="ADSL" <if condition="$aDoor['network_method'] eq 'ADSL'">selected="selected"</if>>ADSL</option>
                                        <option value="LAN" <if condition="$aDoor['network_method'] eq 'LAN'">selected="selected"</if>>LAN</option>
                                        <option value="OTHER" <if condition="$aDoor['network_method'] eq 'OTHER'">selected="selected"</if>>OTHER</option>
                                        <option value="TRAFFIC" <if condition="$aDoor['network_method'] eq 'TRAFFIC'">selected="selected"</if>>TRAFFIC</option>
                                        <option value="BROADBAND" <if condition="$aDoor['network_method'] eq 'BROADBAND'">selected="selected"</if>>BROADBAND</option>
                                    </select>
                                    <span class="form_tips">必选</span>
                                </div>
                                <div class="form-group device_show_21 other_hide" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="network_operator">网络运营商</label></label>
                                    <select class="col-xs-4 col-sm-4 col-md-2" name="network_operator" id="network_operator">
                                        <option value="DX" <if condition="$aDoor['network_operator'] eq 'DX'">selected="selected"</if>>电信</option>
                                        <option value="YD" <if condition="$aDoor['network_operator'] eq 'YD'">selected="selected"</if>>移动</option>
                                        <option value="LT" <if condition="$aDoor['network_operator'] eq 'LT'">selected="selected"</if>>联通</option>
                                        <option value="OTHER" <if condition="$aDoor['network_operator'] eq 'OTHER'">selected="selected"</if>>其他</option>
                                    </select>
                                    <span class="form_tips">必选</span>
                                </div>
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="url">坐标：</label></label>-->
<!--									<input class="col-sm-2" size="20" name="location" id="location" type="text" value="<if condition="$aDoor['lng'] != '0.0000000'">{pigcms{$aDoor.lng},{pigcms{$aDoor.lat}</if>"/>-->
<!--                                    <label style=" margin-left:10px"><a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a></label>-->
<!--                                    <label style="margin-left:10px"><span class="red">*&nbsp;&nbsp;坐标可不填写</span></label>-->
<!--								</div>-->
                                <!--d2设备 start-->
<!--                                <div class="form-group device_type_d2">-->
<!--                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">平台ip</label></label>-->
<!--                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="platform_ip" id="platform_ip" type="text" value=" "/>-->
<!--                                    <span class="form_tips">必填</span>-->
<!--                                </div>-->
<!--                                <div class="form-group device_type_d2">-->
<!--                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">平台端口号</label></label>-->
<!--                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="platform_port" id="platform_port" type="text" value=" "/>-->
<!--                                    <span class="form_tips">必填</span>-->
<!--                                </div>-->
                                <!--d2设备 start-->
                                <div class="form-group device_type_d2">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_domain">设备域名</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_domain" id="device_domain" type="text" value="{pigcms{$aDoor.device_domain}"/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <div class="form-group device_type_d2">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_port">设备端口</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_port" id="device_port" type="text" value="{pigcms{$aDoor.device_port}"/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <div class="form-group device_type_d2 device_show_21">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="internet_card">物联网卡号</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="internet_card" id="internet_card" type="text" value="{pigcms{$aDoor.internet_card}"/>
                                    <span class="form_tips">非必填</span>
                                </div>
                                <!--d2设备 end-->

                            <!--todo A185属性-->
                            <?php if($aDoor['device_type'] == 61){?>

                            <div class="form-group A185_">
                                <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_score">门禁关门时间</label></label>
                                <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_close_time" id="a185_close_time" type="number" max="99" min="1" value="{pigcms{$aDoor.a185_close_time}"/>
                                <span class="form_tips">门禁开门后多久关门（单位：秒数）</span>
                                <span class="form_tips"></span>
                            </div>
                            <div class="form-group A185_">
                                <p class="p_t">密码设置</p>
                                <div>
                                    <div class="pass1_">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">门禁管理密码</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_super_password"  type="text" placeholder="{pigcms{$aDoor.a185_super_password}" minlength="6"/>
                                        <span class="form_tips">必填 (不修改密码请留空，最少6个字符)，<span style="color: red">例如：设备上输入  *+密码</span></span>
                                    </div>
                                    <div class="pass1_">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">门禁开门密码</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_admin_password" type="text" placeholder="{pigcms{$aDoor.a185_admin_password}" minlength="6"/>
                                        <span class="form_tips">必填 (不修改密码请留空，最少6个字符)，<span style="color: red">例如：设备上输入  *+密码</span></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group A185_">
                                <p class="p_t">音量设置</p>
                                <div style="margin-left: 13px;">
                                    <div class="pass1_">
                                        <div class="form-group ">
                                            <p class="p_t">白天音量</p>
                                            <div>
                                                <div class="pass1_" >
                                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">时间区间</label></label>
                                                    <input type="text" class="input-text valid" name="b_time_left" value="{pigcms{$aDoor.a185_volume.day.time.0}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                                                    <input type="text" class="input-text" name="b_time_right" value="{pigcms{$aDoor.a185_volume.day.time.1}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                                                    <span class="form_tips"></span>
                                                </div>
                                                <div class="pass1_">
                                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">音量</label></label>
                                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="b_volume" type="number" value="{pigcms{$aDoor.a185_volume.day.size}"  placeholder="音量范围0~100" maxlength="3" min="0"/>
                                                    <span class="form_tips"></span>
                                                </div>
                                            </div>
                                            <p class="p_t">晚上音量</p>
                                            <div>
                                                <div class="pass1_" >
                                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">时间区间</label></label>

                                                    <input type="text" class="input-text valid" name="w_time_left" value="{pigcms{$aDoor.a185_volume.night.time.0}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                                                    <input type="text" class="input-text" name="w_time_right" value="{pigcms{$aDoor.a185_volume.night.time.1}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">

                                                    <span class="form_tips"></span>
                                                </div>
                                                <div class="pass1_">
                                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">音量</label></label>
                                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="w_volume" type="number" value="{pigcms{$aDoor.a185_volume.night.size}" placeholder="音量范围0~100" maxlength="3" min="0"/>
                                                    <span class="form_tips"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group A185_">
                                <p class="p_t">自动重启设备</p>
                                <div >
                                    <div class="pass1_">
                                        <span class="p_t2">每天是否重启设备</span>
                                        <div class="circular1">
                                            <div class="round-button1"></div>
                                        </div>
                                    </div>
                                    <div class="pass1_">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">重启时间</label></label>
                                        <input type="text" class="input-text valid" name="a185_restart_time" value="{pigcms{$aDoor.a185_restart_time}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group A185_">
                                <p class="p_t">电话卡设置</p>
                                <div>
                                    <div class="pass1_">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">是否有电话卡</label></label>

                                        <label style="padding-left:0px;padding-right:20px;"><input name="is_tele_card" value="1" type="radio" class="ace" <if condition="$aDoor['a185_tele_card']['is_tele_card'] eq 1">checked</if>/><span class="lbl" style="z-index: 1" >有</span></label>
                                        <label style="padding-left:0px;"><input name="is_tele_card" value="0" type="radio" class="ace" <if condition="$aDoor['a185_tele_card']['is_tele_card'] eq 0">checked</if> /><span class="lbl" style="z-index: 1">没有</span></label>
                                    </div>
                                    <div class="pass1_">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">手机号码</label></label>
                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="tele"  type="text" value="{pigcms{$aDoor.a185_tele_card.tele}" />
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
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
    var show_layer_url = "{pigcms{:U('Face_door/device_layer_list')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose_door.js?t={pigcms{:time()}"></script>
<if condition="$aDoor.device_type eq 3 || $aDoor.device_type eq 4 || $aDoor.device_type eq 24">
<script>
    var _this = $('#a3_device_status_btn');
    var device_sn = "{pigcms{$aDoor.device_sn}";
    $.ajax({
        url:"/index.php?g=Index&c=Update&a=get_device_info&is_ajax=1&device_sn=" + device_sn,
        type:"get",
        dataType: "Json",
        success:function(d){
            _this.html(d.status_txt);
            // alert(d.msg_status_txt);
            if(d.status == 1){
                _this.attr('style', 'color:green;');
            }else{
                _this.attr('style', 'color:red;');
            }
        }
    });
</script>
</if>
<script>
    $(function () {
        $('.circular1').click(function () {
            var left = $('.round-button1').css('left');
            left = parseInt(left);
            if (left == 0) {
                $('.round-button1').css({'left': '22px', 'background-color': '#0089ff'});
                $(this).css({'background-color': '#e7e7e7', 'box-shadow': '0 0 5px #999 inset'})
            } else {
                $("input[name='a185_restart_time']").val('')
                $('.round-button1').css({'left': '0', 'background-color': '#fff'})

            }
        })
        <?php if(!empty($aDoor['a185_restart_time'])){  ?>

        $('.round-button1').css({'left': '22px', 'background-color': '#0089ff'});
        $(this).css({'background-color': '#e7e7e7', 'box-shadow': '0 0 5px #999 inset'})
        <?php }  ?>
    })

</script>
<script>


function check_submit(){
	if($('#device_sn').val() == ''){
		alert('设备ID不能为空');
		return false;
	}
	else if($('#device_name').val() == ''){
		alert('设备名不能为空');
		return false;
	}
	else if($('#floor_id').val() == ''){
		alert('请选择对应位置');
		return false;
	}
    if($('#device_type').val() == 61){
        var a185_super_password=$("input[name='a185_super_password']").val(),
            a185_admin_password=$("input[name='a185_admin_password']").val(),
            b_time_left=$("input[name='b_time_left']").val(),
            b_time_right=$("input[name='b_time_right']").val(),
            b_volume=$("input[name='b_volume']").val(),
            w_time_left=$("input[name='w_time_left']").val(),
            w_time_right=$("input[name='w_time_right']").val(),
            w_volume=$("input[name='w_volume']").val(),
            a185_restart_time=$("input[name='a185_restart_time']").val(),
            is_tele_card=$("input[name='is_tele_card']:checked").val(),
            tele=$("input[name='tele']").val();
        if(b_time_left != '' || b_time_right != ''){
            var start = new Date('2020-01-02 '+b_time_left.replace("-", "/").replace("-", "/"));
            var end = new Date('2020-01-02 '+b_time_right.replace("-", "/").replace("-", "/"));
            console.log(start,end)
            if(end<start){
                alert('白天音量时间区间不符');
                return false;
            }
        }

        if(w_time_left != '' || w_time_right != ''){
            var start1 = new Date('2020-01-02 '+w_time_left.replace("-", "/").replace("-", "/"));
            var end1 = new Date('2020-01-02 '+w_time_right.replace("-", "/").replace("-", "/"));
            // if(end1<start1){
            //     alert('晚上音量时间区间不符');
            //     return false;
            // }
        }
        if(is_tele_card > 0){
            if(tele == ''){
                alert('请输入手机号');
                return false;
            }
        }
    }
	window.location.href = "{pigcms{:U('door_eidt')}";
}
$('.del').click(function () {
    $(this).closest('.floor_info').remove();
})

$("input:radio[name='is_tele_card']").change(function (){
   if($(this).val() == 0){
       $("input[name='tele']").val('');
   }
});

$('#device_type').change(function () {
    var val = $(this).val();
    $('.A185_').hide();
    if (1==val) {
        $('.device_type_5').hide();
        $('.other_hide').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_1').show();
        $('.device_type_d2').hide();
    }
    else if (2==val) {
		$('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
	}
    else if (3==val) {
		$('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.device_type_3').show();
        $('.multiple_floor_info_tip').show();
        $('.device_type_d2').hide();
	}
    else if(6==val){
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_syn').show();
        $('.device_type_5').show();
        $('.device_type_d2').hide();
    }
    else if(7==val) {
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.device_show_7, .device_type_7').show();
    }
    else if (4==val) {
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_syn').show();
        $('.device_type_3').show();
        $('.device_type_d2').hide();
    }
    else if(22==val) {
        $('.device_type_7').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_7').hide();
        $('.device_type_3').hide();
        $('.device_type_d2').show();
    }
    else if(21==val) {
        $('.device_type_1').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.device_hide_21').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.device_show_21').show();
    }
    else if (24==val) {
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_syn').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
    }else if(61==val){
        $('.device_type_7').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_7').hide();
        $('.device_type_d2').hide();
        $('.device_type_a185').show();
        $('.device_hide_212').show();
        $('.A185_').show();
    }
});
$('#device_type').change();
window.onload =function() {
    var device_type = "{pigcms{$aDoor.device_type}";
    if(device_type==5 || device_type==6){
        $('.device_type_1').hide();
        $('.device_show_7').hide();
        $('.device_type_syn').show();
        $('.device_type_5').show();
    }else if(7==device_type) {
        $('.device_type_5').hide();
        $('.device_show_7, .device_type_7').show();
    }else if (4==device_type) {
        $('.device_type_1').hide();
        $('.device_show_7').hide();
        $('.device_type_5').hide();
        $('.device_type_syn').show();
    }else if(21==device_type) {
        $('.device_type_1').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.device_hide_21').hide();
        $('.device_show_21').show();
    }
}
</script>
<include file="Public:footer"/>