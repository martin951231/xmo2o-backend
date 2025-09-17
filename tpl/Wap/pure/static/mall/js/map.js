
var map=null,mk=null;


//显示地图
function open_map(fun) {
    var  lats='';
    var lngs='';
    if(app){
        lats='';
        lngs=''
    }
    $("#map_baidu").show();
    $("#shap_manger").hide();
    map = new BMap.Map("map_show", {"enableMapClick":false});
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();

    var oPoint = new BMap.Point(116.331398,39.897445);
    var marker = new BMap.Marker(oPoint);

    // 创建标注 map.addOverlay(marker); marker.enableDragging();
    // 标注拖拽后的位置 marker.addEventListener("dragend", function (e) { alert("当前位置：" + e.point.lng + ", " + e.point.lat); });
    // 点击的位置 map.addEventListener("click", function (e) { //alert("当前位置：" + e.point.lng + ", " + e.point.lat);
    // var pointClick= new BMap.Point(e.point.lng , e.point.lat); map.openInfoWindow(infoWindow, pointClick); })
    var setPoint = function(mk,b){
        var pt = mk.getPosition();
        lngs = pt.lng;
        lats = pt.lat;
        (new BMap.Geocoder()).getLocation(pt,function(rs){
            addComp = rs.addressComponents;
            address_detail = addComp.street + addComp.streetNumber;
            search(rs.address,true,addComp.city)
        });

    };
    marker.enableDragging();
    map.centerAndZoom(oPoint, 16);
    if(lngs == '' || lats == ''){
        var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function(r){
            if(this.getStatus() == BMAP_STATUS_SUCCESS){
                oPoint = new BMap.Point(r.point.lng, r.point.lat);
                map.centerAndZoom(oPoint,16);
                marker.setPosition(oPoint);
                //search(setting.adress,true);
            } else {
                mui.alert('failed'+this.getStatus());
            }
        },{enableHighAccuracy: true})
    }else{
        oPoint = new BMap.Point(lngs, lats);
        map.centerAndZoom(oPoint, 16);
        marker.setPosition(oPoint);
        //search(setting.adress,true);
    }

    map.addControl(new BMap.NavigationControl());
    map.enableScrollWheelZoom();  //鍚敤婊氳疆鏀惧ぇ缂╁皬锛岄粯璁ょ鐢�
    map.enableContinuousZoom();
// 创建标注
    map.addOverlay(marker);
    marker.addEventListener("dragend", function(){
        setPoint(marker,true);
    });
    marker.addEventListener("click", function(e){
        setPoint(marker,true);
    });
    local = new BMap.LocalSearch(map,{
        pageCapacity:1,
        onSearchComplete:function(results){
            map.centerAndZoom(results.getPoi(0).point, 16);
            marker.setPosition(results.getPoi(0).point);
        }
    });

}
function setPlace(search_name) {
    map.clearOverlays();
    function myFun() {
        var pp = local.getResults().getPoi(0).point;
        map.centerAndZoom(pp, 18);
        map.addOverlay(new BMap.Marker(pp));
    }
    var local = new BMap.LocalSearch(map, {
        onSearchComplete: myFun
    });
    local.search(search_name);
}









