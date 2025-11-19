<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/authority_add')}" frame="true" refresh="true">
			<input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
			<input type="hidden" name="menus[]" value="1"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<!--tr >
			<th >
				<label>名称：</label><input type="text" name="name"  class="input fr" value="{pigcms{$group.name}"/>
				<label>价格：</label><input type="text" name="price"  class="input fr" value="{pigcms{$group.price}"/>{pigcms{$config['Currency_txt']}
			</th>
			

		</tr-->
		
			<tr>
				<th width="160">套餐标题</th>
				<td><div class="show"><input type="text" name="name" size="50" class="input fr" value="{pigcms{$group.name}"/></div></td>
			</tr>
			
			<tr>
				<th width="160">是否开启</th>
				<td>	
				
					<span class="cb-enable">
						<label class="cb-enable <if condition="$group['status'] eq 1 OR empty($group)">selected</if>">
						<span>开启</span>
						<input type="radio" name="status" value="1" <if condition="$group['status'] eq 1">checked="checked"</if>/></label>
					</span>
					<span class="cb-disable " >
						<label class="cb-disable  <if condition="$group['status'] eq 0 AND !empty($group)">selected</if>">
						<span>关闭</span>
						<input type="radio" name="status" value="0" <if condition="$group['status'] eq 0">checked="checked"</if>/></label>
					</span>
					<em class="notice_tips" tips="关闭后商家前台不展示，不影响已购买的商家权限使用"></em>
				</td>
			</tr>
			
			
			<tr>
				<th width="160">是否为默认套餐</th>
				<td>	
				
					<span class="cb-enable">
						<label class="cb-enable <if condition="$group['is_default'] eq 1">selected</if>">
						<span>是</span>
						<input type="radio" name="is_default" value="1" <if condition="$group['is_default'] eq 1">checked="checked"</if>/></label>
					</span>
					<span class="cb-disable " >
						<label class="cb-disable  <if condition="$group['is_default'] eq 0">selected</if>">
						<span>否</span>
						<input type="radio" name="is_default" value="0" <if condition="$group['is_default'] eq 0">checked="checked"</if>/></label>
					</span>
					<em class="notice_tips" tips="设置为默认套餐后新注册的商家在第一次登录商家后台将具备默认套餐里的相关功能权限"></em>
				</td>
			</tr>
			<tr>
				<th width="160">是否更新商家权限</th>
				<td>	
				
					<span class="cb-enable">
						<label class="cb-enable <if condition="$group['update_all'] eq 1">selected</if>">
						<span>是</span>
						<input type="radio" name="update_all" value="1" <if condition="$group['update_all'] eq 1">checked="checked"</if>/></label>
					</span>
					<span class="cb-disable " >
						<label class="cb-disable  <if condition="$group['update_all'] eq 0">selected</if>">
						<span>否</span>
						<input type="radio" name="update_all" value="0" <if condition="$group['update_all'] eq 0">checked="checked"</if>/></label>
					</span>
					<em class="notice_tips" tips="设置为更新商家权限后，所有购买本套餐商家的权限都会被本套餐设置的权限直接覆盖"></em>
				</td>
			</tr>
			<tr class="untry_show" <if condition="$group['is_default'] eq 1">style="display:none;"</if>>
				<th width="160">价格</th>
				<td><input type="text" id="price" check_width="180" check_event="keyup" class="input fl" name="price" value="{pigcms{$group.price}" size="25"  />{pigcms{$config.Currency_txt}/
				<select name="time_type" class="valid" style="width:160px">
					<option value="year" <if condition="$group['time_type'] eq 'year'">selected="selected"</if>>年</option>
					<option value="month" <if condition="$group['time_type'] eq 'month'">selected="selected"</if>>月</option>
					<option value="day" <if condition="$group['time_type'] eq 'day'">selected="selected"</if>>日</option>
					
				</select>
				</td>
			</tr>
			<tr>
				<th width="160">平台抽成比例</th>
				<td><div class="show"><input type="text" name="percent"  class="input fr" value="{pigcms{$group.percent}"/>%</div></td>
			</tr>
			
			<tr class="untry_show" <if condition="$group['is_default'] eq 1">style="display:none;"</if>>
				<th width="160">试用日期</th>
				<td>	
				
					<span class="cb-enable">
						<label class="cb-enable <if condition="$group['is_try'] eq 1">selected</if>">
						<span>是</span>
						<input type="radio" name="is_try" value="1" <if condition="$group['is_try'] eq 1">checked="checked"</if>/></label>
					</span>
					<span class="cb-disable " >
						<label class="cb-disable  <if condition="$group['is_try'] eq 0">selected</if>">
						<span>否</span>
						<input type="radio" name="is_try" value="0" <if condition="$group['is_try'] eq 0">checked="checked"</if>/></label>
					</span>
					<em class="notice_tips" tips="试用，即商家可在一定时间内试用该套餐，过期后将会提示商家进行续费套餐"></em>
				</td>
			</tr>
			
			<tr class=" try_time" <if condition="$group['is_default'] eq 1 OR $group['is_try'] eq 0">style="display:none;"</if>>
				<th width="160">试用期限</th>
				<td><input type="text" id="price" check_width="180" check_event="keyup" class="input fl" name="try_time" value="{pigcms{$group.try_time}" size="25"  />/
				<select name="try_time_type" class="valid" style="width:160px">
					
					<option value="month" <if condition="$group['try_time_type'] eq 'month'">selected="selected"</if>>月</option>
					<option value="day" <if condition="$group['try_time_type'] eq 'day'">selected="selected"</if>>日</option>
					
				</select>
				</td>
			</tr>
			
			<tr>
				<th width="160">文字说明</th>
				<td><div class="show"><input type="text" name="des" size="50" class="input fr" value="{pigcms{$group.des}"/></div></td>
			</tr>
			
			<tr>
				<th width="160">排序</th>
				<td><div class="show"><input type="text" name="sort"  class="input fr" value="{pigcms{$group.sort}"/></div></td>
			</tr>
			
			<tr>
				<th width="160">套餐详情</th>
				<td><div class="show">
				<textarea name="detail" rows="6" cols="50">{pigcms{$group.detail}</textarea>
				<em class="notice_tips" style="float:right" tips="显示在套餐列表内部，请用换行来分割"></em>
				</div>
				</td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<volist name="menus" id="rowset">
			<tr>
				<th width="20%" style=" font-weight:bold; font-size:14px">
					<label><input type="hidden"value="{pigcms{$rowset['id']}" name="menus[]">&nbsp;{pigcms{$rowset['name']}</label>
				</th>
			</tr>
			<if condition="$rowset['menu_list']">
				<volist name="rowset['menu_list']" id="row">
				<tr  style="font-weight:bold;">
					<td>
							<label><input type="checkbox" class="menu_{pigcms{$row['id']} father_menu" value="{pigcms{$row['id']}"  name="menus[]" data-fid="{pigcms{$row['fid']}"  <if condition="in_array($row['id'], $group['menus'])">checked</if> />&nbsp;{pigcms{$row['name']}</label>
							<if condition='$row["menu_list"]'>
								<tr style=" background:#fff; ">
									<td style=" padding-left:35px;">
										<volist name="row['menu_list']" id="srow">
											<label><input type="checkbox" class="child_menu_{pigcms{$srow['fid']} child_menu" value="{pigcms{$srow['id']}"  name="menus[]" data-fid="{pigcms{$srow['fid']}"  <if condition="in_array($srow['id'], $group['menus'])">checked</if> />&nbsp;{pigcms{$srow['name']}</label>
										</volist>
									</td>
								</tr>
							</if>
					</td>
				</tr>
				</volist>
			</if>
		</volist>
		<tr>
		
		<td colspan="2"><label><input type="checkbox" id="all"/> 全选</label></td>
		
		
		</tr>
		</table>
	<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<style>
.frame_form td label{
	/* margin: 5px; */
    display: inline-block;
}
</style>
<script type="text/javascript">
$(document).ready(function(){
	$('#all').click(function(){
		if ($(this).attr('checked')) {
			$('.father_menu, .child_menu, .s_child_menu').attr('checked', true);
		} else {
			$('.father_menu, .child_menu, .s_child_menu').attr('checked', false);
		}
	});
	$('.father_menu').click(function(){
		var fid = $(this).val();
		
		if ($(this).attr('checked')) {
			$('.child_menu_' + fid).attr('checked', true);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val();
				$('.s_child_menu_' + id).attr('checked', true);
			});
		} else {
			$('.child_menu_' + fid).attr('checked', false);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val();
				$('.s_child_menu_' + id).attr('checked', false);
			});
		}
		 check_all()
	});
	$('.child_menu').click(function(){
		var fid = $(this).attr('data-fid');
		var id = $(this).val();
		if ($(this).attr('checked')) {
			$('.menu_' + fid).attr('checked', true);
			$('.s_child_menu_' + id).attr('checked', true);
		} else {
			var flag = false;
			$('.child_menu_' + fid).each(function(){
				if ($(this).attr('checked')) {
					flag = true;
				}
			});
			$('.menu_' + fid).attr('checked', flag);
			$('.s_child_menu_' + id).attr('checked', flag);
			// $('#all').attr('checked', flag);
		}
		 check_all()
	});
	
	
	$('.s_child_menu').click(function(){
		var fid= $(this).data('fid');
		var id = $(this).val();

		if($(this).attr('checked')){
			$('.child_menu').each(function(){
				if($(this).val()==fid){
					$(this).attr('checked',true);
					var pid = $(this).data('fid');
					$('.menu_'+pid).attr('checked',true);
				}
			});
		}else{
			var flag = false;
			$('.s_child_menu_' + fid).each(function(){
				if ($(this).attr('checked')) {
					flag = true;
				}
			});
			
			
			$('.child_menu').each(function(){
				if($(this).val()==fid){
					$(this).attr('checked',flag);
					var pid = $(this).data('fid');
					$('.menu_'+pid).attr('checked',flag);
				}
			});
		}
		
		 check_all()
		
	});
	
	 check_all()
});

function check_all(){
	var flags = true;
	
	$(".father_menu").each(function(){
	
		if ($(this).is(':checked')) {
			
		}else{
			console.log($(this).val())
			flags = false;
		}
	});
	
	$(".child_menu").each(function(){
	
		if ($(this).is(':checked')) {
			
		}else{
	
			flags = false;
		}
	});

	$('#all').attr('checked', flags);
	
	
}
</script>

<script type="text/javascript">
$(document).ready(function(){
	$('input[name="is_default"]').click(function(){
		var type = $(this).val();
		if(type==1){
			$('.untry_show').hide();
			$('.try_time').hide();
		}else{
			$('.untry_show').show();
		}
		
	})
	
	$('input[name="is_try"]').click(function(){
		var type = $(this).val();
		if(type==0){
			$('.try_time').hide();
		}else{
			$('.try_time').show();
		}
		
	})
});
</script>
<include file="Public:footer"/>