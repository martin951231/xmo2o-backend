<include file="Public:header"/>
	<form id="myform" method="post" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">URL</th>
				<td>{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=shop_list&token={pigcms{$pass}&phone=手机号码</td>
			</tr>
			<tr>
				<th width="80">Token</th>
				<td>{pigcms{$pass}</td>
			</tr>
			<tr>
				<th width="80"></th>
				<td><a class="button" href="{pigcms{:U('Config/get_order_by_phone')}&change=1">更新</a></td>
			</tr>
		</table>
	</form>
<include file="Public:footer"/>