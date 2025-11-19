<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/add_xiaowei_submit')}" frame="true" refresh="true">
		<input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="160">商家名称</th>
				<td><div class="show">{pigcms{$merchant.name}</div></td>
			</tr>
			<tr>
				<th width="160">身份证人像面照片</th>
				<td>
					<input type="hidden" name="id_card_copy" value="{pigcms{$xiaowei.id_card_copy}"/>
					<input type="hidden" name="id_card_copy_media_id" validate="required:true" value="{pigcms{$xiaowei.id_card_copy_media_id}"/>
					<a href="javascript:void(0)" data-name="id_card_copy" class="btn btn-sm btn-success J_selectImage">上传图片</a>
				    <img src="{pigcms{:file_domain()}{pigcms{$xiaowei.id_card_copy}" width="50px" <if condition="!empty($xiaowei['id_card_copy'])"> style="margin-left: 30px;"<else/>style="margin-left: 30px;display:none;"</if> />
				</td>
			</tr>
			<tr>
				<th width="160">身份证国徽面照片</th>
				<td>
					<input type="hidden" name="id_card_national" value="{pigcms{$xiaowei.id_card_national}"/>
					<input type="hidden" name="id_card_national_media_id" validate="required:true" value="{pigcms{$xiaowei.id_card_national_media_id}"/>
					<a href="javascript:void(0)" data-name="id_card_national" class="btn btn-sm btn-success J_selectImage">上传图片</a>
				    <img src="{pigcms{:file_domain()}{pigcms{$xiaowei.id_card_national}" width="50px" <if condition="!empty($xiaowei['id_card_national'])"> style="margin-left: 30px;"<else/>style="margin-left: 30px;display:none;"</if> />
				</td>
			</tr>	
			<tr>
				<th width="160">身份证姓名</th>
				<td>
					<input type="text" class="input fl" size="20" name="sfz_name" validate="required:true" value="{pigcms{$xiaowei.sfz_name}"/>
				</td>
			</tr>	
			<tr>
				<th width="160">身份证号码</th>
				<td>
					<input type="text" class="input fl" size="40" name="sfz_number" validate="required:true" value="{pigcms{$xiaowei.sfz_number}"/>
				</td>
			</tr>
			<tr>
				<th width="160">身份证有效期限</th>
				<td>
					<input type="text" class="input fl" name="start_time" size="25" placeholder="开始时间" value="{pigcms{$xiaowei.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true" tips="要与上传的身份证照片内容一致，结束时间不填则为‘长期’" />
					<input type="text" class="input fl" name="end_time" size="25" placeholder="结束时间" value="{pigcms{$xiaowei.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" />
				</td>
			</tr>	
			<tr>
				<th width="160">开户名称</th>
				<td>
					<input type="text" class="input fl" size="20" name="account_name" validate="required:true" value="{pigcms{$xiaowei.account_name}" tips="必须与身份证姓名一致"/>
				</td>
			</tr>
			<tr>
				<th width="160">开户银行</th>
				<td>
					<select name="account_bank">
						<option <if condition="$xiaowei.account_bank eq '工商银行'">selected="selected"</if> value="工商银行">工商银行</option>
						<option <if condition="$xiaowei.account_bank eq '交通银行'">selected="selected"</if> value="交通银行">交通银行</option>
						<option <if condition="$xiaowei.account_bank eq '招商银行'">selected="selected"</if> value="招商银行">招商银行</option>
						<option <if condition="$xiaowei.account_bank eq '民生银行'">selected="selected"</if> value="民生银行">民生银行</option>
						<option <if condition="$xiaowei.account_bank eq '中信银行'">selected="selected"</if> value="中信银行">中信银行</option>
						<option <if condition="$xiaowei.account_bank eq '浦发银行'">selected="selected"</if> value="浦发银行">浦发银行</option>
						<option <if condition="$xiaowei.account_bank eq '兴业银行'">selected="selected"</if> value="兴业银行">兴业银行</option>
						<option <if condition="$xiaowei.account_bank eq '光大银行'">selected="selected"</if> value="光大银行">光大银行</option>
						<option <if condition="$xiaowei.account_bank eq '广发银行'">selected="selected"</if> value="广发银行">广发银行</option>
						<option <if condition="$xiaowei.account_bank eq '平安银行'">selected="selected"</if> value="平安银行">平安银行</option>
						<option <if condition="$xiaowei.account_bank eq '北京银行'">selected="selected"</if> value="北京银行">北京银行</option>
						<option <if condition="$xiaowei.account_bank eq '华夏银行'">selected="selected"</if> value="华夏银行">华夏银行</option>
						<option <if condition="$xiaowei.account_bank eq '农业银行'">selected="selected"</if> value="农业银行">农业银行</option>
						<option <if condition="$xiaowei.account_bank eq '建设银行'">selected="selected"</if> value="建设银行">建设银行</option>
						<option <if condition="$xiaowei.account_bank eq '邮政储蓄银行'">selected="selected"</if> value="邮政储蓄银行">邮政储蓄银行</option>
						<option <if condition="$xiaowei.account_bank eq '中国银行'">selected="selected"</if> value="中国银行">中国银行</option>
						<option <if condition="$xiaowei.account_bank eq '宁波银行'">selected="selected"</if> value="宁波银行">宁波银行</option>
						<option <if condition="$xiaowei.account_bank eq '其他银行'">selected="selected"</if> value="其他银行">其他银行</option>
					</select>
				</td>
			</tr>
			<tr>
				<th width="160">开户银行省市</th>
				<td>
					<input type="text" class="input fl" name="search_city" id="search_city" size="25" value="{pigcms{$xiaowei.search_city}" autocomplete="off" placeholder="输入省市并选择" />
					<input type="hidden" name="bank_address_code" validate="required:true" value="{pigcms{$xiaowei.bank_address_code}" tips="输入城市关键词，从搜索到的结果中选择" />
				</td>
			</tr>
			<tr>
				<th width="160">银行账号</th>
				<td>
					<input type="text" class="input fl" size="40" name="account_number" validate="required:true" value="{pigcms{$xiaowei.account_number}" tips="小微商户开户目前不支持以下前缀的银行卡623501;621468;620522;625191;622384;623078;940034;622150;622151;622181;622188;955100;621095;620062;621285;621798;621799;621797;622199;621096;62215049;62215050;62215051;62218849;62218850;62218851;621622;623219;621674;623218;621599;623698;623699;623686;621098;620529;622180;622182;622187;622189;621582;623676;623677;622812;622810;622811;628310;625919;625368;625367;518905;622835;625603;625605;518905"/>
				</td>
			</tr>	
			<tr>
				<th width="160">门店名称</th>
				<td>
					<input type="text" class="input fl" size="20" name="store_name" validate="required:true" value="{pigcms{$xiaowei.store_name}" tips="门店场所：填写门店名称 流动经营/便民服务：填写经营/服务名称 线上商品/服务交易：填写线上店铺名称"/>
				</td>
			</tr>
			<tr>
				<th width="160">门店省市</th>
				<td>
					<input type="text" class="input fl" name="search_store_city" id="search_store_city" size="25" value="{pigcms{$xiaowei.search_store_city}" autocomplete="off" placeholder="输入省市并选择" />
					<input type="hidden" name="store_address_code" validate="required:true" value="{pigcms{$xiaowei.store_address_code}" tips="输入城市关键词，从搜索到的结果中选择" />
				</td>
			</tr>
			<tr>
				<th width="160">门店街道名称</th>
				<td>
					<input type="text" class="input fl" size="50" name="store_street" validate="required:true" value="{pigcms{$xiaowei.store_street}" tips="门店场所：填写店铺详细地址，具体区/县及街道门牌号或大厦楼层 流动经营/便民服务：填写“无” 线上商品/服务交易：填写电商平台名称"/>
				</td>
			</tr>
			<tr>
				<th width="160">门店门口照片</th>
				<td>
					<input type="hidden" name="store_entrance_pic" value="{pigcms{$xiaowei.store_entrance_pic}"/>
					<input type="hidden" name="store_entrance_pic_media_id" validate="required:true" value="{pigcms{$xiaowei.store_entrance_pic_media_id}" />
					<a href="javascript:void(0)" data-name="store_entrance_pic" class="btn btn-sm btn-success J_selectImage">上传图片</a>
				    <img src="{pigcms{:file_domain()}{pigcms{$xiaowei.store_entrance_pic}" width="50px" <if condition="!empty($xiaowei['store_entrance_pic'])"> style="margin-left: 30px;"<else/>style="margin-left: 30px;display:none;"</if> />
				</td>
			</tr>
			<tr>
				<th width="160">店内环境照片</th>
				<td>
					<input type="hidden" name="indoor_pic" value="{pigcms{$xiaowei.indoor_pic}"/>
					<input type="hidden" name="indoor_pic_media_id" validate="required:true" value="{pigcms{$xiaowei.indoor_pic_media_id}"/>
					<a href="javascript:void(0)" data-name="indoor_pic" class="btn btn-sm btn-success J_selectImage">上传图片</a>
				    <img src="{pigcms{:file_domain()}{pigcms{$xiaowei.indoor_pic}" width="50px" <if condition="!empty($xiaowei['indoor_pic'])"> style="margin-left: 30px;"<else/>style="margin-left: 30px;display:none;"</if> />
				</td>
			</tr>
			<tr>
				<th width="160">商户简称</th>
				<td>
					<input type="text" class="input fl" size="20" name="merchant_shortname" validate="required:true" value="{pigcms{$xiaowei.merchant_shortname}" tips="将在支付完成页向买家展示，需与商家的实际经营场景相符"/>
				</td>
			</tr>
			<tr>
				<th width="160">客服电话</th>
				<td>
					<input type="text" class="input fl" size="20" name="service_phone" validate="required:true" value="{pigcms{$xiaowei.service_phone}" tips="在交易记录中向买家展示，请确保电话畅通以便平台回拨确认"/>
				</td>
			</tr>
			<tr>
				<th width="160">服务描述</th>
				<td>
					<select name="product_desc">
						<option <if condition="$xiaowei.product_desc eq '餐饮'">selected="selected"</if> value="餐饮">餐饮</option>
						<option <if condition="$xiaowei.product_desc eq '线下零售'">selected="selected"</if> value="线下零售">线下零售</option>
						<option <if condition="$xiaowei.product_desc eq '居民生活服务'">selected="selected"</if> value="居民生活服务">居民生活服务</option>
						<option <if condition="$xiaowei.product_desc eq '休闲娱乐'">selected="selected"</if> value="休闲娱乐">休闲娱乐</option>
						<option <if condition="$xiaowei.product_desc eq '交通出行'">selected="selected"</if> value="交通出行">交通出行</option>
						<option <if condition="$xiaowei.product_desc eq '其他'">selected="selected"</if> value="其他">其他</option>
					</select>
				</td>
			</tr>
			<tr>
				<th width="160">费率</th>
				<td>
					<input type="text" class="input fl" size="20" name="rate" validate="required:true" value="{pigcms{$xiaowei.rate}" tips="填写一个数字，例如：0.6，则实际费率是0.6%"/>
				</td>
			</tr>
			<tr>
				<th width="160">超级管理员姓名</th>
				<td>
					<input type="text" class="input fl" size="20" name="contact" validate="required:true" value="{pigcms{$xiaowei.contact}" tips="和身份证姓名一致，超级管理员需在开户后进行签约，并可接收日常重要管理信息和进行资金操作，请确定其为商户法定代表人或负责人"/>
				</td>
			</tr>
			<tr>
				<th width="160">手机号码</th>
				<td>
					<input type="text" class="input fl" size="20" name="contact_phone" validate="required:true" value="{pigcms{$xiaowei.contact_phone}" tips="11位数字，手机号码"/>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
<include file="Public:footer"/>
	<link rel="stylesheet" href="{pigcms{$static_public}js/jquery-ui/jquery-ui.css">
	<script type="text/javascript" src="{pigcms{$static_public}js/jquery-ui/jquery-ui.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
$("#search_city").autocomplete({
  source: "{pigcms{:U('search_cityno')}",
  minLength: 1,
  select: function( event, ui ) {
    $("[name='bank_address_code']").val(ui.item.id);
  }
});
$("#search_city").keyup(function(event) {
	$("[name='bank_address_code']").val(0);//必须让上面联想选择
});
$("#search_store_city").autocomplete({
  source: "{pigcms{:U('search_cityno')}",
  minLength: 1,
  select: function( event, ui ) {
    $("[name='store_address_code']").val(ui.item.id);
  }
});
$("#search_store_city").keyup(function(event) {
	$("[name='store_address_code']").val(0);//必须让上面联想选择
});

KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true
	});
	 //var islock=false;
	K('.J_selectImage').click(function(){
		var obj=$(this);
		editor.uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=system/image&wx=1";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					var _url = url.split("|||");
					obj.siblings('[name="'+obj.data('name')+'"]').val(_url[0]);
					obj.siblings('[name="'+obj.data('name')+'_media_id"]').val(_url[1]);
					editor.hideDialog();
					obj.siblings('img').attr('src',_url[0]).show();
					//window.location.reload();
				}
			});
		});
	   
	});
});
</script>