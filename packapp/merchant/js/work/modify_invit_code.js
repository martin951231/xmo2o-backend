if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}


var merchant_info = common.getCache('merchant_info',true);

mui.init();


mui('.mui-content').on('tap','.modify_password',function(e){
	if($('#invit_code').val().length ==0){
		mui.alert('请输入邀请码');
	}else{

		common.http('Merchantapp&a=set_invit_code',{invit_code:$('#invit_code').val()},function(data){
			motify.log('邀请码设置成功');
			history.go(-1)
		});
	}
});

