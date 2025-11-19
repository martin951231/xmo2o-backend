<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/bargain_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("砍价列表")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/bargain_team_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("砍价小组列表")}</a>
			</li>
			<li class="active">{pigcms{:L_("砍价小组详情")}</li>
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
				
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
								
									<th>{pigcms{:L_("参团用户昵称")}</th>
									<th>{pigcms{:L_("手机")}</th>
									<th>{pigcms{:L_("砍价金额")}</th>
									<th>{pigcms{:L_("砍价时间")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										
											<td>{pigcms{$vo['nickname']}</td>
											<td>{pigcms{$vo['phone']}</td>
											<td>{pigcms{$vo['bar_price']}</td>
											<td>{pigcms{$vo['bar_time']|date="Y-m-d H:i:s",###}</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("没有砍价用户")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
