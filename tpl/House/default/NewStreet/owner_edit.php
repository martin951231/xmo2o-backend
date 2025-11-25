<include file="NewStreetPublic:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('User/index')}">住户管理</a>
			</li>
			<li class="active">住户信息设置</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content" style="padding: 0 !important;">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本信息</a>
							</li>
							<li>
								<a href="#txtpwd" data-toggle="tab">住户资料</a>
							</li>
							<li>
								<a href="#userlabel" data-toggle="tab">信息标注</a>
							</li>

						</ul>
					</div>
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('NewStreet/owner_edit')}" onsubmit="return chk_submit()">
						<input  name="pigcms_id" type="hidden"  value="{pigcms{$info.pigcms_id}"/>
                        <input  name="usernum" type="hidden"  value="{pigcms{$info.usernum}"/>
                        <input  name="iframe" type="hidden"  value="{pigcms{$info.iframe}"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								
								<div class="form-group">
									<label class="col-sm-2"><label for="usernum">用户编号</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$info.usernum}" type="text" style="border:none;background:white!important;" readonly="readonly">
								</div>
								
								<div class="form-group">
									<label class="col-sm-2"><label for="name">住户名称</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$info.name}"/>
								</div>

								<div class="form-group">
									<label class="col-sm-2"><label for="phone">手机号：</label></label>
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text" value="{pigcms{$info.phone}" />
									<span class="line-span ">&nbsp;只允许填写一个手机号，如需多个手机号请在下方备注中填写其他备用手机号</span>
									<span class="red line-span none not-phone">&nbsp;&nbsp;</span>
								</div>
								<script>
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
								</script>
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="id_card">身份证号：</label></label>
                                    <input class="col-sm-2" size="20" name="id_card" id="id_card" type="text" value="{pigcms{$info.id_card}"/>
                                    <span class="line-span ">&nbsp;填写后该住户可通过身份证进入门禁</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="ic_card">IC卡号：</label></label>
                                    <input class="col-sm-2" size="20" name="ic_card" id="ic_card" type="text" value="{pigcms{$info.ic_card}"/>
                                    <span class="line-span ">&nbsp;可不填，填写后该住户可通过IC卡进入门禁</span>
                                </div>
								<if condition="$attribute_list">
									<div class="form-group">
										<label class="col-sm-2"><label for="phone">住户属性：</label></label>
										<select name="attribute">
											<option value="0">请选择住户属性</option>
											<volist name="attribute_list" id="vo">
												<option value="{pigcms{$vo.pigcms_id}" <if condition="$info['attribute'] eq $vo['pigcms_id']">selected=selected</if>>{pigcms{$vo.name}</option>
											</volist>
										</select>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-2"><label for="floor_name"><?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元').(D('House_village')->getHouseAlias('room_name')?:'房间')?>：</label></label>
                                    <p style="line-height:30px; color:#666">
										{pigcms{$room_diy_name}
                                    </p>
								</div>


								<div class="form-group">
									<label class="col-sm-2"><label for="housesize">房子平方</label></label>
									{pigcms{$info.housesize}
									<span class="form_tips">m²</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-2"><label for="memo">备注</label></label>
									<textarea class="col-sm-2" size="10" name="memo" id="memo" />{pigcms{$info.memo}</textarea>
								</div>
								
								<div class="form-group">
									<label class="col-sm-2"><label for="unittype">住宅类型</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$info.floor_type_name}" type="text" style="border:none;background:white!important;" readonly="readonly">
								</div>
								
								<if condition='$info.type gt 0'>
									<div class="form-group">
										<label class="col-sm-2"><label for="unittype">关系</label></label>
										<input class="col-sm-2" size="20" <if condition='$info["type"] eq 1'>value="家人"<elseif condition='$info["type"] eq 2' />value="租客"</if> type="text" style="border:none;background:white!important;" readonly="readonly">
									</div>
								</if>


								<if condition="$now_house['write_iccard'] eq 1">
									<div class="form-group">
										<label class="col-sm-2" for="door_control">门禁控制</label>
										<if condition="$info['door_control'] eq 0">
											<label style="padding-left:0px;padding-right:20px;">
												<span class="lbl" style="z-index: 1" >机动车道</span>
											</label>
										</if>
										<if condition="$info['door_control'] eq 1">
											<label style="padding-left:0px;padding-right:20px;">
												<span class="lbl" style="z-index: 1">行人通道</span>
											</label>
										</if>
										<if condition="$info['door_control'] eq '0,1'">
											<label style="padding-left:0px;padding-right:20px;">
												<span class="lbl" style="z-index: 1" >全部通道</span>
											</label>
										</if>
									</div>
								</if>

								<div class="form-group">
									<label class="col-sm-2" for="park_flag">车位信息</label>
									<if condition="$position_list['list']">
										<table class="table table-striped table-bordered table-hover"  style="width: 60%">
											 <thead>
		                                        <tr>
		                                            <th width="15%">车库</th>
		                                            <th width="15%">车位编号</th>
		                                            <th width="15%">车位面积</th>
		                                            <th width="15%">备注</th>
		                                        </tr>
		                                    </thead>

											<volist name="position_list['list']" id="vo">
												<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <td>{pigcms{$vo.garage_num}</td>
                                                    <td>{pigcms{$vo.position_num}</td>
                                                    <td>{pigcms{$vo.position_area}</td>
                                                    <td>{pigcms{$vo.position_note}</td>
                                                </tr>
											</volist>
										</table>
									<else/>
									无
									</if>
									<!-- <label style="padding-left:0px;padding-right:20px;"><input type="radio" name="park_flag" value="1" class="ace" <if condition="$info['park_flag'] eq 1">checked="checked"</if>><span class="lbl" style="z-index: 1">开启</span></label>
									<label style="padding-left:0px;"><input type="radio" name="park_flag" value="0" class="ace" <if condition="$info['park_flag'] eq 0">checked="checked"</if>><span class="lbl" style="z-index: 1" >关闭</span></label> -->
								</div>

								<div class="form-group">
									<label class="col-sm-2" for="park_flag">车辆信息</label>
									<if condition="$car_list">
										<table class="table table-striped table-bordered table-hover"  style="width: 60%">
											 <thead>
		                                        <tr>
		                                            <th width="15%">车位</th>
		                                            <th width="15%">车牌号</th>
		                                            <th width="15%">停车卡号</th>
		                                            <th width="15%">车主姓名</th>
		                                            <th width="15%">车主手机号</th>
		                                            <th width="15%">车辆排量</th>
		                                        </tr>
		                                    </thead>

											<volist name="car_list" id="vo">
												<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <td>{pigcms{$vo.position_num}</td>
                                                    <td>{pigcms{$vo.province}{pigcms{$vo.car_number}</td>
                                                    <td>{pigcms{$vo.car_stop_num}</td>
                                                    <td>{pigcms{$vo.car_user_name}</td>
                                                    <td>{pigcms{$vo.car_user_phone}</td>
                                                    <td>{pigcms{$vo.car_displacement}</td>
                                                </tr>
											</volist>
										</table>
									<else/>
									无
									</if>
								</div>
							</div>
							<div class="tab-pane" id="txtpwd">
								<if condition="$dataList">
									<volist name="dataList" id="vo">
										<if condition="$vo['type'] eq 1">
											<!--文本-->
											<div  class="form-group">
												<label class="col-sm-2"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
												<!--												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>-->
												<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
												<input class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}"/>
                                                <if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
                                            </div>
											<elseif condition="$vo['type'] eq 2"/>
											<!--单选-->
											<div class="form-group">
												<label class="col-sm-2"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
												<!--												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>-->

												<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
												<select name="{pigcms{$vo.key}[value]">
													<option value="0">请选择</option>
													<volist name="vo.use_field" id="voo">
														<option <if condition="$info['authentication_field'][$vo['key']]['value'] eq $voo">selected</if> value="{pigcms{$voo}">{pigcms{$voo}</option>
													</volist>
												</select>
                                                <if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必选项</span></if>
											</div>
                                            <elseif condition="$vo['type'] eq 3"/>
                                            <!--籍贯-->
                                            <div id="basicinfo" class="form-group">
                                                <label class="col-sm-2"><label for="name">{pigcms{$vo.title}：</label></label>
                                                <input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
                                                <!--												<input class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}"/>-->
                                                <fieldset id="choose_street" province_idss="{pigcms{$info['authentication_field'][$vo['key']]['province_idss']}" city_idss="{pigcms{$info['authentication_field'][$vo['key']]['city_idss']}"></fieldset>
                                                <if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
                                            </div>
                                            <elseif condition="$vo['type'] eq 4"/>
                                            <!--日期-->
                                            <div id="basicinfo" class="form-group">
                                                <label class="col-sm-2"><label for="name">{pigcms{$vo.title}：</label></label>
                                                <input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
                                                <input class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                                                <!--                                                <input type="text" name="{pigcms{$vo.key}[value]"  value=""  onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="日期"/>-->

                                                <if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
                                            </div>
										</if>
									</volist>
									<else/>
									暂无住户资料字段
								</if>
								</if>

							</div>
							<div class="tab-pane" id="userlabel">
								<div class="form-group">
									<label class="col-sm-2"><label for="name">{pigcms{$user_political_affiliation_title}：</label></label>
									<volist name="user_political_affiliation_arr" id="aff">
										<label>
											<input type="radio" name="user_political_affiliation" value="{pigcms{$key}" class="ace" onclick="political({pigcms{$key})" <if condition="$user_label['user_political_affiliation'] eq $key">checked="checked"</if> >
											<span class="lbl" style="z-index: 1">{pigcms{$aff}</span>&nbsp;&nbsp;&nbsp;&nbsp;
										</label>

									</volist>
                                    <!--选择党员类型-->
                                    <select name="party_type" id="party_type" onchange="political_type()" <if condition="$user_label['user_political_affiliation'] neq 1"> style="display: none" </if>>
                                        <option value="0" <if condition="$user_label['party_type'] eq 0"> selected </if>>请选择党员属性</option>
                                        <option value="1" <if condition="$user_label['party_type'] eq 1"> selected </if>>社区党员</option>
                                        <option value="2" <if condition="$user_label['party_type'] eq 2"> selected </if>>居住地党员</option>
                                    </select>
                                    <select name="party" id="party" <if condition="$user_label['user_political_affiliation'] neq 1 || $user_label['party_type'] neq 1"> style="display: none" </if>>
<!--                                        <option value="0">请选择党支部</option>-->
                                        <volist name="party" id="vo">
                                            <option value="{pigcms{$vo.id}" <if condition="$vo.id eq $party_bind_user['party_id']"> selected </if>>{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
								</div>
                                <div>
                                    <div id="party_type_msg" <if condition="$user_label['user_political_affiliation'] neq 1"> style="display: none" <else/> style="margin-top: -15px;margin-bottom: 20px；display: none" </if>><span>
                                    社区党员是指：组织关系在本社区的党员；居住地党员是指：组织关系不在社区，但居住在本社区的党员，其中包括流动党员
                                        </span></div>
                                </div>
								<div class="form-group">
									<label class="col-sm-2"><label for="name">{pigcms{$user_special_groups_title}：</label></label>
									<volist name="user_special_groups_arr" id="special">
										<label>
											<input type="checkbox" name="user_special_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_label['user_special_groups'])">checked="checked"</if> >
											<span class="lbl" style="z-index: 1">{pigcms{$special}</span>&nbsp;&nbsp;&nbsp;&nbsp;
										</label>
									</volist>
								</div>
								<div class="form-group">
									<label class="col-sm-2"><label for="name">{pigcms{$user_focus_groups_title}：</label></label>
									<volist name="user_focus_groups_arr" id="focus">
										<label>
											<input type="checkbox" name="user_focus_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_label['user_focus_groups'])">checked="checked"</if> >
											<span class="lbl" style="z-index: 1">{pigcms{$focus}</span>&nbsp;&nbsp;&nbsp;&nbsp;
										</label>
									</volist>
								</div>
								<div class="form-group">
									<label class="col-sm-2"><label for="name">{pigcms{$user_vulnerable_groups_title}：</label></label>
									<volist name="user_vulnerable_groups_arr" id="vulnerable">
										<label>
											<input type="checkbox" name="user_vulnerable_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_label['user_vulnerable_groups'])">checked="checked"</if> >
											<span class="lbl" style="z-index: 1">{pigcms{$vulnerable}</span>&nbsp;&nbsp;&nbsp;&nbsp;
										</label>
									</volist>
								</div>

							</div>
						</div>

						<div class="space"></div>
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
</div>
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
    var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
    var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<style>
    .line-span {
        line-height: 34px;
    }
    #main-container{
        width: 955px !important;
    }

</style>
<script type="text/javascript" language="javascript">
function chk_submit(){
	if(!confirm('确认进行修改?')){
		return false;
	}
}
function political(key_id){
    if(key_id == 1){
        $('#party_type').attr('style','display:');
        var party_type = $('#party_type').val();
        if(party_type == 1){
            $('#party').attr('style','display:');
        }
        $('#party_type_msg').attr('style','margin-top: -15px;margin-bottom: 20px;display:');
    }else{
        $('#party_type').attr('style','display:none');
        // document.all.party.value="0"
        $('#party_type').val("0");
        $('#party').attr('style','display:none');
        $('#party').val("0");
        $('#party_type_msg').attr('style','margin-top: -15px;margin-bottom: 20px;display:none');
    }
}
function political_type(){
    var party_type = $('#party_type').val();
    if(party_type == 1){
        $('#party').attr('style','display:');
    }else{
        $('#party').attr('style','display:none');
        // document.all.party.value="0"
        $('#party').val("0");
    }
}



$('#layer_room').change(function(){
	$('#layer_num').val($(this).find(':selected').data('layer'));
	$('#room_num').val($(this).find(':selected').data('room'));
	$('#vacancy_id').val($(this).find(':selected').val());
})
</script>
<include file="NewStreetPublic:footer"/>