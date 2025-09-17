<!DOCTYPE html>
<html lang="en">
<head>
    <title>{pigcms{$config.gift_alias_name}订单</title>
<include file="Public:gift_header" />
	<style>
		.boxs {
			margin: 15px 0;
			border-width: 1px 0 0;
			background-color: #fff;
		}
		.pay_wrap .boxs.deliver_pick {
			background-color: #f4f4f4;
			margin-top: 0px;
		}
		.pay_wrap .boxs {
			margin: 10px 0;
		}
		.boxs li {
			border: none;
		}

		.boxs li {
			padding: 10px 15px;
			border-width: 0 0 1px;
			line-height: 24px;
		}
		.boxs .delivery_type {
			margin-top: 10px;
			border-left: 5px solid #FF7A3F;
			background: white;
			padding: 10px 0px 10px 10px;
			-webkit-border-image: none;
		}

		.boxs li a {
			min-height: 24px;
		}
		.boxs li a {
			min-height: 24px;
			font-size: 14px;
			display: inline-block;
		}

		.pick_in_store {
			background: #FF7A3F;
			color: white;
		}
		.btn_express {
			padding: 6px 10px;
			border: 1px solid #FF7A3F;
			border-radius: 2px;
			margin-right: 10px;
		}
		.boxs li#li_delivery {
			margin-top: 10px;
			background: white;
		}
		.boxs li a strong {
			width: 100%;
		}

		.boxs li a > * {
			display: table-cell;
			vertical-align: middle;
		}
		strong, b {
			font-weight: normal;
		}
		#showName, #showTel {
			color: #666;
		}
		.boxs li a > * {
			display: table-cell;
			vertical-align: middle;
		}
		.pay_wrap .ico_arrow {
			vertical-align: -2px;
			margin-left: 10px;
		}


		.pick_in_store_click {
			background-color: #fff;
			color: #000;
			border: 1px solid #FF7A3F;
		}
		.weui-cells_form{
			background-color: #fff;
			line-height: 1.47058824;
			font-size: 14px;
			overflow: hidden;
			position: relative;
			margin-top: 0px;
		}
		.weui-cells_form input{
			width: 100%;
			border: 0;
			outline: 0;
			-webkit-appearance: none;
			background-color: transparent;
			color: inherit;
			height: 1.47058824em;
			line-height: 1.47058824;
			font-size: 14px !important;
		}
		.weui-cell{
			padding: 0px 15px;
			position: relative;
			display: -webkit-box;
			display: -webkit-flex;
			display: flex;
			-webkit-box-align: center;
			-webkit-align-items: center;
			align-items: center;
		}
		.weui-cell__hd label{
			display: block;
			width: 105px;
			word-wrap: break-word;
			word-break: break-all;
		}
		.weui-cells:after {
			bottom: 0;
			border-bottom: 1px solid #e5e5e5;
			-webkit-transform-origin: 0 100%;
			transform-origin: 0 100%;
			-webkit-transform: scaleY(.5);
			transform: scaleY(.5);
		}
		.weui-cells:after, .weui-cells:before {
			content: " ";
			position: absolute;
			left: 0;
			right: 0;
			height: 1px;
			color: #e5e5e5;
			z-index: 2;
		}
		
		.deliver_time_box{
			display:none;
		}
		.deliver_time_box .mask{
		    position: fixed;
		    top: 0;
		    left: 0;
		    height: 100%;
		    background: rgba(0,0,0,.6);
		    width: 100%;
		    z-index: 1001;
		}
		.deliver_time_box .timer{
		    position: fixed;
		    bottom: 0;
		    height: 70%;
		    width: 100%;
		    background: white;
		    z-index: 1002;
		}
		.deliver_time_box .timer .header {
			height:50px;
			line-height:50px;
			text-align:center;
			border-bottom:1px solid #f1f1f1;
			color:black;
		}
		.deliver_time_box .content {
			display:flex;
		}
		.deliver_time_box .timer .day{
		    height:100%;
		    flex:30%;
		    background:#F2F2F2;
			overflow-y:auto;
		}
		.deliver_time_box .timer .day .item{
		    text-align: center;
		    height:50px;
		    line-height:50px;
		    background:#F2F2F2;
		    color:#666;
		}
		.deliver_time_box .timer .day .item.active{
		    background:white;
		    color:#fe5842;
		}
		.deliver_time_box .timer .time{
		    height:100%;
		    padding-left:15px;
			flex:70%;
			overflow-y:auto;
		}
		.deliver_time_box .timer .time .item_box{
		    
		}
		.deliver_time_box .timer .time .item{
		    height:50px;
		    line-height:50px;
		    position:relative;
		}
		.deliver_time_box .timer .time .item .price{
		    margin-left:4px;
		    color:#999;
		}
		
		.deliver_time_box .timer .time .item.active:after{
		    display: block;
		    content: "";
		    width:20px;
		    height:20px;
		    background:url(../images/wxapp_time_select.png) no-repeat;
		    background-size:20px;
		    position:absolute;
		    right:15px;
		    top:16px;
		}
		
		.pay_wrap .box.coupon_list,.pay_wrap .box.markbox{
			border:none;
		}
		.deliver_time_box .content{
			display: flex;
			margin-top: 50px;
		}
		.btn_express{
			padding: 4px 14px;
			margin-top: 10px;
		}
		.pick_in_store{
			color: #fff;
		}
		 
		
	</style>
</head>
<script type="text/javascript" language="javascript">
		var exchange_type = {pigcms{$_GET['exchange_type']};
		var exchange_limit_num = {pigcms{$gift_detail.exchange_limit_num};
		var payment_pure_integral = {pigcms{$gift_detail['payment_pure_integral']};
		var payment_integral = {pigcms{$gift_detail['payment_integral']};
		var payment_money = {pigcms{$gift_detail['payment_money']};
		if(exchange_limit_num){
			var total_sku = exchange_limit_num;
		}else{
			var sku = parseInt({pigcms{$gift_detail.sku});
			var sale_count = parseInt({pigcms{$gift_detail.sale_count})
			var total_sku = sku - sale_count;
		}
	</script>
<body>

<form action="{pigcms{:U('buy')}" method="post" id="forms" onSubmit="return chk_submit()">
<section class="order">
	<?php if(!$gift_detail['coupon_id']){ ?>
	<section class="pay_wrap">
		<ul class="boxs deliver_pick">
			<li class="delivery_type">
				<a class="ico">配送方式：</a>&nbsp;&nbsp;
				<br/>
                <?php if($gift_detail['mer_id']>0){ ?>
                    <a class="btn_express pick_in_store"/>商家自提</a>
                <?php }else{ ?>
                    <if condition="in_array('0',$delivery_type)">
                        <input type="hidden" name="now_delivery_type" value="0">
                        <a class="btn_express <if condition='$_GET["pick_id"] || $now_delivery_type gt 0'>pick_in_store_click<else/>pick_in_store</if>" id="post_package">快递配送</a>
                    </if>
                    <if condition="in_array('1',$delivery_type)">
                        <input type="hidden" name="now_delivery_type" value="1">
                        <a class="btn_express <if condition='$_GET["pick_id"] || $now_delivery_type eq 1'>pick_in_store<else/>pick_in_store_click</if>" id="pick_in_store"/> 门店自提</a>
                    </if>
                    <if condition="in_array('2',$delivery_type) and C('config.open_plat_deliver') eq 1">
                        <input type="hidden" name="now_delivery_type" value="2">
                        <a class="btn_express <if condition='!$_GET["pick_id"] && $now_delivery_type eq 2'>pick_in_store<else/>pick_in_store_click</if>" id="post_plat">平台配送</a>
                    </if>
                <?php }?>
			</li>
			<li id="li_delivery"  <if condition="$now_delivery_type eq 1 || $_GET['pick_id']"><else/>style="display:none"</if>>
				<a href="{pigcms{:U('Gift/order_adress',array('gift_id'=>$_GET['gift_id'],'current_id'=>$gift_pick_address['pick_id'],'now_sku'=>$_GET['now_sku'],'exchange_type'=>$_GET['exchange_type']))}">
					<strong>
						<span id="showAddres">省市区：{pigcms{$gift_pick_address['area_info']['province']} {pigcms{$gift_pick_address['area_info']['city']} {pigcms{$gift_pick_address['area_info']['area']} </span><br>
						<span id="">电话：{pigcms{$gift_pick_address['phone']}</span><br>
						<span id="">地址：{pigcms{$gift_pick_address['name']}</span><br>
						<if condition="$gift_pick_address['distance'] neq -1 " ><span id="" style="color:#3D974F">距离：{pigcms{$gift_pick_address['distance']}</span></if>
					</strong>
					<div><i class="ico_arrow"></i></div>
				</a>
			</li>		
		</ul>
	</section>
	<eq name="gift_detail.mer_id" value='0'>
    <section class="list-block">
        <ul>
        	<?php if($merchant_session){ ?>
        		 <li  id="li_system" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">style="display:none"</if>>
					<div class="weui-cells weui-cells_form" >
						<div class="weui-cell weui-cell_warn">
							<div class="weui-cell__hd"><label for="" class="weui-label">姓名</label></div>
							<div class="weui-cell__bd">
								<input class="weui-input" type="text"  name="name"  placeholder="请输入姓名" />
							</div>

						</div>
					</div>
					<div class="weui-cells weui-cells_form" >
						<div class="weui-cell weui-cell_warn">
							<div class="weui-cell__hd"><label for="" class="weui-label">手机号</label></div>
							<div class="weui-cell__bd">
								<input class="weui-input" type="tel"  name="phone"  placeholder="请输入手机号" />
							</div>

						</div>
					</div>
					<div class="weui-cells weui-cells_form" >
						<div class="weui-cell weui-cell_warn" >
							<div class="weui-cell__hd"><label for="" class="weui-label">地址</label></div>
							<div class="weui-cell__bd" style="flex:1">
								<textarea class="weui-textarea input" placeholder="请输入地址" name="address" rows="3" style="height: 3rem;width:100%;font-size: 14px !important;    padding-top: 20px;"></textarea>
							</div>

						</div>
					</div>

	            </li>
        	<?php }else{ ?>
				<if condition="!empty($now_user_adress)">
		            <li  id="li_system" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">style="display:none"</if>>
		                <a href="javascript:void(0);" data-href="{pigcms{:U('My/adress',array('gift_id'=>$_GET['gift_id'],'now_delivery_type'=>$now_delivery_type,'current_id'=>$now_user_adress['adress_id'],'now_sku'=>$_GET['now_sku'],'exchange_type'=>$_GET['exchange_type']))}" class="item-link item-content a_link">
		                    <div class="item-inner">
		                        <div class="item-title">请点击选择送货地址</div>
		                    </div>
		                </a>
		            </li>
				<else />
					<li id="li_system" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">style="display:none"</if>>
		                <a href="javascript:void(0);" data-href="{pigcms{:U('My/adress',array('gift_id'=>$_GET['gift_id'],'now_delivery_type'=>$now_delivery_type,'current_id'=>$now_user_adress['adress_id'],'now_sku'=>$_GET['now_sku'],'exchange_type'=>$_GET['exchange_type']))}" class="item-link item-content a_link">
		                    <div class="item-inner">
		                        <div class="item-title">请点击添加送货地址</div>
		                    </div>
		                </a>
		            </li>
				</if>
        	<?php } ?>
        </ul>
    </section>
	</eq>
	
		<!--<section class="address" <if condition='!empty($_GET["address_id"])'>style="display:none"<else />style="display:block"</if>>
			<ul>
				<li>
					<div class="wrap">
						<div class="textDesc">
							<div class="rowCell">
								<span class="fr">{pigcms{$now_user_adress['phone']}</span>
								<span>收货人：{pigcms{$now_user_adress['name']}</span>
							</div>
							<div class="rowCell">
								收货地址：{pigcms{$now_user_adress['province_txt']} {pigcms{$now_user_adress['city_txt']} {pigcms{$now_user_adress['area_txt']} {pigcms{$now_user_adress['adress']} {pigcms{$now_user_adress['detail']}
							</div>
						</div>
					</div>
				</li>
			</ul>
		</section>-->
		
		<!-- <section class="address" <if condition='empty($_GET["address_id"])'>style="display:none"<else />style="display:block"</if>> -->
		<section class="address" <if condition="$now_delivery_type eq 1 || $_GET['pick_id'] || $gift_detail['mer_id'] gt 0">style="display:none"</if>>
			<if condition="!empty($now_user_adress)">
			<ul >
				<li>
					<div class="wrap">
						<div class="textDesc">
							<div class="rowCell">
								<span class="fr">{pigcms{$now_user_adress['phone']}</span>
								<span>收货人：{pigcms{$now_user_adress['name']}</span>
							</div>
							<div class="rowCell">
								收货地址：{pigcms{$now_user_adress['province_txt']} {pigcms{$now_user_adress['city_txt']} {pigcms{$now_user_adress['area_txt']} {pigcms{$now_user_adress['adress']} {pigcms{$now_user_adress['detail']}
							</div>
						</div>
					</div>
				</li>
			</ul>
			</if>
		</section>
	<?php } ?>
    <section class="orderDetails">
        <div class="item orderItem">
            <div class="wrap clearfix">
                <div class="fl i-pic">
                    <img src="{pigcms{$gift_detail['wap_pic_list'][0]['url']}">
                </div>
                <div class="ofh desc">
                    <div class="wrap pr">
                        <h2>{pigcms{$gift_detail['gift_name']}</h2>
						
						<if condition='$_GET["exchange_type"] eq 0'>
							<div class="ofh">
								<p>{pigcms{$gift_detail['payment_pure_integral']} <em>{pigcms{$config['score_name']}</em></p>
							</div>
						<elseif condition='$_GET["exchange_type"] eq 1'/>
							<div class="ofh">
								<p>{pigcms{$gift_detail['payment_integral']} <em>{pigcms{$config['score_name']}</em> + {pigcms{$gift_detail['payment_money']} <em>{pigcms{$config['Currency_txt']}</em></p>
							</div>
						</if>
                    </div>
                </div>
            </div>
        </div>
        <div class="numCtrl">
            <div class="wrap clearfix">
                <div class="jf-bidNum fr">
                    <!--<span class="reduce">-</span>--><input type="text" value="{pigcms{$_GET['now_sku']}" id="now_sku" readonly="true"><!--<span class="plus">+</span>-->
                </div>
                <span class="odTit">数量</span>
            </div>
        </div>
		<?php if(!$gift_detail['coupon_id']){ ?>
        <div class="orderRow" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">style="display:none"</if>>
		 
            <ul class=" deliver_time">
				<li id="show_arrive_time">
				    <div class="wrap" id="timeBtn">
				        <p class="fr" style="color: #fe5842;"> <span id="arriveTime"> {pigcms{$dates.0.show_date} {pigcms{$dates.0.date_list.0.show_hour_minute}</span></p>
				        <span>送达时间</span>
				    </div>
				</li>
				<li id="show_delivery_fee_box">
				    <div class="wrap" >
				        <p class="fr" style="color: #fe5842;" id="show_delivery_fee"> <span  >{pigcms{$config['Currency_symbol']}{pigcms{$dates.0.date_list.0.delivery_fee}</span></p>
				        <span>配送费</span>
				    </div>
				</li>
                <li>
                    <div class="wrap">
                        <p class="fr pingPei">快递（免邮)</p>
                        <span>配送方式</span>
                    </div>
                </li>
            </ul>
        </div>
		<div class="orderRow deliverTime" <if condition="$now_delivery_type eq 1 || $_GET['pick_id'] || $gift_detail['mer_id'] gt 0">style="display:none"</if>>
	        <ul>
	            <li>
	                <div class="wrap">
						<p>配送时间</p>
	                    <p class="radios">
							<label><input type="radio" name="delivery_type" value="1" checked="checked"/>&nbsp;&nbsp;&nbsp;&nbsp;<span>工作日、双休日与假日均可送货</span></label>
							<label><input type="radio" name="delivery_type" value="2" />&nbsp;&nbsp;&nbsp;&nbsp;<span>只工作日送货</span></label>
							<label><input type="radio" name="delivery_type" value="3" />&nbsp;&nbsp;&nbsp;&nbsp;<span>双休日、假日送货：周六至周日</span></label>
							<label><input type="radio" name="delivery_type" value="4" />&nbsp;&nbsp;&nbsp;&nbsp;<span>白天没人，其它时间送货</span></label>
						</p>
	                </div>
	            </li>
	        </ul>
	    </div>
	    <gt name="gift_detail.mer_id" value='0'>
	    	<div class="orderRow">
	    		<ul>
	                <li>
	                    <div class="wrap">请支付后到商家{pigcms{$gift_detail.mer_name}（联系电话:{pigcms{$gift_detail.phone}）领取，
	                    </div>
	                </li>
	            </ul>
	    	</div>	    	
	    </gt>
		<?php } ?>
    </section>
</section>

<section class="ftHeight"></section>
<footer class="deatilFtBtn">
    <div class="number tc fl">
        <span class="dib tit">合计：</span><div class="jf-bidNum dib">
		<if condition='$_GET["exchange_type"] eq 0'>
			<p><font class="payment_pure_integral">{pigcms{$gift_detail['payment_pure_integral'] * $_GET['now_sku']}</font> <em>{pigcms{$config['score_name']}</em></p>
		<elseif condition='$_GET["exchange_type"] eq 1' />
			<p><font class="payment_integral">{pigcms{$gift_detail['payment_integral'] * $_GET['now_sku']}</font> <em>{pigcms{$config['score_name']}</em> + <font class="payment_money">{pigcms{$gift_detail['payment_money'] * $_GET['now_sku']}</font> <em>{pigcms{$config['Currency_txt']}</em></p>
		</if>
        
        </div>
    </div>

    <div class="buyNow fl">
		<input type="hidden" name="exchange_type" value="{pigcms{$_GET['exchange_type']}" />
		<input type="hidden" name="gift_id" />
		<input type="hidden" name="address_id" id="address_id" />
		<input type="hidden" name="pick_id" id="pick_id"/>
		<input type="hidden" name="coupon_id" id="coupon_id" value="{pigcms{$gift_detail['coupon_id']}" />
		<input type="hidden" id="is_pick" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">value="1"</if>/>
		<input type="hidden" id="is_pick_in_store" name="is_pick_in_store" <if condition="$now_delivery_type eq 1 || $_GET['pick_id']">value="1"<elseif condition="$now_delivery_type eq 2"/>value="2"</if>/>
		<input type="hidden" name="num" />
		<input type="hidden" name="memo" value="{pigcms{$_GET.memo}"/>

		<input type="hidden" id="oarrivalDate" name="oarrivalDate" value="{pigcms{$dates.0.ymd}">
	  	<input type="hidden" id="oarrivalTime" name="oarrivalTime" value="{pigcms{$dates.0.date_list.0.hour_minute}">
	  	<input type="hidden" id="delivery_fee" name="delivery_fee" value="">

		<button type="button" id="submits">立即兑换</button>
    </div>
</footer>
</form>

<div class="deliver_time_box">
	<div class="mask"></div>
	<div class="timer">
		<div class="header">期望送达</div>
		<div class="content">
			<div class="day">
				<volist name="dates" id="vo">
					<div class="item <if condition="$i eq 1">active</if>" data-date="{pigcms{$vo.ymd}">{pigcms{$vo.show_date}</div>
				</volist>
			</div>
			<div class="time" style="-webkit-overflow-scrolling:touch;">
				<volist name="dates" id="vo">
					<div class="item_box timer-{pigcms{$vo.ymd}" <if condition="$i neq 1">style="display:none;"</if>>
						<volist name="vo['date_list']" id="voo" key="k">
							<div class="item <if condition="$i eq 1 && $k eq 1">active</if>" data-show_date="{pigcms{$vo.show_date}" data-ymd="{pigcms{$vo.ymd}" data-time="{pigcms{$voo.show_hour_minute}" data-delivery_fee="{pigcms{$voo.delivery_fee_old}">{pigcms{$voo.show_hour_minute}<span class="price">({pigcms{$voo.delivery_fee_old}{pigcms{$config['Currency_txt']}配送费)</span></div>
						</volist>
					</div>
				</volist>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" language="javascript">
    var currency_symbol = "{pigcms{$config.Currency_symbol}";
</script>
<include file="Public:gift_footer" />
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
<script type="text/javascript" language="javascript">
	//快递配送
	if($('#post_package').hasClass('pick_in_store')){
		$('#show_arrive_time').hide();
        $('#show_delivery_fee_box').hide();
        $('#show_delivery_fee').hide();
	}
	$('#post_package').click(function(){
		$(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
		$('#li_system').show();
		$('.orderRow').show();
		$('.address').show();
		$('#li_delivery').css('display', 'none');
		$('#is_pick').val(0);
		$('#is_pick_in_store').val(0);
		$('.pingPei').html('快递（免邮)')
		$('.deliverTime').show()
		$('#show_arrive_time').hide()
        $('#show_delivery_fee_box').hide();
		$('#show_delivery_fee').hide()
	});
	//自提点配送
	$('#pick_in_store').click(function(){
		$(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
		$('#li_system').hide(); 
		$('.orderRow').hide();
		$('.address').hide();
		$('#li_delivery').show();
		$('#is_pick').val(1);
		$('#is_pick_in_store').val(1); 
		$('.deliverTime').hide()
		$('#show_arrive_time').hide();
	});
	//平台配送
	$('#post_plat').click(function(){
		$(this).removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
		$('#li_system').show();
		$('.orderRow').show();
		$('.address').show(); 
		$('#li_delivery').css('display', 'none');
		$('#is_pick').val(0);
		$('#is_pick_in_store').val(2);
		$('.pingPei').html('平台配送');
		$('.deliverTime').hide();
		$('#show_arrive_time').show();
        $('#show_delivery_fee_box').show();
        $('#show_delivery_fee').show();
	});

	var lock = false;
	$('#submits').click(function(){
		var is_pick_in_store = $('#is_pick_in_store').val(); 
		var now_user_address_id = "{pigcms{$now_user_adress['adress_id']}";
		if(is_pick_in_store == 2 && !now_user_address_id){
			
			layer.open({
				content: '当前暂无可配送的地址或选择的地址不在配送范围内！',
				btn: [ '取消'],
				shadeClose: true,
			});
			return false;
		}
		layer.open({
			content: '确认进行兑换？',
			btn: ['确认', '取消'],
			shadeClose: true,
			yes: function(){
				if (lock) {
					return false;
				}
				lock = true;

				var is_pick = $('#is_pick').val();
				var __is_pick_in_store = $('#is_pick_in_store').val();
				var delivery_fee = $('#delivery_fee').val();
				if (is_pick==0) {
					var address_id = "{pigcms{$_GET['adress_id']}";
					if(!address_id){
						address_id = "{pigcms{$now_user_adress['adress_id']}";
					}
					$('input[name="address_id"]').val(address_id);
					if(__is_pick_in_store == 2 && delivery_fee == ''){
						//平台配送

						$('#delivery_fee').val('{pigcms{$dates.0.date_list.0.delivery_fee}')
					}
				}else{
					var pick_id = "{pigcms{$_GET['pick_id']}";
					if(!pick_id){
						pick_id = "{pigcms{$gift_pick_address['pick_id']}";
					}
					$('input[name="pick_id"]').val(pick_id);
				}

				if(__is_pick_in_store == 1 && pick_id < 1){
					layer.open({
						content: '请先选择门店自提地址！',
						btn: [ '取消'],
						shadeClose: true,
					});
					lock = false;
					return false;
				}
				
				
				var num = $('#now_sku').val();
				$('input[name="num"]').val(num);
				$('input[name="memo"]').val("{pigcms{$_GET['memo']}")
				$('input[name="gift_id"]').val("{pigcms{$_GET['gift_id']}");
				$('#forms').removeAttr('onSubmit');
				
				$('#forms').submit();
			}
		});
		return false;
	});

	$(".a_link").click(function(){
		var url = $(this).attr('data-href');
		var pick_deliver = $('#is_pick_in_store').val();//哪一个配送方式是选中的
		var turn_url = url+'&pick_deliver='+pick_deliver+"&now_delivery_type="+$('input[name="now_delivery_type"]').val();
		window.location.href = turn_url;
	});
	<?php if($_GET['pick_deliver']=='2'){?>
		$('#post_plat').removeClass('pick_in_store_click').addClass('pick_in_store').siblings('.btn_express').removeClass('pick_in_store').addClass('pick_in_store_click');
		$('#li_system').show();
		$('.orderRow').show();
		$('.address').show(); 
		$('#li_delivery').css('display', 'none');
		$('#is_pick').val(0);
		$('#is_pick_in_store').val(2);
		$('.pingPei').html('平台配送');
		$('.deliverTime').hide();
		$('#show_arrive_time').show();
        $('#show_delivery_fee_box').show();
        $('#show_delivery_fee').show();
	<?php } ?>
	// 获取地址栏参数
    function GetQueryString(name)
    {
        var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if(r!=null)return unescape(r[2]);
        return null;
    }

	$('.plus').click(function(){
		var now_num = $('#now_sku').val();
        if(now_num >= total_sku){
            return;
        }
		now_num++;
		$('#now_sku').val(now_num);
        var exchange_type = GetQueryString("exchange_type");
        if (0 === exchange_type || '0' === exchange_type) {
            var price = parseInt("{pigcms{$gift_detail['payment_pure_integral']}");
            $('.payment_pure_integral').html(price * now_num);
        } else {
            var integral_price = parseInt("{pigcms{$gift_detail['payment_integral']}");
            var money_price = parseInt("{pigcms{$gift_detail['payment_money']}");
            var total_inegral = integral_price * now_num;
            var total_money = money_price * now_num;
            $('.payment_integral').html(total_inegral);
            $('.payment_money').html(total_money);
        }
	});
	
	
	$('.reduce').click(function(){
		var now_num = $('#now_sku').val();
		if(now_num > 1){
			now_num--;
			$('#now_sku').val(now_num);
            var exchange_type = GetQueryString("exchange_type");
            var price = parseInt("{pigcms{$gift_detail['payment_pure_integral']}");
            if (0 === exchange_type || '0' === exchange_type) {
                var price = parseInt("{pigcms{$gift_detail['payment_pure_integral']}");
                $('.payment_pure_integral').html(price * now_num);
            } else {
                var integral_price = parseInt("{pigcms{$gift_detail['payment_integral']}");
                var money_price = parseInt("{pigcms{$gift_detail['payment_money']}");
                var total_inegral = integral_price * now_num;
                var total_money = money_price * now_num;
                $('.payment_integral').html(total_inegral);
                $('.payment_money').html(total_money);
            }
		}
	});
	
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
        // bug =>【ID1054084】 选择收货地址修改为走h5页面 $('.a_link') 先注释掉这段代码 
		// $('.item-link.item-content').click(function(){
		// 	var address_id = $('#address_id').val() == '' ? '0' : $('#address_id').val(); 
		// 	if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
		// 		iosCommon.iosFunction('getUserAddress/'+address_id, {name:'getUserAddress', address_id: address_id})
		// 	}else{
		// 		window.lifepasslogin.getUserAddress(address_id);
		// 	}
		// 	return false;
		// });
	}
	
function callbackUserAddress(address){
	// alert(address);
	var addressArr = address.split('<>');
	$('#address_id').val(addressArr[0]);
	<php>
		$tmpGet = $_GET;
		unset($tmpGet['adress_id']);
	</php>
	// alert("{pigcms{:U('order',$tmpGet)}&adress_id="+addressArr[0]);
	window.location.href = "{pigcms{:U('order',$tmpGet)}&adress_id="+addressArr[0];
}
</script>

<script>
	    $('#show_arrive_time').click(function(){
			$('.deliver_time_box .mask').removeClass('hidden');
			$('.deliver_time_box').show();
		});
		$('.deliver_time_box .mask').click(function(){
			$('.deliver_time_box').hide();
		});
		
		$('.deliver_time_box .day .item').click(function(){
			$(this).addClass('active').siblings().removeClass('active');
			$('.timer-'+$(this).data('date')).show().siblings().hide();
			$('.deliver_time_box .time').scrollTop(0);
		});
		$('.deliver_time_box .time .item').click(function(){ 
		    $('.deliver_time_box .time .item').removeClass('active');
			$(this).addClass('active');
			$('#arriveTime').html($(this).data('show_date')+' '+$(this).data('time'));
			 
			$('#oarrivalDate').val($(this).data('ymd'));
			$('#oarrivalTime').val($(this).data('time'));
			$('#delivery_fee').val($(this).data('delivery_fee'));
			$('#show_delivery_fee strong').html('{pigcms{$config['Currency_symbol']}'+$(this).data('delivery_fee'));
			$('#show_delivery_fee span').html('{pigcms{$config['Currency_symbol']}'+$(this).data('delivery_fee'));
			
			$('#count_price').data('deliver', $(this).data('delivery_fee'));
	        var delivery_fee = $(this).data('delivery_fee');
	        if($('#minusDeliveryFee').text()>0){
	            var minusDeliveryFee = $('#minusDeliveryFee').text();
	            if(minusDeliveryFee >= $(this).data('delivery_fee')){
	                delivery_fee = 0;
	            }else{
	                delivery_fee = $(this).data('delivery_fee') - minusDeliveryFee;
	            }
	        }
	        var vipLevelReduceMoney = 0;
	        if($('#vipLevelReduceMoney').text()>0){
	            vipLevelReduceMoney = parseFloat($('#vipLevelReduceMoney').text());
	        }
	        var minus_price_delivery_fee = 0;
	        if($('#count_price').data('deliver')>0){
	            minus_price_delivery_fee =  $('#minus_price').data('deliver').toFixed(2);
	            $('#delivery_fee_reduce_money').show();
	            $('#delivery_fee_reduce_money').parent('li').show();
	        }else{
	            $('#delivery_fee_reduce_money').hide();
	            $('#delivery_fee_reduce_money').parent('li').hide();
	        }
	        var percent = parseFloat($('#count_price').data('percent'));
	        var payPrice = parseFloat(($('#count_price').data('price') + delivery_fee + $('#count_price').data('extprice') + $('#count_price').data('packing_charge')).toFixed(2)).toFixed(2);
	        var discountPrice = parseFloat(parseFloat($('#minus_price').data('price')) + parseFloat(minus_price_delivery_fee) + vipLevelReduceMoney).toFixed(2);
	
	
	        if (percent < 10) {
	            discountPrice = parseFloat((discountPrice + payPrice * (1 - percent * 0.1)).toFixed(2));
	            payPrice = parseFloat((payPrice * percent * 0.1).toFixed(2));
	        }
			if(payPrice < 0){
				payPrice = 0;
			}
	        $('#totalPrice_').html(payPrice);
	        $('#totalPrice_subtotal').html(payPrice);
	        $('#cartNum_').html(discountPrice);
	        
	//         $('#totalPrice_').html();
	        //TODO
			setTimeout(function(){
				$('.deliver_time_box').hide();
			},150);
		});
</script>
</body>
</html>