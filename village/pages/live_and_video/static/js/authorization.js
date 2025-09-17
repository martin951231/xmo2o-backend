const common = require('../../../../utils/common.js');
const authorization = {
	checkAuthen: (type, url_link) => {
		let productBox = uni.getStorageSync('productBox') || ''
		console.log(productBox,'productBox')
		if (productBox) {
			uni.removeStorageSync('productBox')
		}
						
		common.post('Live&a=check_authen',{type}, data => { 
			 
			if(data.check_result ==1){
				//#ifdef H5
					authorization.go_download_app(url_link);
				//#endif
				
				//#ifdef MP-WEIXIN
					if(type =='liveshow'){
						uni.navigateTo({
							url: '/pages/live_and_video/live/creat_live'
						})
					}else{
						uni.navigateTo({
							url: './video/Author/take_video'
						})
					}
				//#endif
			}else if(data.check_result ==3){//未已实名认证
				uni.showModal({
					title: '实名认证',
					content: type == 'liveshow'?'首次使用直播需要你的实名认证哦！':'首次拍摄视频需要你的实名认证哦！',
					cancelText:'取消',
					cancelColor:'#999999',
					confirmText:'去认证',
					confirmColor:'#FF492D', 
					success:  (res) => {
						if (res.confirm) {
							console.log('用户点击确定');
							authorization.go_authentication()
						} else if (res.cancel) {
							console.log('用户点击取消');
						}
					}
				});
			  }else if(data.check_result ==2){//审核中
				 uni.showModal({
					  title: '实名认证',
					  content: '你的实名认证还在审核中，请耐心等待哦！', 
					  showCancel: false,
					  confirmText:'知道了',
					  confirmColor:'#FF492D', 
					  success:  (res) => {
						  if (res.confirm) {
							  console.log('用户点击确定'); 
						  } else if (res.cancel) {
							  console.log('用户点击取消');
						  }
					  }
				 });
			  }else if(data.check_result ==4){//审核失败
				 uni.showModal({
					title: '实名认证',
					content: '你的实名认证未通过，请重新认证！',
					cancelText:'取消',
					cancelColor:'#999999',
					confirmText:'去认证',
					confirmColor:'#FF492D', 
					success:  (res) => {
						if (res.confirm) {
							console.log('用户点击确定');
							authorization.go_authentication()
						} else if (res.cancel) {
							console.log('用户点击取消');
						}
					}
				 });
			  }
		})
		
						
	},
	go_download_app: function(url_link){
		if(common.$checkPlat.checkAndroid()){
			if(url_link.android_app_url){
				common.parseUrl(url_link.android_app_url);
			}else{
				uni.showModal({
					content: 'H5网页暂时无法使用',
					showCancel: false
				})
				return ;
			}
		}else{
			if(url_link.ios_app_url){
				common.parseUrl(url_link.ios_app_url);
			}else{
				uni.showModal({
					content: 'H5网页暂时无法使用',
					showCancel: false
				})
				return ;
			}
		}
	},
	go_authentication: function(){
		// 实名认证
		uni.navigateTo({
			url: '/pages/my/authentication'
		})
	},
}

export default authorization