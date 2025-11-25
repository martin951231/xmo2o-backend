<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("派发优惠券")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab">
							<li >
								<a  href="{pigcms{:U('Card_new/send_coupon')}&tab=1">
									{pigcms{:L_("分组派发")}
								</a>
							</li>
							<li>
								<a  href="{pigcms{:U('Card_new/send_coupon')}&tab=2" >
									{pigcms{:L_("个人派发")}
								</a>
							</li>
							
							<li>
								<a href="{pigcms{:U('Card_new/send_all')}" >
									{pigcms{:L_("全部派发")}
								</a>
							</li>
							<li>
								<a href="{pigcms{:U('Card_new/weixin_send')}" >
									{pigcms{:L_("微信购买派发")}
								</a>
							</li>
							<li class="active">
								<a data-toggle="tab" href="{pigcms{:U('Card_new/send_history')}" >
									{pigcms{:L_("派发记录")}
								</a>
							</li>
						
						</ul>
						<div class="tab-content">
							<div id="groupinfo" class="tab-pane" style="display:block;">
								
								<div class="row">					
									<div class="col-xs-12">		
										<div class="grid-view">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th>ID</th>
														<th>{pigcms{:L_("优惠券名称")}</th>
														<th>{pigcms{:L_("派发时间")}</th>
														<th>{pigcms{:L_("派发对象")}</th>
														<th>{pigcms{:L_("派发对象手机号")}</th>
														<th>{pigcms{:L_("派发结果")}</th>
													</tr>
												</thead>
												<tbody>
												<if condition="$history">
													<volist name="history" id="vo">
														<tr class="<if condition='$i%2 eq 0'>even<else/>odd</if>">
															<td style="width: 120px">{pigcms{$vo.id}</td>
															<td style="width: 120px">{pigcms{$vo.coupon_name}</td>
															<td style="width: 120px">{pigcms{$vo.add_time|date='Y-m-d',###}</td>
															<td style="width: 120px">{pigcms{$vo.nickname}</td>
															<td style="width: 120px">{pigcms{$vo.phone|default='--'}</td>
															<td style="width: 120px"><if condition="$vo.error_code neq 0"><font color="red">{pigcms{:L_("派发失败")}</font>({pigcms{$vo.msg})<else /><font color="green">{pigcms{:L_("派发成功")}</font></if></td>
															
														</tr>
													</volist>
												<else />
													<tr class="odd"><td class="button-column" colspan="4" >{pigcms{:L_("无内容")}</td></tr>
												</if>
												</tbody>
											</table>
											{pigcms{$pagebar}
										</div>						
									</div>
									<!--div class="col-xs-2" style="margin-top: 15px;">
										<a class="btn btn-success" href="#">{pigcms{:L_("导出成")}excel</a>
									</div-->
								</div>
							</div>
							<style type="text/css">
								.radio {
								    min-height: 27px;
								}
							</style>
						
							
						</div>
					</div>	
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	

	$(function(){
		
	});
</script>


<include file="Public:footer"/>
