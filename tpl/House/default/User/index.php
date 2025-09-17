<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">业主管理</a>
            </li>
            <li class="active">业主列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="alert alert-info" style="margin-bottom:10px;">
                注意：<br>
                物业编号添加快速搜索功能，匹配规则为：①【<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋'; ?>名称】-【<?php echo D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元'; ?>名称】-【楼层名称】-【<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>名称】；②【<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋';?>名称】-【<?php echo D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元';?>名称】-【<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>名称】；③【<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋';?>名称】-【<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>名称】<br>
                例：12-2-5-503 表示12<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'栋';?>2<?php echo D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元';?>5层<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>名为503的<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>；12-5-503 表示12<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'栋';?>5<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元').(D('House_village')->getHouseAlias('room_name',$village_id)?:'房间');?>名为503的<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>；12-503 表示12<?php echo (D('House_village')->getHouseAlias('single_name',$village_id)?:'栋').(D('House_village')->getHouseAlias('room_name',$village_id)?:'房间');?>名为503的<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>
                
                <if condition="$haveXiHanFaceDoor">
                    <p style="color: red;font-weight: bold;">列表中的《IC卡管理》里的卡号设置仅对西涵门禁设备有效</p>
               </if>
            </div>
                    
        	<div class="form-group" style="border:1px solid #c5d0dc;">
				<form method="get" action="{pigcms{:U('User/index')}" id="find-form">
                    <input type="hidden" name="c" value="User"/>
                    <input type="hidden" name="a" value="index"/>
                    <?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>:
					<select class=""  name="single_id" id="single" style="height:42px;margin:10px;">
						<option value="">请选择<?php echo D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋';?></option>
						<volist name='single_list' id='single_data'>
							<option <if condition="$_GET['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
						</volist>
					</select>
					<select class=""  name="floor_id" id="floor" style="height:42px;margin:10px;">
						<option <if condition="$_GET['floor_id'] eq '0'">selected="selected"</if> value="0">请选择<?php echo D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元';?></option>
					</select>
					<select class=""  name="layer_id" id="layer" style="height:42px;margin:10px;">
						<option <if condition="$_GET['layer_id'] eq '0'">selected="selected"</if> value="0">请选择楼层</option>
					</select>
					<select class=""  name="vacancy_id" id="vacancy" style="height:42px;margin:10px;">
						<option <if condition="$_GET['vacancy_id'] eq '0'">selected="selected"</if> value="0">请选择<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?></option>
					</select>
					&nbsp;&nbsp;

                    <if condition="$chinapopinOpen">
                        <span style="margin-left:10px;">数据来源：</span>
                        <select name="third_type" id="third_type"  style="height:42px;margin:10px;">
                            <option value="all" <if condition="$_GET['third_type'] eq 'all'">selected="selected"</if>>全部</option>
                            <option value="house" <if condition="$_GET['third_type'] eq 'house'">selected="selected"</if>>社区注册</option>
                            <option value="ezc_chinapopin" <if condition="$_GET['third_type'] eq 'ezc_chinapopin'">selected="selected"</if>>平安小区接口</option>
                            <option value="dopu_cloudintercom" <if condition="$_GET['third_type'] eq 'dopu_cloudintercom'">selected="selected"</if>>朵普接口</option>
                        </select>
                    </if>
                    
					<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;margin:10px;width: 180px;">
                        <option value="2" <if condition="$find_type eq 2">selected="selected"</if>>姓名</option>
						<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>物业编号 / 用户编号</option>
						<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>手机号</option>
						<if condition="$now_house['write_iccard']">
							<option value="5" <if condition="$find_type eq 5">selected="selected"</if>>门禁卡编号</option>
							<option value="6" <if condition="$find_type eq 6">selected="selected"</if>>IC卡号</option>
						</if>
					</select>
					<input value="{pigcms{$find_value}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;margin:10px;"/>
					&nbsp;&nbsp;
					<br/>
					<span style="margin-left:10px;">是否为平台用户：</span>
					<select name="is_platform" id="is_platform"  style="height:42px;margin:10px;">
						<option value="0" <if condition="$_GET['is_platform'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['is_platform'] eq 1">selected="selected"</if>>是</option>
						<option value="2" <if condition="$_GET['is_platform'] eq 2">selected="selected"</if>>否</option>
					</select>
                    <span style="margin-left:10px;">是否为真实业主：</span>
                    <select name="is_real" id="is_real"  style="height:42px;margin:10px;">
                        <option value="0" <if condition="$_GET['is_real'] eq -1">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['is_real'] eq 1">selected="selected"</if>>是</option>
                        <option value="2" <if condition="$_GET['is_real'] eq 2">selected="selected"</if>>否</option>
                    </select>
					&nbsp;&nbsp;
					<span style="margin-left:20px;">人脸上传状态：</span>
					<select name="face_status" id="face_status"  style="height:42px;margin:10px;">
						<option value="0" <if condition="$_GET['face_status'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['face_status'] eq 1">selected="selected"</if>>同步成功</option>
                        <option value="-1" <if condition="$_GET['face_status'] eq -1">selected="selected"</if>>未上传和同步中</option>
						<option value="2" <if condition="$_GET['face_status'] eq 2">selected="selected"</if>>同步失败</option>
					</select>
					&nbsp;&nbsp;
					<span style="margin-left:20px;">是否绑定微信：</span>
					<select name="is_bind_weixin" id="is_bind_weixin"  style="height:42px;margin:10px;width: 100px;">
						<option value="0" <if condition="$_GET['is_bind_weixin'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['is_bind_weixin'] eq 1">selected="selected"</if>>是</option>
                        <option value="2" <if condition="$_GET['is_bind_weixin'] eq 2">selected="selected"</if>>否</option>
					</select>
                    &nbsp;&nbsp;
					<span style="margin-left:20px;">注册来源：</span>
					<select name="source_comefrom" id="source_comefrom"  style="height:42px;margin:10px;width: 150px;">
						<option value="0" <if condition="$_GET['source_comefrom'] eq 0">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['source_comefrom'] eq 1">selected="selected"</if>>公众号</option>
                        <option value="2" <if condition="$_GET['source_comefrom'] eq 2">selected="selected"</if>>小程序</option>
                        <option value="3" <if condition="$_GET['source_comefrom'] eq 3">selected="selected"</if>><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区');?>自动注册</option>
                        <option value="5" <if condition="$_GET['source_comefrom'] eq 5">selected="selected"</if>>H5</option>
                        <option value="4" <if condition="$_GET['source_comefrom'] eq 4">selected="selected"</if>>其他</option>
					</select>
					&nbsp;&nbsp;
                    <!--
					<br/>
					<span style="margin-left:10px;">缴费时间筛选：</span>
					<input type="text" name="property_endtime_start" class="input-text" value="{pigcms{$property_endtime_start}"  style="height:40px;margin:10px;" onfocus="WdatePicker({isShowClear:false, minDate: '1900-01-01', readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
					&nbsp;—&nbsp;
					<input type="text" name="property_endtime_end" class="input-text" value="{pigcms{$property_endtime_end}"  style="height:40px;margin:10px;" onfocus="WdatePicker({isShowClear:false, minDate: '1900-01-01', readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
                    -->
					<input style="margin:10px;" class="btn btn-success" type="submit" id="find_submit" value="查找业主" />&nbsp;
					<a style="margin:10px;"  class="btn btn-success" onclick="location.href='{pigcms{:U('User/index')}'">重置</a>
				</form>
			</div>
            <if condition="!$base_third_config && !$base_third_config['third_id']">
                <button class="btn btn-success" onclick="addUser()" >添加业主</button>&nbsp;
                <button class="btn btn-success" onclick="importUser()" >导入业主</button>&nbsp;
                <else/>
                <button class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">添加业主</button>&nbsp;
                <button class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">导入业主</button>&nbsp;
            </if>
            <if condition="$new_charge_time neq 1">
            <button class="btn btn-success" onclick="importUserDetail()" <if condition="!in_array(117,$house_session['menus'])">disabled="disabled"</if>>导入业主每月帐单明细</button>&nbsp;
            </if>
                <!-- <button class="btn btn-success" onclick="send_property()" <if condition="!in_array(100,$house_session['menus'])">disabled="disabled"</if>>群发微信消息</button>&nbsp; -->
			<button class="btn btn-success" onclick="location.href='{pigcms{:U('user_data')}'" <if condition="!in_array(99,$house_session['menus'])">disabled="disabled"</if>>数据统计</button>&nbsp;
            <if condition="$new_charge_time neq 1">
            <button class="btn btn-success" onclick="payment_add()" <if condition="!in_array(97,$house_session['menus'])">disabled="disabled"</if>>批量添加缴费项</button>&nbsp;
			<button class="btn btn-success" onclick="property_time_edit()" <if condition="!in_array(276,$house_session['menus'])">disabled="disabled"</if>>批量修改物业时间</button>
			</if>
			<if condition="in_array(98,$house_session['menus'])">
<!--                <a onclick="location.href='{pigcms{:U('user_export',$_GET)}'" class="btn btn-success">EXCEL导出</a>-->
                <a class="userExport btn btn-success">EXCEL导出</a>

                <button class="exportUserDataForLouYu btn btn-success">导出楼宇小区业主格式数据</button>
            <else/>
            <button class="btn btn-success disabled" disabled="disabled">EXCEL导出</button>

                
			</if>


            <if condition="$is_device">
                <button class="btn btn-success" onclick="sync_user_data()">同步钥匙</button>
            </if>

            <if condition="$new_charge_time">
                <button class="btn btn-success" style="margin-left: 7px" onclick="property_service_time_audit()">物业服务时间审核</button>
            </if>
            <if condition="$show_sky_cockpit_entrance eq 1">
                <button class="btn btn-success" style="margin-left: 7px" onclick="bind_protocol_set()">协议许可</button>
            </if>
            <if condition="isset($hik_info_vision_i_community) AND $hik_info_vision_i_community eq 1">
                <button class="btn btn-success" style="margin-left: 7px" onclick="sycBindUserToHikCommunity()">将人员数据同步到海康智慧社区管理平台</button>
            </if>
            
			<style type="text/css">
				.ace-file-input a {display:none;}
				.div-intro{ float:right; margin-top:20px}
				.div-intro-detail{width:10px; height:10px; background-color:red; float:left; margin-top:5px}
				.div-intro span{ float:left; margin-left:5px;}
			</style>
			<div class="div-intro">
				<div class="div-intro-detail"></div>
				<span>尚不是平台用户（注：将无法使用<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区';?>服务）</span><br />
				<div class="div-intro-detail" style="background:orange"></div>
				<span>尚未绑定微信</span>
			</div>

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>
                                	<th style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                    <if condition="$config['householdIdShow'] eq 1">
                                        <th width="3%">ID</th>
                                    </if>
                                    <th width="8%">物业编号</th>
                                    <th width="5%">用户编号</th>
                                    <th width="5%">姓名</th>
                                    <th width="6%"><if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if></th>
                                    <th width="8%">手机号</th>
                                    <if condition="$chinapopinOpen">
                                        <th width="6%">数据来源</th>
                                    </if>
                                    <th width="10%">个人证件信息</th>
                                    <th width="10%">住址</th>
                                    <th width="8%">人脸上传</th>
                                    <if condition="$haveXiHanFaceDoor">
                                        <th width="5%" title="仅支持西涵门禁">IC卡管理</th>
                                    </if>
                                    <if condition="$fingerprintJudgeConfig">
                                        <th width="5%">指纹</th>
                                    </if>
                                    <th width="8%" title="服务时间来源于对应<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间';?>">缴费时间（开始 - 结束）</th>
                                    <th width="6%">申请时间</th>
                                    <th width="6%">审核通过时间</th>
                                    <th width="3%">停车位</th>
                                    <th width="5%">房子大小</th>
                                    <if condition="$now_house['write_iccard']">
										<th width="8%">门禁卡编号</th>
									</if>
                                    <th class="button-column" width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$user_list">
                                    <volist name="user_list['user_list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">

                                            <td align="center"><input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}" onclick="return Dcheckbox($(this));"></td>
                                            <if condition="$config['householdIdShow'] eq 1">
                                                <td width="5%">{pigcms{$vo['pigcms_id']}</td>
                                            </if>
                                            <td>
                                                <div class="tagDiv">{pigcms{$vo.usernum}</div>
                                            </td>
                                            <td>
                                                  <div class="tagDiv">{pigcms{$vo['bind_number']}</div>
                                            </td>

                                            <td>
                                            	<if condition="$vo['name']">
                                            	<if condition='!$vo["uid"]'>
                                            		<div class="tagDiv" style="color:red">{pigcms{$vo.name}</div>
                                            	<elseif  condition='!$vo["openid"] AND !$vo["wxapp_openid"]'/>
                                            		<div class="tagDiv" style="color:orange">{pigcms{$vo.name}</div>
                                            	<else/>
                                            		{pigcms{$vo.name}
                                            	</if>
                                            	<else/>--</if>

											<if condition='$vo["bind_list"] AND 1==2'>
												<a class="bind_info red" href="{pigcms{:U('bind_list',array('pigcms_id'=>$vo['pigcms_id']))}">他的<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if>&nbsp;>&nbsp;</a>
											</if>

											</td>
                                            <td>

												<div class="tagDiv">

                                                <if condition="1 OR in_array(468,$house_session['menus'])">
												<a class="bind_info red" href="{pigcms{:U('bind_list',array('pigcms_id'=>$vo['pigcms_id']))}">他的<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if></a>
												<else/>
												他的<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if>(无权限查看)
												</if>


												<if condition="$vo.bind_unverify_num gt 0"><br>有{pigcms{$vo.bind_unverify_num}个待审核业主</if>
												</div>
											</td>
                                            <td><div class="tagDiv"><if condition="$vo['phone']">{pigcms{$vo.phone}<else/>--</if></div></td>

                                            <if condition="$chinapopinOpen">
                                                <th><div style="background-color: {pigcms{$vo.third_type_color};padding: 10px;border-radius: 10px;text-align: center;">{pigcms{$vo.third_type}</div></th>
                                            </if>
                                            <td>
                                                <div class="tagDiv">
                                                    身份证号：{pigcms{$vo['id_card']|default='暂无'}<br/>
                                                    IC卡号：{pigcms{$vo['ic_card']|default='暂无'}
                                                </div>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.address}</div></td>
                                            <td>
												<div class="tagDiv">
                                                    <if condition="$vo['faceTxt']">
                                                        <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">{pigcms{$vo['faceTxt']}</a>
                                                    <elseif condition="$vo['is_bind_face']" />
														<if condition="$vo['face_img_status'] eq 2">
															<a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">同步错误</a>
														<else/>
															<a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">已上传</a>
														</if>
													<else/>
                                                        <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">未上传</a>
                                                    </if>
                                                </div>
                                            </td>
                                            <if condition="$haveXiHanFaceDoor">
                                                <td>
                                                <a style="width: 60px;border-radius: 5px" class="label label-sm label-info" title="点击查看" onclick="userBindIcCard('{pigcms{$vo['pigcms_id']}','{pigcms{$vo.name}','{pigcms{$vo.phone}')">点击查看</a>
                                                </td>
                                            </if>
                                            <if condition="$fingerprintJudgeConfig">
                                                <td>
                                                    <a style="color: #3a87ad!important;" href="javascript:void(0);" onclick="lookFingerprint({pigcms{$vo['pigcms_id']})">查看</a>
                                                </td>
                                            </if>

											<td>
												<div><if condition="$vo['property_starttime'] gt 1">{pigcms{$vo.property_starttime|date="Y-m-d",###}<else/>还未设置</if> -
												<if condition="$vo['property_endtime'] gt 1">{pigcms{$vo.property_endtime|date="Y-m-d",###}<else/>还未设置</if></div>
											</td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.apply_time_txt}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.adopt_time_txt}</div></td>

                                            <td><div class="shopNameDiv"><if condition="$vo['bind_position']">有<else />无</if></div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.housesize} ㎡</div></td>
                                            <if condition="$now_house['write_iccard']">
												<td><div class="shopNameDiv">{pigcms{$vo.card_no}</div></td>
											</if>
                                            <td class="button-column">

                                                <a style="width: 60px;" class="label label-sm label-info" title="业主详情" onclick="userInfo('{pigcms{$vo['pigcms_id']}','{pigcms{$vo['usernum']}')">业主详情</a>&nbsp;
                                                <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                    <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="redirectToUri(this)" data-href="{pigcms{:U('User/edit',array('pigcms_id'=>$vo['pigcms_id'],'usernum'=>$vo['usernum'],'page'=>$_GET['page']))}">编辑</a>&nbsp;
                                                </if>

                                                <if condition="$new_charge_time neq 1">
                                                <a href="javascript:void(0);" style="width: 60px;" class="label label-info detail" pid="{pigcms{$vo.pigcms_id}" usn="{pigcms{$vo.usernum}">未缴明细</a>&nbsp;
                                                <if condition="in_array(120,$house_session['menus'])">
													<a style="width: 60px;" class="label label-sm label-info" title="缴费明细" href="{pigcms{:U('User/orders',array('bind_id'=>$vo['pigcms_id']))}">已缴明细</a>
                                           		</if>
                                                </if>
                                                <if condition="!$base_third_config && !$base_third_config['third_id']&&in_array(94,$house_session['menus'])">
												<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条信息？')){location.href=\'{pigcms{:U('User/user_delete',array('pigcms_id'=>$vo['pigcms_id'],'usernum'=>$vo['usernum']))}\'}">删除</a>
                                              	</if>

                                                <if condition="in_array(95,$house_session['menus'])">
												<if condition="$now_house['write_iccard']">&nbsp; <a style="width: 60px;" class="label label-sm label-info" title="写卡" onclick="WriteSector('{pigcms{$door_sector}','{pigcms{$door_pwd}','{pigcms{$vo.door_str}','{pigcms{$vo.pigcms_id}')" href="javascript:void(0);">写卡</a></if>
                                              	</if>
                                                <if condition="$new_charge_time">&nbsp;
                                                    <a style="width: 80px;" class="label label-sm label-info" title="修改服务时间" onclick="update_service_time('{pigcms{$vo.pigcms_id}')">修改服务时间</a>
                                                </if>
                                                <if condition="$new_charge_time AND $vo.is_service_time_check eq 1">
                                                    <a style="width: 60px;margin-left: 5px" class="label label-sm label-info" title="审核" onclick="audit_service('{pigcms{$vo.pigcms_id}')" href="javascript:void(0);">审核</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                    <if condition="!$base_third_config && !$base_third_config['third_id']&&in_array(269,$house_session['menus'])">
                                    <tr>
                                    	<td colspan="14">&nbsp;&nbsp;&nbsp;<button class=" btn delete_class">删除选中</button></td>
                                    </tr>
                                	</if>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何业主。</td></tr>
                                </if>
                            </tbody>

                        </table>
                        <div style="float: right; padding-left: 5px; ">
	                        <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
	                        <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
	                        <script>

                                function redirectToUri(Obj){
                                    var index = layer.load(0, {time: 10*1000}); //又换了种风格，并且设定最长等待10秒
                                    window.location.href=$(Obj).data('href');
                                }

	                        	function checkPage(){
	                        		var page = $("#page").val();
	                        		if(page == ''){
	                        			return false;
	                        		}
	                        		location.href = "{pigcms{:U('index')}"+"&page="+page;
	                        	}
	                        </script>
                        </div>
                        {pigcms{$user_list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	var static_path = "{pigcms{$static_path}";
</script>
<script>
    var loadExport = false;

    $('.exportUserDataForLouYu').click(function () {
        if (loadExport) {
            layer.msg('数据导出中，请耐心等待~!',{icon: 0,time:0,shade: [0.5]});
            return false
        }
        var _this = $(this);
        _this.html('导出中...');
        _this.removeClass('btn-success');
 
        layer.msg('正在为您导出数据，如果数据多时间会较长，请耐心等待!导出过程中请勿刷新页面！',{icon: 0,time:0,shade: [0.5]});
        // 下载使用的方法
        getDownload = function(url) {
            $.get(url,{},function(data){
                console.log('..............',data)
                layer.closeAll();
                loadExport = false;
                _this.html('导出楼宇小区业主格式数据');
                _this.addClass('btn-success');
                let downloadPath = data.url
                var downloadLink = document.createElement('a')
                downloadLink.style.display = 'none' // 使其隐藏
                downloadLink.href = downloadPath
                downloadLink.download = ''
                downloadLink.click()
                downloadLink.remove()
            },'json')
        };
        getDownload('{pigcms{:U('exportUserDataForLouYu',$_GET)}');
        loadExport = true;
       
    });
    
    $('.userExport').click(function () {
        if (loadExport) {
            layer.msg('当前导出中，请稍后再试!',{icon: 0,time:0,shade: [0.5]});
            return false
        }
        var _this = $(this);
        _this.html('导出中...');
        _this.removeClass('btn-success');

        //下载完成后触发，用来关闭提示框
        disload = function() {
            layer.closeAll();
            loadExport = false;
            _this.html('EXCEL导出');
            _this.addClass('btn-success');
        }
        layer.msg('正在为您导出数据，如果数据多时间会较长，请耐心等待!',{icon: 0,time:0,shade: [0.5]});
        // 下载使用的方法
        getDownload = function(url) {
            $.get(url,{},function(data){
                console.log('..............',data)
                layer.closeAll();
                loadExport = false;
                _this.html('导出数据');
                _this.addClass('btn-success');
                let downloadPath = data.url
                var downloadLink = document.createElement('a')
                downloadLink.style.display = 'none' // 使其隐藏
                downloadLink.href = downloadPath
                downloadLink.download = ''
                downloadLink.click()
                downloadLink.remove()
            },'json')
        };
        // getDownload = function(url) {
        //     var xhr = new XMLHttpRequest();
        //     if(typeof(xhr) !="undefined") {
        //         // 现在使用的方法 可在下载完成的时候关闭load弹窗
        //         xhr.open('GET', url, true);    // 也可用POST方式
        //         xhr.responseType = "blob";
        //         xhr.onload = function () {
        //             if (this.status === 200) {
        //                 var blob = this.response;
        //                 var headerName = xhr.getResponseHeader("Content-disposition");
        //                 headerName=decodeURIComponent(headerName);
        //                 console.log(headerName);
        //                 if (navigator.msSaveBlob == null) {
        //                     var a = document.createElement('a');
        //                     a.download =headerName.substring(20);
        //                     a.href = URL.createObjectURL(blob);
        //                     $("body").append(a);    // 修复firefox中无法触发click
        //                     a.click();
        //                     URL.revokeObjectURL(a.href);
        //                     $(a).remove();
        //                 } else {
        //                     navigator.msSaveBlob(blob,headerName.substring(20));
        //                 }
        //             }
        //             disload();
        //         };
        //         xhr.send();
        //     }
        //     else {
        //         window.location.href = url;
        //     }
        // };

        getDownload('{pigcms{:U('user_export',$_GET)}');
        loadExport = true;
        //window.location.href='{pigcms{:U('user_export',$_GET)}';
        //loadExport = false;
        // _this.html('EXCEL导出');
        //_this.addClass('btn-success');
    });


    function bounced(id) {
        var index = layer.open({
            type: 2,
            area: ['750px', '550px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '人脸上传照片',
            content: "/shequ.php?g=House&c=User&a=faces&id="+id
        });
        layer.style(index, {
            top: '20px'
        });
    }

    function userInfo(pigcms_id,usernum) {
        var url = parent.location.href.split("#")[0]+'#/village/village.chatSidebar/userInfo?pigcms_id='+pigcms_id+'&usernum='+usernum
        var index = layer.open({
            type: 2,
            area: ['92%', '750px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '业主详情',
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
    }
   function userBindIcCard(pigcms_id,name='',phone=''){
        var url = '{pigcms{:U('user_bind_iccard')}&bind_pigcms_id='+pigcms_id;
        var xtitle='IC卡管理（姓名：'+name+'，手机号：'+phone+'）';
        var index = layer.open({
            type: 2,
            area: ['60%', '620px'],
            fixed: true, //不固定
            // maxmin: true,
            title: xtitle,
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
   }
    function lookFingerprint(pigcms_id) {
        var url = parent.location.href.split("#")[0]+'#/village/village.device/userFingerprintInfo?pigcms_id='+pigcms_id
        var index = layer.open({
            type: 2,
            area: ['58%', '500px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '查看指纹信息',
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
    }
    
</script>
	<if condition="$now_house['write_iccard']">
		<script language="javascript" src="http://127.0.0.1:8008/YOWOCloudRFIDReader.js"></script>
		<script language="javascript" src="{pigcms{$static_path}/js/YOWOReaderBase.js"></script>

		<script language="javascript" type="text/javascript">
		    rfidreader.onResult(function(resultdata) {
		        switch(resultdata.FunctionID){
		            case 8:
		                document.getElementById("CardNo").value = resultdata.CardNo;
		                if(resultdata.Result>0) {
		                    alert("同步成功！");
		                    var cardNoUrl = "{pigcms{:U('User/card_no_add')}";
		                    var card_no = resultdata.CardNo;
		                    var pigcms_id = $("#pigcms_id").val();
			                $.post(cardNoUrl,{card_no:card_no,pigcms_id:pigcms_id},function(data){

			                },'json')
		                } else {
		                    alert("同步失败，错误：" + GetErrStr(resultdata.Result));
		                }
		                break;
		        }
		    });
		    function WriteSector(SectorID,Key,Data,pigcms_id){
		        rfidreader.KeyMode=0;
		        rfidreader.KeyStringMode=0;
		        rfidreader.KeyString=Key;
		        rfidreader.Repeat=0
		        rfidreader.M1WriteSector(SectorID, Data,0);
		        $("#pigcms_id").val(pigcms_id);
		    }
		</script>
	</if>


<input name="CardNo" type="hidden" id="CardNo" size="10" maxlength="8" readonly>
<input name="pigcms_id" type="hidden" id="pigcms_id">
<style>
	.modal-content{
		width:400px;
	}
	.modal-body{
		text-align:center;
		padding:20px 15px;
	}
</style>
<!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="myModalLabel">
					警告提示
				</h4>
			</div>
			<div class="modal-body">
				删除业主后，业主下的<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if>会被一起删除，绑定的<?php echo D('House_village')->getHouseAlias('room_name',$village_id)?:'房间'; ?>会被释放，确定删除？
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary class-delete">
					确定
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<div class="modal fade" id="myModal_alert" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="myModalLabel">提示信息</h4>
			</div>
			<div class="modal-body">
				请先选择要执行的业主！
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary class-tip">确定</button>
			</div>
		</div>
	</div>
</div>
<div id="fee_detail" class="grid-view" style="display: none;">
    <table class="table table-striped table-bordered table-hover">
        <tbody>
            <tr class="under_line">
                <th style="width: 190px;">固定缴费项欠费：</th>
                <td style="width: 190px;">&nbsp;</td>
            </tr>
            <tr style="color: #666;">
                <th style="width: 190px;">水费：</th>
                <td style="width: 190px;"><label id="water_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr style="color: #666;">
                <th style="width: 190px;">电费：</th>
                <td style="width: 190px;"><label id="electric_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" style="color: #666;">
                <th style="width: 190px;">燃气费：</th>
                <td style="width: 190px;"><label id="gas_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" style="color: #666;">
                <th style="width: 190px;">停车费：</th>
                <td style="width: 190px;"><label id="park_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" style="color: #666;">
                <th style="width: 190px;">物业费：</th>
                <td style="width: 190px;"><label id="property_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" id="cunstom_money_tr">
                <th style="width: 190px;">自定义缴费项欠费：</th>
                <td style="width: 190px;"><label id="cunstom_money"></label>&nbsp;</td>
            </tr>
            <tr class="under_line" >
                <th style="width: 190px;">总合计：</th>
                <td style="width: 190px;"><label id="total_money"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <if condition="in_array(118,$house_session['menus'])">
            <tr class="under_line" >
                <td colspan="2" style="width: 190px;text-align: center;">
                	<a  class="label label-sm label-info" title="欠费明细" href="javascript:pay_detail();">每月账单欠费明细</a>
                </td>

            </tr>
        	</if>
        </tbody>
    </table>
    <input type="hidden" id="now_id" value="">
    <input type="hidden" id="usn" value="">
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" language="javascript">
	var cat_id = $('#single').val();
	get_son_category(cat_id);
	var floor_id = "{pigcms{$_GET['floor_id']}";
	console.log("============",floor_id);
	// get_new_layer(floor_id);

	var layer_id = "{pigcms{$_GET['layer_id']}";
	console.log("============",layer_id);
	// get_vacancy(layer_id)




	$('#single').change(function(){

		var cat_id = $('#single').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
		get_son_category(cat_id);

	});

	function get_son_category(cat_id){
		var url = "{pigcms{:U('Service/ajax_get_floor')}";
		$.post(url,{'cat_id':cat_id,'type':1},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择'+'<?php echo (D("House_village")->getHouseAlias("floor_name",$village_id)?:"单元");?>'+'</option>';
			var htmls = '<option value="0">请选择楼层</option>';
            var vacancy = '<option value="0">请选择'+'<?php echo (D("House_village")->getHouseAlias("room_name",$village_id)?:"房间");?>'+'</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){

					if (cat_list[i]['floor_id'] == "{pigcms{$_GET['floor_id']}" ) {
						console.log(cat_list[i]['floor_id'])
						console.log("{pigcms{$_GET['floor_id']}")

						html +='<option value="'+ cat_list[i]['floor_id'] +'" selected=selected>' + cat_list[i]['floor_name'] + '</option>';
					}else{
						html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
					}
				}
				$('#floor').html(html);
				if (floor_id && floor_id>0) {
                    get_new_layer(floor_id);
				} else {
                    $('#layer').html(htmls);
                    $('#vacancy').html(vacancy);
				}
			} else {
				$("#floor").html(html);
				$('#layer').html(htmls);
                $('#vacancy').html(vacancy);
			}
           
		},'json');
	}


	$('#floor').change(function(){

		var cat_id = $('#floor').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
//		get_vacancy(cat_id);
		get_new_layer(cat_id);

	});

	function get_vacancy(cat_id){
		var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
		$.post(url,{'cat_id':cat_id,'type':1},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择'+'<?php echo D("House_village")->getHouseAlias("room_name",$village_id)?:"房间";?>'+'</option>';
			if(result['status'] == 1){
				var cat_lists = result.cat_list;
				for(var j in cat_lists){

					if (cat_lists[j]['pigcms_id'] == "{pigcms{$_GET['vacancy_id']}") {
						html +='<option value="'+ cat_lists[j]['pigcms_id'] +'" selected=selected>' + cat_lists[j]['room'] + '</option>';
					}else{
						html +='<option value="'+ cat_lists[j]['pigcms_id'] +'">' + cat_lists[j]['room'] + '</option>';
					}
				}
				$('#vacancy').html(html);
			} else {
				$("#vacancy").html(html);
			}
		},'json');
	}

	$('#layer').change(function(){

		var cat_id = $('#layer').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
		get_vacancy(cat_id);

	});

	function get_new_layer(cat_id){
		var url = "{pigcms{:U('Service/get_new_layer')}";
		$.post(url,{'cat_id':cat_id},function(result){
			console.log("result")
			console.log(result)
			var shtml = '<option value="0">请选择'+'<?php echo D("House_village")->getHouseAlias("room_name",$village_id)?:"房间";?>'+'</option>';
			var html = '<option value="0">请选择楼层</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					if (cat_list[i]['id'] == "{pigcms{$_GET['layer_id']}" ) {
						html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['layer_name'] + '</option>';
					}else{
						html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
					}
				}
				$('#layer').html(html);
                if (layer_id && layer_id>0) {
                    get_vacancy(layer_id)
                } else {
                    $('#vacancy').html(shtml);
                }
			} else {
				$("#layer").html(html);
				$('#vacancy').html(shtml);
			}
		},'json');
	}

$(".checkbox_all").on('click',function(){

	if($(this).is(':checked')){
		$("input[name='delCheckbox[]']").prop("checked",true);
	}else{
		$("input[name='delCheckbox[]']").prop("checked",false);
	}

});

function pay_detail(){
	var usernum = $("#usn").val();
	var url = "{pigcms{:U('User/pay_detail')}";
	window.location.href = url + '&usernum='+usernum;
}


$('.detail').click(function(){
        //获得选中费用
        var pigcms_id = $(this).attr("pid");
        var usernum = $(this).attr("usn");
        $("#now_id").val(pigcms_id);
        $("#usn").val(usernum);
        $.post("{pigcms{:U('Cashier/ajax_cashier_unpaid_detail')}",{pigcms_id:pigcms_id},function(result){
           if (result.status>0) {
                alert(result.msg);return false;
           } else if (result.status==0){
                $('#water_price').html(result.data.water_price);
                $('#electric_price').html(result.data.electric_price);
                $('#gas_price').html(result.data.gas_price);
                $('#park_price').html(result.data.park_price);
                $('#property_price').html(result.data.property_price);
                var cunstom_pay_arr = result.data.cunstom_pay_arr;
                console.log('cunstom_pay_arr', cunstom_pay_arr);
                if (cunstom_pay_arr) {
                    var newElement = '';
                    $('.cunstom_money_box').remove();
                    $('#cunstom_money').html('');
                    for(var i in cunstom_pay_arr) {
                        if (cunstom_pay_arr[i]) {
                            newElement += '<tr class="under_line cunstom_money_box" style="color: #666;">';
                            newElement += '<th style="width: 190px;">'+cunstom_pay_arr[i]['payment_name']+'：</th>';
                            newElement += '<td style="width: 190px;"><label id="cunstom_pay_arr_'+cunstom_pay_arr[i]['payment_id']+'">'+cunstom_pay_arr[i]['total_money']+'</label>&nbsp;{pigcms{$config['Currency_txt']}</td>';
                            newElement += '</tr>';
                        }
                    }
                    console.log('newElement', newElement);
                    if (newElement) {
                        $('#cunstom_money_tr').after(newElement);
                    } else {
                        $('#cunstom_money').html('暂无');
                    }
                } else {
                    $('#cunstom_money').html('暂无');
                }

                $('#total_money').html(result.data.total);
           }
        },'json');
        window.art.dialog({
            content: document.getElementById('fee_detail'),
            id: 'handle',
            title:'欠费详情',
            padding: 0,
            width: 450,
            height: 280,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            okVal:'确定',
            cancelVal:'取消',
            left: '50%',
            top: '50px',
            opacity:'0.4',
            ok:function (argument) {
            }
        });
        return false;
    })
function Dcheckbox(e){
	var n=0;
	var len = $("input[name='delCheckbox[]']").length;
	for(var i=0;i<len;i++){
		if($("input[name='delCheckbox[]']")[i].checked) n++;
	}
	if(n==len){
		$(".checkbox_all").prop("checked",true);
	}else{
		$(".checkbox_all").prop("checked",false);
	}

}

$(".class-tip").on('click',function(){
	$('#myModal_alert').modal('hide');
});

$(".delete_class").on('click',function(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		$('#myModal').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}
});

 var addbtn = [
    {
        name:'添加',
        callback:function () {
            var iframe = this.iframe.contentWindow;
            if (iframe.document.body) {
                var submits=iframe.document.getElementById('dosubmit');
                submits.click();
                var that = this;
                setTimeout(function(){
                    that.close();
                    var win = art.dialog.open.origin;//来源页面
                    win.location.reload();
                }, 1000);
                return false;
            }else{
                return false;
            }
        },
        focus:true
    },
    {name:'关闭',
        callback:function () {
           window.location.reload();
        }}
];

function payment_add(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		var length = $("input[name='delCheckbox[]']").length;
		var value="";
		for(var i=0;i<length;i++){
			if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
		}

		var href = "{pigcms{:U('payment_add',array('pigcms_id'=>$info['pigcms_id']))}"+'uid='+value;
		window.art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'查看',
			padding: 0,
			width: 900,
			height: 603,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
            top: '50px',
			opacity:'0.4'
		});
	}
}

function property_time_edit(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		var length = $("input[name='delCheckbox[]']").length;
		var value="";
		for(var i=0;i<length;i++){
			if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
		}

		var href = "{pigcms{:U('property_time_edit')}"+'&idList='+value;
		window.art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'批量修改物业时间',
			padding: 0,
			width: 700,
			height: 500,
			lock: true,
			button: addbtn,
            top: '50px',
		});
	}
}


$('.bind_info').click(function(){

	return false;
});



$(".class-delete").on('click',function(){
	$('#myModal').modal('hide');
	var length = $("input[name='delCheckbox[]']").length;
	var value="";
	for(var i=0;i<length;i++){
		if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;
	}
	value = value.substring(1);
	if(value){
		var village_id = "{pigcms{$village_info.village_id}";
		var url = "{pigcms{:U('User/ajaxDelete')}";
		$.post(url,{'village_id':village_id,'arr_pigcms_id':value},function(data){
			if(data['status']){
				location.reload();
			}else{
				alert(data['msg']);
			}
		},'json')
	}

});

function importUser(){
	window.location.href = "{pigcms{:U('User/user_import')}";
}
function importUserDetail(){
	window.location.href = "{pigcms{:U('User/detail_import')}";
}
function addUser(){
	window.location.href = "{pigcms{:U('User/user_add')}";
}
function send_property(){
	var property_warn_day = "{pigcms{$village_info['property_warn_day']}";
	if(parseInt(property_warn_day) > 0){
		var confirm_txt = "确认群发微信消息（物业费到期提前" + property_warn_day + "天提醒）";
	}else{
		var confirm_txt = "确认群发微信消息（物业费到期提醒）";
	}

	if(confirm(confirm_txt)){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url , {'is_collective':1},function(data){
			// if(data['status']){
				alert(data['msg']);
			// }
		},'json')
	}
}

function send_property_one(pigcms_id , usernum){
	if(confirm('确认发送微信消息？')){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url,{'pigcms_id':pigcms_id,'usernum':usernum},function(data){
			if(data['status']){
				alert(data['msg']);
			}
		},'json')
	}
}

function sync_user_data() {
    layer.open({
        title: '提示'
        ,type:1
        ,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
    });

    $.post('{pigcms{:U("Face_door/sync_user_data")}',{type:'all'},function(data){
        console.log(data)
        layer.closeAll();
        if (data.code==1) {
            that.html('已同步')
        }else{
            layer.alert(data.msg)
        }
    },'json')
    return false;
}

function bind_protocol_set(){
    var url = "{pigcms{:U('User/bind_protocol_set')}";
    layer.open({
        type: 2,
        title:'协议许可设置',
        content: url,
        area: ['1000px', '750px'],
        maxmin: false
    });
}

    function sycBindUserToHikCommunity(){
        
        var tips= '确定要将人员信息同步到海康智慧社区管理平台吗?<div style="color:red;" >只会同步那些房间已对接绑定到海康智慧社区管理平台的人员信息</div><div>如果房间没有对接绑定到海康智慧社区管理平台，请先去编辑房间绑定一个海康智慧社区房间</div>'
        layer.confirm(tips,{
            btn : [ '确定', '取消' ], //按钮
            shade : false
            //不显示遮罩
        },
        function(index) {
            var loading = layer.msg('正在加载', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
            $.ajax({
                url:"{pigcms{:U('hikCommunityUserSyc')}",
                type:"post",
                data:{},
                dataType: "Json",
                success:function(data){
                    if(data.errorCode == 1){
                        layer.msg(data.errorMsg, {icon: 6,time: 5000});
                    } else {
                        layer.msg('同步命令已压入队列，请等待执行。请不要短时间内重复同步操作！', {icon: 1,time: 7000});
                    }
                    layer.close(loading);
                    layer.close(index);
                }
            });

        }, function(index) {
            layer.close(index);

        });
    }
//物业服务时间审核
function property_service_time_audit()
{
    var url = "{pigcms{:U('User/property_service_time_audit')}";
    layer.open({
        type: 2,
        title:'物业服务时间审核',
        content: url,
        area: ['1080px', '650px'],
        maxmin: false
    });
}

//审核物业服务时间
function audit_service(pigcms_id=0) {
    $.post("{pigcms{:U('User/is_audit')}",{},function(result){
        isposting=false;
        if(result.status == 1){
            var url = "{pigcms{:U('User/audit_service')}";
            url = url + '&pigcms_id='+pigcms_id
            layer.open({
                type: 2,
                title:'修改物业服务时间',
                content: url,
                area: ['950px', '350px'],
                maxmin: false
            });
        }else{
            layer.alert(result.info);
            return false;
        }
    })
}
//修改物业服务时间
function update_service_time(pigcms_id=0)
{
    var url = "{pigcms{:U('User/update_service_time')}";
    url = url + '&pigcms_id='+pigcms_id
    layer.open({
        type: 2,
        title:'修改物业服务时间',
        content: url,
        area: ['950px', '350px'],
        maxmin: false
    });
}


$('.bind_info').click(function(){
	window.art.dialog.open($(this).attr('href'),{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'查看',
		padding: 0,
		width: 1100,
		height: 603,
		lock: true,
		resize: false,
		background:'black',
		button: null,
		fixed: false,
		close: null,
		left: '50%',
		top: '50px',
		opacity:'0.4'
	});
	return false;
});
</script>
<include file="Public:footer"/>
