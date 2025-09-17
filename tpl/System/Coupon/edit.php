<include file="Public:header"/>
	<style>.frame_form td{vertical-align:middle;}
		select{width:80px;}
		textarea{width:300px;height:80px;}
		.textIamge{
			background-image:none!important;
		}
		.wx_coupon{
			<if condition="$coupon['sync_wx'] eq 0 OR  $coupon['wx_cardid'] eq ''">display:none;</if>
		}
		.rand_send{
			<if condition="$coupon['rand_send'] eq 0">display:none;</if>
		}
		.mini_img{
			width:60px;
			height:30px;
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Coupon/edit')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="coupon_id" value="{pigcms{$coupon.coupon_id}"/>
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
					<td colspan="2" id="choose_cityareass" province_idss="{pigcms{$coupon['province_id']}" city_idss="{pigcms{$coupon['city_id']}"></td>
				</tr>
			</if>
			<tr>
				<td width="100">优惠券名称：</td>
				<td>{pigcms{$coupon.name}</td>
				<td></td>
			</tr>
			<tr>
				<td width="100">优惠券图标：</td>
				<td><input type="text"  style="width:200px;" name="img" class="input input-image" value="{pigcms{$coupon.img}"  validate="required:true" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
				<td>图片尺寸建议 正方形 200 X 200</td>			
			</tr>
			
			<tr>
				<td width="100">微信分享图片：</td>
				<td><input type="text"  style="width:200px;" name="wx_share_img" class="input input-image" value="{pigcms{$coupon.wx_share_img}"  validate="" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
				<td><font color="red">不上传图片则不能生产二维码</font></td>			
			</tr>
			<tr>
				<td width="100">同步到微信卡券：</td>
				<td colspan="2">
					<if condition="$coupon['sync_wx'] eq 1"><span>是</span><else /><span>否</span></if>
					&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Coupon/show')}','微信卡券示例图',300,500,true,false,false,'','show',true);" style="color:blue">微信卡券示例图</a>
				</td>
				
			</tr>
			<tr>
				<td width="100">是否仅为手动派券可用：</td>
				<td colspan="2">
					<if condition="$coupon['is_hide'] eq 1"><span>是</span><else /><span>否</span></if>
				</td>
			</tr>
				
			<tr class="wx_coupon">
				<td width="100" style="color:red">创建朋友的券：</td>
				<td >
					<if condition="$coupon['share_friends'] eq 1"><span>是</span><else /><span>否</span></if>
				</td>
				<td>
					选择创建朋友的券后该优惠券不能分享和赠送
				</td>
					
			</tr>

			<tr>
				<td width="100" style="color:red">是否为特权派发券：</td>
				<td >
					<if condition="$coupon['is_privileged'] eq 1"><span>是</span><else /><span>否</span></if>
				</td>
				<td>
					特权派发券需要设置派发时间
				</td>
					
			</tr>
			
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券颜色</td>
				<td colspan="2">
					<div id="wx_color" style="width:30px;height:30px;background-color:{pigcms{$coupon.color}; float:left;margin-left:10px"></div>
				</td>
					
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">商家名称</td>
				<td colspan="2">
				{pigcms{$coupon.brand_name}
				</td>
					
			</tr>
			
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券提示</td>
				<td colspan="2">
					{pigcms{$coupon.notice}
				</td>
					
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">卡券副标题</td>
				<td colspan="2">
					{pigcms{$coupon.center_sub_title}
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">立即使用链接</td>
				<td colspan="2">
				<a href="{pigcms{$coupon.center_url|html_entity_decode}" target="_blank">点击查看</a>
				</td>
					
			</tr>
		
			
			<tr class="wx_coupon">
				<td width="100" style="color:red">更多优惠链接</td>
				<td colspan="2">
					<a href="{pigcms{$coupon['promotion_url']}" target="_blank">点击查看</a>
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">自定义链接</td>
				<td colspan="2">
					标题：{pigcms{$coupon.custom_url_name}<br><br>
					链接：{pigcms{$coupon.custom_url}<br><br>
					副标题：{pigcms{$coupon.custom_url_sub_title}
				</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">封面图片</td>
				<td colspan="2"><img class="mini_img" src="{pigcms{:replace_file_domain($coupon['icon_url_list'])}">&nbsp;&nbsp; 描述 :{pigcms{$coupon.abstract}</td>
			</tr>
			<tr class="wx_coupon">
				<td width="100" style="color:red">商家服务类型</td>
				<td colspan="2">
					<volist name="coupon.business_service" id="vo">
						<if condition="$vo eq 'BIZ_SERVICE_DELIVER'">
						外卖服务&nbsp;&nbsp;
						<elseif condition="$vo eq 'BIZ_SERVICE_FREE_PARK'" />
						停车位&nbsp;&nbsp;
						<elseif condition="$vo eq 'BIZ_SERVICE_WITH_PET'" />
						可带宠物&nbsp;&nbsp;
						<elseif condition="$vo eq 'BIZ_SERVICE_FREE_WIFI'" />
						免费wifi&nbsp;&nbsp;
						</if>
					</volist>
					
				</td>
			</tr>
			<tr class="wx_coupon">
				<td colspan="3">
				<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
					<volist name="coupon.text_image_list" id="vo">
					<tr class="plus textIamge" >
						<td width="60" style="color:red">卡券图文<label>{pigcms{$i}</label></td>
						<td>
							<table style="width:100%;border:#d5dfe8 1px solid;padding:2px;">
								<tr class="textIamge">
									<td width="36" style="color:red">图片：</td>
									<td><img class="mini_img" src="{pigcms{:replace_file_domain($vo['image_url'])}"></td>
									<td width="36" style="color:red">描述：</td>
									<td>
									{pigcms{$vo.text}
									</td>
									<td rowspan="2" class="delete">
									
									</td>
								</tr>
								
							</table>
						</td>
					</tr>
					</volist>
					<tr class="textIamge">
						
					</tr>
					
					
				</table>
				</td>
			</tr>
			<tr>
				<td width="100">是否只可签到领取</td>
				<td colspan="2">
					<if condition="$coupon['allow_sign'] eq 1">是<elseif condition="$coupon['allow_sign'] eq 0"/>否</if>
				</td>				
			</tr>
			<tr>
				<td width="100">是否只允许在积分商城中兑换获得：</td>
				<td colspan="2">
					<if condition="$coupon['allow_gift'] eq 1">是<elseif condition="$coupon['allow_gift'] eq 0"/>否</if>
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
			<if condition="$coupon['allow_gift'] neq 1 && $coupon['allow_sign'] neq 1">
				<tr>
					<td width="100">是否只允许新用户领取：</td>
					<td colspan="2">
						<if condition="$coupon['allow_new'] eq 1">是<elseif condition="$coupon['allow_new'] eq 0"/>否</if>
					</td>
					
				</tr>
				
				<!--<tr>
					<td width="100">是否随机派发：</td>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$coupon['rand_send'] eq 1"> selected</if>"><span>是</span><input type="radio" name="rand_send" value="1" <if condition="$coupon['rand_send'] eq 1"> checked="checked"</if>/></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$coupon['rand_send'] eq 0"> selected</if>"><span>否</span><input type="radio" name="rand_send" value="0" <if condition="$coupon['rand_send'] eq 0"> checked="checked"</if>/></label></span>
					</td>
					<td style="width:100px;">随机派发的优惠券不能被用户主动领取，只能弹窗领取，同样适用于小区开门的随机赠券功能。</td>
				</tr>
			</if>
			<tr class="rand_send">
				<td width="100">随机派发数量</td>
				<td >
					<input type="text" class="input fl" name="rand_send_num" value="{pigcms{$coupon.rand_send_num}"  autocomplete="off" validate="digits:true">
				</td>
				<td style="width:100px;">随机派发数量不受优惠券总数量限制</td>
			</tr>
			<tr class="rand_send">
				<td width="100" >随机派发开始结束时间</td>
				<td >
					<input type="text" class="input-text" name="rand_send_start_time" style="width:120px;" id="d4311"  value="<if condition="$coupon.rand_send_start_time gt 0">{pigcms{$coupon.rand_send_start_time|date='Y:m:d H:i:s',###}</if>" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:00:00'})"/>-
					<input type="text" class="input-text" name="rand_send_end_time" style="width:120px;" id="d4311" value="<if condition="$coupon.rand_send_end_time gt 0">{pigcms{$coupon.rand_send_end_time|date='Y:m:d H:i:s',###}</if>" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:00:00'})" />
				</td>
				<td style="width:100px;">随机派发的优惠券不能被用户主动领取，只能弹窗领取</td>
			</tr>-->
			<tr>
				<td width="100">是否允许开通平台会员等级时赠送：</td>
				<td colspan="2">
					<if condition="$coupon['is_vip_level'] eq 1">是<elseif condition="$coupon['is_vip_level'] eq 0"/>否</if>
				</td>
					
			</tr>
			<tr>
				<td width="100">使用平台：</td>
				<td colspan="2">{pigcms{$coupon.platform}</td>
			</tr>
            <if condition="($coupon['cate_name_origin'] eq 'shop') OR ($coupon['cate_name_origin'] eq 'all')">
            <tr>
                <td width="100">是否与平台满减共享：</td>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$coupon['is_share_with_discount'] eq 1"> selected</if>"><span>是</span><input type="radio" name="is_share_with_discount" value="1" <if condition="$coupon['is_share_with_discount'] eq 1"> checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$coupon['is_share_with_discount'] eq 0"> selected</if>"><span>否</span><input type="radio" name="is_share_with_discount" value="0" <if condition="$coupon['is_share_with_discount'] eq 0"> checked="checked"</if>/></label></span>
                </td>
                <td>目前只针对外卖业务有效</td>
            </tr>
            </if>
			<tr>
				<td width="100">使用类别：</td>
				<td>{pigcms{$coupon.cate_name}</td>
				<if condition="C('customization.internal_membership_card')">
					<td>选择"店内收银"类型，则优惠券适用于"店内收银"和"{pigcms{:C('config.cash_alias_name')}"</td>
				</if>
			</tr>
			<tr>
				<td width="100">使用分类：</td>
				<td colspan="2" id="cate_id">{pigcms{$coupon.cate_id}</td>
			</tr>
			<eq name="coupon.cate_name_origin" value="shop">
			<tr>
				<td width="100">配送类型：</td>
				<td>
					<eq name="coupon.deliver_type" value="0">
						不限
					</eq>
					<eq name="coupon.deliver_type" value="1">
						自取
					</eq>
					<eq name="coupon.deliver_type" value="2">
						配送
					</eq>
				</td>
			</tr>
			<tr>
				<td width="100">优惠类型：</td>
				<td>
					<eq name="coupon.discount_type" value="0">
						减免总费用
					</eq>
					<eq name="coupon.discount_type" value="1">
						只减免运费
					</eq>
					<eq name="coupon.discount_type" value="2">
						只减免餐费
					</eq>
				</td>
			</tr>
			</eq>
			<tr>
				<td width="100">微信展示简短描述(微信卡包优惠说明)：</td>
				<td>
				<textarea name="des" value=""  autocomplete="off" validate="">{pigcms{$coupon.des}</textarea>
				</td>
			</tr>
			<tr>
				<td width="100">领取页面详细描述(微信卡包使用须知)：</td>
				<td>
				<textarea name="des_detial" value=""  autocomplete="off" validate="">{pigcms{$coupon.des_detial}</textarea>
				</td>
				<td>每条描述请换行</td>
			</tr>
			<tr>
				<td width="100">数量：{pigcms{$coupon.now_num}</td>
				<td width="85%" colspan="3">
				
				<input type="hidden" name="status" value="{pigcms{$coupon.status}"/>
				<input type="hidden" name="had_pull" value="{pigcms{$coupon.had_pull}"/>
				<input type="hidden" name="num" value="{pigcms{$coupon.now_num}"/>
				<select name="add" class="fl">
					<option value="0">增加</option>
					<option value="1">减少</option>
				</select>
				<input type="text" class="input fl" style="margin-left:4px;" name="num_add" value=""  autocomplete="off" validate="digits:true,min:1">已经被领了{pigcms{$coupon.had_pull}张 <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Coupon/coupon_add_log',array('coupon_id'=>$coupon['coupon_id']))}','优惠券增加减少记录',800,560,true,false,false,null,'edit_percent',true);" style="color:blue">增加减少记录</a>
				</td>
			</tr>
			
			<if condition="$coupon['allow_gift'] neq 1">
			<tr>
				<td width="100">领取数量限制：</td>
				<td>
				{pigcms{$coupon.limit}
				</td>
			</tr>
			</if>
			<tr style="display:none;">
				<td width="100">使用数量限制：</td>
				<td>
				{pigcms{$coupon.use_limit}
				</td>
				<td>每单使用优惠券的数量，默认使用一张</td>
			</tr>
			<tr>
				<td width="100">是否是折扣券：</td>
				<td>
				<if condition="$coupon.is_discount eq 1">是<else />否</if>
				</td>
			</tr>
			<if condition="$coupon.is_discount eq 1">
			<tr>
				<td width="100">折扣：</td>
				<td>
				{pigcms{$coupon.discount_value|floatval}
				</td>
			</tr>
			<else />
			<tr>
				<td width="100">优惠金额：</td>
				<td>
				{pigcms{$coupon.discount}
				</td>
			</tr>
			</if>

            <if condition="$coupon['cate_name_origin'] eq 'shop' OR $coupon['cate_name_origin'] eq 'mall'">
            <tr>
                <td width="100">平台补贴金额</td>
                <td><input type="text" class="input fl" name="plat_money" value="{pigcms{$coupon.plat_money}" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
            <tr>
                <td width="100">商家补贴金额</td>
                <td><input type="text" class="input fl" name="merchant_money" value="{pigcms{$coupon.merchant_money}" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
            </if>

			<tr>
                <td width="100">最小订单金额：</td>
				<td>
				{pigcms{$coupon.order_money}
				</td>
			</tr>
			<tr>
				<td width="100">使用起始时间：</td>
				<td>
					{pigcms{$coupon.start_time|date='Y年m月d日 H:i',###}——{pigcms{$coupon.end_time|date='Y年m月d日 H:i',###}
				</td>
			</tr>
			<tr>
				<td width="100">领取起始时间：</td>
				<td>
					<if condition="$coupon['get_start_time']">
					{pigcms{$coupon.get_start_time|date='Y年m月d日 H:i',###}——{pigcms{$coupon.get_end_time|date='Y年m月d日 H:i',###}
					<else/>
					无
					</if>
				</td>
			</tr>
			<tr>
				<td width="100">用户可使用的天数：</td>
				<td>
					<if condition="$coupon['valid_time']">
					{pigcms{$coupon.valid_time}天
					<else/>
					无
					</if>
				</td>
			</tr>
			<tr>
				<td width="100">立即使用链接：</td>
				<td colspan="2">
					<input type="text" class="input fl" name="coupon_url" value="{pigcms{$coupon.coupon_url}" id="url"  autocomplete="off" validate="maxlength:200,url:true"  placeholder="请填写链接地址"/><a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<script>
				function addLink(domid,iskeyword){
					art.dialog.data('domid', domid);
					art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
				}
			</script>

            <if condition="$coupon['cate_name_origin'] eq 'shop' || $coupon['cate_name_origin'] eq 'mall' || $coupon['cate_name_origin'] eq 'store' || $coupon['cate_name_origin'] eq 'cash'">
            <tr class="use_type">
                <td width="100">使用店铺</td>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$coupon['use_type'] eq '0'">selected</if>"><span>全部店铺可用</span><input type="radio" name="use_type" value="0" <if condition="$coupon['use_type'] eq '0'">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$coupon['use_type'] eq '1'">selected</if>"><span>指定店铺可用</span><input type="radio" name="use_type" value="1" <if condition="$coupon['use_type'] eq '1'">checked="checked"</if>/></label></span>
                </td>
            </tr>
            </if>
			
			<if condition="($coupon.status eq 0) OR ($coupon.status eq 1)">
			<tr>
				<td width="100">状态</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$coupon['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$coupon['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$coupon['status'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="status" value="0" <if condition="$coupon['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
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
			
			$('select[name="color"]').css('background-color','#63b359');	
			$('select[name="color"]').change(function(event) {
				$('#wx_color').css('background-color',$('select[name="color"]').find('option:selected').html());
				$(this).css('background-color',$('select[name="color"]').find('option:selected').html());
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

