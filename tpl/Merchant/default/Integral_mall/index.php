<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("商家")}{pigcms{$config.gift_alias_name}{pigcms{:L_("积分商品列表")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
            <if condition="$is_add_integral_mall eq 1">
                <div style="margin-bottom:10px;">
                    <a href="{pigcms{:U('Integral_mall/goods_save')}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("添加积分商品")}</a>
                </div>
            </if>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('index')}" method="get">
							<input type="hidden" name="c" value="Integral_mall"/>
							<input type="hidden" name="a" value="index"/>
							{pigcms{:L_("筛选")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="goods_name" <if condition="$_GET['searchtype'] eq 'goods_name'">selected="selected"</if>>{pigcms{:L_("商品名称")}</option>
								<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>{pigcms{:L_("商品编号")}</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							{pigcms{:L_("商品状态")}: <select name="searchstatus">
								<option value="0" <if condition="$_GET['searchstatus'] eq 0">selected="selected"</if>>{pigcms{:L_("全部")}</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>{pigcms{:L_("进行状态")}</option>
								<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>{pigcms{:L_("关闭状态（或已结束）")}</option>
							</select>
							<input type="submit" value="{pigcms{:L_('查询')}" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<style>
				.ace-file-input a {display:none;}
				td.textcenter{text-align: center;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th width="100">{pigcms{:L_("名称")}</th>
									<th>{pigcms{:L_("分类")}</th>
									<th>{pigcms{:L_("销售概览")}</th>
									<th>{pigcms{:L_("所需积分")}</th>
<!--									<th>{pigcms{:L_("所需金额")}</th>-->
                                    <th>{pigcms{:L_("订单来源")}</th>
                                    <th>{pigcms{:L_("状态")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<notempty name='info.list'>
									<volist name='info.list' id='vo'>
										<tr class='<if condition="$i%2 eq 0">odd<else/>even</if>'>
											<td>{pigcms{$vo.goods_id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>
												<font <eq name='vo.is_new' value='1'>color='#06CCAB'</eq> >{pigcms{$vo.goods_name}</font>
											</td>
											<td>{pigcms{$vo.cat_name}</td>
											<td>{pigcms{:L_("售出：")}{pigcms{$vo.sale_count}份</td>
											<td>
												<eq name="vo.exchange_type eq 0">
													{pigcms{$vo.payment_pure_integral}
												<else/>
													{pigcms{$vo.payment_integral}
												</eq>
											</td>
                                            <td>
                                                <if condition="$vo['goods_source_type'] eq 3">
                                                    {pigcms{:L_("虚拟批发")}
                                                    <elseif condition="$vo['goods_source_type'] eq 2"/>
                                                    {pigcms{:L_("批发")}
                                                    <elseif condition="$vo['goods_source_type'] eq 1"/>
                                                    {pigcms{:L_("自主添加")}
                                                </if>
                                            </td>
<!--											<td>-->
<!--												<eq name="vo.exchange_type eq 1">-->
<!--													{pigcms{$vo.payment_money}-->
<!--												</eq>-->
<!--											</td>-->
											<td>
												<eq name='vo.status' value='1'>
													<font color="green">{pigcms{:L_("启用")}</font>
												<elseif condition="$vo['status'] eq 3"/>
													<font color="red">{pigcms{:L_("商品下架")}</font>
												<else/>
													<font color="red">{pigcms{:L_("关闭")}</font>
												</eq>
											</td>
											<td>
                                                <if condition="$vo['goods_source_type'] eq 3">
                                                    <a href="{pigcms{:U('Integral_mall/virtual_wholesale_save',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("编辑")}</a>
                                                    <else/>
                                                    <a href="{pigcms{:U('Integral_mall/goods_save',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("编辑")}</a>
                                                </if>

                                                |
												<a href="javascript:void(0);" class="delete_row" parameter="goods_id={pigcms{$vo.goods_id}" url="{pigcms{:U('goods_del')}">{pigcms{:L_("删除")}</a>
											</td>
										</tr>
									</volist>
									<tr>
										<td class="textcenter pagebar" colspan="9">{pigcms{$info.pagebar}</td>
									</tr>
								<else/>
									<tr class="odd"><td class="textcenter red" colspan="9">{pigcms{:L_("列表为空！")}</td></tr>
								</notempty>
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
$(function(){
	$('.delete_row').click(function(){
		var obj=$(this);
		layer.open({
			content:'{pigcms{:L_("你确定这样操作吗？操作后可能不能恢复！")}',
			btn:['{pigcms{:L_("确定','取消")}'],
			yes:function(index){
				var url=obj.attr('url');
				var parameter=obj.attr('parameter');
				url+='&'+parameter;
				$.post(url,function(res){
					layer.close(index);
					layer.open({
						content:res.info,
						btn:['{pigcms{:L_("确定")}'],
						yes:function(index2){
							if(res.status){
								layer.close(index2);
								window.location.reload();
							}else{
								layer.close(index2);
							}
						}
					});
				});
			},
			cancel:function(index){
				layer.close(index);
			}
		});
	});
});
</script>
<include file="Public:footer"/>