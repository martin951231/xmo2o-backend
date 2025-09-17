<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/distributor_edit')}" frame="true" refresh="true" autocomplete="off">
		<input type="hidden" name="pigcms_id" value="{pigcms{$now_distributor.pigcms_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">ID</th>
				<td width="35%"><div>{pigcms{$now_distributor.pigcms_id}
				<th width="15%">用户UID</th>
				<td width="35%"><div>{pigcms{$now_distributor.uid}
			</tr>

			<tr>
				<th width="15%">姓名</th>
				<td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:50,required:true" value="{pigcms{$now_distributor.name}" readonly="readonly" id="name"/></td>
				
				<th width="15%">手机号</th>
				<td width="35%">
				<input type="text" class="input fl" name="phone" size="20" value="{pigcms{$now_distributor.phone}"></td>
			</tr>
			
			<tr>
				<th width="15%">岗位</th>
				<td width="35%" colspan="3"><input type="text" class="input fl" name="truename" size="20" validate="maxlength:20" value="{pigcms{$now_distributor.position}"/></td>
			</tr>
			<tr>
				<th width="15%">省市</th>
				<td width="35%">
					<div id="choose_cityarea" province_id="{pigcms{$now_distributor['province_id']}" city_id="{pigcms{$now_distributor['city_id']}">
				</td>
			</tr>
			<tr>
				<th width="15%">项目城市</th>
				<td width="35%">
					<select name="project_city_id">
						<volist name="project_city_arr" id="vo">
							<option value="{pigcms{$vo['id']}" <if condition="$now_distributor['project_city_id'] eq $vo['id']">selected</if>>{pigcms{$vo['project_city_name']}</option>
						</volist>
					</select>
				</td>
				<th width="15%">项目</th>
				<td width="35%" class="radio_box">
					<select name="project_id">
						<volist name="project_arr" id="vo">
							<option value="{pigcms{$vo['id']}" <if condition="$now_distributor['project_id'] eq $vo['id']">selected</if>>{pigcms{$vo['project_name']}</option>
						</volist>
					</select>
					
				</td>
			</tr>
			<tr>
				<th width="15%">状态</th>
				<td width="35%"> 
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="status" <if condition="1 eq $detail['status']">checked="checked"</if> value="1"/> 通过</label>
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="status" <if condition="1 neq $detail['status']">checked="checked"</if> value="2" /> 不通过</label>
                </td>
			</tr>	
			<tr class="reason">
				<th width="15%">原因</th>
				<td width="35%">
                   <input type="text" class="input fl" name="reason" value="{pigcms{$detail.reason}" placeholder="审核原因"/>
                </td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$('input[name=status]').click(function (argument) {
			if ($(this).val()==2) {
				$('.reason').show();
			}else{
				$('.reason').hide();
			}
		})
		$('input[name=status]').trigger('click');
		$("select[name=project_city_id]").change(function () {
			var project_city_id = $(this).val();
			var city_name = $(this).find('option:selected').html();
		    $.post('{pigcms{:U("ajax_get_project")}',{city_name:city_name},function (data) {
		       	var html = '';
		       	if (data.length>0) {
		       		for (var i = 0; i < data.length; i++) {
		       			html += '<option value="'+data[i].id+'">'+data[i].project_name+'</option>';
		       		}
		       	}
		       	$("select[name=project_id]").html(html);
		    },'json')
		})
	</script>
	<script type="text/javascript">var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}";choose_area=null</script>
<include file="Public:footer"/>