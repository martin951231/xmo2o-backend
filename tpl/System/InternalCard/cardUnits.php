<include file="Public:header"/>
		<div class="mainbox">
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>办卡单位</th>
								<th>区域</th>
								<th>总用户数</th>
								<th>卡内总余额</th>
								<th>总充值金额</th>
								<th>联系人</th>
								<th>联系电话</th>
								<th>创建时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$units">
								<volist name="units" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.name}</td>
                                        <td>{pigcms{$vo.region_str}</td>
										<td>{pigcms{$vo.card_count}</td>
										<td>{pigcms{$vo.card_total_money|getFormatNumber=###}</td>
										<td>{pigcms{$vo.card_recharge_money|getFormatNumber=###}</td>
                                        <td>{pigcms{$vo.contact_name}</td>
                                        <td>{pigcms{$vo.contact_phone}</td>
                                        <td>{pigcms{$vo.create_time}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$data.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>

<if condition="$admin_area_id eq 0">
    <script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
</if>

<script type="text/javascript">
    function exportData() {
        window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
    }
</script>