<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加店铺")}</li>
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
							<if condition="!empty($levelarr) && isset($config['vip_discount_pay_for'])">
								<li>
									<a data-toggle="tab" href="#vipdiscount">{pigcms{:L_("会员优惠")}</a>
								</li>
							</if>
							<if condition="$config['open_store_vr'] eq 1">
								<li>
									<a data-toggle="tab" href="#vrbox">{pigcms{:L_("VR地址设置")}</a>
								</li>
							</if>
							<li>
								<a data-toggle="tab" href="#open_time">{pigcms{:L_("外卖/餐饮接单时间段
									")}</a>
							</li>
							<if condition="isset($config['dindin_is_open']) and $config['dindin_is_open'] eq 1">
								<li>
									<a data-toggle="tab" href="#dingding">{pigcms{:L_("叮叮配送")}</a>
								</li>
							</if>

						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("店铺名称")}</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text"/>
								</div>
								<div class="form-group"><label class="col-sm-1">{pigcms{:L_("是否设置成主店")}</label><label><input type="radio" name="ismain" <if condition="$ismainno">checked="checked"</if> value="1">&nbsp;&nbsp;{pigcms{:L_("是")}</label>
									&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="ismain" value="0"  <if condition="!$ismainno">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>
								 &nbsp;&nbsp;&nbsp;<span class="form_tips">{pigcms{:L_("必须设置一个店铺为主店铺，前端才会显示商家中心")}</span>
								</div>
								<if condition="file_exists('conf/is_hake.no_delete')">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("开启下单年龄限制")}</label>
										<label><input type="radio" name="age_limit_buy_switch" value="0" checked="checked">&nbsp;&nbsp;{pigcms{:L_("关闭")}</label>&nbsp;&nbsp;&nbsp;
										<label><input type="radio" name="age_limit_buy_switch" value="1" >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>&nbsp;&nbsp;&nbsp;
										<span class="form_tips"> {pigcms{:L_("一般用于店铺中有售卖烟酒类等年龄限制时使用，开启后用户下单时需填写生日，符合18岁以上才可正常下单，默认关闭。目前针对快店业务。")}</span>
									</div>
									<div class="form-group" id="age_limit_buy_box" style="display: none;">
										<label class="col-sm-1">{pigcms{:L_("限制年龄")}</label>
										<input class="col-sm-2" style="width:60px" name="age_limit_buy"  type="text" value="18" />
										<span class="form_tips"> {pigcms{:L_("限制岁数。如：18（单位：岁）")}</span>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">{pigcms{:L_("联系电话")}</label></label>
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text"/>
									<span class="form_tips">{pigcms{:L_("多个电话号码以空格分开")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">{pigcms{:L_("客服电话")}</label></label>
									<input class="col-sm-2" size="20" name="kefu_phone" id="kefu_phone" type="text"/>
									<span class="form_tips">{pigcms{:L_("客服下单电话，商家收入余额短信提醒")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="weixin">{pigcms{:L_("联系微信")}</label></label>
									<input class="col-sm-2" size="20" name="weixin" id="weixin" type="text"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="qq">{pigcms{:L_("联系")}Q Q</label></label>
									<input class="col-sm-2" size="20" name="qq" id="qq" type="text"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("关键词：")}</label>
									<input class="col-sm-3" maxlength="30" name="keywords" type="text" value="" id="keywords"/><span class="form_tips">{pigcms{:L_("选填。")}<font color="red">{pigcms{:L_("（用空格分隔不同的关键词，最多5个）")}</font>{pigcms{:L_("，用户在微信将按此值搜索！")}</span> <a href="javascript:;" id="get_key_btn">{pigcms{:L_("按店铺名称获取")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">{pigcms{:L_("店铺经纬度")}</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly="readonly" data-lat="{pigcms{$now_city.area_lat}" data-long="{pigcms{$now_city.area_lng}"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="permoney">{pigcms{:L_("人均消费")}</label></label>
									<input class="col-sm-2" size="20" name="permoney" id="permoney" type="text" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>
									<span class="form_tips"> {pigcms{$config['Currency_txt']}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="feature">{pigcms{:L_("店铺特色")}</label></label>
									<input class="col-sm-2" style="width:600px" name="feature" id="feature" type="text"/>
									<span class="form_tips">{pigcms{:L_("简单描述本店特色之处80字以内")}</span>
								</div>
								<div class="form-group" id="choose_category_s">
									<label class="col-sm-1"><label>{pigcms{:L_("店铺分类")}</label></label>
									<fieldset id="choose_category"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("店铺所在地")}</label></label>
									<fieldset id="choose_cityarea"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="adress">{pigcms{:L_("店铺地址")}</label></label>
									<input class="col-sm-2" size="20" name="adress" id="adress" type="text"/>
									<span class="form_tips">{pigcms{:L_("地址不能带有上面所在地选择的省")}/区/{pigcms{:L_("商圈信息。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="trafficroute">{pigcms{:L_("交通路线")}</label></label>
									<input class="col-sm-2" name="trafficroute" id="trafficroute" type="text" style="width:600px"/>
									<span class="form_tips">{pigcms{:L_("简单描述本店交通路线80字以内")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("店铺排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="0"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
								</div>
								<if condition="$config['meal_alias_name']">
									<div class="form-group">
										<label class="col-sm-1">WiFi{pigcms{:L_("名称")}</label>
										<input class="col-sm-1" name="wifi_account" type="text" value="" />
									</div>
									<div class="form-group">
										<label class="col-sm-1">WiFi{pigcms{:L_("密码")}</label>
										<input class="col-sm-1" name="wifi_password" type="text" value="" />
									</div>
								</if>
								<if condition="$config['store_open_meal']">
									<div class="form-group">
										<label class="col-sm-1" for="have_meal">{pigcms{$config.meal_alias_name}</label>
										<select name="have_meal" id="have_meal">
											<option value="1" selected="selected">{pigcms{:L_("开启")}</option>
											<option value="0">{pigcms{:L_("关闭")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['store_open_meal'] && false">
									<div class="form-group">
										<label class="col-sm-1" for="store_type">{pigcms{$config.meal_alias_name}{pigcms{:L_("类型")}</label>
										<select name="store_type" id="store_type">
											<option value="0" selected="selected">{pigcms{:L_("订餐和外卖")}</option>
											<option value="1">{pigcms{:L_("订餐")}</option>
											<option value="2">{pigcms{:L_("其他")}</option>
										</select>
										<span class="form_tips">{pigcms{:L_("【其他】是指（外卖，超市，花店...）")}</span>
									</div>
								</if>
								<if condition="$config['store_open_meal'] && false">
									<div class="form-group">
										<label class="col-sm-1" for="store_type">{pigcms{$config.meal_alias_name}{pigcms{:L_("类型")}</label>
										<select name="store_type" id="store_type">
											<option value="1" selected="selected">{pigcms{:L_("到店消费")}</option>
											<option value="3">{pigcms{:L_("到店消费和外送")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['store_open_group']">
									<div class="form-group">
										<label class="col-sm-1" for="have_group">{pigcms{$config.group_alias_name}</label>
										<select name="have_group" id="have_group">
											<option value="1" selected="selected">{pigcms{:L_("开启")}</option>
											<option value="0">{pigcms{:L_("关闭")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['store_open_shop']">
									<div class="form-group" <if condition="$config['single_system_type'] eq 'shop'">style="display:none;"</if>>
										<label class="col-sm-1" for="have_shop">{pigcms{$config.shop_alias_name}</label>
										<select name="have_shop" id="have_shop">
											<option value="1" selected="selected">{pigcms{:L_("开启")}</option>
											<option value="0">{pigcms{:L_("关闭")}</option>
										</select>
									</div>
								</if>
								<if condition="$config['mall_alias_name']">
									<div class="form-group">
										<label class="col-sm-1" for="have_mall">{pigcms{$config.mall_alias_name}</label>
										<select name="have_mall" id="have_mall">
											<option value="1" selected="selected">{pigcms{:L_("开启")}</option>
											<option value="0">{pigcms{:L_("关闭")}</option>
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
												<li class="upload_pic_li"><img src="{pigcms{$now_store.logo}"/><input type="hidden" name="logo" value="{pigcms{$now_store.logo}"/></li>
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
<!--									<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('supplier_nav_second_link',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>href="#modal-table"-->
<!--								</div>-->
							</div>
							<div id="txtstore" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺描述")}</label>
									<textarea class="col-sm-5" rows="5" name="txt_info"></textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺详情")}</label>
									<textarea class="col-sm-3" id="supplier_info" name="supplier_info" style="width: 300px; height: 250px; display: none;"></textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("店铺图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("第一张将作为主图片！最多上传10个图片！图片宽度建议为700px，高度建议为420px。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_merchant['pic']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
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
									<p>{pigcms{:L_("这里的设置只能用于优惠快速买单的地方，其他方式不起作用。如果在快速买单中不想优惠，那么就把优惠的选项全部清空就行了")}</p>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("优惠类型")}</label>
									<label><input type="radio" name="discount_type" value="1">&nbsp;&nbsp;{pigcms{:L_("折扣")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="discount_type" value="2">&nbsp;&nbsp;{pigcms{:L_("满减")}</label>&nbsp;&nbsp;&nbsp;
								</div>
								<div class="form-group percent" style="display:none" id="percent">
									<label class="col-sm-1">{pigcms{:L_("普通折扣率")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_percent"  type="text" value="0" /><b style="color:red">折</b>
									<span class="form_tips">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折")}</span>
								</div>
								<if condition="$config.open_extra_price eq 1">
								<div class="form-group percent" style="display:none" >
									<label class="col-sm-1">{pigcms{:L_("每天限单折扣单数")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_limit"  type="text" value="" /><b style="color:red">单</b>
									<span class="form_tips">{pigcms{:L_("请填大于0的数字，不带小数点，0代表无")}</span>
								</div>
								<div class="form-group percent" style="display:none" >
									<label class="col-sm-1">{pigcms{:L_("每天限单折扣率")}</label>
									<input class="col-sm-2" style="width:60px" name="discount_limit_percent"  type="text" value="" /><b style="color:red">折</b>
									<span class="form_tips">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，每天限单数内的折扣，超过数量将使用普通折扣率")}</span>
								</div>
								</if>
								<div class="form-group" style="display:none" id="condition">
									<label class="col-sm-1">{pigcms{:L_("每满")}</label>
									<input class="col-sm-2" style="width:60px" name="condition_price"  type="text" value="0" />
									<label class="col-sm-1" style="color:red;width: 60px;">{pigcms{$config['Currency_txt']}{pigcms{:L_("，减")}</label>
									<input class="col-sm-2" style="width:60px" name="minus_price"  type="text" value="0" />
									<label class="col-sm-1" style="color:red">{pigcms{$config['Currency_txt']}</label>
								</div>
							</div>
							<if condition="!empty($levelarr)">
								<div id="vipdiscount" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！")}</label>
									</div>
								   <volist name="levelarr" id="vv">
									  <div class="form-group">
										<input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
										<input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
										<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
										{pigcms{:L_("优惠类型：")}&nbsp;
										<select name="leveloff[{pigcms{$vv['level']}][type]" <if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if>>
											<option value="0" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 0">selected="selected"</if>>{pigcms{:L_("无优惠")}</option>
											<option value="1" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 1">selected="selected"</if>>{pigcms{:L_("百分比（%）")}</option>
											<option value="2" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 2">selected="selected"</if>>{pigcms{:L_("立减")}</option>
										</select>
										<input name="leveloff[{pigcms{$vv['level']}][vv]" type="text" value="<if condition="$config.discount_sync eq 1 ">{pigcms{$vv.boon}</if>" placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')" <if condition="empty($_SESSION['system']) AND $config['discount_controler'] eq 1">disabled</if>/>
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
							<div id='open_time' class="tab-pane">
								<div class="alert alert-info">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>
									<p>说明：</p>
									<p>1. 默认关闭所有周几外卖/餐饮接单时间段，关闭代表这一天营业时间为休息中；</p>
									<p>2. 开启后，每周几外卖/餐饮接单时间段的第一个时间段设置为00:00至00:00则代表24小时营业。</p>
								</div>
								<volist name="open_time" id="ti">
									<div class="tab-content">
										<div class="form-group">
											<label class="col-sm-1" for="week_status" style="width:150px;">{pigcms{$ti.show_week}</label>
											<select name="week_status[{pigcms{$ti.week}]" id="week_status">
												<option value="0" selected="selected">{pigcms{:L_("关闭")}</option>
												<option value="1" >{pigcms{:L_("开启")}</option>
											</select>
											<if condition="$key eq 0">
												<button type="button" class="btn btn-sm btn-success" id="fast_copy_time" style="margin-left:50px;">一键复制周日时间段到其他时间</button>
											</if>
										</div>
										<volist name="ti['sort']" id="li">
											<div id="shop_time_{pigcms{$ti.week}_{pigcms{$li.time_sort}" class="tab-pane in active form-group">
												<label class="col-sm-1" style="width:150px;">{pigcms{:L_("外卖/餐饮接单时间段")}{pigcms{$li}</label>
												<div>
													<input class="open_time_{pigcms{$li}" id="Config_shop_start_time_{pigcms{$ti.week}_{pigcms{$li}" type="text" value="{pigcms{$li.open_time|default='00:00:00'}" name="open_time[{pigcms{$ti.week}][{pigcms{$li}]" readonly/>	{pigcms{:L_("至")}
													<input class="close_time_{pigcms{$li}" id="Config_shop_stop_time_{pigcms{$ti.week}_{pigcms{$li}" type="text" value="{pigcms{$li.close_time|default='00:00:00'}" name="close_time[{pigcms{$ti.week}][{pigcms{$li}]" readonly/>
													<div class="errorMessage" id="Config_shop_start_time_em_" style="display:none"></div>
													<div class="errorMessage" id="Config_shop_stop_time_em_" style="display:none"></div>
												</div>
											</div>
										</volist>
									</div>
								</volist>
							</div>
							<if condition="$config['dindin_is_open'] eq 1">
								<div id='dingding' class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("是否叮叮配送")}</label>
										<label><input type="radio" name="is_open_dingding" value="0" <if condition="$now_store.is_open_dingding == 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("关闭")}</label>&nbsp;&nbsp;&nbsp;
										<label><input type="radio" name="is_open_dingding" value="1" <if condition="$now_store.is_open_dingding == 1">checked="checked"</if> >&nbsp;&nbsp;{pigcms{:L_("开启")}</label>&nbsp;&nbsp;&nbsp;
										<span class="form_tips"> {pigcms{:L_("开启后可使用叮叮")}</span>
									</div>
									<div class="form-group" id="dingding1" style="display: none;">
										<label class="col-sm-1"><label for="dingding_user_name">{pigcms{:L_("用户名")}</label></label>
										<input class="col-sm-1" size="20" name="dingding_user_name" id="dingding_user_name" value="" type="text"/>
										<span class="form_tips" style="color:red;">用户名为叮叮的登录名,添加成功后,不可更改以及删除,请谨慎操作</span>
									</div>
									<div class="form-group" id="dingding2" style="display: none;">
										<label class="col-sm-1"><label for="dingding_pass">{pigcms{:L_("密码")}</label></label>
										<input class="col-sm-3" size="20" name="dingding_pass" id="dingding_pass" type="text" value=""/>
										<span class="form_tips" style="color:red;">密码为叮叮的登录密码,请谨慎操作</span>
									</div>
									<div class="form-group" id="dingding4" style="display: none;">
										<label class="col-sm-1"><label for="dingding_user_name">叮叮当前店铺地址</label></label>
										<input class="col-sm-3" size="20" name="dingding_address" id="dingding_address" value="" placeholder="市+区+详细地址" type="text"/>
										<span class="form_tips" style="color:red"> 添加叮叮的当前店铺地址</span>
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
		</div>
	</div>
</div>

<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}",choose_cat_fid="{pigcms{:U('Merchant_category/ajax_cat_fid')}",choose_cat_id="{pigcms{:U('Merchant_category/ajax_cat_id')}";
var choose_street="{pigcms{:U('Area/ajax_street')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js?t=1"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/merchant_category.js"></script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script  src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key={pigcms{$config.google_map_ak}&libraries=places"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/google_map.js"></script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js"></script>
</if>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script type="text/javascript">
var editor;
KindEditor.ready(function(K) {
	editor = K.create('#supplier_info', {
		filterMode: false,
		resizeType : 1,
		langType : "{pigcms{$config.system_lang_alias}",
		allowPreviewEmoticons : false,
		allowImageUpload : true,
		uploadJson : '/merchant.php?g=Merchant&c=Upyun&a=kindedtiropic',
		items : ['source', 'fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr',
			'|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist','link', 'unlink','image','media']
	});

});
$(function($){
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
			//$(start_name).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
			//$(stop_name).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));

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

		}
		else{

			$("#dingding1").hide();
			$("#dingding2").hide();

		}
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
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
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

	$('#edit_form').submit(function(){
		$('#edit_form button[type="submit"]').prop('disabled',true).html('{pigcms{:L_("保存中")}...');
		$.post("{pigcms{:U('Config/store_add')}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Config/store')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("保存")}');
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
});
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
