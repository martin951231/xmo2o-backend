<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>兴趣圈子</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}css/app.css" rel="stylesheet"/>
        <link href="{pigcms{$static_path}css/index_index.css" rel="stylesheet"/>
        <link href="{pigcms{$static_path}css/index.css" rel="stylesheet"/>
        <script src="{pigcms{$static_path}js/iscroll.js"></script>
        <script src="{pigcms{$static_path}js/zepto.min.js"></script>
        <script src="{pigcms{$static_path}scripts/jquery-1.8.3.min.js"></script>
        <script src="{pigcms{$static_path}scripts/common.js"></script>
        <script src="{pigcms{$static_path}scripts/swiper-3.3.1.jquery.min.js"></script>
        <script src="{pigcms{$static_path}scripts/index.js"></script>
        <script>
        var more_url = "{pigcms{:U('Fav/index')}",total_page = {pigcms{$totalpage};</script>
    </head>
    <body>
        <view class="container">
            <div class="slides_scoll">
                <view class="search">
                    <view class="sec_n link-url" data-url="{pigcms{:U('Fav/search')}">
                        <span>搜索帖子</span>
                    </view>
                    <view class="esther link-url" data-url="{pigcms{:U('Fav/write_dynamic')}"></view>
                </view>

                <div class="slider bw">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="icon-list clr" >
                                    <volist name="cateory" id="ca">
                                  <a href="{pigcms{:U('Mycircle/details',array('id'=>$ca['id']))}" class="icon">
                                        <view class="icon-circle">
                                            <img src="{pigcms{$config.site_url}{pigcms{$ca.logo}"/>
                                        </view>
                                        <view class="icon-desc">
                                            <text>{pigcms{$ca.name}</text>
                                        </view>
                                    </a>
                                     </volist>
                                   <a  href="{pigcms{:U('Fav/whole')}" class="icon">
                                        <view class="icon-circle">
                                            <img src="{pigcms{:file_domain()}/upload/adver/000/000/001/5854a4daae6e2.png"/>
                                        </view>
                                        <view class="icon-desc">
                                            <text>全部版块</text>
                                        </view>
                                    </a>
                                </div>
                            </div>
                        </div> 
                        <div class="swiper-pagination"></div>    
                    </div>
                </div>

                <view class="refresh bw clr">
                    <view class="last">最后刷新：{pigcms{$new_refresh|date="Y-m-d",###}</view>
                </view>

               <div class="postlist"  id="wrapper">
                <div class="list"  >

                            <div id="pullDown">
                                <span class="pullDownIcon"></span>
                                <!--<span class="pullDownLabel">下拉刷新...</span>-->
                            </div>
           <div id="wo">
                      <volist name="dynamic" id="dy">
                    <a href="{pigcms{:U('Mycircle/index_details',array('id'=>$dy['id']))}" class="newslist">
                        <view class="one">
                            <view class="tit">
                                <view class="h2">{pigcms{$dy.title}</view>
                                <view class="bot">
                                <span>{pigcms{$dy.nickname}</span>
                                <span>{pigcms{$dy.read_num}阅读</span>
                                </view>
                            </view>
                            <view class="img">
                                <img src="{pigcms{$config.site_url}{pigcms{$dy.image}"/>
                                <!--<view class="pho">2图</view>-->
                            </view>
                        </view>
                    </a>
                   </volist>
                   </div>
                            <div id="pullUp">
                                <span class="pullUpIcon"></span>
                                <span class="pullUpLabel"></span>
                            </div>

                    </div>
                    

                </div>
            </div>
             <view class="bottom_nav pageSlider">
                <ul>
                    <li class="index ">
                        <a href="{pigcms{:U('Index/index')}">首页</a>
                    </li>
                    <li class="fav on">
                        <a href="{pigcms{:U('Fav/index')}">兴趣</a>
                    </li>
                    <li class="myquan">
                        <a href="{pigcms{:U('Mycircle/index')}">我的圈</a>
                    </li>
                    <li class="my">
                        <a href="{pigcms{:U('Center/index')}">个人中心</a>
                    </li>
                </ul>
            </view>
        </view>
       <script>
    var data,myScroll,pullDownEl, pullDownOffset,pullUpEl, pullUpOffset,generatedCount = 0;
    var now_page = 1 ;
    var detail_url = "{pigcms{:U('Mycircle/index_details')}";
    
function pullUpAction () {
     now_page +=1;  
     var get_more_url = more_url+"&page="+now_page;
    if(now_page <= total_page ){
        $.getJSON(get_more_url, function (data, state) {
            if (data && state == 'success') {
                $.each(data,function(ii,vv){
                  if(typeof(vv)=="object"){
                  var html = "";
                  html += "<a href='"+detail_url+"&id="+vv.id+"' class='newslist'>";
                  html += "<view class='one'><view class='tit'>";
                  html += "<view class='h2'>"+vv.title+"</view><view class='bot'>";
                  html += "<span>"+vv.nickname+"</span><span>"+vv.read_num+"阅读</span></view></view>";
                  html += "<view class='img'><img src='{pigcms{$config.site_url}"+vv.image+"'/></view></view></a>";
                  }
                  $('#wo').append(html);
                 })
             }
         });
      }
      
 }
    document.addEventListener('touchmove', function (e) { e.preventDefault(); }, false);
    document.addEventListener('DOMContentLoaded', loaded, false);

    function loaded() {
        pullDownEl = document.getElementById('pullDown');
        pullDownOffset = pullDownEl.offsetHeight;
        pullUpEl = document.getElementById('pullUp');   
        pullUpOffset = pullUpEl.offsetHeight;
        myScroll = new iScroll('wrapper', {
            vScrollbar : false,
            topOffset : pullDownOffset,
            onRefresh : function () {
                if (pullDownEl.className.match('loading')) {
                    pullDownEl.className = '';
                    pullDownEl.querySelector('.pullDownLabel').innerHTML = '下拉刷新...';
                } else if (pullUpEl.className.match('loading')) {
                    pullUpEl.className = '';
                    pullUpEl.querySelector('.pullUpLabel').innerHTML = '上拉加载更多...';
                }
            },
            onScrollMove: function () {
                if (this.y > 5 && !pullDownEl.className.match('flip')) {
                    pullDownEl.className = 'flip';
                    pullDownEl.querySelector('.pullDownLabel').innerHTML = '松手开始更新...';
                    this.minScrollY = 0;
                } else if (this.y < (this.maxScrollY - 5) && !pullUpEl.className.match('flip')) {
                    pullUpEl.className = 'flip';
                    if(now_page < total_page){
                       pullUpEl.querySelector('.pullUpLabel').innerHTML = '松手开始更新...';
                     }else{
                        pullUpEl.querySelector('.pullUpLabel').innerHTML = '没有更多动态.';
                     }
                }
            },
            onScrollEnd: function () {
                if (pullDownEl.className.match('flip')) {
                    pullDownEl.className = 'loading';
                    pullDownEl.querySelector('.pullDownLabel').innerHTML = '加载中...';                
                    pullDownAction();
                } else if (pullUpEl.className.match('flip')) {
                    pullUpEl.className = 'loading';
                    pullUpEl.querySelector('.pullUpLabel').innerHTML = '加载中...';                
                    pullUpAction();
                }
            }
        });
    }
    </script> 
        
    </body>
</html>
