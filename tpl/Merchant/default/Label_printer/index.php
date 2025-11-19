<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-cloud"></i>
			<li class="active">{pigcms{:L_("微硬件")}</li>
			<li class="active">{pigcms{:L_("标签打印机")} </li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<div class="tab-content">
							<div class="tab-pane active">
								<a href="{pigcms{:U('addprint')}" class="btn btn-success">{pigcms{:L_("创建打印机")}</a>
								<div id="shopList" class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th width="100">{pigcms{:L_("店铺名称")}</th>
												<th width="100">{pigcms{:L_("打印机名称")}</th>
												<th width="100">{pigcms{:L_("打印机品牌")}</th>
												<th width="100">{pigcms{:L_("终端号")}</th>
												<th width="100">{pigcms{:L_("密钥")}</th>
												<th width="30">{pigcms{:L_("打印份数")}</th>
												<th width="80">{pigcms{:L_("添加时间")}</th>
												<th width="80" class="button-column">{pigcms{:L_("操作")}</th>
											</tr>
										</thead>
										<tbody>
											<if condition="$list">
												<volist name="list" id="row">
													<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
														<td>{pigcms{$row.store_name}</td>
														<td>{pigcms{$row.name}</td>
														<td>
															{pigcms{:L_("飞鹅打印机")}
														</td>
														<td>{pigcms{$row.mcode}</td>
														<td>{pigcms{$row.mkey}</td>
														<td>{pigcms{$row.count}</td>
														<td><if condition="$row['add_time']">{pigcms{:date('Y-m-d H:i:s',$row['add_time'])}<else/> - </if></td>
														<td class="button-column">
															<a class="green" style="padding-right:8px;" href="{pigcms{:U('addprint', array('pigcms_id' => $row['pigcms_id']))}" >
																<i class="ace-icon fa fa-pencil bigger-130"></i>
															</a>
															<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('delprint', array('pigcms_id' => $row['pigcms_id']))}">
																<i class="ace-icon fa fa-trash-o bigger-130"></i>
															</a>
														</td>
													</tr>
												</volist>
											<else/>
												<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}
</script>
<include file="Public:footer"/>
