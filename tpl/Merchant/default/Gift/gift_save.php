<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		*{list-style: none;}
		#upload_pic_box{overflow: auto;}
		#upload_pic_box .upload_pic_li,.wap_upload_pic_li{width:130px;float:left;list-style:none;display: inline-block;}
		#upload_pic_box img{width:100px;height:70px;}
		#upload_pic_ul,#wap_upload_pic_ul{overflow: auto;margin: 0;}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Gift/index')}">{pigcms{:L_("商家")}{pigcms{$config.gift_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("礼品列表添加")}/{pigcms{:L_("编辑")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<input type="hidden" name='gift_id' value="{pigcms{$info.gift_id}"/>
						<div class="tab-content">				
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("礼品名称")}</label>
									<input class="col-sm-2" size="20" maxlength="20" name="gift_name" type="text" value="{pigcms{$info.gift_name}" />
									<span class="form_tips"></span>
								</div>
								<div class="form-group" style="display:none;">
									<label class="col-sm-1"><label>{pigcms{:L_("礼品兑换类别")}</label></label>
									<select name="exchange_type" class="col-sm-2">
										<option value="0" <if condition='$info["exchange_type"] eq 0'>selected="selected"</if>>纯{pigcms{$config['score_name']}</option>
										<option value="1" <if condition='$info["exchange_type"] eq 1'>selected="selected"</if>>{pigcms{$config['score_name']}+{pigcms{:L_("余额")}</option>
										<option value="2" <if condition='$info["exchange_type"] eq 2'>selected="selected"</if>>{pigcms{:L_("不限")}</option>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<div class="form-group">
									<!-- <label class="col-sm-1">纯{pigcms{$config['score_name']}{pigcms{:L_("类型")}</label> -->
									<label class='col-sm-1' >{pigcms{:L_("所需")}{pigcms{$config['score_name']}</label>
									<input class="col-sm-1" size="20" maxlength="20" name="payment_pure_integral" type="text" value="{pigcms{$info.payment_pure_integral}" />
									<span class="form_tips"></span>
								</div>								
								<!-- {pigcms{:L_("选择分类")} -->
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("选择分类")}</label></label>
									<select id="choose_catfid" name="cat_fid" class="col-sm-1" style="margin-right:10px;" onchange="get_son_category($(this).val())">
										<option value="0" >{pigcms{:L_("请选择")}</option>
										<volist name="cate_list" id="vo">
											<option value="{pigcms{$key}" <if condition='$info["cat_fid"] eq $key'>selected="selected"</if>>{pigcms{$vo}</option>
										</volist>
									</select>
									<select id="choose_catid" name="cat_id" class="col-sm-1" style="margin-right:10px;">
										<option value="0" >{pigcms{:L_("请选择")}</option>
										<volist name="cate_list2" id="vo">
											<option value="{pigcms{$key}" <if condition='$info["cat_id"] eq $key'>selected="selected"</if>>{pigcms{$vo}</option>
										</volist>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<!-- <div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择平台优惠券")}</label>
									<button type="button" onclick="addCoupon()" class="a_choose class_1">{pigcms{:L_("添加平台优惠券")}</button>&nbsp;&nbsp;
									<div class="class_1" id="tags" tabindex="1"> 
										<span class='tag'>
											<notempty name='info.coupon'>
												{pigcms{$info.coupon.name}<button class='close' data-coupon_id="{pigcms{$info.coupon_id}" type='button'>×</button>
											</notempty>
										</span>
										<input type="hidden" class="px" id="coupon_id" value="{pigcms{$info.coupon_id}" name="coupon_id" style="width:300px" >
									</div>
									<span class="form_tips"></span>
								</div> -->
								<div class="form-group" style="display:none;">
									<label class="col-sm-1"><label>{pigcms{:L_("礼品配送方式")}</label></label>
									<select name="deliver_type" class="col-sm-2">
										<option value="0" <if condition='$info["deliver_type"] eq 0'>selected="selected"</if>>{pigcms{:L_("快递配送")}</option>
										<option value="1" <if condition='$info["deliver_type"] eq 1'>selected="selected"</if>>{pigcms{:L_("门店自提")}</option>
										<option value="2" <if condition='$info["deliver_type"] eq 2'>selected="selected"</if>>{pigcms{:L_("快递配送")}+{pigcms{:L_("门店自提")}</option>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("每人限制兑换数量")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="exchange_limit_num" type="text" value="{pigcms{$info.exchange_limit_num}" />
									<span class="form_tips red">0{pigcms{:L_("为不限制")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("库存数量")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="sku" type="text" value="{pigcms{$info.sku}" />
									<span class="form_tips"></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("已兑换人数")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="exchanged_num" type="text" value="{pigcms{$info.exchanged_num}" />
									<span class="form_tips"></span>
								</div>
                                <div class="form-group" >
                                    <label class="col-sm-1">{pigcms{:L_("电脑端图片")}</label>
                                    <div style="display:inline-block;" id="J_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！建议尺寸：")}800*800</span>
                                </div>
								<div class="form-group background">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name='info.pc_pic_list' id='pvo'>
												<li class="upload_pic_li">
													<img src="{pigcms{$pvo.url}"/>
													<input type="hidden" name="pc_pic[]" value="{pigcms{$pvo.title}"/><br/>
													<a href="#" onclick="deleteImage('{pigcms{$pvo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a>
												</li>
											</volist>
										</ul>
									</div>
								</div>
								<div class="form-group" >
                                    <label class="col-sm-1">{pigcms{:L_("手机端图片")}</label>
                                    <div style="display:inline-block;" id="J_wap_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！建议尺寸：")}600*400</span>
                                </div>
								<div class="form-group background">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="wap_upload_pic_ul">
											<volist name='info.wap_pic_list' id='wvo'>
												<li class="upload_pic_li">
													<img src="{pigcms{$wvo.url}"/>
													<input type="hidden" name="wap_pic[]" value="{pigcms{$wvo.title}"/><br/>
													<a href="#" onclick="deleteImage('{pigcms{$wvo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a>
												</li>
											</volist>
										</ul>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("简述")}</label>
									<textarea rows="6" cols="104" name="intro">{pigcms{$info.intro}</textarea>
									<span class="form_tips"></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("规格")}</label>
									<textarea rows="6" cols="104" name="specification">{pigcms{$info.specification}</textarea>
									<span class="form_tips red">{pigcms{:L_("以回车换行进行分割")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("发货清单")}</label>
									<input class="col-sm-1" size="104" maxlength="20" name="invoice_content" type="text" value="{pigcms{$info.invoice_content}" />
									<span class="form_tips"></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("礼品详情")}</label>
									<textarea rows="6" cols="104" name="gift_content" id="content">{pigcms{$info.gift_content}</textarea>
									<span class="form_tips"></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("排序")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="sort" type="text" value="{pigcms{$info.sort}" />
									<span class="form_tips red">{pigcms{:L_("排序值越大，越往前显示！")}</span>
								</div>	
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("今日新品")}</label></label>
									<select name="is_new" class="col-sm-1">
										<option value="1" <if condition='$info["is_new"] eq 1'>selected="selected"</if>>{pigcms{:L_("开启")}</option>
										<option value="0" <if condition='$info["is_new"] eq 0'>selected="selected"</if>>{pigcms{:L_("关闭")}</option>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<if condition="!empty($info) && $info['status'] neq 2">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("礼品状态")}</label></label>
										<select name="status" class="col-sm-1">
											<option value="1" <if condition='$info["status"] eq 1'>selected="selected"</if>>{pigcms{:L_("开启")}</option>
											<option value="0" <if condition='$info["status"] eq 0 || $info["status"] eq 3'>selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										</select>
										<span class="form_tips red" id="showDeliverTips"> </span>
									</div>
								</if>	
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
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<!-- {pigcms{:L_("富文本")} -->
<script type="text/javascript">
var diyTool = "{pigcms{:U('Article/diytool')}";
var editor;
var diyVideo = "{pigcms{:U('Article/diyVideo')}";
KindEditor.ready(function(K) {
	editor = K.create('#content', {
		filterMode: false,
		resizeType : 1,
		allowPreviewEmoticons : false,
		allowImageUpload : true,
		height:200,
		width:650,
		uploadJson : '/admin.php?g=System&c=Upyun&a=kindedtiropic',
		items : ['fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr',
		 '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist','link', 'unlink','image','diyVideo','diyTool']
	});
});
KindEditor.ready(function(K) {
	K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pc_pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});


	K('#J_wap_selectImage').click(function(){
		if($('.wap_upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#wap_upload_pic_ul').append('<li class="wap_upload_pic_li"><img src="'+url+'"/><input type="hidden" name="wap_pic[]" value="'+title+'"/><br/><a href="#" onclick="wap_deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});
});
</script>
<script type="text/javascript">
$(function(){
	$('.tag').on('click','.close',function(){
		$('#coupon_id').val('');
		$('.tag').html('');
		$('#deliver_type').show();
	});
});
</script>
<script type="text/javascript">
var url = "{pigcms{:U('ajax_category')}";
function get_son_category(fid){
	if(fid==0){
		var shtml = '<option value="0" >{pigcms{:L_("请选择")}</option>';
		$('#choose_catid').empty('').append(shtml);
	}else{
		$.post(url,{'cat_fid':fid},function(data){
			if(data.status){
				$('#choose_catid').show();
				var shtml = '<option value="0" >{pigcms{:L_("请选择")}</option>';
				for(var i in data['cat_list']){
					shtml += '<option value="'+i+'">'+data['cat_list'][i]+'</option>';
				}

				$('#choose_catid').empty('').append(shtml);
			}else{
				$('#choose_catid').html('');
				$('#choose_catid').hide();
			}
		},'json');
	}
}
function deleteImage(path,obj){
	$.post("{pigcms{:U('ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
function wap_deleteImage(path,obj){
	$.post("{pigcms{:U('ajax_del_pic')}",{path:path});
	$(obj).closest('.wap_upload_pic_li').remove();
}

function addCoupon(){
	art.dialog.data('titledom', 'titledom');
	art.dialog.data('imgids', 'imgids');
	art.dialog.data('multinews', 'multinews');
	art.dialog.data('singlenews', 'singlenews');
	art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
	art.dialog.data('multione', 'multione');
	art.dialog.open('admin.php?g=System&c=Coupon&a=select_coupon',{lock:true,title:'{pigcms{:L_("选择平台优惠券',width:900,height:600,yesText:'关闭")}',background: '#000',opacity: 0.45});
}
</script>
<include file="Public:footer"/>
