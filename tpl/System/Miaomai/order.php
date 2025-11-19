<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Miaomai/order')}" class="on">订单列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Miaomai/order')}" method="get">
						<input type="hidden" name="c" value="Miaomai"/>
						<input type="hidden" name="a" value="order"/>
						
						零售单号
                        <input type="text" name="order_code" class="input-text" value="{pigcms{$_GET['order_code']}"/>

						门店名称
                        <input type="text" name="stores_name" class="input-text" value="{pigcms{$_GET['stores_name']}"/>

						收银员姓名
                        <input type="text" name="cas_name" class="input-text" value="{pigcms{$_GET['cas_name']}"/>
						
						<font color="#000">日期筛选：</font>
						<select name="searchTimeType" id="searchTimeType">
							<option value="oper_date" <if condition="$_GET['searchTimeType'] eq 'oper_date'">selected="selected"</if>>下单时间</option>
							<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
						</select>
						<input type="text" class="input-text" name="addtime" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="pay_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						<input type="submit" value="查询" class="button"/>　
					</form>
				</td>
			</tr>
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>ID</th>
                            <th>零售单号</th>
							<th>门店名称</th>
							<th>门店编号</th>
							<th>收银员姓名</th>
							<th>收银员编号</th>
							<th>订单总金额</th>
							<th>订单支付金额</th>
							<th>会员姓名</th>
							<th>会员手机号</th>
							<th>会员卡号</th>
							<th>下单时间</th>
							<th>支付时间</th>
							<th>操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($orders)">
							<volist name="orders" id="vo">
								<tr>
                                    <td>{pigcms{$vo.id}</td>
                                    <td>{pigcms{$vo.order_code}</td>
                                    <td>{pigcms{$vo.stores_name}</td>
                                    <td>{pigcms{$vo.wh_code}</td>
                                    <td>{pigcms{$vo.cas_name}</td>
                                    <td>{pigcms{$vo.cas_code}</td>
									<td>{pigcms{$vo.order_money}</td>
									<td><font color="red">{pigcms{$vo.pay_money}</font></td>
                                    <td>{pigcms{$vo.name}</td>
                                    <td>{pigcms{$vo.mobile}</td>
                                    <td>{pigcms{$vo.card_code}</td>
                                    <td>{pigcms{$vo.addtime|date='Y-m-d H:i:s',###}</td>
                                    <td>{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}</td>
                                    <td>
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Miaomai/order_detail',array('id'=>$vo['id']))}','查看淼迈订单详情',1000,'90%',true,false,false,false,'detail',true);">订单详情</a>&nbsp;&nbsp;
									
									<if condition="$vo['has_refund']">
										|&nbsp;&nbsp;
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Miaomai/order_refund_detail',array('order_code'=>$vo['order_code']))}','查看淼迈订单退货详情',1000,'90%',true,false,false,false,'detail',true);">退货详情</a>
									</if>
									</td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="14">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script>


</script>
<include file="Public:footer"/>