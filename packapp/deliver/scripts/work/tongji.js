$(document).ready(function(){
    var date = new Date();
    var month=(date.getMonth()+1)>9?(date.getMonth()+1):'0'+(date.getMonth()+1);
    var data=date.getDate()>9?date.getDate():'0'+date.getDate();
    $(".data").val(date.getFullYear()+"-"+month+"-"+data);
    var begin_time = $('#appDate').val();
    var end_time =$('#appDate1').val();
	var indexData = common.getCache('indexData',true);
	if(!indexData){
		location.href = 'index.html';
	}
	
	if(common.checkWeixin()){
		common.fillPageBg(2,['#fff','#f4f4f4']);
	}
	
	if(common.checkIphoneXApp()){
		$('.bottom').css('padding-bottom','34px');
		$('.Statistics .Statistics_end').css('margin-bottom','84px');
	}
	
	if(!indexData.deliver_info.is_system){
		$('.merchant_hide').hide();
	}
	
	var calendar = new lCalendar();
	calendar.init({
		'trigger': '#appDate',
		'type': 'date',
		'callFunc':function(date){
			changeDate();
		}
	});
	
	$('#begin h2').click(function(){
		$('#appDate').trigger('click');
	});
	
	var calendar2 = new lCalendar();
	calendar2.init({
		'trigger': '#appDate1',
		'type': 'date',
		'callFunc':function(date){
			changeDate();
		}
	});
	
	$('#end h2').click(function(){
		$('#appDate1').trigger('click');
	});

	initDeliverStoreList();
	tongji();

	
    $(".tabs").on('click', 'div', function(){
        $(this).addClass("flexActive").siblings().removeClass("flexActive");;
        var order_from = parseInt($(this).data('order_from'));
        if (order_from == 0) {
            $(".tjt").show();
            $(".xiaofei").hide()
        } else {
            $(".tjt").hide();
            $(".xiaofei").show()
        }
        tongji()
    })

	$('#storeId').on('change',function () {
		tongji()
	})
    
});

function initDeliverStoreList() {
	common.http('Deliver&a=deliverStoreList', {}, function (data) {
		var len = data.length;
		if (len > 0) {
			var _html = '<option value="0" selected>' + L_('全部店铺') + '</option>';
			for (i = 0; i < len; i++) {
				_html += '<option value="' + data[i]['store_id'] + '">' + data[i]['name'] + '</option>'
			}
			$('.deliverStoreList').find('select').html(_html);
			$('.deliverStoreList').show();
		} else {
			$('.deliverStoreList').hide();
		}
	})
}

function changeDate(){
    var begin_time = $('#appDate').val();
    var end_time = $('#appDate1').val();
    if(begin_time && end_time){
        tongji();
    }
}

function tongji(){
    var order_from = parseInt($('.tabs .flexActive').data('order_from'));
    var begin_time = $('#appDate').val();
    var end_time = $('#appDate1').val();
    var store_id = $('#storeId option:selected').val();
    common.http('Deliver&a=new_tongji',{begin_time:begin_time,end_time:end_time,order_from:order_from,store_id:store_id}, function(data){
        common.setData(data);
        console.log('data',data);
        $('#appDate').val(data.appDate);
        $('#appDate1').val(data.appDate1);
        if (data.is_buy || data.is_give) {
            $('.tabs .shop').show();
            if (data.is_buy) {
                $('.service_buy').show();
            } else {
                $('.service_buy').hide();
			}
            if (data.is_give) {
                $('.tabs .service_give').show();
            } else {
                $('.tabs .service_give').hide();
			}
		} else {
            $('.tabs .shop').hide();
		}
		if (data.deliver_model_switch) {
            $('.Statistics_end .pay_line').hide();
            $('.Statistics_end .change_num').hide();
            $('.Statistics_end .offline_money').hide();
            $('.Statistics_end .grab_num').hide();
            $('.Statistics_end .platform_fee').hide();
		}
    });
}

var exitLayer = -1;
function appbackmonitor(){
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
		window.lifepasslogin.closeWebView();
	}else{
		if (common.getVersion() >= 1300) {
			window.pigcmspackapp.returnToHomePage();
		}else if(exitLayer != -1){
			window.pigcmspackapp.closewebview(2);
		}else{
			layer.closeAll();
			exitLayer = layer.open({
				content: L_('您确定要退出程序吗？再次按返回键将退出。')
				,btn: [L_('确定'), L_('取消')]
				,yes: function(index){
					window.pigcmspackapp.closewebview(2);
					layer.close(index);
				}
				,end: function(index){
					exitLayer = -1;
				}
			});
		}
	}
}