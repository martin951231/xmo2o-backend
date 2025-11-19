<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"> 
	<title>文章资讯栏目首页-{pigcms{$config.site_name}</title>
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/> 
	<meta name="format-detection" content="address=no"/>   
	<meta name="keywords" content="本地资讯栏目关键词,关键词,关键词,关键词,关键词,关键词,关键词,关键词">
	<meta name="description" content="本地资讯栏目介绍">
	<link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}portal/css/news-mb.css">
	<!-- <link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}portal/css/news-scroll5.css"> -->
	<style>
		#wrapper2 { height:41px;}
		.slide_tabs{position:relative; overflow:hidden; background-color:#fafafa;}
		.slide_tabs ul{}
		.slide_tabs li{max-width:4em; padding:0 10px; height:40px; line-height:40px;border-bottom:1px solid #eee; overflow:hidden; float:left;text-align:center;overflow:hidden;}
		.slide_tabs li.current{border-bottom:1px solid #06c1ae; color:#06c1ae;}
		.slide_tabs li.current a { color:#06c1ae;}
		.slide_tabs_wrap .more{right:0;background:url('{pigcms{$static_path}portal/images/nav2015BG.png') repeat-y 0 0;position:absolute; z-index:1;top:0;width:50px;height:40px;}
		.slide_tabs_wrap .more span,.slide_tabs_wrap .more span:after { position:absolute; top:16px; left:26px; display:inline-block; border-color:#adadad transparent transparent transparent; border-width:8px; border-style:solid; transition:transform .3s ease; -webkit-transition:-webkit-transform .3s ease; transform-origin:50% 25% 0; -webkit-transform-origin:50% 25% 0;}
		.slide_tabs_wrap .more span:after { position:absolute; top:-10px; left:-8px; content:' '; border-color:#fafafa transparent transparent transparent;}
		.open .more span { transform:rotate(180deg); -webkit-transform:rotate(180deg);}
		#scroller2 {-webkit-tap-highlight-color: rgba(0,0,0,0);	width: 100%;-webkit-transform: translateZ(0);-moz-transform: translateZ(0);	-ms-transform: translateZ(0);-o-transform: translateZ(0);transform: translateZ(0);-webkit-touch-callout: none;-webkit-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;-webkit-text-size-adjust: none;-moz-text-size-adjust: none;-ms-text-size-adjust: none;-o-text-size-adjust: none;text-size-adjust: none;}
		.slide_tabs_wrap { position:relative; z-index:3;}
		.slide_tabs_wrap .node2 { display:none; position:absolute; left:0; top:0; right:0; background-color:#fafafa; box-shadow:0 2px 5px rgba(0,0,0,.2);}
		.open .node2 { display:block;}
		.slide_tabs_wrap .node2 .hd { border-bottom:1px solid #fff;}
		.slide_tabs_wrap .node2 .hd .tit { display:inline-block; padding:7px 3px; color:#fb9031; border-bottom:1px solid #fb9031;}
		.slide_tabs_wrap .node2 ul { padding:10px 0;}
		.slide_tabs_wrap .node2 li { float:left; width:25%; padding:0 5px; -webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box; margin:5px 0;}
		.slide_tabs_wrap .node2 li a { display:inline-block; vertical-align:top; border:1px solid #ddd; border-radius:15px; font-size:12px; line-height:30px; height:30px; overflow:hidden; padding:0 10px; width:4em; text-align:center;}
		.slide_tabs_wrap .node2 li.current a { color:#06c1ae;border:1px solid #06c1ae;}
	</style> 
	<link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}portal/css/mb-base.css">
	<link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}portal/css/mb-index.css">
	<link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}portal/css/mb-common.css">
    <link rel="stylesheet" rev="stylesheet" href="{pigcms{$static_path}css/swiper.min.css"> 
	<script src="{pigcms{$static_path}portal/js/jquery-2.1.1.min.js"></script>
    <script src="{pigcms{$static_path}js/swiper.min.js"></script>
	<!--必须在现有的script外-->
    <style>
         #pageMain{
            padding-bottom:60px;
        }
        .slide_img{ position: relative;}
        .slide_img .swiper-slide{ position: relative; text-align: center;}
        .slide_img .swiper-slide .title{
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 26px;
            line-height: 26px; 
            color: #fff;
            padding: 0 10px;
            box-sizing: border-box; 
            background: url('{pigcms{$static_path}portal/images/slide_tit_bg.png') repeat-x 0 0;
            background-size: auto 26px;
            z-index: 9;
            text-align: left;
        } 
        .swiper-pagination{
            text-align: right;   
        }
        .swiper-container-horizontal>.swiper-pagination{
            bottom: 2px;
        }
        .swiper-pagination-bullet-active{
            background:#fb9031
        } 
        
        #wrapper{
          overflow:scroll;
          -webkit-overflow-scrolling:touch;  
        }
    </style> 
</head>
<body style="min-height: 640px;" class="<if condition="$is_wxapp_browser">like_app</if>" id="box">
	<div id="pageMain">
		<div class="header">
            <div class="title_msg">
                <a href="javascript:void(0);" id="return_index" onclick="return window.history.go(-1);" class="back">返回</a>
                <a href="{pigcms{:U('Wap/My/index')}" class="my <if condition="$user_session['uid']">ico_ok</if>" id="login_ico" style="display: none;">我的</a>
                <div class="type" id="nav_ico">导航</div>
                <span id="ipageTitle" style="">本地资讯</span>
                <include file="Portal:top_nav"/>
            </div>
		</div> 

		<div class="nav_index_bottom" style="overflow:visible;">
			<ul>
				<li class="current">
					<a href="{pigcms{:U('Portal/index')}">
						<span class="home"></span>
						首页
					</a>
				</li>
				<li>
					<a href="{pigcms{:U('Wap/My/index')}">
						<span class="mine"></span>
						我的
					</a>
				</li>
			</ul>
		</div>
		<div class="content news_index">
			<div class="slide_tabs_wrap">
				<div class="slide_tabs" id="wrapper2">
					<ul id="scroller2" style="width: 990px; transition-timing-function: cubic-bezier(0.1, 0.57, 0.1, 1); transition-duration: 0ms; transform: translate(0px, 0px) translateZ(0px);">

						<li class="<if condition="$_GET['cid'] eq ''">current</if> item">
							<a href="{pigcms{:U('Portal/article')}">全部分类</a>
						</li>

						<volist name="cate_list" id="vo">
							<li class="<if condition="$_GET['cid'] eq $vo['cid']">current</if> item">
								<a href="{pigcms{:U('Portal/article',array('cid'=>$vo['cid']))}">{pigcms{$vo.cat_name}</a>
							</li>
						</volist>
					</ul>
				</div>
				<div class="node2">
					<div class="hd">
						<span class="tit">全部分类</span>
					</div>
					<ul id="cloneNav" class="clearfix"> 
					</ul>
				</div>
				<div class="more" id="iscrollto">
					<span></span>
				</div>
			</div> 
            
			<div id="wrapper"  style="top:86px;bottom:51px;"> 
                <div class="swiper-container slide_img">
                  <div class="swiper-wrapper">
                      <volist name="hot_img_news" id="vo">
                          <div class="swiper-slide">
                              <a href="{pigcms{:U('Portal/article_detail',array('aid'=>$vo['aid']))}">
                                  <img src="{pigcms{$vo.thumb}" style=" height: 270px;" alt=""></a>
                              <span class="title">{pigcms{$vo.title}</span>
                          </div>
                      </volist>
                  </div>
                  <div class="swiper-pagination"></div> 
                </div>
                <script>
                var mySwiper = new Swiper('.swiper-container', {
					autoplay : 3000,
					pagination : '.swiper-pagination'
                })
                    $('#iscrollto').click(function(){
                        $('.node2').toggle();
                    });
                </script>  
               
				<div id="scroller" style="transition-timing-function: cubic-bezier(0.1, 0.57, 0.1, 1); transition-duration: 0ms; transform: translate(0px, 0px) translateZ(0px);"> 
					<ul id="innerrow" class="list_normal list_news">
						<volist name="article_list" id="vo">
							<li class="haspic1" onclick="location.href='{pigcms{:U('Portal/article_detail',array('aid'=>$vo['aid']))}'">
								<a href="javascript:;" class="link">
									<p class="img">
										<if condition="$vo['thumb']">
									   		<img src="{pigcms{$vo.thumb}">
									   	<else/>
									   		<img src="{pigcms{$static_path}portal/images/livelistnopic.gif">
									   	</if>
									</p>
									<p class="tit">{pigcms{$vo.title}</p>
									<p class="txt clearfix">
										<span class="left">{pigcms{$vo.dateline|date="m-d H:i",###}</span>
										<span class="right">人气：{pigcms{$vo.PV}</span>
									</p>
								</a>
							</li>
						</volist>
					</ul>
					<ul id="over_list" class="list_normal list_news"></ul> 
				</div> 
			</div>
		</div>
	</div>
<script src="{pigcms{$static_path}portal/js/wap_common.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}portal/js/jquery.cookie.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}portal/js/purl.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}portal/js/mustache.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}portal/js/iscroll2.js"></script>
<script src="{pigcms{$static_path}portal/js/wap_common_2015.js?t=03"></script>
<!-- <script type="text/javascript" src="{pigcms{$static_path}portal/js/iscroll.js"></script> -->
<input type="hidden" id="cid" value="{pigcms{$_GET['cid']}">
<script>

// document.body.addEventListener('touchmove', function (e) {
//   e.preventDefault();
// }, {passive: false});
//  
var myscroll; 
function loaded(){ 
   myscroll=new iScroll("wrapper",{
       vScroll:true,
       scrollbars: true,
        mouseWheel: false,
        interactiveScrollbars: true,
        shrinkScrollbars: 'scale',
        fadeScrollbars: true,
        scrollY: true,
        probeType: 1,
        bindToWrapper: true,
        click: true,
        taps:true,
        disablePointer: true,disableTouch:false,disableMouse:fasle
         
   }); 
 }         
var siteUrl = ''; 
(function($){
	var w_w = $(window).width();
    $("#slide").css('width',$(window).width());
    $(".cell").css('width',$(window).width()); 
    
	$('#foot_link').hide();
	var list = $('#content').find('.cell');
	if(list.length > 0){
		var txt = '';
		list.each(function(i){
			if(i === 0){
				txt += '<li class="active">1</li>';
			}else{
				txt += '<li>'+(i+1)+'</li>';
			}
		});
		$('#slide').show();
		$('#indicator').html(txt);
		// window['myScroll1'] = new C_Scroll({container:'slide',mouseWheel: true,content:'content',ct:'indicator',size:w_w,intervalTime:5000,lazyIMG:!!0});
	}
	var star_nav = $('#scroller2');
	star_nav.css('width',(90*star_nav.find('li').length)+'px');   
	$('#cloneNav').html(star_nav.html());  
    // var myScroll2 = new IScroll('#wrapper2', {
    //     scrollX: true,
    //     scrollY: false,
    //     click:true,
    //     keyBindings: true
    //  }); 
    
})(jQuery);

var page = 1,morePage=true,isLoad=false;
$(window).scroll(function (e) {
    e.stopPropagation();
    e.preventDefault();
	var scrollTop = $(this).scrollTop();
	var scrollHeight = $(document).height();
	var windowHeight = $(this).height();
	console.log(windowHeight);
	if (scrollTop + windowHeight > scrollHeight - 80) {
	    console.log(page);
        loadings();
	}
});
$(document).ready(function(){
    var width= document.documentElement.clientWidth;
    var heiht= document.documentElement.clientHeight;//屏幕高度
    var box = document.getElementById("box")
    var contentHeight = window.getComputedStyle(box).height; //内容高度
    var contentHeights = contentHeight.substr(0, contentHeight.indexOf("px"));
    if(contentHeights<heiht){
        loadings();
    }
})
function loadings() {
    if(!morePage || isLoad){
        return;
    }
    isLoad = true;
    var cid = $("#cid").val();
    var articleListUrl = "{pigcms{:U('Portal/ajax_article_list')}";
    $.post(articleListUrl,{'page':page,'cid':cid},function(data){
        isLoad = false;
        if(data.error == 1){
            page = page+1;
            $("#innerrow").append(data.html);
            if(!data.html){
                morePage = false;
            }
        }else{
            if(!data.html){
                morePage = false;
            }
            $("#over_list").html('<li class="noMore">没有更多了</li>');
        }
    },'json');
}
var searchHtml = '<div class="searchbar2">'+
	'<form id="myform" action="'+"{pigcms{:U('Portal/search_list')}"+'" method="get">'+
		'<input type="hidden" name="g" value="Wap" />'+
		'<input type="hidden" name="c" value="Portal" />'+
		'<input type="hidden" name="a" value="search_list" />'+
		'<input type="text" name="v" id="meSleKey" class="s_ipt" value="" placeholder="输入关键字" />'+
		'<input type="submit" class="s_btn po_ab" value="搜索">'+
	'</form></div>';

function newPageSearch(){ 
} 
</script>
{pigcms{$shareScript}
<script type="text/javascript">
	window.shareData = {
		"moduleName":"Home",
		"moduleID":"0",
		"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Portal/article')}",
		"tTitle": "文章资讯栏目首页",
		"tContent": "文章资讯栏目首页-{pigcms{$config.site_name}"
	};
</script>
</body>
</html>