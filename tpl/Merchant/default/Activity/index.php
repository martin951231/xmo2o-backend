<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-empire"></i>
				{pigcms{:L_("商家推广")}
			</li>
			<li class="active"><a href="{pigcms{:U('Activity/index')}">{pigcms{:L_("平台活动列表")}</a></li>
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
					<form action="{pigcms{:U('Activity/index')}" method="get" style="float:left;margin-left:10px">
						<input type="hidden" name="c" value="Activity"/>
						<input type="hidden" name="a" value="index"/>
						{pigcms{:L_("活动类别：")} <select name="type">
							<option value="2" <if condition="$_GET['type'] eq '2'">selected="selected"</if>>{pigcms{:L_("优惠券")}</option>
						</select>
						<button class="btn btn-success">{pigcms{:L_("查询")}</button>
					</form>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("创建活动")}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="50">{pigcms{:L_("编号")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("类别")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("活动名称")}</th>
									<th id="shopList_c0" width="50"><if condition="$_GET['type'] eq 2">{pigcms{:L_("总商品数量")}<else/>{pigcms{:L_("需参与人次")}</if></th>
									<th id="shopList_c0" width="50">{pigcms{:L_("已参与数量")}</th>
									<th id="shopList_c0" width="50">{pigcms{:L_("所需金钱")}</th>
									<th id="shopList_c0" width="50">{pigcms{:L_("所需")}{pigcms{$config.score_name}</th>
									<th id="shopList_c3" width="100">{pigcms{:L_("开始时间")}/{pigcms{:L_("结束时间")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("状态")}</th>
									<th id="shopList_c11" width="100">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$activity_list">
									<volist name="activity_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.pigcms_id}</td>
											<td>{pigcms{$vo.type_txt}</td>
											<td title="{pigcms{$vo.title}">{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.all_count}</td>
											<td>{pigcms{$vo.part_count}</td>
											<td>{pigcms{$vo.money}</td>
											<td>{pigcms{$vo.mer_score}</td>
											<td>{pigcms{$vo.begin_time|date='Y-m-d H:i',###} / {pigcms{$vo.end_time|date='Y-m-d H:i',###}</td>
											<td><if condition="$vo['is_finish'] eq 1"><font color="green">{pigcms{:L_("已完成")}</font><elseif condition="$vo['status'] eq 0"/><font color="red">{pigcms{:L_("待审核")}</font><elseif condition="$vo['status'] eq 2"/><font color="red">{pigcms{:L_("已结束")}</font><else/><font color="green">{pigcms{:L_("进行中")}</font></if></td>
											<td class="button-column" nowrap="nowrap">
												<if condition="$vo['is_finish']">
													<if condition="$vo['type'] eq 1">
														<a title="{pigcms{:L_("查看详情")}" class="green yiyuan_handle_btn" style="padding-right:8px;" href="{pigcms{:U('Activity/yiyuanduobao',array('id'=>$vo['pigcms_id']))}">
															{pigcms{:L_("中奖信息")}
														</a>
													</if>
												</if>
												<if condition="$vo.type eq 1">
													<a href="{pigcms{:U('yydb_order_list',array('activity_id'=>$vo['pigcms_id']))}">{pigcms{:L_("订单列表")}</a> 
												</if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="10" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	$(function(){
		$('.yiyuan_handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看获奖人信息")}',
				padding: 0,
				width: 720,
				height: 520,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
			});
			return false;
		});
		
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
	function CreateShop(){
		window.location.href = "{pigcms{:U('Activity/add')}";
	}
</script>
<include file="Public:footer"/>
