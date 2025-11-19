var markers_circle = [];
var map;
if ($('#long_lat').val() == '') {
    var latlng = {lng: default_lng, lat: default_lat};
} else {
    var long_lat = $('#long_lat').val().split(',');
    var latlng = {lng: parseFloat(long_lat[0]), lat: parseFloat(long_lat[1])};
}

if (zoom == undefined) {
    var zoom = 4;
}
if(disable_edit_map != undefined && disable_edit_map == 1){
    var editable = false;
}else{
    var editable = true;
}

initMap();
var bermudaTriangle;
var drawingManager;
var selectedShape;

function paint_map(){
    if ($('#long_lat').val() != '') {
         long_lat = $('#long_lat').val().split(',');
         latlng = {lng: parseFloat(long_lat[0]), lat: parseFloat(long_lat[1])};
    }
    initMap();
}

function initMap() {
    map = new google.maps.Map(document.getElementById('cmmap'), {
        zoom: zoom,
        center: latlng,
		disableDefaultUI:true,
		zoomControl:true,
    });
    var infoWindow = new google.maps.InfoWindow({map: map});
    var marker = new google.maps.Marker({
        position: latlng,
        map: map,
        draggable: true
    });
    markers_circle.push(marker);

    //配送员自定义范围
    if (typeof(polygonMap) != 'undefined') {
        if($('#delivery_range_type').val()=='1') {
            var polygon = $('#delivery_range_polygon').val();
            if (typeof(polygon) != 'undefined' && polygon != '') {
                polygon = polygon.split('|');
                var polygonArr = [];
                for (var i in polygon) {
                    var arr = polygon[i].split('-');
					
					if(arr.length == 3){
						if(arr[0] == ''){
							arr[0] = '-' + arr[1];
							arr[1] = arr[2];
						}
					}
					
                    polygonArr.push({lat: +parseFloat(arr[0]), lng: +parseFloat(arr[1])});
                }
                bermudaTriangle = new google.maps.Polygon({
                    paths: polygonArr,
                    strokeColor: '#FF0000',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#FF0000',
                    fillOpacity: 0.35,
                    editable: editable
                });
                bermudaTriangle.setMap(map);
                //创建多边形
            }
            //检测点拖动
            google.maps.event.addListener(bermudaTriangle.getPath(), 'set_at', processVertex);
            google.maps.event.addListener(bermudaTriangle.getPath(), 'insert_at', processVertex);
            google.maps.event.addListener(bermudaTriangle.getPath(), 'remove_at', processVertex);

            function processVertex(e) {
                var logStr = [];
                for (var i = 0; i < this.getLength(); i++) {
                    logStr.push(this.getAt(i).lat() + '-' + this.getAt(i).lng());
                    $('#delivery_range_polygon').val(logStr.join('|'));
                }
            }
        }
    }
    function drawing_open(flag = false) {

        if (flag == false) {
            if (typeof polygonMap != "undefined") {
                bermudaTriangle.setMap(null);
            } else {
                drawingManager.setDrawingMode(null);
                deleteSelectedShape();
            }
            return false;
        } else {
            if (typeof polygon != 'undefined') {
                bermudaTriangle.setMap(null);
            }
        }
        //绘画工具 设置
        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: false,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [
                    google.maps.drawing.OverlayType.POLYGON
                ]
            },
            //设置图形显示样式
            circleOptions: {
                fillColor: '#ffff00',
                fillOpacity: 1,
                strokeWeight: 5,
                clickable: false,
                editable: true,
                zIndex: 1
            },
            polygonOptions: {
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
                editable: true,
            }
        });
        drawingManager.setMap(map);
        //注册 多边形 绘制完成事件
        google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
            var array = polygon.getPath().getArray();
            showLonLat(array);
        });
        //加入数组
        google.maps.event.addListener(drawingManager, 'overlaycomplete', function (e) {
            var newShape = e.overlay;
            newShape.type = e.type;

            if (e.type !== google.maps.drawing.OverlayType.MARKER) {
                drawingManager.setDrawingMode(null);

                google.maps.event.addListener(newShape, 'click', function (e) {
                    if (e.vertex !== undefined) {
                        if (newShape.type === google.maps.drawing.OverlayType.POLYGON) {
                            var path = newShape.getPaths().getAt(e.path);
                            path.removeAt(e.vertex);
                            if (path.length < 3) {
                                newShape.setMap(null);
                            }
                        }
                        if (newShape.type === google.maps.drawing.OverlayType.POLYLINE) {
                            var path = newShape.getPath();
                            path.removeAt(e.vertex);
                            if (path.length < 2) {
                                newShape.setMap(null);
                            }
                        }
                    }
                    setSelection(newShape);
                });
                setSelection(newShape);
                //检测点拖动
                google.maps.event.addListener(newShape.getPath(), 'set_at', processVertex);
                google.maps.event.addListener(newShape.getPath(), 'insert_at', processVertex);
                google.maps.event.addListener(newShape.getPath(), 'remove_at', processVertex);
                function processVertex(e) {
                    var logStr = [];
                    for (var i = 0; i < this.getLength(); i++) {
                        logStr.push(this.getAt(i).lat() + '-' + this.getAt(i).lng());
                        $('#delivery_range_polygon').val(logStr.join('|'));
                    }
                    console.log($('#delivery_range_polygon').val());
                }
            } else {
                google.maps.event.addListener(newShape, 'click', function (e) {
                    setSelection(newShape);
                });
                setSelection(newShape);
            }
        });

        //循环显示 经纬度
        var latlngs = [];

        function showLonLat(arr) {
            var bounds = new google.maps.LatLngBounds();

            for (var i = 0; i < arr.length; i++) {
                bounds.extend(arr[i]);
                latlngs.push(arr[i].lat() + '-' + arr[i].lng());
                $('#delivery_range_polygon').val(latlngs.join('|'));
            }
            $('#lng_lat').val(bounds.getCenter().lng() + ',' + bounds.getCenter().lat());
        }

        //切换代码结束
        function setSelection(shape) {
            selectedShape = shape;
        }

        function deleteSelectedShape() {
            if (selectedShape) {
                selectedShape.setMap(null);
            }
        }
    }

    var geocoder = new google.maps.Geocoder;

    //查找地理位置
    function geocodeLatLng(geocoder, map, latlng) {

        var request = {
            location: latlng,
            radius: '200'
        };

        service = new google.maps.places.PlacesService(map);
        service.nearbySearch(request, callback);

        function callback(results, status) {
            if (status == google.maps.places.PlacesServiceStatus.OK) {
                if (results.length > 1) {
                    $('#adress').val(results[1].name);
                } else {
                    $('#adress').val(results[0].name);
                }
            }
        }

    }

    //结束拖动
    google.maps.event.addListener(marker, 'dragend', function (MouseEvent) {
        var latlng = MouseEvent.latLng;
        $('#long_lat').val(latlng.lng() + ',' + latlng.lat());
        geocodeLatLng(geocoder, map, latlng);
    });

    //搜索
    var service = new google.maps.places.PlacesService(map);

    $('#map-search').submit(function (e) {
        e.preventDefault();
        $('#map-keyword').val($.trim($('#map-keyword').val()));
        if ($('#map-keyword').val().length > 0) {
            performSearch($('#map-keyword').val());
        }
        return false;
    });


    function performSearch(keyword) {
        var request = {
			location: map.getCenter(),
			radius: '50000',
            query: keyword
        };
        service.textSearch(request, callback);
    }

    function callback(results, status) {
		if (status !== google.maps.places.PlacesServiceStatus.OK) {
			console.error(status);
			return;
		}
		if(results.length > 0){
			for (var i = 0; i < markers_circle.length; i++) {
				markers_circle[i].setMap(null);
			}
			
			if(results.length == 1){
				console.log(results[0]);
				$('#adress').val(results[0].name);
				$('#long_lat').val(results[0].geometry.location.lng() + ',' + results[0].geometry.location.lat());
			}

			for (var i = 0, result; result = results[i]; i++) {
				if(i == 0){
					map.setCenter(result.geometry.location);
					map.setZoom(18);
				}
				addMarker(result);
			}
		}
    }

    function addMarker(place) {
        var marker = new google.maps.Marker({
            map: map,
            position: place.geometry.location
        });
        markers_circle.push(marker);
        google.maps.event.addListener(marker, 'click', function () {
			console.log(place);
			
			$('#long_lat').val(place.geometry.location.lng() + ',' + place.geometry.location.lat());
			$('#adress').val(place.name);
			infoWindow.setContent(place.name);
			infoWindow.open(map, marker);
				
        });
    }

    $('select[name=delivery_range_type]').change(function () {
        if ($(this).val() == 0) {
            $('.range').show();
            $('.diy').hide();
            drawing_open(false);
        } else {
            $('.range').hide();
            $('.diy').show();
            drawing_open(true);
            polygonArr = [];
        }
    });

}

