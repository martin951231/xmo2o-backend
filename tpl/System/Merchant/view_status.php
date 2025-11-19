<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/add_xiaowei_submit')}" frame="true" refresh="true">
		<input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="160">商家名称</th>
				<td><div class="show">{pigcms{$merchant.name}</div></td>
			</tr>
			<tr>
				<th width="160">小微商户进件状态</th>
				<td>
					<span style="color:red;">{pigcms{$result_status}</span>
				</td>
			</tr>
			<if condition="$status_num eq '2'">
				<tr>
					<th width="160">重新进件</th>
					<td>
						<a href="{pigcms{$reurl}">点我重新进件</a>
					</td>
				</tr>
			</if>
			<if condition="$status_num eq 4 && $qrcode neq ''">
			<tr>
				<th width="160">签约链接</th>
				<td>
					{pigcms{$qrcode}
				</td>
			</tr>
			</if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
<include file="Public:footer"/>