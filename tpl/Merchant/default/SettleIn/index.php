<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('SettleIn/index')}">{pigcms{:L_("入驻缴费")}</a>
			</li>
			<li class="active">{pigcms{:L_("缴费管理")}</li>
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
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#recharge">{pigcms{:L_("充值")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#refund">{pigcms{:L_("退款")}</a>
							</li> 
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="recharge" class="tab-pane active">
								<div class="form-group">
									<span style="color: red;padding-left: 12px">您的金额为：￥{pigcms{$merchant_info.settle_in_money}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("金额：")}</label></label>
									<input class="col-sm-2" size="20" name="recharge_money" value="" id="invit_code" type="text"/> <span style="line-height:34px;padding-left:5px">元</span> 
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("备注：")}</label></label> 
										<textarea class="col-sm-2" rows="3" name="recharge_remark"></textarea>
								</div>
								<div class="clearfix form-actions">
									<div class="col-md-9">
										<button class="btn btn-info" name="type" value="recharge" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("提交")}
										</button>
									</div>
								</div>




								<div style="margin-top: 50px;">
									<table class="table table-striped table-bordered table-hover">
										<caption style="text-align: left;padding-bottom:5px;padding-left:1px">充值记录</caption>
										<thead>
											<tr>
												<th>{pigcms{:L_("ID")}</th>
												<th>{pigcms{:L_("金额")}</th>
												<th>{pigcms{:L_("类型")}</th>
												<th>{pigcms{:L_("支付方式")}</th>
												<th>{pigcms{:L_("充值时间")}</th>
												<th>{pigcms{:L_("状态")}</th>
												<th>{pigcms{:L_("备注")}</th>  
											</tr>
										</thead>
										<tbody>
											<if condition="$list['recharge']">
												<volist name="list.recharge" id="vo">
													<tr>
														<td>{pigcms{$vo.order_id}</td>
														<td>{pigcms{$vo.money}</td>
														<td>{pigcms{$vo.type_text}</td>
														<td>{pigcms{$vo.pay_type}</td>
														<td>{pigcms{$vo.recharge_time}</td>
														<td>{pigcms{$vo.status_text}</td>
														<td>{pigcms{$vo.remark}</td> 
													</tr>
												</volist>
											<else />
												<tr class="odd"><td class="button-column" colspan="7" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>
								</div>




                               
							</div>
							<div id="refund"  class="tab-pane">
								<div class="form-group">
									<span style="color: red;padding-left: 12px">您的金额为：￥{pigcms{$merchant_info.settle_in_money}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("金额：")}</label></label>
                                    <input class="col-sm-2" size="20" name="refund_money" value="" id="invit_code" type="text"/> <span style="line-height:34px;padding-left:5px">元</span> 
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("备注：")}</label></label> 
                                        <textarea class="col-sm-2" rows="3" name="refund_remark"></textarea>
                                </div>
								<div class="clearfix form-actions">
									<div class="col-md-9">
										<button class="btn btn-info" name="type" value="refund" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("提交")}
										</button>
									</div>
								</div>

								<div style="margin-top: 50px;">

									<table class="table table-striped table-bordered table-hover">
										<caption style="text-align: left;padding-bottom:5px;padding-left:1px">退款记录</caption>
										<thead>
											<tr>
												<th>{pigcms{:L_("ID")}</th>
												<th>{pigcms{:L_("金额")}</th>
												<th>{pigcms{:L_("类型")}</th> 
												<th>{pigcms{:L_("退款时间")}</th>
												<th>{pigcms{:L_("状态")}</th>
												<th>{pigcms{:L_("备注")}</th> 
												<th>{pigcms{:L_("审核理由")}</th> 
											</tr>
										</thead>
										<tbody>
											<if condition="$list['refund']">
												<volist name="list.refund" id="vo">
													<tr>
														<td>{pigcms{$vo.order_id}</td>
														<td>{pigcms{$vo.money}</td>
														<td>{pigcms{$vo.type_text}</td>
														<td>{pigcms{$vo.refund_time}</td>
														<td>{pigcms{$vo.status_text}</td>
														<td>{pigcms{$vo.remark}</td>
														<td>{pigcms{$vo.msg}</td>
													</tr>
												</volist>
											<else />
												<tr class="odd"><td class="button-column" colspan="7" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>


								</div>


							</div>
						 
							
						</div>
					</form>

 
				</div>
			</div>
		</div>
	</div>
</div>
 

 
<include file="Public:footer"/>