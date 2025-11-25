<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active">{pigcms{:L_("积分商城订单")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					<div style="margin-top:10px;width:100%;height:160px;background-color:#81d2cf">
						<p style="text-align:center;font-size: 40px;color: #FFFFFF;padding-top: 20px;"><span style="font-size: 25px;">{pigcms{:L_("剩余平台积分数：")}</span> {pigcms{$mer_info.system_score} <span style="font-size: 25px;"></span></p>
						<p style="text-align:center; padding-top: 20px;" class="my_money">
							
							<a href="{pigcms{:U('index')}"><span >{pigcms{:L_("积分记录")}</span></a>
							<a href="{pigcms{:U('Merchant_get_score/merchant_login')}"" target="_blank"><span >{pigcms{:L_("积分兑换礼品")}</span></a>
							<a href="{pigcms{:U('Merchant_get_score/gift_order')}"><span >{pigcms{:L_("积分商城订单")}</span></a>
						</p>
					</div>


					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab" style="width:100%;">
                            <li <if condition="$_GET['type'] eq 'buy'">class="active"</if>>
                                <a href="{pigcms{:U('Merchant_get_score/index')}">
                                    {pigcms{:L_("积分记录")}
                                </a>
                            </li>
							<li class="active">
								<a href="{pigcms{:U('Merchant_get_score/gift_order')}">
									{pigcms{:L_("积分商城订单")}
								</a>
							</li>
						</ul>
							<div class="tab-pane active">
								<div style="margin-top:10px;margin-bottom: 10px;" >
									<form action="{pigcms{:U('index')}" method="get">
										<input type="hidden" name="c" value="Merchant_get_score"/>
										<input type="hidden" name="a" value="gift_order"/>
										<!-- {pigcms{:L_("日期筛选：")}
										<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
										<input type="text" class="input-text" name="end_time" style="width:120px;margin-left: 5px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp; -->
										<!-- {pigcms{:L_("状态")}:
                                        <select name="status">
                                            <option value="">{pigcms{:L_("请选择类型")}</option>
                                            <option value="-1" <if condition="$_GET['status'] eq '-1'">selected="selected"</if>>{pigcms{:L_("未支付")}</option>
                                            <option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>{pigcms{:L_("已支付")}</option>
                                        </select>
										<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　 -->

										<a class="btn btn-success" style="padding:2px 14px;" href="{pigcms{:U('gift_order_export',$_GET)}">{pigcms{:L_("导出")}</a>

									</form>
								</div>

								<div class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
							                    <th>{pigcms{:L_("订单编号")}</th>
							                    <th>{pigcms{:L_("商品名称")}</th>
							                    <th>{pigcms{:L_("数量")}</th>
							                    <th>{pigcms{:L_("总价")}</th>
							                    <th>{pigcms{:L_("订单状态")}</th>
							                    <th>{pigcms{:L_("下单时间")}</th>
							                    <!-- <th>{pigcms{:L_("操作")}</th> -->
											</tr>
										</thead>
										<tbody>
											<volist name="list['order_list']" id="vo">
												<tr>
                                                    <td>{pigcms{$vo.order_id}</td>
						                            <td>{pigcms{$vo.order_name}</td>
						                            <td>{pigcms{$vo.num}</td>
						                            <td>
						                            	{pigcms{$config['score_name']}：{pigcms{$vo.total_integral}
						                            	<if condition='$vo["exchange_type"] eq 1'>
														<br />
															{pigcms{:L_("金额：")}<span class="money">¥</span>{pigcms{$vo.total_price}
														</if>
						                            </td>
                                                    <td><if condition="$vo['paid'] eq '1'">{pigcms{:L_("已支付")}<else/>{pigcms{:L_("未支付")}</if></td>
                                                    <td>{pigcms{$vo.order_time|date="Y-m-d H:i:s",###}</td>
												</tr>
											</volist>
											
										</tbody>
									</table>
									{pigcms{$list.pagebar}
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
<script>
	$('.bind_info').click(function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("购买平台积分")}',
			padding: 0,
			width: 800,
			height: 603,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});
</script>


<include file="Public:footer"/>
