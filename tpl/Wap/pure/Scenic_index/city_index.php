<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<meta http-equiv="Expires" content="-1">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta http-equiv="Pragma" content="no-cache">
		<meta charset="utf-8">
		<title>{pigcms{$scenic_select_city['area_name']}</title>
		<link href="{pigcms{$static_path}scenic/css/css_whir.css" rel="stylesheet"/>
		<script src="{pigcms{$static_path}scenic/js/jquery-1.8.3.min.js"></script>
		<script src="{pigcms{$static_path}scenic/js/swiper-3.3.1.jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}scenic/js/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		<script>
			var user_long = '0',user_lat  = '0';
		</script>
		<!--[if lte IE 9]>
		<script src="scripts/html5shiv.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<!-- 头部轮播 -->
		<div class="areaheader">
			<div class="swiper-container swiper-container1">
				<div class="swiper-wrapper">
					<volist name="city_advert" id="vo">
						<div class="swiper-slide"><a href="{pigcms{$vo.advert_url}"><img style="width:100%;height:230px;" src="{pigcms{$vo['advert_img']}"></a></div>
					</volist>
				</div>
				<div class="swiper-pagination"></div>
			</div>
		</div>
		<if condition="$weather">
			<div class="areaweather">
				<span class="wespan">今日天气</span>
				<span class="wespan2"> <span><img style="width:30px;height:30px;" src="{pigcms{$static_public}weather/heweather/blue/{pigcms{$weather.cond_code}.png"> {pigcms{$weather.cond_txt}</span> <span>{pigcms{$weather.wind_dir}{pigcms{$weather.wind_sc}级</span> <span>当前{pigcms{$weather.tmp}°C</span></span>
			</div>
		</if>
		<!-- 景点 -->
		<div class="Synav">
			<a href="{pigcms{:U('Scenic_list/index')}"><img src="{pigcms{$static_path}scenic/images/ckfjzjt_03.png"><span>{pigcms{$scenic_number}个景点</span></a>
			<a href="{pigcms{:U('Scenic_travel_plan/index')}"><img src="{pigcms{$static_path}scenic/images/ckfjzjt_05.png"><span>合理规划路线</span></a>
			<a href="{pigcms{:U('Scenic_list/new_mate')}"><img src="{pigcms{$static_path}scenic/images/ckfjzjt_06.png"><span>旅途不在孤单</span></a>
			<a href="{pigcms{:U('Scenic_aguide/index')}"><img src="{pigcms{$static_path}scenic/images/ckfjzjt_07.png"><span>一路相伴左右</span></a>
		</div>
		<!-- 景点   -->
		<!-- 景点列表 -->
		<section>
			<div class="Popular">
				<div class="top">
					<span>景点推荐</span>
					<a href="{pigcms{:U('Scenic_list/hot_index')}">查看全部 &gt</a>
				</div>
			</div>
			<div class="swiper-container swiper-container2">
				<div class="swiper-wrapper">
					<volist name="hot_list" id="vo">
						<div class="swiper-slide" >
							<a href="{pigcms{$vo['url']}">
								<img style="width:100%;height:100px;" src="{pigcms{$vo['pic']}">
								<div class="Popular_bom">
									<p><if condition="$vo['scenic_title']">{pigcms{$vo.scenic_title}<else/>&nbsp;&nbsp;</if></p>
									<div class="clr">
										<span class="repair fl"><if condition="$vo['money']"><em>{pigcms{$config['Currency_symbol']}</em>{pigcms{$vo.money}<em style="color:#999;"> 起</em><else/><em style="font-size:16px;">免费</em></if></span>
										<if condition="$vo['score_mean'] eq '0.0'"><else/><span class="repair_s fr">{pigcms{$vo.score_mean}分</span></if>
									</div>
								</div>
							</a>
						</div>
					</volist>
				</div>
			</div>
		</section>
		<!-- 景点列表 -->
		<!--景区2.0添加 html-->
		<section class="Recommendlive" id="one_recommended"></section>
	<script>
		if(user_long == '0'){
			getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
		}else{
			pageGetList(user_long,user_lat);
		}
		function pageGetList(type){
			$.ajax({
				type : "post",
				url : "{pigcms{:U('recommended')}",
				dataType : "json",
				data:{
					long	:	0,
					lat		:	0,
				},
				async:false,
				success : function(result){
					var one_recommended	=	'';
					var	ride_list	=	result.result;
					if(ride_list){
						var	ride_list_length	=	ride_list.length;
						if(ride_list_length){
							for(var x=0;x<ride_list_length;x++){
								var group_list	=	ride_list[x].list;
								if(!group_list){
									continue;
								}
								one_recommended	+='<div class="Popular">';
								one_recommended	+='	<div class="top" style="border-left:#'+ride_list[x].color+' 5px solid;">';
								one_recommended	+='		<span>'+ride_list[x].cat_name+'推荐</span>';
	//							one_recommended	+='		<a href="'+group_list[0].detail_url+'">查看全部 &gt</a>';
								one_recommended	+='	</div>';
								one_recommended	+='</div>';
								one_recommended	+='<div class="swiper-container swiper-container3">';
								one_recommended	+='	<div class="swiper-wrapper">';
								for(var y=0;y<group_list.length;y++){
									one_recommended	+='		<div class="swiper-slide">';
									one_recommended	+='			<a href="'+group_list[y].url+'">';
									one_recommended	+='				<div class="swiper-slide_top">';
									one_recommended	+='					<h2>'+group_list[y].com_name+'</h2>';
									one_recommended	+='					<div class="swiper-slide_top_n clr">';
									one_recommended	+='						<span class="fl">{pigcms{$config['Currency_symbol']}<em>'+group_list[y].price+'</em> 起</span>';
	//								one_recommended	+='						<i class="fr">已售'+group_list[y].sale_count+'</i>';
									one_recommended	+='					</div>';
									one_recommended	+='				</div>';
									one_recommended	+='				<div class="swiper-slide_end">';
									one_recommended	+='				<img src="{pigcms{:file_domain()}'+group_list[y].com_img+'">';
									one_recommended	+='				</div>';
									//if(group_list[y].juli){
	//									one_recommended	+='				<div class="swiper-slide_bot">';
	//									one_recommended	+='					<span>距离你1212</span>';
	//									one_recommended	+='				</div>';
	//								}
									one_recommended	+='			</a>';
									one_recommended	+='		</div>';
								}
								one_recommended	+='	</div>';
								one_recommended	+='</div>';
								$('#one_recommended').html(one_recommended);
							}
						}
					}
				var myswiper = new Swiper('.swiper-container1', {
					pagination: '.swiper-pagination',
					direction : 'horizontal',
					paginationClickable :true,
					autoplay :'4000',
					autoplayDisableOnInteraction : false,
					loop: true
				});
				var myswiper1 = new Swiper('.swiper-container2', {
					direction : 'horizontal',
					freeMode:true,
					slidesPerView: 'auto'
				});
				var myswiper2 = new Swiper('.swiper-container3', {
					direction : 'horizontal',
					freeMode:true,
					slidesPerView: 'auto'
				});
				$(".swiper-container1 img").each(function(){
					$(this).height($(this).width()*0.56)
				})

				$(".swiper-container2 img").each(function(){
					$(this).height($(this).width()*0.8)
				})
				$(".swiper-container3 img").each(function(){
					$(this).height($(this).width()*0.9)
				})

				$(".swiper-container3 .swiper-wrapper .swiper-slide:first-child").css("margin-left","3%")
				
			},
			error:function(){
			}
		})
	}
window.shareData={
	"moduleName":"Scenic",
	"moduleID":"0",
	"imgUrl": "<if condition="$city_advert[0].advert_img">{pigcms{$city_advert[0].advert_img}<else/>{pigcms{$config['wechat_share_img']}</if>",
	"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Scenic_index/city_index')}",
	"tTitle": "景区-{pigcms{$scenic_select_city['area_name']}",
	"tContent": "{pigcms{$scenic_select_city['area_name']}景点推荐"
	};
	</script>
	{pigcms{$shareScript}

	<if condition="$is_app_browser">
	<script type="text/javascript">
		
		if("{pigcms{$app_browser_type}" == 'android'){
				window.lifepasslogin.shareLifePass("{pigcms{$config['site_name']}景区服务，诚邀您的光临","{pigcms{$scenic_list['addr']}","<if condition="$city_advert[0].advert_img">{pigcms{$city_advert[0].advert_img}<else/>{pigcms{$config['wechat_share_img']}</if>","{pigcms{$config.site_url}{pigcms{:U('Scenic_index/city_index')}");
			} else {
				iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
			}
	</script>
	</if>
<include file="Public/scenic_footer"/>