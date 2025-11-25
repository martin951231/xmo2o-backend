var goodsCart = [], goodsNumber = 0, goodsCartMoney = 0, goods_price_list = [], goods_index_list = [], goodsCartPackCharge = 0, goods_list = [];
$(document).ready(function(){
	//百度地图

    var map = new BMap.Map("biz-map");
    map.centerAndZoom(new BMap.Point(store_long, store_lat), 15);
    map.enableScrollWheelZoom();
  //店铺图标
    var pt2 = new BMap.Point(store_long, store_lat);
    var storeIcon = new BMap.Icon(static_path+"images/storesite.png", new BMap.Size(32,32));
    var marker2 = new BMap.Marker(pt2,{icon:storeIcon});  // 创建标注
    map.addOverlay(marker2);

    $('.goodUserRight li.te, .goodUserTwo .yi li a').hover(function(){
    	$(this).css('color', main_color);
    }, function(){
    	if (!$(this).attr('class') || $(this).attr('class').indexOf('active') == -1){
    		$(this).css('color', '');
    	}
    })
    $('.paging li.active').css('background', main_color)

});