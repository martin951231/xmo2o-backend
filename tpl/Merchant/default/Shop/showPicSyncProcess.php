<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
					<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("图片名称")}</th>
									<th>{pigcms{:L_("是否已同步")}</th>
									<th>{pigcms{:L_("更新时间")}</th>
								</tr>
							</thead>
							<tbody>
                                <volist name='items' id='i'>
                                    <tr>
                                        <td>{pigcms{$i.sync_data.pic_name}</td>
                                        <td>
                                            <if condition="$i['is_sync'] == 1">
                                                <span style="color: #1890ff;">{pigcms{:L_("已同步")}</span>
                                            <elseif condition="$i['is_sync'] == 2"/>
                                                <span style="color: red;">{pigcms{:L_("同步失败")}：{pigcms{$i.fail_reason}</span>
                                            <else/>
                                                <span>{pigcms{:L_("未同步")}</span>
                                            </if>
                                        </td>
                                        <td>{pigcms{$i.update_time}</td>
                                    </tr>
                                </volist>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
</script>
<include file="Public:footer"/>
