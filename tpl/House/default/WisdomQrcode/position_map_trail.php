<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
        body, html,#allmap {width: 100%;height: 100%;overflow: hidden;margin:0;font-family:"微软雅黑";}
    </style>
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    <title>巡检路线</title>
    <style>
        .but{
            display: inline-block;position: absolute;top:5px;left: 5px;padding:10px;z-index: 9999;
        }
    </style>
    <script src="http://c.cnzz.com/core.php"></script></head>
<body>
<div class="but">
    <a href="javascript:history.back(-1)" style="text-decoration: none;">
        <span style="color: black;">查看【{pigcms{$_GET['title']}】负责人</span>
        <br>
        <span style="color: red">返回上一页</span>
    </a>
</div>
<div id="allmap" style="position: relative"></div>
</body>
</html>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript">
    var dataList={pigcms{$res['list']};
    // 百度地图API功能
    var map = new BMap.Map("allmap");    // 创建Map实例
    map.centerAndZoom(new BMap.Point({pigcms{$res['point']['long']},{pigcms{$res['point']['lat']}), 15);  // 初始化地图,设置中心点坐标和地图级别
    map.addControl(new BMap.MapTypeControl());   //添加地图类型控件
    map.setCurrentCity("北京市");          // 设置地图显示的城市 此项是必须设置的
    map.enableScrollWheelZoom(true);     //开启鼠标滚轮缩放
    var carMk;
    var myIcon = new BMap.Icon("https://lbsyun.baidu.com/jsdemo/img/car.png", new BMap.Size(50,25), {imageOffset: new BMap.Size(0, 0)});//卡车

    if(dataList.length > 0){
        for (var i=0;i<dataList.length;i++) {
            drawIcon(dataList[i].start_long,dataList[i].start_lat,dataList[i].end_long,dataList[i].end_lat);
        }
    }
    function drawGreenLine(startLong,startLat,endLong,endLat){
        var polyline = new BMap.Polyline([
            new BMap.Point(startLong,startLat),//起始点的经纬度
            new BMap.Point(endLong,endLat)//终止点的经纬度
        ], {strokeColor:"green",//设置颜色
            strokeWeight:3, //宽度
            strokeOpacity:1});//透明度
        map.addOverlay(polyline);
    }

    function drawIcon(startLong,startLat,endLong,endLat){
        if(carMk){
            map.removeOverlay(carMk);
        }
        carMk = new BMap.Marker(
            new BMap.Point(endLong,endLat),//起始点的经纬度
            // {icon:myIcon}
            );
        map.addOverlay(carMk);
        drawGreenLine(startLong,startLat,endLong,endLat);
    }
</script>