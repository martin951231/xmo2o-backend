<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("模块")}</th>
								<th class="text-middle">{pigcms{:L_("涵盖店铺")}</th>
								<th class="text-middle">{pigcms{:L_("下载Excel数据")}<span style="color:red;">{pigcms{:L_("（下载前请先添加好数据）")}</span></th>
								<th class="text-middle" style="width: 500px;">{pigcms{:L_("导入Excel数据")}<span style="color:red;">{pigcms{:L_("（导入的表格必须是左边下载的表格）")}</span></th>
							</tr>
						</thead>
						<tbody>
							<tr>

                                <td>{pigcms{:L_("商户信息")}</td>
                                <td class="text-middle">----</td>
                                <td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'merchant'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
                                <td class="text-middle"><input type="file" id="merchant_file" data-name="merchant" /></td>
                            </tr>

							<tr>
								<td>{pigcms{:L_("店铺信息")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'merchant_store'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="merchant_store_file" data-name="merchant_store" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("X1商品",['X1'=>$config['group_alias_name']])}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'group'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="group_file" data-name="group" /></td>
							</tr>	
							<tr>
								<td>{pigcms{:L_("X1信息",['X1'=>$config['shop_alias_name']])}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'merchant_store_shop'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="merchant_store_shop_file" data-name="merchant_store_shop" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("X1商品",['X1'=>$config['shop_alias_name']])}</td>
								<td class="text-middle">
									<select name="store_select">
										<option value="">{pigcms{:L_("请选择")}</option>
										<volist name="store_list" id="vo">
											<option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
										</volist>
									</select>
								</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_goods_file" data-name="shop_goods" /></td>
							</tr>
                            <tr>
                                <td>{pigcms{:L_("X1附属商品组",['X1'=>$config['shop_alias_name']])}</td>
                                <td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
                                <td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods_subsidiary_group'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
                                <td class="text-middle"><input type="file" id="shop_goods_subsidiary_group_file" data-name="shop_goods_subsidiary_group" /></td>
                            </tr>
                            <tr>
                                <td>{pigcms{:L_("X1附属商品区块",['X1'=>$config['shop_alias_name']])}</td>
                                <td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
                                <td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_subsidiary_piece'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
                                <td class="text-middle"><input type="file" id="shop_subsidiary_piece_file" data-name="shop_subsidiary_piece" /></td>
                            </tr>
                            <!-- <tr>
                                <td>{pigcms{:L_("自提点")}</td>
                                <td class="text-middle">----</td>
                                <td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'pick_address	'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
                                <td class="text-middle"><input type="file" id="pick_address_file" data-name="pick_address" /></td>
                            </tr> -->
                            <tr>
								<td>{pigcms{:L_("商品属性")}</td>
								<td class="text-middle"><select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select></td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods_properties'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_goods_properties_file" data-name="shop_goods_properties" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("商品规格")}</td>
								<td class="text-middle"><select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select></td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods_spec'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_goods_spec_file" data-name="shop_goods_spec" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("商品规格值")}</td>
								<td class="text-middle"><select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select></td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods_spec_value'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_goods_spec_value_file" data-name="shop_goods_spec_value" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("商品分类")}</td>
								<td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_goods_sort'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_goods_sort_file" data-name="shop_goods_sort" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("会员卡分组")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'card_group'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="card_group_file" data-name="card_group" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("商家会员卡")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'card_new'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="card_new_file" data-name="card_new" /></td>
							</tr>
							
							<tr>
								<td>{pigcms{:L_("组合套餐")}</td>
								<td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'shop_combination'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="shop_combination_file" data-name="shop_combination" /></td>
							</tr>
							<tr>
								<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("桌台类型")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_table_type'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="foodshop_table_type_file" data-name="foodshop_table_type" /></td>
							</tr>
							<tr>
								<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("桌台列表")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_table'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="foodshop_table_file" data-name="foodshop_table" /></td>
							</tr>
                            <tr>
                                <td>{pigcms{:L_("套餐管理")}</td>
                                <td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
                                <td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_goods_package'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
                                <td class="text-middle"><input type="file" id="foodshop_goods_package_file" data-name="foodshop_goods_package" /></td>
                            </tr>
                            <tr>
								<td>{pigcms{:L_("套餐分组")}</td>
								<td class="text-middle">----</td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_goods_package_detail'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="foodshop_goods_package_detail_file" data-name="foodshop_goods_package_detail" /></td>
							</tr>
							<tr>
								<td>{pigcms{:L_("餐饮商品分类")}</td>
								<td class="text-middle">
                                    <select name="store_select">
                                        <option value="">{pigcms{:L_("请选择")}</option>
                                        <volist name="store_list" id="vo">
                                            <option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                </td>
								<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_goods_sort'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
								<td class="text-middle"><input type="file" id="foodshop_goods_sort_file" data-name="foodshop_goods_sort" /></td>
							</tr>
						  	<if condition="$config['open_meal_electronic_menu']">
								<tr>
									<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("自定义页面")}</td>
									<td class="text-middle">----</td>
									<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'merchant_store_foodshop_data'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
									<td class="text-middle"><input type="file" id="merchant_store_foodshop_data_file" data-name="merchant_store_foodshop_data" /></td>
								</tr>
								<tr>
									<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("自定义表单")}</td>
									<td class="text-middle">----</td>
									<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_customer_form'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
									<td class="text-middle"><input type="file" id="foodshop_customer_form_file" data-name="foodshop_customer_form" /></td>
								</tr>
								<tr>
									<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("店铺信息")}</td>
									<td class="text-middle">----</td>
									<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'merchant_store_foodshop'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
									<td class="text-middle"><input type="file" id="merchant_store_foodshop_file" data-name="merchant_store_foodshop" /></td>
								</tr>
								<tr>
									<td>{pigcms{$config.meal_alias_name}{pigcms{:L_("自定义标签")}</td>
									<td class="text-middle">----</td>
									<td class="text-middle"><a href="{pigcms{:U('Config/data_import_download',array('type'=>'foodshop_customer_label'))}" class="download_excel">{pigcms{:L_("下载表格")}</a></td>
									<td class="text-middle"><input type="file" id="foodshop_customer_label_file" data-name="foodshop_customer_label" /></td>
								</tr>
							</if>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/fileinput.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/locales/zh.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}bootstrap-fileinput-master/css/fileinput.css" />
<script type="text/javascript">
	initUpload("merchant_file","merchant");
	initUpload("merchant_store_file","merchant_store");
	initUpload("merchant_store_shop_file","merchant_store_shop");
	initUpload("shop_goods_file","shop_goods");
	initUpload("shop_goods_properties_file","shop_goods_properties");
	initUpload("shop_goods_spec_file","shop_goods_spec");
	initUpload("shop_goods_spec_value_file","shop_goods_spec_value");
	initUpload("shop_goods_subsidiary_group_file","shop_goods_subsidiary_group");
	initUpload("shop_subsidiary_piece_file","shop_subsidiary_piece");
	// initUpload("pick_address_file","pick_address");
	initUpload("shop_goods_sort_file","shop_goods_sort");
	initUpload("card_group_file","card_group");
	initUpload("card_new_file","card_new");
	initUpload("merchant_store_foodshop_data_file","merchant_store_foodshop_data");
	initUpload("foodshop_customer_form_file","foodshop_customer_form");
	initUpload("foodshop_table_type_file","foodshop_table_type");
	initUpload("foodshop_table_file","foodshop_table");
	initUpload("merchant_store_foodshop_file","merchant_store_foodshop");
	initUpload("foodshop_customer_label_file","foodshop_customer_label");	
	initUpload("shop_combination_file","shop_combination");
    initUpload("foodshop_goods_package_file","foodshop_goods_package");
    initUpload("foodshop_goods_package_detail_file","foodshop_goods_package_detail");
    initUpload("foodshop_goods_sort_file","foodshop_goods_sort");
    initUpload("group_file","group");
	function initUpload(ctrlId, type){
		if(type == ''){
			return ;
		}
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("data_import_upload")}',
			language: '<if condition="$config.system_lang eq 'english'">en<else />zh</if>', //设置语言
			showBrowse : true,
			showPreview : true,
			showRemove : true,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls'],
			uploadExtraData: function (e) {
				var store_id = _click.parents('tr').find("[name='store_select']").find(":selected").val();
				var param = {
					"type" : type
				}
				if(typeof store_id !== 'undefined'){
					param.store_id = store_id;
				}
	            return param;
	        }
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info);
	        	//关闭
	            $(".close").click();
	        }
	        else{
	            layer.msg(data.files[index].name + "{pigcms{:L_("导入失败")} " + data.response.info, {icon: 5});
		        //重置
		        $("#fileupload").fileinput("clear");
		        $("#fileupload").fileinput("reset");
		        $('#fileupload').fileinput('refresh');
		        $('#fileupload').fileinput('enable');
		        $(".close").click();
	        }
	    });
	}

	var _click = null;
	$(function(){
		$("[type='file']").bind("click",function(){
			_click = $(this);
			var _val = _click.parents('tr').find("[name='store_select']").find(":selected").val();
			if(typeof _val != 'undefined' && _val == ''){
				layer.tips('{pigcms{:L_("你还没有选择店铺哦")}', _click.parents('tr').find("[name='store_select']"));
				return false;
			}
		})
		$(".download_excel").bind("click",function(){
			_click = $(this);
			var _val = _click.parents('tr').find("[name='store_select']").find(":selected").val();
			if(typeof _val != 'undefined' && _val == ''){
				layer.tips('{pigcms{:L_("你还没有选择店铺哦")}', _click.parents('tr').find("[name='store_select']"));
				return false;
			}
			else
				if(_val != ''){
					var href = $(this).attr('href');
					href += "&store_id="+_val;
					$(this).attr('href',href);
				}			
		})
	})
</script>
