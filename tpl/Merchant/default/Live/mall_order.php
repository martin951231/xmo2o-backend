<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						<form action="{pigcms{:U('Live/mall_order')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Live"/>
							<input type="hidden" name="a" value="mall_order"/>
							<!-- {pigcms{:L_("搜索类型")} -->
							{pigcms{:L_("栏目")}: 
							<select name="type">
								<option value="liveshow" <if condition="$_GET['type'] eq 'liveshow'">selected="selected"</if>>{pigcms{:L_("直播")}</option>
								<option value="livevideo" <if condition="$_GET['type'] eq 'livevideo'">selected="selected"</if>>{pigcms{:L_("短视频")}</option>
							</select>
							<select name="searchtype">
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>{pigcms{:L_("主播/作者UID")}</option>
								<option value="liveno" <if condition="$_GET['searchtype'] eq 'liveno'">selected="selected"</if>>{pigcms{:L_("直播房间号")}</option>
								<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
								<option value="customer_id" <if condition="$_GET['searchtype'] eq 'customer_id'">selected="selected"</if>>{pigcms{:L_("下单人ID")}</option>
							</select>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入关键词")}'/>
							<font color="#000">{pigcms{:L_("下单日期：")}</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<select name="status">
								<option value="0">{pigcms{:L_("全部状态")}</option>
								<option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>{pigcms{:L_("已消费")}</option>
								<option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>{pigcms{:L_("未消费")}</option>
							</select>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div class="alert alert-info" style="margin:10px 0;">
						<b>{pigcms{:L_("订单总数：")}{pigcms{$total_order_nums|floatval} 单</b>　
						<b>{pigcms{:L_("营收总额：")}{pigcms{$total_merchant_money|floatval} </b>　
						<b>{pigcms{:L_("佣金总额：")}{pigcms{$total_author_money|floatval} </b>
					</div>
					<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("类型")}</th>
									<if condition="$now_type eq 'liveshow'">
									<th>{pigcms{:L_("主播")}</th>
									<th>{pigcms{:L_("直播ID")}</th>
									<else/>
									<th>{pigcms{:L_("作者")}</th>
									<th>{pigcms{:L_("短视频ID")}</th>
									</if>
									<th>{pigcms{:L_("订单号")}</th>
									<th>{pigcms{:L_("下单时间")}</th>
									<th>{pigcms{:L_("订单总金额")}</th>
									<th>{pigcms{:L_("消费状态")}</th>
									<th>{pigcms{:L_("营收")}</th>
									<th>{pigcms{:L_("佣金")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
                                        <tr>
                                        	<td>{pigcms{$vo.type}</td>
                                            <td>{pigcms{$vo.author}</td>
											<if condition="$now_type eq 'liveshow'">
											<td>{pigcms{$vo.live_no}</td>
											<else/>
											<td>{pigcms{$vo.video_id}</td>
											</if>
											<td>{pigcms{$vo.real_orderid}</td>
											<td>{pigcms{$vo.create_time}</td>
											<td>{pigcms{$vo.order_money}</td>
											<td><if condition="in_array($vo['status'],[2,3])"><span style="color:green;">已消费</span><else /><span  style="color:red;">未消费</span></if></td>
											<td>{pigcms{$vo.live_merchant_money}</td>
											<td>{pigcms{$vo.live_author_money}</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
</script>
<include file="Public:footer"/>
