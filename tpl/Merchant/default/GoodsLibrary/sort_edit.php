<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('GoodsLibrary/goods_list',['store_id'=>$now_store['store_id']])}">{pigcms{:L_("商品库管理")}</a>			</li>
			</li>
			<li class="active"><a href="{pigcms{:U('GoodsLibrary/goods_sort_list',array('store_id'=>$now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
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
								<a href="{pigcms{:U('GoodsLibrary/sort_edit',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
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
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("排序")}</label></label>
									<input class="col-sm-1" size="10" name="sort" id="sort" type="text" value="{pigcms{$now_sort.sort|default='0'}"/>
									<span class="form_tips">{pigcms{:L_("默认添加顺序排序！手动调值，数值越大，排序越前")}</span>
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
    var ajax_goods_properties = "{pigcms{:U('GoodsLibrary/ajax_goods_properties')}";
    var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';

    var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=GoodsLibrary/content";

    var cssPath = "{pigcms{$static_path}css/group_editor.css";
    var upload_image = "{pigcms{:U('GoodsLibrary/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
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
