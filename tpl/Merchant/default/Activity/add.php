<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-empire"></i>
				{pigcms{:L_("商家推广")}
			</li>
			<li class="active"><a href="{pigcms{:U('Activity/index')}">{pigcms{:L_("平台活动列表")}</a></li>
			<li class="active">{pigcms{:L_("添加活动")}</li>
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
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
								<div class="form-group">
									<label class="col-sm-1"><label for="phone"><span class="required" style="color:red;">*</span>{pigcms{:L_("参与活动期数")}</label></label>
									<select name="activity_term" id="activity_term" class="col-sm-3">
										<volist name="activity_term_list" id="vo">
											<option value="{pigcms{$vo.activity_id}" city_id="{pigcms{$vo.city_id}" >{pigcms{$vo.name} | {pigcms{:L_("活动时间（")}{pigcms{$vo.begin_time|date='Y-m-d H:i',###} {pigcms{:L_("至")} {pigcms{$vo.end_time|date='Y-m-d H:i',###}）</option>
										</volist>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动名称")}</label></label>
									<input type="text" class="col-sm-3" name="name" value="" />
									<span class="form_tips">{pigcms{:L_("不超过50个字！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动标题")}</label></label>
									<input type="text" class="col-sm-3" name="title" value="" />
									<span class="form_tips">{pigcms{:L_("简短的描述一下您的活动")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动类型")}</label></label>
									<select id="activity_type" name="type" class="col-sm-1">
										<option value="2">{pigcms{:L_("优惠券")}</option>
									</select>
								</div>
								<div class="form-group" id="address_info">
									<label class="col-sm-1"><label for="phone"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动地址")}</label></label>
									<fieldset id="choose_cityarea"></fieldset>
								</div>
								<div id="product_price">
									<div class="form-group">
										<label class="col-sm-1"><label for="price">{pigcms{:L_("商品价格")}</label></label>
										<input class="col-sm-1" size="10" name="price" id="price" type="text" value=""/>
										<span class="form_tips">{pigcms{:L_("单位")}{pigcms{$config['Currency_txt']}{pigcms{:L_("，不能有小数。即达到抽奖的购买次数。活动开始后不能修改。")}</span>
									</div>
								</div>
					
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("上传图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！")}<php>if(!empty($config['activity_pic_width'])){$activity_pic_width=explode(',',$config['activity_pic_width']);echo '{pigcms{:L_("图片宽度建议为：")}'.$activity_pic_width[0].'px，';}</php><php>if(!empty($config['activity_pic_height'])){$activity_pic_height=explode(',',$config['activity_pic_height']);echo '{pigcms{:L_("高度建议为：")}'.$activity_pic_height[0].'px';}</php></span>
								</div>
								<div id="image_div" style="display:none;">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
										<div id="upload_pic_box">
											<ul id="upload_pic_ul"></ul>
										</div>
									</div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="info">{pigcms{:L_("活动详情")}</label></label>
									<textarea  class="col-sm-3" id="info" name="info" style="height:125px" ></textarea>
								</div>				
								<div class="form-group">
									<label class="col-sm-1"><label for="sort"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动商品数量")}</label></label>
									<input class="col-sm-1" size="10" name="all_count" id="all_count" type="text" value="1" readonly="readonly"/>
									<span class="form_tips">{pigcms{$config.activity_score_alias}{pigcms{:L_("只能设置一份")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="canrqnums"><span class="required" style="color:red;">*</span>{pigcms{:L_("参与活动限制")}</label></label>
									<div class="radio">
										<label>
											<input name="activity_limit" value="1" type="radio" class="ace" checked="checked"/>
											<span class="lbl" style="z-index: 1">{pigcms{:L_("有偿（金钱）")}</span>
										</label>
										<label>
											<input name="activity_limit" value="0" type="radio" class="ace" />
											<span class="lbl" style="z-index: 1">{pigcms{:L_("无偿")}（{pigcms{$config.score_name}）</span>
										</label>
									</div>
								</div>
								<div id="limit_money">
									<div class="form-group">
										<label class="col-sm-1"><label for="money">{pigcms{:L_("消耗金钱")}</label></label>
										<input class="col-sm-1" size="10" name="money" id="money" type="text" value="1" readonly="readonly"/>
										<span class="form_tips">{pigcms{$config.activity_score_alias}{pigcms{:L_("只能设置为 一元！只能整数")}</span>
									</div>
								</div>
								<div id="limit_score" style="display:none;">
									<div class="form-group">
										<label class="col-sm-1"><label for="mer_score">{pigcms{:L_("商家")}{pigcms{$config.score_name}</label></label>
										<input class="col-sm-1" size="10" name="mer_score" id="mer_score" type="text" value=""/>
										<span class="form_tips">{pigcms{:L_("备注：用户消耗商家")}{pigcms{$config.score_name}{pigcms{:L_("除以")} {pigcms{$config.activity_score_scale} {pigcms{:L_("的平台")}{pigcms{$config.score_name} {pigcms{:L_("也可参与活动")}</span>
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
							</form>
						</div>
					</div>
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
</style>
<!--<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>-->
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript">
	var choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}";
	function _show_address(){
		var city_id = $("#activity_term").find("option:selected").attr("city_id");
		city_id = parseInt(city_id);
		if(city_id>0){
			$('#address_info').show();
			show_area(city_id,'',1);
		}else{
			$('#address_info').hide();
		}
	}
	
	//显示区域
	function show_area(id,name,type){
		$.post(choose_area,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_area" name="area_id" class="col-sm-1" style="margin-right:10px;">';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_cityarea').attr('area_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_area')){
					$('#choose_area').replaceWith(area_dom);
				}else if(document.getElementById('choose_city')){
					$('#choose_city').after(area_dom);
				}else{
					$('#choose_cityarea').prepend(area_dom);
				}
				show_circle($('#choose_area').find('option:selected').attr('value'),$('#choose_area').find('option:selected').html(),1);
				$('#choose_area').change(function(){
					show_circle($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
				});
			}else{
				alert(result.info);
			}
		});
	}
	function show_circle(id,name,type){
		$.post(choose_circle,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_circle" name="circle_id" class="col-sm-1" style="margin-right:10px;">';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_cityarea').attr('circle_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_circle')){
					$('#choose_circle').replaceWith(area_dom);
				}else if(document.getElementById('choose_area')){
					$('#choose_area').after(area_dom);
				}else{
					$('#choose_cityarea').prepend(area_dom);
				}
				
				if(result.list.length == 1){
					$('#choose_circle').hide();
				}
					
				show_market($('#choose_circle').find('option:selected').attr('value'),$('#choose_circle').find('option:selected').html(),1);
				$('#choose_circle').change(function(){
					show_market($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
				});

				$('#choose_cityarea').removeAttr('province_id city_id area_id circle_id');
			}else{
				$('#choose_circle').hide();
				alert(result.info);
			}
		});
	}
	//显示商场
	function show_market(id,name,type){
	        if(typeof(choose_market) != 'undefined' && choose_market!=''){
			if(document.getElementById('choose_market')){
				$('#choose_market').html('<option value="0">正在获取中..</option>');
			}
			$.post(choose_market,{id:id,name:name,type:type},function(result){
				result = $.parseJSON(result);
				if(result.error == 0){
					var area_dom = '<select id="choose_market" name="market_id" class="col-sm-1" style="margin-right:10px;">';
					/*area_dom+= '<option value="0">'+L_('选择商圈')+'</option>'; */
					$.each(result.list,function(i,item){
						area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_cityarea').attr('market_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
					});
					area_dom+= '</select>';
					if(document.getElementById('choose_market')){
						$('#choose_market').replaceWith(area_dom);
					}else if(document.getElementById('choose_circle')){
						$('#choose_circle').after(area_dom);
					}
					$('#choose_circle').show();
				}else{
					$('#choose_market').remove();
	//				show_market($('#choose_area').find('option:first').attr('value'),$('#choose_area').find('option:first').html(),1);
	//				alert(result.info);
				}
				$('#choose_cityarea').removeAttr('market_id');
			});
		}
	}
	_show_address();
	$('#activity_term').change(function(){
		_show_address();
	});
</script>
<script type="text/javascript">
KindEditor.ready(function(K) {
	var content_editor = K.create("#info",{
		width:'702px',
		height:'260px',
		langType : "{pigcms{$config.system_lang_alias}",
		resizeType : 1,
		allowPreviewEmoticons:false,
		allowImageUpload : true,
		filterMode: true,
		autoHeightMode : true,
		afterCreate : function() {
			this.loadPlugin('autoheight');
		},
		items : [
			'fullscreen', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'link', 'table'
		],
		emoticonsPath : './static/emoticons/',
		uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=activity/content",
		cssPath : "{pigcms{$static_path}css/group_editor.css"
	});
	
	var editor = K.editor({
		allowFileManager : true
	});
	K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Activity/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					$('#image_div').show();
					editor.hideDialog();
				}
			});
		});
	});
	
	$('#activity_type').change(function(){
		if($(this).val() == 1){
			$('#all_count,#money').val('1').prop('readonly',true);
			$('input[name="activity_limit"][value="1"]').prop('checked',true);
			$('#limit_score').hide();
			$('#limit_money').show();
			$('#product_price').show();
		}else{
			$('#all_count,#money').prop('readonly',false);
			$('#product_price').hide();
		}
	});
	$('#activity_type').trigger('change');
	
	$('input[name="activity_limit"]').change(function(){
		if($('input[name="activity_limit"]:checked').val() == 1){
			$('#limit_score').hide();
			$('#limit_money').show();
		}else{
			$('#limit_money').hide();
			$('#limit_score').show();
		}
	});
	
	$('#add_form').submit(function(){
		content_editor.sync();
		$('#save_btn').prop('disabled',true);
		$.post("{pigcms{:U('Activity/add')}",$('#add_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Activity/index')}";
			}else{
				alert(result.info);
			}
			$('#save_btn').prop('disabled',false);
		})
		return false;
	});
});
function deleteImage(path,obj){
	$(obj).closest('.upload_pic_li').remove();
}
</script>
<include file="Public:footer"/>