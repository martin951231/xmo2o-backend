<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Live/shop_order')}">外卖</a>
				<a href="{pigcms{:U('Live/mall_order')}" class="on">商城</a>
				<a href="{pigcms{:U('Live/group_order')}">团购</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Live/mall_order')}" method="get">
						<input type="hidden" name="c" value="Live"/>
						<input type="hidden" name="a" value="mall_order"/>
						
						<!-- 搜索类型 -->
						栏目: 
						<select name="type">
							<option value="liveshow" <if condition="$_GET['type'] eq 'liveshow'">selected="selected"</if>>直播</option>
							<option value="livevideo" <if condition="$_GET['type'] eq 'livevideo'">selected="selected"</if>>短视频</option>
						</select>
						<select name="searchtype">
							<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>主播/作者UID</option>
							<option value="liveno" <if condition="$_GET['searchtype'] eq 'liveno'">selected="selected"</if>>直播房间号</option>
							<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>订单编号</option>
							<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>订单流水号</option>
							<option value="customer_id" <if condition="$_GET['searchtype'] eq 'customer_id'">selected="selected"</if>>下单人ID</option>
						</select>
						<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='请输入关键词'/>
						<font color="#000">下单日期：</font>
						<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						<select name="status">
							<option value="0">全部状态</option>
							<option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>已消费</option>
							<option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>未消费</option>
						</select>
						<input type="submit" value="查询" class="button"/>　
					</form>
				</td>
			</tr> 
			<tr>
				<td>
                    <b>订单总数：{pigcms{$total_order_nums}</b>　
                    <b>商家营收：{pigcms{$total_merchant_money|floatval}</b>　
                    <b>佣金：{pigcms{$total_author_money|floatval}</b>　
                </td>
            </tr>         
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<colgroup>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>类型</th>
							<if condition="$now_type eq 'liveshow'">
							<th>主播</th>
							<th>直播ID</th>
							<else/>
							<th>作者</th>
							<th>短视频ID</th>
							</if>
							<th>订单号</th>
							<th>下单时间</th>
							<th>订单总金额</th>
							<th>消费状态</th>
							<th>营收</th>
							<th>佣金</th>
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
							<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>