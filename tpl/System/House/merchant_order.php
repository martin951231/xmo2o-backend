<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('House/village')}" >{pigcms{$config['house_name']}列表</a>
					<!-- <a href="{pigcms{:U('House/pay_order',array('village_id'=>$_GET['village_id']))}" >缴费列表</a> -->
					<a href="{pigcms{:U('House/village_money_list',array('village_id'=>$_GET['village_id']))}" >小区余额</a>
					<a href="{pigcms{:U('House/merchant_order',array('type'=>'group','village_id'=>$_GET['village_id']))}" <if condition="$_GET.type eq 'group'">class="on"</if>> {pigcms{$config.group_alias_name}订单</a>
					<a href="{pigcms{:U('House/merchant_order',array('type'=>'shop','village_id'=>$_GET['village_id']))}" <if condition="$_GET.type eq 'shop'">class="on"</if>>{pigcms{$config.shop_alias_name}订单</a>
					<a href="{pigcms{:U('House/merchant_order',array('type'=>'meal','village_id'=>$_GET['village_id']))}" <if condition="$_GET.type eq 'meal'">class="on"</if>>{pigcms{$config.meal_alias_name}订单</a>
					<a href="{pigcms{:U('House/merchant_order',array('type'=>'appoint','village_id'=>$_GET['village_id']))}" <if condition="$_GET.type eq 'appoint'">class="on"</if>>{pigcms{$config.appoint_alias_name}订单</a>
                    <a href="{pigcms{:U('House/market_order',array('village_id'=>$_GET['village_id']))}">{pigcms{$config.house_name}超市账单</a>
                </ul>
			</div>
			
			<table class="search_table" width="100%">
				<tr>
					<td width="35%">
						
					 <form id="myform" method="post" action="{pigcms{:U('House/merchant_order',array('village_id'=>$_GET['village_id'],'type'=>$_GET['type']))}" >
						<div style="float:left"><font color="#000">时间筛选 ：</font></div>
						<input type="text" class="input fl" name="begin_time" style="width:120px;height: 25px;" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
						<input type="text" class="input fl" name="end_time" style="width:120px;height: 25px; margin-left:20px" id="d4311" value="{pigcms{$end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
						<input type="submit" class="button">
					</form>
					</td>
					<td>本页实际支付总金额：<strong style="color: red" id="total_money"></strong>  {pigcms{$cfrom}（返点比例{pigcms{$rebate_percent}%）：<strong style="color: red" id="money_rebate"></strong><span style="display: inline-block;margin-left: 120px;font-weight: bold;">实际支付总金额：<strong style="color: red">{pigcms{$total_fact}</strong>  {pigcms{$cfrom}（返点比例{pigcms{$rebate_percent}%）：<strong style="color: red">{pigcms{$total_fact_commission}</strong></span></td>
				</tr>
			</table>
			
				<input type="hidden" id="com_pay_money"name="money" value="">
				<input type="hidden" name="village_id" value="{pigcms{$village_id}">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							
						</colgroup>
						<thead>
								<tr>
                                    <th width="10%">商家名称</th>
                                    <th width="10%">订单ID</th>
                                    <th width="10%">订单描述</th>
                                    <th width="6%">联系方式</th>
                                    <th width="6%">订单总价</th>
                                    <th width="6%">实际支付</th>
                                    <th width="6%">佣金</th>
                                    <th width="6%">支付时间</th>
									<th width="10%">订单详情</th>
                                </tr>
						</thead>
						<tbody>
							<if condition="$order_list">
                                    <volist name="order_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td>{pigcms{$vo.mer_name}</td>
                                            <td>{pigcms{$vo.order_id}</td>
                                            <td>{pigcms{$vo.des}</td>
                                            <td>{pigcms{$vo.phone}</td>
                                            <td><php>echo round($vo['total_money'],2);</php></td>
                                            <td class="money" data-money="<php>echo round($vo['pay_in_fact'],2);</php>">
											<if condition="$_GET.type eq 'appoint'">
												<if condition="$vo.payment_status eq 0 AND $vo.product_id eq 0">
													<php>continue;</php>
												<elseif condition="$vo.product_id gt 0" />
													<php>echo round($vo['pay_in_fact'],2);</php>
												<else />
                                                    <php>echo round($vo['payment_money'],2);</php>
												</if>
											<else />
												<php>echo round($vo['pay_in_fact'],2);</php>
											</if>
											</td>
                                            <td><php>if($rebate_percent>0){
                                                $commission=($vo['pay_in_fact']*$rebate_percent)/100;
                                                $commission=round($commission,2);
                                                if($commission>0){
                                                    echo  $commission;
                                                }
                                            }</php></td>
                                            <td>{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}</td>
                                            <td>
												<if condition="$_GET.type eq 'group'">
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Group&a=order_detail&order_id={pigcms{$vo.order_id}','查看订单详情',600,460,true,false,false,false,'order_edit',true);"><button>查看详情</button></a>
											<elseif condition="$_GET.type eq 'meal'" />
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Foodshop&a=order_detail&order_id={pigcms{$vo.order_id}','查看订单详情',600,460,true,false,false,false,'order_edit',true);"><button>查看详情</button></a>
										
											<elseif condition="$_GET.type eq 'appoint'" />
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Appoint&a=order_detail&order_id={pigcms{$vo.order_id}','查看订单详情',600,460,true,false,false,false,'order_edit',true);"><button>查看详情</button></a>
										
											<elseif condition="$_GET.type eq 'shop'" />
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Shop&a=order_detail&order_id={pigcms{$vo.order_id}','查看订单详情',600,460,true,false,false,false,'order_edit',true);"><button>查看详情</button></a>
										
											</if>
											
											
											</td>
                                          
                                        </tr>
                                    </volist>
									
									<tr class="odd">
										<td colspan="10" id="show_count"></td>
									</tr>
									<tr><td class="textcenter pagebar" colspan="10">{pigcms{$page}</td></tr>	
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="10" >没有订单</td></tr>
                                </if>
							</if>
						</tbody>
					</table>
				</div>
			
		</div>
<script type="text/javascript">
$(document).ready(function(){
	var total_money = 0;
	var percent = Number('{pigcms{$rebate_percent}');
	
	$('.money').each(function(index,val){
		total_money+=$(val).data('money');
	});
	$('#total_money').html(total_money.toFixed(2));
	$('#money_rebate').html((total_money*percent/100).toFixed(2));
});
</script>
<include file="Public:footer"/>