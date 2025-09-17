<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Deliver/level_rule_add')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">规则名称</th>
				<td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:50,required:true" placeholder="请填写规则名称"  tips=""/></td>
			</tr>
			<tr>
				<th width="15%">规则内容</th>
				<td width="35%">

					<textarea class="fl" name="content" id="content" rows="6" cols="80" placeholder="请填写规则内容" tips=""/>
						
					</textarea>
				</td>
			</tr>
			<tr>
				<th width="15%">排序</th>
				<td width="35%"><input type="text" class="input fl" name="desc" size="20" placeholder="请填写规则排序" tips="数字越大，越靠前"/></td>
			</tr>
			<tr>
				<th width="15%">状态</th>
				<td width="35%">
					<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="status" value="1" checked="checked"/></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0"/></label></span>
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