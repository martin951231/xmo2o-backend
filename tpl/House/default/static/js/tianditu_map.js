$(function () {
	var local = null;

	var map = null;
	var oPoint = null;

	//小区基本设置页面默认展示对应位置
	if ($('#long_lat').size() > 0 && $.trim($('#long_lat').val()) != '') {
		var oPointArr = $.trim($('#long_lat').val()).split(',');
		const [lng, lat] = Coordinate.bd09ToWgs84(oPointArr[0], oPointArr[1])//转天地图wgs84坐标系
		oPointArr = [lng, lat];
		oPoint = new T.LngLat(oPointArr[0], oPointArr[1]);
		console.log('oPointArr', oPointArr);
	} else {
		oPoint = new T.LngLat(116.331398, 39.897445);
	}

	var marker = new T.Marker(oPoint);
	var setPoint = function (mk, b) {
		var pt = mk.getLngLat();
		const [lng, lat] = Coordinate.wgs84ToBd09(pt.lng, pt.lat)//转百度坐标系
		$('#long_lat').val(lng + ',' + lat);
		(new T.Geocoder()).getLocation(pt, function (rs) {
			addComp = rs.addressComponent;
			if (b === true) {
				if (addComp.province && typeof ($('#choose_province')) != 'undefined') {
					$.each($('#choose_province option'), function (i, item) {
						var text = $(item).html();
						if (text && addComp.province.indexOf(text) != -1) {
							var choose_province = $('#choose_province');
							choose_province.find('option').eq(i).prop('selected', true);
							show_city(choose_province.find('option:selected').attr('value'), choose_province.find('option:selected').html(), 1);
							return false;
						}
					});
				}
				if (addComp.city && typeof ($('#choose_city')) != 'undefined') {
					$.each($('#choose_city option'), function (i, item) {
						var text = $(item).html();
						if (text && addComp.city.indexOf(text) != -1) {
							var choose_city = $('#choose_city');
							choose_city.find('option').eq(i).prop('selected', true);
							show_area(choose_city.find('option:selected').attr('value'), choose_city.find('option:selected').html(), 1);
							return false;
						}
					});
				}
				if (addComp.district && typeof ($('#choose_area')) != 'undefined') {
					$.each($('#choose_area option'), function (i, item) {
						var text = $(item).html();
						if (text && addComp.district.indexOf(text) != -1) {
							var choose_area = $('#choose_area');
							choose_area.find('option').eq(i).prop('selected', true);
							show_circle(choose_area.find('option:selected').attr('value'), choose_area.find('option:selected').html(), 1);
							return false;
						}
					});
				}
				// $('#adress').val(addComp.street + addComp.streetNumber);
			}
		});
	};

	map = new T.Map("cmmap");
	map.enableScrollWheelZoom();
	map.centerAndZoom(oPoint, 12);

	function myFun(result) {
		oPoint = new T.LngLat(result.lnglat.lng, result.lnglat.lat);
		map.centerAndZoom(oPoint, 12);
		marker.setLngLat(oPoint);
	}
	if ($('#long_lat').size() == 0 || $.trim($('#long_lat').val()) == '') {
		var myCity = new T.LocalCity();
		myCity.location(myFun)
	}

	map.addControl(new T.Control.Zoom());
	map.enableScrollWheelZoom();

	map.addOverLay(marker);
	marker.enableDragging();

	var info_opts = {
		width: 100,
		height: 30,
		title: "提示：",
		enableMessage: false,
		message: ""
	};
	var infoWindow = new T.InfoWindow("您的坐标设置成功！", info_opts);

	marker.addEventListener("dragend", function () {
		setPoint(marker, true);
	});
	marker.addEventListener("click", function (e) {
		setPoint(marker, true);
		map.openInfoWindow(infoWindow, marker.getPosition());
	});
	local = new T.LocalSearch(map, {
		pageCapacity: 1,
		onSearchComplete: function (results) {
			let pois = results.getPois() || []
			if (pois.length) {
				let item = pois[0]
				let point = new T.LngLat(item.lonlat.split(',')[0], item.lonlat.split(',')[1]);
				map.centerAndZoom(point, 17);
				marker.setLngLat(point);
			}
		}
	});

	$('#map-search').submit(function () {
		$('#map-keyword').val($.trim($('#map-keyword').val()));
		if ($('#map-keyword').val().length > 0) {
			local.search($('#map-keyword').val());
		}

		return false;
	});;
});