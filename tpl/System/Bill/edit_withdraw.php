<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Bill/edit_withdraw')}">
		<input type="hidden" name="token" value="{pigcms{$token}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">金额</td>
				<td><input type="text" name="money" value="{pigcms{$now_withdraw['money']/100}"> {pigcms{$config['Currency_txt']} (<font color="red">修改金额请在支付备注中注明</font>)
<!--				<input type="hidden"   name="money" value="{pigcms{$now_withdraw.money}"  />-->
				</td>
			</tr>
			<tr>
				<th width="80">支付备注</th>
				<input type="hidden" name="id" value="{pigcms{$id}" />
				<input type="hidden" name="mer_id" value="{pigcms{$mer_id}" />
				<td>
					<textarea  rows="6" cols="40" name="remark" id="reason" >{pigcms{$news.content}</textarea>
				</td>
			</tr>
			<if condition="C('config.company_pay_open') && $now_withdraw['status'] neq 4 AND ($config['city_ownpay'] eq 0 OR ($config['city_ownpay'] eq 1 AND !$can_agree ) )">
			<tr>
				<td width="100">是否自动提现至微信</td>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>是</span><input type="radio" name="is_online" value="2" checked="checked"/></label></span>
					<span class="cb-disable"><label class="cb-disable "><span>否</span><input type="radio" name="is_online" value="0" /></label></span>
				</td>
			</tr>
			</if>
		
		</table>
		<div class="btn hidden">
			<input type="reset" style="float:right;" value="取消" class="button" id="reset" />
			<input type="submit" style="float:right;" name="dosubmit" id="dosubmit" value="提交" class="button" />
		</div>
	</form>
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script type="text/javascript">
		

		// $('input[name="money_tmp"]').change(function(event) {
		// 	console.log()
		// 	$('input[name="money"]').val($('input[name="money_tmp"]').val()*100);
		// });
		
	</script>
	<script>
		$(function(){
			$('#reset').click(function(){
				 window.o2oTop.art.dialog({id:'order_edit'}).close();
			});
		})
	</script>
<include file="Public:footer"/>