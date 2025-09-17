var sendUsers = [];//骑手信息
var userOrders = [];//骑手所拥有的订单
var userPolineArray = [];//配送员的配送订单的配送线图
var userSendLabel = [];//配送员的配送订单的送货地址
var userFetchLabel = [];//配送员的配送订单的取货地址
var polineArray = [];//选择的订单的配送线路
var fetchLabel = [];//选择的订单的取货地址
var sendLabel = [];//选择的订单的送货地址
var supplyArray = [];//待指派的订单
var labelArray = [];//地图上骑手的标志
var orderHtmlFunTime = 0;
var map = new T.Map("_mapNew", {enableMapClick:false});
var initOrdersAjax = null;
var initOrdersLayer = null;
if (lat != 0 && lng != 0) {
    var point = new T.LngLat(lng, lat);
} else {
    var point = new T.LngLat(116.417854,39.921988);
}

map.centerAndZoom(point, 15);
map.enableScrollWheelZoom();   //启用滚轮放大缩小，默认禁用
map.enableContinuousZoom();    //启用地图惯性拖拽，默认禁用
//创建缩放平移控件对象
var control = new T.Control.Zoom();
//添加缩放平移控件
map.addControl(control);

if (lat == 0 || lng == 0) {
    function myFun(result){
		oPoint = new T.LngLat(result.center['lng'],result.center['lat']);
		map.centerAndZoom(oPoint,12);
	}
	var myCity = new T.LocalCity();
	myCity.get(myFun);
}
$(document).ready(function(){
    $('.voice-control').click(function(){
        if ($(this).find('i').hasClass('fa-volume-off')) {
            $(this).find('i').removeClass('fa-volume-off').addClass('fa-volume-up');
        } else {
            $(this).find('i').removeClass('fa-volume-up').addClass('fa-volume-off');
        }
    });
    //查看配送员的订单详情
    $(document).on('click', '.location', function(){
        var uid = $(this).data('uid');
        var html = '';
        if (userOrders[uid] != undefined && userOrders[uid].length > 0) {
            $.each(userOrders[uid], function(i, item){
                html += '<tr>';
                if (3==item.item) {
                    html += '<td style="width:20%"><a href="javascript:void(0);" onclick="turn_service('+item.order_id+');">' + item.real_orderid + '</a></td>';
                } else {
                    html += '<td style="width:20%"><a href="javascript:void(0);" onclick="turn('+item.order_id+');">' + item.real_orderid + '</a></td>';
                }
                html += '<td style="width:10%">' + (((item.item==3) || (item.order_from==10))?item.from_site:item.store_name) + '</td>';
                html += '<td style="width:20%">' + item.aim_site + '</td>';
                if (item.status == 2) {
					html += '<td class="order-status" style="width:10%">' + (item.is_fetch_order == '1' ? '待取单' : '待接单') + '</td>';
                    html += '<td style="width:10%">' + item.expect_pick_time + '</td>';
                    html += '<td style="width:10%">' + item.appoint_time + '</td>';
                    html += '<td style="width:10%">' + item.traffic + '</td>';
					html += '<td style="width:10%"><button type="button" class="btn btn-primary btn-ressign ng-scope changeOrder" data-uid="' + item.uid + '" data-order_id="' + item.supply_id + '">改派</button></td>';
				} else {
					html += '<td class="order-status" style="width:10%">配送中</td>';
					html += '<td style="width:10%">' + item.expect_pick_time + '</td>';
					html += '<td style="width:10%">' + item.appoint_time + '</td>';
                    html += '<td style="width:10%">' + item.traffic + '</td>';
					html += '<td>-</td>';	
                  }
				
				html += '</tr>';
            });
            $('.table-wrapper').find('tbody').html(html);
            $('.assigned-orders-modal').removeClass('ng-hide assigned-orders-modal--hide');
            var top = parseInt($(this).offset().top), thisHeight = parseInt($('.table-wrapper').find('tbody').height()), windowHeight = parseInt($(window).height());

            if (thisHeight > windowHeight) {
                top = top < (windowHeight - 300) ? top : parseInt(windowHeight - 300);
            } else if (parseInt(top) + parseInt(thisHeight) > parseInt(windowHeight)) {
                top = windowHeight - thisHeight - 50;
            }
            $('.assigned-orders-modal').css('top', top);
        } else {
            //加载每个配送员身上的未完成的单子
            var params = {'province_id':0, 'city_id':0, 'area_id':0};
            if ($('#choose_province').val() == null) {
                params.province_id = $('#choose_pca').attr('province_id');
            } else {
                params.province_id = $('#choose_province').val();
            }
            if ($('#choose_city').val() == null) {
                params.city_id = $('#choose_pca').attr('city_id');
            } else {
                params.city_id = $('#choose_city').val();
            }
            if ($('#choose_area').val() == null) {
                params.area_id = $('#choose_pca').attr('area_id');
            } else {
                params.area_id = $('#choose_area').val();
            }
            if ($('#choose_street').val() == null) {
                params.street_id = $('#choose_pca').attr('street_id');
            } else {
                params.street_id = $('#choose_street').val();
            }
            params.uid = uid;
            var that = $(this);
            $.get(url + 'initUserOrders', params, function(response){
                if (response.errcode == false) {
                    var msg = response.data;
                    msg = msg[uid];
                    if (msg && msg.length > 0) {
                        $.each(msg, function(i, item){
                            html += '<tr>';//查看
                            if (3==item.item) {
                                html += '<td style="width:20%"><a href="javascript:void(0);" onclick="turn_service('+item.order_id+');">' + item.real_orderid + '</a></td>';
                            } else {
                                html += '<td style="width:20%"><a href="javascript:void(0);" onclick="turn('+item.order_id+');">' + item.real_orderid + '</a></td>';
                            }
                            html += '<td style="width:10%">' + (((item.item==3) || (item.order_from==10))?item.from_site:item.store_name) + '</td>';
                            html += '<td style="width:20%">' + item.aim_site + '</td>';
                            if (item.status == 2) {
                                html += '<td class="order-status" style="width:10%">' + (item.is_fetch_order == '1' ? '待取单' : '待接单') + '</td>';
                                html += '<td style="width:10%">' + item.expect_pick_time + '</td>';
                                html += '<td style="width:10%">' + item.appoint_time + '</td>';
                                html += '<td style="width:10%">' + item.traffic + '</td>';
                                html += '<td style="width:10%"><button type="button" class="btn btn-primary btn-ressign ng-scope changeOrder" data-uid="' + item.uid + '" data-order_id="' + item.supply_id + '">改派</button></td>';
							} else {
                                html += '<td class="order-status" style="width:10%">配送中</td>';
								html += '<td style="width:10%">' + item.expect_pick_time + '</td>';
								html += '<td style="width:10%">' + item.appoint_time + '</td>';
								html += '<td style="width:10%">' + item.traffic + '</td>';
                                html += '<td>-</td>';
                            }
							
                            html += '</tr>';
                        });
                    } else {
                        html += '<tr>';
                        html += '<td></td>';
                        html += '<td>--</td>';
                        html += '<td class="order-status">--</td>';
                        html += '<td></td>';
                        html += '</tr>';
                    }
                    $('.table-wrapper').find('tbody').html(html);
                    $('.assigned-orders-modal').removeClass('ng-hide assigned-orders-modal--hide');
                    var top = parseInt(that.offset().top), thisHeight = parseInt($('.table-wrapper').find('tbody').height()), windowHeight = parseInt($(window).height());
                    if (parseInt(top) + parseInt(thisHeight) > parseInt(windowHeight)) {
                        top = windowHeight - thisHeight - 50;
                    }
                    $('.assigned-orders-modal').css('top', top);
                }
            }, 'json');
        }
    });
	//查看配送员的订单详情
    $(document).on('click', '.off_work', function(){
		var uid = $(this).data('uid');
		window.top.art.dialog({
			icon: 'question',
			title: '关闭接单状态',
			lock: true,
			fixed: true,
			opacity:'0.4',
			resize: false,
			content: '<div style="font-size:14px;">是否将该递送员从接单状态 变为  关闭接单状态? 关闭后已经指派给<br/>该递送员的订单不受影响，但不能分派新单给该递送员。<br/><br/>如果该递送员当前不在工作，请先把该递送员的订单改派给其他人<br/>后再关闭该递送员的接单状态</div>',
			ok:function (){
				$.post(off_work_url, {uid: uid}, function(result){
					if(result.status == 1){
						if($('#deliverymanItem_' + uid).length > 0){
							$('#deliverymanItem_' + uid).remove();
						}
					}
					art.dialog({
						title: '提示',
						opacity:'0.4',
						lock: true,
						fixed: true,
						resize: false,
						content: result.info
					});
				});
			},
			cancel:true
		});
	});
    //关闭订单详情
    $(document).click(function(e){
        var parent = e.target;
        if (!$('.assigned-orders-modal').hasClass('ng-hide assigned-orders-modal--hide') && !$(parent).parents('#orderDetail').hasClass('assigned-orders-modal') && !$(parent).hasClass('location')){
            $('.assigned-orders-modal').addClass('ng-hide assigned-orders-modal--hide')
        }
        if (!$(parent).hasClass('delivery-icon') && !$(parent).hasClass('deliveryman-icon-click') && !$(parent).parents('div').hasClass('deliveryman-icon-click')) {
            $('.deliveryman-icon-click').hide();
        }
    });
    //地图中配送员的详情显示
    $(document).on('click', '.amap-marker .text-move-animation,.amap-marker .name', function(e){
        e.stopPropagation();
        $('.deliveryman-icon-click').hide();
        var uid = $(this).data('uid');
        $(this).parents('.BMapLabel').css('z-index', 1000);
        $('#__detail' + uid).find('.dispatch-order-count').text($('.order-list').children('.selected').length);
        $('#__detail' + uid).show();
    });
  //地图中配送员的详情隐藏
    $(document).on('click', '.marker-content-close', function(){
        $(this).parents('.deliveryman-icon-click').hide();
    });
    
    //点击指派给配送的按钮事件
//    $(document).on('click', '.dispatch-button, .assign', function(e){
    $(document).on('click', '.assign, .zhipaibutton', function(e){
        e.stopPropagation();
        var uid = $(this).data('uid');
        var supplyIds = [];
        $('.order-list').children('.selected').each(function(obj){
            supplyIds.push($(this).data('supply_id'))
        });
        var supply_ids = '';
        var num = supplyIds.length;
        if (supplyIds.length > 0) {
            supply_ids = supplyIds.join(',');
        } else {
//            alert('请选择指派单子')
            art.dialog({
                content: '请选择一个有效的配送单子指派给该配送员',
                ok: function () {},
                cancelVal: '关闭',
                lock:true,
                cancel: true //为true等价于function(){}
            });
            return false;
        }
        var obj = $(this);
        $(this).attr('disabled', true);
        $.post(url + 'appointDeliver', {'uid':uid, 'supply_ids':supply_ids}, function(response){
            obj.attr('disabled', false);
            if (response.errcode == false) {
                $('.deliveryman-icon-click').hide();
                if (typeof(userOrders[uid]) == 'undefined') {
                    userOrders[uid] = [];
                }
                $('.order-list').children('.selected').each(function(obj){
                    supplyArray[$(this).data('supply_id')].status = 2;
                    userOrders[uid].push(supplyArray[$(this).data('supply_id')]);
                    map.removeOverLay(polineArray[$(this).data('supply_id')]);
                    map.removeOverLay(fetchLabel[$(this).data('supply_id')]);
                    map.removeOverLay(sendLabel[$(this).data('supply_id')]);
                    $(this).remove();
                });
                var tempFetch = parseInt($('#__content' + uid + ' .deliveryman-icon').find('.fetch_' + uid).text());
                $('.fetch_' + uid).text(tempFetch + num);
                sendUsersHtml(sendUsers, 0);
            }else{
                alert(response.msg);
            }
        }, 'json');
    });
    $(document).on('click', '.order-tab a', function(){
        location.href = url + 'desk&type=' + $(this).data('type') + '&city_id=' + $('#choose_city').val() + '&area_id=' + $('#choose_area').val();
    });

    //查看单个配送员的配送订单的线路
    $(document).on('click', '.delivery-icon, .delivery-detail', function(){
        var id = $(this).data('id');
        if ($(this).hasClass('lock')) {
            $('#__content' + id).find('.delivery-icon').removeClass('lock');
            $('#deliverymanItem_' + id).find('.delivery-detail').removeClass('lock');
            $('.BMapLabel').show();
            if (userOrders[id] != undefined && userOrders[id].length > 0) {
                $.each(userOrders[id], function(i, item){
                    map.removeOverLay(userSendLabel[item.supply_id]);
                    map.removeOverLay(userFetchLabel[item.supply_id]);
                    map.removeOverLay(userPolineArray[item.supply_id]);
                });
            }
        } else {
            $('.delivery-icon, .delivery-detail').removeClass('lock');
            $('#__content' + id).find('.delivery-icon').addClass('lock');
            $('#deliverymanItem_' + id).find('.delivery-detail').addClass('lock');
            $('#__content' + id).parents('.BMapLabel').show();
            $('#__content' + id).parents('.BMapLabel').siblings().hide();
            
            map.panTo(new T.LngLat(sendUsers[id].now_lng, sendUsers[id].now_lat), 15);//TODO
            for (var up in userPolineArray) map.removeOverLay(userPolineArray[up]);
            for (var us in userSendLabel) map.removeOverLay(userSendLabel[us]);
            for (var uf in userFetchLabel) map.removeOverLay(userFetchLabel[uf]);
            userSendLabel = [];
            userFetchLabel = [];
            userPolineArray = [];
            if (userOrders[id] != undefined && userOrders[id].length > 0) {
                $.each(userOrders[id], function(i, item){
                    userSendLabel[item.supply_id] = new T.Label({text: '<div class="amap-marker-content" style="opacity: 1;"><i class="assigned-icon user"></i></div>',position:new T.LngLat(item.aim_lnt, item.aim_lat), offset:new T.Point(-15, -25)});  // 创建文本标注对象
                    map.addOverLay(userSendLabel[item.supply_id]);

                    userFetchLabel[item.supply_id] = new T.Label({text: '<div class="amap-marker-content" style="opacity: 1;"><i class="assigned-icon wait-take-rest"></i></div>',position:new T.LngLat(item.from_lnt, item.from_lat), offset:new T.Point(-15, -25)});  // 创建文本标注对象
                    map.addOverLay(userFetchLabel[item.supply_id]);
                    
                    userPolineArray[item.supply_id] = new T.Polyline([
                        new T.LngLat(item.from_lnt, item.from_lat),
                        new T.LngLat(item.aim_lnt, item.aim_lat)
                    ], {strokeColor:"#697694", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'});   //创建折线
                    map.addOverLay(userPolineArray[item.supply_id]); 
                });
            }
            
        }
    });

    
    //选择待指派的单子
    $(document).on('click', '.order-item', function(){
        var supplyId = $(this).data('supply_id');
        console.log($(this).hasClass('selected'));
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            $(this).find('.checked-icon').removeClass('selected');
            polineArray.length && map.removeOverLay(polineArray[supplyId]);
            fetchLabel.length && map.removeOverLay(fetchLabel[supplyId]);
            sendLabel.length && map.removeOverLay(sendLabel[supplyId]);
            sendUsersHtml(sendUsers, 0);
        } else {
            $(this).addClass('selected');
            $(this).find('.checked-icon').addClass('selected');
            $('.dispatch-order-count').text($('.order-list').children('.selected').length);
            var sendHtml = '';
            sendHtml += '<div class="amap-marker-content" style="opacity: 1;">';
            sendHtml += '<div tpl="user" id="user_400000515608307626" class="user-marker">';
            sendHtml += '<div class="big-circle"></div>';
            sendHtml += '<div class="small-circle">送</div>';
            sendHtml += '<div class="triangle"></div>';
            sendHtml += '</div>';
            sendHtml += '</div>';
            if(!supplyArray[supplyId]){
                return
            }
            sendLabel[supplyId] = new T.Label({text: sendHtml,position:new T.LngLat(supplyArray[supplyId].aim_lnt, supplyArray[supplyId].aim_lat), offset:new T.Point(-18, -50)});  // 创建文本标注对象
            map.addOverLay(sendLabel[supplyId]);
            
            var fetchHtml = '';
            fetchHtml += '<div class="amap-marker-content" style="opacity: 1;">';
            fetchHtml += '<div tpl="retailer" id="retailer_15105253" class="retailer-marker">';
            fetchHtml += '<div class="big-circle"></div>';
            fetchHtml += '<div class="small-circle">取</div>';
            fetchHtml += '<div class="triangle"></div>';
            fetchHtml += '</div>';
            fetchHtml += '</div>';
            fetchLabel[supplyId] = new T.Label({text: fetchHtml,position:new T.LngLat(supplyArray[supplyId].from_lnt, supplyArray[supplyId].from_lat), offset:new T.Point(-18, -50)});  // 创建文本标注对象
            map.addOverLay(fetchLabel[supplyId]);
            
            polineArray[supplyId] = new T.Polyline([
                new T.LngLat(supplyArray[supplyId].from_lnt, supplyArray[supplyId].from_lat),
                new T.LngLat(supplyArray[supplyId].aim_lnt, supplyArray[supplyId].aim_lat)
            ], {strokeColor:"blue", strokeWeight:2, strokeOpacity:0.5});   //创建折线
            map.addOverLay(polineArray[supplyId]); 
            
            var newSendUsers = [];
            for (var uid in sendUsers) {
                var tmp = sendUsers[uid]
                tmp.distance = (map.getDistance(new T.LngLat(supplyArray[supplyId].from_lnt, supplyArray[supplyId].from_lat), new T.LngLat(sendUsers[uid].now_lng, sendUsers[uid].now_lat))).toFixed(2);
                tmp.distance = parseFloat(tmp.distance);
                newSendUsers.push(tmp);
            }
            newSendUsers = newSendUsers.sort(compare_asc('distance'))
            sendUsersHtml(newSendUsers, 1);
        }
    });
    

    //调度模式
    $('.dropdown-toggle').click(function(){
        if ($('.dropdown-menu').is(":hidden")) {
            $('.dropdown-menu').show();
        } else {
            $('.dropdown-menu').hide();
        }
    });
    //修改调度模式
    $('.dropdown-menu .ng-binding').click(function(){
        $('.dropdown-toggle .ng-binding').text($(this).text());
        var model = $(this).data('model');
		
		var params = {model:model};
		if ($('#choose_city').val() == null) {
			params.city_id = $('#choose_pca').attr('city_id');
		} else {
			params.city_id = $('#choose_city').val();
		}
        $.get(url + 'changeModel',params, function(response){
            //clearTimeout(orderHtmlFunTime);
            //orderHtmlFun();
            history.go(0)
        });
		/*if(model == 2){
            $('.pre_assign').html('算法预指派');
        }else{
            $('.pre_assign').html('预指派');
        }*/
        $('.dropdown-menu').hide();
    });
    
    //待指派的单子查看详情
    $(document).on('click', '.orderDetail', function(e){
        e.stopPropagation();
        var order_id = $(this).data('order_id');
        if ($(this).data('item') == 3) {
            artiframe('/admin.php?c=Service&a=offer_info&publish_id=' + order_id + '&frame_show=true','查看订单详情',720,520,true,false,false,false,'detail',true);
        } else if($(this).data('item') == 4){
            artiframe('/v20/public/platform/#/mall/platform.orderMange/orderDetail?order_id=' + order_id + '&frame_show=true','查看订单详情',720,520,true,false,false,false,'detail',true);
        }else if($(this).data('item') == 8){   //社区团购
            artiframe('/admin.php?c=Village_group&a=join_order_detail&order_id=' + order_id + '&frame_show=true','查看订单详情',720,520,true,false,false,false,'detail',true);
        }else {
            artiframe('/admin.php?c=Shop&a=order_detail&order_id=' + order_id + '&frame_show=true','查看订单详情',720,520,true,false,false,false,'detail',true);
        }
    });
    
    //配送员标签的鼠标移动效果
    $(document).on('mouseover mouseout', '.postman-item', function(event){
       var id = $(this).attr('id').substr(16);
        if (event.type == 'mouseover') {
            $('#__content' + id).addClass('hover');
            $(this).find('.assign-wrapper').show();
        } else if(event.type == 'mouseout') {
            $('#__content' + id).removeClass('hover');
            $(this).find('.assign-wrapper').hide();
        }
    });
    
    $(document).on('mouseover mouseout', '.deliveryman-marker', function(event){
       var id = $(this).attr('id').substr(9);
        if (event.type == 'mouseover') {
            $(this).addClass('hover');
            $('#deliverymanItem_' + id).find('.assign-wrapper').show();
        } else if(event.type == 'mouseout') {
            $(this).removeClass('hover');
            $('#deliverymanItem_' + id).find('.assign-wrapper').hide();
        }
    });
    
    //搜索骑手
    $('#searchUser').keyup(function(){
        var nowUsers = []
        for (var i in sendUsers) {
            if (sendUsers[i].phone.match($(this).val()) != null || sendUsers[i].name.match($(this).val()) != null) {
                nowUsers.push(sendUsers[i]);
            }
        }
        var html = '';
        if (nowUsers.length > 0) {
            html += '<ul>';
            for (var ii in nowUsers) {
                var item = nowUsers[ii];
                html += '<li id="deliverymanItem_' + item.uid + '" class="postman-item ng-scope">';
                html += '<p>';
                html += '<span class="name ng-binding">' + item.name + '</span>';
                html += '<span class="new-order-count ng-binding ng-hide">+0</span>';
                html += '</p>';
                if(item.traffic != ''){
                    html += '<p class="ng-binding '+item.traffic+1+'" >' + item.traffic_name + '</p>';
                }
                html += '<p class="staff-mobile ng-binding">' + item.phone + '</p>';
                html += '<div>';
                html += '<p class="wait-for-fetch">';
                html += '<span>取：<span class="ng-binding fetch_' + item.uid + '">' + item.fetch + '</span></span>';
                html += '</p>';
                html += '<p class="wait-for-send">';
                html += '<span>送：<span class="ng-binding send_' + item.uid + '">' + item.send + '</span></span>';
                html += '</p>';
                html += '<p class="wait-for-finish">';
                html += '<span>送：<span class="ng-binding finish_' + item.uid + '">' + item.finish + '</span></span>';
                html += '</p>';
                html += '</div>';
                html += '<div class="assign-wrapper">';
                html += '<p class="name-wrapper">';
                html += '<span class="name-label ng-binding">' + item.name + '</span>';
                html += '</p>';
                html += '<p>';
                html += '<span class="delivery-detail" data-id="' + item.uid + '"></span>';
                html += '<span class="assign ng-binding no-motorcycle" data-uid="' + item.uid + '">指派给他</span>';
                html += '<span class="motorcycle">';
                html += '<i class="fa fa-motorcycle"></i>';
                html += '</span>';
                html += '<span class="location" data-uid="' + item.uid + '">详情</span>';
				html += '<span class="off_work" style="background-color:red;" data-uid="' + item.uid + '">关</span>';
                html += '</p>';
                html += '</div>';
                html += '<div class="ng-scope"><div class="ng-scope"></div></div>';
                html += '</li>';
            }
            html += '</ul>';
        }
        $('.postman-list ul').remove();
        $('.postman-list').append(html);
        $('.postman-list').find('.empty-list-label').text('可选骑手(' + nowUsers.length + ')');
    });
    
    //点击改派
    $(document).on('click', '.changeOrder', function(){
        var uid = $(this).data('uid'), supply_id = $(this).data('order_id');
        var html = '';
        for (var i in sendUsers) {
            var item = sendUsers[i];
            if (item.uid != uid) {
                html += '<div class="courier ng-scope" data-uid="' + item.uid + '" data-olduid="' + uid + '" data-supply_id="' + supply_id + '"><span class="ng-binding">' + item.name + '</span>';
                if (item.fetch > 0) {
                    html += '<span class="num ng-binding ng-scope">' + item.fetch + '</span>';
                }
                html += '</div>';
            }
        }
        $('.courier-list').html(html);
        $('.modal-backdrop, .reassign-window').show();
        $('.btn-primary').attr('disabled', true);
    });
    
    //确认改派
    $(document).on('click', '.reassign-window .btn-primary', function(){
        var uid = $('.reassign-window').find('.active').data('uid'), olduid = $('.reassign-window').find('.active').data('olduid');
        var supply_id = $('.reassign-window').find('.active').data('supply_id');
        $.post(url + 'appoint_deliver', {'supply_id':supply_id, 'uid':uid}, function(response){
            if (response.status == 1) {
                $('.modal-backdrop, .reassign-window').hide();
                var tempFetch = parseInt($('#__content' + uid + ' .deliveryman-icon').find('.fetch_' + uid).text());
                var oldtempFetch = parseInt($('#__content' + olduid + ' .deliveryman-icon').find('.fetch_' + olduid).text());
                if (oldtempFetch > 1) {
                    $('.fetch_' + olduid).text(oldtempFetch - 1);
                } else {
                    $('.fetch_' + olduid).text(0);
                }
                $('.fetch_' + uid).text(tempFetch + 1);
                var newUserOrders = [], moveOrder = null;
                $.each(userOrders[olduid], function(i, item){
                    if (item.supply_id != supply_id) {
                        newUserOrders.push(item);
                    } else {
                        moveOrder = item;
                    }
                });
                userOrders[olduid] = newUserOrders;
                sendUsers[olduid].fetch--;
                sendUsers[uid].fetch++;
                if (moveOrder != null) {
                    moveOrder.uid = uid;
                    userOrders[uid].push(moveOrder);
                }
            } else {
                alert(response.info);
            }
        }, 'json')
    });
    //选择配送员
    $(document).on('click', '.courier-list .courier', function(){
        $(this).addClass('active').siblings().removeClass('active');
        $('.btn-primary').attr('disabled', false);
    });
    //点击关闭选择配送员页
    $(document).on('click', '.fa-close, .modal-backdrop', function(){
        $('.modal-backdrop, .reassign-window').hide();
    });
    $(document).on('click', '#sendCount', function(){
        if ($('#choose_city').val() == null) {
            var city_id = $('#choose_pca').attr('city_id');
        } else {
            var city_id = $('#choose_city').val();
        }
        if ($('#choose_area').val() == null) {
            var area_id = $('#choose_pca').attr('area_id');
        } else {
            var area_id = $('#choose_area').val();
        }
        location.href = $(this).data('href') + '&city_id=' + city_id + '&area_id=' + area_id;
    });

    show_province();

    $(document).on('change', '#choose_area', function(){
		if(loading){
			layer.open({
				title:'温馨提示'
				,content:'正在请求上一个区域数据中，此区域数据会过几秒后请求！<br/><div style="color:#888;line-height:16px;">若时间过长依然提示此弹层，请尝试F5手动刷新页面。</div>'
				,btn: ['确定']
				,icon: 4
				,end: function(index){
					
				}
			});
			return false;
		}
        userListFun(1);
//        clearInterval(orderHtmlFunTime);
        clearTimeout(orderHtmlFunTime);
        $('.order-list').html('');
        orderHtmlFun();
//        orderHtmlFunTime = setInterval(orderHtmlFun, 4000);
//        userSendOrderFun();
    });
//    orderHtmlFun();
//    orderHtmlFunTime = setInterval(orderHtmlFun, 4000);
    setInterval(userSendOrderFun, 5000);
    setInterval(userListFun, 3000);
    
});

var userListFun = function(move){
    var params = {'province_id':0, 'city_id':0, 'area_id':0};
    if ($('#choose_province').val() == null) {
        params.province_id = $('#choose_pca').attr('province_id');
    } else {
        params.province_id = $('#choose_province').val();
    }
    if ($('#choose_city').val() == null) {
        params.city_id = $('#choose_pca').attr('city_id');
    } else {
        params.city_id = $('#choose_city').val();
    }
    if ($('#choose_area').val() == null) {
        params.area_id = $('#choose_pca').attr('area_id');
    } else {
        params.area_id = $('#choose_area').val();
    }
    if ($('#choose_street').val() == null) {
        params.street_id = $('#choose_pca').attr('street_id');
    } else {
        params.street_id = $('#choose_street').val();
    }
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.get(url + 'getData', params, function(response){
        if (response.errcode == false) {
            var html = '', indx = 0;
            if (move != undefined && move == 1) {
                for (var t in sendUsers) {
                    if (labelArray[sendUsers[t].uid] != undefined) {
                        map.removeOverLay(labelArray[sendUsers[t].uid]);
                    }
                }
                labelArray = [];
                for (var up in userPolineArray) map.removeOverLay(userPolineArray[up]);
                for (var us in userSendLabel) map.removeOverLay(userSendLabel[us]);
                for (var uf in userFetchLabel) map.removeOverLay(userFetchLabel[uf]);
                
                for (var p in polineArray) map.removeOverLay(polineArray[p]);
                for (var f in fetchLabel) map.removeOverLay(fetchLabel[f]);
                for (var s in sendLabel) map.removeOverLay(sendLabel[s]);
                userPolineArray = [];//配送员的配送订单的配送线图
                userSendLabel = [];
                userFetchLabel = [];
                sendUsers = [];
                userOrders = [];
                polineArray = [];
                fetchLabel = [];
                sendLabel = [];
                supplyArray = [];
            }
            
            
            if (response.data != null) {
//                html += '<ul>';
                var newSendUsers = [], newLabelArray = [];
                $.each(response.data, function(i, item) {
                    newSendUsers[item.uid] = item;
                    if (sendUsers[item.uid] == undefined) {
						if($('#deliverymanItem_' + item.uid).length > 0){
							$('#deliverymanItem_' + item.uid).remove();
						}
//                        sendUsers[item.uid] = item;
                        if (indx == 0) {
                            lng = item.now_lng;
                            lat = item.now_lat;
                        }
                        indx ++;
                        html += '<li id="deliverymanItem_' + item.uid + '" class="postman-item ng-scope">';
                        html += '<p>';
                        html += '<span class="name ng-binding">' + item.name + '</span>';
                        html += '<span class="new-order-count ng-binding ng-hide">+0</span>';
                        html += '</p>';
                        if(item.traffic != ''){
                            html += '<p class="ng-binding '+item.traffic+1+'" >' + item.traffic_name + '</p>';
                        }
                        html += '<p class="staff-mobile ng-binding">' + item.phone + '</p>';
                        html += '<div>';
                        html += '<p class="wait-for-fetch">';
                        html += '<span>取：<span class="ng-binding fetch_' + item.uid + '">' + item.fetch + '</span></span>';
                        html += '</p>';
                        html += '<p class="wait-for-send">';
                        html += '<span>送：<span class="ng-binding send_' + item.uid + '">' + item.send + '</span></span>';
                        html += '</p>';
                        html += '<p class="wait-for-finish">';
                        html += '<span>完：<span class="ng-binding finish_' + item.uid + '">' + item.finish + '</span></span>';
                        html += '</p>';
                        html += '</div>';
                        html += '<div class="assign-wrapper">';
                        html += '<p class="name-wrapper">';
                        html += '<span class="name-label ng-binding">' + item.name + '</span>';
                        html += '</p>';
                        html += '<p>';
                        html += '<span class="delivery-detail" data-id="' + item.uid + '"></span>';
                        html += '<span class="assign ng-binding no-motorcycle" data-uid="' + item.uid + '">指派给他</span>';
                        html += '<span class="motorcycle">';
                        html += '<i class="fa fa-motorcycle"></i>';
                        html += '</span>';
                        html += '<span class="location" data-uid="' + item.uid + '">详情</span>';
						html += '<span class="off_work" style="background-color:red;" data-uid="' + item.uid + '">关</span>';
                        html += '</p>';
                        html += '</div>';
                        html += '<div class="ng-scope"><div class="ng-scope"></div></div>';
                        html += '</li>';
                    } else {
                        $('.fetch_' + item.uid).text(item.fetch);
                        $('.send_' + item.uid).text(item.send);
                        $('.finish_' + item.uid).text(item.finish);
                    }
                    
                    
                    if (labelArray[item.uid] != undefined) {
                        labelArray[item.uid].setLngLat(new T.LngLat(item.now_lng*1, item.now_lat*1));
                    } else {
                        var htmlMap = '';
                        htmlMap += '<div class="amap-marker" >';
                        htmlMap += '<div class="amap-marker-content" style="opacity: 1;">';
                        htmlMap += '<div id="__content' + item.uid + '" class="deliveryman-marker improve">';
                        htmlMap += '<div class="deliveryman-icon" data-id="' + item.uid + '" id="__' + item.uid + '">';
                        htmlMap += '<div class="name'+(item.traffic != '' ? ' '+item.traffic : '')+'" data-uid="' + item.uid + '">' + cut_str(item.name,6) + '</div>';
                        htmlMap += '<div class="display-area" data-uid="' + item.uid + '">';
                        htmlMap += '<div class="text-move-animation" data-uid="' + item.uid + '">';
                        htmlMap += '<span class="wait-for-take wait-for-take-count fetch_' + item.uid + '">' + item.fetch + '</span>';
                        htmlMap += '/';
                        htmlMap += '<span class="wait-for-delivery wait-for-delivery-count send_' + item.uid + '">' + item.send + '</span>';
                        htmlMap += '<div style="text-align: center; display:none; font-weight: normal; color: red; margin-top: -3px; height: 30px;">要单</div>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        htmlMap += '<i class="delivery-icon" data-id="' + item.uid + '"></i>';
                        htmlMap += '<span class="angle"></span>';
                        htmlMap += '<span class="b-oval"></span>';
                        htmlMap += '<span class="s-oval"></span>';
                        htmlMap += '</div>';
                        htmlMap += '<div class="deliveryman-icon-click" id="__detail' + item.uid + '">';
                        htmlMap += '<a class="close marker-content-close" href="javascript: void(0)" data-id="' + item.uid + '">×</a>';
                        htmlMap += '<div>' + item.name;
                        if(item.traffic != ''){
                            htmlMap += '<div class='+item.traffic+1+'>' + item.traffic_name + '</div>';
                        }
                        htmlMap += '<div class="finished">' + item.phone + '</div>';
                        htmlMap += '<div class="wait-for-take">待取餐：<span class="wait-for-take-count fetch_' + item.uid + '">' + item.fetch + '</span></div>';
                        htmlMap += '<div class="wait-for-delivery">待送达：<span class="wait-for-delivery-count send_' + item.uid + '">' + item.send + '</span></div>';
                        htmlMap += '<div class="wait-for-finish">已完成：<span class="wait-for-finish-count finish_' + item.uid + '">' + item.finish + '</span></div>';
                        htmlMap += '<div>将选择的<span class="dispatch-order-count">0</span>笔订单</div>';
                        htmlMap += '<div class="button zhipaibutton" data-uid="' + item.uid + '">';
                        htmlMap += '<a class="dispatch-button" data-uid="' + item.uid + '">指派给' + item.name + '</a>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        htmlMap += '</div>';
                        labelArray[item.uid] = new T.Label({text:htmlMap,position:new T.LngLat(item.now_lng*1, item.now_lat*1), offset:new T.Point(-32, -32)});  // 创建文本标注对象
                        map.addOverLay(labelArray[item.uid]);
                    }
                    
                    newLabelArray[item.uid] = labelArray[item.uid];
                });
//                html += '</ul>';
                for (var uid in sendUsers) {
                    if (newSendUsers[uid] == undefined || newSendUsers[uid] == '' || newSendUsers[uid] == null) {
                        $('#deliverymanItem_' + uid).remove();
                        map.removeOverLay(labelArray[uid]);
                    }
                }
                sendUsers = newSendUsers;
                labelArray = newLabelArray;
            }
            
            if (move != undefined && move == 1) {
                if (lng > 0 && lat > 0) {
                    map.panTo(new T.LngLat(lng, lat), 15);
                }
                
                $('.postman-list ul').remove();
            }
            if ($('.postman-list ul').find('li').length > 0) {
                $('.postman-list ul').append(html);
            } else {
                $('.postman-list ul').remove();
                $('.postman-list').append('<ul>' + html + '</ul>');
            }
            if ($('#searchUser').val() == '') {
                $('.postman-list').find('.empty-list-label').text('可选骑手(' + response.userCount + ')');
            }
			if(response.userCount == 0){
				$('.postman-item').remove();
			}
            $('#unGetCount').html('待指派 ( ' + response.unGetCount + ' )');
            $('#sendCount').html('配送中 ( ' + response.sendCount + ' )');
        }
    }, 'json');
}
var userSendOrderFun = function() {
    //加载每个配送员身上的未完成的单子
    var params = {'province_id':0, 'city_id':0, 'area_id':0};
    if ($('#choose_province').val() == null) {
        params.province_id = $('#choose_pca').attr('province_id');
    } else {
        params.province_id = $('#choose_province').val();
    }
    if ($('#choose_city').val() == null) {
        params.city_id = $('#choose_pca').attr('city_id');
    } else {
        params.city_id = $('#choose_city').val();
    }
    if ($('#choose_area').val() == null) {
        params.area_id = $('#choose_pca').attr('area_id');
    } else {
        params.area_id = $('#choose_area').val();
    }
    if ($('#choose_street').val() == null) {
        params.street_id = $('#choose_pca').attr('street_id');
    } else {
        params.street_id = $('#choose_street').val();
    }
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.get(url + 'initUserOrders', params, function(response){
        if (response.errcode == false) {
            userOrders = response.data;
        }
    }, 'json');
}
var loading = false, newOrderTip = null;
var orderHtmlFun = function(){
    //待指派的单子
    if (loading) return false;
    loading = true;
	initOrdersLayer = layer.msg('请求新单中...',{offset:0,shift:6,time:0,icon: 6});
	$('#choose_area').prop('disabled',true).css('color','#ccc');
    var params = {'type':type, 'province_id':0, 'city_id':0, 'area_id':0};
    if ($('#choose_province').val() == null) {
        params.province_id = $('#choose_pca').attr('province_id');
    } else {
        params.province_id = $('#choose_province').val();
    }
    if ($('#choose_city').val() == null) {
        params.city_id = $('#choose_pca').attr('city_id');
    } else {
        params.city_id = $('#choose_city').val();
    }
    if ($('#choose_area').val() == null) {
        params.area_id = $('#choose_pca').attr('area_id');
    } else {
        params.area_id = $('#choose_area').val();
    }
    if ($('#choose_street').val() == null) {
        params.street_id = $('#choose_pca').attr('street_id');
    } else {
        params.street_id = $('#choose_street').val();
    }
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    initOrdersAjax = $.get(url + 'initOrders', params, function(response){
		if(response.deliver_model){
			$('.dropdown-toggle span').html($.trim($('.dropdown-menu li[data-model="'+response.deliver_model+'"]').html()));
			deliver_model = response.deliver_model;
		}
		
        if (response.errcode == false) {
            var orderHtml = '', newOrders = [];
            if (response.data != null && response.data.length > 0) {
                $.each(response.data, function(i, item){
                    if ($('#supply_' + item.supply_id).length <= 0) {
                        orderHtml += '<div class="order-item ng-scope" id="supply_' + item.supply_id + '" data-supply_id = "' + item.supply_id + '">';
                        if (item.order_from == 1) {
                            orderHtml += '<div class="tips">饿了么</div>';
                        } else if (item.order_from == 2) {
                            orderHtml += '<div class="tips">美团</div>';
                        } else if (item.order_from == 3) {
                            orderHtml += '<div class="tips">帮送</div>';
                        } else if (item.order_from == 4 || item.order_from == 5) {
                            orderHtml += '<div class="tips">帮买</div>';
                        }
                        orderHtml += '<div class="checked-icon">';
                        orderHtml += '<i class="fa fa-check"></i>';
                        orderHtml += '</div>';
                        orderHtml += '<div class="sign ng-hide"></div>';
                        orderHtml += '<div class="section">';
                        orderHtml += '<div class="wrapper">';
                        orderHtml += '<i class="icon icon-fetch"></i>';
                        if (item.order_from == 5) {
                            orderHtml += '<div class="rst-name ng-binding">就近购买</div>';
                        } else {
                            orderHtml += '<div class="rst-name ng-binding">' + item.store_name + '</div>';
                        }
                        
                        orderHtml += '</div>';
                        orderHtml += '</div>';
                        orderHtml += '<div class="section">';
                        orderHtml += '<div class="wrapper">';
                        orderHtml += '<i class="icon icon-delivery"></i>';
                        orderHtml += '<div class="des-addr ng-binding">' + item.aim_site + '</div>';
                        orderHtml += '</div>';
                        orderHtml += '</div>';
                        orderHtml += '<div class="other-info clearfix">';
                        orderHtml += '<div class="info-item order-number">';
                        
                        if (item.order_from == 3) {
                            orderHtml += '<div class="cooking-time" style="padding-top: 12px">取件<span class="ng-binding">' + item.server_time + '</span></div>';
                        } else if (item.order_from == 4) {
                            orderHtml += '<div class="cooking-time" style="padding-top: 12px">指定地址</div>';
                        } else if (item.order_from == 5) {
                            orderHtml += '<div class="cooking-time" style="padding-top: 12px">就近购买</div>';
                        } else {
                            orderHtml += '<div class="res-number active ng-binding">' + item.fetch_number + '</div>';
                            orderHtml += '<div class="cooking-time">预计<span class="ng-binding">' + item.order_out_time + '</span>出餐</div>';
                        }
                        
                        orderHtml += '</div>';
                        orderHtml += '<div class="info-item order-number ng-hide">';
                        orderHtml += '<div class="res-number inactive ng-binding">#7</div>';
                        orderHtml += '</div>';
                        orderHtml += '<div class="info-item timing">';
                        orderHtml += '<div class="ng-isolate-scope">';
                        orderHtml += '<div class="minutes ng-binding" style="font-weight:normal">' + item.expect_use_date + '</div>';
                        orderHtml += '<div class="hint weak ng-binding">用户期望送达</div>';
                        orderHtml += '</div>';
                        orderHtml += '</div>';
                        orderHtml += '<div class="info-item detail"><a class="orderDetail" data-order_id="' + item.order_id + '" data-item="' + item.item + '">详情</a></div>';
                        orderHtml += '</div>';
                        if(item.deliver_user_info != undefined){
                            orderHtml += '<div class="section"><div class="wrapper"><i class="icon icon-yu"></i>' +
                                '<div class="des-addr ng-binding">'+item.deliver_user_info.name+'    '+item.deliver_user_info.traffic_name+'   预计送达:'+item.expect_arrival_short_date+'</div></div></div>';
                        }else if(item.not_dispatch == '1'){
                            orderHtml += '<div class="section" style="padding-left:20px;font-size:12px;"><div class="wrapper">' +
                                '<div class="des-addr ng-binding">期望送达时间不在算法处理的时间范围内</div></div></div>';
                        }
                        orderHtml += '</div>';
                    }
                    newOrders[item.supply_id] = item;
                });
            }
            if (newOrders.length > 0) {
                supplyArray = newOrders;
                $('.order-list .no-data').remove();
                $('.order-list').append(orderHtml);
				
				$.each($('.order-list .order-item'), function(i,item){
					if(!newOrders[$(item).data('supply_id')]){
						$(item).remove();
					}
				});
				
                if (newOrderTip == null && orderHtml != '' && $('.voice-control i').hasClass('fa-volume-up') && deliver_model != '2') {
                    $('#wait_manual_order_hint').trigger('play');
                    newOrderTip =layer.open({
                        title:'新订单提示'
                        ,content:'您有新的订单需要处理。'
                        ,btn: ['确定']
                        ,end: function(index){
                            $('#wait_manual_order_hint').trigger('pause');
                            newOrderTip = null;
                        }
                    });
                }
                
            } else {
                supplyArray = [];
                $('.order-list').html('<p class="no-data ng-scope">暂无新订单</p>');
            }
            $('.order-tab .assigning-count').text('(' + response.nowCount + ')');
            $('.order-tab .other-count').text('(' + response.otherCount + ')');
        } else {
            supplyArray = [];
            $('.order-list').html('<p class="no-data ng-scope">' + response.msg + '</p>');
            $('.order-tab .assigning-count').text('(0)');
            $('.order-tab .other-count').text('(0)');
            $('#unGetCount').html('待指派 (0)');
        }
		loading = false;
		layer.close(initOrdersLayer);
		$('#choose_area').prop('disabled',false).css('color','#444');
        orderHtmlFunTime = setTimeout(function(){
			$('#choose_area').prop('disabled',false).css('color','#444');
			loading = false;
			orderHtmlFun();
		}, 4000);
		
    }, 'json').error(function(xhr,errorText,errorType){
		console.log('xhr',xhr);
		console.log('errorText',errorText);
		console.log('errorType',errorType);
		
		loading = false;
		layer.close(initOrdersLayer);
		$('#choose_area').prop('disabled',false).css('color','#444');
        orderHtmlFunTime = setTimeout(function(){
			$('#choose_area').prop('disabled',false).css('color','#444');
			loading = false;
			orderHtmlFun();
		}, 4000);
	});
}

function sendUsersHtml(data, sort)
{
    var html = '<ul>';
    var ii = 0;
    $.each(data, function(i, item){
        if (undefined != item) {
            html += '<li id="deliverymanItem_' + item.uid + '" class="postman-item ng-scope">';
            html += '<p>';
            html += '<span class="name ng-binding">' + item.name + '</span>';
            html += '<span class="new-order-count ng-binding ng-hide">+0</span>';
            html += '</p>';
            html += '<p class="staff-mobile ng-binding">' + item.phone + '</p>';
            html += '<div>';
            html += '<p class="wait-for-fetch">';
            html += '<span>取：<span class="ng-binding fetch_' + item.uid + '">' + item.fetch + '</span></span>';
            html += '</p>';
            html += '<p class="wait-for-send">';
            html += '<span>送：<span class="ng-binding send_' + item.uid + '">' + item.send + '</span></span>';
            html += '</p>';
            html += '<p class="wait-for-finish">';
            html += '<span>完：<span class="ng-binding finish_' + item.uid + '">' + item.finish + '</span></span>';
            html += '</p>';
            html += '</div>';
            html += '<div class="assign-wrapper">';
            html += '<p class="name-wrapper">';
            html += '<span class="name-label ng-binding">' + item.name + '</span>';
            html += '</p>';
            html += '<p>';
            html += '<span class="delivery-detail" data-id="' + item.uid + '"></span>';
            html += '<span class="assign ng-binding no-motorcycle" data-uid="' + item.uid + '">指派给他</span>';
            html += '<span class="motorcycle">';
            html += '<i class="fa fa-motorcycle"></i>';
            html += '</span>';
            html += '<span class="location" data-uid="' + item.uid + '">详情</span>';
			html += '<span class="off_work" style="background-color:red;" data-uid="' + item.uid + '">关</span>';
            html += '</p>';
            html += '</div>';
            if (ii == 0 && sort == 1) {
                html += '<div class="ng-scope"><div class="ranking ranking ng-scope"></div></div>';
            } else if (ii == 1 && sort == 1) {
                html += '<div class="ng-scope"><div class="ranking ranking1 ng-scope"></div></div>';
            } else if (ii == 2 && sort == 1) {
                html += '<div class="ng-scope"><div class="ranking ranking2 ng-scope"></div></div>';
            } else {
                html += '<div class="ng-scope"><div class="ng-scope"></div></div>';
            }
            html += '</li>';
            ii ++;
        }
    });
    html += '</ul>';
    $('.postman-list ul').remove();
    $('.postman-list').append(html);
    
}


var compare = function (prop) {
    return function (obj1, obj2) {
        var val1 = obj1[prop], val2 = obj2[prop];
        if (val1 < val2) {
            return 1;
        } else if (val1 > val2) {
            return -1;
        } else {
            return 0;
        }
    }
}

var compare_asc = function (prop) {
    return function (obj1, obj2) {
        var val1 = obj1[prop], val2 = obj2[prop];
        if (val1 < val2) {
            return -1;
        } else if (val1 > val2) {
            return 1;
        } else {
            return 0;
        }
    }
}

//art弹框组件
function artiframe(url, title, width, height, lock, resize, background, button, id, fixeds, closefun, left, top, padding){
    if(url.indexOf("?") != -1){
        url = url+'&frame=1';
    }else{
        url = url+'?frame=1';
    }
    if (!width) width = 'auto';
    if (!height) height = 'auto';
    if (!lock) lock = false;
    if (!resize) resize = false;
    if (!background) background = 'black';
    if (!closefun) closefun = null;
    if (!button) button = null;
    if (!left) left = '50%';
    if (!top) top = '38.2%';
    if (!id) id = null;
    if (!fixeds) fixeds = false;
    if (!padding) padding = 0;
    art.dialog.open(url, {
        init: function(){
            var iframe = this.iframe.contentWindow;
            window.top.art.dialog.data('iframe' + id, iframe);
        },
        id: id,
        title: title,
        padding: padding,
        width: width,
        height: height,
        lock: lock,
        resize: resize,
        background: background,
        button: button,
        fixed: fixeds,
        close: closefun,
        left: left,
        top: top,
        opacity:'0.4'
    });
}


//显示省份
function show_province(){
    let params = {

    }
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.post(areaUrl + 'ajax_province',params, function(result){
        result = $.parseJSON(result);
        if(result.error == 0){
            if ($('#choose_pca').attr('is_province') == 1) {
                var area_dom = '<select id="choose_province" name="province_id" class="select2-choice ui-select-match ng-scope">';
            } else {
                var area_dom = '<select id="choose_province" name="province_id" class="select2-choice ui-select-match ng-scope" style="display:none;">';
            }
//            area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('province_id') ? 'selected="selected"' : '')+'>全部省</option>';
            $.each(result.list,function(i,item){
                area_dom += '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('province_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
            });
            area_dom += '</select>';
            
            $('#choose_pca').prepend(area_dom);
            show_city($('#choose_province').val(),$('#choose_province').find('option:selected').html(),1);
            $('#choose_province').change(function(){
                show_city($(this).val(), $(this).find('option:selected').html(), 1);
            });
        }else if(result.error == 2){
            var area_dom = '<select id="choose_province" name="province_id" style="display:none;">';
            area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
            area_dom += '</select>';
            $('#choose_pca').prepend(area_dom);
            show_city(result.id, result.name, 0);
        }else{
            art.dialog({
                time: 5,
                title: '提示信息1',
                opacity:'0.4',
                fixed: true,
                resize: false,
                content: result.info
            });
        }
    });
}
//显示城市

function show_city(id, name, type) {
    let params = {id:id, name:name, type:type}
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.post(areaUrl + 'ajax_city',params, function(result){
        result = $.parseJSON(result);
        if(result.error == 0){
            if ($('#choose_pca').attr('is_city') == 1) {
                var area_dom = '　<select id="choose_city" name="city_id" class="select2-choice ui-select-match ng-scope">';
            } else {
                var area_dom = '　<select id="choose_city" name="city_id" class="select2-choice ui-select-match ng-scope" style="display:none;">';
            }
//            area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('city_id') ? 'selected="selected"' : '')+'>全部市</option>';
            $.each(result.list,function(i,item){
                area_dom += '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('city_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
            });
            area_dom += '</select>';
            
            if (document.getElementById('choose_city')) {
                $('#choose_city').replaceWith(area_dom);
            } else if(document.getElementById('choose_province')) {
                $('#choose_province').after(area_dom);
            } else {
                $('#choose_pca').prepend(area_dom);
            }
            
            show_area($('#choose_city').val(),$('#choose_city').find('option:selected').html(),1);
            $('#choose_city').change(function(){
                show_area($(this).val(), $(this).find('option:selected').html(), 1);
            });
            
        } else if(result.error == 2) {
            var area_dom = '<select id="choose_city" name="city_id" style="display:none;">';
            area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
            area_dom += '</select>';
            $('#choose_pca').prepend(area_dom);
            show_area(result.id, result.name, 0);
        } else {
            art.dialog({
                time: 5,
                title: '提示信息',
                opacity:'0.4',
                fixed: true,
                resize: false,
                content: result.info
            });
        }
    });
}

//显示区域
function show_area(id, name, type){
    let params = {id:id, name:name, type:type}
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.post(areaUrl + 'ajax_area',params,function(result){
        result = $.parseJSON(result);
        if(result.error == 0){
            if ($('#choose_pca').attr('is_area') == 1) {
                var area_dom = '　<select id="choose_area" name="area_id" class="select2-choice ui-select-match ng-scope" >';
            } else {
                var area_dom = '　<select id="choose_area" name="area_id" class="select2-choice ui-select-match ng-scope" style="display:none;">';
            }
            area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('area_id') ? 'selected="selected"' : '')+'>全部区</option>';
            $.each(result.list, function(i,item) {
                area_dom += '<option value="'+item.id+'" '+(item.id == $('#choose_pca').attr('area_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
            });
            area_dom += '</select>';
            
            if (document.getElementById('choose_area')) {
                $('#choose_area').replaceWith(area_dom);
            } else if(document.getElementById('choose_city')) {
                $('#choose_city').after(area_dom);
            } else {
                $('#choose_pca').prepend(area_dom);
            }

            show_street($('#choose_area').val(),$('#choose_area').find('option:selected').html(),1);
            $('#choose_area').change(function(){
                show_street($(this).val(), $(this).find('option:selected').html(), 1);
            });

            /*clearTimeout(orderHtmlFunTime);
            orderHtmlFun();
            userSendOrderFun();
            userListFun(1);
            
			getCityUnGetData();*/
        } else if(result.error == 2) {
            var area_dom = '<select id="choose_area" name="area_id" style="display:none;">';
            area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
            area_dom += '</select>';
            $('#choose_pca').prepend(area_dom);
            clearTimeout(orderHtmlFunTime);
            orderHtmlFun();
            userSendOrderFun();
            userListFun(1);
			
			getCityUnGetData();
        }else{
            art.dialog({
                time: 5,
                title: '提示信息',
                opacity:'0.4',
                fixed: true,
                resize: false,
                content: result.info
            });
        }
    });
}

function show_street(id, name, type){
    let params = {id:id, name:name, type:type}
    if(tianditu_web_key){
        params.coordinate_type = 'wgs84'
    }
    $.post(areaUrl + 'ajax_street',params,function(result){
        result = $.parseJSON(result);
        if(result.error == 0){
            if ($('#choose_pca').attr('is_street') == 1) {
                var area_dom = '　<select id="choose_street" name="street_id" class="select2-choice ui-select-match ng-scope" >';
            } else {
                var area_dom = '　<select id="choose_street" name="street_id" class="select2-choice ui-select-match ng-scope" style="display:none;">';
            }
            area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('street_id') ? 'selected="selected"' : '')+'>全部乡镇</option>';
            $.each(result.list, function(i,item) {
                area_dom += '<option value="'+item.id+'" '+(item.id == $('#choose_pca').attr('street_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
            });
            area_dom += '</select>';

            if (document.getElementById('choose_street')) {
                $('#choose_street').replaceWith(area_dom);
            } else if(document.getElementById('choose_area')) {
                $('#choose_area').after(area_dom);
            } else {
                $('#choose_pca').prepend(area_dom);
            }
            clearTimeout(orderHtmlFunTime);
            orderHtmlFun();
            userSendOrderFun();
            userListFun(1);

            getCityUnGetData();

            $('#choose_street').change(function(){
                clearTimeout(orderHtmlFunTime);
                orderHtmlFun();
                userSendOrderFun();
                userListFun(1);
            });
        } else if(result.error == 2) {
            var area_dom = '<select id="choose_street" name="street_id" style="display:none;">';
            area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
            area_dom += '</select>';
            $('#choose_pca').prepend(area_dom);
            clearTimeout(orderHtmlFunTime);
            orderHtmlFun();
            userSendOrderFun();
            userListFun(1);

            getCityUnGetData();
        }else{
            art.dialog({
                time: 5,
                title: '提示信息',
                opacity:'0.4',
                fixed: true,
                resize: false,
                content: result.info
            });
        }
    });
}

var lastUnGetData = {};
var needPlayUnGetData = false;
var area_list = [];
function getCityUnGetData(){
	return false;
	setInterval(function(){
		$.post(url+'getCityUnGetData',{city_id:$('#choose_city').val()},function(data){
			$('#getCityUnGetDataHtml').remove();
			var getCityUnGetDataHtml = '';
			needPlayUnGetData = false;
			if(data.status == 1){
				for(var i in data.info){
					var item = data.info[i];
					if(area_list[i]){
						if(getCityUnGetDataHtml == ''){
							getCityUnGetDataHtml += '<span style="margin-right:5px;">以下区域有新订单：</span>';
						}
						getCityUnGetDataHtml += '<a href="'+url+'desk&area_id='+i+'" style="margin-right:20px;">'+area_list[i]+' ( '+item+' )</a>';
					}
					if(area_list[i] && (!lastUnGetData[i] || item != lastUnGetData[i]) && i != $('#choose_area').val()){
						needPlayUnGetData = true;
					}
				}
				if(needPlayUnGetData && $('.voice-control i').hasClass('fa-volume-up')) {
                    $('#wait_manual_order_other_hint').trigger('play');
                }
				console.log('getCityUnGetDataHtml',getCityUnGetDataHtml);
				
				lastUnGetData = data.info;
			}else{
				lastUnGetData = {};
			}
			
			if(getCityUnGetDataHtml == ''){
				getCityUnGetDataHtml = '暂时没有区域有新订单，5秒钟请求一次';
			}
			
			getCityUnGetDataHtml = '<div id="getCityUnGetDataHtml" style="position:fixed;top:72px;left:300px;">'+getCityUnGetDataHtml+'</div>';

			$('body').append(getCityUnGetDataHtml);
		});
	},5000);
}


function get_length(s){
	var char_length = 0;
	for (var i = 0; i < s.length; i++){
		var son_char = s.charAt(i);
		encodeURI(son_char).length > 2 ? char_length += 1 : char_length += 0.5;
	}
	return char_length;
}
function cut_str(str, len){
	var char_length = 0;
	for (var i = 0; i < str.length; i++){
		var son_str = str.charAt(i);
		encodeURI(son_str).length > 2 ? char_length += 1 : char_length += 0.5;
		if (char_length >= len){
			var sub_len = char_length == len ? i+1 : i;
			return str.substr(0, sub_len);
			break;
		}
	}
	return str;
}