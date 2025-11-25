var myScroll;
var isApp = motify.checkApp();
$(function(){
    $('.storeProList .more').click(function(){
        $(this).remove();
        $('.storeProList li').show();
		if(!isApp){
			myScroll.refresh();
		}
    });
    navi()
});


function navi() {

    $('.navi').click(function(){
        var _this = $(this);
        if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
            return false;
        }
        if(typeof wx !=='undefined'){
            var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
            $.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
                layer.close(layerLoadTip);
                if(result['status'] == 1){
                    wx.openLocation({
                        latitude: result['info']['lat'],
                        longitude: result['info']['lng'],
                        name: _this.data('name'), // 位置名
                        address: _this.data('address'), // 地址详情说明
                        scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                        infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                    });
                }else{
                    layer.open({
                        content: result.msg
                        ,btn: '好的'
                    });
                }
            });
        }else{
            var url = "http://api.map.baidu.com/direction?origin=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent(_this.data('address'))+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
            // console.log(url);
            location.href = url;
        }
        return false;
    });
}