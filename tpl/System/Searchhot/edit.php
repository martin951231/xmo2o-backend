<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Searchhot/amend')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$search_hot.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">关键词</th>
				<td><input type="text" class="input fl" name="name" size="20" placeholder="请输入关键词" value="{pigcms{$search_hot.name}" validate="maxlength:50,required:true"/></td>
			</tr>
			<if condition="$many_city eq 1">
				<tr id="adver_currency">
					<th width="15%">通用广告</th>
					<td width="35%" class="radio_box">
						<span class="cb-enable"><label class="cb-enable <if condition="$search_hot['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$search_hot['city_id'] eq 0">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$search_hot['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$search_hot['city_id'] neq 0">checked="checked"</if> /></label></span>
					</td>
				</tr>
				<tr id="adver_region" <if condition="$search_hot['city_id'] eq 0">style="display:none;"</if>>
					<th width="15%">所在区域</th>
					<td width="85%" colspan="3" id="choose_cityareass" province_idss="{pigcms{$search_hot['province_id']}" city_idss="{pigcms{$search_hot['city_id']}"></td>
				</tr>
			</if>
			<!--<tr>
				<th width="80">网址</th>
				<td><input type="text" class="input fl" name="url" size="30" placeholder="可不填写" value="{pigcms{$search_hot.url}" validate="url:true" tips="可以为空，默认为搜索该关键词"/></td>
			</tr>-->
			<tr>
				<th width="80">类型</th>
				<td>
					<select name="type">
						<if condition="$config['group_alias_name']">
							<option value="0" <if condition="$search_hot['type'] eq 0">selected="selected"</if>>{pigcms{$config.group_alias_name}</option>
						</if>
						<if condition="$config['meal_alias_name']">
							<option value="1" <if condition="$search_hot['type'] eq 1">selected="selected"</if>>{pigcms{$config.meal_alias_name}</option>
						</if>
						<if condition="$config['shop_alias_name']">
							<option value="2" <if condition="$search_hot['type'] eq 2">selected="selected"</if>>{pigcms{$config.shop_alias_name}</option>
						</if>
						<if condition="$config['appoint_alias_name']">
							<option value="3" <if condition="$search_hot['type'] eq 3">selected="selected"</if>>{pigcms{$config.appoint_alias_name}</option>
						</if>
					</select>
					<em tips="如果填写了网址，选择类型不会影响生成的网址。" class="notice_tips"></em>
				</td>
			</tr>
			<tr>
				<th width="80">链接排序</th>
				<td><input type="text" class="input fl" name="sort" size="10" value="{pigcms{$search_hot.sort}" validate="required:true,number:true,maxlength:6" tips="数值越大，排序越前"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
<script type="text/javascript">
	$(function(){
		$("#yes").click(function () {
	        $("#adver_region").hide();
	    })

	    $("#no").click(function () {
	        $("#adver_region").show();
	    })
		
		var admin_area_id = {pigcms{$system_session.area_id};
		if(admin_area_id > 0){
			$("#adver_currency").hide();
			$("#no").trigger('click');
		}
	})
</script>