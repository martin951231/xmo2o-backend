var myScroll = null;
var isSearch = false, isLoadSelect = false, isPaidSelect =false;
var hasMore = true;
var nowPage = 1, is_open_pick = null, deliver_type = null, is_change = null, timeOut = null;
$(document).ready( function (){
    async function getIndexData () {
        var indexData = await common.getIndexData();
        indexData && indexData.have_appoint_name && $('.public .content').html(indexData.have_appoint_name + '订单');
    }
	getIndexData()
	
    $(".mask").height($(window).height());
 
    var currYear = new Date().getFullYear();
    var opt = {  
        'dateYMD': {
            preset: 'date',
            dateFormat: 'yyyy-mm-dd',
            theme: 'android-ics light', //皮肤样式
            display: 'bottom',           //显示方式
            mode: 'scroller',           //日期选择模式
            showNow: true,
            nowText: "今天",
            onSelect: function (valueText, inst) {
            	$('.entry ul').empty();
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
    
    $('.entry ul').empty();
    showList();
    $('body,html').animate({scrollTop : 0}, 300);
    $('.query').click(function(){
    	$('.entry ul').empty();
        if($('#find_value').val() == ''){
            isSearch = false;
        } else {
            isSearch = true;
        }
        hasMore = true;
        order_id = 0;
        nowPage = 1;
        showList();
        return false;
    });
	
    $('.entry').css({height:$(window).height()-184});
    $('.entry ul').after('<div class="jroll-infinite-tip">正在加载中...</div>');
    common.scroll($('.entry'),function(scrollIndex){
        showList(scrollIndex);
    });
    
    //验证
    $(document).on('click', '.yanzhen', function(e){
        e.stopPropagation();
        $('#order_id').val($(this).data('id'));
        $(".seek, .mask").show();
    });
    $(document).on('click', '.ensure', function(e){
        common.http('Storestaff&a=appointVerify',{'order_id':$('#order_id').val(), noTip:true}, function(data){
        	$('.overtime_' + $('#order_id').val()).remove();
        	$(".seek, .mask").hide();
        });
    });


    $(document).on('click', '.pay_order', function(e){
        e.stopPropagation();
        var order_id = $(this).data('id');
        common.http('Storestaff&a=pay_appoint_order',{'order_id':$(this).data('id'),noTip:true}, function(response){
            console.log(response.pay_qrcode_url)
            if(response){
                layer.open({
                    title:'查看支付二维码',
                    area:['430px','483px'],
                    content: '<img src="'+response.pay_qrcode_url+'"/>',

                });

            }
        });

    });

    //修改价格
    $(document).on('click', '.change_price', function(e){
        e.stopPropagation();
        $('#order_id').val($(this).data('id'));
        $('#order_money_now').html($(this).data('old_price'));
        $(".amend, .mask").show();
        return false;
    });

    //修改价格确认
    $(document).on('click', '.ensure_change', function(e){
        var price = $('#change_price').val();
        common.http('Storestaff&a=change_appoint_order_price',{'order_id':$('#order_id').val(),price:price, noTip:true}, function(data){
            $(".amend, .mask").hide();
            $(`[data-orderid="${$('#order_id').val()}"].old_price`).html(price);
            $('.change_price').data('old_price',price);
            $('#change_price').val('');
            motify.log('修改成功');
            return false;
        });
    });

    $(".mask,.seek .del, .close,.amend .del,.change_worker .alter .del,.change_worker .cancel-btn").click(function(){
        $(".seek,.mask,.amend,.change_worker").hide();
    });

    let workerList = [];
    let curWorkerId = ''
    // 更换技师
    $(document).on('click', '.changeworker', function(e){
        e.stopPropagation();
        workerList = []
        curWorkerId = ''
        $('.change_worker .worker_list_wrap').empty()
        var order_id = $(this).data('id');
        $('.change_worker').data('order_id',order_id);
        common.http('Storestaff&a=storeWorkerList',{noTip:true,order_id:order_id}, (data) =>{
            workerList = data || []
            if(workerList.length) {
                workerList.forEach(item => {
                    const listItem = `<li data-worker_id="${item.merchant_worker_id}">姓名：${item.name}<br/>联系方式：${item.mobile}<br/>待服务单量：${item.receive_total}</li>`
                    $('.change_worker .worker_list_wrap').append(listItem);
                });
                $('.worker_list_wrap').find("li").on("click", function() {
                    $('.worker_list_wrap').find("li").removeClass('active')
                    $(this).addClass('active')
                    curWorkerId = $(this).data("worker_id");
                });
            }
        });
        $(".change_worker, .mask").show();
    });

    
    $('.change_worker .confirm-btn').on("click", function() {
        if(!workerList.length) {
            motify.log('暂无可更换的技师');
            return
        }
        if(!curWorkerId) {
            motify.log('请选择需要更换的技师');
            return
        }
       
        var order_id = $('.change_worker').data('order_id')

        let params = {
            merchant_worker_id: curWorkerId,
            order_id: order_id,
            noTip: false
        }

        common.http('Storestaff&a=changeWorker',params, (data)=>{
            motify.log('更换技师成功');
            $('.entry ul').empty();
            hasMore = true;
            nowPage = 1;
            $(".mask,.change_worker").hide();
            showList();
        });
    });

});



function showList(scrollIndex)
{
    if (hasMore == false) {
    	return false;
    }
    var pay_type = $('select[name=pay_type]').val(), searchtype = $('select[name=searchtype]').val(), service_status = $('select[name=service_status]').val();
    var stime = $('#stime').val(), etime = $('#etime').val(), key = $('#find_value').val();
    var paid = $('select[name=paid]').val();
    
    if (isSearch == false) {
        common.http('Storestaff&a=appointList',{'paid':paid,'page':nowPage, 'key':key, 'stime':stime, 'etime':etime, 'pay_type':pay_type, 'searchtype':searchtype, 'service_status':service_status,noTip:true}, function(data){
        	if (!isLoadSelect) {
        		var html = '<select name="pay_type"><option value="">全部支付方式</option>';
        		for (var i in data.pay_list) {
        			html += '<option value="' + i + '">' + data.pay_list[i].name + '</option>';
        		}
        		html += '</select>';
        		$('.selsct_pad').html(html);
        		isLoadSelect = true;
        	}

			if($('.mask').height() == 0 || $('.entry').height() <= 0){
				$(".mask").height($(window).height());
				$('.entry').css({"height":$(window).height()-172});
			}
            if(nowPage >= data.page){
                hasMore = false;
                $('.jroll-infinite-tip').addClass('hideText');
            }
            if (data.order_list.length > 0) {
	            laytpl($('#listTpl').html()).render(data.order_list, function(html){
	                $('.entry ul').append(html);
	                common.scrollEnd(scrollIndex);
	            });
	            nowPage++;
            } else {
            	$('.entry ul').html('<div class="jroll-infinite-tip L_">暂无数据</div>');
            }
        });
    } else {
        common.http('Storestaff&a=appointList',{'paid':paid,'page':nowPage, 'stime':stime, 'etime':etime, 'pay_type':pay_type, 'key':key, 'searchtype':searchtype,'service_status':service_status, noTip:true}, function(data){
            if(nowPage >= data.page){
                hasMore = false;
                $('.jroll-infinite-tip').addClass('hideText');
            }
			if($('.mask').height() == 0 || $('.entry').height() <= 0){
				$(".mask").height($(window).height());
				$('.entry').css({"height":$(window).height()-172});
			}
            if (data.order_list.length > 0) {
	            laytpl($('#listTpl').html()).render(data.order_list, function(html){
	                $('.entry ul').append(html);
	                common.scrollEnd(scrollIndex);
	            });
	            nowPage++;
            } else {
            	$('.entry ul').html('<div class="jroll-infinite-tip L_">暂无数据</div>');
            }
        });
    }
}