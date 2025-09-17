var live_common = {};  
import common from "../../../../utils/common.js"  
var app = getApp();  

live_common.getRemoteJs = (url) => {
	console.log('开始引入tim.js')
	return new Promise((resolve, reject) => {

		var _doc = document.getElementsByTagName('head')[0];
		var js = document.createElement('script');
		js.setAttribute('type', 'text/javascript');
		js.setAttribute('src', url);
		_doc.appendChild(js);
		
		if (document.all) { //如果是IE
			js.onreadystatechange = function () {
				if (js.readyState == 'loaded' || js.readyState == 'complete') {
					resolve()
				}
			}
		} else {
			js.onload = function () {
			    resolve()
			}
	    }
	})
}
 
live_common.goBuy = (option) => {  //去购买 
	var Param = {
		noTip:false,
		live_id: app.globalData.live_id,
		goods_id:option
	}
	common.post('Live&a=goto_buy',Param, data => {  
		if(data.type == 'shop'){ //快店 
			uni.navigateTo({
				url:"/pages/shop_new/shopDetail/shopDetail?store_id="+data.shop_redirect_info.store_id+'&sort_id='+data.shop_redirect_info.cate_id + '&goods_id='+data.shop_redirect_info.goods_id + '&live_id='+data.live_id
			}) 
		}else{
			common.parseUrl(data.redirect_url);
		} 
	})
}

live_common.shareData = (option,callFun) => {  //直播分享
	var Param = {
		live_id: option
	}
	common.post('Live&a=share',Param, data => { 
		  callFun(data)
	})  
}

live_common.clickCare = (classType,option,type,callFun) => { //关注 取消    
	var Param = {}  
	if(classType == 'live_id'){
		Param = {
			uid:'',
			type:type,
			live_id: option
		}
	}else if(classType == 'video_id'){
		
		Param = {
			uid:'',
			type:type,
			video_id: option,
			noTip:true
		}
	}  
	common.post('Live&a=follow_live',Param, data => { 
		if(type == 2){
			uni.showToast({
				title: '取消关注成功',
				icon: 'none',
				duration: 2000
				
			});
		}else if(type == 1){
			uni.showToast({
				title: '关注成功',
				icon: 'none',
				duration: 2000
			});
		}
		callFun(data) 
	})  
} 

module.exports = live_common 