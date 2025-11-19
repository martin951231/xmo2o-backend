<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cloud"></i>
				<a href="{pigcms{:U('Hardware/index')}">{pigcms{:L_("微硬件")}</a>
			</li>
			<li class="active"><if condition="$_GET['pigcms_id']">{pigcms{:L_("修改")}<else/>{pigcms{:L_("增加")}</if>{pigcms{:L_("打印机")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="alert alert-info" style="margin:10px 0;">
			<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>{pigcms{:L_("注意：如果您的打印机是WiFi打印机类型，就不要填写【绑定手机号】和【绑定账号】这两项，否则用不了！！！")}	
		</div>
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" name="pigcms_id" value="{pigcms{$orderprint['pigcms_id']}"/>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("打印机名称")}</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$orderprint['name']}"/>
									<span class="form_tips">{pigcms{:L_("给打印机取个名称方便菜品在选择打印机更直观")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="print_type">{pigcms{:L_("打印机类别")}</label>
									<select name="print_type" id="print_type">
										<option value="1">{pigcms{:L_("易联云打印机")}</option>
										<option value="2" <if condition="$orderprint['print_type'] eq 2">selected="selected"</if>>{pigcms{:L_("有线打印机")}</option>
										<option value="4" <if condition="$orderprint['print_type'] eq 4">selected="selected"</if>>{pigcms{:L_("飞鹅打印机")}</option>
										<option value="5" <if condition="$orderprint['print_type'] eq 5">selected="selected"</if>>365{pigcms{:L_("云打印机")}</option>
										<option value="6" <if condition="$orderprint['print_type'] eq 6">selected="selected"</if>>{pigcms{:L_("手持POS终端")}</option>
										<if condition="C('customization.trenditiot_printer') eq 1">
											<option value="7" <if condition="$orderprint['print_type'] eq 7">selected="selected"</if>>{pigcms{:L_("大趋云打印机")}</option>
										</if>
									</select>
									<span class="form_tips red">{pigcms{:L_("蓝牙打印机PC端不支持设置，有线打印机已支持80MM、字体大小调整")}</span>
								</div>
								<div class="form-group yilianyun_box">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("绑定手机号")}</label></label>
									<input class="col-sm-2" size="20" name="mp" id="mp" type="text" value="{pigcms{$orderprint['mp']}"/>
									<span class="form_tips red">{pigcms{:L_("绑定手机号和绑定账号只能填写一个（【底部有终端号】需填写手机号，【底部无终端号】需填写绑定账号）")}</span>
								</div>
								<div class="form-group yilianyun_box">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("绑定账号")}</label></label>
									<input class="col-sm-2" size="20" name="username" id="username" type="text" value="{pigcms{$orderprint['username']}"/>
									<span class="form_tips red">{pigcms{:L_("绑定手机号和绑定账号只能填写一个（【底部有终端号】需填写手机号，【底部无终端号】需填写绑定账号）")}</span>
								</div>
								<div class="form-group feie_box" style="display:none;">
									<label class="col-sm-1"><label for="sort">USER</label></label>
									<input class="col-sm-2" size="20" name="feie_user" id="feie_user" type="text" value="{pigcms{$orderprint['feie_user']}"/>
									<span class="form_tips feie_tip" style="display:none;">{pigcms{:L_("在飞鹅管理后台首页查看，")}<a href="http://admin.feieyun.com/" target="_blank">{pigcms{:L_("飞鹅后台跳转链接")}</a></span>
								</div>
								<div class="form-group feie_box" style="display:none;">
									<label class="col-sm-1"><label for="sort">UKEY</label></label>
									<input class="col-sm-2" size="20" name="feie_ukey" id="feie_ukey" type="text" value="{pigcms{$orderprint['feie_ukey']}"/>
									<span class="form_tips feie_tip" style="display:none;">{pigcms{:L_("在飞鹅管理后台首页查看，")}<a href="http://admin.feieyun.com/" target="_blank">{pigcms{:L_("飞鹅后台跳转链接")}</a></span>
								</div>
								<div class="form-group feie_box" style="display:none;">
									<label class="col-sm-1"><label for="sort">语言</label></label>
									<select name="language" id="language">
										<option value="">{pigcms{:L_("通用")}</option>
										<option value="Uyghur" <if condition="$orderprint['language'] eq 'Uyghur'">selected="selected"</if>>{pigcms{:L_("维文")}</option>
									</select>
									<span class="form_tips red">{pigcms{:L_("非维文打印机请选择通用语言")}</span>
								</div>
								<div class="form-group trenditiot_box">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("APPID")}</label></label>
									<input class="col-sm-2" size="20" name="trenditiot_appid" id="trenditiot_appid" type="text" value="{pigcms{$orderprint['trenditiot_appid']}"/>
									<span class="form_tips">{pigcms{:L_("注册成为大趋开放平台用户，获取开发者信息【APPID】")}</span>
								</div>
								<div class="form-group trenditiot_box">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("APPKEY")}</label></label>
									<input class="col-sm-2" size="20" name="trenditiot_appkey" id="trenditiot_appkey" type="text" value="{pigcms{$orderprint['trenditiot_appkey']}"/>
									<span class="form_tips">{pigcms{:L_("注册成为大趋开放平台用户，获取开发者信息【APPKEY】")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("终端号")}</label></label>
									<input class="col-sm-2" size="20" name="mcode" id="mcode" type="text" value="{pigcms{$orderprint['mcode']}"/>
									<span class="form_tips yilianyun_tip">{pigcms{:L_("【底部无终端号】的打印机点击打印机下面的黑色小按钮查看, 【底部有终端号】的打印机在打印机底部查看")}</span>
									<span class="form_tips youxian_tip" style="display:none;">{pigcms{:L_("有线打印机指连接电脑的打印机，通过打印机软件来实现云打印。终端号固定为")} 888888</span>
									<span class="form_tips feie_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【打印机编号(SN)】")}</span>
									<span class="form_tips 365yun_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【机器号】")}</span>
									<span class="form_tips pos_tip" style="display:none;">{pigcms{:L_("在支持的手持POS终端里打开店员APP，首页能看到此信息")}</span>
									<span class="form_tips trenditiot_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【设备编码(SN)】")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("密钥")}</label></label>
									<input class="col-sm-2" size="20" name="mkey" id="mkey" type="text" value="{pigcms{$orderprint['mkey']}"/>
									<span class="form_tips yilianyun_tip">{pigcms{:L_("【底部无终端号】的打印机在注册页面查看, 【底部有终端号】的打印机在打印机底部查看")}</span>
									<span class="form_tips youxian_tip" style="display:none;">{pigcms{:L_("有线打印机软件中添加打印机后，在软件中复制。")}</span>
									<span class="form_tips feie_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【打印机密钥(KEY)】")}</span>
									<span class="form_tips 365yun_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【密钥】")}</span>
									<span class="form_tips pos_tip" style="display:none;">{pigcms{:L_("在支持的手持POS终端里打开店员APP，首页能看到此信息")}</span>
									<span class="form_tips trenditiot_tip" style="display:none;">{pigcms{:L_("在打印机背面查看【绑定码(KEY)】")}</span>
								</div>
								<div class="form-group youxian_box pos_box">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("版本号")}</label></label>
									<input class="col-sm-2" size="20" name="version" id="version" type="text" value="{pigcms{$orderprint['version']}"/>
									<span class="form_tips pos_tip" style="display:none;">{pigcms{:L_("获取打印参数时若获取到版本号则填写在此处，并未获取到版本号则留空。非必填")}</span>
									<span class="form_tips youxian_tip" style="display:none;">{pigcms{:L_("获取打印参数时若获取到版本号则填写在此处，并未获取到版本号则留空。非必填")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("打印份数")}</label></label>
									<input class="col-sm-1" maxlength="2" name="count" id="count" type="text" value="{pigcms{$orderprint['count']|default='1'}"/>
									<span class="form_tips">{pigcms{:L_("每个订单打印几份（最多100）")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("纸张类型")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="float:left;font-size:16px;margin-right:30px;">
											<label><input type="radio" value="0" name="paper" <if condition="$orderprint['paper'] eq 0">checked="checked"</if>/>58mm</label>
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="radio" value="1" name="paper" <if condition="$orderprint['paper'] eq 1">checked="checked"</if>/>80mm</label>
										</div>
									</div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="FoodType_week">{pigcms{:L_("字体大小")}</label>
                                    <div class="col-sm-10" style="margin-top:5px;padding-left:0;">
                                        <div style="float:left;font-size:16px;margin-right:30px;">
                                            <label><input type="radio" value="0" name="is_big" <if condition="$orderprint['is_big'] eq 0">checked="checked"</if>/>{pigcms{:L_("默认")}</label>
                                        </div>
                                        <div style="width:80px;float:left;font-size:16px;">
                                            <label><input type="radio" value="1" name="is_big" <if condition="$orderprint['is_big'] eq 1">checked="checked"</if>/>{pigcms{:L_("中等")}</label>
                                        </div>
                                        <div style="width:180px;float:left;font-size:16px;">
                                            <label><input type="radio" value="2" name="is_big" <if condition="$orderprint['is_big'] eq 2">checked="checked"</if>/>{pigcms{:L_("最大")}</label>
                                        </div>
                                        <span class="form_tips red">{pigcms{:L_("外卖打印模式下，标准与中等格式都是一样的，都为固定格式的部分字体大部分字体小。")}</span>
                                    </div>
                                </div>
								<div class="form-group">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("支持图片")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="float:left;font-size:16px;margin-right:30px;">
											<label><input type="radio" value="1" name="image" <if condition="$orderprint['image'] eq 1">checked="checked"</if>/>{pigcms{:L_("支持")}</label>
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="radio" value="0" name="image" <if condition="$orderprint['image'] eq 0">checked="checked"</if>/>{pigcms{:L_("不支持")}</label>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="store_id">{pigcms{:L_("选择店面")}</label>
									<select name="store_id" id="store_id">
										<volist name="stores" id="store">
										<option value="{pigcms{$store['store_id']}" <if condition="$store['store_id'] eq $orderprint['store_id']">selected="selected"</if>>{pigcms{$store['name']}</option>
										</volist>
									</select>
								</div>
                                <if condition="$shop_offline_staff_print eq 1">
                                <div class="form-group" style="display: none" id="staff_uids" data-id="{pigcms{$orderprint['pigcms_id']}">
                                    <label class="col-sm-1" for="store_id">外卖线下收银打印店员(PC端)</label>
                                    <div class="col-sm-10" style="margin-top:5px;padding-left:0;" id="staff_uids_value">
                                    </div>
                                </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("是否打印日结账单")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="float:left;font-size:16px;margin-right:30px;">
											<label><input type="radio" value="1" name="is_print_report" <if condition="$orderprint['is_print_report']">checked="checked"</if>/>{pigcms{:L_("是")}</label>
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="radio" value="0" name="is_print_report" <if condition="$orderprint['is_print_report'] eq 0">checked="checked"</if>/>{pigcms{:L_("否")}</label>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("主打印机")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="float:left;font-size:16px;margin-right:30px;">
											<label><input type="radio" value="1" name="is_main" <if condition="$orderprint['is_main']">checked="checked"</if>/>{pigcms{:L_("是")}</label>
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="radio" value="0" name="is_main" <if condition="$orderprint['is_main'] eq 0">checked="checked"</if>/>{pigcms{:L_("否")}</label>
										</div>
									</div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("外卖收银台线下订单完成是否打印")}</label>
                                    <div class="col-sm-10" style="margin-top:5px;padding-left:0;">
                                        <div style="float:left;font-size:16px;margin-right:30px;">
                                            <label><input type="radio" value="1" name="shop_offline_order_print" <if condition="$orderprint['shop_offline_order_print'] eq 1">checked="checked"</if>/>{pigcms{:L_("是")}</label>
                                        </div>
                                        <div style="width:180px;float:left;font-size:16px;">
                                            <label><input type="radio" value="0" name="shop_offline_order_print" <if condition="$orderprint['shop_offline_order_print'] eq 0">checked="checked"</if>/>{pigcms{:L_("否")}</label>
                                        </div>
                                    </div>
                                </div>
								
								<if condition="$config.open_foodshop_print_type == 1">
									<div class="form-group">
										<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("打印方式")}</label>
										<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
											<div style="width:100%;font-size:16px;margin-bottom: 15px;">
												<strong>{pigcms{:L_("餐饮行业")}</strong>
											</div>
											<div class="main_type" style="height: 28px;margin-bottom: 15px;">
												<div style="width:180px;float:left;font-size:15px;">
													{pigcms{:L_("触发打印机制")}
												</div>
												<div style="float:left;font-size:16px;">
													<label><input type="checkbox" value="6" name="paid[]" <if condition="in_array(6, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("自动打印预结账单（用户或店员下单成功后）")} </label>&nbsp;&nbsp;
												</div>
												<div style="float:left;font-size:16px;">
													<label><input type="checkbox" value="7" name="paid[]" <if condition="in_array(7, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("自动打印结账单（用户支付成功后，含店员收款成功后）")}</label>&nbsp;&nbsp;
												</div>
											</div>
											<div style="height: 50px;width:100%;" class="is_print_menu">
												<div style="float:left;font-size:15px;width:180px;">
													{pigcms{:L_("是否打印后厨")}
												</div>
												<div class="col-sm-10" style="padding-left:0;">
													<div style="width:180px;float:left;">
														<label><input type="radio" value="1" name="is_print_menu" <if condition="$orderprint['is_print_menu'] eq 1 || !$orderprint['is_print_menu']">checked="checked"</if>/>{pigcms{:L_("是")}</label>
													</div>
													<div style="width:180px;float:left;">
														<label><input type="radio" value="0" name="is_print_menu" <if condition="$orderprint['is_print_menu'] eq 0">checked="checked"</if>/>{pigcms{:L_("否")}</label>
													</div>
												</div>
											</div>
											<div style="height: 50px;width:100%;" class="printWay">
												<div style="float:left;font-size:15px;width:180px;">
													{pigcms{:L_("打印类型")}
												</div>
												<div class="col-sm-10" style="padding-left:0;">
													<div style="width:180px;float:left;">
														<label><input type="radio" value="1" name="print_way" <if condition="$orderprint['print_way'] eq 1 || !$orderprint['print_way']">checked="checked"</if>/>{pigcms{:L_("一菜一单")}</label>
													</div>
													<div style="width:180px;float:left;">
														<label><input type="radio" value="2" name="print_way" <if condition="$orderprint['print_way'] eq 2">checked="checked"</if>/>{pigcms{:L_("整单打印")}</label>
													</div>
													<div style="width:180px;float:left;">
														<label><input type="radio" value="3" name="print_way" <if condition="$orderprint['print_way'] eq 3">checked="checked"</if> />{pigcms{:L_("同时打印")}</label>
													</div>
												</div>
											</div>

	                                        <div style="width:100%;font-size:16px;margin-bottom: 15px;">
												<strong>{pigcms{:L_("其他行业（X1、线下零售、团购）",['X1'=>$config['shop_alias_name']])}</strong>
											</div>
											<div style="width:180px;float:left;font-size:15px;">
												{pigcms{:L_("触发打印机制")}
											</div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="0" name="paid[]" <if condition="in_array(0, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("下单成功后打印")} </label>&nbsp;&nbsp;
											</div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="1" name="paid[]" <if condition="in_array(1, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("支付成功后打印")}</label>&nbsp;&nbsp;
											</div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="5" name="paid[]" <if condition="in_array(5, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("接单成功后打印（X1）",['X1'=>$config['shop_alias_name']])}</label>&nbsp;&nbsp;
											</div>
	                                        <div style="width:180px;float:left;font-size:16px;">
	                                            <label><input type="checkbox" value="2" name="paid[]" <if condition="in_array(2, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("验证消费后打印")}</label>&nbsp;&nbsp;
	                                        </div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="3" name="paid[]" <if condition="in_array(3, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("退款成功后打印")}</label>&nbsp;&nbsp;
											</div>
	                                       <!--  <div style="width:180px;float:left;font-size:16px;">
	                                            <label><input type="checkbox" value="4" name="paid[]" <if condition="in_array(4, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("打印总单")}</label>&nbsp;&nbsp;
	                                        </div> -->
	                                        
										</div>										
									</div>
								<else/>
									<div class="form-group">
										<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("打印类型")}</label>
										<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="0" name="paid[]" <if condition="in_array(0, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("下单成功后打印")} </label>&nbsp;&nbsp;
											</div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="1" name="paid[]" <if condition="in_array(1, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("支付成功后打印")}</label>&nbsp;&nbsp;
											</div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="5" name="paid[]" <if condition="in_array(5, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("接单成功后打印（X1）",['X1'=>$config['shop_alias_name']])}</label>&nbsp;&nbsp;
											</div>
	                                        <div style="width:180px;float:left;font-size:16px;">
	                                            <label><input type="checkbox" value="2" name="paid[]" <if condition="in_array(2, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("验证消费后打印")}</label>&nbsp;&nbsp;
	                                        </div>
											<div style="width:180px;float:left;font-size:16px;">
												<label><input type="checkbox" value="3" name="paid[]" <if condition="in_array(3, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("退款成功后打印")}</label>&nbsp;&nbsp;
											</div>
	                                        <div style="width:180px;float:left;font-size:16px;">
	                                            <label><input type="checkbox" value="4" name="paid[]" <if condition="in_array(4, $orderprint['paid'])">checked="checked"</if>/>{pigcms{:L_("打印总单")}</label>&nbsp;&nbsp;
	                                        </div>
										</div>
									</div>
								</if>

                                <div class="form-group">
                                    <label class="col-sm-1" for="FoodType_week">{pigcms{:L_("外卖预订单打印类型")}</label>
                                    <div class="col-sm-10" style="margin-top:5px;padding-left:0;">
                                        <div style="width:180px;float:left;font-size:16px;">
                                            <label><input type="checkbox" value="1" name="booking_order_print_arr[]" <if condition="$orderprint['booking_order_print']&1">checked="checked"</if>/>{pigcms{:L_("接单时打印")} </label>&nbsp;&nbsp;
                                        </div>
                                        <div style="width:180px;float:left;font-size:16px;">
                                            <label><input type="checkbox" value="2" name="booking_order_print_arr[]" <if condition="$orderprint['booking_order_print']&2">checked="checked"</if>/>{pigcms{:L_("订单提醒时打印")}</label>&nbsp;&nbsp;
                                        </div>
                                       <!-- <div style="width:180px;float:left;font-size:16px;">
                                            <label><input type="checkbox" value="3" name="booking_order_print_arr[]" <if condition="$orderprint['booking_order_print'] eq 3">checked="checked"</if>/>{pigcms{:L_("接单和提醒时都打印")}</label>&nbsp;&nbsp;
                                        </div>-->
                                    </div>
                                </div>



								<div class="form-group">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("新版商城打印类型")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="checkbox" value="1" name="mall_print[]" <if condition="in_array(1, $orderprint['mall_print'])">checked="checked"</if>/>{pigcms{:L_("接单成功后打印")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="checkbox" value="2" name="mall_print[]" <if condition="in_array(2, $orderprint['mall_print'])">checked="checked"</if>/>{pigcms{:L_("支付成功后打印")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="checkbox" value="3" name="mall_print[]" <if condition="in_array(3, $orderprint['mall_print'])">checked="checked"</if>/>{pigcms{:L_("退款成功后打印")}</label>&nbsp;&nbsp;
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("快速买单打印类型")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="checkbox" value="1" name="store_print[]" <if condition="$orderprint['store_print'] && in_array(1, $orderprint['store_print'])">checked="checked"</if>/>{pigcms{:L_("支付成功时打印 ")}</label>&nbsp;&nbsp;
										</div>
									</div>
								</div>
								<if condition="$ok_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:blue;">{pigcms{$ok_tips}</span>				
									</div>
								</if>
								<if condition="$error_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:red;">{pigcms{$error_tips}</span>				
									</div>
								</if>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$(function(){
        var shop_offline_staff_print = {pigcms{$shop_offline_staff_print};
		$('#print_type').change(function(){
			$('.yilianyun_box,.youxian_box,.pos_box,.yilianyun_tip,.youxian_tip,.feie_tip,.feie_box,.365yun_tip,.trenditiot_tip,.trenditiot_box').hide();
			
			var print_type = $('#print_type').val();
			if(print_type == 1){
				$('.yilianyun_box,.yilianyun_tip').show();
			}
			if(print_type == 2){
				$('.youxian_tip').show();
				$('.youxian_box').show();
			}
			if(print_type == 4){
				$('.feie_box,.feie_tip').show();
			}
			if(print_type == 5){
				$('.365yun_tip').show();
			}
			if(print_type == 6){
				$('.pos_tip').show();
				$('.pos_box').show();
			}
			if(print_type == 7){
				$('.trenditiot_tip').show();
				$('.trenditiot_box').show();
			}
		});
		$('#print_type').trigger('change');

		$('input[name=is_main]').change(function(){
			var is_main = $('input[name=is_main]:checked').val();
			var is_print_menu = $('input[name=is_print_menu]:checked').val();
			if(is_main == '1'){
				if (is_print_menu== '1') {
					$('.printWay').show();
				}else{
					$('.printWay').hide();
				}
				$('.main_type').show();
				$('.is_print_menu').show();
			}else{
				$('.main_type').hide();
				$('.printWay').show();
				$('.is_print_menu').hide();
			}
		});

		$('input[name=is_print_menu]').change(function(){
			var is_print_menu = $('input[name=is_print_menu]:checked').val();
			var is_main = $('input[name=is_main]:checked').val();
			if(is_main == '1' && is_print_menu == '0'){
				$('.printWay').hide();
			}else{
				$('.printWay').show();
			}
		});
		$('input[name=is_main]').trigger('change');
		$('input[name=is_print_menu]').trigger('change');
        
        function getStaffList(){
            var store_id = $('#store_id').val();
            var id = $('#staff_uids').data('id');
            var html = '';
            if(store_id != 0){
                $.post("{pigcms{:U('getStaffList')}",{store_id:store_id,id:id},function(result){
                    if(result.status == 0){
                        var data = result.data;
                        for(var i=0;i<data.length;i++){
                            html += '<div style="width:180px;float:left;font-size:16px;"><label><input type="checkbox" value="'+data[i]['id']+'" name="staff_uids[]" '+(data[i]['is_check']?'checked="checked" >':' >')+data[i]['name']+' </label>&nbsp;&nbsp</div>';
                        }
                        $('#staff_uids_value').html(html)
                        $('#staff_uids').show()
                    }else{
                        $('#staff_uids').hide()
                    }
                }, 'json');
            }else{
                $('#staff_uids').hide()
            }
        }
        if(shop_offline_staff_print==1){
            $('#store_id').change(function(){
                getStaffList()
            });
            getStaffList();
        }
	});
</script>
<include file="Public:footer"/>