<include file="Public:header"/>
		<div class="mainbox">
            <include file="internal_unit_menu"/>
			<table class="search_table" width="100%">
				<tr>
					<td>
                        <form method="get">
                            <input type="hidden" name="c" value="{pigcms{:MODULE_NAME}"/>
                            <input type="hidden" name="a" value="{pigcms{:ACTION_NAME}"/>
                            归属单位: <input type="text" name="unit_name" class="input-text" value="{pigcms{$_GET['unit_name']}"/>
                            一卡通充值批次号: <input type="text" name="deduction_batch_no" class="input-text" value="{pigcms{$_GET['deduction_batch_no']}"/>
                            付款批次号: <input type="text" name="recharge_batch_no" class="input-text" value="{pigcms{$_GET['recharge_batch_no']}"/>

                            <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                            <a href="javascript:void(0)" onclick="exportData()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>

                        </form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>ID</th>
								<th>归属单位</th>
								<th>一卡通充值批次号</th>
								<th>付款批次号</th>
								<th>抵扣金额</th>
                                <th>操作员</th>
								<th>创建时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($data['list'])">
								<volist name="data.list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.unit_name}</td>
                                        <td>{pigcms{$vo.deduction_batch_no}</td>
                                        <td>{pigcms{$vo.recharge_batch_no}</td>
                                        <td>{pigcms{$vo.money}</td>
                                        <td>{pigcms{$vo.admin_name}</td>
                                        <td>{pigcms{$vo.create_time}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$data.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>


<script type="text/javascript">
     function exportData() {
        window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
    }
</script>