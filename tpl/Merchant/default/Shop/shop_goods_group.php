<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <div class="clearfix">
                        <a class="btn btn-success" href="{pigcms{:U('Shop/shop_goods_group_add',array('store_id' => $_GET['store_id']))}" style="float:left;">{pigcms{:L_("新建分组")}</a>
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("分组名称")}</th>
									<th>{pigcms{:L_("商品管理")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$goods_group_list">
									<volist name="goods_group_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.id}</td>
											<td>{pigcms{$vo.name}</td>
											<td>
                                                <a style="width: 108px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Shop/shop_goods_group_list',array('group_id'=>$vo['id'], 'store_id'=>$_GET['store_id']))}">{pigcms{:L_("关联商品管理")}</a>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/shop_goods_group_edit',array('group_id'=>$vo['id'], 'store_id' => $_GET['store_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Shop/shop_goods_group_del',array('group_id'=>$vo['id'], 'store_id' => $_GET['store_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>　　
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
    $(function () {
        jQuery(document).on('click', '#shopList a.red', function () {
            if (!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
        });
    });
</script>
<include file="Public:footer"/>