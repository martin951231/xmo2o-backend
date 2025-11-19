 var business_type,order_type,order_type,type,store_list,start_time,end_time,data_type,is_add_up
 var myDate = new Date()
 var getDate=parseInt(myDate.getDate())
 var getMonth=parseInt(myDate.getMonth())+1
 var getFullYear=parseInt(myDate.getFullYear()) 
 var result = $('#startResult')[0];  
 var result2 = $('#endResult')[0]; 
 var config = common.getCache('config',true);
 if(getDate<10){
 	getDate='0'+myDate.getDate()
 }
 if(getMonth<10){
 	getMonth='0'+getMonth
 }
 
$(document).ready(function(){  
	business_type = urlParam.tabType
	order_type = 'shop'
	
	$('.tab_fen span').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur')
	}) 
	
	$('.ul_proSelect li').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur')
	}) 
  
	
	$(document).on('click',".ul_selectStore li",function(e){ 
	    $(this).toggleClass('cur');
	})
	
	
	$('.tab_fen a').each(function(index,element){ 
	  if($(this).hasClass('cur')){  
		  business_type = $(this).data('type')
		  // order_type = $(this).data('type') 
	  }
	})
	
	$('.tab_conEchartsHistory li').each(function(index,element){  
	  if($(this).hasClass('cur')){  
		  type = $(this).data('type') 
	  }
	})
	
	$('.tab_conEchartsHistory li').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur')
		if($(this).data('type') == 4){
			$('.select_time').show();
			initTimePicker();
			start_time = getFullYear+'-'+getMonth+'-'+ getDate 
			end_time = getFullYear+'-'+getMonth+'-'+ getDate 
			result.innerText = start_time
			result2.innerText = end_time
		}else{
			$('.select_time').hide();
		}
		type = $(this).data('type')
		initHistory()
	})
	
	// 图表 
	$('.tab_conEchartChange li').each(function(index,element){
	  if($(this).hasClass('cur')){  
		  data_type = $(this).data('data_type') 
		   
	  }
	}) 
	
	$('.ul_changeFen span').each(function(index,element){
	  if($(this).hasClass('cur')){  
		  is_add_up = $(this).data('is_add_up')  
	  }
	}) 
	
	$('.tab_conEchartChange li').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur');
		data_type=$(this).data('data_type');
		todayChart()
	})
	
	$('.ul_changeFen span').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur');
		is_add_up=$(this).data('is_add_up')
		todayChart()
	})
	 
	if(urlParam.time =='today'){  
		initToday(); 
		todayChart();
	}else if(urlParam.time =='history'){  
		initHistory()
	}else{
		initToday();
		initHistory();
		todayChart();
	} 
})  
  
  //  今日数据渲染
  function initToday(){
	  common.http('Merchantapp&a=statisticsToday',{'business_type':business_type,'order_type':order_type,'store_list':store_list},function(data){ 
		var getTodayData = document.getElementById('todayData').innerHTML; 
		laytpl(getTodayData).render(data, function (html) {   
		  $('.todayData').html(html); 
		  $('.todayData').append('<div class="clear"></div>');
		}); 
		$('.Currency_symbol').html(config.Currency_symbol)  
		
	  });
  }
  //  历史数据渲染
  function initHistory(){
	  var param={
		  'business_type':business_type,
		  'order_type':order_type,
		  'store_list':store_list,
		  'type':type,
		  'start_time':start_time,
		  'end_time':end_time
	  }
	  common.http('Merchantapp&a=statisticsHistory',param,function(data){ 
		var getHistoryData = document.getElementById('historyData').innerHTML;
		laytpl(getHistoryData).render(data, function (html) {  
		  $('.historyData').html(html);
		  $('.historyData').append('<div class="clear"></div>');
		});
		$('.Currency_symbol').html(config.Currency_symbol)  
	  });
  }
  // 获取店铺
  getStoreList()
  var storeArr=''
  function getStoreList(){
	common.http('Merchantapp&a=store_list',{'is_all':1},function(data){  
		storeArr = data.data
		var html=''
		storeArr.forEach(function(element){ 
			html+='<li class="mui-table-view-cell cur" data-store_id="'+element.store_id+'">'+element.name+'</li>'
		})
		$('.ul_selectStore').html(html)  
	});  
  } 
  // 图表 
  var todayArr,yesterdayArr
  function todayChart(){
  	  var paramChart={  	
		  'business_type':business_type,
		  'order_type':order_type,
		  'data_type':data_type,
		  'is_add_up':is_add_up,
		  'store_list':store_list 
  	  }
  	  common.http('Merchantapp&a=statisticsTodayChart',paramChart,function(data){   
		todayArr = data.todayArr
		yesterdayArr = data.yesterdayArr
		initChart()
  	  });
  }
  
 function initChart(){ 
	var dom = document.getElementById("container");
	var myChart = echarts.init(dom);
	var app = {};
	option = null;
	option = { 
		tooltip: {
			trigger: 'axis',
			axisPointer: {
				type: 'cross',
				label: {
					backgroundColor: '#ffb128'
				}
			},
			formatter: function (params, ticket, callback) {
			  var htmlStr = '';
			  for(var i=0;i<params.length;i++){
				var param = params[i];
				var xName = param.name;//x轴的名称
				var seriesName = param.seriesName;//图例名称
				var value = param.value;//y轴值
				var color = param.color;//图例颜色
				
				if(i===0){
					if(is_add_up == 1){
						htmlStr +=parseInt(xName)+":00-"+ ( parseInt(xName)+parseInt(1))+':59' + '<br/>';//x轴的名称
					}else{
						htmlStr +="00:00-"+ ( parseInt(xName)+parseInt(1))+':59' + '<br/>';//x轴的名称
					}
				  
				}
				htmlStr +='<div>';
				//为了保证和原来的效果一样，这里自己实现了一个点的效果
				htmlStr += '<span style="margin-right:5px;display:inline-block;width:10px;height:10px;border-radius:5px;background-color:'+color+';"></span>';
				
				//圆点后面显示的文本
				htmlStr += seriesName + '：'+ value + `${data_type == 0?'元':'单'}`;
				
				htmlStr += '</div>';
			  }
			  return htmlStr;
			 }
			  
		},
		legend: {
			data: ['今日', '昨日']
		},
		toolbox: {
			feature: {
				saveAsImage: {}
			}
		},
		grid: {
			left: '3%',
			right: '4%',
			bottom: '3%',
			containLabel: true
		},
		xAxis: [
			{
				type: 'category',
				boundaryGap: false, 
				data: ['0', '2', '4', '6', '8', '10', '12','14','16','18','20','22'], 
				 //设置坐标轴字体颜色和宽度
				 // axisLine: {
					//  lineStyle: {
					// 	 color: "#fff",
					//  }
				 // } 
			},
			// axisLine: {
			//   show: false, //是否显示y轴
			//   lineStyle: {
			// 	color: '#000', // y坐标轴的轴线颜色
			// 	width: 1, //这里是坐标轴的宽度,可以去掉
			//   }
			// } 
			
		],
		yAxis: [
			// { 
			// 	axisLabel : { 
			// 		formatter: function(){ 
			// 			return ""; 
			// 		} 
			// 	},
			// 	 axisLine: {
			// 		 lineStyle: {
			// 			 color: "#fff",
			// 		 }
			// 	 }

			// },
			{
				splitLine:{
					show:false,  //去掉背景的网格线
				},
				show:true,  //隐藏坐标轴及刻度
				axisLabel: {
					formatter: function(){
						return ""; 
					} 
				},
				axisLine:{
					show:true, //隐藏坐标轴
					lineStyle:{
						color:'#fff',  //坐标轴的颜色
					},
				},
				axisTick:{
					show:false, //隐藏坐标轴的刻度
					}
			} 
			 
		],
		series: [
			{
				name: '今日',
				type: 'line',
				stack: '总量', 
				symbolSize:0,
				itemStyle: {
					normal: {
						lineStyle: {
							width:2, //调整 线条的宽度  5已经很宽啦
							color : '#b2b6c4' ,//线条颜色 
							type:'dotted'
						}
					}
				},
				areaStyle: {
					 color: new echarts.graphic.LinearGradient(0, 0, 0, 0.2, [{
						 offset: 0,
						 color: '#fff'
					 }, {
						 offset: 1,
						 color: '#fff'
					 }])
				 },
				data: todayArr
			},
			{
				name: '昨日',
				type: 'line',
				stack: '总量',
				symbolSize:0, 
				itemStyle: {
					normal: {
						lineStyle: {
							width:1, //调整 线条的宽度  5已经很宽啦
							color : '#ffa637' ,//线条颜色 
						}
					}
				},
				areaStyle: {
					 color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
						 offset: 0,
						 color: '#ffa637'
					 }, {
						 offset: 1,
						 color: '#ffe'
					 }])
				 },
				data: yesterdayArr
			}
			 
		]
	}; 
	if (option && typeof option === "object") {
		myChart.setOption(option, true);
	} 
 }
 
  mui('body').on('tap', '.mui-popover-action li>a', function() { 
  	var a = this,
  		parent;
  	//根据点击按钮，反推当前是哪个actionsheet
  	for (parent = a.parentNode; parent != document.body; parent = parent.parentNode) {
  		if (parent.classList.contains('mui-popover-action')) {
  			break;
  		}
  	}
	
	var store_listArr=[]
	$('.ul_selectStore li').each(function(element){   
		if($(this).hasClass('cur')){ 
			store_listArr.push($(this).data('store_id')) 
		} 
	}) 
	store_list = store_listArr.join(',')
	console.log(store_list)   
	if(storeArr.length > store_listArr.length && store_listArr.length !=0){
		$('.top_xialaSelect a').html('指定店铺') 
	}else{
		$('.top_xialaSelect a').html('全部店铺')
	} 
	
	if(urlParam.time =='today'){ 
		initToday(business_type,order_type,store_list)
	}else if(urlParam.time =='history'){ 
		initHistory(business_type,order_type,type,start_time,end_time)
	}  
  	mui('#' + parent.id).popover('toggle');
  	 
  }) 
  
  function initTimePicker(){  
	 result.innerText=getFullYear+'-'+getMonth+'-'+ getDate
	 var btns = $('#startTime');  
	 btns.each(function(i, btn) {
	 	btn.addEventListener('tap', function() {
	 		var _self = this;
	 		if(_self.picker) {
	 			_self.picker.show(function (rs) {
	 				result.innerText =   rs.text;
	 				_self.picker.dispose();
	 				_self.picker = null;
	 			});
	 		} else {
	 			 
	 			var optionsJson = this.getAttribute('data-options') || '{}';
	 			var options = JSON.parse(optionsJson);
	 			var id = this.getAttribute('id');
	 			/*
	 			 * 首次显示时实例化组件
	 			 * 示例为了简洁，将 options 放在了按钮的 dom 上
	 			 * 也可以直接通过代码声明 optinos 用于实例化 DtPicker
	 			 */
	 			_self.picker = new mui.DtPicker(options);
	 			_self.picker.show(function(rs) {
	 				/*
	 				 * rs.value 拼合后的 value
	 				 * rs.text 拼合后的 text
	 				 * rs.y 年，可以通过 rs.y.vaue 和 rs.y.text 获取值和文本
	 				 * rs.m 月，用法同年
	 				 * rs.d 日，用法同年
	 				 * rs.h 时，用法同年
	 				 * rs.i 分（minutes 的第二个字母），用法同年
	 				 */
                    let now = (new Date()).getTime()
                    let selectValue = (new Date(rs.value)).getTime()
	 				if(selectValue > now){
	 					mui.toast('查询时间不能大于今天')  
	 				}else{
                        let startValue = selectValue
                        let endValue = (new Date(end_time )).getTime() 
                        if(startValue > endValue) {
                            mui.toast('查询开始时间不能大于结束时间')  
                        } else {
                            result.innerText = rs.text;
                            start_time=rs.text
                            initHistory(business_type,order_type,type,start_time,end_time)
                        }
	 				} 
	 				/* 
	 				 * 返回 false 可以阻止选择框的关闭
	 				 * return false;
	 				 */
	 				/*
	 				 * 释放组件资源，释放后将将不能再操作组件
	 				 * 通常情况下，不需要示放组件，new DtPicker(options) 后，可以一直使用。
	 				 * 当前示例，因为内容较多，如不进行资原释放，在某些设备上会较慢。
	 				 * 所以每次用完便立即调用 dispose 进行释放，下次用时再创建新实例。
	 				 */
	 				_self.picker.dispose();
	 				_self.picker = null;
	 			});
	 		}
	 		
	 	}, false);
	 });
	   
	 result2.innerText=getFullYear+'-'+getMonth+'-'+ getDate 
	 var btns2 = $('#endTime');
	 btns2.each(function(i, btn) {
	 	btn.addEventListener('tap', function() {
	 		var _self = this;
	 		if(_self.picker) {
	 			_self.picker.show(function (rs) {
	 				result2.innerText =   rs.text;
	 				_self.picker.dispose();
	 				_self.picker = null;
	 			});
	 		} else {
	 			var optionsJson = this.getAttribute('data-options') || '{}';
	 			var options = JSON.parse(optionsJson);
	 			var id = this.getAttribute('id');
	 			/*
	 			 * 首次显示时实例化组件
	 			 * 示例为了简洁，将 options 放在了按钮的 dom 上
	 			 * 也可以直接通过代码声明 optinos 用于实例化 DtPicker
	 			 */
	 			_self.picker = new mui.DtPicker(options);
	 			_self.picker.show(function(rs) {
	 				/*
	 				 * rs.value 拼合后的 value
	 				 * rs.text 拼合后的 text
	 				 * rs.y 年，可以通过 rs.y.vaue 和 rs.y.text 获取值和文本
	 				 * rs.m 月，用法同年
	 				 * rs.d 日，用法同年
	 				 * rs.h 时，用法同年
	 				 * rs.i 分（minutes 的第二个字母），用法同年
	 				 */
	 				let now = (new Date()).getTime()
                    let selectValue = (new Date(rs.value)).getTime()
	 				if(selectValue > now){
	 					mui.toast('查询时间不能大于今天')  
	 				}else{ 
                        let startValue = (new Date(start_time)).getTime()
                        let endValue = selectValue
                        if(startValue > endValue) {
                            mui.toast('查询开始时间不能大于结束时间')  
                        } else {
                            result2.innerText = rs.text;
                            end_time=rs.text
                            initHistory(business_type,order_type,type,start_time,end_time)
                        }
	 				} 
	 				
	 				/* 
	 				 * 返回 false 可以阻止选择框的关闭
	 				 * return false;
	 				 */
	 				/*
	 				 * 释放组件资源，释放后将将不能再操作组件
	 				 * 通常情况下，不需要示放组件，new DtPicker(options) 后，可以一直使用。
	 				 * 当前示例，因为内容较多，如不进行资原释放，在某些设备上会较慢。
	 				 * 所以每次用完便立即调用 dispose 进行释放，下次用时再创建新实例。
	 				 */
	 				_self.picker.dispose();
	 				_self.picker = null;
	 			});
	 		}
	 		
	 	}, false);
	 }); 
  } 
  // 外卖实时看板 营业额
  mui('.mui-content').on('tap','#shop_tan_yye',function(e){ 
  	mui.alert('<p class="tanPtil">'+L_('统计时间内流水总额，包含：')+'</p><ul class="ul_tanUrl"><li>'+L_('商品原价')+'</li><li>'+L_('打包费')+'</li><li>'+L_('配送费/快递费')+'</li></ul>','',
  	function(e){  
  	});  
  }); 
  // 外卖实时看板  订单收入
  mui('.mui-content').on('tap','#shop_tan_ddsr',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内净收入，包含自有支付金额；扣除：')+'</p><ul class="ul_tanUrl"><li>'+L_('平台抽成&平台配送费')+'</li><li>'+L_('平台满减补贴')+'</li><li>'+L_('商家满减补贴')+'</li><li>'+L_('申请售后退款金额')+'</li></ul>','',
  	function(e){  
  	});  
  });
  
   // 外卖实时看板  有效订单
   mui('.mui-content').on('tap','#shop_tan_yxdd',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消。')+' </p> ','',
  	function(e){  
  	});  
   });
   
   // 外卖实时看板  无效订单
   mui('.mui-content').on('tap','#shop_tan_wxdd',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。')+' </p> ','',
  	function(e){  
  	});  
   }); 
   
   // 外卖实时看板  活动补贴 
   mui('.mui-content').on('tap','#shop_tan_hdbt',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内平台/商家满减优惠与平台/商家优惠券补贴总额，包含：')+' </p> <ul class="ul_tanUrl"><li>'+L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')+'</li><li>'+L_('平台满减优惠商家补贴金额部分，含满减优惠的所有类型?商家自有满减优惠商家补贴金额部分')+'</li><li>'+L_('平台优惠券平台补贴金额部分?商家优惠券商家补贴金额部分')+'</li><li>'+L_('平台积分抵扣平台补贴部分')+'</li></ul>','',
  	function(e){  
  	});  
   }); 
  mui('.mui-content').on('tap','#price_xia_hdbt',function(e){
   	mui.alert('<p class="tanPtil">'+L_('商家补贴总额：')+$(this).data('merchant_total') +'</br>'+L_('平台补贴总额：')+$(this).data('plat_total') +' </p> ','',
   	function(e){  
   	});  
  });
   
   // 外卖实时看板  损失
   mui('.mui-content').on('tap','#shop_tan_ss',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。')+' </p> ','',
  	function(e){  
  	});  
   }); 
   
   // 外卖实时看板  客单价
   mui('.mui-content').on('tap','#shop_tan_ksd',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内有效订单的平均单价，计算公式为：当前时段所有有效订单的订单收入除以有效单数。')+' </p> ','',
  	function(e){  
  	});  
   }); 
   
   // 外卖实时看板  下单用户数
   mui('.mui-content').on('tap','#shop_tan_xdyhs',function(e){
  	mui.alert('<p class="tanPtil">'+L_('统计时间内下单用户数。')+' </p> ','',
  	function(e){  
  	});  
   });

  // 营销实时看板  活动总营业额
  mui('.mui-content').on('tap','#market_tan_hdzyye',function(e){
	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中的所有活动订单总额；活动类型包含：')+' </p> <ul class="ul_tanUrl"><li>'+L_('商家满减优惠活动')+'</li><li>'+L_('商家优惠券活动')+'</li></ul>','',
	 function(e){  
	});  
  }); 
  
  mui('.mui-content').on('tap','#price_xia_hdzyye',function(e){ 
   	mui.alert('<p class="tanPtil">'+L_('商家满减优惠活动：')+$(this).data('merchant_reduce_order_money') +'</br>'+L_('商家优惠券活动：')+$(this).data('card_price_order_money') +' </p> ','',
   	function(e){  
   	});  
  });
   
  
 // 营销实时看板  活动总支出
 mui('.mui-content').on('tap','#market_tan_hdzyzc',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中的所有活动订单总额；活动类型包含：')+' </p> <ul class="ul_tanUrl"><li>'+L_('商家满减优惠活动')+'</li><li>'+L_('商家优惠券活动')+'</li></ul>','',
 	 function(e){  
 	});  
 }); 
 
 mui('.mui-content').on('tap','#price_xia_hdzyzc',function(e){
  mui.alert('<p class="tanPtil">'+L_('商家满减优惠活动：')+$(this).data('merchant_reduce') +'</br>'+L_('商家优惠券活动：')+$(this).data('card_price') +' </p> ','',
  function(e){  
  });  
 });
  
  // 营销实时看板  带动订单 
 mui('.mui-content').on('tap','#market_tan_dddd',function(e){
 	mui.alert('<p class="tanPtil">'+L_('未被取消的订单数；取消包括平台、店员操作的取消。')+' </p>','',
 	 function(e){  
 	});  
 });
 mui('.mui-content').on('tap','#price_xia_dddd',function(e){
  	mui.alert('<p class="tanPtil">'+L_('商家满减优惠活动：')+$(this).data('merchant_reduce_count') +'</br>'+L_('商家优惠券活动：')+$(this).data('card_price_count') +' </p> ','',
  	function(e){  
  	});   
 });
  
 
   // 营销实时看板  活动盈利
 mui('.mui-content').on('tap','#market_tan_hdyl',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中的所有活动订单平台佣金总额减去所有平台活动补贴总额加上平台配送费，可能会出现负数。')+' </p>','',
 	 function(e){  
 	});  
 });
   // 营销实时看板  活动客单价 
 mui('.mui-content').on('tap','#market_tan_hdykdj',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数。')+' </p> ','',
 	 function(e){  
 	});  
 });
 
 // 营销实时看板  活动老客数
 mui('.mui-content').on('tap','#market_tan_hdxks',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中活动订单的老用户数。')+' </p> ','',
 	 function(e){  
 	});  
 });
 
 // 营销实时看板  活动新客数 
 mui('.mui-content').on('tap','#market_tan_hdylks',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内所有有效订单中活动订单的新用户数。')+' </p> ','',
 	 function(e){  
 	});  
 });
 
 // 营销实时看板  投入产出比
 mui('.mui-content').on('tap','#market_tan_trccb',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内活动盈利除以活动总支出，投入产出比越高效果越好，说明通过较少的投入，得到较多的盈利。')+' </p> ','',
 	 function(e){  
 	});  
 });
 
 // 营销实时看板  活动老客数
 mui('.mui-content').on('tap','#market_tan_lqsl',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内用户领取平台优惠券的总数。')+' </p> ','',
 	 function(e){  
 	});  
 });
 
 // 营销实时看板  活动老客数
 mui('.mui-content').on('tap','#market_tan_hxl',function(e){
 	mui.alert('<p class="tanPtil">'+L_('统计时间内带动订单数除以领取数量。')+' </p> ','',
 	 function(e){  
 	});  
 }); 