<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">【{pigcms{$thisCard['cardname']}{pigcms{:L_("】的特权管理")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">	
							<li>
								<a href="{pigcms{:U('Card/members', array('id' => $thisCard['id']))}">{pigcms{:L_("会员管理")}</a>
							</li>	
							<li>
								<a href="{pigcms{:U('Card/coupon', array('id' => $thisCard['id']))}">{pigcms{:L_("优惠券活动")}</a>
							</li>	
							<li>
								<a href="{pigcms{:U('Card/integral', array('id' => $thisCard['id']))}">{pigcms{:L_("礼品券活动")}</a>
							</li>
							<li class="active">
								<a href="{pigcms{:U('Card/privilege', array('id' => $thisCard['id']))}">{pigcms{:L_("特权管理")}</a>
							</li>
							<!-- <li>
								<a href="{pigcms{:U('Card/gifts', array('id' => $thisCard['id']))}">{pigcms{:L_("开卡赠送")}</a>
							</li> -->
						</ul>
					
						<div class="tab-content">
							<div class="tab-pane active">
								<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("发布会员特权")}</button>
								<div id="shopList" class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th id="shopList_c1" width="100">{pigcms{:L_("标题")}</th>
												<th id="shopList_c0" width="100">{pigcms{:L_("使用次数")}</th>
												<th id="shopList_c3" width="100">{pigcms{:L_("创建时间")}</th>
												<th id="shopList_c3" width="100">{pigcms{:L_("过期时间")}</th>
												<th id="shopList_c11" width="100">{pigcms{:L_("操作")}</th>
											</tr>
										</thead>
										<tbody>
											<if condition="$data_vip">
												<volist name="data_vip" id="list">
													<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
														<td>{pigcms{$list.title}</td>
														<td>{pigcms{$list.usetime}</td>
														<td><if condition="$list['type'] eq 1">{pigcms{:L_("无时间限制")}<else/>{pigcms{$list.statdate|date='Y-m-d',###}</if></td>
														<td><if condition="$list['type'] eq 1">{pigcms{:L_("无时间限制")}<else/>{pigcms{$list.enddate|date='Y-m-d',###}</if></td>
														<td class="button-column" nowrap="nowrap">
															<a href="{pigcms{:U('Card/useRecords', array('id'=>$thisCard['id'],'type'=>'privilege','itemid'=>$list['id']))}">{pigcms{:L_("使用统计")}</a>
															|
															<a class="green" style="padding-right:8px;" href="{pigcms{:U('Card/privilege_edit', array('itemid'=>$list['id'],'id'=>$thisCard['id']))}" ><i class="ace-icon fa fa-pencil bigger-130"></i></a>
															|
															<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Card/privilege_del',array('itemid'=>$list['id'],'id'=>$thisCard['id']))}">
																<i class="ace-icon fa fa-trash-o bigger-130"></i>
															</a>
														</td>
													</tr>
												</volist>
											<else/>
												<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>
									{pigcms{$page}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
function CreateShop(){
	window.location.href = "{pigcms{:U('Card/privilege_edit', array('id' => $thisCard['id']))}";
}
function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}
</script>
<include file="Public:footer"/>
