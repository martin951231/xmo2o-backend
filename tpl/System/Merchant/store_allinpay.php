<include file="Public:header"/>
<form id="myform" frame="true" refresh="true">
	<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
		<tbody>
			<tr>
				<th width="160">店铺</th>
				<td>{pigcms{$store.name}（ID：{pigcms{$store.store_id}）</td>
			</tr>
			<tr>
				<th width="160">开启状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$allinpay['allinpay_status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="allinpay_status" value="1" <if condition="$allinpay['allinpay_status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$allinpay['allinpay_status'] neq 1">selected</if>"><span>关闭</span><input type="radio" name="allinpay_status" value="0" <if condition="$allinpay['allinpay_status'] neq 1">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<tr>
				<th width="160">AppID</th>
				<td><input type="text" class="input-text" name="allinpay_appid" id="allinpay_appid" value="{pigcms{$allinpay.allinpay_appid}" size="50" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="160">商户号</th>
				<td><input type="text" class="input-text" name="allinpay_cusid" id="allinpay_cusid" value="{pigcms{$allinpay.allinpay_cusid}" size="50" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="160">通联私钥</th>
				<td><textarea name="pay_allinpay_private_key" id="pay_allinpay_private_key" rows="10" cols="65" validate="required:true">{pigcms{$allinpay.pay_allinpay_private_key}</textarea></td>
			</tr>
		</tbody>
	</table>
	<input type="hidden" name="store_id" value="{pigcms{$store.store_id}"/>
	<div class="btn">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button"/>
	</div>
</form>
<script>
$(function(){
	$('#dosubmit').click(function(){
		var requiredFields = [
			{id: 'allinpay_appid', label: 'AppID'},
			{id: 'allinpay_cusid', label: '商户号'},
			{id: 'pay_allinpay_private_key', label: '通联私钥'}
		];
		for(var i = 0; i < requiredFields.length; i++){
			if($.trim($('#' + requiredFields[i].id).val()) === ''){
				window.o2oTop.msg(0, '请填写通联支付' + requiredFields[i].label, true, 2);
				$('#' + requiredFields[i].id).focus();
				return false;
			}
		}
		$('#dosubmit').prop('disabled', true);
		$.post('{pigcms{:U('Merchant/store_allinpay')}', $('#myform').serialize(), function(data){
			if(data.status == 1){
				window.o2oTop.msg(2, data.info, true, 2);
				window.o2oTop.art.dialog({id:'edit_store_allinpay'}).close();
			}else{
				window.o2oTop.msg(0, data.info, true, 2);
				$('#dosubmit').prop('disabled', false);
			}
		}, 'json').fail(function(){
			window.o2oTop.msg(0, '请求失败，请稍后重试', true, 2);
			$('#dosubmit').prop('disabled', false);
		});
		return false;
	});
});
</script>
<include file="Public:footer"/>
