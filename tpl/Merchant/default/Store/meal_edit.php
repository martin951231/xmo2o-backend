<include file="Store:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cutlery"></i>
				{pigcms{$config.meal_alias_name}{pigcms{:L_("订单列表")}
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post" action="">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("订单号")}</label></label>
									<label class="col-sm-3">{pigcms{$order.order_id}</label>
								</div>
								<div class="form-group">
									<input type="hidden" name="order_id" value="{pigcms{$order.order_id}">
									<label class="col-sm-1"><label for="contact_name">{pigcms{$config.meal_alias_name}{pigcms{:L_("人姓名")}</label></label>
									<label class="col-sm-3">{pigcms{$order.name}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name">{pigcms{$config.meal_alias_name}{pigcms{:L_("人电话")}</label></label>
									<label class="col-sm-3">{pigcms{$order.phone}</label>
								</div>
								<if condition="$order['address']">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_info">{pigcms{$config.meal_alias_name}{pigcms{:L_("人地址")}</label></label>
									<label class="col-sm-3">{pigcms{$order.address}</label>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_info">{pigcms{:L_("餐台号")}</label></label>
									<label class="col-sm-3">{pigcms{$order.tablename}</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_info">{pigcms{:L_("下单时间")}</label></label>
									<label class="col-sm-3">{pigcms{$order.dateline|date="Y-m-d H:i:s",###}</label>
								</div>
								
								<div class="form-group" style="margin-bottom:-35px;">
									<label class="col-sm-1"><label for="AutoreplySystem_img">{pigcms{:L_("菜单列表")}</label></label>
								</div>
								<div class="form-group" style="width:417px;padding-left:152px;">
									<volist name="order['info']" id="info">
									<div>{pigcms{$info['name']}:{pigcms{$config['Currency_symbol']}{pigcms{$info['price']} * {pigcms{$info['num']}</div>
									</volist>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("订单总额")} </label></label>
									<label class="col-sm-3">{pigcms{$config['Currency_symbol']}<if condition="$order['total_price'] gt 0">{pigcms{$order['total_price']}<else />{pigcms{$order.price}</if></label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("优惠金额")} </label></label>
									<label class="col-sm-3">{pigcms{$config['Currency_symbol']}{pigcms{$order['minus_price']}</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("实收金额")} </label></label>
									<label class="col-sm-3">{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price'] - $order['minus_price']}</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("应收取现金")} </label></label>
									<label class="col-sm-3">
									<font color="red">
									<if condition="$order['total_price'] gt 0">
									{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']-$order['minus_price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}{pigcms{$config['Currency_txt']}
									<else />
									{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}{pigcms{$config['Currency_txt']}
									</if>
									</font>
									</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contant_url">{pigcms{:L_("顾客留言")}</label></label>
									<label class="col-sm-3"><if condition="$order['note']">{pigcms{$order.note|htmlspecialchars_decode=ENT_QUOTES}<elseif condition="$order['info'][0]['omark']"/>{pigcms{$order.info.0.omark}<else />{pigcms{:L_("顾客没有特殊说明")}</if></label>
								</div>
								
								<!--div class="form-group">
									<label class="col-sm-1"><label for="sorts">{pigcms{:L_("修改总价")}</label></label>
									<input class="col-sm-1" type="text" id="sorts" value="{pigcms{$order.price}" name="sorts"/>
								</div-->
								
								<div class="form-group">
									<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("付款状态")}</label></label>
									<label class="col-sm-3">
										<if condition="$order['paid'] eq 0">
										<span class="lbl" style="z-index:1;color:red;">{pigcms{:L_("未付款")}</span>
										<elseif condition="$order['paid'] eq 1" />
										<span class="lbl" style="z-index:1;color:green;">{pigcms{:L_("已付款")}</span>
										<elseif condition="$order['paid'] eq 2" />
										<span class="lbl" style="z-index:1;color:green;">{pigcms{:L_("已付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']+$order['merchant_balance']+$order['coupon_price']+$order['card_price']+$order['score_deducte']|floatval}</span>，
										<span class="lbl" style="z-index:1;color:red;">
											<if condition="$order['total_price'] gt 0">
											{pigcms{:L_("未付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']-$order['minus_price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}
											<else />
											{pigcms{:L_("未付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}
											</if>
										</span>
										</if>
									</label>
								</div>
								<if condition="$order['status'] eq 0" >
									<div class="form-group">
										<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("订单状态")}</label></label>
										<div class="radio">
											<label>
												<input name="status" value="1" type="radio" <if condition="$order['status'] eq 1" >checked="checked"</if>/>
												<span class="lbl" style="z-index: 1">{pigcms{:L_("已消费")}</span>
											</label>　
											<label>
												<input name="status" value="0" type="radio" <if condition="$order['status'] eq 0">checked="checked"</if>/>
												<span class="lbl" style="z-index: 1">{pigcms{:L_("未消费")}</span>
											</label>
										</div>	
										<span class="form_tips" style="color: red">{pigcms{:L_("注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，状态修改后就不能修改了")}</span>									
									</div>
								<elseif condition="$order['status'] eq 1" />
									<div class="form-group">
										<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("订单状态")}</label></label>
										<label class="col-sm-3">{pigcms{:L_("已消费")}</label>
									</div>
								<elseif condition="$order['status'] eq 2" />
									<div class="form-group">
										<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("订单状态")}</label></label>
										<label class="col-sm-3">{pigcms{:L_("已评价")}</label>
									</div>
								<elseif condition="$order['status'] eq 3" />
									<div class="form-group">
										<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("订单状态")}</label></label>
										<label class="col-sm-3">{pigcms{:L_("已退款")}</label>
									</div>
								<elseif condition="$order['status'] eq 4" />
									<div class="form-group">
										<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("订单状态")}</label></label>
										<label class="col-sm-3">{pigcms{:L_("已取消")}</label>
									</div>
								</if>
								<if condition="$order['store_uname']">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_info">{pigcms{:L_("上次处理订单人")}</label></label>
									<label class="col-sm-3">{pigcms{$order.store_uname}</label>
								</div>
								</if>
								<if condition="$order['status'] eq 0" >
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
								</if>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function(){
		$('#navbar,#sidebar,#breadcrumbs').hide();
		$('.main-content').css('margin-left','0');
	});
</script>
<include file="Public:footer"/>