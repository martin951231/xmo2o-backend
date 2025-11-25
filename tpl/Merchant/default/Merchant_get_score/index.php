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
						<p style="text-align:center;font-size: 40px;color: #FFFFFF;padding-top: 20px;"><span style="font-size: 25px;">{pigcms{:L_("剩余平台积分数：")}</span> {pigcms{$mer_info.system_score} <span style="font-size: 25px;"></span></p>
						<p style="text-align:center; padding-top: 20px;" class="my_money">
							
							<a href="{pigcms{:U('index')}"><span >{pigcms{:L_("积分记录")}</span></a>
							<a href="{pigcms{:U('Merchant_get_score/merchant_login')}"" target="_blank"><span >{pigcms{:L_("积分兑换礼品")}</span></a>
							<a href="{pigcms{:U('Merchant_get_score/gift_order')}"><span >{pigcms{:L_("积分商城订单")}</span></a>
						</p>
					</div>


					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab" style="width:100%;">
                            <li class="active">
                                <a href="{pigcms{:U('Merchant_get_score/index')}">
                                    {pigcms{:L_("积分记录")}
                                </a>
                            </li>
							<li>
								<a href="{pigcms{:U('Merchant_get_score/gift_order')}">
									{pigcms{:L_("积分商城订单")}
								</a>
							</li>
						</ul>

							<div class="tab-pane active">
								<div style="margin-top:10px;margin-bottom: 10px;" >
									<form action="{pigcms{:U('index')}" method="get">
										<input type="hidden" name="c" value="Merchant_get_score"/>
										<input type="hidden" name="a" value="index"/>
										{pigcms{:L_("日期筛选：")}
										<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
										<input type="text" class="input-text" name="end_time" style="width:120px;margin-left: 5px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;
										{pigcms{:L_("状态")}:
                                        <select name="type">
                                            <option value="">{pigcms{:L_("请选择类型")}</option>
                                            <option value="1" <if condition="$_GET['type'] eq '1'">selected="selected"</if>>{pigcms{:L_("增加")}</option>
                                            <option value="2" <if condition="$_GET['type'] eq '2'">selected="selected"</if>>{pigcms{:L_("消耗")}</option>
                                        </select>
										<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　

										<a class="btn btn-success" style="padding:2px 14px;" href="{pigcms{:U('score_export',$_GET)}">{pigcms{:L_("导出")}</a>

									</form>
                                    <div>
                                        {pigcms{:L_("获得总积分：")}<span style="color: #38CDBE;">{pigcms{$sore_num['score_get']}{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                       	{pigcms{:L_("消耗总积分：")}<span style="color: red;">{pigcms{$sore_num['score_use']}{pigcms{:L_("积分")}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>
								</div>

								<div class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
							                    <th>{pigcms{:L_("编号")}</th>
							                    <th>{pigcms{:L_("订单号")}</th>
							                    <th>{pigcms{:L_("商户名称")}</th>
							                    <th>{pigcms{:L_("类型")}</th>
							                    <th>{pigcms{:L_("积分数")}</th>
							                    <th>{pigcms{:L_("获得时间")}</th>
							                    <th>{pigcms{:L_("备注")}</th>
											</tr>
										</thead>
										<tbody>
											<volist name="score_list['list']" id="vo">
												<tr>
                                                    <td>{pigcms{$vo.id}</td>
						                            <td><if condition="$vo['order_id']">{pigcms{$vo.order_id}<else/>--</if></td>
						                            <td>{pigcms{$vo.name}</td>
                                                    <td><if condition="$vo['type'] eq '1'">{pigcms{:L_("增加")}<else/>{pigcms{:L_("消耗")}</if></td>
                            						<td><if condition="$vo['type'] eq '1'"><span style="color: #38CDBE;">+ <else/><span style="color: red;">- </if>{pigcms{$vo.score}</span></td>
                                                    <td>{pigcms{$vo.time|date="Y-m-d H:i:s",###}</td>
                                                    <td>{pigcms{$vo.desc}</td>
												</tr>
											</volist>
											
										</tbody>
									</table>
									{pigcms{$score_list.pagebar}
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
