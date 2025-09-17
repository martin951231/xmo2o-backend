<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>
<div style="margin-bottom: 15px;background-color: #eef3f7;padding: 10px;">
	1、活动标题长度不超过4位<br/>
</div>
	<form id="myform" method="post" action="{pigcms{:U('index_guess_edit')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$guess_like.id}"/>

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">活动标题</th>
				<td><input type="text" class="input fl" name="title" value="{pigcms{$guess_like.title}" onblur="if(value.length>4) value=value.slice(0,4)" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/>
				</td>
			</tr>

			<tr>
				<th width="80">店铺类型</th>
				<td>
					<select name="store_type" class="input fl" validate="required:true">
						<option value="0" <if condition="$guess_like.store_type eq 0">selected</if>>外卖店铺</option>
						<option value="1" <if condition="$guess_like.store_type eq 1">selected</if>>非外卖店铺</option>
					</select>
				</td>
			</tr>

			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$guess_like.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>

			<tr>
				<th width="80">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="status" value="1" <if condition="$guess_like.status eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$guess_like.status eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>



<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<include file="Publics:footer"/>