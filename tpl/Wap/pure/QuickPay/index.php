<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta charset="utf-8">
    <title>{pigcms{$config.cash_alias_name}</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css"/>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
    <script>var noAnimate = true;</script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js" charset="utf-8"></script>
    <script type="text/javascript">
        var location_url = "{pigcms{:U('QuickPay/ajaxList')}";
        var now_cat_url="<if condition="!empty($now_cat_url)">{pigcms{$now_cat_url}<else/>-1</if>";
        var now_area_url="<if condition="!empty($now_area_url) && $all_area_list">{pigcms{$now_area_url}<else/>-1</if>";
        var now_sort_id="<if condition="!empty($now_sort_array)">{pigcms{$now_sort_array.sort_id}<else/>defaults</if>";
        var now_queue="<if condition="!empty($now_queue)">{pigcms{$now_queue}<else/>-1</if>";
        <if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
    </script>
    <script type="text/javascript" src="{pigcms{$static_path}js/dropdown.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/foodshoplist.js" charset="utf-8"></script>
    <!--[if lte IE 9]>
    <script src="scripts/html5shiv.min.js"></script>
    <![endif]-->
    <style type="text/css">

        *{margin: 0;padding: 0;}
        ul{list-style:none;}
        .swiper-container2 li{
            float: left;
            width: 20%;
            text-align: center;

        }
        .swiper-container2 li a {
            padding-top: 12px;
            display: block;
        }

        .swiper-container2 li .icon-circle {
            display: block;
            margin: auto;
            width: 40px;
            height: 40px;
            text-align: center;
            color: white;
            margin-bottom: 3px;
        }


        .icon-list .icon {
            float: left;
            width: 25%;
            text-align: center;
        }
        .icon-list.num10 .icon {
            float: left;
            width: 20%;
            text-align: center;
        }
        .swiper-container2 li .icon-desc {
            font-size: 12px;
        }

        .swiper-container2 li .icon-desc {
            text-align: center;
            color: #999;
        }
        .swiper-pagination-bullet{
            margin-right: 5px;
        }
        .swiper-pagination{
            bottom: 5px;
        }
    </style>
</head>
<body>
<if condition="$wap_quickpay_top && empty($_GET['cat_url'])">
    <section class="homepage" id="listBanner">
        <div class="swiper-container swiper-container-horizontal">
            <div class="swiper-wrapper">
                <volist name="wap_quickpay_top" id="banner">
                    <div class="swiper-slide">
                        <a href="{pigcms{$banner['url']}">
                            <img src="{pigcms{$banner['pic']}" width="100%" height="100%">
                        </a>
                    </div>
                </volist>
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <a href="{pigcms{:U('QuickPay/search')}" class="Cable">
            <div><i></i>请输入店铺名称</div>
        </a>
    </section>
    <elseif condition="empty($_GET['cat_url'])" />
    <header class="hasManyCity" <if condition='$config["mobile_head_color"]'>style="background-color: {pigcms{$config["mobile_head_color"]};"</if>>
    <div id="searchBox">
        <a href="{pigcms{:U('QuickPay/search')}">
            <i class="icon-search"></i>
            <span>请输入店铺名称</span>
        </a>
    </div>
    </header>
</if>
<if condition="empty($_GET['cat_url'])">
    <script type="text/javascript">
        var myswiper = new Swiper('.swiper-container-horizontal', {
            pagination: '.swiper-container .swiper-pagination',
            direction : 'horizontal',
            paginationClickable :true,
            autoplay :'5000',
            autoplayDisableOnInteraction : false,
            loop: true
        });

        $(".swiper-container-horizontal .swiper-slide").each(function(){
            $(this).height($(this).width()*0.375)
        })

    </script>
</if>
<if condition="$wap_quickpay_slider  && empty($_GET['cat_url'])">
    <section class="slider" style="margin-bottom: 10px;">
        <div class="swiper-container swiper-container2" style="height:168px; background: white;">
            <div class="swiper-wrapper">
                <volist name="wap_quickpay_slider" id="vo">
                    <div class="swiper-slide">
                        <ul class="icon-list num{pigcms{$wap_quickpay_slider_number}">
                            <volist name="vo" id="voo">
                                <li class="icon">
                                    <a href="{pigcms{$voo.url}">
										<span class="icon-circle">
											<img src="{pigcms{$voo.pic}">
										</span>
                                        <span class="icon-desc">{pigcms{$voo.name}</span>
                                    </a>
                                </li>
                            </volist>
                        </ul>
                    </div>
                </volist>
            </div>
            <div class="swiper-pagination swiper-pagination2"></div>
        </div>
    </section>
    <script type="text/javascript">
        var myswiper1 = new Swiper('.swiper-container2', {
            pagination: '.swiper-container2 .swiper-pagination',
            direction : 'horizontal',
            paginationClickable :true,
            // autoplay :'5000',
            autoplayDisableOnInteraction : false,
            loop: true
        });
        $(".swiper-container2 .swiper-slide").each(function(){
            $(this).height($(this).width()*0.375)
        })
        var num = "{pigcms{$wap_quickpay_slider_number}";

    </script>
</if>
</if>
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
            <div class="dropdown-module">
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
    <div class="navBox_list">
    </div>
</section>
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
</style>
<script id="storeListBoxTpl" type="text/html">
    {{# for(var i = 0, len = d.store_list.length; i < len; i++){ }}
    {{# if(d.store_list[i].state == 0){ }}
    <dl class="on">
        <dt data-url="{{ d.store_list[i].store_pay }}&html=1">
            <div class="logo_img" >
                <img src="{{d.store_list[i].image}}" />
            </div>
            <div class="navLtop clr" >
                {{# if(d.store_list[i].isverify == 1){ }}
                <img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
                {{# } }}
                <h2 class="fl">{{# if (d.store_list[i].is_new == 1) { }}<span style="font-size: 12px;background-color: #29c7a2;color: white;margin-right: 5px;padding: 2px;">新店</span>{{# } }}{{ d.store_list[i].name }}</h2>
            </div>
            <div class="navLBt clr">
                <ul class="navLBt_ul fl show_number clr">
                    <li>
                        <div class="atar_Show">
                            <p tip="{{ d.store_list[i].score_mean }}" ></p>
                        </div>
                    </li>
                </ul>
                <div class="Notopen fl">未营业</div>
                <div class="distance fr">{{ d.store_list[i].range }}</div>
            </div>
            {{# if (d.store_list[i].mer_discount > 0) { }}
            <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
            {{# } }}
            {{# if (d.store_list[i].sys_discount > 0) { }}
            <div class="navLBt clr"><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].sys_discount }}折优惠</span></div>
            {{# } }}
        </dt>
        {{# if(d.store_list[i].pay_in_store == 1){ }}
        <dd class="navlink clr">
            <a href="{{ d.store_list[i].store_pay }}&html=1">
                <span class="link_Pay">到店付</span>
                {{# if(d.store_list[i].discount_txt.discount_type == 1 && d.store_list[i].discount_txt.discount_percent != ''){ }}
                <span>{{ d.store_list[i].discount_txt.discount_percent }}折</span>
                {{# } else if(d.store_list[i].discount_txt.discount_type == 2 && d.store_list[i].discount_txt.minus_price != '') { }}
                <span>每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
                {{# } }}
                <span class="link_jt fr"></span>
            </a>
        </dd>
        {{# } }}
    </dl>
    {{# } else { }}
    <dl>
        <dt data-url="{{ d.store_list[i].store_pay }}&html=1">
            <div class="logo_img" >
                <img src="{{d.store_list[i].image}}" />
            </div>
            <div class="navLtop clr" >
                {{# if(d.store_list[i].isverify == 1){ }}
                <img src="./static/images/rec_2.png" style="width:18px; height:20px;margin-top:1px; margin-right:5px;float:left">
                {{# } }}
                <h2 class="fl">{{# if (d.store_list[i].is_new == 1) { }}<span style="font-size: 12px;background-color: #29c7a2;color: white;margin-right: 5px;padding: 2px;">新店</span>{{# } }}{{ d.store_list[i].name }}</h2>
            </div>
            <div class="navLBt clr">
                <ul class="navLBt_ul fl show_number clr">
                    <li>
                        <div class="atar_Show">
                            <p tip="{{ d.store_list[i].score_mean }}" ></p>
                        </div>
                    </li>
                </ul>
                <div class="distance fr">{{ d.store_list[i].range }}</div>
            </div>
            {{# if (d.store_list[i].mer_discount > 0) { }}
            <div class=""><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].mer_discount }}折优惠</span></div>
            {{# } }}
            {{# if (d.store_list[i].sys_discount > 0) { }}
            <div class="navLBt clr"><span style="border: 1px solid #a01615;color: #a01615;font-size: 12px;padding-left: 10px;padding-right: 10px;">{{ d.store_list[i].sys_discount }}折优惠</span></div>
            {{# } }}
        </dt>
        {{# if(d.store_list[i].pay_in_store == 1 && d.store_list[i].discount_txt != ''){ }}
        <dd class="navlink clr" style="padding: 10px 10px;line-height: inherit;">
            <a href="{{ d.store_list[i].store_pay }}&html=1">
                <div style="display:flex;">
                    <div>
                        <span class="link_Pay">到店付</span>
                    </div>

                    <div style="flex:1;padding-left: 10px;">
                        {{# if(d.store_list[i].discount_txt.discount_type == 1){ }}
                        {{# if(d.store_list[i].level_off != null && d.store_list[i].level_off.type == 1){ }}
                        {{# if(d.store_list[i].vip_discount_type == 1){ }}
                        {{# if((d.store_list[i].level_off['vv']/10) >= (d.store_list[i].discount_txt.discount_percent)){ }}
                        <span style="font-size: 16px;">商户优惠{{ (d.store_list[i].discount_txt.discount_percent)}}折</span>
                        {{# }else{ }}
                        <span style="font-size: 16px;display:flex;">会员优惠{{ (d.store_list[i].level_off['vv']/10)}}折</span>
                        {{# } }}
                        {{# } }}

                        {{# if(d.store_list[i].vip_discount_type == 2){ }}
                        <span style="font-size: 16px;">{{ (d.store_list[i].discount_txt.discount_percent)*(d.store_list[i].level_off['vv']/10)/10}}折</span>
                        {{# } }}

                        {{# }else{ }}
                        <span style="font-size: 16px;">商户优惠{{ (d.store_list[i].discount_txt.discount_percent)}}折</span>
                        {{# if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==1){ }}
                        {{# if(d.store_list[i].level_off['vv']){ }}&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 16px;display:flex;">会员优惠{{ d.store_list[i].level_off['vv']/10}}折</span>{{# } }}
                        {{# } else if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==2) { }}
                        &nbsp;&nbsp;&nbsp;&nbsp;{{# if(d.store_list[i].level_off['vv']){ }}<span style="font-size: 16px;display:flex;">会员优惠立减{{ d.store_list[i].level_off['vv']}}{pigcms{$config['Currency_txt']}</span>{{# } }}
                        {{# } }}
                        {{# } }}
                        {{# } else { }}
                        <span style="font-size: 16px;">商户优惠每满{{ d.store_list[i].discount_txt.condition_price }}减{{ d.store_list[i].discount_txt.minus_price }}{pigcms{$config['Currency_txt']}</span>
                        {{# if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==1){ }}
                        {{# if(d.store_list[i].level_off['vv']){ }}&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 16px;display:flex">会员优惠{{ d.store_list[i].level_off['vv']/10}}折{{# } }}</span>
                        {{# } else if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==2) { }}
                        {{# if(d.store_list[i].level_off['vv']){ }}&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 16px;display:flex;">会员优惠立减{{ d.store_list[i].level_off['vv']}}{pigcms{$config['Currency_txt']}{{# } }}</span>
                        {{# } }}
                        {{# } }}
                    </div>

                </div>


            </a>
        </dd>
        {{# }else{ }}

        <dd class="navlink clr">
            <a href="{{ d.store_list[i].store_pay }}&html=1">
                <span class="link_Pay">到店付</span>
                {{# if(d.store_list[i].level_off != null ){ }}
                {{# if(d.store_list[i].level_off['vv']){ }}
                {{# if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==1){ }}
                <span style="font-size: 16px;">{{# if(d.store_list[i].level_off['vv']){ }}会员优惠{{ d.store_list[i].level_off['vv']/10}}折{{# } }}&nbsp;&nbsp;&nbsp;&nbsp;</span>
                {{# } else if(d.store_list[i].level_off != null && d.store_list[i].level_off.type ==2) { }}
                <span style="font-size: 16px;">{{# if(d.store_list[i].level_off['vv']){ }}会员优惠立减{{ d.store_list[i].level_off['vv']}}{pigcms{$config['Currency_txt']}{{# } }}&nbsp;&nbsp;&nbsp;&nbsp;</span>
                {{# } }}
                {{# } }}
                {{# } }}
        </dd>

        {{# } }}
    </dl>
    {{# } }}
    {{# } }}
</script>
{pigcms{$coupon_html}
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Home",
        "moduleID":"0",
        "imgUrl": "{pigcms{$config.site_logo}",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('QuickPay/index')}",
        "tTitle": "{pigcms{$config.meal_alias_name}首页 - {pigcms{$config.site_name}",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
</html> 