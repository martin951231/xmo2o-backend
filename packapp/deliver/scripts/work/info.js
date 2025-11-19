$(document).ready(function(){
	var indexData = common.getCache('indexData',true);
	
	if(!indexData){
		location.href = 'index.html';
	}
	
	if(common.checkWeixin()){
		$('.MyEx').css('min-height',$(window).height());
		common.fillPageBg(2,['#289ffd','#f4f4f4']);
	}
	
	if(common.checkIphoneXApp()){
		$('.bottom').css('padding-bottom','34px');
		$('.Statistics .Statistics_end').css('margin-bottom','84px');
	}
	
	$('.MyEx_top span.bjt').css('background-image','url(' + ( indexData.deliver_info.is_system ? indexData.deliver_info.system_image : indexData.deliver_info.store_image ) + ')');
	indexData.deliver_info.tip = indexData.deliver_info.is_system ? L_('系统配送员') : L_('商家配送员');
	common.setData(indexData);
		
	$('.peiEvelaue').click(function(){
	    openWebviewUrl('reply.html');
	});
	common.http('Deliver&a=new_info',{}, function(data){ 
	    $('#scoreWidth').width(data.score_width);
		common.setData(data); 
		if (data.open_deliver_level == '1') {
			$('#initateKnight').show();
		}else{
			$('#initateKnight').hide();
		}
	});
	
	$('.Setup').click(function(){
		/*启动监听APP退出事件*/
		if(common.checkApp()){
			setInterval(function(){
				var isLogout = common.getCache('isLogout',true);
				if(isLogout){
					common.removeCache('isLogout',true);
					location.href = 'login.html';
				}
			},300);
		}
		var href = 'setting.html';		
		openWebviewUrl(href);
		return false;
	});

	$('#initateKnight').click(function(){ 
		openWebviewUrl('myGrade.html');
	})
});

var exitLayer = -1;
function appbackmonitor(){
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
		window.lifepasslogin.closeWebView();
	}else{
		if (common.getVersion() >= 1300) {
			window.pigcmspackapp.returnToHomePage();
		}else if(exitLayer != -1){
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