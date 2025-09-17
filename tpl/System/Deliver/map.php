<include file="Public:header"/>


    <input type="hidden" name="id" value="{pigcms{$custom['id']}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <td>
                <p><img src="/static/images/map_icon/red.png"/><strong>骑手当前位置：</strong>距离商家{pigcms{$toMerDistance}，距离用户{pigcms{$toUserDistance}</strong></p>
                <p><img src="/static/images/map_icon/green.png"/><strong>商家所在地：</strong>{pigcms{$positions[0].title}</p>
                <p><img src="/static/images/map_icon/blue.png"/><strong>用户收货地址：</strong>{pigcms{$positions[1].title}</p>
            </td>
        </tr>
        
        <tr class="delivery_range_type">
            <td><div id="allmap" style="height:500px;"></div></td>
        </tr>
    </table>


<if condition="$config['tianditu_web_key']">
    <script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function(){
        var map = new T.Map('allmap');
        var point = new T.LngLat("{pigcms{$positions[2].lng}", "{pigcms{$positions[2].lat}");  // 创建点坐标  
        map.centerAndZoom(point, 15);                 // 初始化地图，设置中心点坐标和地图级别 
        map.enableScrollWheelZoom();  
        //创建比例尺控件对象
        var scale = new T.Control.Scale();
        //添加比例尺控件
        map.addControl(scale);

        var redIcon = new T.Icon({
            iconUrl: "/static/images/map_icon/red.png",
            iconSize: new T.Point(22,26),
        });
        var blueIcon = new T.Icon({
            iconUrl: "/static/images/map_icon/blue.png",
            iconSize: new T.Point(22,26),
        });
        var greenIcon = new T.Icon({
            iconUrl: "/static/images/map_icon/green.png",
            iconSize: new T.Point(22,26),
        });

        var marker = new T.Marker(point,{icon:redIcon});        // 创建标注    
        map.addOverLay(marker);   // 将标注添加到地图中 

        var marker1 = new T.Marker(new T.LngLat("{pigcms{$positions[0].lng}", "{pigcms{$positions[0].lat}"),{icon:greenIcon});
        var marker2 = new T.Marker(new T.LngLat("{pigcms{$positions[1].lng}", "{pigcms{$positions[1].lat}"),{icon:blueIcon});
        map.addOverLay(marker1);                   
        map.addOverLay(marker2);                   

    });
    </script>
<else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?v=3.0&ak={pigcms{:C('config.baidu_map_ak_web')}" charset="utf-8"></script>
    <script>
    $(document).ready(function(){
        var map = new BMap.Map('allmap');
        var point = new BMap.Point("{pigcms{$positions[2].lng}", "{pigcms{$positions[2].lat}");  // 创建点坐标  
        map.centerAndZoom(point, 15);                 // 初始化地图，设置中心点坐标和地图级别 
        map.enableScrollWheelZoom(true);  
        map.addControl(new BMap.ScaleControl()); 

        var redIcon = new BMap.Icon("/static/images/map_icon/red.png",new  BMap.Size(22,26));
        var blueIcon = new BMap.Icon("/static/images/map_icon/blue.png",new  BMap.Size(22,26));
        var greenIcon = new BMap.Icon("/static/images/map_icon/green.png",new  BMap.Size(22,26));

        var marker = new BMap.Marker(point,{icon:redIcon});        // 创建标注    
        map.addOverlay(marker);   // 将标注添加到地图中 
        marker.setAnimation(BMAP_ANIMATION_BOUNCE); //跳


        
        var marker1 = new BMap.Marker(new BMap.Point("{pigcms{$positions[0].lng}", "{pigcms{$positions[0].lat}"),{icon:greenIcon});
        var marker2 = new BMap.Marker(new BMap.Point("{pigcms{$positions[1].lng}", "{pigcms{$positions[1].lat}"),{icon:blueIcon});
        map.addOverlay(marker1);                   
        map.addOverlay(marker2);                   

    });
    </script>
</if>

<include file="Public:footer"/>