// 初始化echarts实例
var myChart = echarts.init(document.getElementById('container'));
var option = {
        tooltip : {
            trigger: 'axis',
            formatter:function(params){
              var res = '';
              var title='';
              var count=0;
              var nameList = params;
              for(var i=0; i<params.length; i++){
                if(nameList[i].data != undefined){
                  res += nameList[i].marker+nameList[i].seriesName+ ': '+nameList[i].data+'<br>'
                  count+=parseFloat(nameList[i].data)
                }
              }
              title=nameList[0].dataIndex+":00-"+nameList[0].dataIndex+":59";

              res = res.split('<br>');

              return title+'<br>'+res[0]+'<br>'+res[1];
            },
            axisPointer:{
                lineStyle:{
                    type: 'solid', 
                    color:'#FFA637'
                }
            }
        },
        legend: {
            data:['今日','昨日']
        },
       
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis : [
            {
                type : 'category',
                boundaryGap : false,
                data : ["0","1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18","19","20","21","22","23"],
                 splitLine:{
                   show:false     //去掉网格线
                }
            }
        ],
        yAxis : [
            {
                show:false,
                type : 'value',
                splitLine:{
                    show:true,
                    lineStyle:{
                        color:'#F8F8F8',
                    }
                },
                axisTick: {
                    show: false
                },
                axisLabel: {
                    show: false
                }

            }
        ],
        series : [
        ]
    };
    //初始化echarts实例

//使用制定的配置项和数据显示图表
myChart.setOption(option);

$(function(){
	$('.tips_img').mouseover(function(e){
	    var now    = $(this);
	    var offset = $(this).position();
	    var parent = $(this).closest('h5');
	    now.data('tips',now.attr('title')).attr('title','');
	   
	    var info =  now.data('info');
	    console.log(info)
	    var info_str =  '';
	    if (typeof(info) != 'undefined') {
	        var infoArr = info.split('?');
	        for (var i = 0; i < infoArr.length; i++) {
	            info_str += '<div><strong style="font-size: 22px;margin-right: 5px;">·</strong>'+infoArr[i]+'</div>';
	        }

	    }
	    parent.append('<div class="tooltipdi" style="left:'+(offset.left+25)+'px;top:'+(offset.top-10)+'px"><span><b></b><em></em>'+now.data('tips')+info_str+'</span></div>');
	    var tips_div = parent.find('.tooltipdi');
	    parent.one('mouseout',function(){
	        now.attr('title',now.data('tips'));
	        tips_div.remove();
	    });       
	});

    // 切换全部部分店铺
    $('#select_store').click(function(){
        var obj = $(this);
        var title= L_("选择店铺");
        var type= $(obj).data('type');
        
        window.art.dialog.data('store_list', $('#part_store').val());         
        var href= $(obj).attr('href')+'&type'+type+'&store_list='+$('#part_store').val();
        
        window.art.dialog.open(href,{
            init: function(){
                var iframe = this.iframe.contentWindow;
                window.art.dialog.data('iframe_handle',iframe);
            },
            id: 'part_store',
            title:title,
            padding: 0,
            width: 600,
            height: 500,
            lock: true,
            resize: false,
            background:'black',
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '10%',
            opacity:'0.4',
            close:function(){
                var store_list = window.art.dialog.data('store_list');
                if (!store_list || store_list==-1) {
                    $(obj).find('span').html(L_('全部店铺'))
                }else{
                    $(obj).find('span').html(L_('指定店铺'))
                }
                $('#part_store').val(store_list);
                console.log($('#part_store').val())
            }
        });
    });

    $('.discount_info_show').mouseover(function(e){
        var now    = $(this);
        var offset = $(this).position();
        var parent = $(this).closest('h1');    
        now.siblings('.discount_info').css({'left':(offset.left-120)+'px','top':(offset.top+8)+'px'}).show();

        now.siblings('.discount_info_fr').css({'left':(offset.left-30)+'px','top':(offset.top+18)+'px'})
        now.siblings('.discount_info').show();
        now.siblings('.discount_info_fr').show();
        parent.one('mouseout',function(){
            now.siblings('.discount_info').hide();
            now.siblings('.discount_info_fr').hide();
        });       
    });

	// 今日营业统计及与昨日对比数据
	statisticsToday();

	// 图表数据
	statisticsTodayChart();

	// 历史数据
	statisticsHistory();
});

// 改变button样式
function SupendButtonClick1() {
    $('#SupendButton_1').addClass("active")
    $('#SupendButton_2').removeClass("active")
    statisticsTodayChart()
}
function SupendButtonClick2() {
    $('#SupendButton_2').addClass("active")
    $('#SupendButton_1').removeClass("active")
    statisticsTodayChart()
}
$('.time-choice').click(function(){
    $(this).addClass("time-current").siblings().removeClass("time-current");
    $('#date_type').val($(this).attr('timetype'))
    if ($(this).attr('timetype')=='4') {
        $('.time_select').show();
    }else{
        statisticsHistory();
        $('.time_select').hide();
    }
})
$('.time-sure').click(function(){
    statisticsHistory();
})


// 自动刷新
var time = 300;
function refresh(){
    setTimeout(function(){
        if (time>0) {
            time=time-1;
             var min_str = formatSeconds(time);
             $('.auto_refresh').html(min_str)
            refresh();
        }else{
            location.reload();
        }
    },1000)
}
refresh();

function formatSeconds(value) {
    var secondTime = parseInt(value);// 秒
    var minuteTime = 0;// 分
    var hourTime = 0;// 小时
    if(secondTime > 60) {//如果秒数大于60，将秒数转换成整数
        //获取分钟，除以60取整数，得到整数分钟
        minuteTime = parseInt(secondTime / 60);
        //获取秒数，秒数取佘，得到整数秒数
        secondTime = parseInt(secondTime % 60);
        //如果分钟大于60，将分钟转换成小时
        if(minuteTime > 60) {
            //获取小时，获取分钟除以60，得到整数小时
            hourTime = parseInt(minuteTime / 60);
            //获取小时后取佘的分，获取分钟除以60取佘的分
            minuteTime = parseInt(minuteTime % 60);
        }
    }
    var result = "" + parseInt(secondTime) + "秒";

    if(minuteTime > 0) {
        result = "" + parseInt(minuteTime) + "分" + result;
    }
    if(hourTime > 0) {
        result = "" + parseInt(hourTime) + "小时" + result;
    }
    return result;
}

// 今日营业统计及与昨日对比数据
function statisticsToday(){
    $.post(statisticsTodayUrl,{store_list:store_list,business_type:business_type,order_type:order_type},function(data){
        if (data.status==1) {
            var data = data.info;
            setData(data,'','today-');
            setData(data.discount_info,'','today-');
        }
    })
}

// 图表数据
function statisticsTodayChart(){
    var data_type = $('.data_type').find('.active').attr('data-data_type');
    var is_add_up = $('select[name=is_add_up]').val();
    $.post(statisticsTodayChartUrl,{store_list:store_list,data_type:data_type,is_add_up:is_add_up,business_type:business_type,order_type:order_type},function(data){
        if (data.status==1) {
            var data = data.info;
            var chartData = [{
                name:'昨日',
                type:'line',
                // areaStyle: {},
                itemStyle: {  
                    normal: {  
                        color: "#C9C9C9",
                        opacity: 1, //为0不会绘制图形拐点消失
                        lineStyle:{
                            width:2,
                            type:'dotted'  //'dotted'虚线 'solid'实线
                        }
                    },  
                    emphasis: {  
                        color: '#C9C9C9',  
                        lineStyle: {        // 系列级个性化折线样式  
                            width: 2,  
                            type: 'dotted',  
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{  
                                offset: 0,  
                                color: 'green'  
                            }, {  
                                offset: 1,  
                                color: 'red'  
                            }])  
                        }  
                    }  
                },//线条样式  
                smooth: true,
                data:data.yesterdayArr
            },{
                name:'今日',
                type:'line',
                stack: '总量',
                areaStyle: {
                          normal: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                              { offset: 0, color: "#FFD49D" },
                              { offset: 0.5, color: "#FFECD5" },
                              { offset: 1, color: "white" }
                            ])
                          }},
                itemStyle: {  
                    normal: {  
                        color: "#FFA637",
                        opacity: 1 //为0不会绘制图形拐点消失
                    },  
                    emphasis: {  
                        color: '#FFA637',  
                        lineStyle: {        // 系列级个性化折线样式  
                            width: 2,  
                            type: 'dotted',  
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{  
                                offset: 0,  
                                color: 'green'  
                            }, {  
                                offset: 1,  
                                color: 'red'  
                            }])  
                        }  
                    }  
                },//线条样式  
                smooth: true,
                data:data.todayArr
            }
            ];
            option.series = chartData;

            if (is_add_up==1) {
                // 累计
                option.tooltip = {
                        trigger: 'axis',
                        backgroundColor:'#3F4156',
                        formatter:function(params){
                          var res = '';
                          var title='';
                          var count=0;
                          var nameList = params;
                          for(var i=0; i<params.length; i++){
                            if(nameList[i].data != undefined){
                              res += nameList[i].marker+nameList[i].seriesName+ ': '+(data_type==0?Currency_symbol:'')+nameList[i].data+(data_type==1?L_('单'):'')+'<br>'
                              count+=parseFloat(nameList[i].data)
                            }
                          }
                          title="00:00-"+nameList[0].dataIndex+":59";

                          res = res.split('<br>');
                          var show_text = '';
                          show_text = title+'<br>'+res[0]+'<br>'+res[1];
                          return show_text;
                        },
                        axisPointer:{
                            lineStyle:{
                                type: 'solid', 
                                color:'#FFA637'
                            }
                        }
                    }
            }else{
                // 分时
                option.tooltip = {
                    trigger: 'axis',
                    backgroundColor:'#3F4156',
                    formatter:function(params){
                      var res = '';
                      var title='';
                      var count=0;
                      var nameList = params;
                      for(var i=0; i<params.length; i++){
                        if(nameList[i].data != undefined){
                          res += nameList[i].marker+nameList[i].seriesName+ ': '+(data_type==0?Currency_symbol:'')+nameList[i].data+(data_type==1?L_('单'):'')+'<br>'
                          count+=parseFloat(nameList[i].data)
                        }
                      }
                      title=nameList[0].dataIndex+":00-"+nameList[0].dataIndex+":59";

                      res = res.split('<br>');
                      var show_text = '';
                      show_text = title+'<br>'+res[0]+'<br>'+res[1];
                      return show_text;
                    },
                    axisPointer:{
                        lineStyle:{
                            type: 'solid', 
                            color:'#FFA637'
                        }
                    }
                }
            }
            myChart.setOption(option, true);

            $('.yesterdayTotal').html(data.yesterdayTotal)
        }
    })
}

// 历史数据
function statisticsHistory(){
    var type = $('.time-current').attr('timetype');
    var start_time = $('input[name=start_time]').val();
    var end_time = $('input[name=end_time]').val();
    $.post(statisticsHistoryUrl,{store_list:store_list,type:type,start_time:start_time,end_time:end_time,business_type:business_type,order_type:order_type},function(data){
        if (data.status==1) {
            var data = data.info;
            setData(data,'','history-');
            setData(data.discount_info,'','history-');
        }
    })

    // 更新店铺列表数据
    statisticsHistoryByStore();
}

// 历史店铺数据
function statisticsHistoryByStore(){
    var type = $('.time-current').attr('timetype');
    var start_time = $('input[name=start_time]').val();
    var end_time = $('input[name=end_time]').val();
    var url = statisticsHistoryByStoreUrl+'&type='+type+'&start_time='+start_time+'&end_time'+end_time+'&business_type='+business_type+'&order_type='+order_type+'&store_list='+store_list;
    // 下载链接
    export_url = export_url_source+'&store_list='+store_list+'&type='+type+'&start_time='+start_time+'&end_time'+end_time+'&business_type='+business_type+'&order_type='+order_type;
    var _iframe1=document.getElementById('store_info');
    _iframe1.src = url;
    _iframe1.contentWindow.location.href=url;
}
statisticsHistory();

function setData(data,pData,prefix=''){
    var tmpVar = '';
    var tmpId = '';
    var exec = '';
    var bindData = '';
    var defaultData = '';
    pData = !pData ? '' : pData + '-';
    for(var i in data){
        tmpVar = typeof(data[i]);
        if(tmpVar == 'array' || tmpVar == 'object'){
            setData(data[i],i);
        }else{
            tmpId = $('.'+prefix+(pData+i));
            if(tmpId.size() > 0){
                tmpVar = data[i];
                exec = tmpId.data('exec');
                if(exec){
                    tmpVar = window[exec](tmpVar);
                }
                bindData = tmpId.data('data');
                if(bindData){
                    tmpId.data(bindData,tmpVar);
                }
                bindEmpty = tmpId.data('empty');
                if(tmpVar == '' && tmpVar !== 0){
                    defaultData = tmpId.data('default');
                    tmpId.html(defaultData ? defaultData : '&nbsp;');
                }else{
                    // 在这里做数字变化
                    // var num = tmpVar.split(',');
                    // num = num.join('');
                    // num = parseInt(num);
                    // if (num>0) {
                    //     numRunFun(0,num,tmpVar,tmpId)
                    // }else{
                        tmpId.html(tmpVar);
                    // }
                }
            }
        }
    }
}

 function numRunFun(num, maxNum,maxShow, numBox){
    var num = num;
    var maxNum = maxNum;
    var time = 0.1;
    if (maxNum>1000 && maxNum<5000) {
        time = 0.001
    }else if(maxNum>5000){
        time = 0.0001
    }
    var timer = setInterval(function(){
        num++; // 调节速度
        if(num >= maxNum){
            numBox.html(maxShow);
            clearInterval(timer);
        } else {
            numBox.html(num);
        }
    },time); // 也可以调节速度
}

