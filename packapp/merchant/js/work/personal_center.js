mui.init();
var store_id=''; //全局
var store_index='';
var admin_realname=''
var admin_invit_code=''
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var config = common.getCache('config',true);
var admin_arr = [];
if(!config){
	common.http('Merchantapp&a=config', {}, function(data){
        console.log('data', data);
		config=data;
		common.setCache('config',config,true);
		config_loaded();
	});
}else{
	console.log(config);
	config_loaded();
}
function config_loaded(){ 
    if(config.open_merchant_change_phone && config.open_merchant_change_phone == '1'){
        $('.modify_phone').show();
    }
    console.log('merchant_recommend_wholesale_switch', config['merchant_recommend_wholesale_switch']);
    if(config.merchant_recommend_wholesale_switch && config.merchant_recommend_wholesale_switch == '1'){
        $('.my_spread_merchant').show();
    }

	//language
	if(config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0){
		for(var i in config.lang_config.lang_list){
			if(config.lang_config.lang_list[i].val == now_lang){
				$('#now_lang').html(config.lang_config.lang_list[i].display).show();
			}
		} 
        $('.fans_num').hide();
        $('.fun_group').hide();
        

	}
    if(config.is_switch_wholesale_mobile && config.is_switch_wholesale_mobile == '1'){
    	if (config.is_publish_market_goods!=1) {
            $('.my_sell_wholesale_order').show(); //我的批发销售订单
		}
        $('.my_buy_wholesale_order').show(); //我的批发进货订单
        $('.my_market_goods').show(); //我发布的商品
        $('.my_address').show(); //我的收货地址
        if (config.merchant_integral_mall_control==1) {
            $('.integral_mall_order').show(); //积分商城兑换订单
        }
    }

	if(config.buy_merchant_auth && config.buy_merchant_auth == '1'){
		$('.auth_package_buy').show();
	}
	if(config.site_phone){
		let arr = config.site_phone.split(' ')
			$.each(arr,function(index,item){
				$('#callPhones').prepend(`<div onclick="callPhones('${item}')">${item}</div>`)
			})
	}
	//联系客服
	if(config.site_phone){
		$('.contactPhone').show().click(function(){
			if(config.site_phone && config.site_phone.split(' ').length>1){
				$('#callPhones').show()
				$('#backgroundDiv').show()
			}else{
				location.href = 'tel:' + config.site_phone;
			}
			return false;
		});
    }
	$('#backgroundDiv,#quxiao').click(function(){
		$('#callPhones').hide()
		$('#backgroundDiv').hide()
	})
	//合同管理
	if (config.show_contract) {
		$('.contract').show();
	}
}
function callPhones(item){
	console.log(item);
	location.href = 'tel:' + item;
	$('#callPhones').hide()
	$('#backgroundDiv').hide()
}

function set_index_data(data){
	if(data.logo){
		$('.center img').attr('src',data.logo);
	}
	if (data && data.count_number) {
        if (data.count_number.todayordercount) {
            $('.today').text(data.count_number.todayordercount);
        }
        if (data.count_number.allordercount) {
            $('.alls').text(data.count_number.allordercount);
        }
        if (data.count_number.monthordercount) {
            $('.month').text(data.count_number.monthordercount);
        }
        if (data.count_number.fans_count) {
            $('.plus').text(data.count_number.fans_count);
        }
	}
	$('.names').text(data.name);
}

//首页主体数据
/*先初始化判断有没有缓存，有的话先展示缓存内容*/
var index_data = common.getCache('index_data',true);
if(index_data){
	set_index_data(index_data);
}
common.http('Merchantapp&a=index',{'client':client,noTip:1},function(data){
    console.log('data', data);
	common.setCache('index_data',data,true);
	if(data.invit_code!=''){
		$('.invit_code_admin').show();
		admin_invit_code  = data.invit_code;
		admin_realname  = data.admin_name;
		$('.admin_info').show();
		$('#selects').hide();
		$('.admin_set').hide();
	}else if(data.admin.length > 0){
		$('.invit_code_admin').show();
		$('.admin_set').show();
		$('#selects').show();
		$('.admin_info').hide();
		$.each(data.admin,function(index,val){
			admin_arr.push(val)
		})
	}
	if(data.cancel_app == 1){
		$('.cancel').show();
	}else{
		$('.cancel').hide();
	}
	if (data.merchant_buy_points_switch && data.merchant_buy_points_switch == 1) {
		$('.platform_score').show();
	} else {
        $('.platform_score').hide();
	}

	if (data.open_score_pay_back_mer && data.open_score_pay_back_mer == 1) {
		$('.system_score').show();
		$('.gift_order').show();
	} else {
        $('.system_score').hide();
        $('.gift_order').hide();
	}
    if (data.Merchant_type) {
		$('.merchant_type_info').html(data.Merchant_type.type_name);
        $('.merchant_type_info').show();
    } else {
        $('.merchant_type_info').hide();
    }

	//合同管理
	if (data.show_contract) {
		$('.contract').show();
	}else{
		$('.contract').hide();
	}

	set_index_data(data);
});  


//切换语言
(function($, doc) {
	$.init();
	$.ready(function() {
		
		if(config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0){		
			var lang_show_list = [];
			
			for(var i in config.lang_config.lang_list){
				lang_show_list.push({
					value: config.lang_config.lang_list[i].val,
					text: config.lang_config.lang_list[i].display
				});
			}
			var tab = now_lang;
			var languagePicker = new $.PopPicker();
			languagePicker.setData(lang_show_list);
			languagePicker.pickers[0].setSelectedValue(tab);
			var showUserPickerButton1 = doc.getElementById('now_lang');
			showUserPickerButton1.addEventListener('tap', function(event) {
				languagePicker.show(function(items) {
					if(items[0].value != now_lang){
						document.getElementById('now_lang').innerHTML = items[0].text;
						
						common.removeCache('all_lang',true);
						all_lang = null;
						now_lang = items[0].value;
						common.setCache('now_lang',now_lang,true);
						
						get_lang_list(items[0].value,'changeLanguageOk');
					}
				});
				replace_html_lang();
			}, false);
		}

	});
})(mui, document);

function changeLanguageOk(){
	common.http('Merchantapp&a=config', {}, function(data){
		common.setCache('config',data,true);
		common.setCache('ashop',data.shop_name);
		common.setCache('amall',data.mall_name);
		common.setCache('byin',data.meal_name);
		common.setCache('ctuan',data.group_name);
		common.setCache('dyu',data.appoint_name);
		common.setCache('jif',data.score_name);
		common.setCache('androidUrl',data.staff_android_url);
		common.setCache('iosUrl',data.storestaff_ios_download_url);
		common.setCache('androidBao',data.storestaff_android_package_name);
		common.setCache('iosBao',data.storestaff_ios_package_name);

        redirect_self('index.html');
	});
}



mui('body').on('tap','#selects',function(e){//订单号
	//普通示例
	var userPicker = new mui.PopPicker();

	userPicker.setData(
		admin_arr
	);

	userPicker.show(function(items) {
		document.getElementById('selectsText').innerHTML = items[0].text;
		selectsText=items[0].value;

		common.http('Merchantapp&a=set_invit_code', {'invit_code':items[0].value}, function(data){
			motify.log(L_("设置成功"));
			setTimeout(location.reload(), 3000);
		});
	});
});


//修改密码
mui('.mui-content').on('tap','.modify_password',function(e){
	openWindow({
		url:'modify_password.html',
		id:'modify_password'
	});
});

mui('.mui-content').on('tap','.modify_phone',function(e){
	openWindow({
		url:'modify_phone.html',
		id:'modify_phone'
	});
});

mui('.mui-content').on('tap','.invit_code_admin',function(e){
	if(admin_realname!=undefined && admin_invit_code!=undefined && admin_realname!='' && admin_invit_code!=''){
		
		mui.alert('业务员名称：'+admin_realname+'</br>'+'邀请码：'+admin_invit_code,'绑定业务员','知道了',function(e){
			
		})
	}else{
		openWindow({
			url:'invit_code.html',
			id:'invit_code'
		});
	}

});

mui('.mui-content').on('tap','.clear_cache',function(e){
    if(common.checkApp()){
        if(common.checkAndroidApp()){
            window.pigcmspackapp.clearcache('clearFunc');
        }else{
            common.iosFunction('clearcache/clearFunc',{name:'clearcache',callback:'clearFunc'});
        }
    }else{
        console.log('applicationCache.update');
        try{
            applicationCache.update();
        }catch(err){
            console.log(err);
        }
        motify.log(L_('清空缓存成功.'));
    }
});


function clearFunc() {
    motify.log(L_('清空缓存成功.'));
}

mui('.mui-content').on('tap','.sign_out',function(e){
	var btnArray = [L_('否'), L_('是')];
	mui.confirm(L_('您确认要退出登录吗？'), L_('退出登录'), btnArray, function(e){
		if(e.index == 1){
			common.removeCache('ticket');
			common.removeCache('ticket',true);
			common.removeCache('config');
			common.removeCache('config',true);
			redirect_self('login.html');
		}
	})
});
mui('.mui-content').on('tap','.cancel',function(e){
	common.http('Merchantapp&a=merchant_money_info', {'client':client}, function(data){
		var btnArray = [L_('否'), L_('是')];
		mui.confirm(L_('您当前剩余余额为：'+data.merchant_money_num+'元，商家注销后不可恢复，确认是否注销？'), L_('注销商家'), btnArray, function(e){
			if(e.index == 1){
				common.http('Merchantapp&a=cancel', {}, function(data){
					if(data){
						common.removeCache('ticket');
						common.removeCache('ticket',true);
						common.removeCache('config');
						common.removeCache('config',true);
						redirect_self('login.html');
					}else{
						motify.log(L_('注销商家失败.'));
					}
				});
			}
		})
	});
});
//权限套餐购买
mui('.mui-content').on('tap','.auth_package_buy',function(e){
	openWindow({
		url:'auth_package_buy.html',
		id:'auth_package_buy'
	});
});
//我发布的商品
mui('.mui-content').on('tap','.my_market_goods',function(e){
    openWindow({
        url:'my_market_goods.html',
        id:'my_market_goods'
    });
});
//我的批发销售订单
mui('.mui-content').on('tap','.my_sell_wholesale_order',function(e){
    openWindow({
        url:'my_sell_wholesale_order.html',
        id:'my_sell_wholesale_order'
    });
});
//我的批发进货订单
mui('.mui-content').on('tap','.my_buy_wholesale_order',function(e){
    openWindow({
        url:'my_buy_wholesale_order.html',
        id:'my_buy_wholesale_order'
    });
});
//积分商城兑换订单
mui('.mui-content').on('tap','.integral_mall_order',function(e){
    openWindow({
        url:'integral_mall_order.html',
        id:'integral_mall_order'
    });
});
//我的地址
mui('.mui-content').on('tap','.my_address',function(e){
    openWindow({
        url:'my_address.html',
        id:'my_address'
    });
});
//我推广的商家点击
mui('.mui-content').on('tap','.my_spread_merchant',function(e){
    openWindow({
        url:'my_spread_merchant.html',
        id:'my_spread_merchant'
    });
});

//打印机管理点击
mui('.mui-content').on('tap','.printer',function(e){
	openWindow({
		url:'printer.html',
		id:'printer'
	});
});

//粉丝群发点击
mui('.mui-content').on('tap','.fun_group',function(e){
	openWindow({
		url:'fun_group.html',
		id:'fun_group'
	});
});


//银行卡管理
mui('.mui-content').on('tap','.bank_card',function(e){
	openWindow({
		url:'bank_manger.html',
		id:'bank_manger'
	});
});


//商家平台积分记录管理
mui('.mui-content').on('tap','.platform_score',function(e){
    openWindow({
        url:'platform_score_record.html',
        id:'platform_score_record'
    });
});

//平台商家积分记录管理
mui('.mui-content').on('tap','.system_score',function(e){
    openWindow({
        url:'system_score.html',
        id:'system_score'
    });
});

//积分商城订单
mui('.mui-content').on('tap','.gift_order',function(e){
    openWindow({
        url:'gift_order.html',
        id:'gift_order'
    });
});

//商家合同
mui('.mui-content').on('tap','.contract',function(e){
	openWindow({
		url:'contract.html',
		id:'contract'
	});
});

if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}

//隐私协议
mui('.mui-content').on('tap','#goAgreement',function(e){
	openWindow({
		url:'agreement.html',
		id:'agreement'
	});
});

mui('.mui-content').on('tap','#goUserAgreement',function(e){ 
	openWindow({
		url:'userAgreement.html',
		id:'userAgreement'
	});
});  

var privacy_policy_md5='';   
if(common.checkApp()){   
	common.http('Merchantapp&a=get_privacy_policy',{noTip:true}, function(data){
	  privacy_policy_md5=data.privacy_policy_md5; 
	   if(data.privacy_policy){ 
		$('.fixedAgreement').show();
	  } 
   })   
}