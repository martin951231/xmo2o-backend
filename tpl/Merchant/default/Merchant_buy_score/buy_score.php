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
						<p style="text-align:center;font-size: 40px;color: #FFFFFF;padding-top: 20px;"><span style="font-size: 25px;">{pigcms{:L_("剩余平台积分数：")}</span> {pigcms{$mer_info.platform_score} <span style="font-size: 25px;"></span></p>
						<p style="text-align:center; padding-top: 20px;" class="my_money">
							<if condition="$config['merchant_buy_points_switch'] eq 1 && $config['merchant_buy_points'] gt 0">
                                <a class="bind_info red" href="{pigcms{:U('Merchant_buy_score/score_buy')}"> <span >{pigcms{:L_("购买平台积分")}</span> </a>
							<else/>
                                <a style="" href="javascript:void(0);" onclick="alert('{pigcms{:L_("暂不支持")}')"> <span style="color: #999;border: 1px solid #999;">{pigcms{:L_("购买平台积分")}</span> </a>
							</if>
							
							<a href="{pigcms{:U('Merchant_buy_score/buy_score',array('type'=>'buy'))}"><span >{pigcms{:L_("积分记录")}</span></a>
						</p>
					</div>


					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab" style="width:100%;">
                            <li <if condition="$_GET['type'] eq 'buy'">class="active"</if>>
                                <a href="{pigcms{:U('Merchant_buy_score/buy_score',array('type'=>'buy'))}">
                                    {pigcms{:L_("积分记录")}
                                </a>
                            </li>
<!--							<li <if condition="$_GET['type'] neq 'buy'">class="active"</if>>-->
<!--								<a href="{pigcms{:U('Merchant_buy_score/buy_score',array('type'=>'use'))}">-->
<!--									{pigcms{:L_("短信发送记录")}-->
<!--								</a>-->
<!--							</li>-->
						</ul>

							<div class="tab-pane active">
								<div style="margin-top:10px;margin-bottom: 10px;" >
									<form action="{pigcms{:U('Merchant_buy_score/buy_score')}" method="get">
										<input type="hidden" name="c" value="Merchant_buy_score"/>
										<input type="hidden" name="a" value="buy_score"/>
										<input type="hidden" name="type" value="buy"/>&nbsp;&nbsp;
										{pigcms{:L_("日期筛选：")}
										<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
										<input type="text" class="input-text" name="end_time" style="width:120px;margin-left: 5px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;
										{pigcms{:L_("状态")}:
                                        <select name="set_state">
                                            <option value="" <if condition="$_GET['set_state'] eq ''">selected="selected"</if>>{pigcms{:L_("请选择状态")}</option>
                                            <option value="1" <if condition="$_GET['set_state'] eq '1'">selected="selected"</if>>{pigcms{:L_("支付成功增加")}</option>
                                            <option value="2" <if condition="$_GET['set_state'] eq '2'">selected="selected"</if>>{pigcms{:L_("用户消费消耗")}</option>
                                            <option value="3" <if condition="$_GET['set_state'] eq '3'">selected="selected"</if>>{pigcms{:L_("管理员操作增加")}</option>
                                            <option value="4" <if condition="$_GET['set_state'] eq '4'">selected="selected"</if>>{pigcms{:L_("管理员操作减少")}</option>
                                        </select>
										<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　

										<a class="btn btn-success" style="padding:2px 14px;" href="{pigcms{:U('Merchant_buy_score/buy_score_export',$_GET)}">{pigcms{:L_("导出")}</a>

									</form>
                                    <div>
                                        {pigcms{:L_("支付成功增加总积分：")}<span style="color: #38CDBE;"><if condition="$sore_num['pay_score_add_num']">{pigcms{$sore_num['pay_score_add_num']}<else/>0</if>{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        {pigcms{:L_("用户消费消耗总积分：")}<span style="color: red;"><if condition="$sore_num['user_score_num']">{pigcms{$sore_num['user_score_num']}<else/>0</if>{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        {pigcms{:L_("管理员操作增加总积分：")}<span style="color: #38CDBE;"><if condition="$sore_num['admin_score_add_num']">{pigcms{$sore_num['admin_score_add_num']}<else/>0</if>{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        {pigcms{:L_("管理员操作减少总积分：")}<span style="color: red;"><if condition="$sore_num['admin_score_reduce_num']">{pigcms{$sore_num['admin_score_reduce_num']}<else/>0</if>{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>
								</div>

								<div class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
                                                <th>{pigcms{:L_("编号")}</th>
                                                <th>{pigcms{:L_("订单号")}</th>
                                                <th>{pigcms{:L_("支付金额")}</th>
                                                <th>{pigcms{:L_("购买")}/{pigcms{:L_("消耗积分数")}</th>
                                                <th>{pigcms{:L_("积分获得者")}</th>
                                                <th>{pigcms{:L_("添加时间")}</th>
                                                <th>{pigcms{:L_("支付时间")}</th>
                                                <th>{pigcms{:L_("操作方式")}</th>
                                                <th>{pigcms{:L_("操作类型")}</th>
                                                <th>{pigcms{:L_("状态")}</th>
                                                <th>{pigcms{:L_("备注")}</th>
											</tr>
										</thead>
										<tbody>
											<volist name="orderList" id="vo">
												<tr>
                                                    <td>{pigcms{$vo.order_id}</td>
                                                    <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.orderid}</if></td>
                                                    <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.payment_money}</if></td>
                                                    <td><if condition="$vo['set_type'] eq '1'"><span style="color: red;">- {pigcms{$vo.sore_count}</span><else/><span style="color: #38CDBE;">+ {pigcms{$vo.sore_count}</span></if></td>
                                                    <td>
                                                        <if condition="$vo['set_type'] eq '1' && $vo['operation_type'] eq '0'">
                                                            {pigcms{:L_("昵称：")} {pigcms{$vo.nickname}<br>
                                                            <else/>-</if>
                                                    </td>
                                                    <td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
                                                    <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if></td>
                                                    <td>

                                                        <if condition="$vo['operation_type'] eq '1'">
                                                            {pigcms{:L_("管理员操作")}
                                                            <elseif condition="$vo['business_type']"/>
                                                            {pigcms{:L_("用户消耗")}
                                                            <else/>
                                                            <if condition="$vo['pay_type'] eq 'yue'">{pigcms{:L_("在线充值")}<elseif condition="$vo['pay_type'] eq 'weixin'" />{pigcms{:L_("微信")}-{pigcms{:L_("在线充值")}<elseif condition="$vo['pay_type'] eq 'system'" />{pigcms{:L_("管理员操作")}</if>
                                                        </if>
                                                    </td>
                                                    <td><if condition="$vo['set_type'] eq '1'"><span style="color: red;">{pigcms{:L_("减少")}</span><else/><span style="color: #38CDBE;">{pigcms{:L_("增加")}</span></if></td>

                                                    <td>
                                                        <if condition="$vo['operation_type'] eq '1'">
                                                            {pigcms{:L_("管理员操作")}
                                                            <elseif condition="$vo['business_type']"/>
                                                            -
                                                            <else/>
                                                            <if condition="$vo['paid'] eq '0'">{pigcms{:L_("未支付")}<elseif condition="$vo['paid'] eq '1'" />{pigcms{:L_("支付成功")}<else/>{pigcms{:L_("管理员操作")}</if>
                                                        </if>
                                                    </td>
                                                    <td>{pigcms{$vo.remark}</td>
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
