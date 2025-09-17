<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Shop/goods_sort_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('Shop/goods_list',array('sort_id'=>$now_sort['sort_id'],'store_id'=>$now_store['store_id']))}">{pigcms{$now_sort.sort_name}</a></li>
			<li class="active">{pigcms{:L_("添加商品")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				table .reg_img{height: 60px;vertical-align: bottom;}
				table .reg_file{position: relative;}
				label>font{vertical-align: middle;margin-top: 2px;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtintro">{pigcms{:L_("商品详情")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtattr">{pigcms{:L_("商品规格")}</a>
							</li>
							<!--<li>
								<a data-toggle="tab" href="#seckill">{pigcms{:L_("限时优惠")}</a>
							</li>-->
							<if condition="$now_store['store_theme'] AND $category_list">
							<li>
								<a data-toggle="tab" href="#category">{pigcms{:L_("商城属性设置")}</a>
							</li>
							</if>
							<if condition="$config['open_meal_electronic_menu']">
							<li>
								<a data-toggle="tab" href="#description">{pigcms{:L_("商品基本属性")}</a>
							</li>
							</if>
                            <!-- 商家积分抵扣 -->
                            <li>
                                <a data-toggle="tab" href="#merScoreRate">{pigcms{:L_("商家积分抵扣")}</a>
                            </li>
							<li>
								<a data-toggle="tab" href="#combination">{pigcms{:L_("组合套餐")}</a>
							</li>
                            <if condition="$is_set_employee_card_mer_id">
                                <li>
                                    <a data-toggle="tab" href="#employee_card">{pigcms{:L_("员工商品配置")}</a>
                                </li>
                            </if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="0" id="goods_id" />
                        <input type="hidden" value="0" name="miaomaiyun_g_m_id" id="miaomaiyun_g_m_id" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<if condition="$error_tips">
									<div class="alert alert-danger">
										<p>{pigcms{:L_("请更正下列输入错误")}:</p>
										<p>{pigcms{$error_tips}</p>
									</div>
								</if>
								<if condition="$ok_tips">
									<div class="alert alert-info">
										<p>{pigcms{$ok_tips}</p>
									</div>
								</if>
                                <eq name="is_open_online_collection" value="1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">{pigcms{:L_("淘宝/天猫/阿里巴巴/拼多多地址")}</label></label>
                                        <input class="col-sm-2" style="width:30%" name="taobao_url"  type="text" placeholder="{pigcms{:L_("请输入淘宝/天猫/阿里巴巴/拼多多的商品详情页地址")}"/>
                                        <a class="btn btn-sm btn-success" id="parse_taobao_url" style="line-height: 1.2;margin-left: 10px">{pigcms{:L_("点击采集")}</a>
                                    </div>
                                </eq>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("快速录入")}</label></label>
									<input class="col-sm-2" name="sysname" id="sysname" type="text" placeholder="{pigcms{:L_("扫商品条码录入")}"/> 
									<a class="btn btn-sm btn-success" onclick="addLink('sysname')" style="line-height: 1.2;margin-left: 10px">{pigcms{:L_("从商品库选择")}</a>

<!--                                    <eq name='config.miaomaiyun_erp_open' value='1'>-->
<!--                                        <a class="btn btn-sm btn-success" onclick="chooseFromMiaomaiyunErp('{pigcms{$now_store.store_id}')" style="line-height: 1.2;margin-left: 10px">{pigcms{:L_("淼迈云ERP商品库选择")}</a>-->
<!--                                    </eq>-->
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商品分类")}<font color="red">*</font></label></label>
									<fieldset id="choose_sort"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}<font color="red">*</font></label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$now_goods.name}"/>
									<input id="is_open_shop_weigh" type="hidden" value="{pigcms{$config.is_open_shop_weigh}"/>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
                                    <span id="name_limit" style="display: none;color: red;">{pigcms{:L_("商品名称建议12字以内展示效果更佳！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品副标题")}</label></label>
									<textarea id="describe" class="col-sm-2" name="describe" size='30' style="resize:vertical;" placeholder='{pigcms{:L_("建议30字以内！")}' rows="5">{pigcms{$now_goods.describe}</textarea>
									<span class="form_tips"></span>
                                    <span id="describe_limit" style="display: none;color: red;">{pigcms{:L_("商品副标题请不要超过30个字！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("上传图片")}<font color="red">*</font></label></label>
									<div style="display:inline-block;" id="J_selectImage">
										<div class="btn btn-sm btn-success" style="position:relative;">{pigcms{:L_("选择本地图片")}</div>
									</div>
									<span class="form_tips">{pigcms{:L_("图片尺寸支持900*500px与600*600px，最多上传5个图片")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("选择图片")}</label></label>
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('upload_pic_ul','goods')">{pigcms{:L_("从图片库选择")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("图片预览")}</label></label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul"></ul>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("上传视频")}</label></label>
									<div style="display:inline-block;">
										<div id="select_video" class="btn btn-sm btn-success" style="position:relative;">{pigcms{:L_("上传视频")}</div>
									</div>
									<span class="form_tips">{pigcms{:L_("上传视频后，将在用户端商品详情页第一张主图位置显示视频信息，只支持上传一个视频且格式只支持MP4，视频文件大小建议40MB以内")}</span>
									<input type="hidden" name="shop_video_url" value="{pigcms{$now_goods.shop_video_url}"/>
								</div>
                                <if condition="$config.is_open_shop_weigh eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="price">{pigcms{:L_("计价方式")}</label></label>
                                        <select name="sell_type" id="sell_type">
                                            <option value="1" selected>{pigcms{:L_("计件")}</option>
                                            <option value="2">{pigcms{:L_("计重")}</option>
                                        </select>
                                        <span class="form_tips">{pigcms{:L_("称重商品请选择计重模式")}</span>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品价格")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="price" id="price" type="text" value="{pigcms{$now_goods.price}"/>
									<if condition="$config.open_extra_price eq 1">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="{pigcms{$now_goods.extra_pay_price}" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>
									<span class="form_tips">{pigcms{:L_("必填。")} <span class="weight" style="display: none;">{pigcms{:L_("计重类的商品价格无需设置，是根据下面的商品单价与重量自动计算出来的每份商品价格。")}</span></span>
								</div>
                                <div class="form-group weight" style="display: none;">
                                    <label class="col-sm-1"><label for="unit_price">{pigcms{:L_("商品单价")}<font color="red">*</font></label></label>
                                    <input class="col-sm-1" size="20" name="unit_price" id="unit_price" type="text" value="{pigcms{$now_goods.unit_price}"/>
                                    <span class="form_tips">{pigcms{:L_("元500g（一斤），必填。")}</span>
                                </div>
                                <if condition="$config.is_open_shop_weigh eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="weight">{pigcms{:L_("商品重量")}<font class="weight" color="red" style="display: none;">*</font></label></label>
                                        <input class="col-sm-1" size="20" name="goods_weight" id="weight" type="text" value="{pigcms{$now_goods.goods_weight}"/>
                                        <select name="weight_unit" id="weight_unit" style="margin-left: 10px;">
                                            <option value="1" selected>g</option>
                                            <option value="2">kg</option>
                                        </select>
                                        <span class="form_tips weight" style="display: none;">{pigcms{:L_("必填。")}</span>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品进价")}</label></label>
									<input class="col-sm-1" size="20" name="cost_price" id="cost_price" type="text" value="{pigcms{$now_goods.cost_price}"/>
									<span class="form_tips">{pigcms{:L_("进货价用户是看不到")}</span>
								</div>
                                <div class="form-group weight" style="display: none;">
                                    <label class="col-sm-1"><label for="tare_weight">{pigcms{:L_("商品皮重")}</label></label>
                                    <input class="col-sm-1" size="20" name="tare_weight" id="weight" type="text" value="{pigcms{$now_goods.tare_weight}"/>
                                    <span class="form_tips">{pigcms{:L_("单位")}g</span>
                                </div>
                                <div class="form-group weight" style="display: none;">
                                    <label class="col-sm-1"><label for="stock_weight">{pigcms{:L_("库存重量")}</label></label>
                                    <div>{pigcms{:L_("当前库存重量")}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("原始库存重量")}</div>
                                    <div>
                                        <input style="width:100px;" name="stock_weight" id="stock_weight" type="text" value="{pigcms{$now_goods.stock_weights|default='-1'}"/>   /
                                        <input style="width:100px;" name="original_weight" id="original_weight" type="text" value="{pigcms{$now_goods.original_weight|default='-1'}"/>
                                        <span class="form_tips">-1{pigcms{:L_("表示无限量，单位g，必填")} </span>
                                    </div>
                                </div>
								<div class="form-group num_stock">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品库存")}</label></label>
									<div>{pigcms{:L_("当前库存")}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("原始库存")}</div>
									<div>
										<input style="width:70px;" name="stock_num" id="stock_num" type="text" value="{pigcms{$now_goods.stock_num|default='-1'}"/>   /
                                        <input style="width:70px;" name="original_stock" id="original_stock" type="text" value="{pigcms{$now_goods.original_stock|default='-1'}"/>
										<span class="form_tips">-1{pigcms{:L_("表示无限量。当前库存小于10时，商品详细页面会显示库存。")}</span>
									</div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="unit">{pigcms{:L_("商品单位")}<font color="red">*</font></label></label>
                                    <input class="col-sm-1" size="20" name="unit" id="unit" type="text" value="{pigcms{:L_('份')}"/>
                                    <span class="form_tips">{pigcms{:L_("必填。如个、斤、份")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<input class="col-sm-1" size="20" name="number" id="number" type="text" value="{pigcms{$now_goods.number}"/>
									<span class="form_tips" id="number_tips">{pigcms{:L_("选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{$now_store['pack_alias']|default=L_("打包费")}</label></label>
									<input class="col-sm-1" size="20" name="packing_charge" id="packing_charge" type="text" value="{pigcms{$now_goods.packing_charge|floatval}"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort">{pigcms{:L_("商品排序")}</label></label>
                                    <input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_goods.sort|default='0'}"/>
                                    <span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
                                </div>
								<div class="form-group">
                                    <label class="col-sm-1" for="single_no_delivery"><label>{pigcms{:L_("单点不送")}</label></label>
                                    <select name="single_no_delivery" id="single_no_delivery">
										<option value="0" <eq name="now_goods.single_no_delivery" value="0">selected="selected"</eq>>{pigcms{:L_("关闭")}</option>
                                        <option value="1" <eq name="now_goods.single_no_delivery" value="1">selected="selected"</eq>>{pigcms{:L_("开启")}</option>
                                    </select>
									<span class="form_tips" style="color: red;">{pigcms{:L_("商品设置单点不送后，顾客不可单独购买此类商品，进而有效保障订单利润")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商品状态")}</label></label>
                                    <select name="status" id="Food_status">
                                        <option value="1" <eq name="now_goods.status" value="1">selected="selected"</eq>>{pigcms{:L_("正常")}</option>
                                        <option value="0" <eq name="now_goods.status" value="0">selected="selected"</eq>>{pigcms{:L_("停售")}</option>
                                    </select>
									<span class="form_tips" style="color: red;">{pigcms{:L_("由于部分商品存在有货但线上不售卖，所以停售只会关闭用户下单场景，店员端线下零售商品库中会展示。")}</span>
                                </div>

							</div>
							<div id="txtintro" class="tab-pane">
								<!--div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品原价")}</label></label>
									<input class="col-sm-1" size="20" name="old_price" id="old_price" type="text" value="{pigcms{$now_goods.old_price|floatval}"/>
									<span class="form_tips">{pigcms{:L_("原价可不填，不填和现价一样")}</span>
								</div-->
                                <div class="form-group num_stock">
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("限购类型")}</label></label>
                                    <select name="limit_type" id="limit_type">
                                        <option value="0" <eq name="now_goods.limit_type" value="0">selected</eq>>{pigcms{:L_("每单限购")}</option>
                                        <option value="1" <eq name="now_goods.limit_type" value="1">selected</eq>>{pigcms{:L_("每个ID限购")}</option>
                                    </select>
                                </div>
                                <div class="form-group num_stock">
                                    <label class="col-sm-1"><label id="label_limit">{pigcms{:L_("每单限购")}</label></label>
                                    <input class="col-sm-1" size="20" name="max_num" id="max_num" type="text" value="{pigcms{$now_goods.max_num}"/>
                                    <span class="form_tips" id="label_tips">{pigcms{:L_("每个订单该商品最多可订购的数量，0为不限制，如果同时设置了【最小购买数】与【限购类型】，则【限购类型】无效")}</span>
                                </div>

								<div class="form-group num_stock">
									<label class="col-sm-1"><label for="min_num">{pigcms{:L_("最小购买数量")}</label></label>
									<input class="col-sm-1" size="10" name="min_num" id="min_num" type="text" value="{pigcms{$now_goods.min_num}"/>
									<span class="form_tips">{pigcms{:L_("用户订购此商品时最小购买量，如果同时设置了【最小购买数】与【限购类型】，则【限购类型】无效")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1" for="is_discount"><label>{pigcms{:L_("商品分组")}</label></label>
									<div class="show-select-value" style="">
										<div class="select-list">
										</div>
										<span class="select-icon"></span>
										<ul class="list">
											<volist name="shop_goods_group_list" id="vo">
												<li class="item" data-id="{pigcms{$vo.id}">{pigcms{$vo.name}</li>
											</volist>
										</ul>
										<input type="hidden" name="group_ids" value="">
									</div>
									
                                    <span class="form_tips" style="color: red;">{pigcms{:L_("关联分组后，可在店铺自定义页面装修时一键选择该分组下的所有商品")}</span>
								</div>
								<style type="text/css">
									.show-select-value{position: relative;float: left;border: 1px solid #d5d5d5;width: 400px;min-height: 30px;border-radius: 2px;}
									.show-select-value .select-list{margin: 10px}
									.show-select-value .select-list .item{float: left;border:1px solid #d5d5d5;background: #f2f2f2;padding:0 5px;margin: 0 10px 10px 0;border-radius: 2px;}
									.show-select-value .select-list .item .icon{padding: 0 5px;font-weight: bold;cursor: pointer;}
									.show-select-value .select-icon{position: absolute;right: 10px;top: 17px;vertical-align: middle;width: 0;height: 0;border: 5px solid transparent;border-top-color:#666;}
									.show-select-value .list{position: absolute;bottom: -100px;width: 400px;border: 1px solid #1686ef;background: #fff;border-radius: 2px;display: none;}
									.show-select-value .list.active{display: block;}
									.show-select-value .list .item{padding: 2px 10px}
									.show-select-value .list .item:hover{background: #1686ef;color: #fff;}
								</style>
								<script type="text/javascript">
									$(document).on('click', '.show-select-value', function(){
										if ($(this).find('.list').attr('class').indexOf('active') != -1) {
											$(this).find('.list').removeClass('active');
											return 
										}
										$(this).find('.list').addClass('active').css('bottom', -$(this).find('.list').height()-2)
									})

									$(document).on('click', '.show-select-value .list .item', function(){
										var group_id = $(this).data('id');
										var group_ids = $('input[name=group_ids]').val();
										if (group_ids != '') {
											var group_ids_arr = group_ids.split(',');
											var selected = 0;
											$.each(group_ids_arr,function (index,j) {
												if(group_id == j){
													selected = 1;
												}
											})
											if (selected==0) {
												group_ids_arr.push(group_id);
												group_ids = group_ids_arr.join(',');
												$('input[name=group_ids]').val(group_ids);
											}else{
												$('.show-select-value .list').removeClass('active');
												return false;
											}
										}else{
											$('input[name=group_ids]').val(group_id);
										}
										$('.show-select-value .select-list').append('<div class="item" >'+ $(this).html() +'<span class="icon">×</span></div>');
										$('.show-select-value .list').removeClass('active');
										return false
									})
									$(document).on('click', '.show-select-value .select-list .item .icon', function(){
										var group_id = $(this).data('id');
										var group_ids = $('input[name=group_ids]').val();
										var group_ids_arr = group_ids.split(',');
										group_ids_arr.splice($.inArray(group_id,group_ids_arr),1);
										group_ids = group_ids_arr.join(',');
										$('input[name=group_ids]').val(group_ids);
										$(this).parents('.item').remove()
										return false
									})
								</script>
								<div class="form-group">
									<label class="col-sm-1"><label for="is_discount">{pigcms{:L_("是否参与折扣")}</label></label>
									<select name="is_discount" id="is_discount">
                                        <option value="1" <eq name="now_goods.is_discount" value="1">selected="selected"</eq>>{pigcms{:L_("参与折扣")}</option>
										<option value="0" <eq name="now_goods.is_discount" value="0">selected="selected"</eq>>{pigcms{:L_("不参与折扣")}</option>
									</select>
                                    <span class="form_tips" style="color: red;">{pigcms{:L_("该商品是否参与店铺")}/{pigcms{:L_("分类折扣;折扣包括店铺折扣与分类折扣")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="is_use_coupon"><label>{pigcms{:L_("是否可用商家优惠券")}</label></label>
                                    <select name="is_use_coupon" id="is_use_coupon">
                                        <option value="1" <eq name="now_goods.is_use_coupon" value="1">selected="selected"</eq>>{pigcms{:L_("可用")}</option>
                                        <option value="0" <eq name="now_goods.is_use_coupon" value="0">selected="selected"</eq>>{pigcms{:L_("不可用")}</option>
                                    </select>
                                    <span class="form_tips" style="color: red;">{pigcms{:L_("该商品是否享受商家优惠券的优惠策略")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("每天显示时段")}</label></label>
                                    <div>
                                        <input id="show_start_time" type="text" value="{pigcms{:substr($now_goods['show_start_time'], 0, -3)}" name="show_start_time" readonly style="width:70px"/>   至
                                        <input id="show_end_time" type="text" value="{pigcms{:substr($now_goods['show_end_time'], 0, -3)}" name="show_end_time" readonly style="width:70px"/>
                                        <span class="form_tips" style="color: red;">{pigcms{:L_("不填或都填写00:00表示24小时都显示")}</span>
                                    </div>
                                </div>
								<if condition="$print_list">
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("归属打印机")}</label></label>
									<select name="print_id" id="print_id">
										<option value="0" selected>{pigcms{:L_("选择打印机")}</option>
										<volist name="print_list" id="print">
										<option value="{pigcms{$print['pigcms_id']}" <if condition="$print['pigcms_id'] eq $now_goods['print_id']">selected</if>>{pigcms{$print['name']}</option>
										</volist>
									</select>
									<span class="form_tips" style="color:red;">{pigcms{:L_("如果选择了一台非主打印机的话，那么客户在下单的时候选择的打印机和主打印机同时打印，如果不选打印机或是选择了主打印机的话，那么就主打印机打印")}</span>
								</div>
								</if>
								<div class="form-group" >
									<label class="col-sm-1"><label>{pigcms{:L_("商品详情：")}</label></label>
									<textarea name="des" id="content" style="width:702px;" data-h="450">{pigcms{$now_goods.des}</textarea>
								</div>
							</div>
							<div id="txtattr" class="tab-pane">
								<div class='topic_til'> 
									<h4>{pigcms{:L_("商品规格")}</h4>
								</div>
								<div class="alert alert-info">
									<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
									{pigcms{:L_("规格：当商品有规格时，请为商品添加规格。例如：为“酸菜鱼”设置“份量”：“大份”，“中份”，“小份”。")}
                                    <font color="red">{pigcms{:L_("每次新增或者删除规格值时，必须要重新生成规格关系后才可正常使用。")}</font>
								</div>
								<div class="topic_box">
									<volist name="now_goods['spec_list']" id="row" key="ii">
									<div class="question_box spec">
										<p class="question_info"><span>{pigcms{:L_("规格名称：")}</span>
											<input type="text" class="txt spec_name" value="{pigcms{$row['name']}" name="specs[]"/>
											<input type="hidden" name="spec_id[]" value="{pigcms{$row['id']}"/>
											<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>
										</p>
										<ul id="1" class="optionul">
											<volist name="row['list']" id="r">
											<li>
												<u>{pigcms{:L_("规格属性值：")}</u>
												<input type="hidden" class="hide_txt spec_val_id" name="spec_val_id[{pigcms{$ii-1}][]" value="{pigcms{$r['id']}">
												<input type="text" class="txt spec_val" name="spec_val[{pigcms{$ii-1}][]" value="{pigcms{$r['name']}"/>
												<a class="list_del" href="javascript:;" title="{pigcms{:L_("删除这个选项")}">×</a>
											</li>
											</volist>
										</ul>
										<p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  {pigcms{:L_("添加规格的属性值")}</a></p>
									</div>
									</volist>
									<p class="add_spec"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success" <if condition="count($now_goods['spec_list']) egt 3">style="display:none"</if>>{pigcms{:L_("添加规格")}</a></p>
								</div>
                                <span id="spec_limit" style="display: none;color: red;">{pigcms{:L_("名称请不要超过12个字！")}</span>
							    <div class='topic_til'> 
                                    <h4>{pigcms{:L_("商品属性")} </h4>
                                </div>
                                <div class="alert alert-info">
                                    <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                                    {pigcms{:L_("属性：当商品有属性时，请为商品添加属性信息。例如：为“酸菜鱼”设置“辣度”：“大辣”，“中辣”，“小辣”。")}
                                    <font color="red">{pigcms{:L_("每次新增或者删除属性值时，必须要重新生成规格关系后才可正常使用。")}</font>
                                </div>
								<div class="topic_box">
									<volist name="now_goods['properties_status_list']" id="ro" key="ik">
										<div class="question_box properties">
											<p class="question_info">
												<span>{pigcms{:L_("属性名称：")}</span>
												<input type="text" class="txt properties_name" value="{pigcms{$ro['name']}" name="properties[]"/>
												<span>{pigcms{:L_("可选个数：")}</span><input type="text" class="txt properties_num" value="{pigcms{$ro['num']}" name="properties_num[]" style="width:50px"/>
												<input type="hidden" name="properties_id[]" value="{pigcms{$ro['id']}">
												<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>
											</p>
											<ul id="1" class="optionul">
												<volist name="ro['val']" id="ra">
													<li>
														<u>{pigcms{:L_("属性的属性值：")}</u>
														<input type="text" class="txt properties_val" name="properties_val[{pigcms{$ik-1}][]" value="{pigcms{$ra}"/>
														<a class="list_del" href="javascript:;" title="{pigcms{:L_('删除这个选项')}">×</a>
													</li>
												</volist>
											</ul>
											<p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  {pigcms{:L_("添加属性的属性值")}</a></p>
										</div>
									</volist>
									<p class="add_properties"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success">{pigcms{:L_("添加属性")}</a></p>
								</div>

								<div class="topic_box">
									<p><a href="javascript:;" title="{pigcms{:L_('添加')}" class="btn btn-sm btn-success add_table" <if condition="!$now_goods['spec_list']">style="display:none"</if>>{pigcms{:L_("生成规格关系")}</a></p>
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
										<tbody>
											<tr>
												<volist name="now_goods['spec_list']" id="gs">
												<th>{pigcms{$gs['name']}</th>
												</volist>
												<th>{pigcms{:L_("原价")}</th><th>{pigcms{:L_("进价")}</th><th>{pigcms{:L_("现价")}</th><th>{pigcms{:L_("限时价")}</th><th>{pigcms{:L_("每单限购")}</th><th>{pigcms{:L_("库存")}</th>
												<volist name="now_goods['properties_list']" id="gp">
												<th>{pigcms{$gp['name']}({pigcms{:L_("可选个数")})</th>
												</volist>
											</tr>

											<volist name="now_goods['list']" id="gl" key="num">
												<tr id="{pigcms{$gl['index']}">
													<volist name="gl['spec']" id="g">
													<td>{pigcms{$g['spec_val_name']}</td>
													</volist>

													<td  style="display:none"><input type="text" class="txt" name="old_prices[]" value="{pigcms{$gl['old_price']}" style="width:80px;"></td>
													<td><input type="text" class="txt" name="cost_prices[]" value="{pigcms{$gl['cost_prices']}" style="width:80px;"></td>
													<td><input type="text" class="txt" name="prices[]" value="{pigcms{$gl['price']}" style="width:80px;"></td>
													<td><input type="text" class="txt" name="seckill_prices[]" value="{pigcms{$gl['seckill_price']}" style="width:80px;"></td>
													<td><input type="text" class="txt" name="max_nums[]" value="{pigcms{$gl['max_num']}" style="width:80px;"></td>
													<td><input type="text" class="txt" name="stock_nums[]" value="{pigcms{$gl['stock_num']}" style="width:80px;"></td>

													<volist name="gl['properties']" id="gpp" key="num">
													    <td><input type="text" class="txt" name="num{pigcms{$num-1}[]" value="{pigcms{$gpp['num']}" style="width:80px;"></td>
													</volist>
												</tr>
											</volist>
										</tbody>
									</if>
									</table>

                                    <div class='topic_til'>
                                        <h4>{pigcms{:L_("附属商品区块")}</h4>
                                    </div>

                                    <div class="alert alert-info">
                                        <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                                        <p style="padding-bottom:10px">{pigcms{:L_("附属商品：例如，对于麻辣烫，附属商品为“白菜，金针菇，海带，牛肉”这样的商品即为附属商品。若您在创建的商品为附属商品，请把商品所属的分类选择为“附属商品”这个分类。")} </p>
                                        <p style="padding-bottom:10px">{pigcms{:L_("附属商品区块：当您配置的是麻辣烫这类商品，如果您需要用户在APP上从比如“荤菜”和“素菜”，中选择具体的麻辣烫。您可以在此创建对应的附属商品区块“荤菜”，以及附属商品区块“素菜”，然后添加具体的商品或附属商品到该区块中。")} </p>
                                        <p style="padding-bottom:10px">{pigcms{:L_("附属商品组：在设置附属商品区块时，您可以选择已经配置好的附属商品组。这样可以直接将附属商品组里的商品，添加到附属商品区块中。")} </p>
                                    </div>

								</div>

                                <div class="topic_box">
                                    <div class='fushuWrap'></div>
                                    <p class="addFushuBlock" style="margin-top:20px"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-big btn-success"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>{pigcms{:L_("附属商品区块")}</a></p>
                                </div>

							</div>

							<!--<div id="seckill" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品限时价")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_price" id="seckill_price" type="text" value="{pigcms{$now_goods.seckill_price|default=0}"/>
									<span class="form_tips">0{pigcms{:L_("表示无限时价。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价库存")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_stock" id="seckill_stock" type="text" value="{pigcms{$now_goods.seckill_stock}"/>
									<span class="form_tips">-1{pigcms{:L_("表示无限量。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价类型")}</label></label>
									<span><label><input id='seckill_type0' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("固定时间段")}</span>&nbsp;</label></span>
									<span><label><input id='seckill_type1' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("每天的时间段")}</span></label></span>
								</div>

								<div class="form-group datetime" <if condition="$now_goods['seckill_type'] eq 1 ">style="display:none"</if>>
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时段")}</label></label>
									<div>
										<input id="goods_seckill_open_datetime" type="text" <if condition="$now_goods['seckill_open_time']">value="{pigcms{$now_goods['seckill_open_time']|date='Y-m-d H:i',###}"<else/>value=''</if> name="seckill_open_datetime" readonly/>	{pigcms{:L_("至")}
										<input id="goods_seckill_close_datetime" type="text" <if condition="$now_goods['seckill_close_time']">value="{pigcms{$now_goods['seckill_close_time']|date='Y-m-d H:i',###}"<else/>value=''</if> name="seckill_close_datetime" readonly/>
									</div>
								</div>
                                
                                <div class="form-group time" <if condition="$now_goods['seckill_type'] eq 0 ">style="display:none"</if>>
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("限时段")}</label></label>
                                    <div>
                                        <input id="goods_seckill_open_time" type="text" <if condition="$now_goods['seckill_open_time']">value="{pigcms{$now_goods['seckill_open_time']|date='H:i',###}"<else/>value=''</if> name="seckill_open_time" readonly style="width:70px"/>   至
                                        <input id="goods_seckill_close_time" type="text" <if condition="$now_goods['seckill_close_time']">value="{pigcms{$now_goods['seckill_close_time']|date='H:i',###}"<else/>value=''</if> name="seckill_close_time" readonly style="width:70px"/>
                                    </div>
                                </div>
							</div>-->
							<div id="category" class="tab-pane">
								<div class="alert alert-info" style="margin:10px 0;">
									<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
									{pigcms{:L_("运费模版：用户在选购该商品的时候，不同的区域有不同的运费。")}
									<br/><br/>
									{pigcms{:L_("其他区域运费：指的是用户选择的配送区域不在运费模板的区域内的其他城市的运费！（如果不选择运费模板的话，那么该商品的运费就是这个地方设置的值）")}
									<br/><br/>
									{pigcms{:L_("运费计算方式：1、按最大值算：就是用户同时买了该店铺的多个商品，则本次购物的运费只收取商品运费最高的那个。")}
									<br/><br/>
									　　　　　　　2{pigcms{:L_("、单独计算：指的是用户在购买该商品的时候运费单独另外收取，不与其他商品合并计算运费。")}
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("运费模版")}</label></label>
									<select name="freight_template" id="freight_template">
										<option value="0" <if condition="0 eq $now_goods['freight_template']">selected</if>>{pigcms{:L_("请选择运费模板")}...</option>
										<volist name="express_template" id="express">
										<if condition="$express['id'] eq intval($now_goods['freight_template'])">
										<option value="{pigcms{$express['id']}" selected>{pigcms{$express['name']}</option>
										<else />
										<option value="{pigcms{$express['id']}">{pigcms{$express['name']}</option>
										</if>
										</volist>
									</select>
									<a href="{pigcms{:U('Express/add')}">+{pigcms{:L_("新建")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("其他区域运费")}</label></label>
									<div><input name="freight_value" type="text" value="{pigcms{$now_goods.freight_value|floatval}" /></div>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("运费计算方式")}</label></label>
									<span><label><input name="freight_type" <if condition="$now_goods['freight_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("按最大值算")}</span>&nbsp;</label></span>
									<span><label><input name="freight_type" <if condition="$now_goods['freight_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("单独计算")}</span></label></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商城商品分类")}</label><label>
									<fieldset id="choose_category" cat_fid="0" cat_id="0"></fieldset>
								</div>
								<!-- {pigcms{:L_("增加商品归属限制")} -->	
								<div class="form-group" id="mall_box">
									<label class="col-sm-1"><label>{pigcms{:L_("该商品是否只属于商城")}</label></label>
									<span>
										<label><input name="goods_type" <if condition="$now_goods['goods_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label>
									</span>
									<span>
										<label><input name="goods_type" <if condition="$now_goods['goods_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("是")}</span></label>
									</span>
									<span class="form_tips">{pigcms{:L_("选择是，则该商品只会在商城中显示不会在X1中显示，且该商品的所有的订单也只属于商城。",['X1'=>$config['shop_alias_name']])}</span>
								</div>
<!-- 								<volist name="category_list" id="vo"> -->
<!-- 									<div class="form-group"> -->
<!-- 										<div class="radio"> -->
<!-- 											<label> -->
												<span class="lbl"><label style="color: red">{pigcms{$vo.cat_name}：</label></span>
<!-- 											</label> -->
<!-- 											<volist name="vo['son_list']" id="child"> -->
<!-- 												<label> -->
<!-- 													<input class="cat_class" type="checkbox" name="store_category[]" value="{pigcms{$vo.cat_id}-{pigcms{$child.cat_id}" id="Config_store_category_{pigcms{$child.cat_id}" <if condition="in_array($child['cat_id'],$relation_array)">checked="checked"</if>/> -->
<!-- 													<span class="lbl"><label for="Config_store_category_{pigcms{$child.cat_id}">{pigcms{$child.cat_name}</label></span> -->
<!-- 												</label> -->
<!-- 											</volist> -->
<!-- 										</div> -->
<!-- 									</div> -->
<!-- 								</volist> -->
							</div>
								<div id="description" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("辣度")}：</label></label>
										<div>
											<volist name="spicy_arr" id="vo" key="k">
												<label style="margin-left: 4px;"><input name="spicy" type="radio" value="{pigcms{$k}" <if condition="$now_goods.spicy eq $k">checked</if>/>{pigcms{$vo}</label>
												
											</volist>
										</div>
									</div>
									<div class="form-group recharge">
										<label class="col-sm-1"><label>{pigcms{:L_("口味：")}</label></label>
										<span class="plus_re">
											<input type="text" name="flavor[]"  class="px" value=""/>
											<button type="button" class="plus_recharge"></button>
											<button type="button" class="reduce_recharge"></button>
											<span class="tip"></span>
										</span>
									</div>
									<div class="form-group recharge">
										<label class="col-sm-1"><label>{pigcms{:L_("原料：")}</label></label>
										<span class="plus_re">
											<input type="text" name="material[]"  class="px" value=""/>
											<button type="button" class="plus_recharge"></button>
											<button type="button" class="reduce_recharge"></button>
											<span class="tip"></span>
										</span>
									</div>
									<div class="form-group recharge">
										<label class="col-sm-1"><label>{pigcms{:L_("特殊食材：")}</label></label>
										<span class="plus_re">
											<input type="text" name="special_food_materials[]"  class="px" value=""/>
											<button type="button" class="plus_recharge"></button>
											<button type="button" class="reduce_recharge"></button>
											<span class="tip"></span>
										</span>
									</div>
									<div class="form-group recharge">
										<label class="col-sm-1"><label>{pigcms{:L_("烹饪方式：")}</label></label>
										<span class="plus_re">
											<input type="text" name="cooking_methods[]"  class="px" value=""/>
											<button type="button" class="plus_recharge"></button>
											<button type="button" class="reduce_recharge"></button>
											<span class="tip"></span>
										</span>
									</div>
								</div>

								<div id="combination" class="tab-pane">
	                                <div class='topic_til'>
	                                    <h4>{pigcms{:L_("组合套餐")}<span style="color: red;font-size: 12px;">（{pigcms{:L_("附属菜的主菜商品不可设置为组合套餐")}）</span></h4>
	                                </div>
	                                <div class="alert alert-info">
	                                    <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
		                                <p style="padding-bottom:10px">{pigcms{:L_("可将当前商品与其他商品组合成多人组合套餐，有助于提高销量与转化。套餐价格以实际商品价格相加为准，对于有规格属性的套餐商品，将默认选中第一个规格和属性，一个组合套餐最多只可绑定3个商品;套餐中有任何一个商品当前库存为0则该套餐将不在前端显示")} </p>
		                                <p style="padding-bottom:10px">{pigcms{:L_("以中式快餐为例，最常见的多人组合套餐是：主打菜+米饭+汤类")} </p>
	                                </div>
	                                <if condition="!$now_goods['is_subsidiary_goods']">
									<div class="topic_box">
	                                    <div class='comWrap'></div>
										<p class="addCombination" style="margin-top:20px"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-big btn-success"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>{pigcms{:L_("组合套餐")}</a></p>
									</div>
									</if>

								</div>

                                <div id="employee_card" class="tab-pane">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label>{pigcms{:L_("是否开启员工专卖商品")}</label></label>
                                        <span><label><input name="open_employee_card" checked="checked" value="0" type="radio">&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label></span>
                                        <span><label><input name="open_employee_card" value="1" type="radio" >&nbsp;<span>{pigcms{:L_("是")}</span></label></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label>{pigcms{:L_("选择商家")}</label></label>
                                        <select name="employee_mer_id">
                                            <option value="0">---请选择---</option>
                                            <volist name="mer_list" id="mer_name" key="mer_key">
                                                <option value="{pigcms{$mer_name.mer_id}">{pigcms{$mer_name.name}</option>
                                            </volist>
                                        </select>
                                    </div>
                                    <div class="form-group" id="employee_lables">
                                        <label class="col-sm-2"><label>{pigcms{:L_("选择员工身份标签")}</label></label>
                                    </div>
                                </div>
                                <div id="merScoreRate" class="tab-pane">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label>{pigcms{:L_("商家积分抵扣最大比例")}</label></label>
                                        <div>
                                            <input name="mer_score_rate" id="mer_score_rate" type="number" min="0" max="100" value="{pigcms{$now_goods.mer_score_rate|default='0'}" style="width: 100px;margin-right: 10px;" /><b style="color:red"> {pigcms{:L_("请填写0-100的数字，如")}10</b>
                                        </div>
                                    </div>
                                </div>

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
table .reg_file {
    display: inline-block;
    background: #0099cc;
    color: #fff;
    position: relative;
}
table .reg_img {
    max-height: 60px;
    vertical-align: bottom;
    margin-right: 10px;
}
img {
    border: 0;
}
.ke-dialog {
    position: absolute!important;
    top: 30%!important;
    left: 30%!important;
    margin: 0!important;
    padding: 0!important;
}
.aui_main {
    height: 450px!important;
}
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js?t=1"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script>
var uploaderHas = false;
var uploaderHas2 = false;
var swf='{pigcms{$static_public}js/Uploader.swf';
var server="{pigcms{:U('Mall/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
var diyVideo = "{pigcms{:U('Article/diyVideo')}";

var score_name = "{pigcms{$config['score_name']}";
	var open_price_type = "{pigcms{$open_price_type}";

var json = '{pigcms{$now_goods['json']}', sortList = '{pigcms{$sort_list}', selectIds = '{pigcms{$select_ids}';
var category_list = '{pigcms{$category_list}', ajax_goods_properties = "{pigcms{:U('Shop/ajax_goods_properties')}";
var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';
var video_name="shop_video_url";


//解析淘宝地址
var parse_taobao_url = '{pigcms{:U("Taobao/parseGoods")}';

var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=shop/content&session_id={pigcms{:session_id()}";

var cssPath = "{pigcms{$static_path}css/group_editor.css";
var upload_image = "{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
var store_id = '{pigcms{$now_store.store_id}';

var no_spec = false;
$('#myTab li a').click(function(){
    if(no_spec==true && ($(this).attr('href') == '#txtattr' || $(this).attr('href') == '#seckill')){
        alert('{pigcms{:L_("计重商品暂不支持配置此项！")}');
        return false;
    }
	if(uploaderHas2 == false && $(this).attr('href') == '#txtattr'){
		add_file_upload();
		uploaderHas2 = true;
	}
});

$('select[name=employee_mer_id]').change(function(){
    var mer_id = $(this).val();
    $.post("{pigcms{:U('Shop/get_lable_employee_list')}",{mer_id: mer_id},function(res){
        $("#employee_lables").html(res);
    });
});
var uploader = ''
setTimeout(function(){
	uploader = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}",
			pick: {
				id:'#J_selectImage',
                // 多图上传
				multiple:true
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
			},
            // 图片上传数量限制
            fileNumLimit: 5,
            // 图片重复上传
            duplicate: true
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
			$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+response.url+'"/><input type="hidden" name="pic[]" value="'+response.title+'"/><br/><a href="#"  data-id="' + file.id +'" onclick="deleteImage(\''+response.title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
		}else{
			alert(response.message);
		}
	});

	uploader.on('uploadError', function(file,reason){
		$('.loading'+file.id).remove();
		alert('{pigcms{:L_("上传失败！请重试。")}');
	});

    uploader.on("error", function (type) {
        if(type == 'Q_EXCEED_NUM_LIMIT') {
            alert('{pigcms{:L_("最多上传5个图片！")}');
            return false
        }
    });
},20);
uploaderHas = true;


var formathtml = new Array();
var format_value = new Array();
function deleteImage(path,obj,type){
	type=type || 1;
	$.post("{pigcms{:U('Mall/ajax_del_pic')}",{path:path},function(res){

	});
	if(type==1){
		$(obj).closest('.upload_pic_li').remove();
        if($(obj).data('id') && uploader) {
            uploader.removeFile( $(obj).data('id'),true );
        }
	}else if(type==2){
		var obj2=$(obj).parents('td');
		obj2.find('input[type=hidden]').val('');
		obj2.find('.reg_file').show();
		add_upload_obj(obj2.find('.reg_file'));
		set_reg_info(obj2.find('input[type=hidden]'),'');
		obj2.find('p').remove();
		return false;
	}	
}

window.sessionStorage.setItem(session_index, json);
$(document).ready(function(){
	$('#freight_value1').change(function(){
		if ($(this).val() != 0) {
			$('input[name=freight_type][value=1]').attr("checked",'checked');
		} else {
			$('input[name=freight_type][value=0]').attr("checked",'checked');
		}
	});

	$('#sysname').keypress(function(e){
		
		if(e.keyCode==13){
			e.preventDefault();
		
			$.get('?g=Merchant&c=Shop&a=ajax_goods', {'number':$(this).val()}, function(response){
				if (response.error) {
					alert(response.msg);
				} else {
					$('#name').val(response.data.name);
					$('#number, #sysname').val(response.data.number);
					$('#unit').val(response.data.unit);
					$('#price').val(response.data.price);
					$('#cost_price').val(response.data.cost_price);
					$('#upload_pic_ul').html('<li class="upload_pic_li"><img src="' + response.data.image + '"/><input type="hidden" name="pic[]" value="' + response.data.imageTitle + '"/><br/><a href="#" onclick="deleteImage(\'' + response.data.imageTitle + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
				}
			
			}, 'json');
		
			e.stopPropagation();
			return false;  
		
		}
		
	});

	// "单位不能填写数字")}
    $('#unit').change(function () {
        var val = $(this).val();
        console.log('val', val);
        var reg = /^[0-9]*$/;
        if (reg.test(val)) {
            $(this).val('');
            alert("{pigcms{:L_("请填写非数字的单位！")}");
            return false;
        }
    })

});

function searchErpGoods(number)
{
    $.get('?g=Merchant&c=Shop&a=searchErpGoods', {'g_m_id': number, 'store_id': store_id}, function(response){
        if (response.error) {
            alert(response.error_msg);
        } else {
            $('#name').val(response.retval.name);
            $('#number').val(response.retval.number);
            $('#unit').val(response.retval.unit);
            $('#price').val(response.retval.price);
            $('#cost_price').val(response.retval.cost_price);
            $('#stock_num').val(response.retval.stock_num);
            $('#original_stock').val(response.retval.stock_num);
            $('#miaomaiyun_g_m_id').val(number);
        }
    }, 'json');
}

function searchGoods(number)
{
	$.get('?g=Merchant&c=Shop&a=ajax_goods', {'goods_id':number}, function(response){
		if (response.error) {
			alert(response.msg);
		} else {
            $('#miaomaiyun_g_m_id').val(0);
			$('#name').val(response.data.name);
			$('#number, #sysname').val(response.data.number);
			$('#unit').val(response.data.unit);
			$('#price').val(response.data.price);
			$('#cost_price').val(response.data.cost_price);
			$('#upload_pic_ul').html('<li class="upload_pic_li"><img src="' + response.data.image + '"/><input type="hidden" name="pic[]" value="' + response.data.imageTitle + '"/><br/><a href="#" onclick="deleteImage(\'' + response.data.imageTitle + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
		}
	}, 'json');
}
function addLink(){
	art.dialog.data('fun', searchGoods);
	art.dialog.open('?g=Merchant&c=Shop&a=sys_goods',{lock:true,title:'{pigcms{:L_("商品库商品',width:800,height:550,top:40,yesText:'关闭")}',background: '#000',opacity: 0.45});
}

function chooseFromMiaomaiyunErp(store_id){
    art.dialog.data('erpFun', searchErpGoods);
    art.dialog.open('?g=Merchant&c=Shop&a=miaomaiyunErpGoodsLists&store_id=' + store_id,{lock:true,title:'{pigcms{:L_("商品库商品',width:800,height:550,top:40,yesText:'关闭")}',background: '#000',opacity: 0.45});
}

$('#name').blur(function() {
    this_ = $(this);
    var length = this_.val().length;
    if (length > 12) {
        $('#name_limit').show();
    }else{
        $('#name_limit').hide();
    }
});
//"商品描述")}
$('#describe').blur(function() {
    this_ = $(this);
    var length = this_.val().length;
    var size=this_.attr('size');
    if (length > size) {
        $('#describe_limit').show();
    }else{
        $('#describe_limit').hide();
    }
});
$('.tab-content').on('blur','.spec_name,.spec_val,.properties_name,.properties_val',function(){
    this_ = $(this);
    var length = this_.val().length;
    if (length > 12) {
        $('#spec_limit').show();
    }else{
        $('#spec_limit').hide();
    }
});
$('#sell_type').change(function(){
    if ($(this).val()==2) {
        $('#number_tips').html('{pigcms{:L_("请输入5位数字，必填。")}');
        $('.weight').show();
        $('.num_stock').hide();
        no_spec = true;
        $('#price').attr('readonly','readonly');
    } else {
        $('#number_tips').html('{pigcms{:L_("选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。")}');
        $('.weight').hide();
        $('.num_stock').show();
        no_spec = false;
        $('#price').removeAttr('readonly');
    }
});
$('#unit_price,#weight').keyup(function(){
    var sell_type=Number($('#sell_type').val());
    if(sell_type==1){
    	return false;
    }
    var unit_price = parseFloat($('#unit_price').val());
    var weight = parseFloat($('#weight').val());
    var weight_unit = parseFloat($('#weight_unit').val());
    if(unit_price && weight){
        if(weight_unit==2){
            weight = weight * 1000;
        }
        var now_price = parseFloat((unit_price / 500) * weight).toFixed(2);
    }
    $('#price').val(now_price);
});
$('#weight_unit').change(function(){
    $('#unit_price,#weight').trigger("keyup");
    var val=$(this).val();
    if(val==1){
    	weight_unit='g';
    }else{
    	weight_unit='kg';
    }
});

var swf='{pigcms{$static_public}js/Uploader.swf';
var server="{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";

    $(".addFushuBlock .btn").live('click', function () {
        var i = $('.fushuBox').size();
        var t = '';
        t += '<div class="fushuBox"> '
        t += '	<div class="fushu">'
        t += '		<span>区块名称'
        t += '			 <input type="text" name="name_piece[]"  />'
        t += '		</span>'
        t += '		<span>选择附属商品组：'
        t += '			<select class="selectOption" data-id="'+i+'" onchange="changeOption('+i+')">'
        t += '             <option selected="selected">未选择<option>'
        t += '			</select>'
        t += '		</span>'
        t += '		<span>设置区块总的最少购买数和最大购买数：<input type="text" name="mininum_piece[]" /> ~ <input type="text" name="maxnum_piece[]" />'
        t += '		<a href="javascript:;" class="box_del">删除</a>'
        t += '		<label style="margin-left:10px; color:#999">*最少设置0，即用户可以不选</label>'
        t += '		</span>  '
        t += '	</div> '
        t += '	<table class="table table-striped table-bordered FuShuTbody"  style="width:800px; margin-top:20px;">'
        t += '		<tbody class="tbody_til">'
        t += '				<tr>'
        t += '					<th>附属商品</th>'
        t += '					<th>售价</th>'
        t += '					<th>最少可选数</th>'
        t += '					<th>最多可选数</th>'
        t += '					<th>排序</th>'
        t += '				</tr>'
        t += '		</tbody>'
        t += '		<tbody class="tbodyFushu"> '
        t += '		</tbody>'
        t += '	</table> '
        t += '	<p><a href="javascript:;" data-savegood="'+i+'" class="btn btn-sm btn-success add_tr"> 添加附属商品</a></p> '
        t += '</div> '
        $('.fushuWrap').append(t);
        selectOption();
    });

    $('.tr_del').live('click', function () {
        $(this).parents('tr').remove();
    });

    $('.tr_del').live('click',function(){
        $(this).parents('tr').remove();
    })

    $(".fushu .box_del").live('click',function(){
        $(this).parents(".fushuBox").remove();
    });

    var htmlOption=''
    var group_id='';
    function selectOption(e,thisSelect){
        var store_id = "{pigcms{$now_store['store_id']}";
        $.get("{pigcms{$this->config['site_url']}/merchant.php?g=Merchant&c=Shop&a=goods_subsidiary_group&rejson=1&store_id="+store_id, {}, function (res) {
            res=JSON.parse(res);
            htmlOption='<option value="-1">未选择</option>'
            if(res.data!=null){
                group_id=res.data[0].id;
                for (var i=0;i<res.data.length;i++){
                    htmlOption = htmlOption + "<option value='"+res.data[i].name+"' data-id='"+res.data[i].id+"'>"+res.data[i].name+"</option>" ;
                    if(thisSelect==res.data[i].name){
                        group_id=res.data[i].id;
                    }
                }
                $(".selectOption").html(htmlOption);
                var htmlOptionLast = htmlOption.replace('<option value="-1">未选择</option>','');
                $(".selectOption[data-id="+e+"]").html(htmlOptionLast);
                $(".selectOption[data-id="+e+"] option[value='" + thisSelect + "']").attr("selected", "selected");
            }

        });
    }

        function selectGroup(e,selectObj){
            group_id=selectObj;
            $.get("{pigcms{$this->config['site_url']}/merchant.php?g=Merchant&c=Shop&a=subsidiary_goods_list&group_id="+group_id+"&rejson=1", {}, function (res) {
                res=JSON.parse(res);
                arrayNun=res.data;
                var html=''
                if(arrayNun!=null){
                    arrayNun.forEach(function(value,i,array){
                        if(value.spec_value){
                            var inputstr = '<input type="text" class="txt" name="pg_price['+e+'][]" value="多规格" style="width:52px;" disabled="true">';
                        }else{
                            var inputstr = '<input type="text" class="txt" name="pg_price['+e+'][]" value="'+value.price+'" style="width:80px;">';
                        }
                        html+='<tr><td>'+value.name+'</td><td>'+inputstr+'<input type="hidden" name="pg_goods_id['+e+'][]" value="'+value.goods_id+'"><input type="hidden" name="pg_goods_name['+e+'][]"  value="'+value.name+'" ></td><td><input type="text" class="txt" name="pg_mininum['+e+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_maxnum['+e+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_sort['+e+'][]"  value="0" style="width:80px;">  <a href="javascript:;" class="tr_del">删除</a></td></tr>'
                    })
                }
                $(".selectOption[data-id="+e+"] option[data-id="+selectObj+"]").parents('.fushuBox').find('.tbodyFushu').html(html);
            });
        }

        function changeOption(e){
            if($(".selectOption[data-id="+e+"] option").val()=='-1'){
                $(".selectOption[data-id="+e+"] option[value='-1']").remove();
            }
            options=$(".selectOption[data-id="+e+"] option:selected").val();
            selectOption(e,options);
            var selectObj=$(".selectOption[data-id="+e+"] option:selected").data('id')
            selectGroup(e,selectObj);
        }

        // "关联商品")}
        $('.fushuBox .add_tr').live('click',function(is_edit){
            var savegood=$(this).attr('data-savegood');
            layer.open({
                title: false,
                closeBtn: 0,
                type: 2,
                content: '{pigcms{$this->config['
                site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_piece_list&from=shop_goods_edit&store_id={pigcms{$now_store['store_id']}&savegood='+savegood+'',
                area: ['650px', '576px'],
                shade: 0.2,
                cancel: function () {
                },
                move: false
            });
        })

        function login_box_after(name,price,spec_value,index,savegood,goods_id){
	        //菜品不能重复
	        var pg_goods_id= $("input[name='pg_goods_id["+savegood+"][]'").val();
	        var is_repeat=false;
	        $.each($("input[name='pg_goods_id["+savegood+"][]'"),function (e) {
	        	if ($(this).val()==goods_id) {
	        		is_repeat = true;
	        	}
	        })
	        if (is_repeat) {
	        	layer.alert('不能添加重复商品');
	        	return false;
	        }

            var html='<tr><td>'+name+'</td>'
            if(spec_value == ''){
                html+='<td><input type="text" class="txt" name="pg_price['+savegood+'][]" value="'+price+'" style="width:80px;"></td>'
            }else{
                html+='<td><input type="text" class="txt" name="pg_price['+savegood+'][]" value="多规格" style="width:52px;" disabled="true"></td>'
            }
            html+='<td><input type="text" class="txt" name="pg_mininum['+savegood+'][]"  value="0" style="width:80px;"><input type="hidden" name="pg_goods_id['+savegood+'][]"  value="'+goods_id+'" ><input type="hidden" name="pg_goods_name['+savegood+'][]"  value="'+name+'" ></td>'
            html+='<td><input type="text" class="txt" name="pg_maxnum['+savegood+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_sort['+savegood+'][]"  value="0" style="width:80px;">  <a href="javascript:;" class="tr_del">删除</a></td></tr>'
            var numSavegood=parseInt(savegood)+1;
            // $(".fushuWrap .fushuBox:nth-child("+savegood+1+")").find('.FuShuTbody .tbodyFushu').append(html);
            $(".fushuWrap .fushuBox:nth-child("+numSavegood+")").find('.FuShuTbody .tbodyFushu').append(html);
        }

    if($('.plus').length<2){
		$('.delete').children().hide();
	}
	$.each($('.recharge'),function(){
		if($(this).find('.plus_re').length<2){
			
			$(this).find('.plus_re:first').find('.reduce_recharge').css('visibility','hidden');
		}
	})
	
	$('.plus_recharge').click(function(){
		var item = $(this).parents('.form-group').find('.plus_re:last');
		var newitem = $(item).clone(true);
		
		$(this).css('visibility','hidden');
		$(this).siblings('.reduce_recharge').css('visibility','visible');
		
		newitem.find('.tip').css('visibility','hidden');
		
		$(item).after(newitem);
		newitem.find('input').attr('value','');
			
		
		
	})
	$(".reduce_recharge").click(function() {
		if($(this).parents('.form-group').find('.plus_re').length<=1){
			$('.plus_re input').val('');
			$('.plus_re:first').find('.tip').css('visibility','visible');
			
		}else{
			
			$(this).parents('.plus_re').remove();
			$('.plus_re:last').find('.reduce_recharge').css('visibility','hidden');
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
	// 添加组合套餐
	$(".addCombination .btn").live('click', function () {
        var i = $('.comBox').size();
        var t = '';
        t += '<div class="comBox"> '
        t += '	<div class="comContent">'
        t += '		<span>{pigcms{:L_("套餐名称")}：'
        t += '			 <input type="text" name="name_com[]"  /><input type="hidden" name="com_ids[]" value="0" />'
        t += '		</span>'
        t += '		<span>{pigcms{:L_("组合套餐人数")}：<input type="text" name="num_com[]" /> '
        t += '		<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>'
        t += '		</span>  '
        t += '	</div> '
        t += '	<table class="table table-striped table-bordered comTbody"  style="width:800px; margin-top:20px;">'
        t += '		<tbody class="tbody_til">'
        t += '				<tr>'
        t += '					<th>{pigcms{:L_("商品")}</th>'
        t += '					<th>{pigcms{:L_("价格")}</th>'
        t += '					<th>{pigcms{:L_("当前库存")}</th>'
        t += '				</tr>'
        t += '		</tbody>'
        t += '		<tbody class="tbodyCom"> '
        t += '		</tbody>'
        t += '	</table> '
        t += '	<p><a href="javascript:;" data-savegood="'+i+'" class="btn btn-sm btn-success add_tr">{pigcms{:L_("添加套餐商品")}</a></p> '
        t += '</div> '
        $('.comWrap').append(t);
    });

     // 打开组合套餐添加商品弹层
    $('.comBox .add_tr').live('click',function(is_edit){
        var savegood=$(this).attr('data-savegood');
        parent.layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: '{pigcms{$this->config['
            site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_piece_list&type=com&store_id={pigcms{$now_store.store_id}&savegood='+savegood+'',
            area: ['650px', '576px'],
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    // 组合套餐选择商品后回调方法
    function com_login_box_after(name,price,spec_value,index,savegood,goods_id,stock_num){
        //菜品不能重复
        var com_goods_id= $("input[name='com_goods_id["+savegood+"][]'").val();
        var is_repeat=false;
        $.each($("input[name='com_goods_id["+savegood+"][]'"),function (e) {
        	if ($(this).val()==goods_id) {
        		is_repeat = true;
        	}
        })
        if (is_repeat) {
        	layer.alert('{pigcms{:L_("不能添加重复商品")}');
        	return false;
        }

        if ($("input[name='com_goods_id["+savegood+"][]'").length>1) {
        	layer.alert('{pigcms{:L_("最多添加2个商品")}');
        	return false;
        }

        var html='<tr><td>'+name+'<input type="hidden" name="com_goods_id['+savegood+'][]"  value="'+goods_id+'" ></td>'
      
        html+='<td>'+price+'</td>'
       
        html+='<td>'+stock_num+'</td><td>  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
        var numSavegood=parseInt(savegood)+1;
        $(".comWrap .comBox:nth-child("+numSavegood+")").find('.comTbody .tbodyCom').append(html);
    }

    // 删除组合套餐
    $(".comContent .box_del").live('click',function(){
        $(this).parents(".comBox").remove();
    });
</script> 
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js?t=1231"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/pase_taobao.js"></script>
<include file="Public:footer"/>
<style>
	.add_table{
		width:90px;
	}
	.add_properties{
		width:90px;
	}
	
	.add_spec{
		width:90px;
	}
    .alert-info {
        background-color: #f3f8f2;
        border-color: #f3f8f2;
        color: #999;
    }

    .topic_til {
        background: #f0f0f0;
        padding: 10px;
        position: relative;
    }

    .topic_til:before {
        content: '';
        width: 8px;
        height: 8px;
        background: #87b87f;
        border-radius: 50%;
        display: block;
        position: absolute;
        left: 15px;
        top: 25px;
    }

    .topic_til h4 {
        padding: 0 20px;
    }

    .question_box ul a.list_del {
        background: #f00;
        color: #fff !important;
        border-radius: 100%;
        width: 18px;
        height: 18px;
        text-align: center;
        line-height: 18px;
    }

    .box_del, .tr_del {
        padding: 5px 10px;
        background: #f00;
        color: #fff;
        border-radius: 15px;
    }

    a.box_del, .tr_del:hover {
        color: #fff
    }

    .btn {
        border-radius: 15px;
    }

    .add_spec, .add_table {
        
    }

    .fushu span {
        padding-right: 20px;
    }

    .fushu span input {
        width: 80px;
    }

    .btn-success.btn_guige, .btn-success.btn_guige:focus {
        background-color: #0099cc !important;
        border-color: #0099cc;
    }

    /*.tbody_til {*/
        /*display: none*/
    /*}*/

    .fushuBox {
        margin: 20px 0;
    }
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
	}
	.plus_re{
		margin-right: 50px;
	}
</style>
