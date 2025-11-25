<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<if condition="$is_weixin_browser">
    <title>预约</title>
<else/>
    <title>{pigcms{$now_category.appoint_name}预约_{pigcms{$config.site_name}</title>
</if>
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name='apple-touch-fullscreen' content='yes'/>
<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
<meta name="format-detection" content="telephone=no"/>
<meta name="format-detection" content="address=no"/>
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script src="{pigcms{$static_path}js/fakeLoader.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/fakeLoader.css">
 <div class="container">
    <div class="fakeloader"></div>
</div> 
<script>
            $(".fakeloader").fakeLoader({
                timeToHide:1200,
                bgColor:"#F87EA3",
                spinner:"spinner1"
            });
    </script>
<script type="text/javascript">
			var location_url = "{pigcms{:U('Appoint/ajaxList')}";
			var now_cat_url="<if condition="!empty($now_cat_url)">{pigcms{$now_cat_url}<else/>-1</if>";
			var now_area_url="<if condition="!empty($now_area_url) && $all_area_list">{pigcms{$now_area_url}<else/>-1</if>";
			var now_sort_id="<if condition="!empty($now_sort_array)">{pigcms{$now_sort_array.sort_id}<else/>defaults</if>";
			<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
		</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/swiper.min.css" type="text/css">
<link href="{pigcms{$static_path}css/appoint_wap.css" rel="stylesheet" />
<link href="{pigcms{$static_path}css/base.css" rel="stylesheet" />

<script src="{pigcms{$static_path}js/common_wap.js"></script>
<script src="{pigcms{$static_path}js/swiper.min.js"></script>
<script src="{pigcms{$static_path}js/rem.js"></script>
</head>

<body>


<header>
    <section class="banner">
        <div class="swiper-container s1 swiper-container-horizontal">
            <div class="swiper-wrapper" style="transition-duration: 0ms; -webkit-transition-duration: 0ms;  ">
            <volist name="header_adver" id="vo">
                <div class="swiper-slide  swiper-slide-active"> <a href="{pigcms{$vo['url']}"><img src="{pigcms{$vo['pic']}"></a> </div>
            </volist>
            </div>
            
            <div class="swiper-pagination p1 swiper-pagination-clickable"><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span><span class="swiper-pagination-bullet"></span></div>
        </div>
    </section>
    <script>
				var mySwiper = new Swiper('.s1',{
				    loop: false,
					autoplay: 3000,
						 pagination: '.p1',
				        paginationClickable: true
				  });
 
			</script> 
</header>
<article id="container">
    <div class="contenta clearfix">
    <volist name="category_list" id="vo" offset="0" length="1">
        <div class="content-left clearfix" style=" background:{pigcms{$vo['bg_color']}" onClick="category_list({pigcms{$vo['cat_id']})">
            <div class="content-l-img"> <a href="javascript:void(0)"><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$vo[cat_pic]}" /></a> </div>
            <div class="content-l-title"> <span>{pigcms{$vo['cat_name']}</span>
                <ul>
                    <volist name="vo['son_list']" id="val" offset="0" length="3">
                        <li>{pigcms{$val["cat_name"]}</li>
                    </volist>
                </ul>
            </div>
        </div>
      </volist>
        <div class="content-right">
            <ul>
             <volist name="category_list" id="vo" offset="1" length="2">
                <li class="content-right-li clearfix" style=" background:{pigcms{$vo['bg_color']}" onClick="category_list({pigcms{$vo['cat_id']})">
                    <div class="appoint-pic-r"><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$vo[cat_pic]}" /></div>
                    <div class="appoint-titie-r"> <span>{pigcms{$vo['cat_name']}</span>
                        <ul>
                             <volist name="vo['son_list']" id="val" offset="0" length="3">
                                <li>{pigcms{$val["cat_name"]}</li>
                            </volist>
                        </ul>
                    </div>
                </li>
               </volist> 
            </ul>
        </div>
        <if condition="$vo['son_list']">
        <div class="content-list">
            <ul>
            <volist name="category_list" id="vo" offset="3">
                <li class="content-right-li clearfix" style=" background:{pigcms{$vo['bg_color']}" onClick="category_list({pigcms{$vo['cat_id']})">
                    <div class="appoint-pic-r"><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$vo[cat_pic]}" /></div>
                    <div class="appoint-titie-r"> <span <if condition="!$vo['son_list']">style="border:none; line-height:3rem; font-size=16px"</if>>{pigcms{$vo['cat_name']}</span>
                        <ul>
                            <volist name="vo['son_list']" id="val" offset="0" limit="3">
                                <li>{pigcms{$val["cat_name"]}</li>
                            </volist>
                        </ul>
                    </div>
                </li>
            </volist>
            </ul>
        </div>
        </if>
    </div>
    <div class="clearfix"></div>
</article>


<script>
				var get_near_url = "/wap.php?g=Wap&c=Home&a=near_info";
				var group_index_sort_url = "/wap.php?g=Wap&c=Home&a=group_index_sort";
				var user_long = '0';
				var user_lat = '0';
			</script> 
<script src="{pigcms{$static_path}js/wap_index.js?1450230906"></script> 
<script type="text/javascript">
window.shareData = {  
            "moduleName":"Appoint",
            "moduleID":"0",
            "imgUrl": "", 
            "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Appoint/index')}",
            "tTitle": "预约列表_{pigcms{$config.site_name}",
            "tContent": ""
};
</script>
<link href="{pigcms{$static_path}css/footer.css" rel="stylesheet" />
<div style="height:10px"></div>

<footer class="footermenu">
    <ul class="clearfix">
		<if condition="!C('butt_open')">
			<li> <a href="{pigcms{:U('Home/index')}"> <em class="home"></em>
            <p>首页</p>
            </a> </li>
		<else/>
			<li> <a href="{pigcms{:C('butt_wap_url')}"> <em class="home"></em>
            <p>首页</p>
            </a> </li>
		</if>
			<li class="active"> <a href="{pigcms{:U('Appoint/index')}"> <em class="time "></em>
            <p>预约</p>
            </a> </li>
        <li> <a href="{pigcms{:U('My/appoint_order_list')}"> <em class="order"></em>
            <p>订单</p>
            </a> </li>
        <if condition="!C('butt_open')">
			<li>
				<a href="{pigcms{:U('My/index')}"> 
					<em class="user "></em>
				<p>我的</p>
				</a>
			</li>
		<else/>
			<li>
				<a href="{pigcms{:U('My/appoint_collect')}"> 
					<em class="user "></em>
				<p>收藏</p>
				</a>
			</li>
		</if>
    </ul>
</footer>
<script type="text/javascript">
function category_list(cat_id){
	var url="{pigcms{:U('Appoint/categoryList')}&cat_id="+cat_id;
	location.href=url;
}
</script>
</body>
</html>