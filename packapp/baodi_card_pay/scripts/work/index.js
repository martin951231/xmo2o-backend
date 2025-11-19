//请求配置项
common.getConfig({special_lang:visitWork},function(){
	//判断登录态
	console.log(urlParam);
	if(urlParam.ticket && urlParam.device_id){
		common.setCache('ticket',urlParam.ticket,true);
		common.setCache('device_id',urlParam.device_id,true);
    }

	if(urlParam.from){
		common.setCache('from',urlParam.from,true);
    }
     
	
	page_from = common.getCache('from',true);
	if(page_from == 'pay'){
        $('#setPwd').closest('li').remove(); 
	}
	
    var ticket = common.getCache('ticket',true);  
	var device_id = common.getCache('device_id',true);
	if(!ticket || !device_id){
		var alertDom = weui.alert(L_('没获取到您的登录状态，请重新进入本页面'),{
			buttons: [{
				label: L_('确定'),
				type: 'primary',
				onClick: function(){
					return false;
				}
			}]
		});
		return false;
    }
	var userInfo = common.getCache('userInfo',true);
	if(!userInfo){
		common.http('Login&a=login',{},function(data){
			userInfo = data.user;
			common.setCache('userInfo',userInfo,true);
		});
	}

	$('.ul_bankAddList').show(); 
	getList(1);
});



function getList(state){
	//请求银行卡列表
	common.http('Baodi_card&a=index',{},function(data){
		console.log('data', data);
		laytpl($('#listTpl').html()).render(data,function(html){
            if(state == 1){
				
            }else if(state == 2){
               $('.ul_bankAddList li.li_bankList').remove(); 
            } 
			
            $('.ul_bankAddList').prepend(html);
		});
	});
}

$('.ul_bankAddList li.li_bankList').live('click',function(){
	var id = $(this).data('id'); 
	var balance = $(this).data('balance'); 
	if(!balance || balance == ''){
		return false;
	}
	
	//扣款
	common.http('Baodi_card&a=payCard',{ProfileId: id, orderid: urlParam.orderid},function(data){
		location.replace(data);
	});
})
