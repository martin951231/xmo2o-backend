
//分享
$('#inviteBtn,#shareBox').click(function(){
	if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
		var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)',shadeClose:false});
		$.post(getShareUrl,function(result){
			layer.close(layerLoadTip);
			if(result.status == 1){
				var wxappUrl = '/pages/index/share?shareImg='+encodeURIComponent(result.info.friend_img)+'&sharePath='+encodeURIComponent(result.info.wxapp_path)+'&poster='+encodeURIComponent(result.info.group_img)+'&shareTitle='+encodeURIComponent(result.info.share_title)+'&title='+encodeURIComponent(result.info.title)+'&bg='+encodeURIComponent(result.info.good_img);
				console.log('wxapp_share_url',wxappUrl);
				wx.miniProgram.navigateTo({
					url:wxappUrl
					,fail:function(res){
						layer.open({
							content: '打开分享失败，请重试'
							,btn: '好的'
						});
					}
				});
			}else{
				layer.open({
					content: result.info
					,btn: '好的'
				});
			}
		});
	}else if(typeof wx !=='undefined'){
        $(".detail_mask").show();
        $(".guide").show();
		// layer.open({
		// 	content: '请使用微信右上角分享功能'
		// 	,btn: '好的'
		// });
	}else if(is_app_browser) {
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            iosCommonNew.iosFunction('shareWeChat', {name:'shareWeChat', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }else{
            window.lifepasslogin.shareWeChat(window.shareData.tTitle,window.shareData.tContent,window.shareData.imgUrl,window.shareData.sendFriendLink);
        }
    }else{
		layer.open({
			content: '请使用浏览器分享功能'
			,btn: '好的'
		});
	}
});


//拼团倒计时
if($('.pinDetail .time').size() > 0){
    pin_time();
    setInterval(function(){
        pin_time();
    },1000);
}
function pin_time(){
    $('.pinDetail .time').each(function(i,item){
        var time = parseInt($(item).data('time'));
        if(time <= 0){
            $(item).closest('.item').remove();
        }else{
            var day = Math.floor(time / 86400);
            var hour = Math.floor(time % 86400 / 3600);
            var minutes = Math.floor(time % 86400 % 3600 / 60);
            var seconds = Math.floor(time % 86400 % 3600 % 60);
            var html = '';
            if(day > 0){
                html+= day+'天';
            }
            if(hour > 0){
                html+= (hour < 10 ? '0' : '')+hour+':';
            }
            if(minutes > 0){
                html+= (minutes < 10 ? '0' : '')+minutes+':';
            }
            if(html != ''){
                html+= (seconds < 10 ? '0' : '')+seconds;
            }else{
                html+= (seconds < 10 ? '0' : '')+seconds+'秒';
            }
            $(item).data('time',time-1).html(html);
        }
    });
}