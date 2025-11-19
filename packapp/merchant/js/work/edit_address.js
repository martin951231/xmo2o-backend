/**
 * Created by win7 on 2018-11-05.
 */

mui.init();
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var ashop=common.getCache('ashop');
var ctuan=common.getCache('ctuan');
var byin=common.getCache('byin');
var discount_controler = 0;
common.http('Merchantapp&a=config',{'client':client,noTip:true}, function(data){
    discount_controler= data.discount_controler;
	if(!data.cash_alias_name){
		$('.pay_in_store').hide();
	}
});


var store_id=$.getUrlParam('store_id');
var status=$.getUrlParam('status');
var type=$.getUrlParam('type');
var setting={
    sing_up:'',
    chackchange:true,
    hashchange:0,
    long:'',
    lat:"",
    adress:'',
    add_location:{},
    star:false,
    map_init:true
}
var app = true;

var bind_name = 'input';
var wh=$(window).height();
var h=wh-250-45+'px';
$("#searchMore").css('height',h);
$("#searchAdd").css("height",(wh-45)+'px')
var map=null,mk=null;
var requestImgUrl = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//'+requestDomain;
if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
function trim(str){ //删除左右两端的空格
    return str.replace(/(^\s*)|(\s*$)/g, "");
}
//map()
if(common.checkIosApp()){
    common.iosFunction('getlocation/locationOk', {name: 'getlocation', callback: 'locationOk'});
}else if(common.checkAndroidApp()){
    window.pigcmspackapp.getlocation('locationOk');
}else{
    app=false
}

function locationOk(name,lng,lat){
    setting.add_location={
        lng:lng,
        lat:lat
    }
}





$('#txtAddress').keyup(function(){
    var address=$(this).val();
    search(address);
});
mui('body') .on('tap','.remove_input',function(e){
    if(back){
        $('#searchAdd').hide().html('');
        $('.remove_input').html('完成');
        $("#editmap_id").val("");
        $(".close_map").show();
    }else{
        console.log('setting', setting);
        lat=setting.lat;
        long=setting.long;
        if(setting.long!=''){
            $('#longitude').val(setting.long);
        }
        if(setting.lat!=''){
            $('#latitude').val(setting.lat);
        }
        if(setting.adress!=''){
            $('#adress').val(setting.adress);
        }
        $(".adress_detail").text(setting.adress);
        back_star()
    }
});
function back_star(){
    $("#map_baidu").hide();
    $("#edit").show();
    $(".close_map").show();
    $("#searchAdd").html("").hide();
    $("#editmap_id").val('')
}
function search(address,type,city){
    var params = {'query': address, 'city_limit': false};
    common.http('Map&a=suggestion', params, function (data) {
		var res=data;
		var datas=[];
		if(typeof(res)!='object'){
			res=JSON.parse(res)
		}
		if(res && res.length && res.length>0){
			datas=res;

		}else{
			if(res.status == 1){
				if(res.result && res.result.length>0){
					datas=res.result
				}
			}
		}
		if(!config.map_config || config.map_config == 'baidu'){
			if(type){
				laytpl(document.getElementById('add').innerHTML).render(datas, function(html){
					$('#searchMore').html(html).show();
					back=false;
				});
			}else{
				laytpl(document.getElementById('show_add').innerHTML).render(datas, function(html){
					$('#searchAdd').html(html).show();
					back=true;
				});
			}
		}else{
			if(type){
				laytpl(document.getElementById('google_add').innerHTML).render(datas, function(html){
					$('#searchMore').html(html).show();
					back=false;
				});
			}else{
				laytpl(document.getElementById('google_show_add').innerHTML).render(datas, function(html){
					$('#searchAdd').html(html).show();
					back=true;
				});
			}
		}
		
		if(common.checkIosApp()){
			$("#searchAdd").css("padding-top",'20px')
		}
	});
}


var validate=function(res,is_global){
    result=res.replace(/(^\s+)|(\s+$)/g,"");
    if(is_global.toLowerCase()=="g") {
        if(result==''){
            return false
        }else{
            return true
        }
    }
}
// 搜索框动态搜索
if(!common.isMobile()){
    $('#editmap_id').bind(bind_name ,function(){
        var val=$(this).val();
        $(".pt_45").css("padding-top",'45px')
        if(validate(val,'g')){
            $(".close_map").hide();
            $("#searchAdd").html("");
            $(".remove_input").text("取消");
            back=true;
            setTimeout(function(){
                search(result,false);
            },500)
        } else {
            $('#searchAdd').hide().html('');
            $('.remove_input').html('完成');
            $("#editmap_id").val("");
            $(".close_map").show();
        }
    })
}else{
    $('#myform').bind('search', function (e) {
        $(".pt_45").css("padding-top",'45px')
        var val=$(this).val();
        if(validate(val,'g')){
            $(".close_map").hide();
            $("#searchAdd").html("");
            $(".remove_input").text("取消");
            back=true;
            setTimeout(function(){
                search(result,false);
            },500)


        }
    });
}


function loadData(){
    $("#shap_manger").hide();
    $("#map_baidu").show();
    $(".close_map").hide();
    $("#searchAdd").html("");
    $(".remove_input").text("取消");
    back=true;
    search(result,false);
    $('#editmap_id').blur()
}
var config = common.getCache('config',true);
if(!config.map_config || config.map_config == 'baidu'){
	$.getScript('https://api.map.baidu.com/api?v=2.0&ak='+config.baidu_map_ak+'&callback=initialize_map');
}else{
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key='+config.google_map_ak;
	document.getElementsByTagName("head")[0].appendChild(script);
}

//地址点击
mui('.add_edit_address').on('tap','#color-gray-address',function(e){
    console.log('地址点击');
    $(".close_map").removeClass("mr_t45");
    $("#edit").hide();
    $('#map_baidu').show();
    if(!setting.star){
        open_map();
    }else{
        $(".close_map").addClass("mr_t45")
    }


});
//显示地图
function open_map(fun) {
    var  lats='';
    var lngs='';
    if(app){
        lats=setting.add_location.lat||'';
        lngs=setting.add_location.lng||''
    }else{
        lats=setting.lat;
        lngs=setting.long
    }
    var window_w=$(window).width();
    $("#searchMore").css({
        'top':window_w-29+'px',
        'left':'0px',
        'right':'0px'
    })
    $("#map_baidu").show();
    $("#shap_manger").hide();
	
	//谷歌地图不加载地图
	if(!config.map_config || config.map_config == 'baidu'){
		map = new BMap.Map("map_show",{"enableMapClick":false});
		map.enableScrollWheelZoom();
		map.enableContinuousZoom();
		$(".close_map").addClass("mr_t45")
		map.centerAndZoom(new BMap.Point(116.331398,39.897445),6);
		map.addEventListener("dragend", function(e){
			var centerMap = map.getCenter();
			getPositionInfo(centerMap.lat,centerMap.lng);
		});
		map.addEventListener('zoomend', function (e) {
			console.log(e)
			var centerMap = map.getCenter();
			console.log(centerMap)
			getPositionInfo(centerMap.lat,centerMap.lng);
		});
		console.log(type)
		if(lngs == '' || lats == ''|| type==0){
			var geolocation = new BMap.Geolocation();
			geolocation.getCurrentPosition(function(r){
				if(this.getStatus() == BMAP_STATUS_SUCCESS){
					var oPoint = new BMap.Point(r.point.lng, r.point.lat);
					map.centerAndZoom(oPoint,16);

					getPositionInfo(r.point.lat,r.point.lng);

				} else {
					mui.alert('failed'+this.getStatus());
				}
			},{enableHighAccuracy: true})

		}else{
			var oPoint = new BMap.Point(lngs, lats);
			map.centerAndZoom(oPoint, 16);
			getPositionInfo(lats,lngs);
		}
	}else{
		$('#map_baidu .dot').hide();
	}

}
function getPositionInfo(lat,lng){
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');

}
function getPositionAdress(result){
    console.log(result)
    if(result.status == 0){
        var res = result.result;
        laytpl(document.getElementById('add').innerHTML).render(res.pois, function(html){
            $('#searchMore').html(html).show();
            setting.lat=res.pois[0].point.y;
            setting.long=res.pois[0].point.x;
            setting.adress=res.pois[0].name;
            if(setting.map_init){
                map.centerAndZoom(new BMap.Point(res.pois[0].point.x,res.pois[0].point.y),16);
                setting.map_init=false
            }
            back=false;
            setting.star=true;
        });



    }else{
        alert('获取位置失败！');
    }
}
function setPlace(lat,lng) {
    if(back){
        getPositionInfo(lat,lng)
    }else{
        map.centerAndZoom(new BMap.Point(lng,lat),16);
    }


}
mui('body') .on('tap','.remove_input',function(e){
    if(back){
        $('#searchAdd').hide().html('');
        $('.remove_input').html('完成');
        $("#editmap_id").val("");
        $(".close_map").show();
    }else{
        lat=setting.lat;
        long=setting.long;
        if(setting.long!=''){
            $('#addressText').text(setting.lat+','+setting.long);
        }
        $(".adress_detail").text(setting.adress);
        back_star()
    }
});

//选择地址
mui('body') .on('tap','.allAddress li',function(e){
    $("#editmap_id").blur();
    $(".allAddress li .mui-left").removeClass("adres_check").addClass("adres_no_check");
    $(this).find(".mui-left").removeClass("adres_no_check").addClass("adres_check");
	
	
	if($(this).data('place_id')){
		common.http('Map&a=getAddressByPlaceId',{place_id: $(this).data('place_id')}, function(data){
			
			setting.lat=data.lat;
            setting.long=data.lng;
            setting.adress=data.adress;
			
			lat=data.lat;
			long=data.lng;
			if(data.lng!=''){
				$('#addressText').text(data.lat+','+data.lng);
			}
            if (data.lng!='') {
                $('#longitude').val(data.lng);
            }
            if (data.lat!='') {
                $('#latitude').val(data.lat);
            }
			$(".adress_detail").text(data.adress);
			back_star()
		});
	}else{
		var $_lat=setting.lat=$(this).data('lat');
		var $_long=setting.long=$(this).data('long');
		citySerach=$(this).data('city');
		var search_name=setting.adress=$(this).find('.recommend').text();
		if(!back){
			setting.map_init=false;
			setPlace($_lat,$_long)
			$('#searchAdd').hide().html('');
		}else{
			setting.map_init=true;
			$('#searchAdd').hide().html('');
			$('.remove_input').html('完成');
			$("#editmap_id").val("");
			$(".close_map").show();
			$('#searchMore').show().html('');
			setPlace($_lat,$_long)
		}
    }
});

//关闭按钮点击
$('body #middlePopover p i.mui-pull-right').click(function(e){
    mask.close();
    mui('#middlePopover').popover('hide');
});

mui('body').on('tap','.mui-backdrop',function(e){
    mask.close();
    mui('#middlePopover').popover('hide');
});

var mask = mui.createMask();   //遮罩层




