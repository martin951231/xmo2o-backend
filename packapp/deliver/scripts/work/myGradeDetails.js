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
		
		var ruleArray=data.level_rule
		ruleArray.forEach(element => {  
			if(element.pigcms_id==getParamString("pigcms_id")){
			   $('.myGradeDetails').html(element.content);
			   $('.myGradeTitle').html(element.name); 
			}
			
		}); 

	});
 
	 
});