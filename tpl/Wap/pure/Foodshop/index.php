<!DOCTYPE html>
<html> 
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>{pigcms{$config.meal_alias_name}列表</title>
	<link href="{pigcms{$static_path}css/groupBuy_index.css" rel="stylesheet"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}foodshop/css/foodshop_index.css"/>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script>var noAnimate = true;</script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
<script type="text/javascript">
	var location_url = "{pigcms{:U('Foodshop/ajaxList')}";
	var now_cat_url="<if condition="!empty($now_cat_url)">{pigcms{$now_cat_url}<else/>-1</if>";
	var now_area_url="<if condition="!empty($now_area_url) && $all_area_list">{pigcms{$now_area_url}<else/>-1</if>";
	var now_sort_id="<if condition="!empty($now_sort_array)">{pigcms{$now_sort_array.sort_id}<else/>defaults</if>";
	var now_queue="<if condition="!empty($now_queue)">{pigcms{$now_queue}<else/>-1</if>";
    var public_path ="{pigcms{$static_path}";
    var day = "{pigcms{$day}";
	<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/dropdown.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/foodshoplist.js" charset="utf-8"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
<style>
    .hasManyCity{
        height: 30px;
    }
    .hasManyCity #searchBox{
        margin-top: 0;
    }
    #searchBox{
        margin: 0 auto;
		width:100%
    }
    .hasManyCity #searchBox .icon-search{
        position: initial;
    }
    .navBox_list dt {
    	padding: 0 10px 0 15px;
	}

	.Navigation .head li{
		width: 20%;
	}
	.Navigation .head .head_upper img{
		width: 32px;
		height: 32px;
	}
	.Navigation .head .head_upper span{
		font-size: 13px;
	}
	.Navigation .head .head_upper{
		margin-bottom: 0px;
	}
</style>

</head>
<body>
	<if condition="$wap_foodshop_index_top && empty($_GET['cat_url'])">
	<section class="homepage" id="listBanner">
		<div class="swiper-container swiper-container-horizontal">
			<div class="swiper-wrapper">
				<volist name="wap_foodshop_index_top" id="banner">
				<div class="swiper-slide">
					<a href="{pigcms{$banner['url']}">
						<img src="{pigcms{$banner['pic']}" width="100%" height="100%">
					</a>
				</div>
				</volist>
			</div> 
			<div class="swiper-pagination"></div>
		</div>
		<a href="{pigcms{:U('Foodshop/search')}" class="Cable">
			<div><i></i>请输入店铺名称</div>
		</a>
	</section>
	<elseif condition="empty($_GET['cat_url'])" />
	<header class="hasManyCity" <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if>>
		<div id="searchBox">
			<a href="{pigcms{:U('Foodshop/search')}">
				<i class="icon-search"></i>
				<span>请输入店铺名称</span>
			</a>
		</div>
	</header>
	</if>
	<if condition="empty($_GET['cat_url'])">
    <script type="text/javascript">
      var myswiper = new Swiper('.swiper-container', {
              pagination: '.swiper-container .swiper-pagination',
              direction : 'horizontal',
              paginationClickable :true,
              autoplay :'5000',
              autoplayDisableOnInteraction : false,
              loop: true 
            });
      
      $(".swiper-wrapper .swiper-slide").each(function(){
        $(this).height($(this).width()*0.375)
      })
    </script>
	</if>
	<if condition="$wap_foodshop_slider  && empty($_GET['cat_url'])">
	<header class="Navigation" style="position: static;">
		<div class="head">
			<ul class="clr">
				<volist name="wap_foodshop_slider" id="slider">
					<li>
						<a href="{pigcms{$slider['url']}">
							<span class="head_upper"><img src="{pigcms{$slider['pic']}"></span>
							<span class="head_lower">{pigcms{$slider['name']}</span>
						</a>
					</li>
				</volist>
			</ul>
		</div>
	</header>
	</if>
	<div style="margin-top: 10px;"></div>
<!--	<div>餐饮首页广告图</div>-->
	<!--5-->
	<php> if($config['meal_main_page_show_ad']==1 && $slider_list){</php>
	<if condition="abs($config['meal_main_page_center_type']) eq 5">
		<section class="cityRun">
			<div class="cityItem clear <if condition="$config.meal_main_page_show_type eq 0">acss</if>">
	<div class="item0 clear ft link-url" data-url="{pigcms{$slider_list[0]['url']}">
		<if condition="$config.meal_main_page_show_type eq 1">
			<div class="itemLeft ft fire" style='width:100%'>
				<h3>{pigcms{$slider_list[0]['name']}</h3>
				<h4 class="lan">{pigcms{$slider_list[0]['sub_name']} </h4>

			</div>
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width:100%;height:100%'/>
			</div>
			<else />
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width:100%;height:100%'/>
			</div>

		</if>
	</div>
	<div class="item0 ft link-url" data-url="{pigcms{$slider_list[1]['url']}">
		<if condition="$config.meal_main_page_show_type eq 1">
			<div class="itemLeft ft fire" style='width:100%'>
				<h3>{pigcms{$slider_list[1]['name']}</h3>
				<h4 class="lan">{pigcms{$slider_list[1]['sub_name']} </h4>

			</div>
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}" style='width:100%;height:100%'/>
			</div>
			<else />
			<div class="itemRight ft fire" style='width:100%'>
				<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}" style='width:100%;height:100%'/>
			</div>

		</if>
	</div>
	</div>
	<div class="cityTheck" >
		<ul class="clear" style='display:flex;'>
			<li class="ft link-url" data-url="{pigcms{$slider_list[2]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.meal_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[2]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[2]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four ' style='width:100%'/ >
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four 'style='width:100%'/>
				</if>
			</li>
			<li class="ft link-url" data-url="{pigcms{$slider_list[3]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.meal_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[3]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[3]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four' style='width:100%'/>
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four' style='width:100%'/>
				</if>
			</li>
			<li class="ft link-url" data-url="{pigcms{$slider_list[4]['url']}" style='padding:0px;flex:1'>
				<if condition="$config.meal_main_page_show_type eq 1">
					<h3>{pigcms{$slider_list[4]['name']}</h3>
					<h4 class="c1">{pigcms{$slider_list[4]['sub_name']}</h4>
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[4]['pic']}" class='four' style='width:100%'/>
					<else />
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[4]['pic']}" class='four' style='width:100%'/>
				</if>
			</li>
		</ul>
	</div>
	</section>

	<elseif condition="abs($config['meal_main_page_center_type']) eq 1" />
	<!--1-->
	<section class="run clear  <if condition="$config.meal_main_page_show_type eq 0">adds</if>">
	<div class="ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style="width:100%" >
		<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" class="slideimg1" style='flex:1;width: 100%;'/>
	</div>
	</section>

	<elseif condition="abs($config['meal_main_page_center_type']) eq 2" />
	<!--2-->
	<section class="run clear <if condition="$config.meal_main_page_show_type eq 0">adds</if>">
	<div class="runItem ft add ">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[0]['url']}">
				<li class="ft shree">
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
				</li>
			</ul>
		</div>
	</div>
	<div class="runItem ft">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<li class="ft shree">
					<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
				</li>
			</ul>
		</div>
	</div>
	</section>

	<elseif condition="abs($config['meal_main_page_center_type']) eq 3" />
	<!--3-->
	<section class="run clear <if condition="$config.meal_main_page_show_type eq 0">adds</if>" style='display:flex;'>
	<div class="runItem ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style='padding-top:0px;display:flex;flex-direction: column'>
		<if condition="$config.meal_main_page_show_type eq 1">
			<h3 >{pigcms{$slider_list[0]['name']}</h3>
			<h4>{pigcms{$slider_list[0]['sub_name']} </h4>
			<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='width: 100%; flex:1;max-height:200px'/>
			<else />
			<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" style='flex:1;width: 100%;height:190px;'/>
		</if>
	</div>
	<div class="runItem ft" style='display: flex; flex-direction: column;'>
		<div class="itemTop" style='flex:1;'>
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft">
						<h3 class='hiddens'>{pigcms{$slider_list[1]['name']}</h3>
						<h4>{pigcms{$slider_list[1]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
		<div class="itemBottom" style='flex:1'>
			<ul class="clear link-url" data-url="{pigcms{$slider_list[2]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft">
						<h3>{pigcms{$slider_list[2]['name']}</h3>
						<h4>{pigcms{$slider_list[2]['sub_name']} </h4>

					</li>
					<li class="ft">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
					<else />
					<li class="ft">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>
	</section>
	<elseif condition="abs($config['meal_main_page_center_type']) eq 4" />
	<!--同城跑腿3-->
	<section class="run clear <if condition="$config.meal_main_page_show_type eq 0">adds</if>">
	<div class="runItem ft add ">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[0]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft shree">
						<h3 >{pigcms{$slider_list[0]['name']}</h3>
						<h4>{pigcms{$slider_list[0]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}"/>
					</li>
				</if>

			</ul>
		</div>
		<div class="itemBottom">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[1]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[0]['name']}</h3>
						<h4>{pigcms{$slider_list[0]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[1]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>

	<div class="runItem ft">
		<div class="itemTop">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[2]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[2]['name']}</h3>
						<h4>{pigcms{$slider_list[2]['sub_name']} </h4>
					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
		<div class="itemBottom">
			<ul class="clear link-url" data-url="{pigcms{$slider_list[3]['url']}">
				<if condition="$config.meal_main_page_show_type eq 1">
					<li class="ft shree">
						<h3>{pigcms{$slider_list[3]['name']}</h3>
						<h4>{pigcms{$slider_list[3]['sub_name']} </h4>

					</li>
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}"/>
					</li>
					<else />
					<li class="ft shree">
						<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}"/>
					</li>
				</if>
			</ul>
		</div>
	</div>
	</section>
	</if>
	<php>}</php>
	<div style="margin-top: 10px;"></div>
	<section class="run clear adds">
		<volist name="adverMiddleImg" id="middle">
			<div class="ft acc link-url" data-url="{pigcms{$middle['url']}" style="width:100%" >

				<img src="{pigcms{$middle['pic']}" class="slideimg1" style='flex:1;width: 100%;'/>
			</div>
		</volist>
	</section>
	<if condition="$config['open_meal_activity'] && $foodshop_center_img && !$_GET['cat_url']">
		<section class="run clear" style="padding: 10px 10px;">
			<div class="swiper-container slider-center">
				<div class="swiper-wrapper">
					<volist name="foodshop_center_img" id="middle">
						<div class="swiper-slide link-url" data-url="{pigcms{$middle['url']}"><img src="{pigcms{$middle['pic']}"></div>
					</volist>
				</div>
		        <!-- Add Pagination -->
		        <div class="swiper-pagination"></div>
			</div>
		</section>
	</if>
	<!-- <div style="margin-top: 10px;"></div> -->
	<section class="navBox" >
		<section class="navBox pageSliderHide">
			<ul>
				<li class="dropdown-toggle caret category" data-nav="category"><span class="nav-head-name"><if condition="$now_category">{pigcms{$now_category.cat_name}<else/>全部分类</if></span></li>
				<li class="dropdown-toggle caret biz subway" data-nav="biz">
					<span class="nav-head-name"><if condition="$now_area">{pigcms{$now_area.area_name}<else/>全城</if></span>
				</li>
				<li class="dropdown-toggle caret sort" data-nav="sort">
					<span class="nav-head-name">{pigcms{$other}</span>
				</li>
			</ul>
			<div class="dropdown-wrapper">
				<div class="dropdown-module" style="position: unset;">
					<div class="scroller-wrapper">
						<div id="dropdown_scroller" class="dropdown-scroller" style="overflow:hidden;">
							<div>
								<ul>
									<li class="category-wrapper">
										<ul class="dropdown-list">
											<li data-category-id="all" <if condition="$now_category_url eq 'all'">class="active"</if> onclick="list_location($(this));return false;"><span data-name="全部分类">全部分类</span></li>
											<volist name="all_category_list" id="category">
												<li data-category-id="{pigcms{$category['cat_url']}" <if condition="$category['cat_count'] gt 0">data-has-sub="true"<else/>onclick="list_location($(this));return false;"</if> class="<if condition="$category['cat_count'] gt 0">right-arrow-point-right</if> <if condition="$now_category_url eq $category['area_url']">active</if>">
												
												<span data-name="{pigcms{$category['cat_name']}">{pigcms{$category['cat_name']}</span>
												<if condition="$category['cat_count'] gt 0"><span class="quantity"><b></b></span></if>
													<div class="sub_cat hide" style="display:none;">
														<if condition="$category['cat_count'] gt 0">
															<ul class="dropdown-list sub-list">
																<li data-category-id="{pigcms{$category.cat_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$category.cat_name}">全部</span></div></li>
																<volist name="category['category_list']" id="voo" key="j">
																	<li data-category-id="{pigcms{$voo.cat_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$voo.cat_name}">{pigcms{$voo.cat_name}</span></div></li>
																</volist>
															</ul>
														</if>
													</div>
												</li>
											</volist>
										</ul>
									</li>
									<if condition="$all_area_list">
										<li class="biz-wrapper">
											<ul class="dropdown-list">
												<li data-area-id="-1" <if condition="empty($now_area_url)">class="active"</if> onclick="list_location($(this));return false;"><span data-name="全城">全城</span></li>
												<volist name="all_area_list" id="vo">
													<li data-area-id="{pigcms{$vo.area_url}" <if condition="$vo['area_count'] gt 0">data-has-sub="true"<else/>onclick="list_location($(this));return false;"</if> class="<if condition="$vo['area_count'] gt 0">right-arrow-point-right</if> <if condition="$top_area['area_url'] eq $vo['area_url']">active</if>">
														<span>{pigcms{$vo.area_name}</span>
														<if condition="$vo['area_count'] gt 0"><span class="quantity"><b></b></span></if>
														<div class="sub_cat hide" style="display:none;">
															<if condition="$vo['area_count'] gt 0">
																<ul class="dropdown-list sub-list">
																	<li data-area-id="{pigcms{$vo.area_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$vo.area_name}">全部</span></div></li>
																	<volist name="vo['area_list']" id="voo" key="j">
																		<li data-area-id="{pigcms{$voo.area_url}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{pigcms{$voo.area_name}">{pigcms{$voo.area_name}</span></div></li>
																	</volist>
																</ul>
															</if>
														</div>
													</li>
												</volist>
											</ul>
										</li>
									</if>
									<li class="sort-wrapper">
										<div class="screenxl">
											<h2>排序</h2>
											<ul class="clr">
												<volist name="sort_array" id="vo">
													<li class="fl <if condition="$vo['sort_id'] eq $now_sort_array['sort_id']">on</if>" data-sort-id="{pigcms{$vo.sort_id}"><a href="javascript:void(0);"  data-name="{pigcms{$vo.sort_value}">{pigcms{$vo.sort_value}</a></li>
												</volist>
											</ul>
										</div>
										<div class="screenxl">
											<h2>排号</h2>
											<ul class="clr">
												<li class="fl on" data-queueid="-1"><a href="javascript:void(0);" >不限</a></li>
												<li class="fl" data-queueid="1"><a href="javascript:void(0);" >可排号</a></li>
												<li class="fl" data-queueid="0"><a href="javascript:void(0);">无排号</a></li>
											</ul>
										</div>
										<a href="javascript:void(0)" class="screenwc">完成</a>
									</li>
                  
                  
									<!--li class="sort-wrapper">
										<ul class="dropdown-list">
											<volist name="sort_array" id="vo">
												<li data-sort-id="{pigcms{$vo.sort_id}" <if condition="$vo['sort_id'] eq $now_sort_array['sort_id']">class="active"</if> onclick="list_location($(this));return false;"><span data-name="{pigcms{$vo.sort_value}">{pigcms{$vo.sort_value}</span></li>
											</volist>
										</ul>
									</li>
									
									<li class="category-wrapper">
										<ul class="dropdown-list">
											<li data-category-id="-1" class="active" onclick="list_location($(this));return false;"><span data-name="全部">全部</span></li>
											<li data-category-id="1"  onclick="list_location($(this));return false;"><span data-name="可排号">可排号</span></li>
											<li data-category-id="0"  onclick="list_location($(this));return false;"><span data-name="无排号">无排号</span></li>                 
										</ul>
									</li-->
								</ul>
							</div>
						</div>
						<div id="dropdown_sub_scroller" class="dropdown-sub-scroller"><div></div></div>
					</div>
				</div>
			</div>
			<section class="storeListBox listBox"><div class="shade"></div></section>
		</section>
		<div class="he45"></div>
        <if condition="$config['is_open_merchant_foodshop_discount'] eq 1">
            <div class="mui-card-content date">
                <span class="prevday" data-prev_day="" style="font-size: 14px;float: left;margin-left:10px;width:20%;">前一天</span>
                    <span id="choose_date" style="margin-left: 7%;">
                        <sub></sub>
                        <span class="startdate"></span>
                        <i class="startweek"></i>
                    </span>
                <span class="nextday" date-next_day="" style="font-size: 14px;float: right;margin-right:10px;width:20%;text-align: right;">后一天</span>
            </div>

            <div id="J_Calendar" class="calendar" style="display:none;">
                <div class="mui-bar mui-bar-nav">
                    <a id="close_yui">关闭</a>
                    <h1 class="mui-title">日期选择</h1>
                </div>
                <ul class="calendar-title bar">
                    <li>周日</li>
                    <li>周一</li>
                    <li>周二</li>
                    <li>周三</li>
                    <li>周四</li>
                    <li>周五</li>
                    <li>周六</li>
                </ul>
            </div>
        </if>
		<div class="navBox_list" style="min-height:calc(100vh)">
		</div>
	</section>
    <if condition="$config['is_open_merchant_foodshop_discount'] eq 1">
        <script src="{pigcms{$static_path}js/mui.min.js"></script>
        <script src="https://cdn.staticfile.org/yui/3.18.1/yui/yui.js"></script>
        <script src="{pigcms{$static_path}foodshop/foodshop_index.js?3" type="text/javascript" charset="utf-8"></script>
    </if>
	
	
	<if condition="empty($_GET['cat_url'])">
	<div class="he50"></div>
	<footer class="footerMenu wap">
		<ul>
			<li>
				<a href="{pigcms{:U('Home/index')}"><em class="home"></em><p>首页</p></a>
			</li>
			<li>
				<a class="active"><em class="store"></em><p>{pigcms{$config.meal_alias_name}</p></a>
			</li>
			<li>
				<a href="{pigcms{:U('My/foodshop_order_list')}"><em class="group"></em><p>订单</p></a>
			</li>
			<li>
				<a href="{pigcms{:U('My/index')}"><em class="my"></em><p>我的</p></a>
			</li>
		</ul>
	</footer>
	</if>
</body>
<style>
.MenuPrice .tag{
	display: inline-block;
	margin-left: 3px;
	border: 1px solid #f58300;
	color: #f58300;
	padding: 1px 3px;
	border-radius: 2px;
	font-size: 12px;
	line-height: 12px;
}
.navBox_list .img_box{
	width:56px;
	height:56px;
	border-radius: 28px;
	float: left;
	overflow:auto;
	margin:10px 10px 1px 0;
}
.navBox_list .img_box img{
	width:100%;
	height:100%;
	display: block;
	object-fit:cover;
}
.navBox_list dl.on .img_box img{
	/*-webkit-filter: grayscale(100%);
    filter: grayscale(100%);*/
    opacity: 0.6;
}
.navBox_list dt .navLtop .fl{
	width: 65%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.star i.full {
    background-image: url(./tpl/Wap/pure/static/shop/images/full_star.png);
}
.star i {
    width: 12px;
    height: 12px;
    display: inline-block;
    background: url(./tpl/Wap/pure/static/shop/images/none_star.png) no-repeat;
    background-size: 100%;
    margin-right: 2px;
}
.star i.half {
    background-image: url(./tpl/Wap/pure/static/shop/images/mid_star.png);
}

.slider-center.swiper-container{margin: 0 ;height: 93px;border-radius: 5px;}
.slider-center img{height: 100%;width: 100%;}

.swiper-pagination-bullet {
	margin-left: 8px;
    background: #d8d5d1 !important;
}
.swiper-pagination-bullet-active {
    background: #fff !important;
    width: 14px;
    border-radius: 33%;
}
.swiper-pagination {
    bottom:2px !important;
}

/**flex**/
.rel{position: relative;}
.flex{display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box;display:flex;}
.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;box-pack: center;justify-content: space-between;}
.flex-justify-start{-webkit-box-pack: start;-webkit-justify-content: flex-start;-ms-flex-pack: start;box-pack: start;justify-content: flex-start;}
.flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; box-align: center;align-items: center;}
.flex_one{-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;}
.flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}
.flex-grow{-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex-grow: 1;-ms-flex: 1;flex-grow: 1;}

.activity-box .store-name{
	font-weight: bold;
    padding: 4px 0;
    font-size: 18px;
    margin-bottom: 4px
}
.activity-box .activity-description{
	color: #5D5D5D;
    margin-bottom: 8px
}
.activity-box .detail-info{
    margin-bottom: 8px
}
.activity-box .detail-info .star-num{
	color: #F19205;
}
.activity-box .activity-price{    
	color: #515151;
    border-left: 1px solid #C3C3C3;
    padding-left: 4px;
}
.activity-box .activity-name{
	margin-bottom: 8px;
}
.activity-box .activity-label{
 	margin-bottom: 10px;
}
.activity-box .activity-label span{    
	display: inline-block;
    color: #5D5D5D;
    border: 1px solid #B0B0B0;
    padding: 0px 4px;
    border-radius: 3px;   
    margin: 2px 0;
}
.activity-image-box {
	width: 100%;
}
.activity-image{    
	display: inline-block;
   	padding-right: 10px;
   	flex: 1;
}
.activity-image img{
	border-radius: 6px;
	width: 100%;
	height:100%;
}
.activity-image-box span:last-of-type{
   	padding-right: 0px !important;
}

.activity-box dt{
    padding: 10px !important;
}
.img_box_1{
	width: 85px;
	height: 60px;
	float: left;
    overflow: auto;
    margin: 15px 10px 1px 0;
}
.img_box_1 img{    
	border-radius: 4px;
}
.deliver_name{
	color: rgb(238, 63, 77);
    border: 0.5px solid rgb(238, 63, 77);
    font-size: 11px;
    padding: 2px 3px;
    border-radius: 2px;
    margin-top: -5px;
}
.deliver_name.platform{
	color: #fff;
	background: rgb(238, 63, 77);
}
.deliver-info{
	padding-left: 95px;
}
.deliver-info .delivery_price{
	color: #3C3C3C;
}
.deliver-info .delivery_money{
	color: #3C3C3C;
	margin-left: 20px;
}
.title-box{
	width: 36% !important;
	color: #3C3C3C;

}
.top-box span{
	display: inline-block;
}
.top-box .delivery_time{
	color: #797979;
}
.top-box .distance{
	color: #797979;
	margin-left: 10px;
}
.shop-box .navLtop{
	padding: 14px 0 6px 0 !important;
}
.shop-box dt .navLBt {
    /*padding-bottom: 6px !important;*/
}
.shop-box .star i.full {
    background-image: url(./tpl/Wap/pure/static/img/star.png) !important;
}
.shop-box .star i {
    background: url(./tpl/Wap/pure/static/img/star_grey.png) no-repeat ;
    background-size: 100%;
}
.shop-box .star_num {
	color: #FD5236;
}

</style>
<script id="storeListBoxTpl" type="text/html">
{{# for(var i = 0, len = d.store_list.length; i < len; i++){ }}
	{{# if(d.store_list[i].show_type == 1){  }}
		<dl class="activity-box">
			<dt data-url="{{ d.store_list[i].url }}">
				<div>
					<div class="store-name">{{ d.store_list[i].name }}</div>
					<div class="activity-description">{{ d.store_list[i].activity_info.activity_description }}</div>
					<div class="detail-info">
						<span class="star-num">{{ d.store_list[i].score_mean }}分</span>
						<span class="activity-price">{pigcms{$config['Currency_symbol']}{{ d.store_list[i].activity_info.activity_price }}</span>
						{{# if(d.store_list[i].range != 0){ }}
							<div class="distance fr">{{ d.store_list[i].range }}</div>
		                {{# } }}
					</div>
					<div class="activity-name">{{ d.store_list[i].activity_info.activity_name }}</div>

					{{# if(d.store_list[i].activity_info.label_list.length>0){ }}
					<div class="activity-label">
						{{# for(var k = 0, a_len = d.store_list[i].activity_info.label_list.length; k < a_len; k++){ }}
							<span>{{ d.store_list[i].activity_info.label_list[k] }}</span>
						{{# } }}
					</div>
					{{# } }}
				</div>
				<div class="activity-image-box flex">
					{{# if(d.store_list[i].activity_info.pic_arr.length>0){ }}
						{{# for(var k = 0, i_len = d.store_list[i].activity_info.pic_arr.length; k < i_len; k++){ }}
						<span class='activity-image'>
							<img src="{{ d.store_list[i].activity_info.pic_arr[k].url }}">
						</span>
						{{# } }}
					{{# } }}
				</div>
				
				
				</div>
			</dt>
		</dl>
	{{# }else if(d.store_list[i].show_type == 2){ }}
		<dl class="on shop-box">
			<dt data-url="{{ d.store_list[i].url }}">
				<span class='img_box_1'><img src="{{ d.store_list[i].image }}"></span>
				<div class="navLtop clr top-box" >
					<span class="fl title-box">{{ d.store_list[i].name }}</span>
					
					{{# if(d.store_list[i].range != 0){ }}
						<span class="distance fr">{{ d.store_list[i].range }}</span>
	                {{# } }}
					{{# if(d.store_list[i].delivery_time > 0){ }}
						<span class="delivery_time fr">{{ d.store_list[i].delivery_time }}{{ d.store_list[i].delivery_time_type }}</span>
	                {{# } }}
				</div>
				<div class="navLBt clr">
					<ul class="navLBt_ul fl show_number clr">  
						<li>
	                        <div>
	                            <span class="star">
	                                {{#
	                                    var tmpScore = parseFloat(d.store_list[i].score_mean);
	                                    if(tmpScore>0){
	                                        for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
	                                    }else{
	                                }}
	                                    <i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
	                                {{#
	                                    }
	                                }}
	                            </span>
	                            <span class="star_num">{{ d.store_list[i].score_mean }}</span>
	                            <span class="sell_count">已售{{ d.store_list[i].month_sale_count }}单</span>
	                        </div>
						</li>
					</ul>
				</div>

				<div class="navLBt deliver-info clr">
					<span class="delivery_price">起送{pigcms{$config['Currency_symbol']}{{ d.store_list[i].delivery_price }}</span>
					<span class="delivery_money">配送{pigcms{$config['Currency_symbol']}{{ d.store_list[i].delivery_money }}</span>
					{{# if(d.store_list[i].deliver_type!=2){ }}
						<span class="deliver_name fr {{# if(d.store_list[i].deliver_type==0 || d.store_list[i].deliver_type==3){ }}platform{{# } }}">
							{{# if(d.store_list[i].deliver_type==0 || d.store_list[i].deliver_type==3){ }}
								平台配送
							{{# }else if(d.store_list[i].deliver_type==1 || d.store_list[i].deliver_type==4){ }}
								商家配送
							{{# }else if(d.store_list[i].deliver_type==5){ }}
								快递配送
		                	{{# } }}
						</span>
	                {{# } }}
				</div>
				
			</dt>
		</dl>
	{{# }else{ }}
		{{# if(d.store_list[i].state == 0){ }}
		<dl class="on">
			<dt data-url="{{ d.store_list[i].url }}">
				<span class='img_box'><img src="{{ d.store_list[i].image }}"></span>
				<div class="navLtop clr" >
					{{# if(d.store_list[i].isverify == 1){ }}
						<img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
					{{# } }}
					<h2 class="fl" style="width: 60%">{{# if (d.store_list[i].is_new == 1) { }}<span style="font-size: 12px;background-color: #29c7a2;color: white;margin-right: 5px;padding: 2px;">新店</span>{{# } }}{{ d.store_list[i].name }}</h2>
					<div class="navLtop_right fr">
						
						{{# if(d.store_list[i].is_book == 1){ }}
						<span class="ln">订</span>
						{{# } }}
						{{# if(d.store_list[i].is_queue == 1){ }}
						<span class="zi">排</span>
						{{# } }}
						{{# if(d.store_list[i].is_takeout == 1){ }}
						<span class="lv">外</span>
						{{# } }}
					</div>
				</div>
				<div class="navLBt clr">
					<ul class="navLBt_ul fl show_number clr">  
						<li>
	                        <div>
	                            <!--							<p tip="{{ d.store_list[i].score_mean }}" ></p>-->
	                            <span class="star">
	                                {{#
	                                    var tmpScore = parseFloat(d.store_list[i].score_mean);
	                                    if(tmpScore>0){
	                                        for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
	                                    }else{
	                                }}
	                                    <i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
	                                {{#
	                                    }
	                                }}
	                            </span>
	                        </div>
						</li>
					</ul>
					<div class="Notopen fl">未营业</div>
					{{# if(d.store_list[i].range != 0){ }}
						<div class="distance fr">{{ d.store_list[i].range }}</div>
	                {{# } }}
				</div>
	            {{# if (d.store_list[i].mer_discount > 0) { }}
	            <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
	            {{# } }}
	            {{# if (d.store_list[i].sys_discount > 0) { }}
	            <div class="navLBt clr"><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].sys_discount }}折优惠</span></div>
	            {{# } }}
			</dt>
			{{# if(d.store_list[i].pay_in_store == 1 && d.store_list[i].discount_txt != ''){ }}
			<dd class="navlink clr">
				<a href="{{ d.store_list[i].store_pay }}">
					<span class="link_Pay">到店付</span>
					{{# if(d.store_list[i].discount_txt.discount_type == 1){ }}
						<span>{{ d.store_list[i].discount_txt.discount_percent }}折</span>
					{{# } else { }}
						<span>每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
					{{# } }}
					<span class="link_jt fr"></span>
				</a>
			</dd>
			{{# } }}
			{{# for(var j = 0, jlen = d.store_list[i].group_list.length; j < jlen; j++){ }}
			<dd class="Menulink clr">
				<a href="{{ d.store_list[i].group_list[j].url }}">
					<div class="Menulink_img fl">
						<img class="on" src="{{ d.store_list[i].group_list[j].list_pic }}">
							{{# if(d.store_list[i].group_list[j].pin_num>0){ }}<span class="PinGroup"></span>{{# }else{ }}<span class="MenuGroup"></span>{{# } }}
					</div>
					<div class="Menulink_right">
						<h2>{{ d.store_list[i].group_list[j].name }}</h2>
						{{# if(d.store_list[i].group_list[j].wx_cheap>0){ }}
						<div class="MenuPrice" style="padding-top:0;">
							<span class="tag" style="margin-left:0;">{{ d.store_list[i].group_list[j].wx_cheap_pre}}{{ d.store_list[i].group_list[j].wx_cheap }}{pigcms{$config['Currency_txt']}</span>
							</span>
						</div>
						{{# } }}
						<div class="MenuPrice" {{# if(d.store_list[i].group_list[j].wx_cheap>0){ }} style="padding-top:0;"{{# } }}>
							<span class="PriceF"><i>{pigcms{$config['Currency_symbol']}</i><em>{{ d.store_list[i].group_list[j].price }}</em></span>
							<span class="PriceT">门市价:{pigcms{$config['Currency_symbol']}{{ d.store_list[i].group_list[j].old_price }}
							</span>
							<span class="PriceS">{{ d.store_list[i].group_list[j].sale_txt }}</span>
						</div>
					</div>
				</a>
			</dd>
			{{# } }}
		</dl>
		{{# } else { }}
		<dl>
			<dt data-url="{{ d.store_list[i].url }}">
				<span class='img_box'><img src="{{ d.store_list[i].image }}"></span>
				<div class="navLtop clr" >
					{{# if(d.store_list[i].isverify == 1){ }}
					<img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
					{{# } }}
					<h2 class="fl" style="width: 60%">{{# if (d.store_list[i].is_new == 1) { }}<span style="font-size: 12px;background-color: #29c7a2;color: white;margin-right: 5px;padding: 2px;">新店</span>{{# } }}{{ d.store_list[i].name }}</h2>
					<div class="navLtop_right fr">
						{{# if(d.store_list[i].is_book == 1){ }}
						<span class="ln">订</span>
						{{# } }}
						{{# if(d.store_list[i].is_queue == 1){ }}
						<span class="zi">排</span>
						{{# } }}
						{{# if(d.store_list[i].is_takeout == 1){ }}
						<span class="lv">外</span>
						{{# } }}
					</div>
	                
				</div>
				<div class="navLBt clr">
					<ul class="navLBt_ul fl show_number clr">  
						<li>
							<div>
	<!--							<p tip="{{ d.store_list[i].score_mean }}" ></p>-->
	                            <span class="star">
							{{#
								var tmpScore = parseFloat(d.store_list[i].score_mean);
								if(tmpScore>0){
									for(var tmpI=0;tmpI<5;tmpI++){ if(tmpScore >= tmpI+1){ }}<i class="full"></i>{{# }else if(tmpScore > tmpI){ }}<i class="half"></i>{{# }else{ }}<i></i>{{# } }
								}else{
							}}
								<i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i>
							{{#
								}
							}}
						</span>
							</div>
						</li>
					</ul>
	                {{# if (d.store_list[i].mer_discount > 0) { }}
	                <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;margin-left:10px;float:left;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
	                {{# } }}
					{{# if(d.store_list[i].range != 0){ }}
						<div class="distance fr">{{ d.store_list[i].range }}</div>
	                {{# } }}
				</div>
	            {{# if (d.store_list[i].sys_discount > 0) { }}
	            <div class="navLBt clr"><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].sys_discount }}折优惠</span></div>
	            {{# } }}
			</dt>
			{{# if(d.store_list[i].pay_in_store == 1 && d.store_list[i].discount_txt != ''){ }}
			<dd class="navlink clr">
				<a href="{{ d.store_list[i].store_pay }}">
					<span class="link_Pay">到店付</span>
					{{# if(d.store_list[i].discount_txt.discount_type == 1){ }}
						<span>{{ d.store_list[i].discount_txt.discount_percent }}折</span>
					{{# } else { }}
						<span>每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
					{{# } }}
					<span class="link_jt fr"></span>
				</a>
			</dd>
			{{# } }}
			{{# for(var j = 0, jlen = d.store_list[i].group_list.length; j < jlen; j++){ }}
			<dd class="Menulink clr">
				<a href="{{ d.store_list[i].group_list[j].url }}">
					<div class="Menulink_img fl">
						<img class="on" src="{{ d.store_list[i].group_list[j].list_pic }}">
							{{# if(d.store_list[i].group_list[j].pin_num>0){ }}<span class="PinGroup"></span>{{# }else{ }}<span class="MenuGroup"></span>{{# } }}
					</div>
					<div class="Menulink_right">
						<h2>{{ d.store_list[i].group_list[j].name }}</h2>
						{{# if(d.store_list[i].group_list[j].wx_cheap>0){ }}
						<div class="MenuPrice" style="padding-top:0;">
							<span class="tag" style="margin-left:0;">{{ d.store_list[i].group_list[j].wx_cheap_pre}}{{ d.store_list[i].group_list[j].wx_cheap }}{pigcms{$config['Currency_txt']}</span>
							</span>
						</div>
						{{# } }}
						<div class="MenuPrice" {{# if(d.store_list[i].group_list[j].wx_cheap>0){ }} style="padding-top:0;"{{# } }}>
							<span class="PriceF"><i>{pigcms{$config['Currency_symbol']}</i><em>{{ d.store_list[i].group_list[j].price }}</em></span>
							<span class="PriceT">门市价:{pigcms{$config['Currency_symbol']}{{ d.store_list[i].group_list[j].old_price }}
							</span>
							<span class="PriceS">{{ d.store_list[i].group_list[j].sale_txt }}</span>
						</div>
					</div>
				</a>
			</dd>
			{{# } }}
		</dl>
		{{# } }}
	{{# } }}
	
{{# } }}
</script>
{pigcms{$coupon_html}
<script>
    $('body').off('click','.mui-title').on('click','.mui-title',function(){
        $('#J_Calendar').hide();
    });
	$(function(){
		$('#container').css({top:'50px'});
		var Slide1_height=($(window).width() - 20) * 181 / 696
		$(".slideimg1").css('height',Slide1_height);
	});
	if ($(window).width() <= 320) {
		$('.shree').css("height",'115px');
		$(".fire").css("height",'95px');
		$(".four").css('height','65px' )
	}else if( 320<$(window).width() <= 375){
		$('.shree').css("height",'125px');
		$(".fire").css("height",'109px');
		$(".four").css('height','75px' )
	}else{
		$('.shree').css("height",'133px');
		$(".fire").css("height",'120px');
		$(".four").css('height','85px' )
	}
	var width = $(window).width();
	// 中间栏轮播广告图 690*150
	$('.slider-center').height( (width - 20) * 150 / 690 );
	if ("{pigcms{:count($foodshop_center_img)}" > 1) {
		var swiper_slider_center = new Swiper('.swiper-container.slider-center', {
	    	pagination: '.slider-center .swiper-pagination',
	        paginationClickable: true,
	        observer:true,
	        observeParents:true,
	        autoplay: 2000,
			loop:true,
			autoplayDisableOnInteraction : false
	    });
    }
</script>
<script type="text/javascript">
window.shareData = {
	"moduleName":"Home",
	"moduleID":"0",
	"imgUrl": "{pigcms{$config.site_logo}",
	"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Foodshop/index')}",
	"tTitle": "{pigcms{$config.meal_alias_name}首页 - {pigcms{$config.site_name}",
	"tContent": "{pigcms{$config.site_name}"
};
</script>
{pigcms{$shareScript}
</html> 