$(document).ready(function(){  
	if(common.getCache('isStarted',true)){
		$('#mainPage').show();
		$('#startBg').hide();
		$(".group_list a").each(function(){
			$(this).height($(this).width()*0.94);
		});
	}else{
		common.setCache('isStarted','true',true);
		$('#startBg img').css({height:$(window).height(),width:$(window).width()});
		$('#startBg').show();
		$('#mainPage').hide();
	}
	
	if(common.checkIosApp()){
		$('#biz-map').height($(window).height()-267-64);
		if(common.checkIphoneXApp()){
			$('.bottom').css('padding-bottom','34px');
			$('.map_location').css('bottom','114px');
			$('#biz-map').height($('#biz-map').height()-34);
		}
		common.iosFunction('changecolor/#289FFD', {name: 'changecolor', color_value: '#289FFD'});
	}else if(common.checkAndroidApp()){
		$('#biz-map').height($(window).height()-267-44);
		window.pigcmspackapp.changecolor('#289FFD');
	}else{
		$('#biz-map').height($(window).height()-267);
	}
	
	if(common.checkWeixin()){
		common.fillPageBg(1,'#f4f4f4');
	}
	
	if(common.checkLogin() == false){
		return false;
	}else{
		if (common.getVersion() >= 1300) {
			common.appFunction({name:"closeStartupPage"});
		}
		var DeliverUser = common.getCache('deliver_user',true);
		if(DeliverUser){
			indexData(DeliverUser); 
			//预加载本地资源
			var localData = common.getCache('indexData',true);
			if(localData){
				$('.clerk_img span').css('background-image','url(' + localData.deliver_info.logo + ')');
				common.setData(localData);
			}
			deliver_login(false);
		}else{
			deliver_login(true);
		}
	}
	
	$('#map_location').click(function(){ 
		map.panTo(mk.getPosition());
	});
	
	$('#work_handle').click(function(){
		var is_notice_tmp = $('#work_toggler').prop('checked') ? '1' : '0';
		common.http('Deliver&a=save_notice',{is_notice: is_notice_tmp}, function(data){
			motify.log(is_notice_tmp == 1 ? L_('您已关闭接单') : L_('您已开启接单'));
      deliver_login(false);
		});
	});
     
});

function deliver_login(type){
	var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
	common.http('Deliver&a=login',{noTip:true, 'client':client}, function(data){
		common.setCache('ticket',data.ticket,true);
		common.setCache('deliver_user',data.user,true);
		
		if(type){
			indexData(data.user);
		}else{
			deliver_work(data.user.is_notice);
		}
	},function(data){
		common.removeCache('ticket');
		common.removeCache('ticket',true);
		redirect_self('login.html');
	});
}

function deliver_work(is_notice){
	if(is_notice == '0'){
		$('#work_tip').html(L_('接单中'));
		$('#work_toggler').prop('checked',true);
	}else{
		$('#work_tip').html(L_('未接单'));
		$('#work_toggler').prop('checked',false);
	}
	$('#work_handle').show();
}

var map=null,mk=null,index_data=null;
function indexData(DeliverUser){
	
	deliver_work(DeliverUser.is_notice);
	
	common.http('Deliver&a=new_index',{noTip:true}, function(data){

		if (data.open_multilingual == 1 && data.lang_config && data.lang_config.lang_list && data.lang_config.lang_list.length > 0) {
			common.setCache('lang_list',data.lang_config.lang_list,true);
			get_lang_list(data.lang_config.now_lang);
		}

		data.deliver_info.logo = data.deliver_info.is_system ? data.deliver_info.system_image : data.deliver_info.store_image;
		if(data.deliver_info.is_system){
			data.deliver_info.tip =  L_('系统配送员');
			if (common.checkWeixin()) {
				$('#indexScanOrder').show();
			}
		}else{
			data.deliver_info.tip = L_('商家配送员') + data.deliver_info.store_num_show;
			$('#indexScanOrder').hide();
		}
		//data.deliver_info.tip = data.deliver_info.is_system ? L_('系统配送员') : (L_('商家配送员') + ' ' + data.deliver_info.store_name);
		index_data = data;
		$('.clerk_img span').css('background-image','url(' + data.deliver_info.logo + ')');
		common.setData(data);
		
		if(data.deliver_info){
			common.setCache('indexData',data,true);
		}
		
		//请求完页面参数判断是否需要直接跳转，例如页面列表，或订单详情。
		if(urlParam.gopage && !common.getCache('isGoOtherPage',true)){
			var href = location.protocol+'//'+location.host+'/packapp/'+visitWork+'/'+urlParam.gopage+'.html' + (urlParam.goparam ? '?'+urlParam.goparam : '');
		
			if(common.checkApp()){
				if(common.checkAndroidApp()){
					window.pigcmspackapp.createwebview(href);
				}else{
					var iosHref = window.btoa(href);
					iosHref = iosHref.replace('/','&');
					common.iosFunction('createwebview/'+iosHref, {name:'createwebview', url:iosHref});
				}
			}else{
				common.setCache('isGoOtherPage','true',true);
				location.href = href;
				return false;
			}
		}
		
		$('#mainPage').show();
		$('#startBg').hide();
		
		if(typeof(is_google_map) == 'string' && is_google_map != "" && is_google_map.length > 5){ 
			$.getScript('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key='+is_google_map+'&callback=initialize_map');
		}else{
			$.getScript('https://api.map.baidu.com/api?v=2.0&ak=' + baidu_map_key + '&callback=initialize_map');
		}
		locationEvent();
		setInterval('locationEvent()',10000);	//10秒轮询上报一次
		setInterval('pollorder()',10000);	//10秒一次轮询新订单
        setInterval('initialize_map(0)',30000);
        
		if(common.getVersion() >= 1400){
			var reportLocationParam = {
				url: requestUrl + 'Deliver&a=location',
				param:{
					device_id: common.getDeviceId(),
					ticket: common.getCache('ticket',true),
					app_version: common.getVersion(),
				},
				lngName: 'lng',
				latName: 'lat',
				time: 10
			};
			if(common.checkAndroidApp()){
				if(is_google_map){
					window.pigcmspackapp.reportLocationGoogle(JSON.stringify(reportLocationParam));
				}else{
					window.pigcmspackapp.reportLocation(JSON.stringify(reportLocationParam));
				}
				if(window.pigcmspackapp.isOpenFloatWindow() === false || window.pigcmspackapp.isOpenFloatWindow() == 'false'){
					window.pigcmspackapp.openFloatWindow();
				}
			}else if(common.checkIosApp()){
				if(is_google_map){
					common.iosFunction('reportLocationGoogle/' + JSON.stringify(reportLocationParam), {name: 'getlocationByGoogle', param: JSON.stringify(reportLocationParam)});
				}else{
					common.iosFunction('reportLocation/' + JSON.stringify(reportLocationParam), {name: 'getlocation', param: JSON.stringify(reportLocationParam)});
				}
			}
		}
		
	},function(data){
		if(data.errorCode == '20030102' || data.errorCode == '20030101'){
			layer.open({
				content: data.errorMsg
				,btn: ['确定']
				,yes: function(index){
					common.removeCache('ticket');
					common.removeCache('ticket',true);
					layer.close(index);
					redirect_self('login.html');
				}
			});
		}else{
			motify.log(data.errorMsg);
		}
	});
	
	if(common.checkIos()){
		console.log('IOS判断');
		window.addEventListener('touchstart',loadTipMp3, false);
	}else{
		loadTipMp3();
	}
}

var isLoaded = false;
function loadTipMp3(){
	if(isLoaded == false){
		console.log('加载音乐');
		var myVideo=document.getElementById("newOrderMp3");
		myVideo.load();
		isLoaded = true;
	}
}

var newOrderTipIndex = -2;
function pollorder(){
	common.http('Deliver&a=new_index',{noTip:true,poll:1}, function(data){
		common.setData(data);
	});
}

function locationEvent(){ 
	if(common.checkIosApp()){
		if(is_google_map){
			common.iosFunction('getlocationByGoogle/locationOk', {name: 'getlocationByGoogle', callback: 'locationOk'});
		}else{
			common.iosFunction('getlocation/locationOk', {name: 'getlocation', callback: 'locationOk'});
		}
	}else if(common.checkAndroidApp()){
		if(is_google_map){
			window.pigcmspackapp.getlocationByGoogle('locationOk');
		}else{
			window.pigcmspackapp.getlocation('locationOk');
		}
	}else if(mk != null){
		if(is_google_map != ""){
            if (navigator.geolocation){
                navigator.geolocation.getCurrentPosition(function(position) { 
                    
                	if(position){
                        var pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        common.setCache('lastAddress',pos,true);
                        map.panTo(pos);
                        mk.setPosition(pos);
                        map.setCenter(pos);
                        locationReport(pos.lng,pos.lat);
					}else{
                        motify.log('failed');
					}
                })
            }
		}else{
            var geolocation = new BMap.Geolocation();
            geolocation.getCurrentPosition(function(r){
                if(this.getStatus() == BMAP_STATUS_SUCCESS){
                    common.setCache('lastAddress',r.point,true);
                    map.panTo(r.point);
                    mk.setPosition(r.point);
                    locationReport(r.point.lng,r.point.lat);
                }else{
                    motify.log('failed'+this.getStatus());
                }
            },{enableHighAccuracy:true});
		}

	}
}

function locationOk(name,lng,lat){
	if(is_google_map){
		lat = lng;
		lng = name;
		name = '';
	}
	if(mk != null){
        if(typeof(is_google_map) == 'string' && is_google_map != "" && is_google_map.length > 5){  
            var point = new google.maps.LatLng(lat,lng)
        }else{
        	var point = new BMap.Point(lng,lat);
        } 
	    map.panTo(point);
		mk.setPosition(point);
		locationReport(lng,lat);
	}else{
		var locationMapTimer = setInterval(function(){
			if(mk != null){
				clearInterval(locationMapTimer);
                if(typeof(is_google_map) == 'string' && is_google_map != "" && is_google_map.length > 5){  
                    var point = new google.maps.LatLng(lat,lng)
                }else{
                	var point = new BMap.Point(lng,lat); 
                }
				map.panTo(point);
				mk.setPosition(point);
				locationReport(lng,lat);
			}
		},200);
	}
}

function locationReport(lng,lat){
	common.setCache('deliver_lng',lng);
	common.setCache('deliver_lat',lat);
	console.log('上报：'+lng+'-'+lat);
	common.http('Deliver&a=location',{noTip:true,lng:lng,lat:lat}, function(data){
		console.log('上报成功：'+JSON.stringify(data));
	},function(data){
		console.log('上报失败：'+JSON.stringify(data));
	});
}

 

var exitLayer = -1;
function appbackmonitor(){
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
		window.lifepasslogin.closeWebView();
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

 

var str1=[];   
function initialize_map(status){
    if(map == null){
        if(typeof(is_google_map) == 'string' && is_google_map != "" && is_google_map.length > 5){  
			var lastAddress = common.getCache('lastAddress', true);
			var tmpPoint = null;
			 
			if (lastAddress) { 
				tmpPoint = {lng: Number(lastAddress.lng)-0.0084045, lat: Number(lastAddress.lat)-0.0040667}; 
			} else if (index_data != null) {   
				console.log(index_data.deliver_info)
				tmpPoint = {lng: Number(index_data.deliver_info.map_lng)-0.0084045, lat: Number(index_data.deliver_info.map_lat)-0.0040667}; 
			}  
			console.log(tmpPoint,'yyy') 
            
        	map = new google.maps.Map(document.getElementById('biz-map'),{
        		center: tmpPoint,
        		zoom:12,
        		streetViewControl:false,
        		mapTypeControl:false,
				disableDefaultUI:true,
				zoomControl:false,
				clickableIcons:false
        	});
        	
        	if(tmpPoint){
        		map.setCenter(tmpPoint,12);
        		mk = new google.maps.Marker({
        			position: tmpPoint,
        			map:map
        		});
        	}
        }else{
        	map = new BMap.Map("biz-map"); 
        	var createMapTimer = setInterval(function(){
        		var lastAddress = common.getCache('lastAddress',true);
        		var tmpPoint = null; 
        		if(lastAddress){
        			tmpPoint = new BMap.Point(lastAddress.lng,lastAddress.lat);
        		}else if(index_data != null){
        			tmpPoint = new BMap.Point(index_data.deliver_info.map_lng,index_data.deliver_info.map_lat);
        		}
                
        		if(tmpPoint){ 
        			clearInterval(createMapTimer); 
        			map.centerAndZoom(tmpPoint,13);
        			console.log(tmpPoint);
        			mk = new BMap.Marker(tmpPoint); 
        			map.addOverlay(mk);  
        		}
        	},200);
        }
    }
	if(!status){
		status = 0; 
	} 
	common.http('Deliver&a=get_deliver_list',{noTip:true,status:status}, function(data){   
		var dataArray=data;  
		var num=0;
		var num1=0;
		var aimOverlays=[];
		var markerAim=[]; 
		var labelIcon="";   
        
        if(status == 0){
            $('.postion_deliver').show();
            $('#allOrder').html(dataArray.length);
        }
        
        if(typeof(is_google_map) == 'string' && is_google_map != "" && is_google_map.length > 5){  
			var marker=null;
			var marker1=null;
			var flightPath=null;
			if(dataArray.length >0 ){  
				dataArray.forEach(function(value,index,array){    
					if(data[index].status == 2){
						num++
						$('#allOrderFrom').html(num);  
					}else if(data[index].status==3){
						num1++ 
						$('#allOrderAim').html(num1); 
					}  

					var value1 = new google.maps.LatLng(Number(value.from_lat),Number(value.from_lnt));  
					var value2 = new google.maps.LatLng(Number(value.aim_lat),Number(value.aim_lnt)); 
					marker=new google.maps.Marker({
						position:value1,
						icon:'images/icon_adress1.png'
					}); 
					marker.addListener('click', function(){
						location.href = './detail.html?supply_id=' + value.supply_id;
					});
					 
					marker1=new google.maps.Marker({
						position:value2,
						icon:'images/icon_adress2.png'						 
					}); 
					marker1.addListener('click', function(){
						location.href = './detail.html?supply_id=' + value.supply_id;
					});
					   
					if(status==0){
						marker.setMap(map);
						marker1.setMap(map);  
						var myTrip=[value1,value2];
						flightPath=new google.maps.Polyline({
							path:myTrip,
							strokeColor:"#338af6",
							strokeOpacity:0.8,
							strokeWeight:1
						}); 
						flightPath.setMap(map); 
					}else if(status==2){
						marker.setMap(map);
					}else if(status==3){
						marker1.setMap(map); 
					}
				})
			}else{
				$('#allOrderFrom,#allOrderAim,#allOrder').html(0);
			}
		}else{
			var labelIcon1='<a href="pick.html" data-webview="true" class="deliveryman-icon"><label>'+L_('取')+'</label><span class="angle"></span><span class="b-oval"></span><span class="s-oval"></span></a>'
			var labelIcon2='<a href="pick.html" data-webview="true" class="deliveryman-icon orange"><label>'+L_('送')+'</label><span class="angle"></span><span class="b-oval"></span><span class="s-oval"></span></a>'
			
			var markerFrom1=null;
			var markerAim2=null;
            map.clearOverlays()
			if(mk){
				mk = new BMap.Marker(mk.getPosition()); 
				map.addOverlay(mk);
			}
			if(dataArray.length >0 ){
				dataArray.forEach(function(value,index,array){    
					if(data[index].status == 2){
						num++
						$('#allOrderFrom').html(num);  
					}else if(data[index].status==3){  
						num1++ 
						$('#allOrderAim').html(num1); 
					}  
					
					var value1 = new BMap.Point(value.from_lnt,value.from_lat);  
					var value2 = new BMap.Point(value.aim_lnt,value.aim_lat);
					var label1 = new BMap.Label(labelIcon1); 
					var label2 = new BMap.Label(labelIcon2);   

					markerFrom1= new BMap.Marker(value1,{ offset: new BMap.Size(-0.2+0.01*index, 0)}); 
					markerFrom1.addEventListener('click', function(){
						location.href = './detail.html?supply_id=' + value.supply_id;
					});
					
					markerAim2= new BMap.Marker(value2,{ offset: new BMap.Size(-0.1+0.01*index, 0)}); 
					markerAim2.addEventListener('click', function(){
						location.href = './detail.html?supply_id=' + value.supply_id;
					});
					
					map.addOverlay(markerFrom1); 
					map.addOverlay(markerAim2);     
					 
					if(status==0){
						markerFrom1.setLabel(label1);     
						markerAim2.setLabel(label2);   
						var polyline = new BMap.Polyline([value1,value2], {
							strokeColor:"#2077ca", 
							strokeWeight:1  
						});
						map.addOverlay(polyline);  
					}else if(status==2){    
						markerFrom1.setLabel(label1); 
					}else if(status==3){     
						markerAim2.setLabel(label2); //设置标注说明  
					}
				})
			}else{ 
				$('#allOrderFrom,#allOrderAim,#allOrder').html(0);
			}
		} 
	}) 
}

var status='';  
$('.postion_deliver span').click(function(){   
	$(this).addClass('cur').siblings().removeClass('cur');
	status=$(this).find('label').data('status'); 
	console.log(status) 
	initialize_map(status);  
})

function pageShowFunc(){
	deliver_login(false);
	pollorder();
	$('.postion_deliver span').removeClass('cur');
	$('.postion_deliver span:eq(0)').addClass('cur');
	initialize_map($('.postion_deliver span.cur label').data('status'));
}


// 隐私协议    
 var lastAgreeTime=null;
 var privacy_policy_md5=null;     
if(common.checkApp()){  
    common.http('Deliver&a=get_privacy_policy',{noTip:true}, function(data){
       privacy_policy_md5=data.privacy_policy_md5;
        if(data.privacy_policy){ 
          if (common.checkIosApp()) {  
              common.iosFunction('getAgreement/getAgreementOk', {name: 'getAgreement',callback:'getAgreementOk'});     
           }else{  
              window.pigcmspackapp.getAgreement('getAgreementOk');
          } 
        } 
    })  
 }
      
 function getAgreementOk(option){   
      if(privacy_policy_md5.trim() != option.trim()){ 
        agreementShow();
      } 
}
function agreementShow(){
     weui.confirm(L_('<div class="agreementCon"><p>感谢您信任并使用我们的软件！</p> <p>我们依据最新的法律要求更新了用户协议和隐私政策，特向您推送本提示。请您仔细阅读并充分理解相关条款，其中的重点条款已为您标注，方便您了解自己的权利。</p> <p>我们将通过《隐私政策》帮助您了解我们收集、使用、存储和共享个人信息的情况，以及您所享有的相关权利。并向您介绍了我们公司主体的基本信息以及告知了我们的联系通道与方式。</p><p>您点击"同意"，即表示您已阅读并同意上述协议条款，我们将尽全力保障您的合法权益并继续为您提供优质的产品和服务。如您点击"不同意"，将可能导致无法继续使用我们的产品和服务。</p><p style="color:#999">您可通过阅读完整版<a href="userAgreement.html" class="agreementLink">《用户协议》</a>和<a href="agreement.html" class="agreementLink">《隐私政策》</a>了解详尽的条款内容</p></div>'), {
          title: L_('温馨提示'),
          buttons: [{
              label: L_('不同意'), 
              type: 'gray', 
              onClick: function(){   
                 notice();
              }
          }, {
              label: L_('同意'), 
              type: 'primary', 
              onClick: function(){    
                  if (common.checkIosApp()) {  
                      common.iosFunction('saveAgreement/'+privacy_policy_md5, {name: 'saveAgreement',privacyPolicyMd5:privacy_policy_md5});    
                  }else{  
                      window.pigcmspackapp.saveAgreement(privacy_policy_md5);
                  } 
              }
          }]
      }); 
} 
 function notice(){
     setTimeout(function(){
         weui.alert('<p class="textP"><img src="images/notice.png" style="width:50px"  /><br>'+L_('若不同意则不能正常使用本软件')+'！</p>', { 
        	buttons: [{
        		label:L_('知道了'),
        		type: 'primary',
        		onClick: function(){ 
                    setTimeout(function(){
                        agreementShow();
                    },350) 
                }
        	}]
        });  
     },350);
 }
// 催单提醒
function reminder(){
	common.http('Deliver&a=reminder_list',{noTip:true}, function(data){
		for(var i in data){
			layer.open({
				type: 1,
				shade: false,
				area: ['300px','150px'],
				maxmin: true,
				// content: '用户催单已超时未送达订单，等待您的处理',
				content: '<div style="text-align;center; margin: 10px;">用户催单已超时未送达订单，等待您的处理</div>',
				zIndex: layer.zIndex, //重点1
				success: function(layero){
					layer.setTop(layero); //重点2
				},
				btn: ['查看', '关闭'],
				yes: function(index, layero){
					openWebviewUrl('detail.html?supply_id='+data[i].supply_id);
				}
			});
		}
	});
}
setInterval('reminder()',3000);


