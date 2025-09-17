<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/namesAdd')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <input type="hidden" name="id" value="{pigcms{$record.id|default=0}"/>
            <tr>
                <td width="90">一卡通名称：</td>
                <td>
                    <input type="text" class="input fl" name="card_name" value="{pigcms{$record.card_name|default=''}" tips="名称修改后，用户一卡通名称将同步更新为新名称"  autocomplete="off" validate="required:true">
                </td>
            </tr>
			<tr>
				<td width="90">归属管理员:</td>
				<td>
					<select name="belong_admin_id" style="width:310px; height: 30px">
                        <option value="">--请选择管理员--</option>
                        <volist name="admins" id="vo">
                            <option value="{pigcms{$vo.id}" <if condition="$record['belong_admin_id']==$vo['id']">selected</if>>{pigcms{$vo.account}</option>
                        </volist>
                    </select>
				</td>
			</tr>
			<!-- <tr>
                <td width="90">归属单位：</td>
                <td>
                    <select name="unit_id" style="width:310px; height: 30px">
                        <option value="">--请选择归属单位--</option>
                        <volist name="unit_list" id="vo">
                            <option class="unit_opt" value="{pigcms{$vo.id}" <if condition="$record['unit_id']==$vo['id']">selected</if>>{pigcms{$vo.name}</option>
                        </volist>
                    </select>
                    <input type="text" class="input" id="search" onkeyup="filter_units()" placeholder="关键词搜索"/><br/>
                </td>
            </tr> -->
			<tr>
				<td width="90">充值店铺ID：</td>
				<td>
				<input type="text" style="width: 310px;" class="input fl" name="store_id" value="{pigcms{$record.store_id|default=''}" tips="请正确填写店铺ID"  autocomplete="off" validate="required:true">
				</td>
			</tr>
			<!-- <tr>
				<td width="90">可使用店铺ID：</td>
				<td>
				<input type="text" style="width: 310px;" class="input fl" name="use_store_ids" value="{pigcms{$record.use_store_ids|default=''}" tips="请正确填写店铺ID,多个店铺已英文逗号隔开。为空则不显示店铺使用"  autocomplete="off">
				</td>
			</tr> -->
			<tr>
				<td width="90">状态：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$record['status'] neq 0">selected</if>"><span>正常</span><input type="radio" name="status" value="1" <if condition="$record['status'] neq 0">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$record['status'] eq 0">selected</if>"><span>禁用</span><input type="radio" name="status" value="0" <if condition="$record['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>

			<tr>
				<td width="90">一卡通简介：</td>
				<td>
                    <textarea name="intro" style="width: 300px;">{pigcms{$record.intro|default=''}</textarea>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
<script type='text/javascript'>
	function filter_units(){
		var s = $('#search').val();
		$('.unit_opt').each(function(){
			var unit_name = $(this).html().trim();
			if(unit_name.indexOf(s) === -1){
				$(this).hide();
			}else{
				$(this).show();
			}
		})
	}
</script>

