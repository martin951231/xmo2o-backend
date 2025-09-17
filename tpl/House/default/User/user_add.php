<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<if condition="$_GET['from_type'] neq 1">
				<li>
					<i class="ace-icon fa fa-user"></i>
					<a href="{pigcms{:U('User/index')}">业主管理</a>
				</li>
			</if>
			<li class="active">添加业主</li>
		</ul>
	</div>
    
    <!-- 内容头部 -->
    <style>
		.line-span { line-height:34px;}
		.none { display:none}
        .red-left{
            transform: translateX(10px);
            line-height: 32px;
            float: left;
            color: #dd5a43!important;
        }
	</style>
    <div class="page-content">
        <div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本信息</a>
							</li>
                            <if condition="$new_charge_time neq 1">
							<li>
								<a href="#txtstore" data-toggle="tab">相关费用</a>
							</li>
                            </if>
							<li>
								<a href="#txtpwd" data-toggle="tab">业主资料</a>
							</li>
                            <li>
                                <a href="#infotagging" data-toggle="tab">信息标注</a>
                            </li>
                            <li>
                                <a href="#userLabel" data-toggle="tab">用户标签</a>
                            </li>
						</ul>
					</div>
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div class="tab-pane active" id="basicinfo">
								<div class="form-group">
                                    <span class="red-left">*</span>
									<label class="col-sm-1"><label for="user_name">业主名：</label></label>
									<input class="col-sm-2" size="20" name="user_name" id="user_name" type="text" value=""/>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="phone">手机号：</label></label>
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text" value=""/>
									<span class="line-span ">&nbsp;只允许填写一个手机号，如需多个手机号请在下方备注中填写其他备用手机号</span>
									<span class="red line-span none not-phone">&nbsp;&nbsp;此手机号暂时不是平台用户</span>
								</div>
								<div class="form-group">
                                    <if condition="$set_id_code eq 1">
                                            <span class="red-left">*</span>
                                     </if>
									<label class="col-sm-1"><label for="id_card">身份证号：</label></label>
									<input class="col-sm-2" size="20" name="id_card" id="id_card" type="text" value=""/>
									<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="get_idcard" onclick="get_idcard_func()">
										读取身份证信息
									</button>
									<span class="line-span ">&nbsp;填写后该住户可通过身份证进入门禁。需要门禁硬件支持。</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="ic_card">IC卡号：</label></label>
									<input class="col-sm-2" size="20" name="ic_card" id="ic_card" type="text" value=""/>
									<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="rfid_get_card" onclick="rfid_get_card_func()">
										<i class="ace-icon fa fa-check bigger-110"></i>
										RFID读卡已启动
									</button>
                                    <if condition="$readCard_denake eq 1 ">
                                    <a href="JavaScript:;" onclick="readCard()" style="color: #0699e5;text-decoration: underline;">读取卡号</a>
                                    </if>
                                        <span class="line-span ">&nbsp;可不填，填写后该住户可通过IC卡进入门禁。需要门禁硬件支持。</span>
								</div>

                                <if condition="$huizhisq">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="nmv_card">非机动车卡号：</label></label>
                                        <input class="col-sm-2" size="20" maxlength="10" name="nmv_card" id="nmv_card" type="text" onkeyup="nmvCard()" value=""/>
                                        <span class="line-span ">&nbsp;填写后该住户可通过非机动车卡号门禁。需要门禁硬件支持。</span>
                                    </div>
                                </if>

                                <if condition="$huizhisq eq true">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="pile_card_no">物理卡号：</label></label>
                                        <input class="col-sm-2" size="20" maxlength="10"  name="pile_card_no" id="pile_card_no" type="text" value="{pigcms{$info.pile_card_no}"/>
                                        <span class="line-span ">&nbsp;可不填，填写后该住户可通过物理卡号对电动汽车充电支付</span>
                                    </div>
                                </if>
								<if condition="0 && $attribute_list">
									<div class="form-group">
										<label class="col-sm-1"><label for="phone">业主属性：</label></label>
										<select name="attribute">
											<option value="0">请选择业主属性</option>
											<volist name="attribute_list" id="vo">
												<option value="{pigcms{$vo.pigcms_id}">{pigcms{$vo.name}</option>
											</volist>
										</select>
									</div>
								</if>

								<div class="form-group">
                                    <span class="red-left">*</span>
									<label class="col-sm-1"><label for="floor_name">选择<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>：</label></label>

									<select class="col-sm-1"  name="single_id" id="single">
										<option value="">请选择<?php echo (D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋')?></option>
										<volist name='single_list' id='single_data'>
											<option value="{pigcms{$single_data.id}" >{pigcms{$single_data.single_name}</option>
										</volist>
									</select>

									<select class="col-sm-1"  name="floor_id" id="floor">
										<option value="">请选择<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元')?></option>
									</select>
									<select class="col-sm-1"  name="layer_id" id="layer">
										<option value="">请选择楼层</option>
									</select>

									<select class="col-sm-1"  name="vacancy_id" id="vacancy">
										<option value="">请选择<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?></option>
									</select>
                                    &nbsp;&nbsp;&nbsp;<span id="property_starttime_endtime" style="font-weight: bold;display:none;"><?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>的物业服务时间是：<span><span></span>
								</div>
                                <if condition="$new_charge_time eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label>缴费时间</label></label>
                                        <input type="text" name="property_starttime" data-vservice_start_time="" id="str_property_starttime" class="input-text" value=""  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="缴费开始时间"/>
                                        &nbsp;—&nbsp;
                                        <input type="text" name="property_endtime" data-vservice_end_time="" id="str_property_endtime" class="input-text" value=""  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="缴费结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <span class="form_tips">业主缴费时间只能在<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>的物业服务时间范围内，请按照实际缴费时间填写。</span>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1"><label for="housesize">房子平方：</label></label>
									<input readonly class="col-sm-2" size="20" name="housesize" id="housesize" type="text"  value=""/>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="housesize">车辆信息：</label></label>
                                    <input name="car_id" id="car_id" type="text" style="display: none" value=""/>
                                    <span style="width: 80%;display: inline-block;">
                                        <span class="lbl_label" onclick="addCar()" style="padding: 5px 10px;background-color: #6fb3e0;color: #FFFFFF;cursor: pointer;vertical-align: middle;display: inline-block" >添加车辆</span>
                                    <table class="table table-striped table-bordered table-hover car-table"  style="width: 60%;margin-top: 10px">
                                        <thead>
                                        <tr>
                                            <th width="5%">车位</th>
                                            <th width="5%">车牌号</th>
                                            <th width="5%">停车卡号</th>
                                            <th width="5%">车主姓名</th>
                                            <th width="5%">车主手机号</th>
                                            <th width="5%">操作</th>
                                        </tr>
                                        </thead>
                                        <tbody id="carList">

                                        </tbody>
                                    </table>
                                    </span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="memo">备注：</label></label>
									<textarea class="col-sm-2" size="20" name="memo" id="memo"/></textarea>
								</div>
							</div>
                            <if condition="$new_charge_time neq 1">
							<div class="tab-pane" id="txtstore">
								<div class="form-group">
									<label class="col-sm-1"><label for="water_price">水费总欠费：</label></label>
									<input class="col-sm-2" size="20" name="water_price" id="water_price" type="text" value="" <if condition="!floatval($now_village['water_price'])">readonly="readonly"</if>/><if condition="!floatval($now_village['water_price'])"><span class="form_tips" style="color:red;">您未开通电费缴费，请在基本信息->缴费配置中填写“水费单价”</span></if>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="electric_price">电费总欠费：</label></label>
									<input class="col-sm-2" size="20" name="electric_price" id="electric_price" type="text" value="" <if condition="!floatval($now_village['electric_price'])">readonly="readonly"</if>/><if condition="!floatval($now_village['electric_price'])"><span class="form_tips" style="color:red;">您未开通电费缴费，请在基本信息->缴费配置中填写“电费单价”</span></if>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="gas_price">燃气费总欠费：</label></label>
									<input class="col-sm-2" size="20" name="gas_price" id="gas_price" type="text" value="" <if condition="!floatval($now_village['gas_price'])">readonly="readonly"</if>/><if condition="!floatval($now_village['gas_price'])"><span class="form_tips" style="color:red;">您未开通燃气缴费，请在基本信息->缴费配置中填写“燃气费单价”</span></if>
								</div>

								<!--div class="form-group">
									<label class="col-sm-1"><label for="property_price">物业总欠费：</label></label>
									<input class="col-sm-2" size="20" name="property_price" id="property_price" type="text" value=""/>
								</div-->

								<!-- <div class="form-group">
									<label class="col-sm-1">是否有停车位：</label>
									<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="park_flag"><span style="z-index: 1" class="lbl">开启</span></label>
									<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="park_flag"><span style="z-index: 1" class="lbl">关闭</span></label>
								</div> -->

								<div class="form-group">
									<label class="col-sm-1"><label for="park_price">停车费总欠费：</label></label>
									<input class="col-sm-2" size="20" name="park_price" id="park_price" type="text" value="" <if condition="!floatval($now_village['park_price'])">readonly="readonly"</if>/><if condition="!floatval($now_village['park_price'])"><span class="form_tips" style="color:red;">您未开通停车缴费，请在基本信息->缴费配置中填写“停车位单价”</span></if>
								</div>

								<div class="form-group">
									<label class="col-sm-2"><label for="property_starttime">物业服务开始时间截止至</label></label>
									<input type="text" name="property_starttime" class="input-text" value=""  style="height:42px" onfocus="WdatePicker({minDate: '1900-01-01',isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（开始）"/>
								</div>

								<div class="form-group">
									<label class="col-sm-2"><label for="property_endtime">物业服务时间截止至</label></label>
									<input type="text" name="property_endtime" class="input-text" value=""  style="height:42px" onfocus="WdatePicker({minDate: '1900-01-01',isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（结束）"/>
								</div>
							</div>
                        </if>
							<div class="tab-pane" id="txtpwd">
								<if condition="$dataList">
									<volist name="dataList" id="vo">
										<if condition="$vo['type'] eq 1">
											<!--文本-->
											<div id="basicinfo" class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" class="xtype" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" class="is_must" value="{pigcms{$vo.is_must}"/>
												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
												<input type="hidden" name="{pigcms{$vo.key}[title]" class="xtitle" value="{pigcms{$vo.title}"/>
												<input class="col-sm-2 xvalue" size="20" name="{pigcms{$vo.key}[value]" type="text" value=""/>
												<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>

											</div>
											<elseif condition="$vo['type'] eq 2"/>
											<!--单选-->
											<div id="basicinfo" class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" class="xtype" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" class="is_must" value="{pigcms{$vo.is_must}"/>
												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
												<input type="hidden" name="{pigcms{$vo.key}[title]" class="xtitle" value="{pigcms{$vo.title}"/>
												<select name="{pigcms{$vo.key}[value]" class="xvalue">
													<option value="0">请选择</option>
													<volist name="vo.use_field" id="voo">
														<option value="{pigcms{$voo}">{pigcms{$voo}</option>
													</volist>
												</select>
												<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
											</div>
											<elseif condition="$vo['type'] eq 3"/>
											<!--籍贯-->
											<div id="basicinfo" class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" class="xtype" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" class="is_must province_city" value="{pigcms{$vo.is_must}"/>
												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
												<input type="hidden" name="{pigcms{$vo.key}[title]" class="xtitle" value="{pigcms{$vo.title}"/>
<!--												<input class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}"/>-->
												<fieldset id="choose_street" province_idss="{pigcms{$village_info.province_id}" city_idss="{pigcms{$village_info.city_id}" class="field_province_city" ></fieldset>

												<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
											</div>
                                            <elseif condition="$vo['type'] eq 4"/>
                                            <!--日期-->
                                            <div id="basicinfo" class="form-group">
                                                <label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
                                                <input type="hidden" name="{pigcms{$vo.key}[type]" class="xtype" value="{pigcms{$vo.type}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[is_must]" class="is_must"  value="{pigcms{$vo.is_must}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[title]" class="xtitle" value="{pigcms{$vo.title}"/>
                                                <input class="col-sm-2 xvalue" size="20" name="{pigcms{$vo.key}[value]" type="text" value="" onfocus="WdatePicker({minDate: '1900-01-01',isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                                                <if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>

                                            </div>
										</if>
									</volist>
								<else/>
									请去业主资料处添加资料字段
								</if>
							</div>
                            
                            <div class="tab-pane" id="infotagging">

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_political_affiliation_title}：</label></label>
                                    <volist name="user_tag.user_political_affiliation_arr" id="aff">
                                        <label>
                                            <input type="radio" name="user_political_affiliation" value="{pigcms{$key}" class="ace" onclick="political({pigcms{$key})" >
                                            <span class="lbl" style="z-index: 1">{pigcms{$aff}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>

                                    </volist>
                                    <select name="user_party_id" id="party"  style="display: none">
                                    <volist name="user_tag.user_party_arr" id="vo">
                                        <option value="{pigcms{$vo.id}">{pigcms{$vo.name}</option>
                                    </volist>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_special_groups_title}：</label></label>
                                    <volist name="user_tag.user_special_groups_arr" id="special">
                                        <label>
                                            <input type="checkbox" name="user_special_groups[]" value="{pigcms{$key}" class="ace" >
                                            <span class="lbl" style="z-index: 1">{pigcms{$special}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>
                                    </volist>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_focus_groups_title}：</label></label>
                                    <volist name="user_tag.user_focus_groups_arr" id="focus">
                                        <label>
                                            <input type="checkbox" name="user_focus_groups[]" value="{pigcms{$key}" class="ace" >
                                            <span class="lbl" style="z-index: 1">{pigcms{$focus}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>
                                    </volist>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_vulnerable_groups_title}：</label></label>
                                    <volist name="user_tag.user_vulnerable_groups_arr" id="vulnerable">
                                        <label>
                                            <input type="checkbox" name="user_vulnerable_groups[]" value="{pigcms{$key}" class="ace" >
                                            <span class="lbl" style="z-index: 1">{pigcms{$vulnerable}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>
                                    </volist>
                                </div>


                                <if condition="$user_label && false">
                                    <!--单选-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">政治面貌：</label></label>
                                        <volist name="user_label.political_outlook" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="radio" value="{pigcms{$vo.id}" name="political_outlook" >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">特殊人群：</label></label>
                                        <volist name="user_label.special_population" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="special_population[{pigcms{$vo.id}]" >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">重点人群：</label></label>
                                        <volist name="user_label.key_population" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="key_population[{pigcms{$vo.id}]" >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">关怀对象：</label></label>
                                        <volist name="user_label.care_object" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="care_object[{pigcms{$vo.id}]" >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                </if>
                            </div>

                            <div class="tab-pane" id="userLabel">
                                <if condition="$user_tag['house_custom_label_arr'] ">
                                    <volist name="user_tag.house_custom_label_arr" id="v1">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="name">{pigcms{$v1.name}：</label></label>
                                            <notempty name="v1.children">
                                                <volist name="v1.children" id="v2">
                                                    <label>
                                                        <input type="checkbox" name="user_label_groups[{pigcms{$v2.pid}][]" value="{pigcms{$v2.id}" class="ace"  >
                                                        <span class="lbl" style="z-index: 1">{pigcms{$v2.name}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                                    </label>
                                                </volist>
                                            </notempty>
                                        </div>
                                    </volist>
                                </if>

                            </div>

                            <script>
                                $(function () {
                                    var check = $('input[name="political_outlook"]:checked').val();
                                    if(check == null || check == 'undefined'){
                                        // 指定选中第一个按钮
                                        $('input[name="political_outlook"]:first').prop('checked',true);
                                    }
                                })
                            </script>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										添加
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
    </div>
</div>
<script type="text/javascript">
	var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
	var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
	var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=05"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<script type="text/javascript">
	var cat_id = $('#single').val();
    $('#property_starttime_endtime').hide();
    if(cat_id){
        get_son_category(cat_id);
    }else{
         $('#single').val("");
         $("#single").find("option[text='请选择"+"<?php echo (D('House_village')->getHouseAlias('single_name',$village_id)?:'楼栋')?>"+"']").prop("selected",true);
         $('#single').find('option').first().prop('selected', true);
         $('#single').find('option').first().click();
    }
    var car_id = [];
    $(".car-table").hide()

	$('#single').change(function(){

		var cat_id = $('#single').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
        $('#property_starttime_endtime').hide();
		get_son_category(cat_id);

	});

	// 选中房间后渲染面积
    $('#vacancy').change(function () {
        var pigcms_id = $(this).val();
        var optionObj=$('#vacancy .data-'+pigcms_id);
        var housesize = optionObj.data('housesize');
        console.log('housesize', housesize);
        $('#housesize').val(housesize);
        if($('#property_starttime_endtime').size()>0){
            var pservice_start_time=optionObj.data('service_start_time')||'';
            var pservice_end_time=optionObj.data('service_end_time')||'';
            var property_starttime_endtime ='';
            if(pservice_start_time){
                property_starttime_endtime=pservice_start_time;
                $('#str_property_starttime').data('vservice_start_time',pservice_start_time);
                $('#str_property_starttime').val(pservice_start_time);
            }else{
                $('#str_property_starttime').data('vservice_start_time','');
                property_starttime_endtime='开始时间未设置';
            }
            if(pservice_end_time){
                property_starttime_endtime += ' ~ '+pservice_end_time;
                $('#str_property_endtime').data('vservice_end_time',pservice_end_time);
                $('#str_property_endtime').val(pservice_end_time);
            }else{
                $('#str_property_endtime').data('vservice_end_time','');
                property_starttime_endtime += ' ~ '+'结束时间未设置';
            }
            $('#property_starttime_endtime').show();
            $('#property_starttime_endtime span').text(property_starttime_endtime);
        }
    })

	function get_son_category(cat_id){
        console.log('cat_idxxx',cat_id)
        $('#property_starttime_endtime').hide();
		var url = "{pigcms{:U('Service/ajax_get_floor')}";
		$.post(url,{'cat_id':cat_id},function(result){
			var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元')?>"+'</option>';
			var floor = '<option value="0">请选择楼层</option>';
			var htmls = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>"+'</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
				}
				$('#floor').html(html);
				$('#layer').html(floor);
				$('#vacancy').html(htmls);
			} else {
				$("#floor").html(html);
				$('#layer').html(floor);
				$('#vacancy').html(htmls);
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
        $('#property_starttime_endtime').hide();
		get_new_layer(cat_id);

	});

	function get_vacancy(cat_id){
		var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
        $('#property_starttime_endtime').hide();
		$.post(url,{'cat_id':cat_id,'type':10},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>"+'</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['pigcms_id'] +'" class="data-'+ cat_list[i]['pigcms_id'] +'" data-housesize="'+ cat_list[i]['housesize'] +'" data-service_start_time="'+cat_list[i]['service_start_time']+'" data-service_end_time="'+cat_list[i]['service_end_time']+'">' + cat_list[i]['room'] + '</option>';
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
        $('#property_starttime_endtime').hide();
		$.post(url,{'cat_id':cat_id},function(result){
			var shtml = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>"+'</option>';
			var html = '<option value="0">请选择楼层</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
				}
				$('#layer').html(html);
				$('#vacancy').html(shtml);
			} else {
				$("#layer").html(html);
				$('#vacancy').html(shtml);
			}
		},'json');
	}
	// 添加车辆
    function addCar() {
	    var single = $("#single").val();
	    var floor_id = $("#floor").val();
	    var layer_id = $("#layer").val();
	    var vacancy_id = $("#vacancy").val();
        if($('#housesize').val()=='' || $('#housesize').val()<0){
            alert('房子平方不能为空');
            return false;
        }
        var url = "{pigcms{:U('Unit/vehicle_add')}"+"&source=user&single="+single+'&floor_id='+floor_id+'&layer_id='+layer_id+'&vacancy_id='+vacancy_id;
        console.log('添加车辆========================',url)
        var page = layer.open({
            type: 2,
            area: ['1150px', '650px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '添加车辆',
            content: url,
            end: function(index){
                if(car_id.length > 0){
                    carList();
                }
            }
        });
        layer.style(page, {
            top: '20px'
        });
    }
    // 车辆列表
    function carList(){
        var url = "{pigcms{:U('Unit/vehicle_management_list')}";
        $.post(url,{'car_id':car_id},function(result){
            if(result['status'] == 1){
                var car_list = result.list.info_list;
                console.log(car_list);
                if(car_list.length > 0){
                    $(".car-table").show()
                }else{
                    $(".car-table").hide()
                }
                var html = ''
                for(var i in car_list){
                    html += '<tr class="';
                        if(i%2 === 0){
                            html += 'odd">';
                        }else{
                            html += 'even">';
                        }
                    html += '<td>'+ car_list[i].position_num +'</td>'+
                        '<td>'+ car_list[i].province + car_list[i].car_number +'</td>'+
                        '<td>'+ car_list[i].car_stop_num +'</td>'+
                        '<td>'+ car_list[i].car_user_name +'</td>'+
                        '<td>'+ car_list[i].car_user_phone +'</td>' +
                        '<td> <a class="label label-sm label-info" href="javascript:void(0);" onclick="unbind('+ car_list[i].car_id +')">解绑</a> </td>' +
                        '</tr>';
                }
                $('#carList').html(html);

                var carId = car_id.join(',');
                $("#car_id").val(carId)
            }else {
                alert(result['msg']);
            }
        },'json');
    }
    // 解绑
    function unbind(id){
        var index = car_id.indexOf(id);
        car_id.splice(index,1);
        console.log(car_id)
        carList();
    }

    // 限制非机动车卡号不能使用中文
    function nmvCard(){
	    var nmv_card = $("#nmv_card").val();
        if(nmv_card.length > 0 && !(/^[0-9a-zA-Z]*$/.test(nmv_card))) {
            $("#nmv_card").val('');
            alert('卡号只能包含数字和英文')
        }
    }

</script>
<script type="text/javascript">
var international_phone = "{pigcms{$config.international_phone|intval=###}";
$('#phone').on('blur' , function(){
	phone = $(this).val();
	if(!international_phone && phone.length != 11){
		$(".not-phone").html('&nbsp;&nbsp;* 手机号不正确').show();	
		return false;	
	}
	$.post("{pigcms{:U('ajax_empty_user_info')}" , {phone:phone} , function(data){
		if(data.status==1){
			$(".not-phone").html('&nbsp;&nbsp;* '+data.msg).show();	
		}else{
			$(".not-phone").html('').hide();	
		}	
	},'JSON');
	
});

$('input[name="park_flag"]').change(function(){
	if($(this).val()==1){
		$('input[name="park_price"]').parent().show();
	}else{
		$('input[name="park_price"]').parent().hide();
	}
})


$('#usernum').blur(function(){
	var usernum = $(this).val();
	var url = "{pigcms{:U('ajax_user_bind')}";
	$.post(url,{'usernum':usernum},function(data){
		if(data.status==1){
			$('#usernum').next('span').show();
		}else{
			$('#usernum').next('span').hide();
		}
	},'json')
});


var url = "{pigcms{:U('Service/ajax_get_layer')}";
$('#floor_id').change(function(){
	var floor_id = $(this).val();
	$.post(url,{'floor_id':floor_id},function(data){
		if(data['status'] == 0){
			alert(data.msg);
		}else{
			var list = data['list'];
			var shtml = '';

			if(list){
				shtml += '<option>请选择</option>'
				for(var i in list){
					shtml += '<option data-layer="'+list[i]['layer']+'" data-room="'+list[i]['room']+'" data-housesize="'+list[i]['housesize']+'" value="'+list[i]['pigcms_id']+'">'+list[i]['layer']+'&nbsp;&nbsp;--&nbsp;&nbsp;'+list[i]['room']+'</option>'
				}
			}else{
				shtml += '<option>请选择</option>'
			}
			$('#layer_room').html(shtml)
			$("#layer_num").val(0);
			$("#room_num").val(0);
			$("#vacancy_id").val(0);
		}
	},'json')
}).trigger('change');

$('#layer_room').change(function(){
	$('#housesize').val($(this).find(':selected').data('housesize'));
	$('#layer_num').val($(this).find(':selected').data('layer'));
	$('#room_num').val($(this).find(':selected').data('room'));
	$('#vacancy_id').val($(this).find(':selected').val());
})

function check_submit(){
	if($('#usernum').size()>0 && $('#usernum').val()==''){
		alert('业主编号不能为空！');
		return false;
	}
	var user_name=$('#user_name').val()||'';
    user_name=$.trim(user_name);
	if(user_name=='' || user_name.length<1){
		alert('业主名不能为空！');
		return false;
	}
	
	/*if($('#phone').val()==''){
		alert('手机号不能为空！');
		return false;
	}*/
	
	if($('#floor_name').val()==''){
		alert("<?php echo (D('House_village')->getHouseAlias('floor_name',$village_id)?:'单元')?>"+'名称不能为空！');
		return false;
	}
	
	if($('#floor_layer').val()==''){
		alert('楼号不能为空！');
		return false;
	}
	
	/* if($('#layer_num').val()==''){
		alert('层号不能为空！');
		return false;
	} 
	
	if($('#room_num').val()==''){
		alert('门牌号不能为空！');
		return false;
	}*/
	
	if($('#housesize').val()=='' || $('#housesize').val()<0){
		alert('房子平方不能为空 / 格式不正确');
		return false;
	}
	
	if($('#water_price').val()<0){
		alert('水费总欠费格式不正确');
		return false;
	}
	
	if($('#electric_price').val()<0){
		alert('电费总欠费格式不正确');
		return false;
	}
	
	if($('#gas_price').val()<0){
		alert('燃气费总欠费格式不正确');
		return false;
	}
	
	if($('#park_price').val()<0){
		alert('停车费总欠费格式不正确');
		return false;
	}
    if($('#property_starttime_endtime').size()>0){
        var pservice_start_time=$('#str_property_starttime').val()||'';
        var vservice_start_time = $('#str_property_starttime').data('vservice_start_time')||'';
        var pservice_end_time=$('#str_property_endtime').val()||'';
        var vservice_end_time = $('#str_property_endtime').data('vservice_end_time')||'';
        if(vservice_start_time && vservice_start_time.length>1){
            var vservice_start_time_tmp=vservice_start_time.replace(/\-/g,'');
            vservice_start_time_tmp=parseInt(vservice_start_time_tmp);
            var pservice_start_time_tmp=pservice_start_time.replace(/\-/g,'');
            pservice_start_time_tmp=parseInt(pservice_start_time_tmp);
            if(vservice_start_time_tmp>pservice_start_time_tmp){
            		alert('缴费开始时间不能小于'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>"+'的物业服务开始时间');
		            return false;
            }
            var vservice_end_time_tmp=vservice_end_time.replace(/\-/g,'');
            vservice_end_time_tmp=parseInt(vservice_end_time_tmp);
            var pservice_end_time_tmp=pservice_end_time.replace(/\-/g,'');
            pservice_end_time_tmp=parseInt(pservice_end_time_tmp);
            console.log(pservice_start_time_tmp,pservice_end_time_tmp,vservice_end_time_tmp);
            if(pservice_start_time_tmp>pservice_end_time_tmp){
            		alert('缴费开始时间不能大于缴费结束时间');
		            return false;
            }
            if(vservice_end_time_tmp<pservice_end_time_tmp){
            	alert('缴费结束时间不能大于'+"<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>"+'的物业服务结束时间');
		        return false;
            }
        }
    }
	if($('#txtpwd .form-group input').size()>0){
		var is_alert=false;
		$('#txtpwd .form-group').each(function(ii){
			var xtype=$(this).find('.xtype').val();
			xtype=$.trim(xtype);
			var is_must=$(this).find('.is_must').val();
			is_must=$.trim(is_must);
			var xvalue=$(this).find('.xvalue').val();
			xvalue=$.trim(xvalue);
			var xtitle=$(this).find('.xtitle').val();
			xtitle=$.trim(xtitle);
			if((is_must==1 || is_must=='1') && (xtype=='3' || xtype==3) ){
				$(this).find('.field_province_city').find('select').each(function(mm){
					var valV=$(this).val();
					valV=$.trim(valV);
                    console.log('valV',valV);
					if(!valV || valV=='' || valV=='0' ||  valV==0){
						is_alert=true;
						alert('业主资料中的【'+xtitle+'】没填写或者没选择！');
						return false;
					}
				});
				if(is_alert){
					return false;
				}
			}else if((is_must==1 || is_must=='1') && (!xvalue ||xvalue=='' || xvalue=='0' || xvalue==0)){
				is_alert=true;
				alert('业主资料中的【'+xtitle+'】没填写或者没选择！');
				return false;
			}
		});
		if(is_alert){
			return false;
		}
	}
	return true
    layer.load(0, {shade: [0.6,'#fff']});
}


function readCard() {
    console.log(111);
    var url = "{pigcms{:U('readCard')}";
    var page = layer.open({
        type: 2,
        area: ['650px', '450px'],
        fixed: true, //不固定
        // maxmin: true,
        title: '读取卡号',
        content: url,
    });
    layer.style(page, {top: '20px'});
}

function callbackFunc(d) {
    $('#ic_card').val(d);
}

//解析身份证号码
$('#id_card').bind('input propertychange', function() {
    var val=$(this).val(),is_sex=false,is_birthday=false;
    if($("select[name='sex[value]']").length > 0){
        is_sex=true;
    }
    if($("input[name='birthday[title]").length > 0){
        is_birthday=true;
    }
    if(!is_sex && !is_birthday){
        return false;
    }
    $.post("{pigcms{:U('getIdCardAnalysis')}",{'id_card':val},function(rr){
        if(rr.status == 1){
            var data=rr.data;
            if(is_sex){
                $("select[name='sex[value]']").val(data.sex);
            }
            if(is_birthday){
                $("input[name='birthday[title]").val(data.birthday);
                $("input[name='birthday[value]").val(data.birthday)
            }
        }
    },'json')
});

function political(key_id){
    if(key_id == 1){
        $('#party').attr('style','display:');
    }else{
        $('#party').attr('style','display:none');
        // document.all.party.value="0"
        $('#party').val("0");
    }
}

</script>

<if condition="$now_house['write_iccard']">
	<script language="javascript" src="http://127.0.0.1:8008/YOWOCloudRFIDReader.js"></script>
	<script language="javascript" src="{pigcms{$static_path}/js/YOWOReaderBase.js"></script>

	<script language="javascript" type="text/javascript">
		//demo  http://www.youwokeji.com.cn/CloudReader/Sample1.html
		rfidreader.onResult(function(resultdata) {
			console.log('resultdata', resultdata);
			switch(resultdata.FunctionID) {
				//IC卡读卡器云服务版本号
				case 14:
					$('#rfid_get_card').show();
					console.log(resultdata.strData);
					break;
				case 0:
					if(resultdata.Result>0){
						$('#ic_card').val(resultdata.strData);		
					}
					break;
			}
		});

		function rfid_get_card_func(){
			var FormatID;
			var OrderID;
			
			FormatID = '1'; //1为10进制(10DEC) ，0为16进制(8HEX)
			OrderID = '0'; //0为正常 ，1为倒序
			rfidreader.Repeat=1;
			rfidreader.HaltAfterSuccess=1;
			rfidreader.RequestTypeACardNo(FormatID, OrderID);
		}
		rfid_get_card_func();
	</script>
</if>

<if condition="$now_house['read_idcard']">
	<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/baseISSObject.js"></script>
	<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/baseISSOnline.js"></script>
	<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/common.js"></script>
</if>

<include file="Public:footer"/>
