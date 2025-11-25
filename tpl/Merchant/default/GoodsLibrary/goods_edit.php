<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('GoodsLibrary/goods_list',['store_id'=>$_GET['store_id']])}">{pigcms{:L_("商品库管理")}</a>			</li>
			</li>
			<li class="active"><a href="{pigcms{:U('GoodsLibrary/goods_sort_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('GoodsLibrary/goods_list',array('sort_id'=>$now_sort['sort_id'],'store_id'=>$now_store['store_id']))}">{pigcms{$now_sort.sort_name}</a></li>
			<li class="active">{pigcms{:L_("修改商品")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				table .reg_file{display:inline-block;;background: #0099cc;color: #fff;position: relative;}
				table .reg_img{max-height:60px;vertical-align: bottom;;margin-right: 10px;}
				.question_box ul .txt {
				    width: auto !important;
				}
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
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" id="goods_id" />
						<input type="hidden" value="{pigcms{$_GET['page']}" id="page" name="page">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane  active">
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
                                <if condition="empty($merchant['miaomaiyun_switch'])">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="name">{pigcms{:L_("快速录入")}</label></label>
                                        <input class="col-sm-2" name="sysname" id="sysname" type="text" placeholder="{pigcms{:L_("扫商品条码录入")}"/>
                                        <a class="btn btn-sm btn-success" onclick="addLink('sysname')" style="line-height: 1.2;margin-left: 10px">{pigcms{:L_("从商品库选择")}</a>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商品分类")}<font color="red">*</font></label></label>
									<fieldset id="choose_sort"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}<font color="red">*</font></label></label>
                                    <input class="col-sm-2" size="20" name="name" id="name" type="text" <if condition="$merchant['miaomaiyun_switch']"> disabled </if> value="{pigcms{$now_goods.name}"/>
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
									<span class="form_tips">{pigcms{:L_("图片尺寸支持900*500px与600*600px，图片后缀支持：jpg, jpeg, png, gif")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("选择图片")}</label></label>
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('upload_pic_ul','goods')">{pigcms{:L_("从图片库选择")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("图片预览")}</label></label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</volist>
										</ul>
									</div>
								</div>
                                <if condition="$config.is_open_shop_weigh eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="price">{pigcms{:L_("计价方式")}</label></label>
                                        <select name="sell_type" id="sell_type" disabled="true">
                                            <option value="1" <if condition="$now_goods['sell_type'] eq 1"> selected</if>>{pigcms{:L_("计件")}</option>
                                            <option value="2" <if condition="$now_goods['sell_type'] eq 2"> selected</if>>{pigcms{:L_("计重")}</option>
                                        </select>
                                        <span class="form_tips">{pigcms{:L_("称重商品请选择计重模式，商品添加后不能修改计重方式")}</span>
                                    </div>
                                </if>
                                <!-- <div class="form-group">
									<label class="col-sm-1"><label for="price_range_low">{pigcms{:L_("商品最低价格")}</label></label>
                                    <input class="col-sm-1" size="20" name="price_range_low" id="price_range_low" type="text" value="{pigcms{$now_goods.price_range_low|floatval}" />
								</div> 
                                <div class="form-group">
									<label class="col-sm-1"><label for="price_range_height">{pigcms{:L_("商品最高价格")}</label></label>
                                    <input class="col-sm-1" size="20" name="price_range_height" id="price_range_height" type="text" value="{pigcms{$now_goods.price_range_height|floatval}" />
								</div>  -->
                                <div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品价格")}<font color="red">*</font></label></label>
                                    <if condition="$now_goods.sell_type eq 2">
                                        <input class="col-sm-1" size="20" name="price" id="price" type="text" value="{pigcms{$now_goods.price|floatval}"  readonly='readonly' onclick="tip(this);" />
                                    <else/>
                                        <input class="col-sm-1" size="20" <if condition="$merchant['miaomaiyun_switch']"> disabled </if> name="price" id="price" type="text" value="{pigcms{$now_goods.price|floatval}"  <eq name="now_goods.is_act" value='1'>readonly='readonly' onclick="tip(this);"</eq> />
                                    </if>
									<if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="{pigcms{$now_goods.extra_pay_price}" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>
									<span class="form_tips">{pigcms{:L_("必填。")}<if condition="$now_goods['sell_type'] eq 2"> {pigcms{:L_("计重类的商品价格无需设置，是根据下面的商品单价与重量自动计算出来的每份商品价格。")}</if></span>
								</div> 
								<div class="form-group weight" <if condition="$now_goods['sell_type'] eq 1">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="unit_price">{pigcms{:L_("商品单价")}<font color="red">*</font></label></label>
                                    <input class="col-sm-1" size="20" name="unit_price" id="unit_price" type="text" value="{pigcms{$now_goods.unit_price}"/>
                                    <span class="form_tips">{pigcms{:L_("元500g（一斤），必填。")}</span>
                                </div>
                                <if condition="$config.is_open_shop_weigh eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="weight">{pigcms{:L_("商品重量")}<if condition="$now_goods['sell_type'] eq 2"><font color="red">*</font></if></label></label>
                                        <input class="col-sm-1" size="20" name="goods_weight" id="weight" type="text" value="{pigcms{$now_goods.weight}"/>
                                        <select name="weight_unit" id="weight_unit" style="margin-left: 10px;">
                                            <option value="1" <if condition="$now_goods['weight_unit'] eq 1"> selected</if>>g</option>
                                            <option value="2" <if condition="$now_goods['weight_unit'] eq 2"> selected</if>>kg</option>
                                        </select>
                                        <span class="form_tips"><if condition="$now_goods['sell_type'] eq 2">{pigcms{:L_("必填。")}</if></span>
                                    </div>
                                </if> 
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品进价")}</label></label>
									<input class="col-sm-1" size="20" name="cost_price" id="cost_price" type="text" value="{pigcms{$now_goods.cost_price|floatval}"/>
									<span class="form_tips">{pigcms{:L_("进货价用户是看不到")}</span>
								</div>
                                <div class="form-group weight" <if condition="$now_goods['sell_type'] eq 1">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="tare_weight">{pigcms{:L_("商品皮重")}</label></label>
                                    <input class="col-sm-1" size="20" name="tare_weight" id="weight" type="text" value="{pigcms{$now_goods.tare_weight}"/>
                                    <span class="form_tips">{pigcms{:L_("单位")}g</span>
                                </div>
                                <div class="form-group weight" <if condition="$now_goods['sell_type'] eq 1">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="stock_weight">{pigcms{:L_("库存重量")}</label></label>
                                    <div>{pigcms{:L_("当前库存重量")}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("原始库存重量")}</div>
                                    <div>
                                        <input style="width:100px;" name="stock_weight" id="stock_weight" type="text" value="{pigcms{$now_goods.stock_weight}"/>   /
                                        <input style="width:100px;" name="original_weight" id="original_weight" type="text" value="{pigcms{$now_goods.original_weight}"/>
                                        <span class="form_tips">-1{pigcms{:L_("表示无限量，单位g，必填")} </span>
                                    </div>
                                </div>

                                
                                <div class="form-group num_stock">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品库存")}</label></label>
									<div>{pigcms{:L_("当前库存")}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("原始库存")}</div>
									<div>
										<input name="stock_num" <if condition="$merchant['miaomaiyun_switch']"> disabled </if> style="width:70px;" id="stock_num" type="text" value="{pigcms{$now_goods.stock_num}" <eq name="now_goods.is_act" value='1'> readonly='readonly' onclick="tip(this);"</eq> />   /
                                        <input name="original_stock" <if condition="$merchant['miaomaiyun_switch']"> disabled </if> style="width:70px;" id="original_stock" type="text" value="{pigcms{$now_goods.original_stock}" />
										<span class="form_tips">-1{pigcms{:L_("表示无限量。当前库存小于10时，商品详细页面会显示库存。")}</span>
									</div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="unit"><font color="red">*</font>{pigcms{:L_("商品单位")}</label></label>
                                    <input class="col-sm-1" size="20" name="unit" id="unit" type="text" value="{pigcms{$now_goods.unit}"/>
                                    <span class="form_tips">{pigcms{:L_("必填。如个、斤、份")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<input class="col-sm-1" size="20" name="number" id="number" type="text" value="{pigcms{$now_goods.number}"/>
									<span class="form_tips" id="number_tips">{pigcms{:L_("选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="supply_price">{pigcms{:L_("供货价")}</label></label>
                                    <input class="col-sm-1" size="20" name="supply_price" id="supply_price" type="text" value="{pigcms{$now_goods.supply_price}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="goods_code">{pigcms{:L_("商品编码")}</label></label>
                                    <input class="col-sm-1" size="20" name="goods_code" id="goods_code" type="text" value="{pigcms{$now_goods.goods_code}"/>
                                </div>
							</div>

                            <div id="txtintro" class="tab-pane">
                                <div class="form-group" >
                                    <label class="col-sm-1"><label>{pigcms{:L_("商品详情：")}</label></label>
                                    <textarea name="des" id="content" style="width:702px;">{pigcms{$now_goods.des}</textarea>
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
									<p class="add_spec"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success" <if condition="count($now_goods['spec_list']) egt 3">style="display:none"</if>><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>{pigcms{:L_("添加规格")}</a></p>
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
											<volist name="ro['val_status']" id="ra" key="iii">
											<li>
												<u>{pigcms{:L_("属性的属性值：")}</u>
												<input type="text" class="txt properties_val" name="properties_val[{pigcms{$ik-1}][]" value="{pigcms{$ra[0]}"/>
												<a class="list_del" href="javascript:;" title="{pigcms{:L_("删除这个选项")}">×</a>
                                                <label class="statusSwitch" style="display:inline-block;">
                                                    <input name="properties_val_status_{pigcms{$ik-1}_{pigcms{$iii-1}" class="ace ace-switch ace-switch-6" type="checkbox" value="1" <if condition="$ra[1]">checked</if>/>
                                                    <span class="lbl"></span>
                                                </label>
											</li>
											</volist>
										</ul>
										<p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  {pigcms{:L_("添加属性的属性值")}</a></p>
									</div>
									</volist>
									<p class="add_properties" style="margin-top:20px"><a href="javascript:;" title="{pigcms{:L_("添加属性")}" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>{pigcms{:L_("添加属性")}</a></p>
								</div>

                                <div class='topic_til'>
                                   <h4>{pigcms{:L_("设置规格关系")}</h4>
								</div>
								<p>
									<eq name="now_goods.is_act" value='1'>
										<a href="javascript:;" title="{pigcms{:L_('生成规格关系')}" class="btn btn-sm btn-default"  onclick="tip(this);" <if condition="!$now_goods['spec_list']">style="display:none;"</if>>{pigcms{:L_("生成规格关系")}</a>
									<else/>
										<a href="javascript:;" title="{pigcms{:L_('生成规格关系')}" class="btn btn-sm btn-success add_table" style="background-color:#0099cc;<if condition="!$now_goods['spec_list']">display:none;</if>">{pigcms{:L_("生成规格关系")}</a>
									</eq>
								    {pigcms{:L_("*当您为商品设置了“规格”后，请点击“生成规格关系”，为该商品设置每个规格的价格。")}
								</p>
								<div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("商品条形码")}</th>
											<th>{pigcms{:L_("规格图")}</th>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>

                                            <if condition="$config.is_open_shop_weigh eq 1">
                                                <th>{pigcms{:L_("重量")}(<eq name='now_goods.weight_unit' value='2'>kg<else/>g</eq>)</th>
                                            </if>

                                            <th>{pigcms{:L_("原价")}</th><th>{pigcms{:L_("进价")}</th><th>{pigcms{:L_("现价")}</th><!--<th>{pigcms{:L_("限时价")}</th>--><th><if condition="$now_goods['limit_type'] eq 0">{pigcms{:L_("每单限购")}<else/>{pigcms{:L_("每个ID限购")}</if></th><th>{pigcms{:L_("库存")}</th>
											<volist name="now_goods['properties_list']" id="gp">
											<th>{pigcms{$gp['name']}({pigcms{:L_("可选个数")})</th>
											</volist>
										</tr>

										<volist name="now_goods['list']" id="gl" key="num">
											<tr id="{pigcms{$gl['index']}">
												<td><input type="text" class="txt" name="numbers[]" value="{pigcms{$gl['number']}" style="width:100%;"></td>
												<td>
													<notempty name="gl.reg_img">
														<p>
															<img src="{pigcms{$gl['reg_img']}" class="reg_img" />
															<a href="#" onclick="deleteImage('{pigcms{$gl['reg_title']}',this,2);return false;">[ {pigcms{:L_("删除")} ]</a>
														</p>
													</notempty>
													<input type="hidden" name="reg_imgs[]" value="{pigcms{$gl['reg_title']}" >
													<div class="reg_file" <notempty name="gl.reg_img">style='display:none;'</notempty> >{pigcms{:L_("上传图片")}</div>
												</td>

												<volist name="gl['spec']" id="g">
												    <td>{pigcms{$g['spec_val_name']}</td>
												</volist>
                                                <if condition="$config.is_open_shop_weigh eq 1">
                                                    <td><input type="text" class="txt" name="weight[]" value="{pigcms{$gl['weight']}" style="width:80px;"></td>
                                                </if>
												<td><input type="text" class="txt" name="old_prices[]" value="{pigcms{$gl['old_price']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="cost_prices[]" value="{pigcms{$gl['cost_price']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="prices[]" value="{pigcms{$gl['price']}" style="width:80px;"></td>
												<!--<td><input type="text" class="txt" name="seckill_prices[]" value="{pigcms{$gl['seckill_price']}" style="width:80px;"></td>-->
                                                <td><input type="text" class="txt" name="max_nums[]" value="{pigcms{$gl['max_num']|default='0'}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="stock_nums[]" value="{pigcms{$gl['stock_num']}" style="width:80px;" <eq name="now_goods.is_act" value='1'>readonly='readonly' onclick="tip();" onclick="tip(this);"</eq>  ></td>

												<volist name="gl['properties']" id="gpp" key="num">
												<td><input type="text" class="txt" name="num{pigcms{$num-1}[]" value="{pigcms{$gpp['num']}" style="width:80px;"></td>
												</volist>
											</tr>
										</volist>
									</tbody>
									</if>
									</table>
                                </div>

<!--                                <div class='topic_til'>-->
<!--                                    <h4>{pigcms{:L_("附属商品区块")}</h4>-->
<!--                                </div>-->
<!--                                <div class="alert alert-info">-->
<!--                                    <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>-->
<!--                                    <p style="padding-bottom:10px">{pigcms{:L_("附属商品：例如，对于麻辣烫，附属商品为“白菜，金针菇，海带，牛肉”这样的商品即为附属商品。若您在创建的商品为附属商品，请把商品所属的分类选择为“附属商品”这个分类。")} </p>-->
<!--                                    <p style="padding-bottom:10px">{pigcms{:L_("附属商品区块：当您配置的是麻辣烫这类商品，如果您需要用户在APP上从比如“荤菜”和“素菜”，中选择具体的麻辣烫。您可以在此创建对应的附属商品区块“荤菜”，以及附属商品区块“素菜”，然后添加具体的商品或附属商品到该区块中。")} </p>-->
<!--                                </div>-->
<!--								<div class="topic_box">-->
<!--                                    <div class='fushuWrap'></div>-->
<!--									<p class="addFushuBlock" style="margin-top:20px"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-big btn-success"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>{pigcms{:L_("附属商品区块")}</a></p>-->
<!--								</div>-->

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
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script>
	var score_name = "{pigcms{$config['score_name']}";
	var open_price_type = "{pigcms{$open_price_type}";
    var json = '{pigcms{$now_goods['
    json
    ']}', sortList = '{pigcms{$sort_list}', selectIds = '{pigcms{$select_ids}';
    var category_list = '{pigcms{$category_list}', ajax_goods_properties = "{pigcms{:U('GoodsLibrary/ajax_goods_properties')}";
    var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';

    var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=GoodsLibrary/content&session_id={pigcms{:session_id()}";
    var cssPath = "{pigcms{$static_path}css/group_editor.css";
    var upload_image = "{pigcms{:U('GoodsLibrary/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
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
                $('#supply_price').val(response.data.supply_price);
                $('#goods_code').val(response.data.goods_code);
				$('#upload_pic_ul').html('<li class="upload_pic_li"><img src="' + response.data.image + '"/><input type="hidden" name="pic[]" value="' + response.data.imageTitle + '"/><br/><a href="#" onclick="deleteImage(\'' + response.data.imageTitle + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
			}
		}, 'json');
	}
	function addLink(){
		art.dialog.data('fun', searchGoods);
		art.dialog.open('?g=Merchant&c=Shop&a=sys_goods',{lock:true,title:'{pigcms{:L_("商品库商品',width:800,height:550,yesText:'关闭")}',background: '#000',opacity: 0.45});
	}

	var open_meal_electronic_menu = {pigcms{:intval($config['open_meal_electronic_menu'])};
	var foodshop_goods_sync = '{pigcms{$foodshop_goods_sync}';
	if (open_meal_electronic_menu =='1' && foodshop_goods_sync == '1') {
		$('select[name=status]').change(function(){
			$('.sync_foodshop').show();
		})
	}
    var uploaderHas = false;
    var uploaderHas2 = false;
    var swf = '{pigcms{$static_public}js/Uploader.swf';
    var server = "{pigcms{:U('GoodsLibrary/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
    var diyVideo = "{pigcms{:U('Article/diyVideo')}";
    var no_spec = false;
    $('#myTab li a').click(function () {
        if (no_spec == true && ($(this).attr('href') == '#txtattr' || $(this).attr('href') == '#seckill')) {
            alert('{pigcms{:L_("计重商品暂不支持配置此项！")}');
            return false;
        }
        if (uploaderHas2 == false && $(this).attr('href') == '#txtattr') {
            setTimeout(function () {
                $('table .reg_file').each(function (n, v) {
                    add_upload_obj($(this));
                });
            }, 20);
            uploaderHas2 = true;

            // var obj=$(".selectOption option:selected").data('id');  
            // obj=26
            // selectGroup('',obj);

        }
    });

    // 商品图片
    setTimeout(function () {
        var uploader = WebUploader.create({
            auto: true,
            swf: '{pigcms{$static_public}js/Uploader.swf',
            server: "{pigcms{:U('GoodsLibrary/ajax_upload_pic', array('store_id' => $now_store['store_id']))}",
            pick: {
                id: '#J_selectImage',
                multiple: false
            },
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,png',
                mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
            }
        });
        uploader.on('fileQueued', function (file) {
            if ($('.upload_pic_li').size() >= 5) {
                uploader.cancelFile(file);
                alert('{pigcms{:L_("最多上传5个图片！")}');
                return false;
            }
        });
        uploader.on('uploadSuccess', function (file, response) {
            if (response.error == 0) {
                $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="' + response.url + '"/><input type="hidden" name="pic[]" value="' + response.title + '"/><br/><a href="#" onclick="deleteImage(\'' + response.title + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
            } else {
                alert(response.info);
            }
        });
        uploader.on('uploadError', function (file, reason) {
            $('.loading' + file.id).remove();
            alert('{pigcms{:L_("上传失败！请重试。")}');
        });

    }, 20);
    uploaderHas = true;

    var formathtml = new Array();
    var format_value = new Array();


    function deleteImage(path, obj, type) {
        type = type || 1;
        $.post("{pigcms{:U('GoodsLibrary/ajax_del_pic')}", {path: path}, function (res) {
        });
        if (type == 1) {
            $(obj).closest('.upload_pic_li').remove();
        } else if (type == 2) {
            var obj2 = $(obj).parents('td').find('input');
            obj2.val('');
            var obj3 = $(obj).parents('td').find('.reg_file');
            obj3.show();
            $(obj).parent().remove();
            set_reg_info(obj2, '');
            add_upload_obj(obj3);
        }
    }

    window.sessionStorage.setItem(session_index, json);
    $(document).ready(function () {
        $('#freight_value1').change(function () {
            if ($(this).val() != 0) {
                $('input[name=freight_type][value=1]').attr("checked", 'checked');
            } else {
                $('input[name=freight_type][value=0]').attr("checked", 'checked');
            }
        });

        $('input[name="score_max_type"]').click(function () {
            if ($(this).val() == 1) {
                $('#score_max').show();
            } else {
                $('#score_max').hide();
            }
        });

        $('#limit_type').change(function () {
            if ($(this).val() == 1) {
                $('#label_limit').html('{pigcms{:L_("每个ID限购")}');
                $('#label_tips').html('{pigcms{:L_("每个用户该商品最多可订购的数量，0为不限制，如果同时设置了【最小购买数】与【限购类型】，则【限购类型】无效")}');
            } else {
                $('#label_limit').html('{pigcms{:L_("每单限购")}');
                $('#label_tips').html('{pigcms{:L_("每个订单该商品最多可订购的数量，0为不限制，如果同时设置了【最小购买数】与【限购类型】，则【限购类型】无效")}');
            }
        });
    });

    $('#name').blur(function () {
        this_ = $(this);
        var length = this_.val().length;
        if (length > 12) {
            $('#name_limit').show();
        } else {
            $('#name_limit').hide();
        }
    });

    //"商品描述")}
    $('#describe').blur(function () {
        this_ = $(this);
        var length = this_.val().length;
        var size = this_.attr('size');
        if (length > size) {
            $('#describe_limit').show();
        } else {
            $('#describe_limit').hide();
        }
    });
    $('.tab-content').on('blur', '.spec_name,.spec_val,.properties_name,.properties_val', function () {
        this_ = $(this);
        var length = this_.val().length;
        if (length > 12) {
            $('#spec_limit').show();
        } else {
            $('#spec_limit').hide();
        }
    });

    /*{pigcms{:L_("活动商品点击编辑信息提醒")}*/
    var is_group = {pigcms{$now_goods.is_group | default = 0};
    var is_cut = {pigcms{$now_goods.is_cut | default = 0};
    var act_name = '';
    if (is_group == 1) {
        act_name = '{pigcms{:L_("拼团")}';
    } else if (is_cut == 1) {
        act_name = '{pigcms{:L_("砍价")}';
    }

    function tip(e) {
        if (act_name) {
            var tip_msg = '{pigcms{:L_("抱歉！该商品已经被设置为")}' + act_name + '{pigcms{:L_("活动，该项暂不可修改与删除。请先到商城管理")}--{pigcms{:L_("商城店铺列表")}--' + act_name + '{pigcms{:L_("管理中解禁该商品。")}';
            layer.alert(tip_msg, {title: '{pigcms{:L_("提示")}'});
            e.preventDefault();
            return false;
        }
    }

    if ($('#sell_type').val() == 2) {
        $('#number_tips').html('{pigcms{:L_("请输入5位数字，必填。")}');
        $('.weight').show();
        $('.num_stock').hide();
        no_spec = true;
    } else {
        $('#number_tips').html('{pigcms{:L_("选填。一般用于商超线下零售扫条形码查找商品，打印小票时也会打印条形码。")}');
        $('.weight').hide();
        $('.num_stock').show();
        no_spec = false;
    }
    $('#unit_price,#weight').keyup(function () {
        var sell_type = Number($('#sell_type').val());
        if (sell_type == 1) {
            return false;
        }
        var unit_price = parseFloat($('#unit_price').val());
        var weight = parseFloat($('#weight').val());
        var weight_unit = parseFloat($('#weight_unit').val());
        if (unit_price && weight) {
            if (weight_unit == 2) {
                weight = weight * 1000;
            }
            var now_price = parseFloat((unit_price / 500) * weight).toFixed(2);
        }
        $('#price').val(now_price);
    });
    $('#weight_unit').change(function () {
        $('#unit_price,#weight').trigger("keyup");
        var val = $(this).val();
        if (val == 1) {
            weight_unit = 'g';
        } else {
            weight_unit = 'kg';
        }
    });

    // 附属商品块 
    function subsidiaryCommodities(){
        var goods_id = {pigcms{$now_goods.goods_id};
        $.get("{pigcms{:U('select_subsidiary_piece')}&goods_id="+goods_id, {}, function (res) {
            res=JSON.parse(res);
            var html=''
            var arraySubsidiary=res.data;
            if(arraySubsidiary!=null){
                arraySubsidiary.forEach(function(value,i,array){
                html+='<div class="fushuBox">'
                html+=' <div class="fushu">'
                html += '		<span>{pigcms{:L_("区块名称")}：'
                html += '			 <input type="text" name="name_piece[]" value="'+value.name+'"  />'
                html += '		</span>'
                html+=' <span>'
                html+='{pigcms{:L_("设置区块总的最少购买数和最大购买数")}：<input type="text" name="mininum_piece[]" value="'+value.mininum+'"/> ~ <input type="text" name="maxnum_piece[]" value="'+value.maxnum+'"/>'
                html+=' <a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>'
                html+=' <label style="margin-left:10px; color:#999">*{pigcms{:L_("最少设置0，即用户可以不选")}</label>'
                html+=' </span>  '
                html+='</div> '
                html+=' <table class="table table-striped table-bordered FuShuTbody"  style="width:800px; margin-top:20px;">'
                html+=' <tbody class="tbody_til">'
                html+='     <tr>'
                html += '					<th>{pigcms{:L_("附属商品")}</th>'
                html += '					<th>{pigcms{:L_("售价")}</th>'
                html += '					<th>{pigcms{:L_("最少可选数")}</th>'
                html += '					<th>{pigcms{:L_("最多可选数")}</th>'
                html += '					<th>{pigcms{:L_("排序")}</th>'
                html+=' </tr>'
                html+=' </tbody>'
                html+=' <tbody class="tbodyFushu"> '
                if(value.goods.length>0){
                   var arrayGoods=value.goods;
                   arrayGoods.forEach(function(valueJ,j,array2){
                       if(valueJ.spec_value){
                           var inputstr = '<input type="text" class="txt" name="pg_price['+i+'][]" value="多规格" style="width:52px;" readonly >';
                       }else{
                           var inputstr = '<input type="text" class="txt" name="pg_price['+i+'][]" value="'+valueJ.price+'" style="width:80px;">';
                       }
                       html+='<tr><td>'+valueJ.name+'</td><td>'+inputstr+'<input type="hidden" name="pg_goods_id['+i+'][]" value="'+valueJ.goods_id+'"><input type="hidden" name="pg_goods_name['+i+'][]"  value="'+valueJ.name+'" ></td><td><input type="text" class="txt" name="pg_mininum['+i+'][]"  value="'+valueJ.mininum+'" style="width:80px;"></td><td><input type="text" class="txt" name="pg_maxnum['+i+'][]"  value="'+valueJ.maxnum+'" style="width:80px;"></td><td><input type="text" class="txt" name="pg_sort['+i+'][]"  value="'+valueJ.sort+'" style="width:80px;">  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
                    })
                }
                html+='  </tbody>'
                html+=' </table>'
                html+=' <a   data-savegood="'+i+'" class="btn btn-sm btn-success add_tr">{pigcms{:L_("添加附属商品")}</a>'
                html+='</div> '
            })
            }

            $('.fushuWrap').append(html);
            var options=$(".selectOption option:selected").val();
            selectOption(options);

        });
    }

    $(".addFushuBlock .btn").live('click', function () {
        var i = $('.fushuBox').size();
        var t = '';
        t += '<div class="fushuBox"> '
        t += '	<div class="fushu">'
        t += '		<span>{pigcms{:L_("区块名称")}'
        t += '			 <input type="text" name="name_piece[]"  />'
        t += '		</span>'
        t += '		<span>{pigcms{:L_("设置区块总的最少购买数和最大购买数")}：<input type="text" name="mininum_piece[]" /> ~ <input type="text" name="maxnum_piece[]" />'
        t += '		<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>'
        t += '		<label style="margin-left:10px; color:#999">*{pigcms{:L_("最少设置0，即用户可以不选")}</label>'
        t += '		</span>  '
        t += '	</div> '
        t += '	<table class="table table-striped table-bordered FuShuTbody"  style="width:800px; margin-top:20px;">'
        t += '		<tbody class="tbody_til">'
        t += '				<tr>'
        t += '					<th>{pigcms{:L_("附属商品")}</th>'
        t += '					<th>{pigcms{:L_("售价")}</th>'
        t += '					<th>{pigcms{:L_("最少可选数")}</th>'
        t += '					<th>{pigcms{:L_("最多可选数")}</th>'
        t += '					<th>{pigcms{:L_("排序")}</th>'
        t += '				</tr>'
        t += '		</tbody>'
        t += '		<tbody class="tbodyFushu"> '
        t += '		</tbody>'
        t += '	</table> '
        t += '	<p><a href="javascript:;" data-savegood="'+i+'" class="btn btn-sm btn-success add_tr">{pigcms{:L_("添加附属商品")}</a></p> '
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
        var store_id = {pigcms{$_GET.store_id};
        $.get("{pigcms{:U('goods_subsidiary_group')}&rejson=1&store_id="+store_id, {}, function (res) {
                res=JSON.parse(res);
                htmlOption='<option value="-1">{pigcms{:L_("未选择")}</option>'
                if(res.data!=null && res.data.length > 0){
                    group_id=res.data[0].id;
                    for (var i=0;i<res.data.length;i++){
                        htmlOption = htmlOption + "<option value='"+res.data[i].name+"' data-id='"+res.data[i].id+"'>"+res.data[i].name+"</option>" ;
                        if(thisSelect==res.data[i].name){
                            group_id=res.data[i].id;
                        }
                    }
                    $(".selectOption").html(htmlOption);
                    var htmlOptionLast = htmlOption.replace('<option value="-1">{pigcms{:L_("未选择")}</option>','');
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
                            var inputstr = '<input type="text" class="txt" name="pg_price['+e+'][]" value="多规格" style="width:52px;" readonly >';
                        }else{
                            var inputstr = '<input type="text" class="txt" name="pg_price['+e+'][]" value="'+value.price+'" style="width:80px;">';
                        }
                        html+='<tr><td>'+value.name+'</td><td>'+inputstr+'<input type="hidden" name="pg_goods_id['+e+'][]" value="'+value.goods_id+'"><input type="hidden" name="pg_goods_name['+e+'][]"  value="'+value.name+'" ></td><td><input type="text" class="txt" name="pg_mininum['+e+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_maxnum['+e+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_sort['+e+'][]"  value="0" style="width:80px;">  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
                    })
                }
            $(".selectOption[data-id="+e+"] option[data-id="+selectObj+"]").parents('.fushuBox').find('.tbodyFushu').html(html);
       });
    }

    $(document).ready(function () {
        var options=$(".selectOption option:selected").val();
        subsidiaryCommodities();
        selectOption(options);
        initCombination()
    })

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
        var scrollTop = parent.document.getElementById('contentView').scrollTop;
        var top = '100px'
        if(scrollTop >= 0){
            top = scrollTop + 10 + 'px'
        }
        var savegood=$(this).attr('data-savegood');
        layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: '{pigcms{$this->config['
            site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_piece_list&store_id={pigcms{$_GET.store_id}&savegood='+savegood+'',
            area: ['650px', '576px'],
            offset: top,
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
                html+='<td><input type="text" class="txt" name="pg_price['+savegood+'][]" value="多规格" style="width:52px;" readonly ></td>'
            }
            html+='<td><input type="text" class="txt" name="pg_mininum['+savegood+'][]"  value="0" style="width:80px;"><input type="hidden" name="pg_goods_id['+savegood+'][]"  value="'+goods_id+'" ><input type="hidden" name="pg_goods_name['+savegood+'][]"  value="'+name+'" ></td>'
            html+='<td><input type="text" class="txt" name="pg_maxnum['+savegood+'][]"  value="0" style="width:80px;"></td><td><input type="text" class="txt" name="pg_sort['+savegood+'][]"  value="0" style="width:80px;">  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
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
            site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_piece_list&type=com&store_id={pigcms{$_GET.store_id}&savegood='+savegood+'',
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

     // 组合套餐渲染数据 
    function initCombination(){
        var store_id = {pigcms{$_GET.store_id};
        var goods_id = {pigcms{$now_goods.goods_id};
        $.get("{pigcms{:U('combination_list')}&goods_id="+goods_id+'&store_id='+store_id , {}, function (res) {
            res=JSON.parse(res);
            var html=''
            var arraySubsidiary=res.data;
            if(arraySubsidiary!=null){
                arraySubsidiary.forEach(function(value,i,array){
                html += '<div class="comBox"> '
		        html += '	<div class="comContent">'
		        html += '		<span>{pigcms{:L_("套餐名称")}：'
		        html += '			 <input type="text" name="name_com[]" value="'+value.name+'" /><input type="hidden" name="com_ids[]" value="'+value.com_id+'" />'
		        html += '		</span>'
		        html += '		<span>{pigcms{:L_("组合套餐人数")}：<input type="text" name="num_com[]"  value="'+value.num+'"/> '
		        html += '		<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a>'
		        html += '		</span>  '
		        html += '	</div> '
		        html += '	<table class="table table-striped table-bordered comTbody"  style="width:800px; margin-top:20px;">'
		        html += '		<tbody class="tbody_til">'
		        html += '				<tr>'
		        html += '					<th>{pigcms{:L_("商品")}</th>'
		        html += '					<th>{pigcms{:L_("价格")}</th>'
		        html += '					<th>{pigcms{:L_("当前库存")}</th>'
		        html += '				</tr>'
		        html += '		</tbody>'
		        html += '		<tbody class="tbodyCom"> '

				if(value.goods.length>0){
                   var arrayGoods=value.goods;
                   arrayGoods.forEach(function(valueJ,j,array2){
                       html+='<tr><td>'+valueJ.name+'<input type="hidden" name="com_goods_id['+i+'][]" value="'+valueJ.goods_id+'"></td><td>'+valueJ.price+'</td> <td>'+valueJ.stock_num+'</td> <td> <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
                    })
                }

		        html += '		</tbody>'
		        html += '	</table> '
		        html += '	<p><a href="javascript:;" data-savegood="'+i+'" class="btn btn-sm btn-success add_tr">{pigcms{:L_("添加套餐商品")}</a></p> '
		        html += '</div> '
            })
            }

            $('.comWrap').append(html);
            // var options=$(".selectOption option:selected").val();
            // selectOption(options);

        });
    }
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js?t=1234"></script>
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

    .tbody_til {
        /* display: none */
    }

    .fushuBox {
        margin: 20px 0;
    }
    .topic_box{
        padding-top:0
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
    .comBox {
        margin: 20px 0;
    }
</style>