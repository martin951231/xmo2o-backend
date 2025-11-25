<include file="Public:header"/>
<style type="text/css">
input[type='text']:disabled{background:#ececec;}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Ecard/ecard_edit')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$info.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<notempty name='info.card_bg'>
			<tr>
				<td width="80">封面图：</td>
				<td>
					<img src="{pigcms{$info.card_bg}" style="max-height:80px;"/>
				</td>
			</tr>
			</notempty>
			<tr>
				<td width="80">上传封面图：</td>
				<td>
					<input type="file" name='card_bg' tips='请上传图片' placeholder='请上传图片' />
				</td>
			</tr>
			<tr>
				<td width="80">E卡名称：</td>
				<td>
					<input type="text" class="input fl" name="card_name" value="{pigcms{$info.card_name}"  autocomplete="off" validate="required:true,digits:false">
				</td>
			</tr>
			<tr>
				<td width="80">前缀：</td>
				<td>
					<input type="text" class="input fl" name="qrcode" value="{pigcms{$info.qrcode}"  autocomplete="off" validate="required:true,digits:false" <notempty name="info">disabled='disabled'</notempty> >
				</td>
			</tr>
			<tr>
				<td width="80">制作数量：</td>
				<td>
				<input type="text" class="input fl" name="card_num" value="{pigcms{$info.card_num}"  autocomplete="off" validate="required:true,digits:true" <notempty name="info">disabled='disabled'</notempty>>
				</td>
			</tr>
			<tr>
				<td width="80">卡片面值：</td>
				<td>
				<input type="text" class="input fl" name="card_price" value="{pigcms{$info.card_price}"  autocomplete="off" validate="required:true,digits:true" <notempty name="info">disabled='disabled'</notempty>>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>

