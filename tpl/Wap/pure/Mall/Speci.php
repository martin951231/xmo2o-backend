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
    line-height: 24px;
}
.cyclePurchase .cas_left {
    font-weight: bold;
    color: #000;
}
.cyclePurchase .cas_left label,.cyclePurchase .color_p{
    color: #f23030;
    font-size: 12px;
} 
</style>
<!-- 规格选项 -->
<section class="Speci" data-bottom="-535px">
    <div style="padding-left:10px" class="commentDetail">
        <div class="demo-block comment_item">
            <ul class="ui-list ui-list-link mb_0" style="background-color: transparent;">
                <li class="after  ml_0 clear" >
                    <div style="width: 90px;height: 90px;margin-right:10px;float:left" class="pics rel">
                        <img src="{pigcms{$now_goods.image}" data-pics='{pigcms{$now_goods.image}' style="top:-35px;   border-radius: 5px;" data-index="0" class="w100 h100 abs cursor" id="reg_img" />
                    </div>
                    <div class="ui-list-info ui-border-t  pb0 " style="padding-top:0px;padding-right:0px;float:left;width: 61%;">
                        <div class="title " style="padding:10px 0px 0px 0px;margin: 0px">
                                <div class="flex flex_baseline">
                                    <h2 class="flex_one ui-nowrap-multi">{pigcms{$now_goods['name']}</h2>
                                    <img src="./tpl/Wap/pure/static/mall/images/icon_del.png" class=" del cursor" style="padding-right:10px;width:30px;height:30px;right: 4px;top: 10px;" />
                                </div>
                                <if condition="empty($book_act)" >
                                <div>
                                    <div class="title_Price clr" style="display: flex;align-items: flex-end;">
                                    <span class="tuftof goods_price">{pigcms{$config['Currency_symbol']}
										<i id="show_format_price">
										<if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">
											{pigcms{$now_goods['price']|floatval}
											+{pigcms{$now_goods.extra_pay_price}{pigcms{$config.extra_price_alias_name}
										<elseif condition="$now_goods.price_type eq 3"/>
											{pigcms{$now_goods['account_score']|floatval}{pigcms{$config.score_name}
										<elseif condition="$now_goods.price_type eq 2"/>
											{pigcms{$now_goods['account_money']|floatval}+{pigcms{$now_goods['account_score']|floatval}{pigcms{$config.score_name}
										<elseif condition="$now_goods.price_type eq 1"/>
											{pigcms{$now_goods['price']|floatval}
										<else/>
											 {pigcms{$now_goods['price']|floatval} 
										</if>
										   
								
										</i>
                                        <i class='unit'>/{pigcms{$now_goods.unit}</i>    
                                        </span>
                                        <!-- 拼团价 -->
                                        <if condition="isset($group_info)">
                                        <span class="tuftof goods_price" id="group_price_box">{pigcms{$config['Currency_symbol']}<i id="show_format_price2">
                                            <notempty name='team_info'>
                                                {pigcms{$group_info.join_group_price|floatval}
                                                <else/>
                                                {pigcms{$group_info.group_price|floatval}
                                            </notempty>
                                            <if condition="$config.open_extra_price eq 1 AND $now_goods.extra_pay_price gt 0">+{pigcms{$now_goods.extra_pay_price}{pigcms{$config.extra_price_alias_name}</if></i>
                                            <i class='unit'>/{pigcms{$now_goods.unit}</i>
                                            </span>
                                        </if>
                                        <div class="left color_f inline ml10 bg_fa" id='tip_box2'>
                                            <if condition="$now_goods['is_seckill_price']">
                                                <span class="Discount mg1_3 pd1_3">{pigcms{$config.seckill_alias_name|default='限时优惠'}<php>if ($now_goods['max_num'] > 0) { echo '<span id="showDiscount"> ,限<b id="showMax">' . $now_goods['max_num'] . "</b>" . $now_goods['unit'] . "优惠</span>";}</php></span>
                                                <else/>
                                                <span class="Discount mg1_3 pd1_3" id="showDiscount" <elt name="now_goods.max_num" value='0'>style='display:none;'</elt>  >限购<b id="showMax">{pigcms{$now_goods['max_num']}</b>{pigcms{$now_goods['unit']}</span>
                                        </if>
                                        <if condition="$now_goods['min_num'] gt 1">
                                            <span class="Discount pd1_3">{pigcms{$now_goods['min_num']}{pigcms{$now_goods['unit']}起购</span>
                                        </if> 
                                    </div>
                                    <script>
                                        if($("#tip_box2").html().trim()==''){
                                            $("#tip_box2").hide().remove()
                                        }
                                    </script>
                                     
                                    <if condition="$now_goods['stock_num'] eq -1">
                                        <span class="Surplus fl" id="show_stock_num" style="line-height: 24px;">库存充足 </span>
                                        <else />
                                        <span class="Surplus fl" id="show_stock_num" style="line-height: 24px;">剩余{pigcms{$now_goods['stock_num']} </span>
                                    </if>
                                    <!-- 发起拼团 -->
                                    <if condition="isset($group_info)">
                                        <div style="display:inline-block;margin-left:10px;" id='tip_box1'>
                                            <gt name='group_info.limit_num' value='0'>
                                                <span>限购<i id="group_limit_num">{pigcms{$group_info['limit_num']-$group_info['group_buy_num']}</i>{pigcms{$now_goods.unit}</span>
                                            </gt>
                                        </div>
                                    </if>
                                </div>
								<else/>
								<div>
									<p>定金：<label style="color:#f23030;">{pigcms{$config['Currency_symbol']}</label><label style="color:#f23030;font-size: 15px;" id="bargin_price">{pigcms{$book_act['bargin_price1']}</label>

                                        <if condition="$book_act['discount_price'] gt '0'">
									       <span id="discount_p">抵<label style="color:#f23030;">{pigcms{$config['Currency_symbol']}</label><label style="color:#f23030;font-size: 15px;" id='discount_price' >{pigcms{$book_act['discount_price']+$book_act['bargin_price']}</label></span>
									  </if>
									 </p>
									<p>
										<span>预售价：<label style="color:#f23030;font-size: 15px;">{pigcms{$config['Currency_symbol']}</label><label style="color:#f23030;font-size: 15px;" id="advance_price">{pigcms{$book_act['price']}</label></span>
										<if condition="$now_goods['stock_num'] eq -1">
											 <span class="Surplus " id="show_stock_num">库存充足 </span>
											 <else />
											 <span class="Surplus " id="show_stock_num">剩余{pigcms{$now_goods['stock_num']} </span>
										</if> 
									</p>
								</div>
								</if>
                         </div>


                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="clear"></div>
    </div>
    <div style="overflow:auto;width:100%;border:none;margin-bottom:100px;" class="check_info_title">
        <volist name="now_goods['spec_list']" id="spec">
        <div class="cations clr spec">
            <span class=" cas_left cursor inline">{pigcms{$spec['name']}</span>
            <div class="p35 pl0">
                <ul data-id="{pigcms{$spec['id']}" data-name="{pigcms{$spec['name']}" data-num="1" data-type="spec">
                    <volist name="spec['list']" id="vo">
                        <li data-id="{pigcms{$vo['id']}" data-name="{pigcms{$vo['name']}" class="cursor">{pigcms{$vo['name']}</li>
                    </volist>
                </ul>
            </div>
        </div>
        </volist>
        <volist name="now_goods['properties_list']" id="property">
        <div class="cations clr property">
            <span class=" cas_left cursor inline">{pigcms{$property['name']}</span>
            <div class="p35" style="padding-left:0px">
                <ul data-id="{pigcms{$property['id']}" data-name="{pigcms{$property['name']}" data-num="{pigcms{$property['num']}" data-type="properties">
                    <volist name="property['val']" id="po" key="pi">
                    <li data-id="{pigcms{$pi}" data-name="{pigcms{$po}" class="cursor">{pigcms{$po}</li>
                    </volist>
                </ul>
            </div>
        </div>
        </volist>
        <!-- 周期购 -->
        <notempty name="periodic">
        <div class="cations clr cyclePurchase">
            <span class=" cas_left cursor inline">选择配送日<label>({pigcms{$periodic['periodic_text']})</label></span>
            <div class="p35" style="padding-left:0px">
                <ul class="claarfix periodic_buy" data-name="周期购"> 
                    <volist name="periodic.periodic_date_list" id="po">
                    <li class="cursor periodic_choose" data="{pigcms{$po['val']}">{pigcms{$po['text']}</li> 
                    </volist>
                    <div class="clear"></div> 
                </ul>
            </div>
            <p class="color_p"></p><!--预计最早配送时间：{pigcms{$periodic['default_periodic_list'][0]|date="Y-m-d",###}-->

        </div>
        </notempty>
        <!-- 周期购 end-->  
        
    </div>
    <if condition="empty($book_act)" >
    <div class="cations clr" style="border:none;bottom: 50px;position: absolute;  width: 100%;">
        <span class="fl cas_left">数量</span>
        <div class="wrap right" style="padding-right:20px">
            <div class="plus clr" style="position:relative;">
				<div class="mode" style="position:absolute;left:32px;width:45px;top:-1px;z-index:5;height:32px;"></div>                
                <a href="javascript:void(0)" class="jian">-</a>                
                <input type="text" value="{pigcms{$now_goods['min_num']|default=1}" readonly="readonly" unselectable="on" onfocus="this.blur()"/>
                <a href="javascript:void(0)" class="jia">+</a>
            </div>
        </div>
    </div>
    <else/>
        <div class="plus clr" style="position:relative;display:none;">
            <input type="hidden" value="{pigcms{$now_goods['min_num']|default=1}" readonly="readonly"/>
        </div>
    </if>
    <!-- 新增 -->
    <div class="purchase_right  purchase_right100 clr spec-btn">
        <a href="javascript:void(0);" class="fl cars two_btn" style="display:none;" data-store_id="{pigcms{$store['store_id']}" data-type="add">加入购物车</a>
        <a href="javascript:void(0);" class="fl shops two_btn" style="display:none;" data-store_id="{pigcms{$store['store_id']}" data-type="buy">马上购买</a>
		<a href="javascript:void(0);" class="fl shops shops100  three_btn" style="display:none;" data-store_id="{pigcms{$store['store_id']}" data-type="buy">支付定金</a>
		<a href="javascript:void(0);" class="fl shops shops100 one_btn" style="display:none;" data-store_id="{pigcms{$store['store_id']}" data-type="add">确定</a>
    </div>
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
    })
    $(".periodic_choose").click(function(){
        var val = $(this).attr('data');
        $.post("{pigcms{:U('get_periodic_day')}",{goods_id:"{pigcms{$_GET['goods_id']}",appoint_day:val},function(res){
            $('.color_p').text(res);
        });
    })
    $(".Choice_n").click(function(){
       // $(body).css("overflow",'hidden')
            var type=$(this).data('type');
            if(type=='group_team' || type=='group_join'){
                $('#show_format_price').parent().hide();
                $('#group_price_box').show();
            }else if($('#group_price_box').length && type=='buy'){
                $('#show_format_price').parent().show();
                $('#group_price_box').hide();
            }
            if(type=='group_team' || type=='group_join'){
                $('#tip_box1').show();
                $('#tip_box2').hide().removeClass('inline');
                var min_num=1;               
                var num=json.group_num || 1;
            }else{
                $('#tip_box1').hide();
                $('#tip_box2').show().addClass('inline');
                var min_num=$('.title_data').data('min_num');
                min_num=min_num || 1;
                var num=json.buy_num || min_num;
            }
            if(min_num==num){
                $(".plus a.jian").css("color","#bfbfbf");
            }else{
                $(".plus a.jian").css("color","#333333");
            }
            $('.plus input').val(num);
        $("body").addClass('modal-open')
        $("body").css('top',-$scrooll_top)
        $("body").attr('data-top',-$scrooll_top)
    });
}); 
</script>