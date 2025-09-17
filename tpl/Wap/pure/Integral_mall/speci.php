<style type="text/css">
#group_price_box{
    display: none;
}
 .modal-open {
    position: fixed;
    width: 100%;
}
.goods_price i.unit{
    color: #999999;
    font-size: 12px !important;
    float: right;
    line-height: 20px;
}

</style>
<!-- 规格选项 -->
<section class="Speci" data-bottom="-535px">
    <div style="padding-left:10px" class="commentDetail">
        <div class="demo-block comment_item">
            <ul class="ui-list ui-list-link mb_0" style="background-color: transparent;">
                <li class="after  ml_0 clear" >
                    <div style="width: 90px;height: 90px;margin-right:10px;float:left;z-index: 999;" class="pics rel">
                        <img src="{pigcms{$goods.image}" data-pics='{pigcms{$goods.image}' style="top:-35px;   border-radius: 5px;" data-index="0" class="w100 h100 abs cursor" id="reg_img" />
                    </div>
                    <div class="ui-list-info ui-border-t  pb0 " style="padding-top:0px;padding-right:0px;float:left;width: 61%;">
                        <div class="title " style="padding:10px 0px 0px 0px;margin: 0px">
                            <div class="flex flex_baseline">
                                <h2 class="flex_one ui-nowrap-multi">{pigcms{$goods['goods_name']}</h2>
                            </div>
                            <div class="">
                                <div class="title_Price clr">
                                <span class="tuftof goods_price">积分
                                    <i id="show_format_price">
                                    {pigcms{$goods['payment_pure_integral']|floatval} 88788
                                    </i>
                                    <i class='unit'>/份</i>
                                </span>
                                <div class="left color_f inline ml2 bg_fa" id='tip_box2'>
                                    <span class="Discount mg1_3 pd1_3" id="showDiscount" <elt name="goods.exchange_limit_num" value='0'>style='display:none;'</elt>>
                                    限兑
                                    <b id="showMax">{pigcms{$goods['exchange_limit_num']}</b>
                                    </span>
                                </div>
                                <script>
                                    if($("#tip_box2").html().trim()==''){
                                        $("#tip_box2").hide().remove()
                                    }
                                </script>
                                <!-- <span class="tuftofdw">/1束</span>
                                <del>原价300</del> -->
                                <php>if ($goods['spec_val']['spec_list']) {</php>
                                    <if condition="$goods['sku'] eq -1 || $goods['sku'] eq 0">
                                        <span class="Surplus fl" id="show_stock_num">选择查看库存</span>
                                        <else />
                                        <span class="Surplus fl" id="show_stock_num">选择查看库存</span>
                                    </if>
                                <php>}else{</php>
                                    <if condition="$goods['sku'] eq -1">
                                        <span class="Surplus fl" id="show_stock_num">库存充足</span>
                                        <elseif  condition="$goods['sku'] elt 0"/>
                                        <span class="Surplus fl" id="show_stock_num">库存不足</span>
                                        <else />
                                        <span class="Surplus fl" id="show_stock_num">剩余{pigcms{$goods['sku']}份</span>
                                    </if>
                                <php>}</php>
                            </div>
                         </div>

                        </div>
                    </div>
                </li>
            </ul>
            <div style="position: absolute;top: 8px;right: 0px;" class="coupon_mask_close">
                <img src="{pigcms{$static_path}integral_mall/images/icon_del.png" class=" del cursor" style="padding-right:8px;width:30px;height:30px;right: 4px;top: 10px;" />
            </div>
        </div>
        <div class="clear"></div>
    </div>
    <div style="overflow:auto;width:100%;border:none;margin-bottom:100px;" class="check_info_title">
        <volist name="goods['spec_val']['spec_list']" id="spec">
            <div class="cations clr spec">
                <span class=" cas_left cursor inline">{pigcms{$spec['name']}</span>
                <div class="p35 pl0">
                    <ul data-id="{pigcms{$spec['id']}" data-name="{pigcms{$spec['name']}" data-num="1" data-type="spec">
                        <volist name="spec['list']" id="vo">
                               <php>$key_vi = $vo['id']; $spec_key_arr = $goods['spec_val']['spec_key_arr'];</php>
                                <php>if ($key_vi && in_array($key_vi, $spec_key_arr)) {</php>
                                <li data-buy_num="{pigcms{$msg[$vo['id']]}" data-id="{pigcms{$vo['id']}" data-payment_pure_integral="{pigcms{$goods['spec_val']['list'][$vo['id']]['payment_pure_integral']}" data-sku="{pigcms{$goods['spec_val']['list'][$vo['id']]['sku']}" data-exchange_limit_num="{pigcms{$goods['spec_val']['list'][$vo['id']]['exchange_limit_num']}" data-name="{pigcms{$vo['name']}" class="cursor">{pigcms{$vo['name']}</li>
                                <php>}</php>
                        </volist>
                    </ul>
                </div>
            </div>
        </volist>
    </div>
    <div class="cations clr" style="border:none;bottom: 50px;position: absolute;  width: 100%;">
        <span class="fl cas_left">数量</span>
        <div class="wrap right" style="padding-right:20px">
            <div class="plus clr" style="position:relative;">
				<div class="mode" style="position:absolute;left:32px;width:45px;top:-1px;z-index:5;height:32px;"></div>
                <a href="javascript:void(0)" class="jian">-</a>
                <input type="text" value="{pigcms{$goods['min_num']|default=1}" readonly="readonly" unselectable="on" onfocus="this.blur()"/>
                <a href="javascript:void(0)" class="jia">+</a>
            </div>
        </div>
    </div>
    <!-- 新增 -->
    <div class="purchase_right  purchase_right100 clr spec-btn">
        <a href="javascript:void(0);" class="fl shops shops100 one_btn integral_mall_buy"  data-goods_id="{pigcms{$goods['goods_id']}" data-type="add">确定兑换</a>
    </div>
    <volist name="goods['spec_val']['list']" id="index">
        <input type="hidden" class="index_{pigcms{$key}" value="{pigcms{$index['index']}">
        <input type="hidden" class="payment_pure_integral_{pigcms{$key}" value="{pigcms{$index['payment_pure_integral']}">
        <input type="hidden" class="sku_{pigcms{$key}" value="{pigcms{$index['stock_num']}">
        <input type="hidden" class="exchange_limit_num_{pigcms{$key}" value="{pigcms{$index['exchange_limit_num']}">
        <input type="hidden" class="sell_num_{pigcms{$key}" value="{pigcms{$index['sell_num']}">
        <input type="hidden" class="buy_num_{pigcms{$key}" value="{pigcms{$msg[$key]}">
    </volist>
    <input type="hidden" class="goods_exchange_limit_num" value="{pigcms{$goods['exchange_limit_num']}">
    <input type="hidden" class="goods_sku" value="{pigcms{$goods['sku']}">
    <input type="hidden" class="goods_payment_pure_integral" value="{pigcms{$goods['payment_pure_integral']}">
    <input type="hidden" class="goods_sale_count" value="{pigcms{$goods['sale_count']}">
</section>
<div class="mask cursor"></div>
<script type="text/javascript">
$(function(){
    var reson=false;
      $scrooll_top= 0;
    $('.mask').bind("touchmove",function(e){
        e.preventDefault();
    });
    $('.Speci').bind("touchmove",function(e){


    });
	$('.check_info_title').css('max-height',$(window).height()*0.6-100);
    $(window).on("scroll", function(){
        $scrooll_top=$(window).scrollTop()
    });
}); 
</script>