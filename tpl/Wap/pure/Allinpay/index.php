<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title><if condition="!$now_order">银行卡列表<else/>选择银行卡支付</if></title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/city_car.css?216"/>
		<link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
		<link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112222" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			var delete_url = "{pigcms{:U('delete')}";
			var order_sms_url = "{pigcms{:U('order_sms')}";
			var order_id = "{pigcms{$now_order.allinpay_rand_id}";
			var order_money = "{pigcms{$now_order.order_price}";
            var check_url = "{pigcms{:U('check_discount')}";
            var discountss = "{pigcms{$discountss}";
            var query_url = "{pigcms{:U('query_discount')}";
			var currency_symbol = "{pigcms{$config.Currency_symbol}";
		</script>

		<style>
            ul{    list-style: none;}
            .weui-btn_primary{
                background-color: #f8521d;
            }
            .weui-btn_primary:not(.weui-btn_disabled):visited{
                background-color: #f8521d;
            }
            .weui-btn_primary:not(.weui-btn_disabled):active{
                background-color:rgba(249, 87, 32, 0.6) !important;
            }
			.cursor{cursor: pointer}
			.rel{position:relative}
			.border-t:before {
				top: 0;
				border-top: 1px solid #e5e5e5;
				-webkit-transform-origin: 0 0;
				transform-origin: 0 0;
				-webkit-transform: scaleY(.5);
				transform: scaleY(.5);
				content: " ";
				position: absolute;
				left: 0;
				right: 0;
				height: 1px;
				color: #e5e5e5;
				z-index: 2;
			}
			.weui-cells_radio .weui-icon-checked:before {
				display: block;
				content: '\EA01';
				color: #c9c9c9;
				font-size: 23px;
			}
			.weui-cells_radio .weui-check:checked+.weui-icon-checked:before {
				display: block;
				content: '\EA06';
				color: #e4393c;
				font-size: 23px;
			}
           .mast_del,.mast_dell{
			   border: 1px solid #e4393c;
			   font-size: 12px;
			   border-radius: 100px;
			   padding: 2px 15px 2px 7px;
			   color: #e4393c;
		   }
			.center{text-align:center}
			.h45{height:45px;line-height:45px}
           .model1 .left_title{
               color: #fff;
               font-size: 13px;
               background: #e4393c;
               border-radius: 100px;
               padding: 3px 10px;
           }
			.model1,.model2{
				position: fixed;
				left: 0px;
				right: 0px;
				bottom: -300px;
				height: 300px;
				transition: all .5s;
				z-index:20;
				background: #fff;
				overflow: hidden;
			}
			.model1.active,.model2.active{
				bottom: 0px;
			}
  .showModel1:after{
	  margin-top: -4px !important;
	  right: 8px !important;
	  border-color: #e4393c !important;
  }
			 .band_car{
				 position:fixed;left:0px;right:0px;bottom:0px;
				 z-index:10;

			 }
			.pay:after{content:none !important;}
			.pay{	background: -webkit-linear-gradient(left, #F13310, #FA5A21); /* Safari 5.1 - 6.0 */
				background: -o-linear-gradient(right, #F13310, #FA5A21); /* Opera 11.1 - 12.0 */
				background: -moz-linear-gradient(right, #F13310, #FA5A21); /* Firefox 3.6 - 15 */
				background: linear-gradient(to right, #F13310, #FA5A21); /* 标准的语法 */
				color:#fff;
				border-radius: 0px;
			}
			.model{
				position: fixed;
				top: 0px;
				left: 0px;
				background: rgba(0,0,0,0.7);
				z-index: 2;
				width: 100%;
				bottom: 0px;
			}
			.radio{
				left: 0px;
				width: 100%;
				height: 100%;
				opacity: 0;
				z-index: -1;
			}
			.add_card{
				width: 61%;
				color: #e4393c;
				border-radius: 100px;
				border: 1px solid #e4393c;
				background-color: transparent;
				line-height: 35px;
				margin: auto;
				margin-top: 40px;
			}
		</style>
        <style>
            .discounts{
                background-color: white;
                margin-top: 10px;
                width:100%;
                position:fixed;
            }
            .discount_row{
                margin: 10px;
                padding: 5px;
                border: 1px solid gray;

            }
            .discount_title{
                font-size: 14px;
                padding-left: 5px;
            }
            .discount_info{
                font-size: 12px;
                padding-left: 5px;
            }
            .sign{
                text-align: right;
                color: #999999;
            }
            .sign::after{
                content: " ";
                display: inline-block;
                height: 6px;
                width: 6px;
                border-width: 2px 2px 0 0;
                border-color: #C8C8CD;
                border-style: solid;
                -webkit-transform: matrix(0.71, 0.71, -0.71, 0.71, 0, 0);
                transform: matrix(0.71, 0.71, -0.71, 0.71, 0, 0);
                position: absolute;
                margin-top: -25px;
                right: 8%;
            }
        </style>
	</head>
	<body >
      <div style="padding-bottom: 80px;">
		<if condition="$_GET['now_order']">
			<p class="page__desc mt40" style="margin-top:20px!important;">订单名称：{pigcms{$now_order.order_name}</p>
			<p class="page__desc mt40" style="margin-top:5px!important;">	订单金额：{pigcms{$config['Currency_symbol']}{pigcms{$now_order.order_price|floatval=###}</p>
			<p class="page__desc mt40" style="margin-top:5px!important;">订单流水号：{pigcms{$now_order.order_id}</p>
		</if>
		<div class="weui-cells weui-cells_radio" style="margin-top:0px;    ">
			<volist name="card_list" id="vo">
				<div class="weui-cell weui-check__label c_{pigcms{$vo.card_id} card_row" data-card_id="{pigcms{$vo.card_id}">
					<div class="weui-cell__bd">
						<h4 class="weui-media-box__title"> {pigcms{$vo.bankname}</h4>
						<p class="weui-media-box__desc"> <span><if condition="$vo['creditFlag'] eq '00'">储蓄卡<else/>信用卡</if></span>{pigcms{$vo.cardNo|substr=-4} <span class="unbind" data-card_id="{pigcms{$vo.cardNo}" data-bind_id="{pigcms{$vo.card_id}" style="margin-left: 15px;border-radius: 5px;padding: 2px;font-size: 12px;">[解除绑定]</span></p>
					</div>
					<div class="weui-cell_access my_del">
						<div class="weui-cell__ft showModel1 " data-bind_id="{pigcms{$vo.card_id}" style="padding:0px">
							<span class="mast_del ">请选择优惠</span>
						</div>
					</div>

					<div class="weui-cell__ft rel">
						<input type="radio" class="weui-check radio p_{pigcms{$vo.card_id}" name="radio1" >
						<span class="weui-icon-checked"></span>
					</div>
				</div>
			</volist>
		</div>
        <div class="weui-btn-area">
            <div data-href="{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}" style="color: #e4393c;border-radius: 100px;border: 1px solid #e4393c;background-color: transparent;" class="weui-btn   cursor add_card"  >添加银行卡</div>
        </div>
		<div class="page msg_success js_show" <if condition="!empty($card_list)">style="display:none;"</if>>
			<div class="weui-msg">
				<div class="weui-msg__icon-area"><i class="weui-icon-info weui-icon_msg"></i></div>
				<div class="weui-msg__text-area">
					<h2 class="weui-msg__title">银行卡列表为空</h2>
					<p class="weui-msg__desc">请点击下方按钮添加银行卡</p>
				</div>
				<div class="weui-msg__opr-area">
					<p class="weui-btn-area">
						<a href="javascript:void(0);" data-href="{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}" class="weui-btn weui-btn_primary card_add">添加银行卡</a>
					</p>
				</div>
			</div>
		</div>
		<div class="js_dialog" id="iosDialog1" style="display:none;">
            <div class="weui-mask weui-mask--visible"></div>
            <div class="weui-dialog weui-dialog--visible">
                <div class="weui-dialog__hd"><strong class="weui-dialog__title">确认提示</strong></div>
                <div class="weui-dialog__bd"></div>
                <div class="weui-dialog__ft">
                    <a href="javascript:;" class="weui-dialog__btn weui-dialog__btn_default">取消</a>
                    <a href="javascript:;" class="weui-dialog__btn weui-dialog__btn_primary">确定</a>
                </div>
            </div>
        </div>
		<div id="loadingToast" style="display:none;">
			<div class="weui-mask_transparent"></div>
			<div class="weui-toast">
				<i class="weui-loading weui-icon_toast"></i>
				<p class="weui-toast__content">数据加载中</p>
			</div>
		</div>
		<div class="js_dialog" id="iosDialog2" style="display: none;">
            <div class="weui-mask"></div>
            <div class="weui-dialog">
                <div class="weui-dialog__bd"></div>
                <div class="weui-dialog__ft">
                    <a href="javascript:;" class="weui-dialog__btn weui-dialog__btn_primary">知道了</a>
                </div>
            </div>
        </div>
		<div>
			 <div class="model cursor" style="display:none"></div>
            <volist name="card_list" id="voo" key="key1">
             <div class="id_{pigcms{$voo.card_id} model1"">
				 <div class="flex h45" style="padding:0px 15px;align-items: center;">
					 <img src="{pigcms{$static_path}images/new_my/close_tip.png" style="width:25px;height:25px" class="cursor pic_del"/>
					 <div style="flex:1" class="center">选择优惠</div>
                     <div class="notuse" data-bind_id="{pigcms{$voo.card_id}" style="color:#999"><if condition="empty($voo['discounts'])">暂无优惠<else/>暂不使用</if></div>
				 </div>
				 <div style="height:255px;overflow-y: scroll">
                     <volist name="voo.discounts" id = "row" key="key2">
                         <div class="weui-cells weui-cells_radio"  data-bind_id="{pigcms{$voo.card_id}" data-discount_id="{pigcms{$row.id}" style="margin-top:0px">
                                 <label class="weui-cell weui-check__label" for="y{pigcms{$voo.card_id}-{pigcms{$row.id}" style="padding-left:10px">
                                     <div class="weui-cell__ft">
                                         <span class="left_title"> <if condition="$row['type'] eq 2">立减<elseif condition="$row.type eq 1" />折扣<else/>满减</if></span>
                                     </div>
                                     <div class="weui-cell__bd" style="padding-left:10px">
                                         <if condition="$row.type eq 0">
                                             <h4 class="weui-media-box__title t_{pigcms{$voo.card_id}{pigcms{$row.id}">满{pigcms{$row.reach_money|floatval}减{pigcms{$row.reduce_money|floatval}</h4>
                                             <elseif condition="$row.type eq 1" />
                                             <h4 class="weui-media-box__title t_{pigcms{$voo.card_id}{pigcms{$row.id}"><neq name="row.reach_money" value="0">满{pigcms{$row.reach_money|floatval}享受</neq>{pigcms{$row['discount']/10}折优惠</h4>
                                             <elseif condition="$row.type eq 2" />
                                             <h4 class="weui-media-box__title t_{pigcms{$voo.card_id}{pigcms{$row.id}"><neq name="row.reach_money" value="0">满{pigcms{$row.reach_money|floatval}</neq>随机立减</h4>
                                         </if>
                                         <if condition="$row.card_limit_num eq -1 AND $row.card_can_use eq -1">
                                             <p class="weui-media-box__desc">优惠<span style="color:red;">可用</span></p>
                                             <elseif condition="$row.card_limit_num eq -1 AND $row.card_can_use eq 0" />
                                             <p class="weui-media-box__desc">优惠<span style="color:red;">不可用</span></p>
                                             <elseif condition="$row.card_limit_num gt 0" />
                                             <p class="weui-media-box__desc">
                                                 <eq name="row.cycle_type" value="0">时间<span style="color: red;">不限</span>，当前银行卡还可享受<span style="color: red;">{pigcms{$row.card_can_use}</span>次</eq>
                                                 <eq name="row.cycle_type" value="1">每月优惠<span style="color: red;">{pigcms{$row.card_limit_num}</span>次，当前银行卡还可享受<span style="color: red;">{pigcms{$row.card_can_use}</span>次</eq>
                                                 <eq name="row.cycle_type" value="2">每周优惠<span style="color: red;">{pigcms{$row.card_limit_num}</span>次，当前银行卡还可享受<span style="color: red;">{pigcms{$row.card_can_use}</span>次</eq>
                                                 <eq name="row.cycle_type" value="3">每日优惠<span style="color: red;">{pigcms{$row.card_limit_num}</span>次，当前银行卡还可享受<span style="color: red;">{pigcms{$row.card_can_use}</span>次</eq>
                                             </p>
                                         </if>
                                     </div>
                                     <div class="weui-cell__ft ">
                                         <input type="radio" class='weui-check radio_in <if condition="$key1 eq 1 AND $key2 eq 1"> choosen_one</if>' name="radio{pigcms{$voo.card_id}" data-card_id="{pigcms{$voo.card_id}" data-discount_id="{pigcms{$row.id}" id="y{pigcms{$voo.card_id}-{pigcms{$row.id}">
                                         <span class="weui-icon-checked"></span>
                                     </div>
                                 </label>
                            </div>
                         </volist>
                    </div>
				 </div>
            </volist>
			 <div class="model2">
				 <div class="flex h45" style="padding:0px 15px;align-items: center;">
					 <img src="{pigcms{$static_path}images/new_my/close_tip.png" style="width:25px;height:25px" class="cursor pic_del"/>
					 <div style="flex:1" class="center">添加银行卡</div>
                 </div>
                 <div style="height:255px;overflow-y: scroll">
                     <div class="weui-panel__bd border-t rel">
                         <a onclick="location.href='{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}'" href="{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}" class="weui-media-box weui-media-box_appmsg">
                             <div class="weui-media-box__bd">
                                 <h4 class="weui-media-box__title">使用新卡支付</h4>
<!--                                 <p class="weui-media-box__desc">无需网银</p>-->
                             </div>
                             <div class="weui-cell__ft showModel1 " style="padding:0px">
<!--                                 <span class="mast_del ">单单减最高99</span>-->
                             </div>
                             <div class="weui-cell_access">
                                 <div class="weui-cell__ft">
                                 </div>
                             </div>
                         </a>
                     </div>
                     <volist name="discountss" id="discount" key="kk">
                         <div class="weui-panel__bd border-t rel">
                             <a onclick="location.href='{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}'" href="{pigcms{:U('add',array('order_id'=>$now_order['allinpay_rand_id'],'mer_id'=>$now_order['mer_id']))}" class="weui-media-box weui-media-box_appmsg">
                                 <div class="weui-media-box__bd">
                                     <h4 class="weui-media-box__title">{pigcms{$discount.bank_desc}</h4>
                                     <p class="weui-media-box__desc"><if condition="$discount.banks_len eq -1">全部银行卡可用<else/>含{pigcms{$discount.banks_len}种银行卡</if></p>
                                 </div>
                                 <div class="weui-cell__ft" style="padding:0px">
                                     <if condition="$discount.type eq 0">
                                         <span class="mast_dell">满{pigcms{$discount.reach_money|floatval}减{pigcms{$discount.reduce_money|floatval}</span>
                                         <elseif condition="$discount.type eq 1" />
                                         <span class="mast_dell"><neq name="row.reach_money" value="0">满{pigcms{$discount.reach_money|floatval}享受</neq>{pigcms{$discount['discount']/10}折优惠</span>
                                         <elseif condition="$discount.type eq 2" />
                                         <span class="mast_dell"><neq name="row.reach_money" value="0">满{pigcms{$discount.reach_money|floatval}</neq>随机立减</span>
                                     </if>
                                 </div>
                                 <div class="weui-cell_access">
                                     <div class="weui-cell__ft">
                                     </div>
                                 </div>
                             </a>
                         </div>
                     </volist>
                 </div>
             </div>
        </div>
        <input type="hidden" id="choose_card">
        <input type="hidden" id="choose_discount" value="0">
      </div>
		<div class="band_car" <if condition="empty($card_list)">style="display:none;"</if>>
           <p class="flex" style="padding: 5px 10px;background: #FDF6EC;color:#D4963F;">
               <span >订单金额：{pigcms{$now_order.order_price}{pigcms{$config['Currency_txt']}</span>
               <span style="flex:1;padding-left: 15px;" id="discount_money"></span>
           </p>
           <div class="flex" style="  align-items: center;">
               <span style="flex:3; font-size: 18px;color: #e4393c;padding-left: 10px;" id="paid_money">¥{pigcms{$now_order.order_price}{pigcms{$config['Currency_txt']}</span>
               <button class="weui-btn weui-btn_default pay" style="width:auto;flex:2" >去支付</button>
           </div>

		</div>
		{pigcms{$hideScript}
		<script type="text/javascript" src="{pigcms{$static_path}js/allinpay.js?2112222" charset="utf-8"></script>
	<script>
        var order_price = "{pigcms{$now_order.order_price}";
        $(document).ready(function(){
            if(typeof($('.choosen_one'))!="undefined"){
                var choose_card = $('.choosen_one').data('card_id');
                var choose_disc = $('.choosen_one').data('discount_id');
                $('#choose_card').val(choose_card);
                $('#choose_discount').val(choose_disc);
                var title = $('.t_' + choose_card + choose_disc).text();
                $('.c_' + choose_card).find('.mast_del').text(title);
                $('.choosen_one').prop("checked",true);
                $('.p_' + choose_card).prop('checked',true);
                query_discount();
            }
            $(".showModel1").on("click",function(e){
                e.preventDefault();
                e.stopPropagation();
                $(".model").show();
                var id = $(this).data('bind_id');
                $(".id_" + id).addClass("active");
            })
            $(".model,.pic_del").click(function(){
                $(".model").hide();
                $(".model1,.model2").removeClass("active");
            })
            $(".add_card,.card_add").on("click",function(){
                if(discountss==''){
                    location.href=$(this).data('href');
                }else{
                    $(".model").show();
                    $(".model2").addClass("active");
                }
            });

            $(".weui-cells_radio>div").on("click",function(e){
                e.preventDefault();
                e.stopPropagation();
                $(".radio").prop("checked",false);
                $(".radio_in").prop("checked",false);
                $(this).find('input').prop("checked",true);
                $('#choose_card').val($(this).data('card_id'));
                $('.mast_del').text('请选择优惠');
                $('#choose_discount').val(0);
                $('#discount_money').text('');
                $('#paid_money').text( '¥'+ order_money + '{pigcms{$config['Currency_txt']}');
            });
            $('.radio_in').on('change',function() {
                if($(this).prop('checked')==true){
                    $(".radio_in").prop("checked",false);
                    $(this).prop('checked',true);
                    $(".radio").prop("checked",false);
                    $('.p_' + $(this).data('card_id')).prop('checked',true);
                    var title = $('.t_' + $(this).data('card_id')+$(this).data('discount_id')).text();
                    $('.mast_del').text('请选择优惠');
                    $('.c_' + $(this).data('card_id')).find('.mast_del').text(title);
                    $('#choose_card').val($(this).data('card_id'));
                    $('#choose_discount').val($(this).data('discount_id'));
                    $(".model").hide();
                    $(".model1").removeClass("active");
                    query_discount();
                }
            });
            $('.notuse').click(function(){
                $('.mast_del').text('请选择优惠');
                $('.c_' + $(this).data('bind_id')).find('.mast_del').text('不使用优惠');
                $('.p_' + $(this).data('bind_id')).prop('checked',true);
                $('#choose_card').val($(this).data('bind_id'));
                $(".radio_in").prop("checked",false);
                $('#choose_discount').val(0);
                $(".model").hide();
                $(".model1").removeClass("active");
            });
        });
        function query_discount(){
                    var post_data = {};
                    var discount_id = $('#choose_discount').val();
                    var bind_id = $('#choose_card').val();
                    post_data.bind_id = bind_id;
                    post_data.discount_id = discount_id;
                    post_data.order_id = order_id;
                    $.post(query_url,post_data,function(result){
                        if(result.status=='0'){
                            layer.open({
                                content: result.info
                                ,skin: 'msg'
                                ,time: 2
                            });
                            $('#discount_money').text('');
                            $('#paid_money').text( '¥'+ order_money + '{pigcms{$config['Currency_txt']}');
                        }else{
                            if(result.discount_type=='0'){
                                $('#discount_money').text( '优惠金额：'+ result.discount_money + '{pigcms{$config['Currency_txt']}');
                                $('#paid_money').text( '¥'+ result.paid_money + '{pigcms{$config['Currency_txt']}');
                            }else if(result.discount_type=='1'){
                                if(result.discount_status=='normal'){
                                    $('#discount_money').text( '优惠金额：'+ result.discount_money + '{pigcms{$config['Currency_txt']}');
                                }else{
                                    $('#discount_money').text( '折扣最高优惠金额：'+ result.discount_money + '{pigcms{$config['Currency_txt']}');
                                }
                                $('#paid_money').text( '¥'+ result.paid_money + '{pigcms{$config['Currency_txt']}');
                            }else if(result.discount_type=='2'){
                                $('#discount_money').text( '优惠区间：'+ result.min_money + '~' + result.max_money + '{pigcms{$config['Currency_txt']}');
                                $('#paid_money').text( '¥'+ result.paid_money + '{pigcms{$config['Currency_txt']}');
                            }
                        }
                    },'json');
                }
	</script>
	</body>
</html>