<include file="Public:header"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<if condition="!empty($imgs) && is_array($imgs)">
				<volist name="imgs" id="vol">
					<tr>
						<th width="15%">图片{pigcms{$key+1}</th>
						<td><img src="{pigcms{$vol}" width="80%"></td>
					</tr>
				</volist>
			<else/>
				<tr>
					<th width="15%">图片</th>
					<td><img src="{pigcms{$imgs}" width="80%"></td>
				</tr>
			</if>
		</table>
<include file="Public:footer"/>