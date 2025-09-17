<!DOCTYPE html>
 <html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui">
	<meta name="format-detection" content="telephone=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<title>{pigcms{$storeName.name}</title>
	<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
	<meta name="description" content="{pigcms{$config.seo_description}" />
    <link href="{pigcms{$static_path}shop/css/order_detail.css" rel="stylesheet"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
</head>

<body>
<section class="public">
    <a class="return" href="{pigcms{:U('Integral_mall/order_detail_new', array('order_id' => $order['order_id']))}"></a>
    <div class="content">订单状态</div>
</section>
<div class="h44"></div>
<section class="g_details">
    <div class="orders_list">
        <ul>
            <volist name="status" id="vo">
            <li>
                <div class="time">{pigcms{$vo.date_line|date="Y-m-d H:i",###}</div>
                <div class="p18">
                    <div class="con">
                        <if condition="$vo['status'] eq 0"> <h2>订单生成成功</h2> <p>订单编号：{pigcms{$order.orderid}</p>
                        <elseif condition="$vo['status'] eq 1"/> <h2>订单支付成功</h2> <p>订单编号：{pigcms{$order.orderid}</p>
                        <elseif condition="$vo['status'] eq 2"/> <h2>商家发货成功</h2>
                        <elseif condition="$vo['status'] eq 3"/> <h2>用户收货</h2>
                        <elseif condition="$vo['status'] eq 4"/> <h2>已退款</h2>
                        <elseif condition="$vo['status'] eq 5"/> <h2>用户已自提<if condition="$order.from_type eq 2">(商家操作)</if></h2>
                        </if>
                        <if condition="$i eq 1">
                        <div class="map clr">
                            <div class="pho" id="allmap"></div>
                            <div class="refresh fr"></div>
                        </div>
                        </if>
                    </div>
                </div>
            </li>
            </volist>
        </ul>
    </div>
</section>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&key={pigcms{$config.google_map_ak}"></script>
    <script type="text/javascript">
        var status = '{pigcms{$order['order_status']}';
        if (status > 1 && status < 5) {
            $(document).ready(function(){
                line();
                $('.refresh').click(function(){
                    line();
                });
            });
        } else {
            $('.map').remove();
        }

        function line()
        {
            $.get('{pigcms{:U('Shop/line', array('order_id' => $order['order_id']))}', function(response){
            if (response.err_code == false) {
                var lines = response.lines, center = response.center, points = response.points;
                var map = new google.maps.Map(document.getElementById('allmap'), {
                    mapTypeControl:false,
                    zoom: 13,
                    center: {lng:parseFloat(center.lng),lat:parseFloat(center.lat)}
                });
                var point = {lng:parseFloat(center.lng), lat:parseFloat(center.lat)};
                var from_point = {lng:parseFloat(points.from_site.lng), lat:parseFloat(points.from_site.lat)};
                var aim_point ={lng:parseFloat(points.aim_site.lng), lat:parseFloat(points.aim_site.lat)};

                var deliverIcon = new google.maps.Marker({
                    position: point,
                    map:map,
                    icon:  {url:'{pigcms{$static_path}/images/map/deliver_pos.png', anchor: new google.maps.Point(16, 16)}
                });

                var storeIcon = new google.maps.Marker({
                    position: from_point,
                    map:map,
                    icon:  {url:'{pigcms{$static_path}/images/map/store_pos.png', anchor: new google.maps.Point(16, 16)}
                });

                var userIcon =  new google.maps.Marker({
                    position: aim_point,
                    map:map,
                    icon:  {url:'{pigcms{$static_path}/images/map/my_pos.png', anchor: new google.maps.Point(32, 32)}
                });

                if (lines != null) {
                    var polygonArr = [];
                    for (var i in lines) {
                        polygonArr.push({lng:lines[i].lng, lat:lines[i].lat});
                    }
                    var flightPath = new google.maps.Polyline({
                        path: polygonArr,
                        geodesic: true,
                        strokeColor: 'green',
                        strokeOpacity: 0.5,
                        strokeWeight: 2
                    });

                    flightPath.setMap(map);

                    if (response.status < 4) {
                        var flightPath = new google.maps.Polyline({
                            path:[point, from_point],
                            geodesic: true,
                            strokeColor: 'red',
                            strokeOpacity: 0.5,
                            strokeWeight: 2
                        });
                        flightPath.setMap(map);
                    } else {
                        var flightPath = new google.maps.Polyline({
                            path:[point, aim_point],
                            geodesic: true,
                            strokeColor: 'red',
                            strokeOpacity: 0.5,
                            strokeWeight: 2
                        });
                        flightPath.setMap(map);

                    }
                } else if (response.status < 4) {
                    var flightPath = new google.maps.Polyline({
                        path:[point, from_point, aim_point],
                        geodesic: true,
                        strokeColor: 'red',
                        strokeOpacity: 0.5,
                        strokeWeight: 2
                    });
                    flightPath.setMap(map);
                } else {
                    var flightPath = new google.maps.Polyline({
                        path:[from_point, point, aim_point],
                        geodesic: true,
                        strokeColor: 'red',
                        strokeOpacity: 0.5,
                        strokeWeight: 2
                    });
                    flightPath.setMap(map);
                }
            }
        }, 'json');
        }
    </script>
    <else />
<script src="https://api.map.baidu.com/api?v=2.0&ak=4c1bb2055e24296bbaef36574877b4e2"></script>
<script type="text/javascript">
var status = '{pigcms{$order['order_status']}';
if (status > 1 && status < 5) {
    $(document).ready(function(){
    	line();
    	$('.refresh').click(function(){
    		line();
        });
    });
} else {
	$('.map').remove();
}

function line()
{
	$.get('{pigcms{:U('Shop/line', array('order_id' => $order['order_id']))}', function(response){
		if (response.err_code == false) {
			var lines = response.lines, center = response.center, points = response.points;
			var map = new BMap.Map("allmap", {"enableMapClick":false});
			var point = new BMap.Point(center.lng, center.lat);
	        var from_point = new BMap.Point(points.from_site.lng, points.from_site.lat);
	        var aim_point = new BMap.Point(points.aim_site.lng, points.aim_site.lat);
	        map.centerAndZoom(point, 13);
	        map.enableScrollWheelZoom();

	        var deliverIcon = new BMap.Icon("{pigcms{$static_path}/images/map/deliver_pos.png", new BMap.Size(22,60));
	        map.addOverlay(new BMap.Marker(point, {icon:deliverIcon}));

	        var storeIcon = new BMap.Icon("{pigcms{$static_path}/images/map/store_pos.png", new BMap.Size(22,60));
	        map.addOverlay(new BMap.Marker(from_point, {icon:storeIcon}));
	        var userIcon = new BMap.Icon("{pigcms{$static_path}/images/map/my_pos.png", new BMap.Size(60,60));
	        map.addOverlay(new BMap.Marker(aim_point, {icon:userIcon}));

	        if (lines != null) {
	        	var polygonArr = [];
	        	for (var i in lines) {
	    			polygonArr.push(new BMap.Point(lines[i].lng, lines[i].lat));
	        	}
	        	var polyline = new BMap.Polyline(polygonArr, {strokeColor:"green", strokeWeight:2, strokeOpacity:0.5}); //创建折线
	        	map.addOverlay(polyline); //增加折线
	        }

	        var polyline = new BMap.Polyline([point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
	        map.addOverlay(polyline);
		}
	}, 'json');
}
</script>
</if>
</body>
</html>