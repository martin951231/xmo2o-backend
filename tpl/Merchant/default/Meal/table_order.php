<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Meal/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Meal/index')}">{pigcms{$now_store.name}</a></li>
			<li class="active">{pigcms{:L_("餐台预定详情")}</li>
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
					<button class="btn btn-success">{pigcms{$table['name']}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("预定人")}</th>
									<th>{pigcms{:L_("预定时间")}</th>
									<th>{pigcms{:L_("电话")}</th>
									<th>{pigcms{:L_("支付状态")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.arrive_time|date="Y-m-d H:i:s",###}</td>
											<td>{pigcms{$vo.phone}</td>
											<td><if condition="$vo['paid'] eq 0">{pigcms{:L_("未支付")}<elseif condition="$vo['paid'] eq 2" />{pigcms{:L_("预付定金")}{pigcms{$vo['pay_money']}{pigcms{$config['Currency_txt']}<else />{pigcms{:L_("已支付")}</if></td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>
