<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
			<li class="active">{pigcms{:L_("收入记录")}</li>

		</ul>
	</div>
	<style>
	.mainnav_title{
		margin-top:20px;
	}
	.mainnav_title ul a {
		padding: 15px 20px;
	}
	ul, ol {
		margin-bottom: 15px;
	}
	.mainnav_title span{
		color:#7EBAEF;
		
	}
	.mainnav_title a.on div{
		color:#C1BEBE;
	}
	.all{
		border-collapse:collapse;
		border:none;
	}
	.all td{
		border:solid #000 1px;
		border-color:"#cccc99";
		height: 20px;
		text-align: center;
	}
	.all th{
		border:solid #000 1px;
		border-color:"#cccc99";
		height: 20px;
	}
	button{
		padding: 6px;
		background-color: rgb(241, 235, 235);;
		box-sizing: border-box;
		border-width: 1px;
		border-style: solid;
		border-color: rgba(121, 121, 121, 1);
		border-radius: 2px;
		-moz-box-shadow: none;
		-webkit-box-shadow: none;
		box-shadow: none;
		font-size: 14px;
		color: #666666;
		cursor: pointer;

	}
	
	#myform div{
		margin-top:10px;
	}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<div style="margin:10px;">
						<div class="info" style="font-size:16px;font-family: 'Arial Negreta','Arial';font-weight: 700;">{pigcms{:L_("收入记录")}  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

              <a href="javascript:void(0);" onclick="exports();" style="width: 100px;margin-bottom: 5px; display:<if condition="$config['single_system_type'] neq 'village_group'">block<else/>none</if>;"><button>{pigcms{:L_("导出")}EXCEL</button></a>

						<if condition="$config.open_extra_price eq 1"><a href="{pigcms{:U('Merchant_money/score_log')}" ><button>{pigcms{:L_("线下")}{pigcms{$config.score_name}{pigcms{:L_("记录")}</button></a></if></div> 
						<form id="myform" method="get" action="" style="display:inline;">
                            <input type="hidden" name="g" value="Merchant"/>
                            <input type="hidden" name="c" value="Merchant_money"/>
                            <input type="hidden" name="a" value="income_list"/>
                            <div>
								{pigcms{:L_("业务类型：")}
								<select name="order_type">
									<volist name="alias_name" id="vo">
										<option value="{pigcms{$key}" <if condition="$order_type eq $key">selected=selected</if>>{pigcms{$vo}</option>
									</volist>
								</select>
							</div>
							<div>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("店铺：")}<select id="store_id" name="store_id" onchange="javascript:frmselect.submit()">
										<option value="">{pigcms{:L_("选择店铺")}</option>
										<volist name="store_list" id="vo">
											<option value="{pigcms{$vo.store_id}" <if condition="$store_id eq $vo['store_id']" >selected="selected"</if>>{pigcms{$vo.name}</option>
										</volist>
									</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							</div>
							<div>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("订单：")}<input type="text" name="order_id" value="{pigcms{$order_id}" placeholder="{pigcms{:L_("输入订单号")}" >
							</div>
							<input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
							<div>
							<div style="float:left"><font color="#000">{pigcms{:L_("时间筛选 ：")}</font></div>
							<input type="text" class="input fl" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="text" class="input fl" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="submit" value="{pigcms{:L_("查询")}"> &nbsp;&nbsp;&nbsp;
							<span style="font-size:18px;color:red;">{pigcms{:L_("总计 ：")}{pigcms{$total}
								<notempty name='lakalaArchive'>
									,&nbsp;{pigcms{:L_("在线分账收入")}：{pigcms{$third_pay_merchant_get}
								</notempty>
								<div style="font-size:12px;display:inline">({pigcms{:L_("消费收入")}:{pigcms{$income_total|floatval}
								<if condition="$recharge_total gt 0">{pigcms{:L_("，充值总额为：")}{pigcms{$recharge_total|floatval}</if>
								<if condition="$system_take gt 0">{pigcms{:L_("，平台佣金汇总：")}{pigcms{$system_take|floatval}</if>
								，订单总额：{pigcms{$total_order_money}，退款总额：{pigcms{$total_refund}，
							<php> if(C('customization.cash_back') == 1){</php>
								返还订单总额：{pigcms{$total_back_order_money}，汇总返还总额：{pigcms{$total_back_price}
							<php> }</php>
							)</div> <if condition="$config.open_extra_price eq 1">
								&nbsp;&nbsp;&nbsp;{pigcms{:L_("送出")}{pigcms{$config.score_name}{pigcms{:L_("总计 ：")}{pigcms{$total_score|floatval} 个{pigcms{$config.score_name}
							</if><div style="font-size:12px;display:inline"></div>
						</span>
							</div>
						</form>
					</div>

					<div>

					<div  class="alert alert-info">
						<!-- <a href="#" class="close" data-dismiss="alert">&times;</a> -->
						<p>【消费收入】: 用户消费订单总额，不包含商家在线充值金额，统计列表中“结算金额”；</p>

						<if condition="$recharge_total gt 0">
						<p>【充值总额】: 商家在线充值的总金额；</p>
						</if>

						<if condition="$system_take gt 0">
						<p>【平台佣金汇总】：订单平台抽佣总金额，统计列表中“平台服务费”；</p>
						</if>

						<p>【订单总额】：用户消费订单总金额，不包含商家在线充值金额，统计列表中“订单总额”；</p>

						<php> if(C('customization.cash_back') == 1){</php>
						<p>【返还订单总额】：优惠买单返还订单总金额，不包含返还金额为0的订单；</p>
						<p>【汇总返还总金额】：优惠买单返还金额，统计列表中“返还金额”。</p>
						<php> }</php>

						<if condition="$config.open_extra_price eq 1">
						<p>【送出{pigcms{$config.score_name}】：用户消费赠送{pigcms{$config.score_name}总额，统计列表中“送出{pigcms{$config.score_name}数”。</p>
						</if>
					</div>

					</div>
					<div class="tabbable" >
								<div class="row">					
									<div class="col-xs-13">		
										<div class="grid-view">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th>{pigcms{:L_("店铺名称")}</th>
														<th>{pigcms{:L_("订单号")}</th>
														<th>{pigcms{:L_("订单类型")}</th>
														<th>{pigcms{:L_("详情")}</th>
														<th width="160px">{pigcms{:L_("订单总额")}</th>
														<th>{pigcms{:L_("结算金额")}</th>
														<if condition="$config.open_extra_price eq 1">
															<th>{pigcms{:L_("送出")}{pigcms{$config.score_name}数</th>
															<th>{pigcms{:L_("用户消费")}{pigcms{$config.score_name}数</th>
														</if>
														<!-- <th>{pigcms{:L_("平台佣金")}<font color="red" size="1">({pigcms{:L_("提现代表手续费")})</font></th> -->
														<th>{pigcms{:L_("平台服务费比例")}</th>
														<th>{pigcms{:L_("平台服务费")}</th>
														<php> if(C('customization.cash_back') == 1){</php>
															<th>{pigcms{:L_("返还比例")}</th>
															<th>{pigcms{:L_("返还金额")}</th>
														<php> }</php>
														<th>{pigcms{:L_("支付时间")}</th>
														<th>{pigcms{:L_("当前商家余额")}</th>
														<th class="am-text-middle" width="100px">{pigcms{:L_("操作")}</th>
													</tr>
												</thead>
												<tbody> 
													<if condition="$income_list">
														<volist name="income_list" id="vo">
															<tr>
																<if condition="$vo.type eq 'weidian'">
																	<td>{pigcms{:L_("微店")}</td>
																<elseif condition='$vo.is_autotrophic eq 1' />
																	<td>{pigcms{:L_("平台自营")}</td>
																<else />
																	<td><if condition="$vo.store_id gt 0">{pigcms{$vo.store_name}</if></td>
																</if>

																<td>
																	<if condition="$vo.type eq 'withdraw'">w_{pigcms{$vo.id}<else />{pigcms{$vo.order_id}</if></td>
																<td>
                                                                    <php>if ($vo['type'] == 'marketmulti' || $vo['type'] == 'market' || $vo['type'] == 'market_spread'){</php>
                                                                    {pigcms{:L_("批发")}
                                                                    <php>}elseif ($vo['type'] == 'market_integral_mall') {</php>
                                                                    {pigcms{:L_("批发-积分商城")}
                                                                    <php>}elseif ($vo['type'] == 'dining') {</php>
                                                                    {pigcms{$config['meal_alias_name']}
                                                                    <php>}elseif ($vo['type'] == 'shop_offline') {</php>
                                  {pigcms{:L_("线下零售")}
                                  <php>}else{</php>
                                                                        <?php echo $alias_name[$vo['type']];?>
                                                                    <php>}</php>
                                                                </td>
																<!-- <td>{pigcms{:msubstr($vo['desc'],0,80,true,'utf-8')}</td> -->
																<td>{pigcms{$vo['desc']}</td>
																<td>
																	{pigcms{$vo.total_money|floatval}
																	<if condition="$vo['pay_money'] gt 0">
																		（{pigcms{:L_("实付金额")}：{pigcms{$vo.pay_money|floatval}）
																	</if>
																</td>
																<td><if condition="$vo.income eq 1"><font color="#2bb8aa">+{pigcms{$vo.money|floatval}</font><elseif condition="$vo.income eq 2" /><font color="#f76120">-{pigcms{$vo.money|floatval}</font></if></td>
																<if condition="$config.open_extra_price eq 1">
																	<td>{pigcms{$vo.score|intval}</td>
																	<td>{pigcms{$vo.score_count|floatval}</td>
																</if>
																<!-- <td><font color="#5167de">{pigcms{$vo.system_take} <if condition="$vo['type'] eq 'withdraw' AND $vo['percent'] gt 0"> {pigcms{:L_("（提现手续费）")}<elseif condition="$vo['system_take'] gt 0" />{pigcms{:L_("（抽成比例")} {pigcms{$vo.percent} %）</if></font></td> -->
																<td><font color="#5167de"><if condition="$vo['type'] eq 'withdraw' AND $vo['percent'] gt 0"> {pigcms{:L_("提现手续费")}<elseif condition="$vo['system_take'] gt 0" />{pigcms{$vo.percent} %</if></font></td>
																<td><font color="#5167de">
                                    <if condition="$vo.income eq 1"><font color="#2bb8aa">+{pigcms{$vo.system_take|floatval}</font><elseif condition="$vo.income eq 2" /><font color="#f76120">-{pigcms{$vo.system_take|floatval}</font></if>
                                    <if condition="$vo['live_money'] gt 0"> (含直播佣金{pigcms{$vo.live_money})</if></font></td>
																<php> if(C('customization.cash_back') == 1){</php>
																	<td><font color="#5167de">{pigcms{$vo.back_rate}%</span></td>
																	<td><font color="#5167de">{pigcms{$vo.back_price}</span></td>
																<php> }</php>
																<td><if condition="$vo['use_time'] gt 0">{pigcms{$vo.use_time|date="Y-m-d H:i:s",###}</if></td>
																<td><font color="#5167de">{pigcms{$vo.now_mer_money}</font></td>
																<td class="am-text-middle">
																	<div class="tpl-table-black-operation">
																		<if condition="$vo.type eq 'group'">
																			<a class="layer-iframe" href="{pigcms{:U('Merchant/Group/order_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
                                                                        <elseif condition="$vo.type eq 'village_group'" />
                                                                            <!-- <a class="layer-iframe" href="{pigcms{:U('Merchant/Village_group/join_order_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("查看详情")}</a> -->
																		<elseif condition="$vo.type eq 'meal'" />
																			<a class="layer-iframe" href="{pigcms{:U('Merchant/Foodshop/order_detail',array('order_id'=>$vo['order_id'],'store_id'=>$vo['store_id']))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'appoint'" />
																			<a class="layer-iframe" href="{pigcms{:U('Appoint/order_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'weidian'" />
																			<a class="layer-iframe" href="{pigcms{:U('Orderdetail/weidain_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'waimai'" />
																			<a class="layer-iframe" href="{pigcms{:U('Waimai/order_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'wxapp'" />
																			<a class="layer-iframe" href="{pigcms{:U('Orderdetail/wxapp_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'shop' OR $vo.type eq 'shop_offline'" />
																			<a class="layer-iframe" href="{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'store' OR $vo.type eq 'cash'" />
																			<a class="layer-iframe" href="{pigcms{:U('Orderdetail/store_detail',array('order_id'=>$vo['order_id'],'from'=>'bill'))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'withdraw'" />
																			<a class="layer-iframe" href="{pigcms{:U('Merchant_money/withdraw_order_info',array('id'=>$vo['order_id']))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'coupon'" />
																			<a class="layer-iframe" href="{pigcms{:U('Orderdetail/coupon_detail',array('order_id'=>$vo['order_id'],'mer_id'=>$mer_id))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'yydb'" />
																			<a class="layer-iframe" href="{pigcms{:U('Orderdetail/yydb_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'market'" />
																			<a class="layer-iframe" href="{pigcms{:U('Market/order_detail',array('order_id'=>$vo['order_id']))}&type=<if condition="$vo['income'] eq 1">sell<else />buy</if>">{pigcms{:L_("查看详情")}</a>
																		<elseif condition="$vo.type eq 'marketmulti'" />
																			<a class="layer-iframe" href="{pigcms{:U('Market/order_detail',array('fid'=>$vo['order_id']))}&type=<if condition="$vo['income'] eq 1">sell<else />buy</if>">{pigcms{:L_("查看详情")}</a>
																		<else/>
																			- -
																		</if>
																	</div>
																</td>
															</tr>
														</volist>
												
														
														<tr class="odd">
															<td colspan="13" id="show_count"></td>
														</tr>
														<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>	
													<else />
														<tr class="odd"><td class="textcenter red" colspan="13" >{pigcms{:L_("暂时还没有收入记录")}</td></tr>
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
        var thiswidth = $(this).data('width');
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("操作订单")}',
			padding: 0,
			width: thiswidth,
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
	

});

		 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Bill/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
