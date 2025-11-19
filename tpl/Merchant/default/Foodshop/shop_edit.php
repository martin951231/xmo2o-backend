<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑店铺信息")}</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
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
							<!--<li>
								<a data-toggle="tab" href="#supplier_index_nav">{pigcms{:L_("店铺主页自定义导航")}</a>
							</li>-->
							<!--li>
								<a data-toggle="tab" href="#promotion">{pigcms{:L_("店铺折扣")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#stock">{pigcms{:L_("库存类型选择")}</a>
							</li-->
						  	<if condition="!empty($levelarr) AND false">
							<li>
								<a data-toggle="tab" href="#levelcoupon">{pigcms{:L_("会员优惠")}</a>
							</li>
							</if>

						  	<if condition="$config['open_meal_electronic_menu']">
							<li>
								<a data-toggle="tab" href="#store_fitment">{pigcms{:L_("页面装修")}</a>
							</li>
							</if>

						  	<if condition="$config['open_meal_activity']">
							<li>
								<a data-toggle="tab" href="#activity_content">{pigcms{:L_("活动")}</a>
							</li>
							</if>
<!--                            <li>-->
<!--                                <a data-toggle="tab" href="#v20_store_fitment">{pigcms{:L_("餐饮2.0页面装修")}</a>-->
<!--                            </li>-->
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("餐厅环境图片")}</label>
                                    <div style="display:inline-block;" id="J_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips">{pigcms{:L_("第一张将作为主图片！最多上传5个图片！图片宽度建议为900px，高度建议为450px。")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
                                    <div id="upload_pic_box">
                                        <ul id="upload_pic_ul">
                                            <volist name="store_shop['pic_arr']" id="vo">
                                                <li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
                                            </volist>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("点餐页背景图")}</label>
                                    <div style="display:inline-block;" id="background">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips">{pigcms{:L_("系统默认给一张默认图！图片建议尺寸为1080*1920，大小小于1M。")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
                                    <div id="upload_pic_box">
                                        <ul id="upload_backgroud_li">
                                            <volist name="store_shop['background_arr']" id="vo">
                                                <li class="upload_backgroud_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="background[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
                                            </volist>
                                        </ul>
                                    </div>
                                </div>
								<if condition="empty($store_shop)">
								<div class="alert alert-info" style="margin:10px;">
								<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>{pigcms{:L_("同步数据只能在完善店铺信息的时候同步，以后修改店铺时不允许同步")}
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("同步原餐饮的商品")}</label></label>
									<label><input name="sysnc" value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不同步")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="sysnc" checked="checked" value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("同步")}</label>&nbsp;&nbsp;&nbsp;
								</div>
								</if>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="Config_notice">{pigcms{:L_("店铺公告")}</label></label>
									<textarea class="col-sm-3" rows="4" name="store_notice" id="Config_notice">{pigcms{$store_shop.store_notice}</textarea>
								</div>
								
								<!--div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("开发票")}</label></label>
									<label><input name="is_invoice" <if condition="$store_shop['is_invoice'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不支持")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_invoice" <if condition="$store_shop['is_invoice'] eq 1 ">checked="checked"</if> value="1" type="radio">&nbsp;&nbsp;{pigcms{:L_("支持")}</label>&nbsp;&nbsp;&nbsp;
								</div>
								
								<div class="form-group invoice" <if condition="$store_shop['is_invoice'] eq 0 ">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("满足")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="invoice_price" id="Config_invoice_price" type="text" value="{pigcms{$store_shop.invoice_price|floatval}" />
									<label class="col-sm-1">{pigcms{$config['Currency_txt']}{pigcms{:L_("，可开发票")}</label>
								</div-->
								
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("预订")}</label></label>
									<label><input name="is_book" <if condition="$store_shop['is_book'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不支持")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_book" <if condition="$store_shop['is_book'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("支持")}</label>
								</div>
								<div class="form-group book" <if condition="$store_shop['is_book'] eq 0 ">style="display:none"</if>>
									<label class="col-sm-1"><label>{pigcms{:L_("预订时长")}</label></label>
									<div>
										<input id="book_day" type="text" value="{pigcms{$store_shop.book_day|default=1}" name="book_day"/>
										<span class="form_tips red">{pigcms{:L_("可提前预订多少天后的桌台")}</span>
									</div>
								</div>
								<div class="form-group book" <if condition="$store_shop['is_book'] eq 0 ">style="display:none"</if>>
									<label class="col-sm-1"><label>{pigcms{:L_("预订时间")}</label></label>
									<div>
										<input id="book_start" type="text" value="{pigcms{$store_shop.book_start|default='00:00'}" name="book_start" readonly/>	{pigcms{:L_("至")} 
										<input id="book_stop" type="text" value="{pigcms{$store_shop.book_stop|default='23:59'}" name="book_stop" readonly/>
										<span class="form_tips red">{pigcms{:L_("如果两个都不填写的话，表示从零点开始，按预订间隔时长进行全天预订")}</span>
									</div>
								</div>
								<div class="form-group book" <if condition="$store_shop['is_book'] eq 0 ">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("预订间隔时长")}</label>
									<input class="col-sm-1" name="book_time" type="text" value="{pigcms{$store_shop.book_time|default=60}" />
									<span class="form_tips red">{pigcms{:L_("两个可预订时间之间相隔的时长，单位（分钟）")}</span>
								</div>
								<div class="form-group book">
									<label class="col-sm-1">{pigcms{:L_("服务费别称")}</label>
									<input class="col-sm-1" name="table_service_fee_alias" type="text" value="{pigcms{$store_shop.table_service_fee_alias}" />
									<span class="form_tips red">{pigcms{:L_("不填默认为“服务费”")}</span>
								</div>
								<div class="form-group book" <if condition="$store_shop['is_book'] eq 0 ">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("提前取消时长")}</label>
									<input class="col-sm-1" name="cancel_time" type="text" value="{pigcms{$store_shop.cancel_time|default=60}" />
									<span class="form_tips red">{pigcms{:L_("至少要提前多久才能取消，否则不能取消，单位（分钟）")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("排号")}</label></label>
									<label><input name="is_queue" <if condition="$store_shop['is_queue'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不支持")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_queue" <if condition="$store_shop['is_queue'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("支持")}</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("外送")}</label></label>
									<label><input name="is_takeout" <if condition="$store_shop['is_takeout'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不支持")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_takeout" <if condition="$store_shop['is_takeout'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("支持")}</label>
								</div>
								<if condition="$config['open_meal_electronic_menu']">
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否开启扫码点菜模式")}</label></label>
									<label><input name="open_from_shop" <if condition="$store_shop['open_from_shop'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不开启")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="open_from_shop" <if condition="$store_shop['open_from_shop'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
								</div>
								</if>
								<if condition="$config['meal_unbind_table']">
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否开启用户下单无需绑定桌台即可下单")}</label></label>
									<label><input name="open_unbind_table" <if condition="$store_shop['open_unbind_table'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不开启")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="open_unbind_table" <if condition="$store_shop['open_unbind_table'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
									<span class="form_tips red">MERCHANT_FOODSHOP_SHOP_EDIT_1</span>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("扫码点餐结算形式")}</label></label>
									<label><input name="account_type" <if condition="$store_shop['account_type'] eq 1 ">checked="checked"</if> value="1" type="radio">&nbsp;&nbsp;{pigcms{:L_("先点餐后结算")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="account_type" <if condition="$store_shop['account_type'] eq 2 ">checked="checked"</if> value="2" type="radio" >&nbsp;&nbsp;{pigcms{:L_("先结算后点餐")}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("停车位")}</label></label>
									<label><input name="is_park" <if condition="$store_shop['is_park'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("没有")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_park" <if condition="$store_shop['is_park'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;有</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("整单打印是否只打印归属打印机的菜品 ")}</label></label>
									<label><input name="print_belong_goods" <if condition="$store_shop['print_belong_goods'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="print_belong_goods" <if condition="$store_shop['print_belong_goods'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;是</label>
									<span class="form_tips">{pigcms{:L_(" 选择是，则整单打印时只打印一个订单中归属打印机的菜品，而不是一个订单中的所有菜品")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("扫码立即锁定桌台")}</label>
									<label><input name="scan_lock_table" <if condition="$store_shop['scan_lock_table'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不锁定")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="scan_lock_table" <if condition="$store_shop['scan_lock_table'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("锁定")}</label>
									<span class="form_tips">{pigcms{:L_("不锁定的意义：“为了防止用户随意扫了桌台二维码后就自动锁定了桌台，影响真实点餐，点餐后店员确认菜品后自动锁定”。锁定即代表自动店员确认桌台，锁定桌台。")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否开启用户下单可选、更换具体桌台号功能")}</label></label>
									<label><input name="is_choice_table" <if condition="$store_shop['is_choice_table'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("关闭")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_choice_table" <if condition="$store_shop['is_choice_table'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店员确认上菜")}</label>
									<label><input name="is_auto_order" <if condition="$store_shop['is_auto_order'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("不要确认")}</label>&nbsp;&nbsp;&nbsp;
									<label><input name="is_auto_order" <if condition="$store_shop['is_auto_order'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("要确认")}</label>
									<span class="form_tips red">{pigcms{:L_("客户在通知上菜的时候是否要店员确认后再上菜")}</span>
								</div>
                                
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("点餐页模板")}</label>
                                    <label><input name="template" <if condition="$store_shop['template'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;&nbsp;{pigcms{:L_("列表")}</label>&nbsp;&nbsp;&nbsp;
                                    <label><input name="template" <if condition="$store_shop['template'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;&nbsp;{pigcms{:L_("图片")}</label>
                                    <span class="form_tips red">{pigcms{:L_("点餐页模板的界面显示形式")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("推荐别名")}</label>
                                    <div>
                                        <input id="hot_alias_name" type="text" value="{pigcms{$store_shop.hot_alias_name|default='推荐榜'}" name="hot_alias_name"/>
                                        <span class="form_tips red">{pigcms{:L_("建议四个字以内")}</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("热销榜显示个数")}</label>
                                    <div>
                                        <input id="ranking_num" type="text" value="{pigcms{$store_shop.ranking_num|default=10}" name="ranking_num"/>
                                        <span class="form_tips red">{pigcms{:L_("热销榜显示前几位")}</span>
                                    </div>
                                </div>

<!--								<div class="form-group" style="font-size: 18px;margin-left: 2px;">-->
<!--									自定义导航1-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航名称")}</label></label>-->
<!---->
<!--									<input type="text" class="col-sm-2" id="supplier_nav_first_name" name="supplier_nav_first_name" value="{pigcms{$store_shop.supplier_nav_first_name}" />-->
<!--									<span class="form_tips red">{pigcms{:L_("建议六个字以内")}</span>-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航链接")}</label></label>-->
<!---->
<!--									<input class="col-sm-3" name="supplier_nav_first_link" id="supplier_nav_first_link" type="text" value="{pigcms{$store_shop.supplier_nav_first_link}"/>　-->
<!--									<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('supplier_nav_first_link',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>-->
<!--								</div>-->

<!--								<div class="form-group" style="font-size: 18px;margin-left: 2px;">-->
<!--									自定义导航2-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航名称")}</label></label>-->
<!---->
<!--									<input type="text" class="col-sm-2" id="supplier_nav_second_name" name="supplier_nav_second_name" value="{pigcms{$store_shop.supplier_nav_second_name}" />-->
<!--									<span class="form_tips red">{pigcms{:L_("建议六个字以内")}</span>-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航链接")}</label></label>-->
<!---->
<!--									<input class="col-sm-3" name="supplier_nav_second_link" id="supplier_nav_second_link" type="text" value="{pigcms{$store_shop.supplier_nav_second_link}"/>　-->
<!--									<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('supplier_nav_second_link',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>-->
<!--								</div>-->

								<!--div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("人均消费")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="mean_money" id="Config_mean_money" type="text" value="{pigcms{$store_shop.mean_money|floatval}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']}</span>
								</div-->
							</div>
							<div id="category" class="tab-pane">
								<volist name="category_list" id="vo">
									<div class="form-group">
										<div class="radio">
											<label>
												<span class="lbl"><label style="color: red">{pigcms{$vo.cat_name}：</label></span>
											</label>
											<volist name="vo['son_list']" id="child">
												<label>
													<input class="cat_class" type="checkbox" name="store_category[]" value="{pigcms{$vo.cat_id}-{pigcms{$child.cat_id}" id="Config_store_category_{pigcms{$child.cat_id}" <if condition="in_array($child['cat_id'],$relation_array)">checked="checked"</if>/>
													<span class="lbl"><label for="Config_store_category_{pigcms{$child.cat_id}">{pigcms{$child.cat_name}</label></span>
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
							<div id="promotion" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺折扣")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="store_discount" id="Config_mean_full_money" type="text" value="{pigcms{$store_shop.store_discount}" /><strong style="color:red">{pigcms{:L_("请填写0~100之间的整数，0和100都是表示无折扣，98表示9.8折")}</strong>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("优惠方式")}</label></label>
									<span><label><input id='discount_type0' name="discount_type" <if condition="$store_shop['discount_type'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("折上折")}</span>&nbsp;</span>
									<span><label><input id='discount_type1' name="discount_type" <if condition="$store_shop['discount_type'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("折扣最优")}</span></span>
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
								</div>
								<div style="clear:both;"></div>
							</div>

							<if condition="!empty($levelarr)">
							<div id="levelcoupon" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！")}</label>
								</div>
							    <volist name="levelarr" id="vv">
								  <div class="form-group">
								    <input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
								    <input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
									<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
									{pigcms{:L_("优惠类型：")}&nbsp;
									<select name="leveloff[{pigcms{$vv['level']}][type]">
										<option value="0">{pigcms{:L_("无优惠")}</option>
										<option value="1" <if condition="$vv['type'] eq 1">selected="selected"</if>>{pigcms{:L_("百分比（%）")}</option>
										<!--<option value="2">{pigcms{:L_("立减")}</option>-->
									</select>
									<input name="leveloff[{pigcms{$vv['level']}][vv]" type="text" value="{pigcms{$vv['vv']}" placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>
								</div>
								</volist>
							</div>
							</if>
						  	<if condition="$config['open_meal_electronic_menu']">
							<div id="store_fitment" class="tab-pane">
								<div class="store_fitment_div">
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("轮播图")}
											<button type="button" class="btn fitment banner" data-type="shop_fitment_banner" data-title="{pigcms{:L_("门店专题")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("通过运营位推荐新品、打造爆款商品，更可设置多个不同时间生效的海报。")}</div>
									</div>
									<div class="form-group">
										<div class="group-title">{pigcms{:L_("今日特惠")}
											<button type="button" class="btn fitment preferential" data-type="shop_fitment_preferential" data-title="{pigcms{:L_("今日特惠")}">{pigcms{:L_("装修")}</button>
										</div>
										<div class="group-content">{pigcms{:L_("将您的商品以大图橱窗展现，充分利用店内流量，从而提升下单率。")}</div>
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
								</div>
								<div id="fitment_preview">
									<iframe src ="/packapp/foodshop/#/menu?store_id={pigcms{$store_shop['store_id']}" width="380" height="680" frameborder="0" id="iframe_preview">
									  <p>Your browser does not support iframes.</p>
									</iframe>
									
								</div>
							</div>
							</if>
							<div id="activity_content" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("活动名称")}</label></label>
									<input name="activity_name" type="text" value="{pigcms{$activity['activity_name']}" placeholder="{pigcms{:L_("请填写活动名称")}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("活动描述")}</label></label>
									<input class="col-sm-5" name="activity_description" type="text" value="{pigcms{$activity['activity_description']}" placeholder="{pigcms{:L_("请填写活动描述")}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("活动价格")}</label></label>
									<input name="activity_price" type="text" value="{pigcms{$activity['activity_price']}" placeholder="{pigcms{:L_("请填写活动价格")}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("活动标签")}</label></label>
									<if condition="!empty($activity['label_list']) " >
										<volist name="activity.label_list" id="vv">
											<span class="plus_re">
												<input type="text" name="activity_label[]"  class="px" value="{pigcms{$vv}"/>
												<button type="button" class="plus_recharge"<php>if($i<count($activity['label_list'])){</php> style="visibility:hidden;"<php>}else{</php>style="visibility:visible;"<php>}</php>></button>
												<button type="button" class="reduce_recharge"style="visibility:visible;"></button>
												<span class="tip"></span>
											</span>
										</volist>
									<else />
										<span class="plus_re">
											<input type="text" name="activity_label[]"  class="px" value=""/>
											<button type="button" class="plus_recharge"></button>
											<button type="button" class="reduce_recharge"></button>
											<span class="tip"></span>
										</span>
									</if>
								</div>
								<div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("活动图片")}</label>
                                    <div style="display:inline-block;" id="activityImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips">{pigcms{:L_("最多显示三张，建议尺寸240*240")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
                                    <div id="activity_image_box">
                                        <ul id="activity_image_ul">
                                            <volist name="activity['pic_arr']" id="vo">
                                                <li class="activity_image_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="activity_image[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
                                            </volist>
                                        </ul>
                                    </div>
                                </div>
							</div>

                            <!-- v20 餐饮2.0 页面装修 start  -->
<!--                            <div id="v20_store_fitment" class="tab-pane">-->
<!--                                <div class="store_fitment_div">-->
<!--                                    <div class="form-group">-->
<!--                                        <div class="group-title">{pigcms{:L_("轮播图")}-->
<!--                                            <button type="button" class="btn fitment banner" data-type="shop_fitment_banner" data-title="{pigcms{:L_("门店专题")}">{pigcms{:L_("装修")}</button>-->
<!--                                        </div>-->
<!--                                        <div class="group-content">{pigcms{:L_("通过运营位推荐新品、打造爆款商品，更可设置多个不同时间生效的海报。")}</div>-->
<!--                                    </div>-->
<!--                                    <div class="form-group">-->
<!--                                        <div class="group-title">{pigcms{:L_("今日特惠")}-->
<!--                                            <button type="button" class="btn fitment preferential" data-type="foodshop_fitment_preferential" data-title="{pigcms{:L_("今日特惠")}">{pigcms{:L_("装修")}</button>-->
<!--                                        </div>-->
<!--                                        <div class="group-content">{pigcms{:L_("将您的商品以大图橱窗展现，充分利用店内流量，从而提升下单率。")}</div>-->
<!--                                    </div>-->
<!--                                    <div class="form-group cat-group">-->
<!--                                        <div class="group-title">{pigcms{:L_("特色分类")}</div>-->
<!--                                        <div class="group-content cat">-->
<!--                                            <div class="cat-row clearfix">-->
<!--                                                <div class="cat-name">{pigcms{:L_("热销")}</div>-->
<!--                                                <div class="cat-desc">-->
<!--                                                    <div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_hot_status"><if condition="$subject_info['cat_hot_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span></div>-->
<!--                                                    <div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_hot_sort">-->
<!--                                                                            <if condition="$subject_info['cat_hot_sort'] eq 1">-->
<!--                                                                                {pigcms{:L_("价格从高到低排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 2"/>-->
<!--                                                                                {pigcms{:L_("价格从低到高排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 3"/>-->
<!--                                                                                {pigcms{:L_("销量从高到低排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 4"/>-->
<!--                                                                                {pigcms{:L_("销量从低到高排序")}-->
<!--                                                                            <else/>{pigcms{:L_("默认排序")}</if></span></div>-->
<!--                                                    <div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_hot_desc">{pigcms{$subject_info.cat_hot_desc|default=L_("大家喜欢吃，才叫真好吃")}</span></div>-->
<!--                                                    <div class="cat-edit">-->
<!--                                                        <a title="{pigcms{:L_("修改")}" class="green fitment" style="padding-right:8px;"  data-type="foodshop_fitment_category_hot" data-title="{pigcms{:L_("热销分类")}">-->
<!--                                                        <i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}-->
<!--                                                        </a>-->
<!--                                                    </div>-->
<!--                                                </div>-->
<!--                                            </div>-->
<!--                                            <div class="cat-row clearfix">-->
<!--                                                <div class="cat-name">{pigcms{:L_("优惠")}</div>-->
<!--                                                <div class="cat-desc">-->
<!--                                                    <div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_discount_status"><if condition="$subject_info['cat_discount_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span></div>-->
<!--                                                    <div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_discount_sort">-->
<!--                                                                            <if condition="$subject_info['cat_discount_sort'] eq 1">-->
<!--                                                                                {pigcms{:L_("价格从高到低排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 2"/>-->
<!--                                                                                {pigcms{:L_("价格从低到高排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 3"/>-->
<!--                                                                                {pigcms{:L_("销量从高到低排序")}-->
<!--                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 4"/>-->
<!--                                                                                {pigcms{:L_("销量从低到高排序")}-->
<!--                                                                            <else/>{pigcms{:L_("默认排序")}</if></span></div>-->
<!--                                                    <div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_discount_desc">{pigcms{$subject_info.cat_discount_desc|default=L_("美味又实惠，大家快来抢")}</span></div>-->
<!--                                                    <div class="cat-edit">-->
<!--                                                        <a title="{pigcms{:L_("修改")}" class="green fitment" style="padding-right:8px;"  data-type="foodshop_fitment_category_discount" data-title="{pigcms{:L_("优惠分类")}">-->
<!--                                                        <i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}-->
<!--                                                        </a>-->
<!--                                                    </div>-->
<!--                                                </div>-->
<!--                                            </div>-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                </div>-->
<!--                                <div id="fitment_preview">-->
<!--                                    <iframe src ="/packapp/foodshop/#/menu?store_id={pigcms{$store_shop['store_id']}" width="380" height="680" frameborder="0" id="iframe_preview">-->
<!--                                        <p>Your browser does not support iframes.</p>-->
<!--                                    </iframe>-->
<!---->
<!--                                </div>-->
<!--                            </div>-->
<!--                             v20 餐饮2.0 页面装修 end  -->

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
	#upload_pic_box{margin-top:20px;height:150px;}
	#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
	#upload_pic_box .upload_image_li{width:130px;float:left;list-style:none;}
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

	#store_fitment{
		margin-left:30px;
		position:relative;
	}
    #v20_store_fitment{
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

    .reduce_recharge {
	    background: url(/tpl/Merchant/default/static/css/img/reduce_nc.png) no-repeat;
	    width: 32px;
	    height: 32px;
	    border: none;
	    position: absolute;
	}
	.plus_recharge {
	    background: url(/tpl/Merchant/default/static/css/img/plus_nc.png) no-repeat;
	    width: 32px;
	    height: 32px;
	    border: none;
	    position: absolute;
	    margin-left: 38px;
	}
	.plus_re{
		margin-right: 50px;
	}
	.activity_image_li {
	    width: 130px;
	    float: left;
	    list-style: none;
	}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript">
	$('.fitment').click(function(){
		var _this = $(this);
        art.dialog.open(
        	'{pigcms{$this->config['site_url']}/merchant.php?c=Foodshop&a='+_this.data('type')+'&store_id={pigcms{$_GET.store_id}',
        	{
        		lock:true,
        		title:'{pigcms{:L_("页面装修")} -> ' + _this.data('title'),
        		width:800,
        		height:500,
        		yesText:'关闭',
        		background: '#000',
        		opacity: 0.45,
        		id:'fitment',
    			close: function(){/*关闭窗口时的回调函数*/
    				 var _iframe1=document.getElementById('iframe_preview');
            		_iframe1.contentWindow.location.reload(true);
    			}
    	});
	});
	
	$('.fitment_subject img').click(function(){
		$('.fitment.subject').trigger('click');
	});
	$('.fitment_showcase img').click(function(){
		$('.fitment.showcase').trigger('click');
	});
	
	console.log($('.page-content').width());
	$('#fitment_preview').css('left',(500 + ($('.page-content').width()-500 - 350)/2)+'px');


	// 标签添加
	$(document).on('click','.plus_recharge',function(){
		var item = $(this).parents('.form-group').find('.plus_re:last');
		var newitem = $(item).clone(true);
		
		$(this).css('visibility','hidden');
		$(this).siblings('.reduce_recharge').css('visibility','visible');
		
		newitem.find('.tip').css('visibility','hidden');
		
		$(item).after(newitem);
		newitem.find('input').attr('value','');
	})

	// 标签减少
	$(".reduce_recharge").click(function() {
		if($(this).parents('.form-group').find('.plus_re').length<=1){
			$('.plus_re input').val('');
			$('.plus_re:first').find('.tip').css('visibility','visible');
			
		}else{
			
			$(this).parents('.plus_re').remove();
			// $('.plus_re:last').find('.reduce_recharge').css('visibility','hidden');
			$('.plus_re:last').find('.plus_recharge').css('visibility','visible');
			
			$.each($(this).parents('.form-group').find('.plus_re'), function(index, val) {
				var No =index+1;
				if(No==1){
					$(val).find('.tip').css('visibility','visible')
				}
				$(val).data('id',No);
			
			});
		}
	})
</script>
<script>
$(document).ready(function(){
    var uploader = WebUploader.create({
    	auto: true,
    	swf: '{pigcms{$static_public}js/Uploader.swf',
    	server: "{pigcms{:U('Foodshop/ajax_store_pic')}",
    	pick: {
    		id:'#J_selectImage',
    		multiple:false
    	},
    	accept: {
    		title: 'Images',
    		extensions: 'gif,jpg,jpeg,png',
    		mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
    	}
    });
    uploader.on('fileQueued',function(file){
    	if($('.upload_pic_li').size() >= 5){
    		uploader.cancelFile(file);
    		alert('{pigcms{:L_("最多上传5个图片！")}');
    		return false;
    	}
    });
    uploader.on('uploadSuccess',function(file,response){
    	if(response.error == 0){
    		$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+response.url+'"/><input type="hidden" name="pic[]" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
    	}else{
    		alert(response.info);
    	}
    });
    uploader.on('uploadError', function(file,reason){
    	$('.loading'+file.id).remove();
    	alert('{pigcms{:L_("上传失败！请重试。")}');
    });


    var uploaderBackGround = WebUploader.create({
        auto: true,
        swf: '{pigcms{$static_public}js/Uploader.swf',
        server: "{pigcms{:U('Foodshop/ajax_store_pic')}",
        pick: {
            id:'#background',
            multiple:false
        },
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,png',
            mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
        }
    });
    uploaderBackGround.on('fileQueued',function(file){
        if($('.upload_backgroud_li').size() >= 1){
            uploader.cancelFile(file);
            alert('{pigcms{:L_("最多上传1个图片！")}');
            return false;
        }
    });
    uploaderBackGround.on('uploadSuccess',function(file,response){
        if(response.error == 0){
            $('#upload_backgroud_li').append('<li class="upload_backgroud_li"><img src="'+response.url+'"/><input type="hidden" name="background[]" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
        }else{
            alert(response.info);
        }
    });
    uploaderBackGround.on('uploadError', function(file,reason){
        $('.loading'+file.id).remove();
        alert('{pigcms{:L_("上传失败！请重试。")}');
    });

    
});
// 活动图片
KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true,
		langType : "{pigcms{$config.system_lang_alias}"
	});
	K('#activityImage').click(function(){
		if($('.activity_image_li').size() >= 3){
			alert('{pigcms{:L_("最多上传3个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Foodshop/ajax_store_activity_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#activity_image_ul').append('<li class="activity_image_li"><img src="'+url+'"/><input type="hidden" name="activity_image[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
});
function deleteImage(path,obj){
	$.post("{pigcms{:U('Foodshop/ajax_delstore_pic')}",{path:path});
	$(obj).closest('li').remove();
}
function check(obj){
	var length = $('.paycheck:checked').length;
	if(length == 0){
		$(obj).attr('checked','checked');
		bootbox.alert('{pigcms{:L_("最少要选择一种支付方式")}');
	}			
}
$(function($){
	$('input[name=is_book]').click(function(){
		if ($(this).val() == 1) {
			$('.book').css('display', 'block');
		} else {
			$('.book').css('display', 'none');
		}
	});
	$('input[name=is_invoice]').click(function(){
		if ($(this).val() == 1) {
			$('.invoice').css('display', 'block');
		} else {
			$('.invoice').css('display', 'none');
		}
	});
	
	$('#book_start').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm:ss','hour':'00','minute':'00'}));
	$('#book_stop').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm:ss','hour':'23','minute':'59'}));

	var is_submit = false;
	$('#edit_form').submit(function(){
		if (is_submit) return false;
		is_submit = true;
		$.post("{pigcms{:U('Foodshop/shop_edit',array('store_id'=>$_GET['store_id']))}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Foodshop/index')}";
			}else{
				is_submit = false;
				alert(result.info);
			}
		})
		return false;
	});
});
function addLink(domid,iskeyword, type){
	art.dialog.data('domid', domid);
	art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'{pigcms{:L_("插入链接或关键词',width:800,height:500,yesText:'关闭")}',background: '#000',opacity: 0.45});
}
</script>
<include file="Public:footer"/>
