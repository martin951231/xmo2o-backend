<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>提交订单</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/group_buy.css?001" rel="stylesheet"/>
	<style>
	    #buy dd {
	        font-size: .3rem;
	    }
	    #change_address .more:after {
	        top: .2rem;
	    }
	    #change_address h6 {
	        width: 4em;
	    }
	    h4 small {
	        color: #999;
	        display: inline-block;
	        padding-left: .2rem;
	    }
	    .good-name {
	        color: #666;
	    }

	    .good-left-count {
	        color: #2bb2a3;
	    }

	    .good-left-out {
	        color: #999;
	    }
	    .quantity.kv-line {
	        -webkit-box-align: center;
	    }

	    .campaign_tag {
	        position: static;
	        background: #ff8c00;
	        color: #fff;
	        line-height: 1.5;
	        display: inline-block;
	        padding: 0 .06rem;
	        text-align: center;
	        font-size: .24rem;
	        border-radius: .06rem;
	        vertical-align: text-bottom;
	    }

	    .amount>span {
	        display: block;
	    }

	    .J_campaign-value {
	        font-size: .24rem;
	        color: #999;
	    }

		.J_total-price {
			font-weight: 500;
			color: #F64031;;
			font-size: 16px;
	    }

	    .kv-line-r .btn, .kv-line-r .mt, .kv-line-r .input-weak {
	        margin-top: -.15rem;
	        margin-bottom: -.15rem;
	    }
	    .kv-line-r .kv-k {
	        display: block;
	    }
	    .kv-line .btn, .kv-line .mt, .kv-line .input-weak {
	        margin: -.15rem 0;
	    }

	    /*agreement*/
	    .agreement {
	        padding: .2rem;
	    }

	    .agreement li {
	        display: inline-block;
	        text-align: center;
	        width: 50%;
	        box-sizing: border-box;
	        color: #666;
	    }

	    .agreement li:nth-child(2n) {
	        padding-left: .14rem;
	    }

	    .agreement li:nth-child(1n) {
	        padding-right: .14rem;
	    }

	    .agreement li.active {
	        color: #6bbd00;
	    }

	    .agreement ul.btn-line li {
	        vertical-align: middle;
	        margin-top: .06rem;
	        margin-bottom: 0;
	    }

	    .agreement .text-icon {
	        margin-right: .14rem;
	        vertical-align: top;
	        height: 100%;
	    }

	    .agreement .agree .text-icon {
	        font-size: .4rem;
	        margin-right: .2rem;
	    }

	    label.disabled {
	        color: #ccc;
	    }
	    #birthday_wrap label.select {
	        width: 28%;
	        display: inline-block;
	        margin-right: .16rem;
	    }
	    #birthday_wrap .select select {
	        border: 1px solid #ccc;
	    }
	    #sms-captcha {
	        width: 100%;
	    }
		.btn_express{
			color: #fff;
			font-weight: 300;
			font-size: 16px;
			text-decoration: none;
			text-align: center;
			line-height: 34px;
			padding: 0px 15px;
			margin: 0;
			display: inline-block;
			cursor: pointer;
			border: none;
			box-sizing: border-box;
			transition-property: all;
			transition-duration: 0.3s;
			border-radius: 4px;
		}
		.pick_in_store_click{
			background-color:#fff;
			color:#000;
			border:1px solid #4A96D4;

		}
	    .post_package_click{
			background-color:#fff;
			color:#000;
			border:1px solid #4A96D4;

		}
		.pick_in_store{
			background-color: #4A96D4;
			border-color: #A5DE37;
		}
		.post_package{
			background-color: #4A96D4;
			border-color: #A5DE37;
		}
		input.mt.number {
		    -webkit-appearance: initial;
		    display: inline-block;
		    vertical-align: middle;
		    border: .02rem solid #ddd8ce;
		    border-radius: .06rem;
		    box-sizing: border-box;
		    text-align: center;
		    width: 1.2rem;
		    font-size: 0.28rem;
		    height: 0.28rem;
		    line-height: 0.28rem;
		    padding: 0.26rem;
		}
        .text_info {
            color: #333;
			font-size: 14px;
        }
		.btn-strong{
			background-color:#F64031;
		}
		body{
			background:#F8F8F8;
			font-size:14px;
		}
		dl.list{
			border-top:1px solid #F8F8F8;
			border-bottom:1px solid #F8F8F8;
		}
		dl.list dt, dl.list dd{
			border-bottom:1px solid #F3F3F3;
		}
		.kv-line-r>h6, .kv-line-r>.kv-k{
			font-weight: 500;
		}
		.group-combine-enter {
			position: relative;
			padding: 14px 4px;
			margin: 13px 0;
			box-sizing: border-box;
			background: #ffffff;
		}
		.group-combine-enter:after{
			content: " ";
			display: inline-block;
			height: 6px;
			width: 6px;
			border-width: 1px 1px 0 0;
			border-color: #999;
			border-style: solid;
			-webkit-transform: matrix(0.71, 0.71, -0.71, 0.71, 0, 0);
			transform: matrix(0.71, 0.71, -0.71, 0.71, 0, 0);
			position: relative;
			top: -2px;
			position: absolute;
			top: 50%;
			margin-top: -3px;
			right: 18px;
		}
		.group-combine-txt {
			position: absolute;
			top: 50%;
			left: 8px;
			transform: translate(0,-50%);
		}
	</style>
</head>
<body>
	<div id="tips" class="tips"></div>
	<form id="buy-form" action="{pigcms{:U('Group/buy',array('group_id'=>$now_group['group_id'],'new_group'=>1))}" method="POST" class="wrapper-list" autocomplete="off">
        <input name="specifications_id" type="hidden" value="{pigcms{$_GET['specifications_id']}">
		<input name="openid" type="hidden" value="{pigcms{$_GET['openid']}">
		<input name="share_openid" type="hidden" value="{pigcms{$_GET['share_openid']}">
        <input name="share_uid" type="hidden" value="{pigcms{$_GET['share_uid']}">
		<!-- 团购优惠组合入口 -->
		<if condition="$groupCombine">
			<div class="group-combine-enter link-url" data-url="/packapp/plat/pages/group/groupCombineDetail?combine_id={pigcms{$groupCombine['combine_id']}&group_id={pigcms{$now_group['group_id']}">
				<div class="group-combine-txt">
					<span>该商品正在参加超值优惠组合活动，点击</span><span class="view-more">了解详情</span>
				</div>
			</div>
		</if>
		<h4 style="margin-top:.4rem;margin-bottom:.4rem;">{pigcms{$now_group.s_name}</h4>
		<dl class="list">
			<dd>
				<dl>
                    <if condition="$group_specifications_info">
                        <dd class="dd-padding kv-line-r quantity">
                            <h6>规格：</h6>
                            <span style="position: absolute;right: 8px;top: 15px;">{pigcms{$group_specifications_info.specifications_name}</span>
                        </dd>
                    </if>
					<dd class="dd-padding kv-line-r">
						<h6>单价<if condition="$_GET['type'] eq 3 AND $now_group['start_discount'] egt 0 AND $now_group['start_discount'] neq 10">(团长优惠：{pigcms{$now_group['start_discount']/10}折)</if>：</h6>
						<p>{pigcms{$config['Currency_symbol']}{pigcms{$now_group['price']}<del><if condition="$now_group['extra_pay_price'] gt 0 AND $config.open_extra_price eq 1 ">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></del></p>
					</dd>
					<if condition="$now_group['vip_discount_type'] gt 0 AND ($level_discount gt 0 OR $group_discount gt 0)">
					<dd class="dd-padding kv-line-r">
						<h6>折扣方式</h6>
						<span style="position: absolute;right: 8px;top: 15px;"><if condition="$now_group['vip_discount_type'] eq 1">折扣最优<else />折上折</if></span>
					</dd>
					</if>
					<if condition="$now_group['vip_discount_type'] gt 0 AND $group_discount gt 0">
						<dd class="dd-padding kv-line-r">
							<span>商品折扣：</span>
							<span style="position:absolute;right:8px;top:15px;">折扣后单价<strong style="color:#FF4907;font-weight:500;margin:0 3px;">-{pigcms{$config['Currency_symbol']}{pigcms{$group_discount}</strong></span>
						</dd>
					</if>
					
					<if condition="!empty($leveloff) AND $level_discount gt 0">
						<dd class="dd-padding kv-line-r">
							<span>会员等级：<strong style="font-size:16px;color:#FF4907">{pigcms{$leveloff['lname']}</strong></span>
							<span style="position: absolute;right: 8px;top: 15px;">

							<if condition="$leveloff['type'] eq 1"><strong style="font-size:16px;color:#FF4907">折扣{pigcms{$leveloff['vv']/10|floatval}</strong>折<elseif condition="$leveloff['type'] eq 2" /><strong style="font-size:16px;color:#FF4907">每份立减{pigcms{$config['Currency_symbol']}{pigcms{$leveloff['vv']|floatval}</strong></if>
							<if condition="$now_group['extra_pay_price'] gt 0 AND $config.open_extra_price eq 1 ">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if>
							</span>
						</dd>
					</if>
					<dd class="dd-padding kv-line-r quantity">
						<h6>数量：</h6>
						<div class="kv-v">
                            <if condition="!$group_specifications_info">
                                <div class="stepper" data-com="stepper">
                                    <button type="button" class="btn btn-weak minus" disabled="disabled">-</button>&nbsp;<input class="mt number" type="tel" name="quantity" min="{pigcms{$now_group.once_min}" max="{pigcms{$now_group.once_max}" value="{pigcms{$now_group.once_min}"/>&nbsp;<button type="button" class="btn btn-weak plus">+</button>
                                </div>
                                <else/>
                                <div class="stepper" data-com="stepper">
                                    <button type="button" class="btn btn-weak minus" <if condition="$_GET['num'] elt $now_group['once_min']">disabled="disabled"</if>>-</button>&nbsp;<input class="mt number" type="tel" name="quantity" min="{pigcms{$now_group.once_min}" max="{pigcms{$now_group.once_max}" value="{pigcms{$_GET.num}"/>&nbsp;<button type="button" class="btn btn-weak plus">+</button>
                                </div>
                            </if>
						</div>
					</dd>
					<if condition="$now_group['tuan_type']==2">
						<dd class="dd-padding kv-line-r quantity">
							<h6>运费：</h6>
							<div class="kv-v">
								<div class="stepper" data-com="stepper">
									<span class="express_fee"><if condition="$now_group['express_template']['full_money'] gt 0">满{pigcms{$now_group['express_template']['full_money']|floatval}{pigcms{$config['Currency_txt']}包邮</if>   {pigcms{$config['Currency_symbol']}{pigcms{$now_group['express_fee']|floatval}</span>
								</div>
							</div>
						</dd>
					</if>
					<dd class="dd-padding kv-line-r">
						<h6>总价：</h6>
						<span class="kv-v" id="amount">
                            <if condition="!$group_specifications_info && !empty($leveloff)">
                                    <php>
                                        if($after_discount_finalprice>0){
                                            $price=$after_discount_finalprice;
                                        } else {
                                            $price=$now_group['price'];
                                        }
                                        $now_group['J_total-price']=$price*$now_group['once_min']+$now_group['express_fee'];
                                        $now_group['J_total-price']=round_number($now_group['J_total-price'],2);
                                    </php>
                                    <span class="J_total-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group['J_total-price']}<if condition="$now_group['extra_pay_price'] gt 0">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></span>
                                <elseif condition="!$group_specifications_info && empty($leveloff)"/>
                                    <span class="J_total-price">
                                    <php>
                                        if($after_discount_finalprice>0){
                                            $price=$after_discount_finalprice;
                                        } else {
                                            $price=$now_group['price'];
                                        }
                                        $now_group['J_total-price']=$price*$now_group['once_min']+$now_group['express_fee'];
                                        $now_group['J_total-price']=round_number($now_group['J_total-price'],2);
                                    </php>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_group['J_total-price']}<if condition="$now_group['extra_pay_price'] gt 0 AND $config.open_extra_price eq 1 ">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></span>
                                <elseif condition="$group_specifications_info && !empty($leveloff)"/>
                                    <php>
                                        if($after_discount_finalprice>0){
                                            $price=$after_discount_finalprice;
                                        } else {
                                            $price=$now_group['price'];
                                        }
                                        $now_group['J_total-price']=$price*$_GET['num']+$now_group['express_fee'];
                                        $now_group['J_total-price']=round_number($now_group['J_total-price'],2);
                                    </php>
                                    <span class="J_total-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group['J_total-price']}<if condition="$now_group['extra_pay_price'] gt 0">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></span>
                                <else/>
                                    <span class="J_total-price">
                                    <php>
                                        if($after_discount_finalprice>0){
                                            $price=$after_discount_finalprice;
                                        } else {
                                            $price=$now_group['price'];
                                        }
                                        $now_group['J_total-price']=$price*$_GET['num']+$now_group['express_fee'];
                                        $now_group['J_total-price']=round_number($now_group['J_total-price'],2);
                                    </php>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_group['J_total-price']}<if condition="$now_group['extra_pay_price'] gt 0 AND $config.open_extra_price eq 1 ">+{pigcms{$now_group.extra_pay_price|floatval}{pigcms{$config.extra_price_alias_name}</if></span>
                            </if>
							<span class="J_campaign-value"></span>
						</span>
					</dd>
				</dl>
			</dd>
		</dl>
	
        <h4>您绑定的手机号码</h4>
        <dl class="list" id="mobile-show">
            <dd>
                <if condition="empty($now_user['phone'])">
                    <a id="change-mobile" class="react" href="{pigcms{:U('My/bind_user',array('referer'=>urlencode($_SERVER['REQUEST_URI'])))}">
                        <div>您需要绑定手机，去绑定</div>
                    </a>
                <elseif condition="$now_user['phone']" />
                    <a id="change-mobile" class="react" href="javascript:void(0);">
                        <div>{pigcms{$pigcms_phone}</div>
                    </a>
                <elseif condition="$is_app_browser"/>
                    <a id="change-mobile" class="react">
                        <div>{pigcms{$pigcms_phone}</div>
                    </a>
                <else/>
                    <a id="change-mobile" class="react" href="{pigcms{:U('My/bind_user',array('referer'=>urlencode($_SERVER['REQUEST_URI'])))}">
                        <div>{pigcms{$pigcms_phone}</div>
                    </a>
                </if>
            </dd>
        </dl>
		
		<if condition="C('config.baodi_ticket_url') && $now_group['custom_product_id']">
            <h4>取票用户信息</h4>
            <ul class="yxc-attr-list">
				<li style="padding: 10px 0 0 15px;">取票人姓名</li>
				<li>
					<i class="icon-txt"></i>
					<p class="cover"><input class="ipt-attr text_info" type="text" name="userinput[name]" placeholder="请输入取票人姓名" data-role="text" data-required="required" value="{pigcms{$lastUserInput['name']}"/></p>
				</li>
				
				<li style="padding: 10px 0 0 15px;">取票人身份证</li>
				<li>
					<i class="icon-txt"></i>
					<p class="cover"><input class="ipt-attr text_info" type="text" name="userinput[idcard]" placeholder="请输入取票人身份证" data-role="text" data-required="required" value="{pigcms{$lastUserInput['idcard']}"/></p>
				</li>
            </ul>
        </if>

        <if condition="$write_info">
            <h4>用户填写信息</h4>

            <ul class="yxc-attr-list">
                <volist name="write_info" id="vo" key="key">
                    <li style="padding: 10px 0 0 15px;">{pigcms{$vo.name}:</li>
                    <li>
                        <switch name="vo['type']">
                            <case value="0">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="text" name="custom_field[{pigcms{$key}][value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="text" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                            </case>
                            <case value="1">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover text_info"><textarea class="ipt-attr" name="custom_field[{pigcms{$key}][value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="textarea" <if condition="$vo['iswrite']">data-required="required"</if>></textarea></p>
                            </case>
                            <case value="2">
                                <i class="icon-position"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][long]" data-type="long"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][lat]" data-type="lat"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][address]" data-type="address"/>
                                <p class="cover">
                                    <input data-role="position" class="ipt-attr text_info" type="text" name="custom_field[{pigcms{$key}][value]" placeholder="请标注{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" readonly="readonly" <if condition="$vo['iswrite']">data-required="required"</if>/>
                                </p>
                                <p class="cover">
                                    <input data-role="position-desc" class="ipt-attr text_info" type="text" name="custom_field[{pigcms{$key}][value-desc]" placeholder="请标注地图后填写详细地址" data-required="required"/>
                                </p>
                            </case>
                            <case value="3">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover select">
                                    <select name="custom_field[{pigcms{$key}][value]" class="ipt-attr text_info" data-role="select"  placeholder="请选择{pigcms{$vo.name}" <if condition="$vo['iswrite']">data-required="required"</if>>
                                    <option value="">请选择{pigcms{$vo.name}</option>
                                    <volist name="vo['use_field']" id="voo">
                                        <option value="{pigcms{$voo}">{pigcms{$voo}</option>
                                    </volist>
                                    </select>
                                </p>
                            </case>
                            <case value="4">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="tel" name="custom_field[{pigcms{$key}][value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="number" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                            </case>
                            <case value="5">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="email" name="custom_field[{pigcms{$key}][value]" placeholder="请输入正确的{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="email" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                            </case>
                            <case value="6">
                                <i class="icon-time"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="date" name="custom_field[{pigcms{$key}][value]" id="input_6" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="date" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                           </case>
                            <case value="7">
                                <i class="icon-time"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="time" name="custom_field[{pigcms{$key}][value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="time" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                            </case>
                            <case value="8">
                                <i class="icon-txt"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover">
                                    <input class="ipt-attr text_info" type="date" name="custom_field[{pigcms{$key}][date_value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="datetime" <if condition="$vo['iswrite']">data-required="required"</if>/>
                                    <input class="ipt-attr text_info" type="time" name="custom_field[{pigcms{$key}][time_value]" placeholder="请输入{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="datetime" <if condition="$vo['iswrite']">data-required="required"</if>/>
                                </p>
                            </case>
                            <case value="9">
                                <i class="icon-phone"></i>
                                <input type="hidden" name="custom_field[{pigcms{$key}][name]" value="{pigcms{$vo.name}"/>
                                <input type="hidden" name="custom_field[{pigcms{$key}][type]" value="{pigcms{$vo.type}"/>
                                <p class="cover"><input class="ipt-attr text_info" type="tel" name="custom_field[{pigcms{$key}][value]" placeholder="请输入正确{pigcms{$vo.name}<if condition="!$vo['iswrite']">（可选）</if>" data-role="phone" <if condition="$vo['iswrite']">data-required="required"</if>/></p>
                            </case>
                        </switch>
                    </li>
                </volist>
            </ul>
        </if>





			
		<if condition="$now_group['tuan_type'] == 2 ">
			<h4>选择收货地址</h4>
			<if condition="$now_group['user_adress']['adress_id'] || $now_group['pick_in_store']">
				<dl class="list">
					<input type="hidden" name="pick_in_store" value="<php> if(empty($_GET['pick_addr_id']) && $now_group['open_express']!=0){echo 0;}else{echo 1;}</php>"/>
					<dd class="dd-padding kv-line-r quantity">
						<h6>配送方式</h6>
						<php>if($now_group['open_express']!=0){</php>
						    <a class="btn_express <php>if(empty($_GET['pick_addr_id'])){</php>post_package<php>}else{</php>post_package_click<php>}</php>" id="post_package">快递配送</a>
                        <php>}</php>&nbsp;&nbsp;
                        <php>if($now_group['pick_in_store']){</php>
                            <a class="btn_express <php>if(!empty($_GET['pick_addr_id']) || $now_group['open_express']==0){</php>pick_in_store<php>}else{</php>pick_in_store_click<php>}</php>" id="pick_in_store">到店自提</a>
                        <php>}</php>
					</dd>
					<dd <php>if($now_group['open_express']!=0 && (empty($_GET['pick_addr_id'])||!empty($_GET['address_id']))){</php>style="display:block"<php>}else{</php>style="display:none"<php>}</php>>
						<a id="change_address" class="react" href="{pigcms{:U('My/adress',array('group_id'=>$now_group['group_id'],'current_id'=>$now_group['user_adress']['adress_id']))}&type={pigcms{$_GET['type']}&num={pigcms{$_GET['num']}&specifications_id={pigcms{$_GET['specifications_id']}">
							<div class="more more-weak">
								<input type="hidden" name="adress_id" value="{pigcms{$now_group['user_adress']['adress_id']}"/>
								<div class="kv-line">
									<h6>姓名：</h6><p>{pigcms{$now_group['user_adress']['name']}</p>
								</div>
								<div class="kv-line">
									<h6>手机：</h6><p>{pigcms{$now_group['user_adress']['phone']}</p>
								</div>
								<div class="kv-line">
									<h6>地址：</h6><p>{pigcms{$now_group['user_adress']['show_address']}</p>
								</div>
							</div>
						</a>
					</dd>

					<dd class="pick_address" <php>if(!empty($_GET['pick_addr_id']) || $now_group['open_express']==0){</php>style="display:block"<php>}else{</php>style="display:none"<php>}</php>>
						<a id="change_address" class="react" href="{pigcms{:U('My/pick_address',array('mer_id'=>$now_group['mer_id'],'group_id'=>$now_group['group_id'],'pick_addr_id'=>$pick_address['pick_addr_id']))}&type={pigcms{$_GET['type']}&specifications_id={pigcms{$_GET['specifications_id']}">
							<div class="more more-weak">
								<input type="hidden" name="pick_addr_id" value="{pigcms{$pick_address.pick_addr_id}"/>
								<input type="hidden" name="pick_lat" value="{pigcms{$pick_address.lat}"/>
								<input type="hidden" name="pick_lng" value="{pigcms{$pick_address.long}"/>
								<input type="hidden" name="pick_address" value="{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']} {pigcms{$pick_address['name']} 自提点电话：{pigcms{$pick_address['phone']}"/>
								<div class="kv-line">
									<h6>地址：</h6><p>{pigcms{$pick_address['name']}</p>
								</div>
								<div class="kv-line">
									<h6>手机：</h6><p>{pigcms{$pick_address['phone']}</p>
								</div>
								<div class="kv-line">
									<h6>省市区：</h6><p>{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']} </p>
								</div>

							</div>
						</a>
					</dd>
				</dl>
			<else/>
				<dl class="list">
					<dd>
						<a id="change_address" class="react" href="{pigcms{:U('My/adress',array('group_id'=>$now_group['group_id']))}&type={pigcms{$_GET['type']}&specifications_id={pigcms{$_GET['specifications_id']}">
							<div class="more more-weak">添加收货人地址</div>
						</a>
					</dd>
				</dl>
			</if>
			<if condition="$now_group['open_express']!=0 ">
			<h4 id="send_time">送货时间</h4>
			<dl class="list">
				<dd class="dd-padding">
					<label class="select">
						<select name="delivery_type">
							<option value="1">工作日、双休日与假日均可送货</option>
							<option value="2">只工作日送货</option>
							<option value="3">只双休日、假日送货</option>
							<option value="4">白天没人，其它时间送货</option>
						</select>
					</label>
				</dd>
			</dl>
			<h4>配送说明</h4>
			<dl class="list">
				<dd class="dd-padding">
					<input type="text" class="input-weak" style="width:100%" placeholder="配送特殊说明，配送公司会尽量调节" name="delivery_comment"/>
				</dd>
			</dl>
			</if>
		</if>
			<input type="hidden" name="group_type" value="{pigcms{$_GET['type']}" /> 
		<div class="btn-wrapper">
			<button type="submit" class="btn btn-block btn-strong btn-larger mj-submit" style="display:none;">提交订单</button>
		</div>
	</form>



    <section id="service-position" style="display:none;">
        <div class="yxc-pay-main yxc-payment-bg pad-bot-comm">
            <header class="yxc-brand">
                <a class="arrow-wrapper" data-role="cancel">
                    <i class="bt-brand-back"></i>
                </a>
                <span>选择位置</span>
            </header>
            <div class="selectInput">
                <input type="text" placeholder="直接输入定位您的地址" id="se-input-wd" autocomplete="off"/>
            </div>
            <div class="mapBox">
                <div id="allmap"></div>
                <div class="dot"></div>
            </div>
            <div class="mapaddress">
                <ul id="addressShow"></ul>
            </div>
        </div>
    </section>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script type="text/javascript" src="https://api.map.baidu.com/api?v=2.0&ak=4c1bb2055e24296bbaef36574877b4e2"></script>
    <script src="{pigcms{$static_path}/js/common_wap.js"></script>
	
    <script type="text/javascript" src="{pigcms{$static_path}js/group_buy.js?t={pigcms{:time()}"></script>
	<script>
        <if condition="$long_lat">
            var user_long={pigcms{$long_lat.long},user_lat={pigcms{$long_lat.lat},user_city='{pigcms{$city_name}';
        <else/>
        var user_long=0,user_city='{pigcms{$city_name}';
		</if>
		
		$(document).on('click','.link-url',function(e){
			window.location.href = $(this).data('url');
		});
	</script>
	<script>
		$(function(){
			var extra_price = Number("{pigcms{$now_group.extra_pay_price}");
			var open_extra_price = Number("{pigcms{$config.open_extra_price}");
			var extra_price_name = "{pigcms{$config.extra_price_alias_name}";
			var express_fee = Number("{pigcms{$now_group.express_fee}");
			var full_money = Number("{pigcms{$now_group.express_template.full_money}");
			var total_money = 0;
			$('#post_package').click(function(){
				$(this).removeClass('post_package_click');
				$(this).addClass('post_package');
				$('#pick_in_store').removeClass('pick_in_store');
				$('#pick_in_store').addClass('pick_in_store_click');

				$('input[name="pick_in_store"]').val(0);
				$('.pick_address').css('display','none');
				$(this).parents('dd').next().css('display','block');
				$(this).parents('.list').nextAll('dl').css('display','block');
				$(this).parents('.list').nextAll('h4').css('display','block');
				var pigcms_now_quantity = parseInt(quantity.val());
				total_money = price*(pigcms_now_quantity)/100;
				if(full_money>0){									
				  $('.express_fee').html("满" + full_money + "包邮 {pigcms{$config['Currency_symbol']}" + express_fee);
				}else{
				 $('.express_fee').html("{pigcms{$config['Currency_symbol']}" + express_fee);
				}
				$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + (total_money+express_fee));
			});
			
			$('#pick_in_store').click(function(){
				$(this).removeClass('pick_in_store_click');
				$(this).addClass('pick_in_store');

				$('#post_package').removeClass('post_package');
				$('#post_package').addClass('post_package_click');
				$('input[name="pick_in_store"]').val(1);
				$(this).parents('dd').next().css('display','none');
				$('.pick_address').css('display','block');
				$(this).parents('.list').nextAll('dl').css('display','none');
				$(this).parents('.list').nextAll('h4').css('display','none');
                var pigcms_now_quantity = parseInt(quantity.val());
                total_money = price*(pigcms_now_quantity)/100;
                $('.express_fee').html("{pigcms{$config['Currency_symbol']}0");
                $('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + (total_money));
			});
            var price = {pigcms{$now_group['price']*100};
			var wx_cheap = {pigcms{$now_group['wx_cheap']*100};
			var finalprice={pigcms{$finalprice|floatval}; 
			var after_discount_finalprice={pigcms{$after_discount_finalprice|floatval};
			finalprice = after_discount_finalprice>=0?after_discount_finalprice:finalprice;
            price= finalprice >0 ? finalprice * 100 : price;
			wx_cheap= finalprice >0 ? finalprice * 100 : wx_cheap;
			var quantity = $("input[name='quantity']");
            price = price.toFixed(0);
            console.log('price',price);
			$('button.plus').click(function(){
                var express_money=express_fee;
                if($('input[name="pick_in_store"]').val()==1){
                    express_money = 0;
                }
                var start_max_num = "{pigcms{$now_group['start_max_num']}";
                var type = "{pigcms{$_GET['type']}";
                var pin_num = "{pigcms{$now_group['pin_num']}";
				$('#tips').removeClass('tips-err').empty();
				var pigcms_now_quantity = parseInt(quantity.val());
				if(!/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(pigcms_now_quantity)){
					$('#tips').addClass('tips-err').html('请输入正确的购买数量');
				}else if(pigcms_now_quantity + 1 > quantity.attr('max') && quantity.attr('max') != '0'){
                    $('#tips').addClass('tips-err').html('您最多能购买'+quantity.attr('max')+'单');
                    quantity.val(quantity.attr('max'));
                    $(this).prop('disabled',true);
                }else if(pin_num>0 && type==3 && pigcms_now_quantity + 1 > start_max_num){
                    $('#tips').addClass('tips-err').html('团长每次最多只能购买'+start_max_num+'单');
                    quantity.val(start_max_num);
                    $(this).prop('disabled',true);
                }else{
					quantity.val(pigcms_now_quantity+1);
					if(extra_price>0&&open_extra_price==1){
                        var J_total_price = price*(pigcms_now_quantity+1)/100;
                        J_total_price = J_total_price.toFixed(2);
						$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price + '+' + extra_price*(pigcms_now_quantity+1) + extra_price_name);
					}else{
						total_money = price*(pigcms_now_quantity+1)/100;

						if(total_money>=full_money && full_money>0){
							 $('.express_fee').html("{pigcms{$config['Currency_symbol']}0");
                            total_money = total_money.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + total_money);
						}else{
							if(full_money>0){									
								$('.express_fee').html('满'+full_money+'包邮 ' + "{pigcms{$config['Currency_symbol']}" + express_money);
							}
                            var J_total_price = total_money+express_money;
                            J_total_price = J_total_price.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price);
						}
					}
					$('#wx_cheap').html(wx_cheap*(pigcms_now_quantity+1)/100);
					$('button.minus').prop('disabled',false);
				}
			});
			$('button.minus').click(function(){
                var express_money=express_fee;
                if($('input[name="pick_in_store"]').val()==1){
                    express_money = 0;
                }
				$('#tips').removeClass('tips-err').empty();
				var pigcms_now_quantity = parseInt(quantity.val());
				if(!/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(pigcms_now_quantity)){
					$('#tips').addClass('tips-err').html('请输入正确的购买数量');
				}else if(pigcms_now_quantity - 1 < quantity.attr('min')){
					$('#tips').addClass('tips-err').html('您最少能购买'+quantity.attr('min')+'单');
				}else{
					if(pigcms_now_quantity-1 <= quantity.attr('min')){
						$(this).prop('disabled',true);
					}
					quantity.val(pigcms_now_quantity-1);
					if(extra_price>0&&open_extra_price==1){
                        var J_total_price = price*(pigcms_now_quantity-1)/100;
                        J_total_price = J_total_price.toFixed(2);
						$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price + '+' + extra_price*(pigcms_now_quantity-1)+extra_price_name);
					}else{
						total_money = price*(pigcms_now_quantity-1)/100;

						if(total_money>=full_money && full_money>0){
							 $('.express_fee').html("{pigcms{$config['Currency_symbol']}0");
                            total_money = total_money.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + total_money);
						}else{
							if(full_money>0){									
								$('.express_fee').html('满'+full_money+'包邮 ' + "{pigcms{$config['Currency_symbol']}" + express_money);
							}
                            var J_total_price = total_money+express_money;
                            J_total_price = J_total_price.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price);
						}
						
					}
					$('#wx_cheap').html(wx_cheap*(pigcms_now_quantity-1)/100);
					$('button.plus').prop('disabled',false);
				}
			});
			quantity.blur(function(){
			var express_money=express_fee;
                if($('input[name="pick_in_store"]').val()==1){
                    express_money = 0;
                }
                var start_max_num = "{pigcms{$now_group['start_max_num']}";
                var type = "{pigcms{$_GET['type']}";
                var pin_num = "{pigcms{$now_group['pin_num']}";
				$('#tips').removeClass('tips-err').empty();
				var pigcms_now_quantity = parseInt(quantity.val());
				if(!/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(pigcms_now_quantity)){
					$('#tips').addClass('tips-err').html('请输入正确的购买数量');
				}else{
					if(quantity.attr('max') != '0' && pigcms_now_quantity == quantity.attr('max')){
						$('button.plus').prop('disabled',true);
					}else if(quantity.attr('max') != '0' && pigcms_now_quantity > quantity.attr('max')){
						$('#tips').addClass('tips-err').html('您最多能购买'+quantity.attr('max')+'单');
						$('button.plus').prop('disabled',true);
						quantity.val(quantity.attr('max'));
					}else if(pin_num>0 && type==3 && pigcms_now_quantity > start_max_num){
                        $('#tips').addClass('tips-err').html('团长每次最多只能购买'+start_max_num+'单');
                        quantity.val(start_max_num);
                        $('button.plus').prop('disabled',true);
                    }else{
						$('button.plus').prop('disabled',false);
					}
					if(pigcms_now_quantity == quantity.attr('min')){
						$('button.minus').prop('disabled',true);
					}else if(pigcms_now_quantity < quantity.attr('min')){
						$('#tips').addClass('tips-err').html('您最少能购买'+quantity.attr('min')+'单');
						$('button.minus').prop('disabled',true);
						quantity.val(quantity.attr('min'));
					}else{
						$('button.minus').prop('disabled',false);
					}

					if(open_extra_price && extra_price>0){
                        var J_total_price = price*(parseInt(quantity.val()))/100;
                        J_total_price = J_total_price.toFixed(2);
						$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price + '+' + extra_price*((parseInt(quantity.val())))+extra_price_name);
					}else{
						
						total_money = price*(parseInt(quantity.val()))/100;
					
						if(total_money>=full_money && full_money>0){
							 $('.express_fee').html("{pigcms{$config['Currency_symbol']}0");
							total_money = total_money.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + total_money);
						}else{
							if(full_money>0){									
								$('.express_fee').html('满'+full_money+'包邮 ' + "{pigcms{$config['Currency_symbol']}" + express_money);
							}
							var J_total_price = total_money+express_money;
							J_total_price = J_total_price.toFixed(2);
							$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + J_total_price);
						}
						
					}
				}
			});
			<if condition="!empty($_GET['pick_addr_id'])">
				$('#send_time').css('display','none');
				$('#send_time').nextAll('dl').css('display','none');
				$('#send_time').nextAll('h4').css('display','none');
			<else />
				$('#send_time').css('display','block');
				$('#send_time').nextAll('dl').css('display','block');
				$('#send_time').nextAll('h4').css('display','block');
			</if>
			<if condition="$_GET['pick_addr_id'] neq ''">
				$('.express_fee').html("{pigcms{$config['Currency_symbol']}0");
				var pigcms_now_quantity = parseInt(quantity.val());
				total_money = price*(pigcms_now_quantity)/100;
				console.log(total_money)
				$('.J_total-price').html("{pigcms{$config['Currency_symbol']}" + (total_money));
			  
			</if>
		});
		
		$(document).ready(function(){		
			if(window.__wxjs_is_wkwebview){
				window.addEventListener("pageshow", function(){
					$("button.mj-submit").removeAttr("disabled");
					$("button.mj-submit").html("提交订单");
				},false);
			}else{

			}
		}); 
	</script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
	<script>var showBuyBtn = true;</script>
    <if condition="!$is_app_browser AND $is_weixin_browser">
	    <if condition="$_SESSION['openid']">
		    <switch name="config['weixin_buy_follow_wechat']">
			    <case value="0">
				    <php>if($now_group['wx_cheap']){</php>
                            <script>//layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'在微信中购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！',btn:['好的'],shadeClose:false});
							</script>
				    <php>}</php>
			    </case>
			    <case value="1">
				    <php>if($now_group['wx_cheap']){</php>
					    <php>if($now_user['is_follow']){</php>
						    <script>//layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'在微信中购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！',btn:['好的'],shadeClose:false});
							$('.wx_cheap').show();</script>
					    <php>}else{</php>
						    <script>layer.open({title:['提示：','background-color:#FF658E;color:#fff;'],content:'关注公众号后购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！<br/>长按图片识别二维码关注：<br/><img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=see_qrcode&type=group&id={pigcms{$now_group.group_id}" style="width:230px;height:230px;"/>',shadeClose:false});</script>
					    <php>}</php>
				    <php>}</php>
			    </case>
			    <case value="2">
				    <php>if($now_user['is_follow']){</php>
					    <php>if($now_group['wx_cheap']){</php>
						    <script>//layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'在微信中购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！',btn:['好的'],shadeClose:false});
							
							$('.wx_cheap').show();</script>
					    <php>}</php>
				    <php>}else{</php>
					    <script>layer.open({title:['提示：','background-color:#FF658E;color:#fff;'],content:'您必须关注公众号后才能购买本单！<br/>长按图片识别二维码关注：<br/><img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=see_qrcode&type=group&id={pigcms{$now_group.group_id}<php>if($_GET['gid']>0){echo '&gid='.$_GET['gid'];}</php>" style="width:230px;height:230px;"/>',shadeClose:false});$('button.mj-submit').remove();var showBuyBtn = false;</script>
				    <php>}</php>
			    </case>
		    </switch>
	    <elseif condition="$now_group['wx_cheap']"/>
		        <script>layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'在微信中购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！',btn:['好的'],shadeClose:false});</script>
	    </if>
    <elseif condition="$now_group['wx_cheap']"/>
            <script>layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'在APP中购买本单，每单减免 <b style="color:red;">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.wx_cheap}</b>！',btn:['好的'],shadeClose:false});</script>
    </if>
	<script>if(showBuyBtn){$('button.mj-submit').show();}</script>
	<include file="Public:footer"/>
	{pigcms{$hideScript}

</body>
</html>