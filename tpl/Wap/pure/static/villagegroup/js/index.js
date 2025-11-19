console.log(leader_id);
console.log("郭杰");
cart_cookie = $.cookie('cart_cookie'+leader_id);
if(!cart_cookie){
	$.cookie('cart_cookie'+leader_id,randomString(32),{expires:1,path:'/'});
	cart_cookie = $.cookie('cart_cookie'+leader_id);
}
// get_goods_cart();

var map = null;
var userCurrentPosition = {};
$('#join_pay').click(function(){

	if(can_pay==true){				
		can_pay = false;
	}
	
	var layerLoadTip = weui.loading('加载中');

	$.post(save_group_order,{start_id:start_id,cart_cookie:cart_cookie,goodsCart:goodsCart,leader_id:leader_id},function(data){
		
		layerLoadTip.hide();
		
		if(data.status==1){
			openNewWin(data.url);
		}else if(data.status==0){
			weui.alert(data.info, {
			    title: '提示',
			    buttons: [{
			        label: '好的',
			        type: 'primary',
			        onClick: function(){ 
			        	if(data.url!=''){
							window.location.href= data.url;
						}
			        }
			    }]
			});
		}
	},'json');
});


// 计算列表图片偏移量
function getImgLeft () {
	$('#pageIndex  .goodsBox .goodsItem').each(function(){
		var img_url = $(this).find('.image img').attr('src');
		var obj = $(this);
		getImageWidth(img_url,function(w,h){
	        if (w != h) {
	        	var now_width = w * 120 / h ;
	        	var now_left = (now_width - 120) / 2 ;
	        	obj.find('.image img').css({
	        		left: -now_left+'px',
	        		width: now_width+'px'
	        	});
	        } 
	    });
	})
}

// 获取图片真实高度
function getImageWidth(url,callback){
    var img = new Image();
    img.src = url;
    // 如果图片被缓存，则直接返回缓存数据
    if(img.complete){
        callback(img.width, img.height);
    }else{
        img.onload = function(){
            callback(img.width, img.height);
    	}
	}
}

var cat_ids = -1;
var is_all = false;
function get_goods_by_cat_id(cat_id){
	cat_ids = cat_id

	$.cookie('cat_id', JSON.stringify(cat_id));
	console.log('44444444444', cat_id)
	$.post(goods_cat_url,{cat_id:cat_id,page:page,leader_id:leader_id},function(data){
		html='';
		goods = data.goods;
		cat_id= data.cat_fid;
		if(goods){
			for(var i in goods){
				var good = goods[i];
				var stock = good['stock_num']*1;
				var current_time = Date.parse(new Date());
		
				html+='<div class="goodsItem link newWin" data-goods_id="'+good['goods_id']+'" data-max_num="'+stock+'" data-group_id="'+good['group_id']+'"  data-price="'+good['price']+'" data-url="'+site_url+'/wap.php?c=Village_group&a=goods&goods_id='+good['goods_id']+'&leader_id='+leader_id+'&from_index=1">';
				html+='  <div class="image">';
				html+='    <img class="img" src="'+good['image']+'">';

				// 图片 hidden隐藏

				// 文字加背景颜色
				if(good['label']['type']==0){					
					html+='    <span class="icon icon-text" style="background:linear-gradient(to bottom,'+good['label']['color']+' , '+good['label']['color2']+')">'+good['label']['name']+'</span>';
				}
				
				if(good['label']['type']==1){					
					html+='    <img class="icon" src="'+good['label']['image']+'">';
				
				}



				html+='  </div>';
				html+='  <div class="info">';
				html+='    <div class="font_b font_size15 ellipsis name">'+good['name']+'</div>';
				html+='    <div class="sale-box">';
				html+='      <i class="icon iconfont" style="background:'+mobile_color+'">&#xe617;</i><span class="sale-num" style="border:1px solid '+mobile_color+';color:'+mobile_color+'">累计'+good['sell_count']+'份</span>';

				if(good['cat_show_tag'] && good['cat_show_tag']==1){
					html+= '<span class="pin-num" style="background:'+mobile_color+";border:1px solid"+mobile_color+ '">已拼'+good['wait_hadpull_num']+'份</span>';
				}

				html+='    </div>';
				 if (good['village_group_type'] && good['village_group_type']==1) {
                     html+='    <div class="tag future" data-end_time="'+ good['end_time']+'">周期购</div>';
				 }else if(good['open_time']==1 && good['cat_show_tag'] && good['cat_show_tag']==1){
					html+='    <div class="tag future" data-end_time="'+ good['end_time']+'">预售</div>';
				}else if(good['open_time']==2){
					html+='    <div class="tag">次日达</div>';
				}else if(good['open_time']==3){
					html+='    <div class="tag">当日达</div>';
				}
				
				html+='    <div class="price-box">';

				html+='      <span class="price" style="color:'+mobile_color+'"><i>'+currency_symbol+'</i><i class="font_size20">'+good['price']+'</i></span>';


			
				if(good['old_price']>good['price']){					
					html+='        <span class="old-price">'+currency_symbol+good['old_price']+'</span>';
				}
				if(typeof(goodsCart[good['goods_id']])!='undefined' && goodsCart[good['goods_id']].num>0){
					html+='      <span class="btn addIcon  hidden">';
					if(good['village_group_type']==1) {
                        html+='  <i class="icon iconfont cart_info"  style="background:'+ mobile_color +'"  data-goods_id="'+good['goods_id']+'">&#xe607;</i>';
					} else {
                        html+='  <i class="icon iconfont cart"  style="background:'+ mobile_color +'"  data-goods_id="'+good['goods_id']+'">&#xe607;</i>';
					}

					html+='      </span>';
					html+='      <span class=" btn plus-minus addBox ">';
				}else{
					html+='      <span class="btn addIcon  ">';
					if(good['village_group_type']==1) {
						html+='        <i class="icon iconfont cart_info" style="background:'+ mobile_color + '"  data-goods_id="'+good['goods_id']+'">&#xe607;</i>';
                    } else {
                        html+='        <i class="icon iconfont cart" style="background:'+ mobile_color + '"  data-goods_id="'+good['goods_id']+'">&#xe607;</i>';
					}

					html+='      </span>';
					html+='      <span class=" btn plus-minus addBox hidden">';
				}
			
				
				html+='        <i class="icon iconfont minus" style="color:'+mobile_color+'" data-goods_id="'+good['goods_id']+'">&#xe69a;</i>';
				if(typeof(goodsCart[good['goods_id']])!='undefined' && parseInt(goodsCart[good['goods_id']]['num'])>0){
					html+='        <i class="num number" id="good_'+good['goods_id']+'">'+goodsCart[good['goods_id']]['num']+'</i>';
				}else{
					html+='        <i class="num number" id="good_'+good['goods_id']+'">0</i>';
					
				}
				html+='        <i class="icon iconfont plus" style="color:'+mobile_color+'" data-goods_id="'+good['goods_id']+'" data-sell_count="'+good['sell_count']+'">&#xe649;</i>';
				html+='      </span>';
				html+='    </div>';
				html+='  </div>';
				if((good['stock_num'])<=0 ){
					html+='<div class="sale-out"><div>已售罄</div></div>';
				}
				if ( (good['end_time']+86400)*1000 <= current_time) {
					html+='<div class="sale-out"><div>预售结束</div></div>';
				}
				
				if ( good['start_time']*1000 >  current_time) {
					html+='<div class="sale-out"><div>活动未开始</div></div>';
				}
				
				if (good['village_group_type']!=1 && (good['get_time']+86400)*1000 <= current_time) {
					html+='<div class="sale-out"><div>活动结束</div></div>';
				}
				html+='</div>';
			}
			if(page==1){
				$('#goodsBox').html(html)
			}else{
				$('#goodsBox').append(html)
			}

			if (goods.length == 0) {
				is_all = true
			} 

			getImgLeft()
		}
	},'json');
}

function get_goods_cart(){
	var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
	var all_get_num=0,all_get_money = 0;
	$('.number').html(0);

	$.post(get_cart_url,{cart_cookie:cart_cookie,start_id:start_id,leader_id:leader_id},function(data){
		if(data.status==1){

			if(data.info.length == 0) {
				$('#pageCart .emptyBox').removeClass('hidden')
			} else {
				$('#pageCart .emptyBox').addClass('hidden')
			}

			var goods_detail = data.info;
			var cart_html = '';
			for(var i in goods_detail ){
				if(nowPage=='pageCart'){
					var stock = goods_detail[i]['stock_num']*1 ;

					cart_html +='<div class="goodsItem link" data-goods_id="'+goods_detail[i].goods_id+'" data-max_num="'+stock+'"  data-group_id="'+goods_detail[i].group_id+'"  data-price="'+goods_detail[i].price+'" data-url="./wap.php?c=Village_group&a=goods&goods_id='+goods_detail[i].goods_id+'&leader_id='+leader_id+'">';
					 //cart_html +='	<div class="select"><i class="icon iconfont active">&#xe606;</i></div>';
					cart_html +='	<div class="image">';
					cart_html +='		<img src="'+goods_detail[i].image+'">';
					// 图片 hidden隐藏
					
					

					// 文字加背景颜色
					// cart_html +='    <span class="icon icon-text small ">热销</span>';
					if(goods_detail[i]['label'] !=null){
						
						if(goods_detail[i]['label']['type']==0){					
							cart_html+='    <span class="icon icon-text small" style="background:linear-gradient(to bottom,'+goods_detail[i]['label']['color']+' , '+goods_detail[i]['label']['color2']+')">'+goods_detail[i]['label']['name']+'</span>';
						}
						
						if(goods_detail[i]['label']['type']==1){					
							// html+='    <img class="icon" src="'+good['label']['image']+'">';
							cart_html +='    <img class="icon small" src="'+goods_detail[i]['label']['image']+'">';
						
						}
					}
					
					cart_html +='	</div>';
					cart_html +='	<div class="info">';
					cart_html +='		<div class="name">'+goods_detail[i].name+'</div>';
					// cart_html +='		<div class="font_size12 color_9">规格：袋</div>';
					if (goods_detail[i]['village_group_type'] && goods_detail[i]['village_group_type']==1){
                        cart_html +='		<div class="tag future">周期购</div>';
					}else if(goods_detail[i].open_time==1 && goods_detail[i].cat_show_tag && goods_detail[i].cat_show_tag==1){
					cart_html +='		<div class="tag future">预售</div>';
					}else if(goods_detail[i].open_time==3){
					cart_html +='		<div class="tag">当日达</div>';
					}else if(goods_detail[i].open_time==2){
					cart_html +='		<div class="tag">次日达</div>';
						
					}
					cart_html +='		<div class="price-box">';
					cart_html +='			<span class="price" style="color:'+ mobile_color +'">'+currency_symbol+'<span class="font_size20 now-price">'+goods_detail[i].price+'</span></span>';
					if(goods_detail[i].old_price>goods_detail[i].price){
						
						cart_html +='			<span class="old-price" >'+currency_symbol+goods_detail[i].old_price+'</span>';
					}
					cart_html +='			<span class="fr btn">';
					cart_html +='				<i class="icon iconfont minus" data-goods_id="'+goods_detail[i].goods_id+'">&#xe60c;</i>';
					cart_html +='				<i class="num number font_size13" id="goods_'+goods_detail[i].goods_id+'">'+goods_detail[i].count+'</i>';
					cart_html +='				<i class="icon iconfont plus" data-goods_id="'+goods_detail[i].goods_id+'" data-sell_count="'+goods_detail[i].sell_count+'">&#xe60d;</i>';
					cart_html +='			</span>';
					cart_html +='			';
					cart_html +='		</div>';
					cart_html +='	</div>';
					cart_html +='</div>';
				}
				if (goods_detail[i].price != goods_detail[i].productParam.good_price) {
                    goods_detail[i].productParam.good_price = goods_detail[i].price;
				}
		
				$('#good_'+goods_detail[i].goods_id).html(goods_detail[i].count).closest('.addBox').removeClass('hidden').siblings('.addIcon').addClass('hidden');
				goodsCart[goods_detail[i].goods_id] = {'num':goods_detail[i].count,'good_price':goods_detail[i].productParam.good_price};
				all_get_num +=parseInt(goods_detail[i].count);
				all_get_money +=parseFloat(goods_detail[i].productParam.good_price)*parseInt(goods_detail[i].count);
			}	
			if(nowPage=='pageCart'){
				$(".cartBox").html(cart_html);
			}
				// console.log(cart_html)
	
			goodsCart['all'] = {'all_money':all_get_money,'all_num':all_get_num,'cate_num':goods_detail.length};
		}
			all_money = all_get_money;
			num = all_get_num;
		
			$('.price_total').html(all_get_money.toFixed(2))
			$('.goods_num').html(all_get_num)
			$('#cate_count').html(goodsCart['all'].all_num)
			
	
		layer.close(layerLoadTip);
			
	},'json');
}

function swiperScrollX (swiper) {
	console.log('22222222222222222234', swiper)
	swiperWidth = swiper.container[0].clientWidth
	maxTranslate = swiper.maxTranslate();
	maxWidth = -maxTranslate + swiperWidth / 2
	swiper.on('tap', function(swiper, e) {

	//	e.preventDefault()

		slide = swiper.slides[swiper.clickedIndex]
		slideLeft = slide.offsetLeft
		slideWidth = slide.clientWidth
		slideCenter = slideLeft + slideWidth / 2
		// 被点击slide的中心点

		swiper.setWrapperTransition(300)

		if (slideCenter < swiperWidth / 2) {
			
			swiper.setWrapperTranslate(0)

		} else if (slideCenter > maxWidth) {
			
			swiper.setWrapperTranslate(maxTranslate)

		} else {

			nowTlanslate = slideCenter - swiperWidth / 2

			swiper.setWrapperTranslate(-nowTlanslate)

		}

	})
	
}

function goUrl (url) {
	
	if(typeof wx !=='undefined' && url.indexOf('javascript')===-1 && window.__wxjs_environment === 'miniprogram'){
		if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
			url = 'https://' + document.domain + url;
		}
		wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent('打开新页面')});
	}else{
		location.href=url;
	}
}

function goLeader () {
	var now_url = window.location.href
	// if (now_url.indexOf('&leader_id') == -1) {
	// 	goUrl('/wap.php?g=Wap&c=Village_group&a=leader')
	// } else {
		var arr = now_url.split('&')
		var leader_id = 0
		for (var i=0; i<arr.length;i++) {
			if (arr[i].indexOf('leader_id') != -1) {
				var arr2 = arr[i].split('=')
				leader_id = arr2[1]
				break;
			}
		}
		$.post(check_leader, {leader_id:leader_id}, function(data){
			if (data && data.status == 0 && data.url) {
				goUrl('/wap.php?g=Wap&c=Village_group&a=leader')
			}	
		},'json');
	// }
}

function google_getPositionAdress(result){
    if(result.status == 0){
        result = result.result;
        var cityMatchingUrl = '/wap.php?g=Wap&c=Home&a=cityMatching';
        $.post(cityMatchingUrl,{'city_name':result.addressComponent.city,'area_name':result.addressComponent.district,'get_province':'1','all_city':'1'},function(res){
            // console.log(result);
            // console.log(res);
            if(res.status == 1){
                $('.city_box span').html(res.info.area_name);
                $('#province_id').val(res.info.province_id);
                $('#city_id').val(res.info.area_id);
                $('#area_id').val(res.info.now_area_id);
                var re = [];
                if(result.sematic_description.indexOf("附近0米") < 0){
                    re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
                }
                for(var i in result.pois){
                    re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
                }
                google_getAdress(re,false);
            }else{
                alert('当前城市不可用');
            }
        });
    }else{
        alert('获取位置失败！');
    }
}


function google_search(address){

    var map;
    var service;
    var centerMap = $('#now_center').val();
    centerMap = centerMap.split(',');

    if(centerMap){
        user_long = parseFloat(centerMap[1]);
        user_lat = parseFloat(centerMap[0]);
    }else{
        user_long = 116.403847;
        user_lat = 39.915526;
    }

    map = new google.maps.Map(document.getElementById('hidden_map'), {
        center:{lat:user_lat,lng:user_long},
        zoom: 15
    });

    var request = {
        bounds:map.getBounds(),
        query: address
    };

    service = new google.maps.places.PlacesService(map);
    service.textSearch(request, google_callback);

    function google_callback(results, status) {
        if (status == google.maps.places.PlacesServiceStatus.OK) {
            getAdress(results,false,1);
        }
    }
}

function google_getIframe(userLonglat,userLong,userLat){
    geoconv('google_realResult',userLong,userLat);
}
function google_realResult(result){
    var lng = result.result[0].x;
    var lat = result.result[0].y;
    var point = new BMap.Point(lng, lat);
    map.centerAndZoom(point, 16);
    google_getPositionInfo(lat, lng);
}

function google_getPositionInfo(lat,lng){
    var map;
    var service;
    var centerMap = $('#now_center').val();
    centerMap = centerMap.split(',');
    if(centerMap){
        user_long = parseFloat(centerMap[1]);
        user_lat = parseFloat(centerMap[0]);
    } else{
        user_long = parseFloat(lng);
        user_lat = parseFloat(lat);
    }

    map = new google.maps.Map(document.getElementById('hidden_map'), {
        center:{lat:user_lat,lng:user_long},
        zoom: 15
    });

    var request = {
        location: {lat:user_lat,lng:user_long},
        radius: '200'
    };

    service = new google.maps.places.PlacesService(map);
    service.nearbySearch(request, google_callback);

    function google_callback(results, status) {
        if (status == google.maps.places.PlacesServiceStatus.OK) {
            google_getAdress(results,false);
        }
    }
}
function google_getAdress(re,isSearch,isText){
    $('#addressShow').html('');
    var addressHtml = '';
    if(isText==1){
        for(var i=0;i<re.length;i++){
            if (re[i].geometry.location.lng() == null || re[i].geometry.location.lat() == null) continue;
            addressHtml += '<li lng="'+re[i].geometry.location.lng()+'" lat="'+re[i].geometry.location.lat()+'" sug_address="'+re[i].name+'" address="'+re[i].formatted_address+'" sname="'+re[i].name+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[0].name+'" data-district="'+re[re.length-1]['district']+'"' : '')+'>';
            addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
            addressHtml += '<span class="icon-location" data-node="icon"></span>';
            addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i].name+' </span> </div>';
            addressHtml += '<div class="mapaddress-body"> '+re[i].formatted_address+' </div>';
            addressHtml += '</li>';
        }
    }else{
        for(var i=1;i<re.length-1;i++){
            if (re[i].geometry.location.lng() == null || re[i].geometry.location.lat() == null) continue;
            addressHtml += '<li lng="'+re[i].geometry.location.lng()+'" lat="'+re[i].geometry.location.lat()+'" sug_address="'+re[i].name+'" address="'+re[i].vicinity+'" sname="'+re[i].name+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[0].name+'" data-district="'+re[re.length-1]['district']+'"' : '')+'>';
            addressHtml += '<div class="mapaddress-title '+(i!=1 ? 'notself' : '')+'">';
            addressHtml += '<span class="icon-location" data-node="icon"></span>';
            addressHtml += '<span class="recommend"> '+(i == 1 ? '[建议位置]' : '')+'   '+re[i].name+' </span> </div>';
            addressHtml += '<div class="mapaddress-body"> '+re[i].vicinity+' </div>';
            addressHtml += '</li>';
        }
    }
    $('#addressShow').append(addressHtml);
}

function bMap_getIframe(userLonglat,userLong,userLat){
    geoconv('bMap_realResult',userLong,userLat);
}
function bMap_realResult(result){
    var lng = result.result[0].x;
    var lat = result.result[0].y;
    userCurrentPosition = {lng:lng,lat:lat}
    var point = new BMap.Point(lng, lat);
    map.centerAndZoom(point, 16);
    bMap_getPositionInfo(lat, lng);
}

function bMap_search(address){
    var postData = {city_id:$('#city_id').val(),query:address,city_limit:1};
    if(userCurrentPosition.lng){
        postData.lng = userCurrentPosition.lng;
        postData.lat = userCurrentPosition.lat;
    }
    $.get('index.php?g=Index&c=Map&a=suggestion',postData, function(data){
        if(data.status == 1){
            if(data.result[0] && data.result[0].city && data.result[0].district){
                bMap_getAdress(data.result,true);
            }
        }
    });
}
function bMap_getPositionLocation(result){
    if(result.status == 0){
        result = result.result;
        bMap_getPositionInfo(result.location.lat,result.location.lng);
    }else{
        alert('获取位置失败！');
    }
}
function bMap_getPositionInfo(lat,lng){
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&location='+lat+','+lng+'&output=json&pois=1&callback=bMap_getPositionAdress&json=?');
}
function bMap_getAdress(re,isSearch){
    $('#addressShow').html('');
    var addressHtml = '';
    for(var i=0;i<re.length;i++){
        if (re[i]['long'] == null || re[i]['lat'] == null) continue;
        addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'" sname="'+re[i]['name']+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[i]['city']+'" data-district="'+re[i]['district']+'"' : '')+'>';
        addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
        addressHtml += '<span class="icon-location" data-node="icon"></span>';
        addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i]['name']+' </span>';
        if(re[i].distance){
            addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
        }
        addressHtml += '</div>';
        addressHtml += '<div class="mapaddress-body"> '+re[i]['address']+' </div>';
        addressHtml += '</li>';
    }
    $('#addressShow').append(addressHtml);
    $('.mapaddress').css('width','100%');
}
function bMap_getPositionAdress(result){
    if(result.status == 0){
        result = result.result;
        var cityMatchingUrl = '/wap.php?g=Wap&c=Home&a=cityMatching';
        $.post(cityMatchingUrl,{'city_name':result.addressComponent.city,'area_name':result.addressComponent.district,'get_province':'1','all_city':'1'},function(res){
            // console.log(result);
            // console.log(res);
            if(res.status == 1){
                $('.city_box span').html(res.info.area_name);
                $('#province_id').val(res.info.province_id);
                $('#city_id').val(res.info.area_id);
                $('#area_id').val(res.info.now_area_id);
                var re = [];
                if(result.sematic_description.indexOf("附近0米") < 0){
                    re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
                }
                for(var i in result.pois){
                    re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
                }
                bMap_getAdress(re,false);
            }else{
                alert('当前城市不可用');
            }
        });
    }else{
        alert('获取位置失败！');
    }
}
function bMap_getAdress(re,isSearch){
    $('#addressShow').html('');
    var addressHtml = '';
    for(var i=0;i<re.length;i++){
        if (re[i]['long'] == null || re[i]['lat'] == null) continue;
        addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'" sname="'+re[i]['name']+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[i]['city']+'" data-district="'+re[i]['district']+'"' : '')+'>';
        addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
        addressHtml += '<span class="icon-location" data-node="icon"></span>';
        addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i]['name']+' </span>';
        if(re[i].distance){
            addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
        }
        addressHtml += '</div>';
        addressHtml += '<div class="mapaddress-body"> '+re[i]['address']+' </div>';
        addressHtml += '</li>';
    }
    $('#addressShow').append(addressHtml);
    $('.mapaddress').css('width','100%');
}

//cart end
var pageIndexMiniRefresh = null;
var showPageLoadTip = true;
var pageLoadedFunc = {
	pageIndex: function(){
		setTimeout(function(){
			goLeader()
			if ($('#headerFixed').html() == '') {
				$('#headerFixed').html( $('#headerBox').html() )
			}

			$('#shareIcon').html( $('.shareBox').html())
			$('#indexShareMask').html( $('.shareMask').html())

			cat_ids = $('#menuBox span.active').parents('.swiper-slide').data('cat_id');
			get_goods_by_cat_id(cat_ids)
			

			$(document).on('click', '.shareMaskCommon', function(){
				$('.shareMaskCommon').hide()
			})

			// 一键复制
			$(document).on('click', '#headerFixed .header-box .copy', function () {

				$('#indexShareMask').removeClass('hidden')
			})
			$(document).on('click', '#indexShareMask .mask, #indexShareMask .shareContent .cancel', function () {
				$('#indexShareMask').addClass('hidden')
			})

			var clipboard = new ClipboardJS('.copyBtn');
	        clipboard.on('success', function(e) {
	            $('#indexShareMask').addClass('hidden')
				weui.toast('复制成功', 1500);
	        });

	        clipboard.on('error', function(e) {
	            weui.alert('复制失败，请尝试手动复制！', {
				    title: '提示',
				    buttons: [{
				        label: '好的',
				        type: 'primary',
				        onClick: function(){ console.log('ok') }
				    }]
				});
	        });

			
			// 首页顶部菜单
			var menuBoxSlider = new Swiper('#headerFixed #menuBox',{
				slidesPerView: 'auto',
				resistanceRatio: 0,
		        spaceBetween: 0,
				freeMode: true
			});
			// $("#headerFixed #menuBox").on('touchstart', function(e) {
			// 	e.preventDefault()
			// })
			if ( $('#headerFixed #menuBox .swiper-slide').length > 0 ) {
				swiperScrollX(menuBoxSlider)
			}
			


			// 顶部菜单切换分类
			$(document).on('click', '#headerFixed #menuBox .swiper-slide div', function () {
				 var $color=$(this).attr("data-color");
				pageIndexMiniRefresh.scrollTo(0, 0);
				var current = $(this).parents('.swiper-slide');
				current.siblings().find('span').removeAttr("style");
				var next = current.next()

				cat_id = current.data('cat_id');
				
				$('.pageIndex .goodsItem').hide();
				// $('.cat_'+cat_id).show();
				$('#headerFixed #menuBox span').removeClass('active')
				current.find('span').addClass('active');
				if($color && $color!=''){
					current.find("span.active").css({
						"color":$color,
						"border-bottom": "3px solid" + $color
					})
				}
				$('#goodsBox').html('');
				page = 1
				is_all = false
				get_goods_by_cat_id(cat_id)
				
				if(current.data('cat_id') !==  $('#headerFixed #menuBox .swiper-slide:last-child').data('cat_id') ) {
					$('.change-type .text').html('切换下一分类')
				} else {
					$('.change-type .text').html('暂无数据了，看看其他分类吧~')
				}

			})

			// 首页底部切换下一分类
			$(document).on('click', '.change-type', function () {
				pageIndexMiniRefresh.scrollTo(0, 0);

				var current = $('#headerFixed #menuBox span.active').parents('.swiper-slide')
				var next = current.next()
				
				if ($('#headerFixed #menuBox .swiper-slide:last-child').data('cat_id') == current.data('cat_id') ) {
					return 
				}

				if ( next ) {
					current.find('span').removeClass('active').css({
						"color":'',
						"border-bottom": "none"
					})
					next.find('span').addClass('active').css({
						"color":mobile_color,
						"border-bottom": "3px solid" + mobile_color
					})
					var cat_id = next.data('cat_id')
					
					$('#goodsBox').html('')
					page = 1
					is_all = false
					get_goods_by_cat_id(cat_id)
					if($('#headerFixed #menuBox .swiper-slide:last-child').data('cat_id') == cat_id) {
						$('.change-type .text').html('暂无数据了，看看其他分类吧~')
					}
				} 
			})
			
			//首页顶部轮播图 
			var categoryBoxSlider = new Swiper('#categoryBox .swiper-container',{
				autoplay:3000,
				autoplayDisableOnInteraction: false,
				pagination: '#categoryBox .swiper-pagination'
			});

			// 轮播图高度计算 600*190
			var imgH = $('#categoryBox .swiper-slide img').width() * 190 / 600
			$('#categoryBox, #categoryBox .swiper-slide img').height(imgH)
			
			var recommendSlider = new Swiper('#recommend',{
				onSlideChangeEnd: function(swiper){
					$('.recommend-pagination .now').html(swiper.activeIndex+1);
				}
			});

			

			//下拉刷新效果
			pageIndexMiniRefresh = new MiniRefresh({
				container: '#pageIndex',
				// isLockX:false,
				down: {
					isLock:false,
					callback: function(){
						setTimeout(function(){
							pageIndexMiniRefresh.endDownLoading(true);
							location.href = '/wap.php?g=Wap&c=Village_group&a=index&leader_id='+leader_id;
							return false;
							showPageLoadTip = false;
							pageLoaded[nowPage] = null;
							$(".tabBtn[data-page='"+nowPage+"']").trigger('click');
						},600);
					},
					// 这个也生效
					isWrapCssTranslate: true
				},
				up: {
					isAuto:false,
					offset:75,
					isShowUpLoading: false,
					loadFull:{
						isEnable:false
					},
					onScroll:function(e){
						if (!is_all) {

							var scroll_distance = $('#pageIndex .container').height() - $('#pageIndex').height();
							// console.log('3333333', e, scroll_distance)
							if (scroll_distance > 0 && e > scroll_distance) {
								page++;
								get_goods_by_cat_id(cat_ids);
							}
						} else {
							pageIndexMiniRefresh.endUpLoading(false);
						}
						
					}
				}
			});
		},300);
	},
	pageCart: function(){
		setTimeout(function(){
			//购物车为空时
			$('#pageCart .emptyBox').height( $(window).height() - 94 );

			// 点击商品前面的选择按钮
			$(document).on('click', '.cart .goodsItem .select', function(){
				
				var goods_id = $(this).parents('.goodsItem').data('goods_id')

				// 取消选中
				if ($(this).find('i').attr('class').indexOf('active') != -1 ) { 
					$(this).find('i').html('&#xe72f').removeClass('active')
					var cate_num = goodsCart['all'].cate_num -- ;
					num -= goodsCart[goods_id].num;
					all_money -= goodsCart[goods_id].num * parseFloat(goodsCart[goods_id].good_price);
					goodsCart[goods_id].active = false
					goodsCart['all'] = {'all_money':parseFloat(all_money).toFixed(2),'all_num':num, cate_num: cate_num};
									

					$('.price_total').html(parseFloat(all_money).toFixed(2))
					$('.goods_num').html(num)
					$('#cate_count').html(num)
				} else {
					$(this).find('i').html('&#xe606').addClass('active')
					var cate_num = goodsCart['all'].cate_num ++ ;
					num += goodsCart[goods_id].num;
					all_money += goodsCart[goods_id].num * parseFloat(goodsCart[goods_id].good_price);
				    delete goodsCart[goods_id].active;
				    goodsCart['all'] = {'all_money':parseFloat(all_money).toFixed(2),'all_num':num, cate_num: cate_num};

				    $('.price_total').html(parseFloat(all_money).toFixed(2))
					$('.goods_num').html(num)
					$('#cate_count').html(num)
				}

				console.log('购物车数据', goodsCart)
				$.cookie('VillagegoodsCart', JSON.stringify(goodsCart));

				return false
				
			})
		},0)
		
	},
	pageMy: function(){
		setTimeout(function(){

			console.log('pageMy');
			if ($('#scanCodeMaskFixed').html() == '') {
				$('#scanCodeMaskFixed').html($('#scanCodeMask').html())
			}

			if ($('#GradeFixed').html() == '') {
				$('#GradeFixed').html($('#Grade').html())
			}

			if ($('#modifyAddressesFixed').html() == '') {
				$('#modifyAddressesFixed').html($('#addressHtmlNews').html())
			}
			
			if($('#my-need-login').size() > 0){
				location.href = $('#my-need-login').data('url');
				return false;
			}
            $('.set_address.btn').click(function () {
                // 修改地址点击
                console.log('修改地址点击');
				$('#pageMy .container').css({'height':'100%','overflow':'hidden'});
				pageIndexMiniRefresh.refreshOptions({down:{isLock:true}});
				$('#addressHtml').show(); 
				
            });

            $('.adress_click').click(function () { 
                console.log('选择地址');
				$("#searchAddress").css('display','block');
            });

            $('.cancel_adress').click(function () {
                console.log('取消隐藏');
				$('#addressHtml').hide();
                $('#pageMy .container').css({'height':'','overflow':''});
                pageIndexMiniRefresh.refreshOptions({down:{isLock:false}});
            });

            $('.confirm_adress').click(function () {
                console.log('确定选择地址');
                var tmp_name = $("#tmp_end_name").val();
                var tmp_phone = $("#tmp_end_phone").val();
                var tmp_detail = $("#address_detail").val();
                var tmp_adress = $("#tmp_end_adress").val();
                var tmp_adress_lng = $("#tmp_end_adress_lng").val();
                var tmp_adress_lat = $("#tmp_end_adress_lat").val();
                var tmp_adress_area_id = $("#tmp_end_adress_area_id").val();
                var tmp_adress_area_name = $("#tmp_end_adress_area_name").val();

                var tmp_province_id = $("#province_id").val();
                var tmp_city_id = $("#city_id").val();
                var tmp_area_id = $("#area_id").val();

                console.log('tmp_adress',tmp_adress);
                console.log('tmp_adress_lng',tmp_adress_lng);
                console.log('tmp_adress_lat',tmp_adress_lat);
                console.log('tmp_province_id',tmp_province_id);
                console.log('tmp_city_id',tmp_city_id);
                console.log('tmp_area_id',tmp_area_id);
                console.log('tmp_detail',tmp_detail);


                if(!tmp_adress || !tmp_adress_lng || !tmp_adress_lat || !tmp_province_id || !tmp_city_id || !tmp_area_id){
                    alert('请选择地址');
                    return false;
                }
                if(!tmp_detail){
                    alert('请输入详细地址');
                    return false;
                }
                tmp_detail = tmp_adress + ' ' + tmp_detail;
                var url = '/wap.php?g=Wap&c=Village_group&a=editHeaderAddress';
                $.post(url,{address:tmp_detail,long:tmp_adress_lng,lat:tmp_adress_lat,province_id:tmp_province_id,city_id:tmp_city_id,area_id:tmp_area_id},function(data){
                    if(data.status==1){
                        layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
                                window.location.href= data.url;
                            }});
                    }
                },'json');
            });

            if (map_config && map_config=='google') {
            	console.log('map_config1', map_config);
                var address = '';
                var timeout = 0;
                $(document).ready(function(){
                    $('.map .MapHolder').height(($(window).height()-50)*0.4);
                    $('.mapaddress').height(($(window).height()-52)*0.6);

                    $('#fis_elm__4').height($(window).height()-52);

                    var cityKey = [];
                    $.each($('.cityKey'),function(i,item){
                        cityKey.push($(item).data('city_key'));
                    });

                    $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
                        chars:cityKey,
                        callback:function(ret){
                            $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
                        }
                    });

                    $('.city_location').click(function(){
                        $('.city_box span').html($(this).html());
                        $('.city_box').removeClass('arrow');
                        $('#fis_elm__3').show();
                        $('#fis_elm__4').hide();

                        var geocoder =  new google.maps.Geocoder();
                        geocoder.geocode( { 'address': $(this).html()}, function(results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                map.setCenter({lat:results[0].geometry.location.lat(),lng:results[0].geometry.location.lng()},16);
                                $('#now_center').val(results[0].geometry.location.lat()+','+results[0].geometry.location.lng());
                            } else {
                                alert("Something got wrong " + status);
                            }
                        });
                        setTimeout(function(){
                            var centerMap = map.getCenter();
                            google_getPositionInfo(centerMap.lat(),centerMap.lng());
                        },700);
                    });

                    $('.city_box').click(function(){
                    	console.log('city_box');
                        if($(this).hasClass('arrow')){
                            $(this).removeClass('arrow');
                            $('#fis_elm__3').show();
                            $('#fis_elm__4').hide();
                        }else{
                            $(this).addClass('arrow');
                            $('#fis_elm__3').hide();
                            $('#fis_elm__4').show();
                        }
                    });

                    // 谷歌地图API功能
                    if (lat <= 0 || long <= 0) {
                        lat = 39.915526;
                        long = 116.403847;
                        city = '北京';
                    }

                    map = new google.maps.Map(document.getElementById('cmmap'), {
                        mapTypeControl:false,
                        zoom: 16,
                        center: {lng:parseFloat(long),lat:parseFloat(lat)}
                    });
                    //初始化
                    $('#now_center').val(lat+','+long);
                    $('.city_box span').html(city);
                    google_getPositionInfo(lat,long);

                    map.addListener("dragend", function(e){
                        $('#addressShow').empty();
                        var centerMap = map.getCenter();
                        $('#now_center').val(centerMap.lat()+','+centerMap.lng());
                        google_getPositionInfo(centerMap.lat(),centerMap.lng());
                    });

                    $("#se-input-wd").bind('input', function(e){
                        var address = $.trim($('#se-input-wd').val());
                        if(address.length > 0){
                            $('#cmmap').hide();
                            $('.mapaddress').height(($(window).height()-52));

                            $('#addressShow').empty();
                            clearTimeout(timeout);
                            timeout = setTimeout("google_search('"+address+"')", 500);
                        }else{
                            $('#cmmap').show();
                            $('.mapaddress').height(($(window).height()-52)*0.6);
                        }
                    });

                    $('#addressShow').delegate("li","click",function(){
                        var sname = $(this).attr("sname");
                        var lng = $(this).attr("lng");
                        var lat = $(this).attr("lat");

                        var cityMatchingUrl = '/wap.php?g=Wap&c=Village_group&a=cityMatching';
                        $.get(cityMatchingUrl, {'lng':lng,'lat':lat}, function(data){
                            if(data.error == 1){
                                $("#tmp_end_adress").val(sname);
                                $("#tmp_end_adress_lng").val(lng);
                                $("#tmp_end_adress_lat").val(lat);
                                $("#tmp_end_adress_area_id").val(data.area_id);
                                $("#tmp_end_adress_area_name").val(data.area_name);
                                $("#tmp_adress_end_html").html(sname);
                                $('#area_id').val(data.now_area_id);
                            }else{
                                layer.open({
                                    content: data.msg
                                    ,btn: ['确定']
                                });
                            }
                        },'json');
                        $('.mask').hide();
                        $("#searchAddress").css('display','none');
                    });

                    // var geolocation = new BMap.Geolocation();


                    var user_address = $.cookie('user_address');

                    if(user_address){
                        user_address = $.parseJSON(user_address);
                    }
                    if(user_address && user_address.longitude && user_address.latitude){
                        map.setCenter({lng:parseFloat(user_address.longitude),lat:parseFloat(user_address.latitude)}, 16);
                        $('#now_center').val(parseFloat(user_address.latitude)+','+parseFloat(user_address.longitude));
                        google_getPositionInfo(user_address.latitude,user_address.longitude);
                    }else{
                        navigator.geolocation.getCurrentPosition(function(position){
                            map.setCenter({lng:parseFloat(position.coords.longitude),lat:parseFloat(position.coords.latitude)}, 16);
                            google_getPositionInfo(position.coords.latitude,position.coords.longitude);
                        })
                    }
                });
			} else {
                console.log('map_config', map_config);
                var address = '';
                var timeout = 0;
                var userCurrentPosition = {};
                $(document).ready(function(){
                    $('.map .MapHolder').height(($(window).height()-50)*0.4);
                    $('.mapaddress').height(($(window).height()-52)*0.6);

                    $('#fis_elm__4').height($(window).height()-52);

                    var cityKey = [];
                    $.each($('.cityKey'),function(i,item){
                        cityKey.push($(item).data('city_key'));
                    });

                    $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
                        chars:cityKey,
                        callback:function(ret){
                            $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
                        }
                    });

                    $('.city_location').click(function(){
                        $('.city_box span').html($(this).html());
                        $('.city_box').removeClass('arrow');
                        $('#fis_elm__3').show();
                        $('#fis_elm__4').hide();

                        map.centerAndZoom($(this).html(), 16);
                        setTimeout(function(){
                            var centerMap = map.getCenter();
                            bMap_getPositionInfo(centerMap.lat,centerMap.lng);
                        },700);
                    });

                    $('.city_box').click(function(){
                        if($(this).hasClass('arrow')){
                            $(this).removeClass('arrow');
                            $('#fis_elm__3').show();
                            $('#fis_elm__4').hide();
                        }else{
                            $(this).addClass('arrow');
                            $('#fis_elm__3').hide();
                            $('#fis_elm__4').show();
                        }
                    });

                    // 百度地图API功能
                    if (lat <= 0 || long <= 0) {
                        lat = 39.915526;
                        long = 116.403847;
                        city = '北京';
                    }
                    console.log('lat',lat);
                    console.log('long',long);
                    console.log('city',city);
                    map = new BMap.Map("cmmap",{enableMapClick:false});
                    map.centerAndZoom(new BMap.Point(long,lat), 16);

                    $('#now_center').val(lat+','+long);
                    $('.city_box span').html(city);

                    map.addEventListener("dragend", function(e){
                        $('#addressShow').empty();
                        var centerMap = map.getCenter();
                        bMap_getPositionInfo(centerMap.lat,centerMap.lng);
                    });

                    $("#se-input-wd").bind('input', function(e){
                        var address = $.trim($('#se-input-wd').val());
                        if(address.length > 0){
                            $('#cmmap').hide();
                            $('.mapaddress').height(($(window).height()-52));

                            $('#addressShow').empty();
                            clearTimeout(timeout);
                            timeout = setTimeout("bMap_search('"+address+"')", 500);
                        }else{
                            $('#cmmap').show();
                            $('.mapaddress').height(($(window).height()-52)*0.6);
                        }
                    });

                    $('#addressShow').delegate("li","click",function(){
                        var sname = $(this).attr("sname");
                        var lng = $(this).attr("lng");
                        var lat = $(this).attr("lat");

                        var cityMatchingUrl = '/wap.php?g=Wap&c=Village_group&a=cityMatching';
                        $.get(cityMatchingUrl, {'lng':lng,'lat':lat}, function(data){
                            if(data.error == 1){
                                $("#tmp_end_adress").val(sname);
                                $("#tmp_end_adress_lng").val(lng);
                                $("#tmp_end_adress_lat").val(lat);
                                $("#tmp_end_adress_area_id").val(data.area_id);
                                $("#tmp_end_adress_area_name").val(data.area_name);
                                $("#tmp_adress_end_html").html(sname);
                                $('#area_id').val(data.now_area_id);
                            }else{
                                layer.open({
                                    content: data.msg
                                    ,btn: ['确定']
                                });
                            }
                        },'json');
                        $('.mask').hide();
                        $("#searchAddress").css('display','none');
                    });

                    // var geolocation = new BMap.Geolocation();


                    var user_address = $.cookie('user_address');
                    if(user_address){
                        user_address = $.parseJSON(user_address);
                    }
                    if(user_address && user_address.longitude && user_address.latitude){
                        map.centerAndZoom(new BMap.Point(user_address.longitude,user_address.latitude), 16);
                        bMap_getPositionInfo(user_address.latitude,user_address.longitude);
                    }else{
                        // geolocation.getCurrentPosition(function(r){
                        // 	if(this.getStatus() == BMAP_STATUS_SUCCESS){
                        // 		map.centerAndZoom(new BMap.Point(r.point.lng,r.point.lat), 16);
                        // 		getPositionInfo(r.point.lat,r.point.lng);
                        // 	}else{
                        // 		alert('failed：'+this.getStatus());
                        // 	}
                        // },{enableHighAccuracy: true});
                        getUserLocation({useHistory:false,okFunction:'bMap_getIframe'});
                    }
                });
			}

			// 验证码弹窗的显示与隐藏
			$(document).on('click', '.qcode-box .btn button', function () {
				console.log('验证码弹窗的显示与隐藏1');
				$('.code-mask').show()
			})
			$(document).on('click', '.code-mask .cancel, .code-mask .mask', function () {
                console.log('验证码弹窗的显示与隐藏2');
				$('.code-mask').hide()
			})

			// 点击等级弹窗
			$(document).on('click', '#gradeClick', function () {
				console.log('gradeClick');
				$('.Grade-mask').show()
			})
		    $(document).on('click', '.Grade-mask .cancel, .Grade-mask .mask', function () {
				$('.Grade-mask').hide()
			})

			// 点击修改自提地址
			$(document).on('click', '#addressClick', function () { 
				console.log('fff');
				$('.modifyAddresses_mask').show()
			})
		    $(document).on('click', '.modifyAddresses_mask .cancel, .modifyAddresses_mask .mask', function () {
				console.log('fff');
				$('.modifyAddresses_mask').hide()
			})
			

			$(document).on('click','#scancode',function(){

				if(motify.checkWeixin()){
					// motify.log('正在调用二维码功能');
					wx.scanQRCode({
						desc:'scanQRCode desc',
						needResult:1,
						scanType:["qrCode","barCode"],
						success:function (res){
							
							if (isNaN(res.resultStr)) {
								weui.alert('此核销码不存在！', {
									title: '提示',
									buttons: [{
										label: '好的'
									}]
								});
							} else {
								var url = '/wap.php?g=Wap&c=Village_group&a=leaderOrder'+'&code='+res.resultStr;
								if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
									if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
										url = 'https://' + document.domain + url;
									}
									wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent('打开新页面')});
								}else{
									window.location.href = url;
								}
							}
							// layer.open({type: 1, title: false, content: '调用成功！'});
						},
						error:function(res){
							// motify.log('微信返回错误！请稍后重试。',5);
							// layer.open({type: 1, title: false, content: '微信返回错误！请稍后重试。'});
						},
						fail:function(res){
							
							// motify.log('无法调用二维码功能');
							// alert(JSON.stringify(res));
							layer.open({type: 1, title: false, content: '无法调用二维码功能'});
						}
					});
				}else{
					
					// motify.log('您不是微信访问，无法使用二维码功能');
					layer.open({type: 1, title: false, content: '您不是微信访问，无法使用二维码功能'});
				}
			 });


			pageIndexMiniRefresh = new MiniRefresh({
				container: '#pageMy',
				// isLockX:false,
				down: {
					callback: function(){
						setTimeout(function(){
							pageIndexMiniRefresh.endDownLoading(true);
							location.href = '/wap.php?g=Wap&c=Village_group&a=my&leader_id='+leader_id;
							return false;
							showPageLoadTip = false;
							pageLoaded[nowPage] = null;
							$(".tabBtn[data-page='"+nowPage+"']").trigger('click');
						},600);
					},
					// 这个也生效
					isWrapCssTranslate: true,
					auto: true
				},
				up: {
					isLock:true,
					auto: true
				}
			});

			var needRefresh = $.cookie('needRefresh');
			if (needRefresh && needRefresh == 1 && pageIndexMiniRefresh != null) {
				pageIndexMiniRefresh.refreshOptions({down:{offset: 0}});
				pageIndexMiniRefresh.triggerDownLoading();
				pageIndexMiniRefresh.refreshOptions({down:{offset: 75}});
				$.cookie('needRefresh', 0);
			}
		},0);
	}
};
//页面级的选项卡
var pageLoaded = {};
pageLoaded[nowPage] = true;
//如果方法存在，则调用方法初始化页面
if(pageLoadedFunc[nowPage]){
	pageLoadedFunc[nowPage]();
}
$('.tabBtn').click(function(){
	if($(this).hasClass('my_option')){
		return ;
	}
	$(this).addClass('active').siblings('.tabBtn').removeClass('active');
	nowPage = $(this).data('page');
	var pageDom = $('.'+nowPage);
	pageDom.removeClass('hide').siblings('.page').addClass('hide');
	$('.pageChild').addClass('hide');
	$('.'+nowPage+'Child').removeClass('hide');
	//页面标题
	if(pageDom.data('pagetitle')){
		document.title = pageDom.data('pagetitle');
	}
	if (nowPage != 'pageMy') {
        $('#addressHtml').hide();
	}
  if(main_color && main_color!=''){
  	 $(".tabBtn").removeAttr("style");
  	 $(this).css({
		 color:main_color
	 })
  }
	//模拟更新网址栏，防止用户刷新时，还是原页面
	// var stateObj = { foo: nowPage };
	// history.replaceState(stateObj, "", pageDom.data('loadurl'));


	if(!pageLoaded[nowPage]){
		pageLoaded[nowPage] = true;
		if(showPageLoadTip){
			var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
		}
		pageDom.load(pageDom.data('loadurl')+' .'+nowPage+' .container',function(res){
			if(showPageLoadTip){
				layer.close(layerLoadTip);
			}
			showPageLoadTip = true;
			//如果方法存在，则调用方法初始化页面
			if(pageLoadedFunc[nowPage]){
				pageLoadedFunc[nowPage]();
			}
			if (nowPage == 'pageIndex') {
				$('#headerFixed').html($('#headerBox').html())
				$('#shareIcon').html( $('.shareBox').html())
				$('#indexShareMask').html( $('.shareMask').html()) 
			} else if (nowPage == 'pageMy') {
				$('#scanCodeMaskFixed').html($('#scanCodeMask').html()) 
				$('#modifyAddressesFixed').html($('#addressHtmlNews').html())  
				$('#GradeFixed').html( $('#Grade').html())
 
			}
			get_goods_cart();
		});
	}

	$('.fixed-box, .total-box, #shareIcon').hide()
	$('.footerMenu').height('49')
	
	if (nowPage == 'pageIndex') {
		$('.fixed-box, #shareIcon').show()
	} else if (nowPage == 'pageCart') {
        $('.total-box').show()
        $('.footerMenu').height('94')
    } else if (nowPage == 'pageMy') {
        $('#addressHtml').hide();
        $('#pageMy.page').css({'height':'calc(100% - 49px)','overflow':'auto'});
        $('#pageMy .container').css({'height':'','overflow':''});
        if (pageIndexMiniRefresh != null) {
        	pageIndexMiniRefresh.refreshOptions({down:{isLock:false}});
        }
    }

	get_goods_cart();
	
	
	
});
$(".tabBtn[data-page='"+nowPage+"']").trigger('click');



var pageIndexTimer = setInterval(function(){
	var goPageIndex = localStorage.getItem('goPageIndex');
	if(goPageIndex){
		localStorage.removeItem('goPageIndex');
		$(".tabBtn[data-page='pageIndex']").trigger('click');
	}
},800);


window.addEventListener('pageshow', function (e) {
	var cat_id = JSON.parse( $.cookie('cat_id') )
	//var goods_leader_id = JSON.parse($.cookie('goods_leader_id'))
	var goods_leader_id = $.cookie('goods_leader_id');
	if (goods_leader_id && goods_leader_id != '' && leader_id != goods_leader_id) {
		location.href = '/wap.php?g=Wap&c=Village_group&a=index&leader_id='+goods_leader_id + '&tmp=' + Math.round(Math.random() * 1000)
        $.cookie('goods_leader_id', '')
		return
	}
	console.log('进入pageshow', e, cat_id);
     if (e.persisted) {
     	console.log('页面刷新，阻止页面读取缓存')
        // window.location.reload()
        get_goods_by_cat_id(cat_id)
        goLeader()
     }
})

