<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Village_group/add_leader_area_level')}" frame="true" refresh="true">
        <input type="hidden" name="lid" value="{pigcms{$leveldata['id']}"/>
        <input type="hidden" name="city_id" value="{pigcms{$leveldata['city_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">等级名称：</td>
				<td>
				<input type="text" class="input fl" name="lname" value="{pigcms{$leveldata['lname']}" placeholder="请填写一个等级名" tips="如vip1，vip2等">
				&nbsp;&nbsp;&nbsp;<span class="red">例如：1=>VIP1,2=>VIP2 等</span>
				</td>
			</tr>
			<tr>
				<td width="80">等级级别：</td>
				<td>
				<span class="input fl" style="width: 140px;">{pigcms{$leveldata['level']}</span>
				&nbsp;&nbsp;&nbsp;<span class="red">例如：1=>VIP1,2=>VIP2 等</span>
				</td>
			</tr>
            <tr>
                <td width="80"></td>
                <td>
                    &nbsp;&nbsp;&nbsp;<span class="red">客户想成为该佣金等级等级会员所需要销售额</span>
                </td>
            </tr>
			<tr>
				<td width="80">销售额等级区间：</td>
				<td>
                    <input type="text" class="input fl" name="min_sale_num" value="{pigcms{$leveldata['min_sale_num']}" placeholder="请填写最小销售额" onkeyup="value=value.replace(/[^1234567890]+/g,'')">
                    <div class="fl" style="font-size: 18px;">--</div>
                    <input type="text" class="input fl" name="max_sale_num" value="{pigcms{$leveldata['max_sale_num']}" placeholder="请填写最大销售额" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="销售此佣金比率需要销售额处于改设定区间内">
                </td>
			</tr>
            <tr>
                <td width="80">对应佣金比率</td>
                <td>
                    <input type="text" class="input fl" name="leader_money" value="{pigcms{$leveldata['leader_money']}" placeholder="请填写对应佣金比率" tips="达到销售数量需求后，每一单团长所得佣金比例，请填写0-100的数字，如10代表10%">
                </td>
            </tr>
		   <tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">

KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true
	});
	 //var islock=false;
	K('.J_selectImage').click(function(){
		var obj=$(this);
		editor.uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=system/image";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					obj.siblings('input').val(url);
					editor.hideDialog();
					obj.siblings('img').attr('src',url).show();
					//window.location.reload();
				}
			});
		});
	   
	});

	kind_editor = K.create("#description",{
		width:'480px',
		height:'380px',
		minWidth:'480px',
		resizeType : 1,
		allowPreviewEmoticons:false,
		allowImageUpload : true,
		filterMode: true,
		items : [
			'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'link'
		],
		emoticonsPath : './static/emoticons/',
		uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=system/image"
	});
});
</script>
