<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Employee/write_field_modify')}" frame="true" refresh="true">
		<input type="hidden" name="cat_id" value="{pigcms{$_GET.cat_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="10" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<tr>
				<th width="80">显示排序</th>
				<td><input type="text" class="input fl" name="sort" value="0" size="10" placeholder="显示排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
			</tr>
			<tr>
				<th width="80">是否必填</th>
				<td>
				<span class="cb-enable"><label class="cb-enable  selected"><span>是</span><input name="iswrite" value="1" checked="checked" type="radio"></label></span>
				<span class="cb-disable" ><label class="cb-disable "><span>否</span><input name="iswrite" value="0" type="radio"></label></span>
				<img title="用户入驻时决定此字段用户是否必须填写" class="tips_img" src="./tpl/System/Static/images/help.gif">
				</td>
			</tr>
			<tr>
				<th width="80">字段类型</th>
				<td>
					<select name="type" id='js-selectInput'>
						<option value="0">单行文本 </option>
						<option value="1">多行文本</option>
						<option value="2">地图</option>
						<option value="3">下拉选择框</option>
						<option value="4">数字格式</option>
						<option value="5">邮件格式</option>
						<option value="6">日期格式</option>
						<option value="7">时间格式</option>
                        <option value="8">日期时间格式</option>
                        <option value="9">手机格式</option>
                        <option value="10">上传图片（多图）</option>
                        <option value="12">上传单图</option>
					</select>
				</td>
			</tr>
            <tr id="js-showSelect" style='display:none'>
                <th width="80">下拉框候选值</th>
                <td>
                    <textarea tips="一行一个，将通过下拉框的模式展示候选。" name="use_field" style="width:175px;height:54px;" class="input fl"></textarea>
                </td>
            </tr>
            <tr>
                <th width="80">是否前端显示</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>显示</span><input type="radio" name="is_show" value="1" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>隐藏</span><input type="radio" name="is_show" value="0" /></label></span>
                </td>
            </tr>
            <tr>
                <th width="80">说明</th>
                <td><input type="text" class="input fl" name="des" value="" size="35" maxlength="100" placeholder="填写说明"  tips="在入驻页每个字段空格里面以浅灰色展示，对入驻人员填写起到辅助说明作用。"/></td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script>
    $('#js-selectInput').change(function(){
        $('#js-showSelect').hide();
        if($(this).val() == '3'){
            $('#js-showSelect').show();
        }
    })
</script>
<include file="Public:footer"/>