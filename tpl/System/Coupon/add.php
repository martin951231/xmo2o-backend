<include file="Public:header"/>
	<style>.frame_form td{vertical-align:middle;}
		select{width:80px;}
		textarea{width:300px;height:80px;}
		
		.textIamge{
			background-image:none!important;
		}
		.wx_coupon{
			display:none;
		}
		.rand_send{
			display:none;
		}
		.ke-dialog{
			top:10px;
		}

        .plat_money, .merchant_money, .use_type {
            display: none;
        }
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Coupon/add')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">	
			<if condition="$config['many_city']">
				<tr id="adver_currency">
					<td width="100">跨城市通用</td>
					<td colspan="2" class="radio_box">
						<span class="cb-enable"><label class="cb-enable <if condition="$coupon['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$coupon['city_id'] eq 0">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$coupon['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$coupon['city_id'] neq 0">checked="checked"</if> /></label></span>
					</td>
				</tr>
				<tr id="adver_region" <if condition="$coupon['city_id'] eq 0">style="display:none;"</if>>
					<td width="100">选择城市</td>
					<td colspan="2" id="choose_cityareass" province_idss="" city_idss=""></td>
				</tr>
			</if>
			<tr>
				<td width="100">优惠券名称：</td>
				<td>
					<input type="text"  style="width:300px;" class="input fl" name="name" value=""  validate="required:true" autocomplete="off" />
				</td>
				<td>上限9个汉字长度</td>
			</tr>
			<tr>
				<td width="100">优惠券图标：</td>
				<td><input type="text"  style="width:200px;" validate="required:true" name="img" class="input input-image" value=""   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
				<td>图片尺寸建议 正方形 200 X 200</td>			
			</tr>
			
			<tr>
				<td width="100">微信分享图片：</td>
				<td><input type="text"  style="width:200px;" name="wx_share_img" class="input input-image" value="{pigcms{$coupon.wx_share_img}"  validate="" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
				<td>不上传图片则不能生产二维码</td>			
			</tr>
			<tr>
				<td width="100">是否为特权派发券：</td>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable <if condition="$group['is_auto_offline'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_privileged" value="1" /></label></span>
					<span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="is_privileged" value="0" checked="checked" /></label></span>
				</td>	
			</tr>
			<tr>
				<td width="100">是否仅为手动派券可用：</td>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="is_hide" value="1" /></label></span>
					<span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="is_hide" value="0" checked="checked" /></label></span>
				</td>
			</tr>
			<tr>
				<td width="100">同步到微信卡券：</td>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable "><span>是</span><input type="radio" name="sync_wx" value="1" /></label></span>
					<span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="sync_wx" value="0" checked="checked" /></label></span>
					&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Coupon/show')}','微信卡券示例图',680,620,true,false,false,'','show',true);" style="color:blue">微信卡券示例图</a>
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券颜色</td>
				<td colspan="2">
					<select name="color" id="color" style="float:left;width:100px;" >
						<volist name="color_list" id="vo">
							<option value="{pigcms{$key}" style="background-color:{pigcms{$vo};margin:5px auto;">{pigcms{$vo}</option>
						</volist>
					</select>
					<div id="wx_color" style="width:30px;height:30px;background-color:#63b359; float:left;margin-left:10px"></div>
				</td>
					
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">商家名称</td>
				<td colspan="2">
					<input type="text"  style="width:180px;" name="brand_name" class="input input-image" value="" >（12个汉字以内）
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券提示</td>
				<td colspan="2">
					<input type="text"  style="width:180px;" name="notice" class="input input-image" value="" >（16个汉字以内）
				</td>
					
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券副标题</td>
				<td colspan="2">
					<input type="text"  style="width:180px;" name="center_sub_title" class="input input-image" value="" >（副标题6个汉字以内）
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">立即使用链接</td>
				<td colspan="2">
					<input type="text"  style="width:180px;" name="center_url" class="input input-image" value="" >
				</td>
					
			</tr>
		
			
			<tr class="wx_coupon">
				<td width="100" style="color:red">更多优惠链接</td>
				<td colspan="2">
					<input type="text"  style="width:180px;" name="promotion_url" class="input input-image" value="" >
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">自定义链接</td>
				<td colspan="2">
					标题：<input type="text"  style="width:100px;" name="custom_url_name" class="input input-image" value="" >（5个汉字以内）<br><br>
					链接：<input type="text"  style="width:180px;" name="custom_url" class="input input-image" value="" ><br><br>
					副标题：<input type="text"  style="width:180px;" name="custom_url_sub_title" class="input input-image" value="" >（副标题6个汉字以内）
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">封面图片</td>
				<td colspan="2"><input type="text"  style="width:200px;" name="icon_url_list" class="input input-image" value=""   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a> 描述 :<input type="text"  style="width:200px;" name="abstract" class="input" value="" ></td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">商家服务类型</td>
				<td colspan="2">
					<input type="checkbox"  name="business_service[]" class="input input-image" value="BIZ_SERVICE_DELIVER" checked="checked">外卖服务
					<input type="checkbox"  name="business_service[]" class="input input-image" value="BIZ_SERVICE_FREE_PARK" checked="checked">停车位
					<input type="checkbox"  name="business_service[]" class="input input-image" value="BIZ_SERVICE_WITH_PET" checked="checked">可带宠物
					<input type="checkbox"  name="business_service[]" class="input input-image" value="BIZ_SERVICE_FREE_WIFI" checked="checked">免费wifi
				</td>
			</tr>
			<tr class="wx_coupon">
				<td colspan="3">
					<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
						
						<tr class="plus textIamge" >
							<td width="60" style="color:red">卡券图文<label>1</label></td>
							<td>
								<table style="width:100%;border:#d5dfe8 1px solid;padding:2px;">
									<tr class="textIamge">
										<td width="36" style="color:red">图片：</td>
										<td><input type="text"  style="width:120px;" name="image_url[]" class="input input-image" value=""   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
										<td width="36" style="color:red">描述：</td>
										<td><textarea  style="width:150px;height:60px" class="input" name="text[]" ></textarea></td>
										<td rowspan="2" class="delete">
											<a href="javascript:void(0)" onclick="del(this)"><img style="width:20px;height:20px;" src="{pigcms{$static_path}images/del.jpg"/></a>
										</td>
									<tr/>
									
								</table>
							</td>
						</tr>
						<tr class="textIamge">
							<td></td>
							<td><a href="javascript:void(0)" onclick="plus()"><img style="width:20px;height:20px;" src="{pigcms{$static_path}images/plus.jpg"/></a></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="allow_sign_box">
				<td width="100">是否只可签到领取：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['allow_sign'] eq 1"> selected</if>"><span>是</span><input type="radio" name="allow_sign" value="1" <if condition="$coupon['allow_sign'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['allow_sign'] eq 0"> selected</if>"><span>否</span><input type="radio" name="allow_sign" value="0" <if condition="$coupon['allow_sign'] eq 0"> checked="checked"</if>/></label></span>
				</td>
				<td>设置为只可签到领取后，则该券只能在签到时领取，其他方式均不可领取到，包括不可派发</td>
			</tr>
			<tr class="allow_gift_box">
				<td width="100">是否只允许在积分商城中兑换获得：</td>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['allow_gift'] eq 1"> selected</if>"><span>是</span><input type="radio" name="allow_gift" value="1" <if condition="$coupon['allow_gift'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['allow_gift'] eq 0"> selected</if>"><span>否</span><input type="radio" name="allow_gift" value="0" <if condition="$coupon['allow_gift'] eq 0"> checked="checked"</if>/></label></span>
				</td>
			</tr>
            <tr class="allow_im_get">
                <td width="100">智能客服：</td>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$coupon['allow_im_get'] eq 1"> selected</if>"><span>是</span><input type="radio" name="allow_im_get" value="1" <if condition="$coupon['allow_im_get'] eq 1"> checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$coupon['allow_im_get'] eq 0"> selected</if>"><span>否</span><input type="radio" name="allow_im_get" value="0" <if condition="$coupon['allow_im_get'] eq 0"> checked="checked"</if>/></label></span>
                </td>
                <td></td>
            </tr>
			<tr class="allow_new">
				<td width="100">是否只允许新用户领取：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['allow_new'] eq 1"> selected</if>"><span>是</span><input type="radio" name="allow_new" value="1" <if condition="$coupon['allow_new'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['allow_new'] eq 0"> selected</if>"><span>否</span><input type="radio" name="allow_new" value="0" <if condition="$coupon['allow_new'] eq 0"> checked="checked"</if>/></label></span>
				</td>
				<td>如该优惠券的业务使用类别设置为指定业务，则未在相应业务类别下过单的用户即为新用户；如业务使用类别设置为全部业务，则从未在平台下过单的用户即为新用户</td>
			</tr>
			<tr class="allow_new">
				<td width="100">是否允许开通平台会员等级时赠送：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['is_vip_level'] eq 1"> selected</if>"><span>是</span><input type="radio" name="is_vip_level" value="1" <if condition="$coupon['is_vip_level'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['is_vip_level'] eq 0"> selected</if>"><span>否</span><input type="radio" name="is_vip_level" value="0" <if condition="$coupon['is_vip_level'] eq 0"> checked="checked"</if>/></label></span>
				</td>
				<td>如该优惠券的业务使用类别设置为变成会员后,可以在会员等级中绑定相应的优惠券,用户在哪个会员等级中,可自动获取该优惠券</td>
			</tr>
			<!--<tr class="rand_send1">
				<td width="100">是否随机派发：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['rand_send'] eq 1"> selected</if>"><span>是</span><input type="radio" name="rand_send" value="1" <if condition="$coupon['rand_send'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['rand_send'] eq 0"> selected</if>"><span>否</span><input type="radio" name="rand_send" value="0" <if condition="$coupon['rand_send'] eq 0"> checked="checked"</if>/></label></span>
				</td>
				<td>随机派发的优惠券不能被用户主动领取，只能弹窗领取</td>
			</tr>
			<tr class="rand_send">
				<td width="100" >随机派发数量</td>
				<td >
					<input type="text" class="input fl" name="rand_send_num" value=""  autocomplete="off" validate="digits:true">
				</td>
				<td>随机派发数量不受优惠券总数量限制</td>
			</tr>
			<tr class="rand_send">
				<td width="100" >随机派发开始结束时间</td>
				<td >
					<input type="text" class="input-text" name="rand_send_start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:00:00'})"/>-
					<input type="text" class="input-text" name="rand_send_end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:00:00'})" />
				</td>
				<td>随机派发的优惠券不能被用户主动领取，只能弹窗领取,不填不派发</td>
			</tr>-->
			<tr>
				<td width="100">使用平台：</td>
				<td>
					<volist name="platform" id="vo">
						<label for="platform_{pigcms{$key}"><input type="checkbox" name="platform[]" value="{pigcms{$key}" id="platform_{pigcms{$key}" checked="checked"/>{pigcms{$vo}</label>&nbsp;&nbsp;&nbsp;&nbsp;
					</volist>
				</td>
				<td>默认全选，必填。选择类型为app，则代表app与小程序都可用</td>
			</tr>

			<tr>
				<td width="100">使用类别：</td>
				<td>
					<select name="cate_name">
						<option value="all">全选</option>
						<volist name="category" id="vo">
							<option checked="checked" value="{pigcms{$key}">{pigcms{$vo}</option>
						</volist>
					</select>
				</td>
				<if condition="C('customization.internal_membership_card')">
					<td>选择"店内收银"类型，则优惠券适用于"店内收银"和"{pigcms{:C('config.cash_alias_name')}"</td>
				</if>
			</tr>
			<tr>
				<td width="100">使用分类：</td>
				<td id="cate_id" colspan="2">
					
				</td>
			</tr>
			<tr id="deliver_area" style="display: none;">
				<td width="100">配送类型：</td>
				<td id="deliver_type" colspan="2">
					<select name="deliver_type">
						<option value="0">不限</option>
						<option value="1">自取</option>
						<option value="2">配送</option>
					</select>
				</td>
			</tr>
			<tr id="discount_area" style="display: none;">
				<td width="100">优惠类型：</td>
				<td id="discount_type" colspan="2">
					<select name="discount_type">
						<option value="0">减免总费用</option>
						<option value="1">只减免运费</option>
						<option value="2">只减免餐费</option>
					</select>
				</td>
			</tr>
			<tr id="is_share_with_discount">
                <td width="100">是否与平台满减共享：</td>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>是</span><input type="radio" name="is_share_with_discount" value="1" checked="checked"/></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>否</span><input type="radio" name="is_share_with_discount" value="0"/></label></span>
                </td>
                <td>目前只针对外卖业务有效</td>
            </tr>
			<tr>
				<td width="100">微信展示简短描述(微信卡包优惠说明)：</td>
				<td colspan="2">
					<textarea name="des" value=""  autocomplete="off" validate=""></textarea>
				</td>
			</tr>
			<tr>
				<td width="100">领取页面详细描述(微信卡包使用须知)：</td>
				<td >
				<textarea name="des_detial" value=""  autocomplete="off" validate=""></textarea>
				</td>
				<td>每条描述请换行</td>
			</tr>
			
			<tr>
				<td width="100">数量：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="num" value=""  autocomplete="off" validate="required:true,digits:true,min:1">
				</td>
			</tr>
			<tr class="limit">
				<td width="100">领取数量限制：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="limit" value="1"  autocomplete="off" validate="required:true,digits:true,min:1">
				</td>
			</tr>
			<tr style="display:none;">
				<td width="100">使用数量限制：</td>
				<td >
					<input type="text" class="input fl" name="use_limit" value="1"  autocomplete="off" validate="required:true,digits:true,min:1" readOnly>
				</td>
				<td>每单使用优惠券的数量，默认使用一张</td>
			</tr>
			<if condition="$config.is_open_merchant_foodshop_discount">
			<tr>
				<td width="100">是否是折扣券：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['is_discount'] eq 1"> selected</if>"><span>是</span><input type="radio" name="is_discount" value="1" <if condition="$coupon['is_discount'] eq 1"> checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['is_discount'] eq 0"> selected</if>"><span>否</span><input type="radio" name="is_discount" value="0" <if condition="$coupon['is_discount'] eq 0"> checked="checked"</if>/></label></span>
				</td>
				<td></td>
			</tr>
			
			<tr class="discount" style="display:none">
				<td width="100">折扣：</td>
				<td>
					<input type="text" class="input fl" name="discount_value" value="" autocomplete="off" validate="number:true,min:0.1,max:9.9"/>
				</td>
				<td>0~10之间的数字，支持一位小数！8代表8折，8.5代表85折</td>
			</tr>
			</if>
			<tr class="money">
				<td width="100">优惠金额：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="discount" value="" autocomplete="off" validate="required:true,number:true,min:0.01"/>
				</td>
			</tr>

            <tr class="plat_money">
                <td width="100">平台补贴金额：</td>
                <td><input type="text" class="input fl" name="plat_money" value="" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
            <tr class="merchant_money">
                <td width="100">商家补贴金额：</td>
                <td><input type="text" class="input fl" name="merchant_money" value="" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
			
			<tr>
				<td width="100">最小订单金额：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="order_money" value=""  autocomplete="off" validate="required:true,number:true,min:0"/>
				</td>
			</tr>
			
			
			<tr>
				<td width="100">使用起始时间：</td>
				<td colspan="2">
					<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>-
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true"/>
				</td>
			</tr>
			<tr>
				<td width="100">领取起始时间：</td>
				<td colspan="2">
					<input type="text" class="input-text" name="get_start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>-
					<input type="text" class="input-text" name="get_end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />
				</td>
			</tr>
			<tr>
				<td width="100">用户可使用的天数：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="valid_time" value=""  autocomplete="off" validate="number:true,min:0" tips=" 用户实际使用时间以【使用起始时间】的最后结束时间为准"/>
				</td>
			</tr>

            <tr class="use_type">
                <td width="100">使用店铺：</td>
                <td colspan="2">
                    <span class="cb-enable"><label class="cb-enable selected"><span>全部店铺可用</span><input type="radio" name="use_type" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定店铺可用</span><input type="radio" name="use_type" value="1" /></label></span>
                </td>
            </tr>
			<tr>
				<td width="100">立即使用链接：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="coupon_url" id="url"  autocomplete="off" validate="maxlength:200,url:true"  placeholder="请填写链接地址"/><a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<script>
				function addLink(domid,iskeyword){
					art.dialog.data('domid', domid);
					art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
				}
			</script>
			<tr>
				<td width="100">状态：</td>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked"/></label></span>
					<span class="cb-disable"><label class="cb-disable "><span>禁止</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
		
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.J_selectImage').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(site_url+url);
								editor.hideDialog();
							}
						});
					});
				});

			});
	</script>
	<script type="text/javascript"> 
		$(document).ready(function() {
			$("#yes").click(function(){
				$("#adver_region").hide();
			})
			$("#no").click(function(){
				$("#adver_region").show();
			})
			
			var admin_area_id = {pigcms{$system_session.area_id};
			if(admin_area_id > 0){
				$("#adver_currency").hide();
				$("#no").trigger('click');
			}

			var post_url = "{pigcms{:U('Coupon/ajax_ordertype_cateid')}";
				$('select[name="cate_name"]').change(function(event) {
					var order_type=$(this).val();
					if(order_type!='all'){
						console.log(order_type);
						$.ajax({
							url: post_url,
							type: 'POST',
							dataType: 'json',
							data: {order_type: order_type},
							success:function(date){
								$('#cate_id').html('<select name="cate_id" id="'+order_type+'"><option value="0">全选</option></select>');
								$.each(date, function(index, val) {
									$('#'+order_type).append('<option value="'+val.cat_id+'">'+val.cat_name+'</option>');
								});
							}
						});
					}else{
						$('#cate_id').empty();
					}

					console.log('111');
					//只针对外卖
					if(order_type=='shop'){
						console.log('222');
						$('#deliver_area').show();
						$('#discount_area').show();
						$('.plat_money').show();
                        $('.merchant_money').show();
                        $('.use_type').show()
						//$('#deliver_type').html('');
					}else if (order_type=='mall'){//商城需要能选择是否是指定店铺
						$('.use_type').show();
						$('.plat_money').show();
                        $('.merchant_money').show();
					}else{
						console.log('333');
						//$('#deliver_type').empty();
						$('deliver_area').hide();
						$('#discount_area').hide();
                        $('.plat_money').hide();
                        $('.merchant_money').hide();
						if(order_type == 'store' || order_type == 'cash'){
							$('.use_type').show();
						}else{
							$('.use_type').hide()
						}
					}

					if(order_type == 'shop' || order_type == 'all'){
                        $('#is_share_with_discount').show();
					}else{
                        $('#is_share_with_discount').hide();
                    }

					
				});

			$('select[name="deliver_type"]').change(function(event) {
				var deliver_type=$(this).val();//运费类型
				if(deliver_type=='1'){//快递方式是自取，此种状况下只能选择餐费
					console.log('deliver_type_1');
					$('#discount_type').html('<select name="discount_type"><option value="2">只减免餐费</option></select>');
				}else{//快递方式是不限或配送，此种情况下，可以选择减免餐费或者减免配送费。
					console.log('deliver_type_0');
					$('#discount_type').html('<select name="discount_type"><option value="0">减免总费用</option><option value="1">只减免运费</option><option value="2">只减免餐费</option></select>');
				}
			});
			
			$('select[name="color"]').css('background-color','#63b359');	
			$('select[name="color"]').change(function(event) {
				$('#wx_color').css('background-color',$('select[name="color"]').find('option:selected').html());
				$(this).css('background-color',$('select[name="color"]').find('option:selected').html());
			});		

			$('input:radio[name="allow_gift"]').click(function(i,val){
				if($(this).val()==1){
					$('.allow_new').hide();
					$('.allow_sign_box').hide();
					$('.rand_send1').hide();
					$('.limit').hide();
				}else{
					$('.allow_new').show();
					$('.allow_sign_box').show();
					$('.rand_send1').show();
					$('.limit').show();
				}
			});
			$('input:radio[name="allow_sign"]').click(function(i,val){
				if($(this).val()==1){
					$('.allow_new').hide();
					$('.allow_gift_box').hide();
					$('.rand_send1').hide();
				}else{
					$('.allow_new').show();
					$('.allow_gift_box').show();
					$('.rand_send1').show();
				}
			});

			$('input:radio[name="sync_wx"]').click(function(i,val){
				if($(this).val()==1){
					$('.wx_coupon').show();
				}else{
					$('.wx_coupon').hide();
				}
			});
			$('input:radio[name="rand_send"]').click(function(i,val){
				if($(this).val()==1){
					$('.rand_send').show();
				}else{
					$('.rand_send').hide();
				}
			});
			
			$('input[name="is_discount"]').click(function(){

				if($(this).val()==1){
					$('.discount').show();
					$('.money').hide();
					
					$('input[name="discount_value"]').attr("disabled",false);
					$('input[name="discount"]').attr("disabled",true);
				}else{
					$('.discount').hide();
					$('.money').show();
					$('input[name="discount"]').attr("disabled",false);
					$('input[name="discount_value"]').attr("disabled",true);
				}
			});
		});
		
		function plus(){
			var item = $('.plus:last');
			var newitem = $(item).clone(true);
			var No = parseInt(item.find("label").html())+1;
			$('.delete').children().show();
			if(No>4){
				alert('不能超过4条信息');
			}else{
				$(item).after(newitem);
				newitem.find('input').attr('value','');
				newitem.find('textarea').attr('value','');
				newitem.find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				newitem.find("label").html(No);
				newitem.find('input[name="url[]"]').attr('id','url'+No);
				newitem.find('.delete').children().show();
			}
		}
		function del(obj){
			if($('.plus').length<=1){
				$('.delete').children().hide();
			}else{
				if($('.plus').length==2){
					$('.delete').children().hide();
				}
				$(obj).parents('.plus').remove();
				$.each($('.plus'), function(index, val) {
					var No =index+1;
					$(val).find('label').html(No);
					$(val).find('input[name="url[]"]').attr('id','url'+No);
					$(val).find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				});
			}
		}
		
		
	</script>
	
<include file="Public:footer"/>

