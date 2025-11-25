<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 小区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
    <style type="text/css">
    .green{ color:green}
    </style>
	<body>
		<form id="myform" action="{pigcms{:U('bind_other')}" method='post' onsubmit="return chk_submit()">
			<input type="hidden" name="pigcms_id" value="{pigcms{$_GET['pigcms_id']}" >
			<input type="hidden" name="edit" value="{pigcms{$_GET['edit']}" >
			<table>
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>基本信息</b></td>
				</tr>
				<tr>
					<th width="15%">姓名</th>
					<td width="85%">
						<input type="text" id="user_name" name="name" value="{pigcms{$bind_info.name}" style="line-height: 20px;" />
						
					</td>
				</tr>
                <tr>
                    <th width="15%">电话</th>
                    <td width="85%">
                        <input type="text" name="phone" value="{pigcms{$bind_info.phone}" style="    line-height: 20px;" placeholder="手机号码">
                        <font color="red">非必填，但是不能填写和业主相同的号码</font>
                    </td>
                </tr>
                <tr>
                    <th width="15%">身份证号</th>
                    <td width="85%">
                        <input type="text" id="id_card" name="id_card" value="{pigcms{$bind_info.id_card}" style="line-height:20px;" placeholder="身份证号"/>
						<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="get_idcard" onclick="get_idcard_func()">
							读取身份证信息
						</button>
                        <font>填写后该住户可通过身份证进入门禁</font>
                    </td>
                </tr>
                <tr>
                    <th width="15%">IC卡号</th>
                    <td width="85%">
                        <input type="text" name="ic_card" id="ic_card" value="{pigcms{$bind_info.ic_card}" style="line-height:20px;" placeholder="IC卡号"/>
						<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="rfid_get_card" onclick="rfid_get_card_func()">
							<i class="ace-icon fa fa-check bigger-110"></i>
							RFID读卡已启动
						</button>
                        <font>可不填，填写后该住户可通过IC卡进入门禁</font>
                    </td>
                </tr>

                <if condition="$huizhisq">
                    <tr>
                        <th width="15%">非机动车卡号</th>
                        <td width="85%">
                            <input type="text" name="nmv_card" maxlength="10" onkeyup="nmvCard()" id="nmv_card" value="{pigcms{$bind_info.nmv_card}" style="line-height:20px;" placeholder="非机动车卡号"/>
                            <font>填写后该住户可通过非机动车卡进入门禁</font>
                        </td>
                    </tr>
                </if>

                <if condition="$huizhisq eq true">
                    <tr>
                        <th width="15%">物理卡号</th>
                        <td width="85%">
                            <input type="text" name="pile_card_no" maxlength="10"  id="pile_card_no" value="{pigcms{$bind_info.pile_card_no}" style="line-height:20px;" placeholder="物理卡号"/>
                            <font>可不填，填写后该住户可通过物理卡号对电动汽车充电支付</font>
                        </td>
                    </tr>
                </if>
               <if condition="$userOpenDoorDay">
                   <tr>
                        <th width="15%">欠物业费延期开门天数</th>
                        <td width="85%">
                           <input style="line-height:20px;" size="10" name="open_door_day" id="open_door_day" type="number" value="{pigcms{$bind_info.open_door_day}" /> 天
                        </td>
                </if>

				<tr>
					<th width="15%">关系</th>
					<td width="85%">
						<?php if($_GET['bind_type'] == 1){ ?>
							<label style="padding-left:0px;padding-right:20px;">
								<input type="radio" class="ace" value="1" name="type" <if condition="$bind_info.type eq 1">checked="checked"</if>>
								<span style="z-index: 1" class="lbl"><if condition="$config['open_house_user_name']">亲友<else/>家属</if></span>
								<if condition="$relatives_type_info">
									<select name="relatives_type">
										<volist name="relatives_type_info" id="item">
											<option value="{pigcms{$item.relatives_type}" <if condition="$bind_info['relatives_type'] eq $item['relatives_type']">selected=selected</if>>{pigcms{$item.name}</option>
										</volist>
									</select>
								</if>
							</label>
							<label style="padding-left:0px;">
								<input type="radio" class="ace" value="2" name="type" <if condition="$bind_info.type eq 2">checked="checked"</if>>
                                <span style="z-index: 1" class="lbl"><if condition="$house_type neq 1">租客/员工<elseif condition="$config['open_house_user_name']" />租客&社员<else/>租客</if></span>
							</label>

						<?php }else{ ?>
								<if condition="$bind_info['type'] neq 2">
									<label style="padding-left:0px;padding-right:20px;">
										<input type="radio" class="ace" value="1" name="type" <if condition="$bind_info.type eq 1">checked="checked"</if>>
										<span style="z-index: 1" class="lbl"><if condition="$config['open_house_user_name']">亲友<else/>家属</if></span>
										<if condition="$relatives_type_info">
											<select name="relatives_type">
												<volist name="relatives_type_info" id="item">
													<option value="{pigcms{$item.relatives_type}" <if condition="$bind_info['relatives_type'] eq $item['relatives_type']">selected=selected</if>>{pigcms{$item.name}</option>
												</volist>
											</select>
										</if>
									</label>
									<else/>
									<label style="padding-left:0px;">
										<input type="radio" class="ace" value="2" name="type" <if condition="$bind_info.type eq 2">checked="checked"</if>>
										<span style="z-index: 1" class="lbl"><if condition="$config['open_house_user_name']">租客&社员<else/>租客</if></span>
									</label>
								</if>
						<?php } ?>




					</td>
				</tr>
				<if condition="$attribute_list">
					<tr>
						<th width="15%">业主属性</th>
						<td width="85%">
							<select name="attribute">
								<option value="0">请选择业主属性</option>
								<volist name="attribute_list" id="vo">
									<option value="{pigcms{$vo.pigcms_id}" <if condition="$bind_info['attribute'] eq $vo['pigcms_id']">selected=selected</if>>{pigcms{$vo.name}</option>
								</volist>
							</select>
						</td>
					</tr>
				</if>
				<tr>
					<th width="15%">备注</th>
					<td width="85%">
						<textarea name="memo" placeholder="备注">{pigcms{$bind_info.memo}</textarea>
					</td>
				</tr>

                <if condition="$showArr && $showArr[0]">
                    <tr>
                        <td colspan="4" style="padding-left:5px;color:black;"><b>导入数据</b></td>
                    </tr>
                    <volist name="showArr" id="items">
                        <tr>
                            <th width="15%">{pigcms{$items['title']}</th>
                            <td width="85%">
                                {pigcms{$items.value}
                            </td>
                        </tr>
                    </volist>
                </if>



                <tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>资料填写</b></td>
				</tr>
				<if condition="$dataList">
					<volist name="dataList" id="vo">
						<if condition="$vo['type'] eq 1">
							<tr>
								<th width="15%">{pigcms{$vo.title}：</th>
								<td width="85%">
									<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
									<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
									<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
									<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
									<input type="text" name="{pigcms{$vo.key}[value]" value="{pigcms{$bind_info['authentication_field'][$vo['key']]['value']}" style="line-height: 20px;" >
									<if condition="$vo['is_must'] eq 1"><font color="red">*必填项</font></if>
								</td>
							</tr>
						<elseif condition="$vo['type'] eq 2"/>
							<tr>
								<th width="15%">{pigcms{$vo.title}：</th>
								<td width="85%">
									<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
									<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
									<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
									<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
									<select name="{pigcms{$vo.key}[value]" id="{pigcms{$vo.key}_authentication">
										<option value="0">请选择</option>
										<volist name="vo.use_field" id="voo">
											<option <if condition="$bind_info['authentication_field'][$vo['key']]['value'] eq $voo">selected</if> value="{pigcms{$voo}">{pigcms{$voo}</option>
										</volist>
									</select>
									<if condition="$vo['is_must'] eq 1"><font color="red">*必填项</font></if>
								</td>
							</tr>
						<elseif condition="$vo['type'] eq 3"/>
							<tr>
								<th width="15%">{pigcms{$vo.title}：</th>
									<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
									<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
									<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
									<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
<!--									<input type="text" name="{pigcms{$vo.key}[value]" value="{pigcms{$bind_info['authentication_field'][$vo['key']]['value']}" style="    line-height: 20px;" >-->
<!--									<if condition="$vo['is_must'] eq 1"><font color="red">*必填项</font></if>-->
									<td id="choose_street" province_idss="{pigcms{$bind_info['authentication_field'][$vo['key']]['province_idss']}" city_idss="{pigcms{$bind_info['authentication_field'][$vo['key']]['city_idss']}"></td>
									<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>

							</tr>
                            <elseif condition="$vo['type'] eq 4"/>
                            <tr>
                                <th width="15%">{pigcms{$vo.title}：</th>
                                <td width="85%">
                                    <input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
                                    <input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
                                    <input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
                                    <input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
<!--                                    <input type="text" name="{pigcms{$vo.key}[value]" value="{pigcms{$bind_info['authentication_field'][$vo['key']]['value']}" style="    line-height: 20px;" >-->
                                    <input class="col-sm-2" style="line-height: 20px;" size="20" name="{pigcms{$vo.key}[value]" id="{pigcms{$vo.key}_authentication" type="text" value="{pigcms{$bind_info['authentication_field'][$vo['key']]['value']}" onfocus="WdatePicker({minDate: '1900-01-01',isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                                    <if condition="$vo['is_must'] eq 1"><font color="red">*必填项</font></if>
                                </td>
                            </tr>
						</if>
					</volist>
                </if>

                <tr>
                    <td colspan="4" style="padding-left:5px;color:black;"><b>信息标注</b></td>
                </tr>
                <tr class="col-sm-1">
                    <th width="15%">{pigcms{$user_tag.user_political_affiliation_title}：<a style="cursor: default" href="###" title="社区党员是指：组织关系在本社区的党员;居住地党员是指；组织关系不在社区，但居住在本社区的党员，其中包括流动党员"><img style="margin-bottom:-6px;" src="./tpl/System/Static/images/help.gif"></a></th>
                    <td width="85%">
                        <volist name="user_tag.user_political_affiliation_arr" id="aff">
                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                <input type="radio" name="user_political_affiliation" value="{pigcms{$key}"  onclick="political({pigcms{$key})" <if condition="$user_tag['user_label_arr']['user_political_affiliation'] eq $key">checked="checked"</if> >
                                <span style="z-index: 1">{pigcms{$aff}</span>
                            </label>
                        </volist>
                        <select name="party_type" id="party_type" onchange="political_type()" <if condition="$user_tag['user_label_arr']['user_political_affiliation'] neq 1"> style="display: none" </if>>
                            <option value="0" <if condition="$user_tag['user_label_arr']['party_type'] eq 0"> selected </if>>请选择党员属性</option>
                            <option value="1" <if condition="$user_tag['user_label_arr']['party_type'] eq 1"> selected </if>>社区党员</option>
                            <option value="2" <if condition="$user_tag['user_label_arr']['party_type'] eq 2"> selected </if>>居住地党员</option>
                        </select>
                        <select name="user_party_id" id="party" <if condition="$user_tag['user_label_arr']['user_political_affiliation'] neq 1 || $user_tag['user_label_arr']['party_type'] neq 1"> style="display: none" </if>>
                        <volist name="user_tag.user_party_arr" id="vo">
                            <option value="{pigcms{$vo.id}" <if condition="$vo.id eq $user_tag['user_label_arr']['user_party_id']"> selected </if>>{pigcms{$vo.name}</option>
                        </volist>
                        </select>
                    </td>
                </tr>
                <tr class="col-sm-1">
                    <th width="15%">{pigcms{$user_tag.user_special_groups_title}：</th>
                    <td width="85%">
                        <volist name="user_tag.user_special_groups_arr" id="special">
                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                <input type="checkbox" name="user_special_groups[]" value="{pigcms{$key}"  <if condition="in_array($key,$user_tag['user_label_arr']['user_special_groups'])">checked="checked"</if> >
                                <span style="z-index: 1">{pigcms{$special}</span>
                            </label>
                        </volist>
                    </td>
                </tr>
                <tr class="col-sm-1">
                    <th width="15%">{pigcms{$user_tag.user_focus_groups_title}：</th>
                    <td width="85%">
                        <volist name="user_tag.user_focus_groups_arr" id="focus">
                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                <input type="checkbox" name="user_focus_groups[]" value="{pigcms{$key}" <if condition="in_array($key,$user_tag['user_label_arr']['user_focus_groups'])">checked="checked"</if> >
                                <span style="z-index: 1">{pigcms{$focus}</span>
                            </label>
                        </volist>
                    </td>
                </tr>
                <tr class="col-sm-1">
                    <th width="15%">{pigcms{$user_tag.user_vulnerable_groups_title}：</th>
                    <td width="85%">
                        <volist name="user_tag.user_vulnerable_groups_arr" id="vulnerable">
                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                <input type="checkbox" name="user_vulnerable_groups[]" value="{pigcms{$key}" <if condition="in_array($key,$user_tag['user_label_arr']['user_vulnerable_groups'])">checked="checked"</if> >
                                <span style="z-index: 1">{pigcms{$vulnerable}</span>
                            </label>
                        </volist>
                    </td>
                </tr>

                <if condition="$user_label && false">
                    <!--单选-->
                    <tr class="col-sm-1">
                        <th width="15%">政治面貌</th>
                        <td width="85%">
                            <volist name="user_label.political_outlook" id="vo">
                                <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                    <input type="radio" value="{pigcms{$vo.id}" name="political_outlook" <if condition="$vo.is_checked eq true"> checked </if> >
                                    <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                </label>
                            </volist>
                        </td>
                    </tr>

                    <!--复选框-->
                    <tr class="col-sm-1">
                        <th width="15%">特殊人群</th>
                        <td width="85%">
                            <volist name="user_label.special_population" id="vo">
                                <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                    <input type="checkbox" value="{pigcms{$vo.id}" name="special_population[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                    <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                </label>
                            </volist>
                        </td>
                    </tr>

                    <!--复选框-->
                    <tr class="col-sm-1">
                        <th width="15%">重点人群</th>
                        <td width="85%">
                            <volist name="user_label.key_population" id="vo">
                                <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                    <input type="checkbox" value="{pigcms{$vo.id}" name="key_population[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                    <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                </label>
                            </volist>
                        </td>
                    </tr>

                    <!--复选框-->
                    <tr class="col-sm-1">
                        <th width="15%">关怀对象</th>
                        <td width="85%">
                            <volist name="user_label.care_object" id="vo">
                                <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                    <input type="checkbox" value="{pigcms{$vo.id}" name="care_object[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                    <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                </label>
                            </volist>
                        </td>
                    </tr>
                </if>

                <tr>
                    <td colspan="4" style="padding-left:5px;color:black;"><b>用户标签</b></td>
                </tr>

                <if condition="$user_tag['house_custom_label_arr'] ">
                    <volist name="user_tag.house_custom_label_arr" id="v1">
                        <tr class="col-sm-1">
                            <th width="15%">{pigcms{$v1.name}：</th>
                            <td width="85%">
                                <notempty name="v1.children">
                                    <volist name="v1.children" id="v2">
                                        <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                            <input type="checkbox" name="user_label_groups[{pigcms{$v2.pid}][]" value="{pigcms{$v2.id}" <if condition="$user_tag['user_custom_label_arr'] && in_array($v2['id'],$user_tag['user_custom_label_arr'])"> checked </if> >
                                            <span style="z-index: 1">{pigcms{$v2.name}</span>
                                        </label>
                                    </volist>
                                </notempty>
                            </td>
                        </tr>
                    </volist>
                </if>
				<tr>
					<td colspan="4"><button class="chk_express" style=" margin:0 auto; display:block;float:right">保存</button></td>
				</tr>
			 
			</table>
		</form>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
    <script>
        $(function () {
            var check = $('input[name="political_outlook"]:checked').val();
            if(check == null || check == 'undefined'){
                // 指定选中第一个按钮
                $('input[name="political_outlook"]:first').prop('checked',true);
            }
        })
    </script>
    <script src="{pigcms{$static_path}layer/layer.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script type="text/javascript">
		var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
		var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
		var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";


        function chk_submit(){
            layer.load(0, {shade: [0.6,'#fff']});
        }

        // 限制非机动车卡号不能使用中文
        function nmvCard(){
            var nmv_card = $("#nmv_card").val();
            if(/.*[\u4e00-\u9fa5]+.*$/.test(nmv_card)) {
                $("#nmv_card").val('');
                alert('卡号不能有中文')
            }
        }

        function political(key_id){
            if(key_id == 1){
                $('#party_type').attr('style','display:');
                var party_type = $('#party_type').val();
                if(party_type == 1){
                    $('#party').attr('style','display:');
                }
            }else{
                $('#party_type').attr('style','display:none');
                // document.all.party.value="0"
                $('#party_type').val("0");
                $('#party').attr('style','display:none');
                $('#party').val("0");
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
       $('#id_card').on('input propertychange', function() { 
            //进行相关操作
            var id_card=$(this).val();
            id_card=id_card ? $.trim(id_card):'';
            var sex='';
            var birthday='';
            if(id_card && id_card.length>=15){
               var cardarea={11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",21:"辽宁",22:"吉林",23:"黑龙江",31:"上海",32:"江苏",33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",42:"湖北",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",51:"四川",52:"贵州",53:"云南",54:"西藏",61:"陕西",62:"甘肃",63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外"};
                var areacode=id_card.substr(0,2);
                   areacode=parseInt(areacode);
                   if(cardarea[areacode]!=undefined && cardarea[areacode]){
                          if (id_card.length==18) {
                                 sex=id_card.charAt(16)%2==0?"女":"男";
                                 birthdayno = id_card.substr(6,8);
                                 birthday=birthdayno.substring(0,4)+"-"+birthdayno.substring(4,6)+"-"+birthdayno.substring(6,8);
                                 console.log('sex',sex);
                                 console.log('birthday',birthday);
                                 var sex_authentication=$('#sex_authentication').val();
                                 if(sex){
                                      $('#sex_authentication').val(sex);
                                 }
                                 if(birthdayno){
                                    $('#birthday_authentication').val(birthday);
                                 }
                          }else if(false && id_card.length==15){
                                sex=id_card.charAt(14)%2==0?"女":"男";
                                birthdayno = "19"+id_card.substr(6,6);
                                 birthday=birthdayno.substring(0,4)+"-"+birthdayno.substring(4,6)+"-"+birthdayno.substring(6,8);
                          }
                        
                   }
              }
        });

	</script>
	<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=05"></script>

	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	
	<if condition="$now_house['write_iccard']">
		<script language="javascript" src="http://127.0.0.1:8008/YOWOCloudRFIDReader.js"></script>
		<script language="javascript" src="{pigcms{$static_path}/js/YOWOReaderBase.js"></script>

		<script language="javascript" type="text/javascript">
			//demo  http://www.youwokeji.com.cn/CloudReader/Sample1.html
			rfidreader.onResult(function(resultdata) {
				console.log(resultdata);
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
			setTimeout(function(){
				rfid_get_card_func();
			},1000);
			
		</script>
	</if>
	<if condition="$now_house['read_idcard']">
		<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/baseISSObject.js"></script>
		<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/baseISSOnline.js"></script>
		<script language="javascript" src="{pigcms{$static_path}zkidr_idcard/js/common.js"></script>
	</if>
</html>