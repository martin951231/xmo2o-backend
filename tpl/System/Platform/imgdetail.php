<include file="Public:header"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<if condition="!empty($imgs)">
				<tr>
					<th width="15%">身份证正面照片</th>
					<td><img src="{pigcms{$imgs['face_photo']}" width="100%"></td>
				</tr>
				<tr>
					<th width="15%">身份证背面照片</th>
					<td><img src="{pigcms{$imgs['back_photo']}" width="100%"></td>
				</tr>
				<tr>
					<th width="15%">手持身份证照片</th>
					<td ><img src="{pigcms{$imgs['hand_photo']}" width="100%"></td>
				</tr>
			</if>
		</table>
<include file="Public:footer"/>