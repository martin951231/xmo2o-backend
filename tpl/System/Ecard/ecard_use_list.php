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
						<a href="{pigcms{:U('Ecard/ecrad_sales_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}">E卡售出明细</a>
						<a href="{pigcms{:U('Ecard/ecard_use_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}"  class="on" >E卡消费明细</a>
					</eq>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Ecard/ecard_use_list')}" method="get">
							<input type="hidden" name="c" value="Ecard"/>
							<input type="hidden" name="a" value="ecard_use_list"/>
							<input type="hidden" name="from_type" value="{pigcms{$from_type}"/>
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
							<input type="text" name='search' value="{pigcms{$_GET.search}" placeholder='查找订单号、昵称、卡号' class="input-text">
							<select name="store_id">
								<option value=''>门店</option>
								<volist name="store_list" id='tvo'>
									<option value='{pigcms{$tvo.store_id}' <eq name='_GET.store_id' value='$tvo["store_id"]'>selected='selected'</eq> >{pigcms{$tvo.name}</option>
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
								<th>订单号</th>
								<th>面值礼品卡/卡号</th>
								<th>消费人/手机号</th>
								<th>消费金额</th>
								<th>消费时间</th>
								<th>门店</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.orderid}</td>
										<td>
											{pigcms{$vo.card_name}<br/>
											No.{pigcms{$vo.card_code}
										</td>
										<td>
											{pigcms{$vo.username}({pigcms{$vo.phone})
										</td>
										<td>{pigcms{$config.Currency_symbol}{pigcms{$vo.use_money}</td>
										<td>{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}</td>
										<td>{pigcms{$vo.store_name|default='-'}</td>
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