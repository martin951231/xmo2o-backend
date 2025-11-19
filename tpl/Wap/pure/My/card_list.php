<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<!--<title><if condition="$_GET['coupon_type'] eq 'system'">{pigcms{:L_("我的平台优惠券")}<else />{pigcms{:L_("我的商家优惠券")}</if></title>-->
<title>{pigcms{:L_("我的优惠券")}</title>
<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/swiper-3.3.1.min.css"/>
<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/card_new.css"/>
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}my_card/js/TouchSlide.1.1.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js" charset="utf-8"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
    <style>
        .Coupon .Coupon_top .fl {
            font-size: 16px;
            height: 105px;
            width: 134px;
        }
    </style>
</head>
<body>
    <section class="Coupon">
        <div id="slideBox" class="slideBox">
            <div class="swiper-container bd" id="swiper-container3" >
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <ul class="end_ul">
                            <li>
								<if condition="$coupon_list[0]">
									<volist name="coupon_list[0]" id="vo">
									<dl class="Muse">
										<dd>
											<div class="Coupon_top clr">
                                                <div class="fl" style="margin-top: 10px 0;" <if condition="$vo['designated_shop']">style='height:110px'</if>>
                                                    <div style="font-size: 12px;text-align: center;padding-top: 5px">
                                                            <span style="padding-top: 5px">{pigcms{:L_("X1",$vo['coupon_type_name'])}</span>
                                                    </div>
													<div class="fltop" style="padding-left:40px;padding-top: 6px;" <if condition="$vo.is_discount eq 1">style="text-align:center" </if>>
														<if condition="$vo.is_discount eq 1">{pigcms{:L_("X1折",floatval($vo['discount_value']))}<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vo.discount}</em></if>
														
													</div>
													<div class="flend">
													<if condition="$vo.is_discount eq 1">
														<php>if($vo['order_money']>0){</php>{pigcms{:L_("满X1打",floatval($vo['order_money']))}<php>}</php>{pigcms{:L_("X1折",floatval($vo['discount_value']))}
													<else />
														{pigcms{:L_("满X1减X2",array('X1'=>floatval($vo['order_money']),'X2'=>$vo['discount']))}
													</if>
													</div>
												</div>
												<div class="fr">
													<h2>{pigcms{$vo.name} <php>if($_GET['coupon_type']=='mer'){</php>({pigcms{$vo.merchant})<php>}</php></h2>
													<p>{pigcms{:L_("使用平台")}：{pigcms{$vo.platform}</p>
													<p>{pigcms{:L_("使用类别")}：<php>if($vo['cate_name']=='all'){echo L_("所有");}else{</php>{pigcms{$vo.cate_name}<php>}</php></p>
                                                    <if condition="$vo['coupon_type'] eq 'mer'"><p style="margin-left: -5px">【{pigcms{:L_("商家")}】：{pigcms{$vo.merchant_name}</p>
                                                    </if>

                                                    <if condition="$vo['designated_shop']">
                                                        <p>{pigcms{:L_("指定店铺")}：{pigcms{$vo.designated_shop}</p>
                                                    </if>
												</div>
											</div>
											
											<div class="Coupon_end">
												<div class="Coupon_x">
													<i>
														<php>if($_GET['coupon_type']=='system'){</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d H:i',$vo['start_time']),'X2'=>date('Y-m-d H:i',$vo['end_time'])))}
														<php>}else{</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d',$vo['start_time']),'X2'=>date('Y-m-d',$vo['end_time'])))}
														<php>}</php>
													</i>
<!--													<php>if($_GET['coupon_type']=='system'){</php><a href="{pigcms{$vo.url}" class="ljsy"><em>{pigcms{:L_("立即使用")}</em></a><php>}</php>-->
                                                    <if condition="$vo['coupon_type'] eq 'mer'">

                                                        <a href="{pigcms{$vo.jump_url}" class="ljsy"><em>{pigcms{:L_("立即使用")}</em></a>
                                                        <else/>
                                                        <a href="{pigcms{$vo.jump_url}" class="ljsy"><em>{pigcms{:L_("立即使用")}</em></a>
                                                    </if>
												</div>
												<div class="Coupon_sm">
													<span class="on">{pigcms{:L_("使用说明")}</span>
													<div class="Coupon_text overflow">{pigcms{$vo.des}</div>
												</div>    
											</div> 
											<span class="several" style="display: none;">{pigcms{:L_("X1张",$vo['get_num'])}</span>
<!--											<i class="bj"></i>    -->
										</dd>
									</dl>
									</volist>
<!--										<php>if($_GET['coupon_type']=='mer'){</php><div class="more"><a href="{pigcms{:C('config.site_url')}/wap.php">{pigcms{:L_("快去购买吧")}<span></span></a></div>	<php>}</php>-->
								</if>
<!--									<php>if($_GET['coupon_type']=='system'){</php>-->
<!--									<div class="more"><a href="{pigcms{:U('Wap/Systemcoupon/index')}">{pigcms{:L_("更多好券，去领券中心看看——>")}<span></span></a></div>-->
<!--									<php>}else{</php>-->
<!--									<div class="overdue"><span><if condition="$_GET['coupon_type'] eq 'system'">{pigcms{:L_("正在增加更多平台优惠券中")}<else />{pigcms{:L_("正在增加更多商家优惠券中")}</if></span></div>-->
<!--									<php>}</php>-->

<!--                                    <div class="more"><a href="{pigcms{:U('Wap/Systemcoupon/index')}">{pigcms{:L_("更多好券，去领券中心看看")}<span></span></a></div>-->
                <div class="more"><a href="javascript:;">{pigcms{:L_("更多好券，去领券中心看看")}<span></span></a></div>
									
                            </li>
                        </ul>
                    </div>
                    <div class="swiper-slide">
                        <ul class="end_ul">
                            <li>
								<if condition="$coupon_list[2]">
									<volist name="coupon_list[2]" id="vo">
									<dl class="Expired">
										<dd>
											<div class="Coupon_top clr">
												<div class="fl" style="margin-top: 10px 0;" <if condition="$vo['designated_shop']">style='height:110px' </if>>
                                                <div style="font-size: 12px;text-align: center;padding-top: 5px">
                                                    <span style="padding-top: 5px">{pigcms{:L_("X1",$vo['coupon_type_name'])}</span>
                                                </div>
													<div class="fltop" style="padding-left:40px;padding-top: 6px;" <if condition="$vo.is_discount eq 1">style="text-align:center" </if>>
														<if condition="$vo.is_discount eq 1">{pigcms{:L_("X1折",floatval($vo['discount_value']))}<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vo.discount}</em></if>
													</div>
													<div class="flend">
													<if condition="$vo.is_discount eq 1">
														<php>if($vo['order_money']>0){</php>{pigcms{:L_("满X1打",floatval($vo['order_money']))}<php>}</php>{pigcms{:L_("X1折",floatval($vo['discount_value']))}
													<else />
														{pigcms{:L_("满X1减X2",array('X1'=>floatval($vo['order_money']),'X2'=>$vo['discount']))}
													</if>
													</div>
												</div>
												<div class="fr">
													<h2>{pigcms{$vo.name} <php>if($_GET['coupon_type']=='mer'){</php>({pigcms{$vo.merchant})<php>}</php></h2>
													<p>{pigcms{:L_("使用平台")}：{pigcms{$vo.platform}</p>
													<p>{pigcms{:L_("使用类别")}：<php>if($vo['cate_name']=='all'){echo L_("所有");}else{</php>{pigcms{$vo.cate_name}<php>}</php></p>
                                                    <if condition="$vo['designated_shop']">
                                                        <p>{pigcms{:L_("指定店铺")}：{pigcms{$vo.designated_shop}</p>
                                                    </if>
												</div>
											</div>
											<div class="Coupon_end">
												<div class="Coupon_x">
													<i>
														<php>if($_GET['coupon_type']=='system'){</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d H:i',$vo['start_time']),'X2'=>date('Y-m-d H:i',$vo['end_time'])))}
														<php>}else{</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d',$vo['start_time']),'X2'=>date('Y-m-d',$vo['end_time'])))}
														<php>}</php>
													</i>
												
												</div>
												<div class="Coupon_sm">
													<span class="on">{pigcms{:L_("使用说明")}</span>
													<div class="Coupon_text overflow">{pigcms{$vo.des}</div>
												</div>    
											</div> 
											<span class="several" style="display: none;">{pigcms{:L_("X1张",$vo['get_num'])}</span>
<!--											<i class="bj"></i>    -->
										</dd>
										
									</dl>
									</volist>
								</if>
                                <div class="overdue">
                                    <span>{pigcms{:L_("以上是近期已过期的优惠券")}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="swiper-slide">
                        <ul class="end_ul">
                            <li>
								<if condition="$coupon_list[1]">
									<volist name="coupon_list[1]" id="vo">
									<dl class="Use">
										<dd>
											<div class="Coupon_top clr">
												<div class="fl" style="margin-top: 10px 0;" <if condition="$vo['designated_shop']">style='height:110px'</if>>
                                                    <div style="font-size: 12px;text-align: center;padding-top: 5px">
                                                        <span style="padding-top: 5px">{pigcms{:L_("X1",$vo['coupon_type_name'])}</span>
                                                    </div>
													<div class="fltop" style="padding-left:40px;padding-top: 6px;" <if condition="$vo.is_discount eq 1">style="text-align:center" </if>>
														<if condition="$vo.is_discount eq 1">{pigcms{:L_("X1折",floatval($vo['discount_value']))}<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vo.discount}</em></if>
													</div>
													<div class="flend">
													<if condition="$vo.is_discount eq 1">
														<php>if($vo['order_money']>0){</php>{pigcms{:L_("满X1打",floatval($vo['order_money']))}<php>}</php>{pigcms{:L_("X1折",floatval($vo['discount_value']))}
													<else />
														{pigcms{:L_("满X1减X2",array('X1'=>floatval($vo['order_money']),'X2'=>$vo['discount']))}
													</if>
													</div>
												</div>
												<div class="fr">
													<h2>{pigcms{$vo.name} <php>if($_GET['coupon_type']=='mer'){</php>({pigcms{$vo.merchant})<php>}</php></h2>
													<p>{pigcms{:L_("使用平台")}：{pigcms{$vo.platform}</p>
													<p>{pigcms{:L_("使用类别")}：<php>if($vo['cate_name']=='all'){echo L_("所有");}else{</php>{pigcms{$vo.cate_name}<php>}</php></p>
                                                    <if condition="$vo['designated_shop']">
                                                        <p>{pigcms{:L_("指定店铺")}：{pigcms{$vo.designated_shop}</p>
                                                    </if>
												</div>
											</div>
											<div class="Coupon_end">
												<div class="Coupon_x">
													<i>
														<php>if($_GET['coupon_type']=='system'){</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d H:i',$vo['start_time']),'X2'=>date('Y-m-d H:i',$vo['end_time'])))}
														<php>}else{</php>
															{pigcms{:L_("X1至X2",array('X1'=>date('Y-m-d',$vo['start_time']),'X2'=>date('Y-m-d',$vo['end_time'])))}
														<php>}</php>
													</i>
												
												</div>
												<div class="Coupon_sm">
													<span class="on">{pigcms{:L_("使用说明")}</span>
													<div class="Coupon_text overflow">{pigcms{$vo.des}</div>
												</div>    
											</div> 
											<span class="several" style="display: none;">{pigcms{:L_("X1张",$vo['get_num'])}</span>
<!--											<i class="bj"></i>    -->
										</dd>
									</dl>
									</volist>
								</if>
                                <div class="overdue">
                                    <span>{pigcms{:L_("以上是近期已使用的优惠券")}</span>
                                </div>
                            </li>
                        </ul>
                    </div>   
                </div> 
            </div>  
            <div class="swiper-container hd" id="swiper-container2" >
                <div class="swiper-wrapper">
                    <div class="swiper-slide active-nav">
                        {pigcms{:L_("未使用")} (<i>3</i>)
                    </div>
                    <div class="swiper-slide">
                        {pigcms{:L_("已过期")} (<i>3</i>)
                    </div>
                    <div class="swiper-slide">
                        {pigcms{:L_("已使用")} (<i>3</i>)
                    </div>
                    
                </div>
            </div>
        </div> 
    </section>    
</body>
{pigcms{$hideScript}
<if condition="$is_wxapp_browser && $config['single_system_type'] == 'village_group'">
	<script type="text/javascript">
		$('.ljsy').click(function(){
			wx.miniProgram.switchTab({url: "/pages/index/index"});
			return false;
        });
	</script>
<elseif condition="$is_wxapp_browser"/>
	<script type="text/javascript">
		$('.ljsy').click(function(){
			if($(this).attr('href').indexOf('c=Shop&a=index') > 0){
				wx.miniProgram.switchTab({url: "/pages/shop_index/index"});
                return false;
			}
        });
	</script>
</if>
<script type="text/javascript">
    // 使用次数
	var u=0;
    var s=0;
    var e=0;
	$('.Muse dd .several').each(function(){
		var value = $(this).html().replace(/[^0-9]/ig,""); 
		u+=parseInt(value);
	});
	$('.Expired dd .several').each(function(){
		var value = $(this).html().replace(/[^0-9]/ig,""); 
		s+=parseInt(value);
	});
	$('.Use dd .several').each(function(){
		var value = $(this).html().replace(/[^0-9]/ig,""); 
		e+=parseInt(value);
	});
	
    $(".hd .swiper-slide:nth-child(1)").find("i").text(u)
    $(".hd .swiper-slide:nth-child(2)").find("i").text(s)
    $(".hd .swiper-slide:nth-child(3)").find("i").text(e)


    $(".Coupon_sm").each(function(){
        $(this).find("span").click(function(){
            if($(this).hasClass("on")){
                $(this).removeClass("on")
                $(this).siblings(".Coupon_text").removeClass("overflow"); 
                $(this).parents("dd").siblings().find(".Coupon_sm span").addClass("on");
                $(this).parents("dd").siblings().find(".Coupon_sm .Coupon_text").addClass("overflow"); 
            }else{
                $(this).addClass("on")
                $(this).siblings(".Coupon_text").addClass("overflow"); 
            }
            
        })
    })

</script>

<script> 
    var mySwiper2 = new Swiper('#swiper-container2',{
    watchSlidesProgress : true,
    watchSlidesVisibility : true,
    slidesPerView : 3,
    onTap: function(){
                mySwiper3.slideTo( mySwiper2.clickedIndex)
            }
    })
    var mySwiper3 = new Swiper('#swiper-container3',{
           autoHeight: true,

    onSlideChangeStart: function(){
                updateNavPosition()
            }
    })

    function updateNavPosition(){
            $('#swiper-container2 .active-nav').removeClass('active-nav')
            var activeNav = $('#swiper-container2 .swiper-slide').eq(mySwiper3.activeIndex).addClass('active-nav');
            if (!activeNav.hasClass('swiper-slide-visible')) {
                if (activeNav.index()>mySwiper2.activeIndex) {
                    var thumbsPerNav = Math.floor(mySwiper2.width/activeNav.width())-1
                    mySwiper2.slideTo(activeNav.index()-thumbsPerNav)
                }
                else {
                    mySwiper2.slideTo(activeNav.index())
                }   
            }
        }
</script>
<script>
    $(function() {
        $('.more').click(function () {
            window.location.href = "{pigcms{:U('My_card/merchant_coupon_list')}&all_mer=1";
        })
    });
</script>

</html>

 
   
  


 