<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Foodshop/goods_sort', array('store_id' => $now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
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
								<a href="{pigcms{:U('Foodshop/sort_edit', array('sort_id' => $now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
							</li>
						</ul>
					</div>
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("分类名称")}</label></label>
									<input class="col-sm-2" size="20" name="sort_name" id="sort_name" type="text" value="{pigcms{$now_sort.sort_name}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("分类排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_sort.sort|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
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
										 <span class="form_tips" style="color:red">{pigcms{:L_("全不选择与全选均代表用户前台一周都显示该商品信息。")}</span>
									</div>
								</div>
                                
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("显示时段")}</label>
                                    <div>
                                        <input id="show_start_time" type="text" value="{pigcms{:substr($now_sort['show_start_time'], 0, -3)}" name="show_start_time" readonly style="width:70px"/>   至
                                        <input id="show_end_time" type="text" value="{pigcms{:substr($now_sort['show_end_time'], 0, -3)}" name="show_end_time" readonly style="width:70px"/>
                                        <span class="form_tips" style="color: red;">{pigcms{:L_("不填或都填写00:00表示24小时都显示")}</span>
                                    </div>
                                </div>
								<!--div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("分类下产品折扣率")}</label></label>
									<input class="col-sm-1" size="10" name="sort_discount" id="sort_discount" type="text" value="{pigcms{$now_sort.sort_discount|default='0'}"/>
									<span class="form_tips" style="color:red">{pigcms{:L_("请填写0~100之间的整数，0和100都是表示无折扣，98表示9.8折")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="unit">{pigcms{:L_("分类图片")}</label></label>
									<span class="col-sm-2" style="padding-left:0px;">
										<input id="ytimage-file" type="hidden" value="" name="image"/>
										<input class="col-sm-1" id="image-file" size="200" onchange="previewimage(this)" name="image1" type="file"/>
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
								<if condition="$print_list">
								<div class="form-group">
									<label class="col-sm-1" for="Food_status">{pigcms{:L_("是否开启分类打印机")}</label>
									<select name="is_open_print" id="is_open_print">
										<option value="0" selected>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$now_sort['is_open_print'] eq 1">selected</if>>{pigcms{:L_("打开")}</option>
									</select>
									<span class="form_tips" style="color:red;">{pigcms{:L_("开启，则一菜一单的打印规则按照该分类归属的打印模式来；关闭则表示一菜一单的打印规则还是按照每个菜品自己归属的打印机模式来")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="Food_status">{pigcms{:L_("归属打印机")}</label>
									<if condition="$config.open_foodshop_print_type == 1">
										<volist name="print_list" id="print">
										<label><input type="checkbox" name="print_id[]" value="{pigcms{$print['pigcms_id']}" <if condition="in_array($print['pigcms_id'],$now_sort['print_id'])">checked</if>>	{pigcms{$print['name']}</label>
										</volist>
										<span class="form_tips" style="color:red;">{pigcms{:L_("只有开启分类打印机功能且该分类归属了打印机，才可正常使用分类下商品的一菜一单打印功能；如果选择了非主打印机的话，那么客户在下单的时候选择的打印机和主打印机同时打印，如果不选打印机或是选择了主打印机的话，那么就主打印机打印；支持多选。")}</span>
									<else/>
										<select name="print_id" id="print_id">
											<option value="0" selected>{pigcms{:L_("选择打印机")}</option>
											<volist name="print_list" id="print">
											<option value="{pigcms{$print['pigcms_id']}" <if condition="$print['pigcms_id'] eq $now_sort['print_id']">selected</if>>{pigcms{$print['name']}</option>
											</volist>
										</select>
										<span class="form_tips" style="color:red;">{pigcms{:L_("只有开启分类打印机功能且该分类归属了打印机，才可正常使用分类下商品的一菜一单打印功能；如果选择了一台非主打印机的话，那么客户在下单的时候选择的打印机和主打印机同时打印，如果不选打印机或是选择了主打印机的话，那么就主打印机打印")}</span>
									</if>
								</div>
								</if>

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
<script>
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
	$('#show_start_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#show_end_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
	
});
function previewimage(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) {$('#image_preview_box').html('<img style="width:120px;height:120px" src="'+e.target.result+'" alt="{pigcms{:L_("图片预览")}" title="{pigcms{:L_("图片预览")}"/>');}
		reader.readAsDataURL(input.files[0]);
	}
}
</script>
<include file="Public:footer"/>