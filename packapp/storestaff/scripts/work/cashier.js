var order_id = 0;
var isSearch = false;
var hasMore = true;
var nowPage = 1;
// var 
$(document).ready(function(){
    // 样式设置
    function pageStyle() {
        // 获取顶部导航及筛选条件的高度
        var navHeight = $('.public').outerHeight(true);
        var searchBoxHeight = $('.cable').outerHeight(true);
        $('#order_list').css({height:$(window).outerHeight(true) - navHeight - searchBoxHeight});
        $('#order_list').css({marginTop: 0});
    }
    pageStyle()

    async function getIndexData () {
        var indexData = await common.getIndexData();
        indexData && indexData.have_cash_name && $('.public .content').html(indexData.have_cash_name);
        if(indexData && indexData.open_score_fenrun==1){
            $('.census').show();
            $('.found').hide();
        }else{
            $('.found').show();
            $('.census').hide();
        }
    }
	getIndexData()

    // 时间筛选
    function timePicker() {
        var currYear = new Date().getFullYear();
        var opt = {
            'dateYMD': {
                preset: 'date',
                dateFormat: 'yy-mm-dd',
                theme: 'android-ics light', //皮肤样式
                display: 'bottom',           //显示方式
                mode: 'scroller',           //日期选择模式
                showNow: true,
                nowText: L_("今天"),
                onSelect: function (valueText, inst) {
                    $('#order_list ul').empty();
                    if($('#find_value').val() == ''){
                        isSearch = false;
                    } else {
                        isSearch = true;
                    }
                    hasMore = true;
                    nowPage = 1;
                    showList();
                }
            },'select': {
                preset: 'select'
            }
        } 
        $('#stime').scroller($.extend(opt['dateYMD'],opt['default']));
        $('#etime').scroller($.extend(opt['dateYMD'],opt['default']));
    }
    timePicker()
	showList();
    $('body,html').animate({scrollTop : 0}, 300);
	$('#order_list ul').after('<div class="jroll-infinite-tip">正在加载中...</div>');
	common.scroll($('#order_list'),function(scrollIndex){
		showList(scrollIndex);
	});
	
	$('#searchForm').submit(function(){
		$('#order_list ul').empty();
		if($('#find_value').val() == ''){
			isSearch = false;
		}else{
			isSearch = true;
		}
		hasMore = true;
		order_id = 0;
		nowPage = 1;
		showList();
		
		return false;
	});
	
	 $(".sus_popup").click(function(e){
        event.stopPropagation();
        $(".sus_url").is(":hidden") ? $(".sus_url").show() : $(".sus_url").hide();
    });
	$('.search').click(function(){
		common.scan('scanCardResult');
	});

	// 重打小票
	$(document).on('click', '.printer', function () {
		var short_order_id = $(this).data('short_order_id');
		common.http('Storestaff&a=reprint_store_order',{order_id:short_order_id}, function(data){		
			motify.log(L_('补打小票成功'));
		});
		return false
	})
});

function scanCardResult(str){
	var code = str
	common.http('Storestaff&a=scan_payid_check',{'payid':str}, function(data){		
		if(data.uid>0){		
			window.location.href="cashier_set.html?uid="+data.uid+"&from_scan=1&payid="+data.payid;
		}
	});
}

function showList(scrollIndex){
	if(hasMore == false){
		return false;
	}
    // 开始日期 结束日期
    var stime = $('#stime').val(), etime = $('#etime').val();
	if(isSearch == false){
		common.http('Storestaff&a=store_arrival',{'order_id':order_id,noTip:true,'stime':stime, 'etime':etime}, function(data){
			if(data.order_list.length > 0){
				order_id = data.order_list[data.order_list.length-1].order_id;
			}
			if(nowPage >= data.pagenum){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
			if($('#order_list').height() <= 0){
				$('#order_list').css({"height":$(window).height()-104});
			}
            // 总额
            var totalPrice = data.total_price || 0
            $('#totalPrice').html(totalPrice)
			$('#refundTotalPrice').html(data.total_refund_price || 0)
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('#order_list ul').append(html);
				common.scrollEnd(scrollIndex);
			});
			nowPage++;
		});
	}else{
		common.http('Storestaff&a=store_arrival',{'order_id':order_id,keyword:$('#keyword').val(),condition:$('#condition').val(),noTip:true,'stime':stime, 'etime':etime,}, function(data){
			if(data.order_list.length > 0){
				order_id = data.order_list[data.order_list.length-1].order_id;
			}
			if(nowPage >= data.pagenum){
				hasMore = false;
				$('.jroll-infinite-tip').addClass('hideText');
			}
            // 总额
            var totalPrice = data.total_price || 0
            $('#totalPrice').html(totalPrice)
			$('#refundTotalPrice').html(data.total_refund_price || 0)
			laytpl($('#listTpl').html()).render(data.order_list, function(html){
				$('#order_list ul').append(html);
				common.scrollEnd(scrollIndex);
			});
			nowPage++;
		});
	}
}