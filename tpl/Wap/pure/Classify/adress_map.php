<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <title>查看位置</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?t={pigcms{$_SERVER.REQUEST_TIME}" charset="utf-8"></script>
    <script src="https://cdn.jsdelivr.net/hls.js/latest/hls.min.js"></script>
    <script type="text/javascript">
    var get_route_url = "{pigcms{:U('Classify/get_route')}";
    var vid= "{pigcms{$Think.get.vid}";
    </script>
<style>
    body {
        background: #f0eff5;
        overflow: auto;
        position: static;
        padding:0px;
        margin:0px
    }
    .album {
        height: 9.9rem;
        position: relative;
    }
    #shopDetailMapBar {
        height: 60px;
        line-height: 60px;
        padding: 0 12px 0 20px;
        background: white url(../images/range.png) no-repeat;
        background-size: 11px 14px;
        background-position: 5px center;
        position: fixed;
        width: 100%;
        bottom: 0px;
    }
    .closeBtn {
        position: fixed;
        left: 0;
        top: 0;
        width: 67px;
        height: 67px;
        z-index: 999;
    }
    .closeBtn div {
        width: 44px;
        height: 44px;
        background-color: black;
        border-radius: 100%;
        border: 2px solid #ccc;
        position: absolute;
        left: 15px;
        top: 15px;
    }
    .closeBtn div:before, .closeBtn div:after {
        content: '';
        height: 2px;
        width: 24px;
        display: block;
        background: white;
        border-radius: 10px;
        -webkit-border-radius: 10px;
        -moz-border-radius: 10px;
        position: absolute;
        top: 19px;
        left: 8px;
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
        -moz-transform: rotate(-45deg);
        -o-transform: rotate(-45deg);
        -ms-transform: rotate(-45deg);
    }
    .closeBtn div:after {
        transform: rotate(45deg);
        -webkit-transform: rotate(45deg);
        -moz-transform: rotate(45deg);
        -o-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
    }
    #shopDetailMapAddress{font-size:12px}
    #shopDetailMapAddressGo{
        line-height: 1.5;
        margin-top: 15px;
        background-color: #06C1AE;
        color: white;
        position: absolute;
        right: 42px;
        top: 0;
        display: inline-block;
        border: 1px solid #06C1AE;
        padding: 4px 10px;
        border-radius: 2px;
    }
    .album img {
        width: 100%;
        height: 9.9rem;
        -webkit-transform: translateY(-50%);
        -ms-transform: translateY(-50%);
        top: 50%;
        position: relative;
    }

    .album .desc {
        position: absolute;
        bottom: 0;
        width: 100%;
        color: white;
        text-align: center;
        height: 1.56rem;
        line-height: 1.56rem;
        font-size: 12px;
        background: rgba(0, 0, 0, .5);
    }

    .albumContainer {
        position: fixed;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background: #000;
        z-index: 1000;
        display: none;
        overflow: hidden;
        -webkit-transform-origin:0px 0px;
        opacity:1;
        -webkit-transform:scale(1,1);
    }

    .swiper-slide{
        display: -webkit-box;
        display: -ms-flexbox;
        overflow: hidden;
        -webkit-box-align: center;
        -webkit-box-pack: center;
        -ms-box-align: center;
        -ms-flex-pack: justify;
    }
    .swiper-slide img{
        width:auto;
        height:auto;
        max-width:100%;
        max-height:90%;
    }
    .swiper-pagination{
        left: 0;
        top: 10px;
        text-align: center;
        bottom:auto;
        right:auto;
        width:100%;
    }
    .swiper-close{
        right:10px;
        top:5px;
        text-align:right;
        position:absolute;
        z-index:21;
        color:white;
        font-size:.7rem;
    }
    span.tag{
        background: #fdb338;
        color: #fff;
        line-height: 1.5;
        display: inline-block;
        padding: 0 .06rem;
        font-size: .24rem;
        border-radius: .06rem;
    }


    #enter_im_div {
        bottom: 60px;
        left:10px;
        z-index: 11;
        position: fixed;
        width: 94px;
        height:31px;
    }
    #enter_im {
        width: 94px;
        position: relative;
        display: block;
    }
    a {
        color: #323232;
        outline-style: none;
        text-decoration: none;
    }

</style>
<body>

    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
    <div id="pageMap" class="pageDiv" style="display:none">
        <div id="shopDetailMapClose" class="closeBtn"><a href="javascript:history.go(-1)"><div></div></a></div>
        <div id="shopDetailMapBiz" style="height:100%"></div>
        <div id="shopDetailMapBar">
            <span id="shopDetailMapAddress">地址</span>
            <a class="btn right" id="shopDetailMapAddressGo">查看路线</a>
        </div>
    </div>
    <script src="{pigcms{$static_path}js/jquery.cookie.js"></script>
    <script type="text/javascript" src="{pigcms{$_SERVER.REQUEST_SCHEME}://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    <script>
        var window_width = $(window).width();
        var window_height = $(window).height();
        var longitude="{pigcms{$Think.get.longitude}";
        var latitude="{pigcms{$Think.get.latitude}";
        var adress="{pigcms{$Think.get.address}";
        var url=''
        console.log(adress)
        $('body,#pageMap').css({width:window_width,'min-height':window_height});
            var url='map-'+longitude+'-'+latitude+'-'+encodeURIComponent(adress)
            redirectPage(url)
        function redirectPage(url,type){
            var animateCss = {},animateAfterCss = {},nowPageCss = {},orderPage = 1;
            if(!type){
                type = 'openRightWindow';
            }
            nowPageCss = {'display':'block'};
            var locationHash = url.replace("#","");
            var locationHashParam = locationHash.split('-');
            var locationHashItem = locationHashParam[0];
            nowPage = locationHashItem;
            var loadPage = '';
            $('#pageMap').css(nowPageCss);
           // location.hash = locationHash;
            url=locationHash;
            hash_handle()
        }
        function hash_handle(){
            $('body').css('overflow-y','auto');
            var locationHashParam = url.split('-');
            var locationHashItem = locationHashParam[0];
           showMap(locationHashParam[1],locationHashParam[2],locationHashParam[3]);

        }
        function showMap(lng,lat,address){
            nowPage = 'map';
            $('#shopDetailMapAddress').html(decodeURIComponent(address));
            $('#shopDetailMapBiz').height(window_height-60);
            var map = new BMap.Map("shopDetailMapBiz",{enableMapClick:false});
            map.centerAndZoom(new BMap.Point(lng,lat), 16);

            //map.addControl(new BMap.ZoomControl());  //添加地图缩放控件
            var marker1 = new BMap.Marker(new BMap.Point(lng,lat));  //创建标注
            map.addOverlay(marker1);                 // 将标注添加到地图中
            //创建信息窗口
            var infoWindow1 = new BMap.InfoWindow(decodeURIComponent(address));
            marker1.openInfoWindow(infoWindow1);
            marker1.addEventListener("click", function(){this.openInfoWindow(infoWindow1);});
            $(".lodingCover").remove()
        }
        $('#shopDetailMapAddressGo').click(function(){
            if(motify.checkLifeApp() && motify.getLifeAppVersion() >= 50){
                window.lifepasslogin.startToNavigation(lng,lat,address);
            }else if(typeof(wxSdkLoad) != "undefined"){
                if(typeof(is_google_map) != "undefined"){
                    window.location.href = get_route_url+'&vid='+vid;
                }else{
                    pageLoadTips();
                    $.getJSON(baiduToGcj02Url+"&baidu_lat="+lat+"&baidu_lng="+lng,function(result){
                        pageLoadHides();
                        if(result['status'] == 1){
                            wx.ready(function (){
                                wx.openLocation({
                                    latitude: result['info']['lat'],
                                    longitude: result['info']['lng'],
                                    name: decodeURIComponent(address), // 位置名
                                    address: decodeURIComponent(address), // 地址详情说明
                                    scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                                    infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                                });
                            });
                        }else{
                            window.location.href = get_route_url+'&vid='+vid;
                        }
                    })
                }

            }else{
                window.location.href = get_route_url+'&vid='+vid;
            }
        });
    </script>
    <script type="text/javascript">
        window.shareData = {
            "moduleName":"Classify",
            "moduleID":"0",
            "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
            "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Classify/ShowDetail',array('vid'=>$_GET['vid']))}",
            "tTitle": "{pigcms{$detail['title']}",
            "tContent": "{pigcms{$detail['title']}"
        };
    </script>
    </body>
</html>