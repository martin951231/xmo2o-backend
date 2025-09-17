<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Bill/area_commission_withdraw')}" frame="true" refresh="true">
		<input type="hidden" name="area_id" value="{pigcms{$_GET['area_id']}"/>
        <input type="hidden" name="street_id" value="{pigcms{$_GET['street_id']}"/>
		<p style="color: green; font-size: 20px; font-weight: bold; width: 100%; height: 40px; line-height: 40px; border-bottom: dashed 1px green;">
			您的余额为：{pigcms{$config['Currency_symbol']}{pigcms{$all_money}
		</p>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">真实姓名</th>
				<td><input type="text" class="input fl" name="name" id="name" size="20" placeholder="请输入真实姓名" validate="maxlength:30,required:true"/></td>
			</tr>
			<tr>
				<th width="80">提现金额</th>
				<td><input type="text" class="input fl" name="money" id="money" size="20" placeholder="请输入数字" validate="number:true,required:true,min:0.01"/>元</td>
			</tr>
			<tr>
				<th width="80">提现至</th>
				<td>
					<select name="type">
						<option value="1" >支付宝</option>
						<option value="3" >银行卡</option>
					</select>
				</td>
			</tr>
			<tr id="alipay">
				<th width="80">支付宝账号</th>
				<td><input type="text" class="input fl" name="account_ali" id="account_ali" size="20" placeholder="请输入支付宝账号" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr style="display: none;" id="bank">
				<th width="80">银行卡</th>
				<td><input type="text" class="input fl" name="card_name" id="card_name" size="20" placeholder="请输入持卡人姓名" validate="maxlength:50"/> <input type="text" class="input fl" name="account" id="account" size="20" placeholder="请输入卡号" validate="maxlength:50"/> <input type="text" class="input fl" name="card_bank" id="card_bank" size="20" placeholder="请输入所属银行" validate="maxlength:50"/></td>
			</tr>
			<tr>
				<th width="80">备注</th>
				<td><textarea name="remark" style="width: 80%;height: 120px;"></textarea></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$(function(){
			$('[name="type"]').bind("change", function(){
				if($(this).val() == '3'){
					$("#bank").show();
					$("#alipay").hide();
					$("#account_ali").attr("validate", "maxlength:50");
					$("#card_name").attr("validate", "maxlength:50,required:true");
					$("#account").attr("validate", "maxlength:50,required:true");
					$("#card_bank").attr("validate", "maxlength:50,required:true");
				}
				else{
					$("#bank").hide();
					$("#alipay").show();
					$("#account_ali").attr("validate", "maxlength:50,required:true");
					$("#card_name").attr("validate", "maxlength:50");
					$("#account").attr("validate", "maxlength:50");
					$("#card_bank").attr("validate", "maxlength:50");
				}
			})
		})
	</script>
<include file="Public:footer"/>