<include file="Public:header"/>
<style type="text/css">
div{  
	line-height: 30px;
	margin-left: 10px
}
</style>
<div class="mainbox">
	<div class="table-list">
		<if condition="$detail['condition_type'] eq 0">
			<div><strong>满足下面任意一个条件即可</strong></div>
		<else/>
			<div>必须满足下面所有条件</div>
		</if>
		<if condition="$detail['behavior'] eq 1">
			<if condition="$detail['behavior_type'] eq 0">
				<div>最后消费时间在{pigcms{$detail['behavior_day']}天以外</div>
			<else/>
				<div>最后消费时间在{pigcms{$detail['behavior_start_time']|date="Y-m-d",###}与{pigcms{$detail['behavior_end_time']|date="Y-m-d",###}之间</div>
			</if>
		</if>
		<if condition="$detail['times'] eq 1">
			<div>累计消费次数在{pigcms{$detail['times_min']}与{pigcms{$detail['times_max']}之间</div>
		</if>
		<if condition="$detail['money'] eq 1">
			<div>累计消费金额在{pigcms{$detail['money_min']}与{pigcms{$detail['money_max']}之间</div>
		</if>
		<if condition="$detail['spread_num'] eq 1">
			<div>累计推广用户数在{pigcms{$detail['spread_num_min']}与{pigcms{$detail['spread_num_max']}之间</div>
		</if>
		<if condition="$detail['has_card'] eq 1">
			<div>领过商家会员卡</div>
		</if>
		<if condition="$detail['buy_goods'] eq 1">
			<div>购买过商品</div>
			<table style="width:800px;" class="table table-bordered" id='goods_spec'>
			<thead>
				<tr>
					<th>{pigcms{:L_("商品编号")}</th>
					<th>{pigcms{:L_("所属业务")}</th>
					<th>{pigcms{:L_("商品名称")}</th>
					<th>{pigcms{:L_("现价")}</th>
				</tr>
			</thead>
			<tbody>
				<volist name="detail['buy_goods_list']" id="buy">
				<tr>
					<td>{pigcms{$buy.goods_id}</td>
					<td>{pigcms{$buy.model}</td>
					<td>{pigcms{$buy.name}</td>
					<td>{pigcms{$buy.price}</td>
				</tr>							
				</volist>
			</tbody>
		</table>
		</if>
	</div>
			
</div>
<include file="Public:footer"/>