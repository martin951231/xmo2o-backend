<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-empire"></i>
				<a href="{pigcms{:U($activeType .'/index')}">{pigcms{:L_("微活动")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U($activeType .'/index')}">{pigcms{$thisLottery['title']}</a></li>
			<li class="active">SN{pigcms{:L_("码列表")}</li>
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
									<th id="shopList_c1" width="50">{pigcms{:L_("序号")}</th>
									<th id="shopList_c1" width="100">SN{pigcms{:L_("码(中奖号")})</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("奖项")}</th>
									<th id="shopList_c0" width="100">{pigcms{:L_("是否已发奖品")}</th>
									<th id="shopList_c3" width="100">{pigcms{:L_("奖品发送时间")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("中奖者手机号")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("中奖者微信码")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("中奖时间")}</th>
									<th id="shopList_c11" width="100">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$record">
									<volist name="record" id="record">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$i}</td>
											<td>{pigcms{$record.sn}</td>
											<td>{pigcms{$record.prize}</td>
											<td><if condition="$record['sendstutas'] eq 0">{pigcms{:L_("否")}<else/>{pigcms{:L_("是")}</if></td>
											<td><if condition="$record['sendtime'] eq 0"> <else/>{pigcms{$record.sendtime|date='Y-m-d H:i:s',###}</if></td>
											<td>{pigcms{$record.phone}</td>
											<td>{pigcms{$record.wecha_name}</td>
											<td><if condition="$record['time'] neq 0"> {pigcms{$record.time|date='Y-m-d H:i:s',###}</if></td>
											<td class="button-column" nowrap="nowrap">
												<if condition="$record['sendstutas'] eq 0"> 
													<a href="{pigcms{:U($activeType .'/sendprize',array('id'=>$record['id']))}">{pigcms{:L_("发奖")}</a>
												<else/>
													<a href="{pigcms{:U($activeType .'/sendnull',array('id'=>$record['id']))}">{pigcms{:L_("取消发奖")}</a>
												</if>
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U($activeType .'/snDelete',array('id'=>$record['id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="9" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
</script>
<include file="Public:footer"/>
