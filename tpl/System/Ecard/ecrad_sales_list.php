<include file="Public:header"/>
<style type="text/css">
select{
	display: inline-block;
	margin:0 10px;
}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<eq name='from_type' value='all'>
						<include file='ecard_header' />
					<else/>
						<a href="{pigcms{:U('Ecard/ecard_list')}"  <eq name="ACTION_NAME" value='ecard_list'>class="on"</eq>  >返回</a>
						<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>0))}">未制卡列表</a>
						<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>1))}">已制卡列表</a>
						<a href="{pigcms{:U('Ecard/ecrad_sales_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}" class="on" >E卡售出明细</a>
						<a href="{pigcms{:U('Ecard/ecard_use_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}" >E卡消费明细</a>
					</eq>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Ecard/ecrad_sales_list')}" method="get">
							<input type="hidden" name="c" value="Ecard"/>
							<input type="hidden" name="a" value="ecrad_sales_list"/>
							<input type="hidden" name="from_type" value="{pigcms{$from_type}"/>
							<input type="text" name='search' value="{pigcms{$_GET.search}" placeholder='查找订单号、昵称、卡号' class="input-text">
							<eq name='from_type' value='all'>
								<select name="card_id">
									<option value=''>请选择{pigcms{$config.ecard_alias_name}</option>
									<volist name="ecard_list" id='evo'>
										<option value='{pigcms{$evo.id}' <eq name="_GET.card_id" value="$evo['id']">selected='selected'</eq> >{pigcms{$evo.card_name}</option>
									</volist>
								</select>
								<select name="card_price">
									<option value=''>请选择面值</option>
									<volist name="ecard_list2" id='evo'>
										<option value='{pigcms{$evo.card_price}' <eq name="_GET.card_price" value="$evo['card_price']">selected='selected'</eq>>{pigcms{$config.Currency_symbol}{pigcms{$evo.card_price}</option>
									</volist>
								</select>
							<else/>
								<input type="hidden" name='card_id' value="{pigcms{$card_id}" />
							</eq>
							<select name='order_status'>
								<option value="">订单状态</option>
								<volist name='order_status_arr' id='rvo'>
									<option value="{pigcms{$rvo.status}" <if condition="is_numeric($_GET['order_status']) && $rvo['status']==$_GET['order_status']">selected='selected'</if> >{pigcms{$rvo.msg}</option>
								</volist>
							</select>
							<input type="submit" value="查询" class="button"/>
							<input type="button" value="清空" class="button clear"/>
						</form>
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
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>订单编号</th>
								<th class="textcenter">礼品卡/卡号</th>
								<th>面值</th>
								<th>封面图</th>
								<th>获取人/手机号</th>
								<th>渠道</th>
								<th>状态</th>
								<th>获取时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>
											<eq name='vo.sales_type' value='1'>
												{pigcms{$vo.real_orderid}
											<else/>
												— — —
											</eq>
										</td>
										<td class="textcenter">
											{pigcms{$vo.card_name}<br/>
											{pigcms{$vo.card_code}
										</td>
										<td>{pigcms{$config.Currency_symbol}{pigcms{$vo.card_price}</td>
										<td>
											<notempty name='vo.card_bg'>
												<img src="{pigcms{$vo.card_bg|replace_file_domain}" style="max-height:80px;padding:5px 0;" />
											</notempty>
										</td>
										<td>
											<eq name='vo.sales_type' value='1'>
												{pigcms{$vo.username}/{pigcms{$vo.userphone}
											<else/>
												— — —
											</eq>
										</td>
										<td>
											<eq name='vo.sales_type' value='1'>
												在线购买
											<else/>
												线下购买
											</eq>
										</td>
										<td>
											<eq name='vo.sales_type' value='1'>
												<if condition="$vo['ord_status'] eq 0">
													<b style='color:red;'>未确认</b>
												<elseif condition="$vo['ord_status'] eq 1"/>
													<b style='color:green;'>已发货</b>
												<elseif condition="$vo['ord_status'] eq 2"/>
													<b style='color:green;'>已收货</b>
												<elseif condition="$vo['ord_status'] eq 3"/>
													<b style='color:green;'>已评价</b>
												</if>
											<else/>
												<b style='color:green;'>已收货</b>
											</eq>
										</td>
										<td>{pigcms{$vo.sales_time|date='Y-m-d H:i:s',###}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
$(function(){
	$('form input.clear').click(function(){
		$('form').find('input[type=text]').val('');
		$('form').find('select').val('');
	});
});
</script>
<include file="Public:footer"/>