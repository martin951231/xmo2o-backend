<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Bd/edit')}" frame="true" refresh="true">
		<input type="hidden" name="bd_id" value="{pigcms{$bd_info.bd_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">姓名</th>
				<td><input type="text" class="input fl" name="bd_name" id="bd_name" value="{pigcms{$bd_info.bd_name}" size="20" placeholder="" validate="maxlength:30,required:true"/></td>
			</tr>
			<tr>
				<th width="80"><if condition="$bd_info['type'] == 2">业务经理<else />业务员</if>佣金比例</th>
				<td><input type="text" class="input fl" name="commission_rate" id="commission_rate" value="{pigcms{$bd_info.commission_rate}" size="20" placeholder="请输入数字" validate="number:true,required:true,min:0,max:100"/>%</td>
			</tr>
			<if condition="$customization['open_merchant_commission']">
				<tr>
					<th width="80">商家推广佣金比例</th>
					<td><input type="text" class="input fl" name="merchant_commission_rate" id="merchant_commission_rate" size="20" placeholder="请输入数字" value="{pigcms{$bd_info.merchant_commission_rate}" validate="number:true,required:true,min:0,max:100" tips="推广商家的推广用户消费获得佣金比例"/>%</td>
				</tr>
			</if>
			<if condition="$bd_info['type'] == 2">
				<tr>
					<th width="80">下级佣金比例</th>
					<td><input type="text" class="input fl" name="son_commission_rate" id="son_commission_rate" value="{pigcms{$bd_info.son_commission_rate}" size="20" placeholder="请输入数字" validate="number:true,required:true,min:0,max:100"/>%</td>
				</tr>

				<if condition="$customization['open_merchant_commission']">
					<tr>
						<th width="80">下级商家推广佣金比例</th>
						<td><input type="text" class="input fl" name="son_merchant_commission_rate" id="son_merchant_commission_rate" size="20" placeholder="请输入数字" value="{pigcms{$bd_info.son_merchant_commission_rate}" validate="number:true,required:true,min:0,max:100" tips="推广商家的推广用户消费下级获得佣金比例"/>%</td>
					</tr>
				</if>
			</if>
			<tr>
				<th width="80">绑定用户</th>
				<td><input type="text" class="input fl" name="user_phone" id="user_phone" value="{pigcms{$bd_info.uid}" size="20" placeholder="用户手机/用户ID" validate="maxlength:30,required:true"/></td>
			</tr>
			<tr>
				<th width="80">当前用户信息</th>
				<td>手机号：{pigcms{$user.phone} | 昵称：{pigcms{$user.nickname}</td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$bd_info['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1" <if condition="$bd_info['status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$bd_info['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$bd_info['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$(function(){
			
		})
	</script>
<include file="Public:footer"/>