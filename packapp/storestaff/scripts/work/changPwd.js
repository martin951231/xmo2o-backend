var now_device_id = '',now_ticket = '',clearcache_index = null;
var flag  = false;
$(document).ready(function(){
	
	if(common.checkWeixin()){
		$('#fixed_top').hide();
		document.title = $('#fixed_top .content').html();
	}

    $('.btn_ok').click(function(){ 
        var password=$('.news_pwd1').val();
        var old_password=$('.old_password').val(); 
        var password2=$('.news_pwd2').val(); 
         
         if( password.length < 6){
            motify.log(L_('新密码不能少于6位！'));
            return false;
         } 
         if( password == ''){
            motify.log(L_('新密码不能为空！'));
            return false;
         } 
         if( password2 == ''){
            motify.log(L_('再次输入新密码不能为空！'));
            return false;
         }
         if(password != password2){
            motify.log(L_('两次输入密码不相同！'));
            $('.news_pwd2').val('');
            return false;
         }
         
        common.http('Storestaff&a=changePwd',{'password':password,'old_password':old_password}, function(data){
            layer.open({
				title: L_('提示')
				,content: L_('密码修改成功，下次请使用新密码登录')
				,btn: [L_('好的')]
				,shadeClose:false
				,end: function (index){
					redirect('back');
				}
			});
			$('body').append('<style>.layermbtn:before{content:"";position:static;}</style>');
        })
    })
	
 
});

 