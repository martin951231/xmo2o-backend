<include file="Public:header"/>

<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Face_door/door_list')}">人脸识别门禁列表</a>
			</li>
			<li class="active">添加设备</li>
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
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('door_add',array('deviceType'=>$device_type))}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <if condition="$device_brand">
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_type">设备品牌</label></label>
                                        <select class="col-xs-4 col-sm-4 col-md-2" name="brand_type" id="brand_type">
                                            <option value="">请选择设备品牌</option>
                                            <volist name="device_brand" id="vo">
                                                <option value="{pigcms{$vo.key}" data-key="{pigcms{$vo['id']}">{pigcms{$vo['title']}</option>
                                            </volist>
                                        </select>
                                        <span class="form_tips">必选</span>
                                    </div>
                                </if>
                                <if condition="$device_type_info">
                                    <div class="form-group">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_type">设备类型</label></label>
                                        <select class="col-xs-4 col-sm-4 col-md-2" name="device_type" id="device_type" >
                                            <option value="">请选择设备类型</option>
                                        </select>
                                        <input hidden name="device_brand_type" id="device_brand_type" type="text" value=""/>
                                        <span class="form_tips">必选</span>
                                        <span class="form_tips device_type_syn" style="color: #1686ef;">选择当前类型设备添加，设备会进行后台同步相关数据，一旦同步成功，在列表中将会看到该设备的 最新同步时间</span>
                                    </div>
                                </if>
                                <if condition="$thirdProtocol">
                                    <volist name="thirdProtocol" id="protocol">
                                        <div class="form-group {pigcms{$key}_box thirdProtocolBox" style="display: none;">
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_type">设备协议</label></label>
                                            <select class="col-xs-4 col-sm-4 col-md-2" id="{pigcms{$key}_thirdProtocol">
                                                <option value="">请选择设备协议</option>
                                                <volist name="protocol" id="items">
                                                    <option value="{pigcms{$items['thirdProtocol']}">{pigcms{$items['thirdTitle']}</option>
                                                </volist>
                                            </select>
                                            <span class="form_tips">必选</span>
                                            <span class="form_tips device_type_syn" style="color: #1686ef;">选择当前类型设备添加，必须选择一个协议类型</span>
                                        </div>
                                    </volist>
                                    <input style="display: none;" name="thirdProtocol" id="thirdProtocol" value="{pigcms{$aDoor['thirdProtocol']}">
                                </if>

                                <div class="form-group third_isup_version_box third_protocol_16" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">协议类型</label></label>
                                    <label><input name="isup_version" type="radio" value="ehome" checked/>&nbsp;&nbsp;ISUP2.0</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="isup_version" type="radio" value="ehomeV5" />&nbsp;&nbsp;ISUP5.0</label>
                                    <span class="form_tips">必选</span>
                                    &nbsp;&nbsp;&nbsp;<img src="{pigcms{$static_public}file/village/faceDevice/image_explain.png" style="width:30px;height:30px;" class="view_msg_explain"/>
                                    <span class="form_tips" style="color: #1686ef;">协议类型："ehome"-ISUP2.0(智能安全上行协议 v2.0版本)，"ehomeV5"-ISUP5.0(智能安全上行协议 v5.0版本)。点击图片可以查看。</span>
                                    <input id="isup_version_explain_input" type="hidden" value="{pigcms{$static_public}file/village/faceDevice/isup_version_explain.png">
                                </div>

                                <div class="form-group third_isup_version_box third_protocol_16" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">适用设备型号</label></label>
                                    <img src="{pigcms{$static_public}file/village/faceDevice/image_explain.png" style="width:30px;height:30px;" class="view_msg_applicable_model_explain"/>
                                    <span style="color: #1686ef;">&nbsp;点击图片可以查看</span>
                                    <input id="applicable_model_explain" type="hidden" value="{pigcms{$static_public}file/village/faceDevice/applicable_model_explain.png">
                                    <input id="device_mode_info_explain" type="hidden" value="{pigcms{$static_public}file/village/faceDevice/device_mode_info_explain.png">
                                </div>

                                <div class="form-group device_hide_26">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备ID</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_sn" id="device_sn" type="text" value=""/>
                                    <span class="form_tips">必填</span>
                                    <span class="form_tips" style="color: #1686ef;">设备ID必须与对应的人脸识别门禁设备保持一致，该设备ID一旦与人脸识别设备同步后不要进行修改。</span>
                                </div>
                                <div class="form-group device_show_26" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备ID</label></label>
                                    <select class="col-xs-4 col-sm-4 col-md-2 device_sn_z6" name="device_sn_26" id="device_sn_26">
                                        <option value="">请选择设备ID</option>
                                    </select>
                                    <span class="form_tips">必填</span>
                                    <span class="form_tips" style="color: #1686ef;">设备ID必须与对应的人脸识别门禁设备保持一致，该设备ID一旦与人脸识别设备同步后不要进行修改。</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_name">设备名</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_name" id="device_name" type="text" value=""/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_ip">设备内网IP</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_ip" id="device_ip" type="text" value=""/>
                                </div>
                                <div class="form-group third_protocol_hide_16">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="thirdLoginName">设备用户名</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="thirdLoginName" id="thirdLoginName" type="text" value=""/>
                                    <span class="form_tips">建议填写  对应设备用户名，就是使用ip访问时候的用户名</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1">
                                        <label for="thirdLoginPassword" class="third_protocol_hide_16">设备密码</label>
                                        <label for="thirdLoginPassword" class="third_isup_version_box third_protocol_16" style="display: none;">登录密钥</label>
                                    </label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="thirdLoginPassword" id="thirdLoginPassword" type="text" value=""/>
                                    <span class="form_tips third_protocol_hide_16">建议填写  对应设备密码，就是使用ip访问时候的密码</span>
                                    <span class="form_tips third_isup_version_box third_protocol_16" style="display: none;">必填</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">选择位置</label></label>
                                    <fieldset id="choose_village" style="width: 330px;display: inline-block;vertical-align: middle;" single_id="{pigcms{$aDoor['single_id']}" floor_id="{pigcms{$aDoor['floor_id']}"></fieldset>
                                    <span class="form_tips device_show_29">必选</span>
                                    <div class="multiple_floor_info_tip" style="color: #1686ef;display: none;margin-top: 5px;">当前设备支持关联多单元，选择单元后下方会对应显示，如果所选位置为公共区域会清空下面数据，公共区域只能选择一个。</div>
                                    <div class="multiple_floor_info_box" style="margin-top: 10px;">
                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>
                                    </div>
                                </div>
                                <div class="form-group device_show_7 device_show_30 device_hide_21" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_direction">门禁类型</label></label>
                                    <label><input name="device_model" type="radio" value="0" checked/>&nbsp;&nbsp;门禁</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="device_model" type="radio" value="1"/>&nbsp;&nbsp;电梯控制</label>
                                    <span class="form_tips">电梯控制必须是单元门，必须对应选择电梯安装层</span>
                                </div>
                                <div class="form-group choose_layer_box device_hide_21" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="aFloor">电梯安装层</label></label>
                                    <fieldset id="choose_layer_field"></fieldset>
                                    <span class="form_tips" style="color: #1686ef;">请选择对应电梯控制安装的楼层(只显示2位的数字层)</span>
                                    <input name="device_layer" id="choose_device_layer" type="hidden" value=""/>
                                </div>
                                <div class="form-group device_show_30" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="thirdChnNum">房号匹配</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="thirdChnNum" id="thirdChnNum" type="number" max="100" min="0" value="{pigcms{$aDoor.thirdChnNum}"/>
                                    <span class="form_tips" style="color: #1686ef;"> 填写了房号匹配后，对应后几位完全匹配房号的人员才会进行同步。</span>
                                    <span class="form_tips"></span>
                                </div>
                                <div class="form-group device_type_1 device_type_7 device_hide_21 device_hide_212">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_alive">活体检测</label></label>
                                    <label><input name="device_alive" type="radio" value="1" checked/>&nbsp;&nbsp;开启</label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <label><input name="device_alive" type="radio" value="0"/>&nbsp;&nbsp;关闭</label>
                                    <span class="form_tips" style="color: #1686ef;">开启活体检测，可以增强验证用户是否为真实活体本人操作，有效规避照片、换脸、面具、遮挡以及屏幕翻拍等常见风险。</span>
                                </div>
                                <div class="form-group device_type_1 device_type_3 device_hide_21">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_score">人脸识别阈值</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_score" id="device_score" type="number" max="100" min="0" value="{pigcms{$aDoor.device_score}"/>
                                    <span class="form_tips" style="color: #1686ef;"> 0到100，越高越准确，也越可能出错。建议80左右。</span>
                                    <span class="form_tips"></span>
                                </div>
								<div class="form-group device_type_1 device_type_3 device_type_4 device_type_7 device_show_21 third_protocol_16">
									<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_direction">标识</label></label>
									<label><input name="device_direction" type="radio" value="0" checked/>&nbsp;&nbsp;进</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="device_direction" type="radio" value="1"/>&nbsp;&nbsp;出</label>
                                    <span class="form_tips">请选择该门禁对应的是进门还是出门</span>
								</div>
                                <div class="form-group device_type_5 third_protocol_hide_16">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备验证码</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="cloud_code" id="cloud_code" type="text" value=""/>
                                    <span class="form_tips">
                                        必填&nbsp;&nbsp;&nbsp;&nbsp;
                                        <a class="device_type_5" target="_blank" href="{pigcms{$brandHaikangCodeTip['href']}" style="display:none;color:blue;">{pigcms{$brandHaikangCodeTip['title']}</a>
                                    </span>
                                </div>
                                <!--A5设备 start-->
                                <div class="form-group device_type_5">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">是否支持可视对讲</label></label>
                                    <label><input name="is_visual" type="radio" value="0" />&nbsp;&nbsp;是</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="is_visual" type="radio" value="1" checked />&nbsp;&nbsp;否</label>
                                </div>
                                
                                <div class="form-group device_type_31" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">WebSocket注册</label></label>
                                    <a href="javascript:void(0);" onclick="copyUrlText('{pigcms{$wsUrl}')" style="margin-left: 5px;color: #1686ef;"><span>{pigcms{$wsUrl}</span>&nbsp;点击复制</a>
                                    &nbsp;&nbsp;
                                    <span style="color: #1686ef;cursor: pointer;"  class="viewMsgWebSocketSettingExplain">
                                        <img src="{pigcms{$static_public}file/village/faceDevice/image_explain.png" style="width:30px;height:30px;"/>点击图片可以查看
                                    </span>
                                    <input id="webSocketSettingExplain" type="hidden" value="{pigcms{$webSocketSettingExplain}">
                                </div>
                                <div class="form-group device_type_31" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">HTTP反向注册</label></label>
                                    <a href="javascript:void(0);" onclick="copyUrlText('{pigcms{$deviceTypeNoticeUrl}')" style="margin-left: 5px;color: #1686ef;"><span>{pigcms{$deviceTypeNoticeUrl}</span>&nbsp;点击复制</a>
                                    &nbsp;&nbsp;
                                    <span style="color: #1686ef;cursor: pointer;"  class="viewMsgXiHanHttpNotification">
                                        <img src="{pigcms{$static_public}file/village/faceDevice/image_explain.png" style="width:30px;height:30px;"/>点击图片可以查看
                                    </span>
                                    <input id="xiHanHttpNotification" type="hidden" value="{pigcms{$xiHanHttpNotification}">
                                </div>
                                <div class="form-group device_type_31" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">人脸识别终端设置</label></label>
                                    <div class="col-xs-8 col-sm-8">
                                        <volist name="settingExplain" id="explainItem" key="explainKey">
                                            <div onclick="showExplain('{pigcms{$explainItem.value}')" style="height:30px;color: #1686ef;cursor: pointer;margin-bottom: 5px;">
                                                 {pigcms{$explainKey}. {pigcms{$explainItem['label']}&nbsp;:&nbsp;
                                                <img src="{pigcms{$static_public}file/village/faceDevice/image_explain.png">点击图片可以查看 </img>
                                            </div>
                                        </volist>
                                    </div>
                                </div>
                                
                                <!--A5设备 end-->
                                <div class="form-group device_show_21 other_hide" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="network_method">设备联网方式</label></label>
                                    <select class="col-xs-4 col-sm-4 col-md-2" name="network_method" id="network_method">
                                        <option value="4G">4G</option>
                                        <option value="WIFI">WIFI</option>
                                        <option value="FIBER">FIBER</option>
                                        <option value="ADSL">ADSL</option>
                                        <option value="LAN">LAN</option>
                                        <option value="OTHER">OTHER</option>
                                        <option value="TRAFFIC">TRAFFIC</option>
                                        <option value="BROADBAND">BROADBAND</option>
                                    </select>
                                    <span class="form_tips">必选</span>
                                </div>
                                <div class="form-group device_show_21 other_hide" style="display: none;">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="network_operator">网络运营商</label></label>
                                    <select class="col-xs-4 col-sm-4 col-md-2" name="network_operator" id="network_operator">
                                        <option value="DX">电信</option>
                                        <option value="YD">移动</option>
                                        <option value="LT">联通</option>
                                        <option value="OTHER">其他</option>
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
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备域名</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_domain" id="platform_port" type="text" value=""/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <div class="form-group device_type_d2">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">设备端口</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="device_port" id="platform_port" type="text" value=""/>
                                    <span class="form_tips">必填</span>
                                </div>
                                <div class="form-group device_type_d2 device_show_21">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">物联网卡号</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="internet_card" id="internet_card" type="text" value=""/>
                                    <span class="form_tips">非必填</span>
                                </div>
                                <!--d2设备 end-->



                                <!--todo A185属性-->

                                <div class="form-group A185_">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_score">门禁关门时间</label></label>
                                    <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_close_time" id="a185_close_time" type="number" max="100" min="1" value="3"/>
                                    <span class="form_tips">门禁开门后多久关门（单位：秒数）</span>
                                    <span class="form_tips"></span>
                                </div>


                                <div class="form-group A185_">
                                    <p class="p_t">密码设置</p>
                                    <div>
                                        <div class="pass1_">
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">门禁管理密码</label></label>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_super_password"  type="text" value="" minlength="6"/>
                                            <span class="form_tips">必填 (最少6个字符)，<span style="color: red">例如：设备上输入  *+密码</span></span>
                                        </div>
                                        <div class="pass1_">
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">门禁开门密码</label></label>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_admin_password" type="text" value="" minlength="6"/>
                                            <span class="form_tips">必填 (最少6个字符)，<span style="color: red">例如：设备上输入  *+密码</span></span>
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
                                                        <input type="text" class="input-text valid" name="b_time_left" value="" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                                                        <input type="text" class="input-text" name="b_time_right" value="" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                                                        <span class="form_tips"></span>
                                                    </div>
                                                    <div class="pass1_">
                                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">音量</label></label>
                                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="b_volume" type="number" value=""  placeholder="音量范围0~100"  maxlength="3" min="0"/>
                                                        <span class="form_tips"></span>
                                                    </div>
                                                </div>
                                                <p class="p_t">晚上音量</p>
                                                <div>
                                                    <div class="pass1_" >
                                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">时间区间</label></label>

                                                        <input type="text" class="input-text valid" name="w_time_left" value="" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                                                        <input type="text" class="input-text" name="w_time_right" value="" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">

                                                        <span class="form_tips"></span>
                                                    </div>
                                                    <div class="pass1_">
                                                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">音量</label></label>
                                                        <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="w_volume" type="number" value="" placeholder="音量范围0~100"  maxlength="3" min="0"/>
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
                                            <input type="text" class="input-text valid" name="a185_restart_time" value="" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group A185_">
                                    <p class="p_t">电话卡设置</p>
                                    <div>
                                        <div class="pass1_">
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">是否有电话卡</label></label>

                                            <label style="padding-left:0px;padding-right:20px;"><input name="is_tele_card" value="1" type="radio" class="ace" checked/><span class="lbl" style="z-index: 1" >有</span></label>
                                            <label style="padding-left:0px;"><input name="is_tele_card" value="0" type="radio" class="ace" /><span class="lbl" style="z-index: 1">没有</span></label>
                                        </div>
                                        <div class="pass1_">
                                            <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_sn">手机号码</label></label>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="tele"  type="text" value="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="device_param">设备相关参数</label></label>
                                    <textarea class="col-xs-4 col-sm-4 col-md-2" name="device_param" id="device_param" rows="5"></textarea>
                                  </div>
                                <div class="form-group">
                                    <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="remark">备注</label></label>
                                    <textarea class="col-xs-4 col-sm-4 col-md-2" name="remark" id="remark" rows="5"></textarea>
                                </div>
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
<!--
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
<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/service_map.js"></script>
--->
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
    $(function () {
        $('.circular1').click(function () {
            var left = $('.round-button1').css('left');
            left = parseInt(left);
            if (left == 0) {
                $('.round-button1').css({'left': '22px', 'background-color': '#0089ff'});
                $(this).css({'background-color': '#e7e7e7', 'box-shadow': '0 0 5px #999 inset'})
            } else {
                $('.round-button1').css({'left': '0', 'background-color': '#fff'})

            }
        })


        //预览大图
        $('.view_msg_explain').click(function () {
            var img = $('#isup_version_explain_input').val();
            layer.open({
                type: 1,
                offset: "10px",
                area: ['1505px', '700px'],
                content: '<img style="height: 100%;" src="'+img+'"/>'
            });
            $('.layui-layer-content').css('text-align','center');
        });
        //预览大图
        $('.view_msg_applicable_model_explain').click(function () {
            var img = $('#applicable_model_explain').val();
            var img1 = $('#device_mode_info_explain').val();
            layer.open({
                type: 1,
                offset: "10px",
                area: ['925px', '800px'],
                content: '<img style="height: 30%;" src="'+img+'"/><br/><img style="height: 70%;" src="'+img1+'"/>'
            });
            $('.layui-layer-content').css('text-align','center');
        });
        $('.viewMsgWebSocketSettingExplain').click(function () {
            var img = $('#webSocketSettingExplain').val();
            layer.open({
                type: 1,
                offset: "10px",
                area: ['1400px', '650px'],
                content: '<img style="width: 99%;" src="'+img+'"/>'
            });
            $('.layui-layer-content').css('text-align','center');
        });
        $('.viewMsgXiHanHttpNotification').click(function () {
            var img = $('#xiHanHttpNotification').val();
            layer.open({
                type: 1,
                offset: "10px",
                area: ['1440px', '620px'],
                content: '<img style="width: 99%;" src="'+img+'"/>'
            });
            $('.layui-layer-content').css('text-align','center');
        });
    })
</script>
<script>
function check_submit(){
    var brand_key = $('#brand_type').val();
    var thirdProtocol = $('#thirdProtocol').val();
    if($('#device_type').val() == 26 && $('#device_sn_26').val() == ''){
        alert('设备ID不能为空');
        return false;
    }else if($('#device_type').val() != 26 && $('#device_sn').val() == ''){
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
	else if($('#device_type').val() == 5 && $('#cloud_code').val() == '' && thirdProtocol != 16 && thirdProtocol != 17){
        alert('验证码必填');
        return false;
    }
    else if($('#device_type').val() == 5 && $('#'+brand_key+'_thirdProtocol').val() == '') {
        alert('请选择设备协议');
        return false;
    }
    else if($('#device_type').val() == 88 && $('#'+brand_key+'_thirdProtocol').val() == '') {
        alert('请选择设备协议');
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
        if(a185_super_password == ''){
            alert('请输入门禁管理密码');
            return false;
        }
        if(a185_admin_password == ''){
            alert('请输入门禁开门密码');
            return false;
        }
        if(a185_super_password == a185_admin_password){
            alert('门禁管理密码和门禁开门密码不可相同');
            return false;
        }
        if(b_time_left != '' || b_time_right != ''){
            var start = new Date('2020-01-02 '+b_time_left.replace("-", "/").replace("-", "/"));
            var end = new Date('2020-01-02 '+b_time_right.replace("-", "/").replace("-", "/"));
            // console.log(start,end)
            if(end < start){
                alert('白天音量时间区间不符');
                return false;
            }
        }

        if(w_time_left != '' || w_time_right != ''){
            var start1 = new Date('2020-01-02 '+w_time_left.replace("-", "/").replace("-", "/"));
            var end1 = new Date('2020-01-02 '+w_time_right.replace("-", "/").replace("-", "/"));
            // console.log(start1,end1)
            // if(end1 == start1){
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

    if($('#device_type').val() == 29){
        // 朵普门禁位置必填
        var single_id = $('#choose_single').val()
        var floor_id = $('#choose_floor').val()
        // console.log('================',floor_id)
        // 位置必填
        if(single_id == 0 || single_id == '' || (single_id > 0 && (floor_id == 0 || floor_id == '' || floor_id == undefined))){
            alert('请选择对应位置');
            return false;
        }
    }
	window.location.href = "{pigcms{:U('door_add')}";
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

function showExplain(img)
{
    layer.open({
        type: 1,
        offset: "10px",
        area: ['1400px', '680px'],
        content: '<img style="height: 96%;" src="'+img+'"/>'
    });
    $('.layui-layer-content').css('text-align','center');
}

$('#device_sn_26').change(function () {
    var ip=$(this).find('option:selected').attr('data-ip');
        // console.log('ip',ip);
        $('#device_ip').val(ip);
    }

)
$('#device_type').change(function () {
    var val = $(this).val();
    // console.log(val);
    $('.A185_').hide();
    $('.device_show_29').hide();
    $('.device_show_26').hide();
    $('.device_hide_26').show();
    $('.device_show_30').hide();
    $('.device_type_31').hide();
    if (1==val) {
        $('.device_type_syn').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_1').show();
        $('.device_type_d2').hide();
        $('.dahua').hide();
    }
    else if (2==val) {
		$('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_syn').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.dahua').hide();
	}
    else if (3==val) {
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_syn').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').show();
        $('.device_type_3').show();
        $('.device_type_d2').hide();
        $('.dahua').hide();
    }
    else if (4==val) {
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_4').show();
        $('.device_type_syn').show();
        $('.device_type_d2').hide();
        $('.dahua').hide();
    }
    else if (5==val) {
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').show();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_syn').show();
        $('.device_type_5').show();
        $('.device_type_d2').hide();
        $('.dahua').hide();
    }
    else if(6==val){
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_show_7').hide();
        $('.multiple_floor_info_tip').show();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_syn').show();
        $('.device_type_5').show();
        $('.device_type_d2').hide();
        $('.dahua').hide();
    }
    else if(7==val) {
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.device_show_7, .device_type_7').show();
        $('.dahua').hide();
    }
    else if(22==val) {
        $('.device_type_7').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_7').hide();
        $('.device_type_3').hide();
        $('.device_type_d2').show();
        $('.dahua').hide();
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
        $('.dahua').hide();
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
        $('.dahua').hide();
    }
    else if(61==val){
        $('.device_type_7').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_7').hide();
        $('.device_type_d2').hide();
        $('.device_hide_212').show();
        $('.A185_').show();
        $('.dahua').hide();
    }
    else if(88==val){
        $('.device_type_7').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('#choose_village').append('<span class="dahua" style="color: #1686ef;margin-top: 5px;">如何没有位置信息，需要去数据中心》楼栋管理中添加</span>');
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_7').hide();
        $('.device_type_d2').hide();
        $('.device_type_1').hide();
    }
    else if (29==val){
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.device_type_syn').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_4').show();
        $('.device_show_29').show();
        $('.device_type_d2').hide();
    }
    else if (26==val){
        $('.device_type_1').hide();
        $('.other_hide').hide();
        $('.device_type_5').hide();
        $('.device_show_7').hide();
        $('.device_type_syn').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_show_26').show();
        $('.device_hide_26').hide();
        $('.device_type_d2').hide();
        $.post("{pigcms{:U('get_dnake_device')}",function(data){
            if(data['status'] == 0){
                alert(data.msg);
            }else{
                var list = data['list'];
                var shtml = '<option>请选择设备ID</option>';
                if(list){
                    for(var i in list){
                        shtml += '<option  data-ip="'+list[i]['device_ip']+'"  value="'+list[i]['device_sn']+'">'+list[i]['device_ip']+' ('+list[i]['device_sn']+')</option>'
                    }
                }
                // console.log(shtml);
                $('.device_sn_z6').html(shtml)
            }
        },'json');
    }
    else if(30==val) {
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.device_hide_26').show();
        $('.device_show_26').hide();
        $('.device_show_30').show();
    } 
    else if(31==val){
        $('.device_type_5').hide();
        $('.multiple_floor_info_tip').hide();
        $('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
        $('.device_type_d2').hide();
        $('.device_hide_26').show();
        $('.device_show_26').hide();
        $('.device_type_31').show();
    }
    var device_brand_type =$(this).find("option:selected").attr('data-key');
    // console.log('device_brand_type111',device_brand_type);
    $('#device_brand_type').val(device_brand_type);

});
var url = "{pigcms{:U('User/getType')}";
$('#brand_type').change(function(){
    var brand_id =$(this).find("option:selected").attr('data-key');
    // console.log('brand_id', brand_id);
    // console.log('val', $(this).val());
    var brand_key = $(this).val();
    console.log('brand_key', brand_key);
    $('.thirdProtocolBox').hide();
    if (brand_key) {
        $('#'+brand_key + '_thirdProtocol').val('');
        $('.'+brand_key + '_box').show();
        if ('brand_haikang' != brand_key) {
            $('.third_isup_version_box').hide();
            $('.third_protocol_16').hide();
            $('.third_protocol_hide_16').show();
        }
        $('#'+brand_key + '_thirdProtocol').change(function () {
            var thirdProtocol = $(this).val();
            console.log('thirdProtocol', thirdProtocol);
            $('#thirdProtocol').val(thirdProtocol);
            $('.third_isup_version_box').hide();
            if (thirdProtocol == 16 || thirdProtocol == 17) {
                $('.third_protocol_16').show();
                $('.third_protocol_hide_16').hide();
            } else {
                $('.third_protocol_16').hide();
                $('.third_protocol_hide_16').show();
            }
        })
    }
    $.post(url,{'brand_id':brand_id},function(data){
        if(data['status'] == 0){
            alert(data.msg);
        }else{
            var list = data['list'];
            var shtml = '<option>请选择设备类型</option>';
            if(list){
                for(var i in list){
                    shtml += '<option   data-title="'+list[i]['sub_title']+'" data-key="'+list[i]['key']+'" value="'+list[i]['device_type']+'">'+list[i]['sub_title']+'</option>'
                }
            }
            // console.log(shtml);
            $('#device_type').html(shtml)
        }
    },'json');
})
</script>
<include file="Public:footer"/>