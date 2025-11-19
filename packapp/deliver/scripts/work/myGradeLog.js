var nowPage = 1,hasMore = true
$(document).ready(function(){
	var indexData = common.getCache('indexData',true);
	
	if(!indexData){
		location.href = 'index.html';
	}   
	get_list();
	common.scroll($('#myGradeBox'),function(scrollIndex){ 
		get_list(scrollIndex);
	});
	 
});

function get_list(scrollIndex){
	if(hasMore == false){
		return false;
	} 
	common.http('Deliver&a=my_level_log',{page:nowPage}, function(data){  
		if(data.list.length > 0){ 
			
			laytpl($('#replyListBoxTpl').html()).render(data.list, function(html){  
				
				if(nowPage == 1){
					$('#myGradeBox').html(html);
				}else{
					$('#myGradeBox').append(html);
				} 
				common.scrollEnd(scrollIndex);
			});
			if(nowPage >= data.total_page){
				hasMore = false;
			}
			nowPage++;
		}else{ 
			$('.no_jilu').show();
            hasMore = false; 
		}
	});
}