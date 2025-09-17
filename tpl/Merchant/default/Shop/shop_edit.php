<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑")}{pigcms{$config.shop_alias_name}{pigcms{:L_("信息")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#category">{pigcms{:L_("选择分类")}</a>
							</li>
							<!--li>
								<a data-toggle="tab" href="#label">{pigcms{:L_("选择标签")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#pay">{pigcms{:L_("支付方式")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#delivertime">{pigcms{:L_("配送时间")}</a>
							</li-->
							<li>
								<a data-toggle="tab" href="#promotion">{pigcms{:L_("店铺折扣")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#stock">{pigcms{:L_("库存类型选择")}</a>
							</li>
						  	<if condition="!empty($levelarr)">
								<li>
									<a data-toggle="tab" href="#levelcoupon">{pigcms{:L_("会员优惠")}</a>
								</li>
							</if>
							<if condition="$config.open_store_live eq 1">
								<li>
									<a data-toggle="tab" href="#store_live">{pigcms{:L_("店铺监控")}</a>
								</li>
							</if>
							<if condition="$config['pay_in_store']">
								<li>
									<a data-toggle="tab" href="#offlineShop">{pigcms{:L_("线下零售")}</a>
								</li>
							</if>
							<li>
								<a data-toggle="tab" href="#store_fitment">{pigcms{:L_("页面装修")}</a>
							</li>
                            <if condition="$config.open_scene_vr eq 1">
                                <li>
                                    <a data-toggle="tab" href="#store_vr">720{pigcms{:L_("全景")}</a>
                                </li>
                            </if>
                            <if condition="$config.is_open_shop_weigh eq 1">
                                <li>
                                    <a data-toggle="tab" href="#shop_weigh">{pigcms{:L_("称码对接配置")}</a>
                                </li>
                            </if>
                            <if condition="$config.open_youzanyun eq 1">
                                <li>
                                    <a data-toggle="tab" href="#youzanyun">{pigcms{:L_("有赞参数配置")}</a>
                                </li>
                            </if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">				
							<div id="basicinfo" class="tab-pane active"> 

							   <!-- <div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("关联商品")}</label></label>
									<div class="col-sm-10">
											<table style="width:800px;" id='goods_spec'>
											<tr>
												<th>{pigcms{:L_("商品编号")}</th>
												<th>{pigcms{:L_("商品名称")}</th>
												<th>{pigcms{:L_("规格")}</th>
												<th>{pigcms{:L_("现价")}</th>
												<th>{pigcms{:L_("剩余库存")}</th>
												<th>{pigcms{:L_("操作")}</th>
											</tr>
											<notempty name='info'>
												<tr>
													<input type='hidden' name="goods_id[]" value="{pigcms{$info.goods_id}" />
													<input type='hidden' name="spec_id[]" value="{pigcms{$info.spec_id}" />
													<input type='hidden' name="spec_price[]" value="{pigcms{$info.price}" />
													<td>{pigcms{$info.goods_id}</td>
													<td>{pigcms{$info.name}</td>
													<td>{pigcms{$info.reg_name}</td>
													<td>{pigcms{$info.price}</td>
													<td>{pigcms{$info.stock_num}</td>
													<td>
														<eq name='info.is_edit' value='1'>
															<button type="button" onClick="del_good(1)">{pigcms{:L_("删除")}</button>
														<else/>
															{pigcms{:L_("不可操作")}
														</eq>
													</td>
												</tr>
											</notempty>
										</table>
										<if condition="empty($info)">
											<a class="btn btn-success choose_good_btn" onclick="save_good()">{pigcms{:L_("选择商品")}</a>
										</if>
									</div>
								</div>-->

								<div class="form-group background" <if condition="$store_shop['store_theme'] eq 0">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("是否显示")}{pigcms{$config.shop_alias_name}{pigcms{:L_("店铺")}</label>
									<label><input name="is_close_shop" <if condition="$store_shop['is_close_shop'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("显示")}</span>&nbsp;</label>
									<label><input name="is_close_shop" value="1" <if condition="$store_shop['is_close_shop'] eq 1 ">checked="checked"</if> type="radio" >&nbsp;<span>{pigcms{:L_("不显示")}</span></label>
									<span class="form_tips red">{pigcms{:L_("开启了商城时，")}{pigcms{$config.shop_alias_name}{pigcms{:L_("是否还可用")}</span>
								</div>

								<div class="form-group background">
									<label class="col-sm-1">{pigcms{:L_("打印模式")}{pigcms{$config.shop_alias_name}{pigcms{:L_("店铺")}</label>
									<label><input name="print_type" <if condition="$store_shop['print_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("线下零售模式")}</span>&nbsp;</label>
									<label><input name="print_type" value="1" <if condition="$store_shop['print_type'] eq 1 ">checked="checked"</if> type="radio" >&nbsp;<span>{pigcms{:L_("外卖模式")}</span></label>
									<span class="form_tips red">{pigcms{:L_("默认线下零售模式；线下零售模式为小票打印格式可以根据需求设置标准、中、大三种格式；外卖模式为小票打印格式只可以标准、大两种格式，其中标准格式为固定格式的部分字体大部分字体小。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("配送自提点")}</label></label>
									<label><span><label><input name="is_open_pick" <if condition="$store_shop['is_open_pick'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_open_pick" <if condition="$store_shop['is_open_pick'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="Config_notice">{pigcms{:L_("店铺公告")}</label></label>
									<textarea class="col-sm-3" rows="4" name="store_notice" id="Config_notice">{pigcms{$store_shop.store_notice}</textarea>
								</div>
								<!-- <div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("多级分类")}</label></label>
									<label><span><label><input name="is_mult_class" <if condition="$store_shop['is_mult_class'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_mult_class" <if condition="$store_shop['is_mult_class'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
								    <span class="form_tips red"> {pigcms{:L_("开启多级分类后，")}{pigcms{$config.shop_alias_name}{pigcms{:L_("商品将分三级分类展示，便于您更好的管理多类型商品，注意：开启多级分类后，前台只展示侧重文字模板，侧重图片模板将关闭。")}</span>
								</div> -->
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("打包费收取类型")}</label></label>
                                    <label><span><label><input name="pack_type" <if condition="$store_shop['pack_type'] eq 2 ">checked="checked"</if> value="2" type="radio"></label>&nbsp;<span>{pigcms{:L_("按每单收取")}</span>&nbsp;</span></label>
                                    <label><span><label><input name="pack_type" <if condition="($store_shop['pack_type'] eq 1) or empty($store_shop['pack_type']) ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("按每个商品收取")}</span></span></label>
                                    <span class="form_tips red"> 可设置用户下单时的打包费是按照每单收取一次还是每个商品收取一次</span>
                                </div>
                                <div class="form-group" id="pack_fee" <if condition="$store_shop['pack_type'] != 2">style="display:none"</if>>
                                    <label class="col-sm-1" for="packing_fee_per_order">{pigcms{:L_("每单打包费金额")}</label>
                                    <input class="col-sm-2" size="10" name="pack_fee" id="packing_fee_per_order" type="text" value="{pigcms{$store_shop.pack_fee}"/>
                                </div>

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("自动接单")}</label></label>
									<label><span><label><input name="is_auto_order" <if condition="$store_shop['is_auto_order'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_auto_order" <if condition="$store_shop['is_auto_order'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
								</div>
								
								<if condition="$merchant_session['open_shop_auto_order_notice'] && $config['open_shop_auto_order_notice']">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("自动接单电话提醒时间")}</label></label>
										<input class="col-sm-2" name="shop_auto_order_notice_time" id="shop_auto_order_notice_time" type="text" value="{pigcms{$store_shop.shop_auto_order_notice_time}" />
	                                    <span class="form_tips red"> 单位：分钟;可设置自动接单后多长时间未处理电话通知，默认为0不通知，大于0通知</span>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("自动接单电话通知号码")}</label>
										<input class="col-sm-2" name="shop_auto_order_notice_phone" id="shop_auto_order_notice_phone" type="text" value="{pigcms{$store_shop.shop_auto_order_notice_phone}" />
										<label class="form_tips red">{pigcms{:L_("手机号码，多个以空格分开。超过自动接单电话提醒时间未处理会进行电话通知处理")}</label>
									</div>
								</if>

								<php>if($config['international_phone']== 1){</php>
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("区号")}</label></label>
										<pigcms:national_phone id="phone_country_type" style="height:31px;float:left;margin-right:5px;">
											<option value="{pigcms{$vo.code}"  <if condition="$store_shop.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
										</pigcms:national_phone>
									</div>
								<php>}</php>

								<if condition="$config['shop_order_voice_notice']">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("超时未接单电话通知")}</label>
										
									
										<input class="col-sm-2" name="voice_phone" id="Config_voice_phone" type="text" value="{pigcms{$store_shop.voice_phone}" />
										<label class="form_tips">{pigcms{:L_("手机号码，多个以空格分开。超过")} {pigcms{$config.shop_order_voice_notice} {pigcms{:L_("分钟未接单会进行电话通知接单，防止未及时处理导致取消订单（")}{pigcms{$config.shop_order_cancel_time}{pigcms{:L_("分钟）。")}</label>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("开发票")}</label></label>
									<label><span><label><input name="is_invoice" <if condition="$store_shop['is_invoice'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("不支持")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_invoice" <if condition="$store_shop['is_invoice'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("支持")}</span></span></label>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("线下零售打包费")}</label>
                                    <label><span><label><input name="is_open_retail_pack" <if condition="$store_shop['is_open_retail_pack'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
                                    <label><span><label><input name="is_open_retail_pack" <if condition="$store_shop['is_open_retail_pack'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
                                    <span class="form_tips red"> {pigcms{:L_("开启时线下零售售出的商品按照这个商品设置的打包费收取相应的打包费，关闭时，线下零售售出的商品不收打包费。")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("是否开启订餐提醒")}</label>
									<label><span><label><input name="is_remind" <if condition="$store_shop['is_remind'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_remind" <if condition="$store_shop['is_remind'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
								</div>
                                <div class="form-group remind_item">
                                    <label class="col-sm-1">{pigcms{:L_("订餐提醒星期")}</label>
                                    <input type="checkbox" name="remind_weekend[]" value="1" <if condition="in_array(1,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期一")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="2" <if condition="in_array(2,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期二")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="3" <if condition="in_array(3,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期三")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="4" <if condition="in_array(4,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期四")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="5" <if condition="in_array(5,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期五")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="6" <if condition="in_array(6,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期六")}&nbsp;&nbsp;
                                    <input type="checkbox" name="remind_weekend[]" value="0" <if condition="in_array(0,$store_shop['remind_weekend_arr'])">checked</if>>{pigcms{:L_("星期日")}&nbsp;&nbsp;
                                </div>
								<div class="form-group remind_item">
									<label class="col-sm-1">{pigcms{:L_("订餐提醒时间")}</label>
									<input type="text" class="input-text" name="remind_time" style="width:116px;" placeholder="请选择提醒时间" id="d4311"  value="{pigcms{$store_shop['remind_time']}" onfocus="WdatePicker({readOnly:true,dateFmt:'HH:mm'})"/>
								</div>
                        <script type="text/javascript">

                        </script>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("满足")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="invoice_price" id="Config_invoice_price" type="text" value="{pigcms{$store_shop.invoice_price|floatval}" />
									<label class="form_tips">{pigcms{$config['Currency_txt']}{pigcms{:L_("，可开发票")}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("可提前")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="advance_day" id="Config_advance_day" type="text" value="{pigcms{$store_shop.advance_day}" />
									<label class="form_tips">{pigcms{:L_("天，进行预订下单。")}</label>
									<span class="form_tips red"> {pigcms{:L_("0和空代表只可预订当天的配送时间段。")}</span>
								</div>
								<!--<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("人均消费")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="mean_money" id="Config_mean_money" type="text" value="{pigcms{$store_shop.mean_money|floatval}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']}<span class="required red">*</span></span>
								</div>-->
                                <!--div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("虚拟销量")}</label>
                                    <input class="col-sm-1" size="10" maxlength="10" name="virtual_sale_count" id="Config_virtual_sale_count" type="text" value="{pigcms{$store_shop.virtual_sale_count|intval}" />
                                    <span class="form_tips">{pigcms{:L_("显示给用户看的销量=虚拟销量")}+{pigcms{:L_("实际销量")}</span>
                                </div-->
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("包装费别名")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="pack_alias" type="text" value="{pigcms{$store_shop.pack_alias|default=L_("打包费")}" />
									<span class="form_tips"> {pigcms{:L_("给商品进行包装时所要的耗材产生费用的名称（如：餐盒费,打包费...）。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("运费别名")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="freight_alias" type="text" value="{pigcms{$store_shop.freight_alias|default=L_("配送费用")}" />
									<span class="form_tips"> {pigcms{:L_("把商品从商家送到用户手上所产生的运费的费用名称（如：配送费用,运费...）。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="Config_send_time">{pigcms{:L_("紧急关店")}</label>
                                    <select name="is_close" style="float: left;margin-right:5px;">
                                    <option value="0" <if condition="$store_shop['is_close'] eq 0 ">selected</if>>{pigcms{:L_("开店")}</option>
                                    <option value="1" <if condition="$store_shop['is_close'] eq 1 ">selected</if>>{pigcms{:L_("关店")}</option>
                                    </select>
                                    <input class="col-sm-3" placeholder="{pigcms{:L_("关店原因，选填，建议12个字以内")}" name="close_reason" id="Config_work_time" type="text" value="{pigcms{$store_shop.close_reason}"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="Config_send_time"> {pigcms{:L_("出单时长")}</label>
                                    <input class="col-sm-1" size="10" maxlength="10" name="work_time" id="Config_work_time" type="text" value="{pigcms{$store_shop.work_time}"/>　
                                    <select name="send_time_type">
                                    <option value="0" <if condition="$store_shop['send_time_type'] eq 0 ">selected</if>>{pigcms{:L_("分钟")}</option>
                                    <option value="1" <if condition="$store_shop['send_time_type'] eq 1 ">selected</if>>{pigcms{:L_("小时")}</option>
                                    <option value="2" <if condition="$store_shop['send_time_type'] eq 2 ">selected</if>>天</option>
                                    <option value="3" <if condition="$store_shop['send_time_type'] eq 3 ">selected</if>>周</option>
                                    <option value="4" <if condition="$store_shop['send_time_type'] eq 4 ">selected</if>>月</option>
                                    </select>
                                    <span class="form_tips red"> {pigcms{:L_("注：从店员接单起到店员配好订单所有商品所需要的时间。")}</span>
                                </div>
								
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("配送方式")}</label></label>
									<select name="deliver_type">
									<volist name="deliver_types" id="deliver">
									<option value="{pigcms{$deliver['id']}" <if condition="$store_shop['deliver_type'] eq $deliver['id']">selected</if>>{pigcms{$deliver['name']}</option>
									</volist>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("电动自行车停车时间")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="electric_bicycle_parking_duration" type="text" value="{pigcms{$store_shop.electric_bicycle_parking_duration}" />
									<span class="form_tips"> {pigcms{:L_("单位：分钟")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("摩托车停车时间")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="motorcycle_parking_duration" type="text" value="{pigcms{$store_shop.motorcycle_parking_duration}" />
									<span class="form_tips"> {pigcms{:L_("单位：分钟")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("汽车停车时间")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="car_parking_duration" type="text" value="{pigcms{$store_shop.car_parking_duration}" />
									<span class="form_tips"> {pigcms{:L_("单位：分钟")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("超时自动送达时长")}</label>
                                    <input class="col-sm-1" size="10" maxlength="10" name="auto_overtime_deliver" type="text" value="{pigcms{$store_shop.auto_overtime_deliver}" />
                                    <span class="form_tips"> {pigcms{:L_("单位：小时 (0=不开启)")}</span>
                                </div>
                                
                                <div class="form-group deliver" <if condition="in_array($store_shop['deliver_type'], array(0,2,3,5))">style="display:none"</if>>
                                    <label class="col-sm-1" for="Config_send_time"> {pigcms{:L_("配送时长")}</label>
                                    <input class="col-sm-1" size="10" maxlength="10" name="send_time" id="Config_send_time" type="text" value="{pigcms{$store_shop.send_time}"/>
                                    <span class="form_tips">{pigcms{:L_("分钟")}</span>
                                </div>
								<div class="form-group deliver" <if condition="in_array($store_shop['deliver_type'], array(0,2,3,5))">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("起送价格")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="basic_price" id="Config_basicprice" type="text" value="{pigcms{$store_shop.basic_price|floatval}" />{pigcms{$config['Currency_txt']}
									<span class="form_tips red">{pigcms{:L_("只针对商家配送方式下的起送价设置")}</span>
								</div>
								<div class="form-group deliver" <if condition="in_array($store_shop['deliver_type'], array(0,2,3,5))">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("加价送")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="extra_price" type="text" value="{pigcms{$store_shop.extra_price|floatval}" />
									<span class="form_tips red">{pigcms{$config['Currency_txt']} {pigcms{:L_("【当订单的价格没有达到起送价加的时候加价也可以配送】")}</span>
								</div>
								<div class="form-group deliver" <if condition="in_array($store_shop['deliver_type'], array(0,2,3,5))">style="display:none"</if>>
									<label class="col-sm-1" for="Config_delivery_radius">{pigcms{:L_("服务距离")}</label>
									<div class="col-sm-10">
    									<div class="form-group">
        									<label class="col-sm-1"><input name="delivery_range_type" value="0" type="radio" <if condition="$store_shop['delivery_range_type'] eq 0">checked</if>>{pigcms{:L_("半径范围")}</label>
        									<input class="col-sm-1" size="10" maxlength="10" name="delivery_radius" type="text" value="{pigcms{$store_shop.delivery_radius|floatval}" <if condition="$store_shop['delivery_range_type'] eq 1">disabled</if>/>
        									<span class="form_tips">{pigcms{:L_("公里")}</span>
    									</div>
    									<div class="form-group">
        									<div class="col-sm-1">
            									<div class="form-group">
            									   <label class="col-sm-12"><input name="delivery_range_type" value="1" type="radio" <if condition="$store_shop['delivery_range_type'] eq 1">checked</if>>{pigcms{:L_("自定义范围")}</label>
            									   <input type="hidden" name="delivery_range_polygon" id="delivery_range_polygon" />
            									</div>
            									<div class="form-group map" <if condition="$store_shop['delivery_range_type'] eq 0">style="display:none"</if>>
            									   <label class="col-sm-12"><input type="button" value="{pigcms{:L_("绘制配送范围")}" id="baiduMap"/></label>
            									</div>
        									</div>
        									<div id="allmap" class="col-sm-9 map" style="height:350px;<if condition="$store_shop['delivery_range_type'] eq 0">display:none</if>" ></div>
    									</div>
									</div>
								</div>
								<div class="tabbable deliver deliver_time" <if condition="in_array($store_shop['deliver_type'], array(0, 2, 3))">style="display:none"</if>>
									<ul class="nav nav-tabs" id="myTab">
										<li class="active">
											<a data-toggle="tab" href="#shop_time_1">
												{pigcms{:L_("配送时间段一")}
											</a>
										</li>
										<li>
											<a data-toggle="tab" href="#shop_time_2">
												{pigcms{:L_("配送时间段二")}
											</a>
										</li>
                                        <li>
                                            <a data-toggle="tab" href="#shop_time_3">{pigcms{:L_("配送时间段三")}</a>
                                        </li>
									</ul>
									<div class="tab-content" >
										<div id="shop_time_1" class="tab-pane in active">
											<div class="form-group deliver deliver_time" >
												<label class="col-sm-1"><label>{pigcms{:L_("配送时间")}</label></label>
												<div>
													<input id="delivertime_start" type="text" value="{pigcms{$store_shop.delivertime_start|default='00:00'}" name="delivertime_start" readonly style="width:70px"/>	{pigcms{:L_("至")} 
													<input id="delivertime_stop" type="text" value="{pigcms{$store_shop.delivertime_stop|default='00:00'}" name="delivertime_stop" readonly style="width:70px"/>
													<span class="form_tips red">{pigcms{:L_("如果两个都不填写或两个值都‘00:00’的话，就代表24小时都可以配送的")}</span>
												</div>
											</div>
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("时段一起送价")}</label>
                                                <div>
                                                    <input id="basic_price1" type="text" value="{pigcms{$store_shop.basic_price1|floatval}" name="basic_price1" style="width:50px"/>
                                                    <span class="form_tips red">{pigcms{:L_("每单达到这个价格才给予配送")}</b></span>
                                                </div>
                                            </div>
											<div class="form-group deliver deliver_time">
												<label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("配送费")}</label>
												<div>
													<input id="basic_distance" type="text" value="{pigcms{$store_shop.basic_distance|floatval}" name="basic_distance" style="width:50px"/>	{pigcms{:L_("公里以内")}
													<input id="delivery_fee" type="text" value="{pigcms{$store_shop.delivery_fee|floatval}" name="delivery_fee" style="width:50px"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("，超出范围每公里加")}
													<input id="per_km_price" type="text" value="{pigcms{$store_shop.per_km_price|floatval}" name="per_km_price" style="width:50px"/>{pigcms{$config['Currency_txt']}
												</div>
												<!--input class="col-sm-1" size="10" maxlength="10" name="delivery_fee" id="Config_delivery_fee" type="text" value="{pigcms{$store_shop.delivery_fee|floatval}"/>{pigcms{$config['Currency_txt']}<span class="required">*</span-->
											</div>
											
											<div class="form-group deliver deliver_time">
												<label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("达到起送价格")}</label>
												<label><input name="reach_delivery_fee_type" <if condition="$store_shop['reach_delivery_fee_type'] eq 0 ">checked="checked"</if> value="0" type="radio">{pigcms{:L_("免配送费")}</label>　　
												<label><input name="reach_delivery_fee_type" <if condition="$store_shop['reach_delivery_fee_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >{pigcms{:L_("照样收取配送费")}</label>　　
												<label><input name="reach_delivery_fee_type" <if condition="$store_shop['reach_delivery_fee_type'] eq 2 ">checked="checked"</if> value="2" type="radio" >{pigcms{:L_("达到")}</label>
												<input size="10" maxlength="10" name="no_delivery_fee_value" id="Config_no_delivery_fee_value" type="text" value="{pigcms{$store_shop.no_delivery_fee_value|floatval}"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("免配送费")}
											</div>
										</div>
										<div id="shop_time_2" class="tab-pane">
											<div class="form-group deliver deliver_time">
												<label class="col-sm-1"><label>{pigcms{:L_("配送时间")}</label></label>
												<div>
													<input id="delivertime_start2" type="text" value="{pigcms{$store_shop.delivertime_start2|default='00:00'}" name="delivertime_start2" readonly style="width:70px"/>	{pigcms{:L_("至")} 
													<input id="delivertime_stop2" type="text" value="{pigcms{$store_shop.delivertime_stop2|default='00:00'}" name="delivertime_stop2" readonly style="width:70px"/>
													<span class="form_tips red">{pigcms{:L_("如果两个都不填写或两个值都‘00:00’的话，")}<b>{pigcms{:L_("就表示没有第二配送时间段")}</b></span>
												</div>
											</div>
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("时段二起送价")}</label>
                                                <div>
                                                    <input id="basic_price2" type="text" value="{pigcms{$store_shop.basic_price2|floatval}" name="basic_price2" style="width:50px"/>
                                                    <span class="form_tips red">{pigcms{:L_("每单达到这个价格才给予配送")}</b></span>
                                                </div>
                                            </div>
											<div class="form-group deliver deliver_time">
												<label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("配送费")}</label>
												<div>
													<input id="basic_distance2" type="text" value="{pigcms{$store_shop.basic_distance2|floatval}" name="basic_distance2" style="width:50px"/>	{pigcms{:L_("公里以内")}
													<input id="delivery_fee2" type="text" value="{pigcms{$store_shop.delivery_fee2|floatval}" name="delivery_fee2" style="width:50px"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("，超出范围每公里加")}
													<input id="per_km_price2" type="text" value="{pigcms{$store_shop.per_km_price2|floatval}" name="per_km_price2" style="width:50px"/>{pigcms{$config['Currency_txt']}
												</div>
											</div>
											<div class="form-group deliver deliver_time">
												<label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("达到起送价格")}</label>
												
												<label><input name="reach_delivery_fee_type2" <if condition="$store_shop['reach_delivery_fee_type2'] eq 0 ">checked="checked"</if> value="0" type="radio">{pigcms{:L_("免配送费")}</label>　　
												<label><input name="reach_delivery_fee_type2" <if condition="$store_shop['reach_delivery_fee_type2'] eq 1 ">checked="checked"</if> value="1" type="radio" >{pigcms{:L_("照样收取配送费")}</label>　　
												<label><input name="reach_delivery_fee_type2" <if condition="$store_shop['reach_delivery_fee_type2'] eq 2 ">checked="checked"</if> value="2" type="radio" >{pigcms{:L_("达到")}</label>
												<input size="10" maxlength="10" name="no_delivery_fee_value2" id="Config_no_delivery_fee_value" type="text" value="{pigcms{$store_shop.no_delivery_fee_value2|floatval}"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("免配送费")}
											</div>
										</div>
                                        <div id="shop_time_3" class="tab-pane">
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1"><label>{pigcms{:L_("配送时间")}</label></label>
                                                <div>
                                                    <input id="delivertime_start3" type="text" value="{pigcms{$store_shop.delivertime_start3|default='00:00'}" name="delivertime_start3" readonly style="width:70px"/>  至
                                                    <input id="delivertime_stop3" type="text" value="{pigcms{$store_shop.delivertime_stop3|default='00:00'}" name="delivertime_stop3" readonly style="width:70px"/>
                                                    <span class="form_tips red">{pigcms{:L_("如果两个都不填写或两个值都‘00:00’的话，")}<b>{pigcms{:L_("就表示没有第三配送时间段")}</b></span>
                                                </div>
                                            </div>
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("时段三起送价")}</label>
                                                <div>
                                                    <input id="basic_price3" type="text" value="{pigcms{$store_shop.basic_price3|floatval}" name="basic_price3" style="width:50px"/>
                                                    <span class="form_tips red">{pigcms{:L_("每单达到这个价格才给予配送")}</b></span>
                                                </div>
                                            </div>
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("配送费")}</label>
                                                <div>
                                                    <input id="basic_distance3" type="text" value="{pigcms{$store_shop.basic_distance3|floatval}" name="basic_distance3" style="width:50px"/>   {pigcms{:L_("公里以内")}
                                                    <input id="delivery_fee3" type="text" value="{pigcms{$store_shop.delivery_fee3|floatval}" name="delivery_fee3" style="width:50px"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("，超出范围每公里加")}
                                                    <input id="per_km_price3" type="text" value="{pigcms{$store_shop.per_km_price3|floatval}" name="per_km_price3" style="width:50px"/>{pigcms{$config['Currency_txt']}
                                                </div>
                                            </div>
                                            <div class="form-group deliver deliver_time">
                                                <label class="col-sm-1" for="Config_delivery_fee">{pigcms{:L_("达到起送价格")}</label>
                                                <label><input name="reach_delivery_fee_type3" <if condition="$store_shop['reach_delivery_fee_type3'] eq 0 ">checked="checked"</if> value="0" type="radio">{pigcms{:L_("免配送费")}</label>　　
                                                <label><input name="reach_delivery_fee_type3" <if condition="$store_shop['reach_delivery_fee_type3'] eq 1 ">checked="checked"</if> value="1" type="radio" >{pigcms{:L_("照样收取配送费")}</label>　　
                                                <label><input name="reach_delivery_fee_type3" <if condition="$store_shop['reach_delivery_fee_type3'] eq 2 ">checked="checked"</if> value="2" type="radio" >{pigcms{:L_("达到")}</label>
                                                <input size="10" maxlength="10" name="no_delivery_fee_value3" id="Config_no_delivery_fee_value" type="text" value="{pigcms{$store_shop.no_delivery_fee_value3|floatval}"/>{pigcms{$config['Currency_txt']}{pigcms{:L_("免配送费")}
                                            </div>
                                        </div>
									</div>
								</div>
								<!--div class="form-group">
									<label class="col-sm-1"><label for="Config_area">{pigcms{:L_("配送区域")}</label></label>
									<textarea class="col-sm-3" rows="4" name="delivery_area" id="Config_area">{pigcms{$store_shop.delivery_area}</textarea>
								</div-->
							</div>
							<div id="category" class="tab-pane">
								<div class="alert alert-info">
									<p>说明：</p>
									<p>修改外卖店铺分类需要平台审核通过后才会生效，只有新增的分类才需审核，减少的分类将无需审核。如修改分类后请及时联系平台管理员进行审核</p>
								</div>
								<volist name="category_list" id="vo">
									<div class="form-group">
										<div class="radio">
											<label>
												<span class="lbl"><label style="color: red">{pigcms{$vo.cat_name}：</label></span>
											</label>
											<volist name="vo['son_list']" id="child">
												<label>
													<input class="cat_class" type="checkbox" name="store_category[]" value="{pigcms{$vo.cat_id}-{pigcms{$child.cat_id}" id="Config_store_category_{pigcms{$child.cat_id}" <if condition="in_array($child['cat_id'],$relation_array)">checked="checked"</if>/>
													<span class="lbl"><label for="Config_store_category_{pigcms{$child.cat_id}">{pigcms{$child.cat_name} <if condition="in_array($child['cat_id'],$relation_apply_ids)"><span style="color: red;">（平台审核中）</span></if></label></span>
												</label>
											</volist>
										</div>
									</div>
								</volist>
							</div>
							<div id="label" class="tab-pane">
								<volist name="label_list" id="vo">
									<div class="form-group">
										<div class="radio">
											<label>
												<input class="cat_class" type="checkbox" name="store_labels[]" value="{pigcms{$vo.id}" id="Config_store_label_{pigcms{$vo.id}" <if condition="in_array($vo['id'], $store_shop['store_labels'])">checked="checked"</if>/>
												<span class="lbl"><label for="Config_store_label_{pigcms{$vo.id}">{pigcms{$vo.name}</label></span>
											</label>
										</div>
									</div>
								</volist>
							</div>
							<div id="pay" class="tab-pane">
								<if condition="$config['store_open_payone']">
									<div class="form-group">
										<div class="radio">
											<label>
												<input class="paycheck " type="checkbox" name="openpayone" value="1" id="Config_openpayone" onclick="check(this);" <if condition="$store_shop['openpayone'] eq 1">checked="checked"</if>/>
												<span class="lbl"><label for="Config_openpayone">{pigcms{:L_("货到付款")}</label></span>
											</label>
										</div>
									</div>
								</if>
								<div class="form-group">
									<div class="radio">
										<label>
											<input class="paycheck " type="checkbox" name="openpaytwo" value="1" id="Config_openpaytwo" onclick="check(this);" <if condition="$store_shop['openpaytwo'] eq 1">checked="checked"</if>/>
											<span class="lbl"><label for="Config_openpaytwo">{pigcms{:L_("余额支付")}</label></span>
										</label>
									</div>
								</div>
								<if condition="$config['store_open_paythree']">
									<div class="form-group">
										<div class="radio">
											<label>
												<input class="paycheck " type="checkbox" name="openpaythree" value="1" id="Config_openpaythree" onclick="check(this);" <if condition="$store_shop['openpaythree'] eq 1">checked="checked"</if>/>
												<span class="lbl"><label for="Config_openpaythree">{pigcms{:L_("在线支付")}</label></span>
											</label>
										</div>
									</div>
								</if>
							</div>
							<div id="delivertime" class="tab-pane">
								<div class="alert alert-block alert-success">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>
									<p>{pigcms{:L_("外卖有个特点，就是顾客消费的时间段比较集中，例如中午11点至12点半，晚上5点至6点半，都是点外卖的高峰期。对于某些订单量较大的商家或者多店铺运营者来说，如果顾客都临时下单，很难保证订单的及时配送，可能会导致顾客投诉与抱怨，引起顾客流失。为此，我们提供了店铺配送时间的功能设置。")}<br/><br/>{pigcms{:L_("每个店铺最多可以配置20个配送时间段，顾客在下单的时候，必须选择其中一个时间段。并且可以设置一个最少提前多少分钟下单，例如设置了最少提前30分钟下单，那么选择")}11:30-12:00{pigcms{:L_("时间段配送的顾客，至少要在11点之前下单，否则无法进入订单支付结算页面。")}
									</p>
								</div>
								<div class="form-group">
									<label class="col-sm-2" for="Config_opendelivertime">{pigcms{:L_("是否开启配送时间限制")}</label>
									<select name="open_deliver_time" id="Config_opendelivertime">
										<option value="0" <if condition="$store_shop['open_deliver_time'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$store_shop['open_deliver_time'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-2" for="Config_delivertimerange">{pigcms{:L_("最少提前多少分钟下单")}</label>
									<input class="col-sm-1" size="10" maxlength="3" name="deliver_time_range" id="Config_delivertimerange" type="text" value="{pigcms{$store_shop.deliver_time_range}" />{pigcms{:L_("分钟")}	
								</div>
								<div class="widget-box">
									<div class="widget-header">
										<h5>{pigcms{:L_("配送时间段")}</h5>
									</div>
									<div class="widget-body">
										<div class="widget-main">
											<volist name="store_shop['deliver_time']" id="vo">
												<div style="margin:10px;width:400px;float:left;">({pigcms{$i})
													<input id="delivertime_{pigcms{$i}_start" type="text" value="{pigcms{$vo.start}" name="deliver_time[{pigcms{$i}][start]"/> {pigcms{:L_("至")} <input id="delivertime_{pigcms{$i}_stop" type="text" value="{pigcms{$vo.stop}" name="deliver_time[{pigcms{$i}][stop]"/>
												</div>
											</volist>
											<div style="clear:both;"></div>
										</div>
									</div>
								</div>
								<div style="clear:both;"></div>
							</div>
							
							<div id="promotion" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺折扣")}</label>
									<if condition="!empty($_SESSION['system']) AND isset($config['discount_controler']) AND  $config['discount_controler'] eq 0">
										<strong style="color:red">{pigcms{:L_("只能由商家自己设置")}</strong>
										<input class="col-sm-1" size="10" maxlength="10" name="store_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.store_discount}" disabled /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />
									    <strong style="color:red">{pigcms{:L_("只能由系统设置")}</strong>
										<input class="col-sm-1" size="10" maxlength="10" name="store_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.store_discount}" disabled /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									<else />
									<input class="col-sm-1" size="10" maxlength="10" name="store_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.store_discount}" /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("优惠方式")}</label></label>
									<php>if(1==$config['group_store_discount_more']){</php>
									<if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">
										<strong style="color:red">{pigcms{:L_("只能由商家自己设置")}</strong>
									<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />
									    <strong style="color:red">{pigcms{:L_("只能由系统设置")}</strong>
									<else />
									
									</if>
									<php>}</php>
									<span><label><input id='discount_type0' name="discount_type" <if condition="$store_shop['discount_type'] eq 0 ">checked="checked"</if> value="0" type="radio"  <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>></label>&nbsp;<span>{pigcms{:L_("折上折")}</span>&nbsp;</span>
									<span><label><input id='discount_type1' name="discount_type" <if condition="$store_shop['discount_type'] eq 1 ">checked="checked"</if> value="1" type="radio" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>></label>&nbsp;<span>{pigcms{:L_("折扣最优")}</span></span>
									<strong style="color:red">{pigcms{:L_("折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；")}<br/>
									{pigcms{:L_("折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。")}
									</strong>
								</div>
								<div style="clear:both;"></div>
							</div>
							
							<div id="stock" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("库存类型：")}</label>
									<label><input type="radio" name="stock_type" value="0" <if condition="$store_shop['stock_type'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("每天自动更新固定量的库存")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="stock_type" value="1" <if condition="$store_shop['stock_type'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("固定的库存，不会每天自动更新")}</label>&nbsp;&nbsp;&nbsp;
									<span class="form_tips red">如设置每日更新库存，则系统将会在每日凌晨0点开始更新，请在系统更新后查看相关每日库存回滚情况</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("减库存类型：")}</label>
									<label><input type="radio" name="reduce_stock_type" value="0" <if condition="$store_shop['reduce_stock_type'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("支付成功后减库存")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="reduce_stock_type" value="1" <if condition="$store_shop['reduce_stock_type'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("下单成功后减库存")}</label>
									<span class="form_tips red">1.{pigcms{:L_("支付成功后减库存：可能会出现售出的数量大于商品总数；2.下单成功后减库存：可能会出现大量下单但是没有支付时库存就已经没有了，但是如果在下面设置的买单时长的时间内还是没有买单的话系统自动回滚库存")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("买单时长：")}</label>
									<input class="col-sm-1" size="10" maxlength="3" name="rollback_time" id="rollback_time" type="text" value="{pigcms{$store_shop.rollback_time|default='20'}" />
									<span class="form_tips">{pigcms{:L_("分钟，至少10分钟")}</span>
								</div>
								<div style="clear:both;"></div>
							</div>

							<if condition="!empty($levelarr)">
							<div id="levelcoupon" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！填写数字即可，无需%符号，填写50代表5折，0与100代表没有折扣。<br/>注意：选择立减，是在单个商品的基础上减去设置的金额。")}</label>
								</div>
							    <volist name="levelarr" id="vv">
								  <div class="form-group">
								    <input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
								    <input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
									<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
									{pigcms{:L_("优惠类型：")}&nbsp;
<!--									<select name="leveloff[{pigcms{$vv['level']}][type]" <php>if(1==$config['group_store_discount_more']){</php><if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if> <php>}</php>>-->
									<select name="leveloff[{pigcms{$vv['level']}][type]" <if condition="($config['discount_controler'] eq 1) AND empty($_SESSION['system'])">disabled title="平台才有设置权限"</if> >
										<option value="0">{pigcms{:L_("无优惠")}</option>
										<option value="1" <if condition="$vv['type'] eq 1">selected="selected"</if>>{pigcms{:L_("百分比（%）")}</option>
										<option value="2" <if condition="$vv['type'] eq 2">selected="selected"</if>>{pigcms{:L_("立减")}</option>
									</select>

<!--									<input name="leveloff[{pigcms{$vv['level']}][vv]" type="text" value="{pigcms{$vv['vv']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if><php>}</php> placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>-->
                                      <!--修改为只要同步会员等级优惠，商家后台就无法修改优惠-->
									<input name="leveloff[{pigcms{$vv['level']}][vv]" type="text" value="{pigcms{$vv['vv']}" <if condition="($config['discount_controler'] eq 1) AND empty($_SESSION['system'])">disabled title="平台才有设置权限"</if> placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>

									<php>if(1==$config['group_store_discount_more']){</php>
									<if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">
										<input  name="leveloff[{pigcms{$vv['level']}][type]" type="hidden" value="{pigcms{$vv['type']}"/>
										<input  name="leveloff[{pigcms{$vv['level']}][vv]" type="hidden" value="{pigcms{$vv['vv']}"/>
									</if> 
									<php>}</php>
								</div>
								</volist>
							</div>
							</if>
							<if condition="$config.open_store_live eq 1">
							<div id="store_live" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：如果设置店铺监控，则在店铺页面出现“店铺监控”按钮，用户点击可查看到店内监控，加强信任感。监控缩略图建议尺寸为：  宽度建议为700px，高度建议为420px。")}</label>
								</div>
                                <empty name="store_shop.store_live_urls">
                                    <div style="border-bottom: 1px solid #ddd;margin-bottom: 10px;">
                                        <div class="form-group">
                                            <label class="col-sm-1">{pigcms{:L_("分类名称：")}</label>
                                            <input class="live_cate" name="live_cate[]"/>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-1">{pigcms{:L_("监控地址：")}</label>
                                            <input class="live_cate live_url" name="live_url[]"/>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-1">{pigcms{:L_("封面图片：")}</label>
                                            <div class="show_pic"></div>
                                            <input type="hidden" class="live_cate live_url live_pic" name="live_pic[]"/>
                                            <a href="javascript:void(0)" class="upload_store_live_pic">{pigcms{:L_("上传图片")}</a>
                                            <a href="javascript:void(0)" class="clear_store_live_pic">{pigcms{:L_("清空图片")}</a>
                                        </div>
                                    </div>
                                <else/>
                                    <volist name="store_shop.store_live_urls" id="live" key="key">
                                        <div style="border-bottom: 1px solid #ddd;margin-bottom: 10px;">
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("分类名称：")}</label>
                                                <input class="live_cate" name="live_cate[]" value="{pigcms{$live.live_cate}"/>
                                                <neq name="key" value="1">
                                                    <span class="live_del">删除</span>
                                                </neq>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("监控地址：")}</label>
                                                <input class="live_cate live_url" name="live_url[]" value="{pigcms{$live.live_url}"/>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("封面图片：")}</label>
                                                <div class="show_pic" style="min-height: 100px;min-width: 100px;display: inline-block">
                                                    <img style='max-width: 130px;' src="{pigcms{$live.live_pic|replace_file_domain}">
                                                </div>
                                                <input type="hidden" class="live_cate live_url live_pic" name="live_pic[]" value="{pigcms{$live.live_pic}"/>
                                                <a href="javascript:void(0)" class="upload_store_live_pic">{pigcms{:L_("上传图片")}</a>
                                                <a href="javascript:void(0)" class="clear_store_live_pic">{pigcms{:L_("清空图片")}</a>
                                            </div>
                                        </div>
                                    </volist>
                                </empty>
                                <div class="btn btn-success add_live_input" style="margin-top: 10rem">{pigcms{:L_("添加分类")}</div>
							</div>
							</if>
							<div id="offlineShop" class="tab-pane">
							    <div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("开启线下零售")}</label></label>
									<label><span><label><input name="open_offline" <if condition="$store_shop['open_offline'] eq 1">checked="checked"</if> value="1" type="radio"></label>&nbsp;<span>{pigcms{:L_("开启")}</span>&nbsp;</span></label>
									<label><span><label><input name="open_offline" <if condition="$store_shop['open_offline'] eq 0 ">checked="checked"</if> value="0" type="radio" ></label>&nbsp;<span>{pigcms{:L_("关闭")}</span></span></label>
								    <span class="form_tips red">{pigcms{:L_("是否开启线下零售")}</span>
								</div>
							</div>
							<div id="store_fitment" class="tab-pane">
								<div class="store_fitment_div">
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("点菜别名")}
											<input style="position: absolute;right: 0px;" size="10" maxlength="10" name="order_guide_words" type="text" value="{pigcms{$store_shop.order_guide_words|default=''}" />
										</div>
										<div class="group-content">{pigcms{:L_("设置为空或者不填，默认显示‘点菜’")}</div>
									</div>
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("拼单/分袋")}
											<div style="position: absolute;right: 0px;">
												<select name="open_splicing_order" style="width:94px;font-size: 14px;">
													<option value="1" <if condition="$store_shop['open_splicing_order'] eq '1'">selected="selected"</if>>{pigcms{:L_("开启")}</option>
													<option value="0" <if condition="$store_shop['open_splicing_order'] eq '0'">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
												</select>
											</div>
										</div>
										<div class="group-content">{pigcms{:L_("开启则店铺内显示拼单入口，关闭后则不显示")}</div>
									</div>
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("门店主题色")}
											<button type="button" class="btn fitment" data-type="shop_fitment_color" data-title="{pigcms{:L_("门店主题色")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("有效提升您店铺的品质感，让您的品牌深入人心，从而获得更多顾客的信赖和喜爱。")}</div>
									</div>
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("门店专题")}
											<button type="button" class="btn fitment subject" data-type="shop_fitment_subject" data-title="{pigcms{:L_("门店专题")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("通过运营位推荐新品、打造爆款商品。")}</div>
									</div>
                                    <div class="form-group">
										<div class="group-title">{pigcms{:L_("导航栏导航列表")}
											<button type="button" class="btn fitment slider" data-type="shop_fitment_slider" data-title="{pigcms{:L_("导航栏导航列表")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("每行展示五个，不设置不展示。")}</div>
									</div>
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("爆款橱窗")}
											<button type="button" class="btn fitment showcase" data-type="shop_fitment_showcase" data-title="{pigcms{:L_("爆款橱窗")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("将您的商品以大图橱窗展现，充分利用店内流量，从而提升下单率。")}</div>
									</div>
                                    <div class="form-group">
                                        <div class="group-title">{pigcms{:L_("店铺列表推荐商品")}
                                            <button type="button" class="btn fitment showcase" data-type="shop_good_goods" data-title="{pigcms{:L_("店铺列表推荐商品")}">{pigcms{:L_("装修")}</button>
                                        </div>
                                        <div class="group-content">{pigcms{:L_("展示在外卖主页店铺列表展示推荐商品,默认展示销售前三,最多选择3个商品。")}</div>
                                    </div>
									<div class="form-group cat-group">
										<div class="group-title">{pigcms{:L_("特色分类")}</div>
										<div class="group-content cat">
											<div class="cat-row clearfix">
												<div class="cat-name">{pigcms{:L_("热销")}</div>
												<div class="cat-desc">
													<div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_hot_status"><if condition="$subject_info['cat_hot_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span></div>
													<div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_hot_sort">
														<if condition="$subject_info['cat_hot_sort'] eq 1">
															{pigcms{:L_("价格从高到低排序")}
														<elseif condition="$subject_info['cat_hot_sort'] eq 2"/>
															{pigcms{:L_("价格从低到高排序")}
														<elseif condition="$subject_info['cat_hot_sort'] eq 3"/>
															{pigcms{:L_("销量从高到低排序")}
														<elseif condition="$subject_info['cat_hot_sort'] eq 4"/>
															{pigcms{:L_("销量从低到高排序")}
														<else/>{pigcms{:L_("默认排序")}</if></span></div>
													<div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_hot_desc">{pigcms{$subject_info.cat_hot_desc|default=L_("大家喜欢吃，才叫真好吃")}</span></div>
													<div class="cat-edit">
														<a title="{pigcms{:L_("修改")}" class="green fitment" style="padding-right:8px;"  data-type="shop_fitment_category_hot" data-title="{pigcms{:L_("热销分类")}">
															<i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}
														</a>
													</div>
												</div>
											</div>
											<div class="cat-row clearfix">
												<div class="cat-name">{pigcms{:L_("优惠")}</div>
												<div class="cat-desc">
													<div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_discount_status"><if condition="$subject_info['cat_discount_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span></div>
													<div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_discount_sort">
														<if condition="$subject_info['cat_discount_sort'] eq 1">
															{pigcms{:L_("价格从高到低排序")}
														<elseif condition="$subject_info['cat_discount_sort'] eq 2"/>
															{pigcms{:L_("价格从低到高排序")}
														<elseif condition="$subject_info['cat_discount_sort'] eq 3"/>
															{pigcms{:L_("销量从高到低排序")}
														<elseif condition="$subject_info['cat_discount_sort'] eq 4"/>
															{pigcms{:L_("销量从低到高排序")}
														<else/>{pigcms{:L_("默认排序")}</if></span></div>
													<div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_discount_desc">{pigcms{$subject_info.cat_discount_desc|default=L_("美味又实惠，大家快来抢")}</span></div>
													<div class="cat-edit">
														<a title="{pigcms{:L_("修改")}" class="green fitment" style="padding-right:8px;"  data-type="shop_fitment_category_discount" data-title="{pigcms{:L_("优惠分类")}">
															<i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
									<if condition="$config['mall_alias_name']">
										<div class="form-group">
											<div class="group-title">{pigcms{:L_("品牌故事")}
												<button type="button" class="btn fitment" data-type="shop_fitment_history" data-title="{pigcms{:L_("品牌故事")}">{pigcms{:L_("装修")}</button>
											</div>
											<div class="group-content">{pigcms{:L_("创建个性页面，展现您的品牌故事，让您的品牌深入人心，从而获得更多顾客的信赖和喜爱。")}</div>
										</div>
									</if>
								</div>
								<div id="fitment_preview">
									<div class="page-style" style="width: 372px;height: 627px;box-shadow: 0 0 10px 2px #ddd">
										<iframe src="{pigcms{$viewStoreUrl}" id="viewpageIframe" style="width:100%;height:100%;border:0;"></iframe>
									</div>

									<div style="margin-top: 20px;"><span style="background: #ddd;padding: 5px 10px;margin-right: 20px;" onclick="refreshIframe()">刷新</span>(如果配置未生效，请点击此刷新按钮)</div>
								</div>
							</div>
                            <if condition="$config.open_scene_vr eq 1">
                            <div id="store_vr" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("全景压缩包")}</label>
                                    <input name="store_vr" id="store_vr_zip" type="text" readonly="true" value="{pigcms{$store_shop.store_vr}" size="70"/>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success" id="selectFile">{pigcms{:L_("上传压缩包")}</a>
                                    <span class="form_tips"> {pigcms{:L_("格式为zip的压缩包，里面要包涵index.html否则会上传失败,大小50M以内")}</span>
                                </div>
                            </div>
                            </if>
                            <if condition="$config.is_open_shop_weigh eq 1">
                                <div id="shop_weigh" class="tab-pane">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("称码对接")}</label><span class="form-tips" style="line-height:35px;">{pigcms{:L_("用于线上购买、线下零售的称重商品，支持称重后产生的条形码识别，识别内容包含商品信息、称重金额、重量（称重金额")}/{pigcms{:L_("单价）等")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("称码规则（用于扫码识别解析，请根据约定的称码规则填写）")}</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("商品码长度：")}</label><input name="goods_code_length"  type="text" readonly="true" value="{pigcms{$store_shop.goods_code_length}" size="20"/>{pigcms{:L_("位数字")}
                                        <span class="form_tips">{pigcms{:L_("默认5位，请先将条码显示规则设置为5位形式的商品码，如何设置条码显示规则请查看教程：")}<a href="http://v.youku.com/v_show/id_XMzQ0MDc4MjY3Mg==.html?spm=a2hzp.8244740.0.0" target="_blank">{pigcms{:L_("点击打开链接")}</a></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("金额长度：")}</label><input name="money_code_length"  type="text" readonly="true" value="{pigcms{$store_shop.money_code_length}" size="20"/>{pigcms{:L_("位数字")}
                                        <span class="form_tips">{pigcms{:L_("长度不够时前面会补0，单位：分")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("示例称码：")}<img src="{pigcms{$static_public}images/cmsm.png"></label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("称码接入")}</label><span class="form-tips" style="line-height:35px;">{pigcms{:L_("（用于识别称码前缀，普通商品编码前缀不能包含称码，操作称码后请刷新）")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"> <span style="color: red;">*</span>{pigcms{:L_("称码：")}</label><input type="text" id="weigh_code" val="" placeholder="{pigcms{:L_("输入称码（必填），别名：")}"<input type="text" id="alias_name" val="" placeholder="{pigcms{:L_("输入别名（必填）")}">
                                        <input type="button" id="add_weigh_code" style="background-color: #6fb3e0;padding: 2px 6px;border-radius: 5px;outline: none;text-shadow: 0 -1px 0 rgba(0,0,0,.25)!important;border:none;color:white;" value="{pigcms{:L_("新增")}">
										<span style="color:red;width:95%;padding-left: 10px;">{pigcms{:L_("称码首个数不建议设置为0，会存在部分品牌扫码枪识别兼容问题")}</span>
									</div>
                                    <div class="form-group">
                                        <table class="weigh_table"">
                                        <if condition="$weigh_codes">
                                            <tr>
                                                <td style="padding-left: 12px;">{pigcms{:L_("称码")}</td>
                                                <td style="padding-left: 12px;">{pigcms{:L_("别名")}</td>
                                                <td style="padding-left: 12px;">{pigcms{:L_("操作")}</td>
                                            </tr>
                                            <volist name="weigh_codes" id="weigh_val">
                                                <tr class="weigh_tr" style="padding-left: 12px;">
                                                    <td><input type="text" class="c_weigh_code" value="{pigcms{$weigh_val['weigh_code']}"></td>
                                                    <td><input type="text" class="c_alias_name" value="{pigcms{$weigh_val['alias_name']}"></td>
                                                    <td><a href="javascript:void(0);" data-weigh_id="{pigcms{$weigh_val['id']}" class="edit_weight">{pigcms{:L_("编辑")}</a>|<a href="javascript:void(0);" data-weigh_id="{pigcms{$weigh_val['id']}" class="del_weight">{pigcms{:L_("删除")}</a></td>
                                                </tr>
                                            </volist>
                                        </if>
                                        </table>
                                    </div>
                                </div>
                            </if>
                            <if condition="$config.open_youzanyun eq 1">
                                <div id="youzanyun" class="tab-pane">
                                    <div class="form-group">
										<label class="col-sm-2">{pigcms{:L_("有赞云client id")}</label>
										<input class="col-sm-3" size="200" name="youzanyun_client_id"  type="text" value="{pigcms{$store_shop.youzanyun_client_id}" />
									</div>
                                    <div class="form-group">
										<label class="col-sm-2">{pigcms{:L_("有赞云client secret")}</label>
										<input class="col-sm-3" size="200" name="youzanyun_client_secret" type="text" value="{pigcms{$store_shop.youzanyun_client_secret}" />
									</div>
                                    <div class="form-group">
										<label class="col-sm-2">{pigcms{:L_("有赞云grant id")}</label>
										<input class="col-sm-3" size="200" name="youzanyun_grant_id" type="text" value="{pigcms{$store_shop.youzanyun_grant_id}" />
									</div>
                                    <div class="form-group">
										<label class="col-sm-2">{pigcms{:L_("有赞云store id")}</label>
										<input class="col-sm-3" size="200" name="youzanyun_store_id" type="text" value="{pigcms{$store_shop.youzanyun_store_id}" />
									</div>
	                                <div class="form-group">
	                                    <label class="col-sm-2" for="youzanyun_update_stock"><label>{pigcms{:L_("支付后是否减有赞云商品库存")}</label></label>
	                                    <select name="youzanyun_update_stock" id="youzanyun_update_stock">
	                                        <option value="1" <if condition="$store_shop['youzanyun_update_stock'] eq 1"> selected</if>>{pigcms{:L_("是")}</option>
	                                        <option value="0" <if condition="$store_shop['youzanyun_update_stock'] eq 0"> selected</if>>{pigcms{:L_("否")}</option>
	                                    </select>
	                                </div>
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
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
.webuploader-container{
	position:relative;
}
.webuploader-container div{
	width: 78px!important;
    height: 34px!important;
}
input.ke-input-text {
	background-color: #FFFFFF;
	background-color: #FFFFFF!important;
	font-family: "sans serif",tahoma,verdana,helvetica;
	font-size: 12px;
	line-height: 24px;
	height: 24px;
	padding: 2px 4px;
	border-color: #848484 #E0E0E0 #E0E0E0 #848484;
	border-style: solid;
	border-width: 1px;
	display: -moz-inline-stack;
	display: inline-block;
	vertical-align: middle;
	zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.webuploader-element-invisible {
    position: absolute !important;
    clip: rect(1px 1px 1px 1px);
    clip: rect(1px,1px,1px,1px);
}
.webuploader-pick-hover .btn{
	background-color: #629b58!important;
    border-color: #87b87f;
}
.live_cate{
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 2px;
    padding: 5px 8px;
    width: 10%;
    margin: 0px 10px;
}
.live_url{
    width: 40%;
}
.live_del{
    margin-left: 20px;
    color: #FF0000;
}
.clear_store_live_pic{
    margin-left: 2rem;
}
.show_pic{
    min-height: 100px;
    min-width: 100px;
    display: inline-block;
    margin-left: 10px
}
.upload_store_live_pic{
    margin-left: 1rem;
}
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=drawing&key={pigcms{$config.google_map_ak}"></script>
    <script>
        var polygon = '{pigcms{$store_shop['delivery_range_polygon']}';
        if(polygon){
            polygon = $.parseJSON(polygon);
            var nowlat = '{pigcms{$now_store["lat"]}';
            var nowlng = '{pigcms{$now_store["long"]}';
        }else{
            var nowlat = '{pigcms{$now_store["lat"]}';
            var nowlng = '{pigcms{$now_store["long"]}';
        }
        var bermudaTriangle;
        var map;
        var drawingManager;
        var selectedShape;
        $(document).ready(function() {
            $('input[type="radio"][name="delivery_range_type"]').click(function () {
                if ($(this).val() == 1) {
                    $('input[name="delivery_radius"]').attr('disabled', true);
                    $('.map').show();
                } else {
                    $('input[name="delivery_radius"]').attr('disabled', false);
                    $('.map').hide();
                }
            });
        });
        initMap();
        function initMap() {
            map = new google.maps.Map(document.getElementById('allmap'), {
                zoom: 14,
                center: {lat: parseFloat(nowlat), lng: parseFloat(nowlng)}
            });
            var marker = new google.maps.Marker({
                position: {lat: parseFloat(nowlat), lng: parseFloat(nowlng)},
                map: map,
                title: '{pigcms{:L_("我的位置")}'
            });
            google.maps.event.addDomListener(document.getElementById('baiduMap'), 'click', deleteSelectedShape);
            // Define the LatLng coordinates for the polygon's path.
            //"绘画工具 设置")}
            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.POLYGON
                    ]
                },
                //"设置图形显示样式")}
                circleOptions: {
                    fillColor: '#ffff00',
                    fillOpacity: 1,
                    strokeWeight: 5,
                    clickable: false,
                    editable: true,
                    zIndex: 1
                },
                polygonOptions: {
                    strokeColor: "#FF0000",
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: "#FF0000",
                    fillOpacity: 0.35,
                    editable: true,
                }
            });
            drawingManager.setMap(map);
            //"注册 多边形 绘制完成事件")}
            google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
                var array = polygon.getPath().getArray();
                showLonLat(array);
            });
            //"将写入的加入数组")}
            google.maps.event.addListener(drawingManager, 'overlaycomplete', function (e) {
                var newShape = e.overlay;

                newShape.type = e.type;

                if (e.type !== google.maps.drawing.OverlayType.MARKER) {
                    // Switch back to non-drawing mode after drawing a shape.
                    drawingManager.setDrawingMode(null);

                    // Add an event listener that selects the newly-drawn shape when the user
                    // mouses down on it.
                    google.maps.event.addListener(newShape, 'click', function (e) {
                        if (e.vertex !== undefined) {
                            if (newShape.type === google.maps.drawing.OverlayType.POLYGON) {
                                var path = newShape.getPaths().getAt(e.path);
                                path.removeAt(e.vertex);
                                if (path.length < 3) {
                                    newShape.setMap(null);
                                }
                            }
                            if (newShape.type === google.maps.drawing.OverlayType.POLYLINE) {
                                var path = newShape.getPath();
                                path.removeAt(e.vertex);
                                if (path.length < 2) {
                                    newShape.setMap(null);
                                }
                            }
                        }
                        setSelection(newShape);
                    });
                    setSelection(newShape);
                }
                else {
                    google.maps.event.addListener(newShape, 'click', function (e) {
                        setSelection(newShape);
                    });
                    setSelection(newShape);
                }
            });
            //"循环显示 经纬度")}
            var latlng = [];
            function showLonLat(arr) {
                for (var i = 0; i < arr.length; i++) {

                    latlng.push(arr[i].lat() + ',' + arr[i].lng());
                    $('#delivery_range_polygon').val(latlng.join('|'));
                }
                ;
            }

            var polyCoords = [];
            var lat_lng = [];
            for (i = 0; i < polygon.length; i++) {
                for (k = 0; k < polygon[i].length; k++) {
                    polyCoords.push({lat: +parseFloat(polygon[i][k].lat), lng: +parseFloat(polygon[i][k].lng)});
                    lat_lng.push(polygon[i][k].lat + ',' + polygon[i][k].lng);
                }
            }
            $('#delivery_range_polygon').val(lat_lng.join('|'));
            // Construct the polygon.
            bermudaTriangle = new google.maps.Polygon({
                paths: polyCoords,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35
            });
            addPolygon();
            function setSelection (shape) {
                selectedShape = shape;
            }
            function deleteSelectedShape(){
                bermudaTriangle.setMap(null);
                if (selectedShape) {
                    selectedShape.setMap(null);
                }
            }

            function addPolygon() {
                bermudaTriangle.setMap(map);
            }
        }

    </script>
<else />
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
<link rel="stylesheet" href="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.css" />
    <script>
        var polygon = '{pigcms{$store_shop['delivery_range_polygon']}';
        polygon = $.parseJSON(polygon);
        var oldOverlay = [];
        $(document).ready(function(){

            $('input[type="radio"][name="delivery_range_type"]').click(function(){
                if ($(this).val() == 1) {
                    $('input[name="delivery_radius"]').attr('disabled', true);
                    $('.map').show();
                } else {
                    $('input[name="delivery_radius"]').attr('disabled', false);
                    $('.map').hide();
                }
            });
            var map = new BMap.Map("allmap",{"enableMapClick":false}), point = new BMap.Point('{pigcms{$now_store["long"]}', '{pigcms{$now_store["lat"]}');
            map.centerAndZoom(point, 15);
            map.enableScrollWheelZoom();
            var marker = new BMap.Marker(point);// "创建标注")}
            map.addOverlay(marker);
            marker.enableDragging();
            if (polygon != null) {
                for (var i in polygon) {
                    var polygonArr = [];
                    var lat_lng = [];
                    for (var ii in polygon[i]) {
                        polygonArr.push(new BMap.Point(polygon[i][ii].lng, polygon[i][ii].lat));
                        lat_lng.push(polygon[i][ii].lat + ',' + polygon[i][ii].lng);
                    }
                    $('#delivery_range_polygon').val(lat_lng.join('|'));

                    var poly = new BMap.Polygon(polygonArr, {strokeColor:"rgb(51, 136, 255)",fillColor:"rgb(51, 136, 255)", strokeWeight:2, fillOpacity: 0.2, strokeOpacity:0.8,strokeStyle:'dashed'});

                    map.addOverlay(poly);  //"创建多边形")}
                    poly.enableEditing();
					oldOverlay.push(poly);
					poly.addEventListener("lineupdate", function (e) {
						lat_lng = [];
						delivery_area = $.map(e.target.getPath(), function (item) {
							  lat_lng.push(item.lat + ',' + item.lng);
							  return {'lng': item.lng, 'lat': item.lat};
						});
						$('#delivery_range_polygon').val(lat_lng.join('|'));
					});
                }
            }

            var overlays = [];
            var overlaycomplete = function(e){
                overlays.push(e.overlay);
                var latLng = e.overlay.getPath();
                var lat_lng = [];
				var polygonArr = [];
				for (var i in latLng) {
					lat_lng.push(latLng[i].lat + ',' + latLng[i].lng);
					console.log( lat_lng)
					 polygonArr.push(new BMap.Point(latLng[i].lng, latLng[i].lat));
					   
				}
				
				 var poly = new BMap.Polygon(polygonArr, {strokeColor:"rgb(51, 136, 255)",fillColor:"rgb(51, 136, 255)", strokeWeight:2, fillOpacity: 0.2, strokeOpacity:0.8,strokeStyle:'dashed'});
					
				map.addOverlay(poly);  //"创建多边形")}
				poly.enableEditing();
				poly.addEventListener("lineupdate", function (e) {
					lat_lng = [];
					delivery_area = $.map(e.target.getPath(), function (item) {
						  lat_lng.push(item.lat + ',' + item.lng);
						  return {'lng': item.lng, 'lat': item.lat};
					});
					$('#delivery_range_polygon').val(lat_lng.join('|'));
					
				});
				oldOverlay.push(poly)
				map.removeOverlay(e.overlay);
                $('#delivery_range_polygon').val(lat_lng.join('|'));
            };
            var styleOptions = {
                strokeColor:"rgb(51, 136, 255)",    //"边线颜色。")}
				fillColor:"rgb(51, 136, 255)",      //"填充颜色。当参数为空时，圆形将没有填充效果。")}
				strokeWeight: 3,       //"边线的宽度，以像素为单位。")}
				strokeOpacity: 0.8,	   //"边线透明度，取值范围")}0 - 1。
				fillOpacity: 0.6,      //"填充的透明度，取值范围")}0 - 1。
				strokeStyle: 'dashed' //"边线的样式，solid或dashed。")}
            }
            //"实例化鼠标绘制工具")}
            var drawingManager = new BMapLib.DrawingManager(map, {
                isOpen: false, //"是否开启绘制模式")}
                enableDrawingTool: false, //"是否显示工具栏")}
                drawingMode:BMAP_DRAWING_POLYGON,
                drawingToolOptions: {
                    anchor: BMAP_ANCHOR_TOP_RIGHT, //"位置")}
                    offset: new BMap.Size(5, 5), //"偏离值")}
                },
                circleOptions: styleOptions, //"圆的样式")}
                polylineOptions: styleOptions, //"线的样式")}
                polygonOptions: styleOptions, //"多边形的样式")}
                rectangleOptions: styleOptions //"矩形的样式")}
            });


            $('#baiduMap').click(function(){
                drawingManager._open();
                for(var i = 0; i < overlays.length; i++){
                    map.removeOverlay(overlays[i]);
                }
                if (oldOverlay.length > 0) {
                    console.log(oldOverlay);
                    for(var i = 0; i < oldOverlay.length; i++){
                        map.removeOverlay(oldOverlay[i]);
                    }
                }
                overlays = [];
                oldOverlay = [];
                 polygonArr= [];
            });

            //"添加鼠标绘制工具监听事件，用于获取绘制结果")}
            drawingManager.addEventListener('overlaycomplete', overlaycomplete);
        });
    </script>
</if>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script  type="text/javascript">
    var store_id = "{pigcms{$now_store['store_id']}";
    KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager: true
        });
        $('#selectFile').click(function () {
            editor.uploadJson = "{pigcms{:U('Shop/ajax_upload_zip')}&store_id=" + store_id;
            editor.loadPlugin('insertfile', function () {
                editor.plugin.fileDialog({
                    showRemote: false,
                    clickFn: function (url, title, width, height, border, align) {
                        // if(url.indexOf('/') === 0){
                        //     url = site_url + url;
                        // }
                        $('#store_vr_zip').val(url);
                        editor.hideDialog();
                    }
                });
                setTimeout(function () {
                    $('.ke-dialog').css('top', $('#Main_content', parent.document).scrollTop() + ((screen.height - $('.ke-dialog').height()) / 2) + 'px');
                }, 200);
            });
        });
    });

    $('input[name=pack_type]').on('change',function(){
        if($(this).val() == 2){
            $('#pack_fee').show();
        }else{
            $('#pack_fee').hide();
        }
    })
</script>
<script>
var uploader = WebUploader.create({
	auto: true,
	swf: '{pigcms{$static_public}js/Uploader.swf',
	server: "{pigcms{:U('Shop/ajax_upload_shoppicajax_upload_shoppic', array('store_id' => $now_store['store_id']))}",
	pick: '#J_selectImage',
	accept: {
		title: 'Images',
		extensions: 'gif,jpg,jpeg,png',
		mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
	}
});
uploader.on('fileQueued',function(file){
	if($('.upload_pic_li').size() >= 1){
		uploader.cancelFile(file);
		alert('{pigcms{:L_("最多上传一张图片！")}');
		return false;
	}
});
uploader.on('uploadSuccess',function(file,response){
	if(response.error == 0){
		$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+response.url+'"/><input type="hidden" name="background" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
	}else{
		alert(response.info);
	}
});

uploader.on('uploadError', function(file,reason){
	$('.loading'+file.id).remove();
	alert('{pigcms{:L_("上传失败！请重试。")}');
});


function deleteImage(path,obj){
	$.post("{pigcms{:U('Shop/ajax_del_shoppic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}

function check(obj){
	var length = $('.paycheck:checked').length;
	if(length == 0){
		$(obj).attr('checked','checked');
		bootbox.alert('{pigcms{:L_("最少要选择一种支付方式")}');
	}			
}

var fitment_header_bgcolor = "{pigcms{$now_store.shop_fitment_color|default='06C1AE'}";
$(function($){
    if ($('select[name=deliver_type]').val() == 0) {
        $('#showDeliverTips').html('{pigcms{:LF_("注：如果开启了自有支付，那么")}{pigcms{$config['deliver_name']}{pigcms{:LF_("是无效的！")}{pigcms{$config['deliver_name']}{pigcms{:LF_("时服务距离由平台来设置！")}');
    } else if ($('select[name=deliver_type]').val() == 2 || $('select[name=deliver_type]').val() == 4) {
        $('#showDeliverTips').html('{pigcms{:LF_("注：如果使用自提功能请及时")}  <a href="{pigcms{:U('Config/pick_address_add')}" target="_black">{pigcms{:LF_("添加自提点地址")}</a>; {pigcms{:LF_("如果没有自提点时，则系统默认将该店铺的地址作为自提点并且店员只可直接确认自提不可分配自提")}');
    } else if ($('select[name=deliver_type]').val() == 3) {
        $('#showDeliverTips').html('{pigcms{:LF_("注：如果使用自提功能请及时")}  <a href="{pigcms{:U('Config/pick_address_add')}" target="_black">{pigcms{:LF_("添加自提点地址")}</a>; {pigcms{:LF_("如果没有自提点时，则系统默认将该店铺的地址作为自提点并且店员只可直接确认自提不可分配自提，如果开启了自有支付，那么")}{pigcms{$config['deliver_name']}{pigcms{:LF_("是无效的！")}{pigcms{$config['deliver_name']}{pigcms{:LF_("时服务距离由平台来设置！")}');
    } else if ($(this).val() == 1) {
        $('#showDeliverTips').html('');
    } else if ($(this).val() == 5) {
        $('#showDeliverTips').html('{pigcms{:LF_("快递配送：没有服务距离的限制，按配送时间段一的设置来计算配送费")};');
    }
	$('select[name=deliver_type]').change(function(){
        if ($(this).val() == 0) {
            $('#showDeliverTips').html('{pigcms{:LF_("注：如果开启了自有支付，那么")}{pigcms{$config['deliver_name']}{pigcms{:LF_("是无效的！")}{pigcms{$config['deliver_name']}{pigcms{:LF_("时服务距离由平台来设置！")}');
        } else if ($(this).val() == 2 || $(this).val() == 4) {
            $('#showDeliverTips').html('{pigcms{:LF_("注：如果使用自提功能请及时")}  <a href="{pigcms{:U('Config/pick_address_add')}" target="_black">{pigcms{:LF_("添加自提点地址")}</a>; {pigcms{:LF_("如果没有自提点时，则系统默认将该店铺的地址作为自提点并且店员只可直接确认自提不可分配自提")}');
        } else if ($(this).val() == 3) {
            $('#showDeliverTips').html('{pigcms{:LF_("注：如果使用自提功能请及时")}  <a href="{pigcms{:U('Config/pick_address_add')}" target="_black">{pigcms{:LF_("添加自提点地址")}</a>; {pigcms{:LF_("如果没有自提点时，则系统默认将该店铺的地址作为自提点并且店员只可直接确认自提不可分配自提，如果开启了自有支付，那么")}{pigcms{$config['deliver_name']}{pigcms{:LF_("是无效的！")}{pigcms{$config['deliver_name']}{pigcms{:LF_("时服务距离由平台来设置！")}');
        } else if ($(this).val() == 1) {
            $('#showDeliverTips').html('');
        } else if ($(this).val() == 5) {
            $('#showDeliverTips').html('{pigcms{:LF_("快递配送：没有服务距离的限制，按配送时间段一的设置来计算配送费")};');
        }
		if ($(this).val() == 1 || $(this).val() == 4) {
			$('.deliver').css('display', 'block');
		} else {
			$('.deliver').css('display', 'none');
		}
		if ($(this).val() == 5) {
            $('.deliver_time').css('display', 'block');
        }
		if ($(this).val() == 2) {
			$('.basic_price').css('display', 'none');
		} else {
			$('.basic_price').css('display', 'block');
		}
	});
    
	$('select[name=store_theme]').change(function(){
		if ($(this).val() == 1) {
			$('.background').show();
		} else {
			$('.background').hide();
		}
	});
	$('#delivertime_start').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
	$('#delivertime_stop').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
	$('#delivertime_start2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#delivertime_stop2').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
    $('#delivertime_start3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#delivertime_stop3').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));

	$('#edit_form').submit(function(){
		$.post("{pigcms{:U('Shop/shop_edit',array('store_id'=>$_GET['store_id']))}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Shop/index')}";
			}else{
				alert(result.info);
			}
		})
		return false;
	});
	
	$('.fitment').click(function(){
		var _this = $(this);
        if(_this.data('type') == 'shop_fitment_slider') {
            window.top.artiframe('{pigcms{$config['site_url']}/v20/public/platform/#/dialog/merchant.shopNewEdit/SlideList?store_id={pigcms{$_GET.store_id}&mer_id={pigcms{$_GET.mer_id}',_this.data('title'),'60%',600);
        } else {
            layer.open({
                title:'{pigcms{:L_("页面装修")} -> ' + _this.data('title'),
                type:2,
                fixed:false,
                content:'{pigcms{$this->config['site_url']}/merchant.php?c=Shop&a='+_this.data('type')+'&store_id={pigcms{$_GET.store_id}',
                area:['600px','600px'],
                offset:[$('#store_fitment').offset().top+'px',$('#store_fitment').offset().left-30+'px'],
                shade: 0.01,
                cancel:function(){
                    if(_this.data('type') == 'shop_fitment_color'){
                        $('#fitment_preview .fitment_header').css('background-color','#' + fitment_header_bgcolor);
                    }
                },
                move:false
                // ,shadeClose: false
            });
        }
	});
	
	$('.fitment_subject img').click(function(){
		$('.fitment.subject').trigger('click');
	});
	$('.fitment_showcase img').click(function(){
		$('.fitment.showcase').trigger('click');
	});
	
	console.log($('.page-content').width());
	$('#fitment_preview').css('left',(500 + ($('.page-content').width()-500 - 350)/2)+'px');
});
</script>
<script>
    $('#add_weigh_code').click(function(){
        if($('#weigh_code').val()!=''){
            var weigh_code = $('#weigh_code').val();
        }else{
            alert('{pigcms{:L_("请输入称码！")}');
        }
        var alias_name = '';
        if($('#alias_name').val()!=''){
            alias_name = $('#alias_name').val();
        }
        $.post("{pigcms{:U('Shop/add_weigh_code')}",{store_id:store_id,weigh_code:weigh_code,alias_name:alias_name},function(res){
            if(res.err_code==1){
                alert(res.msg);
                return false;
            }else {
                alert(res.msg);
            }
        },'json')
    });
    $('.edit_weight').click(function(){
        var weigh_id = $(this).data('weigh_id');
        var weigh_code = $(this).parents().find('.c_weigh_code').val();
        var alias_name = $(this).parents().find('.c_alias_name').val();
        if(typeof alias_name != "string"){
            alias_name='';
        }
        $.post("{pigcms{:U('Shop/add_weigh_code')}",{weigh_id:weigh_id,store_id:store_id,weigh_code:weigh_code,alias_name:alias_name},function(res){
            if(res.err_code==1){
                alert(res.msg);
                return false;
            }else{
                alert(res.msg);
            }
        },'json')
    });
    $('.del_weight').click(function(){
        var weigh_id = $(this).data('weigh_id');
        if(confirm('{pigcms{:L_("确定删除吗？")}')){
            $.post("{pigcms{:U('Shop/del_weigh_code')}",{weigh_id:weigh_id},function(res){
                if(res.err_code==1){
                    alert(res.msg);
                    return false;
                }else{
                    alert(res.msg);
                }
            },'json')
        }
	});

    // "关联商品")}
    function save_good(is_edit) {
        is_edit = is_edit || 0;
        parent.layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: '{pigcms{$this->config['
            site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_list&store_id=388&group_id=42&keyword=大',
            area: ['650px', '576px'],
            shade: 0.2,
            cancel: function () {

            },
            move: false
        });
    }

//"选择规格后的展示")}
	function login_box_after(goods_id,spec_id,spec_name,name,price,is_edit,index){ 
		var key=goods_id+':'+spec_id;
		var flag=false;
		$('#goods_spec tr:gt(0)').each(function(){
			if($(this).data('key')==key){ 
				if(spec_id==''){
					layer.msg('{pigcms{:L_("该商品的已添加，请勿重复添加！")}');
				}else{
					layer.msg('{pigcms{:L_("商品的该规格已添加，请勿重复添加！")}');
				}
				flag=true;
				return false;
			}
		});
		if(flag){
			return false;
		}
		var stock_num='';
		$.ajax({
			type:'post',
			url:"{pigcms{:U('Mall/get_spec_stock')}",
			data:{goods_id:goods_id,spec_id:spec_id},
			async:false,
			success:function(res){
				if(res.status){
					stock_num=res.stock_num;
				}
			}
		});
		default_spec_id=spec_id;
		var html='';
		html+='<input type="hidden" name="goods_id[]" value="'+goods_id+'" />';
		html+='<input type="hidden" name="spec_id[]" value="'+spec_id+'" />';
		html+='<input type="hidden" name="spec_price[]" value="'+price+'" />';
		html+='<td>'+goods_id+'</td>';
		html+='<td>'+name+'</td>';
		html+='<td>'+spec_name+'</td>';
		html+='<td>'+price+'</td>';
		html+='<td>'+stock_num+'</td>';
		var floor_price=$('input[name=floor_price]').val();
		if(floor_price>0 && floor_price>=price){
			var tips="({pigcms{:L_("提示：砍价底价应该小于商品原价！")})";
			$('#tips').text(tips);
		}else{
			$('#tips').hide();
		}
		//"更新商品价格")}
		goods_price=price;
		if(is_edit>0){
			html+='<td><button type="button" onClick="del_good(this)">{pigcms{:L_("删除")}</button></td>';
			var obj=$('<tr>',{'data-key':key}).html(html);
			$('#goods_spec').find('tr:gt(0)').remove();
			$('#goods_spec').append(obj);
		}else{
			html+='<td><button type="button" onClick="del_good(this)">{pigcms{:L_("删除")}</button></td>'; 
			var obj=$('<tr>',{'data-key':key}).html(html);
			$('#goods_spec').append(obj);
			// $('.choose_good_btn').hide();	
		}
		// layer.close(index);
	}
    //"删除添加的规格商品")}
	function del_good(e){
		$(e).parents('tr').remove();
	}

	//直播视频
    KindEditor.ready(function(K){
        var site_url = "{pigcms{$config.site_url}";
        var editor = K.editor({
            allowFileManager : true
        });

        $('.upload_store_live_pic').live('click',function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/store_ajax_upload_pic',array('path'=>'store_live'))}";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        upload_file_btn.siblings('.live_pic').val(url);
                        upload_file_btn.siblings('.show_pic').html("<img style='max-width: 130px;' src='" + site_url + url + "'>");
                        editor.hideDialog();
                    }
                });
            });
        })
    });

    $('.add_live_input').on('click', function () {
        var _html = '<div style="border-bottom: 1px solid #ddd;margin-bottom: 10px;">\
                        <div class="form-group">\
                            <label class="col-sm-1">{pigcms{:L_("分类名称：")}</label>\
                            <input class="live_cate" name="live_cate[]"/>\
                            <span class="live_del">删除</span>\
                        </div>\
                        <div class="form-group">\
                            <label class="col-sm-1">{pigcms{:L_("监控地址：")}</label>\
                            <input class="live_cate live_url" name="live_url[]"/>\
                        </div>\
                        <div class="form-group">\
                            <label class="col-sm-1">{pigcms{:L_("封面图片：")}</label>\
                            <div class="show_pic"></div>\
                            <input type="hidden" class="live_cate live_url live_pic" name="live_pic[]"/>\
                            <a href="javascript:void(0)" class="upload_store_live_pic">{pigcms{:L_("上传图片")}</a>\
                            <a href="javascript:void(0)" class="clear_store_live_pic">{pigcms{:L_("清空图片")}</a>\
                        </div>\
                      </div>';
        $(_html).insertBefore('.add_live_input');
    })

    $('.live_del').live('click', function () {
        $(this).parent('.form-group').parent('div').remove();
    })
    $('.clear_store_live_pic').live('click',function(){
        $(this).siblings('.live_pic').val('');
        $(this).siblings('.show_pic').html('');
    })

    //订餐提醒
    $('input[name=is_remind]').on('click', function () {
        if ($(this).val() == 0) {
            $('.remind_item').hide();
        } else {
            $('.remind_item').show();
        }
    })
    $('input[name=is_remind]:checked').trigger('click');

	//刷新预览店铺页面装修
	function refreshIframe(){
		var iframe_url = $('#viewpageIframe').attr('src');
		$('#viewpageIframe').attr('src',iframe_url);
	}
</script>
<style type="text/css">
	#store_fitment{
		margin-left:30px;
		position:relative;
	}
	.store_fitment_div{
		width:500px;
	}
	#store_fitment .form-group{
		border-bottom:1px solid #EEE;
		padding:15px 0;
	}
	#store_fitment .form-group.cat-group{
		border-bottom:0;
	}
	#fitment_preview{
		position: absolute;
		left: 650px;
		top: 0;
		width:315px;
		-webkit-user-select: none;
		-webkit-touch-callout: none;
	}
	#fitment_preview img{
		width:100%;
	}
	.fitment_subject img:hover,.fitment_showcase img:hover{
		cursor:pointer;
		border:2px solid blue;
	}
	
	.group-title{
		position:relative;
		font-size:18px;
		color:black;
		margin-bottom:10px;
	}
	.group-content{
		width:320px;
		line-height:1.6;
		font-size:14px;
		color:#999;
	}
	.group-title .btn.fitment{
		background-color: #438eb9!important;
		border-radius: 3px;
		border: none;
		position:absolute;
		right:0px;
		top:0px;
	}
	.group-content.cat{
		width:100%;
	}
	.group-content .cat-row{
		margin-top:20px;
		position:relative;
	}
	.group-content .cat-name{
		font-size:16px;
		color:black;
		float:left;
		width:30%;
	}
	.group-content .cat-desc{
		font-size:14px;
		float:left;
		width:70%;
		border-bottom:1px solid #EEE;
		padding-bottom: 20px;
	}
	.group-content .cat-edit{
		position:absolute;
		right:0px;
		top:0px;
	}
    .weigh_table td{
        padding-left: 12px;
    }

	#goods_spec{width:600px;border-color: #ccc;border-style: solid;border-width: 1px 0 0 1px;margin-bottom:10px;}
	#goods_spec tr:first-child{background:#eee;}
	#goods_spec th{height:32px;}
	#goods_spec th,#goods_spec td{border-color: #ccc;border-style: solid;border-width: 0 1px 1px 0;text-align: center;height:32px;}
	#goods_spec input{text-indent:3px;margin:5px 0;height:26px;}
</style>
<include file="Public:footer"/>
<script>
    import Vote_list from "../../../../packapp/material_diy_dev/src/pages/write_list";
    export default {
        components: {Vote_list}
    }
</script>