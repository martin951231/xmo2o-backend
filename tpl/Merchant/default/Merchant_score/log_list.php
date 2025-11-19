<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active">{pigcms{:L_("我的平台积分")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					<div style="margin-top:10px;width:100%;height:160px;background-color:#81d2cf">
						<p style="text-align:center;font-size: 40px;color: #FFFFFF;padding-top: 20px;">
							<span style="font-size: 25px;">{pigcms{:L_("可用X1：",$config['score_name'])}</span> {pigcms{$merchant_session.merchant_score} 
							<span style="font-size: 25px;">{pigcms{:L_("锁定X1：",$config['score_name'])}</span> {pigcms{$lock_score} </span>
						</p>
						
					</div>
					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab" style="width:100%;">
							<li class="active">
								<a href="{pigcms{:U('log_list')}" title="积分获得记录">
									积分获得记录
								</a>
							</li>
							<li>
								<a href="{pigcms{:U('index')}" title="锁定积分">
									锁定积分
								</a>
							</li>
						</ul>

							<div class="tab-pane active">
								<div style="margin-top:10px;margin-bottom: 10px;" >
									<form action="{pigcms{:U('log_list')}" method="get">
										<input type="hidden" name="c" value="Merchant_score"/>
										<input type="hidden" name="a" value="log_list"/>
										{pigcms{:L_("日期筛选：")}
										<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
										<input type="text" class="input-text" name="end_time" style="width:120px;margin-left: 5px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;
										
										<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>
									</form>
								</div>

								<div class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
							                    <th>{pigcms{:L_("订单编号")}</th>
							                    <th>{pigcms{:L_("订单类型")}</th>
							                    <th>{pigcms{:L_("积分操作类型")}</th>
							                    <th>{pigcms{:L_("积分数量")}</th>
							                    <th>{pigcms{:L_("时间")}</th>
											</tr>
										</thead>
										<tbody>
											<volist name="list" id="vo">
												<tr>
                                                    <td>{pigcms{$vo.real_orderid}</td>
                                                    <td>{pigcms{$vo.order_type_txt}</td>
                                                    <td>
                                                    	<if condition="$vo['set_type'] eq 1">
                                                    		减少
                                                    	<else/>
                                                    		增加
                                                    	</if>
                                                    </td>
                                                    <td>{pigcms{$vo.score}</td>
                                                    <td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
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
		</div>
	</div>
</div>
<style>
	.my_money span{
		padding: 9px 42px;
		border: 1px solid #fff;
		margin-left: 10px;
		color:#fff;
		border-radius:1px;
	}
	.my_money a:hover {text-decoration:none;}
	.widget-header {
		height: 100px;
	}
	.widget-header div{
		text-align:center;
		float:left;
		height:100%;
	}
	.widget-header p{
		text-align:center;
	}
	.h_title{
		margin-top:10px;
		font-weight: 400;
		font-style: normal;
		font-size: 14px;
		color:#000;
	}
	
	.h_value{
		font-family: 'Arial Negreta', 'Arial';
		font-weight: 700;
		font-style: normal;
		font-size: 28px;
		color:#000;
	}
	.tab-content a.on {
		background: #498CD0;
		color: #FFF;
		padding: 4px 7px;
		text-decoration: none;
	}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
