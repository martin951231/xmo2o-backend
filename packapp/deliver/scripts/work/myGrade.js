function getParamString(name) {
	var paramUrl = window.location.search.substr(1);
	var paramStrs = paramUrl.split('&');
	var params = {};
	for(var index = 0; index < paramStrs.length; index++) {
		params[paramStrs[index].split('=')[0]] = decodeURI(paramStrs[index].split('=')[1]);
	}
	return params[name];
}
 
$(document).ready(function(){
	var indexData = common.getCache('indexData',true);
	
	if(!indexData){
		location.href = 'index.html';
	}  
   
	common.setData(indexData);   
	common.http('Deliver&a=my_level',{}, function(data){    
		common.setData(data); 
		if(data.level_list.length > 0){
			laytpl($('#replyListBoxTpl').html()).render(data.level_list, function(html){ 
				$('.swiperSlideBox').html(html);
			}); 

			var swiper = new Swiper('.swiper-container', {
				slidesPerView: 3.5,
				spaceBetween: 0, 
			  });
			 
		}
		if (data.next_score==0) {
			$('.nextGrade').html('最高等级');
		}
		 
		laytpl($('#level_ruleListBoxTpl').html()).render(data.level_rule, function(html){ 
			$('.gradeList_a').html(html);
		   var ruleArray=data.level_rule
			ruleArray.forEach(element => {  
				if(element.pigcms_id==getParamString("pigcms_id")){
				   $('.myGradeDetails').html(element.content);
				}
				
			});
			

			
		});   
		
		

	});
 
	 
});