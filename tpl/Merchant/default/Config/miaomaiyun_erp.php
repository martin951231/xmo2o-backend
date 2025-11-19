<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{$now_store.name}</li>
			<li class="active">{pigcms{:L_("淼迈云ERP配置")}</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
                                <input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
								<!--<div class="form-group">
									<label class="col-sm-1"><label for="miaomaiyun_erp_appid">{pigcms{:L_("应用ID")}</label></label>
									<input class="col-sm-2" size="10" name="miaomaiyun_erp_appid" id="miaomaiyun_erp_appid" type="text" value="{pigcms{$now_store.miaomaiyun_erp_appid}"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="miaomaiyun_erp_appsecret">{pigcms{:L_("应用密钥")}</label></label>
                                    <input class="col-sm-2" size="10" name="miaomaiyun_erp_appsecret" id="miaomaiyun_erp_appsecret" type="text" value="{pigcms{$now_store.miaomaiyun_erp_appsecret}"/>
                                </div>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="miaomaiyun_erp_appsecret">{pigcms{:L_("门店ID")}</label></label>
                                    <input class="col-sm-2" size="10" name="miaomaiyun_erp_storeid" id="miaomaiyun_erp_storeid" type="text" value="{pigcms{$now_store.miaomaiyun_erp_storeid}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="miaomaiyun_erp_appsecret">{pigcms{:L_("操作人ID")}</label></label>
                                    <input class="col-sm-2" size="10" name="miaomaiyun_erp_pt_user_id" id="miaomaiyun_erp_pt_user_id" type="text" value="{pigcms{$now_store.miaomaiyun_erp_pt_user_id}"/>
                                </div>
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