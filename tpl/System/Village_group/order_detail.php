<include file="Public:header"/>
	<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					
				
					<a href="{pigcms{:U('Village_group/order_list')}" class="on">订单列表</a> 
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td width="200">
						团长：{pigcms{$header.name} 团长电话：{pigcms{$header.phone} 
					</td>
					
					<td>
						<form action="{pigcms{:U('Village_group/order_list')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="order_list"/>
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>用户电话</option>
							
							
							</select>
							<input type="submit" value="查询" class="button"/>
							
						
						</form>
					</td>
				</tr>
				</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>订单id</th>
								<th>用户信息</th>
								<th>总金额</th>
								<th>添加时间</th>
								<th>支付时间</th>
								<th>状态</th>
						
								<th>支付方式</th>
					
				
						
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($order_list)">
								<volist name="order_list" id="vo">
									<tr>
										<td>{pigcms{$vo.real_orderid}</td>
										<td>{pigcms{$vo.uid}</td>
										<td>{pigcms{$vo.total_money|floatval}</td>
										<td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
										<td><if condition="$vo['pay_time'] gt 0">{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if></td>
										<td><if condition="$vo['status'] eq 1"><font color="red">已支付</font><elseif condition="$vo['status'] eq 2"/><font color="green">已消费</font><elseif condition="$vo['status'] eq 3"/><font color="red">已取消</font><elseif condition="$vo['status'] eq 4"/><font color="red">已退款</font><elseif condition="$vo['status'] eq 0"/><font color="red">未支付</font></if></td>
										<td>{pigcms{$vo.pay_type}</td>
										<td class="textcenter">
											
											<a href="javascript:void(0)"
											onclick="window.o2oTop.artiframe('{pigcms{:U('Village_group/join_order_detail',array('order_id'=>$vo['order_id']))}','订单详情',800,600,true,false,false,editbtn,'store_add',true);">订单详情</a> 
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>