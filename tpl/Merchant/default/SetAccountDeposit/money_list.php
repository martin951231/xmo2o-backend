<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('index')}">{pigcms{:L_("云商通托管账号")}</a></li>
			<li class="active"><a href="{pigcms{:U('money_list')}">{pigcms{:L_("商家流水列表")}</a></li>
			<if condition="$now_group">
			<li>{pigcms{$now_group.s_name}</li>
			</if>
			<li>{pigcms{:L_("订单列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<form action="{pigcms{:U('setAccountDeposit/money_list')}" method="get">
				
					
					
					<input type="hidden" name="c" value="setAccountDeposit"/>
							<input type="hidden" name="a" value="money_list"/>
							{pigcms{:L_("筛选")}: 
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>{pigcms{:L_("默认显示7天内的数据, 筛选项开始结束时间不能大于7天")}
				
					
					<a class="btn btn-success"  href="javascript:void(0)" onclick="exports()"  style="float:right;display:none;">{pigcms{:L_("导出订单")}</a>
				</form>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("商户订单号（支付订单）")}</th>
					
									<th>{pigcms{:L_("订单类型")}</th>
									<th>{pigcms{:L_("云账户订单号")}</th>
									<th>{pigcms{:L_("初始金额")}</th>
									<th>{pigcms{:L_("变更金额")}</th>
									<th>{pigcms{:L_("变更时间")}</th>
									<th class="textcenter">{pigcms{:L_("描述")}</th>
								</tr>
							</thead>
							<tbody>
								
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.order_id}</td>
										<td>{pigcms{$vo.order_type}</td>
								
										<td>{pigcms{$vo.tradeNo}</td>
										<td>{pigcms{$vo['oriAmount']/100 }</td>
										<td>{pigcms{$vo['chgAmount']/100  }</td>
										<td>{pigcms{$vo.changeTime  }</td>
							
										<td class="textcenter">{pigcms{$vo.accountSetName }</td>
										
									</tr>
								</volist>
							
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
<script type="text/javascript" src="./static/layer/layer.js"></script>
<script>
	
	   var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Group/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
