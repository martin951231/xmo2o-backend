var myScroll,wx,t,s,nomore = false;
$(function(){
    if(user_long == '0'){
        getUserLocation({errorAction:1,okFunction:'getRecommendList',errorFunction:'getRecommendList'});
    }else{
        getRecommendList('',user_long,user_lat);
    }

});

function mall_list_ajax() {

}


function mallGoods(cateId, isFlash) {

}


var like_page	=	0;
var page_count	=	10;
function getRecommendList(locationStr,lng,lat){
    if(!$.cookie('is_location_city_new')){
        if(lng){
            user_long = lng;
            user_lat = lat;
        }
        geocoder('locationCity',user_long,user_lat);
    }
}

function recommend_list_ajax(lng,lat,page){

}



function locationCity(result){
    if(result.result && result.result.addressComponent && result.result.addressComponent.city){
        var city_name = result.result.addressComponent.city;
        city_name = city_name.replace('市','');
        if(city_name != $('#cityBtn').html()){
            $.post('./wap.php?c=Home&a=cityMatching',{city_name:city_name},function(res){
                if(res.status == 1){
                    $.confirm({
                        title: '提示',
                        text: '当前定位到您在'+ city_name + '，是否进行切换？',
                        onOK: function () {
                            //点击确认
                            $.cookie('now_city',res.info.area_url,{expires:120,path:'/',domain:'.'+$('#cityBtn').data('top_domain')});
                            $.cookie('is_location_city_new','1',{path:'/',domain:'.'+$('#cityBtn').data('top_domain')});
                            location.reload();
                        },
                        onCancel: function () {
                            $.cookie('is_location_city_new','1',{path:'/',domain:'.'+$('#cityBtn').data('top_domain')});
                        }
                    });

                }
            });
        }
    }
}
