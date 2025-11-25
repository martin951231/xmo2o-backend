<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('AISearch/save')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <input type="hidden" name="id" value="{pigcms{$thisWord.id}"/>
			<tr>
				<th width="80">关键词</th>
				<td><input type="text" class="input fl" name="word" size="20" placeholder="请输入关键词" validate="maxlength:50,required:true" value="{pigcms{$thisWord.word|default=''}"/></td>
			</tr>
			<tr>
				<th width="80">类型</th>
				<td>
					<select name="type">
                        <if condition="$config['shop_alias_name']">
							<option value="2" <if condition="$thisWord['type'] eq 2">selected</if>>{pigcms{$config.shop_alias_name}</option>
						</if>
						<if condition="$config['group_alias_name']">
							<option value="0" <if condition="isset($thisWord['type']) && ($thisWord['type'] eq 0)">selected</if>>{pigcms{$config.group_alias_name}</option>
						</if>
						<if condition="$config['meal_alias_name']">
							<option value="1" <if condition="$thisWord['type'] eq 1">selected</if>>{pigcms{$config.meal_alias_name}</option>
						</if>
						<if condition="$config['appoint_alias_name']">
							<option value="3" <if condition="$thisWord['type'] eq 3">selected</if>>{pigcms{$config.appoint_alias_name}</option>
						</if>
                        <option value="4" <if condition="$thisWord['type'] eq 4">selected</if>>自定义链接</option>
					</select>
					<em tips="如果选择自定义链接，请填写对应链接地址" class="notice_tips"></em>
				</td>
			</tr>
            <tr>
				<th width="80">自定义链接</th>
				<td><input type="text" class="input fl" name="link" size="30" value="{pigcms{$thisWord.link|default=''}" validate="url:true" tips="类型为‘自定义链接’时，必填"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" size="10" value="{pigcms{$thisWord.sort|default=0}" validate="required:true,number:true,maxlength:6" tips="数值越大，排序越前"/></td>
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