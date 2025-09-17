<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Unit/import_village')}">房间列表</a>
			</li>
			<li class="active">修改信息</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
                .msg_div{
                    margin-bottom: 15px;
                }
                .msg_span{
                    background-color: #dff0d8;
                    color: #3c763d;
                    padding: 8px;
                    border: 1px solid #d6e9c6;
                    display: inline-block;
                }
			</style>
			<div class="row">
				<div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active">
                                <a href="#basicinfo" data-toggle="tab">基本信息</a>
                            </li>
                            <if condition="$a185_num neq 0">
                            <li>
                                <a href="#txtstore" data-toggle="tab">室内机管理</a>
                            </li>
                            </if>
                            <if condition="C('config.customized_meter_reading')">
                            <li>
                                <a href="#txtstore2" data-toggle="tab">设备管理</a>
                            </li>
                            </if>
                        </ul>
                    </div>

					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="usernum">物业编号</label></label>
                                    <if condition="$detail['property_number']">
                                        <input class="col-sm-2" size="20" name="usernum" id="usernum" type="text" value="{pigcms{$detail.property_number}" readonly="readonly"/>
                                        &nbsp;&nbsp;&nbsp;&nbsp;<span class="red" style="line-height: 30px;">由楼栋编号、单元编号、楼层编号和房屋编号依次拼接而成</span>
                                        <else/>
                                        <input class="col-sm-2" size="20" name="usernum" id="usernum" type="text" value="{pigcms{$detail.usernum}" readonly="readonly"/>
                                    </if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_name">单元名称</label></label>
									<input class="col-sm-2" size="20" name="floor_name" id="floor_name" type="text" value="{pigcms{$detail.floor_name}" readonly="readonly"/>
								</div>
                                <div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">单元楼号</label></label>
									<input class="col-sm-2" size="20" name="floor_layer" id="floor_layer" type="text" value="{pigcms{$detail.layer_name}" readonly="readonly"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="contract_time">合同时间</label></label>
                                    <input type="text" name="contract_time_start" class="input-text" value="{pigcms{$detail.contract_time_start}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="合同开始时间"/>
                                    &nbsp;—&nbsp;
                                    <input type="text" name="contract_time_end" disabled class="input-text" value="{pigcms{$detail.contract_time_end}"  style="height:30px;" onfocus="WdatePicker({isShowClear:true,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="合同结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips">设置房间时间范围，设置后物业只能收取合同时间内收费项目，未到合同开始时间或结束时间不能收费；未设置物业服务小区时间范围，则不影响。</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="layer">层号</label></label>
									<input class="col-sm-2" size="20" name="layer" id="layer" type="text" value="{pigcms{$detail.layer}" readonly="readonly"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="room">房间号</label></label>
									<input class="col-sm-2" size="20" name="room" id="room" type="text" value="{pigcms{$detail.room}" <if condition='$detail["status"] gt 1'>readonly="readonly"</if>/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="room_number">房间编号</label></label>
                                    <input class="col-sm-2" size="20" name="room_number" id="room_number" type="number" value="{pigcms{$detail.room_number}"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="red" style="line-height: 30px;">*必填项（仅限1-9999不重复的数字）</span>
                                </div>
                                  <div class="form-group">
                                    <label class="col-sm-1"><label for="room_alias_id">房间别名编号</label></label>
                                    <input class="col-sm-2" size="30" maxlength="30" min="1" name="room_alias_id" id="room_alias_id" type="text" value="{pigcms{$detail.room_alias_id}" placeholder="" oninput="checkInputNum()" />
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="red" style="line-height: 30px;"> 为房间取一个数字别名，必须具有唯一性</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="housesize">房屋面积</label></label>
									<input class="col-sm-2" size="20" name="housesize" id="housesize" type="text" value="{pigcms{$detail.housesize}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="layer_room">房屋类型：</label></label>
									<select class="col-sm-2" name="house_type" id="house_type" >
                                        <option <if condition="!$detail['house_type']">selected</if> value="0">无</option>
                                        <option <if condition="$detail['house_type'] eq 1">selected</if> value="1">住宅</option>
                                        <option <if condition="$detail['house_type'] eq 2">selected</if> value="2">商铺</option>
                                        <option <if condition="$detail['house_type'] eq 3">selected</if> value="3">办公</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="layer_room">使用状态：</label></label>
									<select class="col-sm-2" name="user_status" id="user_status" >
                                        <option <if condition="$detail['user_status'] eq 0 || !$detail['user_status']">selected</if> value="0">无</option>
										<option <if condition="$detail['user_status'] eq 1">selected</if> value="1">业主入住</option>
										<option <if condition="$detail['user_status'] eq 2 ">selected</if> value="2">未入住</option>
										<option <if condition="$detail['user_status'] eq 3">selected</if> value="3">租客入住</option>
									</select>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="red" style="line-height: 30px;">仅供标记使用，不会自动变化的，需要自行编辑维护</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="layer_room">出售状态：</label></label>
									<select class="col-sm-2" name="sell_status" id="sell_status" >
										<option <if condition="$detail['sell_status'] eq 1">selected</if> value="1">正常居住</option>
										<option <if condition="$detail['sell_status'] eq 2">selected</if> value="2">出售中</option>
										<option <if condition="$detail['sell_status'] eq 3">selected</if> value="3">出租中</option>
									</select>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="red" style="line-height: 30px;">仅供标记使用，不会自动变化的，需要自行编辑维护</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="layer">排序</label></label>
                                    <input class="col-sm-2" size="20" name="sort" id="sort" type="number" value="{pigcms{$detail.sort}" />
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="line-span ">数字越大越靠前</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">状态</label>

                                    <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$detail["status"] egt 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
                                    <label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$detail["status"] eq 0'>checked="checked"</if> name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
                                </div>

                                <if condition="$detail['showArr'] && $detail['showArr'][0]">
                                    <div class="form-group">
                                        <label class="col-sm-1"><b>导入数据</b></label>
                                        &nbsp;
                                    </div>
                                    <volist name="detail['showArr']" id="item">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="showArr">{pigcms{$item['title']}</label></label>
                                            {pigcms{$item.value}
                                        </div>
                                    </volist>
                                </if>
							</div>
                            <if condition="$a185_num neq 0">
                                <div id="txtstore" class="tab-pane">
                                    <div class="msg_div">
                                        <span class="msg_span">注意：室内机编号仅限于 A185智能门禁配合使用</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="room">室内机设备号</label></label>
                                        <input class="col-sm-2" size="20" name="indoor_device_sn" id="indoor_device_sn" type="text" value="{pigcms{$indoor.device_sn}" maxlength="50" placeholder="请输入室内机设备号"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">状态</label>

                                        <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$indoor["status"] egt 1'>checked="checked"</if> class="ace" value="1" name="indoor_status"><span style="z-index: 1" class="lbl">开启</span></label>
                                        <label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$indoor["status"] eq 0'>checked="checked"</if> name="indoor_status"><span style="z-index: 1" class="lbl">关闭</span></label>
                                    </div>
                                </div>
                            </if>
                            <if condition="C('config.customized_meter_reading')">
                            <div id="txtstore2" class="tab-pane">
                                <div class="msg_div">
                                    <span class="msg_span">注意：对接水电燃仪表设备，由设备方提供水电燃设备接口 <span style="color: red;font-weight: bold;margin-left: 10px">冷/热水表表号具有唯一性，不可重复</span></span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="water_number">冷水表表号</label></label>
                                    <input class="col-sm-2" size="20" name="water_number" id="water_number" type="text" value="{pigcms{$detail.water_number}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="water_number">热水表表号</label></label>
                                    <input class="col-sm-2" size="20" name="heat_water_number" id="heat_water_number" type="text" value="{pigcms{$detail.heat_water_number}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="ele_number">电表表号</label></label>
                                    <input class="col-sm-2" size="20" name="ele_number" id="ele_number" type="text" value="{pigcms{$detail.ele_number}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="gas_number">燃气表表号</label></label>
                                    <input class="col-sm-2" size="20" name="gas_number" id="gas_number" type="text" value="{pigcms{$detail.gas_number}"/>
                                </div>
                            </div>
                            </if>

                            <if condition="$show_sky_cockpit_entrance eq 1">
								<div class="form-group">
									<label class="col-sm-1"><label for="bind_people_num">最多绑定住户数量</label></label>
									<input class="col-sm-2" size="20" name="bind_people_num" id="bind_people_num" min="0" type="number" value="{pigcms{$detail.bind_people_num}" /> &nbsp;&nbsp;&nbsp;<span class="msg_span">控制从用户端绑定人时，能绑定的人数，空或者0表示不限制</span>
								</div>
                            </if>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
            						<if condition="in_array(39,$house_session['menus'])">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									<else/>
									<button class="btn btn-info" type="submit" disabled="disabled">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									</if>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
function check_submit(){
	if($('#floor_name').val() == ''){
		alert('单元名称不能为空！');
		return false;
	}

	if($('#floor_layer').val() == ''){
		alert('单元楼号不能为空！');
		return false;
	}

	if(parseFloat($('#housesize').val()) <= 0 ){
		alert('房屋面积不能为空！');
		return false;
	}
    var room_alias_id=$('#room_alias_id').val()||'';
	if(!room_alias_id){
		//alert('房间别名编号必须填写，不能为空！');
        //$('#room_alias_id').focus();
		//return false;
	}
/* 	if($('select[name="property_month_num"]').val() == 0){
		if($('#property_month_num').val() == ''){
			alert('请填写物业缴费周期');
			return false;
		}
	}


	if($('select[name="presented_property_month_num"]').val() == 0){
		if($('#presented_property_month_num').val() == ''){
			alert('请填写赠送物业时间');
			return false;
		}
	} */
}
function checkInputNum(){
   var room_alias_id= $('#room_alias_id').val()||'';
    //console.log('room_alias_id1',room_alias_id);
    room_alias_id=room_alias_id.replace(/[^\d]/g,'');
    //console.log('room_alias_id2',room_alias_id);
    $('#room_alias_id').val(room_alias_id)
}
$('select').change(function(){
	if($(this).val() == 0){
		$(this).next('label').show();
	}else{
		$(this).next('label').hide();
	}
});
</script>

<include file="Public:footer"/>