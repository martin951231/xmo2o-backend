<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/recharge_balance')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

            <tr>
                <td width="80">一卡通ID：</td>
                <td>
                    <if condition="$internal_card['card_id'] gt 0"> {pigcms{$internal_card['card_id']}<input type="hidden"  name="card_id" value="{pigcms{$internal_card['card_id']}" ><else /> <input type="text" class="input fl" name="card_id" value="{pigcms{$internal_card['card_id']}" autocomplete="off" validate="digits:true"  ></if>
                </td>
            </tr>
			<tr>
				<td width="80">当前余额：</td>
				<td>
                    {pigcms{$internal_card['balance_money']}
				</td>
			</tr>

            <tr>
                <td width="80">充值金额：</td>
                <td>
                    <input type="text" class="input fl" name="add_money" value="" tips="请正确输入充值金额，负数则为扣除，正数则为增加对应金额" autocomplete="off">
                </td>
            </tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>