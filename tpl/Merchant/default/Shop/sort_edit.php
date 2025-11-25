<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Shop/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
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
								<a href="{pigcms{:U('Shop/sort_edit',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
							</li>
						</ul>
					</div>
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
							    <if condition="$sort">
								<!-- <div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("父分类名称")}</label></label>
									<input name="fid" type="hidden" value="{pigcms{$sort['sort_id']}"/>
									<input type="text" value="{pigcms{$sort.sort_name}" disabled/>
								</div> -->
								</if>
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
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("分类副标题")}</label></label>
									<input class="col-sm-2" size="20" name="desc" id="desc" type="text" value="{pigcms{$now_sort.desc}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("分类商品限购")}</label></label>
									<input class="col-sm-1" size="10" name="category_limit_num" id="category_limit_num" type="text" value="{pigcms{$now_sort.category_limit_num|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("填写分类限购个，列如填写1，此分类每次下单，只能下单一个商品，填0则不可购买，填-1不限制。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("分类排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_sort.sort|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("是否是下单必点分类")}</label>
                                    <span><label><input name="is_must" <if condition="$now_sort['is_must'] eq 1 ">checked="checked"</if> value="1" type="radio"/>&nbsp;<span>{pigcms{:L_("是")}</span></label></span>
                                    <span><label><input name="is_must" <if condition="$now_sort['is_must'] eq 0 ">checked="checked"</if> value="0" type="radio"/>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label></span>
                                    <span class="form_tips" style="color:red">{pigcms{:L_("设置为必点分类后，用户必须选择必点分类中至少一个商品才能成功下单，每个店铺只能设置一个分类为下单必点分类")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1" for="status">{pigcms{:L_("是否是附属商品分类")}</label>
                                    <span><label><input id='seckill_type1' name="status" <if condition="$now_sort['status'] eq 0 ">checked="checked"</if> value="0" type="radio"/>&nbsp;<span>{pigcms{:L_("是")}</span></label></span>
                                    <span><label><input id='seckill_type0' name="status" <if condition="$now_sort['status'] eq 1 ">checked="checked"</if> value="1" type="radio"/>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label></span>
                                    <span class="form_tips" style="color:red">{pigcms{:L_("如果麻辣烫、奶茶、火锅等存在配菜、配料的情况，请您选中该分类为“附属菜品分类”。选中后，在附属菜品分类里创建的菜品，不会在用户端的餐厅菜品列表中显示。如果设置的是附属菜分类，则以当前设置的为准；如果设置的不是附属菜分类，则以上级分类设置的为准")}</span>
                                </div>
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
                                        <span class="form_tips" style="color:red">{pigcms{:L_("如果设置星期几显示，则以当前设置的为准；如果没有设置，则以上级分类设置的为准")}</span>
									</div>
								</div>
								<div class="form-group">
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
                                    <label class="col-sm-1" for="sort_status"><label>{pigcms{:L_("状态")}</label></label>
                                    <select name="sort_status" id="sort_status">
                                        <option value="1" <eq name="now_sort.sort_status" value="1">selected="selected"</eq>>{pigcms{:L_("开启")}</option>
                                        <option value="0" <eq name="now_sort.sort_status" value="0">selected="selected"</eq>>{pigcms{:L_("关闭")}</option>
                                    </select>
                                    <span class="form_tips" style="color:red;"> {pigcms{:L_("关闭则代表该分类不在用户前端展示，反之则展示；该选项的2个状态只针对不是附属菜的分类有效且只针对外卖业务的分类有效。")}</span>
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
		reader.onload = function (e) {$('#image_preview_box').html('<img style="width:120px;height:120px" src="'+e.target.result+'" alt="{pigcms{:L_("图片预览")}}/>');}
		reader.readAsDataURL(input.files[0]);
	}
}
</script>

<include file="Public:footer"/>
