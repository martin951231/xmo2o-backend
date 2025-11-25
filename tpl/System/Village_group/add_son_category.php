<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_son_category')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="cat_id" value="{pigcms{$_GET['cat_id']}">
			<tr>
				<th width="80">分类名称</th>
				<td><input type="text" class="input fl" name="cat_name" id="cat_name" size="25" value="{pigcms{$category.cat_name}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" id="sort" size="25" value="{pigcms{$category.sort}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<!--
			<tr>
				<th width="80">是否推荐到新版社区拼团首页</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$category.hot_recom eq 1">selected</if>"><span>是</span><input type="radio" name="hot_recom" value="1" <if condition="$category.hot_recom eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$category.hot_recom neq 1">selected</if>"><span>否</span><input type="radio" name="hot_recom" value="0" <if condition="$category.hot_recom neq 1">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="选择是，需要为该分类设置背景图或背景色，且将会在新版社区拼团首页显示该分类"></em>
				</td>
			</tr>
			<tr class="hot_recom" style="display: <if condition="$category.hot_recom neq 1">none</if>;">
				<th width="80">分类背景图</th>
				<td>
					<input type="text"  style="width:200px;" name="bg_img" class="input input-image" value="{pigcms{$category.bg_img}"   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
					<p style="display:block;clear:both;padding-top:10px;">建议尺寸：640*206px</p>
				</td>
			</tr>
			<tr class="hot_recom" style="display: <if condition="$category.hot_recom neq 1">none</if>;">
				<th width="80">分类背景色</th>
				<td>
					<select name="bg_color" id="bg_color" style="width:150px;">
						<option value="">不设置</option>
						<option <if condition="$category['bg_color'] eq '06C1AE'">selected="selected"</if> value="06C1AE">绿色</option>
						<option <if condition="$category['bg_color'] eq 'D32C0C'">selected="selected"</if> value="D32C0C">红色</option>
						<option <if condition="$category['bg_color'] eq 'F57224'">selected="selected"</if> value="F57224">黄色</option>
						<option <if condition="$category['bg_color'] eq '0E6CD8'">selected="selected"</if> value="0E6CD8">蓝色</option>
						<option <if condition="$category['bg_color'] eq '6F31D7'">selected="selected"</if> value="6F31D7">紫色</option>
						<option <if condition="$category['bg_color'] eq 'C0956A'">selected="selected"</if> value="C0956A">棕色</option>
					</select>
				</td>
			</tr>
			<tr class="hot_recom" style="display: <if condition="$category.hot_recom neq 1">none</if>;">
				<th width="80">优先显示</th>
				<td>
					<select name="show_what" id="show_what" style="width:150px;">
						<option <if condition="$category['show_what'] eq '2'">selected="selected"</if> value="2">背景图</option>
						<option <if condition="$category['show_what'] eq '1'">selected="selected"</if> value="1">背景色</option>
					</select>
					<em class="notice_tips" tips="设置优先显示后，前端则展示优先显示的内容"></em>
				</td>
			</tr>-->
			<tr class="icon">
				<th width="80">分类图标</th>
				<td>
					<input type="text"  style="width:200px;" name="icon" class="input input-image" value="{pigcms{$category.icon}"   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
				</td>
			</tr>
			<tr class="hot_recom">
				<th width="80">分类子标题</th>
				<td><input type="text" class="input fl" name="sub_title" id="sub_title" size="25" value="{pigcms{$category.sub_title}" placeholder="" validate="maxlength:20" tips=""/></td>
			</tr>
			<tr>
				<th width="80">分类状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$category.status eq 1 OR empty($category)">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$category.status eq 1 OR empty($category)">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$category.status eq 0 AND isset($category['status'])">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$category.status eq 0 AND isset($category['status'])">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<!--
			<tr>
				<th width="80">是否显示预售与已拼团标识</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$category.show_tag eq 1 OR empty($category)">selected</if>"><span>启用</span><input type="radio" name="show_tag" value="1" <if condition="$category.show_tag eq 1 OR empty($category)">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$category.show_tag eq 0 AND isset($category['show_tag'])">selected</if>"><span>关闭</span><input type="radio" name="show_tag" value="0" <if condition="$category.show_tag eq 0 AND isset($category['show_tag'])">checked="checked"</if>/></label></span>
				</td>
			</tr>-->
		</table>
		<div class="btn hidden">
			<input type="hide" name="fid"  value="{pigcms{$_GET['cat_fid']}"/>
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		  KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.J_selectImage').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(url);
								editor.hideDialog();
							}
						});
					});
				});

			});
			$(function(){
				$('[name="hot_recom"]').bind("click",function(){
					if($(this).val() == '1'){
						$(".hot_recom").show();
					}
					else{
						$(".hot_recom").hide();	
					}
				})
			})
		
	</script>
	
	
<include file="Public:footer"/>