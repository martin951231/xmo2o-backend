  
common.removeCache('pay_pwd',true);
common.removeCache('resetPwd',true);
common.removeCache('show_credit_pwd_tip');
common.removeCache('bind_hasPwd');

common.removeCache('clickNum',true);
common.removeCache('frist_input_pwd',true);
common.removeCache('bindSucess',true);  
// common.removeCache('orderid',true); 

var pay_pwd = false;
var page_from = '';
 

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
        document.title=L_('visa/mastercard')
		if(urlParam.orderid){
			common.setCache('orderid',urlParam.orderid,true);
		}
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

    common.setCache('resetPwd',0,true); 
    var addbank_sucess=common.getCache('addbank_sucess',true);
    if(addbank_sucess==2){
        weui.alert(L_('恭喜，您的银行卡已成功添加'), { 
            buttons: [{
                label: 'OK',
                type: 'primary',
                onClick: function(){    common.removeCache('addbank_sucess',true);      }
            }]
        }); 
    } 
	$('.ul_bankAddList').show(); 
	getList(1);
});



function getList(state){
	//请求银行卡列表
	common.http('Hake_credit&a=index',{},function(data){
		laytpl($('#listTpl').html()).render(data.card_list,function(html){
            if(state == 1){ 
            }else if(state == 2){
               $('.ul_bankAddList li.li_bankList').remove(); 
            } 
             $('.ul_bankAddList').prepend(html);
			
		});
        pay_pwd = data.pay_pwd;
		common.setCache('pay_pwd',pay_pwd,true);
         
		if(data.card_list.length == 0 || pay_pwd == false){ 
           $('#setPwd').hide();
		}
        if(pay_pwd == true){ 
            $('#setPwd').show();
        } 
		if(page_from == 'pay'){
			$('.bankGo').remove();
			if(data.card_list.length == 0){
				var pay_go_add = common.getCache('pay_go_add',true);
				if(!pay_go_add){
					redirect_self('bankDetails.html');
					common.setCache('pay_go_add','1',true);
				}else{
					common.removeCache('pay_go_add',true);
				}
			}
		}
	});
}

$('.ul_bankAddList li.li_bankList').live('click',function(){
	var id=$(this).data('id'); 
	if(page_from == 'pay'){
		if(pay_pwd){
			redirect('payPassword.html?id='+id);
		}else{
			redirect('inputCode.html?id='+id);
		}
	}else{
		weui.actionSheet([
			{
				label: L_('删除银行卡'), 
				onClick: function () { 
					weui.confirm(L_('确定删除银行卡？'), {
						buttons: [{
							label: L_('再想想'),
							type: 'gray',
							onClick: function(){
								
							}
						}, {
							label: L_('确定'),
							type: 'primary',
							onClick: function(){ 
								common.http('Hake_credit&a=removeCard',{id:id},function(data){  
									$('.ul_bankAddList li.li_bankList[data-id='+id+']').remove(); 
									getList(2);
								},function(errData){
									var msg = errData.errorMsg;
									weui.alert(msg); 
								});
							}
						}]
					});
					
				}
			} 
		], [
			{
				label: L_('取消'),
				onClick: function () {
					
				}
			}
		]);
	}
}) 

$('#setPwd').click(function(){
	if(pay_pwd){
		weui.actionSheet([
			{
				label: L_('修改支付密码'),
				onClick: function () {
					redirect('changePassword.html');
				}
			}, {
				label: L_('重新设置支付密码'),
				onClick: function () {
					redirect('inputCode.html');
				}
			} 
		], [
			{
				label: L_('取消'),
				onClick: function () {
					
				}
			}
		]);
	}else{
		weui.actionSheet([
			{
				label: L_('设置支付密码'),
				onClick: function () {
					redirect('inputCode.html');
				}
			} 
		], [
			{
				label: L_('取消'),
				onClick: function () {
					
				}
			}
		]);
	}
}) 


// order_id

$('#addBank').click(function(){  
    redirect('bankDetails.html'); 
}) 

 
