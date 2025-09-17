$(function () {
    paint_map()
});
var map, control, local;
var zoom = 12;

function paint_map() {
    var latlng;
    var long_lat = $('#long_lat').val().split(',');
    map = new T.Map('cmmap');
    //创建缩放平移控件对象
    control = new T.Control.Zoom();
    //添加缩放平移控件
    map.addControl(control);

    if ($('#long_lat').val() == '') {
        latlng = { lng: default_lng, lat: default_lat };
        //设置显示地图的中心点和级别
        map.centerAndZoom(new T.LngLat(latlng.lng, latlng.lat), 12);
    } else {
        latlng = { lng: parseFloat(long_lat[0]), lat: parseFloat(long_lat[1]) };
        map.centerAndZoom(new T.LngLat(latlng.lng, latlng.lat), 18);
    }

    //创建标注对象
    var marker = new T.Marker(new T.LngLat(latlng.lng, latlng.lat));
    //向地图上添加标注
    map.addOverLay(marker);
    marker.enableDragging();

    marker.addEventListener('dragend', function (target) {
        setPoint(marker)
    })
    marker.addEventListener("click", function (e) {
        setPoint(marker, true);
        var markerInfoWin = new T.InfoWindow("您的坐标设置成功！");
        marker.openInfoWindow(markerInfoWin)
    });
    //检索地址
    local = new T.LocalSearch(map, {
        pageCapacity: 1,
        onSearchComplete: function (results) {
            if(!results.getPois()){
                return
            }
            const { lonlat } = results.getPois()[0];
            long_lat = lonlat.split(',');
            oPoint = new T.LngLat(long_lat[0], long_lat[1]);
            map.centerAndZoom(oPoint, 17);
            marker.setLngLat(oPoint);
        }
    });
    // 手动输入经纬度
    $('.sure').on('click', function (event) {
        long_lat = $('#long_lat').val().split(',');
        oPoint = new T.LngLat(long_lat[0], long_lat[1]);
        map.centerAndZoom(oPoint, 18);
        marker.setLngLat(oPoint);
        setPoint(marker, true);
    });
    $('#map-search').submit(function () {
        $('#map-keyword').val($.trim($('#map-keyword').val()));
        if ($('#map-keyword').val().length > 0) {
            local.search($('#map-keyword').val());
        }
        return false;
    });
}

function setPoint(mk) {
    var pt = mk.getLngLat();
    $('#long_lat').val(pt.lng + ',' + pt.lat);
    var area_type_v = $('#area_type_v').val() || 0; //1省份，2城市，3区域 
    area_type_v = area_type_v ? parseInt(area_type_v) : 0;
    //创建逆地址解析对象
    let geocode = new T.Geocoder();
    geocode.getLocation(pt, (rs) => {
        var fulladdress = rs.formatted_address != undefined && rs.formatted_address ? rs.formatted_address : '';
        if (rs.addressComponents != undefined && rs.addressComponents.province != undefined && rs.addressComponents.province) {
            var fulladdress_tmp = rs.addressComponents.province;
            if (area_type_v == 1) {
                $('#area_map_name').val(rs.addressComponents.province)
            }
            if (rs.addressComponents.city != undefined && rs.addressComponents.city) {
                fulladdress_tmp += rs.addressComponents.city;
                if (area_type_v == 2) {
                    $('#area_map_name').val(rs.addressComponents.city)
                }
            }
            if (rs.addressComponents.county != undefined && rs.addressComponents.county) {
                fulladdress_tmp += rs.addressComponents.county;
                if (area_type_v == 3) {
                    $('#area_map_name').val(rs.addressComponents.county)
                }
            }
            if (rs.addressComponents.town != undefined && rs.addressComponents.town) {
                fulladdress_tmp += rs.addressComponents.town;
            }
            if (rs.addressComponents.town_code != undefined && rs.addressComponents.town_code) {
                fulladdress_tmp += rs.addressComponents.town_code;
            }
            if (!fulladdress) {
                fulladdress = fulladdress_tmp;
            }
        }
        $('#adress').val(fulladdress);
        if ($('#detail_address').size() > 0) {
            $('#detail_address').val(fulladdress);
        }
    });
}