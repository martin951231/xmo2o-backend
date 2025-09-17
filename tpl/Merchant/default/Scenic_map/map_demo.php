<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<style type="text/css">
		body, html {width: 100%;height: 100%;margin:0;font-family:"微软雅黑";}
		#allmap{width:100%;height:100%;}
		p{margin:5px; font-size:14px;}
	</style>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=4c1bb2055e24296bbaef36574877b4e2"></script>
	<title>微观图</title>
</head>
<body>
	<div id="allmap"></div>
</body>
</html>
<script type="text/javascript">
	// 百度地图API功能
	var map = new BMap.Map('allmap');
	map.addControl(new BMap.NavigationControl());
	map.centerAndZoom(new BMap.Point(116.332782, 40.007978), 16);
	var tileLayer = new BMap.TileLayer({isTransparentPng: true});
	tileLayer.getTilesUrl = function(tileCoord, zoom) {
		var x = tileCoord.x;
		var y = tileCoord.y;
		return 'http://developer.baidu.com/map/jsdemo/demo/tiles/' + zoom + '/tile' + x + '_' + y + '.png';  //根据当前坐标，选取合适的瓦片图
	}

	var copyCtrl = new BMap.CopyrightControl({anchor: BMAP_ANCHOR_BOTTOM_RIGHT});
	copyCtrl.addCopyright({id: 1, content: "版权说明：清华校园图片取自互联网"});
	map.addControl(copyCtrl);

	function add_control(){
		map.addTileLayer(tileLayer);
	}
	function delete_control(){
		map.removeTileLayer(tileLayer);
	}
	add_control();
</script>