<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		fieldset:disabled select{
			background: #ddd;
		}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑店铺")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtstore">{pigcms{:L_("店铺描述")}</a>
							</li>
							<if condition="$config['pay_in_store']">
								<li>
									<a data-toggle="tab" href="#discount">{pigcms{$config.cash_alias_name}</a>
								</li>
							</if>
							<if condition="isset($config['vip_discount_pay_for'])">
								<li>
									<a data-toggle="tab" href="#vipdiscount">{pigcms{:L_("会员优惠")}</a>
								</li>
							</if>
							<if condition="isset($config['open_store_vr']) and $config['open_store_vr'] eq 1">
								<li>
									<a data-toggle="tab" href="#vrbox">VR{pigcms{:L_("地址设置")}</a>
								</li>
							</if>
							<li>
								<a data-toggle="tab" href="#plat_recommend">{pigcms{:L_("平台推荐")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#open_time">{pigcms{:L_("营业时间设置")}</a>
							</li>
							<if condition="isset($config['dindin_is_open']) and $config['dindin_is_open'] eq 1">
								<li>
									<a data-toggle="tab" href="#dingding">{pigcms{:L_("叮叮配送")}</a>
								</li>
							</if>
							<if condition="$config['is_hark_system']">
								<li>
									<a data-toggle="tab" href="#wxapp_share">{pigcms{:L_("小程序分享参数配置")}</a>
								</li>
							</if>
                            <if condition="$config['shunfeng_is_open']">
                                <li>
                                    <a data-toggle="tab" href="#shunfeng">{pigcms{:L_("顺丰配送配置")}</a>
                                </li>
                            </if>
                            <if condition="$config['ele_is_open']">
                                <li>
                                    <a data-toggle="tab" href="#ele_anubis">{pigcms{:L_("蜂鸟配送配置")}</a>
                                </li>
                            </if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("店铺名称")}</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" value="{pigcms{$now_store.name}" type="text"/>
								</div>
								<div class="form-group"><label class="col-sm-1">{pigcms{:L_("是否设置成主店")}</label><label><input type="radio" name="ismain" value="1" <if condition="$now_store['ismain'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>
									&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="ismain" value="0" <if condition="$now_store['ismain'] neq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>
								 &nbsp;&nbsp;&nbsp;<span class="form_tips">{pigcms{:L_("如果将此店铺设置成主店，系统将自动取消其他已设置的主店")}</span>
								</div>
								<if condition="file_exists('conf/is_hake.no_delete')">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("开启下单年龄限制")}</label>
										<label><input type="radio" name="age_limit_buy_switch" value="0" <if condition="$now_store.age_limit_buy_switch == 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("关闭")}</label>&nbsp;&nbsp;&nbsp;
										<label><input type="radio" name="age_limit_buy_switch" value="1" <if condition="$now_store.age_limit_buy_switch == 1">checked="checked"</if> >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>&nbsp;&nbsp;&nbsp;
										<span class="form_tips"> {pigcms{:L_("一般用于店铺中有售卖烟酒类等年龄限制时使用，开启后用户下单时需填写生日，符合18岁以上才可正常下单，默认关闭。目前针对快店业务。")}</span>
									</div>
									<div class="form-group" id="age_limit_buy_box" <if condition="$now_store.age_limit_buy_switch == 0">style="display: none;"</if>>
										<label class="col-sm-1">{pigcms{:L_("限制年龄")}</label>
										<input class="col-sm-2" style="width:60px" name="age_limit_buy"  type="text" value="<if condition="!empty($now_store['age_limit_buy'])">{pigcms{$now_store.age_limit_buy}<else />18</if>" />
										<span class="form_tips"> {pigcms{:L_("限制岁数。如：18（单位：岁）")}</span>
									</div>
								</if>
								<if condition="$config.international_phone eq 1">
									<div class="form-group">
										<label class="col-sm-1"><label for="password">{pigcms{:L_("区号")}</label></label>
									
										<pigcms:national_phone id="phone_country_type" style="height:34px;float:left;margin-right:5px;">
										 	<option value="{pigcms{$vo.code}"  <if condition="$now_store.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
										</pigcms:national_phone>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">{pigcms{:L_("联系电话")}</label></label>
									
									<input class="col-sm-2" size="20" name="phone" id="phone" value="{pigcms{$now_store.phone}" type="text"/>
									<span class="form_tips">{pigcms{:L_("多个电话号码以空格分开")}</span>
								</div>
								<if condition="!isset($config['not_o2o_demo'])">
									<div class="form-group">
										<label class="col-sm-1"><label for="phone">{pigcms{:L_("客服电话")}</label></label>

										<input class="col-sm-2" size="20" name="kefu_phone" id="kefu_phone" value="{pigcms{$now_store.kefu_phone}" type="text"/>
										<span class="form_tips">{pigcms{:L_("客服下单电话，商家收入余额短信提醒")}</span>
									</div>
								</if>
								<if condition="!isset($config['not_o2o_demo'])">
									<div class="form-group">
										<label class="col-sm-1"><label for="weixin">{pigcms{:L_("联系微信")}</label></label>
										<input class="col-sm-2" size="20" name="weixin" id="weixin" type="text" value="{pigcms{$now_store.weixin}"/>
									</div>
								</if>
								<if condition="!isset($config['not_o2o_demo'])">
									<div class="form-group">
										<label class="col-sm-1"><label for="qq">{pigcms{:L_("联系")}Q Q</label></label>
										<input class="col-sm-2" size="20" name="qq" id="qq" type="text" value="{pigcms{$now_store.qq}"/>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("关键词：")}</label>
									<input class="col-sm-3" maxlength="30" name="keywords" type="text" value="{pigcms{$now_store.keywords}" id="keywords"/><span class="form_tips">{pigcms{:L_("选填。")}<font color="red">{pigcms{:L_("（用空格分隔不同的关键词，最多5个）")}</font>{pigcms{:L_("，用户在微信将按此值搜索！")}</span> <a href="javascript:;" id="get_key_btn">{pigcms{:L_("按店铺名称获取")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">{pigcms{:L_("店铺经纬度")}</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" value="{pigcms{$now_store.long},{pigcms{$now_store.lat}" type="text" readonly="readonly" data-lat ="{pigcms{$now_store.lat}" data-long = "{pigcms{$now_store.long}" />
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="permoney">{pigcms{:L_("人均消费")}</label></label>
									<input class="col-sm-2" size="20" name="permoney" id="permoney" type="text" value="{pigcms{$now_store.permoney}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>
									<span class="form_tips"> {pigcms{$config['Currency_txt']}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="feature">{pigcms{:L_("店铺特色")}</label></label>
									<input class="col-sm-2" style="width:600px" name="feature" id="feature" type="text" value="{pigcms{$now_store.feature}" />
									<span class="form_tips">{pigcms{:L_("简单描述本店特色之处80字以内")}</span>
								</div>
								<div class="form-group" id="choose_category_s">
									<label class="col-sm-1"><label>{pigcms{:L_("店铺所属分类")}</label></label>
									<fieldset id="choose_category" cat_fid="{pigcms{$now_store.cat_fid}" cat_id="{pigcms{$now_store.cat_id}"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("店铺所在地")}</label></label>
									<if condition="empty($_SESSION['system']) and isset($config['merchant_edit_circle']) and $config['merchant_edit_circle'] eq 0">
										<fieldset id="choose_cityarea" street_id="{pigcms{$now_store.street_id}" province_id="{pigcms{$now_store.province_id}" city_id="{pigcms{$now_store.city_id}" area_id="{pigcms{$now_store.area_id}" circle_id="{pigcms{$now_store.circle_id}" market_id="{pigcms{$now_store.market_id}" disabled='disabled'></fieldset>
									<else/>
										<fieldset id="choose_cityarea" street_id="{pigcms{$now_store.street_id}" province_id="{pigcms{$now_store.province_id}" city_id="{pigcms{$now_store.city_id}" area_id="{pigcms{$now_store.area_id}" circle_id="{pigcms{$now_store.circle_id}" market_id="{pigcms{$now_store.market_id}"></fieldset>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="adress">{pigcms{:L_("店铺地址")}</label></label>
									<input class="col-sm-2" size="20" name="adress" id="adress" value="{pigcms{$now_store.adress}" type="text"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="trafficroute">{pigcms{:L_("交通路线")}</label></label>
									<input class="col-sm-2" name="trafficroute" id="trafficroute" type="text" value="{pigcms{$now_store.trafficroute}" style="width:600px"/>
									<span class="form_tips">{pigcms{:L_("简单描述本店交通路线80字以内")}</span>
								</div>
								<!-- <div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("店铺排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_store.sort}" />
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
								</div> -->
								<if condition="$config['meal_alias_name']">
									<div class="form-group">
										<label class="col-sm-1">WiFi{pigcms{:L_("名称")}</label>
										<input class="col-sm-1" name="wifi_account" type="text" value="{pigcms{$now_store.wifi_account}" />
									</div>
									<div class="form-group">
										<label class="col-sm-1">WiFi{pigcms{:L_("密码")}</label>
										<input class="col-sm-1" name="wifi_password" type="text" value="{pigcms{$now_store.wifi_password}" />
									</div>
								</if>
								<if condition="$config['store_open_meal']">
									<div class="form-group">
										<label class="col-sm-1" for="have_meal">{pigcms{$config.meal_alias_name}</label>
										<select name="have_meal" id="have_meal">
											<option value="0" <if condition="$now_store['have_meal'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$now_store['have_meal'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['store_open_meal'] && false">
									<div class="form-group">
										<label class="col-sm-1" for="store_type">{pigcms{$config.meal_alias_name}{pigcms{:L_("类型")}</label>
										<select name="store_type" id="store_type">
											<option value="0" <if condition="$now_store['store_type'] eq 0">selected="selected"</if>>{pigcms{:L_("订餐和外卖")}</option>
											<option value="1" <if condition="$now_store['store_type'] eq 1">selected="selected"</if>>{pigcms{:L_("订餐")}</option>
											<option value="2" <if condition="$now_store['store_type'] eq 2">selected="selected"</if>>{pigcms{:L_("其他")}</option>
										</select>
										<span class="form_tips">{pigcms{:L_("【其他】是指（外卖，超市，花店...）")}</span>
									</div>
								</if>
								<if condition="$config['store_open_meal'] && false">
									<div class="form-group">
										<label class="col-sm-1" for="store_type">{pigcms{$config.meal_alias_name}{pigcms{:L_("类型")}</label>
										<select name="store_type" id="store_type">
											<option value="0" <if condition="$now_store['store_type'] eq 0">selected="selected"</if> disabled>{pigcms{:L_("订餐和外卖")}</option>
											<option value="1" <if condition="$now_store['store_type'] eq 1">selected="selected"</if>>{pigcms{:L_("到店消费")}</option>
											<option value="2" <if condition="$now_store['store_type'] eq 2">selected="selected"</if> disabled>{pigcms{:L_("其他")}</option>
											<option value="3" <if condition="$now_store['store_type'] eq 3">selected="selected"</if>>{pigcms{:L_("到店消费和外送")}</option>
										</select>
										<span class="form_tips red">{pigcms{:L_("【注】订餐和外卖、其他，这两种类型不能再被选择了，但是不影响已经选择过这两项的类型")}</span>
									</div>
								</if>
								<if condition="$config['store_open_group']">
									<div class="form-group">
										<label class="col-sm-1" for="have_group">{pigcms{$config.group_alias_name}</label>
										<select name="have_group" id="have_group">
											<option value="0" <if condition="$now_store['have_group'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$now_store['have_group'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['store_open_shop']">
									<div class="form-group">
										<label class="col-sm-1" for="have_group">{pigcms{$config.shop_alias_name}</label>
										<select name="have_shop" id="have_shop">
											<option value="0" <if condition="$now_store['have_shop'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$now_store['have_shop'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['mall_alias_name']">
									<div class="form-group">
										<label class="col-sm-1" for="have_mall">{pigcms{$config.mall_alias_name}</label>
										<select name="have_mall" id="have_mall">
											<option value="0" <if condition="$now_store['have_mall'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$now_store['have_mall'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺")}LOGO</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectLogo">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("店铺LOGO暂时仅用于小程序")}{pigcms{$config.shop_alias_name}{pigcms{:L_("，建议上传680*480的图片")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">LOGO{pigcms{:L_("预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_logo_ul">
											<if condition="$now_store['logo']">
												<li class="upload_pic_li_logo"><img src="{pigcms{:replace_file_domain($now_store['logo'])}"/><input type="hidden" name="logo" value="{pigcms{$now_store.logo}"/></li>
											</if>
										</ul>
									</div>
								</div>
<!--								<div class="form-group" style="font-size: 18px;margin-left: 2px;">-->
<!--									自定义导航1-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航名称")}</label></label>-->
<!---->
<!--									<input type="text" class="col-sm-2" id="supplier_nav_first_name" name="supplier_nav_first_name" value="{pigcms{$now_store.supplier_nav_first_name}" />-->
<!--									<span class="form_tips red">{pigcms{:L_("建议六个字以内")}</span>-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航链接")}</label></label>-->
<!---->
<!--									<input class="col-sm-3" name="supplier_nav_first_link" id="supplier_nav_first_link" type="text" value="{pigcms{$now_store.supplier_nav_first_link}"/>　-->
<!--									<a class="btn btn-sm btn-success" onclick="addLink('supplier_nav_first_link',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>href="#modal-table"-->
<!--								</div>-->

<!--								<div class="form-group" style="font-size: 18px;margin-left: 2px;">-->
<!--									自定义导航2-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航名称")}</label></label>-->
<!---->
<!--									<input type="text" class="col-sm-2" id="supplier_nav_second_name" name="supplier_nav_second_name" value="{pigcms{$now_store.supplier_nav_second_name}" />-->
<!--									<span class="form_tips red">{pigcms{:L_("建议六个字以内")}</span>-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("导航链接")}</label></label>-->
<!---->
<!--									<input class="col-sm-3" name="supplier_nav_second_link" id="supplier_nav_second_link" type="text" value="{pigcms{$now_store.supplier_nav_second_link}"/>　-->
<!--									<a class="btn btn-sm btn-success" onclick="addLink('supplier_nav_second_link',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>href="#modal-table"-->
<!--								</div>-->
							</div>
							<div id="txtstore" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺描述")}</label>
									<textarea class="col-sm-5" rows="5" name="txt_info">{pigcms{$now_store.txt_info}</textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺详情")}</label>
									<textarea class="col-sm-3" id="supplier_info" name="supplier_info" style="width: 300px; height: 250px; display: none;">{pigcms{$now_store.supplier_info}</textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><span style="color: red;">*</span>{pigcms{:L_("商家图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("第一张将作为主图片！最多上传10个图片！图片宽度建议为700px，高度建议为420px。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_store['pic']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{:file_domain()}{pigcms{$vo}"/><input type="hidden" name="pic[]" value="{pigcms{$vo}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</volist>
										</ul>
									</div>
								</div>
							</div>
							<div id="discount" class="tab-pane">
								<div class="alert alert-block alert-success">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>
									<p>{pigcms{:L_("这里用于设置用户快速买单优惠的地方，不支持其他业务类型。如需不给予用户优惠，将填写项清空即可。")}</p>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("优惠类型")}</label>
									<label><input type="radio" name="discount_type" value="1" <if condition="$now_store['discount_txt']['discount_type'] eq 1">checked="checked"</if> <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>>&nbsp;&nbsp;{pigcms{:L_("折扣")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="discount_type" value="2" <if condition="$now_store['discount_txt']['discount_type'] eq 2">checked="checked"</if> <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>>&nbsp;&nbsp;{pigcms{:L_("满减")}</label>&nbsp;&nbsp;&nbsp;
								</div>
								<div class="form-group percent" <if condition="$now_store['discount_txt']['discount_type'] neq 1">style="display:none"</if> >
									<label class="col-sm-1">{pigcms{:L_("普通折扣率")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_percent"  type="text" value="{pigcms{$now_store['discount_txt']['discount_percent']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>/><b style="color:red">折</b>
									<span class="form_tips">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，不填、填写0或者10都代表没有优惠")}</span>
								</div>
								<if condition="$config.open_extra_price eq 1">
								<div class="form-group percent" <if condition="$now_store['discount_txt']['discount_type'] neq 1">style="display:none"</if> >
									<label class="col-sm-1">{pigcms{:L_("每天限单折扣单数")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_limit"  type="text" value="{pigcms{$now_store['discount_txt']['discount_limit']}" /><b style="color:red">单</b>
									<span class="form_tips">{pigcms{:L_("请填大于0的数字，不带小数点，0代表无")}</span>
								</div>
								<div class="form-group percent" <if condition="$now_store['discount_txt']['discount_type'] neq 1">style="display:none"</if> >
									<label class="col-sm-1">{pigcms{:L_("每天限单折扣率")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_limit_percent"  type="text" value="{pigcms{$now_store['discount_txt']['discount_limit_percent']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if>	<php>}</php>/><b style="color:red">折</b>
									<span class="form_tips">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，每天限单数内的折扣，超过数量将使用普通折扣率")}</span>
								</div>
								</if>
								<div class="form-group" <if condition="$now_store['discount_txt']['discount_type'] neq 2">style="display:none"</if> id="condition">
									<label class="col-sm-1">{pigcms{:L_("每满")}</label>
									<input class="col-sm-2" style="width:60px" name="condition_price"  type="text" value="{pigcms{$now_store['discount_txt']['condition_price']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND ($config['discount_controler'] eq 1)" />disabled</if>								
									<php>}</php>/>
									<label class="col-sm-1" style="color:red;width: 60px;">{pigcms{$config['Currency_txt']}{pigcms{:L_("，减")}</label>
									<input class="col-sm-2" style="width:60px" name="minus_price"  type="text" value="{pigcms{$now_store['discount_txt']['minus_price']}" <php>if(1==$config['group_store_discount_more']){</php><if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if><php>}</php>/>
									<label class="col-sm-1" style="color:red">{pigcms{$config['Currency_txt']}</label>
								</div>
								<php>if(1==$config['group_store_discount_more']){</php>
								<div class="form-group">
								<label class="col-sm-1"><label>{pigcms{:L_("优惠方式")}</label></label>
									<if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">
										<strong style="color:red">{pigcms{:L_("只能由商家自己设置")}</strong>
									<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />
									    <strong style="color:red">{pigcms{:L_("只能由系统设置")}</strong>
									</if>
							
									
									<span><label><input id='discount_type0' name="vip_discount_type" <if condition="$now_store['vip_discount_type'] eq 2 ">checked="checked"</if> value="2" type="radio" <if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if>></label>&nbsp;<span>{pigcms{:L_("折上折")}</span>&nbsp;</span>
									<span><label><input id='discount_type1' name="vip_discount_type" <if condition="$now_store['vip_discount_type'] eq 1 ">checked="checked"</if> value="1" type="radio" <if condition="!empty($_SESSION['system']) AND $config['discount_controler'] eq 0">disabled<elseif condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1" />disabled</if>></label>&nbsp;<span>{pigcms{:L_("折扣最优")}</span></span>
									<strong style="color:red">{pigcms{:L_("折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；")}<br/>
									{pigcms{:L_("折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。")}
									</strong>

								</div>
								
									<php>}</php>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("买单二维码")}</label>
									<a href="{pigcms{$config.site_url}/wap.php?c=My&a=pay&store_id={pigcms{$now_store.store_id}&spread=1" class="see_qrcode">{pigcms{:L_("查看二维码")}</a>
									<span class="form_tips">{pigcms{:L_("您可以将此二维码下载打印成物料放至店铺中")}</span>
								</div>
								<div style="height:40px;"></div>
								<if condition="$config['store_ticket_have'] && $config['pay_in_store']">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("绑定插件：")}</label>
										<select name="bind_store_trade" id="bind_store_trade" class="col-sm-2">
											<option value="" <if condition="$now_store['bind_store_trade'] eq ''">selected="selected"</if>>{pigcms{:L_("不绑定")}</option>
											<option value="ticket" <if condition="$now_store['bind_store_trade'] eq 'ticket'">selected="selected"</if>>{pigcms{:L_("票务插件")}</option>
										</select>
									</div>
								</if>
								<div class="store_trade_div" id="store_trade_ticket" style="display:none;">
									<div class="form-group">
										<span class="form_tips" style="margin-left:12px;">{pigcms{:L_("票务插件作为优惠买单针对线下票务市场（可用于景点售票、公交车票、长途客车票、电影票等）的补充。开启后")} {pigcms{$config.cash_alias_name} {pigcms{:L_("页面将使用此功能，无法使用以上优惠。")}</span>
									</div>
									<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("使用场景")}</label>
											<select name="store_trade_ticket[use_scene]">
												<option value="1" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '1'">selected="selected"</if>>{pigcms{:L_("景点售票")}</option>
												<option value="2" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '2'">selected="selected"</if>>{pigcms{:L_("公交车票")}</option>
												<option value="3" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '3'">selected="selected"</if>>{pigcms{:L_("长途客车票")}</option>
												<option value="4" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '4'">selected="selected"</if>>{pigcms{:L_("电影")}/{pigcms{:L_("演出")}</option>
												<option value="5" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '5'">selected="selected"</if>>{pigcms{:L_("话剧")}/{pigcms{:L_("歌剧")}</option>
												<option value="6" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '6'">selected="selected"</if>>{pigcms{:L_("游乐园")}</option>
												<option value="7" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '7'">selected="selected"</if>>{pigcms{:L_("运动健身")}</option>
												<option value="8" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '8'">selected="selected"</if>>{pigcms{:L_("美发")}/{pigcms{:L_("美业")}</option>
												<option value="0" <if condition="$now_store['store_trade_ticket']['use_scene'] eq '0'">selected="selected"</if>>{pigcms{:L_("其他场景")}</option>
											</select>
											<span class="form_tips">{pigcms{:L_("选择使用场景，页面显示相应图标。其他场景显示通用的店铺图标")}</span>
										</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("单张票默认金额")}</label>
										<input class="col-sm-1" name="store_trade_ticket[default_money]" type="text" value="{pigcms{$now_store['store_trade_ticket']['default_money']|floatval=###}" /><span class="form_tips">{pigcms{$config['Currency_txt']}{pigcms{:L_("，不填写或填写0则不默认")}</span>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("最低售票张数")}</label>
										<input class="col-sm-1" name="store_trade_ticket[limit_num]" type="text" value="{pigcms{$now_store['store_trade_ticket']['limit_num']}" /><span class="form_tips">{pigcms{:L_("张，不填写或填写0都默认为")}1</span>
									</div>
									<if condition="$config['store_ticket_have_insure']">
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("开启销售保险")}</label>
											<select name="store_trade_ticket[have_insure]">
												<option value="1" <if condition="$now_store['store_trade_ticket']['have_insure'] eq '1'">selected="selected"</if>>{pigcms{:L_("开启")}</option>
												<option value="0" <if condition="$now_store['store_trade_ticket']['have_insure'] eq '0'">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											</select>
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险必须购买")}</label>
											<select name="store_trade_ticket[insure_mustby]">
												<option value="0" <if condition="$now_store['store_trade_ticket']['insure_mustby'] eq '0'">selected="selected"</if>>{pigcms{:L_("可选")}</option>
												<option value="1" <if condition="$now_store['store_trade_ticket']['insure_mustby'] eq '1'">selected="selected"</if>>{pigcms{:L_("必须")}</option>
											</select>
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险名称别名")}</label>
											<input class="col-sm-1" name="store_trade_ticket[insure_name]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_name']}" /><span class="form_tips">{pigcms{:L_("保险名称可变更为该行业内用户认可的名称，例如车票类保险可称为 乘意险（乘客意外险）")}</span>
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险（第一档）")}</label>
											{pigcms{:L_("单张票金额满")}&nbsp;&nbsp;<input name="store_trade_ticket[insure_tikcet_1]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_tikcet_1']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}{pigcms{:L_("收取此档保费，")}<input name="store_trade_ticket[insure_1]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_1']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}保 <input name="store_trade_ticket[insure_money_1]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_money_1']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险（第二档）")}</label>
											{pigcms{:L_("单张票金额满")}&nbsp;&nbsp;<input name="store_trade_ticket[insure_tikcet_2]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_tikcet_2']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}{pigcms{:L_("收取此档保费，")}<input name="store_trade_ticket[insure_2]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_2']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}保 <input name="store_trade_ticket[insure_money_2]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_money_2']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险（第三档）")}</label>
											{pigcms{:L_("单张票金额满")}&nbsp;&nbsp;<input name="store_trade_ticket[insure_tikcet_3]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_tikcet_3']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}{pigcms{:L_("收取此档保费，")}<input name="store_trade_ticket[insure_3]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_3']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}保 <input name="store_trade_ticket[insure_money_3]" type="text" value="{pigcms{$now_store['store_trade_ticket']['insure_money_3']|floatval=###}" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("保险购买说明")}</label>
											<textarea class="col-sm-5" rows="5" name="store_trade_ticket[insure_info]">{pigcms{$now_store['store_trade_ticket']['insure_info']}</textarea>
										</div>
									</if>
								</div>
							</div>
							<if condition="!empty($levelarr) AND isset($config['vip_discount_pay_for'])">
								<div id="vipdiscount" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！填写数字即可，无需%符号，填写50代表5折，0与100代表没有折扣。")}</label>
									</div>
									<volist name="levelarr" id="vv">
									  <div class="form-group">
										<input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
										<input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
										<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
										{pigcms{:L_("优惠类型：")}&nbsp;
										<select name="leveloff[{pigcms{$vv['level']}][type]" <if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if>>
											<option value="0">{pigcms{:L_("无优惠")}</option>
											<option value="1" <if condition="$vv['type'] eq 1">selected="selected"</if>>{pigcms{:L_("百分比（%）")}</option>
											<option value="2" <if condition="$vv['type'] eq 2">selected="selected"</if>>{pigcms{:L_("立减")}</option>
										</select>
										<input name="leveloff[{pigcms{$vv['level']}][vv]" type="text" value="{pigcms{$vv['vv']}" placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')" <if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if>/>
										  <if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1 && $config['discount_sync'] eq 1">
											  <input  name="leveloff[{pigcms{$vv['level']}][type]" type="hidden" value="{pigcms{$vv['type']}"/>
											  <input  name="leveloff[{pigcms{$vv['level']}][vv]" type="hidden" value="{pigcms{$vv.boon}"/>
										  </if>
									  </div>
									</volist>
								</div>
							</if>
							<!-- vr{pigcms{:L_("配置")} -->
							<if condition="$config['open_store_vr'] eq 1">
								<div id='vrbox' class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1"><label for="vr_name">{pigcms{:L_("名称")}</label></label>
										<input class="col-sm-1" size="20" name="vr_name" id="vr_name" value="{pigcms{$now_store.vr_name}" type="text"/>
										<span class="form_tips"></span>
									</div>
								   <div class="form-group">
										<label class="col-sm-1"><label for="vr_link">{pigcms{:L_("地址")}</label></label>
										<input class="col-sm-3" size="20" name="vr_link" id="vr_link" type="text" value="{pigcms{$now_store.vr_link}"/>
									</div>
								</div>
							</if>
							<div id='plat_recommend' class="tab-pane">
								<div class="form-group">
									<label class="col-sm-2" for="plat_recommend_type">{pigcms{:L_("平台推荐类型")}</label>
									<select name="plat_recommend_type" id="plat_recommend_type">
										<option value="store" selected="selected">{pigcms{:L_("店铺综合页面")}</option>
										<notempty name="config.shop_alias_name">
											<option value="shop" <if condition="$now_store['plat_recommend_type'] eq 'shop'">selected="selected"</if>>{pigcms{$config.shop_alias_name}</option>
										</notempty>
										<notempty name="config.group_alias_name">
										<option value="group" <if condition="$now_store['plat_recommend_type'] eq 'group'">selected="selected"</if>>{pigcms{$config.group_alias_name}</option>
										</notempty>
										<notempty name="config.meal_alias_name">
										<option value="meal" <if condition="$now_store['plat_recommend_type'] eq 'meal'">selected="selected"</if>>{pigcms{$config.meal_alias_name}</option>
										</notempty>
										<option value="hotel" <if condition="$now_store['plat_recommend_type'] eq 'hotel'">selected="selected"</if>>{pigcms{:L_("酒店")}</option>
										<option value="pin_group" <if condition="$now_store['plat_recommend_type'] eq 'pin_group'">selected="selected"</if>>{pigcms{:L_("拼团")}</option>
									</select>
									<span class="form_tips">{pigcms{:L_("您希望平台给您推介的业务是什么？目前在平台首页猜你喜欢展示。受数据缓存影响，您的更改可能会在一小时内生效。")}</span>
								</div>
								<div class="form-group" id="plat_recommend_store">
									<label class="col-sm-2"><label for="plat_recommend_type_store">{pigcms{:L_("店铺综合页面")}</label></label>
									<select name="plat_recommend_type_store" id="plat_recommend_type_store">
										<option value="cash" <if condition="$now_store['plat_recommend_info_arr']['type'] eq 'cash'">selected="selected"</if>>{pigcms{$config.cash_alias_name}</option>
										<option value="group" <if condition="$now_store['plat_recommend_info_arr']['type'] eq 'group'">selected="selected"</if>>{pigcms{$config.group_alias_name}</option>
										<option value="description" <if condition="$now_store['plat_recommend_info_arr']['type'] eq 'description'">selected="selected"</if>>一句话描述</option>
									</select>
									<span class="form_tips">{pigcms{:L_("店铺综合页面时，您希望推荐什么业务？页面会特别展示。")}</span>
								</div>
								<div class="form-group" id="plat_recommend_group">
									<label class="col-sm-2"><label for="plat_recommend_type_group">{pigcms{$config.group_alias_name}商品</label></label>
									<select name="plat_recommend_type_group" id="plat_recommend_type_group">
										<option value="0">{pigcms{:L_("系统自动推荐")}</option>
										<volist name="plat_recommend_group_list" id="vo">
										<option value="{pigcms{$vo.group_id}" <if condition="$now_store['plat_recommend_info_arr']['value'] eq $vo['group_id']">selected="selected"</if>>{pigcms{$vo.group_name}</option>
										</volist>
									</select>
									<span class="form_tips">{pigcms{:L_("您希望推荐什么商品？")}</span>
								</div>
								<div class="form-group" id="plat_recommend_description" style="display: none;">
									<label class="col-sm-2"><label for="plat_recommend_type_group">一句话描述</label></label>
									<input type="text" id="plat_recommend_type_description" value="{pigcms{$now_store['plat_recommend_info_arr']['value']}"  class="col-sm-5">
									
									<span class="form_tips">{pigcms{:L_("用于前台页面的店铺下显示一句话描述，建议12字以内")}</span>
								</div>
								<div class="form-group" id="plat_recommend_pin_group">
									<label class="col-sm-2"><label for="plat_recommend_type_pin_group">{pigcms{$config.group_alias_name}商品</label></label>
									<select name="plat_recommend_type_pin_group" id="plat_recommend_type_pin_group">
										<option value="0">{pigcms{:L_("系统自动推荐")}</option>
										<volist name="plat_recommend_pin_group_list" id="vo">
										<option value="{pigcms{$vo.group_id}" <if condition="$now_store['plat_recommend_info_arr']['value'] eq $vo['group_id']">selected="selected"</if>>{pigcms{$vo.group_name}</option>
										</volist>
									</select>
									<span class="form_tips">{pigcms{:L_("您希望推荐什么商品？")}</span>
								</div>
								<input type="hidden" name="plat_recommend_info" id="plat_recommend_info" value="{pigcms{$now_store.plat_recommend_info}"/>
							</div>
							<div id='open_time' class="tab-pane">
								<div class="alert alert-info">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>
									<p>说明：</p>
									<p>1.  默认关闭所有周几外卖/餐饮接单时间段，关闭代表这一天营业时间为休息中；</p>
									<p>2. 开启后，每周几外卖/餐饮接单时间段的第一个时间段设置为00:00至00:00则代表24小时营业。</p>
									<p>3. 如果想要设置营业时间不跨天，则结束时间最晚只支持设置到23:59</p>
								</div>
								<volist name="time_date" id="ti">
								<div class="tab-content">
									<div class="form-group">
										<label class="col-sm-1" style="width:150px;" for="week_status">{pigcms{$ti.week_show}</label>
										<select name="week_status[{pigcms{$ti.week}]" id="week_status">
											<option value="0" <if condition="$ti['week_status'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ti['week_status'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
										<if condition="$key eq 0">
											<button type="button" class="btn btn-sm btn-success" id="fast_copy_time" style="margin-left:50px;">一键复制周日时间段到其他时间</button>
										</if>
									</div>
									<volist name="ti['time_list']" id="li">
									<div id="shop_time_{pigcms{$ti.week}_{pigcms{$li.time_sort}" class="tab-pane in active form-group">
										<label class="col-sm-1" style="width:150px;">{pigcms{:L_("外卖/餐饮接单时间段")}{pigcms{$li.time_sort}</label>
										<div>
											<input class="open_time_{pigcms{$li.time_sort}" id="Config_shop_start_time_{pigcms{$ti.week}_{pigcms{$li.time_sort}" type="text" value="{pigcms{$li.open_time|default='00:00'}" name="open_time[{pigcms{$ti.week}][{pigcms{$li.time_sort}]" readonly/>	{pigcms{:L_("至")}
											<input class="close_time_{pigcms{$li.time_sort}" id="Config_shop_stop_time_{pigcms{$ti.week}_{pigcms{$li.time_sort}" type="text" value="{pigcms{$li.close_time|default='00:00'}" name="close_time[{pigcms{$ti.week}][{pigcms{$li.time_sort}]" readonly/>
											<div class="errorMessage" id="Config_shop_start_time_em_" style="display:none"></div>
											<div class="errorMessage" id="Config_shop_stop_time_em_" style="display:none"></div>
										</div>
									</div>
									</volist>
								</div>
								</volist>
							</div>
							<!-- 叮叮配送 -->
							<if condition="$config['dindin_is_open'] eq 1">
								<div id='dingding' class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("是否叮叮配送")}</label>
										<label><input type="radio" name="is_open_dingding" value="0" <if condition="$now_store.is_open_dingding == 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("关闭")}</label>&nbsp;&nbsp;&nbsp;
										<label><input type="radio" name="is_open_dingding" value="1" <if condition="$now_store.is_open_dingding == 1">checked="checked"</if> >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>&nbsp;&nbsp;&nbsp;
										<span class="form_tips"> {pigcms{:L_("开启后可使用叮叮")}</span>
									</div>
									<div class="form-group" id="dingding1" <if condition="$now_store.is_open_dingding == 0">style="display: none;"</if>>
										<label class="col-sm-1"><label for="dingding_user_name">{pigcms{:L_("用户名")}</label></label>
										<input class="col-sm-1" size="20" name="dingding_user_name" id="dingding_user_name" value="{pigcms{$now_store.dingding_user_name}" type="text"/>
									   <span class="form_tips" style="color:red"> {pigcms{:L_("如需绑定叮叮,可到叮叮后台查看当前门店登录名,必须是叮叮审核通过的店铺")}</span>
									</div>
									<div class="form-group" id="dingding2" <if condition="$now_store.is_open_dingding == 0">style="display: none;"</if>>
										<label class="col-sm-1"><label for="dingding_pass">{pigcms{:L_("密码")}</label></label>
										<input class="col-sm-3" size="20" name="dingding_pass" id="dingding_pass" type="text" value="{pigcms{$now_store.dingding_pass}"/>
								<span class="form_tips" style="color:red"> {pigcms{:L_("作为当前叮叮门店的密码,请谨慎操作!")}</span>
									</div>
									<div class="form-group" id="dingding3" <if condition="$now_store.is_open_dingding == 0">style="display: none;"</if>>
										<label class="col-sm-1"><label for="dingding_per_userid">{pigcms{:L_("第三方用户标识")}</label></label>
										<input class="col-sm-3" size="20" name="dingding_per_userid" id="dingding_per_userid" type="text" value="{pigcms{$now_store.dingding_per_userid}"/>
<span class="form_tips" style="color:red"> {pigcms{:L_("如需绑定叮叮后台查看当前门店第三方用户标识,必须是叮叮审核通过的店铺")}</span>
									</div>
<div class="form-group" id="dingding4" <if condition="$now_store.is_open_dingding == 0">style="display: none;"</if>>
<label class="col-sm-1"><label for="dingding_user_name">绑定叮叮店铺地址</label></label>
<input class="col-sm-3" size="20" name="dingding_address" id="dingding_address" value="{pigcms{$now_store.dingding_address}" placeholder="市+区+详细地址" type="text"/>
<span class="form_tips" style="color:red"> 如需绑定叮叮,可到叮叮后台查看当前门店得叮叮店铺地址,必须是叮叮审核通过的店铺</span>
</div>
								</div>
							</if>
							
							<if condition="$config['is_hark_system']">
								<div id='wxapp_share' class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1"><label for="name">{pigcms{:L_("分享标题")}</label></label>
										<input class="col-sm-3" name="wxapp_share_title" id="wxapp_share_title" value="{pigcms{$now_store.wxapp_share_title}" type="text"/>
										<span class="form_tips">{pigcms{:L_("选填。用于小程序环境中分享自定义页面、商城商品详情页、商城主页的分享时使用")}</span>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("分享图片")}</label>
										<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectWxappSharePic">{pigcms{:L_("上传图片")}</a>
										<span class="form_tips">{pigcms{:L_("图片比例 500宽*400高。用户在小程序环境中分享自定义页面、商城商品详情页、商城主页的分享时使用")}</span>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("预览")}</label>
										<div id="upload_pic_box">
											<ul id="upload_wxappShare_ul">
												<if condition="$now_store['wxapp_share_pic']">
													<li class="upload_pic_li_wxapp_share_pic">
														<img src="{pigcms{:replace_file_domain($now_store['wxapp_share_pic'])}"/>
														<input type="hidden" name="wxapp_share_pic" value="{pigcms{$now_store.wxapp_share_pic}"/>
														<br/>
														<a href="#" onclick="deleteWxappSharePic();return false;">[ 删除 ]</a>
													</li>
												</if>
											</ul>
										</div>
									</div>
								</div>
							</if>

                            <!--顺丰配送配置-->
                            <if condition="$config['shunfeng_is_open']">
                                <div id='shunfeng' class="tab-pane">
                                    <div class="form-group">
                                        <label class="col-sm-1" for="shunfeng_is_open">{pigcms{:L_("开启顺丰配送")}</label>
                                        <select name="shunfeng_is_open" id="shunfeng_is_open">
                                            <option value="0" <if condition="$now_store['shunfeng_is_open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
                                            <option value="1" <if condition="$now_store['shunfeng_is_open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
                                        </select>
                                    </div>
                                    <div class="form-group shunfeng_setting" <if condition="$now_store.shunfeng_is_open == 0">style="display: none;"</if>>
                                        <label class="col-sm-1"><label for="shunfeng_dev_id">{pigcms{:L_("开发者账号")}</label></label>
                                        <input class="col-sm-2" name="shunfeng_dev_id" id="shunfeng_dev_id" value="{pigcms{$now_store.shunfeng_dev_id}" type="text"/>
                                    </div>
                                    <div class="form-group shunfeng_setting" <if condition="$now_store.shunfeng_is_open == 0">style="display: none;"</if>>
                                        <label class="col-sm-1"><label for="shunfeng_dev_key">{pigcms{:L_("开发者密钥")}</label></label>
                                        <input class="col-sm-2" name="shunfeng_dev_key" id="shunfeng_dev_key" value="{pigcms{$now_store.shunfeng_dev_key}" type="text"/>
                                    </div>
                                    <div class="form-group shunfeng_setting" <if condition="$now_store.shunfeng_is_open == 0">style="display: none;"</if>>
                                        <label class="col-sm-1"><label for="shunfeng_shop_id">{pigcms{:L_("顺丰店铺ID")}</label></label>
                                        <input class="col-sm-2" name="shunfeng_shop_id" id="shunfeng_shop_id" value="{pigcms{$now_store.shunfeng_shop_id}" type="text"/>
                                    </div>
                                </div>
                            </if>


                            <!--蜂鸟配送配置-->
                            <if condition="$config['ele_is_open']">
                                <div id='ele_anubis' class="tab-pane">
                                    <div class="form-group">
                                        <label class="col-sm-1" for="ele_is_open">{pigcms{:L_("开启蜂鸟配送")}</label>
                                        <select name="ele_is_open" id="ele_is_open">
                                            <option value="0" <if condition="$now_store['ele_is_open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
                                            <option value="1" <if condition="$now_store['ele_is_open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
                                        </select>
                                    </div>
                                    <div class="form-group ele_setting" <if condition="$now_store.ele_is_open == 0">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="ele_app_id">{pigcms{:L_("蜂鸟配送App ID")}</label></label>
                                    <input class="col-sm-2" name="ele_app_id" id="ele_app_id" value="{pigcms{$now_store.ele_app_id}" type="text"/>
                                </div>
                                <div class="form-group ele_setting" <if condition="$now_store.ele_is_open == 0">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="ele_secret_key">{pigcms{:L_("蜂鸟配送Secret Key")}</label></label>
                                    <input class="col-sm-2" name="ele_secret_key" id="ele_secret_key" value="{pigcms{$now_store.ele_secret_key}" type="text"/>
                                </div>
                                <div class="form-group ele_setting" <if condition="$now_store.ele_is_open == 0">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="ele_chain_store_code">{pigcms{:L_("蜂鸟配送门店编号")}</label></label>
                                    <input class="col-sm-2" name="ele_chain_store_code" id="ele_chain_store_code" value="{pigcms{$now_store.ele_chain_store_code}" type="text"/>
                                </div>
                                <div class="form-group ele_setting" <if condition="$now_store.ele_is_open == 0">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="ele_transport_name">{pigcms{:L_("蜂鸟配送门店名称")}</label></label>
                                    <input class="col-sm-2" name="ele_transport_name" id="ele_transport_name" value="{pigcms{$now_store.ele_transport_name}" type="text"/>
                                </div>
                                </div>
                            </if>

						</div>
						<div class="space"></div>
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
<div id="modal-table" class="modal fade" tabindex="-1" style="display:block;">
	<div class="modal-dialog" style="width:80%;">
		<div class="modal-content" style="width:100%;">
			<div class="modal-header no-padding" style="width:100%;">
				<div class="table-header">
					<button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						<span class="white">&times;</span>
					</button>
					{pigcms{:L_("选择经纬度")}
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="{pigcms{:L_("尽量填写城市、区域、街道名")}"/>
					<input type="submit" value="{pigcms{:L_("搜索")}"/>
				</form>
				<div style="margin-left:10px;margin-bottom:10px;" id="map_tips">({pigcms{:L_("用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。")}</div>
				<div style="width:100%;height:560px;min-height:560px;" id="cmmap"></div>
			</div>

			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					{pigcms{:L_("关闭")}
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- PAGE CONTENT ENDS -->

<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}",choose_cat_fid="{pigcms{:U('Merchant_category/ajax_cat_fid')}",choose_cat_id="{pigcms{:U('Merchant_category/ajax_cat_id')}";

var choose_street="{pigcms{:U('Area/ajax_street')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js?t=1"></script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script  src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key={pigcms{$config.google_map_ak}&libraries=places"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/google_map.js"></script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js"></script>
</if>
<script type="text/javascript" src="{pigcms{$static_path}js/merchant_category.js"></script>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<script type="text/javascript">
var content_editor_object = {};
$(function($){
	var supplier_info_editor;
	KindEditor.ready(function(K) {
		supplier_info_editor = K.create('#supplier_info', {
			filterMode: false,
			resizeType : 1,
			langType : "{pigcms{$config.system_lang_alias}",
			allowPreviewEmoticons : false,
			allowImageUpload : true,
			uploadJson : '/merchant.php?g=Merchant&c=Upyun&a=kindedtiropic',
			items : ['source', 'fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
				'insertunorderedlist','link', 'unlink','image','media']
		});
		content_editor_object = supplier_info_editor;
	});
	for (var i=0;i<7;i++)
	{
		for (var j=1;j<4;j++)
		{
			var num  = '_'+i+'_'+j;
			var start_name = '#Config_shop_start_time'+num;
			var stop_name  = '#Config_shop_stop_time'+num;
			var jds = jeDate(start_name,{
				theme:{bgcolor:"#2a6496",pnColor:"#00CCFF"},
				minDate:"00:00:00",              //最小日期
				maxDate:"23:59:59",              //最大日期
				format: "hh:mm:ss"
			});
			var jds = jeDate(stop_name,{
				theme:{bgcolor:"#2a6496",pnColor:"#00CCFF"},
				minDate:"00:00:00",              //最小日期
				maxDate:"23:59:59",              //最大日期
				format: "hh:mm:ss"
			});
			//$(start_name).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm:ss','hour':'10','minute':'52','second':'59'}));
			//$(stop_name).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm:ss','hour':'10','minute':'52','second':'59'}));

		}
	}
	
	$('#fast_copy_time').click(function(){
		var open_time_1 = $('.open_time_1').eq(0).val();
		var open_time_2= $('.open_time_2').eq(0).val();
		var open_time_3= $('.open_time_3').eq(0).val();
		var close_time_1= $('.close_time_1').eq(0).val();
		var close_time_2= $('.close_time_2').eq(0).val();
		var close_time_3= $('.close_time_3').eq(0).val();

		$('.open_time_1').val(open_time_1);
		$('.open_time_2').val(open_time_2);
		$('.open_time_3').val(open_time_3);
		$('.close_time_1').val(close_time_1);
		$('.close_time_2').val(close_time_2);
		$('.close_time_3').val(close_time_3);
	});

	$('input:radio[name=discount_type]').click(function(){
		if (1 == $(this).val()) {
			$('.percent').show();
			$('#condition').hide();
		} else if (2 == $(this).val()) {
			$('.percent').hide();
			$('#condition').show();
		}
	});
	$('input:radio[name="age_limit_buy_switch"]').click(function(event) {
		/* Act on the event */
		if($(this).val() == 1){
			$("#age_limit_buy_box").show();
		}
		else{
			$("#age_limit_buy_box").hide();
		}
	});


	$('input:radio[name="is_open_dingding"]').click(function(event) {
		/* Act on the event */
		if($(this).val() == 1){
			$("#dingding1").show();
			$("#dingding2").show();
			$("#dingding3").show();
		}
		else{

			$("#dingding1").hide();
			$("#dingding2").hide();
			$("#dingding3").hide();
		}
	});

    $('#shunfeng_is_open').on('change', function () {
        if ($(this).val() == 1) {
            $('.shunfeng_setting').show();
        } else {
            $('.shunfeng_setting').hide();
        }
    })

    $('#ele_is_open').on('change', function () {
        if ($(this).val() == 1) {
            $('.ele_setting').show();
        } else {
            $('.ele_setting').hide();
        }
    })


});
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){


	$('.see_qrcode').live('click',function(){
		var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
		var qrcode_url = url+encodeURIComponent($(this).attr('href'));
		art.dialog.open(qrcode_url,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("查看页面二维码")}',
			padding: 0,
			width: 312,
			height: 312,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});
	
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
</script>
<style>
.BMap_cpyCtrl{display:none;}
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
#upload_pic_box .upload_pic_li,.upload_pic_li_logo,.upload_pic_li_wxapp_share_pic{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>

<script type="text/javascript">
KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true,
		langType : "{pigcms{$config.system_lang_alias}"
	});
	K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 10){
			alert('{pigcms{:L_("最多上传10个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Config/store_ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
	
	K('#J_selectLogo').click(function(){
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_logo_ul').html('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="logo" value="'+url+'"/></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
	
	K('#J_selectWxappSharePic').click(function(){
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_wxappShare_ul').html('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="wxapp_share_pic" value="'+url+'"/><br/><a href="#" onclick="deleteWxappSharePic();return false;">[ 删除 ]</a></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
	
	

	$('#edit_form').submit(function(){

		content_editor_object.sync();
		$.post("{pigcms{:U('Config/store_edit')}",$('#edit_form').serialize(),function(result){

			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Config/store')}";


			}else{
				alert(result.info);
			}
		})
		return false;
	});

	$('#get_key_btn').click(function(){
		var s_name = $('input[name="name"]');
		s_name.val($.trim(s_name.val()));
		$('#keywords').val($.trim($('#keywords').val()));
		if(s_name.val().length == 0){
			alert('{pigcms{:L_("请先填写店铺名称！")}');
			s_name.focus();
		}else if($('#keywords').val().length != 0){
			alert('{pigcms{:L_("请先删除您填写的关键词！")}');
			$('#keywords').focus();
		}else{
			$.get("{pigcms{:U('Index/Scws/ajax_getKeywords')}",{title:s_name.val()},function(result){
				result = $.parseJSON(result);
				if(result.num == 0){
					alert('{pigcms{:L_("您的店铺名称没有提取到关键词，请手动填写关键词！")}');
					$('#keywords').focus();
				}else{
					$('#keywords').val(result.list.join(' ')).focus();
				}
			});
		}
	});
	
	if($('#bind_store_trade').val() == 'ticket'){
		$('#store_trade_ticket').show();
	}
	$('#bind_store_trade').change(function(){
		$('.store_trade_div').hide();
		if($('#bind_store_trade').val() == 'ticket'){
			$('#store_trade_ticket').show();
		}
	});
	
	$('#plat_recommend_type_group').change(function(){
		build_plat_recommend_type();
	});
	$('#plat_recommend_type_pin_group').change(function(){
		build_plat_recommend_type();
	});
	$('#plat_recommend_type_store').change(function(){
		if($(this).val() == 'cash'){
			$('#plat_recommend_group').hide();
			$('#plat_recommend_description').hide();
		}else if($(this).val() == 'group'){
			$('#plat_recommend_group').show();
			$('#plat_recommend_description').hide();
		}else if($(this).val() == 'description'){
			$('#plat_recommend_description').show();
			$('#plat_recommend_group').hide();
		}
		build_plat_recommend_type();
	});
	$('#plat_recommend_type').change(function(){
		var value = $(this).val();
		if(value == 'store'){
			$('#plat_recommend_store').show();
			
			$('#plat_recommend_type_store').trigger('change');
			
			$('#plat_recommend_pin_group').hide();
		}else{
			$('#plat_recommend_store').hide();
			$('#plat_recommend_description').hide();
			
			if(value == 'meal' || value == 'group'){
				$('#plat_recommend_group').show();
			}else{
				$('#plat_recommend_group').hide();
			}
			
			if(value == 'pin_group'){
				$('#plat_recommend_pin_group').show();
			}else{
				$('#plat_recommend_pin_group').hide();
			}
		}
		
		build_plat_recommend_type();
	});
	
	$('#plat_recommend_type').trigger('change');
});
$('#plat_recommend_type_description').on('keyup',function(){
	build_plat_recommend_type();
})
function build_plat_recommend_type(){
	var value = $('#plat_recommend_type').val();
	if(value == 'pin_group'){
		$('#plat_recommend_info').val('group_'+$('#plat_recommend_type_pin_group').val());
	}else{
		var type = $('#plat_recommend_type_store').val();
		if(type == 'cash'){
			$('#plat_recommend_info').val('cash');
		}else if(type == 'group'){
			$('#plat_recommend_info').val('group_'+$('#plat_recommend_type_group').val());
		}else if(type == 'description'){
			$('#plat_recommend_info').val('description_'+$('#plat_recommend_type_description').val());
		}
	}
}

function deleteWxappSharePic(){
	$('#upload_wxappShare_ul').empty();
}

function deleteImage(path,obj){
	// $.post("{pigcms{:U('Config/store_ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
function addLink(domid,iskeyword, type){
	art.dialog.data('domid', domid);
	art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'{pigcms{:L_("插入链接或关键词',width:800,height:500,yesText:'关闭")}',background: '#000',opacity: 0.45});
}
</script>

<include file="Public:footer"/>
