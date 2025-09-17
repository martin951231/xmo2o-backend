<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
			<li class="active">{pigcms{:L_("编辑分类")}</li>
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
								<a href="{pigcms{:U('Mall/sort_edit',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
							</li>
						</ul>
					</div>
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" value="{pigcms{$now_sort.sort_id}" id="goods_id" />
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("分类名称")}</label></label>
									<input class="col-sm-2" size="20" name="sort_name" id="sort_name" type="text" value="{pigcms{$now_sort.sort_name}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("上级分类")}</label></label>
									<fieldset id="choose_sort" style="display: inline-block;"></fieldset>
									<span class="form_tips">{pigcms{:L_("如果不选择上级分类，则新增的分类为对应的顶级分类；如果选择上级分类，那么新增的分类则为被选择上级分类的子分类")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("店铺排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_sort.sort|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
								</div>
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1" for="is_weekshow">{pigcms{:L_("开启星期几显示")}</label>-->
<!--									<select name="is_weekshow" id="is_weekshow">-->
<!--										<option value="0" <if condition="$now_sort['is_weekshow'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>-->
<!--										<option value="1" <if condition="$now_sort['is_weekshow'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>-->
<!--									</select>-->
<!--								</div>-->
								<div class="form-group">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("星期几显示")}</label>
									<div class="col-sm-10" style="margin-top:5px;">
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="1" name="week[]" <if condition="in_array('1',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期一")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="2" name="week[]" <if condition="in_array('2',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期二")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="3" name="week[]" <if condition="in_array('3',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期三")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="4" name="week[]" <if condition="in_array('4',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期四")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="5" name="week[]" <if condition="in_array('5',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期五")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="6" name="week[]" <if condition="in_array('6',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期六")}</label>&nbsp;&nbsp;
										</div>
										<div style="width:80px;float:left;font-size:16px;">
											<label><input type="checkbox" value="0" name="week[]" <if condition="in_array('0',$now_sort['week'])">checked="checked"</if>/>{pigcms{:L_("星期日")}</label>&nbsp;&nbsp;
										</div>
									</div>
								</div>
								<div class="form-group" style="display:none;">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("分类下产品折扣率")}</label></label>
									<input class="col-sm-1" size="10" name="sort_discount" id="sort_discount" type="text" value="{pigcms{$now_sort.sort_discount|default='0'}"/>
									<span class="form_tips" style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</span>
								</div>
								<!--div class="form-group">
									<label class="col-sm-1"><label for="unit">{pigcms{:L_("分类图片")}</label></label>
									<span class="col-sm-2" style="padding-left:0px;">
										<input id="ytimage-file" type="hidden" value="" name="image"/>
										<input class="col-sm-1" id="image-file" size="200" onchange="previewimage(this)" name="image" type="file"/>
									</span>
									<span class="form_tips" style="color:red;">{pigcms{:L_("可不填。（图片文件大小不能超过")}{pigcms{$config.meal_pic_size}M,{pigcms{:L_("建议上传大尺寸的图片。） 图片宽度建议为50px，高度建议为")}50px</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择图片")}</label>
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('image_preview_box','goods_sort')">{pigcms{:L_("选择图片")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="unit">{pigcms{:L_("分类图片")}</label></label>
									<span id="image_preview_box">
										<if condition="$now_sort['see_image']">
											<img src="{pigcms{$now_sort.see_image}" style="width:120px;height:120px"/>
										</if>
									</span>
								</div-->

								<if condition="$print_list AND empty($sort)">
								<div class="form-group">
									<label class="col-sm-1" for="Food_status">{pigcms{:L_("归属打印机")}</label>
									<select name="print_id" id="print_id">
										<option value="0">{pigcms{:L_("选择打印机")}</option>
										<volist name="print_list" id="print">
										<option value="{pigcms{$print['pigcms_id']}" <if condition="$print['pigcms_id'] eq $now_sort['print_id']">selected</if>>{pigcms{$print['name']}</option>
										</volist>
									</select>
									<span class="form_tips" style="color:red;"> {pigcms{:L_("选择归属打印机，则该打印机将打印该分类的小票；不选择打印机，则该打印机不会打印小票")}</span>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1" for="is_mall_config">{pigcms{:L_("商城属性配置")}</label>
									<select name="is_mall_config" id="is_mall_config">
										<option value="0" <if condition="$now_sort['is_mall_config'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$now_sort['is_mall_config'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
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
										<label class="col-sm-1">{pigcms{:L_("运费模版")}</label>
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
										<a href="{pigcms{:U('Express/add')}">+{pigcms{:L_("新建")}</a>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("其他区域运费")}</label>
										<div><input name="freight_value" type="text" value="{pigcms{$mall_goods_config.freight_value|floatval}" /></div>
									</div>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("运费计算方式")}</label>
										<span><label><input name="freight_type" <if condition="$mall_goods_config['freight_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("按最大值算")}</span>&nbsp;</label></span>
										<span><label><input name="freight_type" <if condition="$mall_goods_config['freight_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("单独计算")}</span></label></span>
									</div>

									<div class="form-group">
										<label class="col-sm-1" for="Food_status">{pigcms{:L_("商品分类")}</label>
										<fieldset id="choose_category" cat_fid="{pigcms{$mall_goods_config.cat_fid}" cat_id="{pigcms{$mall_goods_config.cat_id}"></fieldset>
									</div>

									<!-- {pigcms{:L_("增加商品归属限制")} -->
									<div class="form-group" id="mall_box">
										<label class="col-sm-1">{pigcms{:L_("该商品是否只属于商城")}</label>
									<span>
										<label><input name="goods_type" <if condition="$mall_goods_config['goods_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label>
									</span>
									<span>
										<label><input name="goods_type" <if condition="$mall_goods_config['goods_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("是")}</span></label>
									</span>
										<span class="form_tips">{pigcms{:L_("选择是，则该商品只会在商城中显示不会在X1中显示，且该商品的所有的订单也只属于商城。",['X1'=>$config['shop_alias_name']])}</span>
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js?t=122"></script>
<script>
	var json = '{pigcms{$now_goods['
    json
    ']}', sortList = '{pigcms{$sort_list_json}', selectIds = '{pigcms{$select_ids}';
    var ajax_goods_properties = "{pigcms{:U('Shop/ajax_goods_properties')}";
    var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';

    var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=shop/content";

    var cssPath = "{pigcms{$static_path}css/group_editor.css";
    var upload_image = "{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
$(function(){
	/*{pigcms{:L_("调整保存按钮的位置")}*/
	$(".nav-tabs li a").click(function(){
		if($(this).attr("href")=="#imgcontent"){		//"店铺图片")}
			$(".form-submit-btn").css('position','absolute');
			$(".form-submit-btn").css('top','670px');	
		}else{
			$(".form-submit-btn").css('position','static');
		}
	});

	$('form.form-horizontal').submit(function(){
		$(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
	});
	/*{pigcms{:L_("分享图片")}*/
	$('#image-file').ace_file_input({
		no_file:'gif|png|jpg|jpeg{pigcms{:L_("格式")}',
		btn_choose:'{pigcms{:L_("选择")}',
		btn_change:'{pigcms{:L_("重新选择")}',
		no_icon:'fa fa-upload',
		icon_remove:'',
		droppable:false,
		onchange:null,
		remove:false,
		thumbnail:false
	});
});

function previewimage(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) {$('#image_preview_box').html('<img style="width:120px;height:120px" src="'+e.target.result+'" alt="{pigcms{:L_('图片预览')}"/>');}
		reader.readAsDataURL(input.files[0]);
	}
}
var category_list = '{pigcms{$category_list}', ajax_goods_properties = "{pigcms{:U('Mall/ajax_sort_goods_properties')}";
$("#is_mall_config").change(function(){
	var mall_config_show =$("#is_mall_config option:selected").val();
	show_category(mall_config_show);
})
function show_category(show){
	if(show==1){
		$("#category").show();
	}else{
		$("#category").hide();
	}
}
show_category($("#is_mall_config option:selected").val());

</script>

<include file="Public:footer"/>
