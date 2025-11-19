<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
	<form id="myform" method="post" action="{pigcms{:U('modify')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <if condition="$is_area_many_city neq 1 ">
                <tr id="adver_currency">
                    <th width="15%">通用</th>
                    <td width="35%" class="radio_box">
                        <span class="cb-enable"><label class="cb-enable selected"><span>通用</span><input id="yes" type="radio" name="currency" value="1" checked="checked" /></label></span>
                        <span class="cb-disable"><label class="cb-disable"><span>不通用</span><input id="no" type="radio" name="currency" value="2" /></label></span>
                        <if condition="C('customization.open_view_multi_city') eq 1">
                        <em for="currency" generated="true" class="error tips" style="position: relative;color: red;font-weight: bold;">选择不通用时请在列表页配置指定区域</em>
                        </if>
                    </td>
                </tr>
            <else/>
                <input id="no" type="hidden" name="currency" value="2" checked="checked" />
            </if>
            <if condition="C('customization.open_view_multi_city') neq 1">
			<tr id="adver_region" style="display:none;">
				<th width="15%">城市</th>
				<td width="85%" colspan="3" id="choose_pca" province_idss="{pigcms{$_GET.province_id}" city_idss="{pigcms{$_GET.city_id}"></td>
			</tr>
            </if>
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="活动名称"/></td>
			</tr>
			<tr>
				<th width="80">活动布局形式</th>
				<td>
					<select name="column_type">
						<option value="0" selected="selected">一行3个</option>
						<option value="1">一行2个</option>
					</select>
				</td>
			</tr>
			<tr class="row-box" style="display: none;">
				<th width="80">最多展示几行</th>
				<td><input type="text" class="input fl" name="row_num" size="20" value="2" validate="maxlength:20"  tips="活动布局形式为一行2个，展示几行"/></td>
			</tr>
			<tr>
				<th width="80">开始时间</th>
				<td><input type="text" class="input fl" name="begin_time" style="width:120px;" id="d4311" validate="required:true" tips="活动开始时间，开始时间必须大于上次活动结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,<if condition="$next_time">minDate:'{pigcms{$next_time|date='Y-m-d H:i',###}',</if>dateFmt:'yyyy-MM-dd HH:mm',maxDate:'#F{$dp.$D(\'d4312\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">结束时间</th>
				<td><input type="text" class="input fl" name="end_time" style="width:120px;" id="d4312" validate="required:true" tips="活动结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',minDate:'#F{$dp.$D(\'d4311\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked"/></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0"/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$('select[name=column_type]').change(function (argument) {
			if ($(this).val()==1) {
				$('.row-box').show();
			}else{
				$('.row-box').hide();
			}
		})

		var admin_area_id = {pigcms{$system_session.area_id};
        var open_view_multi_city = <?php echo C('customization.open_view_multi_city')?:0 ?>;
        if((admin_area_id > 0) && (open_view_multi_city != 1)){
			$("#adver_currency").hide();
			$("#adver_region").show();
			$("#no").trigger('click');
		}

	    $("#yes").click(function () {
	        $("#adver_region").hide();
	    })

	    $("#no").click(function () {
	        $("#adver_region").show();
	    })
	</script>
<include file="Public:footer"/>