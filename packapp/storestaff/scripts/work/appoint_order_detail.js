$(document).ready(function(){
	//查看更多
    $(".three p").each(function(){
        if($(this).height()>22){
            $(".three").addClass("casmore")
        }
    });
    $(".three").click(function(){
        if($(this).hasClass("hasmore")){
            $(this).removeClass("hasmore")
        }else{
            $(this).addClass("hasmore")
        }
    });
    
    $(window).scroll(function(){
        if($(this).scrollTop() > 200){
           $(".coping").show(); 
        }else{
           $(".coping").hide();  
        }
    });
    $(".coping .top").click(function(){
        $("body,html").animate({
            scrollTop: 0
        },500);
    });
	common.http('Storestaff&a=appointDetail', {'order_id':urlParam.order_id}, function(data){
        $('#business_order_id').val(data.order_id);

		laytpl($('#userDetail').html()).render(data, function(html){
			$('.caption').html(html);
		});
		laytpl($('#orderDetail').html()).render(data, function(html){
			$('.g_details .rese_infor ul').html(html);
		});
        if(data.refund && data.refund.id){
            $('#business_refund_id').val(data.refund.id);
            laytpl($('#refundDetail').html()).render(data.refund, function(html){
                $('.refund_details .rese_infor ul').html(html);
            });
        }
	});
	
    //验证
    $(document).on('click', '.verification', function(e){
        e.stopPropagation();
        $('#order_id').val($(this).data('id'));
        $(".seek, .mask").show();
    });
    $(document).on('click', '.ensure', function(e){
        common.http('Storestaff&a=appointVerify',{'order_id':$('#order_id').val(), noTip:true}, function(data){
            location.reload();
        });
    });
    $(document).on('click', '.refund_image', function(e){
        e.stopPropagation();
        var src = $(this).attr('src');
        layer.open({
            type: 1, // page 层类型
            title: '',
            shade: 0.6, // 遮罩透明度
            shadeClose: true, // 点击遮罩区域，关闭弹层
            maxmin: true, // 允许全屏最小化
            anim: 0, // 0-6 的动画形式，-1 不开启
            content: '<img src="'+src+'"/>'
        });
    });

    $(document).on('click', '.disagree_refund', function (e) {
        e.stopPropagation();
        var audit_refund_remark = $('#audit_refund_remark').val();
        if (!audit_refund_remark) {
            $('#audit_refund_remark').focus();
            weui.alert('请填写退款审核备注');
            return ;
        }

        var params = {
            order_id: $('#business_order_id').val(),
            refund_id:  $('#business_refund_id').val(),
            reply_content: $('#audit_refund_remark').val(),
            type: 'disagree',
            noTip: true
        }
        common.http('Storestaff&a=appoint_reply_refund', params, function (data) {
            location.reload();
        });
    });

    $(document).on('click', '.agree_refund', function (e) {
        e.stopPropagation();
        weui.confirm('确认同意退款？', function () {
            var params = {
                order_id: $('#business_order_id').val(),
                refund_id:  $('#business_refund_id').val(),
                reply_content: $('#audit_refund_remark').val(),
                type: 'agree',
                noTip: true
            }
            common.http('Storestaff&a=appoint_reply_refund', params, function (data) {
                location.reload();
            });
        })
    });


    $(".mask,.seek .del, .close").click(function(){
        $(".seek,.mask").hide();
    });

    $(".order_logs").on('click', function () {
        openWebviewUrl("order_trace.html?order_id=" + urlParam.order_id);
    });
});