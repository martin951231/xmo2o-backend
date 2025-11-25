var now_page = 0, hasMorePage = true, isLoading = true;
var showCount=1;
var param={};
var total_page=1;
$(function(){
	$(window).scroll(function() {
		if ($(window).scrollTop() > $('.demo-block').offset().top) {
			if(isLoading == false && hasMorePage == true && $(document).scrollTop() >= $(document).height() - $(window).height()){
				getList(true);
			}
		}
	});
});
function getList(more)
{
	isLoading = true;
	now_page += 1;
	param['showCount']=showCount;
	param['page']=now_page;
	if(more && now_page>total_page){
		return false;
	}
	$.post(shopReplyUrl,param,function(res){
		res = $.parseJSON(res);		
		if(res.all_count){
			$('#all_count').find('.num').text(res.all_count);
		}
		if(res.good_count){
			$('#good_count').find('.num').text(res.good_count);
		}
		if(res.common_count){
			$('#common_count').find('.num').text(res.common_count);
		}
		if(res.bad_count){
			$('#bad_count').find('.num').text(res.bad_count);
		}
		var list=res.list;
		if(typeof res!='object'){
			$('.demo-block').empty();
			$(window).scrollTop(0);
			return false;
		}else{
			total_page=res.total;
		}	
		laytpl($('#shopReplyTpl').html()).render(list, function(html){
			if (more) {
				$('.demo-block').append(html);
			} else {
				$('.demo-block').html(html);
				$(window).scrollTop(0);
			}
		});
		/*星级*/
		$(".star p").each(function(index, element) {
			$(this).css("width", $(this).attr("tip") * 16);
		});
		showCount=0;
		isLoading = false;
	});	
}
