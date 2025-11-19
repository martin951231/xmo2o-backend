<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta name="format-detection" content="telephone=no"/>
	<meta charset="utf-8">
	<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
	<script src="{pigcms{$static_path}js/iscroll.js"></script>
	<script src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js"></script>
	<script src="{pigcms{$static_public}js/layer/layer.js"></script>
	<script>var ajax_goods_list = "{pigcms{:U('Store/ajax_shop_goods')}", store_id = "{pigcms{:$store_id}", arrival_pay = "{pigcms{:U('arrival_pay')}", changePriceUrl = "{pigcms{:U('shop_change_price')}", ajax_card_url = "{pigcms{:U('Store/ajax_card')}", shop_order_save = "{pigcms{:U('Store/shop_order_save')}", shop_arrival_check = "{pigcms{:U('shop_arrival_check')}",verify_phone_url="{pigcms{:U('balance_verify_phone')}",sms_code_url="{pigcms{:U('sendCode')}",print_prev_order="{pigcms{:U('print_prev_order')}";

	</script>
	<script type="text/javascript" src="{pigcms{$static_path}js/market_old.js?{pigcms{$_SERVER['REQUEST_TIME']}"></script>
	<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]-->
    <style type="text/css">
        .fix .set_end .pay li{
            width: auto;
        }
    </style>
</head>

<body>
	<include file="public_header"/>
	<div class="headerBox">
		<div class="txt">{pigcms{:L_("线下零售")}</div>
		<div class="back" data-url="{pigcms{:U('shop_list',array('shop_type'=>$shop_type))}" title="{pigcms{:L_("返回订单列表")}"></div>
		<div class="reload" title="{pigcms{:L_('同步库存')}"></div>
	</div>
	<div class="left">
		<div class="left_n">
			<div class="left_input">
				<input placeholder="{pigcms{:L_("条码")}/{pigcms{:L_("商品名称")}" class="input" id="number_or_name"/>
				<div class="code"></div>
				<div class="del"></div>
			</div>
			<div class="left_tab">
				<div class="tab_top"></div>
				<div class="tab_list">
					<table cellpadding="0" cellspacing="0" border="0">
						<tr class="clr">
							<td class="dy fl">{pigcms{:L_("店员")}: {pigcms{$name}</td>
							<td class="rq fr">{pigcms{:L_("日期")}: {pigcms{$date}</td>
						</tr>
						<tr class="clr">
							<td class="hyh fl left_card_id">{pigcms{:L_("会员号: 无")}</td>
							<td class="ye fr">{pigcms{:L_("余额")}: <span class="jg left_card_money">{pigcms{$config['Currency_symbol']}0</span></td>
						</tr>
					</table>
				</div>
				<div class="tabx_list">
					<table cellpadding="0" cellspacing="0" border="0">
						<tr class="top">
							<th width="42">项</th>
							<th width="235">{pigcms{:L_("名称")}</th>
							<th width="55">{pigcms{:L_("数量")}</th>
							<th width="115">{pigcms{:L_("金额（")}{pigcms{$config['Currency_txt']}）</th>
						</tr>
					</table>
					<div class="roll_table">
						<div class="tabcon">
							<table cellpadding="0" cellspacing="0" border="0" class="slide">
							</table>
						</div>
					</div>
				</div>
				<div class="number clr">
					<div class="fr">
					{pigcms{:L_("合计")}: <span class="ef2">0</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="right clr">
		<div class="right_1uick fl">
			<ul>
				<li class="js" data-opt="/">{pigcms{:L_("结算")} /</li>
				<li class="jia num" data-opt="+">+</li>
				<li class="jian num" data-opt="-">-</li>
				<li class="sl p12" data-opt="*">{pigcms{:L_("数量")} *</li>
				<li class="del p12" data-opt="u">{pigcms{:L_("删除")} u</li>
				<li class="gd p12" data-opt="h">{pigcms{:L_("挂单")} h <em>0</em></li>
				<li class="hyk p12" data-opt="y">{pigcms{:L_("会员卡")} y</li>
				<li class="qk p12" data-opt="d">{pigcms{:L_("清空")} d</li>
                <li class="pt p12" data-opt="p">{pigcms{:L_("补打印")} p</li>
			</ul>
		</div>
		<div class="right_list">
			<div class="right_top">
				<div class="swiper-button-prev"></div>
				<div class="swiper-container">
					<div class="swiper-wrapper">
					</div>
				</div>
				<div class="swiper-button-next"></div>
			</div>
			<div class="right_end">
				<div class="tab_ul">
				</div>
			</div>
		</div>
		<div class="prompt clr"><em></em>{pigcms{:L_("提示：")}<span class="prompt_span"></span><!--span class="prompt_text"> </span--></div>
	</div>

	<div class="fix settlement">
		<div class="set_top">
			<div class="set">{pigcms{:L_("结算订单")}</div>
			<div class="return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="set_end clr">
			<div class="set_fl fl">
				<div class="number bf">
					<div class="tit clr">
						<div class="fl">{pigcms{:L_("订单编号")}</div>
						<div class="fr">{pigcms{:L_("订单总价")}</div>
					</div>
					<div class="pri clr">
						<div class="fl" id="order_id">0</div>
						<div class="fr on" id="order_price" data-price="0">
							<span>{pigcms{$config['Currency_symbol']}0</span>
							<div class="disk"></div>
						</div>
					</div>
				</div>
				<div class="discount bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("商家会员卡折扣")}</div>
					<div class="fr" id="card_discount" data-discount="10">{pigcms{:L_("无折扣")}</div>
				</div>
				<div class="choice bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("商家优惠券")}</div>
					<div class="fr cho_yhj" data-price="0" id="coupon_price" data-coupon="0">{pigcms{:L_("选择优惠券")}</div>
				</div>
				<div class="discount bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("应付金额")}</div>
					<div class="fr">
						<span id="pay_price" data-old_price="0" data-price="0">{pigcms{$config['Currency_symbol']}0</span>
						<input type="hidden" id="change_price_reason" />
						<input type="hidden" id="isChange" value="0"/>

						<if condition="$is_change eq 1">
						<span class="mod_hui" style="display:none;">
							<em>{pigcms{:L_("修改前：")}{pigcms{$config['Currency_symbol']}100</em>
							<em>{pigcms{:L_("备注：哈哈")}</em>
						</span>
						<span class="mod_hand">{pigcms{:L_("修改金额")}</span>
						</if>
					</div>
				</div>
				<div class="credit bf d4">
					<ul class="clr">
						<li class="wb3">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("会员卡号")}</h2>
								<div class="text" id="user_card_number">无</div>
							</div>
						</li>
						<li class="wb2">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("当前余额")}</h2>
								<div class="text ef2" id="user_card_total" data-price="0">{pigcms{$config['Currency_symbol']}0</div>
							</div>
						</li>
						<li class="w3">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("本次可用")}</h2>
								<div class="text ef2"><span id="user_card_money" data-price="0">{pigcms{$config['Currency_symbol']}0</span> <span class="modify">{pigcms{:L_("修改金额")}</span> </div>
							</div>
						</li>
						<!--li class="w2">
							<div class="li_n">
								<div class="text"><span class="use">{pigcms{:L_("使用会员卡")}</span></div>
							</div>
						</li-->
					</ul>
				</div>
				<div class="still bf d4 clr">
					<div class="fr" id="go_pay_money" data-price="0"><span class="still_zf">{pigcms{:L_("还需支付：")}</span>{pigcms{$config['Currency_symbol']}0</div>
				</div>
				<div class="pay">
					<ul class="clr">
						<li class="line">
							<div class="line_n">
							<span>{pigcms{:L_("现金支付")}</span>
							</div>
						</li>
						<li class="wx">
							<div class="line_n">
							<span>{pigcms{:L_("微信支付")}</span>
							</div>
						</li>
						<if condition="$config.arrival_alipay_open eq 1">
						<li class="alipay">
							<div class="line_n">
							<span>{pigcms{:L_("支付宝")}</span>
							</div>
						</li>
						</if>
						<if condition="$config.pay_yzfpay_open eq 1">
						<li class="yzf_pay">
							<div class="line_n">
							<span>{pigcms{:L_("翼支付")}</span>
							</div>
						</li>
						</if>
                        <if condition="$config.open_company_advance_deposit eq 1">
                        <li class="qiye_pay">
                            <div class="line_n">
                                <span>{pigcms{:L_("企业预存款支付")}</span>
                            </div>
                        </li>
                        </if>
						<li class="balance_pay">
							<div class="line_n">
							<span>{pigcms{:L_("余额支付")}</span>
							</div>
						</li>
						<li class="confirm">
							<span>{pigcms{:L_("确认支付")}Ent</span>
						</li>
					</ul>
				</div>
			</div>
			<div class="set_fr fr">
				<ul>
					<li class="cancel">
						<div class="line_n">{pigcms{:L_("整单取消")} z</div>
					</li>
					<li class="on-line">
						<div class="line_n">{pigcms{:L_("用户扫码支付")}</div>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="fix guadan">
		<div class="gua_top">{pigcms{:L_("挂单")}</div>
		<div class="gua_end clr">
			<div class="fl member">
				<h2 class="member_id">{pigcms{:L_("挂单号")}</h2>
				<div class="leaguer">
					<ul>
						<li></li>
						<li></li>
					</ul>
				</div>
			</div>
			<div class="member_list">
				<table class="top">
					<tr>
						<th width="48">{pigcms{:L_("行号")}</th>
						<th width="138">{pigcms{:L_("货号")}</th>
						<th width="187">{pigcms{:L_("商品")}</th>
						<th width="50">{pigcms{:L_("数量")}</th>
						<th width="50">{pigcms{:L_("售价")}</th>
						<th width="75">{pigcms{:L_("小计")}</th>
					</tr>
				</table>
				<div class="tab_end">
					<div class="tab_table">
					</div>
				</div>
			</div>
		</div>
		<div class="operation clr">
			<div class="fl op_gy op_del">{pigcms{:L_("删除")} F4</div>
			<div class="fr op_gy op_confirm">{pigcms{:L_("确认")} Ent</div>
			<div class="fr op_gy op_cancel">{pigcms{:L_("取消")} Esc</div>
		</div>
	</div>


	<div class="fix card">
		<div class="card_top">{pigcms{:L_("会员卡")}</div>
		<div class="card_end clr">
			<div class="inbox_top clr">
				<div class="inbox fl">
					<input type="text" id="card_value" placeholder="{pigcms{:L_("会员卡")}/{pigcms{:L_("手机号")}/{pigcms{:L_("昵称")}" />
					<em></em>
				</div>
				<div class="query fr clr">
					<div class="fr query_qr">{pigcms{:L_("确认使用")}</div>
					<div class="fr query_cx">{pigcms{:L_("查询")}</div>
				</div>
			</div>
			<div class="information">
				<h2 class="h2_top"><span>{pigcms{:L_("会员信息")}</span></h2>
				<ul class="clr">
					<li class="clr">
						<span>{pigcms{:L_("会员卡号：")}</span>
						<div class="p95" id="card_number"></div>
					</li>
					<li class="clr">
						<span>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("名：")}</span>
						<div class="p95" id="card_name"></div>
					</li>
					<li class="clr">
						<span>{pigcms{:L_("实体卡号：")}</span>
						<div class="p95" id="card_number2"></div>
					</li>
					<li class="clr">
						<span>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("别：")}</span>
						<div class="p95" id="card_sex"></div>
					</li>
					<li class="clr">
						<span>{pigcms{:L_("手机号码：")}</span>
						<div class="p95" id="card_phone"></div>
					</li>
					<li class="clr">
						<span>折&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{pigcms{:L_("扣：")}</span>
						<div class="p95" id="card_discount_p"></div>
					</li>
					<li class="bot">
						<div class="p15" id="card_score">{pigcms{:L_("可用")}{pigcms{$config['score_name']}：0</div>
					</li>
					<li class="bot">
						<div class="p15">{pigcms{:L_("储值余额：")}<em id="card_money">{pigcms{$config['Currency_symbol']}0</em></div>
					</li>
				</ul>
				<!-- {pigcms{:L_("没有数据时")} -->
				<div class="img" style="display: none;"><img src="{pigcms{$static_path}images/myxg_03.jpg"></div>
				<!-- {pigcms{:L_("没有数据时")} -->
			</div>
		</div>
	</div>

	<div class="fix payment">
		<div class="payment_top clr">
			<div class="fl pay_xs">{pigcms{:L_("线上支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="payment_end">
			<div class="pay_wc">
				<div class="pay_nc">
					<h2 class="tit">{pigcms{:L_("用户")}<if condition="$config['cash_pay_qrcode']">{pigcms{:L_("微信")}</if>{pigcms{:L_("扫码支付")}</h2>
					<img src="" style="width:198px;height:198px;" id="pay_qrcode_url"/>
				</div>
			</div>
		</div>
	</div>

	<div class="fix chat wx">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("微信支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("扫描用户微信付款码支付")}</h2>
				<div class="input clr">
					<input type="text" class="port fl" value="" id="weixin_txt">
					<div class="firm fr" data-paymethod="weixin">{pigcms{:L_("确认支付")}</div>
				</div>
				<p>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户微信中的码，再写入。如果扫码提示微信错误，可以关闭本页面重新创建订单。")}</p>
			</div>
		</div>
	</div>


	<div class="fix balance">
		<div class="chat_top clr">
			<div class="fl wx_chat" style="padding: 1px 40px;">{pigcms{:L_("平台余额支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
			<div class="fr back_check" style="display:none;">{pigcms{:L_("返回上一步")}</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("只有在平台账户绑定手机号的用户才能使用平台余额支付")}</h2>.
				<div class="input clr check_phone" >
					<input type="text" class="port fl" value="" placeholder="{pigcms{:L_("请输入手机号")}" id="balance_phone">
					<div class="check fr" id="verify_phone">{pigcms{:L_("下一步")}</div>
				</div>
				<div class="input clr confirm_pay" style="display:none;">
					<input type="text" class="port fl" value="" placeholder="{pigcms{:L_("请输入验证码")}" id="smscode" >
					<div class="sms fr" data-second="" >{pigcms{:L_("发送验证码")}</div>
					<br><br><br><br><br>
					<div class="firm" data-paymethod="balance">{pigcms{:L_("确认支付")}</div>
				</div>
				<p></p>
			</div>
		</div>
	</div>

	<div class="fix chat alip ">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("支付宝")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("扫描用户支付宝付款码支付")}</h2>
				<div class="input clr">
					<input type="text" class="port fl" value="" id="alipay_txt">
					<div class="firm fr" data-paymethod="alipay">{pigcms{:L_("确认支付")}</div>
				</div>
				<p>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户支付宝中的码，再写入。如果扫码提示支付宝错误，可以关闭本页面重新创建订单。")}</p>
			</div>
		</div>
	</div>

    <div class="fix chat qiye">
        <div class="chat_top clr">
            <div class="fl wx_chat">{pigcms{:L_("企业预存款支付")}</div>
            <div class="fr return">{pigcms{:L_("返回")} Esc</div>
        </div>
        <div class="chat_end">
            <div class="chat_n">
                <h2>{pigcms{:L_("扫描用户企业预存款付款码支付")}</h2>
                <div class="input clr">
                    <input type="text" class="port fl" value="" id="qiye_txt">
                    <div class="firm fr" data-paymethod="qiye">{pigcms{:L_("确认支付")}</div>
                </div>
                <p>{pigcms{:L_("建议使用扫码枪直接扫描得到值")}</p>
            </div>
        </div>
    </div>

	<div class="fix chat yzf">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("翼支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("扫描用户翼支付付款码支付")}</h2>
				<div class="input clr">
					<input type="text" class="port fl" value="" id="yzf_txt">
					<div class="firm fr" data-paymethod="yzfpay">{pigcms{:L_("确认支付")}</div>
				</div>
				<p>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户翼支付中的码，再写入。如果扫码提示翼支付错误，可以关闭本页面重新创建订单。")}</p>
			</div>
		</div>
	</div>

	<div class="fix coupon">
		<div class="coupon_top">
			<span>{pigcms{:L_("选择优惠券")}</span>
		</div>
		<div class="coupon_end">
			<div class="swiper-button-prev"></div>
			<div class="swiper-container">
				<div class="swiper-wrapper">
				</div>
			</div>
			<div class="swiper-button-next"></div>
		</div>
	</div>

	<div class="fix container slcontainer counter only_counter" data-type="0">
		<div class="con_pos">
			<h2>{pigcms{:L_("请输入数量")}</h2>
			<input type="text" id="text" name="text" class="text" />
			<div class="buttons">
				<input type="button" value="7" class="button show">
				<input type="button" value="8" class="button show">
				<input type="button" value="9" class="button show">
				<input type="button" value="{pigcms{:L_("退格")}" class="button funback">
				<input type="button" value="4" class="button show">
				<input type="button" value="5" class="button show">
				<input type="button" value="6" class="button show">
				<input type="button" value="{pigcms{:L_("取消")}" class="button cancel funclear">
				<input type="button" value="1" class="button show">
				<input type="button" value="2" class="button show">
				<input type="button" value="3" class="button show">
				<input type="button" value=" " class="button"  style="border: none; background: none;" >
				<input type="button" value="00" class="button show">
				<input type="button" value="0" class="button show">
				<input type="button" value="." class="button point" id="intdd1">
				<input type="button" value="{pigcms{:L_("确认")}" class="button confirm getResult" >
			</div>
			<input type="button"  class="jt funclear">
		</div>
	</div>

	<div class="fix linepay line_600 offline_pay">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("线下支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="linepay_end">
			<div class="over">
				<div class="pay_top clr">
					<div class="pay_fl">
						<ul>
							<li>
								<div class="pay_n clr">
								<div class="fl li_fl">{pigcms{:L_("应收")}</div>
								<div class="fr li_fr" id="offline_total_money" data-price="0">0</div>
								</div>
							</li>
							<li>
								<div class="pay_n pay_input clr" >
								<div class="fl li_fl">{pigcms{:L_("已收")}</div>
								<input type="text" id="offline_finish_money" placeholder="{pigcms{:L_("输入收取的金额")}" name="text" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_n1 clr">
									<div class="fl li_fl">{pigcms{:L_("待收")}</div>
									<div class="fr li_fr" id="offline_wait_money" data-price="0">0</div>
								</div>
								<div class="pay_n2 clr">
								<div class="fl li_fl">{pigcms{:L_("找零")}</div>
								<div class="fr li_fr" id="offline_return_money" data-price="0">0</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
				<div class="pay_end offline_pay">
					<ul class="clr">
                    <if condition="empty($offline_pay_list)">
						<li class="zfb" data-id="0">{pigcms{:L_("现金支付")}</li>
                    </if>
						<volist name="offline_pay_list" id="row">
						<li class="wx" data-id="{pigcms{$row['id']}">{pigcms{$row['name']}</li>
						</volist>
					</ul>
				</div>
			</div>
		</div>
	</div>


	<div class="fix linepay line_600 change_price">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("修改金额")}</div>
			<div class="fr return">{pigcms{:L_("返回")} Esc</div>
		</div>
		<div class="linepay_end">
			<div class="over">
				<div class="pay_top clr">
					<div class="pay_fl">
						<ul>
							<li>
								<div class="pay_n  pay_input clr">
									<div class="fl li_fl">{pigcms{:L_("修改值")}</div>
									<input type="number" placeholder="{pigcms{:L_("这里输入您想要的修改的金额")}" name="change_price" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_n pay_input clr" style="line-height: 50px;">
									<div class="fl li_fl">{pigcms{:L_("备注")}</div>
									<input type="text" placeholder="{pigcms{:L_("输入备注，限定8字以内")}" name="change_price_reason" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_35 clr">
								<div class="fl li_fl">{pigcms{:L_("当前值")}</div>
								<div class="fr li_fr" data-price="0" id="show_last_price">0</div>
								</div>
							</li>
						</ul>
					</div>

				</div>
				<div class="pay_end pay_ends">
					<ul class="clr">
						<li class="hf">
							<span>{pigcms{:L_("恢复初始价格")}</span>
						</li>
						<li class="qr">
							<span>{pigcms{:L_("确认修改")}</span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="shadow"></div>
	<div class="shadow_two"></div>
	<script>
		setInterval(function(){
			$.post("/store.php?g=Merchant&c=Store&a=ping");
		},60000);
	</script>
</body>
</html>
