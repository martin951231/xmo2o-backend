$(document).ready(function(){
    async function getIndexData () {
        var indexData = await common.getIndexData();
        indexData && indexData.have_meal_name && $('.public .content').html(indexData.have_meal_name);
    }
	getIndexData()
	
	initData();
	setInterval('initData()', 1000);
});
function initData()
{
    common.http('Storestaff&a=foodshop', {'noTip':true}, function(data){
        $('#lock_count').html(data.lock_count);
        $('#open_count').html(data.open_count);
        $('#eating_count').html(data.eating_count);
        $('#book_count').html(data.book_count);
        $('#confirm_count').html(data.confirm_count);
        if (data.queue_is_open == 0) {
            $('#queue_count').html('').next('div.ex').html(L_('排号功能已关闭'));
        } else {
            $('#queue_count').html(data.queue_count);
        }
        if (data.store_open_table_type == 1) {
            $('.table dd').eq(0).find('div.ex').html(L_('就餐中'));
            $('.table dd').eq(1).find('div.ex').html(L_('空桌'));
        }

        if (data.staff_type != 2) {
            $('.report').hide();
        }else{
            $('.report').show();
        }

    });
}

$('.report').click(function(){ 
    openWebviewUrl("foodshop_report.html");            
})