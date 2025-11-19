<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_list',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{$now_sort.sort_name}</a></li>
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
				/* {pigcms{:L_("商品标签")} */
				#labelSet .label_box{overflow:auto}
				#labelSet .label_box p{display:flex;margin:0 0 10px 0;align-items:center}
				#labelSet .label_box p .btn2{position:relative;display:inline-block;box-sizing:border-box;margin:0 5px;width:20px;height:20px;border:1px solid #ccc;border-radius:10px;cursor:pointer}
				#labelSet .label_box p .btn2:after,#labelSet .label_box p .btn2:before{position:absolute;top:8px;left:4px;display:inline-block;width:10px;height:2px;background-color:#ccc;content:''}
				#labelSet .label_box p .add_btn:after{top:4px;left:8px;width:2px;height:10px}
				#labelSet .label_box p .add_btn{display:none}
				#labelSet .label_box p:first-child .add_btn{display:block}
				#labelSet .label_box p:first-child .reduce_btn{display:none}
				label>font{vertical-align: middle;margin-top: 2px;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<if condition="$now_store['store_theme'] AND $category_list">
							<li>
								<a data-toggle="tab" href="#category">{pigcms{:L_("商城属性设置")}</a>
							</li>
							</if>
							<li>
								<a data-toggle="tab" href="#txtintro">{pigcms{:L_("商品详情")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtattr">{pigcms{:L_("商品规格")}</a>
							</li>
							<!-- <li>
								<a data-toggle="tab" href="#seckill">{pigcms{:L_("限时优惠")}</a>
							</li> -->
							<if condition="!empty($_SESSION['system'])">
							<li>
								<a data-toggle="tab" href="#score">{pigcms{$config.score_name}{pigcms{:L_("设置")}</a>
							</li>
							</if>
							<li>
								<a data-toggle="tab" href="#labelSet">{pigcms{:L_("标签设置")}</a>
							</li>
                            <li>
                                <a data-toggle="tab" href="#areaSet">{pigcms{:L_("选择区域")}</a>
                            </li>

						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<!--<input type="hidden" value="0" id="goods_id" />-->
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
								</div>




								<div class="form-group">
									<label class="col-sm-1" for="sort_id_1"><label>{pigcms{:L_("商品分类")}<font color="red">*</font></label></label>
									<fieldset id="choose_sort"></fieldset>
								</div>
								<div class="form-group">
                                    <input type="hidden" name="use_area" value="0">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}<font color="red">*</font></label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$now_goods.name}"/>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
                                    <span id="name_limit" style="display: none;color: red;">{pigcms{:L_("商品名称建议30字以内展示效果更佳！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="describe">{pigcms{:L_("商品副标题")}</label></label>
									<textarea id="describe" class="col-sm-2" name="describe" size='30' style="resize:vertical;" placeholder='{pigcms{:L_("建议30字以内！")}' rows="5">{pigcms{$now_goods.describe}</textarea>
									<span class="form_tips"></span>
                                    <span id="describe_limit" style="display: none;color: red;">{pigcms{:L_("商品副标题请不要超过30个字！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("上传图片")}<font color="red">*</font></label></label>
									<div style="display:inline-block;" id="J_selectImage">
										<div class="btn btn-sm btn-success" style="position:relative;">{pigcms{:L_("选择本地图片")}</div>
									</div>
									<span class="form_tips">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！同一张图片不能多次选择。【图片尺寸建议为：大于等于600*600px的正方形】")}</span>
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
									<input type="hidden" name="video_url" value="{pigcms{$now_goods.video_url}"/>
								</div>




								<div class="video_box">
									<div id="video_div" class="box" <empty name="now_goods.video_url">style='display:none;'</empty>>
										<video src="{pigcms{$now_goods.video_url}" controls="controls">
											{pigcms{:L_("您的浏览器不支持 video 标签。")}
										</video>
										<a href="{pigcms{:U('Mall/del_video',array('goods_id'=>$now_goods['goods_id']))}" class="del_video">[{pigcms{:L_("删除")}]</a>
									</div>
									<div id="theList"></div>	
								</div>
								<if condition="$config.is_open_shop_weigh eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="sell_type">{pigcms{:L_("计价方式")}</label></label>
                                        <select name="sell_type" id="sell_type">
                                            <option value="1" <if condition="$now_goods['name'] eq 1">selected</if>>{pigcms{:L_("计件")}</option>
                                            <option value="2" <if condition="$now_goods['name'] eq 2">selected</if>>{pigcms{:L_("计重")}</option>
                                        </select>
                                        <span class="form_tips">{pigcms{:L_("称重商品请选择计重模式")}</span>
                                    </div>
                                </if>
								<!-- <div class="form-group">
									<label class="col-sm-1"><label for="price_range_low">{pigcms{:L_("商品最低价格")}</label></label>
									<input class="col-sm-1" size="20" name="price_range_low" id="price_range_low" type="text" value=""/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price_range_height">{pigcms{:L_("商品最高价格")}</label></label>
									<input class="col-sm-1" size="20" name="price_range_height" id="price_range_height" type="text" value=""/>
								</div> -->
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品价格")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="price" id="price" type="text" value=""/>
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
                                    <input class="col-sm-1" size="20" name="goods_weight" id="weight" type="text" value="{pigcms{$now_goods.weight}"/>
                                    <select name="weight_unit" id="weight_unit" style="    margin-left: 10px;">
                                        <option value="1" selected>g</option>
                                        <option value="2">kg</option>
                                    </select>
                                    <span class="form_tips weight" style="display: none;">{pigcms{:L_("必填。")}</span>
                                </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1"><label for="cost_price">{pigcms{:L_("商品进价")}</label></label>
									<input class="col-sm-1" size="20" name="cost_price" id="cost_price" type="text" value="{pigcms{$now_goods.cost_price}"/>
									<span class="form_tips">{pigcms{:L_("进货价用户是看不到")}</span>
								</div>
                                <div class="form-group weight" style="display: none;">
                                    <label class="col-sm-1"><label for="tare_weight">{pigcms{:L_("商品皮重")}</label></label>
                                    <input class="col-sm-1" size="20" name="tare_weight" id="tare_weight" type="text" value="{pigcms{$now_goods.tare_weight}"/>
                                    <span class="form_tips">{pigcms{:L_("单位")}g</span>
                                </div>
								<div class="form-group num_stock">
									<label class="col-sm-1"><label for="stock_num">{pigcms{:L_("商品库存")}</label></label>
									<div>{pigcms{:L_("当前库存")}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("原始库存")}</div>
									<div>
										<input style="width:70px;" name="stock_num" id="stock_num" type="text" value="{pigcms{$now_goods.stock_num|default='-1'}"/>   /
                                        <input style="width:70px;" name="original_stock" id="original_stock" type="text" value="{pigcms{$now_goods.original_stock|default='-1'}"/>
										<span class="form_tips">-1{pigcms{:L_("表示无限量。当前库存小于10时，商品详细页面会显示库存。")}</span>
									</div>
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
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="unit">{pigcms{:L_("商品单位")}<font color="red">*</font></label></label>
                                    <input class="col-sm-1" size="20" name="unit" id="unit" type="text" value="{pigcms{:L_('件')}"/>
                                    <span class="form_tips">{pigcms{:L_("必填。如个、斤、份")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="number">{pigcms{:L_("商品条形码")}</label></label>
									<input class="col-sm-1" size="20" name="number" id="number" type="text" value="{pigcms{$now_goods.number}"/>
									<span class="form_tips" id="number_tips">选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="packing_charge"><?php echo $now_store['pack_alias'] ? $now_store['pack_alias'] : L_("打包费");?></label></label>
									<input class="col-sm-1" size="20" name="packing_charge" id="packing_charge" type="text" value="{pigcms{$now_goods.packing_charge|floatval}"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort">{pigcms{:L_("商品排序")}</label></label>
                                    <input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_goods.sort|default='0'}"/>
                                    <span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="Food_status"><font color="red">*</font>{pigcms{:L_("商品状态")}</label></label>
                                    <select name="status" id="Food_status">
                                        <option value="1" selected>{pigcms{:L_("正常")}</option>
                                        <option value="0">{pigcms{:L_("停售")}</option>
                                    </select>
									<span class="form_tips" style="color: red;">{pigcms{:L_("由于部分商品存在有货但线上不售卖，所以停售只会关闭用户下单场景，店员端线下零售商品库中会展示。")}</span>
                                </div>
								<!--div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品原价")}</label></label>
									<input class="col-sm-1" size="20" name="old_price" id="old_price" type="text" value="{pigcms{$now_goods.old_price|floatval}"/>
									<span class="form_tips">{pigcms{:L_("原价可不填，不填和现价一样")}</span>
								</div-->
								
							</div>
							<div id="txtintro" class="tab-pane">
								<eq name="config.kefu_mer_id" value="$now_store['mer_id']">
									<div class="form-group">
										<label class="col-sm-1"><label for="name">关联{pigcms{$config.ecard_alias_name}</label></label>
										<a id='choose_ecard' class="btn btn-info" >选择E卡</a>
									</div>
								</eq><if condition="$config['open_mall_buy_method'] AND $config['open_score_mall']">
									<div class="form-group">
										<label class="col-sm-1"><label for="price">商品售卖形式</label></label>
										<select name="price_type" id="price_type">
											<option value="1" <if condition="$now_goods['price_type'] eq 1"> selected</if>>全现金</option>
											<option value="2" <if condition="$now_goods['price_type'] eq 2"> selected</if>>{pigcms{$config.score_name}+现金</option>
											<option value="3" <if condition="$now_goods['price_type'] eq 3"> selected</if>>全{pigcms{$config.score_name}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="account_money">所需现金</label></label>
										<input class="col-sm-1" size="20" name="account_money" id="account_money" type="text" value="{pigcms{$now_goods.account_money|floatval}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="account_score">所需{pigcms{$config.score_name}</label></label>
										<input class="col-sm-1" size="20" name="account_score" id="account_score" type="text" value="{pigcms{$now_goods.account_score|floatval}"/>
									</div>
								</if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="limit_type">{pigcms{:L_("限购类型")}</label></label>
                                    <select name="limit_type" id="limit_type">
                                        <option value="0" <if condition="$now_goods['limit_type'] eq 0">selected</if>>{pigcms{:L_("每单限购")}</option>
                                        <option value="1" <if condition="$now_goods['limit_type'] eq 1">selected</if>>{pigcms{:L_("每个ID限购")}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label id="max_num">{pigcms{:L_("每单限购")}</label></label>
                                    <input class="col-sm-1" size="20" name="max_num" id="max_num" type="text" value="{pigcms{$now_goods.max_num}"/>
                                    <span class="form_tips" id="label_tips">{pigcms{:L_("每个订单该商品最多可订购的数量，0为不限制，如果同时设置了【最小购买数】与【限购类型】，则以【最小购买数】为准")}</span>
                                </div>

								<div class="form-group">
									<label class="col-sm-1"><label for="min_num">{pigcms{:L_("最小购买数量")}</label></label>
									<input class="col-sm-1" size="10" name="min_num" id="min_num" type="text" value="{pigcms{$now_goods.min_num}"/>
									<span class="form_tips">{pigcms{:L_("用户订购此商品时最小购买量，如果同时设置了【最小购买数】与【限购类型】，则以【最小购买数】为准")}</span>
								</div>
                                <div class="form-group">
									<label class="col-sm-1" for="is_discount"><label>{pigcms{:L_("商品分组")}</label></label>
									<div class="show-select-value" style="">
										<div class="select-list">
										</div>
										<span class="select-icon"></span>
										<ul class="list hide-select">
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
									<label class="col-sm-1" for="is_discount"><label>{pigcms{:L_("是否参与折扣")}</label></label>
									<select name="is_discount" id="is_discount">
										<option value="1" <if condition="$now_goods['is_discount'] eq 1">selected</if>>{pigcms{:L_("参与折扣")}</option>
										<option value="0" <if condition="$now_goods['is_discount'] eq 0">selected</if>>{pigcms{:L_("不参与折扣")}</option>
									</select>
                                    <span class="form_tips" style="color: red;">{pigcms{:L_("该商品是否参与店铺")}/{pigcms{:L_("分类折扣;折扣包括店铺折扣与分类折扣")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="is_use_coupon"><label>{pigcms{:L_("是否可用商家优惠券")}</label></label>
                                    <select name="is_use_coupon" id="is_use_coupon">
                                        <option value="1" <if condition="$now_goods['is_use_coupon'] eq 1">selected</if>>{pigcms{:L_("可用")}</option>
                                        <option value="0" <if condition="$now_goods['is_use_coupon'] eq 0">selected</if>>{pigcms{:L_("不可用")}</option>
                                    </select>
                                    <span class="form_tips" style="color: red;">{pigcms{:L_("该商品是否享受商家优惠券的优惠策略")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="show_start_time">{pigcms{:L_("每天显示时段")}</label></label>
                                    <div>
                                        <input id="show_start_time" type="text" value="{pigcms{:substr($now_goods['show_start_time'], 0, -3)}" name="show_start_time" readonly style="width:70px"/>   至
                                        <input id="show_end_time" type="text" value="{pigcms{:substr($now_goods['show_end_time'], 0, -3)}" name="show_end_time" readonly style="width:70px"/>
                                        <span class="form_tips" style="color: red;">{pigcms{:L_("不填或都填写00:00表示24小时都显示")}</span>
                                    </div>
                                </div>
								<if condition="$print_list">
								<div class="form-group">
									<label class="col-sm-1" for="print_id"><label>{pigcms{:L_("归属打印机")}</label></label>
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
									<label class="col-sm-1"><label for="content">{pigcms{:L_("商品描述：")}</label></label>
									<textarea name="des" id="content" style="width:702px;">{pigcms{$now_goods.des}</textarea>
								</div>
							</div>
							<div id="txtattr" class="tab-pane">
								<div class="alert alert-info" style="margin:10px 0;">
									<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
									{pigcms{:L_("规格，即大家熟悉的商品可选择类别。例如菜品有大份、中份、小份，衣服有颜色、尺码等，规格可以单独设置价格库存等信息。")}
									<br/><br/>
									{pigcms{:L_("属性，可以理解为用户下单时选择的标签。例如菜品中的过桥米线可以选2个荤菜和选5个素菜，一份菜品可选辣不辣，但是这些不涉及价格，需要标注的。")}
								</div>
								<span id="spec_limit" style="display: none;color: red;">{pigcms{:L_("名称请不要超过12个字！")}</span>
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
												<a class="list_del" href="javascript:;" title="{pigcms{:L_("删除这个选项")}">×</a>
											</li>
											</volist>
										</ul>
										<p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  {pigcms{:L_("添加属性的属性值")}</a></p>
									</div>
									</volist>
									<p class="add_properties"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success">{pigcms{:L_("添加属性")}</a></p>
								</div>

								<div class="topic_box">
									<p class="add_table" <if condition="!$now_goods['spec_list']">style="display:none"</if>><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success" >{pigcms{:L_("生成规格关系")}</a></p>
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>
											<th style="display:none">{pigcms{:L_("原价")}</th><th>{pigcms{:L_("进价")}</th><th>{pigcms{:L_("现价")}</th><th>{pigcms{:L_("每单限购")}</th><th>{pigcms{:L_("库存")}</th>
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
												<!-- <td><input type="text" class="txt" name="seckill_prices[]" value="{pigcms{$gl['seckill_price']}" style="width:80px;"></td> -->
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
								</div>
							</div>
							<div id="seckill" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="seckill_price">{pigcms{:L_("商品限时价")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_price" id="seckill_price" type="text" value="{pigcms{$now_goods.seckill_price|default=0}"/>
									<span class="form_tips">0{pigcms{:L_("表示无限时价。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="seckill_stock">{pigcms{:L_("限时价库存")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_stock" id="seckill_stock" type="text" value="{pigcms{$now_goods.seckill_stock}"/>
									<span class="form_tips">-1{pigcms{:L_("表示无限量。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="seckill_type0">{pigcms{:L_("限时价类型")}</label></label>
									<span><label><input id='seckill_type0' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("固定时间段")}</span>&nbsp;</label></span>
									<span><label><input id='seckill_type1' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("每天的时间段")}</span></label></span>
								</div>

								<div class="form-group datetime" <if condition="$now_goods['seckill_type'] eq 1 ">style="display:none"</if>>
									<label class="col-sm-1"><label for="goods_seckill_open_datetime">{pigcms{:L_("限时段")}</label></label>
									<div>
										<input id="goods_seckill_open_datetime" type="text" value="{pigcms{$now_goods['seckill_open_time']|date='Y-m-d H:i',###}" name="seckill_open_datetime" readonly/>	{pigcms{:L_("至")} 
										<input id="goods_seckill_close_datetime" type="text" value="{pigcms{$now_goods['seckill_close_time']|date='Y-m-d H:i',###}" name="seckill_close_datetime" readonly/>
									</div>
								</div>
                                
                                <div class="form-group time" <if condition="$now_goods['seckill_type'] eq 0 ">style="display:none"</if>>
                                    <label class="col-sm-1"><label for="goods_seckill_open_time">{pigcms{:L_("限时段")}</label></label>
                                    <div>
                                        <input id="goods_seckill_open_time" type="text" value="{pigcms{:date('H:i' , $now_goods['seckill_open_time'])}" name="seckill_open_time" readonly style="width:70px"/>   至
                                        <input id="goods_seckill_close_time" type="text" value="{pigcms{:date('H:i' , $now_goods['seckill_close_time'])}" name="seckill_close_time" readonly style="width:70px"/>
                                    </div>
                                </div>
							</div>
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
									<label class="col-sm-1"><label for="freight_template">运费模版</label></label>
									<select name="freight_template" id="freight_template">
										<option value="0" <if condition="0 eq $mall_goods_config['freight_template']">selected</if>>{pigcms{:L_("请选择运费模板")}...</option>
										<volist name="express_template" id="express">
										<if condition="$express['id'] eq intval($mall_goods_config['freight_template'])">
										<option value="{pigcms{$express['id']}" selected>{pigcms{$express['name']}</option>
										<else />
										<option value="{pigcms{$express['id']}">{pigcms{$express['name']}</option>
										</if>
										</volist>
									</select>
									<a class="handle_btn" href="{pigcms{:U('Express/add',array('no_nav'=>1))}">+{pigcms{:L_("新建")}</a>	
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="freight_value">{pigcms{:L_("其他区域运费")}</label></label>
									<div><input name="freight_value" type="text" value="{pigcms{$mall_goods_config.freight_value|floatval}" /></div>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="freight_type">{pigcms{:L_("运费计算方式")}</label></label>
									<span><label><input name="freight_type" <if condition="$mall_goods_config['freight_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("按最大值算")}</span>&nbsp;</label></span>
									<span><label><input name="freight_type" <if condition="$mall_goods_config['freight_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("单独计算")}</span></label></span>
								</div>
								<input type="hidden" value="{pigcms{$now_sort.sort_id}" id="goods_id" />
								<div class="form-group">
									<label class="col-sm-1"><label for="cat_fid"><font color="red">*</font>{pigcms{:L_("商城商品分类")}</label></label>
									<fieldset id="choose_category" cat_fid="{pigcms{$mall_goods_config.cat_fid}" cat_id="{pigcms{$mall_goods_config.cat_id}"></fieldset>
									<span class="form_tips" style="margin-left:0px;">{pigcms{:L_("如果是属于商城业务的商品，请务必选择商品所属分类，否则不会显示在该店铺的商城商品")}</span>
								</div>

								<!-- {pigcms{:L_("增加商品归属限制")} -->	
								<div class="form-group" id="mall_box">
									<label class="col-sm-1"><label for="goods_type">{pigcms{:L_("该商品是否只属于商城")}</label></label>
									<span>
										<label><input name="goods_type" <if condition="$mall_goods_config['goods_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label>
									</span>
									<span>
										<label><input name="goods_type" <if condition="$mall_goods_config['goods_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("是")}</span></label>
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
							<if condition="!empty($_SESSION['system'])">
								<div id="score" class="tab-pane">
									
									<div class="form-group">
										<label class="col-sm-1"><label for="score_percent">{pigcms{:L_("消费")}1{pigcms{$config['Currency_txt']}{pigcms{:L_("获得")}{pigcms{$config.score_name}</label></label>
										<div>
											<input name="score_percent" type="text" value="{pigcms{$now_goods.score_percent}" /><b style="color:red">(* {pigcms{:L_("请填写>0的数字，设置百分比请填写%，如")}2%)</b>
										</div>
									</div>	
									<div class="form-group">
										<label class="col-sm-1"><label for="score_max_type">{pigcms{$config.score_name}{pigcms{:L_("最大使用数")}</label></label>
										<div>
											
											<input name="score_max_type" type="radio" value="0" <if condition="$now_goods.score_max eq 0">checked</if>/>{pigcms{:L_("跳过")}
											<input name="score_max_type" type="radio" value="1" <if condition="$now_goods.score_max gt 1">checked</if>/>{pigcms{:L_("设置")}
											<input name="score_max" id="score_max" type="text" value="{pigcms{$now_goods.score_max|floatval}" <if condition="$now_goods.score_max eq 0">style="display:none"</if>/><b style="color:red">(* {pigcms{:L_("请填写>0的整数，如")}10)</b>
										</div>
									</div>
								</div>
							</if>
							<div id="labelSet" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="">{pigcms{:L_("商品标签")}</label></label>
									<div class="label_box">
										<notempty name='now_goods.label_arr'>
											<volist name='now_goods.label_arr' id='lvo'>
												<p>
													<input class="col-sm-2" size="20" name="label[]" type="text" value="{pigcms{$lvo}" maxlength="8" />
													<i class='add_btn btn2'></i>
													<i class='reduce_btn btn2'></i>
												</p>
											</volist>
										<else/>
											<p>
												<input class="col-sm-2" size="20" name="label[]" type="text" value="" maxlength="8" />
												<i class='add_btn btn2'></i>
												<i class='reduce_btn btn2'></i>
											</p>
										</notempty>										
										<span class='tips' style="color:red"></span>
										<span style='color:red;'>{pigcms{:L_("每个标签最多填写8个字")}</span>
									</div>
								</div>
							</div>

                            <div id="areaSet" class="tab-pane">
                                <div class="table-list">
                                    <table width="100%" cellspacing="0">
                                        <colgroup>
                                            <col/>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th><label><input type="checkbox" id="all">&nbsp;&nbsp;全选</label>
                                                <!--<input type="submit"  name="dosubmit" value="提交" class="button" />-->
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="is_array($area_list)">
                                            <volist name="area_list" id="vo">
                                                <tr>
                                                    <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$vo['area_id']}" data-id="{pigcms{$vo['area_id']}" <if condition="$vo['select']">checked</if> value="{pigcms{$vo['area_id']}">&nbsp;&nbsp;{pigcms{$vo.area_name}</label><notempty name="vo['son_list']">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="return sqzk({pigcms{$vo['area_id']},this);" href="#" tit="zhankai">展开 ↓</a></notempty></td>
                                                </tr>
                                                <php> if ($vo['son_list']) {</php>
                                                <tr class="son_{pigcms{$vo.area_id}" style="display:none;">
                                                    <td>
                                                        <table width="100%" cellspacing="0">
                                                            <volist name="vo['son_list']" id="son">
                                                                <tr style="background: #f7f6f1">
                                                                    <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$son['area_id']}" data-id="{pigcms{$son['area_id']}" <if condition="$son['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}">&nbsp;&nbsp;|--------{pigcms{$son.area_name}</label><notempty name="son['son_list']">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="return sqzk({pigcms{$son['area_id']},this);" href="#" tit="zhankai">展开 ↓<i class="fa fa-fw fa-angle-right text"></i></a></notempty></td>
                                                                </tr>
                                                                <php> if ($son['son_list']) {</php>
                                                                <tr class="son_{pigcms{$son.area_id}" style="display:none;">
                                                                    <td>
                                                                        <table width="100%" cellspacing="0">
                                                                            <volist name="son['son_list']" id="last">
                                                                                <tr style="background: #e8e8e3">
                                                                                    <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$last['area_id']}" data-id="{pigcms{$last['area_id']}" <if condition="$last['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}-{pigcms{$last['area_id']}">&nbsp;&nbsp;|--------------------{pigcms{$last.area_name}</label></td>
                                                                                    <td></td>
                                                                                </tr>
                                                                            </volist>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <php> } </php>
                                                            </volist>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <php> } </php>
                                            </volist>
                                            <else/>
                                            <tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
                                        </if>
                                        </tbody>
                                    </table>
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
.video_box{margin-left:8%;}
.video_box video{width:300px;}
#theList{}
#theList .upload_li span.weui-btn{
	display: inline-block;
    padding: 2px 8px;    
    margin: 4px 0;
    margin-right: 8px;
    background: #0099cc;
    color: #fff;
    cursor: pointer;
}
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js?{pigcms{$config.system_version}"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/video_upload.js?1"></script>
<script>
var uploaderHas = false;
var uploaderHas2 = false;
var swf='{pigcms{$static_public}js/Uploader.swf';
var server="{pigcms{:U('Mall/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
//"视频上传的地址")}
var _backEndUrl="{pigcms{:U('Mall/vupload')}";
//"删除上传中的缓存视频")}
var del_video_cache_url="{pigcms{:U('Mall/del_video_cache')}";
var diyVideo = "{pigcms{:U('Article/diyVideo')}";
var score_name = "{pigcms{$config['score_name']}";
var open_price_type = "{pigcms{$open_price_type}";
var json = '{pigcms{$now_goods['json']}', sortList = '{pigcms{$sort_list}', selectIds = '{pigcms{$select_ids}';
var category_list = '{pigcms{$category_list}', ajax_goods_properties = "{pigcms{:U('Mall/ajax_sort_goods_properties')}";
var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';
var video_name="video_url";


//解析淘宝地址
var parse_taobao_url = '{pigcms{:U("Taobao/parseGoods")}';

var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=shop/content&session_id={pigcms{:session_id()}";

var cssPath = "{pigcms{$static_path}css/group_editor.css";
var upload_image = "{pigcms{:U('Mall/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
setTimeout(function(){
	var  uploader = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Mall/ajax_upload_pic', array('store_id' => $now_store['store_id']))}",
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
			alert(response.message);
		}
	});

	uploader.on('uploadError', function(file,reason){
		$('.loading'+file.id).remove();
		alert('{pigcms{:L_("上传失败！请重试。")}');
	});

},20);
$('#myTab li a').click(function(){
	uploaderHas = true;
	if(uploaderHas2 == false && $(this).attr('href') == '#txtattr'){
		add_file_upload();
		uploaderHas2 = true;
	}
});



var formathtml = new Array();
var format_value = new Array();
function deleteImage(path,obj,type){
	type=type || 1;
	$.post("{pigcms{:U('Mall/ajax_del_pic')}",{path:path},function(res){

	});
	if(type==1){
		$(obj).closest('.upload_pic_li').remove();
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

    $('.form-horizontal').submit(function() {
        if($("input[type='checkbox']").is(':checked')){
            $("input[name='use_area']").val(1);
        }
    })

	$('#freight_value1').change(function(){
		if ($(this).val() != 0) {
			$('input[name=freight_type][value=1]').attr("checked",'checked');
		} else {
			$('input[name=freight_type][value=0]').attr("checked",'checked');
		}
	});

	$("#price_type").bind("change",function(){
		var _thisval = $(this).find(":selected").val();
		if(_thisval == '0' || _thisval == '1'){
			$("#account_money").parent().hide();
			$("#account_score").parent().hide();
		}
		else if(_thisval == '2'){
			$("#account_money").parent().show();
			$("#account_score").parent().show();	
		}
		else if(_thisval == '3'){
			$("#account_money").parent().hide();
			$("#account_score").parent().show();		
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

	$('input[name="score_max_type"]').click(function(){
		if($(this).val()==1){
			$('#score_max').show();
		}else{
			$('#score_max').hide();
		}
	});
    
    $('#limit_type').change(function(){
        if ($(this).val()==1) {
            $('#label_limit').html('{pigcms{:L_("每个ID限购")}');
            $('#label_tips').html('{pigcms{:L_("每个用户该商品最多可订购的数量，0为不限制")}');
        } else {
            $('#label_limit').html('{pigcms{:L_("每单限购")}');
            $('#label_tips').html('{pigcms{:L_("每个订单该商品最多可订购的数量，0为不限制")}');
        }
    });

    $('#all').click(function(){
        if ($(this).is(':checked')) {
            $("input[name='use_area']").val(1);
            $('input[type="checkbox"]').attr('checked', true);
        } else {
            $("input[name='use_area']").val(0);
            $('input[type="checkbox"]').attr('checked', false);
        }
    });
    //$(".mainbox").height(10000);
    $('input[type="checkbox"]').click(function(){
        var id = $(this).data('id');
        var idStr = $(this).val();
        var idArr = idStr.split('-');

        if ($(this).is(':checked')) {
            if (idArr.length > 1) {
                for (var i = 0; i < idArr.length - 1; i++) {
                    $('#check_' + idArr[i]).attr('checked', true);
                }
            }

            if(id>0){
                $("input[name='use_area']").val(1);
            }
            $('.son_' + id).find('input[type="checkbox"]').attr('checked', true);
        } else {
            $('.son_' + id).find('input[type="checkbox"]').attr('checked', false);
            if($("input[type='checkbox']").is(':checked') && $('#all').is(':checked')==false){
                $("input[name='use_area']").val(1);
            }else{
                $("input[name='use_area']").val(0);
            }
        }
    });
});
function searchGoods(number)
{
	$.get('?g=Merchant&c=Shop&a=ajax_goods', {'goods_id':number}, function(response){
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
}
function addLink(){
	art.dialog.data('fun', searchGoods);
	art.dialog.open('?g=Merchant&c=Shop&a=sys_goods',{lock:true,title:'{pigcms{:L_("商品库商品',width:800,height:550,top:40,yesText:'关闭")}',background: '#000',opacity: 0.45});
}

$('#name').blur(function() {
    this_ = $(this);
    var length = this_.val().length;
    if (length > 30) {
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
</script>
<script type="text/javascript">
$(function(){
	/*{pigcms{:L_("添加标签")}*/
	$(document).on('click','#labelSet .label_box .add_btn',function(){
		var fobj=$(this).parents('.label_box');
		var num=fobj.find('p').length;
		if(num>=4){
			fobj.find('.tips').show().text('{pigcms{:L_("最多添加4个标签！")}');
		}else{
			fobj.find('.tips').hide();
			var obj=fobj.find('p:first-child').clone();
			obj.find('input').val('');
			fobj.find('.tips').before(obj);	
		}					
	});
	/*{pigcms{:L_("删除标签")}*/
	$(document).on('click','#labelSet .label_box .reduce_btn',function(){
		var fobj=$(this).parents('.label_box');
		fobj.find('.tips').hide();
		$(this).parent().remove();
	});
});
</script>
<!-- 自营店铺支持绑定平台E卡 -->
<script type="text/javascript">
$(function(){
	var get_ecard_url='{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=ecard_list&store_id={pigcms{$now_store.store_id}';
	$('#choose_ecard').click(function(){
		parent.layer.open({
			title:false,
			closeBtn: 0,
			type:2,
			content:get_ecard_url+'&default_ecard_id='+default_ecard_id,
			area:['650px','576px'],
			shade: 0.2,
			cancel:function(){
				
			},
			move:false
		});
	});
	$(document).on('click','#ecard_cancel_bind',function(){
		$(this).remove();
		$('input[name=ecard_id]').remove();
		$('#choose_ecard').text('选择E卡');
		default_ecard_id=0;
		$('#stock_num').parents('.form-group').show();
		$('#myTab li a[href="#txtattr"]').show();
	});
});
var default_ecard_id={pigcms{$now_goods.ecard_id|default=0};
if(default_ecard_id>0){
	choice_ecard(default_ecard_id,'{pigcms{$now_goods.ecrad_name}');
}
function choice_ecard(ecard_id,card_name,index){
	default_ecard_id=ecard_id;
	card_name=card_name || '';
	$('#choose_ecard').text('已关联{pigcms{$config.ecard_alias_name}-'+card_name);
	$('#choose_ecard').before($('<input>',{'type':'hidden','name':'ecrad_name','val':card_name}));
	$('#choose_ecard').before($('<input>',{'type':'hidden','name':'ecard_id','val':ecard_id}));
	$('#choose_ecard').after($('<b>',{'text':'取消绑定','id':'ecard_cancel_bind','style':'display:inline-block;margin-left:20px;cursor:pointer;'}));
	if(index){
		layer.close(index);
	}
	/*不支持设置库存和规格*/
	$('#stock_num').parents('.form-group').hide();
	$('#myTab li a[href="#txtattr"]').hide();
}
</script>
<!-- 增加商品重量设置 -->
<script type="text/javascript">
$(function(){
	$('#sell_type').change(function(){
	    if ($(this).val()==2) {
	        $('#number_tips').html('请输入5位数字，必填。');
	        $('.weight').show();
	        $('.num_stock').hide();
	        no_spec = true;
	    } else {
	        $('#number_tips').html('选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。');
	        $('.weight').hide();
	        $('.num_stock').show();
	        no_spec = false;
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
});
	$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("新建运费模板")}',
				padding: 0,
				width: 1000,
				height: 520,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
				cancel: function () {
					reload_express();
				}
			});
			return false;
		});
	//重新请求运费模板
	function reload_express(){
		$.post("{pigcms{:U('Mall/refresh_express')}",{},function(res){
			$("#freight_template").html(res);
		});
	}

function sqzk(aid,_this){
    if($(_this).attr('tit') == 'shouqi'){
        $('.son_' + aid).hide();
        $(_this).text('展开 ↓');
        $(_this).attr('tit','zhankai');
    }
    else{
        $('.son_' + aid).show();
        $(_this).text('收起 ↑');
        $(_this).attr('tit','shouqi');
    }
    return false;
}
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/pase_taobao.js"></script>

<include file="Public:footer"/>
