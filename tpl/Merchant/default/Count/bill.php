<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active">{pigcms{:L_("商家账单")}</li>
			<li class="active">{pigcms{:L_("商家对账")}</li>
		</ul>
	</div>
        <div id="nav" class="mainnav_title">
                <ul>
                        <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'meal'))}" <if condition="$type eq 'meal'">class="on"</if>>{pigcms{$config.meal_alias_name}{pigcms{:L_("账单")}</a>
                        <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'group'))}" <if condition="$type eq 'group'">class="on"</if>>{pigcms{$config.group_alias_name}{pigcms{:L_("账单")}</a>
						<if condition="$config['is_open_weidian']">
                        <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'weidian'))}" <if condition="$type eq 'weidian'">class="on"</if>>{pigcms{:L_("微店账单")}</a>
						</if>
						<if condition="$config['appoint_page_row'] gt 0">
                        <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'appoint'))}" <if condition="$type eq 'appoint'">class="on"</if>>{pigcms{$config.appoint_alias_name}{pigcms{:L_("账单")}</a>
						</if>
                        <if condition="$config['wxapp_url']">
                                <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'wxapp'))}" <if condition="$type eq 'wxapp'">class="on"</if>>{pigcms{:L_("营销账单")}</a>
                        </if>
						<if condition="$config['is_cashier'] OR $config['pay_in_store']">
                        <a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'store'))}" <if condition="$type eq 'store'">class="on"</if>>{pigcms{:L_("到店付账单")}</a>
                        </if>
						<if condition="$config['waimai_alias_name']">
							<a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'waimai'))}" <if condition="$type eq 'waimai'">class="on"</if>>{pigcms{$config.waimai_alias_name}{pigcms{:L_("账单")}</a>
						</if>
						<a href="{pigcms{:U('Count/bill',array('mer_id'=>$mer_id, 'type' => 'shop'))}" <if condition="$type eq 'shop'">class="on"</if>>{pigcms{$config.shop_alias_name}{pigcms{:L_("账单")}</a>
                </ul>
        </div>
	<div class="alert alert-info" style="margin:10px;">
		<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>{pigcms{:L_("只统计已消费的订单")}
	</div>
        <style type="text/css">
            .mainnav_title {line-height:40px;/* height:40px; */border-bottom:1px solid #eee;color:#31708f;}
            .mainnav_title a {color:#004499;margin:0 5px;padding:4px 7px;background:#d9edf7;}
            .mainnav_title a:hover ,.mainnav_title a.on{background:#498CD0;color:#fff;text-decoration: none;}
        </style>
         <notempty name="start_year">
        <div class="alert alert-info" style="margin:10px;">
            <div class="year"></div>
            <div class="month"></div>
            <!--{pigcms{:L_("时间筛选")}-->
            <form id="myform" method="post" action="{pigcms{:U('Count/bill')}" >
                <input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
                <input type="hidden" name="type" value="{pigcms{$type}">
                <div style="float:left"><font color="#000">{pigcms{:L_("开始结束时间 ：")}</font></div>
                <input type="text" class="input fl" name="begin_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
                <input type="text" class="input fl" name="end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
                <input type="submit">
            </form>
        </div>
		</notempty>
	
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<div class="tabbable" >
								<div class="row">					
									<div class="col-xs-12">		
										<div class="grid-view">
										<div><span style="color:#000;">{pigcms{:L_("未对账订单数量：")}<font color="red">{pigcms{$un_bill_count}</font></span></div>
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<if condition="$type eq 'shop'">
													<tr>
														<th colspan="16">{pigcms{$config.shop_alias_name}{pigcms{:L_("的对账公式：应对金额 = 订单总额")} - {pigcms{:L_("商家优惠的金额")} - {pigcms{:L_("商家余额支付的金额")} - {pigcms{$config['deliver_name']}{pigcms{:L_("的配送费 (各个金额请点击查看订单详情")})</th>
													</tr>
													</if>
													<tr>
														<th><input type="checkbox" id="all_select"/></th>
														<th>{pigcms{:L_("类型")}</th>
														<th>{pigcms{:L_("店铺名称")}</th>
														<th>{pigcms{:L_("订单号")}</th>
														<th>{pigcms{:L_("订单详情")}</th>
														<th>{pigcms{:L_("数量")}</th>
														<th>{pigcms{:L_("金额")}</th>
														<th>{pigcms{:L_("余额支付金额")}</th>
														<th>{pigcms{:L_("在线支付金额")}</th>
														<th>{pigcms{:L_("商家会员卡余额支付金额")}</th>
														<th>{pigcms{:L_("平台优惠金额")}</th>
														<if condition="($type eq 'group')">
															<th>{pigcms{:L_("已退款金额")}</th>
															<th>{pigcms{:L_("退款手续费")}</th>
														</if>
														<th>{pigcms{:L_("商家优惠券")}</th>
														<th><if condition="$type eq 'group'">{pigcms{:L_("使用时间")}<else />{pigcms{:L_("下单时间")}</if></th>
														<th>{pigcms{:L_("支付时间")}</th>
														<th>{pigcms{:L_("支付类型")}</th>
                                                        <if condition="($type eq 'meal') or ($type eq 'group')"><th>{pigcms{:L_("状态")}</th></if>
														<th>{pigcms{:L_("对账状态")}</th>
													</tr>
												</thead>
												<tbody>
													<if condition="$order_list">
														<volist name="order_list" id="vo">
															<tr>
																<td><if condition="$vo['is_pay_bill'] eq 0"><input type="checkbox" value="{pigcms{$vo.name}_{pigcms{$vo.order_id}" class="select" data-price="{pigcms{:sprintf("%.2f",$vo['order_price'])}" <if condition="($type eq 'meal') or ($type eq 'group') or ($type eq 'appoint')">system_pay="{pigcms{$vo['score_deducte']+$vo['coupon_price']}"<elseif condition="$type eq 'shop'" />system_pay="{pigcms{$vo['score_deducte']+$vo['coupon_price']-$vo['no_bill_money']}"<else />system_pay="0"</if> <if condition="$type eq 'group'">refund-fee="{pigcms{$vo.refund_fee}" refund-money="{pigcms{$vo.refund_money}"</if>  payment_money="{pigcms{$vo.payment_money}" balance_pay="{pigcms{$vo.balance_pay}"/></if></td>
																<td>{pigcms{$type_name}</td>
																<td>{pigcms{$vo.store_name}</td>
																<td><if condition="$type eq 'group' OR $type eq 'shop'">{pigcms{$vo.real_orderid}<else />{pigcms{$vo.order_id}</if></td>
																<td>
																
																<if condition="$vo['name'] eq 1">
																	<volist name="vo['order_name']" id="menu">
																	{pigcms{$menu['name']}:{pigcms{$menu['price']}*{pigcms{$menu['num']}</br>
																	</volist>
																<elseif condition="$vo['name'] eq 3" />
																<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" href="{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id']))}">
																	<i class="ace-icon fa fa-search bigger-130"></i>
																</a>
																<else />
																{pigcms{$vo.order_name}
																</if>
																</td>
																<td>{pigcms{$vo.total}</td>
																<td>{pigcms{$vo['order_price']|floatval}</td>
																<td>{pigcms{$vo.balance_pay|floatval}</td>
																<td>{pigcms{$vo.payment_money|floatval}</td>
																<td>{pigcms{$vo.merchant_balance|floatval}</td>
																<td>{pigcms{$vo['score_deducte']+$vo['coupon_price']|floatval}</td>
																<if condition="$type eq 'group'">
																	<td>{pigcms{$vo.refund_money}</td>
																	<td>{pigcms{$vo.refund_fee}</td>
																</if>
																<td><if condition="$vo['card_id'] eq 0">{pigcms{:L_("未使用")}<else/>{pigcms{:L_("已使用")}</if></td>
																<td>{pigcms{$vo.dateline|date="Y-m-d H:i:s",###}</td>
																<td><if condition="$vo['pay_time'] gt 0">{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if></td>
																<td>{pigcms{$vo.pay_type_show}</td>
																<if condition="($type eq 'meal') or ($type eq 'group')">
                                                                                                                                    <td>
																	<if condition="$vo['paid'] eq 0">
																		{pigcms{:L_("未付款")}
																	<elseif condition="$vo['status'] eq 6" />
																		{pigcms{:L_("部分退款")}
																	<else />
																		<if condition="$vo['pay_type'] eq 'offline' AND empty($vo['third_id'])">{pigcms{:L_("线下未支付")}
																		<elseif condition="$vo['status'] eq 0" />{pigcms{:L_("未消费")}
																		<elseif condition="$vo['status'] eq 1" />{pigcms{:L_("未评价")}
																		<elseif condition="$vo['status'] eq 2" />{pigcms{:L_("已完成")}
																		</if>
																	</if>
                                                                                                                                    </td>
                                                                                                                                </if>
																<td><if condition="$vo['is_pay_bill'] eq 0"><strong style="color: red">{pigcms{:L_("未对账")}</strong><else /><strong style="color: green"><strong type="color:green">{pigcms{:L_("已对账")}</strong></if></td>
															</tr>
														</volist>
														<input type="hidden" id="percent" value="{pigcms{$percent}" />
														<tr class="even" style="display:none">
															<td colspan="16">
															<if condition="$percent">
															{pigcms{:L_("平台的抽成比例：")}<strong style="color: green">{pigcms{$percent}%</strong> <br/>
															{pigcms{:L_("本页总金额：")}<strong style="color: green">{pigcms{$total}</strong>　{pigcms{:L_("本页已出账金额：")}<strong style="color: red">{pigcms{$finshtotal} * {pigcms{$percent}%</strong><br/> 
															{pigcms{:L_("总金额：")}<strong style="color: green">{pigcms{$alltotal+$alltotalfinsh}</strong>　{pigcms{:L_("总已出账金额：")}<strong style="color: red">{pigcms{$alltotalfinsh} * {pigcms{$percent}%</strong><br/>
															<strong>{pigcms{:L_("本页平台应获取的抽成金额：")}</strong><strong style="color: green">{pigcms{$total*$percent/100}</strong><br/>
															<strong>{pigcms{:L_("平台应获取的抽成金额：")}</strong><strong style="color: green">{pigcms{$alltotal+$alltotalfinsh-$all_total_percent}</strong><br/>
															<strong>{pigcms{:L_("本页应获取的金额：")}</strong><strong style="color: green">{pigcms{$total_percent}</strong><br/>
															<strong>{pigcms{:L_("应获取的总金额：")}</strong><strong style="color: red">{pigcms{$all_total_percent}</strong><br/>
															<else />
																{pigcms{:L_("本页总金额：")}<strong style="color: green">{pigcms{$total}</strong>　{pigcms{:L_("本页已出账金额：")}<strong style="color: red">{pigcms{$finshtotal}</strong><br/> 
																{pigcms{:L_("总金额：")}<strong style="color: green">{pigcms{$alltotal+$alltotalfinsh}</strong>　{pigcms{:L_("总已出账金额：")}<strong style="color: red">{pigcms{$alltotalfinsh}</strong><br/>
															</if>
															</td>
														</tr>
														<tr class="odd">
															<td colspan="19" id="show_count"></td>
														</tr>
														<tr><td class="textcenter pagebar" colspan="19">{pigcms{$pagebar}</td></tr>	
													<else/>
														<tr class="odd"><td class="textcenter red" colspan="19" >{pigcms{:L_("该的店铺暂时还没有订单。")}</td></tr>
													</if>
												</tbody>
											</table>
										</div>						
									</div>
									<!--div class="col-xs-2" style="margin-top: 15px;">
										<a class="btn btn-success" href="#">{pigcms{:L_("导出成")}excel</a>
									</div-->
								</div>
							</div>
						</div>
					</div>	
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	$('.handle_btn').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("操作订单")}',
			padding: 0,
			width: 720,
			height: 520,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});
	
	$('#group_id').change(function(){
		$('#frmselect').submit();
	});
});

var selected_year = {pigcms{$selected_year};
var selected_month = {pigcms{$selected_month};

$(document).ready(function(){
        getyear();
	$('#all_select').click(function(){
		if ($(this).attr('checked')){
			$('.select').attr('checked', true);
		} else {
			$('.select').attr('checked', false);
		}
		total_price();
	});
	$('.select').click(function(){total_price();});
});


function total_price()
{
	var total = 0;
	var system_pay = 0;
	$('.select').each(function(){
		if ($(this).attr('checked')) {
			if(parseFloat($(this).attr('refund-fee'))>0||parseFloat($(this).attr('refund-money'))>0){
				total += parseFloat($(this).attr('data-price'))-parseFloat($(this).attr('refund-money'));
				system_pay +=parseFloat($(this).attr('system_pay')) + parseFloat($(this).attr('balance_pay')) + parseFloat($(this).attr('payment_money'))-parseFloat($(this).attr('refund-money'));
			}else{
				total += parseFloat($(this).attr('data-price'));
				system_pay += parseFloat($(this).attr('system_pay')) + parseFloat($(this).attr('balance_pay')) + parseFloat($(this).attr('payment_money'));
			}
		}
	});
	total = Math.round(total * 100)/100;
	system_pay = Math.round(system_pay * 100)/100;
	var percent = $('#percent').val();

	if (total > 0) {
		$('#show_count').html('{pigcms{:L_("账单总计金额：")}<strong style=\'color:red\'>{pigcms{$config['Currency_symbol']}' + total + '</strong>, {pigcms{:L_("平台对该商家的抽成比例是：")}<strong style=\'color:green\'>' + percent + '%</strong>, {pigcms{:L_("平台抽成金额：")}<strong style=\'color:green\'>{pigcms{$config['Currency_symbol']}' + Math.round(system_pay * percent) /100 + '</strong>,{pigcms{:L_("平台支付金额：")}<strong style=\'color:green\'>{pigcms{$config['Currency_symbol']}' + Math.round(system_pay*100)/100 + '</strong>,{pigcms{:L_("商家应得金额")}:<strong style=\'color:red\'>{pigcms{$config['Currency_symbol']}' + Math.round((system_pay - Math.round(system_pay * percent) /100) * 100)/100 + '</strong>');
	} else {
		$('#show_count').html('');
	}
}

function getyear(){
        var now = new Date();
        var now_year = now.getFullYear();
        $('.year').empty();
        var year_list='<div id="nav" class="mainnav_title"><ul>';
        year_list+='<font color="#000">年 :</font>' ;
        for(var year={pigcms{$start_year};year<=now_year;year++){
            if(selected_year!=''&&year==selected_year){
                year_list+='<a  href="{pigcms{:U(\'Count/bill\',array(\'mer_id\'=>$mer_id,\'type\'=>$type))}&year='+year+'" class="on">'+year+'</a>  ';
            }else{
                year_list+='<a  href="{pigcms{:U(\'Count/bill\',array(\'mer_id\'=>$mer_id,\'type\'=>$type))}&year='+year+'">'+year+'</a>  ';
            }
               
        }
        year_list+='</ul></div>'
        $('.year').append(year_list);
        var month = $('.year .on').length>0?$('.year .on').html():now_year;
        getmonth(month);
}
function getmonth(year){
        var now=Date();
        $('.month').empty();
        var now = new Date();
        
        var month_list = '<div id="nav" class="mainnav_title"><ul>';
        month_list+='<font color="#000">月 :</font>' ;
        var month_end = year<now.getFullYear()?12:now.getMonth()+1;
        for (var m = 1; m <= month_end; m++) {
            if (m==selected_month) {
                month_list +='<a href="{pigcms{:U(\'Count/bill\',array(\'mer_id\'=>$mer_id,\'type\'=>$type))}&year='+year+'&month='+m+'"  class="on">'+m+'月 '+'</a>';
            }else{
                month_list +='<a href="{pigcms{:U(\'Count/bill\',array(\'mer_id\'=>$mer_id,\'type\'=>$type))}&year='+year+'&month='+m+'" >'+m+'月 '+'</a>';
            }
        }
        month_list+='</ul></div>';
        $('.month').empty();
        $('.month').append(month_list);
}
</script>
<include file="Public:footer"/>
