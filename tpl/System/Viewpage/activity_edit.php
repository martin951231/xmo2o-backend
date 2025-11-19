<include file="Public:header"/>
	<style type="text/css">
		.goods_tags{
			margin-top: 10px;
			display: inline-block;
		}
		 .goods_tags .goods_tag{
		    display: inline-block;
		    padding: 2px 2px;
		    margin: 0px 5px 5px 0;
		    border: 1px solid #ccc;
		}
		.goods_tags .goods_tag .store_close {
		    display: inline-block;
		    padding: 0 4px;
		    color: #277fc1;
		    font-weight: bold;
		    cursor: pointer;
		}

		#upload_pic_box{margin-top:20px;height:100px;}
		#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
		#upload_pic_box .upload_image_li{width:130px;float:left;list-style:none;}
		#upload_pic_box img{width:100px;height:100px;}
		a{
		    text-decoration:none
		}
		.a_choose{
			border: 1px solid #ccc;
		    padding: 2px;
		    margin-left: 6px;
		    cursor: pointer;
		}
		a.a_choose:hover{
			color: #333;
			text-decoration: none;
		} 
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Viewpage/activity_edit')}" enctype="multipart/form-data" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$now_activity.id}"/>
		<input type="hidden" name="business_type" value="{pigcms{$_GET.business_type}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">绑定店铺：</th>
				<td>
					<div class="goods_tags" id="goods_tags" tabindex="1"> 
						<div class="goods_content">{pigcms{$now_store.name}</div>
						<input type="hidden" id="store_id" name="store_id" value="{pigcms{$now_activity.store_id}" placeholder="店铺id" />
						<input type="hidden" id="mer_id" name="mer_id" value="{pigcms{$now_activity.mer_id}"  placeholder="商家id"/>
					</div>
					<a onclick="addStore()" class="a_choose">选择店铺</a>
				</td>
			</tr>
			<tr>
				<th width="80">活动标题：</th>
				<td><input type="text" class="input fl" name="title" size="20" placeholder="请输入活动标题" validate="maxlength:20,required:true" value="{pigcms{$now_activity.title}" /></td>
			</tr>
			<tr>
				<th width="80">活动价格：</th>
				<td><input type="text" class="input fl" name="price" size="20" placeholder="请输入活动价格" validate="required:true" value="{pigcms{$now_activity.price}"/></td>
			</tr>
			<tr>
				<th width="80">活动描述：</th>
				<td><input type="text" class="input fl" name="description" size="50" placeholder="请输入活动描述" validate="required:true" value="{pigcms{$now_activity.description}"/></td>
			</tr>
			<tr>
				<th width="80">活动图片</th>
				<td>
					<div style="display:inline-block;cursor: pointer;" id="J_selectImage">
                        <div class="btn btn-sm btn-success" style="position:relative;width:56px;height:26px;padding: 0px 7px;border: 1px solid #e5e5e5;line-height: 26px;display: inline-block;">上传图片</div>
                    </div><span style="margin-left: 4px;color: red">建议尺寸【3张平铺：220*200; 1大2小：220 *160（小），  440*320（大）】</span>
				</td>
			</tr>
			<tr>
				<th width="80">图片预览</th>
                <td id="upload_pic_box">
                    <ul id="upload_pic_ul">
                        <volist name="now_activity['image']" id="vo">
                            <li class="upload_pic_li"><img src="{pigcms{$vo}"/><input type="hidden" name="image[]" value="{pigcms{$vo}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
                        </volist>
                    </ul>
                </td>
			</tr>
			<tr>
				<th width="80">活动图片布局形式</th>
				<td>
					<select name="show_type">
						<option value="0" <if condition="$now_activity['show_type'] eq 0">selected="selected"</if>>平铺3张</option>
						<option value="1" <if condition="$now_activity['show_type'] eq 1">selected="selected"</if>>1大2小</option>
					</select>
				</td>
			</tr>
			<tr>
				<th width="80">活动标签</th>
				<td><textarea name="label" style="width: 200px;height: 100px" tips="多个标签使用换行分割">{pigcms{$now_activity.label}</textarea></td>
			</tr>
			<!-- <tr>
				<th width="80">活动排序</th>
				<td><input type="text" class="input fl" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr> -->
		</table>
		<div class="btn hidden">
			<input type="hidden" name="img_id" value="{pigcms{$imgInfo.img_id}">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script>
    function addStore(){
		art.dialog.data('titledom', 'titledom');
		art.dialog.data('imgids', 'imgids');
		art.dialog.data('multinews', 'multinews');
		art.dialog.data('singlenews', 'singlenews');
		art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
		art.dialog.data('multione', 'multione');
		art.dialog.open('?g=System&c=Viewpage&a=select_store',{lock:true,title:'选择店铺',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45});
	}

	$(".store_close").live("click", function() {
		$(this).parent(".goods_tag").remove();
		$('#store_id').val('');
		$('#mer_id').val('');
	});

		
	KindEditor.ready(function(K){
		var editor = K.editor({
			allowFileManager : true,
			langType : "{pigcms{$config.system_lang_alias}"
		});

		K('#J_selectImage').click(function(){
			if($('.upload_pic_li').size() >= 3){
				alert('{pigcms{:L_("最多上传3个图片！")}');
				return false;
			}
			editor.uploadJson = "{pigcms{:U('ajax_upload_pic_k',['size_type'=>'big'])}";
			editor.loadPlugin('image', function(){
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#course_pic').val(),
					clickFn : function(url, title, width, height, border, align) {
						$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="image[]" value="'+url+'"/><br/><a href="#" onclick="deleteImage(\''+url+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
						editor.hideDialog();
					}
				});
				setTimeout(function(){
					$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
				},200);
			});
		});
	});

	function deleteImage(path,obj){
		$(obj).closest('li').remove();
	}
</script>
<include file="Public:footer"/>