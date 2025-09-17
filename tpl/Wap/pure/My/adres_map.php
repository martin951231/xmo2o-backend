<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="keywords" content="{pigcms{$config.seo_keywords}"/>
    <meta name="description" content="{pigcms{$config.seo_description}"/>
    <title>选择位置</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_5e96991.css"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_dd39d16.css"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/address_9d295cd.css"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script src="/tpl/Static/js/coordinate.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?t=2"></script>
    <script type="text/javascript">
        var baidu_map_ak = "<php>echo C('config.baidu_map_ak');</php>";
        var uniapp_env = "<php>echo $_GET['uniapp_env'];</php>"; //uniapp的环境,h5/app
    </script>
    <style>
        /*加载层*/
        .motifyShade{
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            bottom:0;
            padding: 0;
            z-index: 998;
            width: 100%;
        }
        .motify {
            display: none;
            position: fixed;
            top: 35%;
            left: 50%;
            width: 260px;
            padding: 0;
            margin: 0 0 0 -130px;
            z-index: 999;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            font-size: 14px;
            line-height: 1.5em;
            border-radius: 6px;
            -webkit-box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .motify .motify-inner {
            padding: 10px 10px;
            text-align: center;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="hIphone" style="padding-bottom:initial;">
<div id="hidden_map" style="display: none;"></div>
<input type="hidden" id="now_center" value="" />
<input type="hidden" id="area_id"/>
<input type="hidden" id="city_id"/>
<input type="hidden" id="province_id"/>
<div id="wrapper">
    <div id="fis_elm__2">
        <div id="address-widget-map" class="address-widget-map">
            <div class="address-map-nav">
                <!--<div class="left-slogan" onclick="history.go(-1);">
                    <a class="left-arrow icon-arrow-left2"></a>
                </div>-->
                <div class="city_box">
                    <span>读取中</span>
                </div>
                <div class="center-title">
                    <div class="ui-suggestion-mask">
                        <img src="{pigcms{$static_path}img/quanquan.png" class="ui-mask-img">
                        <input type="text" placeholder="小区/写字楼/学校等" id="se-input-wd" autocomplete="off"/>
                        <div class="ui-suggestion-quickdel"></div>
                    </div>
                </div>
                <div class="his-postion" data-node="historypos" style="">
                    <div class="ui-suggestion" id="ui-suggestion-0" style="top: 0px; left: 0px; position: relative;">
                        <div class="ui-suggestion-content" style="-webkit-tap-highlight-color: rgba(255, 255, 255, 0);"></div>
                        <div class="ui-suggestion-button"><span class="ui-suggestion-clear" style="-webkit-tap-highlight-color: rgba(255, 255, 255, 0);">清除历史记录</span><span class="ui-suggestion-close" style="-webkit-tap-highlight-color: rgba(255, 255, 255, 0);"></span></div>
                    </div>
                </div>
            </div>
            <div id="fis_elm__3">
                <div class="map">
                    <div class="MapHolder" id="cmmap"></div>
                    <div class="dot" style="display:block;z-index: 999;"></div>
                    <div style="position: absolute;right: 10px;bottom: 10px" onclick="setCurrentLocation(false, true)">
                        <div style="width: 40px;height: 40px;background-color: #fff;border-radius: 50%;display: flex;align-items: center;justify-content: center;">
                            <img src="{pigcms{$static_path}img/location.png" style="width: 20px;height: 20px;">
                        </div>
                    </div>
                </div>
                <div class="mapaddress" data-node="mapaddress">
                    <ul id="addressShow"> </ul>
                </div>
            </div>
            <div id="fis_elm__4" style="display:none;">
                <section class="citylistBox">
                    <dl>
                        <volist name="all_city" id="vo">
                            <dt id="city_{pigcms{$key}" class="cityKey" data-city_key="{pigcms{$key}">{pigcms{$key}</dt>
                            <volist name="vo" id="voo">
                                <dd class="city_location" data-city_url="{pigcms{$voo.area_url}"  data-lnglat="{pigcms{$voo.area_lng},{pigcms{$voo.area_lat}">{pigcms{$voo.area_name}</dd>
                            </volist>
                        </volist>
                    </dl>
                </section>
                <div id="selectCharBox"></div>
            </div>
        </div>
    </div>
</div>
<div class="global-mask layout"></div>
{pigcms{$hideScript}
</body>
<script type="text/javascript" src="{pigcms{$static_path}js/SelectChar.js?210" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/hybrid_html_uni.webview.1.5.5.js" charset="utf-8"></script>
<script type="text/javascript">
    if(uniapp_env == 'app'){
        document.addEventListener('UniAppJSBridgeReady', function() {
            let env = ''
            uni.webView.getEnv(function(res) {
                env = JSON.parse(JSON.stringify(res))
                console.log('当前环境：' + JSON.stringify(res));
            });
            //向uniapp 的webview发送消息
            window.uniPostMessage = function(data){
                uni.webView.postMessage({
                    data: {
                        addressObj: data,
                        env: env
                    }
                });
            }
        });
    }
</script>
<if condition="$config['tianditu_web_key']">
<script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
<script type="text/javascript">
    var address = '';
    var timeout = 0;
    var map;
    var info = {};
    var userCurrentPosition = {};
    var address_info = {};
    $(document).ready(function(){
        $('.map .MapHolder').height(($(window).height()-50)*0.4);
        $('.mapaddress').height(($(window).height()-52)*0.6);

        $('#fis_elm__4').height($(window).height()-52);

        var cityKey = [];
        $.each($('.cityKey'),function(i,item){
            cityKey.push($(item).data('city_key'));
        });
        $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
            chars:cityKey,
            callback:function(ret){
                $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
            }
        });

        $('.city_location').click(function(){
            const [lng,lat] = $(this).data('lnglat').split(',')
            if((!lng || !lat) || (lng<=0 || lat<=0)){
                alert('当前城市信息不全');
                return
            }
            // 1. WGS-84(天地图) 转 BD-09(百度)
            const lnglat = Coordinate.wgs84ToBd09(lng*1,lat*1);
            $('.city_box span').html($(this).html());
            $('.city_box').removeClass('arrow');
            $('#fis_elm__3').show();
            $('#fis_elm__4').hide();

            map.centerAndZoom(new T.LngLat(lnglat[0],lnglat[1]), 16);
            setTimeout(function(){
                var centerMap = map.getCenter();
                getPositionInfo(centerMap.lat,centerMap.lng);
            },700);            });

        $('.city_box').click(function(){
            if($(this).hasClass('arrow')){
                $(this).removeClass('arrow');
                $('#fis_elm__3').show();
                $('#fis_elm__4').hide();
            }else{
                $(this).addClass('arrow');
                $('#fis_elm__3').hide();
                $('#fis_elm__4').show();
            }
        });

        // 百度地图API功能
        map = new T.Map("cmmap",{enableMapClick:false});
        map.centerAndZoom(new T.LngLat({pigcms{$now_city.area_lng},{pigcms{$now_city.area_lat}), 16);

        
        map.addEventListener("dragend", function(e){
            console.log(132)

            $('#addressShow').empty();
            var centerMap = map.getCenter();
            getPositionInfo(centerMap.lat,centerMap.lng);
        });

        $("#se-input-wd").bind('input', function(e){
            var address = $.trim($('#se-input-wd').val());
            if(address.length > 0){
                $('#cmmap').hide();
                $('.mapaddress').height(($(window).height()-52));

                $('#addressShow').empty();
                clearTimeout(timeout);
                timeout = setTimeout("search('"+address+"')", 500);
            }else{
                $('#cmmap').show();
                $('.mapaddress').height(($(window).height()-52)*0.6);
            }
        });

        $('#addressShow').delegate("li","click",function(){
            if($.cookie('user_address')){
                info = JSON.parse($.cookie('user_address'));
            }
            info.adress = $(this).attr("sname");
            info.longitude = $(this).attr("lng");
            info.latitude = $(this).attr("lat");
            if($(this).data('search')){
                let params = {
                    'lat':info.latitude,
                    'lng':info.longitude,
                    'get_province':'1',
                    'all_city':'1',
                    'coordinate_type':'wgs84'
                }
                $.post("{pigcms{:U('Home/cityMatching')}",params,function(res){
                    if(res.status == 1){
                        $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                        $('#province_id').val(res.info.province_id);
                        $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                        $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                        info.province = res.info.province_id;
                        info.city = res.info.city_id ? res.info.city_id : res.info.area_id;
                        info.area = res.info.now_area_id ? res.info.now_area_id : res.info.area_id;
                        $.cookie('user_address', JSON.stringify(info));
                        <?php if($_GET['from_uni']){ ?>
                        history.go(-1);
                        <?php }else if($_GET['from'] == 'uniapp'){ ?>
                        var addressObj = {};
                        addressObj.module = 'locationPicker';
                        addressObj.poiname = info.adress;
                        addressObj.city = $('#city_id').val();
                        addressObj.latlng = {
                            lat: info.latitude,
                            lng: info.longitude,
                        };
                        addressObj.address_info = address_info;
                        addressObj.coordinate_type = 'wgs84';
                        if(window.uniPostMessage){
                            window.uniPostMessage(addressObj);
                        }else{
                            window.parent.postMessage(addressObj, '*');
                        }
                        <?php }else{ ?>
                        location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                        <?php } ?>
                    }else{
                        alert('当前城市不可用');
                        return false;
                    }
                });

            }else{
                info.province = $('#province_id').val();
                info.city = $('#city_id').val();
                info.area = $('#area_id').val();
                $.cookie('user_address', JSON.stringify(info));
                <?php if($_GET['from_uni']){ ?>
                history.go(-1);
                <?php }else if($_GET['from'] == 'uniapp'){ ?>
                var addressObj = {};
                addressObj.module = 'locationPicker';
                addressObj.poiname = info.adress;
                addressObj.city = $('#city_id').val();
                addressObj.latlng = {
                    lat: info.latitude,
                    lng: info.longitude,
                };
                addressObj.address_info = address_info;
                addressObj.coordinate_type = 'wgs84';
                console.log(info,'完整地址66666');
                if(window.uniPostMessage){
                    window.uniPostMessage(addressObj);
                }else{
                    window.parent.postMessage(addressObj, '*');
                }
                <?php }else{ ?>
                location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                <?php } ?>
            }
        });

        // var geolocation = new BMap.Geolocation();


        var user_address = $.cookie('user_address');
        if(user_address){
            user_address = $.parseJSON(user_address);
            
            if (user_address.longitude === '' || user_address.latitude === '') {
                setCurrentLocation(true, true);
            } else {
                setCurrentLocation(true, false);
            }
        }
        
        if(user_address && user_address.longitude && user_address.latitude){
            console.log(1322,user_address)

            map.centerAndZoom(new T.LngLat(user_address.longitude,user_address.latitude), 16);
            getPositionInfo(user_address.latitude,user_address.longitude);
        }else{
            getUserLocation({useHistory:false,okFunction:'getIframe'});console.log(13422)
        }
    });
    
    //定义自定义覆盖物的构造函数
    function ComplexCustomOverlay(point){
        this._point = point;

    }
    // 继承API的BMap.Overlay
    ComplexCustomOverlay.prototype = new T.Overlay();
    //2、初始化自定义覆盖物
    // 实现初始化方法
    ComplexCustomOverlay.prototype.initialize = function(map1){
        // 保存map对象实例
        this._map = map1;
        // 创建div元素，作为自定义覆盖物的容器
        var div = this._div = document.createElement("div");
        div.style.position = "absolute";
        div.style.zIndex = T.Overlay.getZIndex(this._point.lat);//聚合功能?
        // 可以根据参数设置元素外观
        div.style.height = "10px";
        div.style.width="10px";

        var arrow = this._arrow = document.createElement("img");
        arrow.src = "{pigcms{$config.site_url}/tpl/Wap/pure/static/img/cricle.png";
        arrow.style.width = "20px";
        arrow.style.height = "20px";
        arrow.style.top = "10px";
        arrow.style.left = "10px";
        arrow.style.display = 'block';
        div.appendChild(arrow);

        // 将div添加到覆盖物容器中
        map.getPanes().labelPane.appendChild(div);//getPanes(),返回值:MapPane,返回地图覆盖物容器列表  labelPane呢???
        // 需要将div元素作为方法的返回值，当调用该覆盖物的show、
        // hide方法，或者对覆盖物进行移除时，API都将操作此元素。
        return div;

    }

    //3、绘制覆盖物
    // 实现绘制方法
    ComplexCustomOverlay.prototype.draw = function(){
        var map = this._map;
        var pixel = map.pointToOverlayPixel(this._point);
        this._div.style.left = pixel.x - parseInt(this._arrow.style.left) + "px";
        this._div.style.top  = pixel.y - parseInt(this._arrow.style.top) + "px";
    }



    //4、自定义覆盖物添加事件方法
    ComplexCustomOverlay.prototype.addEventListener = function(event,fun){
        this._div['on'+event] = fun;
    }

    function setCurrentLocation(flag = true, isDrawToLocation = true) {
        // 获取当前定位
        var geolocation = new T.Geolocation();
        geolocation.getCurrentPosition(function(r){
            if(this.getStatus() == 0 || this.getStatus() == 1){
                // var mk = new BMap.Marker(r.point);
                if(flag) {
                    var mk = new T.Circle(r.point, 250, {
                        fillColor: "blue",
                        strokeWeight: 1,
                        fillOpacity: 0.1,
                        strokeOpacity: 0.3
                    });
                    map.addOverlay(mk);

                    var myCompOverlay = new ComplexCustomOverlay(new T.LngLat(r.point.lng, r.point.lat));
                    map.addOverlay(myCompOverlay);//将标注添加到地图中
                }

                console.log('您的位置：'+r.point.lng+','+r.point.lat);
                if (isDrawToLocation) {
                    map.panTo(r.point);
                    getPositionInfo(r.point.lat,r.point.lng);
                }
            }
            else {
                console.log('failed'+this.getStatus());
            }
        },{enableHighAccuracy: true})
    }
    function getIframe(userLonglat,userLong,userLat){
        geoconv('realResult',userLong,userLat,'tianditu');
    }
    function realResult(result){
        var lng = result.x;
        var lat = result.y;
        userCurrentPosition = {lng:lng,lat:lat,coordinate_type: 'wgs84'}
        var point = new T.LngLat(lng, lat);
        map.centerAndZoom(point, 16);
        getPositionInfo(lat, lng);
    }
    function search(address){
        var postData = {city_id:$('#city_id').val(),query:address,city_limit:1};
        if(userCurrentPosition.lng){
            postData.lng = userCurrentPosition.lng;
            postData.lat = userCurrentPosition.lat;
        }else{
            const {lat,lng} = map.getCenter()
            postData.lng = lng
            postData.lat = lat
        }
        postData.coordinate_type = 'wgs84';
        $.get('index.php?g=Index&c=Map&a=suggestion', postData, function(data){
            if(data.status == 1){
                getAdress(data.result,true);
            }
        });
    }
    function getPositionLocation(result){
        if(result.status == 0){
            result = result.result;
            getPositionInfo(result.location.lat,result.location.lng);
        }else{
            alert('获取位置失败！');
        }
    }
    function getPositionInfo(lat,lng){;
        //用经纬度获取地理位置
        geocoder('',lng,lat,getPositionAdress,'tianditu')
    }
    function getPositionAdress(result){
        if(result.status == 0){
            result = result.result;
            let params = {
                'lat':result.location.lat,
                'lng':result.location.lon,
                'get_province':'1',
                'all_city':'1',
                'coordinate_type':'wgs84'
            }

            $.post("{pigcms{:U('Home/cityMatching')}",params,function(res){
                //新版商城我的收货地址新增几个字段
                address_info.province = result.addressComponent.province
                address_info.district = result.addressComponent.district
                address_info.street = result.addressComponent.street
                address_info.street_number = result.addressComponent.street_number
                address_info.city =  result.addressComponent.city
                if(res.status == 1){
                    $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                    $('#province_id').val(res.info.province_id);
                    $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                    $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                    var re = [];
                    if(result.addressComponent){
                        re.push({'name':result.addressComponent.address,'address':result.formatted_address,'long':result.location.lon,'lat':result.location.lat});
                    }
                    getAdress(re,false);
                }else{
                    alert('当前城市不可用');
                }
            });
        }else{
            alert('获取位置失败！');
        }
    }
    function getAdress(re,isSearch){
        $('#addressShow').html('');
        var addressHtml = '';
        for(var i=0;i<re.length;i++){
            if (re[i]['long'] == null || re[i]['lat'] == null) continue;
            addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'" sname="'+re[i]['name']+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[i]['city']+'" data-district="'+re[i]['district']+'"' : '')+'>';
            addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
            addressHtml += '<span class="icon-location" data-node="icon"></span>';
            addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i]['name']+' </span>';
            if(re[i].distance){
                addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
            }
            addressHtml += '</div>';
            addressHtml += '<div class="mapaddress-body"> '+re[i]['address']+' </div>';
            addressHtml += '</li>';
        }
        $('#addressShow').append(addressHtml);
    }
</script>
<else />
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key={pigcms{$config.google_map_ak}"></script>
    <script type="text/javascript">
        var address = '';
        var timeout = 0;
        var info = {};
        $(document).ready(function(){
            $('.map .MapHolder').height(($(window).height()-50)*0.4);
            $('.mapaddress').height(($(window).height()-52)*0.6);

            $('#fis_elm__4').height($(window).height()-52);

            var cityKey = [];
            $.each($('.cityKey'),function(i,item){
                cityKey.push($(item).data('city_key'));
            });
            $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
                chars:cityKey,
                callback:function(ret){
                    $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
                }
            });

            $('.city_location').click(function(){
                $('.city_box span').html($(this).html());
                $('.city_box').removeClass('arrow');
                $('#fis_elm__3').show();
                $('#fis_elm__4').hide();

                var geocoder =  new google.maps.Geocoder();
                geocoder.geocode( { 'address': $(this).html()}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        map.setCenter({lat:results[0].geometry.location.lat(),lng:results[0].geometry.location.lng()},16);
                        $('#now_center').val(results[0].geometry.location.lat()+','+results[0].geometry.location.lng());
                    } else {
                        alert("Something got wrong " + status);
                    }
                });
                setTimeout(function(){
                    var centerMap = map.getCenter();
                    getPositionInfo(centerMap.lat(),centerMap.lng());
                },700);
            });

            $('.city_box').click(function(){
                if($(this).hasClass('arrow')){
                    $(this).removeClass('arrow');
                    $('#fis_elm__3').show();
                    $('#fis_elm__4').hide();
                }else{
                    $(this).addClass('arrow');
                    $('#fis_elm__3').hide();
                    $('#fis_elm__4').show();
                }
            });

            // 谷歌地图API功能
            var map = new google.maps.Map(document.getElementById('cmmap'), {
                mapTypeControl:false,
                zoom: 16,
                center: {lng:parseFloat({pigcms{$now_city.area_lng}),lat:parseFloat({pigcms{$now_city.area_lat})}
            });
            //初始化
            $('#now_center').val('{pigcms{$now_city.area_lat},{pigcms{$now_city.area_lng}');
            $('.city_box span').html('{pigcms{$now_city.area_name}');
            getPositionInfo({pigcms{$now_city.area_lat},{pigcms{$now_city.area_lng});

            map.addListener("dragend", function(e){
                $('#addressShow').empty();
                var centerMap = map.getCenter();
                $('#now_center').val(centerMap.lat()+','+centerMap.lng());
                getPositionInfo(centerMap.lat(),centerMap.lng());
            });

            $("#se-input-wd").bind('input', function(e){
                var address = $.trim($('#se-input-wd').val());
                if(address.length > 0){
                    $('#cmmap').hide();
                    $('.mapaddress').height(($(window).height()-52));

                    $('#addressShow').empty();
                    clearTimeout(timeout);
                    timeout = setTimeout("search('"+address+"')", 500);
                }else{
                    $('#cmmap').show();
                    $('.mapaddress').height(($(window).height()-52)*0.6);
                }
            });

            $('#addressShow').delegate("li","click",function(){
                if($.cookie('user_address')){
                    info = JSON.parse($.cookie('user_address'));
                }
                info.adress = $(this).attr("sname");
                info.longitude = $(this).attr("lng");
                info.latitude = $(this).attr("lat");
                if($(this).data('search')){
                    $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':$(this).data('city'),'area_name':$(this).data('district'),'get_province':'1','all_city':'1'},function(res){
                        if(res.status == 1){
                            $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                            $('#province_id').val(res.info.province_id);
                            $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                            $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                            info.province = res.info.province_id;
                            info.city = res.info.city_id ? res.info.city_id : res.info.area_id;
                            info.area = res.info.now_area_id ? res.info.now_area_id : res.info.area_id;
                            $.cookie('user_address', JSON.stringify(info));
                            <?php if($_GET['from_uni']){ ?>
                            history.go(-1);
                            <?php }else{ ?>
                            location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                            <?php } ?>
                        }else{
                            alert('当前城市不可用');
                            return false;
                        }
                    });

                }else{
                    info.province = $('#province_id').val();
                    info.city = $('#city_id').val();
                    info.area = $('#area_id').val();
                    $.cookie('user_address', JSON.stringify(info));
                    <?php if($_GET['from_uni']){ ?>
                    history.go(-1);
                    <?php }else{ ?>
                    location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                    <?php } ?>
                }
            });


            var user_address = $.cookie('user_address');

            if(user_address){
                user_address = $.parseJSON(user_address);
            }
            if(user_address && user_address.longitude && user_address.latitude){
                map.setCenter({lng:parseFloat(user_address.longitude),lat:parseFloat(user_address.latitude)}, 16);
                $('#now_center').val(parseFloat(user_address.latitude)+','+parseFloat(user_address.longitude));
                getPositionInfo(user_address.latitude,user_address.longitude);
            }else{
                navigator.geolocation.getCurrentPosition(function(position){
                    map.setCenter({lng:parseFloat(position.coords.longitude),lat:parseFloat(position.coords.latitude)}, 16);
                    getPositionInfo(position.coords.latitude,position.coords.longitude);
                })
            }
        });

        function search(address){

            var map;
            var service;
            var centerMap = $('#now_center').val();
            centerMap = centerMap.split(',');

            if(centerMap){
                user_long = parseFloat(centerMap[1]);
                user_lat = parseFloat(centerMap[0]);
            }else{
                user_long = 116.403847;
                user_lat = 39.915526;
            }

            map = new google.maps.Map(document.getElementById('hidden_map'), {
                center:{lat:user_lat,lng:user_long},
                zoom: 15
            });

            var request = {
                bounds:map.getBounds(),
                query: address
            };

            service = new google.maps.places.PlacesService(map);
            service.textSearch(request, callback);

            function callback(results, status) {
                if (status == google.maps.places.PlacesServiceStatus.OK) {
                    getAdress(results,false,1);
                }
            }
        }

        function getPositionInfo(lat,lng){
            var map;
            var service;
            var centerMap = $('#now_center').val();
            centerMap = centerMap.split(',');
            if(centerMap){
                user_long = parseFloat(centerMap[1]);
                user_lat = parseFloat(centerMap[0]);
            } else{
                user_long = parseFloat(lng);
                user_lat = parseFloat(lat);
            }

            map = new google.maps.Map(document.getElementById('hidden_map'), {
                center:{lat:user_lat,lng:user_long},
                zoom: 15
            });

            var request = {
                location: {lat:user_lat,lng:user_long},
                radius: '200'
            };

            service = new google.maps.places.PlacesService(map);
            service.nearbySearch(request, callback);

            function callback(results, status) {
                if (status == google.maps.places.PlacesServiceStatus.OK) {
                    getAdress(results,false);
                }
            }
        }

        function getPositionAdress(result){
            if(result){
                var len = result.length-1;
                $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':result[0].name,'area_name':result[len].name,'get_province':'1','all_city':'1'},function(res) {
                    console.log(result);
                    console.log(res);
                    if(res.status == 1){
                        $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                        $('#province_id').val(res.info.province_id);
                        $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                        $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                        getAdress(result,false,0);
                    }else{
                        alert('当前城市不可用');
                    }
                });
            }else{
                alert('获取位置失败！');
            }

        }
        function getAdress(re,isSearch,isText){
            $('#addressShow').html('');
            var addressHtml = '';
            if(isText==1){
                for(var i=0;i<re.length;i++){
                    if (re[i].geometry.location.lng() == null || re[i].geometry.location.lat() == null) continue;
                    addressHtml += '<li lng="'+re[i].geometry.location.lng()+'" lat="'+re[i].geometry.location.lat()+'" sug_address="'+re[i].name+'" address="'+re[i].formatted_address+'" sname="'+re[i].name+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[0].name+'" data-district="'+re[re.length-1]['district']+'"' : '')+'>';
                    addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
                    addressHtml += '  <span class="icon-location" data-node="icon"></span>';
                    addressHtml += '  <span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i].name+' </span>';
                    if(re[i].distance){
                        addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
                    }
                    addressHtml += '</div>';
                    addressHtml += '<div class="mapaddress-body"> '+re[i].formatted_address+' </div>';
                    addressHtml += '</li>';
                }
            }else{
                var show_address = false;
                <?php if($config['google_map_position']){ ?>
                show_address = true;
                addressHtml += '<li lng="' + user_long + '" lat="' + user_lat + '" sug_address="当前位置" address="当前位置" sname="当前位置" class="addresslist">';
                addressHtml += '<div class="mapaddress-title" style="margin-top:6px;">';
                addressHtml += '  <span class="icon-location" data-node="icon"></span>';
                addressHtml += '  <span class="recommend"> [当前位置] </span>';
                addressHtml += '</div>';
                addressHtml += '<div class="mapaddress-body">  </div>';
                addressHtml += '</li>';
                <?php } ?>
                for(var i=1;i<re.length-1;i++){
                    if (re[i].geometry.location.lng() == null || re[i].geometry.location.lat() == null) continue;
                    addressHtml += '<li lng="'+re[i].geometry.location.lng()+'" lat="'+re[i].geometry.location.lat()+'" sug_address="'+re[i].name+'" address="'+re[i].vicinity+'" sname="'+re[i].name+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[0].name+'" data-district="'+re[re.length-1]['district']+'"' : '')+'>';
                    addressHtml += '<div class="mapaddress-title '+(i!=1 || show_address ? 'notself' : '')+'">';
                    addressHtml += '  <span class="icon-location" data-node="icon"></span>';
                    addressHtml += '  <span class="recommend"> '+(i == 1 && show_address == false ? '[建议位置]' : '')+'   '+re[i].name+' </span>';
                    if(re[i].distance){
                        addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
                    }
                    addressHtml += '</div>';
                    addressHtml += '<div class="mapaddress-body"> '+re[i].vicinity+' </div>';
                    addressHtml += '</li>';
                }
            }
            $('#addressShow').append(addressHtml);
        }
    </script>
<elseif condition="!$is_wxapp_browser || $weixin_browser_type == 'android'"/>
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config['baidu_map_ak_web']}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript">
        var address = '';
        var timeout = 0;
        var map;
        var info = {};
        var userCurrentPosition = {};
        var address_info = {};
        console.log(13)
        $(document).ready(function(){
            $('.map .MapHolder').height(($(window).height()-50)*0.4);
            $('.mapaddress').height(($(window).height()-52)*0.6);

            $('#fis_elm__4').height($(window).height()-52);

            var cityKey = [];
            $.each($('.cityKey'),function(i,item){
                cityKey.push($(item).data('city_key'));
            });
            $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
                chars:cityKey,
                callback:function(ret){
                    $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
                }
            });

            $('.city_location').click(function(){
                $('.city_box span').html($(this).html());
                $('.city_box').removeClass('arrow');
                $('#fis_elm__3').show();
                $('#fis_elm__4').hide();

                map.centerAndZoom($(this).html(), 16);
                setTimeout(function(){
                    var centerMap = map.getCenter();
                    getPositionInfo(centerMap.lat,centerMap.lng);
                },700);            });

            $('.city_box').click(function(){
                if($(this).hasClass('arrow')){
                    $(this).removeClass('arrow');
                    $('#fis_elm__3').show();
                    $('#fis_elm__4').hide();
                }else{
                    $(this).addClass('arrow');
                    $('#fis_elm__3').hide();
                    $('#fis_elm__4').show();
                }
            });

            // 百度地图API功能
            map = new BMap.Map("cmmap",{enableMapClick:false});
            map.centerAndZoom(new BMap.Point({pigcms{$now_city.area_lng},{pigcms{$now_city.area_lat}), 16);



            map.addEventListener("dragend", function(e){
                console.log(132)

                $('#addressShow').empty();
                var centerMap = map.getCenter();
                getPositionInfo(centerMap.lat,centerMap.lng);
            });

            $("#se-input-wd").bind('input', function(e){
                var address = $.trim($('#se-input-wd').val());
                if(address.length > 0){
                    $('#cmmap').hide();
                    $('.mapaddress').height(($(window).height()-52));

                    $('#addressShow').empty();
                    clearTimeout(timeout);
                    timeout = setTimeout("search('"+address+"')", 500);
                }else{
                    $('#cmmap').show();
                    $('.mapaddress').height(($(window).height()-52)*0.6);
                }
            });

            $('#addressShow').delegate("li","click",function(){
                if($.cookie('user_address')){
                    info = JSON.parse($.cookie('user_address'));
                }
                info.adress = $(this).attr("sname");
                info.longitude = $(this).attr("lng");
                info.latitude = $(this).attr("lat");
                if($(this).data('search')){
                    $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':$(this).data('city'),'area_name':$(this).data('district'),'get_province':'1','all_city':'1'},function(res){
                        if(res.status == 1){
                            $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                            $('#province_id').val(res.info.province_id);
                            $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                            $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                            info.province = res.info.province_id;
                            info.city = res.info.city_id ? res.info.city_id : res.info.area_id;
                            info.area = res.info.now_area_id ? res.info.now_area_id : res.info.area_id;
                            $.cookie('user_address', JSON.stringify(info));
                            <?php if($_GET['from_uni']){ ?>
                            history.go(-1);
                            <?php }else if($_GET['from'] == 'uniapp'){ ?>
                            var addressObj = {};
                            addressObj.module = 'locationPicker';
                            addressObj.poiname = info.adress;
                            addressObj.city = $('#city_id').val();
                            addressObj.latlng = {
                                lat: info.latitude,
                                lng: info.longitude,
                            };
                            addressObj.address_info = address_info;
                            if(window.uniPostMessage){
                                window.uniPostMessage(addressObj);
                            }else{
                                window.parent.postMessage(addressObj, '*');
                            }
                            <?php }else{ ?>
                            location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                            <?php } ?>
                        }else{
                            alert('当前城市不可用');
                            return false;
                        }
                    });

                }else{
                    info.province = $('#province_id').val();
                    info.city = $('#city_id').val();
                    info.area = $('#area_id').val();
                    $.cookie('user_address', JSON.stringify(info));
                    <?php if($_GET['from_uni']){ ?>
                    history.go(-1);
                    <?php }else if($_GET['from'] == 'uniapp'){ ?>
                    var addressObj = {};
                    addressObj.module = 'locationPicker';
                    addressObj.poiname = info.adress;
                    addressObj.city = $('#city_id').val();
                    addressObj.latlng = {
                        lat: info.latitude,
                        lng: info.longitude,
                    };
                    addressObj.address_info = address_info;
                    console.log(info,'完整地址66666');
                    if(window.uniPostMessage){
                        window.uniPostMessage(addressObj);
                    }else{
                        window.parent.postMessage(addressObj, '*');
                    }
                    <?php }else{ ?>
                    location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                    <?php } ?>
                }
            });

            // var geolocation = new BMap.Geolocation();


            var user_address = $.cookie('user_address');
            if(user_address){
                user_address = $.parseJSON(user_address);
				
				if (user_address.longitude === '' || user_address.latitude === '') {
					setCurrentLocation(true, true);
				} else {
					setCurrentLocation(true, false);
				}
            }
			
            if(user_address && user_address.longitude && user_address.latitude){
                console.log(1322)

                map.centerAndZoom(new BMap.Point(user_address.longitude,user_address.latitude), 16);
                getPositionInfo(user_address.latitude,user_address.longitude);
            }else{
                getUserLocation({useHistory:false,okFunction:'getIframe'});console.log(13422)
                /* geolocation.getCurrentPosition(function(r){
                    if(this.getStatus() == BMAP_STATUS_SUCCESS){
                        map.centerAndZoom(new BMap.Point(r.point.lng,r.point.lat), 16);
                        getPositionInfo(r.point.lat,r.point.lng);
                    }else{
                        alert('failed：'+this.getStatus());
                    }
                },{enableHighAccuracy: true}); */
            }
        });


        //定义自定义覆盖物的构造函数
        function ComplexCustomOverlay(point){
            this._point = point;

        }
        // 继承API的BMap.Overlay
        ComplexCustomOverlay.prototype = new BMap.Overlay();
        //2、初始化自定义覆盖物
        // 实现初始化方法
        ComplexCustomOverlay.prototype.initialize = function(map1){
            // 保存map对象实例
            this._map = map1;
            // 创建div元素，作为自定义覆盖物的容器
            var div = this._div = document.createElement("div");
            div.style.position = "absolute";
            div.style.zIndex = BMap.Overlay.getZIndex(this._point.lat);//聚合功能?
            // 可以根据参数设置元素外观
            div.style.height = "10px";
            div.style.width="10px";

            var arrow = this._arrow = document.createElement("img");
            arrow.src = "{pigcms{$config.site_url}/tpl/Wap/pure/static/img/cricle.png";
            arrow.style.width = "20px";
            arrow.style.height = "20px";
            arrow.style.top = "10px";
            arrow.style.left = "10px";
            arrow.style.display = 'block';
            div.appendChild(arrow);

            // 将div添加到覆盖物容器中
            map.getPanes().labelPane.appendChild(div);//getPanes(),返回值:MapPane,返回地图覆盖物容器列表  labelPane呢???
            // 需要将div元素作为方法的返回值，当调用该覆盖物的show、
            // hide方法，或者对覆盖物进行移除时，API都将操作此元素。
            return div;

        }

        //3、绘制覆盖物
        // 实现绘制方法
        ComplexCustomOverlay.prototype.draw = function(){
            var map = this._map;
            var pixel = map.pointToOverlayPixel(this._point);
            this._div.style.left = pixel.x - parseInt(this._arrow.style.left) + "px";
            this._div.style.top  = pixel.y - parseInt(this._arrow.style.top) + "px";
        }



        //4、自定义覆盖物添加事件方法
        ComplexCustomOverlay.prototype.addEventListener = function(event,fun){
            this._div['on'+event] = fun;
        }

        function setCurrentLocation(flag = true, isDrawToLocation = true) {
            // 获取当前定位
            var geolocation = new BMap.Geolocation();
            geolocation.getCurrentPosition(function(r){
                if(this.getStatus() == BMAP_STATUS_SUCCESS){
                    // var mk = new BMap.Marker(r.point);
                    if(flag) {
                        var mk = new BMap.Circle(r.point, 250, {
                            fillColor: "blue",
                            strokeWeight: 1,
                            fillOpacity: 0.1,
                            strokeOpacity: 0.3
                        });
                        map.addOverlay(mk);

                        var myCompOverlay = new ComplexCustomOverlay(new BMap.Point(r.point.lng, r.point.lat));
                        map.addOverlay(myCompOverlay);//将标注添加到地图中
                    }

                    console.log('您的位置：'+r.point.lng+','+r.point.lat);
                    if (isDrawToLocation) {
                        map.panTo(r.point);
                        getPositionInfo(r.point.lat,r.point.lng);
                    }
                }
                else {
                    console.log('failed'+this.getStatus());
                }
            },{enableHighAccuracy: true})
        }
        function getIframe(userLonglat,userLong,userLat){
            geoconv('realResult',userLong,userLat);
        }
        function realResult(result){
            console.log(result);
            var lng = result.result[0].x;
            var lat = result.result[0].y;
            userCurrentPosition = {lng:lng,lat:lat}
            var point = new BMap.Point(lng, lat);
            // alert(JSON.stringify(result));
            map.centerAndZoom(point, 16);
            getPositionInfo(lat, lng);
        }
        function search(address){
            var postData = {city_id:$('#city_id').val(),query:address,city_limit:1};
            if(userCurrentPosition.lng){
                postData.lng = userCurrentPosition.lng;
                postData.lat = userCurrentPosition.lat;
            }
            $.get('index.php?g=Index&c=Map&a=suggestion', postData, function(data){
                if(data.status == 1){
                    // if(data.result[0] && data.result[0].city && data.result[0].district){
                    // 例：东莞市 万江区 ，只有市则无法搜索出列表
                    if(data.result[0] && data.result[0].city){
                        getAdress(data.result,true);
                    }
                }
            });
        }
        function getPositionLocation(result){
            if(result.status == 0){
                result = result.result;
                getPositionInfo(result.location.lat,result.location.lng);
            }else{
                alert('获取位置失败！');
            }
        }
        function getPositionInfo(lat,lng){
            console.log(lat,lng);
            //用经纬度获取地理位置
            geocoder('',lng,lat,getPositionAdress)
        }
        function getPositionAdress(result){
            if(result.status == 0){
                result = result.result;
                $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':result.addressComponent.city,'area_name':result.addressComponent.district,'get_province':'1','all_city':'1'},function(res){
                    console.log(result);
                    //新版商城我的收货地址新增几个字段
                    address_info.province = result.addressComponent.province
                    address_info.district = result.addressComponent.district
                    address_info.street = result.addressComponent.street
                    address_info.street_number = result.addressComponent.street_number
                    address_info.city =  result.addressComponent.city
                    console.log(res);
                    if(res.status == 1){
                        $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                        $('#province_id').val(res.info.province_id);
                        $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                        $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                        var re = [];
                        if(result.sematic_description.indexOf("附近0米") < 0){
                            re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
                        }
                        for(var i in result.pois){
                            re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
                        }
                        getAdress(re,false);
                    }else{
                        alert('当前城市不可用');
                    }
                });
            }else{
                alert('获取位置失败！');
            }
        }
        function getAdress(re,isSearch){
            $('#addressShow').html('');
            var addressHtml = '';
            for(var i=0;i<re.length;i++){
                if (re[i]['long'] == null || re[i]['lat'] == null) continue;
                addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'" sname="'+re[i]['name']+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[i]['city']+'" data-district="'+re[i]['district']+'"' : '')+'>';
                addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
                addressHtml += '<span class="icon-location" data-node="icon"></span>';
                addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i]['name']+' </span>';
                if(re[i].distance){
                    addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
                }
                addressHtml += '</div>';
                addressHtml += '<div class="mapaddress-body"> '+re[i]['address']+' </div>';
                addressHtml += '</li>';
            }
            $('#addressShow').append(addressHtml);
        }
    </script>
<else/>
	<script type="text/javascript">
        var address = '';
        var timeout = 0;
        var map;
        var info = {};
        var userCurrentPosition = {};
        console.log(13)
        $(document).ready(function(){
			$('.map').remove();
            $('.map .MapHolder').height(($(window).height()-50)*0.4);
            $('.mapaddress').height(($(window).height()-52)*0.6);

            $('#fis_elm__4').height($(window).height()-52);

            var cityKey = [];
            $.each($('.cityKey'),function(i,item){
                cityKey.push($(item).data('city_key'));
            });
            $("#selectCharBox").css({'float':'right',height:($(window).height()-50),width:50,'z-index':9998}).seleChar({
                chars:cityKey,
                callback:function(ret){
                    $('#fis_elm__4').scrollTop(($('#city_'+ret).position().top) + $('#fis_elm__4').scrollTop() - 50);
                }
            });

            $('.city_location').click(function(){
                $('.city_box span').html($(this).html());
                $('.city_box').removeClass('arrow');
                $('#fis_elm__3').show();
                $('#fis_elm__4').hide();
				
				
				$.post("{pigcms{:U('Home/cityMatching')}",{'city_name':$(this).html(),'get_province':'1','all_city':'1'},function(res){
					if(res.status == 1){
						$('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
						$('#province_id').val(res.info.province_id);
						$('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
						$('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
					}else{
						alert('当前城市不可用');
						return false;
					}
				});
            });

            $('.city_box').click(function(){
                if($(this).hasClass('arrow')){
                    $(this).removeClass('arrow');
                    $('#fis_elm__3').show();
                    $('#fis_elm__4').hide();
                }else{
                    $(this).addClass('arrow');
                    $('#fis_elm__3').hide();
                    $('#fis_elm__4').show();
                }
            });

            $("#se-input-wd").bind('input', function(e){
                var address = $.trim($('#se-input-wd').val());
                if(address.length > 0){
                    $('#cmmap').hide();
                    $('.mapaddress').height(($(window).height()-52));

                    $('#addressShow').empty();
                    clearTimeout(timeout);
                    timeout = setTimeout("search('"+address+"')", 500);
                }else{
                    $('#cmmap').show();
                    $('.mapaddress').height(($(window).height()-52)*0.6);
                }
            });

            $('#addressShow').delegate("li","click",function(){
                if($.cookie('user_address')){
                    info = JSON.parse($.cookie('user_address'));
                }
                info.adress = $(this).attr("sname");
                info.longitude = $(this).attr("lng");
                info.latitude = $(this).attr("lat");
                if($(this).data('search')){
                    $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':$(this).data('city'),'area_name':$(this).data('district'),'get_province':'1','all_city':'1'},function(res){
                        if(res.status == 1){
                            $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                            $('#province_id').val(res.info.province_id);
                            $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                            $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                            info.province = res.info.province_id;
                            info.city = res.info.city_id ? res.info.city_id : res.info.area_id;
                            info.area = res.info.now_area_id ? res.info.now_area_id : res.info.area_id;
                            $.cookie('user_address', JSON.stringify(info));
                            <?php if($_GET['from_uni']){ ?>
                            history.go(-1);
                            <?php }else if($_GET['from'] == 'uniapp'){ ?>
                            var addressObj = {};
                            addressObj.module = 'locationPicker';
                            addressObj.poiname = info.adress;
                            addressObj.city = $('#city_id').val();
                            addressObj.latlng = {
                                lat: info.latitude,
                                lng: info.longitude,
                            };
                            if(window.uniPostMessage){
                                window.uniPostMessage(addressObj);
                            }else{
                                window.parent.postMessage(addressObj, '*');
                            }
                            <?php }else{ ?>
                            location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                            <?php } ?>
                        }else{
                            alert('当前城市不可用');
                            return false;
                        }
                    });

                }else{
                    info.province = $('#province_id').val();
                    info.city = $('#city_id').val();
                    info.area = $('#area_id').val();
                    $.cookie('user_address', JSON.stringify(info));
                    <?php if($_GET['from_uni']){ ?>
                    history.go(-1);
                    <?php }else if($_GET['from'] == 'uniapp'){ ?>
                    var addressObj = {};
                    addressObj.module = 'locationPicker';
                    addressObj.poiname = info.adress;
                    addressObj.city = $('#city_id').val();
                    addressObj.latlng = {
                        lat: info.latitude,
                        lng: info.longitude,
                    };
                    console.log(info,'完整地址');
                    if(window.uniPostMessage){
                        window.uniPostMessage(addressObj);
                    }else{
                        window.parent.postMessage(addressObj, '*');
                    }
                    <?php }else{ ?>
                    location.href = "{pigcms{:U('My/edit_adress',$params)}&adress_id="+info.id;
                    <?php } ?>
                }
            });
			
			var user_address = $.cookie('user_address');
            if(user_address){
                user_address = $.parseJSON(user_address);
            }
			
            if(user_address && user_address.longitude && user_address.latitude){
                console.log(1322)
                getPositionInfo(user_address.latitude,user_address.longitude);
            }else{
                getUserLocation({useHistory:false,okFunction:'getIframe',errorFunction:'getIframeError'});console.log(13422)
            }
        });

		function getIframeError(){
			$.post("{pigcms{:U('Home/cityMatching')}",{'city_name':'','get_province':'1','all_city':'1'},function(res){
				if(res.status == 1){
					$('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
					$('#province_id').val(res.info.province_id);
					$('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
					$('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
				}else{
					alert('当前城市不可用');
					return false;
				}
			});
		}
		
		function getIframe(userLonglat,userLong,userLat){
            geoconv('realResult',userLong,userLat);
        }
        function realResult(result){
            console.log(result);
            var lng = result.result[0].x;
            var lat = result.result[0].y;
            getPositionInfo(lat, lng);
        }
		
        function search(address){
            var postData = {city_id:$('#city_id').val(),query:address,city_limit:1};
            if(userCurrentPosition.lng){
                postData.lng = userCurrentPosition.lng;
                postData.lat = userCurrentPosition.lat;
            }
            $.get('index.php?g=Index&c=Map&a=suggestion', postData, function(data){
                if(data.status == 1){
                    if(data.result[0] && data.result[0].city && data.result[0].district){
                        getAdress(data.result,true);
                    }
                }
            });
        }
        function getPositionLocation(result){
            if(result.status == 0){
                result = result.result;
                getPositionInfo(result.location.lat,result.location.lng);
            }else{
                alert('获取位置失败！');
            }
        }
        function getPositionInfo(lat,lng){
            console.log(lat,lng);
            geocoder('',lng,lat,getPositionAdress)
        }
        function getPositionAdress(result){
            if(result.status == 0){
                result = result.result;
                $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':result.addressComponent.city,'area_name':result.addressComponent.district,'get_province':'1','all_city':'1'},function(res){
                    console.log(result);
                    console.log(res);
                    if(res.status == 1){
                        $('.city_box span').html(res.info.city_name ? res.info.city_name : res.info.area_name);
                        $('#province_id').val(res.info.province_id);
                        $('#city_id').val(res.info.city_id ? res.info.city_id : res.info.area_id);
                        $('#area_id').val(res.info.now_area_id ? res.info.now_area_id : res.info.area_id);
                        var re = [];
                        if(result.sematic_description.indexOf("附近0米") < 0){
                            re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
                        }
                        for(var i in result.pois){
                            re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
                        }
                        getAdress(re,false);
                    }else{
                        alert('当前城市不可用');
                    }
                });
            }else{
                alert('获取位置失败！');
            }
        }
        function getAdress(re,isSearch){
            $('#addressShow').html('');
            var addressHtml = '';
            for(var i=0;i<re.length;i++){
                if (re[i]['long'] == null || re[i]['lat'] == null) continue;
                addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'" sname="'+re[i]['name']+'" class="addresslist" '+(isSearch ? 'data-search="true" data-city="'+re[i]['city']+'" data-district="'+re[i]['district']+'"' : '')+'>';
                addressHtml += '<div class="mapaddress-title '+(i!=0 ? 'notself' : '')+'">';
                addressHtml += '<span class="icon-location" data-node="icon"></span>';
                addressHtml += '<span class="recommend"> '+(i == 0 ? '[建议位置]' : '')+'   '+re[i]['name']+' </span>';
                if(re[i].distance){
                    addressHtml += '  <span class="distance"> '+re[i].distance+'km </span>';
                }
                addressHtml += '</div>';
                addressHtml += '<div class="mapaddress-body"> '+re[i]['address']+' </div>';
                addressHtml += '</li>';
            }
            $('#addressShow').append(addressHtml);
        }
    </script>
</if>
</if>
</html>