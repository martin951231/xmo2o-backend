$(document).ready(function(){
	initData();
});
function initData()
{
    common.http('Storestaff&a=foodshop_report', {'noTip':true}, function(data){
        console.log(data.pay_type_report)
        if (data.pay_type_report.length > 0) {
            laytpl($('#reportTpl').html()).render(data, function(html){
                $('.pay_type_report').html(html);
            });
        } else {
            var html = '<li class="pay_type_report">'
                html += '<div class="top pitch clr">'
                html += '<div class="fl">支付方式统计</div>'
                html += '</div>'
                html += '<div class="con">'
                html += '<div class="con_top pay_type_report">'
                html += ' <p>暂无订单统计记录。</p>'
                html += '</div>'
                html += ' </div>'
                html += '</li>';
            $('.pay_type_report').html(html);
        }
        $('.stime').html(data.stime);
        $('.etime').html(data.etime);
        $('.num').html(data.num);
        $('.people_num').html(data.people_num);
        $('.per_capita_consumption').html(data.per_capita_consumption);
        $('.report').click(function(){ 
            openWebviewUrl("foodshop_report.html");            
        })
    });
}

//打印菜单
$(document).on('click', '.click .print', function() {
    var order_id = $(this).data('order_id');
    $('.prints .button .ensure').data('order_id', $(this).data('order_id'));
    $('.prints .button .ensure').data('is_print_menu', 0);
    $(".prints,.mask").fadeIn();
});

$('.prints .button .ensure').click(function() {
    common.http('Storestaff&a=foodshop_report_print',{noTip:true}, function(data){
        motify.log(data);
        $(".prints,.mask").fadeOut();
    });
});