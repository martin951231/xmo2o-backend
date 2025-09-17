<include file="Public:header"/>

<form id="myform" method="post" action="{pigcms{:U('NewGridding/customSave')}" frame="true" refresh="true" style="margin-left:0px;padding: 20px 40px;">
    <input type="hidden" name="id" value="{pigcms{$custom['id']}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th style="width: 12rem;">选择管理模式</th>
            <td>
                <select name="pattern_model" id="pattern_model" class="" style=" margin-right:10px;">
                    <option value="0" <if condition="!isset($_GET['pattern_model']) || $_GET['pattern_model'] eq 0">selected="selected"</if>>只看模式</option>
                   <!-- <if condition="!$_GET['fid']"><option value="1" <if condition="$_GET['pattern_model'] eq 1">selected="selected"</if>>编辑模式</option></if>-->
                </select>
                <span class="pattern_model_tips form_tips_0" <if condition="isset($_GET['pattern_model']) && $_GET['pattern_model'] neq 0">style="display:none;"</if>>该模式下只能查看每个网格的管理员信息，不能添加、编辑区域及网格员</span>
                <span class="pattern_model_tips form_tips_1" <if condition="!isset($_GET['pattern_model']) || $_GET['pattern_model'] eq 0">style="display:none;"</if>>编辑模式下可移动鼠标在地图上规划区域后编辑区域名称及对应的网格员</span>
            </td>
        </tr>
        <tr class="delivery_range_type add_range" style="position: absolute;right:40px;top:70px;display: none;">
            <td>
                <input type="button" class="btn btn-info" value="绘制网格" id="baiduMap"/>
            </td>
        </tr>
        <tr class="delivery_range_type">
            <!--记录当前区块整体关键信息id-->
            <input type="hidden" name="custom_id" id="custom_id" value="{pigcms{$custom_id}"/>
            <input type="hidden" name="bind_id" id="bind_id" value="{pigcms{$bind_id}"/>
            <input type="hidden" name="type" id="type" value="{pigcms{$type}"/>
            <input type="hidden" name="fid" id="fid" value="{pigcms{$fid}"/>
            <input type="hidden" name="id" id="id_val"/>
            <!--循环处理下记录-->
            <div class="manage_range_polygon_box">
                <!--给予新添加的区块做存储记录-->
                <input type="hidden" name="manage_range_polygon" id="manage_range_polygon" />
                <if condition="$custom_polygon">
                    <volist name="custom_polygon" id="polygon_val">
                        <input type="hidden" id="manage_range_polygon_{pigcms{$polygon_val['id']}"/>
                    </volist>
                </if>
            </div>
            <!--网格员信息-->
            <div class="custom_polygon_box">
                <if condition="$custom_polygon">
                    <volist name="custom_polygon" id="polygon_val">
                        <input type="hidden" id="charge_box_val_{pigcms{$polygon_val['id']}" value="{pigcms{$polygon_val['polygon_name']}"/>
                        <div class="charge_box_{pigcms{$polygon_val['id']}" style="display: none;">
                            <if condition="$polygon_val['polygon_name']">
                                <div class="charge_box">
                                    <div class="polygon_title">{pigcms{$polygon_val['polygon_name']}</div>
                                    <div class="charge_content">
                                        <img src="{pigcms{$polygon_val['charge_avatar']}">
                                        <div class="charge_txt">
                                            <div class="charge_txt_name">
                                                {pigcms{$polygon_val['charge_name']}
                                            </div>
                                            <div class="charge_txt_phone">
                                                {pigcms{$polygon_val['charge_phone']}
                                            </div>
                                        </div>
                                        <div class="look_img_box" onclick="custom_polygon_add({pigcms{$polygon_val['custom_id']},{pigcms{$polygon_val['id']},1);">
                                            <img class="look_img" src="{pigcms{$static_path}area_street/img/look.png">
                                        </div>
                                        <div class="edit_img_box" onclick="custom_polygon_add({pigcms{$polygon_val['custom_id']},{pigcms{$polygon_val['id']},0);" style="display: none;">
                                            <img class="edit_img" src="{pigcms{$static_path}area_street/img/edit.png">
                                        </div>
                                    </div>
                                </div>
                                <else/>
                                <div class="charge_box">
                                    <div class="add_box" onclick="custom_polygon_add({pigcms{$polygon_val['custom_id']},{pigcms{$polygon_val['id']},0);">
                                        <span>+</span> 添加网格员
                                    </div>
                                </div>
                            </if>
                        </div>

                    </volist>
                </if>
            </div>

            <div class="show_info" style="display: none">

            </div>

            <!--记录当前所处地图中心-->
            <input type="hidden" name="lng_lat" id="lng_lat" value="{pigcms{$custom['lng_lat']}"/>
            <td colspan="2"><div id="allmap" style="height:600px;margin:20px 0;border: 1px solid #dcdee0;"></div></td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<style>
    #baiduMap{
        background-color: #1686ef !important;
    }

    .btn-info {
        background-color: #1686ef;
        border-color: #1686ef !important;
    }
    .charge_box {
        padding: 10px 20px;
        background-color: white;
        width: 250px;
    }
    .polygon_title {
        width: 100%;
        text-align: center;
        font-size: 16px;
        font-weight: 600;
    }
    .charge_content {
        padding: 20px 0;
        display: flex;
    }
    .charge_content img {
        vertical-align:middle;
        width: 50px;
        height: 50px;
    }
    .charge_txt {
        height: 50px;
        margin-left: 10px;
    }
    .charge_txt div {
        line-height: 25px;
    }
    .charge_content .look_img,.charge_content .edit_img {
        width: 20px;
        height: 20px;
    }
    .charge_content .look_img_box,.charge_content .edit_img_box {
        margin-left: 10px;
    }
    .add_box {
        padding: 5px 10px;
        background-color: #1686ef;
        color: white;
        text-align: center;
        font-size: 16px;
        font-weight: 400;
        cursor:pointer;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_path}js/area_deliver.js"></script>
<script>
    var scrollHeight = document.body.scrollHeight;
    var availHeight = window.screen.availHeight-150;
    var height = scrollHeight;
    var timeoutID = '';
    var map;
    var map_size = 15;
    if (parseInt("{pigcms{$map_size}")>0) {
        map_size = parseInt("{pigcms{$map_size}");
    }
    console.log('map_size',map_size);
    if (availHeight>scrollHeight) {
        height = availHeight;
    }
    $('#allmap').css('height',height);
    var pattern_model=0;

    function close_all_page(info) {
        console.log('info',info)
        if (info.id) {
            var id = info.id;
            var custom_id = info.custom_id;
            var html = '<div class="charge_box">';
            html += '    <div class="polygon_title">'+info.polygon_name+'</div>';
            html += '    <div class="charge_content">';
            html += '        <img src="'+info.charge_avatar+'">';
            html += '        <div class="charge_txt">';
            html += '            <div class="charge_txt_name">'+info.charge_name+'</div>';
            html += '            <div class="charge_txt_phone">'+info.charge_phone+'</div>';
            html += '        </div>';
            if (pattern_model==1) {
                html += '        <div class="look_img_box" style="display: none;">';
            } else {
                html += '        <div class="look_img_box" onclick="custom_polygon_add('+custom_id+','+id+',1);">';
            }
            html += '            <img class="look_img" src="{pigcms{$static_path}area_street/img/look.png">';
            html += '        </div>';
            if (pattern_model==0) {
                html += '        <div class="edit_img_box" style="display: none;">';
            } else {
                html += '        <div class="edit_img_box" onclick="custom_polygon_add('+custom_id+','+id+',0);">';
            }
            html += '            <img class="look_img"  src="{pigcms{$static_path}area_street/img/edit.png">';
            html += '        </div>';
            html += '    </div>';
            html += '</div>';
            $('.charge_box_'+id).html(html);
        }
        layer.closeAll();
    }

    // 区块添加和编辑
    function custom_add(id,poly) {
        if (id>0) {
            $('#id_val').val(id);
        }
        $.post($('#myform').attr('action'),$('#myform').serialize(),function(data){
            console.log('添加或者编辑后获得返回',data)
            if (data.errorCode) {
                layer.open({
                    content: data.errorMsg
                    ,skin: 'msg'
                    ,time: 2 //2秒后自动关闭
                });
            } else {
                var result = data.result;
                if (result.id>0 && (!id || id <= 0)) {
                    var val = $('#manage_range_polygon').val();
                    var id = result.id;
                    if($('#manage_range_polygon_'+id).length>0) {
                        $('#manage_range_polygon_'+id).val(val);
                    } else {
                        var html = '<input type="hidden" id="manage_range_polygon_'+id+'" value="'+val+'"/>';
                        $('.manage_range_polygon_box').append(html);

                    }
                    if($('#charge_box_'+id).length<=0) {
                        var custom_id = result.custom_id
                        var html_user = '<div class="charge_box_'+id+'" style="display: none;">';
                        html_user += '<div class="charge_box">';
                        html_user += '<div class="add_box" onclick="custom_polygon_add('+custom_id+','+id+',0);">';
                        html_user += '<span>+</span> 添加网格员';
                        html_user += '</div>';
                        html_user += '</div>';
                        html_user += '</div>';
                        $('.custom_polygon_box').append(html_user);
                    }
                    if (poly) {
                        // 划线监听事件
                        lineupdateFunction = function (id) {
                            return function (event) {
                                lat_lng = [];
                                delivery_area = $.map(event.target.getPath(), function (item) {
                                    lat_lng.push(item.lat + ',' + item.lng);
                                    return {'lng': item.lng, 'lat': item.lat};
                                });
                                $('#manage_range_polygon_'+id).val(lat_lng.join('|'));
                                $('#manage_range_polygon').val(lat_lng.join('|'));
                                setTimeout(function(){
                                    custom_add(id);
                                },500)
                            };
                        }(id);
                        poly.addEventListener("lineupdate", lineupdateFunction);



                        polygon_array[id] = poly;

                        // 点击事件
                        clickFunction = function (vl) {
                            return function (event) {
                                console.log('单击1触发', event)
                                var point = event.point;
                                if (timeoutID) {
                                    clearTimeout(timeoutID);
                                }
                                timeoutID = window.setTimeout(function (){
                                    //写单击事件要干的事
                                    console.log('单击1处理', vl)
                                    console.log('单击1点坐标', point)
                                },300);
                            };
                        }(id);
                        poly.addEventListener("click", clickFunction);
                        // 鼠标覆盖事件
                        mouseoverFunction = function (vl) {
                            return function (event) {
                                console.log('鼠标1覆盖事件', vl)
                                var charge_box_val = $('#charge_box_val_'+vl).val();
                                if (pattern_model==0 && !charge_box_val) {
                                    return false
                                }
                                console.log('鼠标覆盖事件', polygon)
                                var content = $('.charge_box_'+vl).html();
                                var jb = event.pixel;
                                var top = parseInt(jb['y']);
                                if (charge_box_val) {
                                    top = top - 100;
                                } else {
                                    top = top - 15;
                                }
                                var left = parseInt(jb['x']) - 90;
                                layer.open({
                                    title:'网格员的信息',
                                    content: content,
                                    shadeClose: true,
                                    closeBtn: 1,
                                    btn:[],
                                    offset: [top+'px',left+'px']
                                });
                            };
                        }(id);
                        poly.addEventListener("mouseover", mouseoverFunction);
                        // 鼠标移除事件
                        mouseoutFunction = function (vl) {
                            return function (event) {
                                console.log('鼠标1移除事件', vl)
                            };
                        }(id);
                        poly.addEventListener("mouseout", mouseoutFunction);
                        // 右击触发删除
                        rightclickFunction = function (id,poly) {
                            return function (event) {
                                if (is_del) {
                                    layer.open({
                                        content: '是否确认删除,会把点击的区块删除,删除后数据无法回复！请谨慎操作！',
                                        shadeClose: false,
                                        closeBtn: 0
                                        ,btn: ['确定', '取消']
                                        ,yes:function(index){
                                            map.removeOverlay(poly);
                                            delete polygon_array[id];
                                            layer.closeAll();
                                            custom_del(id)
                                        }
                                    });
                                    console.log('poly', poly)
                                    console.log('右击1触发删除', id)
                                }
                            };
                        }(id,poly);
                        poly.addEventListener("rightclick", rightclickFunction);
                    }
                }
            }


        },'json');
    }

    // 区块删除
    function custom_del(id) {
        $.post("{pigcms{:U('NewGridding/delCustom')}",{'id': id},function(data){
            if (data.errorCode) {
                layer.open({
                    content: data.errorMsg
                    ,skin: 'msg'
                    ,time: 2 //2秒后自动关闭
                });
            }
        })
    }

    // 添加编辑查看区块网格员
    function custom_polygon_add(custom_id=0,id=0,look=0) {
        var url = "{pigcms{:U('NewGridding/custom_polygon_add')}";
        url +='&look='+look;
        url +='&custom_id='+custom_id;
        var title = '添加网格员';
        if (id>0) {
            url +='&id='+id;
            if (look>0) {
                title = '查看网格员信息';
            } else {
                title = '编辑网格员';
            }
        }
        layer.open({
            type: 2,
            title:title,
            content: url,
            area: ['750px', '650px'],
            maxmin: false
        });
    }

</script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=drawing&key={pigcms{$config.google_map_ak}"></script>
    <script>
        // 暂时未处理google端
        var polygon = '{pigcms{$custom['manage_range_polygon']}';
        var default_pos = '{pigcms{$defaultPos}';
        if(polygon){
            polygon = $.parseJSON(polygon);
            var nowlat = '{pigcms{$custom["lat"]}';
            var nowlng = '{pigcms{$custom["lng"]}';
        }else if(default_pos){
            var nowlat = '{pigcms{$defaultPos["lat"]}';
            var nowlng = '{pigcms{$defaultPos["lng"]}';
        }else{
            var nowlat = 31.817797156213604;
            var nowlng = 117.22220727680337;
        }
        var bermudaTriangle;
        var drawingManager;
        var selectedShape;
        initMap();

        $('#choose_cityss').live('change',function(){
            var city_lat = $(this).find('option:selected').attr('lat');
            var city_lng = $(this).find('option:selected').attr('lng');
            if(city_lat != 0 || city_lng != 0){
                nowlat = city_lat;
                nowlng = city_lng;
                initMap()
            }
        })

        function initMap() {
            map = new google.maps.Map(document.getElementById('allmap'), {
                zoom: 14,
                center: {lat: parseFloat(nowlat), lng: parseFloat(nowlng)}
            });
            var marker = new google.maps.Marker({
                position: {lat: parseFloat(nowlat), lng: parseFloat(nowlng)},
                map: map,
                title: '默认位置'
            });
            google.maps.event.addDomListener(document.getElementById('baiduMap'), 'click', deleteSelectedShape);
            // Define the LatLng coordinates for the polygon's path.
            //绘画工具 设置
            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
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
            //将写入的加入数组
            google.maps.event.addListener(drawingManager, 'overlaycomplete', function (e) {
                var newShape = e.overlay;

                deleteSelectedShape();
                latlng=[];
                newShape.type = e.type;

                if (e.type !== google.maps.drawing.OverlayType.MARKER) {
                    // Switch back to non-drawing mode after drawing a shape.
                    drawingManager.setDrawingMode(null);

                    // Add an event listener that selects the newly-drawn shape when the user
                    // mouses down on it.
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
                }
                else {
                    google.maps.event.addListener(newShape, 'click', function (e) {
                        setSelection(newShape);
                    });
                    setSelection(newShape);
                }
            });
            //循环显示 经纬度
            var latlng = [];
            function showLonLat(arr) {
                var bounds = new google.maps.LatLngBounds();

                for (var i = 0; i < arr.length; i++) {
                    bounds.extend(arr[i]);
                    latlng.push(arr[i].lat() + ',' + arr[i].lng());
                    $('#manage_range_polygon').val(latlng.join('|'));
                }
                $('#lng_lat').val(bounds.getCenter().lng()+','+bounds.getCenter().lat());
            }

            var polyCoords = [];
            var lat_lng = [];
            for (i = 0; i < polygon.length; i++) {
                for (k = 0; k < polygon[i].length; k++) {
                    polyCoords.push({lat: +parseFloat(polygon[i][k].lat), lng: +parseFloat(polygon[i][k].lng)});
                    lat_lng.push(polygon[i][k].lat + ',' + polygon[i][k].lng);
                }
            }
            $('#manage_range_polygon').val(lat_lng.join('|'));
            // Construct the polygon.
            bermudaTriangle = new google.maps.Polygon({
                paths: polyCoords,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35
            });
            addPolygon();
            function setSelection (shape) {
                selectedShape = shape;
            }
            function deleteSelectedShape(){
                bermudaTriangle.setMap(null);
                if (selectedShape) {
                    selectedShape.setMap(null);
                }
            }

            function addPolygon() {
                bermudaTriangle.setMap(map);
            }
        }

    </script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
    <script>
        var polygon = '{pigcms{$custom['manage_range_polygon']}';
        var village_grid_range_ids='{pigcms{$custom['village_grid_range_ids']}';
        var arr_color = '{pigcms{$arr_color}';
        var superior_polygon = '{pigcms{$superior_custom['manage_range_polygon']}';
        polygon = $.parseJSON(polygon);
        village_grid_range_ids= $.parseJSON(village_grid_range_ids);
        console.log('village_grid_range_ids', village_grid_range_ids);
        superior_polygon = $.parseJSON(superior_polygon);
        console.log('209-polygon', polygon);
        console.log('209-superior_polygon', superior_polygon);
        var oldOverlay = [];
        var polygon_array = [];
        var is_del = false;
        $(document).ready(function(){

            $('#pattern_model').change(function(){
                var val = $(this).val();
                $('.pattern_model_tips').hide();
                $('.form_tips_'+val).show();
                if (val==1 && polygon_array) {
                    for(var pol in polygon_array) {
                        if (polygon_array[pol]) {
                            polygon_array[pol].enableEditing();
                        }
                    }
                    is_del = true;
                    $('.add_range').show();
                    $('.look_img_box').hide();
                    $('.edit_img_box').show();
                    pattern_model = 1;
                } else if (polygon_array) {
                    for(var pol in polygon_array) {
                        if (polygon_array[pol]) {
                            polygon_array[pol].disableEditing();
                        }
                    }
                    is_del = false;
                    $('.add_range').hide();
                    $('.edit_img_box').hide();
                    $('.look_img_box').show();
                    pattern_model = 0;
                } else {
                    is_del = false;
                    $('.add_range').hide();
                    $('.edit_img_box').hide();
                    $('.look_img_box').show();
                    pattern_model = 0;
                }
            })


            map = new BMap.Map("allmap",{"enableMapClick":false}), point = new BMap.Point('{pigcms{$custom["lng"]}', '{pigcms{$custom["lat"]}');
            map.centerAndZoom(point, map_size);
            map.enableScrollWheelZoom();
            if ($('#lng_lat').val() == '') {
                var geolocation = new BMap.Geolocation();
                geolocation.getCurrentPosition(function(r){
                    if(this.getStatus() == BMAP_STATUS_SUCCESS){
                        var marker = new BMap.Marker(r.point);
                        map.addOverlay(marker);
                        map.panTo(r.point);
                    } else {
                        alert('failed'+this.getStatus());
                    }
                },{enableHighAccuracy: true})
            } else {
                var marker = new BMap.Marker(point);// 创建标注
                map.addOverlay(marker);
                marker.enableDragging();
            }
            if (superior_polygon != null) {
                for (var i_f in superior_polygon) {
                    var polygonArr = [];
                    var lat_lng = [];
                    for (var j_f in superior_polygon[i_f]) {
                        polygonArr.push(new BMap.Point(superior_polygon[i_f][j_f].lng, superior_polygon[i_f][j_f].lat));
                        lat_lng.push(superior_polygon[i_f][j_f].lat + ',' + superior_polygon[i_f][j_f].lng);
                    }

                    var poly = new BMap.Polygon(polygonArr, {strokeColor:"{pigcms{$superior_line_color}",fillColor:"{pigcms{$superior_fill_color}", strokeWeight:2, fillOpacity: 0.2, strokeOpacity:0.8,strokeStyle:'dashed'},{value:999});
                    map.addOverlay(poly);  //创建多边形
                    poly.disableEditing();
                }
            }

            if (polygon != null) {
                console.log('lijie',polygon);
                var timeoutID = null;
                var is_start = true;
                var polygon_len = parseInt("{pigcms{$custom['polygon_length']}")-1;
                var val_num=0
                for (var i in polygon) {
                    var polygonArr = [];
                    var lat_lng = [];
                    for (var ii in polygon[i]) {
                        polygonArr.push(new BMap.Point(polygon[i][ii].lng, polygon[i][ii].lat));
                        lat_lng.push(polygon[i][ii].lat + ',' + polygon[i][ii].lng);
                    }
                    $('#manage_range_polygon_'+i).val(lat_lng.join('|'));
                    var poly = new BMap.Polygon(polygonArr, {strokeColor:JSON.parse(arr_color)[i],fillColor:JSON.parse(arr_color)[i], strokeWeight:2, fillOpacity: 0.2, strokeOpacity:0.8,strokeStyle:'dashed'},{value:999});
                    polygon_array[i] = poly;
                    map.addOverlay(poly);  //创建多边形
                    poly.disableEditing();
                    oldOverlay.push(poly);

//                    poly.addEventListener("lineupdate", function (e) {
//                        lat_lng = [];
//                        delivery_area = $.map(e.target.getPath(), function (item) {
//                            lat_lng.push(item.lat + ',' + item.lng);
//                            return {'lng': item.lng, 'lat': item.lat};
//                        });
//                        $('#manage_range_polygon').val(lat_lng.join('|'));
//
//                    });

                    // 划线监听事件
                    lineupdateFunction = function (vl) {
                        return function (event) {
                            console.log('划线监听',event);
                            lat_lng = [];
                            delivery_area = $.map(event.target.getPath(), function (item) {
                                lat_lng.push(item.lat + ',' + item.lng);
                                return {'lng': item.lng, 'lat': item.lat};
                            });
                            $('#manage_range_polygon_'+vl).val(lat_lng.join('|'));
                            console.log('is_start',is_start)
                            if (!is_start) {
                                $('#manage_range_polygon').val(lat_lng.join('|'));
                                custom_add(vl);
                            }
                        };
                    }(i);
                    poly.addEventListener("lineupdate", lineupdateFunction);

//                    poly.addEventListener("click", function (e) {
//                        console.log('点击',e.target.getPath())
//                    });
                    // 点击事件
                    clickFunction = function (vl,id) {
                        return function (event) {
                            console.log('单击触发', event)
                            var str_lng_lat = '';
                            var path = event.point;
                            console.log('lijie',path)
                            for(var m=0;m<path.length-1;m++){
                                str_lng_lat += path[m].lng+','+path[m].lat+'|';
                            }
                            str_lng_lat=str_lng_lat.substring(0,str_lng_lat.length-1);
                            console.log('1111',str_lng_lat);
                            $.post("{pigcms{:U('NewGridding/showInfo')}",{str_lng_lat:str_lng_lat,id:id},function(data){
                                data = JSON.parse(data);
                                if(data.data.type == 1){
                                    var html = '<p>街道名称：'+data.data.area_name+'</p><p>街道联系方式：'+data.data.phone+'</p><p>街道人口：'+data.data.count+'</p>';
                                }
                                if(data.data.type == 2){
                                    var html = '<p>网格名：'+data.data.polygon_name+'</p><p>绑定社区：'+data.data.area_name+'</p><p>网格员：'+data.data.grid_name+'</p><p>网格员联系方式：'+data.data.grid_phone+'</p>';
                                }
                                if(data.data.type == 3){
                                    var html = '<p>网格名：'+data.data.polygon_name+'</p><p>绑定小区：'+data.data.village_name+'</p><p>所属社区：'+data.data.area_name+'</p><p>网格员：'+data.data.grid_name+'</p><p>网格员联系方式：'+data.data.grid_phone+'</p>';
                                }
                                if(data.data.type == 4){
                                    var html = '<p>网格名：'+data.data.polygon_name+'</p><p>绑定楼栋：'+data.data.single_name+'</p><p>所属社区：'+data.data.area_name+'</p><p>所属小区：'+data.data.village_name+'</p><p>网格员：'+data.data.grid_name+'</p><p>网格员联系方式：'+data.data.grid_phone+'</p>';
                                }
                                $('.show_info').html('');
                                $('.show_info').append(html);
                                var content = $('.show_info').html();
                                layer.open({
                                    title:'网格信息',
                                    content: content,
                                    shadeClose: true,
                                    closeBtn: 1,
                                    btn:[],
                                });
                            });
//                            var point = event.point;
//                            clearTimeout(timeoutID);
//                            timeoutID = window.setTimeout(function (){
//                                //写单击事件要干的事
//                                console.log('单击处理', vl,v)
//                                console.log('单击点坐标', point)
//                                layer.open({
//                                    content: '是否前往下一层级！',
//                                    shadeClose: false,
//                                    closeBtn: 0
//                                    ,btn: ['确定', '取消']
//                                    ,yes:function(index){
//                                        var url = "{pigcms{:U('NewGridding/area_street_custom')}";
//                                        var bind_id = $('#bind_id').val();
//                                        if (bind_id>0) {
//                                            url += '&fid='+bind_id;
//                                        }
//                                        if (point) {
//                                            url += '&lng='+point.lng;
//                                            url += '&lat='+point.lat;
//                                        }
//                                        window.location.href = url;
//                                    }
//                                });
//                            },300);
                        };
                    }(i,village_grid_range_ids[i]);
                    poly.addEventListener("click", clickFunction);
                    // 鼠标覆盖事件
                    mouseoverFunction = function (vl,polygon) {
                        return function (event) {
                            var charge_box_val = $('#charge_box_val_'+vl).val();
                            if (pattern_model==0 && !charge_box_val) {
                                return false
                            }
                            console.log('鼠标覆盖事件', polygon)
                            var content = $('.charge_box_'+vl).html();
                            var jb = event.pixel;
                            console.log('event', event)
                            var top = parseInt(jb['y']);
                            if (charge_box_val) {
                                top = top - 100;
                            } else {
                                top = top - 15;
                            }
                            var left = parseInt(jb['x']) - 90;
                            layer.open({
                                title:'网格员的信息',
                                content: content,
                                shadeClose: true,
                                closeBtn: 1,
                                btn:[],
                                offset: [top+'px',left+'px']
                            });
                        };
                    }(i,polygon[i]);
                    poly.addEventListener("mouseover", mouseoverFunction);
                    // 鼠标移除事件
                    mouseoutFunction = function (vl,v) {
                        return function (event) {
                            console.log('鼠标移除事件', vl,v)
                        };
                    }(i,polygon[i]);
                    poly.addEventListener("mouseout", mouseoutFunction);
                    // 右击触发删除
                    rightclickFunction = function (vl,poly) {
                        return function (event) {
                            if (is_del) {
                                layer.open({
                                    content: '是否确认删除,会把点击的区块删除,删除后数据无法回复！请谨慎操作！',
                                    shadeClose: false,
                                    closeBtn: 0
                                    ,btn: ['确定', '取消']
                                    ,yes:function(index){
                                        map.removeOverlay(poly);
//                                    polygon_array.remove(vl);
                                        delete polygon_array[vl];
                                        layer.closeAll();
                                        custom_del(vl);
                                    }
                                });
                                console.log('map', map)
                                console.log('poly', poly)
                                console.log('右击触发删除', vl)
                            }
                        };
                    }(i,poly);
                    poly.addEventListener("rightclick", rightclickFunction);

                    if (val_num==polygon_len && is_start) {
                        setTimeout(function () {
                            is_start = false;
                        }, 800);
                    }
                    val_num++;
                }
            }

            var overlays = [];
            var overlaycomplete = function(e){
                overlays.push(e.overlay);
                var latLng = e.overlay.getPath();
                console.log('overlaycomplete_lng',e.overlay.getBounds().getCenter().lng)
                console.log('overlaycomplete_lat',e.overlay.getBounds().getCenter().lat)
                var lat_lng = [];
                var polygonArr = [];
                for (var i in latLng) {
                    lat_lng.push(latLng[i].lat + ',' + latLng[i].lng);
                    polygonArr.push(new BMap.Point(latLng[i].lng, latLng[i].lat));
                }

                var poly = new BMap.Polygon(polygonArr, {strokeColor:"{pigcms{$line_color}",fillColor:"{pigcms{$fill_color}", strokeWeight:2, fillOpacity: 0.2, strokeOpacity:0.8,strokeStyle:'dashed'});

                map.addOverlay(poly);  //创建多边形
                poly.enableEditing();


                oldOverlay.push(poly)

                map.removeOverlay(e.overlay);

                $('#manage_range_polygon').val(lat_lng.join('|'));
                console.log( $('#manage_range_polygon').val())
                $('#lng_lat').val(e.overlay.getBounds().getCenter().lng + ',' + e.overlay.getBounds().getCenter().lat);
                poly.addEventListener("lineupdate", function (e) {
                    lat_lng = [];
                    delivery_area = $.map(e.target.getPath(), function (item) {
                        lat_lng.push(item.lat + ',' + item.lng);
                        return {'lng': item.lng, 'lat': item.lat};
                    });
                    $('#manage_range_polygon').val(lat_lng.join('|'));
                    console.log( $('#manage_range_polygon').val())
                });

                custom_add(0,poly);
            };
            var styleOptions = {
                strokeColor:"{pigcms{$line_color}",    //边线颜色。
                fillColor:"{pigcms{$fill_color}",      //填充颜色。当参数为空时，圆形将没有填充效果。
                strokeWeight: 3,       //边线的宽度，以像素为单位。
                strokeOpacity: 0.8,	   //边线透明度，取值范围0 - 1。
                fillOpacity: 0.6,      //填充的透明度，取值范围0 - 1。
                strokeStyle: 'dashed' //边线的样式，solid或dashed。
            }
            //实例化鼠标绘制工具
            var drawingManager = new BMapLib.DrawingManager(map, {
                isOpen: false, //是否开启绘制模式
                enableDrawingTool: false, //是否显示工具栏
                drawingMode:BMAP_DRAWING_POLYGON,
                drawingToolOptions: {
                    anchor: BMAP_ANCHOR_TOP_RIGHT, //位置
                    offset: new BMap.Size(5, 5), //偏离值
                },
                circleOptions: styleOptions, //圆的样式
                polylineOptions: styleOptions, //线的样式
                polygonOptions: styleOptions, //多边形的样式
                rectangleOptions: styleOptions //矩形的样式
            });


            $('#baiduMap').click(function(){
                drawingManager.open();
//                for(var i = 0; i < overlays.length; i++){
//                    map.removeOverlay(overlays[i]);
//                }
//                if (oldOverlay.length > 0) {
//                    console.log(oldOverlay);
//                    for(var i = 0; i < oldOverlay.length; i++){
//                        map.removeOverlay(oldOverlay[i]);
//                    }
//                }
//                overlays = [];
//                oldOverlay = [];
//                polygonArr = [];
            });

            //添加鼠标绘制工具监听事件，用于获取绘制结果
            drawingManager.addEventListener('overlaycomplete', overlaycomplete);
        });
    </script>
</if>
<include file="Public:footer"/>