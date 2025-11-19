<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Config/staff', array('store_id' => $now_store['store_id']))}">【{pigcms{$now_store.name}{pigcms{:L_("】 店员列表")}</a>
			</li>
			<li class="active"><if condition="$item">{pigcms{:L_("修改")}<else/>{pigcms{:L_("添加")}</if>{pigcms{:L_("店员")}</li>
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
									<label class="col-sm-1"><label for="name">{pigcms{:L_("姓名")}</label></label>
									<input type="text" class="col-sm-2" name="name" id="name" value="{pigcms{$item.name}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("店员类型")}</label></label>
									<select name="type">
										<volist name="staff_type" id="vo">
											<option value="{pigcms{$key}" <if condition="$key eq $item['type']">selected="selected"</if> >{pigcms{$vo}</option>
										</volist>
									</select>
									<if condition="$config['open_staff_power'] eq 1">
									<span class="form_tips type_desc"></span>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="username">{pigcms{:L_("帐号")}</label></label>
									<input type="text" class="col-sm-2" name="username" id="username" value="{pigcms{$item.username}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="password">{pigcms{:L_("密码")}</label></label>
									<input type="password" class="col-sm-2" name="password" id="password" />
									<if condition="$item['password']"><span class="form_tips">({pigcms{:L_("如果不修改密码请留空")})</span></if>
								</div>
								<if condition="$config.international_phone eq 1">
									<div class="form-group">
									<label class="col-sm-1"><label for="password">{pigcms{:L_("区号")}</label></label>
										<pigcms:national_phone id="phone_country_type" style="height:34px;float:left;margin-right:5px;">
												<option value="{pigcms{$vo.code}"  <if condition="($item['phone_country_type'] eq $vo['code'] AND isset($item['phone_country_type'])) OR (!isset($item['phone_country_type']) AND $config['qcloud_sms_default_country'] eq $vo['code'])">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
										</pigcms:national_phone>
									</div>
								</if>
								<if condition="!isset($config['not_o2o_demo']) OR empty($item['tel'])">
									<div class="form-group">
										<label class="col-sm-1"><label for="contact_name">{pigcms{:L_("电话")}</label></label>
										<input type="text" class="col-sm-2" name="tel" id="tel" value="{pigcms{$item.tel}" />
									</div>
								</if>
								<div class="form-group change_price">
									<label class="col-sm-1">{pigcms{:L_("能否修改订单价格")}</label>
									<label><span><label><input name="is_change" <if condition="$item['is_change'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("不能")}</span>&nbsp;</span></label>
									<label><span><label><input name="is_change" <if condition="$item['is_change'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("能")}</span></span></label>
									<if condition="$config['open_staff_power'] eq 1">
									<span class="form_tips change_desc"></span>
									</if>
								</div>
								<eq name="is_open_scenic" value='1'>
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("能否核销景区门票订单")}</label>
										<label><span><label><input name="is_verify_ticket" <if condition="$item['is_verify_ticket'] eq 0 ">checked="checked"</if> value="0" type="radio"></label>&nbsp;<span>{pigcms{:L_("不能")}</span>&nbsp;</span></label>
										<label><span><label><input name="is_verify_ticket" <if condition="$item['is_verify_ticket'] eq 1 ">checked="checked"</if> value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("能")}</span></span></label>
									</div>
								</eq>
								<div class="clearfix form-actions">
									<div class="col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$('select[name=type]').on('change',function(){
	var type = $(this).val();
	if (type==0) {
		$('.type_desc').html('{pigcms{:L_("该角色不具备线下零售结算、餐饮收银台结算、到店付结算、店铺管理的权限，但店员的其他权限均具备。")}');
		$('.change_desc').html('{pigcms{:L_("指是否具备修改X1在线订单的结算价权限。",['X1'=>$config['shop_alias_name']])}');
	}else if(type==1){
		$('.type_desc').html('{pigcms{:L_("该角色不具备店铺管理的权限，但店员的其他权限均具备。")}');
		$('.change_desc').html('{pigcms{:L_("指是否具备修改线下零售、餐饮收银台、X1在线订单的结算价权限。",['X1'=>$config['shop_alias_name']])}');
	}else if(type==2){
		$('.type_desc').html('{pigcms{:L_("该角色具备店员的所有权限。")}');
		$('.change_desc').html('{pigcms{:L_("指是否具备修改线下零售、餐饮收银台、X1在线订单的结算价权限。",['X1'=>$config['shop_alias_name']])}');
	}
})
$('select[name=type]').trigger('change');	
</script>
<include file="Public:footer"/>