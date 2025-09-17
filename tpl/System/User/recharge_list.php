<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/recharge_list')}" class="on">订单列表</a>
					<a href="{pigcms{:U('User/admin_recharge_list')}" >管理员充值列表</a>
					<a href="{pigcms{:U('User/recharge_discount_list')}">充值优惠管理</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('recharge_list')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="recharge_list"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>支付流水号</option>
                                <option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>第三方交易流水号</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
							</select>&nbsp;&nbsp;
							<font color="#000">日期：</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <font color="#000">支付方式：</font>
                            <select name="pay_type">
                                <option value="" <if condition="$_GET['pay_type'] eq ''">selected="selected"</if>>全部</option>
                                <option value="weixin" <if condition="$_GET['pay_type'] eq 'weixin'">selected="selected"</if>>微信</option>
                                <option value="alipay" <if condition="$_GET['pay_type'] eq 'alipay'">selected="selected"</if>>支付宝</option>
                            </select>&nbsp;
                            <input type="submit" value="查询" class="button"/>　
							<!--支付状态：
							<select name="status" id="status">
								<option value="-1" <if condition="$_GET['status'] eq -1">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>已支付</option>
								<option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>未支付</option>
							</select>-->
							　
							&nbsp;&nbsp;&nbsp;&nbsp;
							总金额：{pigcms{$money_count}{pigcms{$config['Currency_txt']}
							<a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出</a>

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
						<thead>
							<tr>
								<th>订单编号</th>
								<th>订单信息</th>
								<th>订单用户</th>
								<th>查看用户信息</th>
								<th>时间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($order_list)">
								<volist name="order_list" id="vo">
									<tr>
										<td>{pigcms{$vo.order_id}</td>
										<td>充值：{pigcms{$config['Currency_symbol']}{pigcms{$vo.payment_money|floatval}</td>

										<td>用户名：{pigcms{$vo.nickname}</td>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);">查看用户信息</a>
										</td>
										<td>
											下单时间：{pigcms{$vo['add_time']|date='Y-m-d H:i:s',###}<br/>
											<if condition="$vo['paid']">付款时间：{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}</if>
										</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/order_detail',array('order_id'=>$vo['order_id']))}','查看充值订单详情',800,560,true,false,false,false,'order_edit',true);">查看详情</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
	$(function(){
		$('#status').change(function(){
			location.href = "{pigcms{:U('User/recharge_list')}&status=" + $(this).val();
		});

	});

	var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('User/recharge_export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>