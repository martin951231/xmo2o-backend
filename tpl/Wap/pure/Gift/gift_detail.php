<!DOCTYPE html>
<html lang="en">
<head>
    <title>{pigcms{$config.gift_alias_name}详情</title>
<include file="Public:gift_header" />
	<script type="text/javascript" language="javascript">
		var exchange_limit_num = {pigcms{:intval($gift_detail['exchange_limit_num'])};
		if(exchange_limit_num){
			var total_sku = exchange_limit_num;
		}else{
			var sku = parseInt({pigcms{$gift_detail.sku});
			var sale_count = parseInt({pigcms{$gift_detail.sale_count})
			var total_sku = sku - sale_count;
		}
		var exchange_type = "{pigcms{$gift_detail.exchange_type}"
        var payment_integral = "{pigcms{$gift_detail['payment_integral']}"
        var payment_money = "{pigcms{$gift_detail['payment_money']}"
	</script>
	<style type="text/css">
		.detail .wrap{
			padding: 10px;
		}
		.detailsSwiper .swiper-slide img{width: 100%;height:100% }
		.detail .desc .img-p img,.descMain img{ max-width:100%!important}
		.detail .desc .img-p {
			letter-spacing: 4px;
			text-indent: 0em;
			padding: .5rem;
		}
	</style>
</head>
<body>
<div class="lodingCover">
    <div class="spinner">

	<volist name='gift_detail["wap_pic_list"]' id='gift_image_info'>
        <div class="rect{pigcms{$i}"></div>
	</volist>
    </div>
</div>
<section class="scroll detailsSwiper">
    <!-- Swiper -->
    <div class="swiper-container swiper-container-banner" id="banner_hei">
        <div class="swiper-wrapper">
			<volist name='gift_detail["wap_pic_list"]' id='gift_image_info'>
				<div class="swiper-slide">
					<img style="height:100%;  " src="{pigcms{$gift_image_info['url']}" >
					<!--div class="titles">
						<p></p>
					</div-->
				</div>
			</volist>
        </div>
        <div class="swiper-pagination swiper-pagination-banner"></div>
    </div>
</section>
<section class="detail">
    <div class="info">
        <div class="wrap pr">
			<if condition='!in_array($gift_detail["exchange_type"],array(1,2))'>
				<p class="price">{pigcms{$gift_detail['payment_pure_integral']} <em>{pigcms{$config['score_name']}</em></p>
			<else />
				<p class="price">{pigcms{$gift_detail['payment_integral']} <em>{pigcms{$config['score_name']}</em> + {pigcms{$gift_detail['payment_money']} <em>{pigcms{$config['Currency_txt']}</em></p>
			</if>

            <small class="note">
                <if condition="$gift_detail['coupon_id']">重要提示：兑换成功后，可在个人中心-我的卡券-平台优惠券中查看并使用<else/>重要提示：所有兑换礼品颜色随机发货</if>
            </small>
            <if condition="$gift_detail['coupon_id']">
                <br/>
                <small class="note">
                    券有效期：{pigcms{$gift_detail['coupon']['start_time']|date='Y年m月d日',###} 至 {pigcms{$gift_detail['coupon']['end_time']|date='Y年m月d日',###}
                </small>
            </if>
            <small class="tip pa" style="    top: .3rem;">
                已兑换
	           <em><?php if( $gift_detail['exchanged_num']){ ?>{pigcms{$gift_detail['exchanged_num']}<?php }else{ ?>{pigcms{$gift_detail['sale_count']}<?php } ?></em>

			     <if condition="$gift_detail['coupon_id']">张<else/>件</if>
            </small>
            <div class="attr">
                <div class="row">
                    <span class="fl">选择规格：</span>
                    <div class="ofh tags JSattr">
                        <div class="wraps memo">
							<volist name='gift_detail["specification"]' id='attr'>
								<a href="javascript:void(0)" <if condition='$i eq 1'>class="on"</if>>{pigcms{$attr|htmlspecialchars}</a>
							</volist>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <span class="fl">兑换方式：</span>
                    <div class="ofh tags JSways">
                        <div class="wraps exchange_type">
							<if condition='$gift_detail["exchange_type"] eq 0'>
								<a href="javascript:void(0)" data-exchange-type="0" class="on">全{pigcms{$config['score_name']}</a>
							<elseif condition='$gift_detail["exchange_type"] eq 1' />
								<a href="javascript:void(0)" data-exchange-type="1" class="on">{pigcms{$config['score_name']}+现金</a>
							<else />
								<a href="javascript:void(0)" onclick="exchangeType(0,this)" data-exchange-type="0" class="exchangeType ">全{pigcms{$config['score_name']}</a>
								<a href="javascript:void(0)" onclick="exchangeType(1,this)" data-exchange-type="1" class="exchangeType on">{pigcms{$config['score_name']}+现金</a>
							</if>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="desc">
        <div class="hd">
            <h2>礼品详情</h2>
        </div>
        <div class="descMain">
            <div class="wrap">
                <p></p>
                <p class="img-p tc">
                    {pigcms{:replace_file_domain_content(html_entity_decode($gift_detail['gift_content']))}
                </p>
            </div>
        </div>
    </div>

	<div class="desc">
        <div class="hd">
            <h2>发货清单</h2>
        </div>
        <div class="descMain">
            <div class="wrap">
                <p></p>
                <p class="img-p tc">
                    {pigcms{$gift_detail['invoice_content']|nl2br=###}
                </p>
            </div>
        </div>
    </div>
    <!-- 存在商家信息 -->
    <notempty name="mer_info">
    <style type="text/css">
    .mer_box{padding:.5rem;}
    .mer_box>div{font-size: 14px;padding:4px 0;}
    </style>
    <div class="desc">
        <div class="hd">
            <h2>商家信息</h2>
        </div>
        <div class="descMain mer_box">
            <div class="wrap">
                商家名称：{pigcms{$mer_info.name}
            </div>
            <div class="wrap">
                商家电话：{pigcms{$mer_info.phone}
            </div>
            <div class="wrap">
                商家地址：{pigcms{$mer_info.provice}{pigcms{$mer_info.city}{pigcms{$mer_info.area}
            </div>
        </div>
    </div>
    </notempty>
</section>
<section class="ftHeight"></section>
<footer class="deatilFtBtn">
    <div class="number tc fl">
        <span class="dib tit">选择数量：</span><div class="jf-bidNum dib">
            <span class="reduce">-</span><input type="text" value="1" id="now_sku"><span class="plus">+</span>
        </div>
    </div>
    <div class="buyNow fl">
        <a href="javascript:void(0)">立即兑换</a>
    </div>
</footer>
<style> 
	@media only screen and (device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) {
	    .deatilFtBtn {
	        padding-bottom: 34px !important;
			background-color: #fff;
	    }
		.ftHeight{
			height: 4rem
		}
	}
</style>
<script type="text/javascript" language="javascript">
    var currency_symbol = "{pigcms{$config.Currency_symbol}";
</script>
<include file="Public:gift_footer" />
<script>
	$('#banner_hei').height($(window).width());
    var swiper = new Swiper('.swiper-container-banner', {
        loop:true,
        autoplay: 5000,//可选选项，自动滑动
        // 如果需要分页器
        pagination: '.swiper-pagination-banner'
    });

    // 切换
    function exchangeType(type,index) {
        $('.exchangeType').removeClass('on');
        $(index).addClass('on');
        if (type == 0) {
            $html = "{pigcms{$gift_detail['payment_pure_integral']}<em>{pigcms{$config['score_name']}</em>";
            $('.price').html($html);
        } else {
            $html = "{pigcms{$gift_detail['payment_integral']} <em>{pigcms{$config['score_name']}</em> + {pigcms{$gift_detail['payment_money']} <em>{pigcms{$config['Currency_txt']}</em>";
            $('.price').html($html);
        }
    }



    $(function(){
        //如果需要赋值，请根据具体情况修改
        tagFilter(".JSattr");//选择规格
        tagFilter(".JSways");//兑换方式

       function tagFilter(obj){
           var tag=$(obj).find("a");
           if (tag) {
               tag.click(function(){
                   if($(this).hasClass("disabled") || $(this).hasClass("on") ){
                       return false;
                   }else{
                       tag.removeClass("on");
                       $(this).addClass("on");
                   }
               })
           }
       }
    })



	$('.buyNow').click(function(){
		var order_url = "{pigcms{:U('order')}";
		var gift_id = "{pigcms{$gift_detail['gift_id']}";
		var now_sku = $('#now_sku').val();
		var exchange_type = $('.exchange_type>.on').attr('data-exchange-type');
		var memo = $('.memo>.on').html().replace('<br>','');

		location.href = order_url + '&gift_id=' + gift_id + '&now_sku=' + now_sku + '&exchange_type=' + exchange_type+'&memo='+memo;
	});
</script>

<script type="text/javascript">
window.shareData = {
			"moduleName":"Gift",
			"moduleID":"0",
			"imgUrl": "<if condition="$gift_detail['wap_pic_list'][0]['url']">{pigcms{$gift_detail['wap_pic_list'][0]['url']}<else/>{pigcms{$config.site_logo}</if>",
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Gift/gift_detail',array('gift_id'=>$_GET['gift_id']))}",
			"tTitle": "{pigcms{$gift_detail['gift_name']} - {pigcms{$config.gift_alias_name}详情",
			"tContent": "{pigcms{$config.site_name}"
};
</script>
{pigcms{$shareScript}
</body>
</html>