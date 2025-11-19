<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>{pigcms{$config.mall_alias_name|default='商城'}订单详情</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}mall/css/order_detail.css?11111" rel="stylesheet"/>
         <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/orderDetail.css?t={pigcms{:time()}"/>

        <!-- <link href="../static/shop/css/order_detail.css?11111" rel="stylesheet"/> -->
        <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
       	<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112222" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
        <style>
            #pageLoadTip{display:none !important;}
            .delay_unable{
                cursor:pointer; 
                pointer-events: none;     
                background: #ccc;
            }
			
			.fa_infor .clr .fl{
				padding-left: 10px;
				position: relative;  
				background: url('../static/mall/images/dot1.png') left center no-repeat; 
			}
			.fa_infor .clr .fl{
				 
			}
			.fa_infor .cur{
				
			}
			.ad_tips{
				background-color: rgba(255,246,224);
				color: #f89406;
				padding: 10px;
			}
			.consumes li.replace{
				margin-bottom: 5px;
			}
            .cant-pay{
                background-color: lightgrey;border: grey 1px solid;
            }
        </style>
    </head>
    <body>
		<if condition="$is_weixin_browser AND $config['open_share_lottery'] eq 1 AND $config['lottery_must_share'] eq 1 AND !empty($lottery) AND $order_details.paid  eq 1 AND $order_details.share_status eq 0">
			<div class="t_remind <if condition="$weixin_browser_type eq 'android'">az</if>" style="z-index:880">
				<p style="padding: 16px 0 5px 0">{pigcms{$lottery['detail_msg']}</p>
			</div>
		</if>
		<if condition="$is_weixin_browser AND $order_details.share_status eq 0 AND $config['open_share_lottery'] eq 0 AND $config.share_coupon eq 1 AND $order_details.paid  eq 1 AND $order_details.status neq 4 AND !empty($share_coupon)"  >
		<div class="t_remind <if condition="$weixin_browser_type eq 'android'">az</if>" style="z-index:880" >
			<p>{pigcms{$config.share_coupon_notice}</p>
		</div>

		</if>
        <section class="public">
            <a class="return link-url" id="goBackUrl" href="{pigcms{$goBackUrl|default='/packapp/plat/pages/my/my_order'}" data-url="{pigcms{$goBackUrl|default='/packapp/plat/pages/my/my_order'}"></a>
            <div class="content">订单详情</div>
            <a class="ipho phone" data-phone="{pigcms{$store['phone']}" style="cursor: pointer;"></a>
        </section>
        <if condition="$order_details['deliver_log_list']">
        <section class="defrayal">
            <div class="defrayal_n">
                <a href="{pigcms{:U('Shop/order_detail', array('order_id' => $order_details['order_id']))}" target="app_blank">
                    {pigcms{$order_details['last_log']['html']}
                    <div class="time">{pigcms{$order_details['last_log']['dateline']}</div>
                    <em>更多状态</em>
                </a>
            </div>
        </section>
        </if>
        <div class="h105"></div>
        <section class="g_details p40">
            <div class="infor">
                <ul>
                    <li class="first storext">
                        <a href="javascript:;" id="go_shop" data-store_id="{pigcms{$store['store_id']}" data-is_mult_class="1">
                            <div class="img">
                                <img src="{pigcms{$store['image']}">
                            </div>
                            <div class="tit">{pigcms{$store['name']}</div>
                        </a>
                    </li>
                </ul>
                <!-- 新版的商品详情 -->
                <ul class="menu_list order_list" id="orderList">
                    <volist name='info' id='goods_data'>
                        <volist name='goods_data.list' id='vo'>
                            <li class="goods-item">
                                <div>
                                    <img src="{pigcms{$vo.image}" alt="{pigcms{$vo.name}">
                                </div>
                                <div>
                                    <h3 class="ellipsis">{pigcms{$vo.name}</h3>
                                    <div class="spec ellipsis font_size12 color_9" style="line-height: 1.4;">
                                        <span>{pigcms{$vo.spec}</span>
                                    </div>
                                    <span class="count">3</span>
                                    <strong class="price font_size16">
                                        {pigcms{$config['Currency_symbol']}<span class="unit_price">{pigcms{$vo.price}<if condition="getFormatNumber($vo['score'])">+{pigcms{$vo.score|intval}{pigcms{$config.score_name}</if></span>
                                        <!-- 秒杀价 -->
                                        <eq name='vo.is_seckill' value='1'>
                                        <span class='seckill_tag'>{pigcms{$config.seckill_alias_name|default='限时优惠'}价</span>
                                        </eq>
                                        <span class="fr font_size14 color_3" style="margin-top: 2px;">x{pigcms{$vo.num}</span>
                                    </strong>
                                </div>
                            </li>
                            <if condition="$vo.give_goods_id neq '' || $vo.give_coupon_id neq ''">
                            <li style="background: #f8f8f8;margin-bottom: 10px;height: 50px;" class="goods-item">
                                <div style="height: 50px;">
                                    <h3>赠品：</h3>
                                    <if condition="$vo.give_goods_id neq ''">
                                    <p style="margin-left:15%;" class=''>
                                        商品:&nbsp;{pigcms{$vo.give_goods_id} &nbsp;{pigcms{$config['Currency_symbol']}{pigcms{$vo.give_goods_price}&nbsp;X1
                                    </p>
                                    </if>
                                    <if condition="$vo.give_coupon_id neq ''">
                                    <p style="margin-left:15%;" class=''>
                                        优惠券:&nbsp;{pigcms{$vo.give_coupon_id} &nbsp;X1
                                    </p>
                                    </if>
                                </div>
                            </li>
                            </if>
                        </volist>
                    </volist>
                </ul>
                <if condition="!empty($discount_detail) or $store_discount_price gt 0">
                <div class="reduce">
                    <dl>
                        <gt name="store_discount_price" value='0'>
                            <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl ff0" style="background: #edcd60;">店</em>
                                    <div class="p20">店铺折扣</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$store_discount_price|getFormatNumber}({pigcms{$store_discount|bcdiv=10|getFormatNumber}折)</div>
                            </dd>
                        </gt>
                        <volist name="discount_detail" id="discount">
                            <if condition="$discount['discount_type'] eq 1">
                            <dd class="clr">
                                <div  class="fl clr">
                                    <em class="fl e0c">首</em>
                                    <div class="p20">平台首单满{pigcms{$config['Currency_symbol']}{pigcms{$discount['money']|getFormatNumber}减{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                            </dd>
                            <elseif condition="$discount['discount_type'] eq 2" />
                            <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl d52">减</em>
                                    <div class="p20">平台优惠满{pigcms{$config['Currency_symbol']}{pigcms{$discount['money']|getFormatNumber}减{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                            </dd>
                            <elseif condition="$discount['discount_type'] eq 3" />
                            <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl ffa">首</em>
                                    <div class="p20">店铺首单满{pigcms{$config['Currency_symbol']}{pigcms{$discount['money']|getFormatNumber}减{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                            </dd>
                            <elseif condition="$discount['discount_type'] eq 4" />
                            <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl ff6">减</em>
                                    <div class="p20">店铺优惠满{pigcms{$config['Currency_symbol']}{pigcms{$discount['money']|getFormatNumber}减{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                            </dd>
                            <elseif condition="$discount['discount_type'] eq 5" />
                            <!-- <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl ff0">惠</em>
                                    <div class="p20">商品满{pigcms{$config['Currency_symbol']}{pigcms{$discount['money']|getFormatNumber}配送费减{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['minus']|getFormatNumber}</div>
                            </dd> -->
                            <elseif condition="$discount['discount_type'] eq 6" />
                            <dd class="clr">
                                <div class="fl clr">
                                    <em class="fl ff0" style="background: #edcd60;">V</em>
                                    <div class="p20">会员等级优惠金额：</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$discount['vipLevelReduceMoney']|getFormatNumber}({pigcms{$discount['vip_discount']}折)</div>
                            </dd>
                            </if>
                        </volist>
						<volist name="pack_act_list" id="pack">
							<dd class="clr">
                                <div class="fl clr">
                                    <!--<em class="fl ff0" style="background: #edcd60;">店</em>-->
                                    <div class="p20">{pigcms{$pack.pack_price}元任意{pigcms{$pack.pack_num}件</div>
                                </div>
                                <div class="fr ff3">-{pigcms{$config['Currency_symbol']}{pigcms{$pack.pack_discount_price}</div>
                            </dd>
						</volist>
                    </dl>
                </div>
                </if>
                <div class="mealfee">
                    <dl>
                        <if condition="$order_details['packing_charge'] gt 0">
                            <dd class="clr">
                                <div class="fl">{pigcms{$store['pack_alias']|default="打包费"}</div>
                                <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['packing_charge']|getFormatNumber}</div>
                            </dd>
                        </if>
                        <if condition="$order_details['freight_charge'] gt 0">
                            <dd class="clr">
                                <div class="fl">{pigcms{$store['freight_alias']|default="配送费"}</div>
                                <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['freight_charge']|getFormatNumber}</div>
                            </dd>
                        </if>
                        <if condition="$order_details['other_money'] gt 0">
                            <dd class="clr">
                                <div class="fl">加价送的金额</div>
                                <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['other_money']|getFormatNumber}</div>
                            </dd>
                        </if>
                    </dl>
                </div>
                <div class="answer clr">
                    <div class="fl">
                        <!--预售-->
                        <if condition="$order_details['book_id'] gt 0">
                            订单{pigcms{$config['Currency_symbol']}{pigcms{$order_details['book_all_pay']|intval}
                            <else />
                            订单{pigcms{$config['Currency_symbol']}{pigcms{$order_details['discount_price']+$order_details['vip_level_reduce_money']+$store_discount_price|intval}
                        </if>
					   <if condition="$order_details['total_score'] gt 0">
					   +{pigcms{$order_details['total_score']|intval}{pigcms{$config.score_name}
					   </if>
                        <if condition="$order_details['minus_price'] gt 0 || $order_details['vip_level_reduce_money'] gt 0 || $store_discount_price gt 0"> 
                            优惠-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['minus_price']+$order_details['vip_level_reduce_money']+$store_discount_price|getFormatNumber}
                        </if>
                    </div>
                    <div class="fr">
                        <div>
                            <!--预售-->
                        <if condition="$order_details['book_id'] gt 0">
                            应收总额: {pigcms{$config['Currency_symbol']}{pigcms{$order_details['book_all_pay']|getFormatNumber}
                            <else />
                            应收总额: {pigcms{$config['Currency_symbol']}{pigcms{$order_details['go_pay_price']|getFormatNumber}
                        </if>
						<if condition="$order_details['total_score'] gt 0">
						+{pigcms{$order_details['total_score']|intval}{pigcms{$config.score_name}
						</if>
					   </div>
                       <php> if (($order_details['status'] == 2 || $order_details['status'] == 3 || $isShowRefund == 1) && $config['is_open_shop_refund'] == 1 && empty($is_periodic)) {</php>
                          <div class="shenshou" style='padding:5px 0px 0px 0px '>
                         <a type="button" href="{pigcms{:U('Shop/refund', array('order_id' => $order_details['order_id']))}">申请售后</a>
                         </div>
                     <php> } </php>
                    </div>
                </div>            
            </div>
             
              <!--周期购配送期 -->
			 <notempty name="periodic_deliver">
             <div class="cyclePurchase_box">
                 <div class="cyclePurchase_box_til">
                     <span class="p1 fl">配送期</span>
                     <span class="p2 fr"><label style="color: #2ecc71;">{pigcms{$now_deliver_num}</label>/{pigcms{$periodic_deliver_count}</span> 
                     <div class="clear"></div>
                 </div>
                 
                <div class="delivery-progress">
                    <div class="progress-detail">
                    <volist name="periodic_deliver" id="po">
                    <div class="item flex {pigcms{$po['front']}">
                        <div class="rel">
                            <span class="progress-line top">
                            </span>
                            <span class="txt">
                                {pigcms{$po['deliver_num']}
                            </span>
                            <span class="progress-line bottom">
                            </span>
                        </div>
                        <div class="flex flex_justify desc">
                            <div class="delivery-time">
                                <div>
                                    {pigcms{$po.text}
                                </div>
                                <div>
                                    {pigcms{$po.deliver_time_day}
                                </div>
                            </div>
                            <div class="delay-btn" <if condition="$po['delay_class'] eq 'delay_unable'">style="background: #ccc;"</if>>
                                <span class="{pigcms{$po.delay_class}" data-detail_id="{pigcms{$po.id}" data-type="{pigcms{$po.delay_type}">
                                    {pigcms{$po.delay_text}
                                </span>
                            </div>
                            <div class="check-detail">
                                <div>
                                    {pigcms{$po.update_time}
                                </div>
                                <div class="orderStatus red" data-goods_id="275">
                                    查看配送详情>  
                                </div>
                                <div class="status_model" style="display:none" data-id="">
                                    <section class="g_details">
                                        <div style="overflow:hidden">
                                            <span class="close">
                                                x
                                            </span>
                                        </div>
                                        <div class="orders_list">
                                            <ul>
                                                <volist name="po.info_list" id="v">
                                                <li>
                                                    <div class="time">
                                                        {pigcms{$v.time}
                                                    </div>
                                                    <div class="p18">
                                                        <div class="con">
                                                            <!-- <h2>订单生成成功</h2> 0：未配送 1 已配送 2 已送达  3 待自提 4 取消配送', -->
                                                            <p>
                                                                {pigcms{$v.text}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                </volist>
                                            </ul>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                    </volist>
                    
                    
                    
                </div>
                    
                     <div class="btn_xiala">
                        <img src="{pigcms{$static_path}mall/images/xia_la.png" />
                     </div>
                     
                      <div class="btn_shangla">
                        <img src="{pigcms{$static_path}mall/images/xia_la.png" style="transform:rotate(900deg)" />
                     </div>
                    
                 </div> 
                 
                 
             </div>
             <!--周期购配送期 end--> 
			 </notempty>
             <style>
                 
                 .cyclePurchase_box{  background: #fff; margin-bottom: 10px; padding: 10px;}
                 .cyclePurchase_box .cyclePurchase_box_til .p1{font-size: 15px;}
                 .rel{
                     position: relative;
                 } 
                 .g_details .cyclePurchase_box .top{
                   padding: 0;  
                 }
                .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
                  .flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;box-pack: center;}
                  .flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; align-items: center;box-align: center;}
                  .flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}
                  .flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;} 
           
                .delivery-progress .progress-line{ 
                    background: #2ecc71; 
                } 
                .delivery-progress .delay-btn{ 
                    background: #2ecc71; 
                } 

               .delivery-progress .item.is-delayed .delay-btn{
                    background: #ccc; 
                } 
                .delivery-progress .item.is-delayed .progress-line{
                    background: #ccc;
                }
               .delivery-progress .red, .delivery-progress .item.is-delivery .delivery-time{
                  color: #2ecc71;
               } 
                .delivery-progress .txt{
                   background: #2ecc71; 
                }
                 .delivery-progress .item.is-delayed .delay-btn .delay_submit{
                     background: #ccc; 
                 }
                 .delivery-progress .item.is-delayed .delay-btn{
                     padding: 4px 8px;
                 }
                .delivery-progress{
                    padding: 10px 5px;
                }
                .progress-detail{
                    overflow: hidden;
                }
                .btn_xiala,.btn_shangla{
                    text-align: center;
                    padding: 20px 0;
                    display: none;
                }
                .btn_xiala img,.btn_shangla img{
                    width:30px; 
                }
                .consume li{margin-right: 3px;}
             </style>
             
             <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
             <div class="coupon_mask cursor" style="display:none"></div>
             <!-- layer -->
             <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
             <script>
                 	$(".orderStatus").on("click",function(e){
                        e.stopPropagation();
                        e.preventDefault();
                        $(this).next().show();
                        $(".coupon_mask").show()
                    })
                    $(".close").on("click",function(e){
                        e.stopPropagation();
                        e.preventDefault();
                        $(".coupon_mask,.status_model").hide()
                    })

                    //周期购确认收货
                    $('.confirm_submit').click(function () {
                        var detail_id = $(this).data('detail_id');
                        layer.open({
                            title:['提示','background-color:#2ecc71;color:#fff;'],
                            content:'<p style="width:180px;color:#ff372d;">确认收货吗？</p>',
                            btn: ['确定','取消'],
                            yes: function(index){
                                layer.close(index);
                                $.post("{pigcms{:U('periodic_confirm')}",{id:detail_id},function(data){
                                    console.log('data',data);
                                    if(data.status==1){
                                        layer.open({
                                            content: data.info
                                            ,skin: 'msg'
                                            ,anim: 'up'
                                            ,time: 2
                                        });
                                        window.location.reload();
                                    }else{
                                        layer.open({
                                            content: data.info
                                            ,skin: 'msg'
                                            ,anim: 'up'
                                            ,time: 2
                                        });
                                    }
                                },'json')
                            }
                        });
                    });
                    
                    $('.delay_submit').click(function () {
                        var detail_id = $(this).data('detail_id');
                        var type = $(this).data('type');
                        if(type=='reply'){
                            var tip_str = '<p style="width:180px;color:#ff372d;">确认延期该周期购吗？延期后当前周期不会发货！</p>';
                        }else{
                            var tip_str = '<p style="width:180px;color:#ff372d;">确认取消该延期吗？取消后可恢复配送</p>';
                        }
                        layer.open({
                            title:['提示','background-color:#2ecc71;color:#fff;'],
                            content:tip_str,
                            btn: ['确定','取消'],
                            yes: function(index){
                                layer.close(index);
                                $.post("{pigcms{:U('periodic_delay')}",{id:detail_id,type:type},function(data){
                                    console.log('data',data);
                                    if(data.status==1){
                                        layer.open({
                                            content: data.info
                                            ,skin: 'msg'
                                            ,anim: 'up'
                                            ,time: 2
                                        });
                                        window.location.reload();
                                    }else{
                                        layer.open({
                                            content: data.info
                                            ,skin: 'msg'
                                            ,anim: 'up'
                                            ,time: 2
                                        });
                                    }
                                },'json')
                            }
                        });
                    });
                  
                    if($('.progress-detail .item').size() >3){  
                        $('.progress-detail').css('height',$('.progress-detail .item').height()*4);  
                        $('.btn_xiala').show(); 
                    }else{ 
                    }  
                    
                    $('.btn_xiala').click(function(){
                        $('.progress-detail').css('height','auto');
                        $('.btn_xiala img').css('transform', 'rotate(900deg)');
                        $('.btn_shangla').show();
                        $('.btn_xiala').hide();

                     }) 
                       
                    $('.btn_shangla').click(function(){
                        $('.progress-detail').css('height',$('.progress-detail .item').height()*4);
                        $('.btn_xiala img').css('transform', 'rotate(360deg)');
                        $('.btn_shangla').hide();
                        $('.btn_xiala').show();
                     })  
             </script>
             
            <php>if ($order_details['order_from'] != 6) {</php>
            <div class="infor">
                <ul>
                    <if condition="$order_details['is_pick_in_store'] eq 2">
                    <li class="clr first">
                        <div class="fl match">自提信息</div>
                    </li>
                    <li class="clr">
                        <div class="fl">配送方式</div>
                        <div class="fr">{pigcms{$order_details['deliver_str']}</div>
                    </li>
                    <li class="clr">
                        <div class="fl">自提地址</div>
                        <div class="p90">
                            <p>{pigcms{$order_details['address']}</p>
                        </div>
                    </li>
                    <li class="clr">
                        <div class="fl">自提二维码</div>
                        <div class="p90">
                            <if condition="!$un_group_team_pick_qrcode">
								<p id="see_storestaff_qrcode">查看二维码</p>
							<else/>
								<p>未成团无法查看</p>
							</if>
                        </div>
                    </li>
                    <else />
                    <li class="clr first">
                        <div class="fl match">配送信息</div>
                    </li>
                    <li class="clr">
                        <div class="fl">配送方式</div>
                        <div class="fr">{pigcms{$order_details['deliver_str']}</div>
                    </li>
                    <if condition="$order_details['freight_charge'] gt 0">
                        <li class="clr">
                            <div class="fl">配送费</div>
                            <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['freight_charge']}</div>
                        </li>
                    </if>
                    <?php if (in_array($order_details['is_pick_in_store'],array(0,1))): ?>
                    <li class="clr">
                        <div class="fl">期望时间</div>
                        <div class="fr">{pigcms{$order_details['expect_use_time']}</div>
                    </li>
                    <?php endif ?>
                    <li class="clr">
                        <div class="fl">收货信息</div>
                        <div class="p90">
                            <p>{pigcms{$order_details['address']}</p>
                            <p>{pigcms{$order_details['username']} {pigcms{$order_details['userphone']}</p>
                        </div>
                    </li>
                    <if condition="$order_details['is_pick_in_store'] eq 3 AND $order_details['express_name'] AND $order_details['express_number']">
                        <li class="clr">
                            <div class="fl">快递详情</div>
                            <dl class="kd_dl kd_dls">
                                <dd>
                                    <?php if($order_details['jd_order_id']>0){ ?>
                                        <h2 class="endt">{pigcms{$order_details['express_name']}
                                            <volist name="jd_express" id="jdexp">
                                                    <a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_details['express_code']}&postid={pigcms{$jdexp}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" >{pigcms{$jdexp}</a>
                                            </volist>
                                            <?php if(count($jd_express)<2){ ?>
                                                <p><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_details['express_code']}&postid={pigcms{$order_details['jd_order_id']}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" >查看物流 ></a></p>
                                            <?php }else{ ?>
                                                <p>订单已被分单，请逐个点击单号查看物流</p>
                                            <?php } ?>
                                        </h2>
                                    <?php }else{ ?>
                                        <h2 class="endt">店员已发货：{pigcms{$order_details['express_name']} <a>{pigcms{$order_details['express_number']}</a></h2>
                                        <if condition="$config['kdniao_appkey']">
											<p><a href="{pigcms{:U('express_new')}&type={pigcms{$order_details['express_code']}&postid={pigcms{$order_details['express_number']}" target="_blank" >查看实时物流 ></a></p>
                                        <else/>
											<!--<p><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_details['express_code']}&postid={pigcms{$order_details['express_number']}&callbackurl=<?php /*echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);*/?>" target="_blank" >查看物流 ></a></p>-->
                                            <p><a href="https://m.kuaidi100.com/app/query/?com={pigcms{$order_details['express_code']}&nu={pigcms{$order_details['express_number']}" target="_blank" >查看实时物流 ></a></p>
                                        </if>
                                    <?php } ?>
                                </dd>
                            </dl>
                        </li>
                    </if>
                    <if condition="$order_details['deliver_info']">
                    <li class="clr">
                        <div class="fl">配送详情</div>
                        <dl class="kd_dl kd_dls">
                            <dd>
                                <h2 class="endt">配送员：{pigcms{$order_details['deliver_info']['name']} <a href="tel:{pigcms{$order_details['deliver_info']['phone']}">{pigcms{$order_details['deliver_info']['phone']}</a></h2>
                            </dd>
                        </dl> 
                    </li>
                    </if>
                    </if>
                </ul>
            </div>
            <php>}</php> 
            
            <div class="infor">
                <ul>
                    <li class="clr first">
                        <div class="fl book">订单信息</div>
                    </li>
                    <li class="clr">
                        <div class="fl">订单编号</div>
                        <div class="fr">{pigcms{$order_details['real_orderid']}</div>
                    </li>
                    <li class="clr">
                        <div class="fl">下单时间</div>
                        <div class="fr">{pigcms{$order_details['create_time']}</div>
                    </li>
                    <li class="clr">
                        <div class="fl">备注信息</div>
                        <div class="fr">{pigcms{$order_details['note']}</div>
                    </li>
                    <if condition="$order_details['cue_field']">
                    <volist name="order_details['cue_field']" id="row">
                    <li class="clr">
                        <div class="fl">{pigcms{$row['title']}</div>
                        <div class="fr">{pigcms{$row['txt']}</div>
                    </li>
                    </volist>
                    </if>
                </ul>
            </div>
             <if condition="$order_details['paid'] eq 1">
             <div class="infor">
                 <ul>
        	         <li class="clr first">
        	             <div class="fl branch">支付信息</div>
        	         </li>
                     <li class="clr">
                         <div class="fl">支付时间</div>
                         <div class="fr">{pigcms{$order_details['pay_time']}</div>
                     </li>
                     <li class="clr">
                         <div class="fl">支付方式</div>
                         <div class="fr">{pigcms{$order_details['pay_type_str']}</div>
                     </li>
        	         <li class="clr">
        	             <div class="fl">应收总额</div>
        	             <div class="p90">
                             <!--预售-->
                             <if condition="$order_details['book_id'] gt 0">
                                 <p class="e2c">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['book_all_pay']|getFormatNumber}</p>
                                 <else />
                                 <if condition="$order_details['change_price'] gt 0">
                                 <p class="e2c">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['price']}</p>
                                 <p class="kdsize">（修改前：{pigcms{$config['Currency_symbol']}{pigcms{$order_details['change_price']|getFormatNumber}，备注：{pigcms{$order_details['change_price_reason']|default="无"}）</p>
                                 <else />
                                 <p class="e2c">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['go_pay_price']}</p>
                                 </if>
                             </if>
        	             </div>
        	         </li>
                     <if condition="$order_details['book_id'] eq 0">
        	         <if condition="$order_details['card_discount'] neq 0 AND $order_details['card_discount'] neq 10">
        	         <li class="clr">
        	             <div class="fl">商家会员卡折扣</div>
        	             <div class="p90">
                            <p class="e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['minus_card_discount']}（{pigcms{$order_details['card_discount']}折）</p>
                            <p class="kdsize">（备注：{pigcms{$store['freight_alias']|default="配送费"}不参加折扣）</p>
                         </div>
        	         </li>
        	         </if>
                     </if>
        	         <if condition="$order_details['coupon_price'] gt 0">
        	         <li class="clr">
        	             <div class="fl">平台优惠券</div>
        	             <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['coupon_price']|getFormatNumber}</div>
        	         </li>
        	         </if>
        	         <if condition="$order_details['card_price'] gt 0">
        	         <li class="clr">
        	             <div class="fl">商家优惠券</div>
        	             <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['card_price']|getFormatNumber}</div>
        	         </li>
        	         </if>
                     <gt name='order_details.use_ecard_price' value='0'>
                        <li class="clr">
                            <div class="fl">{pigcms{$config.ecard_alias_name}支付</div>
                            <div class="p90">
                                <p class="e2c">
                                    -{pigcms{$config['Currency_symbol']}{pigcms{$order_details.use_ecard_price}    
                                </p>
                            </div>
                        </li>
                    </gt>
        	         <if condition="$order_details['score_deducte'] gt 0">
                     <li class="clr">
                         <div class="fl">{pigcms{$config.score_name}抵扣</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['score_deducte']|getFormatNumber}（使用{pigcms{$order_details['score_used_count']|getFormatNumber}{pigcms{$config.score_name}）</div>
                     </li>
                     </if>
                     <if condition="$order_details['book_id'] eq 0">
                         <if condition="$order_details['card_give_money'] gt 0">
                             <li class="clr">
                                 <div class="fl">会员卡赠送余额支付</div>
                                 <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['card_give_money']|getFormatNumber}</div>
                             </li>
                         </if>
                         <if condition="$order_details['merchant_balance'] gt 0">
                             <li class="clr">
                                 <div class="fl">商家余额支付</div>
                                 <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['merchant_balance']|getFormatNumber}</div>
                             </li>
                         </if>
                         <if condition="$order_details['balance_pay'] gt 0">
                             <li class="clr">
                                 <div class="fl">平台余额支付</div>
                                 <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['balance_pay']|getFormatNumber}</div>
                             </li>
                         </if>
                         <else />
                         <if condition="$order_details['merchant_balance'] gt 0">
                             <li class="clr">
                                 <div class="fl">商家余额支付</div>
                                 <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['bargin_price']+$order_details['rest_price']|getFormatNumber}</div>
                             </li>
                         </if>
                         <if condition="$order_details['balance_pay'] gt 0">
                             <li class="clr">
                                 <div class="fl">平台余额支付</div>
                                 <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['bargin_price']+$order_details['rest_price']|getFormatNumber}</div>
                             </li>
                         </if>
                     </if>

                     <if condition="$order_details['payment_money'] gt 0">
                     <li class="clr">
                         <div class="fl">{pigcms{$order_details['pay_type_str']}</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['payment_money']|getFormatNumber}</div>
                     </li>
                     </if>
                 </ul>
             </div>
            </if>
			
			<if condition="$order_details['book_id'] gt '0'">
			<div class="infor fa_infor">
			    <ul>
			        <li class="clr first">
			            <div class="fl branch">发货时间</div>
						<div class="fr">
                            <if condition="$book_act.send_goods_type eq '1'">
                                {pigcms{$book_act['send_goods_date']|date="Y-m-d",###}
                            <else/>
                                支付尾款后{pigcms{$book_act['send_goods_day']}天发货
                            </if>
                        </div>
			        </li>
			        <li class="clr cur">
			            <div class="fl">阶段一：定金
                            <if condition="$order_details['book_status'] eq '0'">
                                (待支付)
                            <else/>
                                (已支付)
                            </if>
                        </div>
			            <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['bargin_price']|getFormatNumber}
                          <if condition="$order_details['book_discount_price'] gt 0">
                              抵 {pigcms{$config['Currency_symbol']}{pigcms{$order_details['book_discount_price']|getFormatNumber}
                          </if>
                        </div>
			        </li>
					<li class="clr">
					    <div class="fl">阶段二：尾款
                        <if condition="$order_details['book_status'] eq 2">
                            (已支付)
                        </if>
                        </div>
					    <div class="fr">{pigcms{$config['Currency_symbol']}{pigcms{$order_details['rest_price']|getFormatNumber}</div> 
					</li> 
			        </if>
			    </ul>
			</div>
	        </if>


            <if condition="$book_tips neq ''">
                <div class="ad_tips">
                    <if condition="$is_tips eq ''">
                        {pigcms{$book_tips}
                        <else />
                        <i style="color: red">订单已过期</i>
                    </if>
                </div>
            </if>
            <div class="consume consumes">
                <ul class="clr">
                    <if condition="$order_details['status'] lt 3 OR ($order_details['paid'] eq 1 AND $order_details['status'] eq 5) OR ($order_details['paid'] eq 0 AND ($order_details['status'] eq 7 OR $order_details['status'] eq 9))">
                        <if condition="$order_details['paid'] eq 0 && (empty($order_details['pay_type_str']) || $order_details['pay_type_str'] == '未支付')">
							<li class="fl firmly order-pay" data-order_id="{pigcms{$order_details['order_id']}">支付订单</li>
                        </if>
                        <php> if($config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_refund'] == 0 && $order['is_own'] == 2 && $order['pay_type'] == 'weixin'){</php>
                        	<li class="fr zlyd">该订单不能退款，请联系商家 【{pigcms{$now_merchant.name}】</li>
                        <php>}else{</php>
                            <if condition="$order_details['paid'] eq 0 AND empty($cancel_order) AND $order_details['book_id'] eq 0">
                            <li class="fr replace" data-url="{pigcms{:U('Shop/orderdel', array('order_id' => $order_details['order_id']))}">取消订单</li>
                        <elseif condition="$order_details['paid'] eq 0 AND empty($cancel_order) AND $order_details['book_id'] gt 0"/>
                            <li class="fr replace book" data-url="{pigcms{:U('Shop/orderdel', array('order_id' => $order_details['order_id']))}">取消订单</li>
                        <elseif condition="$order_details['paid'] eq 1 AND $order_details['status'] eq 1 AND $order_details['is_pick_in_store'] eq 3  AND empty($cancel_order)" />
                            <li class="fl firmly finishorder" data-url="{pigcms{:U('Shop/finishOrder', array('order_id' => $order_details['order_id']))}">确认收货</li>
                                <?php if($order_details['cancle_apply_count']==2 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace full_cancle " data-url="">取消订单</li>
                                <?php }elseif($order_details['cancle_apply_count']==1 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id'],'apply'=>1))}">再次申请退款</li>
                                <?php }elseif($order_details['cancle_apply_count']==0 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id'],'apply'=>1))}">申请退款</li>
                                <?php }elseif($order_details['order_status'] < 1){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id']))}">取消订单</li>
                                <?php } ?> 
                        <elseif condition="$order_details['paid'] eq 1 AND $order_details['status'] lt 2 AND empty($cancel_order)" />
                                <?php if($order_details['cancle_apply_count']==2 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace full_cancle" data-url="">取消订单</li>
                                <?php }elseif($order_details['cancle_apply_count']==1 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id'],'apply'=>1))}">再次申请退款</li>
                                <?php }elseif($order_details['cancle_apply_count']==0 && $order_details['order_status'] == '1'){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id'],'apply'=>1))}">申请退款</li>
                                <?php }elseif($order_details['order_status'] < 1){?>
                                    <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id']))}">取消订单</li>
                                <?php } ?>
                        <elseif condition="$order_details['paid'] eq 1 AND $order_details['status'] eq 5  AND empty($cancel_order)" />
                            <li class="fr replace" data-url="{pigcms{:U('My/shop_order_refund', array('order_id' => $order_details['order_id']))}">退款</li>
                        </if>
                        <php>}</php>
                        <if condition="$order_details['status'] eq 2">
                            <li class="fl replace" data-url="{pigcms{:U('My/mall_feedback',array('order_id' => $order_details['order_id']))}">去评价</li>
                            <li class="fr zlyd" data-url="{pigcms{:U('Mall/confirm_order', array('order_id' => $order_details['order_id'], 'store_id' => $store['store_id'],'params'=>onlybuy))}">再来一单</li>
                        </if>
                    <else/>
                        <eq name="order_details.status" value="11">
                             <li class="fr replace cancel_apply" data-url="{pigcms{:U('Mall/cancel_apply', array('order_id' => $order_details['order_id'], 'store_id' => $store['store_id'],'params'=>onlybuy))}">退款申请中</li>
                        <else/>
                            <if condition="$order_details.status eq '4'">
                            <li class="fr replace_ccc" data-url="" style="float: left;">退款成功</li>
                            </if>
                            <li class="fr zlyd" data-url="{pigcms{:U('Mall/confirm_order', array('order_id' => $order_details['order_id'], 'store_id' => $store['store_id'],'params'=>onlybuy))}">再来一单</li>
                        </eq>
                       
                    </if>
                    <if condition="$order_details.book_status eq '1' AND $order_details.status lt '2'">
                        <if condition="$is_tips eq ''">
                            <if condition="$is_click eq 0">
                                <!--不到时间不可支付-->
                                <li class="cant-pay">付尾款{pigcms{$config['Currency_symbol']}{pigcms{$order_details.rest_price}</li>
                                <else/>
                                <li class="fr" data-url="{pigcms{$book_pay_url}">付尾款{pigcms{$config['Currency_symbol']}{pigcms{$order_details.rest_price}</li>
                            </if>
                        </if>
                    </if>
                </ul>
            </div>
        </section>
		<if condition="$config.open_share_lottery eq 1 AND $is_weixin_browser AND $order_details.paid  eq 1 AND $lottery_info.status neq 1">
			<div class="float-open" id="float-open"  <if condition=" ( $lottery.status eq 0) OR  ($order_details.share_status eq 0 AND $config.lottery_must_share eq 1)">style="display:none;"</if> ><a class="open-btn" href="javascript:void(0);"><img src="{pigcms{$static_path}shop/images/lottery.png"></a></div>
		<elseif condition="$config.open_share_lottery eq 0  AND $config.share_coupon eq 1 AND $is_weixin_browser AND $config.get_coupon_must_share eq 0 AND $order_details.paid  eq 1 AND !empty($share_coupon)" />
			<div class="float-open" id="send_friend" ><a class="open-btn" href="javascript:void(0);"><img src="{pigcms{$static_path}shop/images/share_coupon.png"></a></div>
		</if>
		
		
		 <if condition="$config.open_share_lottery eq 0 AND $order_details.paid  eq 1 AND $is_weixin_browser  AND (($order_details['share_status'] eq 0 AND $config.get_coupon_must_share eq 1) OR $config.get_coupon_must_share eq 0) AND $config.share_coupon eq 1 AND $config.share_coupon_num gt 0 AND $order_details.status neq 4 AND !empty($share_coupon) AND !empty($share_coupon)">
            <div class="coupon_share mongolia_layer coupon_my"  <if condition="$config.get_coupon_must_share eq 0 OR $order_details.show_lottery_first gt 0">style="display:none"</if>></div>
            <div class="coupon_share Coupon coupon_my afters"  <if condition="$config.get_coupon_must_share eq 0">style="display:none"</if>>
                <span class="delate_money rg"></span>
				<h3>恭喜您获得{pigcms{$config.share_coupon_num}张优惠劵</h3>
                <p>赶快把优惠劵分享给大伙伴们抢吧,分享后您自己可获得{pigcms{$config.share_coupon_get_num}张优惠劵</p>
                <button class="btn" id="share_btn">立即分享</button>
            </div>
         </if>
	
		  <if condition="$config.open_share_lottery eq 0 AND $order_details.paid  eq 1 AND $is_weixin_browser AND ($order_details['share_status'] eq 1 OR $config['get_coupon_must_share'] eq 0)  AND $config.share_coupon eq 1 AND $config.share_coupon_num gt 0 AND $order_details.status neq 4 AND !empty($share_coupon)">
            <div class="coupon_share mongolia_layer" ></div>
            <div class="coupon_share Coupon afters" >
                <span class="delate_money rg"></span>
				<h3>恭喜您获得{pigcms{$config.share_coupon_get_num}张平台优惠劵</h3>
                <p style="text-align: center;">已存入您的账号</p>
                <button class="btn" id="use_right_now">立即查看</button>
            </div>
			
         </if>
            <!-- <div id="come_quick">
                
            </div> -->
        <!-- 浮动tabble -->
        <include file="floatTabble"/>
    </body>

	<script>
			window.shareData = {
				"moduleName":"Shop",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else />{pigcms{$config.site_logo}</if>", 
				"sendFriendLink": "<if condition="$config['share_coupon'] eq 1 AND ($config['open_share_lottery'] eq 0 OR empty($lottery))">{pigcms{$config.site_url}{pigcms{:U('Share_lottery/share_coupon',array('order_id'=>$order_details['order_id'],'type'=>'shop'))}<else />{pigcms{$config.site_url}{pigcms{:U('Shop/index')}&shop-id={pigcms{$store.store_id}</if>",
				"tTitle": "<if condition="$config['share_coupon'] eq 1 AND ($config['open_share_lottery'] eq 0 OR empty($lottery))">{pigcms{$config.share_coupon_title}<else />{pigcms{$config.shop_alias_name} - {pigcms{$config.site_name}</if>",
				"tContent": "<if condition="$config['share_coupon'] eq 1 AND ($config['open_share_lottery'] eq 0 OR empty($lottery))">{pigcms{$config.share_coupon_num}张优惠劵，快来抢啊！<else />{pigcms{$config.seo_description}</if>"
			};
			<if condition="$is_weixin_browser OR 1==1 ">
			function call_back(){
				<if condition="$config.open_share_lottery eq 0 AND $config.share_coupon eq 1 AND $order_details.paid  eq 1">
					$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'shop',order_id: {pigcms{$order_details['order_id']}}, function(data, textStatus, xhr) {
						if(!data.status){
							layer.open({content:data.info,btn: ['确定']});
						}
						window.location.reload();
					},'json');
				
				</if>
				
				<if condition="$config.open_share_lottery eq 1 AND $order_details.paid  eq 1" >
					$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'shop',order_id: {pigcms{$order_details['order_id']}}, function(data, textStatus, xhr) {
						if(data.status){
							$('#float-open').show(); 
						}
					},'json');
				</if>
			}
			</if>
			
			<if condition="$config.open_share_lottery eq 0 AND $is_weixin_browser AND $order_details.paid  eq 1  AND $config.share_coupon eq 1 AND $order['share_status'] eq 0 AND $config.get_coupon_must_share eq 0">
				$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'shop',order_id: {pigcms{$order_details['order_id']}}, function(data, textStatus, xhr) {
					if(!data.status){
						//layer.open({content:data.info,btn: ['确定']});
					}
					//window.location.reload();
				},'json');
				
			</if>
			
			<if condition="$config.open_share_lottery eq 1 AND $is_weixin_browser AND $order_details.paid  eq 1  AND $order['share_status'] eq 0 AND $config.lottery_must_share eq 0">
					$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'store',order_id: {pigcms{$order['order_id']}}, function(data, textStatus, xhr) {
						if(data.status){
							$('#float-open').show(); 
						}
						//window.location.reload();
					},'json');
				
				</if>
			$('.delate_money').click(function(){
                $('.mongolia_layer').hide();
                $('.Coupon').hide();
			})
			$('#share_btn').click(function(){
                $('.mongolia_layer').hide();
                $('.Coupon').hide();
				$('.t_remind').css('min_height','43px');
				$('.t_remind').css('height','43px');
				$('.t_remind').show();
				$(".public").each(function(){
					$(this).css("top",$(".t_remind").height())
				});
				$(".defrayal").each(function(){
					$(this).css("top",$(".t_remind").height()+44);
				});
				$(".h105").each(function(){
					$(this).css("height",105 + $(".t_remind").height());
				})
			})
			
			$('#use_right_now').click(function(){
				window.location.href='{pigcms{:U('Share_lottery/my_get_coupon',array('order_id'=>$order_details['order_id'],'type'=>'shop'))}';
			})

          
	</script>
	<if condition="$config['open_share_lottery'] eq 1 AND !empty($lottery) AND $order_details.paid  eq 1">
	{pigcms{$shareScript}
	<elseif condition="$config['share_coupon'] eq 1 AND $config['open_share_lottery'] eq 0  AND $order_details.paid  eq 1" />
	{pigcms{$shareScript}
	<else />
	{pigcms{$hideScript}
	</if>
    <script src="{pigcms{$static_public}js/jquery.qrcode.min.js"></script>
    <script type="text/javascript">
    $(function(){
        //查看自提二维码
        $('#see_storestaff_qrcode').click(function(){
            var qrcode_width = $(window).width()*0.6 > 256 ? 256 : $(window).width()*0.6;
            layer.open({
                title:['自提二维码','background-color:#8DCE16;color:#fff;'],
                content:'生成的二维码仅限提供给商家店铺员工扫描验证消费使用！<br/><br/><div id="qrcode"></div>',
                success:function(){
                    $('#qrcode').qrcode({
                        width:qrcode_width,
                        height:qrcode_width,
                        text:"1{pigcms{$order_details.real_orderid|default=0}"
                    });
                }
            });
            $('.layermbox0 .layermchild').css({width:qrcode_width+30+'px','max-width':qrcode_width+30+'px'});
        });
    });
    </script>
	<div class="pageSliderHide"></div>
</html>
<style>
	.lottery_before{
		color:#999;
		border-color:#999;
	}
	.lottery{
		color:red;
		border-color:red;
	}
	.float-open{
		
		right: 0px;
		height: 60;

		padding: 4px 4px 4px 6px;
		width: 60px;
		z-index: 99;
		top: 50%;
		position: fixed;
		
	}
</style>
<script>
$(document).ready(function(){
	$('.consumes ul li').click(function(){
		// 判断预售时候取消订单弹窗
		if ($(this).hasClass('book')) {
			var url = $(this).data('url');
			layer.open({
			    content: '取消支付定金将不退回？'
			    ,btn: ['确定', '取消']
			    ,yes: function(index){  
			        if(url){
			        	location.href = url;
			        }
			    }
			  });
			  
		}else  if ($(this).hasClass('finishorder')) {
            var postUrl = $(this).data('url');
            layer.open({
                content: '您确定已经收到货物了？'
                ,btn: ['确定收货', '取消']
                ,yes: function(index){
                    $.post(postUrl, function(res) {
                        layer.close(index);
                        if (res.errcode == 1) {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                              });
                        } else {
                            location.reload();
                            layer.close(index);
                        }
                    }, 'json');
                }
              });
        } else if($(this).hasClass('cancel_apply')){
            var postUrl = $(this).data('url');
            layer.open({
                content: '是否撤回此次退款申请？'
                ,btn: ['确定撤回', '取消']
                ,yes: function(index){
                    $.post(postUrl, function(res) {
                        layer.close(index);
                        if (res.errcode == 1) {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                              });
                        } else {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                              });
                            layer.close(index);
                            location.reload();
                            
                        }
                    }, 'json');
                }
              });
        }else if($(this).hasClass('full_cancle')){
            layer.open({
                        content:'抱歉，最多只能申请两次退款！'
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                      });
        }else {
            var url = $(this).data('url');
			if(url){
				location.href = url;
			}
        }
    });
	$('#send_friend').click(function(){
		$('.coupon_my').show();
	})
	
	if(motify.checkWeixinApp()){
	
		$('.t_remind').remove()  
		$('#goBackUrl').hide()  
		$(".public").each(function(){
			$(this).css("top",0)
		});
		$(".defrayal").each(function(){
			$(this).css("top",44);
		});
		$(".h105").each(function(){
			$(this).css("height",105 );
		})
	}
	
	$('#float-open').click(function(){
		$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'shop',order_id: {pigcms{$order_details['order_id']}}, function(data, textStatus, xhr) {
			if(data.status){
					window.location.href=data.url;
			}else{
					layer.open({content:data.info,btn: ['确定'],
					 yes: function(index){
						  location.reload();
						  layer.close(index); 
					}});
			}
		},'json');
	});
	
    $(document).on('click','.phone',function(event){
        if($(this).attr('data-phone')){
            var tmpPhone = $(this).attr('data-phone').split(' ');
            var msg_dom = '<div class="msg-bg"></div>';
            msg_dom+= '<div id="msg" class="msg-doc msg-option">';
            msg_dom+= '<div class="msg-bd">'+($(this).data('phonetitle') ? $(this).data('phonetitle') : '拨打电话')+'</div>';
            for(var i in tmpPhone){
                msg_dom+= '<div class="msg-option-btns"><a class="btn msg-btn" href="tel:'+tmpPhone[i]+'">'+(tmpPhone.length == 1 && $(this).data('phonetip') ? $(this).data('phonetip') : tmpPhone[i])+'</a></div>';
            }
            msg_dom+= '     <button class="btn msg-btn-cancel" type="button">取消</button>';
            msg_dom+= '</div>'; 
            $('body').append(msg_dom);
        }
        event.stopPropagation();
    });
    $(document).on('click','.msg-btn-cancel,.msg-bg',function(){
        $('.msg-doc,.msg-bg').remove();
    });
	
	if(typeof(wxSdkLoad) != "undefined"){
		wx.ready(function(){
			if(window.__wxjs_environment === 'miniprogram'){
				go_shop_bind(1);
			}else{
				go_shop_bind(1);
			}
		});
	}else{
		go_shop_bind(1);
	}
});

function go_shop_bind(type){
	if(type == 1){
		$('#go_shop').click(function(){
			if($(this).data('is_mult_class') == '1'){
				location.href = "{pigcms{:U('Mall/store')}&store_id={pigcms{$store['store_id']}&show_own=1";
			}else{
				location.href = "{pigcms{:U('Mall/index')}#shop-{pigcms{$store['store_id']}";
			}
		});
	}else{
		$('#go_shop').click(function(){
			wx.miniProgram.navigateTo({url: "/pages/shop_new/shopDetail/shopDetail?store_id={pigcms{$store['store_id']}"});
		});
	}
}

$(".public").each(function(){
    $(this).css("top",$(".t_remind").height())
});
$(".defrayal").each(function(){
    $(this).css("top",$(".t_remind").height()+44);
});
$(".h105").each(function(){
    $(this).css("height",105 + $(".t_remind").height());
})


$(function(){
    hideWebview();
    setTimeout(function(){
        hideWebview();
    },500);
});
function hideWebview(){
    if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
        var reg = /versioncode=(\d+),/;
        var arr = reg.exec(navigator.userAgent.toLowerCase());
        if(arr == null){
            
        }else{
            var version = parseInt(arr[1]);
            if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
				if(version >= 1400){
					$('#goBackUrl').remove();
				}
                $('#goBackUrl').click(function(){
                    iosCommon.iosFunction('webViewGoBack', {name:'webViewGoBack'});
                    return false;
                });
            }else{
                if(version >= 100){
                    window.lifepasslogin.hideWebViewHeader(false);
                }
                $('#goBackUrl').click(function(){
                    window.lifepasslogin.webViewGoBack();
                    return false;
                });
            }
        }
    }
}


function appbackmonitor(){
	if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
        iosCommon.iosFunction('webViewGoBack', {name:'webViewGoBack'});
	}else{
		window.lifepasslogin.webViewGoBack();
	}
}
$('.order-pay').click(function(){
	var order_id = $(this).data('order_id');
	if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
		wx.miniProgram.navigateTo({url:'/pages/pay/index?order_id='+ order_id +'&type=mall'});
	}else{
		location.href = "{pigcms{$config.site_url}{pigcms{:D('House_village')->base_url}pages/shop_new/confirmOrder/pay?order_id=" + order_id + "&type=mall";
	}
});
</script>