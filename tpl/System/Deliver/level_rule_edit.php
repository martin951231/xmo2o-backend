<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Deliver/level_rule_edit')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="pigcms_id" value="{pigcms{$now_rule['pigcms_id']}">
			<tr>
				<th width="15%">规则名称</th>
				<td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:50,required:true" placeholder="请填写规则名称"  tips="" value="{pigcms{$now_rule['name']}" /></td>
			</tr>
			<tr>
				<th width="15%">规则内容</th>
				<td width="35%"><textarea class="fl" name="content" id="content" rows="6" cols="80" validate="required:true" placeholder="请填写规则内容" tips="" />{pigcms{$now_rule['content']}</textarea></td>
			</tr>
			<tr>
				<th width="15%">排序</th>
				<td width="35%"><input type="text" class="input fl" name="sort" size="20" placeholder="请填写规则排序" tips="数字越大，越靠前" value="{pigcms{$now_rule['sort']}"/></td>
			</tr>
			<tr>
				<th width="15%">状态</th>
				<td width="35%">  
					<span class="cb-enable"><label class="cb-enable <if condition="$now_rule['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1"  <if condition="$now_rule['status'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_rule['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0"  <if condition="$now_rule['status'] eq 0">checked="checked"</if>/></label></span>
                </td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css">
	<script src="./static/kindeditor/kindeditor.js"></script>
	<script src="./static/kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
			kind_editor = K.create(
				'#content',
				{
					pasteType : 1,
					resizeType : 1,
					width:"90%",
					height:"370px",
					allowImageUpload : true,
					uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"
				});
		});
	</script>
<include file="Public:footer"/>