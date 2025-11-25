<include file="Public:header"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<if condition="!empty($imgs)">
				<tr>
					<th width="15%">序号</th>
					<td>{pigcms{$imgs['id']}</td>
				</tr>
				<tr>
					<th width="15%">姓名</th>
					<td>{pigcms{$imgs['name']}</td>
				</tr>
				<tr>
					<th width="15%">手机号</th>
					<td>{pigcms{$imgs['phone']}</td>
				</tr>
				<tr>
					<th width="15%">微信号</th>
					<td>{pigcms{$imgs['wx_card']}</td>
				</tr>
				<tr>
					<th width="15%">身份证号</th>
					<td>{pigcms{$imgs['id_card']}</td>
				</tr>
				<tr>
					<th width="15%">区域</th>
					<td>{pigcms{$imgs['province_name']}-{pigcms{$imgs['city_name']}</td>
				</tr>
				<tr>
					<th width="15%">分类名称</th>
					<td>{pigcms{$imgs['cate_name']}</td>
				</tr>
				<tr>
					<th width="15%">添加时间</th>
					<td>{pigcms{$imgs['add_time']|date='Y-m-d H:i:s',###}</td>
				</tr>
				<tr>
					<th width="15%">身份证正面照片</th>
					<td><img src="{pigcms{$imgs['face_photo']}" width="80%"></td>
				</tr>
				<tr>
					<th width="15%">身份证背面照片</th>
					<td><img src="{pigcms{$imgs['back_photo']}" width="80%"></td>
				</tr>
				<tr>
					<th width="15%">手持身份证照片</th>
					<td ><img src="{pigcms{$imgs['hand_photo']}" width="80%"></td>
				</tr>
			</if>
		</table>
		<div class="btn hidden">
			<form id="myform" method="post" action="{pigcms{:U('Platform/verifyManage')}" frame="true" refresh="true">
		     <input type="hidden" name="id" value="{pigcms{$imgs['id']}"/>
			 <input type="submit" name="dosubmit" id="dosubmit" class="button" />
			 </form>
		</div>
<include file="Public:footer"/>