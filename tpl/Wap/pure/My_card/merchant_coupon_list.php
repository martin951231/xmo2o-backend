<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title><if condition="$_GET['all_mer'] eq 1">全部优惠券<else />{pigcms{$now_merchant.name}的优惠券</if></title>
<link href="{pigcms{$static_path}css/swiper.min.css" rel="stylesheet"/>
<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/card_new.css"/>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js" charset="utf-8"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
<style>
    .active-border{
        border-bottom: transparent;
    }
    .active-border>div{
        height: 95%;
        border-bottom: #ff5277 2px solid;
    }
	.can_get{
		margin-top:30px;
	}
	.Coupons .right .collar.had_pull_again{
		margin-top:8px;
	}
    .overdue{ text-align: center; font-size: 14px; color: #999999; background: url(../images/hxt_03.jpg) left center; background-repeat: repeat-x; padding: 100px 0; margin: 0px 10px;  }
    .overdue span{ background: #f4f4f4; padding: 0px 20px; }
    .Coupons .end_ul dd .left .top {
        padding: 13px 8px 18px 8px;
        border-bottom: #ececec 1px dashed;
    }
    .Coupons .right {
        height: 155px;
    }

    .Coupons .rightAl {
        background-size: 6px 155px;
    }
    .Coupons .rightAl .collar {
        margin-top: 110px;
    }

</style>
</head>
<body>
    <section class="Coupons">
        <div id="slideBox" class="slideBox">
            <div class="swiper-container bd" id="swiper-container3" >
                <div class="swiper-wrapper">
				<volist name="category_tmp" id="c"  key="k">
                    <div class="swiper-slide" <if condition="$key eq $max_category">style="display:block;"</if>>
                        <ul class="end_ul">
                            <li>
                                <dl class="Receive">
								<volist name="coupon_list[$key]" id="vv">
								
                                    <dd>
                                        <a <php>if($vv['can_get_num'] <= 0){ </php> href="javascript:void(0);" data-href="{pigcms{$vv.jump_url}" <php>}else{</php> href="javascript:void(0);" <php>}</php> class="clr" data-type="{pigcms{$vv.coupon_id}" data-mer_id="{pigcms{$vv.mer_id}" data-cate_name="{pigcms{$vv.cate_name}" data-wxcardid="{pigcms{$vv.wx_cardid}" data-tickettime="{pigcms{$vv.wx_ticket_addtime}" data-sign="{pigcms{$vv.cardsign}" data-status="{pigcms{$vv.status}" data-allow_new="{pigcms{$vv.allow_new}" data-can_get_num="{pigcms{$vv.can_get_num}" data-coupon_type="{pigcms{$vv.coupon_type}">
                                            <div class="left">
                                                <div class="top">
                                                    <img src="{pigcms{:replace_file_domain($vv['img'])}" class="fl"/>
                                                    <div class="p60">
                                                        <p style="background: #ff5277;width: 45px;border-radius: 10px;margin-top: -3px;"><span style="color:#FFFFFF;text-align: center;font-size: 12px;margin-left:4px;line-height: 15px;">{pigcms{$vv.coupon_type_name}</span></p>
                                                        <h2>
                                                            <span>{pigcms{$config['Currency_symbol']}<i>{pigcms{$vv.discount}</i></span>
                                                            <div class="Buytext">{pigcms{$vv.name}</div>
                                                        </h2>
                                                        <p>· 满<em>{pigcms{$vv.order_money|floatval}</em>{pigcms{$config['Currency_txt']}使用</p>
                                                        <if condition="$vv.coupon_type eq 'mer'">
                                                            <p style="color:black;">【商家】{pigcms{$vv.merchant_name}</p>
                                                        </if>
                                                    </div>    
                                                </div>
                                                <div class="end">
                                                    <p>{pigcms{$vv.start_time|date='Y年m月d日',###}至{pigcms{$vv.end_time|date='Y年m月d日',###}</p>
                                                    <if condition="$vv.use_categroy_name neq ''"><p>使用类别：{pigcms{$vv.use_categroy_name}</p></if>
                                                    <p>使用平台：{pigcms{$vv.platform}</p>
                                                </div>
                                            </div>
                                            <div style="background-size: 6px 155px;" class="right <if condition="$vv.status eq 3">rightEnd<elseif condition ="$vv.selected eq 1 AND $vv.can_get_num elt 0" />rightAl</if>">
                                                <div class="rob">
													<if condition="$vv.status eq 3">
													<div class="Already Alreadyon"></div>
													<elseif condition ="$vv.selected eq 1"/>
													<php>if($vv['can_get_num']<=0 ){</php>
													<div class="Already"></div>
													<php>}else{</php>
													<div class="can_get">您已领取{pigcms{$vv.has_pull}张，还可领取{pigcms{$vv['can_get_num']}张！</div>
													<php>}</php>
													<else/>
                                                    <div class="bio-chart">
                                                        <div class="circle">
                                                            <div class="pie_left">
                                                                <div class="bf_left"></div>
                                                            </div>
                                                            <div class="pie_right">
                                                                <div class="bf_right"></div>
                                                            </div>
                                                            <div class="mask1">
                                                                <span style="display: none;">{pigcms{$vv.had_pull}</span>
                                                                <P>已抢</P>
                                                                <i class="zs">{pigcms{$vv.had_pull}</i>张
                                                                <span1 style="display: none;">{pigcms{$vv.num}</span1>
                                                            </div>
                                                        </div>   
                                                    </div>    
													</if>
                                                </div>
												<if condition="$vv.status eq 3">
												<div class="collar">已抢光</div>
												<elseif condition ="$vv.selected eq 1"/>
                                                    <div class="collar <php>if($vv['can_get_num'] > 0){ </php> had_pull_again<php>}</php>"><php>if($vv['can_get_num'] > 0){ </php>继续领取<php>}else{ </php><span>去使用</span><php>}</php></div>
												<else />
                                                <div class="collar">立即领取</div>
												</if>
                                            </div>
                                            <if condition="$vv.allow_new eq 1"><div class="newuser"></div></if>
                                        </a>
                                        <div class="Coupon_sm Coupon_smon">
                                            <span class="on">使用说明</span>
                                            <div class="Coupon_text overflow">{pigcms{$vv.des}</div>
                                        </div>  
                                    </dd>
                                    </volist>
                                </dl>                        
                            </li>
                        </ul>
                    </div>
					</volist>
                   
                </div>
            </div>
            

            <div class="swiper-container hd" id="swiper-container2" <if condition="$empty_coupon eq 1"> style="display: none;"</if>>
                <div class="swiper-wrapper">
					<volist name="category_tmp" id="vc">
                    <div class="swiper-slide <if condition="$key eq $max_category">active-border</if>" style="width:auto;padding:0 15px;border:transparent !important">
						<div>{pigcms{$category[$key]}</div>
                    </div>
					</volist>
                </div>
            </div>

        <div class="overdue"><span><if condition="$empty_coupon eq 1">{pigcms{:L_("我们正在添加更多优惠券哦~")}</if></span>
        </div>
        </div>
    </section>

</body>
{pigcms{$hideScript}
<script>
	<if condition="!$is_weixin_browser">
		var is_weixin_browser = false;
	<else />
		var is_weixin_browser = true;
	</if>
</script>
<script type="text/javascript">
	var is_new =Number("{pigcms{$isnew}");

	$(function(){
		$('dd a').click(function(event) {
		    var jump_url = $(this).attr('data-href');

			var tmp= $(this);
			if($(this).data('wxcardid')!=''&&tmp.data('status')==1 && is_weixin_browser){
				 layer.open({
					title:[
						'是否同步到微信优惠券',
						'background-color:#8DCE16; color:#fff;'
					],
					content: '同步微信优惠券，您可以在微信卡包中查看'
					,btn: ['是', '否']
					,yes: function(index){
						var cardlist = [];
						var i = 0;
						$('dd a').each(function(index,val){
							var wxcardid = $(val).data('wxcardid');
							var sign = $(val).data('sign');
							var tickettime = $(val).data('tickettime');
							var status = $(val).data('status');
							var allow_new = Number($(val).data('allow_new'));
							if(wxcardid!=''&&status==1&&(is_new&&allow_new||!allow_new)){
								cardlist.push({'cardId':wxcardid,'cardExt':'{"code": "", "openid": "", "timestamp":"'+tickettime+'","signature":"'+sign+'"}'});
							}
						});
						if(cardlist){
							wx.addCard({
							  cardList:
								cardlist
							 ,
							  success: function (res) {
									layer.open({
										content: '已成功同步到微信卡包'
										,btn: ['我知道了']
									  });

								window.location.reload();
							  }
							});
						}
					}
					,no:function(index){
						ajax_had_pull(tmp,jump_url)
					}
				});

			}else{
				ajax_had_pull(tmp,jump_url)
			}
		});
				
	});
	
	
	function ajax_had_pull(tmp,jump_url=''){
		$.ajax({
			url: "{pigcms{:U('My_card/had_pull')}",
			type: 'POST',
			dataType: 'json',
			data: {coupon_id:$(tmp).data('type'),coupon_type:$(tmp).data('coupon_type')},
			success:function(data){

				if(data.error_code){
                    if (jump_url && ($(tmp).data('can_get_num') <= 0 || data.error_code == 5 || data.error_code == 7)) {
                        window.location.href = jump_url;
                        return false
                    }


					if($(tmp).data('can_get_num')<=0){
                        layer.open({
                            content: data.msg
                            ,btn: ['我知道了']
                            ,yes:function(){
                                window.location.reload();
                            }
                        });
						// window.location.href="{pigcms{:U('link')}&mer_id="+$(tmp).data('mer_id')+"&cate_name="+$(tmp).data('cate_name');
					}else{
						if(data.error_code == 1 || data.error_code==2){
							 layer.open({
							content: data.msg
							,btn: ['我知道了']
							,yes:function(){
								 window.location.reload();
							}
						  });

						}else if(data.error_code==5){
                            layer.open({
                                content: data.msg
                                ,btn: ['我知道了']
                                ,yes:function(){
                                    window.location.reload();
                                }
                            });
							 // window.location.href="{pigcms{:U('link')}&mer_id="+$(tmp).data('mer_id')+"&cate_name="+$(tmp).data('cate_name');
						}else if(data.error_code==7){
							   if(!is_weixin_browser){
								  window.location.href="{pigcms{:U('Login/index')}";
								}else{
								  window.location.href="{pigcms{:U('My/index')}";
								}
						}else if(data.error_code==3){
							 layer.open({
							content: data.msg
							,btn: ['我知道了']
						  });
							tmp.find('.rob').html('<div class="Already Alreadyon"></div>');
							tmp.find('.right').addClass('rightEnd');
							tmp.find('.collar').html('已抢光');
						}else {
							 layer.open({
							content: data.msg
							,btn: ['我知道了']
						  });

						}
					}
				}else{
				    // var can_get = 0;
				    // var user_can_get = data.coupon.limit - data.coupon.has_get;
				    // var sys_can_get = data.coupon.num - data.coupon.had_pull;
				    // if(user_can_get < sys_can_get) {
				    //     can_get = user_can_get;
                    // } else {
                    //     can_get = sys_can_get - 1;
                    // }
                    // // console.log(can_get)
					if(data.coupon.can_get>0){
						tmp.find('.rob').html('<div class="can_get">您已领取'+data.coupon.has_get+'张，还可领取'+data.coupon.can_get+'张！</div>');
						tmp.find('.collar').addClass('had_pull_again');
						tmp.find('.collar').html('继续领取');
					}else{
						// tmp.find('.rob').html('<div class="Already "></div>');
						// tmp.find('.right').addClass('rightAl');
						// tmp.find('.collar').html('去使用');
						// tmp.find('.collar').css('margin-top','95px');

                        tmp.find('.rob').html('<div class="Already Alreadyon"></div>');
                        tmp.find('.right').addClass('rightEnd');
                        tmp.find('.collar').html('已抢光');
                        tmp.find('.collar').css('margin-top','95px');
					}

					 layer.open({
						content: data.msg
						,btn: ['我知道了']
					  });
				}
			}
		});
	}
    //圆角效果  
    $(".circle").each(function(index) {
            var span1=$(this).find("span1").text();
            var num = $(this).find("span").text() * 360/span1;
            if(num<=180){
                $(this).find(".bf_right").css("transform","rotate("+num+"deg)");
                }else{
                    $(this).find(".bf_right").css("transform","rotate(180deg)");
                    $(this).find(".bf_left").css("transform","rotate("+(num-180)+"deg)");
                    }
            var b=$(this).find(".mask1 span").text();
            var c=$(this).find(".mask1 span1").text();
            var e=(b/c*100).toFixed(1)       
            $(this).find(".mask1").find("i").text(e) 
            $(this).find("i.zs").text(b);    
        });

    // 展开
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


    // 标题影藏超出
    $(".Buytext").each(function(){
        var w=$(this).siblings("span").width();
        $(this).width($(this).parents("h2").width()-w-82).css("left",w);
    })
   
</script>

<script> 
     var mySwiper2 = new Swiper('#swiper-container2',{
        direction : 'horizontal',
        freeMode:true,
        slidesPerView: 'auto'
    })
    $(".Coupons .hd .swiper-slide").click(function(){
    $(this).addClass("active-border").siblings(".swiper-slide").removeClass("active-border");
    var index = $(this).index();
    $(".Coupons .bd .swiper-slide").eq(index).show().siblings(".swiper-slide").hide();
    })
</script>

</html>

 
   
  


 

 