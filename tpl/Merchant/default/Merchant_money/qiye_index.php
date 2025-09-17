<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active">{pigcms{:L_("商家余额")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					
					<div style="margin-top:10px;width:100%;height:240px;background-color:#81d2cf">
						<p style="text-align:center;font-family: 'Arial Normal', 'Arial';font-weight: 400;font-style: normal;font-size: 36px;color: #FFFFFF;padding-top: 36px;">{pigcms{$config['Currency_symbol']}{pigcms{$merchant.qiye_money|getFormatNumber}

                        <?php if ($config['open_multilingual']!=1): ?>
						<p style="text-align:center;    padding-top: 36px;" class="my_money">
							<a href="{pigcms{:U('Merchant_money/qiye_cashout')}">
								<span >{pigcms{:L_("申请提现")}</span>
							</a>
						
							<a href="{pigcms{:U('Merchant_money/qiye_cashout_list')}">
								<span >{pigcms{:L_("提现记录")}</span>
							</a>
						</p>
                        <?php endif ?>
							
						<p style="text-align:center;padding-top: 20px;" class="my_money">
							<a href="{pigcms{:U('Merchant_money/qiye_income_list')}">
								<span >{pigcms{:L_("收入记录")}</span>
							</a>
						</p>								
						
					</div>
					<div style="margin-top:10px;display: none">
						{pigcms{:L_("数据统计")}
					</div>
					<div class="tabbable" style="margin-top:20px;display: none">
						<ul class="nav nav-tabs" id="myTab" style="width:100%;">
							<if condition="$config['shop_alias_name']">
								<li  <if condition="$_GET['type'] eq 'shop'">class="active"</if>>
									<a data-toggle="tab" href="#shopinfo" title="{pigcms{$config.shop_alias_name}">
										{pigcms{$config.shop_alias_name}
									</a>
								</li>
							</if>						
							<if condition="$config['is_cashier'] OR $config['pay_in_store']">
								<li  <if condition="$_GET['type'] eq 'store'">class="active"</if>>
									<a data-toggle="tab" href="#storeinfo" title="{pigcms{:L_("到店")}">
										{pigcms{:L_("到店")}
									</a>
								</li>
							</if>							
						</ul>
						
						<div class="tab-content" style="width:100%">
							<div style="margin-top:10px;margin-bottom: 10px;" >
								<a href="javascript:void(0)" onclick="location_count(1)" <if condition="$day eq 1">class="on"</if>>{pigcms{:L_("今天")}</a>&nbsp;&nbsp;
								<a href="javascript:void(0)" onclick="location_count(2)" <if condition="$day eq 2">class="on"</if>>{pigcms{:L_("本月统计")}</a>&nbsp;&nbsp;
								<a href="javascript:void(0)" onclick="location_count(7)" <if condition="$day eq 7">class="on"</if>>{pigcms{:L_("最近一周")}</a>&nbsp;&nbsp;
								<a href="javascript:void(0)" onclick="location_count(30)" <if condition="$day eq 30">class="on"</if>>{pigcms{:L_("最近三十天")}</a>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<form id="frmselect" name="frmselect" method="get" action="" style="margin-bottom:0;margin-left:13px;display:inline;">
									{pigcms{:L_("时间筛选 ：")}
									<input type="text" class="input fl" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
									<input type="text" class="input fl" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
									<if condition="$type neq 'wxapp'">
										&nbsp;&nbsp;&nbsp;{pigcms{:L_("店铺：")}<select id="store_id" name="store_id" onchange="javascript:frmselect.submit()">
											<option value="">{pigcms{:L_("所有店铺")}</option>
											<volist name="store_list" id="vo">
												<option value="{pigcms{$vo.store_id}" <if condition="$_GET['store_id'] eq $vo['store_id']" >selected="selected"</if>>{pigcms{$vo.name}</option>
											</volist>
										</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									</if>
									<input type="submit" value="{pigcms{:L_("筛选")}">
									<input type="hidden" name="g" value="Merchant"/>
									<input type="hidden" name="c" value="Merchant_money"/>
									<input type="hidden" name="a" value="index"/>
									<input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
									<input type="hidden" name="day" value="{pigcms{$day}">
									<input type="hidden" name="type" value="{pigcms{$type}">
									<input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
									
								</form>	
							</div>
							<!--<div id="basicinfo" class="tab-pane active">
								<div class="widget-box">
									<div class="widget-header">
										<h5>{pigcms{:L_("统计图")}</h5>
									</div>
									<div class="widget-body" id="main" style="padding:20px;height:400px;width:100%;"></div>
								</div>
							</div>-->
							<div id="groupinfo" class="tab-pane <if condition="$_GET['type'] eq 'group' OR empty($_GET['type'])">active</if>" style="display:block;" >
								<div class="widget-box" >
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.group_order_count_all gt 0">{pigcms{$pigcms_list.group_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.group_income_all gt 0">{pigcms{:number_format($pigcms_list['group_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									
									</div>
									<div class="widget-body" id="group_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="group_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
							</div>
							<div id="mealinfo" class="tab-pane <if condition="$_GET['type'] eq 'meal'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.meal_order_count_all gt 0">{pigcms{$pigcms_list.meal_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.meal_income_all gt 0">{pigcms{:number_format($pigcms_list['meal_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="meal_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="meal_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
							</div>
							<div id="shopinfo" class="tab-pane <if condition="$_GET['type'] eq 'shop'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.shop_order_count_all gt 0">{pigcms{$pigcms_list.shop_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.shop_income_all gt 0">{pigcms{:number_format($pigcms_list['shop_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="shop_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="shop_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
								
							</div>
							<div id="appointinfo" class="tab-pane <if condition="$_GET['type'] eq 'appoint'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.appoint_order_count_all gt 0">{pigcms{$pigcms_list.appoint_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.appoint_income_all gt 0">{pigcms{:number_format($pigcms_list['appoint_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="appoint_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="appoint_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
								
							</div>
							<div id="waimaiinfo" class="tab-pane <if condition="$_GET['type'] eq 'waimai'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.waimai_order_count_all gt 0">{pigcms{$pigcms_list.waimai_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.waimai_income_all gt 0">{pigcms{:number_format($pigcms_list['waimai_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="waimai_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="waimai_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
								
							</div>
							<div id="storeinfo" class="tab-pane <if condition="$_GET['type'] eq 'store'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.store_order_count_all gt 0">{pigcms{$pigcms_list.store_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.store_income_all gt 0">{pigcms{:number_format($pigcms_list['store_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="store_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="store_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
								
							</div>
							<div id="weidianinfo" class="tab-pane <if condition="$_GET['type'] eq 'weidian'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.weidian_order_count_all gt 0">{pigcms{$pigcms_list.weidian_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.weidian_income_all gt 0">{pigcms{:number_format($pigcms_list['weidian_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
										
									</div>
									<div class="widget-body" id="weidian_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="weidian_count" style="padding:20px;height:400px;width:100%;"></div>
								</div>	
								
							</div>
							<div id="wxappinfo" class="tab-pane <if condition="$_GET['type'] eq 'wxapp'">active</if>" style="display:block;">
								<div class="widget-box">
									<div class="widget-header">
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总数")}</p>
											<p class="h_value"><if condition="$pigcms_list.wxapp_order_count_all gt 0">{pigcms{$pigcms_list.wxapp_order_count_all}<else />0</if></p>
										</div>
										<div style="width:50%;">
											<p class="h_title">{pigcms{:L_("订单总额")}</p>
											<p class="h_value">{pigcms{$config['Currency_symbol']}<if condition="$pigcms_list.wxapp_income_all gt 0">{pigcms{:number_format($pigcms_list['wxapp_income_all'],2,'.','')}<else />0.00</if></p>
										</div>
									
									</div>
									<div class="widget-body" id="wxapp_main" style="padding:20px;height:400px;width:100%;"></div>
									<div class="widget-body" id="wxapp_count" style="padding:20px;height:400px;width:100%;"></div>
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
<script src="{pigcms{$static_path}js/echarts-plain.js"></script>
<script type="text/javascript">
	$(function(){
		$('#myTab li').click(function(){
			var x = $(this).children('a').attr('href');
			$('input[name="type"]').val(x.match(/#(\w+)info/)[1]);
			$('#frmselect').submit();
		});
		// if(!$('#myTab li').eq(0).hasClass('active')){
		// 	$('#myTab li').eq(0).trigger('click');
		// }
	})
	function location_count(day){
		var type = $('.active').children('a').attr('href');
		type = type.match(/#(\w+)info/)[1];
		window.location.href = "{pigcms{:U('Merchant_money/index')}&day="+day+'&type='+type;
	}
	
	<volist name="alias_name" id="vo">
		<if condition="$pigcms_list['xAxis_txt'] neq '' AND ($vo eq $_GET['type'] OR $_GET['type'] eq '')">
		var {pigcms{$vo}_myChart = echarts.init(document.getElementById('{pigcms{$vo}_main')); 
		var {pigcms{$vo}_myChart2 = echarts.init(document.getElementById('{pigcms{$vo}_count')); 
		var {pigcms{$vo}_option = {
			title : {
				text: '<?php echo $config[$vo.'_alias_name'];?>{pigcms{:L_("相关收入统计图")}',
				x:'left'
			},
			tooltip : {
				trigger: 'axis'
			},
			legend: {
				data:['{pigcms{:L_("总收入")}'],
				x: 'right'
			},
			toolbox: {
				show : false,
				feature : {
					mark : {show: false},
					dataView : {show: false, readOnly: false},
					magicType : {show: true, type: ['line', 'bar', 'stack', 'tiled']},
					restore : {show: false} ,
					saveAsImage : {show: true}
				}
			},
			calculable : true,
			xAxis : [
				{
					type : 'category',
					boundaryGap : false,
					data : [{pigcms{$pigcms_list.xAxis_txt}]
				}
			],
			yAxis : [
				{
					type : 'value'
				}
			],
			series : [
				{
					name:'{pigcms{:L_("总收入")}',
					type:'line',
					tiled: '{pigcms{:L_("总量")}',
					itemStyle : {
						normal : {
							color:'#ff7f50',
							label : {
								show : true,
								formatter : '{c}',
								position : 'top',
								textStyle : {
									fontWeight : '700',
									fontSize : '12',
									color:'#ff7f50'
								}
							},
							lineStyle:{
								color:'#ff7f50'
							}
						}
					},
					data: [<?php echo $pigcms_list[$vo]['income_txt'];?>]
				}
			
				

			]

		};
		var {pigcms{$vo}_option2 = {
			title : {
				text: '<?php echo $config[$vo.'_alias_name'];?>{pigcms{:L_("相关订单数统计图")}',
				x:'left'
			},
			tooltip : {
				trigger: 'axis'
			},
			legend: {
				data:['{pigcms{:L_("订单总数")}'],
				x: 'right'
			},
			toolbox: {
				show : false,
				feature : {
					mark : {show: false},
					dataView : {show: false, readOnly: false},
					magicType : {show: true, type: ['line', 'bar', 'stack', 'tiled']},
					restore : {show: false} ,
					saveAsImage : {show: true}
				}
			},
			calculable : true,
			xAxis : [
				{
					type : 'category',
					boundaryGap : false,
					data : [{pigcms{$pigcms_list.xAxis_txt}]
				}
			],
			yAxis : [
				{
					type : 'value'
				}
			],
			series : [
				
				{
					name:'{pigcms{:L_("订单总数")}',
					type:'line',
					tiled: '{pigcms{:L_("总量")}',
					itemStyle : {
						normal : {
							color:'#2e7ee4',
							label : {
								show : true,
								formatter : '{c}',
								position : 'top',
								textStyle : {
									fontWeight : '700',
									fontSize : '12',
									color:'#2e7ee4'
								}
							},
							lineStyle:{
								color:'#2e7ee4'
							}
						}
					},
					data:[<?php echo $pigcms_list[$vo]['order_count_txt'];?>]
				}

			]

		};                 
		{pigcms{$vo}_myChart.setOption({pigcms{$vo}_option); 
		{pigcms{$vo}_myChart2.setOption({pigcms{$vo}_option2); 
		<else />
			$('#{pigcms{$vo}_main').css({'line-height':'200px','text-align':'center','font-size':'36px'}).html('{pigcms{:L_("数据加载中")}......');
		</if>
	</volist>

	
	
	$('.tab-pane').removeAttr('style');
</script>
<include file="Public:footer"/>
