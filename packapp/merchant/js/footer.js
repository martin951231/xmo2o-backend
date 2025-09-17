var power=common.getCache('power');
if(power){
	power.card==1?$('.shop_plus').show():$('.shop_plus').hide();//会员
	power.merchant_money==1?$('.merchant_money').show():$('.merchant_money').hide();//商家余额
	power.store_list==1?$('.shop_mange').show():$('.shop_mange').hide();//店铺
	power.shop==1?$('.shop_list').show():$('.shop_list').hide();//快店
	power.mall==1?$('.mall_list').show():$('.mall_list').hide();//商城
	power.meal==1?$('.eat_list').show():$('.eat_list').hide();//餐饮
	power.group==1?$('.grounp_buy').show():$('.grounp_buy').hide();//团购
	power.appoint==1?$('.booking').show():$('.booking').hide();//预约
	power.hardware==1?$('.printer').show():$('.printer').hide();//打印机
    power.fans_send==1?$('.fun_group').show():$('.fun_group').hide();//粉丝群发
    power.wholesale==1?$('.wholesale_info').show():$('.wholesale_info').hide();//批发市场
	power.is_integral_mall==1?$('.integral_mall_info').show():$('.integral_mall_info').hide();//积分商城
	power.is_sell_order==1?$('.my_sell_wholesale_order').show():$('.my_sell_wholesale_order').hide();//销售订单
    power.is_buy_order==1?$('.my_buy_wholesale_order').show():$('.my_buy_wholesale_order').hide();//进货订单
	if(power.card!=1&&power.merchant_money!=1&&power.store_list!=1&&power.shop!=1&&power.mall!=1&&power.meal!=1&&power.group!=1&&power.appoint!=1&&power.hardware!=1&&power.fun_group!=1&&power.wholesale!=1&&power.is_integral_mall!=1){
		$('#Gallery').hide();
	}else{
		$('#Gallery').show();
	}
}

if(common.checkIphoneXApp()){
	$('#tabbar_margin_top').css('padding-bottom','94px');
	$('.mui-bar-tab').css('padding-bottom','34px');
	if(visitPage == 'plus'){
		$('.plus_class').css('bottom','84px');
	}
}



//点击进入用户评价
mui('.mui-bar-tab').on('tap','.shop_take',function(e){
	if(this.className.indexOf('mui-active') >= 0) {
		return false;
	} else {
		if(!common.checkApp()){
			location.replace('user_rating.html');
		}else{
			mui.openWindow({
				url: 'user_rating.html',
				id: 'user_rating'
			});
		}
	}		
});
//点击进入首页页面
mui('.mui-bar-tab').on('tap', '.shop_home', function(e) {
	if(this.className.indexOf('mui-active') >= 0) {
		return;
	} else {
		if(!common.checkApp()){
			location.replace('index.html');
		}else{
			mui.openWindow({
				url: 'index.html',
				id: 'index'
			});
		}
	}
});

//点击进入会员页面
mui('.mui-bar-tab').on('tap','.shop_plus',function(e){
	if(this.className.indexOf('mui-active') >= 0) {
		return;
	} else {
		if(!common.checkApp()){
			location.replace('plus.html');
		}else{
			mui.openWindow({
				url: 'plus.html',
				id: 'plus'
			});
		}
	}
});

//点击我的进入页面
mui('.mui-bar-tab').on('tap', '.shop_my', function(e) {
	if(this.className.indexOf('mui-active') >= 0) {
		return;
	} else {
		if(!common.checkApp()){
			location.replace('personal_center.html');
		}else{
			mui.openWindow({
				url: 'personal_center.html',
				id: 'personal_center'
			});
		}
	}
});


// 点击进入 批发市场首页
mui('.mui-bar-tab').on('tap', '.market_home', function(e) {
    if(this.className.indexOf('mui-active') >= 0) {
        return;
    } else {
        if(!common.checkApp()){
            location.replace('market_list.html');
        }else{
            mui.openWindow({
                url: 'market_list.html',
                id: 'market_list'
            });
        }
    }
	//清除缓存分类管理的页面的分类id
	sessionStorageRemoveItem('segmentedControlsId')
});
//点击分类进入页面
mui('.mui-bar-tab').on('tap', '.market_plus', function(e) {
    if(this.className.indexOf('mui-active') >= 0) {
        return;
    } else {
        if(!common.checkApp()){
            location.replace('market_category_management.html');
        }else{
            mui.openWindow({
                url: 'market_category_management.html',
                id: 'market_category_management'
            });
        }
    }
	sessionStorageRemoveItem('segmentedControlsId')
});

//点击购物车进入页面
mui('.mui-bar-tab').on('tap', '.market_take', function(e) {
    if(this.className.indexOf('mui-active') >= 0) {
        return;
    } else {
        if(!common.checkApp()){
            location.replace('market_cart.html');
        }else{
            mui.openWindow({
                url: 'market_cart.html',
                id: 'market_cart'
            });
        }
    }
	sessionStorageRemoveItem('segmentedControlsId')
});

//点击我的进入页面
mui('.mui-bar-tab').on('tap', '.market_my', function(e) {
    if(this.className.indexOf('mui-active') >= 0) {
        return;
    } else {
        if(!common.checkApp()){
            location.replace('personal_center.html');
        }else{
            mui.openWindow({
                url: 'personal_center.html',
                id: 'personal_center'
            });
        }
    }
	sessionStorageRemoveItem('segmentedControlsId')
});

var exitLayer = -1;
function appbackmonitor(){
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
		if (visitPage != undefined && (visitPage == 'bank_manger' || visitPage == 'add_bank')) {
			redirect('back')
		} else {
			window.lifepasslogin.closeWebView();
		}
	}else{
		if (common.getVersion() >= 1300) {
			window.pigcmspackapp.returnToHomePage();
			return false 
		}
		if(exitLayer != -1){
			window.pigcmspackapp.closewebview(2);
		}else{
			layer.closeAll();
			exitLayer = layer.open({
				content: '您确定要退出程序吗？再次按返回键将退出。'
				,btn: ['确定', '取消']
				,yes: function(index){
					window.pigcmspackapp.closewebview(2);
					layer.close(index);
				}
				,end: function(index){
					exitLayer = -1;
				}
			});
		}
	}
}


function sessionStorageRemoveItem(key){
	window.sessionStorage.removeItem(key)
}