<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Tw/order')}" class="on">订单列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Tw/order')}" method="get">
						<input type="hidden" name="c" value="Tw"/>
						<input type="hidden" name="a" value="order"/>
						
						搜索: 
                        <select name="searchtype" id="searchtype">
							<option value="tw_order_no" <if condition="$_GET['searchtype'] eq 'tw_order_no'">selected="selected"</if>>图维订单号</option>
							<option value="cust_phone" <if condition="$_GET['searchtype'] eq 'cust_phone'">selected="selected"</if>>下单人手机号</option>
						</select>
                        <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						
						<font color="#000">日期筛选：</font>
						<select name="searchTimeType" id="searchTimeType">
							<option value="oper_date" <if condition="$_GET['searchTimeType'] eq 'oper_date'">selected="selected"</if>>下单时间</option>
							<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
						</select>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						订单状态:
						<select id="status" name="status">
								<option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>全部</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>结账付款成功</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>流水</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>退货</option>
						</select>&nbsp;&nbsp;&nbsp;&nbsp;
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
                            <th>图维订单号</th>
							<th>门店名称</th>
							<th>下单人手机号</th>
							<th>下单人</th>
							<th>收货地址</th>
							<th>下单时间</th>
							<th>订单金额</th>
                            <th>运费</th>
                            <th>实付金额</th>
							<th>订单状态</th>
							<th>支付方式</th>
							<th>支付时间</th>
							<th>操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($orders)">
							<volist name="orders" id="vo">
								<tr>
                                    <td>{pigcms{$vo.id}</td>
                                    <td>{pigcms{$vo.tw_order_no}</td>
                                    <td>{pigcms{$vo.shop_name}</td>
                                    <td>{pigcms{$vo.cust_phone}</td>
                                    <td>{pigcms{$vo.cust_name}</td>
                                    <td>{pigcms{$vo.cust_add}</td>
                                    <td>{pigcms{$vo.oper_date}</td>
                                    <td>{pigcms{$vo.sheet_amount}</td>
                                    <td>{pigcms{$vo.freight}</td>
                                    <td><font color="red">{pigcms{$vo.real_amount}</font></td>
									<td>
										<if condition="$vo['status'] eq 1">
											结账付款成功
										<elseif condition="$vo['status'] eq 2"/>
											流水
										<elseif condition="$vo['status'] eq 3"/>
											退货
										<else/>
											--
										</if>
									</td>
                                    <td>{pigcms{$vo.pay_type}</td>
                                    <td>{pigcms{$vo.pay_time}</td>
                                    <td>
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Tw/order_detail',array('id'=>$vo['id']))}','查看图维订单详情',950,'90%',true,false,false,false,'detail',true);">查看</a>
									</td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script>


</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>