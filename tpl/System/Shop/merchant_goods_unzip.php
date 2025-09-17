<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('merchant_goods_unzip')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th colspan="2"><span style="color:red">请以商品ID为文件名，有图片的商品将不会上传成功。压缩包内文件名不要出现中文。</span><br/>服务器限制上传文件大小为：{pigcms{:ini_get('upload_max_filesize')}</th>
			</tr>
			<tr>
				<th width="80">新增/覆盖</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>新增</span><input type="radio" name="save_type" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>覆盖</span><input type="radio" name="save_type" value="0"/></label></span>
					<em class="notice_tips" tips="新增模式：当商品已经存在图片时不处理图片，多次上传同一图片时不会造成服务器图片增加。<br/>覆盖模式：当商品已经存在图片时，通过新图片覆盖老图片，方便更换图片。"></em>
				</td>
			</tr>
			<tr>
				<th width="80">图片zip压缩包</th>
				<td><input type="file" class="input fl" name="file" style="width:200px;" placeholder="请上传图片zip压缩包" validate="required:true"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>