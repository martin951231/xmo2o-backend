<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Service/auto_cat_modify')}" enctype="multipart/form-data">
	<input type="hidden" name="id" value="{pigcms{$info.id}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="80">分类名称</th>
			<td>
				<input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$info.name}" placeholder="" validate="maxlength:20,required:true" tips=""/>
			</td>
		</tr>
        <tr>
            <th width="80">跨城市通用</th>
            <td>
				<span class="cb-enable">
					<label class="cb-enable <if condition="$info.is_common == '0'">selected</if>">
						<span>通用</span>
						<input type="radio" name="is_common" value="0" <if condition="$info.is_common != '0'">checked="checked"</if> />
                    </label>
				</span>
                <span class="cb-disable">
					<label class="cb-disable <if condition="$info.is_common != '0'">selected</if>">
						<span>不通用</span>
						<input type="radio" name="is_common" value="1" <if condition="$info.is_common == '0'">checked="checked"</if> />
                    </label>
				</span>
            </td>
        </tr>

         <tr id="show_pro">
                <td width="80">城市</td>
                <!--<td id="choose_cityarea" province_id="{pigcms{$info.province}" city_id="{pigcms{$info.city}" circle_id="-1"></td>-->
                <td id="choose_cityareass" province_idss="{pigcms{$info.province}" city_idss="{pigcms{$info.city}"></td>
         </tr>

		<tr>
			<th width="80">分类类型</th>
			<td>
				<select name="type">
					<option value="0" <if condition="$info['type'] eq 0">selected</if> >自定义商品名称</option>
					<option value="1" <if condition="$info['type'] eq 1">selected</if>>指定商家</option>
				</select>
			</td>
		</tr>
		<tr>
			<th width="80">分类排序</th>
			<td>
				<input type="text" class="input fl" name="sort" value="{pigcms{$info.sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/>
			</td>
		</tr>

		<if condition="$info.img neq ''">
			<tr>
				<th width="80">分类现图</th>
				<td><img src="{pigcms{:file_domain()}/upload/system/merchant/{pigcms{$info.img}" style="width:50px;height:50px;" class="view_msg"/></td>
			</tr>
		</if>
		<tr>
			<th width="80">分类LOGO图标</th>
			<td><input type="file" class="input fl" name="img" style="width:200px;" placeholder="分类LOGO图片" tips=""/></td>
		</tr>

		<tr>
			<th width="80">分类状态</th>
			<td>
				<span class="cb-enable">
					<label class="cb-enable <if condition="$info.status != '0'">selected</if>">
						<span>启用</span>
						<input type="radio" name="status" value="1" <if condition="$info.status != '0'">checked="checked"</if> />
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable <if condition="$info.status == '0'">selected</if>">
						<span>关闭</span>
						<input type="radio" name="status" value="0" <if condition="$info.status == '0'">checked="checked"</if> />
					</label>
				</span>
			</td>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<include file="Public:footer"/>
<script>
    $("input[type='radio']").click(function () {
        if($(this).val()==0){
            $("#show_pro").hide();
        }else{
            $("#show_pro").show();
        }
    })
</script>