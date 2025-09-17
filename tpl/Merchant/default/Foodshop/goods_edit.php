<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Foodshop/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('Foodshop/goods_list',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{$now_sort.sort_name}</a></li>
			<li class="active">{pigcms{:L_("修改商品")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
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
							<if condition="!empty($_SESSION['system'])">
							<li>
								<a data-toggle="tab" href="#score">{pigcms{:L_("积分设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#take_a_percentage">{pigcms{:L_("抽成比例")}</a>
							</li>
							</if>
							<!--li>
								<a data-toggle="tab" href="#seckill">{pigcms{:L_("限时优惠")}</a>
							</li-->
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
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
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_id">{pigcms{:L_("商品分类")}<font color="red">*</font></label></label>
                                    <select name="sort_id" id="sort_id">
                                    	<volist name="sort_list" id="vo">
                                        	<option value="{pigcms{$vo.sort_id}" <eq name="vo.sort_id" value="$now_goods['sort_id']">selected="selected"</eq>>{pigcms{$vo.sort_name}</option>
                                    	</volist>
                                    </select>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="name" id="name" type="text" value="{pigcms{$now_goods.name}"/>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_id">{pigcms{:L_("上传图片")}<font color="red">*</font></label></label>
									<div style="display:inline-block;" id="J_selectImage">
										<div class="btn btn-sm btn-success" style="position:relative;">{pigcms{:L_("选择本地图片")}</div>
									</div>
									<span class="form_tips">{pigcms{:L_("图片宽度建议为：900px，高度建议为：")}500px</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_id">{pigcms{:L_("选择图片")}</label></label>
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('upload_pic_ul','foodshop_goods')">{pigcms{:L_("从图片库选择")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_id">{pigcms{:L_("图片预览")}</label></label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</volist>
										</ul>
									</div>
								</div>
                                <if condition="$now_store['template'] eq 1">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("图片显示：")}</label>
                                    <label><input type="radio" name="show_type" value="0" <if condition="$now_goods['show_type'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("小图")}</label>&nbsp;&nbsp;&nbsp;
                                    <label><input type="radio" name="show_type" value="1" <if condition="$now_goods['show_type'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("大图")}</label>&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips" style="color:red;">{pigcms{:L_("该设置项是控制前台点餐页面商品展示的图片是大图还是小图")}</span>
                                </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品原价")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="old_price" id="old_price" type="text" value="{pigcms{$now_goods.old_price|floatval}"/>
									<span class="form_tips">{pigcms{:L_("原价可不填，不填和现价一样")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品价格")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="price" id="price" type="text" value="{pigcms{$now_goods.price|floatval}"/>
									
									<if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="{pigcms{$now_goods.extra_pay_price}" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="unit">{pigcms{:L_("商品单位")}<font color="red">*</font></label></label>
									<input class="col-sm-1" size="20" name="unit" id="unit" type="text" value="{pigcms{$now_goods.unit}"/>
									<span class="form_tips">{pigcms{:L_("必填。如个、斤、份")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("原始库存")}</label></label>
									<input class="col-sm-1" size="20" name="original_stock" id="original_stock" type="text" value="{pigcms{$now_goods.original_stock|default='-1'}"/>
									<span class="form_tips" style="color:red;">{pigcms{:L_("当库存更新类型是每天更新的话，每天会将这个原始库存的值自动填充当前库存。（注：")}-1{pigcms{:L_("表示无限量），当库存更新类型是每天更新的话，每天必须第一个用户购买商品后才会将这个原始库存的值自动填充当前库存。")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("当前库存")}</label></label>
									<input class="col-sm-1" size="20" name="stock_num" id="stock_num" type="text" value="{pigcms{$now_goods.stock_num}"/>
									<span class="form_tips" style="color:red;">-1{pigcms{:L_("表示无限量。数量小于10时，商品详细页面会显示库存。")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("库存更新类型")}</label></label>
                                    <select name="update_stock_type" id="update_stock_type">
                                        <option value="0" <if condition="$now_goods['update_stock_type'] eq 0">selected</if>>{pigcms{:L_("每天更新")}</option>
                                        <option value="1" <if condition="$now_goods['update_stock_type'] eq 1">selected</if>>{pigcms{:L_("固定不变")}</option>
                                    </select>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("商品排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_goods.sort|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商品状态")}</label></label>
									<select name="status" id="Food_status">
										<option value="1" <if condition="$now_goods['status'] eq 1">selected</if>>{pigcms{:L_("正常")}</option>
										<option value="0" <if condition="$now_goods['status'] eq 0">selected</if>>{pigcms{:L_("停售")}</option>
									</select>
								</div>
							</div>
							<div id="txtintro" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<input class="col-sm-1" size="20" name="number" id="number" type="text" value="{pigcms{$now_goods.number}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="update_stock_type"><label>{pigcms{:L_("参与服务费")}</label></label>
									<select name="join_table_service" id="join_table_service">
										<option value="1" <if condition="$now_goods['join_table_service'] eq 1">selected</if>>参与</option>
										<option value="0" <if condition="$now_goods['join_table_service'] eq 0">selected</if>>不参与</option>
									</select>
								</div>
							
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否必点：")}</label></label>
									<label><input type="radio" name="is_must" value="0" <if condition="$now_goods['is_must'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="is_must" value="1" <if condition="$now_goods['is_must'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
									<span class="form_tips" style="color:red;">{pigcms{:L_("必点是在菜单中看不到的，用户下单是按照用餐人数来算份数，如餐具。推荐商品页面中不会显示必点菜。")}</span>
								</div>
							
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("必点菜店员是否可操作：")}</label></label>
									<label><input type="radio" name="operate_must_goods" value="0" <if condition="$now_goods['operate_must_goods'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="operate_must_goods" value="1" <if condition="$now_goods['operate_must_goods'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
									<span class="form_tips" style="color:red;">{pigcms{:L_("即移动端或pc端店员是否可对必点菜进行增减等操作")}</span>
								</div>
							
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否面客：")}</label></label>
									<label><input type="radio" name="is_show" value="0" <if condition="$now_goods['is_show'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="is_show" value="1" <if condition="$now_goods['is_show'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
									<span class="form_tips" style="color:red;">{pigcms{:L_("若设置为“非面客”则只在店员中心的右侧的点菜菜单中出现（不在用户展示），默认是面客的。")}</span>
								</div>
							
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("是否推荐：")}</label></label>
									<label><input type="radio" name="is_hot" value="0" <if condition="$now_goods['is_hot'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="is_hot" value="1" <if condition="$now_goods['is_hot'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
								    <span class="form_tips" style="color:red;">{pigcms{:L_("确定推荐后，此商品会出现在前台点餐页面指定推荐位置供用户查看")}</span>
                                </div>
                                <if condition="$config['is_open_merchant_discount'] eq 1 AND $merchant['is_discount'] eq 1 AND $merchant['discount_percent'] gt 0">
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("是否参与商家折扣：")}</label></label>
                                    <label><input type="radio" name="is_discount" value="0" <if condition="$now_goods['is_discount'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
                                    <label><input type="radio" name="is_discount" value="1" <if condition="$now_goods['is_discount'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips" style="color:red;">{pigcms{:L_("设置参与，则该菜品在结算的时候会享受商家折扣")}</span>
                                </div>
                                </if>

                                <if condition="$config['is_open_merchant_foodshop_discount'] eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label>{pigcms{:L_("是否参与商家桌台折扣：")}</label></label>
                                        <label><input type="radio" name="is_table_discount" value="0" <if condition="$now_goods['is_table_discount'] eq 0">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
                                        <label><input type="radio" name="is_table_discount" value="1" <if condition="$now_goods['is_table_discount'] eq 1">checked="checked"</if>>&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
                                        <span class="form_tips" style="color:red;">{pigcms{:L_("设置参与，则该菜品在结算的时候会享受商家桌台折扣")}</span>
                                    </div>
                                </if>
								
								<if condition="$print_list">
								<div class="form-group">
									<label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("归属打印机")}</label></label>
									<if condition="$config.open_foodshop_print_type == 1">
										<volist name="print_list" id="print">
										<label><input type="checkbox" name="print_id[]" value="{pigcms{$print['pigcms_id']}" <if condition="in_array($print['pigcms_id'],$now_goods['print_id'])">checked</if>>	{pigcms{$print['name']}</label>
										</volist>
										<span class="form_tips" style="color:red;">{pigcms{:L_("如果选择了非主打印机的话，那么客户在下单的时候选择的打印机和主打印机同时打印，如果不选打印机或是选择了主打印机的话，那么就主打印机打印；支持多选。")}</span>
									<else/>
										<select name="print_id" id="print_id">
											<option value="0" selected>{pigcms{:L_("选择打印机")}</option>
											<volist name="print_list" id="print">
											<option value="{pigcms{$print['pigcms_id']}" <if condition="$print['pigcms_id'] eq $now_goods['print_id']">selected</if>>{pigcms{$print['name']}</option>
											</volist>
										</select>
										<span class="form_tips" style="color:red;">{pigcms{:L_("如果选择了一台非主打印机的话，那么客户在下单的时候选择的打印机和主打印机同时打印，如果不选打印机或是选择了主打印机的话，那么就主打印机打印")}</span>
									</if>
								</div>
								</if>
                                
                                <if condition="$labels">
                                <div class="form-group">
                                    <label class="col-sm-1" for="Food_status"><label>{pigcms{:L_("商品标签")}</label></label>
                                    <select name="label" id="label">
                                        <option value="" selected>{pigcms{:L_("无标签")}</option>
                                        <volist name="labels" id="label">
                                        <option value="{pigcms{$label['name']}" <if condition="$label['name'] eq $now_goods['label']">selected</if>>{pigcms{$label['name']}</option>
                                        </volist>
                                    </select>
                                </div>
                                </if>
								<div class="form-group" >
									<label class="col-sm-1"><label>{pigcms{:L_("商品描述：")}</label></label>
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
									<p class="add_properties"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success">{pigcms{:L_("添加属性")}</a></p>
								</div>
								
								<div class="topic_box">
									<p class="add_table_foodshop" <if condition="!$now_goods['spec_list']">style="display:none"</if>><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success" >{pigcms{:L_("生成规格关系")}</a></p>
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("商品条形码")}</th>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>
											<th>{pigcms{:L_("原价")}</th><th>{pigcms{:L_("现价")}</th><th style="display:none">{pigcms{:L_("限时价")}</th><th>{pigcms{:L_("当前库存")}</th><th>{pigcms{:L_("原始库存")}</th>
											<volist name="now_goods['properties_list']" id="gp">
											<th>{pigcms{$gp['name']}({pigcms{:L_("可选个数")})</th>
											</volist>
										</tr>
										
										<volist name="now_goods['list']" id="gl" key="num">
											<tr id="{pigcms{$gl['index']}">
												<td><input type="text" class="txt" name="numbers[]" value="{pigcms{$gl['number']}" style="width:100%;"></td>
												<volist name="gl['spec']" id="g">
												<td>{pigcms{$g['spec_val_name']}</td>
												</volist>
												
												<td><input type="text" class="txt" name="old_prices[]" value="{pigcms{$gl['old_price']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="prices[]" value="{pigcms{$gl['price']}" style="width:80px;"></td>
												<td style="display:none;"><input type="text" class="txt" name="seckill_prices[]" value="{pigcms{$gl['seckill_price']}" style="width:80px;display:none"></td>
												<td><input type="text" class="txt" name="stock_nums[]" value="{pigcms{$gl['stock_num']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="original_stocks[]" value="{pigcms{$gl['original_stock']}" style="width:80px;"></td>
												
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
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品限时价")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_price" id="seckill_price" type="text" value="{pigcms{$now_goods.seckill_price|default=0}"/>
									<span class="form_tips">0{pigcms{:L_("表示无限时价。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价库存")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_stock" id="seckill_stock" type="text" value="{pigcms{$now_goods.seckill_stock|default=-1}"/>
									<span class="form_tips">-1{pigcms{:L_("表示无限量。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价类型")}</label></label>
									<span><label><input id='seckill_type0' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("固定时间段")}</span>&nbsp;</label></span>
									<span><label><input id='seckill_type1' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("每天的时间段")}</span></label></span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时段")}</label></label>
									<div>
										<input id="goods_seckill_open_time" type="text" value="{pigcms{$now_goods['seckill_open_time']|date='Y-m-d H:i',###}" name="seckill_open_time" readonly/>	{pigcms{:L_("至")} 
										<input id="goods_seckill_close_time" type="text" value="{pigcms{$now_goods['seckill_close_time']|date='Y-m-d H:i',###}" name="seckill_close_time" readonly/>
										<div class="errorMessage" id="Config_shop_start_time_em_" style="display:none"></div>
										<div class="errorMessage" id="Config_shop_stop_time_em_" style="display:none"></div>
									</div>
								</div>
							</div>
							<if condition="!empty($_SESSION['system'])">
							<div id="score" class="tab-pane">

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("消费1元所获得积分")}</label></label>
									<div>
										<input name="score_percent" type="text" value="{pigcms{$now_goods.score_percent}" /><b style="color:red">{pigcms{:L_("请填写一个数字")}</b>
									</div>
								</div>
							</div>
							<div id="take_a_percentage" class="tab-pane">

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("抽成比例")}</label></label>
									<div>
										<input name="take_a_percentage" type="text" value="{pigcms{$now_goods.take_a_percentage}" /><b style="color:red">{pigcms{:L_("请填写一个0-100的数字")}</b>
									</div>
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
var uploaderHas = false;
setTimeout(function(){
	var  uploader = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Foodshop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}",
			pick: {
				id:'#J_selectImage',
				multiple:false
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/*'
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
	
},20);
uploaderHas = true;

var formathtml = new Array();
var format_value = new Array();
var json = '{pigcms{$now_goods['json']}';
var session_index = 'foodshop_goods_{pigcms{$now_sort["sort_id"]}';
var diyVideo = "{pigcms{:U('Article/diyVideo')}";
var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=group/content";
var cssPath = "{pigcms{$static_path}css/group_editor.css";
var upload_image = "{pigcms{:U('Foodshop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
function deleteImage(path,obj){
	$.post("{pigcms{:U('Foodshop/ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
window.sessionStorage.setItem(session_index, json);
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js"></script>
<include file="Public:footer"/>
