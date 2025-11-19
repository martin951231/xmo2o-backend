<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Config/discount',array('store_id'=>$now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
			<li class="active">{pigcms{:L_("添加店铺优惠")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<div class="form-group">
									<label class="col-sm-1"><label for="use_limit">{pigcms{:L_("适用的业务")}</label></label>
									<select name="use_limit">
										<option value="0">仅{pigcms{$config.shop_alias_name}</option>
										<option value="1">仅{pigcms{$config.meal_alias_name}</option>
										<option value="2">{pigcms{$config.shop_alias_name}和{pigcms{$config.meal_alias_name}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("优惠条件")}</label></label>
									<input class="col-sm-1" size="10" name="full_money" id="full_money" type="text" value="{pigcms{$now_discount.full_money|default=''}"/>
									<span class="form_tips" style="color: red">{pigcms{:L_("满足条件的金额")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("优惠的金额")}</label></label>
									<input class="col-sm-1" size="10" name="reduce_money" id="reduce_money" type="text" value="{pigcms{$now_discount.reduce_money|default=''}"/>
									<span class="form_tips" style="color: red">{pigcms{:L_("可优惠的金额")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="type">{pigcms{:L_("优惠类型")}</label>
									<select name="type" id="type">
										<option value="0" <if condition="$now_discount['type'] eq 0">selected="selected"</if>>{pigcms{:L_("新单")}</option>
										<option value="1" <if condition="$now_discount['type'] eq 1">selected="selected"</if>>{pigcms{:L_("满减")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("同享规则")}</label></label>
									<span><label><input name="is_share" value="1" checked="checked" type="radio">&nbsp;<span style="color: blue;">{pigcms{:L_("与限时优惠、店铺")}/{pigcms{:L_("分类折扣、会员优惠同享")}</span>&nbsp;</label></span>
									<span><label><input name="is_share" value="0" type="radio" >&nbsp;<span>{pigcms{:L_("与限时优惠、店铺")}/{pigcms{:L_("分类折扣、会员优惠不同享")}</span></label></span>
                                    <span class="form_tips" style="color: red">{pigcms{:L_("同享，则所有店铺优惠用户均可享用；不同享，则满减优惠（含新单，满减）用户不能享用，其他店铺优惠（含限时优惠、店铺")}/{pigcms{:L_("分类折扣、会员优惠）正常享用")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("使用状态")}</label></label>
                                    <span><label><input name="status" value="0" type="radio">&nbsp;<span>{pigcms{:L_("停用")}</span>&nbsp;</label></span>
                                    <span><label><input name="status" checked="checked" value="1" type="radio" >&nbsp;<span>{pigcms{:L_("开启")}</span></label></span>
                                </div>
								<if condition="$ok_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:blue;">{pigcms{$ok_tips}</span>				
									</div>
								</if>
								<if condition="$error_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:red;">{pigcms{$error_tips}</span>				
									</div>
								</if>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
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
<include file="Public:footer"/>
