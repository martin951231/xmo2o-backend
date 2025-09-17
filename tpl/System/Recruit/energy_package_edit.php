<include file="Public:header"/>
<form id="myform" method="post" action="" enctype="multipart/form-data">
	<input type="hidden" name="package_id" value="{pigcms{$now_package.package_id}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%"><tr>
		<th width="80">套餐名</th>
			<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="套餐名" value="{pigcms{$now_package.name}" /></td>
		</tr>
		<tr>
			<th width="80">价格</th>
			<td><input type="text" class="input fl" name="price" size="20" validate="maxlength:20,required:true" tips="价格" value="{pigcms{$now_package.price}"/></td>
		</tr>
		<tr>
			<th width="80">原价</th>
			<td><input type="text" class="input fl" name="old_price" size="20" validate="maxlength:20,required:true" tips="原价" value="{pigcms{$now_package.old_price}"/></td>
		</tr>
		<tr>
			<th width="80">查看视频次数</th>
			<td><input type="text" class="input fl" name="couse_times" size="20" validate="maxlength:20,required:true" tips="查看视频次数" value="{pigcms{$now_package.couse_times}"/></td>
		</tr>
		<tr>
			<th width="80">查看简历次数</th>
			<td><input type="text" class="input fl" name="resume_times" size="20" validate="maxlength:20,required:true" tips="查看简历次数" value="{pigcms{$now_package.resume_times}"/></td>
		</tr>
		<tr>
			<th width="80">邀约简历次数</th>
			<td><input type="text" class="input fl" name="invitation_times" size="20" validate="maxlength:20,required:true" tips="邀约简历次数" value="{pigcms{$now_package.invitation_times}"/></td>
		</tr>
		<tr>
			<th width="80">有效时间</th>
			<td><input type="text" class="input fl" name="active_time" size="20" validate="maxlength:20,required:true" tips="在此期限内有效" value="{pigcms{$now_package.active_time}"/>天</td>
		</tr>
		<tr>
			<th width="80">排序</th>
			<td><input type="number" class="input fl" name="sort" size="20" validate="maxlength:20" tips="排序" value="{pigcms{$now_package.sort}"/></td>
		</tr>
		<tr>
			<th width="80">状态</th>
			<td>
				<span class="cb-enable"><label class="cb-enable <if condition='$now_package["status"] eq 1'>selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition='$now_package["status"] eq 1'>checked="checked"</if>/></label></span>
				<span class="cb-disable"><label class="cb-disable <if condition='$now_package["status"] eq 0'>selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition='$now_package["status"] eq 0'>checked="checked"</if>/></label></span>
			</td>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<include file="Public:footer"/>