<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					
					<div style="margin-top:10px;width:100%;height:300px;background-color:#81d2cf">
                        <p style="text-align:left;font-family: 'Arial Normal', 'Arial';font-style: normal;color: #FFFFFF;padding: 10px;margin:0px">未结算金额：</p>
						<p style="text-align:center;font-family: 'Arial Normal', 'Arial';font-weight: 400;font-style: normal;font-size: 36px;color: #FFFFFF;padding-top: 20px;">{pigcms{$config['Currency_symbol']}{pigcms{$qiye.money|floatval} </p>
                        <p style="text-align:center;color: red">(已消费未结算金额：{pigcms{$config['Currency_symbol']}{pigcms{$qiye.unsettlement_money|floatval}，手续费比列：{pigcms{$qiye.settlement_ratio}%) </p>

						<p style="text-align:center;    padding-top: 36px;" class="my_money">
							<a href="{pigcms{:U('Analysis/applySettlement')}">
								<span >{pigcms{:L_("提交结算凭证")}</span>
							</a>
						
							<a href="{pigcms{:U('Analysis/settlement')}">
								<span >{pigcms{:L_("结算记录")}</span>
							</a>
						</p>

						<p style="text-align:center;padding-top: 20px;" class="my_money">
							<a href="{pigcms{:U('Analysis/trade')}">
								<span >{pigcms{:L_("消费数据报表")}</span>
							</a>
							<a href="{pigcms{:U('Analysis/orders')}">
                                <span >{pigcms{:L_("消费记录")}</span>
							</a>
							<a href="{pigcms{:U('Analysis/balance')}">
                                <span >{pigcms{:L_("余额操作记录")}</span>
                            </a>
						</p>								
						
					</div>
					<div style="margin-top:10px;">
						{pigcms{:L_("数据统计")}
					</div>
					<div class="tabbable" style="height:800px;margin-top:20px;">
                        <p style="text-align: center;font-size: 2rem">待开发...</p>
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
