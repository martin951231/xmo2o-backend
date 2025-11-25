<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<button class="btn btn-success" onclick="CreateCategory()">{pigcms{:L_("添加分类")}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="120">{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("名称（悬浮查看商品标题、图片）")}</th>
									<th>{pigcms{:L_("价格")}</th>
									<th>{pigcms{:L_("销售概览")}</th>
									<th>{pigcms{:L_("时间")}</th>
									<th>{pigcms{:L_("查看数")}</th>
									<th>{pigcms{:L_("评论数")}</th>
									<th>{pigcms{:L_("查看二维码")}</th>
									<th>{pigcms{:L_("运行状态")}</th>
									<th width="100">{pigcms{$config.group_alias_name}{pigcms{:L_("状态")}</th>
									<th width="200" style="text-align:center;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$group_list">
									<volist name="group_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.group_id}</td>
											<td><a href="{pigcms{$config.site_url}/index.php?g=Group&c=Detail&group_id={pigcms{$vo.group_id}" target="_blank" data-title="{pigcms{$vo.name}" data-pic="{pigcms{$vo.list_pic}" class="group_name">{pigcms{$vo.s_name}</a></td>
											<td>{pigcms{$config.group_alias_name}{pigcms{:L_("价：")}{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}{pigcms{$config['Currency_txt']}<br/>{pigcms{:L_("原价：")}{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price}{pigcms{$config['Currency_txt']}</td>
											<td>{pigcms{:L_("售出：")}{pigcms{$vo.sale_count} 份<br/>{pigcms{:L_("库存：")} <if condition="$vo['count_num']">{pigcms{$vo.count_num}<else/>{pigcms{:L_("无限制")}</if><br/>{pigcms{:L_("虚拟：")}{pigcms{$vo.virtual_num} 人</td>
											<td>{pigcms{:L_("开始时间：")}{pigcms{$vo.begin_time|date='Y-m-d H:i:s',###}<br/>{pigcms{:L_("结束时间：")}{pigcms{$vo.end_time|date='Y-m-d H:i:s',###}<br/>{pigcms{$config.group_alias_name}{pigcms{:L_("券有效期：")}{pigcms{$vo.deadline_time|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo.hits}</td>
											<td>{pigcms{$vo.reply_count}</td>
											<td><a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=group&id={pigcms{$vo.group_id}&img=1" class="see_qrcode">{pigcms{:L_("查看二维码")}</a></td>
											<!--td><switch name="vo['type']"><case value="1">{pigcms{:L_("进行中，未开团")}</case><case value="2">{pigcms{:L_("进行中，已开团")}</case><case value="3">{pigcms{:L_("已结束，团购成功")}</case><case value="4">{pigcms{:L_("已结束，团购结束")}</case></switch></td-->
											<td>
												<if condition="$vo['begin_time'] gt $_SERVER['REQUEST_TIME']">
													{pigcms{:L_("未开团")}
												<elseif condition="$vo['end_time'] lt $_SERVER['REQUEST_TIME']"/>
													{pigcms{:L_("已结束")}
												<else/>
													{pigcms{:L_("进行中")}
												</if>
											</td>
											<td><switch name="vo['status']"><case value="0"><font color="red">{pigcms{:L_("关闭")}</font></case><case value="1"><font color="green">{pigcms{:L_("正常")}</font></case><case value="2"><font color="red">{pigcms{:L_("审核中")}</font></case></switch></td>
											<td style="text-align:center;"><a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("评论列表")}" href="{pigcms{:U('Message/group_reply',array('group_id'=>$vo['group_id']))}">{pigcms{:L_("评论列表")}</a>&nbsp;&nbsp;<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("订单列表")}" href="{pigcms{:U('Group/order_list',array('group_id'=>$vo['group_id']))}">{pigcms{:L_("订单列表")}</a><if condition="$config['merchant_edit_group']">&nbsp;&nbsp;<a class="label label-sm label-info" title="{pigcms{:L_("编辑")}" href="{pigcms{:U('Group/edit',array('group_id'=>$vo['group_id']))}">{pigcms{:L_("编辑")}</a></if></td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("您没有添加过商品！")}</td></tr>
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
	function CreateCategory(){
		window.location.href = "{pigcms{:U('cat_add')}";
	}
</script>
<include file="Public:footer"/>
