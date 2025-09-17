<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8" />
    <title>{pigcms{$config.mall_alias_name|default='商城'}</title>
    <link href="{pigcms{$static_path}css/groupBuy_index.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/load.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet" />
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>

    <script>
        var $home=true
        var $ctx='{pigcms{$static_path}mall'
		var open_extra_price =Number("{pigcms{$config.open_extra_price}");
		var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
		var ajax_property_url="{pigcms{:U('Mall/ajax_property')}";
		var score_name = "{pigcms{$config['score_name']}";
		console.log(score_name,'score_name')
    </script>
    <style>
        #pageLoadTip{display:none !important;}
        .h25{height:25px}
        .h45{height:45px}
        .swiper-pagination9.swiper-pagination{
            width:100% !important;
            padding-right: 0px !important
        }

        .swiper-container9 .swiper-pagination-bullet {
            width: 5px !important;
            height: 5px !important;
            display: inline-block !important;
            border-radius: 100% !important;
            background: #000 !important;
            opacity: .2 !important;
            border: 1px solid #000;

        }
        .swiper-container9  .swiper-pagination-bullet-active {
            opacity: 1 !important;
            background: #f23030 !important;
            border-radius: 50% !important;
            width: 5px !important;
            height: 5px !important;
            border: 1px solid #f23030;
        }

        #scrollers{
            position: absolute;
            z-index: 1;
            /*	-webkit-touch-callout:none;*/
            -webkit-tap-highlight-color: rgba(0,0,0,0);
            width: 100%;
            padding: 0;
        }
        .tjt_border_t:before{  top: -1px !important;  }
        .tjt_border_b:before{  bottom: -1px !important;  }
        .tjt_border_r:before{  right: -1px !important;  }
        .tjt_border_l:before{  left: -1px !important;  }
        .nav_state,.nav_state_star{background: rgba(0,0,0,0.5);
            position: absolute;
            display: block;
            right: 0;
            left: 50%;
            -webkit-transition-duration: .2s;
            transition-duration: .2s;
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            margin: 0;
            top: 50%;
            vertical-align: bottom;
            color: #fff;
            width: 60%;
            border-radius: 50%;
            height: 60%;
        }
        .nav_state_star{
            background:unset;
            border-radius: unset;
        }
        .nav_state>div{
            position: absolute;
            display: block;
            left: 50%;
            -webkit-transition-duration: .2s;
            transition-duration: .2s;
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            margin: 0;
            top: 50%;
            color: #fff;
            width: 100%;
            font-size: 12px;
        }
        .no_star_con{
            transform: rotate(45deg);
            position: absolute;
            top: 12%;
            right: -5%;
            color: #fff;
            z-index: 100;
            font-size: 11px;
        }
        .is_over{
            position: absolute;
            top: 4px;
            right: -21px;
            width: 20%;
            height: 0;
            border-width: 0px 26px 25px 26px;
            border-style: none solid solid;
            border-color: transparent transparent #989898;
            transform: rotate(45deg);

        }
        .is_no_over{
            border-color: transparent transparent #f23030 !important;


        }
        a.jump_box{position: absolute;width: 100%;height: 100%;left:0;top:0;z-index: 1;}

        #wrapper{
            /* overflow-x: auto; */
			height: 85px;
        }
        #cat-scroller{
            /* white-space: nowrap; */
        }
        #cat-scroller li {
            padding: 0 5px;
            font-size: 14px;
            color: #333;
            display: inline-block;
            text-align: center;
            height: 40px;
            line-height: 40px;
        }
        #cat-scroller  li a {
            color: #333;
            display: block;
            padding: 0 6px;
            line-height: 42px;
        }
        #cat-scroller li.is_scroll a {
            color: #f23030;
            height: .9rem;
        }
        #cat-scroller li.is_scroll a:after {
            border-top: 3px solid #f23030;
            top: 37px;
            content: '';
            position: absolute;
            width: 80%;
            height: 8px;
            left: 10%;
            z-index: 10;
        }
        .minirefresh-totop{
            bottom: calc(60px + constant(safe-area-inset-bottom))!important;
		    bottom: calc(60px + env(safe-area-inset-bottom))!important;
        }
       #youxuan{
         display:block;
	     overflow-x:hidden
       }
	   .ui-grid li{
		   padding-bottom: 10px;
	   }  
		#slideBox{
			/* margin-top: 46px; */
		} 
		.slideBox .nav_topfied{
			top:50px
		}
		.bg_f{
			position: relative;
		}
		body{
			overflow: hidden;
		}
		.add_select{
			position: absolute;
			right: 10px;
			bottom: 10px;
			height: 25px;
			padding: 0 10px;
			z-index: 999;
			width: 30px;
		}
		#ride_all>div {
			top:135px
		}
		.tip_yu{
			position: absolute;
			bottom: 0;
			left: 0;
			height: 30px;
			line-height: 30px;
			background-color: rgba(0,0,0,0.6);
			color: #fff;
			width: 100%;
			text-align: center;
			z-index: 999;
		}
        /* 可得养老金 */
        .pension-wrap {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .pension {
            padding: 0 7px 0 18px;
            font-size: 9px;
            line-height: 18px;
            min-height: 18px;
            background-color: red;
            background-image:url({pigcms{$static_public}/images/oldAgePensionIcon.png);
            background-size: 12px 12px;
            background-repeat: no-repeat;
            background-position: 4px;
            color: #ffffff;
            border-radius: 20px;
            white-space: pre-wrap;
            word-break: break-all;
            word-wrap: break-word;
            max-width: 100%;
            font-weight: normal;
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
<!-- 返回顶部按钮 -->
<div id='toTop' class="cursor"><img src="{pigcms{$static_path}mall/images/go_top.png" class="w100 h100" /></div>
<div>
    <div class="demo-block" id="search_title" >
        <div class="ui-searchbar-wrap ui-border-b ">
            <div class="ui-searchbar tjt-searchbar align_left">
                <i class="ui-icon-search"></i>
                <div class="ui-searchbar-text cursor " style="-webkit-box-flex: 1">搜索商品</div>
                <div class="ui-searchbar-input search_input"><input value="" type="text" placeholder="搜索商品" autocapitalize="off"></div>
                <i class="ui-icon-close cursor "></i>
            </div>
            <button class="ui-searchbar-cancel search_input">取消</button>
        </div>
    </div>
    <div class="slideBox content " id="slideBox"> 
		<div class="hd nav_topfied" style="z-index:1000">
			 <div  class="bg_f ">
				<div class="news-scroller bg-w  wrapper03 wrapTop"  id="wrapper">
					<ul class="clearfix clearfix_list font_size14" id="cat-scroller"> 
						<div class="clear"></div>
					</ul> 
				 </div>
				<div class='add_more add_select'></div>
			</div>
			<div style='display:none;padding-top: 0px;width:100%' id='ride_all' class="bg_f">
				<div  class="cursor "></div>
				<ul class="hotKey bg_f" data-down="1" style="display: block;"></ul>
			</div>
		</div>	
        <div style="display:none" class="h35"></div>
        <div id='refresh' class="" style="overflow:auto;">
            <div id="ui_list">
                <div class="homepage ui-border-b " id="listBanner">
                    <div class="swiper-container swiper-container1 w100 h100" >
                        <div class="swiper-wrapper w100 h100">

                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
                <div  id="one_img" class="list_all ui-border-b hide" style="margin:auto">
                    <div class="swiper-container swiper-container2 w100 h100" >
                        <div class="swiper-wrapper w100 h100">

                        </div>

                    </div>
                </div>
                <div class="menu ui-borderloading-b mb_10 none" id="listSlider">
                    <div class="swiper-container swiper-container9 w100 h100" style="padding:20px 0;">
                        <div class="swiper-wrapper w100 h100">

                        </div>
                        <div class="swiper-pagination swiper-pagination9" style="bottom: 2px;"></div>
                    </div>
                </div>
                <!--5-->
                <php> if($config['mall_main_page_show_ad']==1 && $slider_list){</php>
                <if condition="abs($config['mall_main_page_center_type']) eq 5">
                    <section class="cityRun">
                        <div class="cityItem clear <if condition="$config.mall_main_page_show_type eq 0">acss</if>">
							<div class="item0 clear ft link-url" data-url="{pigcms{$slider_list[0]['url']}">
								<if condition="$config.mall_main_page_show_type eq 1">
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
								<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
										<h3>{pigcms{$slider_list[2]['name']}</h3>
										<h4 class="c1">{pigcms{$slider_list[2]['sub_name']}</h4>
										<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four ' style='width:100%'/ >
										<else />
										<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[2]['pic']}" class='four 'style='width:100%'/>
									</if>
								</li>
								<li class="ft link-url" data-url="{pigcms{$slider_list[3]['url']}" style='padding:0px;flex:1'>
									<if condition="$config.mall_main_page_show_type eq 1">
										<h3>{pigcms{$slider_list[3]['name']}</h3>
										<h4 class="c1">{pigcms{$slider_list[3]['sub_name']}</h4>
										<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four' style='width:100%'/>
										<else />
										<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[3]['pic']}" class='four' style='width:100%'/>
									</if>
								</li>
								<li class="ft link-url" data-url="{pigcms{$slider_list[4]['url']}" style='padding:0px;flex:1'>
									<if condition="$config.mall_main_page_show_type eq 1">
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
				<elseif condition="abs($config['mall_main_page_center_type']) eq 1" />
					<!--1-->
					<section class="run clear  <if condition="$config.mall_main_page_show_type eq 0">adds</if>">
						<div class="ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style="width:100%" >
							<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider_list[0]['pic']}" class="slideimg1" style='flex:1;width: 100%;'/>
						</div>
					</section>
				<elseif condition="abs($config['mall_main_page_center_type']) eq 2" />
					<!--2-->
					<section class="run clear <if condition="$config.mall_main_page_show_type eq 0">adds</if>">
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
				<elseif condition="abs($config['mall_main_page_center_type']) eq 3" />
					<section class="run clear <if condition="$config.mall_main_page_show_type eq 0">adds</if>" style='display:flex;'>
						<div class="runItem ft acc link-url" data-url="{pigcms{$slider_list[0]['url']}" style='padding-top:0px;display:flex;flex-direction: column'>
							<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
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
				<elseif condition="abs($config['mall_main_page_center_type']) eq 4" />
					<section class="run clear <if condition="$config.mall_main_page_show_type eq 0">adds</if>">
						<div class="runItem ft add ">
							<div class="itemTop">
								<ul class="clear link-url" data-url="{pigcms{$slider_list[0]['url']}">
									<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
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
									<if condition="$config.mall_main_page_show_type eq 1">
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
			<!--<div>中间单图</div>-->
            <div  id="one_middle_img" class="list_all ui-border-b hide" style="margin:auto">
                <div class="swiper-container swiper-container2 w100 h100" >
                    <div class="swiper-wrapper w100 h100">

                    </div>

                </div>
            </div>
                <div >
                    <div class="closeItem list_all bg_f mb_10" id="kill_data" style="display:none">
                        <div class="bide-cells ">
                            <a class="bide-cell bide-cell_access pr10 " href="{pigcms{:U('Mall/seckill_goods_list')}" target="_blank" app_target_blank="_blank">
                                <div class="bide-cell__hd">
                                    <img src="{pigcms{$static_path}mall/images/seckill.png" alt="" style="width:20px;margin-right:5px;display:block"/>
                                </div>
                                <div class="bide-cell__hd font_size14" style="color: #e32650;font-size: 14px !important;">
                                    <p>{pigcms{$config.seckill_alias_name|default='限时优惠'}</p>
                                </div>
                                <div class="bide-cell__bd ">
                                    <!-- <p class="align_left color_7 time-box pl10 flex none activity_over">
                                         <span style="padding-right:7px;font-size: 12px !important;" class='font_size14' >已结束</span>
                                     </p>-->
                                    <div class="activity_go">
                                        <p class="align_left color_7 time-box pl10 flex  ">
                                            <span style="padding-right:7px;font-size: 12px !important;" class='font_size14' >距结束</span>
                                            <span class="day-box flex  ">
                                                  <span class="time joind">00</span>
                                                  <span class="font_b font_size14 color_0" >&nbsp;天&nbsp;</span>
                                              </span>
                                            <span class="hour-box flex ">
                                                  <span class="time joinh">00</span>
                                                  <span class="font-weight color_0" style="padding-bottom: 3px;">&nbsp;:&nbsp;</span>
                                              </span>

                                            <span class="time joinm">00</span>
                                            <span class="font-weight color_0" style="padding-bottom: 3px;">&nbsp;:&nbsp;</span>
                                            <span class="time joins">00</span>
                                        </p>
                                    </div>

                                </div>
                                <div class="bide-cell__ft color_2 pr15 font_size14 kill">全部</div>
                            </a>
                        </div>
                        <div class="flex  kill_list flex_baseline pb5">
                        </div>
                    </div>
                    <div class="closeItem  list_all mb_10" id="group_data" style="display:none">
                        <div class="demo-block">
                            <ul class="ui-list ui-list-single ui-list-link  mb_0">
                                <li class="after">
                                    <div class="ui-list-info">
                                        <h4 class="ui-nowrap"><span class="rel  " style='padding-left: 5px; border-left: 3px solid #f23030 ;'>拼团</span></h4>
                                    </div>
                                </li>
                            </ul>
                            <div class="group">

                            </div>

                        </div>
                    </div>
                    <div class="closeItem list_all mb_10" id="cut_data" style="display:none">
                        <div class="demo-block">
                            <ul class="ui-list ui-list-single ui-list-link  mb_0">
                                <li class="after">
                                    <div class="ui-list-info">
                                        <h4 class="ui-nowrap"><span class="rel " style='padding-left: 5px; border-left: 3px solid #f23030 ;'>砍价</span></h4>
                                    </div>
                                </li>
                            </ul>
                            <div>
                            </div>

                        </div>
                    </div>
                </div>
                <div>
                    <!--按照销量、添加时间排序；最多20-->
                    <div class="all" id="goods_list" style='display:none'>
                        <div class="demo-block ">
                            <ul class="ui-list ui-list-single ui-list-link  mb_0 hide" >
                                <li class="after">
                                    <div class="ui-list-info">
                                        <h4 class="ui-nowrap ui-selected align_center"><span class="rel point color_red">为您优选</span></h4>
                                    </div>

                                </li>
                            </ul>
                            <ul class="ui-list ui-list-single ui-list-link  mb_0">
                                <li class="after">
                                    <div class="ui-list-info">
                                        <h4 class="ui-nowrap ui-selected"><span class="rel " style="padding-left: 5px; border-left: 3px solid #f23030">为您优选</span></h4>
                                    </div>
                                </li>
                            </ul>
                            <div class="demo-list" id="check_item">
                                <div class="ui-panel ui-grid ui-grid-trisect pt0 pb0  mb_0" id="youxuan" ><ul id="" class="clr pb10"></ul></div>
                            </div>
                        </div>
                    </div>
                    <div class="one"></div>
                </div>

            </div>
        </div>
    </div>
    <div id="loading" class="hide"></div>
</div>
<include file="footer"/>
</div>
<!-- 首页轮播图 -->
<script type="text/html" id="swiper1">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var item=_this[i] &>
    <div class="swiper-slide w100 h100" >
        <a href="<&= item.url?item.url:'javascript:;'&>" target="_blank" app_target_blank="_blank" class="w100 block h100">
            <img src="<&= item.pic || item.image&>" alt=""  class="w100 h100"/>
        </a>
    </div>
    <&}&>
</script>
<script type="text/html" id="swiper2">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <div class="swiper-slide">
        <div class="flex flex_col">
            <div class="flex w100">
                <& for(var n=0;n< _item.length;n++) {&>
                <& var _item_list=_item[n] &>
                <& if(n<=4) {&>
                <div style="width:20%;" class='align_center'>
                    <a href="<&= _item_list.url &>" target="_blank" app_target_blank="_blank">
						<span class="icon-circle">
							 <img src="<&= _item_list.pic || _item_list.icon_path&>" alt="" width="36" height="36" style="display:block;margin:0 auto 5px;">
						  </span>
                        <span class="icon-desc font_size12 color_9 overflow_ellipsis2"><&= _item_list.name &></span>
                    </a>
                </div>
                <& }&>
                <& }&>
            </div>
            <div class="flex w100">
                <& for(var n=0;n< _item.length;n++) {&>
                <& var _item_list=_item[n] &>
                <& if(n>4) {&>
                <div style="width:20%;padding-top:12px;" class='align_center'>
                    <a href="<&= _item_list.url &>" target="_blank" app_target_blank="_blank" style="display:block;padding-top:2px;">
						<span class="icon-circle">
							<img src="<&= _item_list.pic || _item_list.icon_path&>" alt="" width="36" height="36" style="display:block;margin:0 auto 5px;">
						</span>
                        <span class="icon-desc font_size12 color_9 overflow_ellipsis2"><&= _item_list.name &></span>
                    </a>
                </div>
                <& }&>
                <& }&>
            </div>

        </div>
    </div>
    <& }&>

</script>
<!-- 限时秒杀 -->
<script type="text/html" id="kill">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var item=_this[i] &>
    <div class=" align_center pd5" style="width:24%">
        <a href="{pigcms{:U('Mall/detail')}&goods_id=<&= item.goods_id&>" target="_blank" app_target_blank="_blank">
            <div class="w100 rel " style="padding-top: 100%;">
                <div class="w100 h100 abs" style="top: 0;left: 0;">
                    <img src="<&= item.image&>" class="w100 h100"/>
                    <& if(item.seckill_status==0){ &>
                    <span class="no_star_con font_size10">未开始</span>
                    <div class="is_over is_no_over"></div>
                    <& }else if(item.seckill_status==2){ &>
                    <span class="no_star_con font_size10">已结束</span>
                    <div class="is_over"></div>
                    <& } &>
                </div>
            </div>
            <p class="color_orgin mg_t5 font_b font_size12">{pigcms{$config['Currency_symbol']}<&= item.seckill_price &>{pigcms{$config['Currency_txt']}/<&= item.unit &></p>
            <span class="color_9 rel   <&= View.width() &>" style="text-decoration:line-through;">{pigcms{$config['Currency_symbol']}<&= item.price &>{pigcms{$config['Currency_txt']}/<&= item.unit &></span>
    </div>
    </a>
    <&}&>
</script>
<!-- 拼团活动 -->
<script type="text/html" id="group">
    <div class="demo-block ping_tuan mb_0">
        <ul class="ui-list  mb_0">
            <& for(var i=0;i< _this.length;i++) {&>
            <& var _item=_this[i] &>
            <li class="block  ui-border-t">
                <a href="{pigcms{:U('Mall/detail')}&goods_id=<&= _item.goods_id&>&group_id=<&= _item.group_id&>" class='jump_box' target="_blank" app_target_blank="_blank"></a>
                <div class="flex flex_baseline">
                    <div class="ui-list-img-square">
                            <span >
                              <img src="<&= _item.image&>" class="w100 h100"/>
                           </span>
                    </div>
                    <div class="ui-list-info  flex_one">
                        <h4 class="font_size14 mb_8 ellipsis" style="line-height: 18px;"><&= _item.name&></h4>
                        <div class="color_9" style="margin-top:22px">单买价 <span class="border_center rel">{pigcms{$config['Currency_symbol']}<&= _item.price &></span></div>
                        <div class="demo-block" >
                            <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                                <li class="after ml_0">
                                    <div class="ui-list-info pd0">
                                        <h4 class="ui-nowrap money"><&= _item.complete_num&>人团&nbsp;&nbsp;{pigcms{$config['Currency_symbol']}<span class="font_size20"><&= _item.group_price&></span></h4>
                                        <div class="ui-txt-info font_size14 color_7 ">
                                            <p class="ui-nowrap pin_num inline color_red">
                                                <& if(_item.act_status==0){ &>
                                                <span class="color_f inline disabled">未开始</span>
                                                <& }else if(_item.act_status==2){ &>
                                                <span class="color_f inline disabled">已结束</span>
                                                <& }else if(_item.act_status==1){ &>
                                                <& if(_item.has_stock==0){ &>
                                                <span class="color_f inline disabled">已拼完</span>
                                                <& }else{ &>
                                                <span class="color_f inline">去拼团<i class="icon iconfont color_f">&#xe616;</i></span>
                                                <& } &>
                                                <& } &>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </li>
            <& }&>
        </ul>
    </div>
    <a href="{pigcms{:U('Mall/group_goods_list')}" target="_blank" app_target_blank="_blank" class='show_all'>
        <div class="ui-panel-more  align_right pr10 ui-border-t">
            <span class="ui-arrowlink">查看全部</span>
        </div>
    </a>
</script>
<!-- 砍价活动 -->
<script type="text/html" id="cat">
    <div class="demo-block ping_tuan ping_kan mb_0">
        <ul class="ui-list  mb_0">
            <& for(var i=0;i< _this.length;i++) {&>
            <& var _item=_this[i] &>
            <li class="block ui-border-t">
                <a href="{pigcms{:U('Mall/detail')}&goods_id=<&=_item.goods_id&>&bar_id=<&=_item.bar_id&>" class='jump_box' target="_blank" app_target_blank="_blank"></a>
                <div class="flex flex_baseline">
                    <div class="ui-list-img-square">
                          <span >
                             <img src="<&=_item.image&>" class="w100 h100"/>
                          </span>
                    </div>
                    <div class="ui-list-info  flex_one">
                        <h4 class="overflow_ellipsis1  font_size15 ">
                            <&=_item.name&>
                            <& if(_item.spec_str){ &>
                            <span class='font_size13 color_7'>(<&= _item.spec_str &>)</span>
                            <& } &>
                        </h4>
                        <& if(_item.describe && _item.describe!='') {&>
                        <div class="join_person flex mg_t5">
                            <div class="font_size12 color_9 ellipsis">
                                <&=_item.describe&>
                            </div>
                        </div>
                        <&}&>
                        <div class="demo-block "  style="margin-top:<& if(_item.describe && _item.describe!='') {&>5px<&}else{&> 28px<&}&>"  >
                            <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                                <li class="after ml_0">
                                    <div class="ui-list-info pd0">
                                        <h4 class="ui-nowrap money font_size12">
                                            <div class="color_red"><&=_item.bar_total_num&>人正在参加</div>
                                            <div class="color_3" style="margin-top:3px">价值<&= _item.spec_price &>{pigcms{$config['Currency_txt']}</div>
                                        </h4>
                                        <div class="ui-txt-info font_size14 color_7 mg_t10">
                                            <p class="ui-nowrap pin_num inline color_red">
                                                <& if(_item.act_status==0){ &>
                                                <span class="color_f inline disabled">未开始</span>
                                                <& }else if(_item.act_status==2){ &>
                                                <span class="color_f inline disabled">已结束</span>
                                                <& }else if(_item.act_status==1){ &>
                                                <& if(_item.has_stock==0){ &>
                                                <span class="color_f inline disabled">已砍完</span>
                                                <& }else{ &>
                                                <span class="color_f inline"><&=_item.floor_price&>{pigcms{$config['Currency_txt']}砍价<i class="icon iconfont color_f">&#xe616;</i></span>
                                                <& } &>
                                                <& }&>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </li>
            <&}&>
        </ul>
    </div>
    <a href="{pigcms{:U('Mall/cut_goods_list')}" target="_blank" app_target_blank="_blank" class='show_all'>
        <div class="ui-panel-more  align_right pr10 ui-border-t">
            <span class="ui-arrowlink">查看全部</span>
        </div>
    </a>
</script>
<script type="text/html" id="templated">
    <li data-id=""  data-index="0">
        <a href="javascript:void(0)" class=" rel ">推荐</a>
    </li>
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <li data-id="<&= _item.id &>" data-index="<&=i+1&>">
        <a href="javascript:void(0)" class=" rel "><&=_item.name &></a>
    </li>
    <& }&>
</script>
<script type="text/html" id='cate_all'>
    <li>
        <a href="javascript:void(0)"  class="bg_active" data-id="" data-index="0">推荐</a>
    </li>
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <li>
        <a href="javascript:void(0)"  class="" data-fid="<&= _item.fid &>" data-id="<&= _item.id &>" data-index="<&=i+1&>"><&= _item.name &></a>
    </li>

    <&}&>
</script>
<script type="text/html" id="checkAll">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <li class="w47 ui-border-t tjt_border_t  <&= i%2==0?'clear':'' &>" style="margin:<&= i%2==0?'2% 1% 0% 2%;':' 2% 2% 0% 1%;' &>">
        <a href="<&= _item.url&>" target="_blank" app_target_blank="_blank">
            <div class="pt0 pb0  bg_f  ui-border-l tjt_border_l  " >
                <div class="ui-border-r tjt_border_r">
                    <div class="ui-img-vertical" style="padding-top:100%; position: relative;">
                        <span class="pd0">
                            <img src="<&= _item.image&>"  style="width:<&= View.setting.pic_w&>px;height:<&= View.setting.pic_w&>px"/>
                            <& if(_item.jd_sku_id != 0){ &>
                                <div style="position: absolute;top: 0px;line-height: 20px;font-size: 12px;background: #fe5842;text-align: center;border-radius: 0px 0px 5px 5px;padding: 0 5px;color: #fff;">京东配送</div>
                             <& } &>
                        </span>

						<& if(_item.act_info && _item.act_info['is_booking'] && _item.act_info['is_booking'] == 1){ &>

							<div class="tip_yu" style="background-color: #06c1ae; opacity:0.85">预售</div>
						 <& } &>
                    </div>

                    <div class="ui-grid-info  m_0  ui-border-b">
						<!-- style="height:<&= View.height(_this,_item,i) &>" -->
                        <h4 class="ui-nowrap-multi pl5 " style="height:<&= View.height1(_this,_item,i) &>">
							<!-- style="height:<&= View.height1(_this,_item,i) &>" -->
                            <& if(_item.is_self==1){ &>
                            <i class="self_tag">自营</i>
                            <& } &>
                            <&= _item.name&>
                            <& if(_item.act_info && _item.act_info.spec_str){ &>
                            <span class='font_size13 color_7 m_0'>(<&= _item.act_info.spec_str &>)</span>
                            <& } &>
                        </h4>

                        <& if((_item.price&& _item.price!='') ||(_item.sell_count && _item.sell_count!='')){ &>
                        <p class="pl5 pb0 pension-wrap">
                            <!-- <span class="font_size18 color_red font-weight" >{pigcms{$config['Currency_symbol']}<&= _item.price&> +200米粒</span> -->
							<& if(_item.is_booking && _item.is_booking == 1){ &>
								 预售价 
							 <& } &> 
							<span class="font_size16 color_red font-weight ">
								
								 <label style="font-size: 12px;">{pigcms{$config['Currency_symbol']}</label>
							<& if (_item.price_type == 0){ &> 
							 <&= _item.price&> 
							<& } else if(_item.price_type == 1){ &>
							 <i class=" color_red font-weight "><&= _item.price &> </i>
							<& } else if(_item.price_type == 2){ &>
							 <i class=" color_red font-weight "><&=  _item.account_money &> </i>+ <&=  _item.account_score &>  <label style="font-size: 12px;"><&= score_name&></label>
							<& } else if(_item.price_type == 3){ &>
							 <&=  _item.account_score &> <label style="font-size: 12px;"><&= score_name&></label>
							<& } &> 
							</span> 
							 
                            <& if(_item.is_seckill_price){ &>
                            <span class="font_size2  color_9 border_center rel" style="font-size:12px">原价{pigcms{$config['Currency_symbol']}<&= _item.old_price &></span>
                            <& } &> 
                            <if condition="$config['open_old_age_pension'] == '1'">
							<span class="pension">可得养老金 {pigcms{$config['Currency_symbol']}<&= _item.old_age_pension &></span>
                            </if>
                        </p>
						

                        <& if(_item.act_info && _item.act_info.price){ &>
                        <p class="pb5 pl5">
                            <span class="font_size16 color_red ">{pigcms{$config['Currency_symbol']}<&= _item.act_info.price &></span>
                            <& if(_item.act_info.is_group==1){ &>
                            <span class="font_size12 color_f bg_r pd1_3 fr" ><&= _item.act_info.num &>人拼团价</span>
                            <& }else if(_item.act_info.is_cut==1){ &>
                            <span class="font_size12 color_f bg_r pd1_3 fr">砍价拿</span>
                            <& } &>
                        </p>
                        <& }else if((_item.sell_count==0 && _item.is_new==1) ||(_item.is_seckill_price && _item.is_seckill_price!='')){ &>
                        <p class=" pb0 pl5" style="overflow:hidden">
                            <& if(_item.sell_count==0 && _item.is_new==1){ &>
                            <span class="font_size12 color_f bg_795 pd1_3 left" style="margin-right:10px">新品</span>
                            <& } &>
                            <& if(_item.is_seckill_price){ &>
                            <span class="font_size12 color_f bg_fa pd1_3 left">{pigcms{$config.seckill_alias_name|default='限时优惠'}</span>
                            <& } &>
                        </p>
                        <& } &>
							
							
						<div class="goodlogoName">
							<img src="<&= _item.store_logo&>" class="store_logo" style="height: 40px;"/> 
							<div class="text">
								<span class="span1"><&= _item.store_name&></span>
								<& if(_item.sell_count>0){ &>
								<span class="font_size12" style="color:#777; float:right ;">已售<&= _item.sell_count&></span>
								<& } &> 
								 <div style="clear:both"></div>
							</div>  
							<& } &> 
							<label style="color:#999; font-size: 12px;"><&= _item.store_score_mean&>分</label>   
							<& if(_item.show_deliver == true){ &>
							   <label style="color:#999; font-size: 12px;">起送<&= _item.mall_s_basic_price&></label>
							<& } &>  
						</div>
							
                    </div>
                </div>
            </div>
        </a>
        <& }&>
    </li>
    <& if(View.setting.pageNo==1 && _this.length>0 && false){&>
    <li class="clear w100" style="width:100%">
        <div class="ui-panel-more ui-border-b align_right pr10 ">
            <span class="ui-arrowlink">查看全部</span>
        </div>
    </li>
    <&}&>
    <& if(View.isEmpty(_this)){&>
    <li class="w100 align_center" style="width:100%">
        <div class="weui-loadmore " >
            <div><img src="{pigcms{$static_path}mall/images/noresult.png"/></div>
            <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
        </div>
    </li>
    <&}&>
</script>
<script type="text/html" id="one">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var item=_this[i] &>
    <div class="swiper-slide w100 h100" >
        <a href="<&= item.url?item.url:'javascript:;'&>" target="_blank" app_target_blank="_blank">
            <img src="<&= item.pic &>" alt=""  class="w100 h100" id="<& if(i==0) {&>do_pic<&}&>"/>
        </a>
    </div>
    <&}&>
</script>
<script type="text/html" id="middle_one">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var item=_this[i] &>
    <div class="swiper-slide w100 h100" >
        <a href="<&= item.url?item.url:'javascript:;'&>" target="_blank" app_target_blank="_blank">
            <img src="<&= item.pic &>" alt=""  class="w100 h100" id="<& if(i==0) {&>do_Middle_pic<&}&>"/>
        </a>
    </div>
    <&}&>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/flexible.js"></script>
<script src="{pigcms{$static_path}mall/js/swiper.min.js"></script>

<!-- minirefresh -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>


<script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?21033322dfd" charset="utf-8"></script>

<!-- 基本变量 -->
<script type="text/javascript">
    var home_url="{pigcms{:U('Mall/ajax_home')}";
    var swiper=[];
    var pageIndexMiniRefresh = null;
</script>
<script>
    var  View={
        init:function(){
            this.initList();
            this.showIndex();
            this.search();
            this.click();
            this.scroll();
        },
        setting:{
            add_x:[],
            show_x:0,
            w:0,
            myswiper:null,
            mySwiper9:null,
            now_x:0,
            chexk_index:0,
            page_size:6,
            total_pages:0,
            pageNo: 1,
            cat_id:"",
            isNomore:false,
            isEmpty:false,
            pic_w:186,
            top:false,
            windowTime:0
        },
        width:function(){
            if($(window).width()<=350){
                return 'font_size10'
            }else{
                return 'font_size12'
            }
        },
        height:function(list,item,index){
            //偶数
            if(index*1%2==0){
                if(index*1+1!=list.length){
                    if((list[index+1].act_info && list[index+1].act_info.price) || (item.act_info && item.act_info.price) ){
                        var lcontent=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str:''+(item.is_self==1)?'自营':'';
                        var rcontent=(list[index+1].name && list[index+1].name!='')?list[index+1].name:''+(list[index+1].act_info && list[index+1].act_info.spec_str)?list[index+1].act_info.spec_str :''+(list[index+1].is_self==1)?'自营':'';

                        if((rcontent.length*14+16 >View.setting.pic_w) || (lcontent.length*14+16>View.setting.pic_w)){
                            if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index+1].sell_count==0 && list[index+1].is_new==1) ||(list[index+1].is_seckill_price && list[index+1].is_seckill_price!=''))){
                                return '105px'
                            }else{
                                return '100px'
                            }

                        }else{
                            if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index+1].sell_count==0 && list[index+1].is_new==1) ||(list[index+1].is_seckill_price && list[index+1].is_seckill_price!=''))){
                                return '85px'
                            }else{
                                return '85px'
                            }

                        }

                    }else{
                        var lcontent=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str:''+(item.is_self==1)?'自营':'';
                        var rcontent=(list[index+1].name && list[index+1].name!='')?list[index+1].name:''+(list[index+1].act_info && list[index+1].act_info.spec_str)?list[index+1].act_info.spec_str :''+(list[index+1].is_self==1)?'自营':'';

                        if((rcontent.length*14+16 >View.setting.pic_w) || (lcontent.length*14+16>View.setting.pic_w)){
                            if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index+1].sell_count==0 && list[index+1].is_new==1) ||(list[index+1].is_seckill_price && list[index+1].is_seckill_price!=''))){

                                return '103px'
                            }else{
                                return '78px'
                            }

                        }else{
                            if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index+1].sell_count==0 && list[index+1].is_new==1) ||(list[index+1].is_seckill_price && list[index+1].is_seckill_price!=''))){
                                return '85px'
                            }else{
                                return '58px'
                            }

                        }

                    }
                }else{
                    return 'auto'
                }
            }else{
                //奇数
                if((list[index-1].act_info && list[index-1].act_info.price) || (item.act_info && item.act_info.price) ){
                    var lcontent=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str :''+(item.is_self==1)?'自营':'';
                    var rcontent=(list[index-1].name && list[index-1].name!='')?list[index-1].name:''+(list[index-1].act_info && list[index-1].act_info.spec_str)?list[index-1].act_info.spec_str :''+(list[index-1].is_self==1)?'自营':'';


                    if((rcontent.length*14+16 >View.setting.pic_w) || (lcontent.length*14+16>View.setting.pic_w)){
                        if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index-1].sell_count==0 && list[index-1].is_new==1) ||(list[index-1].is_seckill_price && list[index-1].is_seckill_price!=''))){
                            return '105px'
                        }else{
                            return '100px'
                        }
                    }else{
                        if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index-1].sell_count==0 && list[index-1].is_new==1) ||(list[index-1].is_seckill_price && list[index-1].is_seckill_price!=''))){
                            return '85px'
                        }else{
                            return '85px'
                        }

                    }


                }else{
                    var lcontent=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str :''+(item.is_self==1)?'自营':'';
                    var rcontent=(list[index-1].name && list[index-1].name!='')?list[index-1].name:''+(list[index-1].act_info && list[index-1].act_info.spec_str)?list[index-1].act_info.spec_str :''+(list[index-1].is_self==1)?'自营':'';

                    if((rcontent.length*14+16 >View.setting.pic_w) || (lcontent.length*14+16>View.setting.pic_w)){
                        if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index-1].sell_count==0 && list[index-1].is_new==1) ||(list[index-1].is_seckill_price && list[index-1].is_seckill_price!=''))){
                            return '103px'
                        }else{
                            return '78px'
                        }

                    }else{
                        if(((item.sell_count==0 && item.is_new==1) ||(item.is_seckill_price && item.is_seckill_price!='')) || ((list[index-1].sell_count==0 && list[index-1].is_new==1) ||(list[index-1].is_seckill_price && list[index-1].is_seckill_price!=''))){
                            return '85px'
                        }else{
                            return '58px'
                        }

                    }
                }
            }

        },
        height1:function(list,item,index){
            //偶数
            if(index*1%2==0){
                if(index*1+1!=list.length){
                    var lcontent1=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str:''+(item.is_self==1)?'自营':'';
                    var rcontent1=(list[index+1].name && list[index+1].name!='')?list[index+1].name:''+(list[index+1].act_info && list[index+1].act_info.spec_str)?list[index+1].act_info.spec_str :''+(list[index+1].is_self==1)?'自营':'';
                    if((rcontent1.length*14+16 >View.setting.pic_w) || (lcontent1.length*14+16>View.setting.pic_w)){
                        return '45px'
                    }else{
                        return '25px'
                    }

                }else{
                    return 'auto'
                }
            }else{
                //奇数
                var lcontent1=(item.name && item.name!='')?item.name :''+(item.act_info && item.act_info.spec_str)?item.act_info.spec_str :''+(item.is_self==1)?'自营':'';
                var rcontent1=(list[index-1].name && list[index-1].name!='')?list[index-1].name:''+(list[index-1].act_info && list[index-1].act_info.spec_str)?list[index-1].act_info.spec_str :''+(list[index-1].is_self==1)?'自营':'';
                if((rcontent1.length*14+16 >View.setting.pic_w) || (lcontent1.length*14+16>View.setting.pic_w)){
                    return '45px'
                }else{
                    return '25px'
                }
            }

        },
        preventDefault:function(){
            var _this=this;
            $(".swiper-container .swiper-slide  a").click(function(e){
                if((_this.setting.myswiper!=null && _this.setting.myswiper.animating==true) || (_this.setting.mySwiper9.animating!=null && _this.setting.mySwiper9.animating==true)){
                    e.stopPropagation();
                    e.preventDefault();
                }
            });
        },
        isEmpty: function(list){
            var b = View.setting.pageNo == 1 && !list.length;
            this.setting.isEmpty=b;
            return b;
        },
        isNomore:function(list){
            console.log(this.setting.total_pages);
            console.log(this.setting.pageNo);
            console.log(list.length);
            console.log(this.setting.page_size);
            if((list.length < this.setting.page_size)){
                return true;
            }
            var b =this.setting.total_pages>0 && ((this.setting.pageNo >= this.setting.total_pages) || (list.length < this.setting.page_size)) ;
            this.setting.isNomore=b;
            return b
        },
        showIndex:function(){
            var _this=this;
            $.ajax({
                url:home_url,
                type:'post',
                dataType:'json',
                async:false,
                success:function(res){
                    console.log(res.adverMiddleImg)
                    _this.setting.windowTime=new Date().getTime();
                    _this.showList(res)
                },
                error:function(){
                }
            });

        },
        showList:function(res){
            var _this=this;
            var w=$(window).width();
            var del_h=$("#search_title").height()+$(".footerMenu").height();
			if (res.category_list.length > 0) {
				del_h = del_h + 35
			}
            $("#refresh").css("height",($(window).height()-del_h )+'px')
            _this.setting.pic_w=w*0.94/2;
            //首页轮播
            _this.banner_list=function(list,type){
                if(list && list.length>0){
                    if(_this.setting.myswiper!=null){
                        _this.setting.myswiper.destroy(false);
                        $(".swiper-container1").find(".swiper-wrapper").html('')
                    }
                    var swiper1 = Simplite.getTemplate('swiper1');
                    var swiper1_item = Simplite.compile(swiper1)(list);
                    $('#listBanner .swiper-wrapper').html(swiper1_item);
                    var r_x=$(".add_more").width();
                    var wh=$(window).width();
                    _this.setting.show_x=wh-r_x;
                    var banner_height=wh/320;
                    banner_height=Math.ceil(banner_height*119);
                    $("#listBanner").css('height',banner_height);
                    if(list.length<2){
                        $(".swiper-container1 .swiper-pagination").hide()
                    }else{
                        $(".swiper-container1 .swiper-pagination").show()
                    }
                    _this.setting.myswiper = new Swiper('.swiper-container1', {
                        pagination: {
                            el: '.swiper-container1 .swiper-pagination',
                            clickable: true,
                            type:'bullets'
                        },
                        autoplay:list.length>1?true:false,
                        autoplayDisableOnInteraction : false,
                        loop: true,
                        preventClicks : true,
                        preventLinksPropagation:true
                    });

                    _this.preventDefault()
                }
            }
            _this.banner_list(res.banner_list)

            if(res.adverMiddleImg && res.adverMiddleImg!=null && res.adverMiddleImg.length>0){
                var tp_middle_one = Simplite.getTemplate('middle_one');
                var win_w=$(window).width();
                var one_img_h=win_w*0.38;
                var compiledTpl = Simplite.compile(tp_middle_one)(res.adverMiddleImg );
                $('#one_middle_img .swiper-wrapper').html(compiledTpl);
                $("#one_middle_img").css('height','104px');
                $("#one_middle_img").removeClass('hide');
                setTimeout(function(){
                    APP.imgPic({
                        'id':'do_Middle_pic',
                        'callback':function(dimensions){
                            console.log(dimensions);
                            var i=dimensions.h/dimensions.w;
                            if(dimensions.w>win_w){
                                if(win_w*0.38>dimensions.h){
                                    $("#one_middle_img").css({
                                        'height':'104px'
                                    });
                                }else{
                                    if(i>0.38){
                                        $("#one_middle_img").css({
                                            'width':(win_w*0.38/i)+'px'
                                        });
                                    }else{
                                        $("#one_middle_img").css({
                                            'height':'104px'
                                        });
                                    }
                                }
                            }else{
                                if(win_w*0.38>dimensions.h){
                                    $("#one_middle_img").css({
                                        'width':dimensions.w+'px',
                                        'height':'104px',
                                    });

                                }else{
                                    $("#one_middle_img").css({
                                        'width':(win_w*0.38/i)+'px'
                                    });
                                }
                            }
                        }
                    })
                },200)

            }
            if(res.adver_img && res.adver_img!=null && res.adver_img.length>0){
                var tp_one = Simplite.getTemplate('one');
                var win_w=$(window).width();
                var one_img_h=win_w*0.38;
                var compiledTpl = Simplite.compile(tp_one)(res.adver_img );
                $('#one_img .swiper-wrapper').html(compiledTpl);
                $("#one_img").css('height',one_img_h+'px');
                $("#one_img").removeClass('hide');
                setTimeout(function(){
                    APP.imgPic({
                        'id':'do_pic',
                        'callback':function(dimensions){
                            console.log(dimensions);
                            var i=dimensions.h/dimensions.w;
                            if(dimensions.w>win_w){
                                if(win_w*0.38>dimensions.h){
                                    $("#one_img").css({
                                        'height':(win_w*i)+'px'
                                    });
                                }else{
                                    if(i>0.38){
                                        $("#one_img").css({
                                            'width':(win_w*0.38/i)+'px'
                                        });
                                    }else{
                                        $("#one_img").css({
                                            'height':(win_w*i)+'px'
                                        });
                                    }
                                }
                            }else{
                                if(win_w*0.38>dimensions.h){
                                    $("#one_img").css({
                                        'width':dimensions.w+'px',
                                        'height':dimensions.h+'px',
                                    });

                                }else{
                                    $("#one_img").css({
                                        'width':(win_w*0.38/i)+'px'
                                    });
                                }
                            }
                        }
                    })
                },200)

            }
            //分类
            if(res.category_list && res.category_list.length>0){
                var tp = Simplite.getTemplate('templated');
                var tp_all=Simplite.getTemplate('cate_all');
                var compiledTpl = Simplite.compile(tp)(res.category_list );
                var compiledTpl_all = Simplite.compile(tp_all)(res.category_list );
                $('#cat-scroller').html(compiledTpl);
                $('#ride_all ul').html(compiledTpl_all);
                if(res.category_list.length<3){
                    $('#scroller').parent().next().hide();
                    setTimeout(function(){
                        $("#scroller").css('padding-left',($(window).width()-$("#scroller>ul").width())/2+'px');
                    },200)

                }
                $('.hd').removeClass("hide");
                $('#cat-scroller li').eq(0).addClass('is_scroll');
            }else{
                $(".hd").addClass('hide')
            }
            _this.slider_list=function(list,type){
                if(list && list.length>0){
                    if(_this.setting.mySwiper9!=null){
                        _this.setting.mySwiper9.destroy(false);
                        $(".swiper-container9").find(".swiper-wrapper").html('')
                    }
                    var swiper2=Simplite.getTemplate('swiper2');
                    var  result=[];
                    for(var i=0,len=list.length;i<len;i+=10){
                        result.push(list.slice(i,i+10));
                    }
                    if(result.length==0){
                        $('#listSlider .swiper-wrapper').addClass('none');
                    }else{
                        var swiper2_item = Simplite.compile(swiper2)(result);
                        $('#listSlider .swiper-wrapper').html(swiper2_item).removeClass('none');
                        $('#listSlider').removeClass('none');
                        if(result.length<2){
                            $(".swiper-container9 .swiper-pagination9").hide()
                            $(".swiper-container9 .swiper-slide").addClass('swiper-no-swiping')
                        }else{
                            $(".swiper-container9 .swiper-pagination9").show()
                        }
                    }

                    _this.setting.mySwiper9=new Swiper('.swiper-container9', {
                        pagination: {
                            el: '.swiper-container9 .swiper-pagination',
                            clickable: true,
                            type:'bullets'
                        },
                        autoplay:false,
                        autoplayDisableOnInteraction : false,
                        // loop: true,
                        preventClicks : true,
                        preventLinksPropagation:true,
                        noSwiping: result.length > 1 ? false : true
                    });
                    $(".swiper-container9").css("padding",'20px 0px')
                    _this.preventDefault()
                }
            }
            _this.slider_list(res.slider_list, false)
            //限时购
            if(res.kill_data &&  res.kill_data.list && res.kill_data.list.length>0){
                $("#kill_data").show();
                var kill_data = Simplite.getTemplate('kill');
                var kill_all = Simplite.compile(kill_data)(res.kill_data.list );
                $('#kill_data .kill_list').html(kill_all);
                console.log(res.kill_data.max_clock_time)
                APP.timer({
                    time: res.kill_data.max_close_time*1*1000-_this.setting.windowTime,
                    initTime:true,
                    max_close_time:res.kill_data.max_close_time*1,
                    onBackTime:function(d){
                        /* if ( d[0] > 0 ) {
                         $(".joind").html(d[0]);
                         } else {
                         $('day-box').css('display', 'none')
                         }

                         if( d[1] > 0 ) {
                         $(".joinh").html(d[1]);
                         } else {
                         $('hour-box').css('display', 'none')
                         }*/
                        $(".joind").html(d[0]);
                        $(".joinh").html(d[1]);
                        $(".joinm").html(d[2]);
                        $(".joins").html(d[3]);


                    }
                })
            }else{
                $("#kill_data").addClass('noShow');
            }
            //拼团
            if(res.group_data &&  res.group_data.list && res.group_data.list.length>0){
                $("#group_data").show();
                var group_data = Simplite.getTemplate('group');
                var group_all = Simplite.compile(group_data)(res.group_data.list );
                $('#group_data .demo-block>div').html(group_all);
                if(res.group_data.list.length<3){
                    $("#group_data").find('.show_all').hide();
                }
            }else{
                $("#group_data").addClass('noShow');
            }
            //砍价
            if(res.cut_data &&  res.cut_data.list && res.cut_data.list.length>0){
                $("#cut_data").show();
                var cat_data = Simplite.getTemplate('cat');
                var cat_all = Simplite.compile(cat_data)(res.cut_data.list );
                $('#cut_data .demo-block>div').html(cat_all);
                if(res.cut_data.list.length<3){
                    $("#cut_data").find('.show_all').hide();
                }
            }else{
                $("#cut_data").addClass('noShow');
            }
            //优选
            $("#refresh").css({
                top:$("#search_title").height()+$("#slideBox .hd").height()+'px',
                bottom:$(".footerMenu").height()+'px'
            })
            _this.goodList(false,false,true,true)

        },
        goodList:function(type,scroll,load,is_init){
            var _this=this;
            if(type){
                _this.setting.pageNo++
            }else{
                //_this.setting.pageNo=1;
            }
            //新的规则，最多加载5次，每次6个
            //if(_this.setting.pageNo>5){
                //.isEmpty([],_this.setting.pageNo)
                //refresher.isNomore([],_this.setting.pageNo,5,_this.setting.page_size)
                //return false;
            //}
            //addr为首页标志
            var datas={
                cat_fid:_this.setting.cat_id,
                page:_this.setting.pageNo,
                page_size:_this.setting.page_size,
                addr:"home"
            }
            APP.ajax("{pigcms{:U('Mall/get_goods_list')}",datas,'post',function(res){
                if(res.goods_list &&  res.goods_list && res.goods_list!=null && res.goods_list.length>0){
                    $("#goods_list").show();
                    _this.setting.total_pages=res.total_page;

                    var checkitem = Simplite.getTemplate('checkAll');
                    var compiled_item = Simplite.compile(checkitem)(res.goods_list);
                    if(type){
                        $('#youxuan ul').append(compiled_item);
                    }else{
                        $('#youxuan ul').html('');
                        $('#youxuan ul').html(compiled_item);
                    }
                }else{
                    res.goods_list=[];
                    if(!type){
                        $('#youxuan ul').html('');
                    }
                }
                pageIndexMiniRefresh.endUpLoading(View.isNomore(res.goods_list));

                setTimeout(function () {
                    if (!load && scroll) {
                        _this.setting.top=true;
                        $("#toTop").trigger("click");
                    }
                    APP.hideLoad()
                }, 200)

            },function(){
                APP.hideLoad()
            })

        },
        search:function(){
            var url="{pigcms{:U('Mall/search')}";
            $(document).on("click",'.ui-searchbar-text',function(e){
						if(/(UniCustApp)/.test(navigator.userAgent) ){
							dsBridge.call("goSmallPigDetail", {"url":url});
							return false;
						}else{
							e.preventDefault();
							e.stopPropagation();
							if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
								url = location.protocol + '//' + document.domain + url;
							}
							if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
								var reg = /versioncode=(\d+),/;
								var arr = reg.exec(navigator.userAgent.toLowerCase());
								if(arr == null){
									
								}else{
									var version = parseInt(arr[1]);
									if(version >= 1400){
										if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
											var iosUrl = window.btoa(url);
											iosUrl = iosUrl.replace('/','&');
											window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'createwebview', url:iosUrl, webUrl: url});
											return false;
										}else{
											window.lifepasslogin.createwebview(url);
											return false;
										}
									}
								}
							}
							location.href = url;
						}
            });
        },
        scroll:function(){
            //分类定位
            var offet_top=0;
            var _self=this;
            $(window).scroll(function() {
                if(!$(".hd").hasClass("hide")){
                    if ($(window).scrollTop() > $(".slideBox").offset().top) {
                        $(".slideBox .hd").addClass("nav_topfied");
                        $(".slideBox .h35").show().css("height",$(".slideBox .hd").height()+'px')
                    }else{
                        $(".slideBox .hd").removeClass("nav_topfied");
                        $(".slideBox .h35").hide()
                    }
                }

                if($(window).scrollTop() == $(document).height() - $(window).height()){
                    // $('#loading').removeClass('hide');
                    offet_top=$(window).scrollTop();
                    if(!_self.setting.isNomore && !_self.setting.isEmpty){
                        // _self.goodList(true);
                    }

                }else{
                    if($(window).scrollTop()<offet_top){
                        $('#loading').addClass('hide');
                    }

                }
            });


        },
        click:function(){
            var _this=this;
            $(document).on("click",'.add_more',function(){
                if($(this).hasClass('add_more_all')){
                    $(this).removeClass("add_more_all")
                    $("#ride_all").hide() ;
                    $(".look_all").hide();
                    $(".clearfix_list").show();
                    // $("#cat-scroller").removeClass("no_tranns")
                }else{
                    $("#ride_all").show() ;
                    $(".look_all").show();
                    // $(".clearfix_list").hide();
                    // $("#cat-scroller").addClass("no_tranns")
                    $(this).addClass("add_more_all")
                }

            });

            $(document).on("click",'#cat-scroller li',function(){
                $(this).addClass('is_scroll').siblings().removeClass('is_scroll');
                // var nav_obj=$("#ride_all li").eq(parseInt($(this).data('id'))).find('a');
                // if(nav_obj.hasClass('bg_active')){
                //     return false;
                //  } 
				var id=$(this).data('id'); 
				var cat_id= id
				var title=document.title;
				var arr=title.split('-');
				if(cat_id>0){
					document.title=arr['0']+'-'+$(this).text();
				}else{
					document.title=arr['0'];
				} 
				 
				var nav_obj=$("#ride_all li:nth-child("+parseInt($(this).data('index')+1)+")").find('a');
				$("#ride_all li a").removeClass("bg_active");  
				nav_obj.addClass("bg_active");
				
                _this.get_info($(this).data('id'));

                _this.setting. total_pages=0;
                _this.setting.pageNo=1;
                _this.setting.cat_id=$(this).data('id')
                _this.goodList(false,true,false);
                if(parseInt($(this).data('index'))!=0){
                    $(".list_all").hide();
                    $("#goods_list>div>ul").eq(0).removeClass("hide");
                    $("#goods_list>div>ul").eq(1).addClass("hide");
                }else{
                    $(".list_all").each(function(){
                        if(!$(this).hasClass('noShow')){
                            $(this).show();
                        }
                    });
                    $("#goods_list>div>ul").eq(0).addClass("hide");
                    $("#goods_list>div>ul").eq(1).removeClass("hide");
                }
            });

            $(document).on("click",'#ride_all a',function(){ 
                $("#ride_all a").removeClass("bg_active")
                var index=parseInt($(this).attr("data-index"))
                $("#cat-scroller li").removeClass("active")
                $("#cat-scroller li").eq(index).addClass("active");
                $(".add_more").removeClass("add_more_all")
                $("#ride_all").hide() ;
                $(".look_all").hide();
                $(".clearfix_list").show();
                $("#cat-scroller").removeClass("no_tranns");
                $("#cat-scroller li").eq(index).trigger("click");
            })



            $(document).on('click', '#ride_all>div', function(){
                $(".add_more").trigger("click")

            })


        },
        initList:function(){
            var _self=this;
            
            //下拉刷新效果
			pageIndexMiniRefresh = new MiniRefresh({
				container: '#refresh',
				down: {
					callback: function(){
						location.reload();
					},
					// 这个也生效
					isWrapCssTranslate: true
				},
				up: {
					callback: function(){
                        Load();
					},
                    isAuto:false,
					// 这个也生效
					isWrapCssTranslate: true
				}
			});

            function Refresh(){
                _self.goodList(false,true,true)

            }
            function Load(){
                _self.goodList(true,true,true)
            }
            function pullTop(type){
                if(type){
                    if(_self.setting.top){
                        $("#toTop").fadeOut()
                        _self.setting.top=false
                    }else{
                        $("#toTop").fadeIn()
                    }

                }else{
                    $("#toTop").fadeOut()
                }

            }

        },
        get_info:function(id){
            var _this=this;
            $.ajax({
                url:"{pigcms{:U('Mall/get_menu_info')}",
                type:'post',
                dataType:'json',
                data:{cat_fid:id},
                async:false,
                success:function(res){
                    if(res.data.banner_list && res.data.banner_list!=null && res.data.banner_list.length>0){
                        $('#listBanner ').show();
                        _this.banner_list(res.data.banner_list,true)
                    }else{
                        $('#listBanner ').hide();
                        $('#listBanner .swiper-wrapper').html("");
                        if(_this.setting.myswiper!=null){
                            _this.setting.myswiper.removeAllSlides()
                        }

                    }

                    if(res.data.slider_list && res.data.slider_list!=null && res.data.slider_list.length>0){
                        $('#listSlider ').show();

                        _this.slider_list(res.data.slider_list,true)
                    }else{
                        $('#listSlider ').hide();
                        $('#listSlider .swiper-wrapper').html("");


                    }

                    _this.preventDefault()
                },
                error:function(){
                }
            });
        }
    }
    window.View=View;
    View.init();
function getJsBridge(){
	window._dsf=window._dsf||{};return{call:function(method,args,cb){var ret="";if(typeof args=="function"){cb=args;args={}}if(typeof cb=="function"){window.dscb=window.dscb||0;var cbName="dscb"+window.dscb++;window[cbName]=cb;args["_dscbstub"]=cbName}args=JSON.stringify(args||{});if(window._dswk){ret=prompt(window._dswk+method,args)}else{if(typeof _dsbridge=="function"){ret=_dsbridge(method,args)}else{ret=_dsbridge.call(method,args)}}return ret},register:function(name,fun){if(typeof name=="object"){Object.assign(window._dsf,name)}else{window._dsf[name]=fun}}}
}window.dsBridge=getJsBridge(); 
</script>
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/home')}",
        "tTitle": "{pigcms{$config.mall_alias_name|default='商城'}",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
<!-- 跳转加载的效果 -->
{pigcms{$coupon_html}
<if condition="$is_app_browser">
    <script type="text/javascript">
        if("{pigcms{$app_browser_type}" == 'android'){
            window.lifepasslogin.shareLifePass("{pigcms{$config.mall_alias_name|default='商城'}", "{pigcms{$config.site_name}", "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", "{pigcms{$config.site_url}{pigcms{:U('Mall/index')}");
        } else {
            iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }



    </script>
</if>
<!--广告位显示样式-->
<script type="text/javascript">
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
</script>
</body>
</html>