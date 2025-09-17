<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="#" onclick="goUrl('v20/public/platform/#/merchant/store.merchant/StoreList','','1','')">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{$now_store.name}</li>
			<li class="active">{pigcms{:L_("店铺优惠列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				.cur_place{
					padding: 10px 0 20px 0;
					color:#999
				}
				.cur_place span,.cur_place a{
					padding: 0 5px;
					 
				}
			.application-list {
					margin-bottom: 24px;
					-webkit-animation: enterIn .2s ease-out;
					animation: enterIn .2s ease-out;
					-webkit-animation-fill-mode: forwards;
					animation-fill-mode: forwards;
				}
				.application-title {
					margin-bottom: 20px;
					color: #323233;
				}
				.application-title .title-main {
					font-size: 16px;
					font-weight: 700;
				}
				.application-title .title-desc {
					color: #999;
					margin-left: 10px;
					font-size:13px;
				}
			</style>
			<div class="application-title">
				<span class="title-main">店铺优惠</span>
				<span class="title-desc">有效活跃整场活动气氛，吸引顾客下单购买</span>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<a class="btn btn-success" title="{pigcms{:L_("新建优惠")}" href="{pigcms{:U('discount_add', array('store_id' => $store['store_id']))}">{pigcms{:L_("新建优惠")}</a>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
								<th>{pigcms{:L_("编号")}</th>
								<th>{pigcms{:L_("适用的业务")}</th>
								<th>{pigcms{:L_("类别")}</th>								
								<th>{pigcms{:L_("满足金额")}</th>
								<th>{pigcms{:L_("优惠金额")}</th>
								<th>{pigcms{:L_("是否与限时优惠、店铺")}/{pigcms{:L_("分类折扣、会员优惠同享")}</th>
								<th>{pigcms{:L_("使用状态")}</th>
								<th class="textcenter">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
							<if condition="is_array($discount_list)">
								<volist name="discount_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td><if condition="$vo['use_limit'] eq 0">仅{pigcms{$config.shop_alias_name}<elseif condition="$vo['use_limit'] eq 1" />仅{pigcms{$config.meal_alias_name}<else />{pigcms{$config.shop_alias_name}和{pigcms{$config.meal_alias_name}</if></td>
										<td><if condition="$vo['type'] eq 0">{pigcms{:L_("新单")}<elseif condition="$vo['type'] eq 1" />{pigcms{:L_("满减")}<else />{pigcms{:L_("配送")}</if></td>
										<td>{pigcms{$vo.full_money}</td>
										<td>{pigcms{$vo.reduce_money}</td>
										<td><if condition="$vo['is_share'] eq 1"><font color="green">{pigcms{:L_("同享")}</font><else/><font color="red">{pigcms{:L_("不同享")}</font></if></td>
										<td><if condition="$vo['status'] eq 1"><font color="green">{pigcms{:L_("启用")}</font><else/><font color="red">{pigcms{:L_("停用")}</font></if></td>
										<td class="button-column">
											<a style="width:80px;" class="label label-sm label-info" href="{pigcms{:U('Config/discount_edit',array('store_id'=>$vo['store_id'], 'id' => $vo['id']))}">{pigcms{:L_("编辑")}</a>
										</td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="6">{pigcms{:L_("列表为空！")}</td></tr>
							</if>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	 function goUrl(url,new_blank,new_tab,new_page) {
				if(new_blank>0){
					window.open(url)
				}else if(new_tab){
					top.openNewTab(url)

				}else if(new_page>0){
                    window.top.location.href = url
                }else{
					window.top.contentScrollToTop()
					location.href=url
				}
			}
 </script>
<include file="Public:footer"/>
