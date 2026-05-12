<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('User/index')}">业主管理</a>
			</li>
			<li class="active">业主信息设置</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
                .line-span {
                    line-height: 29px;
                }
                .packageaskhelp{
                    background-color: #e6e6e6;
                    border-radius: 10px;
                    padding: 0 4px;
                    margin-left: 5px;
                    cursor: pointer;
                    position: relative;
                    left: 0;
                    font-size: 14px;
                    line-height: 29px;
                }
                .layui-layer-tips .layui-layer-content{
                    background-color: #ffffff;
                    color: #939192;
                }
                .attachment-toolbar{
                    margin: 10px 0 15px;
                }
                .attachment-toolbar .form_tips{
                    margin-left: 10px;
                    color: #f5222d;
                }
                .attachment-item{
                    border: 1px solid #e5e5e5;
                    background: #fafafa;
                    padding: 15px 15px 5px;
                    margin-bottom: 15px;
                }
                .attachment-item .form-group{
                    margin-bottom: 12px;
                }
                .attachment-title-input{
                    width: 320px;
                    height: 34px;
                    line-height: 34px;
                    padding: 6px 12px;
                    border: 1px solid #d5d5d5;
                    border-radius: 4px;
                    vertical-align: middle;
                }
                .attachment-title-action{
                    margin-left: 10px;
                    vertical-align: middle;
                }
                .attachment-file-input{
                    display: inline-block;
                    height: 34px;
                    vertical-align: middle;
                }
                .attachment-item .form_tips{
                    display: inline-block;
                    margin-left: 12px;
                    vertical-align: middle;
                    color: #f5222d;
                }
                .attachment-file-list,
                .attachment-selected-list{
                    margin-top: 10px;
                }
                .attachment-file-row{
                    display: inline-block;
                    margin: 0 16px 10px 0;
                }
                .attachment-file-tag{
                    display: inline-block;
                    padding: 6px 10px;
                    margin: 0 4px 0 0;
                    border: 1px solid #d5d5d5;
                    background: #ffffff;
                    border-radius: 4px;
                    line-height: 20px;
                }
                .attachment-file-tag a{
                    color: #428bca;
                }
                .remove-attachment-file{
                    display: inline-block;
                    margin-left: 6px;
                    color: #d15b47;
                    cursor: pointer;
                }
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
                            <li class="active">
                                <a href="#basicinfo" data-toggle="tab">基本信息</a>
                            </li>
							<li>
                                <a href="#txtpwd" data-toggle="tab">业主资料</a>
                            </li>
                            <if condition="$new_charge_time neq 1">
                            <li>
                                <a href="#txtstore" data-toggle="tab" class="about">相关费用</a>
                            </li>
                            </if>
                            <li>
                                <a href="#infotagging" data-toggle="tab">信息标注</a>
                            </li>
                            <li>
                                <a href="#userLabel" data-toggle="tab">用户标签</a>
                            </li>
                            <li>
                                <a href="#attachments" data-toggle="tab">附件</a>
                            </li>
                            <li>
                                <a href="#audit" data-toggle="tab">审核流程</a>

                            </li>
						</ul>
					</div>
					<form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('User/edit')}" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<input  name="pigcms_id" type="hidden"  value="{pigcms{$info.pigcms_id}"/>
						<input  name="usernum" type="hidden"  value="{pigcms{$info.usernum}"/>
						<input type="hidden" name="page" value="{pigcms{$_GET['page']}" />
						<input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}" />
						<input type="hidden" name="attachment_auto_save" id="attachment_auto_save" value="0" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">

                                <if condition="$info['bind_number']">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="bind_number">用户编号</label></label>
                                        <input class="col-sm-2" size="20" id="bind_number" type="text" readonly  value="{pigcms{$info.bind_number}"/>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="usernum">用户编号</label></label>
                                        <input class="col-sm-2" size="20" id="usernum"  value="{pigcms{$info.usernum}" type="text" readonly>
                                    </div>
                                </if>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="name">业主名称</label></label>
									<input class="col-sm-2" size="20" name="name" id="user_name" type="text" value="{pigcms{$info.name}"/>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="phone">手机号：</label></label>
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
                                    <label class="col-sm-1"><label for="id_card">身份证号：</label></label>
                                    <input class="col-sm-2" size="20" name="id_card" id="id_card" type="text" value="{pigcms{$info.id_card}"/>
                                    <button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="get_idcard" onclick="get_idcard_func()">
										读取身份证信息
									</button>
									<span class="line-span ">&nbsp;填写后该住户可通过身份证进入门禁</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="ic_card">IC卡号：</label></label>
                                    <input class="col-sm-2" size="20" name="ic_card" id="ic_card" type="text" <if condition="isset($_GET['ic_card'])"> value="{pigcms{$_GET['ic_card']}" <else /> value="{pigcms{$info.ic_card}" </if> />
									<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="rfid_get_card" onclick="rfid_get_card_func()">
										<i class="ace-icon fa fa-check bigger-110"></i>
										RFID读卡已启动
									</button>
                                    <if  condition="$readCard_denake eq true ">
                                    <a href="JavaScript:;" onclick="readCard()" style="color: #0699e5;text-decoration: underline;">读取卡号</a>
                                    </if>
									<span class="line-span ">&nbsp;可不填，填写后该住户可通过IC卡进入门禁</span>
                                </div>

                                <if condition="$huizhisq eq true">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="nmv_card">非机动车卡号：</label></label>
                                        <input class="col-sm-2" size="20" maxlength="10" onkeyup="nmvCard()" name="nmv_card" id="nmv_card" type="text" value="{pigcms{$info.nmv_card}"/>
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
                                <if condition="$userOpenDoorDay">
                                     <div class="form-group door_day">
                                        <label class="col-sm-1"><label for="open_door_day">欠物业费延期开门天数</label></label>
                                        <input class="col-sm-1" size="10" name="open_door_day" id="open_door_day" type="number" value="{pigcms{$info.open_door_day}" /> 天
                                        <span class="form_tips" style="color:red;">（当前用户开门服务到期时间 {pigcms{$info.property_endtime|date='Y-m-d',###}）</span>
                                    </div>
                                </if>
                                <if condition="$new_charge_time eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="property_time">缴费时间</label></label>
                                        <input type="text" name="property_time" class="col-sm-2" value="{pigcms{$info.property_starttime|date='Y-m-d',###} -- {pigcms{$info.property_endtime|date='Y-m-d',###}" readonly />
                                        <span class="packageaskhelp propertyTime">?</span>
                                    </div>
                                    
                                    <!--
                                   <div class="form-group">
                                        <label class="col-sm-1"><label>缴费时间</label></label>
                                        <input type="text" name="property_starttime" id="str_property_starttime" class="input-text" value="{pigcms{$info.property_starttime|date='Y-m-d',###}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd',minDate:'{pigcms{$info.min_service_start_time}',maxDate:'{pigcms{$info.max_service_end_time}'})" placeholder="缴费开始时间"/>
                                        &nbsp;—&nbsp;
                                        <input type="text" name="property_endtime" id="str_property_endtime" class="input-text" value="{pigcms{$info.property_endtime|date='Y-m-d',###}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd',minDate:'{pigcms{$info.min_service_start_time}',maxDate:'{pigcms{$info.max_service_end_time}'})" placeholder="缴费结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <span class="form_tips">业主缴费时间只能在房间的物业服务时间范围内，请按照实际缴费时间填写。</span>
                                    </div>
                                    -->
                                </if>

								<div class="form-group">
									<label class="col-sm-1"><label for="floor_name">住址：</label></label>
                                    <p style="line-height:30px; color:#666">
										{pigcms{$room_diy_name}
                                    </p>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="housesize">房子平方</label></label>
									{pigcms{$info.housesize}
									<span class="form_tips">m²</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="memo">备注</label></label>
									<textarea class="col-sm-2" size="10" name="memo" id="memo" />{pigcms{$info.memo}</textarea>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="unittype">房屋类型</label></label>
                                    <if condition='$info["house_type"] eq 1'>
                                        <div class="shopNameDiv">住宅</div>
                                        <elseif condition='$info["house_type"] eq 2' />
                                        <div class="shopNameDiv">商铺</div>
                                        <else />
                                        <div class="shopNameDiv">办公</div>
                                    </if>
								</div>
								
								<if condition='$info.type gt 0'>
									<div class="form-group">
										<label class="col-sm-1"><label for="unittype">关系</label></label>
										<input class="col-sm-2" size="20" <if condition='$info["type"] eq 1'>value="家人"<elseif condition='$info["type"] eq 2' />value="租客"</if> type="text" style="border:none;background:white!important;" readonly="readonly">
									</div>
								</if>


								<if condition="$now_house['write_iccard']">
									<div class="form-group">
										<label class="col-sm-1" for="door_control">门禁控制</label>

										<label style="padding-left:0px;padding-right:20px;">
											<input type="radio" name="door_control" value="0" class="ace" <if condition="$info['door_control'] eq 0">checked="checked"</if>>
											<span class="lbl" style="z-index: 1" >机动车道</span>
										</label>

										<label style="padding-left:0px;padding-right:20px;">
											<input type="radio" name="door_control" value="1" class="ace" <if condition="$info['door_control'] eq 1">checked="checked"</if>>
											<span class="lbl" style="z-index: 1">行人通道</span>
										</label>
										
										<label style="padding-left:0px;padding-right:20px;">
											<input type="radio" name="door_control" value="0,1" class="ace" <if condition="$info['door_control'] eq '0,1'">checked="checked"</if>>
											<span class="lbl" style="z-index: 1" >全部通道</span>
										</label>
									</div>
								</if>
								
								<div class="form-group">
									<label class="col-sm-1" for="park_flag">车位信息</label>
									<if condition="$position_list['list']">
										<table class="table table-striped table-bordered table-hover"  style="width: 40%">
											 <thead>
		                                        <tr>
		                                            <th width="5%">车库</th>
		                                            <th width="5%">车位编号</th>
		                                            <th width="5%">车位面积</th>
                                                    <th width="5%">车位类型</th>
                                                    <th width="5%">所属母车位</th>
		                                            <th width="5%">备注</th>
		                                            <th width="5%">操作</th>
		                                        </tr>
		                                    </thead>

											<volist name="position_list['list']" id="vo">
												<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <td>{pigcms{$vo.garage_num}</td>
                                                    <td>{pigcms{$vo.position_num}</td>
                                                    <td>{pigcms{$vo.position_area}</td>
                                                    <td>{pigcms{$vo.children_type_txt}</td>
                                                    <td>{pigcms{$vo.parent_position_num}</td>
                                                    <td>{pigcms{$vo.position_note}</td>
                                                    <td>
                                                        <a class="label label-sm label-info" href="javascript:void(0);" onclick="unbind('{pigcms{$vo.bind_id}',1)">解绑</a>
                                                    </td>
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
									<label class="col-sm-1" for="park_flag">车辆信息</label>
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
                                                    <!--		                                            <th width="5%">车辆排量</th>-->
		                                            <th width="5%">操作</th>
		                                        </tr>
		                                    </thead>

											<tbody id="carList">
                                                <volist name="car_list" id="vo">
                                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                        <td>{pigcms{$vo.position_num}</td>
                                                        <td>{pigcms{$vo.province}{pigcms{$vo.car_number}</td>
                                                        <td>{pigcms{$vo.car_stop_num}</td>
                                                        <td>{pigcms{$vo.car_user_name}</td>
                                                        <td>{pigcms{$vo.car_user_phone}</td>
                                                    <!--                                                    <td>{pigcms{$vo.car_displacement}</td>-->
                                                        <td>
                                                            <a class="label label-sm label-info" href="javascript:void(0);" onclick="unbind('{pigcms{$vo.id}',2)">解绑</a>
                                                        </td>
                                                    </tr>
                                                </volist>
                                            </tbody>
                                        </table>
                                    </span>
								</div>
                                
                                <if condition="$showArr && $showArr[0]">
                                    <volist name="showArr" id="items">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="showArr">{pigcms{$items['title']}</label></label>
                                            {pigcms{$items.value}
                                        </div>
                                    </volist>
                                </if>
                                
							</div>
                             <if condition="$new_charge_time neq 1">
							<div class="tab-pane " id="txtstore">
								<div class="form-group">
									<label class="col-sm-1"><label for="water_price">水费总欠费</label></label>
									<input class="col-sm-2" size="10" name="water_price" id="water_price" type="text" readonly  value="{pigcms{$info.water_price|floatval=###}"/>
									<span class="form_tips">{pigcms{$config['Currency_txt']} （支持两位小数）</span>
                                    <img src="{pigcms{$static_public}images/edit.png" onclick="edit_price({pigcms{$_GET['pigcms_id']},1)" style="height: 20px;width: 20px">
                                    &nbsp;&nbsp;&nbsp;
                                    <img src="{pigcms{$static_public}images/record.png" onclick="record({pigcms{$_GET['pigcms_id']},1)" style="height: 15px;width: 15px">
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="electric_price">电费总欠费</label></label>
                                    <input class="col-sm-2" size="10" name="electric_price" id="electric_price" type="text" readonly  value="{pigcms{$info.electric_price|floatval=###}"/>
									<span class="form_tips">{pigcms{$config['Currency_txt']} （支持两位小数）</span>
                                    <img src="{pigcms{$static_public}images/edit.png" onclick="edit_price({pigcms{$_GET['pigcms_id']},2)" style="height: 20px;width: 20px">
                                    &nbsp;&nbsp;&nbsp;
                                    <img src="{pigcms{$static_public}images/record.png" onclick="record({pigcms{$_GET['pigcms_id']},2)" style="height: 15px;width: 15px">
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="gas_price">燃气费总欠费</label></label>
									<input class="col-sm-2" size="10" name="gas_price" id="gas_price" type="text" readonly value="{pigcms{$info.gas_price|floatval=###}"/>
									<span class="form_tips">{pigcms{$config['Currency_txt']} （支持两位小数）</span>
                                    <img src="{pigcms{$static_public}images/edit.png" onclick="edit_price({pigcms{$_GET['pigcms_id']},3)" style="height: 20px;width: 20px">
                                    &nbsp;&nbsp;&nbsp;
                                    <img src="{pigcms{$static_public}images/record.png" onclick="record({pigcms{$_GET['pigcms_id']},3)" style="height: 15px;width: 15px">
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="park_price">停车费总欠费</label></label>
									<input class="col-sm-2" size="10" name="park_price" id="park_price" type="text" readonly  value="{pigcms{$info.park_price|floatval=###}"/>
									<span class="form_tips">{pigcms{$config['Currency_txt']} （支持两位小数）</span>
                                    <img src="{pigcms{$static_public}images/edit.png" onclick="edit_price({pigcms{$_GET['pigcms_id']},4)" style="height: 20px;width: 20px">
                                    &nbsp;&nbsp;&nbsp;
                                    <img src="{pigcms{$static_public}images/record.png" onclick="record({pigcms{$_GET['pigcms_id']},4)" style="height: 15px;width: 15px">
								</div>

								<div class="form-group">
									<label class="col-sm-2"><label for="property_starttime">物业服务开始时间</label></label>
									<if condition="$info['property_starttime']">
										<input type="text" name="property_starttime" class="input-text" value="{pigcms{$info.property_starttime|date='Y-m-d',###}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间"/>
										<else/>
										<input type="text" name="property_starttime" class="input-text" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（开始）"/>
									</if>
								</div>

								<div class="form-group">
									<label class="col-sm-2"><label for="property_endtime">物业服务结束时间</label></label>
									<if condition="$info['property_endtime']">
										<input type="text" name="property_endtime" class="input-text" value="{pigcms{$info.property_endtime|date='Y-m-d',###}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间"/>
										<else/>
										<input type="text" name="property_endtime" class="input-text" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="物业服务时间（结束）"/>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="park_flag">收费项</label>
									<if condition="in_array(97,$house_session['menus'])">
										<a class="bind_info btn label-info" href="{pigcms{:U('payment_add',array('pigcms_id'=>$info['pigcms_id']))}">添加收费项</a>
										<else/>
										<button class="btn" disabled="disabled">添加收费项</button>
									</if>
									<div class="grid-view" style="width: 80%;margin-left: 8%" >
										<table class="table table-striped table-bordered table-hover">
											<thead>
											<tr>
												<th width="13%">收费项目</th>
												<th>收费模式</th>
												<th>计量方式</th>
												<th>计量数量</th>
												<th>收费金额</th>
												<th>收费周期</th>
												<th>收费周期上限</th>
												<th>开始时间</th>
												<th>结束时间</th>
												<th>签订周期</th>
												<th>已缴费周期</th>
												<!-- <th>备注</th> -->
												<th>操作</th>
											</tr>
											</thead>
											<tbody class="class-checkbox">
											<if condition="is_array($payment_list)">
												<volist name="payment_list" id="vo">
													<tr>
														<td>
															<if condition="$vo['garage_num']">
																{pigcms{$vo.payment_name}({pigcms{$vo.garage_num}-{pigcms{$vo.position_num})
																<else/>
																{pigcms{$vo.payment_name}
															</if>
														</td>
														<td><div class="tagDiv"><if condition="$vo.pay_type eq 1">固定费用<else/>按金额*计量方式</if></div></td>
														<td>{pigcms{$vo.metering_mode}</td>
														<td>{pigcms{$vo.metering_mode_val}</td>
														<td>{pigcms{$vo.pay_money}</td>
														<td>{pigcms{$vo.pay_cycle} ({pigcms{$cycle_type[$vo['cycle_type']]})&nbsp;/&nbsp;周期</td>
														<td>{pigcms{$vo.max_cycle} &nbsp;周期</td>
														<td>{pigcms{$vo.start_time|date="Y-m-d",###}</td>
														<td>{pigcms{$vo.end_time|date="Y-m-d",###}</td>
														<td>{pigcms{$vo.cycle_sum}</td>
														<td>{pigcms{$vo.paid_cycle}</td>
														<!-- <td>{pigcms{$vo.remarks}</td> -->
														<td>
															<if condition="in_array(114,$house_session['menus'])">
																<a class="label label-sm label-info" href="javascript:void(0);" onclick="payment_del({pigcms{$vo.bind_id},{pigcms{$vo.position_id})">删除</a>
																<else/>
																无
															</if>
														</td>
													</tr>
												</volist>
												<else/>
												<tr><td colspan="11" style="text-align: center; color: red;">暂无收费项</td></tr>

											</if>

											</tbody>
										</table>
									</div>
								</div>
								<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
								<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
								<script>
                                    $(function () {
                                        let page = getUrlParam('page');
                                        if(page == 1){
                                            //$('#myTab').children('li').eq(0).removeClass('active');
                                            $('.about').trigger('click');
                                        }
                                        if(window.location.hash){
                                            var $hashTab = $('#myTab a[href="' + window.location.hash + '"]');
                                            var $hashPane = $(window.location.hash);
                                            if($hashTab.length && $hashPane.length){
                                                $('#myTab li').removeClass('active');
                                                $hashTab.closest('li').addClass('active');
                                                $('.tab-content > .tab-pane').removeClass('active in');
                                                $hashPane.addClass('active in');
                                            }
                                        }
                                        packageaskhelp(".propertyTime",'1、未设置物业（服务开始时间-服务结束时间）业主服务时间无限制；<br/>2、设置物业（服务开始时间-服务结束时间）业主服务时间限制；<br/>3、设置物业服务开始时间，未设置物业服务结束时间，业主服务时间无限制；<br/>4、未设置物业服务开始时间，设置物业服务结束时间，业主服务时间限制；<br/>备注：物业服务时间关联的功能有人脸识别门禁、蓝牙门禁等');
                                    });
                                    function getUrlParam(name) {
                                        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
                                        var r = window.location.search.substr(1).match(reg); //匹配目标参数
                                        if (r != null) return unescape(r[2]); return null; //返回参数值
                                    }
									$('.bind_info').click(function(){
										art.dialog.open($(this).attr('href'),{
											init: function(){
												var iframe = this.iframe.contentWindow;
												window.top.art.dialog.data('iframe_handle',iframe);
											},
                                            close: function () {
											    window.location.reload();
											},
											id: 'handle',
											title:'添加收费项',
											padding: 0,
											width: 1000,
											height: 603,
											lock: true,
											resize: false,
											background:'black',
											button: null,
											fixed: false,
                                            // close: function () {
                                            //     window.location.href = location.protocol + '//' + location.host + '/'+"shequ.php?g=House&c=User&a=edit&pigcms_id={pigcms{$_GET['pigcms_id']}&usernum={pigcms{$_GET['usernum']}&page=1";
                                            // },
											left: '50%',
											top: '5%',
											opacity:'0.4'
										});
										return false;
									});

                                    // 小问号提示
                                    function packageaskhelp (dom,msg){
                                        $(dom).hover(function (event){
                                            if(event.type == 'mouseenter'){
                                                var that = this;
                                                layer.tips(msg, that,{
                                                    tips:3,
                                                    time:0
                                                });
                                            }else{
                                                layer.closeAll();
                                            }
                                        })
                                    }
								</script>

							</div>
                            </if>
							<div class="tab-pane" id="txtpwd">
								<if condition="$dataList">
									<volist name="dataList" id="vo">
										<if condition="$vo['type'] eq 1">
											<!--文本-->
											<div class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
												<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
												<input {pigcms{$vo.is_edit} class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}"/>
												<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
											</div>
										<elseif condition="$vo['type'] eq 2"/>
											<!--单选-->
											<div class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
												<input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
												<input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
												<input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
												<input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
												<select name="{pigcms{$vo.key}[value]" {pigcms{$vo.is_edit}>
													<option value="0">请选择</option>
													<volist name="vo.use_field" id="voo">
														<option <if condition="$info['authentication_field'][$vo['key']]['value'] eq $voo">selected</if> value="{pigcms{$voo}">{pigcms{$voo}</option>
													</volist>
												</select>
												<if condition="$vo['is_must'] eq 1">&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span></if>
											</div>
										<elseif condition="$vo['type'] eq 3"/>
											<!--籍贯-->
											<div id="basicinfo" class="form-group">
												<label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
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
                                                <label class="col-sm-1"><label for="name">{pigcms{$vo.title}：</label></label>
                                                <input type="hidden" name="{pigcms{$vo.key}[type]" value="{pigcms{$vo.type}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[is_must]" value="{pigcms{$vo.is_must}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[key]" value="{pigcms{$vo.key}"/>
                                                <input type="hidden" name="{pigcms{$vo.key}[title]" value="{pigcms{$vo.title}"/>
                                                <input {pigcms{$vo.is_edit} class="col-sm-2" size="20" name="{pigcms{$vo.key}[value]" id="" type="text" value="{pigcms{$info['authentication_field'][$vo['key']]['value']}" onfocus="WdatePicker({isShowClear:false, minDate: '1900-01-01', readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
<!--                                                <input type="text" name="{pigcms{$vo.key}[value]"  value=""  onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="日期"/>-->

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
                                            <input type="radio" name="user_political_affiliation" value="{pigcms{$key}" class="ace" onclick="political({pigcms{$key})" <if condition="$user_tag['user_label_arr']['user_political_affiliation'] eq $key">checked="checked"</if> >
                                            <span class="lbl" style="z-index: 1">{pigcms{$aff}</span>&nbsp;&nbsp;&nbsp;&nbsp;
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
                                    <span id="party_type_msg" <if condition="$user_tag['user_label_arr']['user_political_affiliation'] neq 1"> style="display: none" </if>> 社区党员是指：组织关系在本社区的党员；居住地党员是指：组织关系不在社区，但居住在本社区的党员，其中包括流动党员</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_special_groups_title}：</label></label>
                                    <volist name="user_tag.user_special_groups_arr" id="special">
                                        <label>
                                            <input type="checkbox" name="user_special_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_tag['user_label_arr']['user_special_groups'])">checked="checked"</if> >
                                            <span class="lbl" style="z-index: 1">{pigcms{$special}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>
                                    </volist>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_focus_groups_title}：</label></label>
                                    <volist name="user_tag.user_focus_groups_arr" id="focus">
                                        <label>
                                            <input type="checkbox" name="user_focus_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_tag['user_label_arr']['user_focus_groups'])">checked="checked"</if> >
                                            <span class="lbl" style="z-index: 1">{pigcms{$focus}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                        </label>
                                    </volist>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">{pigcms{$user_tag.user_vulnerable_groups_title}：</label></label>
                                    <volist name="user_tag.user_vulnerable_groups_arr" id="vulnerable">
                                        <label>
                                            <input type="checkbox" name="user_vulnerable_groups[]" value="{pigcms{$key}" class="ace" <if condition="in_array($key,$user_tag['user_label_arr']['user_vulnerable_groups'])">checked="checked"</if> >
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
                                                <input type="radio" value="{pigcms{$vo.id}" name="political_outlook" <if condition="$vo.is_checked eq true"> checked </if> >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">特殊人群：</label></label>
                                        <volist name="user_label.special_population" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="special_population[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">重点人群：</label></label>
                                        <volist name="user_label.key_population" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="key_population[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                    <!--复选框-->
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">关怀对象：</label></label>
                                        <volist name="user_label.care_object" id="vo">
                                            <label class="col-sm-1" style="padding-left:0;padding-right:20px;">
                                                <input type="checkbox" value="{pigcms{$vo.id}" name="care_object[{pigcms{$vo.id}]" <if condition="$vo.is_checked eq true"> checked </if> >
                                                <span style="z-index: 1">{pigcms{$vo.label_name}</span>
                                            </label>
                                        </volist>
                                    </div>
                                </if>
							</div>
                            <div class="tab-pane" id="userLabel">
                                <if condition="$user_tag['house_custom_label_arr']">
                                    <volist name="user_tag.house_custom_label_arr" id="v1">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="name">{pigcms{$v1.name}：</label></label>
                                            <notempty name="v1.children">
                                                <volist name="v1.children" id="v2">
                                                    <label>
                                                        <input type="checkbox" name="user_label_groups[{pigcms{$v2.pid}][]" value="{pigcms{$v2.id}" class="ace"  <if condition="$user_tag['user_custom_label_arr'] && in_array($v2['id'],$user_tag['user_custom_label_arr'])"> checked </if>>
                                                        <span class="lbl" style="z-index: 1">{pigcms{$v2.name}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                                    </label>
                                                </volist>
                                            </notempty>
                                        </div>
                                    </volist>
                                </if>
                            </div>
                            <div class="tab-pane" id="attachments">
                                <div class="attachment-toolbar">
                                    <span class="form_tips">附件字段取自“添加档案字段”里配置的图片/PDF类型，可为空，支持多次保存补传，单个文件不能超过20M。</span>
                                </div>
                                <div id="attachmentContainer">
                                    <if condition="$attachment_field_list">
                                        <volist name="attachment_field_list" id="attachment">
                                            <div class="attachment-item" data-key="{pigcms{$attachment.key}" data-field-title="{pigcms{$attachment.title|htmlspecialchars=###}" data-type-name="{pigcms{$attachment.type_name|htmlspecialchars=###}" data-allow-exts="{pigcms{$attachment.allow_exts_text}">
                                                <div class="form-group">
                                                    <label class="col-sm-1"><label>{pigcms{$attachment.title}</label></label>
                                                    <div class="col-sm-10">
                                                        <input type="file" class="attachment-file-input js-attachment-file" name="attachment_files_{pigcms{$attachment.key}[]" accept="{pigcms{$attachment.accept}" multiple />
                                                        <span class="form_tips">仅支持 {pigcms{$attachment.type_name}</span>
                                                        <div class="attachment-selected-list js-selected-files"></div>
                                                        <div class="attachment-file-list">
                                                            <volist name="attachment.files" id="file">
                                                                <div class="attachment-file-row existing-attachment-file">
                                                                    <span class="attachment-file-tag"><a href="{pigcms{$file.file_url_full}" target="_blank">{pigcms{$file.original_name|htmlspecialchars=###}</a></span>
                                                                    <span class="remove-attachment-file js-remove-existing-file">删除</span>
                                                                    <input type="hidden" name="attachment_existing_url[{pigcms{$attachment.key}][]" value="{pigcms{$file.file_url|htmlspecialchars=###}" />
                                                                    <input type="hidden" name="attachment_existing_name[{pigcms{$attachment.key}][]" value="{pigcms{$file.original_name|htmlspecialchars=###}" />
                                                                    <input type="hidden" name="attachment_existing_size[{pigcms{$attachment.key}][]" value="{pigcms{$file.size}" />
                                                                    <input type="hidden" name="attachment_existing_suffix[{pigcms{$attachment.key}][]" value="{pigcms{$file.suffix|htmlspecialchars=###}" />
                                                                    <input type="hidden" name="attachment_existing_time[{pigcms{$attachment.key}][]" value="{pigcms{$file.add_time}" />
                                                                </div>
                                                            </volist>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </volist>
                                        <else/>
                                        <div class="form-group">
                                            <div class="col-sm-offset-1 col-sm-10">
                                                <span class="form_tips">请先到“添加档案字段”中新增图片或 PDF 类型字段。</span>
                                            </div>
                                        </div>
                                    </if>
                                </div>
                                <if condition="$legacy_attachment_list">
                                    <div class="attachment-toolbar">
                                        <span class="form_tips">历史附件为旧版手工标题数据，当前保留只读展示；如需迁移，可重新上传到上面的对应字段。</span>
                                    </div>
                                    <volist name="legacy_attachment_list" id="legacyAttachment">
                                        <div class="attachment-item">
                                            <div class="form-group">
                                                <label class="col-sm-1"><label>{pigcms{$legacyAttachment.title}</label></label>
                                                <div class="col-sm-10">
                                                    <div class="attachment-file-list">
                                                        <volist name="legacyAttachment.files" id="file">
                                                            <div class="attachment-file-tag">
                                                                <a href="{pigcms{$file.file_url_full}" target="_blank">{pigcms{$file.original_name|htmlspecialchars=###}</a>
                                                            </div>
                                                        </volist>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </volist>
                                </if>
                            </div>


                            <div class="tab-pane" id="audit">
                                <if condition="$reply_info">
                                    <div>
                                        <h1 style="font-size: 18px;"><b>申请详情</b></h1>
                                        <p style="margin-top: 6px">申请人：{pigcms{$reply_info['apply_name']}</p>
                                        <p style="margin-top: 6px">申请时间：{pigcms{$reply_info['add_time_txt']}</p>
                                        <p style="margin-top: 6px">修改后服务时间：{pigcms{$reply_info['service_time']}</p>
                                        <p style="margin-top: 6px">申请原因：{pigcms{$reply_info['apply_reason']}</p>
                                        <hr>
                                    </div>
                                    <volist name="reply_detail_info" id="vo">
                                        <div>
                                            <div style="float: left"><img src="/static/images/cancelordercheckauth.png" style="width: 50px"></div>
                                            <div style="float: left;margin-left: 20px;margin-bottom: 10px">
                                                <p style="margin-top: 6px">审批人：{pigcms{$vo['name']}</p>
                                                <p style="margin-top: 6px">审批状态：{pigcms{$vo['status_txt']}</p>
                                                <p style="margin-top: 6px">审批时间：{pigcms{$vo['apply_time_txt']}</p>
                                                <p style="margin-top: 6px">审批说明：{pigcms{$vo['bak']}</p>
                                            </div>
                                            <hr>
                                        </div>
                                    </volist>
                                    <else />
                                    暂无审核的物业服务时间
                                </if>
                            </div>
						</div>
						<script>
                            function activateUserEditTabByHash() {
                                if(!window.location.hash){
                                    return;
                                }
                                var $hashTab = $('#myTab a[href="' + window.location.hash + '"]');
                                var $hashPane = $(window.location.hash);
                                if($hashTab.length && $hashPane.length){
                                    $('#myTab li').removeClass('active');
                                    $hashTab.closest('li').addClass('active');
                                    $('.tab-content > .tab-pane').removeClass('active in');
                                    $hashPane.addClass('active in');
                                }
                            }
                            var car_id = [];
                            var attachmentAutoSubmit = false;
                            var attachmentAutoSubmitting = false;
                            function escapeAttachmentHtml(text) {
                                if (text === null || text === undefined) {
                                    return '';
                                }
                                return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            }
                            function getAttachmentItemConfig(input) {
                                var $item = $(input).closest('.attachment-item');
                                var allowExtsText = String($item.attr('data-allow-exts') || '').toLowerCase();
                                var allowExts = allowExtsText ? allowExtsText.split(',') : [];
                                return {
                                    item: $item,
                                    fieldTitle: $item.attr('data-field-title') || '\u9644\u4ef6',
                                    typeName: $item.attr('data-type-name') || 'PDF/\u56fe\u7247',
                                    allowExts: allowExts
                                };
                            }
                            function validateAttachmentFiles(input) {
                                var config = getAttachmentItemConfig(input);
                                if (!input || !input.files || !input.files.length) {
                                    return true;
                                }
                                for (var i = 0; i < input.files.length; i++) {
                                    var fileName = input.files[i].name || '';
                                    var fileExt = fileName.split('.').pop().toLowerCase();
                                    if ($.inArray(fileExt, config.allowExts) === -1) {
                                        alert('\u3010' + config.fieldTitle + '\u3011\u53ea\u652f\u6301' + config.typeName + '\u683c\u5f0f\uff01');
                                        return false;
                                    }
                                    if (input.files[i].size > 20 * 1024 * 1024) {
                                        alert('\u5355\u4e2a\u9644\u4ef6\u4e0d\u80fd\u8d85\u8fc720M\uff01');
                                        return false;
                                    }
                                }
                                return true;
                            }
                            function renderSelectedAttachmentFiles(input) {
                                var html = [];
                                if (input.files && input.files.length) {
                                    for (var i = 0; i < input.files.length; i++) {
                                        html.push('<div class="attachment-file-tag">' + escapeAttachmentHtml(input.files[i].name) + '</div>');
                                    }
                                }
                                $(input).closest('.attachment-item').find('.js-selected-files').html(html.join(''));
                            }
                            function autoSaveAttachmentFiles(input) {
                                if (!input || !input.files || !input.files.length || attachmentAutoSubmitting) {
                                    return;
                                }
                                attachmentAutoSubmit = true;
                                attachmentAutoSubmitting = true;
                                $('#attachment_auto_save').val('1');
                                $('#edit_form').find('button[type="submit"]').prop('disabled', true);
                                $('#edit_form').trigger('submit');
                            }
                            $(function (){
                                activateUserEditTabByHash();
                                setTimeout(activateUserEditTabByHash, 0);
                                var url = "{pigcms{:U('Unit/vehicle_management_user_list')}";
                                $.post(url,{'pigcms_id': "{pigcms{$info.pigcms_id}"},function(result) {
                                    if (result['status'] == 1) {
                                        if(result.list.length < 1){
                                            $(".car-table").hide()
                                        }
                                        car_id = result.list;
                                        console.log(car_id);
                                        var carId = car_id.join(',');
                                        $("#car_id").val(carId)
                                    } else {
                                        alert(result['msg']);
                                    }
                                },'json');
                            })
                            $(document).on('click', '.js-remove-existing-file', function () {
                                $(this).closest('.existing-attachment-file').remove();
                            });
                            $(document).on('change', '.js-attachment-file', function () {
                                if (!validateAttachmentFiles(this)) {
                                    this.value = '';
                                    $(this).closest('.attachment-item').find('.js-selected-files').html('');
                                    return false;
                                }
                                renderSelectedAttachmentFiles(this);
                                autoSaveAttachmentFiles(this);
                            });
							function payment_del(bind_id,position_id) {
								var payment_del_url = "{pigcms{:U('payment_del')}";
								var pigcms_id = "{pigcms{$_GET['pigcms_id']}";
								var data = {bind_id:bind_id,pigcms_id:pigcms_id};
								if (position_id) {
									data = {bind_id:bind_id,position_id:position_id};
								}
								layer.confirm('您确定要删除这条收费项吗？', {
									btn: ['确定','取消'] //按钮
								}, function(){
									$.post(payment_del_url,data,function(data){
										if(data.error == 1){
											alert(data.msg);
											location.href = location.href;
										}else{
											alert(data.msg);
										}
									},'json');
								});
							}
							$(function () {
                                var check = $('input[name="political_outlook"]:checked').val();
                                if(check == null || check == 'undefined'){
                                    // 指定选中第一个按钮
                                    $('input[name="political_outlook"]:first').prop('checked',true);
                                }
                            })

							// 添加车辆
                            function addCar() {
                                var single = "{pigcms{$info['single_id']}";
                                var floor_id = "{pigcms{$info['floor_id']}";
                                var layer_id = "{pigcms{$info['layer_id']}";
                                var vacancy_id = "{pigcms{$info['vacancy_id']}";
                                var user_id = "{pigcms{$info['pigcms_id']}";
                                var url = "{pigcms{:U('Unit/vehicle_add')}"+"&source=user&single="+single+'&floor_id='+floor_id+'&layer_id='+layer_id+'&vacancy_id='+vacancy_id+'&user_id='+user_id;
                                var page = layer.open({
                                    type: 2,
                                    area: ['1150px', '650px'],
                                    fixed: true, //不固定
                                    // maxmin: true,
                                    title: '添加车辆',
                                    content: url,
                                    end: function(index){
                                        carList()
                                    }
                                });
                                layer.style(page, {
                                    top: '20px'
                                });
                            }
                            // 车辆列表
                            function carList(){
                                var url = "{pigcms{:U('Unit/vehicle_management_user_list')}";
                                $.post(url,{'pigcms_id': "{pigcms{$info.pigcms_id}"},function(result){
                                    if(result['status'] == 1){
                                        var car_list = result.data;
                                        console.log(car_list)
                                        if(car_list.length > 0){
                                            $(".car-table").show()
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
                                                '<td> <a class="label label-sm label-info" href="javascript:void(0);" onclick="unbind('+ car_list[i].id +',2)">解绑</a> </td>'+
                                                '</tr>';
                                        }
                                        $('#carList').html(html);

                                        var carId = car_id.join(',');
                                        $("#car_id").val(carId)
                                    }else{
                                        alert(result['msg']);
                                    }
                                },'json');
                            }
						</script>

						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" <if condition="!in_array(93,$house_session['menus'])">disabled="disabled"</if>>
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
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=05"></script>
<script type="text/javascript" language="javascript">
function chk_submit(){
	/* var ex = /^\d+$/;
	if(!ex.test($('input[name="property_month_num"]').val())){
		alert('物业服务周期必须为整数！');
		return false;
	}
	
	if(!ex.test($('input[name="presented_property_month_num"]').val())){
		alert('赠送物业时间必须为整数！');
		return false;
	} 
	
	if(!confirm('确认进行修改，修改后会微信通知业主。')){
		return false;
	}*/
	
	if(!attachmentAutoSubmit && !confirm('确认进行修改?')){
		return false;
	}
    var attachmentValidateError = false;
    $('#attachmentContainer .js-attachment-file').each(function () {
        if (!validateAttachmentFiles(this)) {
            $(this).focus();
            attachmentValidateError = true;
            return false;
        }
    });
    if (attachmentValidateError) {
        return false;
    }
    layer.load(0, {shade: [0.6,'#fff']});
    return true;
}

function edit_price(pigcms_id,type){
    var url = "{pigcms{:U('User/edit_fee')}";
    if (pigcms_id && pigcms_id>0) {
        url += "&pigcms_id=" + pigcms_id+'&type='+type+"&usernum={pigcms{$_GET['usernum']}";
    }
    var index = layer.open({
        type: 2,
        title:'编辑用户费用',
        content: url,
        area: ['950px', '455px'],
        maxmin: false
    });
    layer.style(index, {
        top: '20px'
    });
}

function record(pigcms_id,type){
    var url = "{pigcms{:U('User/price_edit_record')}";
    if (pigcms_id && pigcms_id>0) {
        url += "&pigcms_id=" + pigcms_id+'&type='+type+"&usernum={pigcms{$_GET['usernum']}";
    }
    var index = layer.open({
        type: 2,
        title:'更改记录',
        content: url,
        area: ['950px', '455px'],
        maxmin: false
    });
    layer.style(index, {
        top: '20px'
    });
}

 function unbind(bind_id,type){
    if(!bind_id || bind_id==''){
        layer.msg('请选择您要解绑的用户!',{icon: 2});
        return false;
    }
    var url;
    if (type==1) {
   		url = "{pigcms{:U('Unit/unbind_position')}";
    }else if(type==2){
   		url = "{pigcms{:U('Unit/unbind_car')}";
    }

    layer.confirm('确认解绑？', {
      btn: ['确定','取消'] //按钮
    }, function(){
        $.post(url,{'bind_id':bind_id},function(data){
            if(data.code == 1){
                layer.msg(data.msg,{icon: 1},function(){
                    location.reload();
                });
            }
            if(data.code == 2){
                layer.msg(data.msg,{icon: 2},function(){
                    location.reload();
                });
            }
        },'json');
    }, function(){
      
    });  

    
}

/*function closeAll(data) {
    console.log('data',data)
}*/

var url = "{pigcms{:U('ajax_get_layer')}";
$('#floor_id').change(function(){
	var floor_id = $(this).val();
	$.post(url,{'floor_id':floor_id},function(data){
		if(data['status'] == 0){
			alert(data.msg);
		}else{
			var list = data['list'];
			var shtml = '<option>请选择</option>';
			
			if(list){
				for(var i in list){
					shtml += '<option data-layer="'+list[i]['layer']+'" data-room="'+list[i]['room']+'" value="'+list[i]['pigcms_id']+'">'+list[i]['layer']+'&nbsp;&nbsp;--&nbsp;&nbsp;'+list[i]['room']+'</option>'
				}
			}
			$('#layer_room').html(shtml)
		}
	},'json')
});

$('#layer_room').change(function(){
	$('#layer_num').val($(this).find(':selected').data('layer'));
	$('#room_num').val($(this).find(':selected').data('room'));
	$('#vacancy_id').val($(this).find(':selected').val());
})

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
// 限制非机动车卡号不能使用中文
function nmvCard(){
    var nmv_card = $("#nmv_card").val();
    if(nmv_card.length > 0 && !(/^[0-9a-zA-Z]*$/.test(nmv_card))) {
        $("#nmv_card").val('');
        alert('卡号只能包含数字和英文')
    }
}

//解析身份证号码
$('#id_card').bind('input propertychange', function() {
    var is_sex=false,is_birthday=false;
    if($("select[name='sex[value]']").length > 0){
        is_sex=true;
    }
    if($("input[name='birthday[title]").length > 0){
        is_birthday=true;
    }
    if(!is_sex && !is_birthday){
        return false;
    }
    $.post("{pigcms{:U('getIdCardAnalysis')}",{'id_card':$(this).val()},function(rr){
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
        $('#party_type').attr('style','display:');
        var party_type = $('#party_type').val();
        if(party_type == 1){
            $('#party').attr('style','display:');
        }
        $('#party_type_msg').attr('style','display:');
    }else{
        $('#party_type').attr('style','display:none');
        // document.all.party.value="0"
        $('#party_type').val("0");
        $('#party').attr('style','display:none');
        $('#party').val("0");
        $('#party_type_msg').attr('style','display:none');
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

</script>

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
	
<include file="Public:footer"/>
