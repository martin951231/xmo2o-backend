mui.init();
var store_id=''; //全局
var store_index='';
var admin_realname=''
var admin_invit_code=''
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var config = common.getCache('config',true);
if(!config){
	common.http('Merchantapp&a=config', {}, function(data){
		common.setCache('config',data,true);
		config=common.getCache('config');
		config_loaded();
	});
}else{
	config_loaded();
}

function config_loaded(){
	// 留做处理配置相关
}


function set_index_data(data){
	if(data.logo){
		$('.center img').attr('src',data.logo);
	}
    $('.names').text(data.name);
    $('.all_money').text(data.all_money);
}

//首页主体数据
/*先初始化判断有没有缓存，有的话先展示缓存内容*/
var index_data = common.getCache('index_data',true);
if(index_data){
	set_index_data(index_data);
}
common.http('Merchantapp&a=my_spread_merchant_money',{'client':client,noTip:1},function(data){
    console.log('data', data);
	common.setCache('index_data',data,true);
	
	set_index_data(data);
});  



//我的商家邀请码
mui('.mui-content').on('tap','.my_mer_spread_code',function(e){
    openWindow({
        url:'my_mer_spread_code.html',
        id:'my_mer_spread_code'
    });
});

//我的商家
mui('.mui-content').on('tap','.my_spread_merchant',function(e){
    openWindow({
        url:'my_spread_merchant_list.html',
        id:'my_spread_merchant_list'
    });
});

//我的商家佣金记录
mui('.mui-content').on('tap','.my_spread_merchant_money',function(e){
    openWindow({
        url:'my_spread_merchant_money.html',
        id:'my_spread_merchant_money'
    });
});


if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}