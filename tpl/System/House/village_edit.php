<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('House/village_edit')}" frame="true" refresh="true" style="margin-bottom: 50px;">
	<input type="hidden" name="village_id" value="{pigcms{$now_village.village_id}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="120">{pigcms{$config['house_name']}名称</th>
			<td><input type="text" class="input fl" name="village_name" value="{pigcms{$now_village.village_name}" size="40" placeholder="请输入{pigcms{$config['house_name']}名称" validate="maxlength:20,required:true"/></td>
		</tr>
		<tr >
			<th width="15%">所在区域</th>
<!--			<td id="choose_cityarea" province_id="{pigcms{$now_village.province_id}" city_id="{pigcms{$now_village.city_id}" area_id="{pigcms{$now_village.area_id}" circle_id="-1" circle="-1"></td>-->

            <td id="choose_street" province_idss="{pigcms{$now_village.province_id}" city_idss="{pigcms{$now_village.city_id}" area_id="{pigcms{$now_village.area_id}" street_idss="{pigcms{$now_village.street_id}" community_idss="{pigcms{$now_village.community_id}" style="display:inline"></td>
        </tr>
		<tr>
			<th width="120">{pigcms{$config['house_name']}地址</th>
			<td><input type="text" class="input fl" name="village_address" value="{pigcms{$now_village.village_address}" size="40" placeholder="请输入{pigcms{$config['house_name']}地址" validate="maxlength:50,required:true"/></td>
		</tr>


        <if condition="$now_village['village_single_support_digit'] gt 0">
            <tr>
                <th width="150">该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>楼栋是否超过100栋</th>
                <td>
                    <span class="disable-cb-enable"><label readonly="readonly" class="disable-cb-enable <if condition="$now_village['village_single_support_digit'] eq 3">selected</if>"><span>是</span><input type="radio" value="3" <if condition="$now_village['village_single_support_digit'] eq 3">checked="checked"</if> disabled/></label></span>
                    <span class="disable-cb-disable"><label readonly="readonly" class="disable-cb-disable <if condition="$now_village['village_single_support_digit'] eq 2">selected</if>"><span>否</span><input type="radio" value="2" <if condition="$now_village['village_single_support_digit'] eq 2">checked="checked"</if> disabled/></label></span>
                    <em tips="选择是，楼栋号即可支持三位 不支持编辑" class="notice_tips"></em>
                </td>
            </tr>
        </if>

        <!--			<tr>-->
		<!--				<th width="120">物业公司名称</th>-->
		<!--				<td><input type="text" class="input fl" name="property_name" value="{pigcms{$now_village.property_name}" size="40" placeholder="请输入物业公司名称" validate="maxlength:50,required:true"/></td>-->
		<!--			</tr>-->
		<!--			<tr>-->
		<!--				<th width="120">物业联系地址</th>-->
		<!--				<td><input type="text" class="input fl" name="property_address" value="{pigcms{$now_village.property_address}" size="40" placeholder="请输入物业联系地址" validate="maxlength:50,required:true"/></td>-->
		<!--			</tr>-->
		<!--			<tr>-->
		<!--				<th width="120">物业联系电话</th>-->
		<!--				<td><input type="text" class="input fl" name="property_phone" value="{pigcms{$now_village.property_phone}" size="20" placeholder="请输入物业联系电话" validate="maxlength:50,required:true" tips="多个号码以空格分开"/></td>-->
		<!--			</tr>-->
		<!--			<tr>-->
		<!--				<th width="120">{pigcms{$config['house_name']}后台管理帐号</th>-->
		<!--				<td><input type="text" class="input fl" name="account" value="{pigcms{$now_village.account}" size="20" placeholder="请输入{pigcms{$config['house_name']}后台管理帐号" validate="maxlength:50,required:true" tips="多个{pigcms{$config['house_name']}帐号一致，将认为是同一家物业公司。进入{pigcms{$config['house_name']}后台会提示进入哪个{pigcms{$config['house_name']}"/></td>-->
		<!--			</tr>-->
		<!--			<tr>-->
		<!--				<th width="120">{pigcms{$config['house_name']}后台管理密码</th>-->
		<!--				<td><input type="text" class="input fl" name="pwd" size="20" placeholder="不修改请勿填写" validate="maxlength:50,minlength:6" tips="不修改请勿填写"/></td>-->
		<!--			</tr>-->
		<!--			<tr>-->
		<!--				<th width="100">{pigcms{$config['house_name']}到期时间</th>-->
		<!--				<td><input type="text" class="input fl" name="expiration_time" size="25" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" tips="{pigcms{$config['house_name']}到期时间，到期之后物业与用户均不允许进入{pigcms{$config['house_name']}并关闭该{pigcms{$config['house_name']}，清空为永不过期" readonly="" value="{pigcms{$now_village.expiration_time}"></td>-->
		<!--			</tr>-->
		<tr>
			<th width="160">认证<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></th>
			<td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_village['isverify'] eq 1">selected</if>"><span>是</span>
                    <input type="radio" name="isverify" value="1" <if condition="$now_village['isverify'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['isverify'] eq 0">selected</if>"><span>否</span>
                    <input type="radio" name="isverify" value="0" <if condition="$now_village['isverify'] eq 0">checked="checked"</if>/></label></span>
			</td>
		</tr>
		<tr>
			<th width="120">{pigcms{$config['house_name']}抽成比例</th>
			<td>

					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['percent'] egt 0">selected</if>"><span>设置</span>
					<input type="radio" name="open_own_percent" value="1" <if condition="$now_village['percent'] egt 0">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['percent'] lt 0">selected</if>"><span>跳过</span>
					<input type="radio" name="open_own_percent" value="0" <if condition="$now_village['percent'] lt 0">checked="checked"</if>/></label></span>
				<em class="notice_tips" tips="设置后将按这个抽成比例抽成"></em>

				<input type="text" class="input fl" name="percent" id="percent" value="{pigcms{$now_village.percent}" size="20" <if condition="$now_village['percent'] lt 0">style="display:none"</if> validate="number:true" />

			</td>
		</tr>
		<tr>
			<th width="120">{pigcms{$config['house_name']}业务返点</th>
			<td>

					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['rebate_percent'] egt 0">selected</if>"><span>设置</span>
					<input type="radio" name="open_rebate_percent" value="1" <if condition="$now_village['rebate_percent'] egt 0">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['rebate_percent'] lt 0">selected</if>"><span>跳过</span>
					<input type="radio" name="open_rebate_percent" value="0" <if condition="$now_village['rebate_percent'] lt 0">checked="checked"</if>/></label></span>
				<em class="notice_tips" tips="请填写0-100数字，代表返点的百分比，0 不返点，2 返点2%，跳过则代表以平台总设置为准"></em>

				<input type="text" class="input fl" name="rebate_percent" id="rebate_percent" value="{pigcms{$now_village.rebate_percent}" size="20" <if condition="$now_village['rebate_percent'] lt 0">style="display:none"</if> validate="number:true" />

			</td>
		</tr>
		<tr>
			<th width="120">是否开启平台的猜你喜欢</th>
			<td>

					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['is_platform_guess'] egt 1">selected</if>"><span>开启</span>
					<input type="radio" name="is_platform_guess" value="1" <if condition="$now_village['is_platform_guess'] egt 0">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="!$now_village['is_platform_guess'] || $now_village['is_platform_guess'] lt 0">selected</if>"><span>关闭</span>
					<input type="radio" name="is_platform_guess" value="0" <if condition="!$now_village['is_platform_guess'] || $now_village['is_platform_guess'] lt 0">checked="checked"</if>/></label></span>
				    <em class="notice_tips" tips="若不开启，<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>首页下方猜你喜欢店铺将不会展示"></em>
			</td>
		</tr>
<!--		<tr>-->
<!--			<th width="160">是否开启物业管理功能</th>-->
<!--			<td>-->
<!--                    <span class="cb-enable"><label class="cb-enable <if condition="$now_village['is_open_estate'] eq 1">selected</if>"><span>是</span>-->
<!--                    <input type="radio" name="is_open_estate" value="1" <if condition="$now_village['is_open_estate'] eq 1">checked="checked"</if>/></label></span>-->
<!--                    <span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['is_open_estate'] eq 0">selected</if>"><span>否</span>-->
<!--                    <input type="radio" name="is_open_estate" value="0" <if condition="$now_village['is_open_estate'] eq 0">checked="checked"</if>/></label></span>-->
<!--				<em class="notice_tips" tips="开启，则小区后台左侧功能导航会出现“物业管理”功能，反之，则隐藏该功能"></em>-->
<!--			</td>-->
<!--		</tr>-->
		<if condition="$config.open_sub_mchid eq 1">
			<tr>
				<th width="160">是否开启特约子商功能</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['open_sub_mchid'] eq 1">selected</if>"><span>是</span>
		<input type="radio" name="open_sub_mchid" value="1" <if condition="$now_village['open_sub_mchid'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['open_sub_mchid'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="open_sub_mchid" value="0" <if condition="$now_village['open_sub_mchid'] eq 0">checked="checked"</if>/></label></span>
		<em class="notice_tips" tips="开启后，该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>不能设置使用自有的微信支付，该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>的支付将按服务商的子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号支付<br>(开启后请正确配置子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号、子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>退款、是否允许使用平台余额、是否允许使用平台优惠)"></em>
		</td>
		</tr>
		</if>

		<if condition="$config['buy_sms'] eq 1">
			<tr <php>if($can_recharge==0){echo 'style="display:none"';}</php> >
			<th width="15%">短信条数</th>
			<td width="85%" colspan="3"><div style="height:30px;line-height:24px;">当前短信：{pigcms{$now_village.now_sms_number}（条） &nbsp;&nbsp;&nbsp;&nbsp;<select name="set_sms_type"><option value="1">增加</option><option value="2">减少</option></select>&nbsp;&nbsp;<input type="text" class="input" name="set_sms_number" size="10" validate="number:true," tips="此处填写增加或减少的条数，不是将短信条数变为此处填写的值"/>
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/sms_order',array('village_id'=>$now_village['village_id'],'frame_show'=>true))}','查看短信变动记录',800,600,true,false,false,false,'detail',true);">记录</a>
					<input type="hidden" name="now_sms_number" value="{pigcms{$now_village.now_sms_number}">
				</div></td>
			</tr>
		</if>

		<tr class="sub_mch">
			<th width="160">微信子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号</th>
			<td><input type="text" class="input fl" name="sub_mch_id" size="25" value="{pigcms{$now_village['sub_mch_id']}" placeholder="子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号" validate="" />
				<em class="notice_tips" tips="开启子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>支付后必须要填写正确的子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号，子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>号可以在微信子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>平台查看"></em>
			</td>
		</tr>

		<!--<tr class="sub_mch" style="display:none">
            <th width="160">是否开启子小区支付退款</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$now_village['sub_mch_refund'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_refund" value="1" <if condition="$now_village['sub_mch_refund'] eq 1">checked="checked"</if> /></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$now_village['sub_mch_refund'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_refund" value="0" <if condition="$now_village['sub_mch_refund'] eq 0">checked="checked"</if>/></label></span>
                <font color="red">请确认子小区是否授权退款权限,如果没有请不要开启</font>
                <em class="notice_tips" tips="开启子小区支付退款，需要子小区申请退款权限给微信服务商，如果未得到授权，退款将失败;<br>关闭后用户将不能退款，当开启子小区支付功能时才有效"></em>
            </td>
        </tr>-->


		<tr class="sub_mch">
			<th width="160">是否允许适用平台余额</th>
			<td>
				<span class="cb-enable"><label class="cb-enable  <if condition="$now_village['sub_mch_sys_pay'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_sys_pay" value="1" <if condition="$now_village['sub_mch_sys_pay'] eq 1">checked="checked"</if> /></label></span>
				<span class="cb-disable"><label class="cb-disable  <if condition="$now_village['sub_mch_sys_pay'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_sys_pay" value="0" <if condition="$now_village['sub_mch_sys_pay'] eq 0">checked="checked"</if>/></label></span>
				<em class="notice_tips" tips="开启后用户通过子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>支付的同时也可以使用平台余额，且平台余额部分参与平台跟<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>对账,必须开启特约子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>功能;<br>关闭后，用户不能使用平台余额，当开启子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>支付功能时才有效"></em>
			</td>
		</tr>
		<tr class="sub_mch">
			<th width="160">是否允许适用平台优惠(积分)</th>
			<td>
				<span class="cb-enable"><label class="cb-enable  <if condition="$now_village['sub_mch_discount'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_discount" value="1" <if condition="$now_village['sub_mch_discount'] eq 1">checked="checked"</if>  /></label></span>
				<span class="cb-disable"><label class="cb-disable  <if condition="$now_village['sub_mch_discount'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_discount" value="0" <if condition="$now_village['sub_mch_discount'] eq 0">checked="checked"</if> /></label></span>
				<em class="notice_tips" tips="开启后,用户通过子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>支付的同时也可以使用平台优惠，且优惠部分参与平台跟<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>对账,必须开启特约子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>功能;<br>关闭后用户不能使用平台优惠券，平台积分抵扣，当开启子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>支付功能时才有效"></em>
			</td>
		</tr>

		<if condition="$config.open_mer_owe_money">
			<tr class="sub_mch">
				<th width="160">是否允许<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>余额欠费</th>
				<td>
					<input type="text" class="input fl" name="village_owe_money" size="25" placeholder="子<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>欠钱额度" value="{pigcms{$now_village.village_owe_money|floatval}" validate="" />
					<em class="notice_tips" tips="0 表示不允许欠款，一旦<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>余额不足够平台抽成，平台将关闭<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>，1000 表示平台允许<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>欠平台1000{pigcms{$config['Currency_txt']}，超过1000后平台将关闭<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"></em>
				</td>

			</tr>

		</if>
		</if>

		<tr>
			<th width="120">{pigcms{$config.house_market_name}{pigcms{$config.shop_alias_name}编号</th>
			<td><input type="text" class="input fl" name="shop_id" value="{pigcms{$now_village.shop_id}" size="5" validate="number:true" tips="{pigcms{$config.house_market_name}单独绑定的{pigcms{$config.shop_alias_name}编号，0为不绑定"/></td>
		</tr>
		<tr>
			<th width="120">{pigcms{$config.house_village_group_name}/团长编号</th>
			<td><input type="text" class="input fl" name="header_id" value="{pigcms{$now_village.header_id}" size="5" validate="number:true" tips="{pigcms{$config.house_village_group_name}单独绑定的团长编号，0为不绑定"/></td>
		</tr>

        <!---->
        <tr>
            <th width="120">是否开启积分抵扣物业费短信提醒</th>
            <td>
                <div class="radio-line">
                    <span class="cb-enable">
                        <label class="cb-enable <if condition="$now_village['sms_notice_set'] eq 1 ">selected</if>">
                            <span>是</span>
                            <input type="radio" name="sms_notice_set" value="1" <if condition="$now_village['sms_notice_set'] eq 1 ">checked="checked"</if>/>
                        </label>
                    </span>
                    <span class="cb-disable">
                        <label class="cb-disable <if condition="$now_village['sms_notice_set'] eq 0">selected</if>">
                            <span>否</span>
                        <input type="radio" name="sms_notice_set" value="0" <if condition="$now_village['sms_notice_set'] eq 0">checked="checked"</if>/>
                        </label>
                    </span>
                </div>
                &nbsp; <em tips="(1)开启后，到设置时间会给业主发送账户可用积分能抵扣物业费金额的提醒&lt;br/&gt;(2)否则不给业主推送短信" class="notice_tips"></em>
            </td>
        </tr>

        <!---->
        <tr>
            <th width="120">是否允许使用<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>住户余额</th>
            <td>
                <div class="radio-line">
                    <span class="cb-enable">
                        <label class="cb-enable <if condition="$now_village['open_village_balance'] eq 1 ">selected</if>">
                            <span>是</span>
                            <input type="radio" name="open_village_balance" value="1" <if condition="$now_village['open_village_balance'] eq 1 ">checked="checked"</if>/>
                        </label>
                    </span>
                    <span class="cb-disable">
                        <label class="cb-disable <if condition="$now_village['open_village_balance'] eq 0">selected</if>">
                            <span>否</span>
                        <input type="radio" name="open_village_balance" value="0" <if condition="$now_village['open_village_balance'] eq 0">checked="checked"</if>/>
                        </label>
                    </span>
                </div>
                &nbsp; <em tips="开启后<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>业务支付时可使用该余额支付" class="notice_tips"></em>
            </td>
        </tr>
		<!-- wangdong star-->
		<tr>
			<!-- <th width="120">物业缴费获得积分</th> -->
			<th width="120">缴费获得积分</th>
			<td>
				<div class="radio-line">
					<input type="radio" id="village_pay_integral_1" name="village_pay_integral" class="regular-radio" value="1" <if condition="$now_village['village_pay_integral']==1">checked</if>/><label for="village_pay_integral_1" class="label-class"><div></div><span>开启</span></label>
					<input type="radio" id="village_pay_integral_2" name="village_pay_integral" class="regular-radio " value="0" <if condition="$now_village['village_pay_integral']==0">checked</if>/><label for="village_pay_integral_2" class="label-class"><div></div><span>继承平台</span></label>
					<input type="radio" id="village_pay_integral_3" name="village_pay_integral" class="regular-radio" value="-1" <if condition="$now_village['village_pay_integral']==-1">checked</if> /><label for="village_pay_integral_3" class="label-class"><div></div><span>关闭</span></label>
				</div>
				&nbsp; <em tips="(1)开启后，用户缴费可以获得积分&lt;br/&gt;(2)使用积分缴纳的部分不会获得积分" class="notice_tips"></em>
			</td>
		</tr>


		<tr class="class-village-pay-integral" <if condition="$now_village['village_pay_integral']!=1">style="display:none"</if>>
		<th width="120">欠缴物业费获得积分</th>
		<td>
			<span class="cb-enable"><label class="cb-enable <if condition="$now_village['village_owe_pay_integral'] eq 1">selected</if>"><span>开启</span><input type="radio" name="village_owe_pay_integral" value="1" <if condition="$now_village['village_owe_pay_integral'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_village['village_owe_pay_integral'] eq 0">selected</if>"><span>关闭</span>
					<input type="radio" name="village_owe_pay_integral" value="0" <if condition="$now_village['village_owe_pay_integral'] eq 0">checked="checked"</if>/></label></span>
			<em tips="开启后，用户欠缴物业费缴费时会获得积分，只针对用户前台在线缴费" class="notice_tips"></em>
		</td>
		</tr>

		<tr class="class-village-owe-pay-integral" <if condition="$now_village['village_pay_integral'] neq 1">style="display:none"</if>>
		<th width="120">消费1{pigcms{$config['Currency_txt']}获得的积分</th>
		<td>
                     <span class="cb-enable">
                        <label class="cb-enable  <if condition="$now_village['open_score_get_percent'] eq 0">selected</if>">
                            <span>积分</span>
                            <input type="radio" name="open_score_get_percent" value="0" <if condition="$now_village['open_score_get_percent'] eq 0">checked="checked"</if>/>
						 </label>
                     </span>
                     <span class="cb-disable" style="margin-right: 5px;">
                        <label class="cb-disable  <if condition="$now_village['open_score_get_percent'] eq 1">selected</if>">
                            <span>积分百分比</span>
                            <input type="radio" name="open_score_get_percent" value="1" <if condition="$now_village['open_score_get_percent'] eq 1">checked="checked"</if>"/>
						 </label>
                    </span>
			<input type="text" class="input fl user_score_get_input" name="user_score_get" value="{pigcms{$now_village.user_score_get}" size="10" <if condition="$now_village['open_score_get_percent'] neq 0">style="display:none"</if>/><div class="fl user_score_get"  <if condition="$now_village['open_score_get_percent'] neq 0">style="display:non;"</if>> <em tips="消费一元获得的积分" class="notice_tips"></em></div>
			<input type="text" class="input fl score_get_percent_input" name="score_get_percent" value="{pigcms{$now_village.score_get_percent}" size="10" <if condition="$now_village['open_score_get_percent'] neq 1">style="display:none"</if>/><div class="fl score_get_percent"  <if condition="$now_village['open_score_get_percent'] neq 1">style="display:none;"</if>> <em tips="消费一元获得积分百分比" class="notice_tips"></em></div>
		</td>
		</tr>

		<tr>
			<th width="120">缴费使用积分</th>
			<td>
				<div class="radio-line">
					<input type="radio" id="village_pay_use_integral_1" name="village_pay_use_integral" class="regular-radio" value="1" <if condition="$now_village['village_pay_use_integral']==1">checked</if>/><label for="village_pay_use_integral_1" class="label-class"><div></div><span>开启</span></label>
					<input type="radio" id="village_pay_use_integral_2" name="village_pay_use_integral" class="regular-radio " value="0" <if condition="$now_village['village_pay_use_integral']==0">checked</if>/><label for="village_pay_use_integral_2" class="label-class"><div></div><span>继承平台</span></label>
					<input type="radio" id="village_pay_use_integral_3" name="village_pay_use_integral" class="regular-radio" value="-1" <if condition="$now_village['village_pay_use_integral']==-1">checked</if> /><label for="village_pay_use_integral_3" class="label-class"><div></div><span>关闭</span></label>
				</div>
				&nbsp; <em tips="开启后，用户缴费时可以使用积分" class="notice_tips"></em>
			</td>
		</tr>
		<tr class="class-village-pay-use-integral" <if condition="$now_village['village_pay_use_integral']!=1">style="display:none"</if>>
		<th width="120">欠缴物业费使用积分</th>
		<td><span class="cb-enable"><label class="cb-enable <if condition="$now_village['village_pay_owe_use_integral'] eq 1">selected</if>"><span>开启</span><input type="radio" name="village_pay_owe_use_integral" value="1" <if condition="$now_village['village_pay_owe_use_integral'] eq 1">checked="checked"</if>/></label></span>
			<span class="cb-disable"><label class="cb-disable <if condition="$now_village['village_pay_owe_use_integral'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="village_pay_owe_use_integral" value="0" <if condition="$now_village['village_pay_owe_use_integral'] eq 0">checked="checked"</if>/></label></span>
			<em tips="开启后，用户在缴纳欠缴的物业费时可以使用积分，只针对用户前台在线缴费" class="notice_tips"></em></td>
		</tr>
		<tr class="class-village-pay-use-integral" <if condition="$now_village['village_pay_use_integral']!=1">style="display:none"</if>>
		<th width="120">可使用最大积分</th>
		<td><input type="text" class="input fl" name="use_max_integral_num" value="{pigcms{$now_village.use_max_integral_num}" size="10"/>&nbsp;<span style="line-height:32px;">积分</span> <em tips="缴费最多使用积分数量，如果设置了 缴费可使用积分占总金额百分比功能 则此功能设置无效" class="notice_tips"></em></td>
		</tr>

		<tr class="class-village-pay-use-integral" <if condition="$now_village['village_pay_use_integral']!=1">style="display:none"</if>>
		<th width="120">积分占总金额百分比</th>
		<td><input type="text" class="input fl" name="use_max_integral_percentage" value="{pigcms{$now_village.use_max_integral_percentage}" size="10"/>&nbsp;<span style="line-height:32px;">%</span> <em tips="缴费最多使用积分占总金额的百分比，如果设置了此项 则缴费可使用最大积分功能 无效" class="notice_tips"></em></td>
		</tr>
        <if condition="$config.open_chinaums eq 1">
        <tr>
            <th width="120">银联支付商户号</th>
            <td><input type="text" class="input fl" name="mid" value="{pigcms{$now_village.mid}" style="width: 250px;"  tips="银联分配商户编号，用以调用支付"/></td>
        </tr>
        <tr>
		<th width="120">银联企业用户号</th>
				<td><input type="text" class="input fl" name="chinaums_zg_mid" style="width: 250px;" value="{pigcms{$now_village.chinaums_zg_mid}" placeholder="银联分配资管企业用户号，用以分账"   tips="银联分配资管企业用户号，用以分账" />
				</td>
			</tr>
        </if>

        <if condition="$is_scuru eq 1">
        <tr>
            <th width="120">四川信用社商户号</th>
            <td><div style="margin-bottom: 15px;">一级商户号：<input type="text" class="input" name="scrcu_mer_no" value="{pigcms{$now_village.scrcu_mer_no}" size="25"  tips="四川信用社商一级户号"/>&nbsp;&nbsp;&nbsp;<span style="color:red;">请仔细核对您的商户号类型是一级的还是二级的</span></div> <div>二级商户号：<input type="text" class="input" name="scrcu_mer_no_2" value="{pigcms{$now_village.scrcu_mer_no_2}" size="25"  tips="四川信用社商二级户号"/> &nbsp;&nbsp;&nbsp;<span style="color:red;">二级商户号可不填，如果填写了则一级商户必须填写！</span></div></td>
        </tr>
        </if>
		<!---  wangdong end -->

		<if condition="$now_village['status'] eq 3">
			<tr>
				<th width="120">审核</th>
				<td class="radio_box">
					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['status'] eq 1 || $now_village['status'] eq 0 || $now_village['status'] eq 3">selected</if>"><span>通过</span><input type="radio" name="status" value="<if condition="$now_village['status'] eq 1">1<else/>0</if>" <if condition="$now_village['status'] eq 1 || $now_village['status'] eq 0 || $now_village['status'] eq 3">checked="checked"</if>/></label></span>
		<span class="cb-disable"><label class="cb-disable <if condition="$now_village['status'] eq 4">selected</if>"><span>拒绝</span><input type="radio" name="status" value="4" <if condition="$now_village['status'] eq 4">checked="checked"</if>/></label></span>
		<em tips="审核拒绝请对应填写下方的拒绝理由" class="notice_tips"></em></td>
		</td>
		</tr>

		<tr class="reason_txt" <if condition="$now_village['status'] neq 4">style="display:none"</if>>
		<th width="120">审核拒绝理由</th>
		<td>
			<textarea name="reason" style="width:15rem;height:50px;" id="reason"></textarea>
		</td>
		</tr>

		<else/>
		<tr>
			<th width="120">状态</th>
			<td class="radio_box">
				<span class="cb-enable"><label class="cb-enable <if condition="$now_village['status'] eq 1 || $now_village['status'] eq 0">selected</if>"><span>正常</span><input type="radio" name="status" value="<if condition="$now_village['status'] eq 0">0<else/>1</if>" <if condition="$now_village['status'] eq 1 || $now_village['status'] eq 0">checked="checked"</if>/></label></span>
				<span class="cb-disable"><label class="cb-disable <if condition="$now_village['status'] eq 2">selected</if>"><span>禁止</span><input type="radio" name="status" value="2" <if condition="$now_village['status'] eq 2">checked="checked"</if>/></label></span>
			</td>
		</tr>
		</if>
        <tr>
            <th width="100">{pigcms{$config['house_name']}排序</th>
            <td><input type="text" class="input fl" name="village_sort" size="30" value="{pigcms{$now_village.village_sort}" placeholder="请输入{pigcms{$config['house_name']}排序" /><span> 提示：排序值越大展示越靠前</span> </td>
           </tr>
        <tr>
            <th width="120">是否设置为平台默认<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></th>
            <td>
                <div class="radio-line">
                    <span class="cb-enable">
                        <label class="cb-enable <if condition="$now_village['is_default'] eq 1 ">selected</if>">
                            <span>是</span>
                            <input type="radio" name="is_default" value="1" <if condition="$now_village['is_default'] eq 1 ">checked="checked"</if>/>
                        </label>
                    </span>
                    <span class="cb-disable">
                        <label class="cb-disable <if condition="$now_village['is_default'] eq 0">selected</if>">
                            <span>否</span>
                        <input type="radio" name="is_default" value="0" <if condition="$now_village['is_default'] eq 0">checked="checked"</if>/>
                        </label>
                    </span>
                </div>
                &nbsp; <em tips="设置后，用户会默认进入此<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>，不会跳转<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>列表" class="notice_tips"></em>
            </td>
        </tr>

          <tr>
            <th width="120"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>业务短信使用类型</th>
            <td>
				<div class="radio-line">
					<input type="radio" id="sms_type_1" name="sms_type" class="regular-radio" value="1" <if condition="$now_village['sms_type']==1">checked</if>/><label class="label-class" for="sms_type_1" ><div></div><span><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>短信</span></label>

                    <input type="radio" id="sms_type_2" name="sms_type" class="regular-radio" value="2" <if condition="$now_village['sms_type']==2">checked</if> /><label  class="label-class"  for="sms_type_2"><div></div><span>平台短信</span></label>
				</div>
				&nbsp; <em tips="默认为<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>选项时，发送催缴短信通知时扣除<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>的短信数，<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>没有短信数量，需要在<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>进行提示，并发送失败。选项为平台时，扣除平台的短信数" class="notice_tips"></em>
            </td>
        </tr>
        
        <if condition="$tencent_voice_asr_open eq 1">
          <tr>
            <th width="120">语音识别功能</th>
            <td>
				<div class="radio-line">
					<input type="radio" id="voice_recognition_open_1" name="voice_recognition_open" class="regular-radio" value="1" <if condition="$now_village['voice_recognition_open'] eq 1">checked</if>/><label class="label-class" for="voice_recognition_open_1" ><div></div><span>开启</span></label>

                    <input type="radio" id="voice_recognition_open_0" name="voice_recognition_open" class="regular-radio" value="0" <if condition="$now_village['voice_recognition_open'] neq 1">checked</if> /><label  class="label-class"  for="voice_recognition_open_0"><div></div><span>关闭</span></label>
				</div>
				&nbsp; <em tips="当系统后台 系统设置里开启了语音识别，<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>的此配置项会出现，控制<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>用户端是否显示语音识别功能" class="notice_tips"></em>
                &nbsp;&nbsp;<span>如果开启了语音识别功能，请去<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?> 的 热词管理 下创建热词数据</span>
            </td>
            </td>
          </tr>
        </if>

        <if condition="$supplyStationArr && $supplyStationArr[0]">
            <tr>
                <th width="100">绑定供暖站</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="supply_bind_village_id" id="supply_bind_village_id" style="width: 145px;">
                            <option style="display: none;">请选择绑定供暖站</option>
                            <volist name="supplyStationArr" id="vo">
                                <option value="{pigcms{$vo.village_id}" <if condition="$supplyStation['bind_village_id'] eq $vo['village_id']">selected="selected"</if>>{pigcms{$vo['village_name']} [{pigcms{$vo.facilities_type_text}]</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>
        </if>
        <if condition="$developerArr && $developerArr[0]">
            <tr>
                <th width="100">绑定开发商</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="developer_bind_village_id" id="developer_bind_village_id" style="width: 145px;">
                            <option style="display: none;">请选择绑定开发商</option>
                            <volist name="developerArr" id="vo">
                                <option value="{pigcms{$vo.village_id}" <if condition="$developer['bind_village_id'] eq $vo['village_id']">selected="selected"</if>>{pigcms{$vo['village_name']} [{pigcms{$vo.facilities_type_text}]</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>
        </if>
        <if condition="$communityAboutInfo && $communityAboutInfo['facilities_type']">
            <tr>
                <th width="100">万事兴{pigcms{$communityAboutInfo['facilities_type_text']}小区</th>
                <td>
                    <div class="radio-line">
                        <input type="radio" id="is_contact_available_1" name="is_contact_available" class="regular-radio" value="1" <if condition="$communityAboutInfo['is_contact_available'] eq 1">checked</if>/><label class="label-class" for="is_contact_available_1" ><div></div><span>是</span></label>
                        <input type="radio" id="is_contact_available_0" name="is_contact_available" class="regular-radio" value="0" <if condition="$communityAboutInfo['is_contact_available'] neq 1">checked</if> /><label  class="label-class"  for="is_contact_available_0"><div></div><span>否</span></label>
                    </div>
                    &nbsp;&nbsp;<em class="notice_tips" tips="1. 设置为'否'时，普通小区的工作人员可以调用其{pigcms{$communityAboutInfo['facilities_type_text']}站小区的小区名称、联系电话、地址；<br/>2. 设置为'是'时，普通小区的工作人员不支持调用 '万事兴{pigcms{$communityAboutInfo['facilities_type_text']}小区' 的小区名称、联系电话、地址；"></em>
                </td>
            </tr>
        </if>
        <if condition="$hikCommunity && $hikCommunity[0]">
            <tr>
                <th width="120">选择绑定对应【海康-智慧社区管理平台】的小区</th>
                <td>
                    <select name="hikCommunityIndexCode" style="width:100px;">
                        <option value="0">未绑定</option>
                        <volist name="hikCommunity" id="vo">
                            <option value="{pigcms{$vo['index_code']}" <if condition="$vo['village_id'] eq $now_village['village_id']">selected</if>>{pigcms{$vo['name']}</option>
                        </volist>
                    </select>
                    &nbsp;&nbsp;<em tips="绑定了对应小区后才可以获取到对应设备并下发先关信息到设备上" class="notice_tips"></em>
                </td>
            </tr>
        </if>

        <if condition="$lilin_pf_door Or ($lilin_pf_client_id AND $lilin_pf_client_secret)">
          <tr>
            <th width="120">立林对接地址</th>
            <td><input type="text" class="input fl" name="lilin_pf_base_api" value="{pigcms{$now_village.lilin_pf_base_api}" style="width: 300px;"  tips="请填写立林平台云端接口地址"/></td>
          </tr>
                <tr>
            <th width="120">立林对接clientId</th>
            <td><input type="text" class="input fl" name="lilin_pf_client_id" value="{pigcms{$now_village.lilin_pf_client_id}" style="width: 300px;"  tips="请填写立林平台接口clientId"/></td>
        </tr>
                <tr>
            <th width="120">立林对接clientSecret</th>
            <td><input type="text" class="input fl" name="lilin_pf_client_secret" value="{pigcms{$now_village.lilin_pf_client_secret}" style="width: 300px;"  tips="请填写立林平台接口clientSecret"/></td>
        </tr>
        <tr>
            <th width="120">立林社区小区号码</th>
            <td><input type="text" class="input fl" name="lilin_village_no" value="{pigcms{$now_village.lilin_village_no}" style="width: 300px;"  tips="请填写立林平台社区小区号码"/></td>
        </tr>
        </if>

	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=03"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<style>
    .disable-cb-enable, .disable-cb-disable, .disable-cb-enable span, .disable-cb-disable span {
        background: url({pigcms{$static_path}css/img/form_onoff.png) repeat-x;
        display: block;
        float: left;
        cursor: pointer;
    }
    .disable-cb-enable .selected {
        background-position: 0 -48px;
    }
    .disable-cb-enable span, .disable-cb-disable span {
        font-weight: bold;
        line-height: 24px;
        background-repeat: no-repeat;
        display: block;
    }
    .disable-cb-enable span {
        background-position: left -72px;
        padding: 0 10px;
    }
    .disable-cb-enable .selected span {
        background-position: left -120px;
        color: #fff;
    }
    .disable-cb-enable input, .disable-cb-disable input {
        display: none;
    }
    .disable-cb-disable span {
        background-position: right -144px;
        padding: 0 10px;
    }
    .disable-cb-disable .selected {
        background-position: 0 -24px;
    }
    .disable-cb-disable .selected span {
        background-position: right -168px;
        color: #fff;
    }
</style>
<script>
	function addLink(domid,iskeyword){
		art.dialog.data('domid', domid);
		art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	}

	$(":radio[name='village_pay_integral']").click(function(){
		//document.write($(this).val());
		var pay_integral_val = $(this).val();
		if(pay_integral_val!=1){
			$(".class-village-pay-integral").hide(200);
			$(".class-village-owe-pay-integral").hide(200);
		}else{
			$(".class-village-pay-integral").show(200);
			$(".class-village-owe-pay-integral").show(200);
		}
	});

	$("input[name='status']").click(function () {
		var val = $(this).val();
		console.log('val',val);
		if (4==val) {
			$('.reason_txt').show();
		} else {
			$('.reason_txt').hide();
		}
	});


	$(":radio[name='open_score_get_percent']").click(function(){
		var open_score_get_percent_val = $(this).val();
		if(open_score_get_percent_val!=1){
			$(".user_score_get").show(200);
			$(".score_get_percent").hide(200);
			$(".user_score_get_input").show(200);
			$(".score_get_percent_input").hide(200);
		}else{
			$(".user_score_get").hide(200);
			$(".score_get_percent").show(200);
			$(".user_score_get_input").hide(200);
			$(".score_get_percent_input").show(200);
		}
	});


	$(":radio[name='village_pay_use_integral']").click(function(){
		var pay_use_integral_val = $(this).val();
		if(pay_use_integral_val!=1){
			$(".class-village-pay-use-integral").hide(200);
		}else{
			$(".class-village-pay-use-integral").show(200);
		}
	});

	var percent = $('#percent');
	$('input[name="open_own_percent"]').click(function(){
		if($(this).val()==1){
			percent.show();
			if(percent.val()<0){
				percent.val('');
			}

		}else{
			percent.hide();
			percent.attr('value',-1);

		}
	});

	var rebate_percent = $('#rebate_percent');
	$('input[name="open_rebate_percent"]').click(function(){
		if($(this).val()==1){
			rebate_percent.show();
			if(rebate_percent.val()<0){
				rebate_percent.val('');
			}

		}else{
			rebate_percent.hide();
			rebate_percent.attr('value',-1);

		}
	});

	if($('input[name="open_sub_mchid"]:checked').val()==1){
		$('.sub_mch').show();
	}else{
		$('.sub_mch').hide();
	}
	$('input[name="open_sub_mchid"]').click(function(){
		var sub = $(this);
		if(sub.val()==1){
			$('.sub_mch').show();
		}else{
			$('.sub_mch').hide();
		}
	});

</script>
<include file="Public:footer"/>