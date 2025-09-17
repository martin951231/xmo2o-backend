<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <div class="clearfix">
                        <a class="btn btn-success" href="{pigcms{:U('Shop/shop_goods_group',array('store_id' => $_GET['store_id']))}" style="float:left;">{pigcms{:L_("返回分组列表")}</a>
                        <a class="btn btn-success" id="choose_ecard" style="float:left;margin-left: 20px;">{pigcms{:L_("新增关联商品")}</a>
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("库存")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$goods_list">
									<volist name="goods_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.goods_id}</td>
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.stock_num}</td>
											<td>
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Shop/shop_goods_group_goods_del',array('goods_id'=>$vo['goods_id'], 'store_id' => $_GET['store_id'], 'group_id' => $_GET['group_id'],'id'=>$vo['id']))}">
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

    $(function(){
        var get_ecard_url='{pigcms{$this->config['site_url']}/merchant.php?c=Shop&a=shop_goods_group_op_list&store_id={pigcms{$_GET.store_id}&group_id={pigcms{$_GET.group_id}';
        $('#choose_ecard').click(function(){
            parent.layer.open({
                title: '{pigcms{:L_("可选商品列表")}',
                closeBtn: 1,
                type:2,
                content:get_ecard_url,
                area:['1000px','600px'],
                shade: 0.2,
                cancel:function(){

                }
            });
        });
    });
    function choice_ecard(ecard_id,card_name,index){  
        card_name=card_name || '';
        $('#choose_ecard').text('已关联{pigcms{$config.ecard_alias_name}-'+card_name);
        $('#choose_ecard').before($('<input>',{'type':'hidden','name':'ecrad_name','val':card_name}));
        $('#choose_ecard').before($('<input>',{'type':'hidden','name':'ecard_id','val':ecard_id}));
        $('#choose_ecard').after($('<b>',{'text':'{pigcms{:L_("取消绑定")}','id':'ecard_cancel_bind','style':'display:inline-block;margin-left:20px;cursor:pointer;'}));
        if(index){
            layer.close(index);
        }
        /*不支持设置库存和规格*/
        $('#stock_num').parents('.form-group').hide();
        $('#myTab li a[href="#txtattr"]').hide();
    }

</script>
<include file="Public:footer"/>