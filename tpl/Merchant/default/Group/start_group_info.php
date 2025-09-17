<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('start_group_list')}">{pigcms{:L_("团购小组列表")}</a>
				
			</li>
			<li class="active">
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="javascript:void(0)">{pigcms{:L_("团购小组详情")}</a>
				
			</li>
			
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
				
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("参团用户昵称")}</th>
									<th>{pigcms{:L_("手机")}</th>
									<th>{pigcms{:L_("购买时间")}</th>
									<th>{pigcms{:L_("消费状态")}</th>
									<th>{pigcms{:L_("团员身份")}</th>
									<th class="text-middle">{pigcms{:L_("订单详情")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$buyer_list">
									<volist name="buyer_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo['nickname']}</td>
											<td>{pigcms{$vo['phone']}</td>
											<td><if condition="$vo.type eq 0">{pigcms{$vo['pay_time']|date="Y-m-d H:i:s",###}</if></td>
											<td><if condition="$vo.type eq 0"><if condition="$vo.status eq 0"><font color="green">{pigcms{:L_("未消费")}</font><elseif condition="$vo.status eq 1 OR $vo.status eq 2" /><font color="blue">{pigcms{:L_("已消费")}</font><elseif condition="$vo.status eq 3" /><font color="red">{pigcms{:L_("已退款")}</font><elseif condition="$vo.status eq 6" /><font color="orange">{pigcms{:L_("部分退款")}</font></if></if></td>
											<td>
												<if condition="$vo.is_head gt 0">
													<font color="green">{pigcms{:L_("团长")}</font>
												<elseif condition="$vo.type eq 1" />
													<font color="red">{pigcms{:L_("机器人(商家手动生成")})</font>
												<else />
													<font color="blue">{pigcms{:L_("成员")}</font>
												</if>
											</td>
											<td class="text-middle">
												<if condition="$vo.type eq 0">
													<div class="tpl-table-black-operation">
														<a class="tpl-table-black-operation-green layer-iframe" href="{pigcms{:U('Group/order_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("查看订单")}</a>
													</div>
												</if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("没有团购小组")}</td></tr>
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
<include file="Public:footer"/>
