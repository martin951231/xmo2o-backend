<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Shop/system_refund')}" frame="true" refresh="true">
		<input type="hidden" name="order_id" value="{pigcms{$_GET['order_id']}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="90">退款原因</th>
				<td>
					<select name="reason">
						<volist name="refund_reason" id="vo">
							<option value="{pigcms{$key}">{pigcms{$vo}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<th width="90">退款金额</th>
				<td>{pigcms{$config['Currency_symbol']}&nbsp;<input type="text" class="input" name="price" id="price" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<tr>
				<th width="90">退款账户</th>
				<td>
					<select name="refund_type" id="refund_type">
						<option value="-1">请选择退款账户</option>
						<option value="0">平台余额</option>
						<option value="1">原路退回</option>
					</select>
					&nbsp;<span class="refund_msg" style="display: none;color: red;">平台积分、商家会员卡余额还是按照原路返回退款</span>
				</td>
			</tr>
			<tr>
				<th width="90">退款说明</th>
				<td>
					<textarea name="remark" style="width: 150px;height: 100px"></textarea>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$("#refund_type").change(function () {
	        if ($("#refund_type").val() == 0) {
	            $(".refund_msg").show();
	        } else {
	            $(".refund_msg").hide();
	        }
	    })
	</script>
<include file="Public:footer"/>