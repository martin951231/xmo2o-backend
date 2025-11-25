<include file="Public:header"/>
		<div class="mainbox">
        <div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
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
								<th>批次号</th>
								<th>支出金额</th>
								<th>支出时间</th>
								<th>备注</th>
                                <th>操作员</th>
								<th>创建时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$data['list']">
								<volist name="data.list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.batch_no}</td>
                                        <td>{pigcms{$vo.total_money}</td>
                                        <td>{pigcms{$vo.use_time}</td>
                                        <td>{pigcms{$vo.remark}</td>
                                        <td>{pigcms{$vo.admin_name}</td>
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
		</div>
<include file="Public:footer"/>

<if condition="$admin_area_id eq 0">
    <script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
</if>