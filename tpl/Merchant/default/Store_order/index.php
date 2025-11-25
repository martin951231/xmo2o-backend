<include file="Public:header"/>
<style>
    .card_button{
        border-radius: 5px;
        border:2px;
        background-color: #0a94ff;
        color: #ffffff;
        font-size: 12px;
        padding: 5px;
    }
</style>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Store_order/index')}">{pigcms{:L_("到店消费列表")}</a></li>

			<li>{pigcms{:L_("订单列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<form action="{pigcms{:U('Store_order/index')}" method="get">
					<input type="hidden" name="c" value="Store_order"/>
					<input type="hidden" name="a" value="index"/>


					{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
						<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
<!--						<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>-->
                        <option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>{pigcms{:L_("店铺名称")}</option>
						<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
					</select>
					<font color="#000">{pigcms{:L_("日期筛选：")}</font>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>


					{pigcms{:L_("支付方式筛选")}:
					<select id="pay_type" name="pay_type">
						<option value="" <if condition="$_GET['pay_type'] eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
						<volist name="pay_method" id="vo">
							<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
						</volist>
						<option value="balance" <if condition="$_GET['pay_type'] eq 'balance'">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
						<if condition="$config['open_pufa_pay'] && empty($pay_method['offline'])">
							<option value="offline" <if condition="$_GET['pay_type'] eq 'offline'">selected="selected"</if>>{pigcms{:L_("线下支付")}</option>
						</if>
					</select>
					<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　　
					&nbsp;&nbsp;&nbsp;{pigcms{:L_("总金额 ：")}{pigcms{$all_money|floatval}{pigcms{$config['Currency_txt']}

					<a class="btn btn-success"  href="javascript:void(0)" onclick="exports()"  style="float:right;">{pigcms{:L_("导出订单")}</a>
				</form>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单序号")}</th>
                                    <th>{pigcms{:L_("订单编号")}</th>
									<th>{pigcms{:L_("订单名称")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
                                    <th>{pigcms{:L_("订单金额")}</th>
                                    <th>{pigcms{:L_("支付类型")}</th>
                                    <th>{pigcms{:L_("订单描述")}</th>
                                    <th>{pigcms{:L_("备注")}</th>
									<th>{pigcms{:L_("状态")}</th>
									<th class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td width="100">{pigcms{$vo.order_id}</td>
										<td width="100">{pigcms{$vo.orderid}</td>
										<td width="200">{pigcms{$vo.name}</td>
										<td width="200">
											{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
											{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
											{pigcms{:L_("订单手机号：")}{pigcms{$vo.phone}<br/>
										</td>
										<td width="200">
											<if condition="$vo['paid'] eq 1">
												<font color="green">{pigcms{:L_("已支付")}</font>
											<else/>
												<font color="red">{pigcms{:L_("未付款")}</font>
											</if><br/>

											<if condition="$vo['paid']">{pigcms{:L_("付款时间：")}{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}</if>
										</td>
										<td width="200">{pigcms{$vo.price}</td>
                                        <td>
                                            {pigcms{$vo.pay_type_show}
                                            <if condition="$vo['internal_card_pay']">
                                                <button class="card_button" onclick='showInternalCardPay({pigcms{$vo.internal_card_pay})'>查看</button>
                                            </if>
                                        </td>
                                        <td>{pigcms{$vo.desc}</td>
                                        <td>{pigcms{$vo.remark}</td>
										
										<td>
											<if condition="$vo['refund'] eq 1">
												<if condition="$vo['refund_staff_name']">
													{pigcms{$vo.refund_time|date="Y/m/d H:i:s",###}退款<br/>{pigcms{$vo.refund_staff_name}
												<else/>
													{pigcms{$vo.refund_time|date="Y/m/d H:i:s",###}退款
												</if>
											</if>
										</td>
										
										<td class="button-column" width="40">
											<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" data-width="720" style="padding-right:8px;" href="{pigcms{:U('Orderdetail/store_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">
												<i class="ace-icon fa fa-search bigger-130"></i>
											</a>
										</td>
									</tr>
								</volist>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
    function showInternalCardPay(card_pay){
        let content = '';
        for (let key in card_pay) {
            if (card_pay.hasOwnProperty(key) && card_pay[key] > 0) {
                content += `<p>卡号：${key} , 支付金额：${card_pay[key]}元</p>`;
            }
        }
        layer.open({
            type: 0,
            title: '一卡通支付明细',
            content: content,
            offset: '300px',
            time:5000,
            btn:[]
        });
    }
	$(function(){
		$('.handle_btn').live('click',function(){
			layer.open({
				title:'{pigcms{:L_("操作订单")}',
				area:['800px','600px'],
				type: 2,
				shade: 0.4,
				offset: getScrollTop(),
				content:$(this).attr('href')
			});
			return false;
		});

		$('#group_id').change(function(){
			$('#frmselect').submit();
		});

	});
    
	   var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Store_order/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
