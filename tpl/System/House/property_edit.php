<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('House/property_edit')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$now_village.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

			<tr>
				<th width="120">物业公司名称</th>
				<td><input type="text" class="input fl" name="property_name" value="{pigcms{$now_village.property_name}" size="40" placeholder="请输入物业公司名称" validate="maxlength:50,required:true"/></td>
			</tr>
            <tr>
                <th width="100">物业公司名称简称</th>
                <td><input type="text" class="input fl" name="property_short_name" value="{pigcms{$now_village.property_short_name}" size="30" placeholder="请输入物业公司名称简称" validate="maxlength:100,required:true"/></td>
            </tr>
			<tr >
				<th width="15%">所在区域</th>
				<td id="choose_cityarea" province_id="{pigcms{$now_village.province_id}" city_id="{pigcms{$now_village.city_id}" area_id="{pigcms{$now_village.area_id}" circle_id="-1" circle="-1"></td>
			</tr>
			<tr>
				<th width="120">物业联系地址</th>
				<td><input type="text" class="input fl" name="property_address" value="{pigcms{$now_village.property_address}" size="40" placeholder="请输入物业联系地址" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="120">物业联系电话</th>
				<td><input type="text" class="input fl" name="property_phone" value="{pigcms{$now_village.property_phone}" size="20" placeholder="请输入物业联系电话" validate="maxlength:50,required:true" tips="多个号码以空格分开"/></td>
			</tr>
			<tr>
				<th width="120">物业后台管理帐号</th>
				<td>{pigcms{$now_village.account}</td>
			</tr>
			<tr>
				<th width="120">物业后台管理密码</th>
				<td><input type="text" class="input fl" name="password" size="20" placeholder="不修改请勿填写" validate="maxlength:50,minlength:6" tips="不修改请勿填写"/></td>
			</tr>
<!--            <tr>-->
<!--                <th width="100">可添加小区数量</th>-->
<!--                <td><input type="text" class="input fl" name="village_num" size="20" value="{pigcms{$now_village.village_num}" placeholder="请输入小区数量" validate="maxlength:50,required:true"/></td>-->
<!--            </tr>-->
<!---->
<!--            <tr>-->
<!--                <th width="100">可导入房间数量</th>-->
<!--                <td>-->
<!--                    <input type="text" class="input fl" name="room_limit_num" size="20" value="{pigcms{$now_village.room_limit_num|default=0}" placeholder="请输入限制导入房间数量" tips="填写0或者小于0均不作限制" validate="maxlength:50,required:true"/>-->
<!--                    <span>当前物业下已经导入房间数：<span style="color: red;">{pigcms{$limit_info['room_num']|default='0'}</span></span>-->
<!--                </td>-->
<!---->
<!--            </tr>-->
            <tr>
                <th width="100">功能套餐名称</th>
                <td>{pigcms{$package_order_info['package_title']}
                    <input type="hidden" name="order_id" value="{pigcms{$package_order_info['order_id']}">
                </td>
            </tr>
            <tr>
                <th width="100">功能套餐到期时间</th>
                <td>
                    <if condition="$package_order_info['package_end_time'] gt 0">
                    {pigcms{$package_order_info['package_end_time']|date='Y-m-d',###}
                    </if>
                </td>
            </tr>
            <tr>
                <th width="100">针对当前套餐</th>
                <td>
                    <label><input name="upgrade_type" type="radio" onclick="upgrade(this.value)" value="1"/>&nbsp;&nbsp;升级</label>
                    &nbsp;&nbsp;&nbsp;
                    <label><input name="upgrade_type" type="radio" onclick="upgrade(this.value)" value="2" checked/>&nbsp;&nbsp;续费</label>
                </td>
            </tr>
            <tr id="trs" style="display: none">
                <th>套餐功能选项</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="package_id" id="package_id" onchange="package(this.value)" style="width: 145px;">
                            <option value="0">请选择</option>
                            <volist name="package_info" id="vo">
                                <option value="{pigcms{$vo.package_id}">{pigcms{$vo['package_title']}</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>

            <tr  <if condition="!$isSuiChuan"> style="display:none;" </if> >
                <th width="120">是否虚拟物业</th>
                <td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['is_virtual'] eq 1">selected</if>" ><span>是</span>
					<input type="radio" name="is_virtual" value="1" <if condition="$now_village['is_virtual'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['is_virtual'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="is_virtual" value="0" <if condition="$now_village['is_virtual'] eq 0">checked="checked"</if>/></label></span>
                </td>
            </tr>


            <tr id="trst" style="display: none">
                <th>功能套餐购买周期</th>
                <td>
                    <input type="number" oninput="value=value.replace(/[^\d]/g,'')" min="1" class="input" name="pay_year" size="20" placeholder="请输入套餐购买周期" validate="maxlength:3"/>最多可购买<span id="years"></span>年
                </td>
            </tr>
            <tr id="tim">
                <th>功能套餐购买周期</th>
                <td>
                    <input type="number" oninput="value=value.replace(/[^\d]/g,'')" min="1" class="input fl" name="pay_years" size="20"  placeholder="请输入套餐购买周期" validate="maxlength:3"/>最多可购买{pigcms{$max_renewal}年
                </td>
            </tr>

            <tr>
                <th width="120">物业业务返点</th>
                <td>

					<span class="cb-enable"><label class="cb-enable <if condition="$now_village['rebate_percent'] egt 0">selected</if>"><span>设置</span>
					<input type="radio" name="open_rebate_percent" value="1" <if condition="$now_village['rebate_percent'] egt 0">checked="checked"</if>/></label></span>
                    <span class="cb-disable " ><label class="cb-disable  <if condition="$now_village['rebate_percent'] lt 0">selected</if>"><span>跳过</span>
					<input type="radio" name="open_rebate_percent" value="0" <if condition="$now_village['rebate_percent'] lt 0">checked="checked"</if>/></label></span>
                    <em class="notice_tips" tips="请填写0-100数字，代表返点的百分比，0 不返点，2 返点2%，跳过则代表以平台总设置为准"></em>

                    <input type="text" class="input fl" name="rebate_percent" id="rebate_percent" value="{pigcms{$now_village.rebate_percent}" size="20" <if condition="$now_village['rebate_percent'] lt 0">style="display:none"</if> validate="number:true" />

                </td>
            </tr>
			
			<if condition="$config['open_tianquetech']">
				<tr>
					<th width="160">随行付商户编号</th>
					<td><input type="text" class="input fl" name="app_secret" size="25" value="{pigcms{$now_village['app_secret']}" placeholder="随行付分配商户编号" validate="" />
						<p style="height: 15px;line-height: 15px;" class="fl">
							<if condition="$now_village.tianque_mno_status eq 1">
								<span style="color:green">当前商户号已签约</span>
								<elseif condition="$now_village.tianque_mno_status eq 2" />
								<span style="color:red">当前商户号签约存在异常（可能是商户号填写不正确）</span>
								<elseif condition="$now_village.tianque_mno_url neq ''" />
								<span style="color:red">将该地址（{pigcms{$now_village['tianque_mno_url']}）复制给商家进行签约分账权限，签约成功后，次日生效。</span>
							</if>
						</p>
					</td>
				</tr>
			</if>

			<tr>
				<th width="100">绑定商家ID</th>
				<td><input type="text" class="input fl" name="merchant_id" size="20" value="{pigcms{$now_village.merchant_id}" placeholder="请输入商家ID" validate="maxlength:50,number:true" tips="如果需要解绑,则填写0或者不填"/></td>
			</tr>

            <tr>
                <th width="200">电子发票身份认证</th>
                <td><input type="text" class="input fl" name="identity" size="20" value="{pigcms{$now_village.identity}" placeholder="请输入电子发票身份认证" validate="maxlength:250"/></td>
            </tr>

            <tr>
                <th width="200">开票请求域名</th>
                <td><input type="text" class="input fl" name="house_e_invoice_url" size="20" value="{pigcms{$now_village.house_e_invoice_url}" placeholder="请输入开票请求域名" validate="maxlength:250" /></td>
            </tr>

            <tr>
                <th width="200">获取电子发票PDF域名</th>
                <td><input type="text" class="input fl" name="house_e_pdf_url" size="20" value="{pigcms{$now_village.house_e_pdf_url}" placeholder="请输入获取电子发票PDF域名" validate="maxlength:250" /></td>
            </tr>
            <tr>
                <th width="200">短信签名</th>
                <td><input type="text" class="input fl" name="sms_sign" size="20" value="{pigcms{$now_village.sms_sign}" placeholder="请输入短信签名" validate="maxlength:250" />
                    <em class="notice_tips" tips="物业发送短信时的签名，设置后，需要联系客服进行报备"></em>
                </td>
            </tr>

			<if condition="$config['is_demo_domain']">
				<tr>
					<th width="80">是否展示由我们提供技术支持</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$now_village.is_config_show eq 1">selected</if>"><span>是</span><input type="radio" name="is_config_show" value="1" <if condition="$now_village.is_config_show eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$now_village.is_config_show eq 0 ">selected</if>"><span>否</span><input type="radio" name="is_config_show" value="0" <if condition="$now_village['is_config_show'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
            <!-- 物业的没有的
            <if condition="$config.open_chinaums eq 1">
            <tr>
                <th width="120">银联支付商户号</th>
                <td><input type="text" class="input fl" name="mid" value="{pigcms{$now_village.mid}" size="25"  tips="银联支付商户号"/></td>
            </tr>
            </if>
            -->

            <if condition="$is_scuru eq 1">
            <tr>
                <th width="120">四川信用社商户号</th>
                <td><div style="margin-bottom: 15px;">一级商户号：<input type="text" class="input" name="scrcu_mer_no" value="{pigcms{$now_village.scrcu_mer_no}" size="25"  tips="四川信用社商一级户号"/>&nbsp;&nbsp;&nbsp;<span style="color:red;">请仔细核对您的商户号类型是一级的还是二级的</span></div> <div>二级商户号：<input type="text" class="input" name="scrcu_mer_no_2" value="{pigcms{$now_village.scrcu_mer_no_2}" size="25"  tips="四川信用社商二级户号"/> &nbsp;&nbsp;&nbsp;<span style="color:red;">二级商户号可不填，如果填写了则一级商户必须填写！</span></div></td> 
            </tr>

            </if>

            <tr>

				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_village.status eq 1 OR empty($now_village)">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_village.status eq 1 OR empty($now_village)">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="($now_village['status'] eq 0 || $now_village['status'] eq 2) AND isset($now_village['status'])">selected</if>"><span>关闭</span><input type="radio" name="status" value="2" <if condition="($now_village['status'] eq 0 || $now_village['status'] eq 2) AND isset($now_village['status'])">checked="checked"</if>/></label></span>
                    <em class="notice_tips" tips="若物业在待审核状态中，通过请选择启用，拒绝请选择关闭"></em>
                </td>
			</tr>
            <tr>
                <th width="100">企微登录凭依手机号</th>
                <td>
                    <input type="text" class="input fl" name="login_phone" value="{pigcms{$workWeiXinPhoneInfo.phone}" size="30" placeholder="请输入登录凭依手机号" tips="填写了登录凭依手机号，企业微信移动管理端登录后以此手机号为准进行账号匹配自动登录"/>
                </td>
            </tr>
            
            <if condition="$about_info['is_virtual'] eq 1">
                <tr>
                    <th width="120">是否虚拟物业</th>
                    <td>是</td>
                </tr>
                <tr>
                    <th width="120">项目类型</th>
                    <td>{pigcms{$about_info.facilities_type_text}</td>
                </tr>
            </if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    function upgrade(vals) {
        if(vals== 1)
        {
            $('#trst').attr('style','dispaly:');
            $('#trs').attr('style','dispaly:');
            $('#tim').hide();
        }
        if(vals== 2){
            $('#trst').hide();
            $('#trs').hide();
            $('#tim').attr('style','display:');
        }
    }
    function package(id) {
        $.ajax({
            url: '{pigcms{:U("getPackageY")}',
            type: 'post',
            dataType: 'json',
            data: {
                'package_id':id,
                'id': "{pigcms{$now_village.id}"
            },
            success: function(res){
                if(res.code == 1)
                {
                    $('#trs').attr('style','dispaly:');
                    $('#years').html(res.package_limit_num)
                }else{
                    $('#trs').hide();
                }
            }
        })
    }
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