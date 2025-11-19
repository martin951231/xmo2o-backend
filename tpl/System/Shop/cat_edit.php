<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Shop/cat_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
		<input type="hidden" name="cat_fid" value="{pigcms{$parentid}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="90">分类名称</th>
				<td><input type="text" class="input fl" name="cat_name" id="cat_name" value="{pigcms{$now_category.cat_name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<tr>
				<th width="90">短标记(url)</th>
				<td><input type="text" class="input fl" name="cat_url" id="cat_url" value="{pigcms{$now_category.cat_url}" size="25" placeholder="英文或数字" validate="maxlength:20,required:true,en_num:true" tips="只能使用英文或数字，用于网址（url）中的标记！建议使用分类的拼音"/></td>
			</tr>
			<if condition="empty($now_category['cat_fid'])">
				<if condition="!empty($now_category['cat_pic'])">
					<tr>
						<th width="80">分类现图</th>
						<td><img src="{pigcms{:file_domain()}/upload/system/shop/{pigcms{$now_category.cat_pic}" style="width:50px;height:50px;" class="view_msg"/></td>
					</tr>
				</if>
				<tr>
					<th width="80">分类LOGO图标</th>
					<td><input type="file" class="input fl" name="cat_pic" style="width:200px;" placeholder="分类LOGO图片" tips="分类LOGO小图标，建议尺寸100*100"/></td>
				</tr>
			</if>
			<tr>
				<th width="90">分类排序</th>
				<td><input type="text" class="input fl" name="cat_sort" value="{pigcms{$now_category.cat_sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
			</tr>
			<tr>
				<th width="90">分类状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_category['cat_status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="cat_status" value="1"  <if condition="$now_category['cat_status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_category['cat_status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="cat_status" value="0"  <if condition="$now_category['cat_status'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			<tr>
				<th width="90">分类下店铺显示</th>
				<td>
					<select name="show_method" class="valid" tips="当用户选择主分类筛选时，显示规则读取主分类。当用户选择子分类筛选时，显示规则读取子分类">
						<option value="0" <if condition="$now_category['show_method'] eq 0">selected</if>>不营业不显示</option>
						<option value="1" <if condition="$now_category['show_method'] eq 1">selected</if>>不营业正常显示</option>
						<option value="2" <if condition="$now_category['show_method'] eq 2">selected</if>>不营业靠后显示</option>
					</select>
				</td>
			</tr>

            <if condition="$now_category['cat_fid'] gt 0">
            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效日期</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_category['effect_date_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="effect_date_type" value="1" <if condition="$now_category['effect_date_type'] neq 2">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_category['effect_date_type'] eq 2">selected</if>"><span>自定义日期</span><input type="radio" name="effect_date_type" value="2" <if condition="$now_category['effect_date_type'] eq 2">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择日期</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_date" style="width:120px;" id="d4311"  value="{pigcms{$now_category.start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
                    <input type="text" class="input-text" name="end_date" style="width:120px;" id="d4311" value="{pigcms{$now_category.end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true"/>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择星期</th>
                <td colspan="2">
                    <input type="checkbox" name="week[]" value="0" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'0') !== false)">checked</if>>&nbsp;星期日&nbsp;
                    <input type="checkbox" name="week[]" value="1" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'1') !== false)">checked</if>>&nbsp;星期一&nbsp;
                    <input type="checkbox" name="week[]" value="2" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'2') !== false)">checked</if>>&nbsp;星期二&nbsp;
                    <input type="checkbox" name="week[]" value="3" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'3') !== false)">checked</if>>&nbsp;星期三&nbsp;
                    <input type="checkbox" name="week[]" value="4" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'4') !== false)">checked</if>>&nbsp;星期四&nbsp;
                    <input type="checkbox" name="week[]" value="5" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'5') !== false)">checked</if>>&nbsp;星期五&nbsp;
                    <input type="checkbox" name="week[]" value="6" <if condition="($now_category['effect_date_type'] eq 1) or (strpos($now_category['week'],'6') !== false)">checked</if>>&nbsp;星期六
                </td>
            </tr>
            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效时间段</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_category['effect_time_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="effect_time_type" value="1" <if condition="$now_category['effect_time_type'] neq 2">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_category['effect_time_type'] eq 2">selected</if>" ><span>自定义时间段</span><input type="radio" name="effect_time_type" value="2" <if condition="$now_category['effect_time_type'] eq 2">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr class="effect_time_tr">
                <th width="80">选择时间</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4312"  value="{pigcms{$now_category.start_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>-
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4312" value="{pigcms{$now_category.end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" validate="required:true"/>
                </td>
            </tr>
        </if>
        </table>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<style type="text/css">
    .effect_date_tr,.effect_time_tr{
        display: none;
    }
</style>
<script type="text/javascript">
    var ignoreValidate = ":hidden";

    $('input[name=effect_date_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_date_tr').show();
        } else {
            $('.effect_date_tr').hide();
        }
    })

    $('input[name=effect_time_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_time_tr').show();
        } else {
            $('.effect_time_tr').hide();
        }
    })
    $('input[name=effect_date_type]:checked').trigger('click');
    $('input[name=effect_time_type]:checked').trigger('click');

</script>
<include file="Public:footer"/>