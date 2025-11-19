var itemArr = [];
var android_baidumap_has = false;
var android_baidumap_loading = true;

var android_gaodemap_has = false;
var android_gaodemap_loading = true;

var android_tencentmap_has = false;
var android_tencentmap_loading = true;

var android_googlemap_has = false;
var android_googlemap_loading = true;

var ios_baidumap_has = false;
var ios_baidumap_loading = true;

var ios_gaodemap_has = false;
var ios_gaodemap_loading = true;

var ios_tencentmap_has = false;
var ios_tencentmap_loading = true;

var ios_googlemap_has = false;
var ios_googlemap_loading = true;

var nowMapLabel = null;
$(document).ready(function(){
	
	if(common.checkWeixin()){
		common.fillPageBg(1,'#f4f4f4');
	}
	
	nowMapLabel = common.getCache('navigation_type');
	if(typeof nowMapLabel == 'object'){
		$('.navigation .enquire').html(nowMapLabel.text);
	}else{
		$('.navigation .enquire').html(L_('询问'));
	}
	
	var DeliverUser = common.getCache('deliver_user',true);
	if(common.checkWeixin()){
		if(urlParam.openid){
			if(urlParam.openid != DeliverUser.openid){
				DeliverUser.openid = urlParam.openid;
				common.setCache('deliver_user',DeliverUser,true);
				common.http('Deliver&a=save_openid',{openid:DeliverUser.openid}, function(data){
					motify.log(L_('已成功绑定微信'));
				});
			}
		}
		if(DeliverUser.openid){
			$('.openid .enquire').html(L_('已绑定'));
		}else{
			$('.openid .enquire').html(L_('未绑定'));
		}
		$('.openid').show();
	}
	
	if(!DeliverUser.is_notice || DeliverUser.is_notice == 0){
		$('.notice .enquire').html(L_('接收'));
	}else{
		$('.notice .enquire').html(L_('不接收'));
	}
  
	var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
	common.http('Deliver&a=login',{noTip:true, 'client':client}, function(data){
		if(data.device_id){
			common.setCache('device_id',data.device_id,true);
		}
		common.setCache('ticket',data.ticket,true);
		common.setCache('deliver_user',data.user,true);
		if(data.user.is_notice == 0){
  		$('.notice .enquire').html(L_('接收'));
  	}else{
  		$('.notice .enquire').html(L_('不接收'));
  	}
	});
  
	$('.notice').click(function(event){
		var itemArr = [L_('接收'),L_('不接收')];
		common.actionsheet({
			itemArr:itemArr,
			showCancel:false,
			success:function(index){
				common.http('Deliver&a=save_notice',{is_notice:index}, function(data){
					DeliverUser.is_notice = index;
					common.setCache('deliver_user',DeliverUser,true);
					$('.notice .enquire').html(itemArr[index]);
				});
			}
		});
	});
	
	var lang_list = common.getCache('lang_list',true);
	if(lang_list && lang_list.length > 0){
		for(var i in lang_list){
			if(lang_list[i].val == now_lang){
				$('.language .enquire').html(lang_list[i].display);
				$('.language').show();
			}
		}
	}
	$('.language').click(function(event){
		var itemArr = [];
		for(var i in lang_list){
			itemArr[lang_list[i].val] = lang_list[i].display;
		}
		common.actionsheet({
			itemArr:itemArr,
			showCancel:false,
			success:function(index){
				$('.language .enquire').html(itemArr[index]);
				common.removeCache('all_lang',true);
				all_lang = null;
				now_lang = index;
				common.setCache('now_lang',now_lang,true);

				get_lang_list(index,'changeLanguageOk');
			}
		});
	});
	
	
	if(common.checkAndroidApp()){
		//判断安卓百度地图
		window.pigcmspackapp.judgeappexist('com.baidu.BaiduMap','android_check_baidumap_callback');
		//判断安卓高德地图
		window.pigcmspackapp.judgeappexist('com.autonavi.minimap','android_check_gaodemap_callback');
		//判断安卓腾讯地图
		window.pigcmspackapp.judgeappexist('com.tencent.map','android_check_tencentmap_callback');
		//判断安卓谷歌地图
		window.pigcmspackapp.judgeappexist('com.google.android.apps.maps','android_check_googlemap_callback');
	}else if(common.checkIosApp()){
		//判断IOS百度地图
		common.iosFunction('judgeappexist/baidumap/ios_check_baidumap_callback', 
			{name: 'judgeappexist', url_schemes:'baidumap', callback:'ios_check_baidumap_callback'});
		//判断IOS高德地图
		common.iosFunction('judgeappexist/iosamap/ios_check_gaodemap_callback', 
			{name: 'judgeappexist', url_schemes:'iosamap', callback:'ios_check_gaodemap_callback'});
		//判断IOS腾讯地图
		common.iosFunction('judgeappexist/qqmap/ios_check_qqmap_callback',
			{name: 'judgeappexist', url_schemes:'qqmap', callback:'ios_check_qqmap_callback'});
		//判断IOS谷歌地图
		common.iosFunction('judgeappexist/comgooglemaps/ios_check_googlemap_callback',
			{name: 'judgeappexist', url_schemes:'comgooglemaps', callback:'ios_check_googlemap_callback'});
	}
	
	$('.navigation').click(function(event){
		itemArr = [];
		if(common.checkIosApp()){
			itemArr.push({text:L_('苹果地图'),label:'ios_map'});
			if(ios_baidumap_has){
				itemArr.push({text:L_('百度地图'),label:'ios_baidu'});
			}
			if(ios_gaodemap_has){	
				itemArr.push({text:L_('高德地图'),label:'ios_gaode'});
			}
			if(ios_tencentmap_has){
				itemArr.push({text:L_('腾讯地图'),label:'ios_tencent'});
			}
			if(ios_googlemap_has){
				itemArr.push({text:L_('谷歌地图'),label:'ios_tencent'});
			}
		}else if(common.checkAndroidApp()){
			if(android_baidumap_has){
				itemArr.push({text:L_('百度地图'),label:'android_baidu'});
			}
			if(android_gaodemap_has){	
				itemArr.push({text:L_('高德地图'),label:'android_gaode'});
			}
			if(android_tencentmap_has){
				itemArr.push({text:L_('腾讯地图'),label:'android_tencent'});
			}
			if(android_googlemap_has){
				itemArr.push({text:L_('谷歌地图'),label:'android_googlemap'});
			}
		}else if(common.checkWeixin()){
			itemArr.push({text:L_('微信内置地图'),label:'weixin'});
		}else{
			itemArr.push({text:L_('百度地图wap'),label:'browser'});
		}
		if(itemArr.length == 0){
			layer.open({
				content: '请先安装地图类软件 !'
				,btn: [ L_('确定')]
				,yes: function(index){ 
					layer.close(index);
				}
			});
		}else{
			common.actionsheet({
				itemArr:itemArr,
				success:function(obj){
					nowMapLabel = obj;
					common.setCache('navigation_type',obj);
					$('.navigation .enquire').html(obj.text);
				}
			});
		}
		
		event.stopPropagation();
	});
	$('.openid').click(function(event){
		layer.open({
			content: DeliverUser.openid ? L_('您确认前往重新授权绑定微信？') : L_('您确认前往绑定微信？')
			,btn: [L_('前往'), L_('取消')]
			,yes: function(index){
				location.href = window.location.protocol + '//' + requestDomain + '/wap.php?c=Packapp&a=bind&referer=' + encodeURI(location.href);
				layer.close(index);
			}
		});
	});
	$('.cache').click(function(event){
		console.log('applicationCache.update');
		try{
			applicationCache.update();
		}catch(err){
			console.log(err);
		}
		motify.log(L_('清空缓存成功.'));
	});
	$('#logout').click(function(){
		layer.open({
			content: L_('您确定要退出吗？')
			,btn: [L_('确定'), L_('取消')]
			,yes: function(index){
				common.http('Deliver&a=logout', {}, function(data){
					common.removeCache('ticket');
					common.removeCache('ticket',true);
					if(common.checkApp()){
						common.setCache('isLogout','true',true);
						if(common.checkAndroidApp()){
							window.pigcmspackapp.closewebview(2);
						}else{
							common.iosFunction('closewebview/2', {name: 'closewebview', type: '2'});
						}
					}else{
						redirect_self('login.html');
					}
					layer.close(index);
				});
			}
		});
	});
	
	
	//联系客服
	var indexData = common.getCache('indexData',true);
	if(indexData.site_phone && indexData.site_phone != ''){
		$('.contactPhone .enquire').html(indexData.site_phone).click(function(){
			location.href = 'tel:' + indexData.site_phone;
		});
	}else{
		$('.contactPhone').hide();
	}
});

function changeLanguageOk(){
	location.reload();
}

//安卓
function android_check_baidumap_callback(text){
	if(text == '1'){
		android_baidumap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'android_baidu'){
			removeMapCache();
		}
	}
	android_baidumap_loading = false;
}
function android_check_gaodemap_callback(text){
	if(text == '1'){
		android_gaodemap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'android_gaode'){
			removeMapCache();
		}
	}
	android_gaodemap_loading = false;
}
function android_check_tencentmap_callback(text){
	if(text == '1'){
		android_tencentmap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'android_tencent'){
			removeMapCache();
		}
	}
	android_tencentmap_loading = false;
}
function android_check_googlemap_callback(text){
	if(text == '1'){
		android_googlemap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'android_google'){
			removeMapCache();
		}
	}
	android_googlemap_loading = false;
}



//IOS
function ios_check_baidumap_callback(text){
	if(text == '1'){
		ios_baidumap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'ios_baidu'){
			removeMapCache();
		}
	}
	ios_baidumap_loading = false;
}
function ios_check_gaodemap_callback(text){
	if(text == '1'){
		ios_gaodemap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'ios_gaode'){
			removeMapCache();
		}
	}
	ios_gaodemap_loading = false;
}
function ios_check_qqmap_callback(text){
	if(text == '1'){
		ios_tencentmap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'ios_tencent'){
			removeMapCache();
		}
	}
	ios_tencentmap_loading = false;
}
function ios_check_googlemap_callback(text){
	if(text == '1'){
		ios_googlemap_has = true;
	}else{
		if(typeof nowMapLabel == 'object' && nowMapLabel.label == 'ios_google'){
			removeMapCache();
		}
	}
	ios_googlemap_loading = false;
}

function removeMapCache(){
	common.removeCache('navigation_type');
	$('.navigation .enquire').html(L_('询问'));
}
var privacy_policy_md5=''; 
 if(common.checkApp()){
	$('.p_ban').html('v.'+common.getVersionName()) 
	common.http('Deliver&a=get_privacy_policy',{noTip:true}, function(data){ 
		privacy_policy_md5=data.privacy_policy_md5; 
		if(data.privacy_policy){
		  $('.fixedAgreement').show();  
		} 
   }) 
 }

 $('#goAgreement').on("click",function(){
    openWebviewUrl('agreement.html');
}); 
$('#goUserAgreement').on("click",function(){
    openWebviewUrl('userAgreement.html');
}); 

   